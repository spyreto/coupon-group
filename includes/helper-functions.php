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
    if(isset($_GET['message']) && $_GET['message'] == 'success') {
        echo '<div class="updated notice is-dismissible"><p>Form successfully submitted!</p></div>';
    }

    // Fetch the coupon groups
    $args = array(
        'post_type' => 'coupon_group',
        'posts_per_page' => -1  // Fetch all groups; modify as per your requirement
    );

    $query = new WP_Query($args);

    // Check if we have groups
    if ($query->have_posts()) : ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Expire</th>
                    <th>Is Active</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($query->have_posts()) : $query->the_post(); ?>
                    <?php 
                    // Using get_the_ID()
                    $expiry = get_post_meta(get_the_ID(), '_expiry_date', true);
                    $is_active = get_post_meta(get_the_ID(), '_is_active', true);
                    ?>
                    <tr>
                        <td><?php the_ID(); ?></td>
                        <td><?php the_title();?></td>
                        <td><?php echo esc_html($expiry); ?></td>
                        <td><?php echo esc_html($expiry); ?></td>
                        <td>                                                 
                            <a href="<?php echo admin_url('admin.php?page=edit-coupon-group&group_id=' . get_the_ID()) ?>">Edit</a>
                            <a href="<?php echo get_edit_post_link(); ?>">Delete</a>
                        </td> <!-- Link to edit the group -->
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
