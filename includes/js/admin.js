jQuery(document).ready(function () {
    // Functionality to mark commission as paid for a particular order.
    jQuery('.pmpro_affiliates_mark_as_paid').on('click', function (e) {
        e.preventDefault();

        var data = {
            action: 'pmpro_affiliates_mark_as_paid',
            order_id: jQuery(this).attr('order_id'),
            paid_status: pmpro_affiliates_admin.paid_status,
            _wpnonce: jQuery(this).attr('_wpnonce')
        }

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
                    jQuery('#order_' + data.order_id).html(data.paid_status);
                }
            }
        });
    });


    // Functionality to reset paid commissions to unpaid for a particular order.
    jQuery('.pmpro_affiliates_reset_paid_status').on('click', function (e) {
        e.preventDefault();

        var data = {
            action: 'pmpro_affiliates_reset_paid_status',
            order_id: jQuery(this).attr('order_id'),
            reset_status: pmpro_affiliates_admin.reset_status,
            _wpnonce: jQuery(this).attr('_wpnonce')
        }

        jQuery.ajax({
            url: pmpro_affiliates_admin.ajaxurl,
            type: 'POST',
            timeout: 2000,
            dataType: 'html',
            data: data,
            error: function (xml) {
                alert('Error resetting order.');
            },
            success: function (responseHTML) {
                if (responseHTML == 'error') {
                    alert('Error resetting paid status.');
                } else {
                    jQuery('#order_' + data.order_id).html(data.reset_status);
                }
            }
        });
    });

    // Functionality for autocomplete search via AJAX using autocomplete jQuery
    jQuery(function () {
        var searchRequest;
        jQuery('#affiliateuser').autocomplete({
            delay: 500,
            source: function (term, suggest) {
                try { searchRequest.abort(); } catch (e) { }
                searchRequest = jQuery.post(pmpro_affiliates_admin.ajaxurl,
                    {
                        search: term.term,
                        action: 'pmpro_affiliates_autocomplete_user_search',
                        search_nonce: pmpro_affiliates_admin.search_nonce
                    },
                    function (res) {
                        suggest(res.data);
                    });
            }
        });
    });
}); //end of document ready
