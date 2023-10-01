<?php
// Helper functions

// Ensure this file is being included by WordPress (and not accessed directly)
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function get_readable_discount_type($type)
{
    $types = array(
        'fixed_cart'      => __('Fixed cart discount', 'woocommerce'),
        'percent'         => __('Percentage discount', 'woocommerce'),
        'fixed_product'   => __('Fixed product discount', 'woocommerce'),
        'percent_product' => __('Percentage product discount', 'woocommerce'),
    );

    return isset($types[$type]) ? $types[$type] : __('Unknown discount type', 'woocommerce');
}


/**
 * Check if the provided date is valid and not a past date.
 *
 * @param string $date Date in 'yy-mm-dd' format.
 * @return string The is_active value (1 = true)
 */
function is_valid_expiry_date($date, $is_active)
{
    if (empty($date)) {
        return null;
    } elseif (!preg_match('/^\d{2}-\d{2}-\d{4}$/', $date)) {
        // Check if the format is correct
        throw new Exception("The expiry date you entered is invalid. Please enter a valid future date.");
    } elseif ($is_active == "1") { // If the offer is active
        // Convert date string to timestamp
        $date_timestamp = strtotime($date);

        // Check if the date is a valid date (for example, not something like 00-00-00)
        if (!$date_timestamp) {
            throw new Exception("The expiry date you entered is invalid. Please enter a valid future date.");
        }

        // Get today's date
        $today = strtotime(date('y-m-d'));

        // Check if the provided date is in the past
        if ($date_timestamp < $today) {
            throw new Exception("The expiry date you entered is invalid or in the past. Please enter a valid future date.");
        }
        return $date;
    } else {
        return $date;
    }
}

/**
 * Check if the provided string value isn't empty.
 *
 * @param string $field_value Value in string format.
 * @param string $err_message Error message for the exception in string format.
 * @return bool True if valid , otherwise throws an error.
 */
function is_not_empty($field_value, $err_message)
{
    if (empty(trim($field_value))) {
        throw new Exception($err_message);
    }
    return true;
}

/**
 * Retrieve WooCommerce coupon code from coupon ID.
 *
 * @param int $coupon_id The ID of the WooCommerce coupon.
 * @return string|null The coupon code or null if the coupon doesn't exist.
 */
function get_wc_coupon_code_from_id($coupon_id)
{
    $coupon_post = get_post($coupon_id);

    if ($coupon_post && $coupon_post->post_type === 'shop_coupon') {
        return strtolower($coupon_post->post_title);
    }

    return null;
}

/**
 * Checks if a coupon is part of a group.
 *
 * @param int $coupon_id WooCommerce coupon ID.
 * @return bool True if part of a group, false otherwise.
 */
function is_coupon_part_of_group($coupon_id)
{
    // Here you should have the logic to determine if the coupon is part of a group.
    // For example, you can check if the coupon ID exists in any 'coupon_group' meta fields.
    $args = array(
        'post_type'  => 'coupon_group',
        'meta_query' => array(
            array(
                'key'     => '_wc_coupons',
                'value'   => $coupon_id,
                'compare' => 'LIKE'
            )
        )
    );

    $coupon_group_query = new WP_Query($args);
    if ($coupon_group_query->have_posts()) {
        return true;  // The coupon is part of a group
    }
    return false;  // The coupon is not part of a group
}

/**
 * Retrieve coupon groups where a specific coupon is included.
 *
 * @param int Coupon Code.
 * @return array List of WP_Post objects representing the coupon groups.
 */
function get_active_coupon_groups_for_coupon($coupon)
{
    // Check if the coupon is associated with any Coupon Group.
    $args = array(
        'post_type'  => 'coupon_group', // Your Coupon Group CPT
        'meta_query' => array(
            array(
                'key'     => '_wc_coupons', // Adjust with your actual meta key for coupons
                'value'   => $coupon,
                'compare' => 'LIKE',
            ),
        ),
    );
    return get_posts($args);
}


/**
 * Retrieve coupon groups where a specific user is included.
 *
 * @param int $user_id The ID of the user.
 * @return array List of WP_Post objects representing the coupon groups.
 */
function get_active_coupon_groups_for_user($user_id)
{
    $args = array(
        'post_type'      => 'coupon_group',
        'posts_per_page' => -1,
        'meta_query'     => array(
            'relation' => 'AND',
            array(
                'key'     => '_customers',
                'value'   => '"' . $user_id . '"',  // Searching for serialized array.
                'compare' => 'LIKE'
            ),
            array(
                'key'     => '_is_active',
                'value'   => '1',
                'compare' => 'LIKE'
            )
        )
    );

    return get_posts($args);
}


/**
 * Filters the provided coupon group to retain only active options.
 *
 * @param array $array The associative coupon group to be filtered.
 * @return array Returns a new associative array containing only the ids of active options.
 */
function get_active_group_options($coupon_group)
{
    // Use array_filter to filter the array based on value.
    $filtered_array = array_filter($coupon_group, function ($value) {
        // Keep the element in the array if its value is 1.
        return $value == 1;
    });

    // Return the filtered array.
    return array_keys($filtered_array);
}

/**
 * Function to modify the WHERE clause of the WP_Query object.
 * It adds additional conditions to filter the posts based on multiple fields.
 * @param string $where The existing WHERE clause of the WP_Query.
 * @return string The modified WHERE clause with the additional conditions.
 */
function filter_posts_where_date_comparison($where)
{
    global $wpdb;
    $where .= " AND STR_TO_DATE({$wpdb->postmeta}.meta_value, '%d-%m-%Y') < CURDATE()";
    return $where;
}

/**
 * Searches through custom coupon options to find the option where the 'id' key matches the given $id.
 * 
 * @param string $id The ID to search for.
 * @return array|null Returns the sub-array option with the matching ID or null if no matching ID is found.
 * 
 */
function find_coupon_option_by_id($id)
{
    $available_options = get_option('custom_coupon_options', array());
    foreach ($available_options as $option) {
        if (isset($option['id']) && $option['id'] == $id) {
            return $option;
        }
    }
    return null; // Return null if no element is found with the given id
}
