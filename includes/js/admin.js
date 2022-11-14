jQuery(document).ready(function () {
    jQuery('#pmpro_affiliates_mark_as_paid').on('click', function (e) {
        e.preventDefault();

        var data = {
            action: 'pmpro_affiliates_mark_as_paid',
            order_id: jQuery(this).attr('order_id'),
            paid_status: pmpro_affiliates_admin.paid_status,
            _wpnonce: jQuery(this).attr('_wpnonce')
        }

        console.log(data);

        jQuery.ajax({
            url: pmpro_affiliates_admin.ajaxurl,
            type: 'POST',
            timeout: 2000,
            dataType: 'html',
            data: data,
            error: function (xml) {
                alert('Error marking as paid.');
            },
            success: function (responseHTML) {
                if (responseHTML == 'error') {
                    alert('Error marking item as paid.');
                } else {
                    jQuery('span.pmpro_affiliate_paid_status').html(data.paid_status);
                }
            }
        });
    });

});
