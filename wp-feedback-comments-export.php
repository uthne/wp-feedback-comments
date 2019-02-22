<?php
define( 'WP_USE_THEMES', false );

$path = preg_replace('/wp-content.*$/','',__DIR__);
require( $path.'wp-load.php' );

global $wpdb;
$options = get_option('feedback_options');

if (isset($_GET['export']) && $_GET['export'] !='') {
    $export = explode(',',$_GET['export']);
    $export_ext = ( $options['ss-export-format'] ) ? $options['ss-export-format'] : 'csv';

    $td_start = ($export_ext == 'xls') ? '<td>' : '';
    $td_end = ($export_ext == 'xls') ? '</td>' : ';';
    $tr_start = ($export_ext == 'xls') ? '<tr>' : '';
    $tr_end = ($export_ext == 'xls') ? '</tr>' : "\n";

    $return = ($export_ext == 'xls') ? '<table border="1"><tr>' : '';

    if ( in_array( 'message', $options['ss-export-select'] ) ) $return .= $td_start . 'Melding' . $td_end;
    if ( in_array( 'articlename', $options['ss-export-select'] ) ) $return .= $td_start . 'Artikkel' . $td_end;
    if ( in_array( 'articleurl', $options['ss-export-select'] ) ) $return .= $td_start . 'URL</td>' . $td_end;
    if ( in_array( 'authorsname', $options['ss-export-select'] ) ) $return .= $td_start . 'Avsender' . $td_end;
    if ( in_array( 'authorsemail', $options['ss-export-select'] ) ) $return .= $td_start . 'Epost' . $td_end;
    $return .= $tr_end;
    foreach( $export as $eid ) {
        $return .= $tr_start;
        $the_comment = get_comment( $eid ); 
        $the_post = $the_comment->comment_post_ID; 

        $the_title = get_the_title($the_post); 
        $the_link  = get_permalink($the_post);

        if ( in_array( 'message', $options['ss-export-select'] ) ) $return .= $td_start . $the_comment->comment_content . $td_end;
        if ( in_array( 'articlename', $options['ss-export-select'] ) ) $return .= $td_start . $the_title . $td_end;
        if ( in_array( 'articleurl', $options['ss-export-select'] ) ) $return .= $td_start . $the_link . $td_end;
        if ( in_array( 'authorsname', $options['ss-export-select'] ) ) $return .= $td_start . $the_comment->comment_author . $td_end;
        if ( in_array( 'authorsemail', $options['ss-export-select'] ) ) $return .= $td_start . $the_comment->comment_author_email . $td_end;
        $return .= $tr_end;
    }
    $return .= ($export_ext == 'xls') ? '</table>' : '';
}

$fileName = 'Comments_' . date("Ymd") . '.' . $export_ext;
header( "Content-type: application/vnd.ms-excel" ); 
header( "Content-Disposition: attachment; filename=$fileName" );

echo $return;
exit;
?>