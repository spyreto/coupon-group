<?php

/*
Plugin Name: Coupon Group
Description: Adds the ability to create coupon groups with unique properties.
Version: 1.0
Author: Spiros Dimou
Auhtor URI:
*/

function coupon_group_post_type() {
    $args = array(
        'public' => true,
        'label'  => 'Coupon Group',
        'supports' => array('title', 'editor'),
        'menu_icon' => plugins_url('assets/images/baseline_local_offer_white_18dp.png', __FILE__),
    );
    register_post_type('coupon_group', $args);
}
add_action('init', 'coupon_group_post_type');

