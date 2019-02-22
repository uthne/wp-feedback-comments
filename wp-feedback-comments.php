<?php
/*
Plugin Name: Feedback Comments
Plugin URI: http://fjuz.no
Description: Add "Was this article helpful?" at the end, start and/or at custom hook of any post type. The question replaces normal comments section.
Version: 1.3
Author: Kjetil Uthne Hansen
Author URI: 
Author Email: uthne@me.com
Text Domain: wp-feedback-comments
Domain Path: /languages
Credits:
    This plugin was based on Article Feedback by themeidol
    http://themeidol.com/
    The Font Awesome icon set was created by Dave Gandy (dave@davegandy.com)
    http://fontawesome.io

License:

    Copyright (C) 2016 Kjetil Uthne Hansen / Fjuz Kommunikasjon

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.

*/



class FeedbackComments {
    private static $instance;
    const VERSION = '1.0';

    private static function has_instance() {
        return isset( self::$instance ) && null != self::$instance;
    }

    public static function get_instance() {
        if ( !self::has_instance() ) {
            self::$instance = new FeedbackComments;
        }
        return self::$instance;
    }

    public static function setup() {
        self::get_instance();
    }

    protected function __construct() {
        if ( ! self::has_instance() ) {
            $this->init();
        }
    }

    public function init() {
        add_action( 'wp_enqueue_scripts', array( $this, 'register_plugin_styles' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'register_plugin_admin_styles' ) );
        add_shortcode( 'feedback_comment', array( $this, 'feedback_shortcode' ) );

        add_action( 'admin_menu', array( $this, 'register_submenu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );

        add_filter( 'the_content', array( $this, 'append_feedback_html' ) );
        add_action( 'fc_feedback_hook', array( $this, 'append_customhook_html' ) );
        register_activation_hook( __FILE__, array( $this, 'load_defaults' ) );

        add_action('wp_ajax_send_feedback', array( $this,'feedback_receiver'));
        add_action('wp_ajax_nopriv_send_feedback', array( $this,'feedback_receiver'));

        add_action( 'plugins_loaded', array($this,'feedback_load_textdomain') );

        //bulk action
        if(is_admin()) {
            add_action('admin_footer-edit-comments.php', array(&$this, 'custom_bulk_admin_footer'));
            add_action('load-edit-comments.php',         array(&$this, 'custom_bulk_action'));
            add_action('admin_notices',         		 array(&$this, 'custom_bulk_admin_notices'));
        }

        add_action( 'template_redirect', array( $this, 'filter_query' ), 9 );	// before redirect_canonical
    }	

    public function filter_query() {
        $feedback_options = $this->get_feedback_options('feedback_options');
        if( is_comment_feed() && $feedback_options['ss-stop-feed']=="yes" ) {
            wp_die( sprintf( __( 'No comment feed available, please visit our <a href="%s">homepage</a>!','wp-feedback-comments' ), get_bloginfo('url') ), '', array( 'response' => 403 ) );
        }
    }

    /**
    * Extract varibles from shortcode.
    * @since 1.0
    */
    public function feedback_shortcode( $atts ) {
        extract( shortcode_atts( array(
            'question' => '',
            'yes' => '',
            'no' => ''
        ), $atts ) );  
        $feedback_html_markup = $this->feedback_content($question,$yes,$no);
        echo ($feedback_html_markup);
    }

    /**
    * Feedback Plugin styles.
    * @since 1.0
    */
    public function register_plugin_styles() {
        global $wp_styles;
        $feedback_options = $this->get_feedback_options('feedback_options');
        $sendyes=$feedback_options['ss-send-yes'];
        $yestext=($feedback_options['ss-yes-text']!="")?$feedback_options['ss-yes-text']:__('Yes, I found what I was looking for','wp-feedback-comments');
        $fontsize=$feedback_options['ss-font-size'];
        $Upcolor=($feedback_options['ss-thumbs-up']!="")?$feedback_options['ss-thumbs-up']:'#FF3234';
        $Downcolor=($feedback_options['ss-thumbs-down']!="")?$feedback_options['ss-thumbs-down']:'#5C7ED7';

        if ($feedback_options['ss-load-font']=="yes") {
            wp_enqueue_style( 'font-awesome-styles', plugins_url( 'assets/css/font-awesome.min.css', __FILE__ ), array(), self::VERSION, 'all' );
        }

        $DefaultStyling=($feedback_options['ss-style-on']=="yes")?'assets/css/front-feedback-styles.css':'assets/css/front-feedback.css';
        wp_enqueue_style( 'feedback-front-styles', plugins_url( $DefaultStyling, __FILE__ ), array(), self::VERSION, 'all' );

        if ($feedback_options['ss-style-url']!="") {
            wp_enqueue_style( 'feedback-custom-styles', $feedback_options['ss-style-url'], array(), self::VERSION, 'all' );
        }

        wp_enqueue_script( 'feedback-front-script', plugins_url( 'assets/js/feedback-comments.js', __FILE__ ), array('jquery'), self::VERSION, 'all' );
        // declare the URL to the file that handles the AJAX request (wp-admin/admin-ajax.php)
        wp_localize_script( 'feedback-front-script', 'FeedbackAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ), 'sendyes' => $sendyes, 'yestext' => $yestext ) );

        if ($feedback_options['ss-style-on']=="yes") {
        wp_add_inline_style( 'feedback-front-styles', 'a.m-feedback-prompt__button.m-feedback-prompt__social.yes, a.m-feedback-prompt__button.m-feedback-prompt_form.no, m-feedback-prompt__button--active, a.m-feedback-prompt__button.m-feedback-prompt__social_thumbsdown.no {
                color: '.$Upcolor.';
                font-size:'.$fontsize.'em;
            }
            a.m-feedback-prompt__button.m-feedback-prompt_form.no, a.m-feedback-prompt__button.m-feedback-prompt__social_thumbsdown.no {
                color: '.$Downcolor.';
                font-size:'.$fontsize.'em;
            }' 
        );
        }
    }

    /**
    * Add custom css for admin section
    * @since 1.0
    */
    function register_plugin_admin_styles() {
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script('feedback-admin-custom-script', plugins_url( 'assets/js/feedback-comments-admin.js', __FILE__ ), array('jquery','wp-color-picker'), self::VERSION, 'all' );
        wp_localize_script( 'feedback-admin-custom-script', 'FeedbackAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
        wp_register_style( 'feedback-admin_css', plugin_dir_url(__FILE__) . '/assets/css/admin-feedback.css', false, $version );
        wp_enqueue_style( 'feedback-admin_css' );
    }

    /**
    * Load plugin textdomain.
    * @since 1.0
    */
    function feedback_load_textdomain() {
        load_plugin_textdomain( 'wp-feedback-comments', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' ); 
    }

    /**
    * Feedback Content.
    * @since 1.0
    */
    public function feedback_content($question_var='',$yes_var='',$no_var='') {
        global $post;
        $feedack_options = $this->get_feedback_options('feedback_options');
        $title_phrase=($feedack_options['ss-title-phrase']!="")?$feedack_options['ss-title-phrase']:__('Was this article helpful?','wp-feedback-comments');
        $feedback_phrase=($feedack_options['ss-feedback-phrase']!="")?$feedack_options['ss-feedback-phrase']:__('Help us improve. Give us your feedback.','wp-feedback-comments');
        $title_level=($feedack_options['ss-title-level']!="")?$feedack_options['ss-title-level']:'h4';
        $feedback_name=($feedack_options['ss-feedback-name']!="")?$feedack_options['ss-feedback-name']:__('Your full name (optional)','wp-feedback-comments');
        $feedback_ymail=($feedack_options['ss-feedback-ymail']!="")?$feedack_options['ss-feedback-ymail']:__('Your email address (optional)','wp-feedback-comments');
        $share_phrase=($feedack_options['ss-share-phrase']!="")?$feedack_options['ss-share-phrase']:__('Awesome, share it!','wp-feedback-comments');
        $thank_title=($feedack_options['ss-thank-title']!="")?$feedack_options['ss-thank-title']:__('Thanks!','wp-feedback-comments');
        $thank_text=($feedack_options['ss-thank-text']!="")?$feedack_options['ss-thank-text']:__('Thanks for helping us improve.','wp-feedback-comments');
        $placeholder_text=($feedack_options['ss-placeholder-phrase']!="")?$feedack_options['ss-placeholder-phrase']:__('Write a comment or just click Send.','wp-feedback-comments');
        
        if ($feedack_options['ss-show-schema']=='yes') {
            $thumbsdown_class='m-feedback-prompt_form';
        } else {
            $thumbsdown_class='m-feedback-prompt__social_thumbsdown';
        }

        if ($question_var != '') $title_phrase = $question_var;

        $social_share = '';
        if( in_array( 'facebook', (array)$feedack_options['ss-share-on'] ) ) {
            $social_share .= '<a class="m-feedback-prompt__social--button p-button-social has-icon facebook fa fab fa-facebook" href="https://www.facebook.com/sharer/sharer.php?u='.urldecode(get_permalink($post->ID)).'" target="_blank"> <span class="p-button-social__social-text">'.__('Facebook','wp-feedback-comments').'</span> </a> ';
        }
        if( in_array( 'twitter', (array)$feedack_options['ss-share-on'] ) ) {
            $social_share .= '<a class="m-feedback-prompt__social--button p-button-social has-icon twitter fa fab fa-twitter" href="https://twitter.com/intent/tweet?url='.urldecode(get_permalink($post->ID)).'&text='.urldecode(get_the_title($post->ID)).'" target="_blank"> <span class="p-button-social__social-text">'.__('Twitter','wp-feedback-comments').'</span> </a> </a> ';
        }
        if( in_array( 'linkedin', (array)$feedack_options['ss-share-on'] ) ) {
            $social_share .= '<a class="m-feedback-prompt__social--button p-button-social has-icon linkedin fa fab fa-linkedin" href="https://www.linkedin.com/shareArticle?mini=true&url='.urldecode(get_permalink($post->ID)).'&title='.urldecode(get_the_title($post->ID)).'" target="_blank"> <span class="p-button-social__social-text">'.__('LinkedIn','wp-feedback-comments').'</span> </a> ';
        }

        $yes_text = ( isset($feedack_options['ss-text-yes']) && $feedack_options['ss-text-yes'] !='') ? $feedack_options['ss-text-yes'] : 'fas fa-thumbs-up';
        if ($yes_var != '') $yes_text = $yes_var;
        if (strpos($yes_text, "fa-") === 0 ) $yes_text = "far " . $yes_text;
        $yes_text = '<i class="'.$yes_text.'">&nbsp;&nbsp;</i>';

        $no_text  = ( isset($feedack_options['ss-text-no']) && $feedack_options['ss-text-no'] !='') ? $feedack_options['ss-text-no'] : 'fas fa-thumbs-down';
        if ($no_var != '') $no_text = $no_var;
        if (strpos($no_text, "fa-") === 0 ) $no_text = "far " . $no_text;
        $no_text = '<i class="fa '.$no_text.'">&nbsp;&nbsp;</i>';

        if ( !isset($feedack_options['ss-show-fields']) || $feedack_options['ss-show-fields'] !='yes') {
            $author_fields ='<label>'.$feedback_name.'</label>
        <input class="p-input__text" type="text" name="feedbackfullname" id="feedbackfullname">
        <label>'.$feedback_ymail.'</label>
        <input class="p-input__text" type="text" name="mailinglistemail" id="mailinglistemail">';
        } else {
            $author_fields ='<input value="" type="hidden" name="feedbackfullname" id="feedbackfullname"><input value="" type="hidden" name="mailinglistemail" id="mailinglistemail">';
        }

        return '
<div class="m-entry__feedback">
    <div class="m-feedback-prompt"> <'.$title_level.' class="m-feedback-prompt__header" style="display: inline;">'.$title_phrase.'</'.$title_level.'>&nbsp;&nbsp; <a href="#" class="m-feedback-prompt__button m-feedback-prompt__social m-feedback-prompt__social_thumbsup yes" data-analytics-link="feedback-prompt:yes"> '.$yes_text.' </a> <a href="#" class="m-feedback-prompt__button '.$thumbsdown_class.' no" data-analytics-link="feedback-prompt:no"> '.$no_text.' </a><br>
        <div class="m-feedback-prompt__display m-feedback-prompt__social yes">
            <p class="m-feedback-prompt__text">'.$share_phrase.'</p>
            '.$social_share.' </div>
        <div class="m-feedback-prompt__display m-feedback-prompt__form no">
            <div class="thanks feedback-nodisplayall">
                <h2> '.$thank_title.' </h2>
                <div class="m-contact">
                    <p>'.$thank_text.'</p>
                </div>
            </div>
            <form id="contact-form" class="new_support_request" action="" accept-charset="UTF-8" method="post">
                '.wp_nonce_field(-1,'authenticity_token',true, false).'
                <input value="'.$post->ID.'" type="hidden" name="currentid" id="currentid">
                <input value="'.urldecode(get_permalink($post->ID)).'" type="hidden" name="currenturl" id="currenturl">
                <input value="'.urldecode(get_the_title($post->ID)).'" type="hidden" name="currenttitle" id="currenttitle">
                <label class="is-required">'.$feedback_phrase.'</label>
                <textarea class="p-input__textarea" name="feedbackmessage" id="feedbackmessage" placeholder="'.$placeholder_text.'"></textarea>
                '.$author_fields.'
                <div class="feedback-message" id="feedback-message"></div>
                <div class="__submit">
                    <input type="submit" name="commit" value="'.__('Submit','wp-feedback-comments').'" class="p-button" id="submit-contact-form" data-analytics-link="feedback-prompt:submit">
                </div>
            </form>
        </div>
    </div>
</div>
';
    }

    public function empty_comments_template() {
        return dirname(__FILE__) . '/empty-comments-template.php';
    }

    /**
    * Feedback Append HTML with Content with Thumbs Up and Down.
    * @since 1.0
    */
    public function append_feedback_html( $content ) {

        $feedack_options = $this->get_feedback_options('feedback_options');

        // get current post's id
        global $post;
        $post_id = $post->ID;
        $post_type = $post->post_type;

        if( in_array($post_id,explode(',',$feedack_options['ss-exclude-on'])) )
            return $content;
        if( (is_front_page() || is_home()) && !in_array( 'home', (array)$feedack_options['ss-show-on'] ) )
            return $content;  
        if( is_single() && ( !in_array( $post_type, (array)$feedack_options['ss-show-off']  ) ) )
            return $content;
        if( is_page() && ( !in_array( $post_type, (array)$feedack_options['ss-show-off'] ) ) )
            return $content;
        if( is_archive() && ( !in_array( 'archive', (array)$feedack_options['ss-show-on'] ) || !in_array( $post_type, (array)$feedack_options['ss-show-off'] ) ) )
            return $content;

        $feedback_html_markup = $this->feedback_content();
        $is_in_use = 0;

        if( is_array($feedack_options['ss-select-position']) && in_array('before-content', $feedack_options['ss-select-position']) ) {
            $content = $feedback_html_markup.$content;
            $is_in_use = 1;
        }
        if( is_array($feedack_options['ss-select-position']) && in_array('after-content', (array)$feedack_options['ss-select-position']) ) {
            $content .= $feedback_html_markup;
            $is_in_use = 1;
        }
        if ($is_in_use != 0) {
            add_filter('comments_template', array($this, 'empty_comments_template'));
            wp_deregister_script('comment-reply');
            if ($feedback_options['ss-stop-feed']=="yes") {
                remove_action( 'wp_head', 'feed_links_extra', 3 );
            }
        }
        return $content;
    }

    public function append_customhook_html() {

        $feeback_options = $this->get_feedback_options('feedback_options');
        if ( !in_array('custom-hook', $feeback_options['ss-select-position']) ) return;

        // get current post's id
        global $post;
        $post_id = $post->ID;
        $post_type = $post->post_type;

        if( in_array($post_id,explode(',',$feeback_options['ss-exclude-on'])) )
            return;
        if( (is_front_page() || is_home()) && !in_array( 'home', (array)$feeback_options['ss-show-on'] ) )
            return;  
        if( is_single() && ( !in_array( $post_type, (array)$feeback_options['ss-show-off']  ) ) )
            return;
        if( is_page() && ( !in_array( $post_type, (array)$feeback_options['ss-show-off'] ) ) )
            return;
        if( is_archive() && ( !in_array( 'archive', (array)$feeback_options['ss-show-on'] ) || !in_array( $post_type, (array)$feeback_options['ss-show-off'] ) ) )
            return;

        $feedback_html_markup = $this->feedback_content();
        $is_in_use = 0;

        if( is_array($feeback_options['ss-select-position']) && in_array('custom-hook', $feeback_options['ss-select-position']) ) {
            $is_in_use = 1;
        }
        if ($is_in_use != 0) {
            add_filter('comments_template', array($this, 'empty_comments_template'));
            wp_deregister_script('comment-reply');
        }
        echo $feedback_html_markup;
    }

    public function load_defaults() {
        $loadopts = get_option('feedback_options');
        if (!get_option('feedback_options') || $loadopts['ss-delete-opts']=='yes' ) {
            update_option( 'feedback_options', $this->get_defaults() );
        }
    }

    public function get_defaults($preset=true) {
        return array(
            'ss-select-position' => $preset ? array('before-content') : array(),
            'ss-show-on' => $preset ? array('home') : array(),
            'ss-show-off' => $preset ? array('page', 'post') : array(),
            'ss-share-on' => $preset ? array('facebook', 'twitter', 'linkedin') : array(),
            'ss-export-select' => $preset ? array('message', 'articlename', 'articleurl', 'authorsname', 'authorsname') : array(),
            'ss-export-format' => 'csv',
            'ss-title-level'=>'h4',
            'ss-title-phrase'=>'',
            'ss-feedback-phrase'=>'',
            'ss-feedback-name'=>'',
            'ss-feedback-ymail'=>'',
            'ss-share-phrase'=>'',
            'ss-thank-title'=>'',
            'ss-thank-text'=>'',
            'ss-yes-text'=>'',
            'ss-no-text'=>'',
            'ss-exclude-on' => '',
            'ss-feedback-email'=>'',
            'ss-load-font'=> $preset ? 'yes' : '',
            'ss-stop-feed'=> $preset ? 'yes' : '',
            'ss-anonymize' => $preset ? 'yes' : '',
            'ss-send-yes'=> $preset ? 'yes' : '',
            'ss-show-fields'=> $preset ? 'yes' : '',
            'ss-show-schema'=> $preset ? 'yes' : '',
            'ss-style-on'=> $preset ? 'yes' : '',
            'ss-style-url'=> '',
            'ss-font-size'=>'2.4',
            'ss-thumbs-up'=>'#5C7ED7',
            'ss-thumbs-down'=>'#FF3234',
            'ss-text-yes' => '',
            'ss-text-no' => '',
            'ss-delete-opts' => ''
            );
    }

    public function register_settings() {
        register_setting( 'feedback_options', 'feedback_options' );
    }

    /**
     * Add sub menu page in Comments for configuring plugin
     * @since 1.0
     */
    public function register_submenu() {
        $ip_feedback_options = get_option('feedback_options');
        if ( $ip_feedback_options['ss-submenu-move']=="yes" ) {
            add_submenu_page( 'options-general.php', 'Feedback Comments settings', 'Feedback Comments', 'activate_plugins', 'feedback-comments-settings', array( $this, 'submenu_page' ) ); // subenu under options general
        } else {
            add_submenu_page( 'edit-comments.php', __('Feedback Comments settings','wp-feedback-comments'), __('Feedback Options','wp-feedback-comments'), 'activate_plugins', 'feedback-comments-settings', array( $this, 'submenu_page' ) ); //submenu under Comments
        }
    }

    public function get_feedback_options() {
        return array_merge( $this->get_defaults(false), get_option('feedback_options') );
    }

    /*
     * Callback for add_submenu_page for generating markup of page
     */
    public function submenu_page() {
    ?>
<div class="wrap">
    <h2 class="boxed-header">
        <?php  _e('Feedback Comments Settings','wp-feedback-comments');?>
    </h2>
    <p><em>
        <?php _e('See help tab in top right corner.','wp-feedback-comments'); ?>
        </em></p>
    <form method="POST" action="options.php">
        <div class="activate-boxed-highlight activate-boxed-option">
            <?php settings_fields('feedback_options'); ?>
            <?php $feedback_options = get_option('feedback_options');?>
            <?php if (!isset($feedback_options['ss-hide-donate']) || $feedback_options['ss-hide-donate'] !='yes') { ?>
            <div style="border:1px solid #ddd; position: relative; height: auto; padding-bottom: 20px; max-width: 800px"><img src="<?php echo(plugin_dir_url(__FILE__)); ?>/assets/images/coffeepot_small.png" height="125" width="113" align="left" style="padding: 20px"/>
                <h2>
                    <?php _e('Donate', 'wp-feedback-comments'); ?>
                </h2>
                <p>
                    <?php _e('This software is free to use, but you can donate to support my work with a brew of strong hot coffee.', 'wp-feedback-comments'); ?>
                    <br />
                    <?php _e('Donate any amount you feel appropriate with PayPal by following the link below.', 'wp-feedback-comments'); ?>
                </p>
                <p><a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&amp;business=uthne@mac.com&lc=US&item_name=Donate+to+Feedback+Comments+plugin&no_note=0&cn=&curency_code=USD&bn=PP-DonationsBF:btn_donateCC_LG.gif:NonHosted" target="_blank">
                    <?php _e('Donate with PayPal', 'wp-feedback-comments') ?>
                    </a>
                    <?php _e('or hide this box', 'wp-feedback-comments') ?>
                    &nbsp;&nbsp;
                    <input type="checkbox" name="feedback_options[ss-hide-donate]" id="donate" class="css-checkbox" value="yes" <?php echo(__checked_selected_helper( $feedback_options['ss-hide-donate'],'yes', false,'checked' )); ?> >
                    &nbsp;&nbsp;</p>
            </div>
            <?php } ?>
            <?php echo $this->admin_form($feedback_options); ?> </div>
        <div class="activate-use-option sidebox first-sidebox">
            <h3>
                <?php  _e('Instruction to use Plugin','wp-feedback-comments');?>
            </h3>
            <p><em>
                <?php _e('See also help tab in top right corner.','wp-feedback-comments'); ?>
                </em></p>
            <hr />
            <h4>
                <?php _e('Using Shortcode','wp-feedback-comments');?>
            </h4>
            <p>
                <?php _e('You can place the shortcode','wp-feedback-comments')?>
            </p>
            <p><code>[feedback_comment]</code></p>
            <p>
                <?php _e('wherever you want to display the Feedback Comments.','wp-feedback-comments');?>
            </p>
            <hr />
            <h4>
                <?php _e('Using custom hook','wp-feedback-comments');?>
            </h4>
            <p>
                <?php _e('You can place the code','wp-feedback-comments')?>
            </p>
            <p><code><?php echo("&lt;"."?php"." do_action('fc_feedback_hook'); ?"."&gt;"); ?></code></p>
            <p>
                <?php _e('in your template to place Feedback Comments.','wp-feedback-comments');?>
            </p>
            <hr />
            <?php if (isset($feedback_options['ss-hide-donate']) && $feedback_options['ss-hide-donate'] =='yes') { ?>
            <p>
                <?php _e('Hide donation box', 'wp-feedback-comments') ?>
                <input type="checkbox" name="feedback_options[ss-hide-donate]" id="donate" class="css-checkbox" value="yes" <?php echo(__checked_selected_helper( $feedback_options['ss-hide-donate'],'yes', false,'checked' )); ?> </p>
            <?php } ?>
        </div>
    </form>
</div>
    <?php
    }

    /**
     * Admin form for Feedback Settings
     *
     *@since 1.0
     */
    public function admin_form( $feedback_options ) {
    
        $post_types = get_post_types();
        $ss_off_selector = '<select style="min-width: 190px;" id="ss-show-off"
            name="feedback_options[ss-show-off][]" size="4"
            multiple="multiple">';
        foreach ($post_types as $post_type_name) {
            $ss_off_selector .= '<option value="' . esc_attr($post_type_name) . '" '; 
            if ( isset($feedback_options['ss-show-off']) && in_array($post_type_name, (array)$feedback_options['ss-show-off']) ) $ss_off_selector .= 'selected="selected"'; 
            $ss_off_selector .= '>'. esc_html(get_post_type_object($post_type_name)->labels->name) . '</option>';
        }
        $ss_off_selector .= '</select>';
        $ss_off_selector .= '<a class="button-secondary" onclick="javascript:jQuery(\'#ss-show-off\')[0].selectedIndex = -1;return false;">'.__('Clear','wp-feedback-comments').'</a>';	
        return '
<table class="form-table settings-table">
    <tr>
        <th><label for="ss-select-postion">'.__('Select Position','wp-feedback-comments').'</label></th>
        <td><input type="checkbox" name="feedback_options[ss-select-position][]" id="before-content" class="css-checkbox" value="before-content" '.__checked_selected_helper( in_array( 'before-content', (array)$feedback_options['ss-select-position'] ),true, false,'checked' ).'>
            <label for="before-content" class="css-label cb0">'.__('Before Content','wp-feedback-comments').'</label>
            &nbsp;&nbsp;
            <input type="checkbox" name="feedback_options[ss-select-position][]" id="after-content" class="css-checkbox" value="after-content" '.__checked_selected_helper( in_array( 'after-content', (array)$feedback_options['ss-select-position'] ),true, false,'checked' ).'>
            <label for="after-content" class="css-label cb0">'.__('After Content','wp-feedback-comments').'</label>
            &nbsp;&nbsp;
            <input type="checkbox" name="feedback_options[ss-select-position][]" id="custom-hook" class="css-checkbox" value="custom-hook" '.__checked_selected_helper( in_array( 'custom-hook', (array)$feedback_options['ss-select-position'] ),true, false,'checked' ).'>
            <label for="after-content" class="css-label cb0">'.__('At custom hoook','wp-feedback-comments').'</label>
            <small><br />
            <em>'.__('To use Custom Hook, add the code &lt;php do_action(\'fc_feedback_hook\'); ?&gt; to your template.','wp-feedback-comments').'</em></small></td>
    </tr>
    <tr>
        <th><label for="ss-select-postion">'.__('Include on types','wp-feedback-comments').'</label></th>
        <td>'.$ss_off_selector.'</td>
    </tr>
    <tr>
        <th><label for="ss-select-postion">'.__('Also show on','wp-feedback-comments').'</label></th>
        <td><input type="checkbox" name="feedback_options[ss-show-on][]" id="home" class="css-checkbox" value="home" '.__checked_selected_helper( in_array( 'home', (array)$feedback_options['ss-show-on'] ),true, false,'checked' ).'>
            <label for="home" class="css-label cb0">'.__('Home Page','wp-feedback-comments').'</label>
            &nbsp;&nbsp;
            <input type="checkbox" name="feedback_options[ss-show-on][]" id="archives" class="css-checkbox" value="archive" '.__checked_selected_helper( in_array( 'archive', (array)$feedback_options['ss-show-on'] ),true, false,'checked' ).'>
            <label for="archives" class="css-label cb0">'.__('Archives','wp-feedback-comments').'</label>
            &nbsp;<br />
            <small><em>'.__('Will be included on selected types when shown as this','wp-feedback-comments').'</em></small></td>
    </tr>
    <tr>
        <th><label for="ss-exclude-on">'.__('Exclude on ID\'s','wp-feedback-comments').'</label></th>
        <td><input type="text" name="feedback_options[ss-exclude-on]" value="'.$feedback_options['ss-exclude-on'].'">
            <small><em>'.__('Comma seperated post id\'s Eg:','wp-feedback-comments').' </em><code>1207,1222</code></small></td>
    </tr>
    <tr>
        <th><label for="ss-stop-feed">'.__('Stop comments feed','wp-feedback-comments').'</label></th>
        <td><input type="checkbox" name="feedback_options[ss-stop-feed]" id="stopFeed" class="css-checkbox" value="yes" '.__checked_selected_helper( $feedback_options['ss-stop-feed'],'yes', false,'checked' ).'>
            <small><em>'.__('Check to stop responses showing in RSS feed','wp-feedback-comments').'</em></small></td>
    </tr>
    <tr>
        <th><label for="ss-anonymize">'.__('Anonymize IP-number','wp-feedback-comments').'</label></th>
        <td><input type="checkbox" name="feedback_options[ss-anonymize]" id="anonOn" class="css-checkbox" value="yes" '.__checked_selected_helper( $feedback_options['ss-anonymize'],'yes', false,'checked' ).'>
            <small><em>'.__('To comply with GDPR, check this to anonymize last number in IP','wp-feedback-comments').'</em></small></td>
    </tr>
    <tr>
        <th><label for="ss-show-fields">'.__('Hide author fields','wp-feedback-comments').'</label></th>
        <td><input type="checkbox" name="feedback_options[ss-show-fields]" id="showfields" class="css-checkbox" value="yes" '.__checked_selected_helper( $feedback_options['ss-show-fields'],'yes', false,'checked' ).'>
            <small><em>'.__('Check if you want to hide Name and E-mail fields, and avoid GDPR directive','wp-feedback-comments').'</em></small></td>
    </tr>
    <tr>
        <th><label for="ss-send-yes">'.__('Save positive response','wp-feedback-comments').'</label></th>
        <td><input type="checkbox" name="feedback_options[ss-send-yes]" id="sendYes" class="css-checkbox" value="yes" '.__checked_selected_helper( $feedback_options['ss-send-yes'],'yes', false,'checked' ).'>
            <small><em>'.__('Check to save feedback on positive response','wp-feedback-comments').'</em></small></td>
    </tr>
    <tr>
        <th><label for="ss-show-schema">'.__('Show dialouge','wp-feedback-comments').'</label></th>
        <td><input type="checkbox" name="feedback_options[ss-show-schema]" id="showmessage" class="css-checkbox" value="yes" '.__checked_selected_helper( $feedback_options['ss-show-schema'],'yes', false,'checked' ).'>
            <small><em>'.__('Uncheck if you want to hide the message dialogue on negative feedback','wp-feedback-comments').'</em></small></td>
    </tr>
    <tr>
        <th><label for="ss-load-font">'.__('Load FontAwesome','wp-feedback-comments').'</label></th>
        <td><input type="checkbox" name="feedback_options[ss-load-font]" id="fontAwe" class="css-checkbox" value="yes" '.__checked_selected_helper( $feedback_options['ss-load-font'],'yes', false,'checked' ).'>
            <small><em>'.__('Uncheck if FontAwesome is loaded with the theme or other plugins','wp-feedback-comments').'</em></small></td>
    </tr>
    <tr>
        <th><label for="ss-style-on">'.__('Use default style','wp-feedback-comments').'</label></th>
        <td><input type="checkbox" name="feedback_options[ss-style-on]" id="styleOn" class="css-checkbox" value="yes" '.__checked_selected_helper( $feedback_options['ss-style-on'],'yes', false,'checked' ).'>
            <small><em>'.__('Uncheck if want to use your own CSS styling','wp-feedback-comments').'</em></small></td>
    </tr>
    <tr>
        <th><label for="ss-style-url">'.__('Custom style URL','wp-feedback-comments').'</label></th>
        <td><input type="text" name="feedback_options[ss-style-url]" value="'.$feedback_options['ss-style-url'].'">
            <small><em>'.__('URL of custom CSS styling file','wp-feedback-comments').' </em></small></td>
    </tr>
    <tr>
        <th><label for="ss-text-yes">'.__('YES text','wp-feedback-comments').'</label></th>
        <td><input type="text" name="feedback_options[ss-text-yes]" value="'.$feedback_options['ss-text-yes'].'">
            <small><em>'.__('Will replace the thumbs up symbol, leave blank to show symbol','wp-feedback-comments').' </em></small></td>
    </tr>
    <tr>
        <th><label for="ss-text-no">'.__('NO text','wp-feedback-comments').'</label></th>
        <td><input type="text" name="feedback_options[ss-text-no]" value="'.$feedback_options['ss-text-no'].'">
            <small><em>'.__('Will replace the thumbs down symbol, leave blank to show symbol','wp-feedback-comments').' </em></small></td>
    </tr>
    <tr>
        <th><label for="ss-font-size">'.__('Font Size','wp-feedback-comments').'</label></th>
        <td><input type="range" min="0.1" max="10"  id="fader" step="0.1" name="feedback_options[ss-font-size]" value="'.$feedback_options['ss-font-size'].'">
            <small><strong>&nbsp;&nbsp;<span id="fontsize">'.$feedback_options['ss-font-size'].'em</span></strong>&nbsp;&nbsp;<em>'.__('Thumbs Up and Down Font Size','wp-feedback-comments').' </em></small></td>
    </tr>
    <tr>
        <th><label for="ss-thumbs-up">'.__('Thumbs Up color','wp-feedback-comments').'</label></th>
        <td><input type="text" name="feedback_options[ss-thumbs-up]" id="ssthumbsup" data-default-color="#5C7ED7" value="'.$feedback_options['ss-thumbs-up'].'"></td>
    </tr>
    <tr>
        <th><label for="ss-thumbs-down">'.__('Thumbs Down Color','wp-feedback-comments').'</label></th>
        <td><input type="text" name="feedback_options[ss-thumbs-down]" id="ssthumbsdown" data-default-color="#FF3234" value="'.$feedback_options['ss-thumbs-down'].'"></td>
    </tr>
    <tr>
        <th><label for="ss-title-level">'.__('Title size','wp-feedback-comments').'</label></th>
        <td><select name="feedback_options[ss-title-level]" name="feedback_options[ss-title-level]">
            <option value="h1" '.__checked_selected_helper( in_array( 'h1', (array)$feedback_options['ss-title-level'] ),true, false,'selected' ).'>Heading 1</option>
            <option value="h2" '.__checked_selected_helper( in_array( 'h2', (array)$feedback_options['ss-title-level'] ),true, false,'selected' ).'>Heading 2</option>
            <option value="h3" '.__checked_selected_helper( in_array( 'h3', (array)$feedback_options['ss-title-level'] ),true, false,'selected' ).'>Heading 3</option>
            <option value="h4" '.__checked_selected_helper( in_array( 'h4', (array)$feedback_options['ss-title-level'] ),true, false,'selected' ).'>Heading 4</option>
            <option value="h5" '.__checked_selected_helper( in_array( 'h5', (array)$feedback_options['ss-title-level'] ),true, false,'selected' ).'>Heading 5</option>
            <option value="h6" '.__checked_selected_helper( in_array( 'h6', (array)$feedback_options['ss-title-level'] ),true, false,'selected' ).'>Heading 6</option>
            <option value="p" '.__checked_selected_helper( in_array( 'p', (array)$feedback_options['ss-title-level'] ),true, false,'selected' ).'>Paragraph</option>
            <option value="div" '.__checked_selected_helper( in_array( 'div', (array)$feedback_options['ss-title-level'] ),true, false,'selected' ).'>Div</option>
            </select>
            &nbsp;&nbsp;&nbsp; <small><em>'.__('Size of the question text.','wp-feedback-comments').' </em></small></td>
    </tr>
    <tr>
        <th><label for="ss-title-phrase">'.__('Title Phrase','wp-feedback-comments').'</label></th>
        <td><input type="text" name="feedback_options[ss-title-phrase]" value="'.$feedback_options['ss-title-phrase'].'">
            <small><em>'.__('Keep this section blank, if you want "Was this article helpful?"','wp-feedback-comments').' </em></small></td>
    </tr>
    <tr>
        <th><label for="ss-feedback-phrase">'.__('Feedback Phrase','wp-feedback-comments').'</label></th>
        <td><input type="text" name="feedback_options[ss-feedback-phrase]" value="'.$feedback_options['ss-feedback-phrase'].'">
            <small><em>'.__('Keep this section blank, if you want "Help us improve. Give us your feedback."','wp-feedback-comments').' </em></small></td>
    </tr>
    <tr>
        <th><label for="ss-feedback-name">'.__('Feedback name text','wp-feedback-comments').'</label></th>
        <td><input type="text" name="feedback_options[ss-feedback-name]" value="'.$feedback_options['ss-feedback-name'].'">
            <small><em>'.__('Keep this section blank, if you want "Your full name (optional)"','wp-feedback-comments').' </em></small></td>
    </tr>
    <tr>
        <th><label for="ss-feedback-ymail">'.__('Feedback email text','wp-feedback-comments').'</label></th>
        <td><input type="text" name="feedback_options[ss-feedback-ymail]" value="'.$feedback_options['ss-feedback-ymail'].'">
            <small><em>'.__('Keep this section blank, if you want "Your email address (optional)"','wp-feedback-comments').' </em></small></td>
    </tr>
    <tr>
        <th><label for="ss-placeholder-phrase">'.__('Message placeholder','wp-feedback-comments').'</label></th>
        <td><input type="text" name="feedback_options[ss-placeholder-phrase]" value="'.$feedback_options['ss-placeholder-phrase'].'">
            <small><em>'.__('Keep this section blank, if you want "Write a comment or just click Send. "','wp-feedback-comments').' </em></small></td>
    </tr>
    <tr>
        <th><label for="ss-share-phrase">'.__('Share phrase','wp-feedback-comments').'</label></th>
        <td><input type="text" name="feedback_options[ss-share-phrase]" value="'.$feedback_options['ss-share-phrase'].'">
            <small><em>'.__('Keep this section blank, if you want "Awesome, share it"','wp-feedback-comments').' </em></small></td>
    </tr>
    <tr>
        <th><label for="ss-thank-title">'.__('Thanks title','wp-feedback-comments').'</label></th>
        <td><input type="text" name="feedback_options[ss-thank-title]" value="'.$feedback_options['ss-thank-title'].'">
            <small><em>'.__('Keep this section blank, if you want "Thanks!"','wp-feedback-comments').' </em></small></td>
    </tr>
    <tr>
        <th><label for="ss-thank-text">'.__('Thanks text','wp-feedback-comments').'</label></th>
        <td><input type="text" name="feedback_options[ss-thank-text]" value="'.$feedback_options['ss-thank-text'].'">
            <small><em>'.__('Keep this section blank, if you want "Thanks for helping us improve."','wp-feedback-comments').' </em></small></td>
    </tr>
    <tr>
        <th><label for="ss-yes-text">'.__('Positive response text','wp-feedback-comments').'</label></th>
        <td><input type="text" name="feedback_options[ss-yes-text]" value="'.$feedback_options['ss-yes-text'].'">
            <small><em>'.__('Keep this section blank, if you want "Yes I found what I was looking for."','wp-feedback-comments').' </em></small></td>
    </tr>
    <tr>
        <th><label for="ss-no-text">'.__('Negative default text','wp-feedback-comments').'</label></th>
        <td><input type="text" name="feedback_options[ss-no-text]" value="'.$feedback_options['ss-no-text'].'">
            <small><em>'.__('Keep this section blank, if you want "No, i did not find it."','wp-feedback-comments').' </em></small></td>
    </tr>
    <tr>
        <th><label for="ss-delete-opts">'.__('Reactivate to default','wp-feedback-comments').'</label></th>
        <td><input type="checkbox" name="feedback_options[ss-delete-opts]" id="delOpts" class="css-checkbox" value="yes" '.__checked_selected_helper( $feedback_options['ss-delete-opts'],'yes', false,'checked' ).'>
            <small><em>'.__('Check to force default options when plugin is deactivated and reactivated.','wp-feedback-comments').'</em></small></td>
    </tr>
    <tr>
        <th><label for="ss-submenu-move">'.__('Settings submenu','wp-feedback-comments').'</label></th>
        <td><input type="checkbox" name="feedback_options[ss-submenu-move]" id="menuMove" class="css-checkbox" value="yes" '.__checked_selected_helper( $feedback_options['ss-submenu-move'],'yes', false,'checked' ).'>
            <small><em>'.__('Check move submenu from Comments to Settings menu','wp-feedback-comments').'</em></small></td>
    </tr>
    <tr>
        <th><label for="ss-share-on">'.__('Share on','wp-feedback-comments').'</label></th>
        <td><input type="checkbox" name="feedback_options[ss-share-on][]" id="facebook" class="css-checkbox" value="facebook" '.__checked_selected_helper( in_array( 'facebook', (array)$feedback_options['ss-share-on'] ),true, false,'checked' ).'>
            <label for="facebook" class="css-label cb0">'.__('Facebook','wp-feedback-comments').'</label>
            &nbsp;&nbsp;
            <input type="checkbox" name="feedback_options[ss-share-on][]" id="twitter" class="css-checkbox" value="twitter" '.__checked_selected_helper( in_array( 'twitter', (array)$feedback_options['ss-share-on'] ),true, false,'checked' ).'>
            <label for="twitter" class="css-label cb0">'.__('Twitter','wp-feedback-comments').'</label>
            &nbsp;&nbsp;
            <input type="checkbox" name="feedback_options[ss-share-on][]" id="linkedin" class="css-checkbox" value="linkedin" '.__checked_selected_helper( in_array( 'linkedin', (array)$feedback_options['ss-share-on'] ),true, false,'checked' ).'>
            <label for="linkedin" class="css-label cb0">'.__('LinkedIn','wp-feedback-comments').'</label>
            &nbsp;<br />
            <small><em>'.__('Select at least one, if not it will look pretty stupid!"','wp-feedback-comments').' </em></small></td>
    </tr>
    <tr>
        <th><label for="ss-export-select">'.__('Export','wp-feedback-comments').'</label></th>
        <td><input type="checkbox" name="feedback_options[ss-export-select][]" id="message" class="css-checkbox" value="message" '.__checked_selected_helper( in_array( 'message', (array)$feedback_options['ss-export-select'] ),true, false,'checked' ).'>
            <label for="message" class="css-label cb0">'.__('Message','wp-feedback-comments').'</label>
            &nbsp;&nbsp;
            <input type="checkbox" name="feedback_options[ss-export-select][]" id="articlename" class="css-checkbox" value="articlename" '.__checked_selected_helper( in_array( 'articlename', (array)$feedback_options['ss-export-select'] ),true, false,'checked' ).'>
            <label for="articlename" class="css-label cb0">'.__('Article name','wp-feedback-comments').'</label>
            &nbsp;&nbsp;
            <input type="checkbox" name="feedback_options[ss-export-select][]" id="articleurl" class="css-checkbox" value="articleurl" '.__checked_selected_helper( in_array( 'articleurl', (array)$feedback_options['ss-export-select'] ),true, false,'checked' ).'>
            <label for="articleurl" class="css-label cb0">'.__('Article URL','wp-feedback-comments').'</label>
            &nbsp;&nbsp;
            <input type="checkbox" name="feedback_options[ss-export-select][]" id="authorsname" class="css-checkbox" value="authorsname" '.__checked_selected_helper( in_array( 'authorsname', (array)$feedback_options['ss-export-select'] ),true, false,'checked' ).'>
            <label for="authorsname" class="css-label cb0">'.__('Authors name','wp-feedback-comments').'</label>
            &nbsp;&nbsp;
            <input type="checkbox" name="feedback_options[ss-export-select][]" id="authorsemail" class="css-checkbox" value="authorsemail" '.__checked_selected_helper( in_array( 'authorsemail', (array)$feedback_options['ss-export-select'] ),true, false,'checked' ).'>
            <label for="authorsemail" class="css-label cb0">'.__('Authors email','wp-feedback-comments').'</label>
            &nbsp;<br />
            <small><em>'.__('Select the fields you wish to include in the CSV-file','wp-feedback-comments').' </em></small></td>
    </tr>
    <tr>
        <th><label for="ss-export-format">'.__('Export format','wp-feedback-comments').'</label></th>
        <td><select name="feedback_options[ss-export-format]">
                <option value="csv" '.__checked_selected_helper( in_array( 'csv', (array)$feedback_options['ss-export-format'] ),true, false,'selected' ).'>CSV</option>
                <option value="xls" '.__checked_selected_helper( in_array( 'xls', (array)$feedback_options['ss-export-format'] ),true, false,'selected' ).'>Excel</option>
            </select>
            &nbsp;&nbsp;&nbsp; <small><em>'.__('Format of exported document.','wp-feedback-comments').' </em></small></td>
    </tr>
</table>
<p class="submit">
    <input type="submit" name="submit" id="submit" class="button button-primary" value="'.__('Save Changes','wp-feedback-comments').'">
</p>
';
	}

    /**
    * Feedback send Feedback Comments to WP comments using Ajax
    * @since 1.0 
    */

    function feedback_receiver() {
    
        $feedback_options = get_option('feedback_options');
        $email = (isset($_POST['email'])) ? sanitize_email($_POST['email']) : "";
        $name = (isset($_POST['name'])) ? sanitize_text_field($_POST['name']) : "";
        $message=sanitize_text_field($_POST['message']);
        $id=intval($_POST['id']);
        $url=esc_url($_POST['url']);
        $title=sanitize_text_field($_POST['title']);
        $emailError="";
        $emailError="";


        /* Let blank email field pass */
        if (!preg_match("/^[[:alnum:]][a-z0-9_.-]*@[a-z0-9.-]+\.[a-z]{2,4}$/i", trim($_POST['email']))) {
            $emailError = __('You entered an invalid email address.','wp-feedback-comments');
            $hasError = true;
        } else {
            $email = sanitize_email($_POST['email']);
        }
        if (isset($_POST['message'])) {
            $allMesage = esc_html($message)."\n\n";

            if (strlen($allMesage < '6')) {
                $allMesage = ($feedback_options['ss-no-text'] != '') ? $feedback_options['ss-no-text'] . "\n" . $allMesage : __('No, i did not find it.','wp-feedback-comments') . "\n" . $allMesage;
            }

            $commentdata = array(
                'comment_post_ID' => $id, // to which post the comment will show up
                'comment_author' => $name, //fixed value - can be dynamic 
                'comment_author_email' => $email, //fixed value - can be dynamic 
                'comment_author_url' => '', //fixed value - can be dynamic 
                'comment_content' => $allMesage, //fixed value - can be dynamic 
                'comment_type' => '', //empty for regular comments, 'pingback' for pingbacks, 'trackback' for trackbacks
                'comment_parent' => 0, //0 if it's not a reply to another comment; if it's a reply, mention the parent comment ID here
                'user_id' => '', //passing current user ID or any predefined as per the demand
            );

            //Insert new comment and get the comment ID
            if ($comment_id = wp_new_comment( $commentdata )) {
                echo 'success';
            }else {
                echo __('There was a problem. Please try again.','wp-feedback-comments').$emailError;
            }			
        }
        wp_die();
        die();
    }

    function feedback_load_csv() {
        //for future use
    }


    //Bulk actions
    function custom_bulk_admin_footer() {
        //load wp-feedback-comments-export.php in iframe to
        global $post_type;
        $export_frame ='';
        if (isset($_GET['export_ids'])) {
            $export_frame = '<iframe style="width:1px;height:1px;display:none;" src="' 
                . plugins_url( '/wp-feedback-comments-export.php', __FILE__ ) 
                . '?export=' . $_GET['export_ids']
                . '</iframe>';
        };
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function() {
                jQuery('<option>').val('export').text('<?php _e('Export selected comment','wp-feedback-comments')?>').appendTo("select[name='action']");
                jQuery('<option>').val('export').text('<?php _e('Export selected comment','wp-feedback-comments')?>').appendTo("select[name='action2']");
                jQuery('<option>').val('exportsame').text('<?php _e('Export selected post','wp-feedback-comments')?>').appendTo("select[name='action']");
                jQuery('<option>').val('exportsame').text('<?php _e('Export selected post','wp-feedback-comments')?>').appendTo("select[name='action2']");
                jQuery('<option>').val('exportall').text('<?php _e('Export all','wp-feedback-comments')?>').appendTo("select[name='action']");
                jQuery('<option>').val('exportall').text('<?php _e('Export all','wp-feedback-comments')?>').appendTo("select[name='action2']");
            });
        </script>
        <?php
        echo $export_frame;
    }


    function custom_bulk_action() {
        global $typenow;
        $post_type = $typenow;

        // get the action
        $wp_list_table = _get_list_table('WP_Comments_List_Table');  // depending on your resource type this could be WP_Users_List_Table, WP_Comments_List_Table
        $action = $wp_list_table->current_action();

        $allowed_actions = array("export","exportsame","exportall");
        if(!in_array($action, $allowed_actions)) return;

        // security check
        check_admin_referer('bulk-comments');

        // make sure ids are submitted.  depending on the resource type, this may be 'media' or 'ids'
        if(isset($_REQUEST['delete_comments'])) {
            $post_ids = array_map('intval', $_REQUEST['delete_comments']);
        }

        // this is based on wp-admin/edit.php
        $sendback = remove_query_arg( array('exported', 'untrashed', 'deleted', 'ids'), wp_get_referer() );
        if ( !$sendback ) $sendback = admin_url( "edit-comments.php" );

        $pagenum = $wp_list_table->get_pagenum();
        $sendback = add_query_arg( 'paged', $pagenum, $sendback );

        switch($action) {
            case 'exportsame':
                $exported = 0;
                $commemts_id = array();
                foreach( $post_ids as $post_id ) {
                    $the_comment = get_comment( $post_id );
                    $the_post = $the_comment->comment_post_ID;
                    $args =  array('post_id' => $the_post);
                    $comments = get_comments( $args );
                    foreach( $comments as $comment ) {
                        if (!in_array($comment->comment_ID, $commemts_id)) $comments_id[] = $comment->comment_ID;
                    }
                    $exported++;
                }
                $sendback = add_query_arg( array('exported' => $exported, 'export_ids' => implode(',', $comments_id) ), $sendback );
            break;
            case 'exportall':
                $exported = 0;
                $comments = get_comments();
                $comments_id = array();
                foreach( $comments as $comment ) {
                    if (!in_array($comment->comment_ID, $commemts_id)) $comments_id[] = $comment->comment_ID;
                    $exported++;
                }
                $sendback = add_query_arg( array('exported' => $exported, 'export_ids' => implode(',', $comments_id) ), $sendback );
            break;
            case 'export':
                $exported = 0;
                foreach( $post_ids as $post_id ) {
                    //if ( !$this->perform_export($post_id) ) wp_die( __('Error exporting post.') );
                    $exported++;
                }
                $sendback = add_query_arg( array('exported' => $exported, 'export_ids' => implode(',', $post_ids) ), $sendback );
            break;
            default: return;
        }
        $sendback = remove_query_arg( array('action', 'action2', 'tags_input', 'post_author', 'comment_status', 'ping_status', '_status',  'post', 'bulk_edit', 'post_view'), $sendback );
        wp_redirect($sendback);
        exit();
    }

    function custom_bulk_admin_notices() {
        global $post_type, $pagenow;

        if($pagenow == 'edit-comments.php' && isset($_REQUEST['exported']) && (int) $_REQUEST['exported']) {
            $message = sprintf( _n( '%s comment exported.', '%s comments exported.', $_REQUEST['exported'], 'wp-feedback-comments'), number_format_i18n( $_REQUEST['exported'] ) );
            echo "<div class=\"updated\"><p>{$message}</p></div>";
        }
    }
} // END class FeedbackComments
FeedbackComments::setup();

if (!class_exists('FeedbackBulkAction')) {
    class FeedbackBulkAction {
        public function __construct() {

        }
    }
}
//new FeedbackBulkAction();

$ip_feedback_options = get_option('feedback_options');
if ( $ip_feedback_options['ss-anonymize']=="yes" ) {
    add_filter('pre_comment_user_ip', 'pre_comment_anonymize_ip');
}
if ( !function_exists('pre_comment_anonymize_ip' )) {
    function pre_comment_anonymize_ip( $comment_author_ip )
    {
        $ip_split = explode('.',$comment_author_ip);
        $ip_split[3] = '255';
        return implode('.',$ip_split);
    }
}

if(is_admin()) {
    add_action('admin_head', 'codex_wpfc_help_tab');
    add_action('admin_head', 'codex_wpfc_comments_help_tab');
}

function codex_wpfc_comments_help_tab() {
    $screen = get_current_screen();

    // Return early if we're not on the correct page.
    if (strpos($screen->id, 'edit-comments') === false) return;
    
    $export_help = '<h3>' . __('Export comments', 'wp-feedback-comments') . '</h3>'
    . '<p>' . __('From the <em>Bulk Action</em> menu you can choose options for exporting comments.', 'wp-feedback-comments') . '</p>'
    . '<p>' . __('<strong> - Export selected comment</strong> will export only the selected comments.', 'wp-feedback-comments') . '</p>'
    . '<p>' . __('<strong> - Export selected post</strong> will export all comments from selected post.', 'wp-feedback-comments') . '</p>'
    . '<p>' . __('<strong> - Export all</strong> will export all comments.', 'wp-feedback-comments') . '</p>'
    . '<p>' . __('Go to <em>Feedback Comments Settings</em> to choose what columns and file format to export.', 'wp-feedback-comments') . '</p>';

    $args_export = array(
        'id'      => 'wpfc_export_tab', //unique id for the tab
        'title'   => __('Export', 'wp-feedback-comments'),//unique visible title for the tab
        'content' => $export_help  //actual help text
    );

    $screen->add_help_tab( $args_export );   
}

function codex_wpfc_help_tab() {
    $screen = get_current_screen();

    // Return early if we're not on the correct page.
    if (strpos($screen->id, 'feedback-comments-settings') === false) return;

    $general_help = '<h3>' . __('General', 'wp-feedback-comments') . '</h3>'
    . '<p>' . __('This plugin will replace the comments section on your Wordpress site with a question like "Did you find what you are looking for?". All the feedback will end up under Comments in your admin-panel, and you can handle them just like normal comments.', 'wp-feedback-comments') . '</p>' 
    . '<p>' . __('The user can send a positive feedback, with the option to share the post – or a negative feedback with a written comment.', 'wp-feedback-comments') . '</p>' 
    . '<p>' . __('In the Comments view you\'ll get the added options to export the comments. You can export a selected feedbacks, all feedback on a single post or all feedback to a spreadsheet.', 'wp-feedback-comments') . '</p>' 
    . '<p>' . __('For more help on comments, se help sheet in the Comments view.', 'wp-feedback-comments') . '</p>';

    $settings_help = '<h3>' . __('Setting up', 'wp-feedback-comments') . '</h3>'
    . '<p>' . __('First you must select where on the page/post you would like to implement the question - before or after the main content, or at a custom hook. Then you select the post type it should be included on, Page, Post, etc.', 'wp-feedback-comments') . '</p>' 
    . '<p>' . __('You then select if the question should be included if the selected types are shown as homepage or archive.', 'wp-feedback-comments') . '</p>' 
    . '<p>' . __('To use the custom hook you\'ll have to get your favorite developer add the hook to your template at the location you want to place the question. Add this code to your template:', 'wp-feedback-comments') . '</p>' 
    . '<pre>' . __('<span style="color:red"><&quest;php</span> <span style="color:blue">do_action</span><span style="color:green">(\'fc_feedback_hook\')</span>; <span style="color:red">&quest;></span>.', 'wp-feedback-comments') . '</pre>' 
    . '<p>' . __('If you don\'t have a developer laying around try using the shortcode (see Shoortcode tab).', 'wp-feedback-comments') . '</p>';

    $styling_help = '<h3>' . __('Styling', 'wp-feedback-comments') . '</h3>'
    . '<p>' . __('There are several options for styling the look of your question and reply. You can obviously write your own text on all the different elements including text to replace the thumbs up/down symbols. A neat trick is the ability to use any Font Awesome symbol by writing the name of the symbol like ‘fas fa-utensils’.', 'wp-feedback-comments') . '</p>' 
	. '<p>' . __('You can abbreviate regular style like ‘far fa-utensils’ to just ‘fa-utensils’, but other styles like solid, light and brands you must write with the leading ‘<strong>fas&nbsp;</strong>fa-utensils’, ‘<strong>fal&nbsp;</strong>fa-utensils’ and ‘<strong>fab&nbsp;</strong>fa-twitter’.', 'wp-feedback-comments') . '</p>'
    . '<p>' . __('You can find more <a href="https://fontawesome.com/icons?d=gallery" target="_blank">symbols here</a>.', 'wp-feedback-comments') . '</p>' 
    . '<p>' . __('You can style using your own CCS stylesheet by turning off the internal CSS styles and linking to the URL of your own css file or including the styles in your theme css file.
A good starting point would be to copy the styles from <em>front-feedback-styles.css.</em>', 'wp-feedback-comments') . '</p>';

    $shortcode_help = '<h3>' . __('Shortcode', 'wp-feedback-comments') . '</h3>'
    . '<p>' . __('You can implement the question almost anywhere with the shortcode. The shortcode also give you the option to customize the question for individual pages.', 'wp-feedback-comments') . '</p>' 
    . '<p>' . __('Add the following shortcode to content, sidebar, footer, etc.', 'wp-feedback-comments') . '</p>' 
    . '<p>' . __('<pre><span style="color:green">[feedback_comment</span> <span style="color:blue">question=</span><span style="color:red">"Your question"</span> <span style="color:blue">yes=</span><span style="color:red">"Yes"</span> <span style="color:blue">no=</span><span style="color:red">"No"</span><span style="color:green">]</span></pre>', 'wp-feedback-comments') . '</p>' 
    . '<p>' . __('You can omit any of the variables and the default you set on this page will be used. In other words you could set the variable <em>question</em> and have the default thumbs up/down as alternatives.', 'wp-feedback-comments') . '</p>';

    $gdpr_help = '<h3>' . __('About GDPR', 'wp-feedback-comments') . '</h3>'
    . '<p>' . __('If you collect IP-numbers, names, e-mail adresses or any other personal data that might identify individuals you must comply with the GDPR regulation to operate within the EU. To circumvent this requirement you can anonymize IP-numbers and hide name and e-mail fields in the comment form.', 'wp-feedback-comments') . '</p>' 
    . '<p>' . __('The anonymization of the IP-number effectively set the three last digit (last number) of IP to 255. The IP number will thus be obfuscated but you still have some idea if several feedbacks where made by the same person.', 'wp-feedback-comments') . '</p>';
 
    $credits_help = '<h3>' . '<h3>' . __('Credits', 'wp-feedback-comments') . '</h3>' 
    . '<p>' . __('This plugin was built on "Article Feedback" by Themeidol. Even though the code has been copletely re-written, the basic idea and apparence was derived from the work done by Themeidol.', 'wp-feedback-comments') . '</p>'
    . '<p>' . __('This plugin was developed by Kjetil Uthne Hansen and is provided under a free GPL license and "as is" without warranty of any kind, either expressed or implied.', 'wp-feedback-comments') . '</p>'
    . '<p>' . __('Font Awesome pictogram font is provided by Fonicons Inc. under a free CC/GPL/MIT license.', 'wp-feedback-comments') . '</p>'
    . '<p>' . __('Read the <em>README.txt</em> file for more developer information and disclaimer.', 'wp-feedback-comments') . '</p>';
    
    $donate_help = '<h3>' . '<h3>' . __('Donate', 'wp-feedback-comments') . '</h3>' 
    . '<img src="'. plugin_dir_url(__FILE__) . '/assets/images/coffeepot_small.png" height="125" width="113" align="left" style="padding: 0 20px"/>' 
    . '<p>' . __('This software is free to use, but you can donate to support my work with a brew of strong hot coffee.', 'wp-feedback-comments') 
    . ' ' . __('Donate any amount you feel appropriate with PayPal by following the link below.', 'wp-feedback-comments') . '</p>'
    . '<p><a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&amp;business=uthne@mac.com&lc=US&item_name=Donate+to+Feedback+Comments+plugin&no_note=0&cn=&curency_code=USD&bn=PP-DonationsBF:btn_donateCC_LG.gif:NonHosted" target="_blank">' . __('Donate with PayPal', 'wp-feedback-comments') . '</a></p>';
        
    
    
    $args_general = array(
        'id'      => 'wpfc_general_tab', //unique id for the tab
        'title'   => __('General', 'wp-feedback-comments'),//unique visible title for the tab
        'content' => $general_help  //actual help text
    );

    $args_settings = array(
        'id'      => 'wpfc_settings_tab', //unique id for the tab
        'title'   => __('Settings', 'wp-feedback-comments'),//unique visible title for the tab
        'content' => $settings_help  //actual help text
    );

    $args_styling = array(
        'id'      => 'wpfc_styling_tab', //unique id for the tab
        'title'   => __('Styling', 'wp-feedback-comments'),//unique visible title for the tab
        'content' => $styling_help  //actual help text
    );

    $args_shortcode = array(
        'id'      => 'wpfc_shortcode_tab', //unique id for the tab
        'title'   => __('Shortcode', 'wp-feedback-comments'),//unique visible title for the tab
        'content' => $shortcode_help  //actual help text
    );

    $args_gdpr = array(
        'id'      => 'wpfc_gdpr_tab', //unique id for the tab
        'title'   => __('About GDPR', 'wp-feedback-comments'),//unique visible title for the tab
        'content' => $gdpr_help  //actual help text
    );

    $args_credits = array(
        'id'      => 'wpfc_credits_tab', //unique id for the tab
        'title'   => __('Credits', 'wp-feedback-comments'),//unique visible title for the tab
        'content' => $credits_help  //actual help text
    );

    $args_donate = array(
        'id'      => 'wpfc_donate_tab', //unique id for the tab
        'title'   => __('Donate', 'wp-feedback-comments'),//unique visible title for the tab
        'content' => $donate_help  //actual help text
    );

    $screen->add_help_tab( $args_general );
    $screen->add_help_tab( $args_settings );
    $screen->add_help_tab( $args_styling );
    $screen->add_help_tab( $args_shortcode );
    $screen->add_help_tab( $args_gdpr );
    $screen->add_help_tab( $args_credits );
    $screen->add_help_tab( $args_donate );
}
