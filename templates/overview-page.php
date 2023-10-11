<?php

/**
 * Render the coupon groups overview page
 * 
 */
function overview_page()
{
?>
    <div class="admin-cg-wrap">
        <div id="overlay"></div>
        <?php
        if (isset($_GET['group_deleted']) == 'true') {
        ?>
            <div class="updated notice is-dismissible">
                <p><?php echo esc_html__('The Coupon Group ', 'coupon-group'); ?> <strong><?php echo $_GET['group_name'] ?></strong> <?php echo esc_html__(' has been deleted.', 'coupon-group'); ?> </p>
            </div>
        <?php
        } elseif (isset($_GET['group_updated']) == 'true') {
        ?>
            <div class="updated notice-success is-dismissible">
                <p><?php echo esc_html__('The Coupon Group ', 'coupon-group'); ?> <strong><?php echo $_GET['group_name'] ?></strong> <?php echo esc_html__(' has been updated successfully.', 'coupon-group'); ?></p>
            </div>
        <?php
        } elseif (isset($_GET['group_created']) == 'true') {
        ?>
            <div class="updated notice is-dismissible">
                <p><?php echo esc_html__('The Coupon Group ', 'coupon-group'); ?> <strong><?php echo $_GET['group_name'] ?></strong><?php echo esc_html__(' has been created successfully.', 'coupon-group'); ?></p>
            </div>
        <?php
        } elseif (isset($_GET['option_deleted']) == 'true') {
        ?>
            <div class="updated notice is-dismissible">
                <p><?php echo esc_html__('The Coupon Group Option ', 'coupon-group'); ?> <strong><?php echo $_GET['option_title'] ?></strong> <?php echo esc_html__(' has been deleted.', 'coupon-group'); ?></p>
            </div>
        <?php
        } elseif (isset($_GET['option_updated']) == 'true') {
        ?>
            <div class="updated notice-success is-dismissible">
                <p><?php echo esc_html__('The Coupon Group Option ', 'coupon-group'); ?> <strong><?php echo $_GET['option_title'] ?></strong> <?php echo esc_html__(' has been updated successfully.', 'coupon-group'); ?></p>
            </div>
        <?php
        } elseif (isset($_GET['option_created']) == 'true') {
        ?>
            <div class="updated notice is-dismissible">
                <p><?php echo esc_html__('The Coupon Group Option ', 'coupon-group'); ?> <strong><?php echo $_GET['option_title'] ?></strong><?php echo esc_html__(' has been created successfully.', 'coupon-group'); ?></p>
            </div>
        <?php
        }
        ?>
        <h1><?php echo esc_html__('Coupon Group', 'coupon-group'); ?></h1>
        <h2><?php echo esc_html__('Welcome to the Coupon Group plugin management page. Use the tools below to view and manage coupon groups and group otpions.', 'coupon-group'); ?></h2>
        <?php
        display_delete_confirmation_box();
        display_coupon_groups();
        display_coupon_options();
        ?>
    </div>
<?php
    display_footer();
}


/**
 * Displays coupon groups.
 * 
 */
function display_coupon_groups()
{
    // Fetch the coupon groups
    $args = array(
        'post_type' => 'coupon_group',
        'posts_per_page' => -1  // Fetch all groups; modify as per your requirement
    );

    $query = new WP_Query($args);

?>
    <h3><?php echo esc_html__('Coupon Groups', 'coupon-group'); ?></h3>
    <table class="wp-list-table widefat fixed striped">

        <thead>
            <tr>
                <th><?php echo esc_html__('ID', 'coupon-group'); ?></th>
                <th><?php echo esc_html__('Name', 'coupon-group'); ?></th>
                <th><?php echo esc_html__('Expiry', 'coupon-group'); ?></th>
                <th><?php echo esc_html__('Is Active', 'coupon-group'); ?></th>
                <th><?php echo esc_html__('Users | Usage', 'coupon-group'); ?></th>
                <th><?php echo esc_html__('Action', 'coupon-group'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($query->have_posts()) {
                while ($query->have_posts()) : $query->the_post();

                    // Using get_the_ID()
                    $group_id = get_the_ID();
                    $expiry = get_post_meta($group_id, '_expiry_date', true);
                    $is_active = get_post_meta($group_id, '_is_active', true) == "1" ? "Yes" : "No";
                    $total_users = get_post_meta($group_id, '_customers', true);
                    $usage = get_post_meta($group_id, '_usage_count', true);

                    // For group deletion
                    $delete_nonce = wp_create_nonce('delete_coupon_group_' . $group_id);
                    $delete_link = admin_url('admin.php?page=coupon-group&action=delete&group_id=' .  $group_id . '&_wpnonce=' . $delete_nonce);

            ?>
                    <tr>
                        <td><?php the_ID(); ?></td>
                        <td><?php the_title(); ?></td>
                        <td><?php echo esc_html($expiry); ?></td>
                        <td><?php echo esc_html($is_active); ?></td>
                        <td><?php echo is_array($total_users) ? count($total_users) : "0"; ?> | <?php echo $usage ? $usage : "0"; ?></td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=edit-coupon-group&group_id=' . $group_id) ?>"><?php echo esc_html__('Edit', 'coupon-group'); ?></a>
                            <span>|</span>
                            <a class="admin-cg-delete-link" data-name="<?php echo the_title(); ?>" data-type="Coupon Group" href="<?php echo $delete_link ?>"><?php echo esc_html__('Delete', 'coupon-group'); ?></a>
                        </td>
                    </tr>
                <?php
                endwhile;
                // Reset the global $post object
                wp_reset_postdata();
            } else {
                ?>
                <tr>
                    <td class="admin-cg-empty-table-cell" colspan='6'><?php echo esc_html__('No Coupon Groups found.', 'coupon-group'); ?></td>
                </tr>
            <?php
            }
            ?>
        </tbody>
    </table>
<?php
}

/**
 * Displays custom coupon options.
 * 
 */
function display_coupon_options()
{
    $custom_coupon_options = get_option('custom_coupon_options', array());

?>
    <h3><?php echo esc_html__('Coupon Group Options', 'coupon-group'); ?></h3>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php echo esc_html__('Name', 'coupon-group'); ?></th>
                <th><?php echo esc_html__('Description', 'coupon-group'); ?></th>
                <th><?php echo esc_html__('Action', 'coupon-group'); ?></th>
            </tr>
        </thead>
        <tbody>

            <?php
            if (!empty($custom_coupon_options)) {
                foreach ($custom_coupon_options as $option) {
                    // For group deletion
                    $delete_nonce = wp_create_nonce('delete_coupon_group_option_' . $option['id']);
                    $delete_link = admin_url('admin.php?page=coupon-group&action=delete-option&option_id=' .  $option['id'] . '&_wpnonce=' . $delete_nonce);

            ?>
                    <tr>
                        <td><?php echo esc_html($option['title']) ?></td>
                        <td><?php echo esc_html($option['description']) ?></td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=edit-coupon-option&option_id=' . $option['id']) ?>"><?php echo esc_html__('Edit', 'coupon-group'); ?></a>
                            <span>|</span>
                            <a class="admin-cg-delete-link" data-name="<?php echo $option['title']; ?>" data-type="Coupon Group Option" href="<?php echo $delete_link ?>" data-name="<?php echo the_title(); ?>"><?php echo esc_html__('Delete', 'coupon-group'); ?></a>
                        </td>
                        <td></td>
                    </tr>
                <?php
                }
            } else {
                ?>
                <tr>
                    <td class="admin-cg-empty-table-cell" colspan='3'><?php echo esc_html__('No custom coupon options found.', 'coupon-group'); ?></td>
                </tr>
            <?php
            }
            ?>

        </tbody>
    </table>

<?php
}
