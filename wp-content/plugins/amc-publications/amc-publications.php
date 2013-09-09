<?php
/*
Plugin Name: Art Matters Consulting Publications
Description: Adds publications post type
Version: 1.0
Author: Chad Jewsbury
Author URI: mailto:chadjewsbury@gmail.com
*/


if (!defined('AMCPUBLICATONS_VERSION_KEY')) {
    define('AMCPUBLICATONS_VERSION_KEY', 'AMCPUBLICATONS_version');
}

if (!defined('AMCPUBLICATONS_VERSION_NUM')) {
    define('AMCPUBLICATONS_VERSION_NUM', '1.0');
}

add_option(AMCPUBLICATONS_VERSION_KEY, AMCPUBLICATONS_VERSION_NUM);


/**
 * Provides publications post types to WordPress.
 *
 * @author  Chad Jewsbury <chadjewsbury@gmail.com>
 * @version 2013-09-09
 */
class AmcPublications
{

    /* S T A T I C
      --------------------------------------------------------------------------------------------------------------- */

    public static $fields
        = array(
            'Publication Information' => array(
                'pub_sub_title'        => array(
                    'label'     => 'Publication Sub Title',
                    'type'      => 'text',
                    'required'  => false,
                ),
                'pub_description'  => array(
                    'label'    => 'Summary',
                    'type'     => 'textarea',
                    'required' => true,
                    'autop'    => true,
                ),
                'pub_source'  => array(
                    'label'    => 'Journal Name',
                    'type'     => 'text',
                    'required' => true,
                ),
                'pub_date'  => array(
                    'label'    => 'Date Published',
                    'type'     => 'text',
                    'required' => true,
                ),
                'pub_author'  => array(
                    'label'    => 'Author',
                    'type'     => 'text',
                    'required' => true,
                ),
            ),
            'Url'                => array(
                'pub_url'        => array(
                    'label'    => 'Publication URL (if available)',
                    'type'     => 'text',
                    'required' => false,
                ),
                'pub_url_note' => array(
                    'label'    => 'URL comments (ie: subscription required)',
                    'type'     => 'textarea',
                    'required' => false,
                ),
            ),
        );


    /* B O O T S T R A P
     * -------------------------------------------------------------------------------------------------------------- */

    /**
     * Initializes the plugin and sets all of WordPress' actions and hooks.
     */
    public function AmcPublications()
    {
        // Administration
        add_action('admin_init', array(&$this, 'initAdmin'));
        add_action('init', array(&$this, 'initTaxonomy'));

        // Initialization
        add_action('init', array(&$this, 'install'));

//        add_filter('enter_title_here', array(&$this, 'initTitle'));
//        add_action('do_meta_boxes', array(&$this, 'initRolloverImage'));

        add_action('save_post', array(&$this, 'savePublication'), 3, 1);
    }


    /**
     * Installs and sets up this plugin, for both first-time setup, upgrades, and per-use settings.
     */
    public function install()
    {
        // Register our post type
        register_post_type(
            'amc_publications',
            array(
                'labels'        => array(
                    'name'               => _x('Publications', 'Publications general name'),
                    'singular_name'      => _x('Publication', 'Publication singular name'),
                    'add_new_item'       => __('Add New Publication'),
                    'edit_item'          => __('Edit Publication'),
                    'new_item'           => __('New Publication'),
                    'view_item'          => __('View Publication'),
                    'items_archive'      => __('Publications Archive'),
                    'search_items'       => __('Search Publications'),
                    'not_found'          => __('No publications found'),
                    'not_found_in_trash' => __('No publications found in Trash'),
                ),
                'description'   => 'Articles Publised by Art Matters Consulting.',
                'public'        => true,
                'has_archive'   => true,
                'show_ui'       => true,
                'show_in_menu'  => true,
                'menu_position' => 20,
                'supports'      => array(
                    'title',
                    'thumbnail',
                    'revisions',
                    'page-attributes',
                ),
                'rewrite'       => array(
                    'slug'       => __('publications'),
                    'with_front' => false,
                ),
                'capability_type' => 'page',
                'taxonomies' => array('publications_tags', 'publications_categories'),
            )
        );

        // Adds rendered version of all ' content to post_content field for SEO Plugin.
        /*if (get_option('gwip_case_studies_content_update') === false) {

            $allCaseStudies = new WP_Query( array(
                'post_type' => 'case_studies',
                'nopaging'  => 'true',
            ));

            while ($allCaseStudies->have_posts()) {
                $allCaseStudies->the_post();

                if (wp_is_post_revision($allCaseStudies->post)) {
                    continue;
                }

                ob_start();
                get_template_part('content', 'case_studies');
                $rendered = ob_get_clean();

                remove_action('save_post', array(&$this, 'saveCaseStudy'), 3, 1);
                $args = array(
                    'ID'           => $allCaseStudies->post->ID,
                    'post_content' => $rendered,
                );
                wp_update_post($args);
                add_action('save_post', array(&$this, 'saveCaseStudy'), 3, 1);
            }

            add_option('gwip_case_studies_content_update', 1);
        }*/
    }


    /* A D M I N I S T R A T I O N
     * -------------------------------------------------------------------------------------------------------------- */

    /**
     * Initializes our admin to support the new metabox.
     */
    public function initAdmin()
    {
        // Add our write panels
        add_meta_box(
            'amc_publications_meta',
            __('Publication Details'),
            array(&$this, 'renderPublication'),
            'amc_publications',
            'normal',
            'high'
        );


        // Styles and scripts
        wp_enqueue_style('amc_metabox_style', plugin_dir_url(__FILE__) . 'assets/style.css');

        // Ensure our permalinks get updated
        flush_rewrite_rules();
    }


    /**
     * Initialize our taxonomies we use with these meta boxes.
     */
    public function initTaxonomy()
    {
        register_taxonomy(
            'publications_tags',
            'amc_publications',
            array(
                'labels'                     => array(
                    'name'                       => _x('Publication Tags', 'Taxonomy General Name'),
                    'singular_name'              => _x('Publication Tag', 'Taxonomy Singular Name'),
                    'menu_name'                  => __('Publication Tags'),
                    'all_items'                  => __('All Publication Tags'),
                    'parent_item'                => __('Parent Publication Tag'),
                    'parent_item_colon'          => __('Parent Publication Tag:'),
                    'new_item_name'              => __('New Publication Tag'),
                    'add_new_item'               => __('Add New Publication Tag'),
                    'edit_item'                  => __('Edit Publication Tag'),
                    'update_item'                => __('Update Publication Tag'),
                    'separate_items_with_commas' => __('Separate Publication Tags with commas'),
                    'search_items'               => __('Search Publication Tags'),
                    'add_or_remove_items'        => __('Add or remove Publication Tags'),
                    'choose_from_most_used'      => __('Choose from the most used Publication Tags'),
                ),
                'hierarchical'               => false,
                'public'                     => true,
                'show_ui'                    => true,
                'show_admin_column'          => true,
                'show_in_nav_menus'          => true,
                'show_tagcloud'              => false,
                'rewrite'                    => array(
                    'slug'                       => __('publications/tags'),
                    'with_front'                 => false,
                    'hierarchical'               => false,
                ),
            )
        );


        register_taxonomy(
            'publications_categories',
            'amc_publications',
            array(
                'labels'                     => array(
                    'name'                       => _x('Publication Categories', 'Taxonomy General Name'),
                    'singular_name'              => _x('Publication Category', 'Taxonomy Singular Name'),
                    'menu_name'                  => __('Publication Categories'),
                    'all_items'                  => __('All Publication Categories'),
                    'parent_item'                => __('Parent Publication Category'),
                    'parent_item_colon'          => __('Parent Publication Category:'),
                    'new_item_name'              => __('New Publication Category'),
                    'add_new_item'               => __('Add New Publication Category'),
                    'edit_item'                  => __('Edit Publication Category'),
                    'update_item'                => __('Update Publication Category'),
                    'separate_items_with_commas' => __('Separate Publication Categories with commas'),
                    'search_items'               => __('Search Publication Categories'),
                    'add_or_remove_items'        => __('Add or remove Publication Categories'),
                    'choose_from_most_used'      => __('Choose from the most used Publication Categories'),
                ),
                'hierarchical'               => false,
                'public'                     => true,
                'show_ui'                    => true,
                'show_admin_column'          => true,
                'show_in_nav_menus'          => true,
                'show_tagcloud'              => false,
                'rewrite'                    => array(
                    'slug'                       => __('publications/categories'),
                    'with_front'                 => false,
                    'hierarchical'               => false,
                ),
            )
        );
    }


    /**
     * Updates the title entry on the entry screen.
     *
     * @param string $title the current title
     *
     * @return string the new title
     */
    public function initTitle($title)
    {
        $screen = get_current_screen();

        if ('amc_publications' == $screen->post_type) {
            $title = __('Publication Title');
        }

        return $title;
    }


    /**
     * Generates the Publication write panel
     */
    public function renderPublication()
    {
        global $post;

        // Build our HTML
        echo "<div class='amc-panel'>\n";

        foreach (AmcPublications::$fields as $group => $fields) {
            echo "  <div class='box'>\n";
            echo "    <h2>" . __($group) . "</h2>\n";

            foreach ($fields as $field => $info) {
                echo"    <label for='" . esc_attr($field) . "'>" . __($info['label']) . ($info['required'] ?
                        " <span class='required'>" . __('(Required)') . "</span>" : "") . "</label>\n";

                $value = get_post_meta($post->ID, $field, true);

                switch ($info['type']) {
                    case 'textarea':
                        echo"    <textarea name='" . esc_attr($field) . "' id='" . esc_attr($field) . "'>"
                            . htmlspecialchars($value) . "</textarea>\n";
                        break;

                    default:
                        echo"    <input type='text' name='" . esc_attr($field) . "' id='" . esc_attr($field)
                            . "' value='" . esc_attr($value) . "' />\n";
                }
            }

            echo "  </div>\n";
        }

        echo "</div>\n\n";

        // Add a noncename to verify this meta later
        wp_nonce_field('amc_publications_nonce', 'amc_publications_nonce');
    }


    /**
     * Saves the Publications write panel
     *
     * @param int $post_id the post ID
     */
    public function savePublication($post_id)
    {
        // Ensure that the post came from our Publications metabox
        if (!wp_verify_nonce($_POST['amc_publications_nonce'], 'amc_publications_nonce')) {
            return $post_id;
        }

        // Ensure we have user rights to edit posts
        if (!current_user_can('edit_post', $post_id)) {
            return $post_id;
        }

        $post_type_id = $_POST['post_type'];

        // Save each field after validation
        $validation_errors = new WP_Error();

        foreach (AmcPublications::$fields as $group => $fields) {
            foreach ($fields as $field => $info) {
                $value = trim(isset($_POST[$field]) ? $_POST[$field] : '');

                if (!$value && $info['required']) {
                    $validation_errors->add('validation', __($info['label']) . ' ' . __('is a required field.'));
                }

                if ($info['autop'] == true){
                    $value = wpautop( $value );
                }

                if (!$value) {
                    delete_post_meta($post_id, $field);
                } else {
                    update_post_meta($post_id, $field, $value);
                }
            }
        }

        // @TODO - Display validation errors as admin notices

        return $post_id;
    }
}


// Initialize and run our plugin
$publications = new AmcPublications();
