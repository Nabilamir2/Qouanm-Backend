<?php
/**
 * Success Stories API Endpoints
 */

if (!defined('ABSPATH')) {
    exit;
}

// Register success stories endpoints
add_action('rest_api_init', function() {
    // Get all success stories
    register_rest_route('qounam/v1', '/success-stories', array(
        'methods' => 'GET',
        'callback' => 'qounam_get_success_stories',
        'permission_callback' => '__return_true'
    ));
});

/**
 * Get all success stories with ACF fields
 */
function qounam_get_success_stories() {
    // Check if ACF is active
    if (!function_exists('get_field')) {
        return new WP_REST_Response(array(
            'success' => false,
            'message' => 'ACF is not active',
            'data' => array()
        ), 500);
    }

    // Get the success stories group
    $stories_section = get_field('success_stories', 'option');
    
    if (!$stories_section) {
        return new WP_REST_Response(array(
            'success' => true,
            'data' => array(
                'title' => '',
                'stories' => array()
            )
        ));
    }

    // Format the response
    $response = array(
        'title' => $stories_section['title'] ?? '',
        'stories' => array()
    );

    // Process stories if they exist
    if (!empty($stories_section['stories'])) {
        foreach ($stories_section['stories'] as $index => $story) {
            $response['stories'][] = array(
                'id' => $index + 1,
                'text' => $story['text'] ?? '',
                'image' => $story['image'] ?? '',
                'name' => $story['name'] ?? ''
            );
        }
    }

    return new WP_REST_Response(array(
        'success' => true,
        'data' => $response
    ));
}
