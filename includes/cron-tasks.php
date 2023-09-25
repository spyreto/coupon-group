<?php

// Ensure this file is being included by WordPress (and not accessed directly)
if (!defined('ABSPATH')) {
  exit; // Exit if accessed directly
}


/**
 * Schedules a daily task to check coupon groups for expiry.
 * 
 * This function is intended to run upon plugin activation.
 */
function start_my_daily_task()
{
  if (!wp_next_scheduled('daily_coupon_group_check')) {
    wp_schedule_event(time(), 'daily', 'daily_coupon_group_check');
  }
}

/**
 * Removes the scheduled task that checks for coupon group expiry.
 * 
 * This function is intended to run upon plugin deactivation.
 */
function stop_my_daily_task()
{
  wp_clear_scheduled_hook('daily_coupon_group_check');
}

/**
 * A cron job function that checks if coupon groups have expired.
 *
 * If a coupon group is determined to have expired, associated WooCommerce coupons
 * are set to 'draft' status, effectively disabling them.
 */
function check_coupon_groups_expiry()
{
  // Query coupon_groups that are expired and are active
  $args = array(
    'post_type'  => 'coupon_group',
    'posts_per_page' => -1, // Retrieve all posts. Modify as needed.
    'meta_query' => array(
      'relation' => 'AND',
      array(
        'key' => '_expiry_date',
        'value' => date('d-m-Y'),  // Get today's date
        'compare' => '<',
        'type'    => 'CHAR',
      ),
      array(
        'key'     => '_is_active',
        'value'   => '1',
        'compare' => 'LIKE'
      )
    )
  );

  // Add filter to modify WHERE clause for the date comparison
  add_filter('posts_where', 'filter_posts_where_date_comparison');

  // Perform the query
  $query = new WP_Query($args);

  // Remove filter after performing the query
  remove_filter('posts_where', 'filter_posts_where_date_comparison');

  if ($query->have_posts()) {
    while ($query->have_posts()) {
      update_post_meta(get_the_ID(), '_is_active', null);
    }
  }
}
add_action('daily_coupon_group_check', 'check_coupon_groups_expiry');
