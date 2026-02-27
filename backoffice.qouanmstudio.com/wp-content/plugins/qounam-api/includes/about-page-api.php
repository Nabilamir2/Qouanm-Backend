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
    $page = get_field('about_fields', 137);
    
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
            'listFeatures' => !empty($page['gallery']) ? 
                array_map(function($image_url) {
                    return array(
                            $image_url                       
                    );
                }, $page['gallery']) : []
        ),
        'our_story_section' => array(
            'video' => $page['video'] ?? '',
            'label' => $page['story_label'] ?? '',
            'title' => $page['story_title'] ?? '',
            'listParagraphs' => $page['story_description'] ?? '',
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
        'mission_section' => array(
            'label' => $page['mission_label'] ?? '',
            'title' => $page['mission_title'] ?? '',
            'image' => $page['mission_image'] ?? '',
        ),
        'vision_section' => array(
            'label' => $page['vision_label'] ?? '',
            'title' => $page['vision_description'] ?? '',
            'image' => $page['vision_image'] ?? '',

        ),
        'team_section' => array(
            'title' => $page['team_title'] ?? '',
            'subtitle' => $page['team_subtitle'] ?? '',
            'team' => !empty($page['team']) ? 
                array_map(function($value) {
                    return array(
                        'name' => $value['name'] ?? '',
                        'image' => $value['image'] ?? '',
                        'title' => $value['position'] ?? ''
                    );
                }, $page['team']) : []
        ),
        // 'mission_section' => array(
        //     'mission_label' => $page['mission_label'] ?? '',
        //     'mission_title' => $page['mission_title'] ?? '',
        //     'mission_image' => $page['mission_image'] ?? '',
        //     'mission' => !empty($page['mission']) ? 
        //         array_map(function($value) {
        //             return array(
        //                 'title' => $value['title'] ?? '',
        //                 'image' => $value['image'] ?? '',
        //                 'description' => $value['description'] ?? ''
        //             );
        //         }, $page['values']) : []
        // ),
    );

    return new WP_REST_Response(array(
        'success' => true,
        'data' => $response
    ));
}
