<?php
/*
Plugin Name: Art Matters Consulting Site Options
Description: Adds site-wide Options for AMC
Version: 1.0
Author: Chad Jewsbury
Author URI: mailto:chadjewsbury@gmail.com
*/


if (!defined('AMCOPTIONS_VERSION_KEY')) {
    define('AMCOPTIONS_VERSION_KEY', 'AMCOPTIONS_version');
}

if (!defined('AMCOPTIONS_VERSION_NUM')) {
    define('AMCOPTIONS_VERSION_NUM', '1.0');
}

add_option(AMCOPTIONS_VERSION_KEY, AMCOPTIONS_VERSION_NUM);


/**
 * Provides site wide options to WordPress.
 *
 * @author  Chad Jewsbury <chadjewsbury@gmail.com>
 * @version 2013-11-17
 */
class AmcOptions
{
    /* B O O T S T R A P
    * -------------------------------------------------------------------------------------------------------------- */

    /**
     * Initializes the plugin and sets all of WordPress' actions and hooks.
     */
    public function AmcOptions()
    {
        // Administration
        add_action('admin_menu', array(&$this, 'initAdmin'));
    }

    /**
     * Installs and sets up this plugin, for both first-time setup, upgrades, and per-use settings.
     */
    public function install()
    {

    }

    /* A D M I N I S T R A T I O N
     * -------------------------------------------------------------------------------------------------------------- */

    /**
     * Initializes our admin to support the new metabox.
     */
    public function initAdmin()
    {
        // Adds menu item
        add_menu_page('AMC Options', 'AMC Options', 'manage_options', 'amc-options', array(&$this, 'amcOptionsRender'), '', 30);
    }

    public function amcOptionsRender()
    {
        wp_enqueue_media();
        include(__DIR__ . '/assets/options.php');
    }
}

// Initialize and run our plugin
$amcOptions = new AmcOptions();