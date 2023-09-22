<?php
// Helper functions

// Ensure this file is being included by WordPress (and not accessed directly)
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

function get_readable_discount_type($type) {
    $types = array(
        'fixed_cart'      => __('Fixed cart discount', 'woocommerce'),
        'percent'         => __('Percentage discount', 'woocommerce'),
        'fixed_product'   => __('Fixed product discount', 'woocommerce'),
        'percent_product' => __('Percentage product discount', 'woocommerce'),
    );

    return isset($types[$type]) ? $types[$type] : __('Unknown discount type', 'woocommerce');
}

/**
 * Displays coupon groups.
 * 
 */
function display_coupon_groups() {
    // Fetch the coupon groups
    $args = array(
        'post_type' => 'coupon_group',
        'posts_per_page' => -1  // Fetch all groups; modify as per your requirement
    );

    $query = new WP_Query($args);

    // Check if we have groups
    if ($query->have_posts()) : ?>
        <h3>Coupon Groups</h3>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Expire</th>
                    <th>Is Active</th>
                    <th>Total Users</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($query->have_posts()) : $query->the_post(); ?>
                    <?php 
                    // Using get_the_ID()
                    $group_id = get_the_ID();
                    $expiry = get_post_meta( $group_id, '_expiry_date', true);
                    $is_active = get_post_meta( $group_id, '_is_active', true) == "1"? "Yes" : "No";
                    $total_users = get_post_meta( $group_id, '_customers', true);

                    // For group deletion
                    $delete_nonce = wp_create_nonce('delete_coupon_group_' . $group_id);
                    $delete_link = admin_url('admin.php?page=coupon-group&action=delete&group_id=' .  $group_id . '&_wpnonce=' . $delete_nonce);

                    ?>
                    <tr>
                        <td><?php the_ID(); ?></td>
                        <td><?php the_title();?></td>
                        <td><?php echo esc_html($expiry); ?></td>
                        <td><?php echo esc_html($is_active); ?></td>
                        <td><?php echo is_array($total_users)? count($total_users) : "0"; ?></td>
                        <td>                                                 
                            <a href="<?php echo admin_url('admin.php?page=edit-coupon-group&group_id=' . $group_id) ?>">Edit</a>
                            <span>|</span>
                            <a href="<?php echo $delete_link ?>" class="delete-coupon-group" data-group-id="<?php echo $group_id ?>">Delete</a>
                        </td> 
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

    <?php
        // Reset the global $post object
        wp_reset_postdata();
    else :
    ?>
        <p>No coupon groups found.</p>
    <?php
    endif;
}

/**
 * Displays custom coupon options.
 * 
 */
function display_coupon_options() {
    $custom_coupon_options = get_option('custom_coupon_options', array());

    ?>    
     <h3>Custom Coupon Option</h3>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                
                <?php 
                    if (!empty( $custom_coupon_options)) {
                        foreach ($custom_coupon_options as $option) {
                            // For group deletion
                        $delete_nonce = wp_create_nonce('delete_coupon_group_' . '$group_id');
                        $delete_link = admin_url('admin.php?page=coupon-group&action=delete&group_id=' .  '$group_id' . '&_wpnonce=' . $delete_nonce);
                        
                        ?>
                        <tr>
                            <td><?php echo esc_html($option['title'])?></td>
                            <td><?php echo esc_html($option['description'])?></td>
                            <td>                                                 
                                <a href="<?php echo admin_url('admin.php?page=edit-coupon-group&group_id=' . '$group_id') ?>">Edit</a>
                                <span>|</span>
                                <a href="<?php echo $delete_link ?>" class="delete-coupon-group" data-group-id="<?php echo '$group_id' ?>">Delete</a>
                            </td> 
                            <td></td>
                        </tr>
                        <?php                        
                        }
                    } else {
                        ?>
                        <tr>
                            <p>No custom coupon options found.</p>
                        </tr>
                        <?php
                    }
                ?>                    
                
            </tbody>
        </table>

    <?php
}

/**
 * Ηandles the deletion of a coupon group.
 * 
 */
function coupon_group_deletion_handler() {
    if (isset($_GET['action']) && $_GET['action'] === 'delete') {
        // Check if nonce is set and valid
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'delete_coupon_group_' . $_GET['group_id'])) {
            die("Security check failed!");
        }
        
        $group_id = $_GET['group_id'];
        $group_name = get_the_title($group_id);

        // $deleted_group['name'] = get_the_title($group_id);
        // $deleted_group['wc_coupons'] = get_post_meta($group_id, '_wc_coupons', true);
        // $deleted_group['customers'] = get_post_meta($group_id, '_customers', true); 

        // $deleted_group = array(
        //     'name' => get_the_title($group_id),
        //     'wc_coupons' => get_post_meta($group_id, '_wc_coupons', true),
        //     'customers' => get_post_meta($group_id, '_customers', true),
        // );

        // Delete the coupon group
        wp_delete_post($_GET['group_id'], true);  // true means force delete (won't go to trash)

        // // Hook firing for Group deletion
        // do_action('coupon_group_group_deletion',  $group_id);

        // Redirect back to the coupon group list with a message maybe
        wp_redirect(admin_url('admin.php?page=coupon-group&group_deleted=true&group_name=' . $group_name));
        exit;
    }
}
add_action('admin_init', 'coupon_group_deletion_handler');


/**
 * Check if the provided date is valid and not a past date.
 *
 * @param string $date Date in 'yy-mm-dd' format.
 * @return string The is_active value (1 = true)
 */
function is_valid_expiry_date($date, $is_active) {
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
        if ($date_timestamp <= $today) {
            throw new Exception("The expiry date you entered is invalid or in the past. Please enter a valid future date.");
        }
        return $date;
    }else {
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
function is_not_empty($field_value, $err_message) {
    if (empty(trim($field_value))){
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
function get_wc_coupon_code_from_id($coupon_id) {
    $coupon_post = get_post($coupon_id);
    
    if ($coupon_post && $coupon_post->post_type === 'shop_coupon') {
        return strtolower($coupon_post->post_title);
    }
    
    return null;
}