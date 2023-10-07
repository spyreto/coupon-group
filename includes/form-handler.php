<?php
// Ensure this file is being included by WordPress (and not accessed directly)
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handles form submission for creating new coupon custom_coupons.
 */
function create_or_edit_coupon_group_handler()
{
    // Check if form has been submitted
    if (!isset($_POST['coupon_group_nonce']) && !wp_verify_nonce($_POST['coupon_group_nonce'], 'coupon_group_nonce_action')) {
        wp_die('Nonce verification failed.');
    }
    try {

        $group_id = (isset($_POST['group_id']) ? sanitize_text_field($_POST['group_id']) : null);
        // Save form data for prepopulated fields
        $form_data = array(
            'group_name' => (isset($_POST['group_name']) ? sanitize_text_field($_POST['group_name']) : null),
            'wc_coupons' => (isset($_POST['wc_coupons'])
                && is_array($_POST['wc_coupons']))
                ? array_map('sanitize_text_field', $_POST['wc_coupons']) : array(),
            'expiry_date' => (isset($_POST['expiry_date']) ? sanitize_text_field($_POST['expiry_date']) : null),
            'customers' => (isset($_POST['customers'])
                && is_array($_POST['customers']))
                ? array_map('sanitize_text_field', $_POST['customers']) : array(),
            'is_active' => (isset($_POST['is_active']) ? sanitize_text_field($_POST['is_active']) : '0'),
            'unlimited_use' => (isset($_POST['unlimited_use']) ? sanitize_text_field($_POST['unlimited_use']) : '0'),
            'options' => array(),
        );

        // Check for empty Coupon Group name
        is_not_empty($form_data['group_name'], "The \"Coupon Group Name\" field is required. Please enter a value.");

        // Validating the form data       
        $expiry_date = is_valid_expiry_date($form_data['expiry_date'], $form_data['is_active']);

        $available_options = get_option('custom_coupon_options', array());

        foreach ($available_options as $option) {
            $form_data['options'][$option['id']] = (isset($_POST[$option['id']]) ?
                sanitize_text_field($_POST[$option['id']]) : null);
        }

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
            $post_id = wp_insert_post(array(
                'post_title'    => $form_data['group_name'],
                'post_type'     => 'coupon_group',
                'post_status'   => 'publish',
            ));
        }

        if ($post_id) {
            // Save additional data as post meta
            update_post_meta($post_id, '_is_active', $form_data['is_active'],);
            update_post_meta($post_id, '_unlimited_use', $form_data['unlimited_use'],);
            update_post_meta($post_id, '_expiry_date', $expiry_date,);
            update_post_meta($post_id, '_customers', $form_data['customers'],);
            update_post_meta($post_id, '_wc_coupons', $form_data['wc_coupons'],);
            update_post_meta($post_id, '_custom_coupon_options', $form_data['options'],);

            // Redirect back to overview page
            if ($group_id) {
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
        if ($group_id) {
            wp_redirect(admin_url('admin.php?page=edit-coupon-group&group_id=' . $group_id));
        } else {
            wp_redirect(admin_url('admin.php?page=new-group'));
        }
    }
}
add_action('admin_post_create_or_edit_coupon_group_handler', 'create_or_edit_coupon_group_handler');

/**
 * Handles form submission for adding custom coupon. 
 * 
 */
function create_or_edit_coupon_option_handler()
{
    // Check if form has been submitted
    if (!isset($_POST['coupon_option_nonce']) && !wp_verify_nonce($_POST['coupon_option_nonce'], 'coupon_option_nonce_action')) {
        wp_die('Nonce verification failed.');
    }

    try {
        $option_id = (isset($_POST['option_id']) ? sanitize_text_field($_POST['option_id']) : null);

        // Save form data for prepopulated fields
        $form_data = array(
            'title' => sanitize_text_field($_POST['custom_option_title']),
            'description' => sanitize_textarea_field($_POST['custom_option_description'])
        );
        // Check for empty "Title" field
        is_not_empty($form_data['title'], "The \"Coupon Title\" field is required. Please enter a value.");
        $custom_coupon_options = get_option('custom_coupon_options', array());

        if (!empty($option_id)) {
            foreach ($custom_coupon_options as $index => $option) {
                if ($option['id'] == $option_id) {
                    $custom_coupon_options[$index]['title'] = $form_data['title'];
                    $custom_coupon_options[$index]['description'] = $form_data['description'];
                    break;
                }
            }
            update_option('custom_coupon_options', $custom_coupon_options);
            // Redirect back to overview page
            wp_redirect(admin_url('admin.php?page=coupon-group&option_updated=true&option_title=' . $form_data['title']));
        } else {
            $custom_coupon_options[] = array(
                'id' => wp_generate_uuid4(),
                'title' => $form_data['title'],
                'description' => $form_data['description'],
            );
            update_option('custom_coupon_options', $custom_coupon_options);
            // Redirect back to overview page
            wp_redirect(admin_url('admin.php?page=coupon-group&option_created=true&option_title=' . $form_data['title']));
        }
        exit;
    } catch (Exception $e) {
        set_transient('new_coupon_option_form_error_msg', $e->getMessage(), 45);
        set_transient('new_coupon_option_form_data', $form_data, 60);

        // Redirect back to form page
        wp_redirect(admin_url('admin.php?page=new_coupon_option'));
    }
}
add_action('admin_post_create_or_edit_coupon_option_form_action', 'create_or_edit_coupon_option_handler');



/**
 * Ηandles the deletion of a coupon group.
 * 
 */
function coupon_group_deletion_handler()
{
    if (isset($_GET['action']) && $_GET['action'] === 'delete') {
        // Check if nonce is set and valid
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'delete_coupon_group_' . $_GET['group_id'])) {
            die("Security check failed!");
        }

        $group_id = $_GET['group_id'];
        $group_name = get_the_title($group_id);


        wp_delete_post($_GET['group_id'], true);  // true means force delete (won't go to trash)
        wp_redirect(admin_url('admin.php?page=coupon-group&group_deleted=true&group_name=' . $group_name));
        exit;
    }
}
add_action('admin_init', 'coupon_group_deletion_handler');


/**
 * Ηandles the deletion of a coupon group option.
 * 
 */
function coupon_group_option_deletion_handler()
{
    if (isset($_GET['action']) && $_GET['action'] === 'delete-option') {
        // Check if nonce is set and valid
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'delete_coupon_group_option_' . $_GET['option_id'])) {
            die("Security check failed!");
        }

        $option_id = $_GET['option_id'];

        $indexToRemove = null;
        $option_title = "";
        $custom_coupon_options = get_option('custom_coupon_options', array());

        if (!empty($option_id)) {
            foreach ($custom_coupon_options as $index => $option) {
                if ($option['id'] == $option_id) {
                    $indexToRemove = $index;
                    $option_title = $option['title'];
                    break;
                }
            }
            unset($custom_coupon_options[$indexToRemove]);
            update_option('custom_coupon_options', $custom_coupon_options);
            wp_redirect(admin_url('admin.php?page=coupon-group&option_deleted=true&option_title=' . $option_title));
        }

        exit;
    }
}
add_action('admin_init', 'coupon_group_option_deletion_handler');
