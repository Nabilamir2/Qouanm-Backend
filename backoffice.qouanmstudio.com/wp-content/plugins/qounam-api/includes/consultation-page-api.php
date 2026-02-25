<?php
/**
 * Consultation Page API Endpoints
 */

if (!defined('ABSPATH')) {
    exit;
}

// Register consultation page endpoints
add_action('rest_api_init', function() {
    // Get consultation page data
    register_rest_route('qounam/v1', '/consultation', array(
        'methods' => 'GET',
        'callback' => 'qounam_get_consultation_page',
        'permission_callback' => '__return_true'
    ));
});

/**
 * Get consultation page data with ACF fields
 */
function qounam_get_consultation_page() {
    // Check if ACF is active
    if (!function_exists('get_field')) {
        return new WP_REST_Response(array(
            'success' => false,
            'message' => 'ACF is not active',
            'data' => array()
        ), 500);
    }

    $page_id = 287;
    $consultation = get_field('consultation_group', $page_id);
    
    if (!$consultation) {
        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'No consultation page data found',
            'data' => array()
        ));
    }

    // Format the response
    $response = array(
        'hero' => array(
            'label' => $consultation['label'] ?? '',
            'title' => $consultation['title'] ?? '',
            'subtitle' => $consultation['subtitle'] ?? '',
            'second_title' => $consultation['second_title'] ?? '',
            'description' => $consultation['description'] ?? '',
            'image' => $consultation['image'] ?? ''
        ),
        'features' => array(
            'label' => $consultation['features_label'] ?? '',
            'title' => $consultation['features_title'] ?? '',
            'features' => !empty($consultation['features']) ? 
                array_map(function($feature) {
                    return array(
                        'icon' => $feature['icon'] ?? '',
                        'title' => $feature['title'] ?? '',
                        'subtitle' => $feature['description'] ?? '',
                    );
                }, $consultation['features']) : []
        ),
        'joining_section' => array(
            'label' => $consultation['joining_label'] ?? '',
            'title' => $consultation['joining_title'] ?? '',
            'subtitle' => $consultation['joining_subtitle'] ?? '',
            'image' => $consultation['joining_image'] ?? '',
            'steps' => !empty($consultation['joining_steps']) ? 
                array_map(function($value) {
                    return array(
                        'title' => $value['text'] ?? '',
                    );
                }, $consultation['joining_steps']) : []
        )
    );

    return new WP_REST_Response(array(
        'success' => true,
        'data' => $response
    ));
}