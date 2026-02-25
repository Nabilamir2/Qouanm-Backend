<?php
/**
 * Careers Page API Endpoints
 */

if (!defined('ABSPATH')) {
    exit;
}

// Register careers page endpoints
add_action('rest_api_init', function() {
    // Get careers page data
    register_rest_route('qounam/v1', '/careers', array(
        'methods' => 'GET',
        'callback' => 'qounam_get_careers_page',
        'permission_callback' => '__return_true'
    ));

    register_rest_route('qounam/v1', '/careers-posts', array(
    'methods'  => 'GET',
    'callback' => 'qounam_get_careers_posts',
    'permission_callback' => '__return_true',
    'args' => array(
        'category' => array('description' => 'Slug(s) or "all"'),
        'type'     => array('description' => 'Slug(s) or "all"'),
        'location' => array('description' => 'Slug(s) or "all"'),
    ),
    ));

    register_rest_route('qounam/v1', '/careers/(?P<slug>[a-zA-Z0-9-_%]+)', array(
        'methods'             => 'GET',
        'callback'            => 'qounam_get_single_career_by_slug',
        'permission_callback' => '__return_true',
    ));
});


/**
 * Get careers page data with ACF fields
 */
function qounam_get_careers_page() {
    // Check if ACF is active
    if (!function_exists('get_field')) {
        return new WP_REST_Response(array(
            'success' => false,
            'message' => 'ACF is not active',
            'data' => array()
        ), 500);
    }

    $page_id = 313;
    $careers = get_field('careers_group', $page_id);
    
    if (!$careers) {
        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'No careers page data found',
            'data' => array()
        ));
    }

    $categories = get_terms(array(
        'taxonomy' => 'career-category',
        'hide_empty' => false,
    ));
    $types = get_terms(array(
        'taxonomy' => 'career-type',
        'hide_empty' => false,
    ));
    $locations = get_terms(array(
        'taxonomy' => 'career-location',
        'hide_empty' => false,
    ));

    $careers_posts = get_posts(array(
        'post_type' => 'career',
        'posts_per_page' => 6,
        'post_status' => 'publish',
        'orderby' => 'date',
        'order' => 'DESC'
    ));
    // Format the response
    $response = array(
        'hero' => array(
            'label' => $careers['label'] ?? '',
            'title' => $careers['title'] ?? '',
            'subtitle' => $careers['subtitle'] ?? ''
        ),
        'environment_section' => array(
            'title' => $careers['environment_title'] ?? '',
            'image' => $careers['environment_image'],
            'description' => $careers['environment_description'] ?? '',
            'values' => !empty($careers['values']) ? 
                array_map(function($value) {
                    return array(
                        // 'icon' => $value['icon'] ?? '',
                        'title' => $value['text'] ?? ''
                    );
                }, $careers['values']) : []
        ),
        'careers_section' => array(
            'careers_categories' => array_map(function($category) {
                return array(
                    'id' => $category->term_id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'count' => $category->count,
                    'description' => $category->description
                );
            }, $categories),
            'careers_types' => array_map(function($type) {
                return array(
                    'id' => $type->term_id,
                    'name' => $type->name,
                    'slug' => $type->slug,
                    'count' => $type->count,
                    'description' => $type->description
                );
            }, $types),
            'careers_locations' => array_map(function($location) {
                return array(
                    'id' => $location->term_id,
                    'name' => $location->name,
                    'slug' => $location->slug,
                    'count' => $location->count,
                    'description' => $location->description
                );
            }, $locations),
            'careers_posts' => array_map(function($careers_post) {
                return array(
                    'ID' => $careers_post->ID,
                    'title' => $careers_post->post_title,
                    'slug' => $careers_post->post_name,
                    'location' => get_the_terms($careers_post->ID, 'career-location')[0]->name,
                    'type' => get_the_terms($careers_post->ID, 'career-type')[0]->name,
                    'category' => get_the_terms($careers_post->ID, 'career-category')[0]->name,
                    'apply_url' => get_field('apply_now_button', $careers_post->ID)['url'],
                    'excerpt' => $careers_post->post_excerpt
                    // 'description' => $careers_post->post_content,
                );
            }, $careers_posts),
        )
    );

    return new WP_REST_Response(array(
        'success' => true,
        'data' => $response
    ));
}
/**
 * Careers posts filter endpoint
 * Accepts: category, type, location (slug or array of slugs). "all" or empty means no filter.
 */
function qounam_get_careers_posts( WP_REST_Request $request ) {

    $normalize =  function(string $param) use ($request) {
        $v = $request->get_param($param);
        if (is_null($v)) return '';
        $v = trim((string)$v);
        if ($v === '' || strtolower($v) === 'all') return '';
        // sanitize to a slug
        return sanitize_title($v);
    };

    $categories = $normalize('category');
    $types      = $normalize('type');
    $locations  = $normalize('location');

    $tax_query = array();

    if (!empty($categories)) {
        $tax_query[] = array(
            'taxonomy' => 'career-category',
            'field'    => 'slug',
            'terms'    => $categories,
        );
    }

    if (!empty($types)) {
        $tax_query[] = array(
            'taxonomy' => 'career-type',
            'field'    => 'slug',
            'terms'    => $types,
        );
    }

    if (!empty($locations)) {
        $tax_query[] = array(
            'taxonomy' => 'career-location',
            'field'    => 'slug',
            'terms'    => $locations,
        );
    }

    if (count($tax_query) > 1) {
        $tax_query['relation'] = 'AND';
    }

    $per_page = (int) ($request->get_param('per_page') ?: -1);

    $args = array(
        'post_type'      => 'career',
        'posts_per_page' => $per_page,
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
    );

    if (!empty($tax_query)) {
        $args['tax_query'] = $tax_query;
    }

    $posts = get_posts($args);

    $formatted = array_map(function($p) {
    // term slugs (supports multiple; returns [] if none)
    $locations = wp_get_post_terms($p->ID, 'career-location', array('fields' => 'names'));
    $types     = wp_get_post_terms($p->ID, 'career-type', array('fields' => 'names'));

    return array(
        'id'              => (int) $p->ID,
        'slug'            => $p->post_name,
        'permalink'       => get_permalink($p->ID),
        'title'           => get_the_title($p->ID),
        'career_location' => is_wp_error($locations) ? array() : array_values($locations),
        'career_type'     => is_wp_error($types) ? array() : array_values($types),
    );
    }, $posts);
    return new WP_REST_Response(array(
        'success' => true,
        'filters' => array(
            'category' => $categories, // what actually applied
            'type'     => $types,
            'location' => $locations,
        ),
        'count' => count($formatted),
        'posts' => $formatted,
    ));
}


function qounam_format_single_career_response( WP_Post $post ) {
    // Taxonomies you’re using for the "career" CPT.
    $tx_map = array(
        'category' => 'career-category',
        'location' => 'career-location',
        'type'     => 'career-type',
    );

    $terms_out = array();
    foreach ($tx_map as $key => $tx) {
        $terms = wp_get_post_terms($post->ID, $tx, array('fields' => 'all'));
        if (is_wp_error($terms)) {
            $terms_out[$key] = array();
            continue;
        }
        // Return array of { name, slug }
        $terms_out[$key] = array_map(function($t){
            return array(
                'name' => $t->name,
                'slug' => $t->slug,
            );
        }, $terms);
    }

    $content = apply_filters('the_content', get_post_field('post_content', $post->ID));

    $acf_apply = function_exists('get_field') ? get_field('apply_now_button', $post->ID) : null;

    return array(
        'id'        => (int) $post->ID,
        'slug'      => $post->post_name,
        'title'     => get_the_title($post->ID),
        'permalink' => get_permalink($post->ID),
        'content'   => $content,
        'taxonomies'=> array(
            'category' => $terms_out['category'],
            'location' => $terms_out['location'],
            'type'     => $terms_out['type'],
        ),
        'apply_now_button'  => $acf_apply,
    );
}

/**
 * GET /career-by-slug/{slug}
 */
function qounam_get_single_career_by_slug( WP_REST_Request $request ) {
    $slug = sanitize_title_for_query($request['slug']);

    $post = get_page_by_path($slug, OBJECT, 'career'); // fast slug lookup for CPT
    if (!$post || $post->post_type !== 'career' || $post->post_status !== 'publish') {
        // Fallback: WP_Query in case of odd rewrite setups
        $q = new WP_Query(array(
            'post_type'      => 'career',
            'name'           => $slug,
            'posts_per_page' => 1,
            'post_status'    => 'publish',
        ));
        $post = $q->have_posts() ? $q->posts[0] : null;
    }

    if (!$post) {
        return new WP_REST_Response(array(
            'success' => false,
            'message' => 'Career not found.',
        ), 404);
    }

    return new WP_REST_Response(array(
        'success' => true,
        'data'    => qounam_format_single_career_response($post),
    ));
}