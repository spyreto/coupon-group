<?php


/**
 * Display coupon group perks information after the shipping line item on the Order Edit screen.
 * 
 * @param int $order_id The order ID.
 */
function display_coupon_group_options_after_shipping($order_id)
{
  // Get the order object
  $order = wc_get_order($order_id);

  if ($order) {
    // Get the user ID from the order
    $user_id = $order->get_user_id();

    // Get the user group from user meta
    $coupon_groups = get_active_coupon_groups_for_user($user_id);
    $available_options = get_option('custom_coupon_options', array());

    foreach ($coupon_groups as $coupon_group) {
?>
      <a class='group-name-order-page' href="<?php echo admin_url('admin.php?page=edit-coupon-group&group_id=' . $coupon_group->ID) ?>"> Member of <?php echo get_the_title($coupon_group) ?> Coupon Group </a>
      <?php
      $group_options = get_post_meta($coupon_group->ID, '_custom_coupon_options', true);
      foreach ($available_options as $available_option) {
        // Each $option is an associative array with 'id' and 'value' keys.
        if (isset($group_options[$available_option['id']]) && $group_options[$available_option['id']] == 1) {
      ?>
          <tr>
            <td class=" label"><?php ?>
            </td>
            <td width="1%"></td>
            <td class="total"><bdi>
                <span class="woocommerce-Price-amount amount">
                  <?php echo $available_option['title'] ?>
                </span>
            </td>
          </tr>
<?php

        }
      }
    }
  }
}
add_action('woocommerce_admin_order_totals_after_shipping', 'display_coupon_group_options_after_shipping');
