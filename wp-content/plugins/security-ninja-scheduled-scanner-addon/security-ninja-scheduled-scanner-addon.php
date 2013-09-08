<?php
/*
Plugin Name: Security Ninja - Scheduled Scanner add-on
Plugin URI: http://security-ninja.webfactoryltd.com/scheduled-scanner/
Description: Automatically run Security Ninja and its add-ons on a predefined schedule and have the reports emailed to you.
Author: Web factory Ltd
Version: 1.0
Author URI: http://www.webfactoryltd.com/
*/


if (!function_exists('add_action')) {
  die('Please don\'t open this file directly!');
}


define('WF_SN_SS_VER', '1.0');
define('WF_SN_SS_OPTIONS_KEY', 'wf_sn_ss');
define('WF_SN_SS_CRON', 'wf_sn_ss_cron');
define('WF_SN_SS_TABLE', 'wf_sn_ss_log');
define('WF_SN_SS_LOG_LIMIT', 50);


class wf_sn_ss {
  // earlier hook for problematic filters
  function plugins_loaded() {
    add_filter('cron_schedules', array(__CLASS__, 'cron_intervals'));
  } // plugins_loaded

  // init plugin
  function init() {
    // does the user have enough privilages to use the plugin?
    if (is_admin() && current_user_can('administrator')) {
      if (self::check_sn_version()) {
        // this plugin requires WP v3.4
        if (!version_compare(get_bloginfo('version'), '3.4',  '>=')) {
          add_action('admin_notices', array(__CLASS__, 'min_version_error_wp'));
        }

        // add tab to Security Ninja tabs
        add_filter('sn_tabs', array(__CLASS__, 'sn_tabs'));

        // aditional links in plugin description
        add_filter('plugin_action_links_' . basename(dirname(__FILE__)) . '/' . basename(__FILE__),
                   array(__CLASS__, 'plugin_action_links'));
        add_filter('plugin_row_meta', array(__CLASS__, 'plugin_meta_links'), 10, 2);

        // enqueue scripts
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_scripts'));

        // register ajax endpoints
        add_action('wp_ajax_sn_ss_truncate_log', array(__CLASS__, 'truncate_log'));
        add_action('wp_ajax_sn_ss_sn_details', array(__CLASS__, 'dialog_sn_details'));
        add_action('wp_ajax_sn_ss_cs_details', array(__CLASS__, 'dialog_cs_details'));
        add_action('wp_ajax_sn_ss_cs_test', array(__CLASS__, 'do_cron_task_ajax'));

        // check and set default settings
        self::default_settings(false);

        // settings registration
        add_action('admin_init', array(__CLASS__, 'register_settings'));

        // check Core Scanner version
        if (self::is_core_scanner_active() && !self::check_cs_version()) {
          add_action('admin_notices', array(__CLASS__, 'min_version_error_cs'));
        }
      } else {
        // Security Ninja core plugin is missing
        add_action('admin_notices', array(__CLASS__, 'no_sn_core_error'));
      }
    } // if admin

    // register cron action
    add_action('wf_sn_ss_cron', array(__CLASS__, 'do_cron_task'));
  } // init


  // add links to plugin's description in plugins table
  function plugin_meta_links($links, $file) {
    $documentation_link = '<a target="_blank" href="' . plugin_dir_url(__FILE__) . 'documentation/' .
                          '" title="View documentation">Documentation</a>';
    $support_link = '<a target="_blank" href="http://codecanyon.net/user/WebFactory#from" title="Contact Web factory">Support</a>';

    if ($file == plugin_basename(__FILE__)) {
      $links[] = $documentation_link;
      $links[] = $support_link;
    }

    return $links;
  } // plugin_meta_links


  // add settings link to plugins page
  function plugin_action_links($links) {
    $settings_link = '<a href="tools.php?page=wf-sn#sn_scheduled" title="Schedule automatic scans with Security Ninja">Schedule scans</a>';
    array_unshift($links, $settings_link);

    return $links;
  } // plugin_action_links


  // check if we're on the SN page
  function is_plugin_page() {
    $current_screen = get_current_screen();

    if ($current_screen->id == 'tools_page_wf-sn') {
      return true;
    } else {
      return false;
    }
  } // is_plugin_page


  // enqueue CSS and JS scripts on plugin's admin page
  function enqueue_scripts() {
    if (self::is_plugin_page()) {
      $plugin_url = plugin_dir_url(__FILE__);

      wp_enqueue_style('wp-jquery-ui-dialog');
      wp_enqueue_script('jquery-ui-dialog');

      wp_enqueue_script('sn-ss', $plugin_url . 'js/wf-sn-ss.js', array(), WF_SN_SS_VER, true);
      wp_enqueue_style('sn-ss', $plugin_url . 'css/wf-sn-ss.css', array(), WF_SN_SS_VER);
    } // if
  } // enqueue_scripts


  // add new tab
  function sn_tabs($tabs) {
    $schedule_tab = array('id' => 'sn_schedule', 'class' => '', 'label' => 'Scheduled Scanner', 'callback' => array(__CLASS__, 'schedule_page'));
    $done = 0;

    for ($i = 0; $i < sizeof($tabs); $i++) {
      if ($tabs[$i]['id'] == 'sn_schedule') {
        $tabs[$i] = $schedule_tab;
        $done = 1;
        break;
      }
    } // for

    if (!$done) {
      $tabs[] = $schedule_tab;
    }

    return $tabs;
  } // sn_tabs


  // check if proper Security Ninja core version exists
  function check_sn_version() {
    if (class_exists('wf_sn')
        && defined('WF_SN_VER')
        && version_compare(WF_SN_VER, 1.45,  '>=')) {
      return true;
    } else {
      return false;
    }
  } // check_sn_version


  // check if proper Core Scanner version exists
  function check_cs_version() {
    if (class_exists('wf_sn_cs')
        && defined('WF_SN_CS_VER')
        && version_compare(WF_SN_CS_VER, 1.20,  '>=')) {
      return true;
    } else {
      return false;
    }
  } // check_cs_version


  // check if core scanner add-on is active
  function is_core_scanner_active() {
    if (class_exists('wf_sn_cs')
        && defined('WF_SN_CS_VER')) {
      return true;
    } else {
      return false;
    }
  } // is_core_scanner_active


  // set default options
  function default_settings($force = false) {
    $defaults = array('main_setting' => '0',
                      'scan_schedule' => 'twicedaily',
                      'email_report' => '2',
                      'email_to' => get_bloginfo('admin_email'));

    $options = get_option(WF_SN_SS_OPTIONS_KEY);

    if ($force || !$options || !$options['scan_schedule']) {
      update_option(WF_SN_SS_OPTIONS_KEY, $defaults);
    }
  } // default_settings


  // sanitize settings on save
  function sanitize_settings($values) {
    $old_options = get_option(WF_SN_SS_OPTIONS_KEY);

    foreach ($values as $key => $value) {
      switch ($key) {
        case 'main_setting':
        case 'scan_schedule':
        case 'email_report':
        case 'email_to':
          $values[$key] = trim($value);
        break;
      } // switch
    } // foreach

    if ($values['email_to'] && !is_email($values['email_to'])) {
      add_settings_error('wf-sn-ss', 'wf-sn-ss-save', 'Please check the email address, it\'s invalid.', 'error');
    }

    self::setup_cron($values);

    return array_merge($old_options, $values);
  } // sanitize_settings


  // register cron event
  function setup_cron($options = false) {
    if (!$options) {
      $options = get_option(WF_SN_SS_OPTIONS_KEY);
    }

    wp_clear_scheduled_hook(WF_SN_SS_CRON);

    if ($options['main_setting'] && $options['scan_schedule']) {
      if (!wp_next_scheduled(WF_SN_SS_CRON)) {
        wp_schedule_event(time() + 300, $options['scan_schedule'], WF_SN_SS_CRON);
      }
    }
  } // setup_cron


  // add additional cron intervals
  function cron_intervals($schedules) {
    $schedules['weekly'] = array(
      'interval' => DAY_IN_SECONDS * 7,
      'display' => 'Once Weekly');
    $schedules['monthly'] = array(
      'interval' => DAY_IN_SECONDS * 30,
      'display' => 'Once Monthly');
    $schedules['2days'] = array(
      'interval' => DAY_IN_SECONDS * 2,
      'display' => 'Once in Two Days');

    return $schedules;
  } // cron_intervals


  // runs cron tast manually
  function do_cron_task_ajax() {
    self::do_cron_task();
    die('1');
  } // do_cron_task_ajax

  // core cron function
  function do_cron_task() {
    global $wpdb;
    $options = get_option(WF_SN_SS_OPTIONS_KEY);
    $sn_change = $cs_change = 0;
    $sn_results = $cs_results = 0;
    $start_time = microtime(true);

    if (!self::check_sn_version()) {
      return;
    }

    $old = $wpdb->get_row('SELECT * FROM ' . $wpdb->prefix . WF_SN_SS_TABLE . ' ORDER BY id DESC LIMIT 1');

    if ($options['main_setting'] == '1' || $options['main_setting'] == '3') {
      $sn_results = wf_sn::run_tests(true);

      $old_sn_results = unserialize($old->sn_results);
      if ($sn_results['test'] != $old_sn_results['test']) {
        $sn_change = 1;
      }
    }

    if ($options['main_setting'] == '2' || $options['main_setting'] == '3') {
      $cs_results = wf_sn_cs::scan_files(true);

      $old_cs_results = unserialize($old->cs_results);
      unset($cs_results['last_run'], $old_cs_results['last_run']);
      if ($cs_results != $old_cs_results) {
        $cs_change = 1;
      }
    }

    // write results atabase
    $date = date('Y-m-d H:i:s', current_time('timestamp'));
    $query = $wpdb->prepare('INSERT INTO ' . $wpdb->prefix . WF_SN_SS_TABLE .
                            ' (runtime, timestamp, sn_results, cs_results, sn_change, cs_change)
                            VALUES (%s, %s, %s, %s, %s, %s)',
                            microtime(true) - $start_time, $date,
                            serialize($sn_results), serialize($cs_results), $sn_change, $cs_change);
    $wpdb->query($query);

    // send report email
    if ($options['email_report']) {
      if ($options['email_report'] == '2' && !$sn_change && !$cs_change) {
        // no change - don't send
      } else {
        $subject = 'Security Ninja scheduled scan report';
        $body = 'Scan was run on ' . date(get_option('date_format') . ' @ ' . get_option('time_format')) . "\n";
        $body .= 'Run time: ' . round(microtime(true) - $start_time) . " sec \n";
        if (!$sn_results) {
          $body .= 'Security Ninja results: test were not run'  . "\n";
        } else {
          if ($sn_change) {
            $body .= 'Security Ninja results: results *have changed* since last scan'  . "\n";
          } else {
            $body .= 'Security Ninja results: no changes since last scan'  . "\n";
          }
        }
        if (!$cs_results) {
          $body .= 'Core Scanner results: test were not run'  . "\n";
        } else {
          if ($cs_change) {
            $body .= 'Core Scanner results: results *have changed* since last scan'  . "\n";
          } else {
            $body .= 'Core Scanner results: no changes since last scan'  . "\n";
          }
        }
        $body .= "\n";
        $body .= 'Login to view details - ' . admin_url('tools.php?page=wf-sn#sn_scheduled');

        wp_mail($options['email_to'], $subject, $body);
      }
    }
  } // do_cron_task


  // all settings are saved in one option key
  function register_settings() {
    register_setting(WF_SN_SS_OPTIONS_KEY, 'wf_sn_ss', array(__CLASS__, 'sanitize_settings'));
  } // register_settings


  // display results
  function schedule_page() {
    if (!current_user_can('administrator')) {
      echo 'Cheating, are you?';
      return;
    }

    settings_errors();

    $main_settings =   array();
    $main_settings[] = array('val' => '0', 'label' => 'Disable scheduled scans');
    $main_settings[] = array('val' => '1', 'label' => 'Enable scheduled scans only for Security Ninja');
    if (self::check_cs_version()) {
      $main_settings[] = array('val' => '2', 'label' => 'Enable scheduled scans only for Core Scanner add-on');
      $main_settings[] = array('val' => '3', 'label' => 'Enable scheduled scans for both Security Ninja and Core Scanner add-on');
    }

    $scan_schedule = array();
    $tmp = wp_get_schedules();
    foreach ($tmp as $name => $details) {
      if ($name == 'twicedaily') {
        $scan_schedule[] = array('val' => $name, 'label' => $details['display'] . ' (recommended)');
      } else {
        $scan_schedule[] = array('val' => $name, 'label' => $details['display']);
      }
    }

    $email_reports =   array();
    $email_reports[] = array('val' => '0', 'label' => 'Never send any emails');
    $email_reports[] = array('val' => '1', 'label' => 'Send an email each time the tests run');
    $email_reports[] = array('val' => '2', 'label' => 'Send an email only when test results change');

    $options = get_option(WF_SN_SS_OPTIONS_KEY);

    echo '<p><strong>Please read!</strong> WordPress cron function depends on site visitors to regularly run its tasks. If your site has very few visitors the tasks wont be run on a regular, predefined interval. Wptuts+ has a great <a href="http://wp.tutsplus.com/articles/insights-into-wp-cron-an-introduction-to-scheduling-tasks-in-wordpress/" target="_blank">article</a> explaining how to make sure the cron does get run even if you have very few visitors.<br />
    Please test the settings after changing them to ensure you\'re getting the emails and that test finish in a timely manner.</p>';


    if ($options['main_setting']) {
      $tmp = wp_get_schedules();
      $tmp = '<span class="sn-ss-nochange">scheduled scans are <b>enabled</b> and will run ' . strtolower($tmp[$options['scan_schedule']]['display']) . '</span>';
    } else {
      $tmp = '<span class="sn-ss-change">scheduled scans are <b>disabled</b></span>';
    }

    echo '<form action="options.php" method="post">';

    settings_fields('wf_sn_ss');

    echo '<h3 class="ss_header">Settings - ' . $tmp . '</h3>';
    echo '<table class="form-table"><tbody>';

    echo '<tr valign="top">
    <th scope="row"><label for="main_setting">Scan Settings</label></th>
    <td><select id="main_setting" name="wf_sn_ss[main_setting]">';
      self::create_select_options($main_settings, $options['main_setting']);
    echo '</select>';
    echo '<br /><span>Depending on the Security Ninja add-ons that are active you can choose to include them in scheduled scans or not.</span>';
    echo '</td></tr>';

    echo '<tr valign="top">
    <th scope="row"><label for="scan_schedule">Scan Schedule</label></th>
    <td><select id="scan_schedule" name="wf_sn_ss[scan_schedule]">';
      self::create_select_options($scan_schedule, $options['scan_schedule']);
    echo '</select>';
    echo '<br><span>Running the scan once a day will ensure you get a prompt notice of any problems and at the same time don\'t overload the server.</span>';
    echo '</td></tr>';

    echo '<tr valign="top">
    <th scope="row"><label for="email_report">Email Report</label></th>
    <td><select id="email_report" name="wf_sn_ss[email_report]">';
      self::create_select_options($email_reports, $options['email_report']);
    echo '</select>';
    echo '<br><span>Depending on the amount of email you like to receive you can get reports for all scans or just ones when results change.</span>';
    echo '</td></tr>';

    echo '<tr valign="top">
    <th scope="row"><label for="email_to">Email Recipient</label></th>
    <td><input type="text" class="regular-text" id="email_to" name="wf_sn_ss[email_to]" value="' . $options['email_to'] . '" />';
    echo '<br><span>Email address of the person (usually the site admin) who\'ll receive the email reports.</span>';
    echo '</td></tr>';

    echo '<tr valign="top"><td colspan="2">';
    echo '<p class="submit"><input type="submit" value="Save Changes" class="button-primary" name="Submit" />';
    echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="button" value="Test settings (run scan)" class="button-secondary" id="sn-ss-test" /></p>';
    echo '</td></tr>';

    echo '</table>';
    echo '</form>';

    self::log_list();

    echo '<div id="wf-ss-dialog"></div>';
  } // core_page


function log_list() {
    global $wpdb;

    $logs = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . WF_SN_SS_TABLE . " ORDER by timestamp DESC LIMIT " . WF_SN_SS_LOG_LIMIT);

    echo '<br /><br /><h3 class="ss_header">Scan Log</h3>';
    echo '<table class="wp-list-table widefat" cellspacing="0" id="wf-sn-ss-log">';
    echo '<thead><tr>';
    echo '<th id="header_time">Timestamp</th>';
    echo '<th id="header_runtime">Run time</th>';
    echo '<th id="header_sn">Security Ninja results</th>';
    echo '<th id="header_ss">Core Scanner results</th>';
    echo '</tr></thead>';
    echo '<tbody>';

    if ($logs) {
      foreach ($logs as $log) {
        $tmp = strtotime($log->timestamp);
        $tmp = date(get_option('date_format') . ' @ ' . get_option('time_format') ,$tmp);
        echo '<tr>';
        echo '<td class="log-sn-ss-timestamp">' . $tmp . '</td>';
        echo '<td class="log-sn-ss-runtime">' . round($log->runtime) . ' sec</td>';
        echo '<td class="log-sn-ss-sn">';
        if (!unserialize($log->sn_results)) {
          echo '<i>Tests were not run.</i>';
        } else {
          if ($log->sn_change) {
            echo '<span class="sn-ss-change">Results <b>have changed</b> since last scan.</span>';
        } else {
            echo '<span class="sn-ss-nochange">No changes in results since last scan.</span>';
        }
          echo ' &nbsp;&nbsp;<a href="#" data-timestamp="' . $tmp . '" data-row-id="' . $log->id . '" class="button-secondary ss-details-sn">View details</a>';
        }
        echo '</td>';
        echo '<td class="log-sn-ss-ss">';
        if (!unserialize($log->cs_results)) {
          echo '<i>Tests were not run.</i>';
        } else {
          if ($log->cs_change) {
            echo '<span class="sn-ss-change">Results <b>have changed</b> since last scan.</span>';
          } else {
            echo '<span class="sn-ss-nochange">No changes in results since last scan.</span>';
          }
          echo ' &nbsp;&nbsp;<a href="#" data-timestamp="' . $tmp . '" data-row-id="' . $log->id . '" class="button-secondary ss-details-cs">View details</a></td>';
        }
        echo '</td>';
        echo '</tr>';
      } // foreach $logs
    } else {
      echo '<tr><td colspan="4"><span class="no-logs">No log records found.</span></td></tr>';
    }
    echo '</tbody>';
    echo '</table>';

    echo '<br /><br />';
    echo '<p><input type="button" value="Delete all log entries" class="button-secondary" id="wf-sn-ss-truncate-log"></p>';
  } // log_list


  // helper function for creating dropdowns
  function create_select_options($options, $selected = null, $output = true) {
    $out = "\n";

    foreach ($options as $tmp) {
      if ($selected == $tmp['val']) {
        $out .= "<option selected=\"selected\" value=\"{$tmp['val']}\">{$tmp['label']}&nbsp;</option>\n";
      } else {
        $out .= "<option value=\"{$tmp['val']}\">{$tmp['label']}&nbsp;</option>\n";
      }
    } // foreach

    if ($output) {
      echo $out;
    } else {
      return $out;
    }
  } // create_select_options


  // display warning if WP is outdated
  function min_version_error_wp() {
    echo '<div id="message" class="error"><p>Security Ninja - Scheduled Scanner add-on <b>requires WordPress version 3.4</b> or higher to function properly. You\'re using WordPress version ' . get_bloginfo('version') . '. Please <a href="' . admin_url('update-core.php') . '" title="Update WP core">update</a>.</p></div>';
  } // min_version_error_wp


  // display warning if CS is outdated
  function min_version_error_cs() {
    echo '<div id="message" class="error"><p>Security Ninja - Scheduled Scanner add-on <b>requires Security Ninja Core Scanner add-on version 1.20</b> or higher to function properly. You\'re using Core Scanner version ' . WF_SN_CS_VER . '. Please disable Core Scanner or <a target="_blank" href="http://codecanyon.net/item/core-scanner-addon-for-security-ninja/2927931" title="Update CS">download</a> a new version.</p></div>';
  } // min_version_error_cs


  // display error message if SN version is too low
  function no_sn_core_error() {
    echo '<div id="message" class="error"><p>Security Ninja - Schedule Scanner Add-on <b>requires Security Ninja version 1.45</b> or higher to function properly. If you have already purchased Security Ninja please upgrade it; otherwise get it for only $10 on <a href="http://codecanyon.net/item/security-ninja/577696?ref=WebFactory">CodeCanyon</a>.</p></div>';
  } // no_sn_core_error


  // truncate scan log table
  function truncate_log() {
    global $wpdb;

    $wpdb->query('TRUNCATE TABLE ' . $wpdb->prefix . WF_SN_SS_TABLE);
    die('1');
  } // truncate_log


  // displaysd ialog with sn test details
  function dialog_sn_details() {
    global $wpdb;

    $id = (int) $_POST['row_id'];
    $result = $wpdb->get_row('SELECT * FROM ' . $wpdb->prefix . WF_SN_SS_TABLE . ' WHERE id = ' . $id . ' LIMIT 1');

    if ($result->sn_results && is_array(unserialize($result->sn_results))) {
      echo '<table class="wp-list-table widefat" cellspacing="0" id="security-ninja">';
      echo '<thead><tr>';
      echo '<th class="sn-status">Status</th>';
      echo '<th>Test description</th>';
      echo '<th>Test results</th>';
      echo '<th>&nbsp;</th>';
      echo '</tr></thead>';
      echo '<tbody>';

      $tmp = unserialize($result->sn_results);
      foreach($tmp['test'] as $test_name => $details) {
        echo '<tr>
                <td class="sn-status">' . wf_sn::status($details['status']) . '</td>
                <td>' . $details['title'] . '</td>
                <td>' . $details['msg'] . '</td>
                <td class="sn-details"><a href="#' . $test_name . '" class="button action">Details, tips &amp; help</a></td>
              </tr>';
      } // foreach ($tests)

      echo '</tbody>';
      echo '<tfoot><tr>';
      echo '<th class="sn-status">Status</th>';
      echo '<th>Test description</th>';
      echo '<th>Test results</th>';
      echo '<th>&nbsp;</th>';
      echo '</tr></tfoot>';
      echo '</table>';
    } else {
      echo 'Undocumented error.';
    }

    die();
  } // dialog_sn_details


  // displays dialog with core scanner details
  function dialog_cs_details() {
    global $wpdb;

    $id = (int) $_POST['row_id'];
    $result = $wpdb->get_row('SELECT * FROM ' . $wpdb->prefix . WF_SN_SS_TABLE . ' WHERE id = ' . $id . ' LIMIT 1');

    echo '<div style="margin: 20px">';
    if ($result->cs_results && is_array(unserialize($result->cs_results))) {
      $results = unserialize($result->cs_results);

      if ($results['changed_bad']) {
        echo '<div class="sn-cs-changed-bad">';
        echo '<h4>Following core files have been modified and they should not have been!</h4>';
        echo '<p>If you didn\'t modify the following files and don\'t know who did they are most probably infected by a 3rd party malicious code.<br>
        Please use caution when restoring files because there is no undo button.</p>';
        echo wf_sn_cs::list_files($results['changed_bad'], 0, 0);
        echo '</div>';
      }

      if ($results['missing_bad']) {
        echo '<div class="sn-cs-missing-bad">';
        echo '<h4>Following core files are missing and they should not be.</h4>';
        echo '<p>Missing core files my indicate a bad auto-update or they simply were not copied on the server when the site was setup.<br>
        If there is no legitimate reason for the files to be missing use the restore action to create them.</p>';
        echo wf_sn_cs::list_files($results['missing_bad'], 0, 0);
        echo '</div>';
      }

      if ($results['changed_ok']) {
        echo '<div class="sn-cs-changed-ok">';
        echo '<h4>Following core files have been modified and they are supposed to be.</h4>';
        echo '<p>There are only two core files (<i>/wp-config.php</i> and <i>/index.php</i>) that should be modified. This is normal and one or both of them should appear on this list.<br>
        You can still have a look at their source to check for any suspicious code.</p>';
        echo wf_sn_cs::list_files($results['changed_ok'], 0, 0);
        echo '</div>';
      }

      if ($results['missing_ok']) {
        echo '<div class="sn-cs-missing-ok">';
        echo '<h4>Following core files are missing but they are not vital.</h4>';
        echo '<p>Some files like <i>/readme.html</i> are not vital and should be removed to hide WP version info. Do not restore them unless you really need them and know what you are doing.</p>';
        echo wf_sn_cs::list_files($results['missing_ok'], 0, 0);
        echo '</div>';
      }

      if ($results['ok']) {
        echo '<div class="sn-cs-ok">';
        echo '<h4>A total of <span class="sn_count">' . $results['total'] . '</span> files were scanned and <span class="sn_count">' . sizeof($results['ok']) . '</span> are unmodified and safe.</h4>';
        echo '<p>Do not expect to get a ' . $results['total'] . '/' . $results['total'] . ' count. That is impossible. The number only servers as a reference to reassure you in the number of files that were scanned and found unmodified for WP core v' . get_bloginfo('version') . '.</p>';
        echo '</div>';
      }

    } else {
      echo 'Undocumented error.';
    }

    echo '</div>';
    die();
  } // dialog_cs_details


  // activate plugin
  function activate() {
    // create table
    global $wpdb;
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    $table_name = $wpdb->prefix . WF_SN_SS_TABLE;
    $wpdb->query('DROP TABLE IF EXISTS ' . $table_name);
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
      $sql = "CREATE TABLE IF NOT EXISTS " . $table_name . " (
                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                `timestamp` datetime NOT NULL,
                `runtime` float NOT NULL,
                `sn_results` text,
                `cs_results` text,
                `sn_change` tinyint(4) NOT NULL,
                `cs_change` tinyint(4) NOT NULL,
                PRIMARY KEY (`id`)
              ) ENGINE=MyISAM DEFAULT CHARSET=utf8";
      dbDelta($sql);
    }

    self::default_settings(false);
  } // activate


  // clean-up when deactivated
  function deactivate() {
    global $wpdb;

    wp_clear_scheduled_hook(WF_SN_SS_CRON);
    delete_option(WF_SN_SS_OPTIONS_KEY);
    $wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . WF_SN_SS_TABLE);
  } // deactivate
} // wf_sn_ss class


// hook everything up
add_action('init', array('wf_sn_ss', 'init'));
add_action('plugins_loaded', array('wf_sn_ss', 'plugins_loaded'));

// setup environment when activated
register_activation_hook(__FILE__, array('wf_sn_ss', 'activate'));

// when deativated clean up
register_deactivation_hook( __FILE__, array('wf_sn_ss', 'deactivate'));
?>