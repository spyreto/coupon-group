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

// Admin Menu Setup
add_action('admin_menu', 'CouponGroupAdminMenu');

function CouponGroupAdminMenu(){
    $icon_url = plugins_url( '/assets/images/baseline_local_offer_white_18dp.png', __FILE__ );

    add_menu_page(
        'Coupon Group',           // Page title
        'Coupon Group',           // Menu title
        'manage_options',         // Capability
        'coupon-group',           // Menu slug
        'coupon_group_admin_page', // Callback function
        $icon_url,
        55.5                          // Position in menu
    );
}

// Render admin page
function coupon_group_admin_page() {
    echo '<div class="wrap">';
    echo '<h1>Coupon Group Management</h1>';
    echo '<p>Welcome to the Coupon Group plugin management page. Use the tools below to manage groups, discounts, and privileges.</p>';
   echo '</div>';
  }