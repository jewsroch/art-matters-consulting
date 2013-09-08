<?php
/*
Plugin Name: Security Ninja
Plugin URI: http://security-ninja.webfactoryltd.com/
Description: Check your site for <strong>security vulnerabilities</strong> and get precise suggestions for corrective actions on passwords, user accounts, file permissions, database security, version hiding, plugins, themes and other security aspects.
Author: Web factory Ltd
Version: 1.55
Author URI: http://www.webfactoryltd.com/
*/


if (!function_exists('add_action')) {
  die('Please don\'t open this file directly!');
}


// constants
define('WF_SN_VER', '1.55');
define('WF_SN_DIC', plugin_dir_path(__FILE__) . 'brute-force-dictionary.txt');
define('WF_SN_OPTIONS_KEY', 'wf_sn_results');
define('WF_SN_MAX_USERS_ATTACK', 5);
define('WF_SN_MAX_EXEC_SEC', 200);


require_once 'sn-tests.php';


class wf_sn {
  // init plugin
  function init() {
    // does the user have enough privilages to use the plugin?
    if (is_admin() && current_user_can('administrator')) {
      // this plugin requires WP v3.1
      if (!version_compare(get_bloginfo('version'), '3.5',  '>=')) {
        add_action('admin_notices', array(__CLASS__, 'min_version_error'));
        return;
      } else {
        // add menu item to tools
        add_action('admin_menu', array(__CLASS__, 'admin_menu'));

        // aditional links in plugin description
        add_filter('plugin_action_links_' . basename(dirname(__FILE__)) . '/' . basename(__FILE__),
                   array(__CLASS__, 'plugin_action_links'));
        add_filter('plugin_row_meta', array(__CLASS__, 'plugin_meta_links'), 10, 2);

        // enqueue scripts
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_scripts'));

        // register ajax endpoints
        add_action('wp_ajax_sn_run_tests', array(__CLASS__, 'run_tests'));
        add_action('wp_ajax_sn_hide_core_tab', array(__CLASS__, 'hide_core_tab'));
        add_action('wp_ajax_sn_hide_schedule_tab', array(__CLASS__, 'hide_schedule_tab'));

        // warn if tests were not run
        add_action('admin_notices', array(__CLASS__, 'run_tests_warning'));

        // warn if Wordfence is active
        add_action('admin_notices', array(__CLASS__, 'wordfence_warning'));
      } // if version
    } // if
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
    $settings_link = '<a href="tools.php?page=wf-sn" title="Security Ninja">Analyze site</a>';
    array_unshift($links, $settings_link);

    return $links;
  } // plugin_action_links


  // test if plugin's page is visible
  function is_plugin_page() {
    $current_screen = get_current_screen();

    if ($current_screen->id == 'tools_page_wf-sn') {
      return true;
    } else {
      return false;
    }
  } // is_plugin_page


  // hide the core add-on ad tab
  function hide_core_tab() {
    $tmp = (int) set_transient('wf_sn_hide_core_tab', true, 60*60*24*1000);

    die("$tmp");
  } // hide_core_tab


  // hide the core add-on ad tab
  function hide_schedule_tab() {
    $tmp = (int) set_transient('wf_sn_hide_schedule_tab', true, 60*60*24*1000);

    die("$tmp");
  } // hide_core_tab


  // enqueue CSS and JS scripts on plugin's pages
  function enqueue_scripts() {
    if (self::is_plugin_page()) {
      $plugin_url = plugin_dir_url(__FILE__);

      wp_enqueue_script('jquery-ui-tabs');
      wp_enqueue_script('sn-jquery-plugins', $plugin_url . 'js/wf-sn-jquery-plugins.js', array(), WF_SN_VER, true);
      wp_enqueue_script('sn-js', $plugin_url . 'js/wf-sn-common.js', array(), WF_SN_VER, true);
      wp_enqueue_style('sn-css', $plugin_url . 'css/wf-sn-style.css', array(), WF_SN_VER);
    } // if
  } // enqueue_scripts


  // add entry to admin menu
  function admin_menu() {
    add_management_page('Security Ninja', 'Security Ninja', 'manage_options', 'wf-sn', array(__CLASS__, 'options_page'));
  } // admin_menu


  // display warning if test were never run
  function run_tests_warning() {
    $tests = get_option(WF_SN_OPTIONS_KEY);

    if (self::is_plugin_page() && !$tests['last_run']) {
      echo '<div id="message" class="error"><p>Security Ninja <strong>tests were never run.</strong> Click "Run tests" to run them now and analyze your site for security vulnerabilities.</p></div>';
    } elseif (self::is_plugin_page() && (current_time('timestamp') - 30*24*60*60) > $tests['last_run']) {
      echo '<div id="message" class="error"><p>Security Ninja <strong>tests were not run for more than 30 days.</strong> It\'s advisable to run them once in a while. Click "Run tests" to run them now and analyze your site for security vulnerabilities.</p></div>';
    }
  } // run_tests_warning


  // display warning if Wordfence plugin is active
  function wordfence_warning() {
    if (defined('WORDFENCE_VERSION') && WORDFENCE_VERSION) {
      echo '<div id="message" class="error"><p>Please <strong>deactivate Wordfence plugin</strong> before running Security Ninja tests. Some tests are detected as site attacks by Wordfence and hence can\'t be performed properly. Activate Wordfence once you\'re done testing.</p></div>';
    }
  } // wordfence_warning


  // display warning if test were never run
  function min_version_error() {
    echo '<div id="message" class="error"><p>Security Ninja <b>requires WordPress version 3.5</b> or higher to function properly. You\'re using WordPress version ' . get_bloginfo('version') . '. Please <a href="' . admin_url('update-core.php') . '" title="Update WP core">update</a>.</p></div>';
  } // min_version_error


  function core_ad_page() {
    echo '<p><b>Core Scanner</b> is an add-on available for Security Ninja. It gives you a peace of mind by scanning all your core WP files (600+) to ensure they have not been modified by
    a 3rd party.<br>Add-on offers the following functionality;</p>';
    echo '<ul class="sn-list">
<li>scan WP core files with <strong>one click</strong></li>
<li>quickly identify <strong>problematic files</strong></li>
<li><strong>restore modified files</strong> with one click</li>
<li>great for removing <strong>exploits</strong> and fixing accidental file edits/deletes</li>
<li>view files\' <strong>source</strong> to take a closer look</li>
<li><strong>fix</strong> broken WP auto-updates</li>
<li>detailed help and description</li>
<li><strong>color-coded results</strong> separate files into 5 categories:
<ul>
<li>files that are modified and should not have been</li>
<li>files that are missing and should not be</li>
<li>files that are modified and they are supposed to be</li>
<li>files that are missing but they are not vital to WP</li>
<li>files that are intact</li>
</ul></li>
<li>complete integration with Ninja\'s easy-to-use GUI</li>
</ul>';

    echo '<p><br /><a target="_blank" href="http://codecanyon.net/item/core-scanner-addon-for-security-ninja/2927931/?ref=WebFactory" class="button-primary">Get the Core Scanner add-on for only $5</a>
  &nbsp;&nbsp;&nbsp;&nbsp;<a id="sn_hide_core_ad" href="#" title="Hide this tab"><i>No thank you, I\'m not interested (hide this tab)</i></a></p>';
  } // core_ad_page


  function schedule_ad_page() {
    echo '<p><b>Scheduled Scanner</b> is an add-on available for Security Ninja. It gives you an additional peace of mind by automatically running Security Ninja and Core Scanner tests every day. If any changes occur or your site gets hacked you\'ll immediately get notified via email.<br>Add-on offers the following functionality;</p>';
    echo '<ul class="sn-list">
<li>give yourself a peace of mind with <strong>automated scans</strong> and email reports</li>
<li><strong>get alerted</strong> when your site is <strong>hacked</strong></li>
<li>compatible with both <strong>Security Ninja & Core Scanner add-on</strong></li>
<li>extremely <strong>easy</strong> to setup - set once and forget</li>
<li>optional <strong>email reports</strong> - get them after every scan or only after changes occur on your site</li>
<li>detailed, color-coded <strong>scan log</strong></li>
<li>complete integration with Ninja\'s easy-to-use GUI</li>
</ul>';

    echo '<p><br /><a target="_blank" href="http://codecanyon.net/item/scheduled-scanner-addon-for-security-ninja/3686330?ref=WebFactory" class="button-primary">Get the Scheduled Scanner add-on for only $5</a>
  &nbsp;&nbsp;&nbsp;&nbsp;<a id="sn_hide_schedule_ad" href="#" title="Hide this tab"><i>No thank you, I\'m not interested (hide this tab)</i></a></p>';
  } // schedule_ad_page


  // whole options page
  function options_page() {
    // does the user have enough privilages to access this page?
    if (!current_user_can('administrator'))  {
      wp_die('You do not have sufficient permissions to access this page.');
    }

    $tabs = array();
    $tabs[] = array('id' => 'sn_tests', 'class' => '', 'label' => 'Tests', 'callback' => array('self', 'tests_table'));
    $tabs[] = array('id' => 'sn_help', 'class' => 'sn_help', 'label' => 'Test details, tips &amp; help', 'callback' => array('self', 'help_table'));
    if (!get_transient('wf_sn_hide_core_tab')) {
      $tabs[] = array('id' => 'sn_core', 'class' => 'sn_core_ad', 'label' => 'Core Scanner (add-on)', 'callback' => array('self', 'core_ad_page'));
    }
    if (!get_transient('wf_sn_hide_schedule_tab')) {
      $tabs[] = array('id' => 'sn_schedule', 'class' => 'sn_schedule_ad', 'label' => 'Scheduled Scanner (add-on)', 'callback' => array('self', 'schedule_ad_page'));
    }
    $tabs = apply_filters('sn_tabs', $tabs);

    echo '<div class="wrap">' . get_screen_icon('sn-lock');
    echo '<h2>Security Ninja</h2>';

    echo '<div id="tabs">';
    echo '<ul>';
    foreach ($tabs as $tab) {
      echo '<li><a href="#' . $tab['id'] . '" class="' . $tab['class'] . '">' . $tab['label'] . '</a></li>';
    }
    echo '</ul>';

    foreach ($tabs as $tab) {
      echo '<div id="' . $tab['id'] . '">';
      call_user_func($tab['callback']);
      echo '</div>';
    }

    echo '</div>'; // tabs
    echo '</div>'; // wrap
  } // options_page


  // display tests help & info
  function help_table() {
    require_once 'tests-description.php';
  } // help_table


  // display tests table
  function tests_table() {
    // get test results from cache
    $tests = get_option(WF_SN_OPTIONS_KEY);

    echo '<p class="submit"><input type="submit" value=" Run tests " id="run-tests" class="button-primary" name="Submit" />&nbsp;&nbsp;';

    if ($tests['last_run']) {
      echo '<span class="sn-notice">Tests were last run on: ' . date(get_option('date_format') . ' ' . get_option('time_format'), $tests['last_run']) . '.</span>';
    }

    echo '</p>';

    echo '<p><strong>Please read!</strong> These tests only serve as suggestions! Although they cover years of best practices getting all test <i>green</i> will not guarantee your site will not get hacked. Likewise, getting them all <i>red</i> doesn\'t mean you\'ll certainly get hacked. Please read each test\'s detailed information to see if it represents a real security issue for your site. Suggestions and test results apply to public, production sites, not local, development ones. <br /> If you need an in-depth security analysis please hire a security expert.</p><br />';

    if ($tests['last_run']) {
      echo '<table class="wp-list-table widefat" cellspacing="0" id="security-ninja">';
      echo '<thead><tr>';
      echo '<th class="sn-status">Status</th>';
      echo '<th>Test description</th>';
      echo '<th>Test results</th>';
      echo '<th>&nbsp;</th>';
      echo '</tr></thead>';
      echo '<tbody>';

      if (is_array($tests['test'])) {
        // test Results
        foreach($tests['test'] as $test_name => $details) {
          echo '<tr>
                  <td class="sn-status">' . self::status($details['status']) . '</td>
                  <td>' . $details['title'] . '</td>
                  <td>' . $details['msg'] . '</td>
                  <td class="sn-details"><a href="#' . $test_name . '" class="button action">Details, tips &amp; help</a></td>
                </tr>';
        } // foreach ($tests)
      } else { // no test results
        echo '<tr>
                <td colspan="4">No test results are available. Click "Run tests" to run tests now.</td>
              </tr>';
      } // if tests

      echo '</tbody>';
      echo '<tfoot><tr>';
      echo '<th class="sn-status">Status</th>';
      echo '<th>Test description</th>';
      echo '<th>Test results</th>';
      echo '<th>&nbsp;</th>';
      echo '</tr></tfoot>';
      echo '</table>';
    } // if $results
  } // tests_table


  // run all tests; via AJAX
  function run_tests($return = false) {
    @set_time_limit(WF_SN_MAX_EXEC_SEC);
    $test_count = 0;
    $test_description = array('last_run' => current_time('timestamp'));

    foreach(wf_sn_tests::$security_tests as $test_name => $test){
      if ($test_name[0] == '_') {
        continue;
      }
      $response = wf_sn_tests::$test_name();

      $test_description['test'][$test_name]['title'] = $test['title'];
      $test_description['test'][$test_name]['status'] = $response['status'];

      if (!isset($response['msg'])) {
        $response['msg'] = '';
      }

      if ($response['status'] == 10) {
        $test_description['test'][$test_name]['msg'] = sprintf($test['msg_ok'], $response['msg']);
      } elseif ($response['status'] == 0) {
        $test_description['test'][$test_name]['msg'] = sprintf($test['msg_bad'], $response['msg']);
      } else {
        $test_description['test'][$test_name]['msg'] = sprintf($test['msg_warning'], $response['msg']);
      }
      $test_count++;
    } // foreach

    if ($return) {
      return $test_description;
    } else {
      update_option(WF_SN_OPTIONS_KEY, $test_description);
      die('1');
    }
  } // run_test


  // convert status integer to button
  function status($int) {
    if ($int == 0) {
      $string = '<span class="sn-error">Bad</span>';
    } elseif ($int == 10) {
      $string = '<span class="sn-success">OK</span>';
    } else {
      $string = '<span class="sn-warning">Warning</span>';
    }

    return $string;
  } // status


  // clean-up when deactivated
  function deactivate() {
    delete_option(WF_SN_OPTIONS_KEY);
  } // deactivate
} // wf_sn class


// hook everything up
add_action('init', array('wf_sn', 'init'));

// when deativated clean up
register_deactivation_hook( __FILE__, array('wf_sn', 'deactivate'));
?>