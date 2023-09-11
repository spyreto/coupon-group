<?php
// Ensure this file is being included by WordPress (and not accessed directly)
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Handle form submission for creating new coupon groups.
 */
function create_coupon_group_handler() {
    // Check if form has been submitted
    if(!wp_verify_nonce($_POST['coupon_group_nonce'], 'coupon_group_nonce_action')) {
        wp_die('Nonce verification failed.');
    }
    try {
        // Save form data for prepopulated fields
        $form_data = array(
            'group_name' => sanitize_text_field( $_POST['group_name']),
            'wc_coupons' => ( isset( $_POST['wc_coupons'] ) 
                && is_array( $_POST['wc_coupons'] ) ) 
                ? array_map( 'sanitize_text_field', $_POST['wc_coupons'] ) : array(),
            'custom_coupons' => ( isset( $_POST['custom_coupons'] ) 
                && is_array( $_POST['custom_coupons'] ) ) 
                ? array_map( 'sanitize_text_field', $_POST['custom_coupons'] ) : array(),
            'expiry_date' => 'sanitize_text_field'( $_POST['expiry_date'] ),
            'customers' => ( isset( $_POST['customers'] ) 
                && is_array( $_POST['customers'] ) ) 
                ? array_map( 'sanitize_text_field', $_POST['customers'] ) : array()
        );         

        // Validating the form data       
        $expiry_date   = is_valid_expiry_date( $_POST['expiry_date'] );

        // Create new post in the coupon_group custom post type
        $post_id = wp_insert_post( array(
            'post_title'    => $form_data['group_name'],
            'post_type'     => 'coupon_group',
            'post_status'   => 'publish',
        ) );

        if ( $post_id ) {
            // Save additional data as post meta
            update_post_meta( $post_id, '_wc_coupons', $form_data['wc_coupons'], );
            update_post_meta( $post_id, '_custom_coupons', $form_data['coustom_coupons'], );
            update_post_meta( $post_id, '_expiry_date', $expiry_date );
            update_post_meta( $post_id, '_customers', $form_data['customers'], );
            // Optionally redirect to the post editing screen for the newly created group or show a success message.
            wp_redirect( admin_url("admin.php?page=coupon-group" ) );  
        } else {
        // Handle error, e.g., display an error message to the admin.
        }   
    } catch (Exception $e) {
        set_transient('new_group_form_error_msg', $e->getMessage(), 45);
        set_transient('new_group_form_data', $form_data, 60);  

         // Redirect back to form page
         wp_redirect(admin_url('admin.php?page=new_group'));
    } 
}

/**
 * Check if the provided date is valid and not a past date.
 *
 * @param string $date Date in 'yy-mm-dd' format.
 * @return bool True if valid and not in the past, false otherwise.
 */
function is_valid_expiry_date($date) {
    if (empty($date)) {
        throw new Exception("Please enter a valid future expiry date.");
    }
    // Check if the format is correct
    if (!preg_match('/^\d{2}-\d{2}-\d{4}$/', $date)) {
        throw new Exception("The expiry date you entered is invalid. Please enter a valid future date.");
    }

    // Convert date string to timestamp
    $date_timestamp = strtotime($date);
    
    // Check if the date is a valid date (for example, not something like 00-00-00)
    if (!$date_timestamp) {
        throw new Exception("The expiry date you entered is invalid. Please enter a valid future date.");
    }

    // Get today's date
    $today = strtotime(date('y-m-d'));

    // Check if the provided date is in the past
    if ($date_timestamp <= $today) {
        throw new Exception("The expiry date you entered is invalid or in the past. Please enter a valid future date.");
    }

    return $date;
}


function has_users($date) {
    if (empty($date)) {
        throw new Exception("Please enter a valid future expiry date.");
    }
    // Check if the format is correct
    if (!preg_match('/^\d{2}-\d{2}-\d{4}$/', $date)) {
        throw new Exception("The expiry date you entered is invalid. Please enter a valid future date.");
    }

    // Convert date string to timestamp
    $date_timestamp = strtotime($date);
    
    // Check if the date is a valid date (for example, not something like 00-00-00)
    if (!$date_timestamp) {
        throw new Exception("The expiry date you entered is invalid. Please enter a valid future date.");
    }

    // Get today's date
    $today = strtotime(date('y-m-d'));

    // Check if the provided date is in the past
    if ($date_timestamp <= $today) {
        throw new Exception("The expiry date you entered is invalid or in the past. Please enter a valid future date.");
    }

    return $date;
}

add_action('admin_post_create_coupon_group_handler', 'create_coupon_group_handler');



