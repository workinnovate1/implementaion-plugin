jQuery(document).ready(function ($) {

    if (!aimtData || !aimtData.show_alert) return;

    console.log('Post or page has been saved. Translation popup should be shown.');

    const translateNow = confirm(
        'The post or page has been saved successfully.\n\nWould you like to translate it now?'
    );

    $.post(aimtData.ajax_url, {
        action: 'aimt_clear_alert_flag',
        post_id: aimtData.post_id,
        nonce: aimtData.nonce
    });

    if (translateNow) {
        alert('User chose to translate the post.');
        // window.location.href = 'admin.php?page=aimt-onboarding&post_id=' + aimtData.post_id;
    } else {
        console.log('User chose not to translate the post.');
    }
});
