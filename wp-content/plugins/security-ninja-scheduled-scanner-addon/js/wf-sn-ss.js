/*
 * Security Ninja - Scheduled Scanner add-on
 * (c) Web factory 2012
 */

jQuery(document).ready(function($){
  // run tests, via ajax
  $('#sn-ss-test').removeAttr('disabled');
  $('#sn-ss-test').click(function(){
    if (!confirm('Please remember to save settings before testing them. Continue?')) {
      return;
    }
    var data = {action: 'sn_ss_cs_test'};

    $(this).attr('disabled', 'disabled')
           .val('Running scan, please wait!');
    $.blockUI({ message: 'Security Ninja is running a scheduled scan.<br />Please wait, it can take a few minutes.' });

    $.post(ajaxurl, data, function(response) {
      if (response != '1') {
        alert('Undocumented error. Page will automatically reload.');
        window.location.reload();
      } else {
        window.location.reload();
      }
    });
  }); // run tests

  // truncate log table
  $('#wf-sn-ss-truncate-log').click(function(){
    var answer = confirm("Are you sure you want to delete all log entries?");
    if (answer) {
      var data = {action: 'sn_ss_truncate_log'};
      $.post(ajaxurl, data, function(response) {
        if (!response) {
          alert('Bad AJAX response. Please reload the page.');
        } else {
          window.location.reload();
        }
      });
    }
    return false;
  });

  // security ninja results details
  $('.ss-details-sn').click(function(){
    var data = {action: 'sn_ss_sn_details', row_id: $(this).attr('data-row-id') };
    var timestamp = $(this).attr('data-timestamp');

    $.post(ajaxurl, data, function(response) {
      if (!response) {
        alert('Bad AJAX response. Please reload the page.');
      } else {
        $('#wf-ss-dialog').html(response)
                           .dialog({ title: 'Security Ninja results from ' + timestamp })
                           .dialog('open');
      }
    });
    return false;
  }); // $('.ss-details-sn').click

  // cores canner results details
  $('.ss-details-cs').click(function(){
    var data = {action: 'sn_ss_cs_details', row_id: $(this).attr('data-row-id') };
    var timestamp = $(this).attr('data-timestamp');

    $.post(ajaxurl, data, function(response) {
      if (!response) {
        alert('Bad AJAX response. Please reload the page.');
      } else {
        $('#wf-ss-dialog').html(response)
                           .dialog({ title: 'Core Scanner results from ' + timestamp })
                           .dialog('open');
      }
    });
    return false;
  }); // $('.ss-details-cs').click

  // prepare dialog
  $('#wf-ss-dialog').dialog({'dialogClass': 'wp-dialog',
                              'modal': true,
                              'resizable': false,
                              'zIndex': 9999,
                              'width': 900,
                              'height': 550,
                              'hide': 'fade',
                              'show': 'fade',
                              'autoOpen': false,
                              'closeOnEscape': true
                              });

}); // onload