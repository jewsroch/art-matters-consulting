<?php
/*
Plugin Name: Security Ninja - Core Scanner add-on
Plugin URI: http://security-ninja.webfactoryltd.com/core-scanner/
Description: Scan your WordPress core files to ensure they are intact and exploit free!
Author: Web factory Ltd
Version: 1.70
Author URI: http://www.webfactoryltd.com/
*/


if (!function_exists('add_action')) {
  die('Please don\'t open this file directly!');
}


define('WF_SN_CS_VER', '1.70');
define('WF_SN_CS_OPTIONS_KEY', 'wf_sn_cs_results');
define('WF_SN_CS_SALT', 'monkey');


class wf_sn_cs {
  // init plugin
  function init() {
    // does the user have enough privilages to use the plugin?
    if (is_admin() && current_user_can('administrator')) {
      if (self::check_sn_version()) {
        // add tab to Security Ninja tabs
        add_filter('sn_tabs', array(__CLASS__, 'sn_tabs'));

        // aditional links in plugin description
        add_filter('plugin_action_links_' . basename(dirname(__FILE__)) . '/' . basename(__FILE__),
                   array(__CLASS__, 'plugin_action_links'));
        add_filter('plugin_row_meta', array(__CLASS__, 'plugin_meta_links'), 10, 2);

        // enqueue scripts
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_scripts'));

        // register ajax endpoints
        add_action('wp_ajax_sn_core_get_file_source', array(__CLASS__, 'get_file_source'));
        add_action('wp_ajax_sn_core_restore_file', array(__CLASS__, 'restore_file_dialog'));
        add_action('wp_ajax_sn_core_restore_file_do', array(__CLASS__, 'restore_file'));
        add_action('wp_ajax_sn_core_run_scan', array(__CLASS__, 'scan_files'));

        // this plugin requires WP v3.3
        if (!version_compare(get_bloginfo('version'), '3.3',  '>=')) {
          add_action('admin_notices', array(__CLASS__, 'min_version_error'));
        }

        // warn if tests were never run
        add_action('admin_notices', array(__CLASS__, 'run_tests_warning'));
      } else {
        // Security Ninja core plugin is missing
        add_action('admin_notices', array(__CLASS__, 'no_sn_core_error'));
      }
    } // if admin
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
    $settings_link = '<a href="tools.php?page=wf-sn#sn_core" title="Scan core files with Security Ninja">Scan files</a>';
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

      wp_enqueue_script('sn-core-js', $plugin_url . 'js/wf-sn-core.js', array(), WF_SN_CS_VER, true);
      wp_enqueue_style('sn-core-css', $plugin_url . 'css/wf-sn-core.css', array(), WF_SN_CS_VER);

      wp_enqueue_script('sn-core-snippet', $plugin_url . 'js/snippet.min.js', array(), '1.0', true);
      wp_enqueue_style('sn-core-snippet', $plugin_url . 'css/snippet.min.css', array(), '1.0');
    } // if
  } // enqueue_scripts


  // ajax for viewing file source
  function get_file_source() {
    $out = array();

    if (!current_user_can('administrator') || md5(WF_SN_CS_SALT . stripslashes(@$_POST['filename'])) != $_POST['hash']) {
      $out['err'] = 'Cheating are you?';
      die(json_encode($out));
    }

    $out['ext'] = pathinfo(@$_POST['filename'], PATHINFO_EXTENSION);
    $out['source'] = '';

    if (is_readable($_POST['filename'])) {
      $content = file_get_contents($_POST['filename']);
      if ($content !== FALSE) {
        $out['err'] = 0;
        $out['source'] = utf8_encode($content);
      } else {
        $out['err'] = 'File is empty.';
      }
    } else {
      $out['err'] = 'File does not exist or is not readable.';
    }

    die(json_encode($out));
  } // get_file_source


  // add new tab
  function sn_tabs($tabs) {
    $core_tab = array('id' => 'sn_core', 'class' => '', 'label' => 'Core Scanner', 'callback' => array(__CLASS__, 'core_page'));
    $done = 0;

    for ($i = 0; $i < sizeof($tabs); $i++) {
      if ($tabs[$i]['id'] == 'sn_core') {
        $tabs[$i] = $core_tab;
        $done = 1;
        break;
      }
    } // for

    if (!$done) {
      $tabs[] = $core_tab;
    }

    return $tabs;
  } // sn_tabs


  // check if proper Security Ninja core version exists
  function check_sn_version() {
    if (class_exists('wf_sn')
        && defined('WF_SN_VER')
        && version_compare(WF_SN_VER, 1.2,  '>=')) {
      return true;
    } else {
      return false;
    }
  } // check_sn_version


  // do the actual scanning
  function scan_files($return = false) {
    $results['missing_ok'] =  $results['missing_bad'] = array();
    $results['changed_ok'] = $results['changed_bad'] = array();
    $results['ok'] = array();
    $results['last_run'] = current_time('timestamp');
    $results['total'] = 0;

    $i = 0;

    $ver = get_bloginfo('version');
    $missing_ok = array('index.php', 'readme.html', 'license.txt', 'wp-config-sample.php',
                        'wp-admin/install.php', 'wp-admin/upgrade.php', 'wp-config.php');
    $changed_ok = array('index.php', 'wp-config.php');

    if (file_exists(dirname(__FILE__) . '/hashes/filehashes-' . $ver . '.php')) {
      require 'hashes/filehashes-' . $ver . '.php';

      $results['total'] = sizeof($filehashes['files']);
      foreach ($filehashes['files'] as $file => $hash) {
        clearstatcache();

        if (file_exists(ABSPATH . $file)) {
          if ($hash == md5_file(ABSPATH . $file)) {
            $results['ok'][] = $file;
          } elseif (in_array($file, $changed_ok)) {
            $results['changed_ok'][] = $file;
          } else {
            $results['changed_bad'][] = $file;
          }
        } else {
          if (in_array($file, $missing_ok)) {
            $results['missing_ok'][] = $file;
          } else {
            $results['missing_bad'][] = $file;
          }
        }
      } // foreach file

      if ($return) {
        return $results;
      } else {
        update_option(WF_SN_CS_OPTIONS_KEY, $results);
        die('1');
      }
    } else {
      // no file definitions for this version of WP
      if ($return) {
        return null;
      } else {
        update_option(WF_SN_CS_OPTIONS_KEY, null);
        die('0');
      }
    }
  } // scan_files


  // display results
  function core_page() {
    if (!current_user_can('administrator')) {
      echo 'Cheating are you?';
      return;
    }

    if (!file_exists(dirname(__FILE__) . '/hashes/filehashes-' . get_bloginfo('version') . '.php')) {
      echo '<p><b>Error:</b> Unfortunately Core Scanner does not have core file definitions for your version of WordPress. Please upgrade both WordPress and Security Ninja Core Scanner to the latest available version.</p>';
      return;
    }

    $results = get_option(WF_SN_CS_OPTIONS_KEY);

    echo '<p class="submit"><input type="button" value=" Scan core files " id="sn-run-scan" class="button-primary" />&nbsp;&nbsp;';
    if (isset($results['last_run']) && $results['last_run']) {
      echo '<span class="sn-notice">Files were last scanned on: ' . date(get_option('date_format') . ' ' . get_option('time_format'), $results['last_run']) . '.</span>';
    }
    echo '</p>';
    echo '<p><strong>Please read!</strong> Files are scanned and compared via the MD5 hashing algorithm to original WordPress core files available from wordpress.org.
    Not every change on core files is malicious and changes can serve a legitimate purpose. However if you are not a developer and you did not change the files yourself the changes most probably come from an exploit.<br />
    The WordPress community strongly advises that you never modify any WP core files!</p><br />';

    if ($results['changed_bad']) {
      echo '<div class="sn-cs-changed-bad">';
      echo '<h4>Following core files have been modified and they should not have been!</h4>';
      echo '<p>If you didn\'t modify the following files and don\'t know who did they are most probably infected by a 3rd party malicious code.<br>
      Please use caution when restoring files because there is no undo button.</p>';
      echo self::list_files($results['changed_bad'], true, true);
      echo '</div>';
    }

    if ($results['missing_bad']) {
      echo '<div class="sn-cs-missing-bad">';
      echo '<h4>Following core files are missing and they should not be.</h4>';
      echo '<p>Missing core files my indicate a bad auto-update or they simply were not copied on the server when the site was setup.<br>
      If there is no legitimate reason for the files to be missing use the restore action to create them.</p>';
      echo self::list_files($results['missing_bad'], false, true);
      echo '</div>';
    }

    if ($results['changed_ok']) {
      echo '<div class="sn-cs-changed-ok">';
      echo '<h4>Following core files have been modified and they are supposed to be.</h4>';
      echo '<p>There are only two core files (<i>/wp-config.php</i> and <i>/index.php</i>) that should be modified. This is normal and one or both of them should appear on this list.<br>
      You can still have a look at their source to check for any suspicious code.</p>';
      echo self::list_files($results['changed_ok'], true, false);
      echo '</div>';
    }

    if ($results['missing_ok']) {
      echo '<div class="sn-cs-missing-ok">';
      echo '<h4>Following core files are missing but they are not vital.</h4>';
      echo '<p>Some files like <i>/readme.html</i> are not vital and should be removed to hide WP version info. Do not restore them unless you really need them and know what you are doing.</p>';
      echo self::list_files($results['missing_ok'], false, true);
      echo '</div>';
    }

    if ($results['ok']) {
      echo '<div class="sn-cs-ok">';
      echo '<h4>A total of <span class="sn_count">' . $results['total'] . '</span> files were scanned and <span class="sn_count">' . sizeof($results['ok']) . '</span> are unmodified and safe.</h4>';
      echo '<p>Do not expect to get a ' . $results['total'] . '/' . $results['total'] . ' count. That is impossible. The number only servers as a reference to reassure you in the number of files that were scanned and found unmodified for WP core v' . get_bloginfo('version') . '.</p>';
      echo '</div>';
    }

    // dialogs
    echo '<div id="source-dialog" style="display: none;" title="File source"><p>Please wait.</p></div>';
    echo '<div id="restore-dialog" style="display: none;" title="Restore file"><p>Please wait.</p></div>';
  } // core_page


  // check if files can be restored
  function check_file_write() {
    $url = wp_nonce_url('options.php?page=wf-sn', 'wf-sn-cs');
    ob_start();
    $creds = request_filesystem_credentials($url, '', false, false, null);
    ob_end_clean();

    return (bool) $creds;
  } // check_file_write


  // restore the selected file
  function restore_file() {
    $file = str_replace(ABSPATH, '', stripslashes($_POST['filename']));

    $url = wp_nonce_url('options.php?page=wf-sn', 'wf-sn-cs');
    $creds = request_filesystem_credentials($url, '', false, false, null);
    if (!WP_Filesystem($creds)) {
      die('can\'t write to file.');
    }

    $org_file = wp_remote_get('http://core.trac.wordpress.org/browser/tags/' . get_bloginfo('version') . '/src/' . $file . '?format=txt');
    if (!$org_file['body']) {
      die('can\'t download remote file source.');
    }

    global $wp_filesystem;
    if (!$wp_filesystem->put_contents(trailingslashit(ABSPATH) . $file, $org_file['body'], FS_CHMOD_FILE)) {
      die('unknown error while writing file.');
    }

    self::scan_files();
    die('1');
  } // restore_file


  // render restore file dialog
  function restore_file_dialog() {
    $out = array();

    if (!current_user_can('administrator') || md5(WF_SN_CS_SALT . stripslashes(@$_POST['filename'])) != $_POST['hash']) {
      $out['err'] = 'Cheating are you?';
      die(json_encode($out));
    }

    if (self::check_file_write()) {
      $out['out'] = '<p>By clicking the "restore file" button a copy of the original file will be downloaded from wordpress.org and the
      modified file will be overwritten. Please note that there is no undo!<br /><br /><br />
      <input type="button" value="Restore file" data-filename="' . stripslashes(@$_POST['filename']) . '" id="sn-restore-file" class="button-primary" /></p>';
    } else {
      $out['out'] = '<p>Your WordPress core files are not writable from PHP. This is not a bad thing as it increases your security but
      you will have to restore the file manually by logging on to your FTP account and overwriting the file. You can
      <a target="_blank" href="http://core.trac.wordpress.org/browser/tags/' . get_bloginfo('version') . '/' . str_replace(ABSPATH, '', stripslashes($_POST['filename'])) . '?format=txt' . '">download the file directly</a> from worpress.org.</p>';
    }

    die(json_encode($out));
  } // restore_file


  // helper function for listing files
  function list_files($files, $view = false, $restore = false) {
    $out = '';
    $out .= '<ul class="sn-file-list">';

    foreach ($files as $file) {
      $out .= '<li>';
      $out .= '<span class="sn-file">' . ABSPATH . $file . '</span>';
      if ($view) {
        $out .= ' <a data-hash="' . md5(WF_SN_CS_SALT . ABSPATH . $file) . '" data-file="' . ABSPATH . $file . '" href="#source-dialog" class="sn-show-source">view file source</a>';
      }
      if ($restore) {
        $out .= ' <a data-hash="' . md5(WF_SN_CS_SALT . ABSPATH . $file) . '" data-file="' . ABSPATH . $file . '" href="#restore-dialog" class="sn-restore-source">restore file</a>';
      }
      $out .= '</li>';
    } // foreach $files

    $out .= '</ul>';

    return $out;
  } // list_files


  // display warning if test were never run
  function run_tests_warning() {
    $tests = get_option(WF_SN_CS_OPTIONS_KEY);

    if (self::is_plugin_page() && !@$tests['last_run']) {
      echo '<div id="message" class="error"><p>Security Ninja Core Scanner <strong>tests were never run.</strong> Click "Scan core files" to run them now and check your core files for exploits.</p></div>';
    } elseif (self::is_plugin_page() && (current_time('timestamp') - 30*24*60*60) > $tests['last_run']) {
      echo '<div id="message" class="error"><p>Security Ninja Core Scanner <strong>tests were not run for more than 30 days.</strong> It\'s advisable to run them once in a while. Click "Scan core files" to run them now check your core files for exploits.</p></div>';
    }
  } // run_tests_warning


  // display warning if WP is outdated
  function min_version_error() {
    echo '<div id="message" class="error"><p>Security Ninja - Core Scanner add-on <b>requires WordPress version 3.3</b> or higher to function properly. You\'re using WordPress version ' . get_bloginfo('version') . '. Please <a href="' . admin_url('update-core.php') . '" title="Update WP core">update</a>.</p></div>';
  } // min_version_error


  // display error message if SN version is too low
  function no_sn_core_error() {
    echo '<div id="message" class="error"><p>Security Ninja - Core Scanner Add-on <b>requires Security Ninja version 1.2</b> or higher to function properly. If you have already purchased Security Ninja please upgrade it; otherwise get it for only $10 on <a href="http://codecanyon.net/item/security-ninja/577696?ref=WebFactory">CodeCanyon</a>.</p></div>';
  } // no_sn_core_error


  // clean-up when deactivated
  function deactivate() {
    delete_option(WF_SN_CS_OPTIONS_KEY);
  } // deactivate
} // wf_sn_cs class


// hook everything up
add_action('init', array('wf_sn_cs', 'init'));

// when deativated clean up
register_deactivation_hook( __FILE__, array('wf_sn_cs', 'deactivate'));
?>