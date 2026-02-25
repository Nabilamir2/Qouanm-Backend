<?php
/**
 * Facilities Page API Endpoints
 */

if (!defined('ABSPATH')) {
    exit;
}

// Register facilities page endpoints
add_action('rest_api_init', function() {
    // Get facilities page data
    register_rest_route('qounam/v1', '/facilities', array(
        'methods' => 'GET',
        'callback' => 'qounam_get_facilities_page',
        'permission_callback' => '__return_true'
    ));
});

/**
 * Get facilities page data with ACF fields
 */
function qounam_get_facilities_page() {
    // Check if ACF is active
    if (!function_exists('get_field')) {
        return new WP_REST_Response(array(
            'success' => false,
            'message' => 'ACF is not active',
            'data' => array()
        ), 500);
    }

    $page_id = 550;
    $facilities = get_field('facilities_page_fields', $page_id);
    
    if (!$facilities) {
        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'No facilities page data found',
            'data' => array()
        ));
    }

    $filters = [];
    $rooms   = [];

    if (!empty($facilities['training_rooms']) && is_array($facilities['training_rooms'])) {

        $filters[] = [
            'title' => 'All Rooms',
            'ref'   => 'all-rooms',
        ];

        foreach ($facilities['training_rooms'] as $single) {

            $title = trim($single['title'] ?? '');
            $ref   = $title ? sanitize_title($title) : 'room';

            $filters[] = [
                'title' => $title,
                'ref'   => $ref,
            ];

            $rooms[] = [
                'ref'     => $ref,
                'title'   => $title,
                'gallery' => $single['gallery'] ?? [],
            ];
        }
    }

    // Format the response
    $response = array(
        'hero' => array(
            'label' => $facilities['label'] ?? '',
            'title' => $facilities['title'] ?? '',
            'subtitle' => $facilities['subtitle'] ?? ''
        ),
        'experience' => array(
            'title' => $facilities['experience_title'] ?? '',
            'subtitle' => $facilities['experience_description'] ?? '',
            'image' => $facilities['experience_image'] ?? '',
            'facilities' => !empty($facilities['facilities']) ? 
                array_map(function($value) {
                    return array(
                        'title' => $value['title'] ?? '',
                        'icon' => $value['icon'] ?? ''
                    );
                }, $facilities['facilities']) : []
        ),
        'training_rooms' => array(
            'label' => $facilities['training_label'] ?? '',
            'title' => $facilities['training_title'] ?? '',
            'data'  => array(
                'filters' => $filters,
                'rooms'   => $rooms,
            ),
        )
    );

    return new WP_REST_Response(array(
        'success' => true,
        'data' => $response
    ));
}