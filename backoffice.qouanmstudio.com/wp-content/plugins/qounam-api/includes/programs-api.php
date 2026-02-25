<?php
/**
 * Programs API Endpoints
 * 
 * Handles program listing, filtering, sorting, and enrollment
 */

if (!defined('ABSPATH')) {
    exit;
}

// Register custom endpoints
add_action('rest_api_init', function () {
    // Get programs with pagination, sorting and filtering
    register_rest_route('qounam/v1', '/programs', array(
        'methods' => 'GET',
        'callback' => 'qounam_get_programs',
        'permission_callback' => '__return_true',
        'args' => array(
            'page' => array(
                'validate_callback' => function ($param) {
                    return is_numeric($param);
                },
                'default' => 1
            ),
            'per_page' => array(
                'validate_callback' => function ($param) {
                    return is_numeric($param);
                },
                'default' => 10
            ),
            'category' => array(
                'validate_callback' => function ($param) {
                    return is_string($param);
                },
                'default' => ''
            ),
            'orderby' => array(
                'validate_callback' => function ($param) {
                    return in_array($param, array('date', 'price', 'price-desc', 'title'));
                },
                'default' => 'date'
            ),
        ),
    ));

    // Get program categories
    register_rest_route('qounam/v1', '/programs/program-categories', array(
        'methods' => 'GET',
        'callback' => 'qounam_get_program_categories',
        'permission_callback' => '__return_true',
    ));

    // Enroll in program (use slug)
    register_rest_route('qounam/v1', '/programs/(?P<program_slug>[^/]+)/enroll', array(
        'methods' => 'POST',
        'callback' => 'qounam_enroll_program',
        'permission_callback' => 'qounam_check_jwt_auth',
    ));

    // Get program details (use slug)
    register_rest_route('qounam/v1', '/programs/(?P<slug>[^/]+)', array(
        'methods' => 'GET',
        'callback' => 'qounam_get_program_details',
        'permission_callback' => '__return_true',
    ));

    // Wishlist endpoints
    register_rest_route('qounam/v1', '/wishlist', array(
        'methods' => 'GET',
        'callback' => 'qounam_get_wishlist',
        'permission_callback' => 'qounam_check_jwt_auth',
    ));

    register_rest_route('qounam/v1', '/wishlist/add', array(
        'methods' => 'POST',
        'callback' => 'qounam_add_to_wishlist',
        'permission_callback' => 'qounam_check_jwt_auth',
    ));

    register_rest_route('qounam/v1', '/wishlist/remove', array(
        'methods' => 'POST',
        'callback' => 'qounam_remove_from_wishlist',
        'permission_callback' => 'qounam_check_jwt_auth',
    ));

    // Get programs calendar
    register_rest_route('qounam/v1', '/programs-calendar', array(
        'methods' => 'GET',
        'callback' => 'qounam_get_programs_calendar',
        'permission_callback' => '__return_true',
    ));
});

/**
 * Get programs with pagination, sorting and filtering
 */
function qounam_get_programs($request)
{
    $page = max(1, intval($request->get_param('page')));
    $per_page = intval($request->get_param('per_page'));
    $category = sanitize_text_field($request->get_param('category'));
    $orderby = sanitize_text_field($request->get_param('orderby'));
    $search = sanitize_text_field($request->get_param('s'));

    $args = array(
        'post_type' => 'program',
        'post_status' => 'publish',
        'posts_per_page' => $per_page,
        'paged' => $page,
        'tax_query' => array(),
    );

    // Add search filter
    if (!empty($search)) {
        $args['s'] = $search;
    }

    // Add category filter
    if (!empty($category)) {
        $args['tax_query'][] = array(
            'taxonomy' => 'program-category',
            'field' => 'slug',
            'terms' => $category
        );
    }

    // Add sorting
    switch ($orderby) {
        case 'price':
            $args['meta_key'] = 'price';
            $args['orderby'] = 'meta_value_num';
            $args['order'] = 'ASC';
            break;
        case 'price-desc':
            $args['meta_key'] = 'price';
            $args['orderby'] = 'meta_value_num';
            $args['order'] = 'DESC';
            break;
        case 'title':
            $args['orderby'] = 'title';
            $args['order'] = 'ASC';
            break;
        default: // date
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
    }


    $query = get_posts($args);
    $total_programs = count($query);
    $total_pages = ceil($total_programs / $per_page);

    $programs = array();
    if ($query) {
        foreach ($query as $program) {
            $program_id = $program->ID;
            $categories = get_the_terms($program_id, 'program-category');
            $category_list = '';

            if ($categories && !is_wp_error($categories)) {
                // $category_list = array_map(function($cat) {
                //     return array(
                //         'id' => $cat->term_id,
                //         'name' => $cat->name,
                //         'slug' => $cat->slug
                //     );
                // }, $categories);
                $category_list = $categories[0]->name;
            }

            $programs[] = array(
                'id' => $program_id,
                'title' => $program->post_title,
                'excerpt' => $program->post_excerpt,
                'slug' => $program->post_name,
                'thumbnail' => get_the_post_thumbnail_url($program_id),
                'hover_thumbnail' => get_field('hover_thumbnail', $program_id),
                'category' => $category_list,
                'days_per_week' => get_field('days_per_week', $program_id),
                'duration_in_weeks' => get_field('duration_in_weeks', $program_id),
                'start_from' => get_field('start_from', $program_id),
                'price' => get_field('price', $program_id),
                'sale_price' => get_field('sale_price', $program_id),
                'image' => get_the_post_thumbnail_url($program_id),
                'seats_available' => get_field('seats_available', $program_id),
                'limited_offer' => get_field('limited_offer', $program_id)
            );
        }
        wp_reset_postdata();
    }

    return array(
        'success' => true,
        'page' => $page,
        'per_page' => $per_page,
        'total_items' => $total_programs,
        'total_pages' => $total_pages,
        'programs' => $programs,
    );
}

/**
 * Get all program categories
 */
function qounam_get_program_categories()
{
    $categories = get_terms(array(
        'taxonomy' => 'program-category',
        'hide_empty' => false,
    ));

    if (is_wp_error($categories)) {
        return new WP_Error('no_categories', 'No categories found', array('status' => 404));
    }

    $formatted_categories = array_map(function ($category) {
        return array(
            'id' => $category->term_id,
            'name' => htmlspecialchars_decode($category->name),
            'slug' => $category->slug,
            'count' => $category->count,
            'description' => $category->description
        );
    }, $categories);

    return array(
        'success' => true,
        'categories' => $formatted_categories
    );
}

/**
 * Enroll user in program
 */
function qounam_enroll_program($request)
{
    $user_id = qounam_get_current_user_from_jwt();
    $program_slug = $request['program_slug'];
    $params = $request->get_json_params();

    if (!$user_id) {
        return new WP_Error('unauthorized', 'Unauthorized', array('status' => 401));
    }

    // Check if program exists
    $program = get_page_by_path($program_slug, OBJECT, 'program');
    if (!$program || $program->post_type !== 'program') {
        return new WP_Error('program_not_found', 'Program not found', array('status' => 404));
    }

    $program_id = $program->ID;

    $registration_button = get_field('register_button', $program_id);
    $redirect_url = (!empty($registration_button)) ? $registration_button['url'] : '';
    // Check if user already has a pending or approved request for this program
    $existing_request = get_posts(array(
        'post_type' => 'program-request',
        'meta_query' => array(
            'relation' => 'AND',
            array(
                'key' => 'program',
                'value' => $program_id,
                'compare' => '='
            ),
            array(
                'key' => 'user_id',
                'value' => $user_id,
                'compare' => '='
            )
        )
    ));

    if (!empty($existing_request)) {
        return new WP_Error('request_exists', 'You have already submitted a request for this program', array('status' => 400));
    }

    // Create new program request
    $request_data = array(
        'post_title' => 'Program Request - ' . get_the_title($program_id) . ' - ' . $params['first_name'] . ' ' . $params['last_name'],
        'post_type' => 'program-request',
        'post_status' => 'publish',
        'meta_input' => array(
            'first_name' => sanitize_text_field($params['first_name']),
            'last_name' => sanitize_text_field($params['last_name']),
            'email' => sanitize_email($params['email']),
            'phone_number' => sanitize_text_field($params['phone_number']),
            'government' => sanitize_text_field($params['government']),
            'position' => sanitize_text_field($params['position']),
            'company' => sanitize_text_field($params['company']),
            'status' => 'pending',
            'user_id' => $user_id,
            'program' => $program_id,
            'request_date' => current_time('mysql')
        )
    );

    $request_id = wp_insert_post($request_data);

    if (is_wp_error($request_id)) {
        return new WP_Error('request_failed', 'Failed to submit request', array('status' => 500));
    }

    // Send notification email to admin
    $admin_email = get_option('admin_email');
    $subject = 'New Program Request: ' . get_the_title($program_id);
    $message = 'A new program request has been submitted:<br>';
    $message .= 'Program: ' . get_the_title($program_id) . '<br>';
    $message .= 'Name: ' . $params['first_name'] . ' ' . $params['last_name'] . '<br>';
    $message .= 'Email: ' . $params['email'] . '<br>';
    $message .= 'Phone: ' . $params['phone_number'] . '<br>';
    $message .= 'Company: ' . $params['company'] . '<br>';
    $message .= 'Position: ' . $params['position'] . '<br>';
    $message .= 'Government: ' . $params['government'] . '<br><br>';
    $message .= 'View request: ' . admin_url('post.php?post=' . $request_id . '&action=edit');

    $headers = array('Content-Type: text/html; charset=UTF-8');
    wp_mail($admin_email, $subject, $message, $headers);

    return array(
        'success' => true,
        'message' => 'Your request has been submitted successfully',
        'request_id' => $request_id,
        'redirect_url' => $redirect_url
    );
}

/**
 * Get user's wishlist
 */
function qounam_get_wishlist($request)
{
    $user_id = qounam_get_current_user_from_jwt();

    if (!$user_id) {
        return new WP_Error('unauthorized', 'Unauthorized', array('status' => 401));
    }

    $wishlist = get_user_meta($user_id, 'wishlist', true) ?: array();
    $wishlist = array_map('intval', $wishlist); // Ensure all IDs are integers

    // Get program details for each item in wishlist
    $programs = array();
    if (!empty($wishlist)) {
        $args = array(
            'post_type' => 'program',
            'post__in' => $wishlist,
            'posts_per_page' => -1,
            'orderby' => 'post__in', // Maintain the order of IDs
        );

        $query = new WP_Query($args);
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $program_id = get_the_ID();
                $programs[] = array(
                    'id' => $program_id,
                    'title' => get_the_title(),
                    'slug' => get_post_field('post_name'),
                    'status' => get_field('status', $program_id),
                    'thumbnail' => get_the_post_thumbnail_url($program_id),
                    'hover_thumbnail' => get_field('hover_thumbnail', $program_id),
                    'category' => get_terms(array(
                        'taxonomy' => 'program-category',
                        'hide_empty' => true,
                        'object_ids' => $program_id
                    ))[0]->name,
                    'excerpt' => get_the_excerpt($program_id),
                    'price' => get_field('price', $program_id),
                    'sale_price' => get_field('sale_price', $program_id),
                    'seats_available' => get_field('seats_available', $program_id),
                    'limited_offer' => get_field('limited_offer', $program_id),
                    'days_per_week' => get_field('days_per_week', $program_id),
                    'duration_in_weeks' => get_field('duration_in_weeks', $program_id),
                    'start_from' => get_field('start_from', $program_id),
                    'ends_at' => get_field('ends_at', $program_id)
                );
            }
            wp_reset_postdata();
        }
    }

    return array(
        'success' => true,
        'count' => count($wishlist),
        'programs' => $programs
    );
}

/**
 * Add program to wishlist
 */
function qounam_add_to_wishlist($request)
{
    $user_id = qounam_get_current_user_from_jwt();
    $params = $request->get_json_params();
    $program_id = isset($params['program_id']) ? intval($params['program_id']) : 0;

    if (!$user_id) {
        return new WP_Error('unauthorized', 'Unauthorized', array('status' => 401));
    }

    if (!$program_id) {
        return new WP_Error('missing_program_id', 'Program ID is required', array('status' => 400));
    }

    // Check if program exists
    $program = get_post($program_id);
    if (!$program || $program->post_type !== 'program') {
        return new WP_Error('program_not_found', 'Program not found', array('status' => 404));
    }

    // Get current wishlist
    $wishlist = get_user_meta($user_id, 'wishlist', true) ?: array();
    $wishlist = array_map('intval', $wishlist);

    // Check if already in wishlist
    if (in_array($program_id, $wishlist)) {
        return new WP_Error('already_in_wishlist', 'Program is already in your wishlist', array('status' => 400));
    }

    // Add to wishlist
    $wishlist[] = $program_id;
    update_user_meta($user_id, 'wishlist', $wishlist);

    return array(
        'success' => true,
        'message' => 'Program added to wishlist',
        'wishlist_count' => count($wishlist)
    );
}

/**
 * Remove program from wishlist
 */
function qounam_remove_from_wishlist($request)
{
    $user_id = qounam_get_current_user_from_jwt();
    $params = $request->get_json_params();
    $program_id = isset($params['program_id']) ? intval($params['program_id']) : 0;

    if (!$user_id) {
        return new WP_Error('unauthorized', 'Unauthorized', array('status' => 401));
    }

    if (!$program_id) {
        return new WP_Error('missing_program_id', 'Program ID is required', array('status' => 400));
    }

    // Get current wishlist
    $wishlist = get_user_meta($user_id, 'wishlist', true) ?: array();
    $wishlist = array_map('intval', $wishlist);

    // Find and remove program from wishlist
    $index = array_search($program_id, $wishlist);
    if ($index !== false) {
        unset($wishlist[$index]);
        $wishlist = array_values($wishlist); // Reindex array
        update_user_meta($user_id, 'wishlist', $wishlist);
    }

    return array(
        'success' => true,
        'message' => 'Program removed from wishlist',
        'wishlist_count' => count($wishlist)
    );
}

/**
 * Example: Get program details with ACF fields
 */
function qounam_get_program_details($request)
{
    $slug = sanitize_title($request['slug']);

    // Resolve slug -> program post
    $program = get_page_by_path($slug, OBJECT, 'program');

    if (!$program || $program->post_type !== 'program') {
        return new WP_Error('program_not_found', 'Program not found', array('status' => 404));
    }

    $program_id = $program->ID;


    // Get ACF fields if available
    $acf_fields = function_exists('get_fields') ? get_fields($program_id) : array();
    $program_fields = get_field('program_fields', $program_id);

    if (!$program_fields) {
        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'No program fields found',
            'data' => array()
        ));
    }
    return array(
        'success' => true,
        'program' => array(
            'id' => $program->ID,
            'title' => $program->post_title,
            'excerpt' => $program->post_excerpt,
            'content' => $program->post_content,
            'cover_image' => get_field('cover_image', $program_id),
            'start_from' => get_field('start_from', $program_id),
            'ends_at' => get_field('ends_at', $program_id),
            'time' => get_field('time', $program_id),
            'duration_in_weeks' => get_field('duration_in_weeks', $program_id),
            'days_per_week' => get_field('days_per_week', $program_id),
            'seats_available' => get_field('seats_available', $program_id),
            'limited_offer' => get_field('limited_offer', $program_id),
            'price' => get_field('price', $program_id),
            'sale_price' => get_field('sale_price', $program_id),
            'register_button' => get_field('register_button', $program_id),
            'pdf_button' => get_field('pdf_button', $program_id),
            'tabs_title' => 'Compensation, Benefits and Incentives',
            'tabs' => array(
                'Overview',
                'Content',
                'Benefits',
                'Audience',
                'Registration Terms',
                'FAQs'
            ),
            'overview_section' => array(
                'title' => $program_fields['overview_title'],
                'description' => $program_fields['overview_description'],
                'image' => $program_fields['overview_image'],
            ),
            'content_section' => array(
                'label' => $program_fields['contents_label'],
                'title' => $program_fields['contents_title'],
                'contents' => !empty($program_fields['contents']) ?
                    array_map(function ($feature) {
                        return array(
                            'title' => $feature['text'] ?? ''
                        );
                    }, $program_fields['contents']) : []
            ),
            'benefits_section' => array(
                'label' => $program_fields['benefits_label'],
                'title' => $program_fields['benefits_title'],
                'subtitle' => $program_fields['benefits_subtitle'],
                'image' => $program_fields['benefits_image'],
                'benefits' => !empty($program_fields['benefits']) ?
                    array_map(function ($feature) {
                        return array(
                            'title' => $feature['text'] ?? ''
                        );
                    }, $program_fields['benefits']) : []
            ),
            'audience_section' => array(
                'label' => $program_fields['audience_label'],
                'title' => $program_fields['audience_title'],
                'subtitle' => $program_fields['audience_subtitle']
            ),
            'registration_terms_section' => array(
                'label' => $program_fields['terms_label'],
                'title' => $program_fields['terms_title'],
                'image' => $program_fields['terms_image'],
                'registration_terms' => !empty($program_fields['terms']) ?
                    array_map(function ($feature) {
                        return array(
                            'title' => $feature['text'] ?? ''
                        );
                    }, $program_fields['terms']) : []
            ),
        ),
    );
}

/**
 * Get programs calendar grouped by month
 */
/**
 * Get programs calendar grouped by month then by day-of-month (based on start_from)
 *
 * Response shape:
 * data: {
 *   january: [
 *     { DAYS: 3,  program: [ ...programs starting on Jan 3... ] },
 *     { DAYS: 20, program: [ ... ] }
 *   ],
 *   february: [],
 *   ...
 * }
 */
function qounam_get_programs_calendar($request) {

    $year = (int) ($request->get_param('year') ?: date('Y'));

    $months_order = [
        'january','february','march','april','may','june',
        'july','august','september','october','november','december'
    ];

    // Pre-seed all months (in order) with empty arrays
    $response = [
        'success' => true,
        'year'    => $year,
        'data'    => array_fill_keys($months_order, []),
    ];

    $programs = get_posts([
        'post_type'   => 'program',
        'post_status' => 'publish',
        'numberposts' => -1,
        'meta_query'  => [
            [ 'key' => 'start_from', 'compare' => 'EXISTS' ],
            [ 'key' => 'ends_at',    'compare' => 'EXISTS' ],
        ],
        'orderby'  => 'meta_value',
        'meta_key' => 'start_from',
        'order'    => 'ASC',
    ]);

    // Internal maps for grouping: month => day => bucket
    $month_day_map = [];

    foreach ($programs as $program) {

        $start_raw = get_field('start_from', $program->ID);
        $end_raw   = get_field('ends_at', $program->ID);
        if (empty($start_raw) || empty($end_raw)) continue;

        // Support ACF-style d/m/Y OR ISO-ish dates
        $start = DateTime::createFromFormat('d/m/Y', $start_raw) ?: new DateTime($start_raw);
        $end   = DateTime::createFromFormat('d/m/Y', $end_raw)   ?: new DateTime($end_raw);

        // Only include programs whose START date is in requested year
        if ((int) $start->format('Y') !== $year) continue;

        $month_key = strtolower($start->format('F'));
        $day_key   = (int) $start->format('d'); // 1..31

        // duration in days (inclusive) — keep as metadata
        $start0 = (clone $start)->setTime(0,0,0);
        $end0   = (clone $end)->setTime(0,0,0);
        $days_count = (int) $start0->diff($end0)->format('%a') + 1;

        if (!isset($month_day_map[$month_key])) {
            $month_day_map[$month_key] = [];
        }

        if (!isset($month_day_map[$month_key][$day_key])) {
            $month_day_map[$month_key][$day_key] = [
                'DAYS'    => $day_key, // grouping by day
                'program' => [],
            ];
        }

        $month_day_map[$month_key][$day_key]['program'][] = [
            'id'          => $program->ID,
            'title'       => $program->post_title,
            'slug'        => $program->post_name,
            'start_date'  => $start->format('Y-m-d'),
            'end_date'    => $end->format('Y-m-d'),
            'start_day'   => (int) $start->format('d'),
            'end_day'     => (int) $end->format('d'),
            'start_month' => strtolower($start->format('F')),
            'end_month'   => strtolower($end->format('F')),
            'year'        => (int) $start->format('Y'),
            'days_count'  => $days_count, // duration
            'status' => get_field('status', $program->ID),
            'thumbnail' => get_the_post_thumbnail_url($program->ID),
            'hover_thumbnail' => get_field('hover_thumbnail', $program->ID),
            'category' => get_terms(array(
                'taxonomy' => 'program-category',
                'hide_empty' => true,
                'object_ids' => $program->ID
            ))[0]->name,
            'excerpt' => get_the_excerpt($program->ID),
            'price' => get_field('price', $program->ID),
            'sale_price' => get_field('sale_price', $program->ID),
            'seats_available' => get_field('seats_available', $program->ID),
            'limited_offer' => get_field('limited_offer', $program->ID),
            'days_per_week' => get_field('days_per_week', $program->ID),
            'duration_in_weeks' => get_field('duration_in_weeks', $program->ID),
        ];
    }

    // Build response months in fixed order, with day buckets sorted
    foreach ($months_order as $m) {
        if (!isset($month_day_map[$m])) {
            $response['data'][$m] = [];
            continue;
        }
        ksort($month_day_map[$m]);                 // sort by day 1..31
        $response['data'][$m] = array_values($month_day_map[$m]);
    }

    return $response;
}



/**
 * HOW TO ADD YOUR OWN CUSTOM ENDPOINTS:
 * 
 * 1. Create a new function that handles your endpoint logic
 * 2. Register it with register_rest_route() in the rest_api_init hook
 * 3. Use qounam_get_current_user_from_jwt() to get authenticated user
 * 4. Use qounam_check_jwt_auth as permission callback for protected endpoints
 * 
 * Example:
 * 
 * register_rest_route('qounam/v1', '/my-custom-endpoint', array(
 *     'methods' => 'POST',
 *     'callback' => 'my_custom_endpoint_handler',
 *     'permission_callback' => 'qounam_check_jwt_auth', // For protected endpoints
 * ));
 * 
 * function my_custom_endpoint_handler($request) {
 *     $user_id = qounam_get_current_user_from_jwt();
 *     
 *     // Your logic here
 *     
 *     return array('success' => true, 'data' => $data);
 * }
 */
