/*
  Loading Spinner for Like Count
 */
jQuery(document).ready(function ($) {

    $('.helpful-button').on('click', function (e) {

        $('<div class="loading">Loading&#8230;</div>').appendTo('#page');

        var baseUrl = document.location.origin,
            comment_id = this.id,
            helpBtn = $(this);

        jQuery.ajax({
            url: baseUrl + '/wp-admin/admin-ajax.php',
            type: 'post',
            data: {
                action: "helpful_data",
                commentId: comment_id
            },
            success: function (response) {

                $('.loading').hide();

                var responseValue = JSON.parse(response);

                if (responseValue['status'] === true) {

                    var userCount = responseValue['data'];
                    helpBtn.children("span").html(userCount);

                } else {

                    helpBtn.nextAll("span").remove();
                    helpBtn.after(' <span>' + responseValue['data'] + '</span>');

                }

            }
        });

    });

});