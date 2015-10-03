(function() {
    // distinct list of all attachment IDs populated
    var ids = [];

    // the HTML elements to be updates
    var pendingIcons;

    // current index in pendingIcons
    var i = 0;

    // find all document-icons without icons generated and start processing
    jQuery(document).ready(function() {
        pendingIcons = jQuery('.document-icon[data-dg-id]');
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

        for (; i < pendingIcons.length; i++) {
            var id = jQuery(pendingIcons[i]).data('dg-id');

            // if we have multiple galleries, we could have multiple elements
            // needing the same icon amd no need to request multiple times
            if (-1 !== jQuery.inArray(id, ids)) {
                continue;
            }

            ids.push(id);
            idBatch.push(id);

            if (idBatch.length === batchLimit) {
                break;
            }
        }

        if (idBatch.length != 0) {
            // request the next batch of icons
            jQuery.post(ajaxurl, { action: 'dg_generate_icons', ids: idBatch }, function(response) {
                // find all of the relevant elements and set the img src
                for (var id in response) {
                    if (response.hasOwnProperty(id)) {
                        var target = jQuery('.document-icon[data-dg-id="' + id + '"] img');
                        if (target.attr('src') !== response[id]) {
                            (function(id, target) {
                                target.fadeOut('fast', function () {
                                    jQuery(this).attr('src', response[id]);
                                    jQuery(this).fadeIn('fast');
                                })
                            })(id, target);
                        }
                    }
                }

                // start next batch once this response is processed
                retrieveNextIcons();
            });
        }
    }
})();