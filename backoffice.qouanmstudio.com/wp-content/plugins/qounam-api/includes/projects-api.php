<?php
/**
 * Projects API Endpoints
 * 
 * Handles project listing, filtering, sorting, and enrollment
 */

if (!defined('ABSPATH')) {
    exit;
}

// Register custom endpoints
add_action('rest_api_init', function () {
    // Get projects with pagination, sorting and filtering
    register_rest_route('qounam/v1', '/projects', array(
        'methods' => 'GET',
        'callback' => 'qounam_get_projects',
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

    // Get project categories
    register_rest_route('qounam/v1', '/projects/project-categories', array(
        'methods' => 'GET',
        'callback' => 'qounam_get_project_categories',
        'permission_callback' => '__return_true',
    ));

    // Enroll in project (use slug)
    register_rest_route('qounam/v1', '/projects/(?P<project_slug>[^/]+)/enroll', array(
        'methods' => 'POST',
        'callback' => 'qounam_enroll_project',
        'permission_callback' => 'qounam_check_jwt_auth',
    ));

    // Get project details (use slug)
    register_rest_route('qounam/v1', '/projects/(?P<slug>[^/]+)', array(
        'methods' => 'GET',
        'callback' => 'qounam_get_project_details',
        'permission_callback' => '__return_true',
    ));

    // Wishlist endpoints
    // register_rest_route('qounam/v1', '/wishlist', array(
    //     'methods' => 'GET',
    //     'callback' => 'qounam_get_wishlist',
    //     'permission_callback' => 'qounam_check_jwt_auth',
    // ));

    // register_rest_route('qounam/v1', '/wishlist/add', array(
    //     'methods' => 'POST',
    //     'callback' => 'qounam_add_to_wishlist',
    //     'permission_callback' => 'qounam_check_jwt_auth',
    // ));

    // register_rest_route('qounam/v1', '/wishlist/remove', array(
    //     'methods' => 'POST',
    //     'callback' => 'qounam_remove_from_wishlist',
    //     'permission_callback' => 'qounam_check_jwt_auth',
    // ));

    // // Get projects calendar
    // register_rest_route('qounam/v1', '/projects-calendar', array(
    //     'methods' => 'GET',
    //     'callback' => 'qounam_get_projects_calendar',
    //     'permission_callback' => '__return_true',
    // ));
});

/**
 * Get projects with pagination, sorting and filtering
 */
function qounam_get_projects($request)
{
    $page = max(1, intval($request->get_param('page')));
    $per_page = intval($request->get_param('per_page'));
    $service = sanitize_text_field($request->get_param('service'));
    $orderby = sanitize_text_field($request->get_param('orderby'));
    $search = sanitize_text_field($request->get_param('s'));

    $args = array(
        'post_type' => 'project',
        'post_status' => 'publish',
        'posts_per_page' => $per_page,
        'paged' => $page,
        'meta_query'     => array('relation' => 'AND'),
    );

    // Add search filter
    if (!empty($search)) {
        $args['s'] = $search;
    }

    $service_id = 0;
    if (!empty($service) && $service !== 'all') {
        $service_post = get_page_by_path($service, OBJECT, 'service');
        if (!$service_post) {
            return new WP_Error('service_not_found', 'Service not found', array('status' => 404));
        }

        $service_id = (int) $service_post->ID;

        $args['meta_query'][] = array(
            'key'     => 'related_services',
            'value'   => '"' . $service_id . '"',
            'compare' => 'LIKE',
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
    $total_projects = count($query);
    $total_pages = ceil($total_projects / $per_page);

    $projects = array();
    if ($query) {
        foreach ($query as $project) {
            $project_id = $project->ID;
            $related_services = get_field('related_services', $project_id);

            $projects[] = array(
                'title' => html_entity_decode(get_the_title($project_id)) ?? '',
                'slug' => get_post_field( 'post_name', $project_id),
                'image' => get_the_post_thumbnail_url($project_id),
                'logo' => get_field('logo', $project_id),
                'excerpt' => get_the_excerpt($project_id) ?? '',
                'rooms_design' => get_field('rooms_design',$project_id) ?? '',
                'furniture_units' => get_field('furniture_units',$project_id) ?? '',
                'weeks' => get_field('weeks',$project_id) ?? '',
                'related_services' =>   
                    array_map(function ($service_id) {
                        return array(
                            'title' => html_entity_decode(get_the_title($service_id)) ?? ''
                        );
                    }, $related_services)
            );
        }
        wp_reset_postdata();
    }

        /**
     * Build filter_services: all services that have projects
     * (Unique services referenced by any project.related_services)
     */
    $all_project_ids = get_posts(array(
        'post_type'      => 'project',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'no_found_rows'  => true,
    ));

    $service_ids_map = array(); // associative set
    foreach ($all_project_ids as $pid) {
        $sids = get_field('related_services', $pid) ?: array(); // IDs
        foreach ((array) $sids as $sid) {
            $sid = (int) $sid;
            if ($sid) $service_ids_map[$sid] = true;
        }
    }

    $service_ids = array_keys($service_ids_map);

    $filter_services = array(
        array('slug' => 'all', 'title' => 'All Services')
    );

    if (!empty($service_ids)) {
        $service_posts = get_posts(array(
            'post_type'      => 'service',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'post__in'       => $service_ids,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ));

        foreach ($service_posts as $sp) {
            $filter_services[] = array(
                'id'    => (int) $sp->ID,
                'title' => html_entity_decode(get_the_title($sp->ID)) ?: '',
                'slug'  => get_post_field('post_name', $sp->ID) ?: '',
            );
        }
    }
    return array(
        'success' => true,
        'page' => $page,
        'per_page' => $per_page,
        'total_items' => $total_projects,
        'total_pages' => $total_pages,
        'projects' => $projects,
        'filter_services' => $filter_services,
        'selected_service' => (!empty($service) && $service !== 'all' && $service_id) ? array(
            'id'    => $service_id,
            'slug'  => $service,
            'title' => html_entity_decode(get_the_title($service_id)) ?: '',
        ) : array('slug' => 'all', 'title' => 'All Services'),
    );
}

/**
 * Get all project categories
 */
function qounam_get_project_categories()
{
    $categories = get_terms(array(
        'taxonomy' => 'project-category',
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
 * Enroll user in project
 */
function qounam_enroll_project($request)
{
    $user_id = qounam_get_current_user_from_jwt();
    $project_slug = $request['project_slug'];
    $params = $request->get_json_params();

    if (!$user_id) {
        return new WP_Error('unauthorized', 'Unauthorized', array('status' => 401));
    }

    // Check if project exists
    $project = get_page_by_path($project_slug, OBJECT, 'project');
    if (!$project || $project->post_type !== 'project') {
        return new WP_Error('project_not_found', 'Project not found', array('status' => 404));
    }

    $project_id = $project->ID;

    $registration_button = get_field('register_button', $project_id);
    $redirect_url = (!empty($registration_button)) ? $registration_button['url'] : '';
    // Check if user already has a pending or approved request for this project
    $existing_request = get_posts(array(
        'post_type' => 'project-request',
        'meta_query' => array(
            'relation' => 'AND',
            array(
                'key' => 'project',
                'value' => $project_id,
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
        return new WP_Error('request_exists', 'You have already submitted a request for this project', array('status' => 400));
    }

    // Create new project request
    $request_data = array(
        'post_title' => 'Project Request - ' . get_the_title($project_id) . ' - ' . $params['first_name'] . ' ' . $params['last_name'],
        'post_type' => 'project-request',
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
            'project' => $project_id,
            'request_date' => current_time('mysql')
        )
    );

    $request_id = wp_insert_post($request_data);

    if (is_wp_error($request_id)) {
        return new WP_Error('request_failed', 'Failed to submit request', array('status' => 500));
    }

    // Send notification email to admin
    $admin_email = get_option('admin_email');
    $subject = 'New Project Request: ' . get_the_title($project_id);
    $message = 'A new project request has been submitted:<br>';
    $message .= 'Project: ' . get_the_title($project_id) . '<br>';
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

    // Get project details for each item in wishlist
    $projects = array();
    if (!empty($wishlist)) {
        $args = array(
            'post_type' => 'project',
            'post__in' => $wishlist,
            'posts_per_page' => -1,
            'orderby' => 'post__in', // Maintain the order of IDs
        );

        $query = new WP_Query($args);
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $project_id = get_the_ID();
                $projects[] = array(
                    'id' => $project_id,
                    'title' => get_the_title(),
                    'slug' => get_post_field('post_name'),
                    'status' => get_field('status', $project_id),
                    'thumbnail' => get_the_post_thumbnail_url($project_id),
                    'hover_thumbnail' => get_field('hover_thumbnail', $project_id),
                    'category' => get_terms(array(
                        'taxonomy' => 'project-category',
                        'hide_empty' => true,
                        'object_ids' => $project_id
                    ))[0]->name,
                    'excerpt' => get_the_excerpt($project_id),
                    'price' => get_field('price', $project_id),
                    'sale_price' => get_field('sale_price', $project_id),
                    'seats_available' => get_field('seats_available', $project_id),
                    'limited_offer' => get_field('limited_offer', $project_id),
                    'days_per_week' => get_field('days_per_week', $project_id),
                    'duration_in_weeks' => get_field('duration_in_weeks', $project_id),
                    'start_from' => get_field('start_from', $project_id),
                    'ends_at' => get_field('ends_at', $project_id)
                );
            }
            wp_reset_postdata();
        }
    }

    return array(
        'success' => true,
        'count' => count($wishlist),
        'projects' => $projects
    );
}

/**
 * Add project to wishlist
 */
function qounam_add_to_wishlist($request)
{
    $user_id = qounam_get_current_user_from_jwt();
    $params = $request->get_json_params();
    $project_id = isset($params['project_id']) ? intval($params['project_id']) : 0;

    if (!$user_id) {
        return new WP_Error('unauthorized', 'Unauthorized', array('status' => 401));
    }

    if (!$project_id) {
        return new WP_Error('missing_project_id', 'Project ID is required', array('status' => 400));
    }

    // Check if project exists
    $project = get_post($project_id);
    if (!$project || $project->post_type !== 'project') {
        return new WP_Error('project_not_found', 'Project not found', array('status' => 404));
    }

    // Get current wishlist
    $wishlist = get_user_meta($user_id, 'wishlist', true) ?: array();
    $wishlist = array_map('intval', $wishlist);

    // Check if already in wishlist
    if (in_array($project_id, $wishlist)) {
        return new WP_Error('already_in_wishlist', 'Project is already in your wishlist', array('status' => 400));
    }

    // Add to wishlist
    $wishlist[] = $project_id;
    update_user_meta($user_id, 'wishlist', $wishlist);

    return array(
        'success' => true,
        'message' => 'Project added to wishlist',
        'wishlist_count' => count($wishlist)
    );
}

/**
 * Remove project from wishlist
 */
function qounam_remove_from_wishlist($request)
{
    $user_id = qounam_get_current_user_from_jwt();
    $params = $request->get_json_params();
    $project_id = isset($params['project_id']) ? intval($params['project_id']) : 0;

    if (!$user_id) {
        return new WP_Error('unauthorized', 'Unauthorized', array('status' => 401));
    }

    if (!$project_id) {
        return new WP_Error('missing_project_id', 'Project ID is required', array('status' => 400));
    }

    // Get current wishlist
    $wishlist = get_user_meta($user_id, 'wishlist', true) ?: array();
    $wishlist = array_map('intval', $wishlist);

    // Find and remove project from wishlist
    $index = array_search($project_id, $wishlist);
    if ($index !== false) {
        unset($wishlist[$index]);
        $wishlist = array_values($wishlist); // Reindex array
        update_user_meta($user_id, 'wishlist', $wishlist);
    }

    return array(
        'success' => true,
        'message' => 'Project removed from wishlist',
        'wishlist_count' => count($wishlist)
    );
}

/**
 * Example: Get project details with ACF fields
 */
function qounam_get_project_details($request)
{
    $slug = sanitize_title($request['slug']);

    // Resolve slug -> project post
    $project = get_page_by_path($slug, OBJECT, 'project');

    if (!$project || $project->post_type !== 'project') {
        return new WP_Error('project_not_found', 'Project not found', array('status' => 404));
    }

    $project_id = $project->ID;


    // Get ACF fields if available
    $acf_fields = function_exists('get_fields') ? get_fields($project_id) : array();
    $project_fields = get_field('project_fields', $project_id);

    if (!$project_fields) {
        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'No project fields found',
            'data' => array()
        ));
    }
    return array(
        'success' => true,
        'project' => array(
            'id' => $project->ID,
            'title' => $project->post_title,
            'excerpt' => $project->post_excerpt,
            'content' => $project->post_content,
            'cover_image' => get_field('cover_image', $project_id),
            'start_from' => get_field('start_from', $project_id),
            'ends_at' => get_field('ends_at', $project_id),
            'time' => get_field('time', $project_id),
            'duration_in_weeks' => get_field('duration_in_weeks', $project_id),
            'days_per_week' => get_field('days_per_week', $project_id),
            'seats_available' => get_field('seats_available', $project_id),
            'limited_offer' => get_field('limited_offer', $project_id),
            'price' => get_field('price', $project_id),
            'sale_price' => get_field('sale_price', $project_id),
            'register_button' => get_field('register_button', $project_id),
            'pdf_button' => get_field('pdf_button', $project_id),
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
                'title' => $project_fields['overview_title'],
                'description' => $project_fields['overview_description'],
                'image' => $project_fields['overview_image'],
            ),
            'content_section' => array(
                'label' => $project_fields['contents_label'],
                'title' => $project_fields['contents_title'],
                'contents' => !empty($project_fields['contents']) ?
                    array_map(function ($feature) {
                        return array(
                            'title' => $feature['text'] ?? ''
                        );
                    }, $project_fields['contents']) : []
            ),
            'benefits_section' => array(
                'label' => $project_fields['benefits_label'],
                'title' => $project_fields['benefits_title'],
                'subtitle' => $project_fields['benefits_subtitle'],
                'image' => $project_fields['benefits_image'],
                'benefits' => !empty($project_fields['benefits']) ?
                    array_map(function ($feature) {
                        return array(
                            'title' => $feature['text'] ?? ''
                        );
                    }, $project_fields['benefits']) : []
            ),
            'audience_section' => array(
                'label' => $project_fields['audience_label'],
                'title' => $project_fields['audience_title'],
                'subtitle' => $project_fields['audience_subtitle']
            ),
            'registration_terms_section' => array(
                'label' => $project_fields['terms_label'],
                'title' => $project_fields['terms_title'],
                'image' => $project_fields['terms_image'],
                'registration_terms' => !empty($project_fields['terms']) ?
                    array_map(function ($feature) {
                        return array(
                            'title' => $feature['text'] ?? ''
                        );
                    }, $project_fields['terms']) : []
            ),
        ),
    );
}

/**
 * Get projects calendar grouped by month
 */
/**
 * Get projects calendar grouped by month then by day-of-month (based on start_from)
 *
 * Response shape:
 * data: {
 *   january: [
 *     { DAYS: 3,  project: [ ...projects starting on Jan 3... ] },
 *     { DAYS: 20, project: [ ... ] }
 *   ],
 *   february: [],
 *   ...
 * }
 */
function qounam_get_projects_calendar($request) {

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

    $projects = get_posts([
        'post_type'   => 'project',
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

    foreach ($projects as $project) {

        $start_raw = get_field('start_from', $project->ID);
        $end_raw   = get_field('ends_at', $project->ID);
        if (empty($start_raw) || empty($end_raw)) continue;

        // Support ACF-style d/m/Y OR ISO-ish dates
        $start = DateTime::createFromFormat('d/m/Y', $start_raw) ?: new DateTime($start_raw);
        $end   = DateTime::createFromFormat('d/m/Y', $end_raw)   ?: new DateTime($end_raw);

        // Only include projects whose START date is in requested year
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
                'project' => [],
            ];
        }

        $month_day_map[$month_key][$day_key]['project'][] = [
            'id'          => $project->ID,
            'title'       => $project->post_title,
            'slug'        => $project->post_name,
            'start_date'  => $start->format('Y-m-d'),
            'end_date'    => $end->format('Y-m-d'),
            'start_day'   => (int) $start->format('d'),
            'end_day'     => (int) $end->format('d'),
            'start_month' => strtolower($start->format('F')),
            'end_month'   => strtolower($end->format('F')),
            'year'        => (int) $start->format('Y'),
            'days_count'  => $days_count, // duration
            'status' => get_field('status', $project->ID),
            'thumbnail' => get_the_post_thumbnail_url($project->ID),
            'hover_thumbnail' => get_field('hover_thumbnail', $project->ID),
            'category' => get_terms(array(
                'taxonomy' => 'project-category',
                'hide_empty' => true,
                'object_ids' => $project->ID
            ))[0]->name,
            'excerpt' => get_the_excerpt($project->ID),
            'price' => get_field('price', $project->ID),
            'sale_price' => get_field('sale_price', $project->ID),
            'seats_available' => get_field('seats_available', $project->ID),
            'limited_offer' => get_field('limited_offer', $project->ID),
            'days_per_week' => get_field('days_per_week', $project->ID),
            'duration_in_weeks' => get_field('duration_in_weeks', $project->ID),
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
