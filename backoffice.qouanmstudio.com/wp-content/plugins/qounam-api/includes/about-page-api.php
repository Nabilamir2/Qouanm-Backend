<?php
/**
 * About Page API Endpoints
 */

if (!defined('ABSPATH')) {
    exit;
}

// Register page endpoints
add_action('rest_api_init', function() {
    // Get page data
    register_rest_route('qounam/v1', '/about', array(
        'methods' => 'GET',
        'callback' => 'qounam_get_about',
        'permission_callback' => '__return_true'
    ));
});

/**
 * Get page data with ACF fields
 */
function qounam_get_about() {
    // Check if ACF is active
    if (!function_exists('get_field')) {
        return new WP_REST_Response(array(
            'success' => false,
            'message' => 'ACF is not active',
            'data' => array()
        ), 500);
    }

    // Get the page fields group
    $page = get_field('about_fields', 130);
    
    if (!$page) {
        return new WP_REST_Response(array(
            'success' => true,
            'data' => array()
        ));
    }

    // Format the response
    $response = array(
        'hero' => array(
            'label' => $page['label'] ?? '',
            'title' => $page['title'] ?? '',
            'description' => $page['description'] ?? '',
            'first_image' => $page['first_image'] ?? '',
            'second_image' => $page['second_image'] ?? ''
        ),
        'video_section' => array(
            'video' => $page['video'] ?? '',
            'numbers' => !empty($page['numbers']) ? 
                array_map(function($number) {
                    return array(
                        'number' => $number['number'] ?? '',
                        'text' => $number['text'] ?? ''
                    );
                }, $page['numbers']) : []
        ),
        'mission_vision_section' => array(
            'vision_label' => $page['vision_label'] ?? '',
            'vision_description' => $page['vision_description'] ?? '',
            'mission_vision_image' => $page['mission_vision_image'] ?? '',
            'mission_label' => $page['mission_label'] ?? '',
            'mission_description' => $page['mission_description'] ?? '',
        ),
        'values_section' => array(
            'values_label' => $page['values_label'] ?? '',
            'values_title' => $page['values_title'] ?? '',
            'values_description' => $page['values_description'] ?? '',
            'values' => !empty($page['values']) ? 
                array_map(function($value) {
                    return array(
                        'title' => $value['title'] ?? '',
                        'image' => $value['image'] ?? '',
                        'description' => $value['description'] ?? ''
                    );
                }, $page['values']) : []
        )
    );

    return new WP_REST_Response(array(
        'success' => true,
        'data' => $response
    ));
}
