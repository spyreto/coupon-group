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
    wp_schedule_event(time(), 'hourly', 'daily_coupon_group_check');
  }
}
register_activation_hook(__FILE__, 'start_my_daily_task');

/**
 * Removes the scheduled task that checks for coupon group expiry.
 * 
 * This function is intended to run upon plugin deactivation.
 */
function stop_my_daily_task()
{
  wp_clear_scheduled_hook('daily_coupon_group_check');
}
register_deactivation_hook(__FILE__, 'stop_my_daily_task');
