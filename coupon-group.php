<?php

/*
Plugin Name: Coupon Group
Description: This plugin enhances WooCommerce by introducing grouped coupon functionalities. Store administrators can effortlessly create, manage, and assign coupon groups with varying properties, such as discount rates or same-day delivery perks. Designed for tailored promotions, it allows for precise targeting by associating specific customers with unique coupon groups, ensuring personalized shopping experiences for every user.
Version: 1.0
Author: Spiros Dimou
Auhtor URI:
*/

// Ensure this file is being included by WordPress (and not accessed directly)
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Check for WooCommerce
// if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
//     die("WooCommerce not activated!");
// }

/**
 * Check if WooCommerce is activated
 */
if ( ! function_exists( 'is_woocommerce_activated' ) ) {
	function is_woocommerce_activated() {
		if ( class_exists( 'woocommerce' ) ) { return true; } else { return false; }
	}
}

require_once plugin_dir_path( __FILE__ ) . 'includes/form-handler.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/helper-functions.php';

class CouponGroupPlugin
{
    function __construct() {
        add_action('admin_menu', array($this, 'menu'));
        add_action('admin_init', array($this, 'register_coupon_group_cpt'));  
        add_action('admin_enqueue_scripts', array($this, 'add_scripts'));

        add_action('woocommerce_coupon_options', array($this, 'display_custom_coupon_option_fields'), 10, 2);
        add_action('save_post',  array($this,'save_custom_coupon_option_fields'), 10, 3);        
    }

    function add_scripts() {
        //Custom style
        wp_enqueue_style('coupon-group', plugins_url( 'assets/css/coupon-group.css', __FILE__ ) );
    }

    function register_coupon_group_cpt() {
        $args = array(
            'public' => true,
            'label'  => 'Coupon Groups',
            'menu_icon' => 'dashicons-tickets',
        );
        register_post_type('coupon_group', $args);
    }
    
    
    function create_coupon_option_page() {
        $form_data = get_transient('new_coupon_option_form_data');
        delete_transient('new_coupon_option_form_data');
        
        // Display error message for form validation
        if ($error = get_transient('new_coupon_option_form_error_msg')) {
            delete_transient('new_coupon_option_form_error-msg');
            echo "<div class='error is-dismissible'><p>{$error}</p></div>";            
        }
        // Display form.
        ?>
        <div class="admin-cg-wrap">
            <div class="admin-cg-main">
                <h1>Create Coupon Option</h1>
                <form method="POST" action="<?php echo esc_url( admin_url( 'admin-post.php' ) );?>">                      
                    <div class="admin-cg-form-field">
                        <label for="custom_coupon_title">Coupon Title</label>
                        <input 
                            type="text" 
                            name="custom_coupon_title" 
                            id="custom_coupon_title" 
                            value="<?php echo esc_attr($form_data['custom_coupon_title'] ?? ''); ?>" 
                            required>                  
                    </div>
                    <div class="admin-cg-form-field">
                        <label for="custom_coupon_description">Description (Optional)</label>
                        <textarea 
                            name="custom_coupon_description" 
                            id="custom_coupon_description" 
                            rows="5" cols="40"><?php echo esc_attr($form_data['custom_coupon_description'] ?? '');?></textarea>
                    </div>
                    <input type="hidden" name="action" value="new_coupon_option_form_action">                
                    <?php 
                        wp_nonce_field('new_coupon_option_action', 'new_coupon_option_nonce'); 
                        submit_button("Save Option");
                    ?>
                </form>
            </div>
        </div>
        <?php
    }

    /**
     * Displays the custom coupon options in Add New Coupon page.
     * 
     */
    function display_custom_coupon_option_fields($coupon_id, $coupon) {
        $custom_coupon_options = get_option('custom_coupon_options');
               
        foreach ($custom_coupon_options as $index => $custom_option) {
            $checkbox_id = 'enable_custom_coupon_option_' . $index;
            $is_checked = get_post_meta($coupon_id, $custom_option['title'], true) === 'yes';
           
            woocommerce_wp_checkbox(array(
                'id'            => $checkbox_id,
                'label'         => $custom_option['title'],
                'description'   => $custom_option['description'],
                'value'         => $is_checked ? 'yes' : 'no',
            ));
        }        
    }

    /**
     * Saves the custom coupon options when a wc coupon is created.
     * 
     */
    function save_custom_coupon_option_fields($post_id, $post, $update) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (get_post_type($post_id) == 'shop_coupon') {
            $custom_coupons = get_option('custom_coupon_options', array());
            foreach ($custom_coupons as $index => $custom_coupon) {
                $checkbox_id = 'enable_custom_coupon_option_' . $index;
        
                // Check if the checkbox for this custom coupon was checked
                if (isset($_POST[$checkbox_id]) && $_POST[$checkbox_id] === 'yes') {
                    // Save it into the coupon's metadata
                    update_post_meta($post_id, $custom_coupon['title'], 'yes');
                } else {
                    // Otherwise, delete the metadata (or set it to 'no', depending on your preference)
                    update_post_meta($post_id, $custom_coupon['title'], 'no');
                }
            }
        }
        return;
    }
        
    /**
     * Plugin Menu
     * 
     */
    function menu() {        
        add_menu_page(
            'Overview',         // Page title
            'Coupon Group',     // Menu title
            'manage_options',   // Capability
            'coupon-group',     // Menu slug
            array($this, 'overview_page'), // Callback function
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
            array($this, 'overview_page'), 
        );

        // New coupon group page
        $new_coupon_hook = add_submenu_page(
            'coupon-group',                    // The slug name for the parent menu (to which you are adding this submenu).
            'New Group',          // The text to be displayed in the title tags of the page when the menu is selected.
            'New Group',               // The text to be used for the menu.
            'manage_options',                // The capability required for this menu to be displayed to the user.
            'new_group',        // The slug name to refer to this menu by.
            array($this, 'new_group_page')  // The function to be called to output the content for this page.
        );

        add_action("load-{$new_coupon_hook}", array($this, 'new_group_page_assets'));
        
        // // New coupon page
        // add_submenu_page(
        //     'coupon-group',                    // The slug name for the parent menu (to which you are adding this submenu).
        //     'New Coupon',          // The text to be displayed in the title tags of the page when the menu is selected.
        //     'New Coupon',               // The text to be used for the menu.
        //     'manage_options',                // The capability required for this menu to be displayed to the user.
        //     'new_coupon',        // The slug name to refer to this menu by.
        //     array($this, 'new_coupon_page')  // The function to be called to output the content for this page.
        // );
        
        //Create coupon option submenu
        add_submenu_page(
            'coupon-group',           // Parent slug
            'New Coupon Option',               // Page title
            'New Coupon Option',                     // Menu title
            'manage_woocommerce',             // Capability
            'create_coupon_coupon',                // Menu slug
            array($this, 'create_coupon_option_page')  // Callback function
        );
    }
    
    function new_group_page_assets(){
        // Enqueue the jQuery UI style for the datepicker
        wp_enqueue_style('jquery-ui', '//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');

        // Enqueue WordPress built-in script for datepicker
        wp_enqueue_script('jquery-ui-datepicker');

        // Enqueue Select2 for better select boxes
        wp_enqueue_script('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', array('jquery'), '4.0.13', true);
        wp_enqueue_style('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css');
        
        wp_enqueue_script('create-coupon-group', plugins_url( 'assets/js/create-coupon-group.js', __FILE__ ) );
    }
  
    // Render admin page
    function overview_page() { 
        ?>
            <div class="wrap">
                <h1>Coupon Group</h1>
                <h4>Welcome to the Coupon Group plugin management page. Use the tools below to manage groups, discounts, and privileges.</h4>
                <?php
                    'display_coupon_groups'();
                ?>
            </div>
        <?php
    }
 
    function new_group_page() {
        // Fetch WooCommerce coupons
        $args = array(
            'post_type' => 'shop_coupon',
            'posts_per_page' => -1
        );
        $coupons = get_posts($args);
    
        // Fetch Users (can limit the number if there are a lot of users)
        $users = get_users();
        
        // Retrieve stored form data from transient
        $form_data = get_transient('new_group_form_data');
        delete_transient('new_group_form_data');
        
        // Display error message for form validation
        if ($error = get_transient('new_group_form_error_msg')) {
            delete_transient('new_group_form_error-msg');
            echo "<div class='error is-dismissible'><p>{$error}</p></div>";            
        }        
    
        ?>
        <div class="admin-cg-wrap">
            <div class="admin-cg-main">
                <h1>Create Coupon Group</h1>
                <?php if (isset($_POST["create_coupon_group_submitted"]) && $_POST["create_coupon_group_submitted"] == 'true') create_coupon_group_handler()  ?>                
                <form method="POST">                       
                    <!-- Group Name -->
                    <div class="admin-cg-form-field">
                        <label for="group_name">Group Name</label>
                        <input type="text" name="group_name" id="group_name" value="<?php echo esc_attr($form_data['group_name'] ?? ''); ?>">
                    </div>
                    
                    <!-- WooCommerce Coupons -->
                    <div class="admin-cg-form-field">
                        <label for="wc_coupons">WooCommerce Coupons</label>
                        <select name="wc_coupons[]" id="wc_coupons" multiple>
                            <?php foreach($coupons as $index =>$coupon): ?>
                                <option value="<?php echo $coupon->ID; ?>" <?php echo isset($form_data['wc_coupons']) && in_array($coupon->ID, $form_data['wc_coupons']) ? 'selected' : ''; ?>>
                                    <?php echo esc_attr($form_data['wc_coupons'][$index]->post_title ?? $coupon->post_title); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <a href="<?php echo admin_url('edit.php?post_type=shop_coupon'); ?>" target="_blank">Go to WooCommerce Coupons</a>
                    </div>                        
                    <!-- Expiry Date -->
                    <div class="admin-cg-form-field ">
                        <label for="expiry_date">Expiry Date</label>
                        <input type="text" name="expiry_date" id="expiry_date" class="date-picker" autocomplete="off">
                    </div>
                    
                    <!-- Customers -->
                    <div class="admin-cg-form-field ">
                        <label for="customers">Customers</label>
                        <select name="customers[]" id="customers" multiple>
                            <?php foreach($users as $user): ?>
                                <option value="<?php echo $user->ID; ?>" <?php echo isset($form_data['customers']) && in_array($user->ID, $form_data['customers']) ? 'selected' : ''; ?>>
                                    <?php echo $user->user_email; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <input type="hidden" name="create_coupon_group_submitted" value="true">
                    <?php wp_nonce_field('coupon_group_nonce_action', 'coupon_group_nonce'); ?>                     
                    <?php 
                        submit_button();
                    ?>
                </form>  
            </div>
        </div>
        <?php
    }
}

$coupon_group_plugin = new CouponGroupPlugin();
