<?php
/**
 * E-Learning Page API Endpoints
 */

if (!defined('ABSPATH')) {
    exit;
}

// Register e-learning page endpoints
add_action('rest_api_init', function() {
    // Get e-learning page data
    register_rest_route('qounam/v1', '/e-learning', array(
        'methods' => 'GET',
        'callback' => 'qounam_get_elearning_page',
        'permission_callback' => '__return_true'
    ));
});

/**
 * Get e-learning page data with ACF fields
 */
function qounam_get_elearning_page() {
    // Check if ACF is active
    if (!function_exists('get_field')) {
        return new WP_REST_Response(array(
            'success' => false,
            'message' => 'ACF is not active',
            'data' => array()
        ), 500);
    }

    $page_id = 187;
    $elearning = get_field('e_learning_page_fields', $page_id);
    
    if (!$elearning) {
        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'No e-learning page data found',
            'data' => array()
        ));
    }

    // Format the response
    $response = array(
        'hero' => array(
            'label' => $elearning['label'] ?? '',
            'title' => $elearning['title'] ?? '',
            'subtitle' => $elearning['subtitle'] ?? '',
            'second_title' => $elearning['second_title'] ?? '',
            'description' => $elearning['description'] ?? '',
            'image' => $elearning['image'] ?? ''
        ),
        'benefits' => array(
            'label' => $elearning['benefits_label'] ?? '',
            'title' => $elearning['benefits_title'] ?? '',
            'items' => !empty($elearning['benefits']) ? 
                array_map(function($benefit) {
                    return array(
                        'title' => $benefit['title'] ?? '',
                        'subtitle' => $benefit['subtitle'] ?? '',
                        'icon' => $benefit['image'] ?? ''
                    );
                }, $elearning['benefits']) : []
        )
    );

    return new WP_REST_Response(array(
        'success' => true,
        'data' => $response
    ));
}