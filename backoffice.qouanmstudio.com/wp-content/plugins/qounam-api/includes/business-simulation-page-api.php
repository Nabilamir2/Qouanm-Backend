<?php
/**
 * Business Simulation Page API Endpoints
 */

if (!defined('ABSPATH')) {
    exit;
}

// Register business simulation page endpoints
add_action('rest_api_init', function() {
    // Get business simulation page data
    register_rest_route('qounam/v1', '/business-simulation', array(
        'methods' => 'GET',
        'callback' => 'qounam_get_business_simulation_page',
        'permission_callback' => '__return_true'
    ));
});

/**
 * Get business simulation page data with ACF fields
 */
function qounam_get_business_simulation_page() {
    // Check if ACF is active
    if (!function_exists('get_field')) {
        return new WP_REST_Response(array(
            'success' => false,
            'message' => 'ACF is not active',
            'data' => array()
        ), 500);
    }

    $page_id = 238;
    $business_simulation = get_field('business_simulation_group', $page_id);
    
    if (!$business_simulation) {
        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'No business simulation page data found',
            'data' => array()
        ));
    }

    // Format the response
    $response = array(
        'hero' => array(
            'label' => $business_simulation['label'] ?? '',
            'title' => $business_simulation['title'] ?? '',
            'subtitle' => $business_simulation['subtitle'] ?? '',
            'second_title' => $business_simulation['second_title'] ?? '',
            'description' => $business_simulation['description'] ?? '',
            'image' => $business_simulation['image'] ?? ''
        ),
        'features' => array(
            'label' => $business_simulation['features_label'] ?? '',
            'title' => $business_simulation['features_title'] ?? '',
            'features' => !empty($business_simulation['features']) ? 
                array_map(function($feature) {
                    return array(
                        'icon' => $feature['icon'] ?? '',
                        'title' => $feature['title'] ?? ''
                    );
                }, $business_simulation['features']) : []
        ),
        'joining_section' => array(
            'label' => $business_simulation['joining_label'] ?? '',
            'title' => $business_simulation['joining_title'] ?? '',
            'image' => $business_simulation['joining_image'] ?? '',
            'steps' => !empty($business_simulation['joining_steps']) ? 
                array_map(function($value) {
                    return array(
                        'text' => $value['text'] ?? '',
                    );
                }, $business_simulation['joining_steps']) : []
        )
    );

    return new WP_REST_Response(array(
        'success' => true,
        'data' => $response
    ));
}