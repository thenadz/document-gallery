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
   jQuery('#tab-Thumbnail').submit( function (event) {
	  event.preventDefault();
	  if (jQuery('.cb-ids:checked').length > 0) {
         var a = jQuery(this).attr('action');
         var b = jQuery(this).serialize() + '&document_gallery%5Bajax%5D=true';
         jQuery.post(a, b, function(response) {
    	    if (response.indexOf("\n") == -1) {
    		   var result = eval(response);
    	       for (var index in result) {
    	          jQuery('input[type=checkbox][value='+result[index]+']').closest('tr').fadeOut('slow', 0.00, function() {jQuery(this).slideUp('slow', function() {jQuery(this).remove();});});
    	       }
    	    } else {
    		   console.log('Invalid response from server:');
    		   console.log(response);
    	    }
         } ).fail(function() {
            console.log( 'Problem in reaching the server' );
         });
	  }
      return false;
   });
   
   jQuery('#tab-Advanced #options-dump').click(function() {
      jQuery(this).select();
   });
   
   function toggleSpoiler() {
      var sel = getSelection().toString();
      if(!sel){
         jQuery(this).next().slideToggle('slow');
         jQuery(this).find('.dashicons').toggleClass('dashicons-arrow-down-alt2 dashicons-arrow-up-alt2');
         jQuery(this).toggleClass('expander collapser');
         jQuery(this).attr('title', (jQuery(this).hasClass('expander') ? 'Click to Expand' : 'Click to Collapse'));
      }
   }

   jQuery('.expander').click(toggleSpoiler);

   if (jQuery('.spoiler-body').length)
   {
      jQuery('.expandAll, .collapseAll').addClass('button');
      jQuery('.expandAll').click(function(e) {
         e.preventDefault();
         jQuery('.expander').trigger('click');
      });
      jQuery('.collapseAll').click(function(e) {
         e.preventDefault();
         jQuery('.collapser').trigger('click');
      });
   }

   jQuery('.levelSelector input').change(function() {
      if (jQuery(this).val() == 'all') {
         jQuery('.levelSelector input').not("[value='all']").prop('checked', jQuery(this).is(':checked'));
         jQuery(this).is(':checked') ? jQuery('#LogTable tbody tr').show() : jQuery('#LogTable tbody tr').hide();
      }
      else {
         jQuery(this).is(':checked') ? jQuery('#LogTable tbody tr:has(span.'+jQuery(this).val()+')').show() : jQuery('#LogTable tbody tr:has(span.'+jQuery(this).val()+')').hide();
         if ( (jQuery('.levelSelector input:checked').not("[value='all']").length + 1) == jQuery('.levelSelector input[type="checkbox"]').length ) {
            jQuery('.levelSelector input[value="all"]').prop('checked', true);
         } else {
            jQuery('.levelSelector input[value="all"]').prop('checked', false);
         }
      }
   });
});