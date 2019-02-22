jQuery(document).ready(function ($) {
    jQuery(".m-feedback-prompt__social_thumbsup").on("click", function (e) {
        e.preventDefault();

        jQuery(this).siblings('.m-feedback-prompt_form').removeClass('m-feedback-prompt__button--active');
        jQuery(this).siblings('.m-feedback-prompt__social_thumbsdown').removeClass('m-feedback-prompt__button--active');
        jQuery(this).toggleClass('m-feedback-prompt__button--active');
        jQuery(this).siblings('.m-feedback-prompt__form').removeClass('show');
        jQuery(this).siblings('.m-feedback-prompt__social').toggleClass("show");


        var currenturl = jQuery('#currenturl').val();
        var message = FeedbackAjax.yestext;
        var feedbackfullname = "";

        var currenttitle = jQuery('#currenttitle').val();
        var currentid = jQuery('#currentid').val();
        var data = {
            action: 'send_feedback',
            name: "",
            email: "",
            message: message,
            id: currentid,
            url: currenturl,
            title: currenttitle
        };

        if (FeedbackAjax.sendyes == 'yes') {
            jQuery.post(FeedbackAjax.ajaxurl, data, function (response) {

            });
        }
    });

    jQuery(".m-feedback-prompt__social_thumbsdown").on("click", function (e) {
        e.preventDefault();

        jQuery(this).siblings('.m-feedback-prompt__social').removeClass('m-feedback-prompt__button--active');
        jQuery(this).toggleClass('m-feedback-prompt__button--active');
        jQuery(this).siblings('.m-feedback-prompt__social').removeClass('show');
        jQuery(this).siblings('.m-feedback-prompt__form').toggleClass("show");
        jQuery(this).siblings('.m-feedback-prompt__form').find("#contact-form").hide();
        jQuery(this).siblings('.m-feedback-prompt__form').find('.thanks').removeClass('feedback-displayall').addClass('feedback-nodisplayall');
        jQuery("#mailinglistsubmit").hide();
        jQuery('.thanks').removeClass('feedback-nodisplayall');
        jQuery(".thanks").addClass('feedback-displayall');

        var currenturl = jQuery('#currenturl').val();
        var message = "";
        var feedbackfullname = "";

        var currenttitle = jQuery('#currenttitle').val();
        var currentid = jQuery('#currentid').val();
        var data = {
            action: 'send_feedback',
            name: "",
            email: "",
            message: message,
            id: currentid,
            url: currenturl,
            title: currenttitle
        };


        jQuery.post(FeedbackAjax.ajaxurl, data,
            function (response) {
                if (response == 'success' || response == '0') {} else {
                    jQuery(".thanks .m-contact").html('<strong>' + response + '</strong>');
                }
            });
        return false;
    });


    jQuery(".m-feedback-prompt_form").on("click", function (e) {
        e.preventDefault();

        jQuery(this).siblings('.m-feedback-prompt__social').removeClass('m-feedback-prompt__button--active');
        jQuery(this).toggleClass('m-feedback-prompt__button--active');
        jQuery(this).siblings('.m-feedback-prompt__social').removeClass('show');
        jQuery(this).siblings('.m-feedback-prompt__form').toggleClass("show");
        jQuery(this).siblings('.m-feedback-prompt__form').find("#contact-form").show();
        jQuery(this).siblings('.m-feedback-prompt__form').find('.thanks').removeClass('feedback-displayall').addClass('feedback-nodisplayall');
    });
    //Ajax Mail for Feedback

    jQuery("#contact-form").submit(function () {
        var email = jQuery('#mailinglistemail').val();
        if (email != "" && (email.indexOf("@") == -1 || email.indexOf(".") == -1)) {
            jQuery("#contact-form #feedback-message").text("Please enter a valid email address.");
            return false;
        } else {

            var feedbackfullname = jQuery('#feedbackfullname').val();
            var currenturl = jQuery('#currenturl').val();
            var currenttitle = jQuery('#currenttitle').val();
            var currentid = jQuery('#currentid').val();
            var message = jQuery('#feedbackmessage').val();
            var data = {
                action: 'send_feedback',
                name: feedbackfullname,
                email: email,
                message: message,
                id: currentid,
                url: currenturl,
                title: currenttitle
            };
            jQuery("#mailinglistsubmit").hide();
            jQuery(".ajaxsave").show();
            jQuery.post(FeedbackAjax.ajaxurl, data,
                function (response) {
                    if (response == 'success' || response == '0') {
                        jQuery("#contact-form").hide();
                        jQuery('.thanks').removeClass('feedback-nodisplayall');
                        jQuery(".thanks").addClass('feedback-displayall');
                    } else {
                        jQuery("#contact-form #feedback-message").html(response);
                    }
                });
            return false;
        }
    });

});