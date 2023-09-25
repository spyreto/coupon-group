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
    ?>
        <div class="admin-cg-wrap">
            <div class="admin-cg-main">
                <h1>Edit Coupon Group</h1>
                <?php if (isset($_POST["create_coupon_group_submitted"]) && $_POST["create_coupon_group_submitted"] == 'true') create_coupon_group_handler()  ?>
                <form method="POST">
                    <!-- Group Name -->
                    <div class="admin-cg-form-field sm-form-field">
                        <label for="group_name">Group Name</label>
                        <input type="text" name="group_name" id="group_name" value="<?php echo esc_attr($form_data['group_name'] ?? ($old_coupon_group->name  ?? '')); ?>">
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
                                    <option value="<?php echo $coupon->ID; ?>" <?php echo isset($form_data['wc_coupons']) && in_array($coupon->ID, $form_data['wc_coupons']) ? 'selected' : '';
                                                                                ?>>
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
                                <input type="checkbox" id="<?php echo esc_attr($option['title']); ?>" name="<?php echo esc_attr($option['id']); ?>" value="1" <?php
                                                                                                                                                                isset($form_data['options']) && isset($form_data['options'][$option['id']]) ?
                                                                                                                                                                    checked($form_data['options'][$option['id']], 1) :
                                                                                                                                                                    checked($old_coupon_group->options[$option['id']], 1);
                                                                                                                                                                ?>>
                                <p>
                                    <?php echo esc_attr($option['description']); ?>
                                </p>
                            </div>
                        <?php
                        }
                        ?>
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
    } else { // Create a new coupon group 
    ?>
        <div class="admin-cg-wrap">
            <div class="admin-cg-main">
                <h1>Create Coupon Group</h1>
                <?php if (isset($_POST["create_coupon_group_submitted"]) && $_POST["create_coupon_group_submitted"] == 'true') create_coupon_group_handler()  ?>
                <form method="POST">
                    <!-- Group Name -->
                    <div class="admin-cg-form-field sm-form-field">
                        <label for="group_name">Group Name</label>
                        <input type="text" name="group_name" id="group_name" value="<?php echo esc_attr($form_data['group_name'] ?? ''); ?>">
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
function display_create_coupon_option_page()
{
    $form_data = get_transient('new_coupon_option_form_data');
    delete_transient('new_coupon_option_form_data');

    // Display error message for form validation
    if ($error = get_transient('new_coupon_option_form_error_msg')) {
        delete_transient('new_coupon_option_form_error-msg');
        echo "<div class='error is-dismissible'><p>{$error}</p></div>";
    }
    // Display form.
    ?>
    <div class="admin-cg-wrap">
        <div class="admin-cg-main">
            <h1>Create Coupon Option</h1>
            <form method="POST" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <div class="admin-cg-form-field">
                    <label for="custom_coupon_title">Coupon Title</label>
                    <input type="text" name="custom_coupon_title" id="custom_coupon_title" value="<?php echo esc_attr($form_data['custom_coupon_title'] ?? ''); ?>" required>
                </div>
                <div class="admin-cg-form-field">
                    <label for="custom_coupon_description">Description (Optional)</label>
                    <textarea name="custom_coupon_description" id="custom_coupon_description" rows="5" cols="40"><?php echo esc_attr($form_data['custom_coupon_description'] ?? ''); ?></textarea>
                </div>
                <input type="hidden" name="action" value="new_coupon_option_form_action">
                <?php
                wp_nonce_field('new_coupon_option_action', 'new_coupon_option_nonce');
                submit_button("Save Option");
                ?>
            </form>
        </div>
    </div>
<?php
}
