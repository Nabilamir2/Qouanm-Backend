<?php
/**
 * Accreditation Page API Endpoints
 */

if (!defined('ABSPATH')) {
    exit;
}

// Register page endpoints
add_action('rest_api_init', function() {
    // Get page data
    register_rest_route('qounam/v1', '/accreditation', array(
        'methods' => 'GET',
        'callback' => 'qounam_get_accreditation',
        'permission_callback' => '__return_true'
    ));
});

/**
 * Get page data with ACF fields
 */
function qounam_get_accreditation() {
    // Check if ACF is active
    if (!function_exists('get_field')) {
        return new WP_REST_Response(array(
            'success' => false,
            'message' => 'ACF is not active',
            'data' => array()
        ), 500);
    }

    // Get the page fields group
    $page = get_field('accreditation_fields', 626);
    
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
            'subtitle' => $page['subtitle'] ?? ''
        ),
        'leading_section' => array(
            'title' => $page['leading_title'] ?? '',
            'subtitle' => $page['leading_subtitle'] ?? '',
            'description' => $page['leading_description'] ?? ''
        ),
        'accreditation_section' => array(
            'accreditations' => !empty($page['accreditations']) ? 
                array_map(function($accreditation) {
                    return array(
                        'title' => $accreditation['title'] ?? '',
                        'subtitle' => $accreditation['subtitle'] ?? '',
                        'icon' => $accreditation['image'] ?? ''
                    );
                }, $page['accreditations']) : []
        )
    );

    return new WP_REST_Response(array(
        'success' => true,
        'data' => $response
    ));
}
