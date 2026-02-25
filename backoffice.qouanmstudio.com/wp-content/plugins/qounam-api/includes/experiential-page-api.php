<?php
/**
 * Experiential Page API Endpoints
 */

if (!defined('ABSPATH')) {
    exit;
}

// Register experiential page endpoints
add_action('rest_api_init', function() {
    // Get experiential page data
    register_rest_route('qounam/v1', '/experiential', array(
        'methods' => 'GET',
        'callback' => 'qounam_get_experiential_page',
        'permission_callback' => '__return_true'
    ));
});

/**
 * Get experiential page data with ACF fields
 */
function qounam_get_experiential_page() {
    // Check if ACF is active
    if (!function_exists('get_field')) {
        return new WP_REST_Response(array(
            'success' => false,
            'message' => 'ACF is not active',
            'data' => array()
        ), 500);
    }

    $page_id = 205;
    $experiential = get_field('experiential_page_fields', $page_id);
    
    if (!$experiential) {
        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'No experiential page data found',
            'data' => array()
        ));
    }

    $our_programs = get_posts([
        'post_type' => 'program',
        'post_status' => 'publish',
        'numberposts' => 6
    ]);
    // Format the response
    $response = array(
        'hero' => array(
            'label' => $experiential['label'] ?? '',
            'title' => $experiential['title'] ?? '',
            'subtitle' => $experiential['subtitle'] ?? '',
            'second_title' => $experiential['second_title'] ?? '',
            'description' => $experiential['description'] ?? '',
            'image' => $experiential['image'] ?? ''
        ),
        'video' => array(
            'url' => $experiential['video'] ?? ''
        ),
        'why_experiential' => array(
            'label' => $experiential['why_experiential_label'] ?? '',
            'image' => $experiential['why_experiential_image'] ?? '',
            'values' => !empty($experiential['values']) ? 
                array_map(function($value) {
                    return array(
                        'title' => $value['text'] ?? '',
                        'subtitle' => $value['description'] ?? '',
                        'icon' => $value['icon'] ?? ''
                    );
                }, $experiential['values']) : []
        ),
        'how_it_works' => array(
            'label' => $experiential['how_it_works_label'] ?? '',
            'title' => $experiential['how_it_works_title'] ?? '',
            'steps' => !empty($experiential['steps']) ? 
                array_map(function($step, $index) {
                    return array(
                        'number' => $index + 1,
                        'title' => $step['title'] ?? '',
                        'subtitle' => $step['description'] ?? ''
                    );
                }, $experiential['steps'], array_keys($experiential['steps'] ?? [])) : []
        ),
        'our_programs' => array_map(function($program) {
                $program_id = $program->ID;
                return array(
                    'id' => $program_id,
                    'title' => get_the_title($program_id),
                    'slug' => get_post_field('post_name', $program_id),
                    'thumbnail' => get_the_post_thumbnail_url($program_id),
                    'hover_thumbnail' => get_field('hover_thumbnail', $program_id),
                );
        }, $our_programs),
        'moments' => array(
            'label' => $experiential['moments_label'] ?? '',
            'title' => $experiential['moments_title'] ?? '',
            'images' => !empty($experiential['moments']) ? 
                array_map(function($image) {
                    return array(
                        'url' => $image
                    );
                }, $experiential['moments']) : []
        )
    );

    return new WP_REST_Response(array(
        'success' => true,
        'data' => $response
    ));
}