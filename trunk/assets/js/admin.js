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
      if (code == 13) {//Enter keycode
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
         var b = jQuery(this).serialize() +
            '&document_gallery%5Bajax%5D=true' +
            '&document_gallery%5Bcleanup%5D=true';
         jQuery.post(a, b, function(response) {
            if (response.indexOf("\n") == -1) {
               eval('var reply = ' + response + ';');
               if (reply.result) {
                  var result = reply.deleted;
                  for (var index in result) {
                     jQuery('input[type=checkbox][value='+result[index]+']').closest('tr').fadeOut('slow', 0.00, function() {jQuery(this).slideUp('slow', function() {jQuery(this).remove();});});
                  }
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

   if (jQuery('.spoiler-body').length) {
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

   function DragDropFilesStop(e) {
      e = e || event;
      if (e.dataTransfer.types) {
         //Testing if dragenter/dragover Event Contains Files - http://css-tricks.com/snippets/javascript/test-if-dragenterdragover-event-contains-files/
         for (var i = 0; i < e.dataTransfer.types.length; i++) {
            if (e.dataTransfer.types[i] == 'Files') {
               //Or maybe it is better just to test e.dataTransfer.files[0].name - http://stackoverflow.com/a/12622904/3951387
               //NO: before drop the list is not accessible
               e.stopPropagation();
               e.preventDefault();
               e.dataTransfer.dropEffect = 'none';
               break;
            }
         }
         //jQuery way - Not working
         /*if (jQuery.inArray('Files', e.dataTransfer.types)) {
            e.stopPropagation();
            e.preventDefault();
            e.dataTransfer.dropEffect = 'none';
         }*/
      }
   }

   //Preventing browser from acting on drag&dropped files beside the dedicated areas
   window.addEventListener('dragover', DragDropFilesStop, false);
   window.addEventListener('drop', DragDropFilesStop, false);

   function handleDragOver(e) {
      e = e || event;
      if (e.originalEvent.dataTransfer.types) {
         for (var i = 0; i < e.originalEvent.dataTransfer.types.length; i++) {
            if (e.originalEvent.dataTransfer.types[i] == 'Files') {
               e.stopPropagation();
               e.preventDefault();
               //Have to exploit broker to access standart properties while using jQuery to bind handlers - http://stackoverflow.com/a/14792183/3951387
               e.originalEvent.dataTransfer.dropEffect = 'move';
               return false;
            }
         }
      }
   }

   //Firing HTML5 DragLeave only when all the DragEnter'ed child elements were DragLeave'd - http://stackoverflow.com/a/21002544
   var counter = {};
   function handleDragEnter(e) {
      // this / e.target is the current hover target.
      e = e || event;
      if (e.originalEvent.dataTransfer.types) {
         for (var i = 0; i < e.originalEvent.dataTransfer.types.length; i++) {
            if (e.originalEvent.dataTransfer.types[i] == 'Files') {
               this.classList.add('dragover');
               counter[jQuery(this).data('entry')]++;//or without jQuery: this.getAttribute('data-entry')
               break;
            }
         }
      }
   }

   function handleDragLeave(e) {
      e = e || event;
      if (e.originalEvent.dataTransfer.types) {
         for (var i = 0; i < e.originalEvent.dataTransfer.types.length; i++) {
            if (e.originalEvent.dataTransfer.types[i] == 'Files') {
               counter[jQuery(this).data('entry')]--;
               if (counter[jQuery(this).data('entry')] === 0) {
                  this.classList.remove('dragover');// this / e.target is previous target element.
               }
               break;
            }
         }
      }
   }

   function handleDrop(e) {
      e = e || event;
      if (e.originalEvent.dataTransfer.types) {
         for (var i = 0; i < e.originalEvent.dataTransfer.types.length; i++) {
            if (e.originalEvent.dataTransfer.types[i] == 'Files') {

               e.stopPropagation();// Stops some browsers from redirecting.
               e.preventDefault();

               processFiles(e.originalEvent.dataTransfer.files,jQuery(this).data('entry'));
               counter[jQuery(this).data('entry')] = 0;
               this.classList.remove('dragover');
               break;
            }
         }
      }
   }

   function handleBrowseButton(e) {
      e = e || event;
      processFiles(e.target.files,jQuery(this).closest('tr').data('entry'));
      //Was thinking about purging input:file control - http://stackoverflow.com/questions/1043957/clearing-input-type-file-using-jquery
      //Decided just to get rid of name properties thus such controls wouldn't be taken into consideration during form submit or processed with FormData
   }

   function processFiles(files,entry) {
      for (var i = 0, f; f = files[i]; i++) {
         //Processing only first qualifying file
         if (f.type.indexOf('image/') == 0 && typeof dg_admin_vars.upload_limit != 'undefined' && f.size <= parseInt(dg_admin_vars.upload_limit)) {
            var form = document.getElementById('tab-Thumbnail');
            var formData = new FormData(form);
            formData.append('document_gallery[entry]', entry);
            formData.append('document_gallery[ajax]', 'true');
            formData.append('document_gallery[upload]', 'true');
            formData.append('file', f);
            var xhr = new XMLHttpRequest();
            xhr.open('POST', jQuery('#tab-Thumbnail').attr('action'));
            var theImg = jQuery('[data-entry='+entry+']').find('.column-icon img');
            xhr.onreadystatechange = function() {
               if (xhr.readyState == 4) {
                  if (xhr.responseText.indexOf("\n") == -1) {
                     eval('var response = ' + xhr.responseText + ';');
                     if (response.result) {
                        // check if generated thumbnail has the same url
                        if (response.url === theImg.attr('src')) {
                           theImg.attr('src', theImg.attr('src') + '?' + new Date().getTime());
                        } else {
                           theImg.attr('src', response.url);
                        }
                     }
                  } else {
                     console.log('Invalid response from server:');
                     console.log(xhr.responseText);
                  }
               }
            }
            xhr.send(formData);
            break;
         }
      }
   }

   // Prepairing all the drop-zones on page load
   jQuery('#ThumbsTable tbody tr').each(function() {
      jQuery(this)
         .on('dragenter', handleDragEnter)
         .on('dragover',  handleDragOver)
         .on('dragleave', handleDragLeave)
         .on('drop',      handleDrop);
      counter[jQuery(this).data('entry')] = 0;
      jQuery(this).find('input:button').on('click', function() {
            jQuery(this).prevAll('input:file').click();
      });
      jQuery(this).find('input:file').on('change', handleBrowseButton);
   });

   //Checking Drag&Drop support
   //Structure is Not supported in Chrome's WebKit
   /*if (!('files' in DataTransfer.prototype)) {//Determine presence of HTML5 drag'n'drop file upload API - http://stackoverflow.com/a/2312859/3951387
      jQuery('.html5dndmarker').hide();
   }*/
   if (!('draggable' in document.createElement('span'))) {
      jQuery('.html5dndmarker').hide();
   }
});