<?php

/*
Plugin Name: Coupon Group
Description: This plugin enhances WooCommerce by introducing grouped coupon functionalities. Store administrators can effortlessly create, manage, and assign coupon groups with varying properties, such as discount rates or same-day delivery perks. Designed for tailored promotions, it allows for precise targeting by associating specific customers with unique coupon groups, ensuring personalized shopping experiences for every user.
Version: 1.0
Author: Spiros Dimou
Auhtor URI:
*/

// Add custom style
function coupon_group_styles() {  
  wp_enqueue_style( 'coupon-group', plugins_url( 'css/coupon-group.css', __FILE__ ) );
}
add_action( 'admin_enqueue_scripts', 'coupon_group_styles' );



// Add  menu item to the main menu in the WordPress admin dashboard 
function add_coupon_group_menu() {
  add_menu_page(
      'Coupon Group',            // Page title
      'Coupon Group',            // Menu title
      'manage_options',           // Capability required to access
      'coupon-group',            // Menu slug
      'render_coupon_group_page', // Callback function to render the page
      plugins_url( '/assets/images/baseline_local_offer_white_18dp.png', __FILE__ ),
      55.5 // Under WooCommerce 
  );
}

// Add code here to render your coupon groups admin page.
function render_coupon_group_page() {
  ?>
    <div class="admin-cg-wrapper">
      <main class="admin-cg-main">
        <h2>Coupon Group</h2>
      </main>
      <footer>Developed By Spyreto</footer>
    </div>
  <?php
}

// Hook the function to add the menu item to the admin menu.
add_action('admin_menu', 'add_coupon_group_menu');


function custom_coupon_groups_taxonomy() {
  $labels = array(
      'name' => 'Coupon Groups',
      'singular_name' => 'Coupon Group',
      'menu_name' => 'Coupon Groups',
  );

  $args = array(
      'hierarchical' => true, // Hierarchical like categories.
      'labels' => $labels,
      'show_ui' => true,
      'show_admin_column' => true,
      'query_var' => true,
      'rewrite' => array('slug' => 'coupon-group'),
  );

  register_taxonomy('coupon_group', 'shop_coupon', $args);
}
add_action('init', 'custom_coupon_groups_taxonomy');

function create_custom_user_roles() {
  add_role('coupon_manager', 'Coupon Manager', array(
      'read' => true,
      // Add capabilities as needed.
  ));
}

register_activation_hook(__FILE__, 'create_custom_user_roles');


