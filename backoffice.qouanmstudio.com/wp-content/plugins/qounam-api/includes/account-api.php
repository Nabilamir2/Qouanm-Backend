<?php
/**
 * Account API - User Profile Management
 */

if (!defined('ABSPATH')) {
    exit;
}

// Register account endpoints
add_action('rest_api_init', function () {
    // Forgot password - request reset link
    register_rest_route('qounam/v1', '/account/forgot-password', array(
        'methods' => 'POST',
        'callback' => 'qounam_forgot_password',
        'permission_callback' => '__return_true',
    ));

    // Reset password with token
    register_rest_route('qounam/v1', '/account/reset-password', array(
        'methods' => 'POST',
        'callback' => 'qounam_reset_password',
        'permission_callback' => '__return_true',
    ));

    // Get current user profile
    register_rest_route('qounam/v1', '/account/profile', array(
        'methods' => 'GET',
        'callback' => 'qounam_get_profile',
        'permission_callback' => 'qounam_check_jwt_auth',
    ));

    // Update user profile
    register_rest_route('qounam/v1', '/account/update-profile', array(
        'methods' => 'POST',
        'callback' => 'qounam_update_profile',
        'permission_callback' => 'qounam_check_jwt_auth',
    ));

    // Change password
    register_rest_route('qounam/v1', '/account/change-password', array(
        'methods' => 'POST',
        'callback' => 'qounam_change_password',
        'permission_callback' => 'qounam_check_jwt_auth',
    ));

    // Get account settings
    register_rest_route('qounam/v1', '/account/settings', array(
        'methods' => 'GET',
        'callback' => 'qounam_get_settings',
        'permission_callback' => 'qounam_check_jwt_auth',
    ));

    // Update account settings
    register_rest_route('qounam/v1', '/account/settings', array(
        'methods' => 'POST',
        'callback' => 'qounam_update_settings',
        'permission_callback' => 'qounam_check_jwt_auth',
    ));

    // Delete account
    register_rest_route('qounam/v1', '/account/delete', array(
        'methods' => 'POST',
        'callback' => 'qounam_delete_account',
        'permission_callback' => 'qounam_check_jwt_auth',
    ));
});

/**
 * Get user profile
 */
function qounam_get_profile($request)
{
    $user_id = qounam_get_current_user_from_jwt();

    if (!$user_id) {
        return new WP_Error('unauthorized', 'Unauthorized', array('status' => 401));
    }

    $user = get_user_by('id', $user_id);

    if (!$user) {
        return new WP_Error('user_not_found', 'User not found', array('status' => 404));
    }

    $user_programs = get_posts([
        'post_type' => 'program-request',
        'post_status' => 'publish',
        'numberposts' => -1,
        'meta_query' => array(
            array(
                'key' => 'user_id',
                'value' => $user->ID,
                'compare' => '='
            )
        ),
    ]);
    return array(
        'success' => true,
        'user' => array(
            'id' => $user->ID,
            'username' => $user->user_login,
            'email' => $user->user_email,
            'name' => $user->display_name,
            'first_name' => get_user_meta($user->ID, 'first_name', true),
            'last_name' => get_user_meta($user->ID, 'last_name', true),
            'avatar' => get_avatar_url($user->ID),
            'position' => get_user_meta($user->ID, 'position', true),
            'phone_number' => get_user_meta($user->ID, 'phone_number', true),
            'government' => get_user_meta($user->ID, 'government', true),
            'company' => get_user_meta($user->ID, 'company', true),
            'registered' => $user->user_registered,
            'programs' => array_map(function ($program) {
                $program_id = get_field('program', $program->ID);
                return array(
                    'id' => $program_id,
                    'title' => get_the_title($program_id),
                    'status' => get_field('status', $program->ID),
                    'slug' => get_post_field('post_name', $program_id),
                    'thumbnail' => get_the_post_thumbnail_url($program_id),
                    'excerpt' => get_the_excerpt($program_id),
                    'hover_thumbnail' => get_field('hover_thumbnail', $program_id),
                    'category' => get_terms(array(
                        'taxonomy' => 'program-category',
                        'hide_empty' => true,
                        'object_ids' => $program_id
                    ))[0]->name,
                    'price' => get_field('price', $program_id),
                    'sale_price' => get_field('sale_price', $program_id),
                    'seats_available' => get_field('seats_available', $program_id),
                    'limited_offer' => get_field('limited_offer', $program_id),
                    'days_per_week' => get_field('days_per_week', $program_id),
                    'duration_in_weeks' => get_field('duration_in_weeks', $program_id),
                    'start_from' => get_field('start_from', $program_id),
                    'ends_at' => get_field('ends_at', $program_id)
                );
            }, $user_programs),
            'wishlist' => get_user_meta($user->ID, 'wishlist', true) ? array_map(function ($program_id) {
                $title = get_the_title($program_id);
                $thumbnail = get_the_post_thumbnail_url($program_id);
                $excerpt = get_the_excerpt($program_id);
                return [
                    'id' => $program_id,
                    'title' => $title ?: '',
                    'status' => get_field('status', $program_id),
                    'slug' => get_post_field('post_name', $program_id),
                    'excerpt' => $excerpt ?: '',
                    'thumbnail' => $thumbnail ?: '',
                    'hover_thumbnail' => get_field('hover_thumbnail', $program_id),
                    'category' => get_terms(array(
                        'taxonomy' => 'program-category',
                        'hide_empty' => true,
                        'object_ids' => $program_id
                    ))[0]->name,
                    'price' => get_field('price', $program_id),
                    'sale_price' => get_field('sale_price', $program_id),
                    'seats_available' => get_field('seats_available', $program_id),
                    'limited_offer' => get_field('limited_offer', $program_id),
                    'days_per_week' => get_field('days_per_week', $program_id),
                    'duration_in_weeks' => get_field('duration_in_weeks', $program_id),
                    'start_from' => get_field('start_from', $program_id),
                    'ends_at' => get_field('ends_at', $program_id),
                ];
            }, get_user_meta($user->ID, 'wishlist', true) ?? []) : [],
        ),
    );
}

/**
 * Update user profile
 */
function qounam_update_profile($request)
{
    $user_id = qounam_get_current_user_from_jwt();

    if (!$user_id) {
        return new WP_Error('unauthorized', 'Unauthorized', array('status' => 401));
    }

    $params = $request->get_json_params();
    $update_data = array('ID' => $user_id);

    // Update basic info
    if (isset($params['first_name'])) {
        update_user_meta($user_id, 'first_name', sanitize_text_field($params['first_name']));
    }

    if (isset($params['last_name'])) {
        update_user_meta($user_id, 'last_name', sanitize_text_field($params['last_name']));
    }

    if (isset($params['email'])) {
        $update_data['user_email'] = sanitize_text_field($params['email']);
    }

    if (isset($params['phone_number'])) {
        update_user_meta($user_id, 'phone_number', sanitize_text_field($params['phone_number']));
    }

    if (isset($params['company'])) {
        update_user_meta($user_id, 'company', sanitize_text_field($params['company']));
    }

    if (isset($params['position'])) {
        update_user_meta($user_id, 'position', sanitize_textarea_field($params['position']));
    }

    if (isset($params['government'])) {
        update_user_meta($user_id, 'government', sanitize_text_field($params['government']));
    }

    wp_update_user($update_data);

    return qounam_get_profile($request);
}

/**
 * Change password
 */
function qounam_change_password($request)
{
    $user_id = qounam_get_current_user_from_jwt();

    if (!$user_id) {
        return new WP_Error('unauthorized', 'Unauthorized', array('status' => 401));
    }

    $params = $request->get_json_params();
    $current_password = $params['current_password'] ?? '';
    $new_password = $params['new_password'] ?? '';
    $confirm_password = $params['confirm_password'] ?? '';

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        return new WP_Error('missing_fields', 'All fields are required', array('status' => 400));
    }

    if ($new_password !== $confirm_password) {
        return new WP_Error('password_mismatch', 'New passwords do not match', array('status' => 400));
    }

    if (strlen($new_password) < 6) {
        return new WP_Error('weak_password', 'Password must be at least 6 characters', array('status' => 400));
    }

    // Verify current password
    $user = get_user_by('id', $user_id);
    $user_obj = new WP_User($user_id);

    if (!wp_check_password($current_password, $user_obj->user_pass, $user_id)) {
        return new WP_Error('invalid_password', 'Current password is incorrect', array('status' => 401));
    }

    // Update password
    wp_set_password($new_password, $user_id);

    return array(
        'success' => true,
        'message' => 'Password changed successfully',
    );
}

/**
 * Get account settings
 */
function qounam_get_settings($request)
{
    $user_id = qounam_get_current_user_from_jwt();

    if (!$user_id) {
        return new WP_Error('unauthorized', 'Unauthorized', array('status' => 401));
    }

    $settings = array(
        'email_notifications' => get_user_meta($user_id, 'email_notifications', true) ?: 'yes',
        'newsletter' => get_user_meta($user_id, 'newsletter', true) ?: 'yes',
        'privacy' => get_user_meta($user_id, 'privacy', true) ?: 'public',
        'two_factor' => get_user_meta($user_id, 'two_factor', true) ?: 'no',
    );

    return array(
        'success' => true,
        'settings' => $settings,
    );
}

/**
 * Update account settings
 */
function qounam_update_settings($request)
{
    $user_id = qounam_get_current_user_from_jwt();

    if (!$user_id) {
        return new WP_Error('unauthorized', 'Unauthorized', array('status' => 401));
    }

    $params = $request->get_json_params();

    if (isset($params['email_notifications'])) {
        update_user_meta($user_id, 'email_notifications', sanitize_text_field($params['email_notifications']));
    }

    if (isset($params['newsletter'])) {
        update_user_meta($user_id, 'newsletter', sanitize_text_field($params['newsletter']));
    }

    if (isset($params['privacy'])) {
        update_user_meta($user_id, 'privacy', sanitize_text_field($params['privacy']));
    }

    if (isset($params['two_factor'])) {
        update_user_meta($user_id, 'two_factor', sanitize_text_field($params['two_factor']));
    }

    return qounam_get_settings($request);
}

/**
 * Forgot password - send reset link
 */
function qounam_forgot_password($request)
{
    $email = sanitize_email($request->get_param('email'));

    if (empty($email) || !is_email($email)) {
        return new WP_Error('invalid_email', 'Please provide a valid email address', array('status' => 400));
    }

    $user = get_user_by('email', $email);

    if (!$user) {
        // For security, don't reveal if the email exists or not
        return array(
            'success' => true,
            'message' => 'If the email exists, a password reset link has been sent.'
        );
    }

    // Generate reset key
    $key = get_password_reset_key($user);

    if (is_wp_error($key)) {
        return new WP_Error('reset_key_error', 'Failed to generate reset key', array('status' => 500));
    }

    // Get frontend reset URL from settings or use a default
    $frontend_reset_url = get_option('frontend_reset_url', 'http://localhost:3000/my-account/reset-password/');

    // Create reset link for frontend
    $reset_link = add_query_arg(array(
        'key' => $key,
        'login' => rawurlencode($user->user_login)
    ), $frontend_reset_url);

    // Send email with reset link
    $to = $user->user_email;
    $subject = 'Password Reset Request';
    $message = 'Hello ' . $user->display_name . ",<br><br>";
    $message .= 'You have requested to reset your password. Click the link below to set a new password:<br>';
    $message .= $reset_link . "<br>";
    $message .= 'This link will expire in 24 hours.<br><br>';
    $message .= 'If you did not request this, please ignore this email.<br>';
    $message .= 'Thank you,<br>The Qounam Team';

    $headers = array('Content-Type: text/html; charset=UTF-8');

    $sent = wp_mail($to, $subject, $message, $headers);

    if (!$sent) {
        return new WP_Error('email_failed', 'Failed to send reset email', array('status' => 500));
    }

    return array(
        'success' => true,
        'message' => 'If the email exists, a password reset link has been sent.'
    );
}

/**
 * Reset password with token
 */
function qounam_reset_password($request)
{
    $key = sanitize_text_field($request->get_param('key'));
    $login = sanitize_text_field($request->get_param('login'));
    $password = $request->get_param('password');
    $confirm_password = $request->get_param('confirm_password');

    // Check if passwords match
    if ($password !== $confirm_password) {
        return new WP_Error('password_mismatch', 'Passwords do not match', array('status' => 400));
    }

    if (empty($key) || empty($login) || empty($password)) {
        return new WP_Error('missing_fields', 'Key, login, and password are required', array('status' => 400));
    }

    // Validate password strength
    if (strlen($password) < 8) {
        return new WP_Error('weak_password', 'Password must be at least 8 characters long', array('status' => 400));
    }

    $user = check_password_reset_key($key, $login);

    if (is_wp_error($user)) {
        return new WP_Error('invalid_key', 'Invalid or expired reset link', array('status' => 400));
    }

    // Reset the password
    reset_password($user, $password);

    return array(
        'success' => true,
        'message' => 'Password has been reset successfully. You can now log in with your new password.'
    );
}

/**
 * Delete account
 */
function qounam_delete_account($request)
{
    $user_id = qounam_get_current_user_from_jwt();

    if (!$user_id) {
        return new WP_Error('unauthorized', 'Unauthorized', array('status' => 401));
    }

    $password = $request->get_param('password');

    if (empty($password)) {
        return new WP_Error('missing_password', 'Password is required', array('status' => 400));
    }

    $user = get_user_by('id', $user_id);

    if (!wp_check_password($password, $user->user_pass, $user_id)) {
        return new WP_Error('invalid_password', 'Incorrect password', array('status' => 400));
    }

    // Delete user
    require_once(ABSPATH . 'wp-admin/includes/user.php');
    $result = wp_delete_user($user_id);

    if (!$result) {
        return new WP_Error('delete_failed', 'Failed to delete account', array('status' => 500));
    }

    return array(
        'success' => true,
        'message' => 'Account deleted successfully'
    );
}
