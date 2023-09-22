<?php
// Helper functions

// Ensure this file is being included by WordPress (and not accessed directly)
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * A cron job function that checks if coupon groups have expired.
 *
 * If a coupon group is determined to have expired, associated WooCommerce coupons
 * are set to 'draft' status, effectively disabling them.
 */
function check_coupon_groups_expiry() {
  // Get today's date
  $today = strtotime(date('y-m-d'));

  // Query coupon_groups that are expired
  $args = array(
      'post_type' => 'coupon_group',
      'meta_query' => array(
          array(
              'key' => 'expiry_date',
              'value' => $today,
              'compare' => '<'
          ),
      )
  );

  $expired_coupon_groups = get_posts($args);

  foreach ($expired_coupon_groups as $group) {
      $wc_coupons = get_post_meta($group->ID, '_wc_coupons', true);
      $customers = get_post_meta($group->ID, '_customers', true);

    // Here, you can take desired actions for expired groups.
    // For example, disabling associated WC coupons:
    foreach ($wc_coupons as $coupon_id) {
      wp_update_post(array(
          'ID' => $coupon_id,
          'post_status' => 'draft'
      ));
      foreach ($customers as $user_id) {
        // Remove coupon from the user's session
        remove_coupon_from_user_session($user_id, $coupon_id);
      }
    }
  }
}
add_action('my_daily_coupon_group_check', 'check_coupon_groups_expiry');


/**
 * Removes coupons from users' sessions when the associated coupon_group is deleted.
 *
 * This function hooks into the delete_post action and is triggered just before
 * a post or page is deleted. If the post is of the type 'coupon_group', it fetches
 * the associated WC coupons and users, and removes those coupons from the users' sessions.
 *
 * @param int $post_id The ID of the post being deleted.
 */
function remove_coupons_on_coupon_group_deletion($post_id) {
  // Check if the post being deleted is of the 'coupon_group' post type
  if (get_post_type($post_id) !== 'coupon_group') {
      return;
  }
  
  $wc_coupons = get_post_meta($post_id, '_wc_coupons', true);
  $customers = get_post_meta($post_id, '_customers', true);

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
add_action('before_delete_post', 'remove_coupons_on_coupon_group_deletion');


/**
 * Flags a specific coupon for removal from a user's next session.
 *
 * This function adds a user meta for the given user, indicating that a specific
 * coupon should be removed from their session the next time they interact with the site.
 *
 * @param int $user_id   The ID of the user.
 * @param int $coupon_id The ID of the coupon to flag for removal.
 */
function remove_coupon_from_user_session($user_id, $coupon_id) {
  // Get existing flagged coupons for removal (if any)
  $coupons_to_remove = get_user_meta($user_id, '_coupons_to_remove', true);
  if (!$coupons_to_remove) {
    $coupons_to_remove = array();
  }

  // Add the coupon to the list if not already there
  if (!in_array($coupon_id, $coupons_to_remove)) {
    $coupons_to_remove[] = $coupon_id;
    update_user_meta($user_id, '_coupons_to_remove', $coupons_to_remove);
  }
}

/**
 * Checks for any flagged coupons to remove from the user's session.
 *
 * When the user interacts with the site, this function will check if there are
 * any coupons flagged for removal and remove them from the user's WooCommerce cart session.
 */
function check_and_remove_flagged_coupons() {
  if (!is_user_logged_in() || current_user_can('manage_options')) {
    return; // Only proceed for logged-in users
  }

  $user_id = get_current_user_id();
  $coupons_to_remove = get_user_meta($user_id, '_coupons_to_remove', true);

  if (!$coupons_to_remove || !is_array($coupons_to_remove)) {
    return;
  }

  $cart = WC()->cart;
  
  // Get applied wc coupons
  $applied_coupons = WC()->session->get( 'applied_coupons' ) !== null ? WC()->session->get( 'applied_coupons' ) : '';
  $coupon_codes_to_remove =array();

  // Save coupon ids as coupon codes
  foreach ($coupons_to_remove as $coupon_id) {
    $coupon_code = get_wc_coupon_code_from_id($coupon_id);
    if(!$coupon_code) continue;
    $coupon_codes_to_remove[] = $coupon_code;
  }

  foreach ($coupon_codes_to_remove as $coupon_code) {
    if(in_array($coupon_code, $applied_coupons ) ) {
      $cart->remove_coupon($coupon_code);
    }
  }
  // Recheck and attempt removal again
  foreach ($coupon_codes_to_remove as $coupon_code) {
    if(in_array($coupon_code, $applied_coupons ) ) {
      $cart->remove_coupon($coupon_code);
    }
  }

  $cart->calculate_totals();
  // Clear the flagged coupons for removal for the user
  delete_user_meta($user_id, '_coupons_to_remove');
}
add_action('wp_loaded', 'check_and_remove_flagged_coupons'); 


/**
 * Automatically adds coupons from a coupon_group to the users who are members of that group.
 *
 * This function is triggered when a 'coupon_group' post is saved. It fetches
 * the associated WC coupons and users, and flags the coupons to be added to the users' next session.
 *
 * @param int $meta_id The ID of the meta data being saved.
 * @param int $post_id The ID of the post being saved.
 */
function add_coupons_to_user_session($meta_id, $post_id) {
  // Check if it's a 'coupon_group' post type
  if (get_post_type($post_id) !== 'coupon_group') {
      return;
  }

  $wc_coupons = get_post_meta($post_id, '_wc_coupons', true);
  $customers = get_post_meta($post_id, '_customers', true);

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
add_action('added_post_meta', 'add_coupons_to_user_session', 10, 2);

/**
 * Flags a specific coupon to be added to a user's next session.
 *
 * @param int $user_id   The ID of the user.
 * @param int $coupon_id The ID of the coupon to flag for addition.
 */
function flag_coupon_for_addition($user_id, $coupon_id) {
  $coupons_to_add = get_user_meta($user_id, '_coupons_to_add', true);
  if (!$coupons_to_add) {
    $coupons_to_add = array();
  }
  
  if (!in_array($coupon_id, $coupons_to_add)) {
    $coupons_to_add[] = $coupon_id;
    update_user_meta($user_id, '_coupons_to_add', $coupons_to_add);
  }
}

/**
 * Adds any flagged coupons to the user's session.
 */
function check_and_add_flagged_coupons() {
  if (!is_user_logged_in() || current_user_can('manage_options')) {
      return;
  }

  $user_id = get_current_user_id();
  $coupons_to_add = get_user_meta($user_id, '_coupons_to_add', true);

  if (!$coupons_to_add || !is_array($coupons_to_add)) {
      return;
  }
  $applied_coupons = WC()->session->get( 'applied_coupons' ) !== null ? WC()->session->get( 'applied_coupons' ) : '';
  $cart = WC()->cart;

  foreach ($coupons_to_add as $coupon_id) {
      $coupon_code = get_wc_coupon_code_from_id($coupon_id);
      if(!$coupon_code) continue;
      if( !in_array( $coupon_code, $applied_coupons ) ) {
        $cart->apply_coupon($coupon_code);
      }
  }
  $cart->calculate_totals();
  delete_user_meta($user_id, '_coupons_to_add');
}
add_action('wp_loaded', 'check_and_add_flagged_coupons');


/**
 * Sends an email notification to a user about a new coupon.
 *
 * @param int $user_id   The ID of the user.
 * @param int $group_id The ID of the coupon group.
 */
function notify_user_about_coupon($user_id, $group_id) {
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
 * Filters the coupon message displayed on the frontend.
 *
 * @param string $message Default coupon message.
 * @param string $msg_code Message code.
 * @param WC_Coupon $coupon Coupon object.
 * @return string Modified coupon message.
 */
function custom_coupon_message($message, $msg_code, $coupon) {
  // Check if the coupon belongs to a group
  if (is_coupon_part_of_group($coupon->get_id())) {
      // Display a custom message for grouped coupons
      return 'Your grouped coupon has been applied!';
  }

  // For non-grouped coupons, return the default message
  return $message;
}
add_filter('woocommerce_coupon_message', 'custom_coupon_message', 10, 3);

/**
* Checks if a coupon is part of a group.
*
* @param int $coupon_id WooCommerce coupon ID.
* @return bool True if part of a group, false otherwise.
*/
function is_coupon_part_of_group($coupon_id) {
  // Here you should have the logic to determine if the coupon is part of a group.
  // For example, you can check if the coupon ID exists in any 'coupon_group' meta fields.
  $args = array(
      'post_type'  => 'coupon_group',
      'meta_query' => array(
          array(
              'key'     => 'wc_coupons',
              'value'   => $coupon_id,
              'compare' => 'LIKE'
          )
      )
  );

  $coupon_group_query = new WP_Query($args);
  if ($coupon_group_query->have_posts()) {
      return true;  // The coupon is part of a group
  }

  return false;  // The coupon is not part of a group
}

