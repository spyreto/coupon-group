<?php

/*
Plugin Name: Coupon Group
Description: This plugin enhances WooCommerce by introducing grouped coupon functionalities. Store administrators can effortlessly create, manage, and assign coupon groups with varying properties, such as discount rates or same-day delivery perks. Designed for tailored promotions, it allows for precise targeting by associating specific customers with unique coupon groups, ensuring personalized shopping experiences for every user.
Version: 1.0
Author: Spiros Dimou
Auhtor URI:
*/

require_once plugin_dir_path( __FILE__ ) . 'includes/form-handler.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/helper-functions.php';


// Check for WooCommerce
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    die("WooCommerce not activated!");
}

class CouponGroupPlugin
{
    function __construct()
    {
        add_action('admin_menu', array($this, 'main_menu'));
        add_action('admin_menu', array($this, 'add_submenus'));
        add_action('admin_init', array($this, 'register_coupon_group_cpt'));  
        add_action('admin_enqueue_scripts', array($this, 'add_scripts'));

    }

    function add_scripts() {
        //Custom style
        wp_enqueue_style('coupon-group', plugins_url( 'assets/css/coupon-group.css', __FILE__ ) );
        wp_enqueue_script('create-coupon-group', plugins_url( 'assets/js/create-coupon-group.js', __FILE__ ) );
        // Enqueue the jQuery UI style for the datepicker
        wp_enqueue_style('jquery-ui', '//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');

        // Enqueue WordPress built-in script for datepicker
        wp_enqueue_script('jquery-ui-datepicker');

        // Enqueue Select2 for better select boxes
        wp_enqueue_script('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', array('jquery'), '4.0.13', true);
        wp_enqueue_style('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css');
    }

    function register_coupon_group_cpt()
    {
        $args = array(
            'public' => true,
            'label'  => 'Coupon Groups',
            'menu_icon' => 'dashicons-tickets',
        );
        register_post_type('coupon_group', $args);
    }

    function main_menu()
    {
        $icon_url = plugins_url('/assets/images/baseline_local_offer_white_18dp.png', __FILE__);

        add_menu_page(
            'Overview',         // Page title
            'Coupon Group',     // Menu title
            'manage_options',   // Capability
            'coupon-group',     // Menu slug
            array($this, 'overview_page'), // Callback function
            $icon_url,
            55.5                          // Position in menu
        );
    }

    // Render admin page
    function overview_page()
    { 
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

    
    function add_submenus()
    {
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
        add_submenu_page(
            'coupon-group',                    // The slug name for the parent menu (to which you are adding this submenu).
            'New Group',          // The text to be displayed in the title tags of the page when the menu is selected.
            'New Group',               // The text to be used for the menu.
            'manage_options',                // The capability required for this menu to be displayed to the user.
            'new_group',        // The slug name to refer to this menu by.
            array($this, 'new_group_page')  // The function to be called to output the content for this page.
        );

        // New coupon page
        add_submenu_page(
            'coupon-group',                    // The slug name for the parent menu (to which you are adding this submenu).
            'New Coupon',          // The text to be displayed in the title tags of the page when the menu is selected.
            'New Coupon',               // The text to be used for the menu.
            'manage_options',                // The capability required for this menu to be displayed to the user.
            'new_coupon',        // The slug name to refer to this menu by.
            array($this, 'new_coupon_page')  // The function to be called to output the content for this page.
        );
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
        if ($error = get_transient('expiry_date_error')) {
            delete_transient('expiry_date_error');
            echo "<div class='notice notice-error is-dismissible'><p>{$error}</p></div>";            
        }
        
    
        ?>
        <div class="admin-cg-wrap">
            <div class="admin-cg-main">
                <h1>Create Coupon Group</h1>                
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
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
                                <option value="<?php echo $coupon->ID; ?>" <?php echo in_array($coupon->ID, $form_data['wc_coupons']) ? 'selected' : ''; ?>>
                                <?php echo esc_attr($form_data['wc_coupons'][$index]->post_title ?? $coupon->post_title); ?>
                            </option>
                                <?php endforeach; ?>
                        </select>
                        <a href="<?php echo admin_url('edit.php?post_type=shop_coupon'); ?>" target="_blank">Go to WooCommerce Coupons</a>
                    </div>

                    <!-- Custom Coupons -->
                    <div class="admin-cg-form-field">
                        <label for="custom_coupons">Custom Coupons</label>
                        <select name="custom_coupons[]" id="custom_coupons" multiple>
                            <?php foreach($coupons as $coupon): ?>
                                <option value="<?php echo $coupon->ID; ?>" <?php echo in_array($coupon->ID, $form_data['custom_coupons']) ? 'selected' : ''; ?>>
                                    <?php echo $coupon->post_title; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <a href="<?php echo admin_url('edit.php?post_type=shop_coupon'); ?>" target="_blank">Create a Custom Coupons</a>
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
                                <option value="<?php echo $user->ID; ?>" <?php echo in_array($user->ID, $form_data['customers']) ? 'selected' : ''; ?>>
                                    <?php echo $user->user_email; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <input type="hidden" name="action" value="create_coupon_group_handler">
                    <?php wp_nonce_field('coupon_group_nonce_action', 'coupon_group_nonce'); ?>    
                    <div class="submit">
                        <input type="submit" value="Create Group" class="button button-primary">
                    </div>
                </form>  
            </div>
        </div>
        <?php
    } 
}

$coupon_group_plugin = new CouponGroupPlugin();
