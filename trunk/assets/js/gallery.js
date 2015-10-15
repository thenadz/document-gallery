(function($) {
    // distinct list of all document IDs to be fetched via AJAX requests
    var ids;

    // current index in ids array
    var i;

    // cached pagination pages
    var prevPg, nextPg;

    // find all document-icons without icons generated and start processing
    $(document).ready(function() {
        resetPendingIcons();

        // listen for all pagination click in current DOM and any future elements
        $('body').delegate('.dg-paginate-wrapper a.paginate', 'click', function (e) {
            var target = $(this).closest('.dg-paginate-wrapper');
            var atts = target.children('div[data-shortcode]').data('shortcode');
            var newPg;

            if (!atts.hasOwnProperty('skip')) {
                atts['skip'] = 0;
            }

            if ($(this).hasClass('left')) {
                atts['skip'] -= atts['limit'];

                nextPg = target;
                newPg = prevPg;
                prevPg = null;
            } else {
                atts['skip'] += atts['limit'];

                prevPg = target;
                newPg = nextPg;
                nextPg = null;
            }

            // perform pagination either by cached pg or new pg
            if (newPg) {
                target.replaceWith(newPg);
                resetPendingIcons();
            } else {
                retrieveGallery(atts, target);
            }

            e.preventDefault();
        });
    });

    /**
     * Collects all of the DG icons that need to be generated and starts requesting them via AJAX.
     */
    function resetPendingIcons() {
        ids = [];
        i = 0;

        $('.document-icon[data-id]').each(function() {
            var id = $(this).data('id');

            // if we have multiple galleries, we could have multiple elements
            // needing the same icon and no need to request multiple times
            if (-1 === $.inArray(id, ids)) {
                ids.push(id);
            }
        });

        retrieveNextIcons();
    }

    /**
     * Requests a gallery generated with the given attributes to populate the given target element.
     * @param atts array The gallery shortcode attributes.
     * @param target element The element to be updated with the AJAX HTML response.
     */
    function retrieveGallery(atts, target) {
        $.post(ajaxurl, { action: 'dg_generate_gallery', atts: atts }, function(html) {
            target.replaceWith(html);
            resetPendingIcons();
        });
    }

    /**
     * Sends AJAX request to server requesting some of the not-yet-generated icons (if any).
     */
    function retrieveNextIcons() {
        // max number of icons to retrieve per AJAX request
        var batchLimit = 2;

        // IDs already retrieved
        var idBatch = [];

        for (; i < ids.length; i++) {
            if (idBatch.length === batchLimit) {
                break;
            }

            idBatch.push(ids[i]);
        }

        if (idBatch.length != 0) {
            // request the next batch of icons
            $.post(ajaxurl, { action: 'dg_generate_icons', ids: idBatch }, processRetrievedThumbnails);
        }
    }

    /**
     * Find all of the relevant elements and set the img src, then start next batch of thumbnail retrieval.
     * @param response Associative array mapping attachment ID to thumbnail URL.
     */
    function processRetrievedThumbnails(response) {
        for (var id in response) {
            if (response.hasOwnProperty(id)) {
                var target = $('.document-icon[data-id="' + id + '"] img');
                target.removeAttr('data-id');

                (function(id, target) {
                    var speed = 'fast';
                    target.fadeOut(speed, function () {
                        $(this).attr('src', response[id]);
                        $(this).fadeIn(speed);
                    });
                })(id, target);
            }
        }

        // start next batch once this response is processed
        retrieveNextIcons();
    }
})(jQuery);