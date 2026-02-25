<?php
/**
 * Homepage API Endpoints
 */

if (!defined('ABSPATH')) {
    exit;
}

// Register homepage endpoints
add_action('rest_api_init', function() {
    // Get homepage data
    register_rest_route('qounam/v1', '/homepage', array(
        'methods' => 'GET',
        'callback' => 'qounam_get_homepage',
        'permission_callback' => '__return_true'
    ));
});

/**
 * Get homepage data with ACF fields
 */
function qounam_get_homepage() {
    // Check if ACF is active
    if (!function_exists('get_field')) {
        return new WP_REST_Response(array(
            'success' => false,
            'message' => 'ACF is not active',
            'data' => array()
        ), 500);
    }

    // Get the homepage fields group
    $homepage = get_field('homepage_fields', 69);
    
    if (!$homepage) {
        return new WP_REST_Response(array(
            'success' => true,
            'data' => array()
        ));
    }

    // Format the response
    $response = array(
        'hero_section' => array(
            'title' => $homepage['title'] ?? '',
            'subtitle' => $homepage['subtitle'] ?? '',
            'description' => $homepage['description'] ?? '',
            'image' => $homepage['image'] ?? '',
            'listOfFeatures' => !empty($homepage['features']) ? 
                array_map(function($text) {
                    return array(
                        $text['title'] ?? ''
                    );
                }, $homepage['features']) : [],
        ),
        'about_section' => array(
            'title' => $homepage['about_title'] ?? '',
            'subtitle' => $homepage['about_subtitle'] ?? '',
            'image' => $homepage['about_image'] ?? '',
        ),
        'solutions_section' => array(
            'title' => $homepage['solutions_title'] ?? '',
            'subtitle' => $homepage['solutions_subtitle'] ?? '',
            'solutions' => !empty($homepage['solutions']) ? 
                array_map(function($text) {
                    return array(
                        'title' => $text['title'] ?? '',
                        'description' => $text['subtitle'] ?? '',
                        'icon' => $text['icon'] ?? ''
                    );
                }, $homepage['solutions']) : [],
        ),
        'services_section' => array(
            'title' => $homepage['services_title'] ?? '',
            'subtitle' => $homepage['services_subtitle'] ?? '',
            'services' => !empty($homepage['services']) ? 
                array_map(function($service) {
                    return array(
                        'title' => $service['title'],
                        'description' => $service['description'],
                        'gallery' => array_map(function($image_url) {
                            return array(
                                $image_url,
                            );
                        }, $service['gallery']),
                        'statistics' => array_map(function($statistics) {
                            return array(
                                'title' => $statistics['title'] ?? '',
                                'subtitle' => $statistics['subtitle'] ?? ''
                            );
                        }, $service['statistics']),
                    );
                }, $homepage['services']) : []
        ),
        'partners' => array(
                'logos' => array_map(function($logo) {
                            return array(
                                $logo['image'],
                            );
                        }, $homepage['logos']),
        ),
        'ceo_section' => array(
            'image' => $homepage['ceo_image'] ?? '',
            'title' => $homepage['ceo_title'] ?? '',
            'description' => $homepage['ceo_description'] ?? '',
            'signature' => $homepage['ceo_signature'] ?? '',
            'position' => $homepage['position'] ?? ''
        ),
        'projects_section' => array(
            'title' => $homepage['projects_title'] ?? '',
            'subtitle' => $homepage['projects_subtitle'] ?? '',
            'projects' => !empty($homepage['projects']) ? 
                array_map(function($project) {
                    $project_id = $project['project'];
                    return array(
                        'image' => $project['image'] ?? '',
                        'logo' => get_the_post_thumbnail_url($project_id) ?? '',
                        'title' => get_the_title($project_id) ?? '',
                        'excerpt' => get_the_excerpt($project_id) ?? '',
                        'rooms_design' => get_field('rooms_design',$project_id) ?? '',
                        'furniture_units' => get_field('furniture_units',$project_id) ?? '',
                        'weeks' => get_field('weeks',$project_id) ?? '',
                        'slug' => get_post_field('post_name', $project_id) ?? '',
                    );
                }, $homepage['projects']) : []
        )

    );

    return new WP_REST_Response(array(
        'success' => true,
        'data' => $response
    ));
}
