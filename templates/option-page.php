<?php


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
                <p><?php echo esc_html__('No Coupon Option found.', 'coupon-group'); ?></p>
            </div>
        <?php
        } else {
            // Display form.
        ?>
            <div class="admin-cg-wrap">
                <div class="admin-cg-main">
                    <h1><?php echo esc_html__('Edit Coupon Group Option', 'coupon-group'); ?></h1>
                    <?php if (isset($_POST["create_coupon_option_submitted"]) && $_POST["create_coupon_option_submitted"] == 'true') create_or_edit_coupon_option_handler()  ?>
                    <form method="POST">
                        <div class="admin-cg-form-field">
                            <label for="custom_coupon_title"><?php echo esc_html__('Option Title', 'coupon-group'); ?></label>
                            <input type="text" name="custom_option_title" id="custom_option_title" value="<?php echo esc_attr($form_data['custom_option_title'] ?? ($option['title']  ?? '')); ?>" required>
                        </div>
                        <div class="admin-cg-form-field">
                            <label for="custom_option_description"><?php echo esc_html__('Description (Optional)', 'coupon-group'); ?></label>
                            <textarea name="custom_option_description" id="custom_option_description" rows="5" cols="40"><?php echo esc_attr($form_data['custom_option_description'] ?? ($option['description']  ?? '')); ?></textarea>
                        </div>
                        <input type="hidden" name="option_id" value="<?php echo $option_id ?>">
                        <input type="hidden" name="create_coupon_option_submitted" value="true">
                        <?php wp_nonce_field('coupon_option_nonce_action', 'coupon_option_nonce'); ?>
                        <?php
                        wp_nonce_field('coupon_option_nonce_action', 'coupon_option_nonce');
                        submit_button(__('Update', 'coupon-group'));
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
                <h1><?php echo esc_html__('Create Coupon Group Option', 'coupon-group'); ?></h1>
                <?php if (isset($_POST["create_coupon_option_submitted"]) && $_POST["create_coupon_option_submitted"] == 'true') create_or_edit_coupon_option_handler()  ?>
                <form method="POST">
                    <div class="admin-cg-form-field">
                        <label for="custom_coupon_title"><?php echo esc_html__('Option Title', 'coupon-group'); ?></label>
                        <input type="text" name="custom_option_title" id="custom_option_title" value="<?php echo esc_attr($form_data['custom_option_title'] ?? ''); ?>" required>
                    </div>
                    <div class="admin-cg-form-field">
                        <label for="custom_option_description"><?php echo esc_html__('Description (Optional)', 'coupon-group'); ?></label>
                        <textarea name="custom_option_description" id="custom_option_description" rows="5" cols="40"><?php echo esc_attr($form_data['custom_option_description'] ?? ''); ?></textarea>
                    </div>
                    <input type="hidden" name="create_coupon_option_submitted" value="true">
                    <?php wp_nonce_field('coupon_option_nonce_action', 'coupon_option_nonce'); ?>
                    <?php
                    wp_nonce_field('coupon_option_nonce_action', 'coupon_option_nonce');
                    submit_button(__('Save Option', 'coupon-group'));
                    ?>
                </form>
            </div>
        </div>
<?php
    }
    display_footer();
}
