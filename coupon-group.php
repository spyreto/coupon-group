<?php

/*
Plugin Name: Coupon Group
Description: This plugin enhances WooCommerce by introducing grouped coupon functionalities. Store administrators can effortlessly create, manage, and assign coupon groups with varying properties, such as discount rates or same-day delivery perks. Designed for tailored promotions, it allows for precise targeting by associating specific customers with unique coupon groups, ensuring personalized shopping experiences for every user.
Version: 1.0
Author: Spiros Dimou
Auhtor URI:
*/

// Check for WooCommerce
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
  die( "WooCommerce not activated!" );
}

class CouponGroupPlugin {
    function __construct() {
        add_action('admin_menu', array($this, 'main_menu'));
        add_action('admin_menu', array($this,'add_submenus'));
        add_action('admin_init', array($this, 'register_coupon_group_cpt'));
    }

    function register_coupon_group_cpt() {
        $args = array(
            'public' => true,
            'label'  => 'Coupon Groups',
            'menu_icon' => 'dashicons-tickets',
        );
        register_post_type('coupon_group', $args);
    }

    // function settings() {
    //     $args = array(
    //         'posts_per_page'   => -1,  // Get all coupons
    //         'orderby'          => 'title',
    //         'order'            => 'asc',
    //         'post_type'        => 'shop_coupon',
    //         'post_status'      => 'publish',
    //     );
    //     $coupons = get_posts( $args );
        
    //     foreach ( $coupons as $coupon ) {
    //         $discount_rate = $coupon->__get('coupon_amount');
    //         error_log($discount_rate);
    //     }
    // }

    function main_menu(){
        $icon_url = plugins_url( '/assets/images/baseline_local_offer_white_18dp.png', __FILE__ );
    
        add_menu_page(
            'Coupon Group',           // Page title
            'Coupon Group',           // Menu title
            'manage_options',         // Capability
            'coupon-group',           // Menu slug
            array($this, 'admin_page'), // Callback function
            $icon_url,
            55.5                          // Position in menu
        );
    }

    function display_coupon_groups_on_plugin_page() {
        // Fetch the coupon groups
        $args = array(
            'post_type' => 'coupon_group',
            'posts_per_page' => -1  // Fetch all groups; modify as per your requirement
        );
        
        $query = new WP_Query($args);
        
        // Check if we have groups
        if ($query->have_posts()) : ?>
    
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($query->have_posts()) : $query->the_post(); ?>
                        <tr>
                            <td><?php the_title(); ?></td>
                            <td><a href="<?php echo get_edit_post_link(); ?>">Edit</a></td>  <!-- Link to edit the group -->
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
    
            <?php
            // Reset the global $post object
            wp_reset_postdata();
        else : 
            ?><p>No coupon groups found.</p><?php
        endif;
    }
    
    
    // Render admin page
    function admin_page() { ?>
        <div class="wrap">
            <h1>Coupon Group</h1>
            <h4>Welcome to the Coupon Group plugin management page. Use the tools below to manage groups, discounts, and privileges.</h4>
            <?php
                    array($this, 'display_coupon_groups_on_plugin_page')();
            ?> 
       </div>
       <?php
      }

      
      function add_submenus() {
        // Add first submenu
        add_submenu_page(
            'coupon-group',                    // The slug name for the parent menu (to which you are adding this submenu).
            'New Group',          // The text to be displayed in the title tags of the page when the menu is selected.
            'New Group',               // The text to be used for the menu.
            'manage_options',                // The capability required for this menu to be displayed to the user.
            'new_group',        // The slug name to refer to this menu by.
            array($this, 'new_group_page')  // The function to be called to output the content for this page.
        );

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
        echo '<div class="wrap">';
        echo '<h1>My Plugin Submenu Page 1</h1>';
        echo '<p>This is content for the first submenu.</p>';
        echo '</div>';
    }
    
    function new_coupon_page() {
        echo '<div class="wrap">';
        echo '<h1>My Plugin Submenu Page 2</h1>';
        echo '<p>This is content for the second submenu.</p>';
        echo '</div>';
    }
}

$coupon_group_plugin = new CouponGroupPlugin();