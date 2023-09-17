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
        // Delete the coupon group
        wp_delete_post($_GET['group_id'], true);  // true means force delete (won't go to trash)

        // Redirect back to the coupon group list with a message maybe
        wp_redirect(admin_url('admin.php?page=coupon-group&group_deleted=true&group_name=' . $group_name));
        exit;
    }
}
add_action('admin_init', 'coupon_group_deletion_handler');

/**
 * Convert date from 'yy-mm-dd' to 'dd-mm-yy'.
 *
 * @param string $date Date in 'yy-mm-dd' format.
 * @return string Date in 'dd-mm-yy' format or false if provided date is invalid.
 */
function convert_date_format($date) {
    // Convert date string to timestamp
    $date_timestamp = strtotime($date);

    // Check if it's a valid timestamp
    if (!$date_timestamp) {       
        return false;
    }

    // Convert and return in 'dd-mm-yy' format
    return date('d-m-y', $date_timestamp);
}


function auto_apply_coupon($cart) {
    if ( ! is_admin() && $cart->is_empty() === false ) {
        // Today's date in 'dd-mm-yyyy' format
        $today = strtotime(date('y-m-d'));

        $customer = $cart->get_customer();
        $customer_id = $customer->get_id();

        $args = array(
            'post_type'  => 'coupon_group',
            'posts_per_page' => -1, // retrieve all posts; adjust as needed
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key'     => '_customers',
                    'value' => '"' . $customer_id . '"',
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => '_is_active',
                    'value' => 1,
                    'compare' => '='
                ),
                array(
                    'key' => '_expiry_date',
                    'value' => $today,
                    'compare' => '>=', // This assumes the date is saved in an ascending format. Adjust if needed.
                    'type' => 'CHAR'
                )
            )
        );

        $query = new WP_Query($args);

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                error_log('fifis');
                $query->the_post();
                $group_id = get_the_ID();

                $wc_coupons = get_post_meta( $group_id, '_wc_coupons', true);

                // Add coupons discounts
                foreach($wc_coupons as $coupon_id){
                    $coupon_post = get_post($coupon_id);
                    $coupon_code = $coupon_post->post_title;
                    $coupon_data = new WC_Coupon( $coupon_code );

                    if ( ! $cart->has_discount( $coupon_code ) ) {
                        $cart->add_discount( $coupon_code );
                    }
                }                
            }
        } else {
            error_log("No Coupon");
        }
        wp_reset_postdata();
    }
}

add_action('woocommerce_before_calculate_totals', 'auto_apply_coupon');
