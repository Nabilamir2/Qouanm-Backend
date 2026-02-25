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
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'message' => array(
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            )
        ),
    ));

    // Get all tailored course requests (admin only)
    // register_rest_route('qounam/v1', '/tailored-course/requests', array(
    //     'methods' => 'GET',
    //     'callback' => 'get_tailored_course_requests',
    //     'permission_callback' => function() {
    //         return current_user_can('edit_others_posts');
    //     },
    //     'args' => array(
    //         'per_page' => array(
    //             'default' => 10,
    //             'sanitize_callback' => 'absint',
    //         ),
    //         'page' => array(
    //             'default' => 1,
    //             'sanitize_callback' => 'absint',
    //         ),
    //         'status' => array(
    //             'default' => 'publish',
    //             'sanitize_callback' => 'sanitize_text_field',
    //             'validate_callback' => function($param) {
    //                 return in_array($param, array('publish', 'pending', 'draft', 'trash', 'any'));
    //             }
    //         ),
    //     ),
    // ));
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
        'post_type'   => 'contact-form-reqs',
        'post_status' => 'publish',
        'meta_input'  => array(
            'first_name'         => $request->get_param('first_name'),
            'last_name'         => $request->get_param('last_name'),
            'email'             => $request->get_param('email'),
            'phone_number'             => $request->get_param('phone_number'),
            'inquiry_purpose'    => $request->get_param('inquiry_purpose'),
            'message'           => $request->get_param('message')
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
    $message .= "Inquiry Purpose: " . $request->get_param('inquiry_purpose') . "<br>";
    $message .= "Message: " . $request->get_param('message') . "<br><br>";
    $headers = array('Content-Type: text/html; charset=UTF-8');
    wp_mail($to, $subject, $message, $headers);

    return new WP_REST_Response(array(
        'success' => true,
        'message' => 'Your request has been submitted successfully. We will contact you soon.',
        'request_id' => $post_id,
    ), 201);
}

/**
 * Get all tailored course requests (admin only)
 */
// function get_tailored_course_requests($request) {
//     $per_page = $request->get_param('per_page');
//     $page = $request->get_param('page');
//     $status = $request->get_param('status');
    
//     $args = array(
//         'post_type'      => 'tailored_course_request',
//         'posts_per_page' => $per_page,
//         'paged'          => $page,
//         'post_status'    => $status === 'any' ? 'any' : $status,
//         'orderby'        => 'date',
//         'order'          => 'DESC',
//     );
    
//     $query = new WP_Query($args);
//     $requests = array();
    
//     if ($query->have_posts()) {
//         while ($query->have_posts()) {
//             $query->the_post();
//             $post_id = get_the_ID();
            
//             $requests[] = array(
//                 'id' => $post_id,
//                 'title' => get_the_title(),
//                 'date' => get_the_date('Y-m-d H:i:s'),
//                 'status' => get_post_status(),
//                 'fields' => array(
//                     'first_name'         => get_post_meta($post_id, 'first_name', true),
//                     'last_name'         => get_post_meta($post_id, 'last_name', true),
//                     'email'             => get_post_meta($post_id, 'email', true),
//                     'phone_number'             => get_post_meta($post_id, 'phone_number', true),
//                     'company'           => get_post_meta($post_id, 'company', true),
//                     'position'          => get_post_meta($post_id, 'position', true),
//                     'course_objectives'    => get_post_meta($post_id, 'course_objectives', true),
//                     'government'    => get_post_meta($post_id, 'government', true),
//                 )
//             );
//         }
//     }
    
//     wp_reset_postdata();
    
//     return new WP_REST_Response(array(
//         'success' => true,
//         'data' => array(
//             'requests' => $requests,
//             'pagination' => array(
//                 'total' => (int) $query->found_posts,
//                 'pages' => (int) $query->max_num_pages,
//                 'current_page' => (int) $page,
//                 'per_page' => (int) $per_page,
//             )
//         )
//     ));
// }
