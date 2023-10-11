<?php

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
        $old_coupon_group->unlimited_use = get_post_meta($group_id, '_unlimited_use', true);

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
                <p> <?php echo esc_html__('No Coupon Group found', 'coupon-group'); ?></p>
            </div>
        <?php
        } else {

        ?>
            <div class="admin-cg-wrap">
                <div class="admin-cg-main">
                    <h1><?php echo esc_html__('Edit Coupon Group', 'coupon-group'); ?></h1>
                    <?php if (isset($_POST["create_coupon_group_submitted"]) && $_POST["create_coupon_group_submitted"] == 'true') create_or_edit_coupon_group_handler()  ?>
                    <form method="POST">
                        <!-- Group Name -->
                        <div class="admin-cg-form-field sm-form-field">
                            <label for="group_name"><?php echo esc_html__('Group Name', 'coupon-group'); ?></label>
                            <input type="text" name="group_name" id="group_name" value="<?php echo esc_attr($form_data['group_name'] ?? ($old_coupon_group->name  ?? '')); ?>" required>
                        </div>

                        <!-- WooCommerce Coupons -->
                        <div class="admin-cg-form-field">
                            <label for="wc_coupons"><?php echo esc_html__('WooCommerce Coupons', 'coupon-group'); ?></label>
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
                            <a href="<?php echo admin_url('edit.php?post_type=shop_coupon'); ?>"><?php echo esc_html__('Go to WooCommerce Coupons', 'coupon-group'); ?></a>
                        </div>

                        <!-- Customers -->
                        <div class="admin-cg-form-field">
                            <label for="customers"><?php echo esc_html__('Customers', 'coupon-group'); ?></label>
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
                        <div class="admin-cg-form-field">
                            <label for="expiry_date"><?php echo esc_html__('Expiry Date', 'coupon-group'); ?></label>
                            <input type="text" name="expiry_date" id="expiry_date" class="date-picker sm-form-field" autocomplete="off" value="<?php echo esc_attr(isset($form_data['expiry_date']) ? $form_data['expiry_date'] : ($old_coupon_group->expiry_date ?? '')); ?>">
                            <span class="admin-cg-input-info"><?php echo esc_html__('End date of the Coupon Group. If it is empty the group never expires.', 'coupon-group'); ?></span>
                        </div>

                        <!-- Is Active -->
                        <div class="admin-cg-form-checkbox">
                            <label for="is_active"><?php echo esc_html__('Is Active:', 'coupon-group'); ?></label>
                            <input type="checkbox" id="is_active" name="is_active" value="1" <?php isset($form_data['is_active']) ? checked($form_data['is_active'], 1) : checked($old_coupon_group->is_active, 1) ?> />
                            <span class="admin-cg-input-info"><?php echo esc_html__('Check this box to activate the Coupon Group until the expiration date (if set).', 'coupon-group'); ?></span>
                        </div>

                        <!-- Unlimited Use -->
                        <div class="admin-cg-form-checkbox">
                            <label for="unlimited_use"><?php echo esc_html__('Unlimited Use:', 'coupon-group'); ?></label>
                            <input type="checkbox" id="unlimited_use" name="unlimited_use" value="1" <?php isset($form_data['unlimited_use']) ? checked($form_data['unlimited_use'], 1) : checked($old_coupon_group->unlimited_use, 1) ?> />
                            <span class="admin-cg-input-info"><?php echo esc_html__('Check this box to allow unlimited use of the Coupon Group until the expiration date (if set).', 'coupon-group'); ?></span>
                        </div>

                        <!-- Group options -->
                        <div class="admin-cg-fieldset">
                            <h3><?php echo esc_html__('Coupon Group Options', 'coupon-group'); ?></h3>
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
                            <a href="<?php echo admin_url('admin.php?page=create-coupon-option'); ?>"><?php echo esc_html__('Create Coupon Group Option', 'coupon-group'); ?></a>
                        </div>

                        <!-- Sents group id in order to update the group -->
                        <input type="hidden" name="group_id" value="<?php echo $group_id ?>">
                        <input type="hidden" name="create_coupon_group_submitted" value="true">
                        <?php wp_nonce_field('coupon_group_nonce_action', 'coupon_group_nonce'); ?>
                        <?php
                        submit_button(__('Update', 'coupon-group'));
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
                <h1><?php echo esc_html__('Create Coupon Group', 'coupon-group'); ?></h1>
                <?php if (isset($_POST["create_coupon_group_submitted"]) && $_POST["create_coupon_group_submitted"] == 'true') create_or_edit_coupon_group_handler()  ?>
                <form method="POST">
                    <!-- Group Name -->
                    <div class="admin-cg-form-field sm-form-field">
                        <label for="group_name"><?php echo esc_html__('Group Name', 'coupon-group'); ?></label>
                        <input type="text" name="group_name" id="group_name" value="<?php echo esc_attr($form_data['group_name'] ?? ''); ?>" required>
                    </div>

                    <!-- WooCommerce Coupons -->
                    <div class="admin-cg-form-field">
                        <label for="wc_coupons"><?php echo esc_html__('WooCommerce Coupons', 'coupon-group'); ?></label>
                        <select name="wc_coupons[]" id="wc_coupons" multiple>
                            <?php foreach ($coupons as $index => $coupon) : ?>
                                <option value="<?php echo $coupon->ID; ?>" <?php echo isset($form_data['wc_coupons']) && in_array($coupon->ID, $form_data['wc_coupons']) ? 'selected' : ''; ?>>
                                    <?php echo esc_attr($coupon->post_title); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <a href="<?php echo admin_url('edit.php?post_type=shop_coupon'); ?>"><?php echo esc_html__('Go to WooCommerce Coupons', 'coupon-group'); ?></a>
                    </div>

                    <!-- Customers -->
                    <div class="admin-cg-form-field ">
                        <label for="customers"><?php echo esc_html__('Customers', 'coupon-group'); ?></label>
                        <select name="customers[]" id="customers" multiple>
                            <?php foreach ($users as $user) : ?>
                                <option value="<?php echo $user->ID; ?>" <?php echo isset($form_data['customers']) && in_array($user->ID, $form_data['customers']) ? 'selected' : ''; ?>>
                                    <?php echo $user->user_email; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Expiry Date -->
                    <div class="admin-cg-form-field">
                        <label for="expiry_date">Expiry Date</label>
                        <input type="text" name="expiry_date" id="expiry_date" class="date-picker sm-form-field" autocomplete="off" value="<?php echo esc_attr($form_data['expiry_date'] ?? ""); ?>">
                        <span class="admin-cg-input-info"><?php echo esc_html__('End date of the Coupon Group. If it is empty the group never expires.', 'coupon-group'); ?></span>
                    </div>

                    <!-- Is Active -->
                    <div class="admin-cg-form-checkbox">
                        <label for="is_active"><?php echo esc_html__('Is Active:', 'coupon-group'); ?></label>
                        <input type="checkbox" id="is_active" name="is_active" value="1" <?php isset($form_data['is_active']) ? checked($form_data['is_active'], 1) : ""; ?> />
                        <span class="admin-cg-input-info"><?php echo esc_html__('Check this box to activate the Coupon Group until the expiration date (if set).', 'coupon-group'); ?></span>
                    </div>

                    <!-- Unlimited Use -->
                    <div class="admin-cg-form-checkbox">
                        <label for="unlimited_use"><?php echo esc_html__('Unlimited Use:', 'coupon-group'); ?></label>
                        <input type="checkbox" id="unlimited_use" name="unlimited_use" value="1" <?php isset($form_data['unlimited_use']) ? checked($form_data['unlimited_use'], 1) : ""; ?> />
                        <span class="admin-cg-input-info"><?php echo esc_html__('Check this box to allow unlimited use of the Coupon Group until the expiration date (if set).', 'coupon-group'); ?></span>
                    </div>

                    <!-- Group options -->
                    <div class="admin-cg-fieldset">
                        <h3><?php echo esc_html__('Coupon Group Options', 'coupon-group'); ?></h3>
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
                        <a href="<?php echo admin_url('admin.php?page=create-coupon-option'); ?>"><?php echo esc_html__('Create Coupon Group Option', 'coupon-group'); ?></a>
                    </div>

                    <input type="hidden" name="create_coupon_group_submitted" value="true">
                    <?php wp_nonce_field('coupon_group_nonce_action', 'coupon_group_nonce'); ?>
                    <?php
                    submit_button(__('Save Changes', 'coupon-group'));
                    ?>
                </form>
            </div>
        </div>
<?php

    }
}
