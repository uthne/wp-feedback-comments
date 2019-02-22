=== Feedback Comments ===
Contributors: uthne
Built on: Article feedback by themeidol
Tags: icons, font-awesome, feedback, share, wp article feedback, WordPress Feedback Comments, Article, article, post, page, custom post type
Version: 1.3
Requires at least: 3.0
Tested up to: 5.0.3
Stable tag: 1.3
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Use WP comments to collect feedback on any post type.



== Disclaimer ==

This software is provided free and "as is" without warranty of any kind, either expressed or implied, including, 
but not limited to, the implied warranties of merchantability and fitness for a particular purpose. 
The entire risk as to the quality and performance of the program is with you. 
Should the program prove defective, you assume the cost of all necessary servicing, repair or correction.


== Article Feedback ==

This plugin was built on the work done by themeidol for the Article Feedback plugin. 
The two plugins differ in the way feedbacks are handled. 
If you prefer to have feedback sendt by mail from your website, use the Article Feedback plugin.
http://themeidol.com/


== Description ==

Add "Was this article helpful?" at the end, start and/or at custom hook of any post type. 
Positive reply give option to share and negative response will prompt for feedback. 
The plugin will remove the normal comments from the post/pages you use the feedback form.


== Font Awesome ==

Feedback comments comes with Font Awesome version 5.4.1 Free font for pictogram symbols.
The font carry its own licenses (https://fontawesome.com/license/free),
and are implemented "as is" as recomended by Font Awesome.
You can turn off loading of the Font Awesome fontset in the plugins settings.


== GDPR compliance ==

If you collect IP-numbers, names, e-mail adresses or any other personal data that might identify individuals
you must comply with the GDPR regulation to operate within the EU. To circumvent this requirement you can anonymize
IP-numbers and hide name and e-mail fields in the comment form.
The anonymization of the IP-number effectively set the three last digit (last number) of IP to 255. 
The IP number will thus be obfuscated but you still have some idea if several feedbacs where made by the same person.


== Features ==

 - Options to add "Was this article helpful?" at the end, start and/or at custom hook of any post type.
 - Options to place on front page, pages, any post types and archive.
 - Options to actively exclude any custom post type.
 - Options to exlude from post ID's
 - Options to stop comments feed (approved feedbacks will show up in feed when not stopped)
 - Options to anonymize three last digits in responders IP-number to avoid requirements in GDPR
 - Options to turn on/off name and e-mail fields in response message form
 - Options to turn off negative response message form (and only receive default negative feedback text)
 - Options to turn off pugins internal styling of feedback form
 - Options to turn off internal loading of Font Awesome font (if font is loadesd through theme or other plugin)
 - Styling of form from options page
 - Unique classes for CSS-styling of form
 - Possible to link to custom css file URL
 - All texts replacable, including thumbs-up and thumbs-down, from options page
 - Yes/no (thumbs up/down) support any Font Awesome symbol 
 - Select prefered social media share buttons (Facebook, Twitter, LinkedIn)
 - Adds "Export selected comment", "Export selected post" and "Export all" in batch menu under comments
 - Exports comments to CSV or Excel format (Options page)
 - Options to select exported columns in spreadsheet (Options page)
 - Options page as sub-menu of Comments or sub-menu of Settings
 - Includes internal help-tab on options page
 - All texts in the plugin can be localized 

The appearance of the feedback form may be affected by the Wordpress theme in use.

You can turn off all internal styling of from the plugin on the options page.
To style the form with CSS, copy the styles from the '/assets/css/front-feedback.css' to your theme css file
and do your edits in the theme css file, or link to a custom CSS file from settings.

 

== Installation ==

1. Upload the plugin files to the '/wp-content/plugins/[plugin-name]' directory, or download the plugin as ZIP-file install with the "Add new" button on the plugin page in WordPress.  
2. Activate the plugin on the Plugins page.  
3. Place using one of the methods (before/after content, PHP custom hook or Shortcode) within the content of your posts or pages.  
4. Use the Shortcode method inside your text widgets.

__Before / After content__

Use the settings on the plugin settings page to insert the question before and/or after the content on your page.


__Custom hook__

If you like to place the feedback in any other place of your design than before or after the content,
simply place the custom hook in your template, and select 'Custom Hook' on the settings page.

The code to use is <?php do_action('fc_feedback_hook'); ?>


__Shortcode__

You can use a shortcode in your posts, pages widgets and place that supports shortcodes to dispaly Feedback comments.
The shortcode to use is '[feedback_comment question="Your question" yes="Yes" no="No"]'.
Any of the attributes 'question', 'yes' and 'no' can be omitted. Attributes will override settings from the plugin settings page if used.





== Frequently Asked Questions ==
__A question that someone might have__
An answer to that question.



== Screenshots ==

1.  Feedback Comments Backend Settings.
2.  Feedback Comments Front End Look with Thumbs Up clicked.
3.  Feedback Comments Front End Look when Thumbs Up is clicked with author fields.
4.  Feedback Comments Front End Look when Thumbs Down is clicked without author fields.


== Changelog ==
= 1.3.0 =
Tidying up code and some small bugfixes, and language updates.
= 1.2.9 =
Added PayPal donation link. Getting ready for version 3.0
= 1.2.8 =
Added options to turn off negative response message form
This option will send only default negative feedback text.
Small bugfixes in FontAwesome display.
= 1.2.7 =
Added placeholder text in message field
Added default negative text that will replace empty feedback message.
= 1.2.6 =
Font Awesome upgraded from version 4.7 to 5.4
= 1.2.5 =
Includes internal help-tab on options page
Supports any Font Awesome symbol in Yes/No settings text-filed
Fixed broken logic in "include on post types"
= 1.2 =
Added help tabs in admin
Reversed "Exclude on types" to "Include on types"
Added option to move submenu from Comments to Settings
Removed share button for Google+, RIP
Small bugfixes
= 1.1 =
GDPR compliance through anonymizing responders IP-number.
Some bug fixes frontside css file to avoid setting styles for generic input fields.
= 1.0 =
* Default:Add "Was this article helpful?" at the end, start and/or at custom hook of any post type. Positive reply give option to share and negative response will prompt for feedback. The plugin will remove the normal comments from the post/pages it is in use.




== License ==

Good news, this plugin is free for everyone! Since it's released under the GPL v3, you can use it free of charge on your personal or commercial blog. The general idea of GPL license is that you can use the software for anything you like, but you must publish under GPL v3 license if you chose to make it publically available - thus giving other the same freedom of use.

The pictogram font Font Awesome carries its own FREE Creative Commons/GPL/MIT licenses.

Donate with PayPal:
https://www.paypal.com/cgi-bin/webscr?cmd=_donations&amp;business=uthne@mac.com&lc=US&item_name=Feedback+Comments+plugin&no_note=0&cn=&curency_code=USD&bn=PP-DonationsBF:btn_donateCC_LG.gif:NonHosted


== Translations ==

The plugin comes with Norwegian translation, please refer to the [WordPress Codex](http://codex.wordpress.org/Installing_WordPress_in_Your_Language "Installing WordPress in Your Language") for more information about activating the translation. 