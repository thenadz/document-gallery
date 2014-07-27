jQuery(document).ready(function(){
   jQuery('.cb-ids').change(function() {
      if(jQuery(this).is(':checked')) {
         jQuery(this).closest('tr').addClass('selected');
      }
      else {
         jQuery(this).closest('tr').removeClass('selected');
      }
   });
   jQuery('th input:checkbox').change(function() {
      if(jQuery(this).is(':checked')) {
         jQuery('#ThumbsTable tbody tr').addClass('selected');
      }
      else {
         jQuery('#ThumbsTable tbody tr').removeClass('selected');
      }
   });
   jQuery('input.current-page').bind('keypress', {}, function(e) {
      var code = (e.keyCode ? e.keyCode : e.which);
      if (code == 13) { //Enter keycode
         e.preventDefault();
         jQuery(location).attr('href','?'+jQuery.param(jQuery.extend(URL_params,{ sheet: this.value })));
      }
   });
   jQuery('select.limit_per_page').change(function() {
      jQuery(location).attr('href','?'+jQuery.param(jQuery.extend(URL_params,{ limit: this.value })));
   });
   jQuery('.deleteSelected').click(function() {
      var data = {
         action: 'DG_multipleDeletion',
         'selectedIDs[]' : []
      };
      jQuery('.cb-ids:checked').each(function() {
         data['selectedIDs[]'].push(jQuery(this).val());
      });
      jQuery.post(ajaxurl, data, function(response) {
         var result = eval(response);
         for (var index in result){
            jQuery('input[type=checkbox][value='+result[index]+']').closest('tr').fadeOut('slow', 0.00, function() {jQuery(this).slideUp('slow', function() {jQuery(this).remove();});});
         }
      });
   });
});