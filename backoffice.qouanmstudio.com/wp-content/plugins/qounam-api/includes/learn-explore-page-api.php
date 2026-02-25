<?php
/**
 * Learn & Explore Page API Endpoints
 */

if (!defined('ABSPATH')) {
    exit;
}

// Register experiential page endpoints
add_action('rest_api_init', function() {
    // Get experiential page data
    register_rest_route('qounam/v1', '/learn-explore', array(
        'methods' => 'GET',
        'callback' => 'qounam_get_learn_explore_page',
        'permission_callback' => '__return_true'
    ));
});

/**
 * Get learn & explore page data with ACF fields
 */
function qounam_get_learn_explore_page() {
    // Check if ACF is active
    if (!function_exists('get_field')) {
        return new WP_REST_Response(array(
            'success' => false,
            'message' => 'ACF is not active',
            'data' => array()
        ), 500);
    }

    $page_id = 284;
    $learn_explore = get_field('learn_&_explore_group', $page_id);
    
    if (!$learn_explore) {
        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'No learn & explore page data found',
            'data' => array()
        ));
    }

    // Format the response
    $response = array(
        'hero' => array(
            'label' => $learn_explore['label'] ?? '',
            'title' => $learn_explore['title'] ?? '',
            'subtitle' => $learn_explore['subtitle'] ?? '',
            'second_title' => $learn_explore['second_title'] ?? '',
            'description' => $learn_explore['description'] ?? '',
            'image' => $learn_explore['image'] ?? ''
        ),
        'features' => array(
            'label' => $learn_explore['features_label'] ?? '',
            'title' => $learn_explore['features_title'] ?? '',
            'image' => $learn_explore['features_image'] ?? '',
            'features' => !empty($learn_explore['features']) ? 
                array_map(function($feature) {
                    return array(
                        'icon' => $feature['icon'] ?? '',
                        'title' => $feature['title'] ?? ''
                    );
                }, $learn_explore['features']) : []
        ),
        'joining_section' => array(
            'label' => $learn_explore['joining_label'] ?? '',
            'title' => $learn_explore['joining_title'] ?? '',
            'image' => $learn_explore['joining_image'] ?? '',
            'steps' => !empty($learn_explore['joining_steps']) ? 
                array_map(function($value) {
                    return array(
                        'title' => $value['text'] ?? '',
                    );
                }, $learn_explore['joining_steps']) : []
        )
    );

    return new WP_REST_Response(array(
        'success' => true,
        'data' => $response
    ));
}