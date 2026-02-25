<?php
/**
 * Verification functionality for Qounam API
 */

if (!defined('ABSPATH')) {
    exit;
}

// Register verification endpoints
add_action('rest_api_init', function() {
    // Verify email endpoint
    register_rest_route('qounam/v1', '/auth/verify-email', array(
        'methods' => 'POST',
        'callback' => 'qounam_verify_email',
        'permission_callback' => '__return_true',
    ));

    // Resend verification code endpoint
    register_rest_route('qounam/v1', '/auth/resend-verification', array(
        'methods' => 'POST',
        'callback' => 'qounam_resend_verification',
        'permission_callback' => '__return_true',
    ));
});

/**
 * Generate a 6-digit verification code
 */
function qounam_generate_verification_code() {
    return str_pad(wp_rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

/**
 * Send verification email
 */
function qounam_send_verification_email($email, $code) {

    if ( ! email_exists($email) ) {
        return new WP_Error('email_not_exists', 'Email not exists', array('status' => 400));
    }
    $user = get_user_by('email', $email);
    $verified = get_user_meta($user->ID, '_email_verified', true);
    if ( $verified ) {
        return new WP_Error('email_verified', 'Email already verified', array('status' => 400));
    }
    $subject = 'Verify Your Email Address';
    $message = sprintf(
        "Your verification code is: %s<br>This code will expire in 1 hour.",
        $code
    );
    
    $headers = "Content-Type: text/html; charset=UTF-8\r\n";
    
    return wp_mail($email, $subject, $message, $headers);
}

/**
 * Store verification code in user meta
 */
function qounam_store_verification_code($user_id, $code) {
    $expires = time() + HOUR_IN_SECONDS; // 1 hour expiration
    update_user_meta($user_id, '_verification_code', wp_hash($code));
    update_user_meta($user_id, '_verification_code_expires', $expires);
}

/**
 * Verify email with code
 */
function qounam_verify_email($request) {
    $email = sanitize_email($request->get_param('email'));
    $code = sanitize_text_field($request->get_param('code'));
    
    if (empty($email) || empty($code)) {
        return new WP_Error('missing_fields', 'Email and verification code are required', array('status' => 400));
    }
    
    $user = get_user_by('email', $email);
    
    if (!$user) {
        return new WP_Error('invalid_email', 'No account found with this email', array('status' => 404));
    }
    
    $stored_code = get_user_meta($user->ID, '_verification_code', true);
    $expires = (int) get_user_meta($user->ID, '_verification_code_expires', true);
    
    if (empty($stored_code) || $expires < time()) {
        return new WP_Error('invalid_code', 'Invalid or expired verification code', array('status' => 400));
    }
    
    // Mark user as verified
    update_user_meta($user->ID, '_email_verified', true);
    
    // Clean up
    delete_user_meta($user->ID, '_verification_code');
    delete_user_meta($user->ID, '_verification_code_expires');
    
    return array(
        'success' => true,
        'message' => 'Email verified successfully',
        'user_id' => $user->ID
    );
}

/**
 * Resend verification code
 */
function qounam_resend_verification($request) {
    $email = sanitize_email($request->get_param('email'));
    
    if (empty($email)) {
        return new WP_Error('missing_email', 'Email is required', array('status' => 400));
    }
    
    $user = get_user_by('email', $email);
    
    if (!$user) {
        return new WP_Error('invalid_email', 'No account found with this email', array('status' => 404));
    }
    
    if ( ! email_exists($email) ) {
        return new WP_Error('email_not_exists', 'Email not exists', array('status' => 400));
    }
    $verified = get_user_meta($user->ID, '_email_verified', true);
    if ( $verified ) {
        return new WP_Error('email_verified', 'Email already verified', array('status' => 400));
    }
    // Generate new verification code
    $code = qounam_generate_verification_code();
    qounam_store_verification_code($user->ID, $code);
    
    // Send verification email
    $sent = qounam_send_verification_email($email, $code);
    
    if (!$sent) {
        return new WP_Error('email_failed', 'Failed to send verification email', array('status' => 500));
    }
    
    return array(
        'success' => true,
        'message' => 'Verification code resent successfully'
    );
}

/**
 * Check if user's email is verified
 */
function qounam_is_email_verified($user_id) {
    return (bool) get_user_meta($user_id, '_email_verified', true);
}

/**
 * Check if user needs to verify email before login
 */
function qounam_needs_verification($user) {
    return !qounam_is_email_verified($user->ID);
}
