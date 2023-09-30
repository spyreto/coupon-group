<?php

/**
 * Render the coupon groups overview page
 * 
 */
function overview_page()
{

?>
    <div class="wrap">
        <?php
        if (isset($_GET['group_deleted']) == 'true') {
        ?>
            <div class="updated notice is-dismissible">
                <p>The Coupon Group <strong><?php echo $_GET['group_name'] ?></strong> has been deleted.</p>
            </div>
        <?php
        } elseif (isset($_GET['group_updated']) == 'true') {
        ?>
            <div class="updated notice-success is-dismissible">
                <p>The Coupon Group <strong><?php echo $_GET['group_name'] ?></strong> has been updated successfully.</p>
            </div>
        <?php
        } elseif (isset($_GET['group_created']) == 'true') {
        ?>
            <div class="updated notice is-dismissible">
                <p>The Coupon Group <strong><?php echo $_GET['group_name'] ?></strong> has been created successfully.</p>
            </div>
        <?php
        } elseif (isset($_GET['option_deleted']) == 'true') {
        ?>
            <div class="updated notice is-dismissible">
                <p>The Coupon Group Option <strong><?php echo $_GET['option_title'] ?></strong> has been deleted.</p>
            </div>
        <?php
        } elseif (isset($_GET['option_updated']) == 'true') {
        ?>
            <div class="updated notice-success is-dismissible">
                <p>The Coupon Group Option <strong><?php echo $_GET['option_title'] ?></strong> has been updated successfully.</p>
            </div>
        <?php
        } elseif (isset($_GET['option_created']) == 'true') {
        ?>
            <div class="updated notice is-dismissible">
                <p>The Coupon Group Option <strong><?php echo $_GET['option_title'] ?></strong> has been created successfully.</p>
            </div>
        <?php
        }
        ?>
        <h1>Coupon Group</h1>
        <h4>Welcome to the Coupon Group plugin management page. Use the tools below to manage groups, discounts, and privileges.</h4>
        <?php
        'display_coupon_groups'();
        'display_coupon_options'();
        ?>
    </div>
    <?php
}

/**
 * Render the page for creating new coupon group
 * 
 */
function display_create_or_edit_group_page()
{
    $group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : 0;
    $old_coupon_group = new stdClass();

    // Get the available custom options.
    $available_options = get_option('custom_coupon_options', array());


    if ($group_id) {
        // Fetch existing data
        $old_coupon_group->name = get_the_title($group_id);
        $old_coupon_group->wc_coupons = get_post_meta($group_id, '_wc_coupons', true);
        $old_coupon_group->expiry_date = get_post_meta($group_id, '_expiry_date', true);
        $old_coupon_group->customers = get_post_meta($group_id, '_customers', true);
        $old_coupon_group->is_active = get_post_meta($group_id, '_is_active', true);

        // Get the saved meta values (if they exist).
        $old_coupon_group->options = get_post_meta($group_id, '_custom_coupon_options', true);
    }

    // Fetch WooCommerce coupons
    $args = array(
        'post_type' => 'shop_coupon',
        'posts_per_page' => -1
    );
    $coupons = get_posts($args);

    // Fetch Users (can limit the number if there are a lot of users)
    $users = get_users();

    // Retrieve stored form data from transient
    $form_data = get_transient('group_form_data');
    delete_transient('group_form_data');

    // Display error message for form validation
    if ($error = get_transient('group_form_error_msg')) {
        delete_transient('group_form_error-msg');
        echo "<div class='error is-dismissible'><p>{$error}</p></div>";
    }

    // Edit the coupon group
    if ($group_id) {
        // Check if the group with the provided id exists
        $group = get_post($group_id);
        ($group_id);
        if (!$group) {
    ?>
            <div class="admin-cg-no-found-wrap">
                <svg xmlns="http://www.w3.org/2000/svg" height="64px" viewBox="0 0 24 24" width="64px" fill="#3c434a">
                    <path d="M0 0h24v24H0V0z" fill="none" />
                    <circle cx="12" cy="19" r="2" />
                    <path d="M10 3h4v12h-4z" />
                </svg>
                <p> No Coupon Group found</p>
            </div>
        <?php
        } else {

        ?>
            <div class="admin-cg-wrap">
                <div class="admin-cg-main">
                    <h1>Edit Coupon Group</h1>
                    <?php if (isset($_POST["create_coupon_group_submitted"]) && $_POST["create_coupon_group_submitted"] == 'true') create_or_edit_coupon_group_handler()  ?>
                    <form method="POST">
                        <!-- Group Name -->
                        <div class="admin-cg-form-field sm-form-field">
                            <label for="group_name">Group Name</label>
                            <input type="text" name="group_name" id="group_name" value="<?php echo esc_attr($form_data['group_name'] ?? ($old_coupon_group->name  ?? '')); ?>" required>
                        </div>

                        <!-- WooCommerce Coupons -->
                        <div class="admin-cg-form-field">
                            <label for="wc_coupons">WooCommerce Coupons</label>
                            <select name="wc_coupons[]" id="wc_coupons" multiple>
                                <?php
                                if (empty($form_data['wc_coupons'])) {
                                    foreach ($coupons as $index => $coupon) {
                                ?>
                                        <option value="<?php echo $coupon->ID; ?>" <?php echo isset($old_coupon_group->wc_coupons) && in_array($coupon->ID, $old_coupon_group->wc_coupons) ? 'selected' : ''; ?>>
                                            <?php echo esc_attr($coupon->post_title); ?>
                                        </option>
                                    <?php
                                    }
                                } else {
                                    foreach ($coupons as $coupon) {
                                    ?>
                                        <option value="<?php echo $coupon->ID; ?>" <?php echo isset($form_data['wc_coupons']) && in_array($coupon->ID, $form_data['wc_coupons']) ? 'selected' : ''; ?>>
                                            <?php echo esc_attr($coupon->post_title); ?>
                                        </option>
                                <?php
                                    }
                                }
                                ?>
                            </select>
                            <a href="<?php echo admin_url('edit.php?post_type=shop_coupon'); ?>">Go to WooCommerce Coupons</a>
                        </div>

                        <!-- Customers -->
                        <div class="admin-cg-form-field">
                            <label for="customers">Customers</label>
                            <select name="customers[]" id="customers" multiple>
                                <?php
                                if (empty($form_data['wc_coupons'])) {
                                    foreach ($users as $user) {
                                ?>
                                        <option value="<?php echo $user->ID; ?>" <?php echo isset($old_coupon_group->customers) && in_array($user->ID, $old_coupon_group->customers) ? 'selected' : ''; ?>>
                                            <?php echo $user->user_email; ?>
                                        </option>
                                    <?php
                                    }
                                } else {
                                    foreach ($users as $user) {
                                    ?>
                                        <option value="<?php echo $user->ID; ?>" <?php echo isset($form_data['customers']) && in_array($user->ID, $form_data['customers']) ? 'selected' : ''; ?>>
                                            <?php echo $user->user_email; ?>
                                        </option>
                                <?php
                                    }
                                }
                                ?>
                            </select>
                        </div>

                        <!-- Expiry Date -->
                        <div class="admin-cg-form-field sm-form-field">
                            <label for="expiry_date">Expiry Date</label>
                            <input type="text" name="expiry_date" id="expiry_date" class="date-picker" autocomplete="off" value="<?php echo esc_attr(isset($form_data['expiry_date']) ? $form_data['expiry_date'] : ($old_coupon_group->expiry_date ?? '')); ?>">
                        </div>

                        <!-- Is Active -->
                        <div class="admin-cg-form-checkbox">
                            <label for="is_active">Is active:</label>
                            <input type="checkbox" id="is_active" name="is_active" value="1" <?php isset($form_data['is_active']) ? checked($form_data['is_active'], 1) : checked($old_coupon_group->is_active, 1) ?> />
                        </div>

                        <!-- Group options -->
                        <div class="admin-cg-fieldset">
                            <h3>Coupon Group Options</h3>
                            <?php
                            // Display checkboxes for each available option.
                            foreach ($available_options as $option) {
                            ?>
                                <div class="admin-cg-form-checkbox-option">
                                    <label for="<?php echo esc_attr($option['title']); ?>">
                                        <?php echo esc_attr($option['title']); ?>
                                    </label>
                                    <input type="checkbox" id="<?php echo esc_attr($option['title']); ?>" name="<?php echo esc_attr($option['id']); ?>" value="1" <?php isset($form_data['options']) && isset($form_data['options'][$option['id']]) ?  checked($form_data['options'][$option['id']], 1) : (isset($old_coupon_group->options[$option['id']]) ? checked($old_coupon_group->options[$option['id']], 1) : 0); ?>>
                                    <p>
                                        <?php echo esc_attr($option['description']); ?>
                                    </p>
                                </div>
                            <?php
                            }
                            ?>
                            <a href="<?php echo admin_url('admin.php?page=create-coupon-option'); ?>">Create Coupon Group Option</a>
                        </div>

                        <!-- Sents group id in order to update the group -->
                        <input type="hidden" name="group_id" value="<?php echo $group_id ?>">
                        <input type="hidden" name="create_coupon_group_submitted" value="true">
                        <?php wp_nonce_field('coupon_group_nonce_action', 'coupon_group_nonce'); ?>
                        <?php
                        submit_button("Update");
                        ?>
                    </form>
                </div>
            </div>
        <?php
        }
    } else { // Create a new coupon group 
        ?>
        <div class="admin-cg-wrap">
            <div class="admin-cg-main">
                <h1>Create Coupon Group</h1>
                <?php if (isset($_POST["create_coupon_group_submitted"]) && $_POST["create_coupon_group_submitted"] == 'true') create_or_edit_coupon_group_handler()  ?>
                <form method="POST">
                    <!-- Group Name -->
                    <div class="admin-cg-form-field sm-form-field">
                        <label for="group_name">Group Name</label>
                        <input type="text" name="group_name" id="group_name" value="<?php echo esc_attr($form_data['group_name'] ?? ''); ?>" required>
                    </div>

                    <!-- WooCommerce Coupons -->
                    <div class="admin-cg-form-field">
                        <label for="wc_coupons">WooCommerce Coupons</label>
                        <select name="wc_coupons[]" id="wc_coupons" multiple>
                            <?php foreach ($coupons as $index => $coupon) : ?>
                                <option value="<?php echo $coupon->ID; ?>" <?php echo isset($form_data['wc_coupons']) && in_array($coupon->ID, $form_data['wc_coupons']) ? 'selected' : ''; ?>>
                                    <?php echo esc_attr($coupon->post_title); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <a href="<?php echo admin_url('edit.php?post_type=shop_coupon'); ?>">Go to WooCommerce Coupons</a>
                    </div>

                    <!-- Customers -->
                    <div class="admin-cg-form-field ">
                        <label for="customers">Customers</label>
                        <select name="customers[]" id="customers" multiple>
                            <?php foreach ($users as $user) : ?>
                                <option value="<?php echo $user->ID; ?>" <?php echo isset($form_data['customers']) && in_array($user->ID, $form_data['customers']) ? 'selected' : ''; ?>>
                                    <?php echo $user->user_email; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Expiry Date -->
                    <div class="admin-cg-form-field sm-form-field">
                        <label for="expiry_date">Expiry Date</label>
                        <input type="text" name="expiry_date" id="expiry_date" class="date-picker" autocomplete="off" value="<?php echo esc_attr($form_data['expiry_date'] ?? ""); ?>">
                    </div>

                    <!-- Is Active -->
                    <div class="admin-cg-form-checkbox">
                        <label for="is_active">Is active:</label>
                        <input type="checkbox" id="is_active" name="is_active" value="1" <?php isset($form_data['_is_active']) ? checked($form_data['_is_active'], 1) : ""; ?> />
                    </div>

                    <!-- Group options -->
                    <div class="admin-cg-fieldset">
                        <h3>Coupon Group Options</h3>
                        <?php
                        // Display checkboxes for each available option.
                        foreach ($available_options as $option) {
                        ?>
                            <div class="admin-cg-form-checkbox-option">
                                <label for="<?php echo esc_attr($option['title']); ?>">
                                    <?php echo esc_attr($option['title']); ?>
                                </label>
                                <input type="checkbox" id="<?php echo esc_attr($option['title']); ?>" name="<?php echo esc_attr($option['id']); ?>" value="1">
                                <p>
                                    <?php echo esc_attr($option['description']); ?>
                                </p>
                                <?php
                                isset($form_data['id']) && isset($form_data['options'][$option['id']]) ?
                                    checked($form_data['options'][$option['id']], 1) : "";
                                ?>
                            </div>
                        <?php
                        }
                        ?>
                        <a href="<?php echo admin_url('admin.php?page=create-coupon-option'); ?>">Create Coupon Group Option</a>
                    </div>

                    <input type="hidden" name="create_coupon_group_submitted" value="true">
                    <?php wp_nonce_field('coupon_group_nonce_action', 'coupon_group_nonce'); ?>
                    <?php
                    submit_button();
                    ?>
                </form>
            </div>
        </div>
        <?php

    }
}

/**
 * Render the page for creating new coupon custom option-perk
 * 
 */
function display_create_or_edit_coupon_option_page()
{
    $option_id = isset($_GET['option_id']) ? $_GET['option_id'] : 0;

    $form_data = get_transient('new_coupon_option_form_data');
    delete_transient('new_coupon_option_form_data');

    // Display error message for form validation
    if ($error = get_transient('new_coupon_option_form_error_msg')) {
        delete_transient('new_coupon_option_form_error-msg');
        echo "<div class='error is-dismissible'><p>{$error}</p></div>";
    }
    // Check if the option with the provided id exists
    if ($option_id) {
        $option = find_coupon_option_by_id($option_id);
        if (!$option) {
        ?>
            <div class="admin-cg-no-found-wrap">
                <svg xmlns="http://www.w3.org/2000/svg" height="64px" viewBox="0 0 24 24" width="64px" fill="#3c434a">
                    <path d="M0 0h24v24H0V0z" fill="none" />
                    <circle cx="12" cy="19" r="2" />
                    <path d="M10 3h4v12h-4z" />
                </svg>
                <p> No Coupon Option found</p>
            </div>
        <?php
        } else {
            // Display form.
        ?>
            <div class="admin-cg-wrap">
                <div class="admin-cg-main">
                    <h1>Edit Coupon Option</h1>
                    <?php if (isset($_POST["create_coupon_option_submitted"]) && $_POST["create_coupon_option_submitted"] == 'true') create_or_edit_coupon_option_handler()  ?>
                    <form method="POST">
                        <div class="admin-cg-form-field">
                            <label for="custom_coupon_title">Coupon Title</label>
                            <input type="text" name="custom_option_title" id="custom_option_title" value="<?php echo esc_attr($form_data['custom_option_title'] ?? ($option['title']  ?? '')); ?>" required>
                        </div>
                        <div class="admin-cg-form-field">
                            <label for="custom_option_description">Description (Optional)</label>
                            <textarea name="custom_option_description" id="custom_option_description" rows="5" cols="40"><?php echo esc_attr($form_data['custom_option_description'] ?? ($option['description']  ?? '')); ?></textarea>
                        </div>
                        <input type="hidden" name="option_id" value="<?php echo $option_id ?>">
                        <input type="hidden" name="create_coupon_option_submitted" value="true">
                        <?php wp_nonce_field('coupon_option_nonce_action', 'coupon_option_nonce'); ?>
                        <?php
                        wp_nonce_field('coupon_option_nonce_action', 'coupon_option_nonce');
                        submit_button("Update");
                        ?>
                    </form>
                </div>
            </div>
        <?php
        }
    } else {
        ?>
        <div class="admin-cg-wrap">
            <div class="admin-cg-main">
                <h1>Create Coupon Option</h1>
                <?php if (isset($_POST["create_coupon_option_submitted"]) && $_POST["create_coupon_option_submitted"] == 'true') create_or_edit_coupon_option_handler()  ?>
                <form method="POST">
                    <div class="admin-cg-form-field">
                        <label for="custom_coupon_title">Coupon Title</label>
                        <input type="text" name="custom_option_title" id="custom_option_title" value="<?php echo esc_attr($form_data['custom_option_title'] ?? ''); ?>" required>
                    </div>
                    <div class="admin-cg-form-field">
                        <label for="custom_option_description">Description (Optional)</label>
                        <textarea name="custom_option_description" id="custom_option_description" rows="5" cols="40"><?php echo esc_attr($form_data['custom_option_description'] ?? ''); ?></textarea>
                    </div>
                    <input type="hidden" name="create_coupon_option_submitted" value="true">
                    <?php wp_nonce_field('coupon_option_nonce_action', 'coupon_option_nonce'); ?>
                    <?php
                    wp_nonce_field('coupon_option_nonce_action', 'coupon_option_nonce');
                    submit_button("Save Option");
                    ?>
                </form>
            </div>
        </div>
    <?php
    }
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
                    $expiry = get_post_meta($group_id, '_expiry_date', true);
                    $is_active = get_post_meta($group_id, '_is_active', true) == "1" ? "Yes" : "No";
                    $total_users = get_post_meta($group_id, '_customers', true);

                    // For group deletion
                    $delete_nonce = wp_create_nonce('delete_coupon_group_' . $group_id);
                    $delete_link = admin_url('admin.php?page=coupon-group&action=delete&group_id=' .  $group_id . '&_wpnonce=' . $delete_nonce);

                    ?>
                    <tr>
                        <td><?php the_ID(); ?></td>
                        <td><?php the_title(); ?></td>
                        <td><?php echo esc_html($expiry); ?></td>
                        <td><?php echo esc_html($is_active); ?></td>
                        <td><?php echo is_array($total_users) ? count($total_users) : "0"; ?></td>
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
function display_coupon_options()
{
    $custom_coupon_options = get_option('custom_coupon_options', array());

    ?>
    <h3>Custom Coupon Options</h3>
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
                            <a href="<?php echo admin_url('admin.php?page=edit-coupon-option&option_id=' . $option['id']) ?>">Edit</a>
                            <span>|</span>
                            <a href="<?php echo $delete_link ?>" class="delete-coupon-group" data-group-id="<?php echo $option['id'] ?>">Delete</a>
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
