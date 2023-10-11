<?php

/**
 * Displays coupon groups.
 * 
 */
function display_delete_confirmation_box()
{
?>
  <div class="admin-cg-delete-confirmation-box">
    <p><?php echo esc_html__('Are you sure that you want to delete ', 'coupon-group'); ?><strong class="admin-cg-delete-item-type"></strong><?php echo esc_html__(' with name ', 'coupon-group'); ?> <strong class="admin-cg-delete-item-name"></strong></p>
    <div>
      <button data-action='cancel' class="admin-cg-button-secondary"><?php echo esc_html__('Cancel', 'coupon-group'); ?></button>
      <button data-action='delete' class="admin-cg-button-danger"><?php echo esc_html__('Delete', 'coupon-group'); ?></button>
    </div>
  </div>
<?php
}
