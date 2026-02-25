<?php
/**
 * JWT Authentication for Qounam API
 */

if (!defined('ABSPATH')) {
    exit;
}

// Register JWT endpoints
add_action('rest_api_init', function () {
    // Login endpoint
    register_rest_route('qounam/v1', '/auth/login', array(
        'methods' => 'POST',
        'callback' => 'qounam_login',
        'permission_callback' => '__return_true',
    ));

    // Register endpoint
    register_rest_route('qounam/v1', '/auth/register', array(
        'methods' => 'POST',
        'callback' => 'qounam_register',
        'permission_callback' => '__return_true',
    ));

    // Verify token endpoint
    register_rest_route('qounam/v1', '/auth/verify', array(
        'methods' => 'POST',
        'callback' => 'qounam_verify_token',
        'permission_callback' => '__return_true',
    ));

    // Refresh token endpoint
    register_rest_route('qounam/v1', '/auth/refresh', array(
        'methods' => 'POST',
        'callback' => 'qounam_refresh_token',
        'permission_callback' => '__return_true',
    ));
});

/**
 * Generate JWT token
 */
function qounam_generate_jwt($user_id)
{
    $secret_key = defined('JWT_AUTH_SECRET_KEY') ? JWT_AUTH_SECRET_KEY : 'your-secret-key-change-this';

    $issued_at = time();
    $expire = $issued_at + (30 * DAY_IN_SECONDS); // 30 days

    $payload = array(
        'iss' => get_bloginfo('url'),
        'iat' => $issued_at,
        'exp' => $expire,
        'user_id' => $user_id,
    );

    return qounam_encode_jwt($payload, $secret_key);
}

/**
 * Encode JWT token
 */
function qounam_encode_jwt($payload, $secret)
{
    $header = json_encode(array('typ' => 'JWT', 'alg' => 'HS256'));
    $payload_encoded = json_encode($payload);

    $header_encoded = rtrim(strtr(base64_encode($header), '+/', '-_'), '=');
    $payload_encoded = rtrim(strtr(base64_encode($payload_encoded), '+/', '-_'), '=');

    $signature = hash_hmac('sha256', "$header_encoded.$payload_encoded", $secret, true);
    $signature_encoded = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

    return "$header_encoded.$payload_encoded.$signature_encoded";
}

/**
 * Decode JWT token
 */
function qounam_decode_jwt($token, $secret)
{
    $parts = explode('.', $token);

    if (count($parts) !== 3) {
        return false;
    }

    list($header_encoded, $payload_encoded, $signature_encoded) = $parts;

    $signature = hash_hmac('sha256', "$header_encoded.$payload_encoded", $secret, true);
    $signature_expected = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

    if ($signature_encoded !== $signature_expected) {
        return false;
    }

    $payload = json_decode(base64_decode(strtr($payload_encoded, '-_', '+/')), true);

    if ($payload['exp'] < time()) {
        return false;
    }

    return $payload;
}

/**
 * Login user
 */
function qounam_login($request)
{
    $email = $request->get_param('email');
    $password = $request->get_param('password');

    if (empty($email) || empty($password)) {
        return new WP_Error('missing_credentials', 'Email and password are required', array('status' => 400));
    }

    // Get user by email
    $user = get_user_by('email', $email);

    if (!$user) {
        return new WP_Error('invalid_credentials', 'Invalid email or password', array('status' => 401));
    }

    // Check password
    if (!wp_check_password($password, $user->user_pass, $user->ID)) {
        return new WP_Error('invalid_credentials', 'Invalid email or password', array('status' => 401));
    }

    // Check if email is verified
    require_once plugin_dir_path(__FILE__) . 'verification.php';
    if (qounam_needs_verification($user)) {
        return new WP_Error('email_not_verified', 'Please verify your email address before logging in', array(
            'status' => 403,
            'needs_verification' => true,
            'email' => $user->user_email
        ));
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
    $token = qounam_generate_jwt($user->ID);

    return array(
        'success' => true,
        'token' => $token,
        'user' => array(
            'id' => $user->ID,
            'email' => $user->user_email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'is_verified' => true,
            'government' => get_user_meta($user->ID, 'government', true) ?: '',
            'company' => get_user_meta($user->ID, 'company', true) ?: '',
            'position' => get_user_meta($user->ID, 'position', true) ?: '',
            'phone_number' => get_user_meta($user->ID, 'phone_number', true) ?: '',
            'avatar' => get_avatar_url($user->ID),
            'programs' => array_map(function($program) {
                $program_id = get_field('program', $program->ID);
                return array(
                    'id' => $program_id,
                    'title' => get_the_title($program_id),
                    'slug' => get_post_field('post_name', $program_id),
                    'thumbnail' => get_the_post_thumbnail_url($program_id),
                    'hover_thumbnail' => get_field('hover_thumbnail', $program_id),
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
            'wishlist' => get_user_meta($user->ID, 'wishlist', true) ? array_map(function($program_id) {
                $title = get_the_title($program_id);
                $thumbnail = get_the_post_thumbnail_url($program_id);
                $excerpt = get_the_excerpt($program_id);
                return [
                    'id' => $program_id,
                    'title' => $title ?: '', 
                    'slug' => get_post_field('post_name', $program_id),
                    'excerpt' => $excerpt ?: '',
                    'thumbnail' => $thumbnail ?: '',
                    'hover_thumbnail' => get_field('hover_thumbnail', $program_id),
                    'price' => get_field('price', $program_id),
                    'sale_price' => get_field('sale_price', $program_id),
                    'seats_available' => get_field('seats_available', $program_id),
                    'limited_offer' => get_field('limited_offer', $program_id),
                    'days_per_week' => get_field('days_per_week', $program_id),
                    'duration_in_weeks' => get_field('duration_in_weeks', $program_id),
                    'start_from' => get_field('start_from', $program_id),
                    'ends_at' => get_field('ends_at', $program_id),
                ];
            }, get_user_meta($user->ID, 'wishlist', true) ?? []): [],
        ),
    );
}

/**
 * Check password strength
 */
function qounam_validate_password($password)
{
    $errors = array();

    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long';
    }

    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter';
    }

    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must contain at least one lowercase letter';
    }

    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one number';
    }

    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = 'Password must contain at least one special character';
    }

    return $errors;
}

/**
 * Generate a unique username from email
 */
function qounam_generate_username($email)
{
    $username = sanitize_user(current(explode('@', $email)), true);
    $original_username = $username;
    $i = 1;

    while (username_exists($username)) {
        $username = $original_username . $i;
        $i++;
    }

    return $username;
}

/**
 * Register user
 */
function qounam_register($request)
{
    $email = sanitize_email($request->get_param('email'));
    $password = $request->get_param('password');
    $first_name = sanitize_text_field($request->get_param('first_name'));
    $last_name = sanitize_text_field($request->get_param('last_name'));
    $phone_number = sanitize_text_field($request->get_param('phone_number'));
    $company = sanitize_text_field($request->get_param('company'));
    $position = sanitize_text_field($request->get_param('position'));
    $government = sanitize_text_field($request->get_param('government'));

    // Basic validation
    if (empty($email) || empty($password) || empty($first_name) || empty($last_name) || empty($phone_number)) {
        return new WP_Error('missing_fields', 'Fields are required', array('status' => 400));
    }

    // Validate email format
    if (!is_email($email)) {
        return new WP_Error('invalid_email', 'Please provide a valid email address', array('status' => 400));
    }

    // Check if email already exists
    if (email_exists($email)) {
        return new WP_Error('email_exists', 'An account with this email already exists', array('status' => 400));
    }

    // Validate password strength
    $password_errors = qounam_validate_password($password);
    if (!empty($password_errors)) {
        return new WP_Error('weak_password', implode(', ', $password_errors), array('status' => 400));
    }

    // Generate username from email
    $username = qounam_generate_username($email);

    // Create user
    $user_id = wp_create_user($username, $password, $email);

    if (is_wp_error($user_id)) {
        return new WP_Error('registration_failed', $user_id->get_error_message(), array('status' => 400));
    }

    // Set user meta
    wp_update_user(array(
        'ID' => $user_id,
        'first_name' => $first_name,
        'last_name' => $last_name,
        'display_name' => $first_name . ' ' . $last_name,
        'role' => 'subscriber'
    ));

    // Set user meta
    update_user_meta($user_id, 'phone_number', $phone_number);
    update_user_meta($user_id, 'company', $company);
    update_user_meta($user_id, 'position', $position);
    update_user_meta($user_id, 'government', $government);

    // Generate and store verification code
    require_once plugin_dir_path(__FILE__) . 'verification.php';
    $code = qounam_generate_verification_code();
    qounam_store_verification_code($user_id, $code);

    // Send verification email
    $email_sent = qounam_send_verification_email($email, $code);

    if (!$email_sent) {
        // Log error but don't fail the registration
        error_log('Failed to send verification email to: ' . $email);
    }

    // Get user data
    $user = get_user_by('id', $user_id);

    return array(
        'success' => true,
        'message' => 'Registration successful. Please check your email to verify your account.',
        'needs_verification' => true,
        'user' => array(
            'id' => $user->ID,
            'email' => $user->user_email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'is_verified' => false
        )
    );
}

/**
 * Extract JWT token from Authorization header
 */
function qounam_get_auth_token()
{
    $auth_header = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '';

    // Check for Bearer token in Authorization header
    if (!empty($auth_header) && preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
        return $matches[1];
    }

    // Fallback to token parameter for backward compatibility
    $token = isset($_GET['token']) ? $_GET['token'] : '';

    return $token;
}

/**
 * Verify JWT token
 */
function qounam_verify_token($request)
{
    $token = qounam_get_auth_token();

    // For the verify endpoint, still allow token as a parameter
    if (empty($token)) {
        $token = $request->get_param('token');
    }

    if (empty($token)) {
        return new WP_Error('missing_token', 'Authorization token is required', array('status' => 401));
    }

    $secret_key = defined('JWT_AUTH_SECRET_KEY') ? JWT_AUTH_SECRET_KEY : 'your-secret-key-change-this';
    $payload = qounam_decode_jwt($token, $secret_key);

    if (!$payload) {
        return new WP_Error('invalid_token', 'Invalid or expired token', array('status' => 401));
    }

    $user = get_user_by('id', $payload['user_id']);

    if (!$user) {
        return new WP_Error('user_not_found', 'User not found', array('status' => 404));
    }

    return array(
        'success' => true,
        'user' => array(
            'id' => $user->ID,
            'username' => $user->user_login,
            'email' => $user->user_email,
            'name' => $user->display_name,
            'avatar' => get_avatar_url($user->ID),
        ),
    );
}

/**
 * Refresh JWT token
 */
function qounam_refresh_token($request)
{
    $token = $request->get_param('token');

    if (empty($token)) {
        return new WP_Error('missing_token', 'Token is required', array('status' => 400));
    }

    $secret_key = defined('JWT_AUTH_SECRET_KEY') ? JWT_AUTH_SECRET_KEY : 'your-secret-key-change-this';
    $payload = qounam_decode_jwt($token, $secret_key);

    if (!$payload) {
        return new WP_Error('invalid_token', 'Invalid or expired token', array('status' => 401));
    }

    $new_token = qounam_generate_jwt($payload['user_id']);

    return array(
        'success' => true,
        'token' => $new_token,
    );
}

/**
 * Get current user from JWT token
 */
function qounam_get_current_user_from_jwt()
{
    $token = qounam_get_auth_token();

    if (empty($token)) {
        return null;
    }

    $secret_key = defined('JWT_AUTH_SECRET_KEY') ? JWT_AUTH_SECRET_KEY : 'your-secret-key-change-this';
    $payload = qounam_decode_jwt($token, $secret_key);

    if ($payload) {
        return $payload['user_id'];
    }

    return null;
}

/**
 * Check JWT authentication
 */
function qounam_check_jwt_auth()
{
    $user_id = qounam_get_current_user_from_jwt();
    return !empty($user_id);
}
