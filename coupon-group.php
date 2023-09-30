<?php

/*
Plugin Name: Coupon Group
Description: This plugin enhances WooCommerce by introducing grouped coupon functionalities. Store administrators can effortlessly create, manage, and assign coupon groups with varying properties, such as discount rates or same-day delivery perks. Designed for tailored promotions, it allows for precise targeting by associating specific customers with unique coupon groups, ensuring personalized shopping experiences for every user.
Version: 1.0
Author: Spiros Dimou
Auhtor URI:
*/

// Ensure this file is being included by WordPress (and not accessed directly)
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}


// Check if WooCommerce is activated
if (!function_exists('is_woocommerce_activated')) {
    function is_woocommerce_activated()
    {
        if (class_exists('woocommerce')) {
            return true;
        } else {
            return false;
        }
    }
}

require_once plugin_dir_path(__FILE__) . 'includes/form-handler.php';
require_once plugin_dir_path(__FILE__) . 'includes/helper-functions.php';
require_once plugin_dir_path(__FILE__) . 'includes/coupons-state-handler.php';
require_once plugin_dir_path(__FILE__) . 'includes/cron-tasks.php';
require_once plugin_dir_path(__FILE__) . 'includes/views.php';

include_once plugin_dir_path(__FILE__) . 'includes/order-page-view.php';


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
            'Coupon Group',     // Menu title
            'manage_options',   // Capability
            'coupon-group',     // Menu slug
            'overview_page', // Callback function
            'data:image/svg+xml;base64, PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIGhlaWdodD0iMjRweCIgdmlld0JveD0iMCAwIDI0IDI0IiB3aWR0aD0iMjRweCIgZmlsbD0iI2ZmZmZmZiI+PHBhdGggZD0iTTAgMGgyNHYyNEgwVjB6IiBmaWxsPSJub25lIi8+PHBhdGggZD0ibTIxLjQxIDExLjU4LTktOUMxMi4wNSAyLjIyIDExLjU1IDIgMTEgMkg0Yy0xLjEgMC0yIC45LTIgMnY3YzAgLjU1LjIyIDEuMDUuNTkgMS40Mmw5IDljLjM2LjM2Ljg2LjU4IDEuNDEuNThzMS4wNS0uMjIgMS40MS0uNTlsNy03Yy4zNy0uMzYuNTktLjg2LjU5LTEuNDFzLS4yMy0xLjA2LS41OS0xLjQyek0xMyAyMC4wMSA0IDExVjRoN3YtLjAxbDkgOS03IDcuMDJ6Ii8+PGNpcmNsZSBjeD0iNi41IiBjeT0iNi41IiByPSIxLjUiLz48L3N2Zz4=',
            55.5                          // Position in menu
        );

        // Submenus
        // This submenu change the title of the item that refers to the main page
        add_submenu_page(
            'coupon-group',
            'Overview',
            'Overview',
            'manage_options',
            'coupon-group',
            'overview_page',
        );

        // Create coupon group submenu
        $new_coupon_group_hook = add_submenu_page(
            'coupon-group',                    // The slug name for the parent menu (to which you are adding this submenu).
            'New Group',          // The text to be displayed in the title tags of the page when the menu is selected.
            'New Group',               // The text to be used for the menu.
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
            'New Coupon Option',               // Page title
            'New Coupon Option',                     // Menu title
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

        wp_enqueue_script('create-coupon-group', plugins_url('assets/js/create-coupon-group.js', __FILE__));

        //Custom Select2 style
        wp_enqueue_style('select2-custom-style', plugins_url('assets/css/select2-custom-style.css', __FILE__));
    }
}

$coupon_group_plugin = new CouponGroupPlugin();
