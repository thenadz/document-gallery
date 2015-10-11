(function() {
    // distinct list of all attachment IDs populated
    var ids = [];

    // current index in pendingIcons
    var i = 0;

    // find all document-icons without icons generated and start processing
    jQuery(document).ready(function() {
        jQuery('.document-icon[data-dg-id]').each(function() {
            var id = jQuery(this).data('dg-id');

            // if we have multiple galleries, we could have multiple elements
            // needing the same icon and no need to request multiple times
            if (-1 === jQuery.inArray(id, ids)) {
                ids.push(id);
            }
        });

        retrieveNextIcons();
    });

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
            jQuery.post(ajaxurl, { action: 'dg_generate_icons', ids: idBatch }, processRetrievedThumbnails);
        }
    }

    /**
     * Find all of the relevant elements and set the img src, then start next batch of thumbnail retrieval.
     * @param response Associative array mapping attachment ID to thumbnail URL.
     */
    function processRetrievedThumbnails(response) {
        for (var id in response) {
            if (response.hasOwnProperty(id)) {
                var target = jQuery('.document-icon[data-dg-id="' + id + '"] img');
                (function(id, target) {
                    target.fadeOut('fast', function () {
                        jQuery(this).attr('src', response[id]);
                        jQuery(this).fadeIn('fast');
                    })
                })(id, target);
            }
        }

        // start next batch once this response is processed
        retrieveNextIcons();
    }
})();