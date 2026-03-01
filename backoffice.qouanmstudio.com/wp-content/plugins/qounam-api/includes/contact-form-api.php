<?php
/**
 * Contact Form API Endpoints
 */

if (!defined('ABSPATH')) {
    exit;
}

// Register API endpoints
add_action('rest_api_init', function() {
    // Submit new tailored course request
    register_rest_route('qounam/v1', '/contact-form/request', array(
        'methods' => 'POST',
        'callback' => 'submit_contact_form_request',
        'permission_callback' => '__return_true',
        'args' => array(
            'first_name' => array(
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'last_name' => array(
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'email' => array(
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_email',
                'validate_callback' => 'is_email',
            ),
            'phone_number' => array(
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'inquiry_purpose' => array(
                'required' => false,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'company' => array(
                'required' => false,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'position'  => array(
                'required' => false,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'message' => array(
                'required' => false,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'service_id' => array(
                'required' => false,
                'type' => 'integer',
                'sanitize_callback' => 'sanitize_text_field',
            )
        ),
    ));
});

/**
 * Submit a new contact form request
 */
function submit_contact_form_request($request) {
    // Verify nonce if you're using it
    // if (!wp_verify_nonce($request->get_header('X-WP-Nonce'), 'wp_rest')) {
    //     return new WP_Error('invalid_nonce', 'Invalid nonce', array('status' => 403));
    // }

    // Create new request
    $post_id = wp_insert_post(array(
        'post_title'  => sprintf('Request from %s - %s', 
            $request->get_param('email'), 
            current_time('Y-m-d H:i:s')
        ),
        'post_type'   => 'quotation_request',
        'post_status' => 'publish',
        'meta_input'  => array(
            'first_name'         => $request->get_param('first_name'),
            'last_name'         => $request->get_param('last_name'),
            'email'             => $request->get_param('email'),
            'phone_number'             => $request->get_param('phone_number'),
            'inquiry_purpose'    => $request->get_param('inquiry_purpose'),
            'company'           => $request->get_param('company'),
            'position'          => $request->get_param('position'),
            'message'           => $request->get_param('message'),
            'service_id'        => $request->get_param('service_id'),
        ),
    ));

    if (is_wp_error($post_id)) {
        return new WP_Error(
            'submission_failed', 
            'Failed to submit your request. Please try again.', 
            array('status' => 500)
        );
    }

    // Send notification email (optional)
    $to = get_option('admin_email');
    $subject = 'New Contact Form Request: ' . $request->get_param('email');
    $message = "A new contact form request has been submitted:<br>";
    $message .= "Name: " . $request->get_param('first_name') . "<br>";
    $message .= "Last Name: " . $request->get_param('last_name') . "<br>";
    $message .= "Email: " . $request->get_param('email') . "<br>";
    $message .= "Phone Number: " . $request->get_param('phone_number') . "<br>";
    $message .= "Message: " . $request->get_param('message') . "<br><br>";
    $headers = array('Content-Type: text/html; charset=UTF-8');
    wp_mail($to, $subject, $message, $headers);

    return new WP_REST_Response(array(
        'success' => true,
        'message' => 'Your request has been submitted successfully. We will contact you soon.',
        'request_id' => $post_id,
    ), 201);
}
