<?php
// Helper functions

// Ensure this file is being included by WordPress (and not accessed directly)
if (!defined('ABSPATH')) {
  exit; // Exit if accessed directly
}

/**
 * Removes coupons from users' sessions when the associated coupon_group is deleted.
 *
 * This function hooks into the delete_post action and is triggered just before
 * a post or page is deleted. If the post is of the type 'coupon_group', it fetches
 * the associated WC coupons and users, and removes those coupons from the users' sessions.
 *
 * @param int $post_id The ID of the post being deleted.
 */
function remove_coupons_from_users($post_id, $remove_from_users = null, $removed_coupons = null)
{
  // Check if the post being deleted is of the 'coupon_group' post type
  if (get_post_type($post_id) !== 'coupon_group') {
    return;
  }

  $customers = (empty($remove_from_users) || !isset($remove_from_users)) ?
    get_post_meta($post_id, '_customers', true) : $remove_from_users;

  $wc_coupons = (empty($removed_coupons) || !isset($removed_coupons)) ?
    get_post_meta($post_id, '_wc_coupons', true) : $removed_coupons;

  if (empty($wc_coupons) || empty($customers)) {
    return;
  }

  foreach ($customers as $user_id) {
    foreach ($wc_coupons as $coupon_id) {
      // Remove coupon from the user's session
      remove_coupon_from_user_session($user_id, $coupon_id);
    }
  }
}
add_action('before_delete_post', 'remove_coupons_from_users');


/**
 * Flags a specific coupon for removal from a user's next session.
 *
 * This function adds a user meta for the given user, indicating that a specific
 * coupon should be removed from their session the next time they interact with the site.
 *
 * @param int $user_id   The ID of the user.
 * @param int $coupon_id The ID of the coupon to flag for removal.
 */
function remove_coupon_from_user_session($user_id, $coupon_id)
{
  // Get existing flagged coupons for removal (if any)
  $coupons_to_remove = get_user_meta($user_id, '_coupons_to_remove', true);
  if (!$coupons_to_remove) {
    $coupons_to_remove = array();
  }

  // Add the coupon to the list if not already there
  if (!in_array($coupon_id, $coupons_to_remove)) {
    $coupons_to_remove[] = $coupon_id;
    update_user_meta($user_id, '_coupons_to_remove', $coupons_to_remove);
    // Save the current timestamp as another user meta
    $timestamp_key = '_coupons_to_remove' . '_timestamp';
    update_user_meta($user_id, $timestamp_key, current_time('mysql'));
  }
}

/**
 * Automatically adds coupons from a coupon_group to the users who are members of that group.
 *
 * This function is triggered when a 'coupon_group' post is saved. It fetches
 * the associated WC coupons and users, and flags the coupons to be added to the users' next session.
 *
 * @param int $meta_id The ID of the meta data being saved.
 * @param int $post_id The ID of the post being saved.
 */
function add_coupons_to_users($meta_id, $post_id, $add_to_users = null, $coupons_to_add = null)
{
  // Check if it's a 'coupon_group' post type
  if (get_post_type($post_id) !== 'coupon_group') {
    return;
  }

  $customers = (empty($add_to_users) || !isset($add_to_users)) ?
    get_post_meta($post_id, '_customers', true) : $add_to_users;

  $wc_coupons = (empty($coupons_to_add) || !isset($coupons_to_add)) ?
    get_post_meta($post_id, '_wc_coupons', true) : $coupons_to_add;

  if (!$wc_coupons || !$customers) {
    return;
  }

  foreach ($customers as $user_id) {
    foreach ($wc_coupons as $coupon_id) {
      flag_coupon_for_addition($user_id, $coupon_id);
      notify_user_about_coupon($user_id, $post_id);
    }
  }
}
add_action('added_post_meta', 'add_coupons_to_users', 10, 2);

/**
 * Flags a specific coupon to be added to a user's next session.
 *
 * @param int $user_id   The ID of the user.
 * @param int $coupon_id The ID of the coupon to flag for addition.
 */
function flag_coupon_for_addition($user_id, $coupon_id)
{
  $coupons_to_add = get_user_meta($user_id, '_coupons_to_add', true);
  if (!$coupons_to_add) {
    $coupons_to_add = array();
  }

  if (!in_array($coupon_id, $coupons_to_add)) {
    $coupons_to_add[] = $coupon_id;
    update_user_meta($user_id, '_coupons_to_add', $coupons_to_add);
    // Save the current timestamp as another user meta
    $timestamp_key = '_coupons_to_add' . '_timestamp';
    update_user_meta($user_id, $timestamp_key, current_time('mysql'));
  }
}

/**
 * Adds or removes any flagged coupons to the user's session.
 */
function check_flagged_coupons()
{
  if (!is_user_logged_in() || current_user_can('manage_options')) {
    return;
  }

  $user_id = get_current_user_id();
  $applied_coupons = WC()->session->get('applied_coupons') !== null ? WC()->session->get('applied_coupons') : '';
  $cart = WC()->cart;

  $coupons_to_add = get_user_meta($user_id, '_coupons_to_add', true) !== '' ?
    get_user_meta($user_id, '_coupons_to_add', true) : array();
  $coupons_to_remove = get_user_meta($user_id, '_coupons_to_remove', true) !== '' ?
    get_user_meta($user_id, '_coupons_to_remove', true) : array();

  if (empty($coupons_to_add) && empty($coupons_to_remove)) {
    return;
  } elseif (!empty($coupons_to_add) && empty($coupons_to_remove)) {
    add_coupons_to_cart($user_id, $coupons_to_add, $applied_coupons, $cart);
  } elseif (!empty($coupons_to_remove) && empty($coupons_to_add)) {
    remove_coupons_from_cart($user_id, $coupons_to_remove, $applied_coupons, $cart);
  } else {
    // Get timestamps
    $coupons_to_add_timestamp = get_user_meta($user_id, '_coupons_to_add_timestamp', true);
    $coupons_to_remove_timestamp = get_user_meta($user_id, '_coupons_to_remove_timestamp', true);

    //Get datetimes
    $coupons_to_add_datetime = $coupons_to_add_timestamp !== '' ?
      new DateTime($coupons_to_add_timestamp) : new DateTime();
    $coupons_to_remove_datetime = $coupons_to_remove_timestamp !== '' ?
      new DateTime($coupons_to_remove_timestamp) : new DateTime();

    if ($coupons_to_add_datetime > $coupons_to_remove_datetime) {
      add_coupons_to_cart($user_id, $coupons_to_add, $applied_coupons, $cart);

      $updated_coupons_to_remove = array_diff($coupons_to_remove, $coupons_to_add);
      remove_coupons_from_cart($user_id, $updated_coupons_to_remove, $applied_coupons, $cart);
    } else {
      remove_coupons_from_cart($user_id, $coupons_to_remove, $applied_coupons, $cart);

      $updated_coupons_to_add = array_diff($coupons_to_add, $coupons_to_remove);
      add_coupons_to_cart($user_id, $updated_coupons_to_add, $applied_coupons, $cart);
    }
  }
  // Update cart total
  $cart->calculate_totals();
}
add_action('wp_loaded', 'check_flagged_coupons');


// Temporary storage for the old coupon group values
global $before_update_coupon_group;
$before_update_coupon_group = array();

// Hook before metadata is updated
add_filter('update_post_metadata', function ($check, $object_id, $meta_key) use (&$before_update_coupon_group) {
  // Get the old value
  $before_update_coupon_group[$meta_key] = get_post_meta($object_id, $meta_key, true);
  return $check; // Allow the update to happen
}, 10, 3);


/**
 * Handle coupon group post update logic.
 *
 * @param int $post_id The post ID.
 * @param WP_Post $post_after Post object following the update.
 * @param WP_Post $post_before Post object before the update.
 */
function handle_coupon_group_update($meta_id, $object_id, $meta_key, $meta_value)
{
  // Check if the post being updated is of the 'coupon_group' post type
  if (get_post_type($object_id) !== 'coupon_group') {
    return;
  }

  global $before_update_coupon_group;

  switch ($meta_key) {
    case "_is_active":
      $is_activated = $meta_value;
      // Activation or deactivation logic
      if ($is_activated) {  // If activated
        add_coupons_to_users($meta_id, $object_id);
      } else {  // If deactivated
        remove_coupons_from_users($object_id);
      }
      break;

    case "_customers":
      $customers_after = $meta_value;
      $customers_before = (isset($before_update_coupon_group['_customers']) ?
        $before_update_coupon_group['_customers'] : $meta_value);

      // Logic for adding/removing users
      $added_users = array_diff($customers_after, $customers_before);
      $removed_users = array_diff($customers_before, $customers_after);

      if (!empty($added_users)) {
        add_coupons_to_users($meta_id, $object_id, $added_users);
      }
      if (!empty($removed_users)) {
        remove_coupons_from_users($object_id, $removed_users);
      }
      break;

    case "_wc_coupons":
      $wc_coupons_after = $meta_value;
      $wc_coupons_before = (isset($before_update_coupon_group['_wc_coupons']) ?
        $before_update_coupon_group['_wc_coupons'] : null);

      // Logic for adding/removing coupons
      $added_coupons = array_diff($wc_coupons_after, $wc_coupons_before);
      $removed_coupons = array_diff($wc_coupons_before, $wc_coupons_after);

      if (!empty($added_coupons || !empty($removed_coupons))) {

        if (!empty($added_coupons)) {
          add_coupons_to_users($meta_id, $object_id, null, $added_coupons);
        }
        if (!empty($removed_coupons)) {
          remove_coupons_from_users($object_id, null, $removed_coupons);
        }
      }
      break;
    default:
      echo "I don't know you!";
      break;
  }
}
add_action('updated_post_meta', 'handle_coupon_group_update', 10, 4);


/**
 * Adds coupons to user's cart session.
 *
 * @param int $user_id The user's ID.
 * @param array $coupons_to_remove Coupons to add to a user.
 * @param array $applied_coupons Applied coupons.
 * @param WC_Cart $cart User's cart.
 */
function add_coupons_to_cart($user_id, $coupons_to_add, $applied_coupons, $cart)
{
  foreach ($coupons_to_add as $coupon_id) {
    $coupon_code = get_wc_coupon_code_from_id($coupon_id);
    if (!$coupon_code) continue;
    if (!in_array($coupon_code, $applied_coupons)) {
      $cart->apply_coupon($coupon_code);
    }
  }
  // Clear the flagged coupons for addition for the user
  delete_user_meta($user_id, '_coupons_to_add');
  delete_user_meta($user_id, '_coupons_to_add_timestamp');
}

/**
 * Removes coupons from user's cart session.
 *
 * @param int $post_id The post ID.
 * @param array $coupons_to_remove Coupons to remove from user.
 * @param array $applied_coupons Applied coupons.
 * @param WC_Cart $cart User's cart.
 */
function remove_coupons_from_cart($user_id, $coupons_to_remove, $applied_coupons, $cart)
{
  if (!empty($applied_coupons)) {
    $coupon_codes_to_remove = array();
    // Save coupon ids as coupon codes
    foreach ($coupons_to_remove as $coupon_id) {
      $coupon_code = get_wc_coupon_code_from_id($coupon_id);
      if (!$coupon_code) continue;
      $coupon_codes_to_remove[] = $coupon_code;
    }

    foreach ($coupon_codes_to_remove as $coupon_code) {
      if (in_array($coupon_code, $applied_coupons)) {
        $cart->remove_coupon($coupon_code);
      }
    }
    // Recheck and attempt removal again
    foreach ($coupon_codes_to_remove as $coupon_code) {
      if (in_array($coupon_code, $applied_coupons)) {
        $cart->remove_coupon($coupon_code);
      }
    }
  }
  // Clear the flagged coupons for removal for the user
  delete_user_meta($user_id, '_coupons_to_remove');
  delete_user_meta($user_id, '_coupons_to_remove_timestamp');
}


/**
 * Sends an email notification to a user about a new coupon.
 *
 * @param int $user_id The ID of the user.
 * @param int $group_id The ID of the coupon group.
 */
function notify_user_about_coupon($user_id, $group_id)
{
  $user_info = get_userdata($user_id);
  $group_name = get_post($group_id)->post_title;

  $to = $user_info->user_email;
  $subject = 'You have been added to a coupon group!';
  $message = "Hello {$user_info->display_name},\n\n";
  $message .= "The {$group_name} offer package has been added to your account. Use it on your next purchase!\n\n";
  $message .= "Best regards,\nYour Site Name";

  wp_mail($to, $subject, $message);
}


/**
 * Overrides the default WooCommerce coupon applied message.
 *
 * @param string    $msg      The original message.
 * @param int       $msg_code The message code.
 * @param WC_Coupon $coupon   The coupon object.
 * @return string The custom message for applied coupons or the original message.
 */
function custom_coupon_message($message, $msg_code, $coupon)
{
  // Display a custom message for grouped coupons
  // 'Your grouped coupon has been applied!'

  // Check if the msg_code corresponds to the "coupon applied" code and the coupon belongs to a group
  if ($msg_code === WC_Coupon::WC_COUPON_SUCCESS && is_coupon_part_of_group($coupon->get_id())) {
    $coupon_code = $coupon->get_code();
    // Return your custom message for applied coupons
    return __('You are member of a coupon group, coupon ' . $coupon_code . ' has been applied!');
  }

  // For non-grouped coupons, return the default message
  return $message;
}
add_filter('woocommerce_coupon_message', 'custom_coupon_message', 10, 3);



function my_custom_cart_section()
{
  if (!is_user_logged_in() || current_user_can('manage_options')) {
    return;
  }

  $user_id = get_current_user_id();
  $coupon_groups = get_active_coupon_groups_for_user($user_id);
  $available_options = get_option('custom_coupon_options', array());

  foreach ($coupon_groups as $coupon_group) {
    $group_options = get_post_meta($coupon_group->ID, '_custom_coupon_options', true);

    foreach ($available_options as $available_option) {
      // Each $option is an associative array with 'id' and 'value' keys.
      if (isset($group_options[$available_option['id']]) && $group_options[$available_option['id']] == 1) {
?>
        <tr>
          <th><?php echo $available_option['title'] ?></th>
          <td><?php echo $available_option['description'] ?></td>
        </tr>
<?php
      }
    }
  }
}
add_action('woocommerce_cart_totals_before_shipping', 'my_custom_cart_section');
add_action('woocommerce_review_order_before_shipping', 'my_custom_cart_section');
