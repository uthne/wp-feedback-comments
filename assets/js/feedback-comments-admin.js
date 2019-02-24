jQuery(function ($) {
    //Color Picker
    jQuery('#ssthumbsup,#ssthumbsdown').wpColorPicker();
    jQuery("#fader").on("input", function () {
        //jQuery('#fontsize').css('font-size',jQuery(this).val() + "em");
        jQuery('#fontsize').html(jQuery(this).val() + "em");
        jQuery('#fader').val(jQuery(this).val());
    });
    jQuery("#noalign").on("input",function () {
    		//jQuery('#fontsize').css('font-size',jQuery(this).val() + "em");
            jQuery('#noalignout').html(jQuery(this).val() + "%");
            jQuery('#noalign').val(jQuery(this).val());
    });
    jQuery("#yesalign").on("input",function () {
    		//jQuery('#fontsize').css('font-size',jQuery(this).val() + "em");
            jQuery('#yesalignout').html(jQuery(this).val() + "%");
            jQuery('#yesalign').val(jQuery(this).val());
    });
    var FC_GET = [];
    window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function (a, name, value) {
        FC_GET[name] = value;
    });
    if (FC_GET['export_ids'] !== false && FC_GET['export_ids'] != '' && typeof (FC_GET['export_ids']) == 'string') {
        expIDs = FC_GET['export_ids'];
        expIDs = expIDs.split('%2C').join(',');
        //alert ('DEBUG Export IDs: ' + expIDs);/**/
    }
});
