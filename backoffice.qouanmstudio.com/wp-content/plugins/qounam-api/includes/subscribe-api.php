<?php
/**
 * Subscribe API Endpoints
 */

if (!defined('ABSPATH')) {
    exit;
}

// Register API endpoints
add_action('rest_api_init', function() {
    // Subscribe endpoint
    register_rest_route('qounam/v1', '/subscribe', array(
        'methods' => 'POST',
        'callback' => 'submit_subscription',
        'permission_callback' => '__return_true',
        'args' => array(
            'email' => array(
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_email',
                'validate_callback' => 'is_email',
            ),
        ),
    ));

    // Unsubscribe endpoint
    register_rest_route('qounam/v1', '/unsubscribe', array(
        'methods' => 'POST',
        'callback' => 'unsubscribe_user',
        'permission_callback' => '__return_true',
        'args' => array(
            'email' => array(
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_email',
                'validate_callback' => 'is_email',
            ),
        ),
    ));
});

/**
 * Submit a new subscription
 */
function submit_subscription($request) {
    // Check if email already exists
    $existing_subscriber = get_posts(array(
        'post_type' => 'subscriber',
        'meta_query' => array(
            array(
                'key' => 'email',
                'value' => $request->get_param('email'),
                'compare' => '='
            )
        ),
        'posts_per_page' => 1,
    ));

    if (!empty($existing_subscriber)) {
        return new WP_Error(
            'already_subscribed',
            'This email is already subscribed to our newsletter.',
            array('status' => 400)
        );
    }

    // Create new subscriber post
    $post_id = wp_insert_post(array(
        'post_title'  => sprintf('Subscriber: %s', $request->get_param('email')),
        'post_type'   => 'subscriber',
        'post_status' => 'publish',
        'meta_input'  => array(
            'email'             => $request->get_param('email'),
        ),
    ));

    if (is_wp_error($post_id)) {
        return new WP_Error(
            'subscription_failed',
            'Failed to submit your subscription. Please try again.',
            array('status' => 500)
        );
    }

    // Send confirmation email
    $email_sent = qounam_send_welcome_email($request->get_param('email'), $post_id);

    // Send admin notification
    qounam_send_admin_subscription_notification($request, $post_id);

    return new WP_REST_Response(array(
        'success' => true,
        'message' => 'Thank you for subscribing! Please check your email for confirmation.',
        'subscriber_id' => $post_id,
        'email_sent' => $email_sent,
    ), 201);
}

/**
 * Get all subscribers (admin only)
 */
function get_subscribers($request) {
    $per_page = $request->get_param('per_page');
    $page = $request->get_param('page');
    $status = $request->get_param('status');
    $search = $request->get_param('search');

    $args = array(
        'post_type'      => 'subscriber',
        'posts_per_page' => $per_page,
        'paged'          => $page,
        'post_status'    => $status === 'any' ? 'any' : $status,
        'orderby'        => 'date',
        'order'          => 'DESC',
    );

    if (!empty($search)) {
        $args['s'] = $search;
    }

    $query = new WP_Query($args);
    $subscribers = array();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();

            $subscribers[] = array(
                'id' => $post_id,
                'title' => get_the_title(),
                'date' => get_the_date('Y-m-d H:i:s'),
                'status' => get_post_status(),
                'fields' => array(
                    'email'             => get_post_meta($post_id, 'email', true),
                )
            );
        }
    }

    wp_reset_postdata();

    return new WP_REST_Response(array(
        'success' => true,
        'data' => array(
            'subscribers' => $subscribers,
            'pagination' => array(
                'total' => (int) $query->found_posts,
                'pages' => (int) $query->max_num_pages,
                'current_page' => (int) $page,
                'per_page' => (int) $per_page,
            )
        )
    ));
}

/**
 * Unsubscribe user
 */
function unsubscribe_user($request) {
    $email = $request->get_param('email');

    // Find subscriber by email
    $subscriber = get_posts(array(
        'post_type' => 'subscriber',
        'meta_query' => array(
            array(
                'key' => 'email',
                'value' => $email,
                'compare' => '='
            )
        ),
        'posts_per_page' => 1,
    ));

    if (empty($subscriber)) {
        return new WP_Error(
            'subscriber_not_found',
            'No subscription found with this email address.',
            array('status' => 404)
        );
    }

    $subscriber_id = $subscriber[0]->ID;

    // Optionally move to trash
    wp_update_post(array(
        'ID' => $subscriber_id,
        'post_status' => 'trash'
    ));

    return new WP_REST_Response(array(
        'success' => true,
        'message' => 'You have been successfully unsubscribed from our newsletter.',
    ));
}



function qounam_send_admin_subscription_notification($request, $subscriber_id) {
    $to = get_option('admin_email');
    $subject = 'New Newsletter Subscription: ' . $request->get_param('email');

    $message = "A new user has subscribed to the newsletter:<br>";
    $message .= "Email: " . $request->get_param('email') . "<br>";
    $headers = array('Content-Type: text/html; charset=UTF-8');
    wp_mail($to, $subject, $message, $headers);
}

/**
 * Send welcome email after confirmation
 */
function qounam_send_welcome_email($email, $subscriber_id) {
    $subject = 'Welcome to ' . get_bloginfo('name') . ' Newsletter!';

    $message = "Welcome to our newsletter!<br><br>";
    $message .= "Thank you for confirming your subscription. You will now receive our latest updates, news, and training opportunities.<br><br>";
    $message .= "If you have any questions or need to update your preferences, please don't hesitate to contact us.<br><br>";
    $message .= "Best regards,<br>";
    $message .= get_bloginfo('name') . " Team<br>";
 
    return wp_mail($email, $subject, $message);
}
