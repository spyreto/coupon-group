<?php

/*
Plugin Name: Coupon Group
Description: The Coupon Group plugin extends the functionality of your WooCommerce store by allowing you to create and manage custom coupon groups with unique offers and membership options.
Version: 1.0.0-beta2
Author: Spiros Dimou
Auhtor URI: https://www.linkedin.com/in/spiridon-dimou
*/

// Ensure this file is being included by WordPress (and not accessed directly)
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Include the necessary WordPress core file.
include_once(ABSPATH . 'wp-admin/includes/plugin.php');

// Check if WooCommerce is active
if (is_admin() && !is_plugin_active('woocommerce/woocommerce.php')) {
    // WooCommerce is not activated.
    wp_die('Error: WooCommerce plugin is not activated. Please activate WooCommerce to use Coupon Group Plugin.');
}

require_once plugin_dir_path(__FILE__) . 'includes/form-handler.php';
require_once plugin_dir_path(__FILE__) . 'includes/helper-functions.php';
require_once plugin_dir_path(__FILE__) . 'includes/coupons-state-handler.php';
require_once plugin_dir_path(__FILE__) . 'includes/cron-tasks.php';
require_once plugin_dir_path(__FILE__) . 'templates/overview-page.php';
require_once plugin_dir_path(__FILE__) . 'templates/group-page.php';
require_once plugin_dir_path(__FILE__) . 'templates/option-page.php';
require_once plugin_dir_path(__FILE__) . 'templates/shared.php';
require_once plugin_dir_path(__FILE__) . 'templates/footer.php';
require_once plugin_dir_path(__FILE__) . 'templates/order-page-view.php';


// Plugin pages ids
define('COUPON_GROUP_PAGES', array(
    'toplevel_page_coupon-group',
    'admin_page_edit-coupon-group',
    'admin_page_edit-coupon-option',
    'coupon-group_page_new-group',
    'coupon-group_page_create-coupon-option'
));

class CouponGroupPlugin
{
    function __construct()
    {
        add_action('admin_menu', array($this, 'menu'));
        add_action('admin_init', array($this, 'register_coupon_group_cpt'));
        add_action('admin_enqueue_scripts', array($this, 'add_scripts'));

        register_activation_hook(__FILE__, 'start_my_daily_task');
        register_deactivation_hook(__FILE__, 'stop_my_daily_task');
    }

    function add_scripts()
    {
        //Custom style
        wp_enqueue_style('coupon-group', plugins_url('assets/css/coupon-group.css', __FILE__));
        // Main script
        wp_enqueue_script('coupon-group', plugins_url('assets/js/coupon-group.js', __FILE__));
    }

    /**
     * Register the Custom Post Type for coupon group
     * 
     */
    function register_coupon_group_cpt()
    {
        $args = array(
            'public' => true,
            'label'  => 'Coupon Groups',
            'menu_icon' => 'dashicons-tickets',
        );
        register_post_type('coupon_group', $args);
    }

    /**
     * Plugin Menu
     * 
     */
    function menu()
    {

        add_menu_page(
            'Overview',         // Page title
            __('Coupon Group', 'coupon-group'),     // Menu title
            'manage_options',   // Capability
            'coupon-group',     // Menu slug
            'overview_page', // Callback function
            'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIGhlaWdodD0iMjBweCIgdmlld0JveD0iMCAwIDI0IDI0IiB3aWR0aD0iMjBweCIgPgo8cGF0aCBmaWxsPSJibGFjayIgIGQ9Im0yMS40MSAxMS41OC05LTlDMTIuMDUgMi4yMiAxMS41NSAyIDExIDJINGMtMS4xIDAtMiAuOS0yIDJ2N2MwIC41NS4yMiAxLjA1LjU5IDEuNDJsOSA5Yy4zNi4zNi44Ni41OCAxLjQxLjU4czEuMDUtLjIyIDEuNDEtLjU5bDctN2MuMzctLjM2LjU5LS44Ni41OS0xLjQxcy0uMjMtMS4wNi0uNTktMS40MnpNMTMgMjAuMDEgNCAxMVY0aDd2LS4wMWw5IDktNyA3LjAyeiIvPgo8Y2lyY2xlIGZpbGw9ImJsYWNrIiBjeD0iNi41IiBjeT0iNi41IiByPSIxLjUiLz4KPC9zdmc+',
            55.5,                          // Position in menu
        );

        // Submenus
        // This submenu change the title of the item that refers to the main page
        add_submenu_page(
            'coupon-group',
            'Overview',
            __('Overview', 'coupon-group'),
            'manage_options',
            'coupon-group',
            'overview_page',
        );

        // Create coupon group submenu
        $new_coupon_group_hook = add_submenu_page(
            'coupon-group',                    // The slug name for the parent menu (to which you are adding this submenu).
            'New Group',          // The text to be displayed in the title tags of the page when the menu is selected.
            __('New Group', 'coupon-group'),               // The text to be used for the menu.
            'manage_options',                // The capability required for this menu to be displayed to the user.
            'new-group',        // The slug name to refer to this menu by.
            'display_create_or_edit_group_page' // The function to be called to output the content for this page.
        );
        add_action("load-{$new_coupon_group_hook}", array($this, 'new_group_page_assets'));

        // Edit coupon group submenu(not displayed)
        $edit_coupon_group_hook = add_submenu_page(
            null, // this makes it a hidden submenu
            'Edit Coupon Group',
            'Edit Coupon Group',
            'manage_options',
            'edit-coupon-group',
            'display_create_or_edit_group_page'
        );
        add_action("load-{$edit_coupon_group_hook}", array($this, 'new_group_page_assets'));

        //Create coupon option submenu
        add_submenu_page(
            'coupon-group',           // Parent slug
            'New Option',               // Page title
            __('New Option', 'coupon-group'),   // Menu title
            'manage_woocommerce',             // Capability
            'create-coupon-option',                // Menu slug
            'display_create_or_edit_coupon_option_page'  // Callback function
        );
        //Create coupon option submenu
        add_submenu_page(
            null,
            'Edit Coupon Option',               // Page title
            'Edit Coupon Option',                     // Menu title
            'manage_woocommerce',             // Capability
            'edit-coupon-option',                // Menu slug
            'display_create_or_edit_coupon_option_page'  // Callback function
        );
    }

    /**
     * Loads the new coupon group page assets
     * 
     */
    function new_group_page_assets()
    {
        // Enqueue the jQuery UI style for the datepicker
        wp_enqueue_style('jquery-ui', '//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');

        // Enqueue WordPress built-in script for datepicker
        wp_enqueue_script('jquery-ui-datepicker');

        // Enqueue Select2 for better select boxes
        wp_enqueue_script('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', array('jquery'), '4.0.13', true);
        wp_enqueue_style('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css');

        wp_enqueue_script('create-coupon-group-page', plugins_url('assets/js/create-coupon-group-page.js', __FILE__));

        //Custom Select2 style
        wp_enqueue_style('select2-custom-style', plugins_url('assets/css/select2-custom-style.css', __FILE__));
    }
}


/**
 * Uninstallation routine for My Custom Plugin.
 *
 * This function is called when the user decides to delete the plugin.
 * It performs cleanup tasks to remove any plugin-related data and settings.
 */
function coupon_group_uninstall()
{
    // Get all user IDs.
    $user_ids = get_users(array('fields' => 'ID'));

    // Loop through each user and remove the plugin's user_meta.
    foreach ($user_ids as $user_id) {
        // Check if the user_meta exists for the user.
        $coupons_to_remove_meta = get_user_meta($user_id, '_coupons_to_remove', true);
        $coupons_to_remove_timestamp_meta = get_user_meta($user_id, '_coupons_to_remove_timestamp', true);
        $coupons_to_add_meta = get_user_meta($user_id, '_coupons_to_add', true);
        $coupons_to_add_timestamp_meta = get_user_meta($user_id, '_coupons_to_add_timestamp', true);

        if ($coupons_to_remove_meta !== '') {
            delete_user_meta($user_id, '_coupons_to_remove');
        }
        if ($coupons_to_remove_timestamp_meta !== '') {
            delete_user_meta($user_id, '_coupons_to_remove_timestamp');
        }
        if ($coupons_to_add_meta !== '') {
            delete_user_meta($user_id, '_coupons_to_add');
        }
        if ($coupons_to_add_timestamp_meta !== '') {
            delete_user_meta($user_id, '_coupons_to_add_timestamp');
        }
    }

    // Deactivate all active coupon groups
    $active_coupon_groups = get_active_coupon_groups();
    foreach ($active_coupon_groups as $coupon_group) {
        update_post_meta($coupon_group->ID, '_is_active', '0');
    }

    // Unregister the custom post type, so the rules are no longer in memory.
    unregister_post_type('coupon_group');
}
// Register the uninstallation hook.
register_uninstall_hook(__FILE__, 'coupon_group_uninstall');

// Define a custom function to run when the plugin is deactivated.
function coupon_group_deactivation_action()
{
    // Deactivate all active coupon groups
    $active_coupon_groups = get_active_coupon_groups();
    foreach ($active_coupon_groups as $coupon_group) {
        update_post_meta($coupon_group->ID, '_is_active', '0');
    }
}
// Register the deactivation hook.
register_deactivation_hook(__FILE__, 'coupon_group_deactivation_action');

/**
 * Load the text domain for translation.
 */
function coupon_group_load_text_domain()
{
    load_plugin_textdomain('coupon-group', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}

add_action('plugins_loaded', 'coupon_group_load_text_domain');


// Initialize the plugin
$coupon_group_plugin = new CouponGroupPlugin();
