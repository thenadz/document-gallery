(function() {
    var ids = [];
    var pendingIcons;
    var i = 0;

    jQuery(document).ready(function() {
        pendingIcons = jQuery('.document-icon[data-dg-id]');
        retrieveNextIcons();
    });

    /**
     * Sends AJAX request to server requesting some of the not-yet-generated icons (if any).
     */
    function retrieveNextIcons() {
        var batchLimit = 1;
        var idBatch = [];

        for (; i < pendingIcons.length; i++) {
            var id = jQuery(pendingIcons[i]).data('dg-id');
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
            jQuery.post(ajaxurl, { action: 'dg_generate_icons', ids: idBatch }, function(response) {
                for (var id in response) {
                    if (response.hasOwnProperty(id)) {
                        console.log('Adding src (' + response[id] + ') for ID = ' + id);
                        jQuery('.document-icon[data-dg-id="' + id + '"] img').attr('src', response[id]);
                    }
                }

                // start next batch once this response is processed
                retrieveNextIcons();
            });
        }
    }
})();