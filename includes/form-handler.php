
<?php
// Ensure this file is being included by WordPress (and not accessed directly)
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

require_once plugin_dir_path( __FILE__ ) . 'helper-functions.php';

/**
 * Handles form submission for creating new coupon custom_coupons.
 */
function create_coupon_group_handler() {
    // Check if form has been submitted
    if(!isset($_POST['coupon_group_nonce']) && !wp_verify_nonce($_POST['coupon_group_nonce'], 'coupon_group_nonce_action')) {
        wp_die('Nonce verification failed.');
    }
    try {

        $group_id= (isset($_POST['group_id']) ? sanitize_text_field($_POST['group_id']) : null);
        // Save form data for prepopulated fields
        $form_data = array(
            'group_name' => (isset($_POST['group_name']) ? sanitize_text_field($_POST['group_name']) : null),
            'wc_coupons' => ( isset( $_POST['wc_coupons'] ) 
                && is_array( $_POST['wc_coupons'] ) ) 
                ? array_map( 'sanitize_text_field', $_POST['wc_coupons'] ) : array(),
            'expiry_date' => (isset($_POST['expiry_date']) ? sanitize_text_field($_POST['expiry_date']) : null),
            'customers' => ( isset( $_POST['customers'] ) 
                && is_array( $_POST['customers'] ) ) 
                ? array_map( 'sanitize_text_field', $_POST['customers'] ) : array(),
            'is_active' => (isset($_POST['is_active']) ? sanitize_text_field($_POST['is_active']) : null),  
        );         

        // Validating the form data       
        $expiry_date = is_valid_expiry_date($form_data['expiry_date'], $form_data['is_active']);      

        $post_id = null;

        if (!empty($group_id)) {
            $form_data['group_id'] = $group_id;
            // Update the coupon group
            $post_id = wp_update_post(array(
                'ID'           => $group_id, // Your post ID you want to update
                'post_title'   => $form_data['group_name'],
                'post_content' => 'Updated content of the post.',
            ));
            if (is_wp_error($post_id)) {
                $errors = $post_id->get_error_messages();
                $error_string = implode("\n", $errors);
                throw new Exception($error_string);
            }
        } else {
            // Create new post in the coupon_custom_coupon custom post type
            $post_id = wp_insert_post( array(
                'post_title'    => $form_data['group_name'],
                'post_type'     => 'coupon_group',
                'post_status'   => 'publish',
            ) );            
        }

        if ( $post_id ) {
            // Save additional data as post meta
            update_post_meta( $post_id, '_wc_coupons', $form_data['wc_coupons'], );
            update_post_meta( $post_id, '_expiry_date', $expiry_date, );
            update_post_meta( $post_id, '_customers', $form_data['customers'], );
            update_post_meta( $post_id, '_is_active', $form_data['is_active'], );

            // Redirect back to form page
            if($group_id){
                wp_redirect(admin_url('admin.php?page=coupon-group&group_updated=true&group_name=' . $form_data['group_name']));
            } else {
                wp_redirect(admin_url('admin.php?page=coupon-group&group_created=true&group_name=' . $form_data['group_name']));
            }
        } else {
            // Handle error, e.g., display an error message to the admin.
            throw new Exception("An unexpected error occurred. Please try again later.");
        }   
    } catch (Exception $e) {
        set_transient('group_form_error_msg', $e->getMessage(), 45);
        set_transient('group_form_data', $form_data, 60);   

        // Redirect back to form page
        if($group_id){
            wp_redirect(admin_url('admin.php?page=edit-coupon-group&group_id=' . $group_id));
        } else {
            wp_redirect(admin_url('admin.php?page=new_group'));
        }
    } 
}
add_action('admin_post_create_coupon_group_handler', 'create_coupon_group_handler');

/**
 * Handles form submission for adding custom coupon. 
 * 
 */
function new_coupon_option_handler() {
    // Check if form has been submitted
    if(!wp_verify_nonce($_POST['new_coupon_option_nonce'], 'new_coupon_option_action')) {
        wp_die('Nonce verification failed.');
    }

    try {
        // Save form data for prepopulated fields
        $form_data = array(
            'title' => sanitize_text_field($_POST['custom_coupon_title']),
            'description' => sanitize_textarea_field($_POST['custom_coupon_description'])
        );
        // Check for empty "Title" field
        is_not_empty($form_data['title'], "The \"Coupon Title\" field is required. Please enter a value.");
     
        $custom_coupons = get_option('custom_coupon_options', array());
        $custom_coupons[] = array('title' => $form_data['title'], 'description' => $form_data['description']);

        
        update_option('custom_coupon_options', $custom_coupons);
        wp_redirect(admin_url('admin.php?page=coupon-group&option_update=true&option_name='));
        exit;
    } catch (Exception $e) {
        set_transient('new_coupon_option_form_error_msg', $e->getMessage(), 45);
        set_transient('new_coupon_option_form_data', $form_data, 60);  

         // Redirect back to form page
         wp_redirect(admin_url('admin.php?page=new_coupon_option'));
    }
}
add_action('admin_post_new_coupon_option_form_action', 'new_coupon_option_handler');

