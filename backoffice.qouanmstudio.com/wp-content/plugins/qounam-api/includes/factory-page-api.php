<?php
/**
 * Factory Page API Endpoints
 */

if (!defined('ABSPATH')) {
    exit;
}

// Register page endpoints
add_action('rest_api_init', function() {
    // Get page data
    register_rest_route('qounam/v1', '/factory', array(
        'methods' => 'GET',
        'callback' => 'qounam_get_factory',
        'permission_callback' => '__return_true'
    ));
});

/**
 * Get page data with ACF fields
 */
function qounam_get_factory() {
    // Check if ACF is active
    if (!function_exists('get_field')) {
        return new WP_REST_Response(array(
            'success' => false,
            'message' => 'ACF is not active',
            'data' => array()
        ), 500);
    }

    // Get the page fields group
    $page = get_field('factory_fields', 441);
    
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
            'subtitle' => $page['description'] ?? '',
            'image' => $page['image'] ?? '',
            'numbers' => !empty($page['numbers']) ? 
                array_map(function($number) {
                    return array(
                        'number' => $number['number'] ?? '',
                        'text' => $number['text'] ?? ''
                    );
            }, $page['numbers']) : []
        ),
        'design_section' => array(
            'label' => $page['design_label'] ?? '',
            'title' => $page['design_title'] ?? '',
            'description' => $page['design_description'] ?? '',
            'second_title' => $page['design_second_title'] ?? '',
            'second_description' => $page['design_second_description'] ?? '',
            'image' => $page['design_image'] ?? '',
            'timelines' => !empty($page['timelines']) ? 
                array_map(function($number) {
                    return array(
                        'icon' => $number['icon'] ?? '',
                        'title' => $number['title'] ?? '',
                        'description' => $number['description'] ?? '',
                        'image' => $number['image'] ?? ''
                    );
                }, $page['timelines']) : []
            
        ),
        'process_section' => array(
            'title' => $page['process_title'],
            'subtitle' => $page['process_subtitle'],
            'points' => !empty($page['points']) ?
                array_map(function ($feature) {
                    return array(
                        'title' => $feature['title'] ?? '',
                        'subtitle' => !empty($feature['subpoints']) ? 
                            array_map(function($subpoint) {
                                return $subpoint['text'];
                        }, $feature['subpoints']) : [],
                    );
                }, $page['points']) : [],
            'gallery' => !empty($page['gallery']) ? 
                array_map(function($image_url) {
                    return $image_url ?? ''; 
                }, $page['gallery']) : []
        ),
        'sustain_section' => array(
            'label' => $page['sustain_label'] ?? '',
            'title' => $page['sustain_title'] ?? '',
            'description' => $page['sustain_description'] ?? '',
            'image' => $page['sustain_image'] ?? '',
        ),
        'awards_section' => array(
            'title' => $page['awards_title'] ?? '',
            'awards' => !empty($page['awards']) ? 
                array_map(function($value) {
                    return array(
                        'image' => $value['image'] ?? '',
                    );
                }, $page['awards']) : []
        ),
    );

    return new WP_REST_Response(array(
        'success' => true,
        'data' => $response
    ));
}
