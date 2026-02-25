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
        'hero' => array(
            'label' => $homepage['label'] ?? '',
            'title' => $homepage['title'] ?? '',
            'subtitle' => $homepage['subtitle'] ?? '',
            'image' => $homepage['image'] ?? '',
            'video' => $homepage['video'] ?? ''
        ),
        'explore' => array(
            'label' => $homepage['explore_label'] ?? '',
            'title' => $homepage['explore_title'] ?? '',
            'subtitle' => $homepage['explore_subtitle'] ?? '',
            'programs' => !empty($homepage['programs']) ? 
                array_map(function($program) {
                    return array(
                        'id' => $program->ID,
                        'title' => $program->post_title,
                        'slug' => $program->post_name,
                        'thumbnail' => get_the_post_thumbnail_url($program->ID),
                        'hover_thumbnail' => get_field('hover_thumbnail', $program->ID),
                        'category' => get_terms(array(
                            'taxonomy' => 'program-category',
                            'hide_empty' => true,
                            'object_ids' => $program->ID
                        ))[0]->name,
                        'days_per_week' => get_field('days_per_week', $program->ID),
                        'duration_in_weeks' => get_field('duration_in_weeks', $program->ID),
                        'start_from' => get_field('start_from', $program->ID),
                        'price' => get_field('price', $program->ID),
                        'sale_price' => get_field('sale_price', $program->ID),
                        'image' => get_the_post_thumbnail_url($program->ID),
                        'seats_available' => get_field('seats_available', $program->ID),
                        'limited_offer' => get_field('limited_offer', $program->ID)
                    );
                }, $homepage['programs']) : []
        ),
        'overview' => array(
            'title' => $homepage['overview_title'] ?? '',
            'description' => $homepage['overview_description'] ?? '',
            'first_small_image' => $homepage['first_small_image'] ?? '',
            'second_small_image' => $homepage['second_small_image'] ?? '',
            'red_line_text' => !empty($homepage['red_line_text']) ? 
                array_map(function($text) {
                    return array(
                        'word' => $text['word'] ?? ''
                    );
                }, $homepage['red_line_text']) : [],
        ),
        'discover' => array(
            'label' => $homepage['overview_label'] ?? '',
            'second_title' => $homepage['overview_second_title'] ?? '',
            'second_description' => $homepage['overview_second_description'] ?? '',
            'pages' => !empty($homepage['overview_pages']) ? 
                array_map(function($page) {
                    return array(
                        'id' => $page->ID,
                        'title' => $page->post_title,
                        'slug' => $page->post_name,
                        'excerpt' => get_the_excerpt($page->ID),
                        'image' => get_the_post_thumbnail_url($page->ID)
                    );
                }, $homepage['overview_pages']) : []
        ),
        'facilities' => array(
            'label' => $homepage['facilities_label'] ?? '',
            'title' => $homepage['facilities_title'] ?? '',
            'facilities' => array(
                array(
                    'title' => $homepage['facility_title_1'] ?? '',
                    'description' => $homepage['facility_description_1'] ?? ''
                ),
                array(
                    'title' => $homepage['facility_title_2'] ?? '',
                    'description' => $homepage['facility_description_2'] ?? ''
                ),
                array(
                    'title' => $homepage['facility_title_3'] ?? '',
                    'description' => $homepage['facility_description_3'] ?? ''
                )
            ),
            'small_image' => $homepage['facilities_small_image'] ?? '',
            'large_image' => $homepage['facilities_large_image'] ?? ''
        ),
        'news' => array(
            'label' => $homepage['news_label'] ?? '',
            'title' => $homepage['news_title'] ?? '',
            'posts' => !empty($homepage['news']) ? 
                array_map(function($post) {
                    return array(
                        'id' => $post->ID,
                        'title' => $post->post_title,
                        'excerpt' => get_the_excerpt($post->ID),
                        'slug' => $post->post_name,
                        'date' => get_the_date('', $post->ID),
                        'image' => get_the_post_thumbnail_url($post->ID, 'large')
                    );
                }, $homepage['news']) : []
        ),
        'partners' => array(
            'label' => $homepage['partners_label'] ?? '',
            'title' => $homepage['partners_title'] ?? '',
            'logos' => !empty($homepage['partners']) ? 
                array_map(function($partner) {
                    return array(
                        'image' => $partner['image'] ?? ''
                    );
                }, $homepage['partners']) : []
        ),
        'accreditation' => array(
            'label' => $homepage['accreditation_label'] ?? '',
            'title' => $homepage['accreditation_title'] ?? '',
            'accreditations' => !empty($homepage['accreditations']) ? 
                array_map(function($logo) {
                    return array(
                        'title' => $logo['title'] ?? '',
                        'subtitle' => $logo['subtitle'] ?? '',
                        'icon' => $logo['icon'] ?? ''
                    );
                }, $homepage['accreditations']) : []
        )

    );

    return new WP_REST_Response(array(
        'success' => true,
        'data' => $response
    ));
}
