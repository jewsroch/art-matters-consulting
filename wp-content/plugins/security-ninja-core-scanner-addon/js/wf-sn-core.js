jQuery(document).ready(function($){
  $('a.sn-show-source').click(function() {
      $($(this).attr('href')).dialog('option', { title: 'File source: ' + $(this).attr('data-file'), file_path: $(this).attr('data-file'), file_hash: $(this).attr('data-hash') } ).dialog('open');
      return false;
  });
  $('a.sn-restore-source').click(function() {
      $($(this).attr('href')).dialog('option', { title: 'Restore file source: ' + $(this).attr('data-file'), file_path: $(this).attr('data-file'), file_hash: $(this).attr('data-hash') } ).dialog('open');
      return false;
  });
  

    
  $('#source-dialog').dialog({'dialogClass': 'wp-dialog',
                              'modal': true,
                              'resizable': false,
                              'zIndex': 9999,
                              'width': 800,
                              'height': 550,
                              'hide': 'fade',
                              'open': function(event, ui) { renderSource(event, ui); fixDialogClose(event, ui); },
                              'close': function(event, ui) { jQuery('#source-dialog').html('<p>Please wait.</p>') },
                              'show': 'fade',
                              'autoOpen': false,
                              'closeOnEscape': true
                              });
  $('#restore-dialog').dialog({'dialogClass': 'wp-dialog',
                               'modal': true,
                               'resizable': false,
                               'zIndex': 9999,
                               'width': 450,
                               'height': 350,
                               'hide': 'fade',
                               'open': function(event, ui) { renderRestore(event, ui); fixDialogClose(event, ui); },
                               'close': function(event, ui) { jQuery('#restore-dialog').html('<p>Please wait.</p>') },
                               'show': 'fade',
                               'autoOpen': false,
                               'closeOnEscape': true
                              });
  // scan files
  $('#sn-run-scan').removeAttr('disabled').click(function(){
    var data = {action: 'sn_core_run_scan'};

    $(this).attr('disabled', 'disabled')
           .val('Scanning files, please wait!');
    $.blockUI({ message: 'Security Ninja is scanning your core files.<br />Please wait, it can take a minute.' });

    $.post(ajaxurl, data, function(response) {
      if (response != '1') {
        alert('Undocumented error. Page will automatically reload.');
        window.location.reload();
      } else {
        window.location.reload();
      }
    });
  }); // run tests
}); // onload

function renderSource(event, ui) {
  dialog_id = '#' + event.target.id;
  
  jQuery.post(ajaxurl, {action: 'sn_core_get_file_source', filename: jQuery('#source-dialog').dialog('option', 'file_path'), hash: jQuery('#source-dialog').dialog('option', 'file_hash')}, function(response) {
      if (response) {
        if (response.err) {
          jQuery(dialog_id).html('<p><b>An error occured.</b> ' + response.err + '</p>');
        } else {
          jQuery(dialog_id).html('<pre class="sn-core-source"></pre>');
          jQuery('pre', dialog_id).text(response.source);
          jQuery('pre', dialog_id).snippet(response.ext, {style: 'whitengrey'});  
        }
      } else {
        alert('An undocumented error occured. The page will reload.');
        window.location.reload();
      }
    }, 'json');
} // renderSource


function renderRestore(event, ui) {
  dialog_id = '#' + event.target.id;
  
  jQuery.post(ajaxurl, {action: 'sn_core_restore_file', filename: jQuery(dialog_id).dialog('option', 'file_path'), hash: jQuery(dialog_id).dialog('option', 'file_hash')}, function(response) {
      if (response) {
        if (response.err) {
          jQuery(dialog_id).html('<p><b>An error occured.</b> ' + response.err + '</p>');
        } else {
          jQuery(dialog_id).html(response.out);
          
            jQuery('#sn-restore-file').on('click', function(event){
              jQuery(this).attr('disabled', 'disabled').attr('value', 'Please wait ...');
              jQuery.post(ajaxurl, {action: 'sn_core_restore_file_do', filename: jQuery(this).attr('data-filename')}, function(response) {
                if (response == '1') {
                  alert('File successfully restored!\nThe page will reload and files will be rescanned.');
                  window.location.reload();
                } else {
                  alert('An error occured - ' + response);
                  jQuery(this).attr('disabled', '').attr('value', 'Restore file');
                }
              });  
            });
        }
      } else {
        alert('An undocumented error occured. The page will reload.');
        window.location.reload();
      }
    }, 'json');
} // renderSource


function fixDialogClose(event, ui) {
  jQuery('.ui-widget-overlay').bind('click', function(){ jQuery('#' + event.target.id).dialog('close'); });
} // fixDialogClose