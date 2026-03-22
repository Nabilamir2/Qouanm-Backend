<?php
/**
 * Services API Endpoints
 * 
 * Handles service listing, filtering, sorting, and enrollment
 */

if (!defined('ABSPATH')) {
    exit;
}

// Register custom endpoints
add_action('rest_api_init', function () {
    // Get services with pagination, sorting and filtering
    register_rest_route('qounam/v1', '/services', array(
        'methods' => 'GET',
        'callback' => 'qounam_get_services',
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
            // 'category' => array(
            //     'validate_callback' => function ($param) {
            //         return is_string($param);
            //     },
            //     'default' => ''
            // ),
            'orderby' => array(
                'validate_callback' => function ($param) {
                    return in_array($param, array('date', 'price', 'price-desc', 'title'));
                },
                'default' => 'date'
            ),
        ),
    ));

    // Get service categories
    // register_rest_route('qounam/v1', '/services/service-categories', array(
    //     'methods' => 'GET',
    //     'callback' => 'qounam_get_service_categories',
    //     'permission_callback' => '__return_true',
    // ));

    // Enroll in service (use slug)
    // register_rest_route('qounam/v1', '/services/(?P<service_slug>[^/]+)/enroll', array(
    //     'methods' => 'POST',
    //     'callback' => 'qounam_enroll_service',
    //     'permission_callback' => 'qounam_check_jwt_auth',
    // ));

    // Get service details (use slug)
    register_rest_route('qounam/v1', '/services/(?P<slug>[^/]+)', array(
        'methods' => 'GET',
        'callback' => 'qounam_get_service_details',
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

    // Get services calendar
    // register_rest_route('qounam/v1', '/services-calendar', array(
    //     'methods' => 'GET',
    //     'callback' => 'qounam_get_services_calendar',
    //     'permission_callback' => '__return_true',
    // ));
});

/**
 * Get services with pagination, sorting and filtering
 */
function qounam_get_services($request)
{
    $page = max(1, intval($request->get_param('page')));
    $per_page = intval($request->get_param('per_page'));
    // $category = sanitize_text_field($request->get_param('category'));
    $orderby = sanitize_text_field($request->get_param('orderby'));
    $search = sanitize_text_field($request->get_param('s'));

    $args = array(
        'post_type' => 'service',
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
            'taxonomy' => 'service-category',
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
    $total_services = count($query);
    $total_pages = ceil($total_services / $per_page);
        
    $services = array();
    if ($query) {
        foreach ($query as $service) {
            $service_id = $service->ID;
            $gallery = get_field('gallery', $service_id);
            $statistics = get_field('statistics', $service_id);
            $services[] = array(
                'id' => $service_id,
                'title' => $service->post_title,
                'excerpt' => $service->post_excerpt,
                'slug' => $service->post_name,
                'thumbnail' => get_the_post_thumbnail_url($service_id),
                'description' => get_field('description', $service_id),
                'gallery' =>!empty($gallery) ? 
                    array_map(function($image_url) {
                        return $image_url;
                    }, $gallery) : [],
                'statistics' => array_map(function($statistics) {
                    return array(
                        'title' => $statistics['title'] ?? '',
                        'subtitle' => $statistics['subtitle'] ?? ''
                    );
                }, $statistics)
            );
        }
        wp_reset_postdata();
    }

    return array(
        'success' => true,
        'label' => get_field('label', 182),
        'title' => get_field('title', 182),
        'subtitle ' => get_field('subtitle', 182),
        'services' => $services,
        'page' => $page,
        'per_page' => $per_page,
        'total_items' => $total_services,
        'total_pages' => $total_pages
    );
}

/**
 * Example: Get service details with ACF fields
 */
function qounam_get_service_details($request)
{
    $slug = sanitize_title($request['slug']);

    // Resolve slug -> service post
    $service = get_page_by_path($slug, OBJECT, 'service');

    if (!$service || $service->post_type !== 'service') {
        return new WP_Error('service_not_found', 'Service not found', array('status' => 404));
    }

    $service_id = $service->ID;


    // Get ACF fields if available
    $acf_fields = function_exists('get_fields') ? get_fields($service_id) : array();
    $service_fields = get_field('page_fields', $service_id);

    if (!$service_fields) {
        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'No service fields found',
            'data' => array()
        ));
    }

    $related_projects = get_field('related_projects', $service_id);
    $gallery = get_field('gallery', $service_id);
    $statistics = get_field('statistics', $service_id);
    return array(
        'success' => true,
        'service' => array(
            'id' => $service->ID,
            'title' => $service->post_title,
            'excerpt' => $service->post_excerpt,
            'gallery' =>!empty($gallery) ? 
                array_map(function($image_url) {
                    return array(
                        $image_url,
                    );
                }, $gallery) : [],
            'overview_section' => array(
                'title' => 'Overview',
                'description' => $service->post_content,
                'image' => $service_fields['overview_image'],
            ),
            'includes_section' => array(
                'title' => $service_fields['title'],
                'subtitle' => $service_fields['subtitle'],
                'big_image' => $service_fields['Big_Image'],
                'small_image' => $service_fields['small_image'],
                'points' => !empty($service_fields['points']) ?
                    array_map(function ($feature) {
                        return array(
                            'title' => $feature['text'] ?? ''
                        );
                    }, $service_fields['points']) : []
            ),
            'process_section' => array(
                'title' => $service_fields['process_title'],
                'subtitle' => $service_fields['process_subtitle'],
                'processes' => !empty($service_fields['processes']) ?
                    array_map(function ($feature) {
                        return array(
                            'title' => $feature['title'] ?? '',
                            'subtitle' => $feature['description'] ?? ''
                        );
                    }, $service_fields['processes']) : []
            ),
            'quality_section' => array(
                'title' => $service_fields['quality_title'],
                'subtitle' => $service_fields['quality_subtitle'],
                'qualities' => !empty($service_fields['qualities']) ?
                    array_map(function ($feature) {
                        return array(
                            'title' => $feature['title'] ?? '',
                            'subtitle' => $feature['description'] ?? ''
                        );
                    }, $service_fields['qualities']) : []
            ),
            'projects_section' => array(
                'title' => $service_fields['projects_title'],
                'subtitle' => $service_fields['projects_subtitle'],
                'projects' => !empty($related_projects) ?
                    array_map(function ($project_id) {
                        return array(
                            'title' => get_the_title($project_id),
                            'slug' => get_post_field( 'post_name', $project_id),
                            'image' => get_the_post_thumbnail_url($project_id),
                            'logo' => get_field('logo', $project_id),
                            'title' => get_the_title($project_id) ?? '',
                            'excerpt' => get_the_excerpt($project_id) ?? '',
                            'rooms_design' => get_field('rooms_design',$project_id) ?? '',
                            'furniture_units' => get_field('furniture_units',$project_id) ?? '',
                            'weeks' => get_field('weeks',$project_id) ?? '',
                        );
                    }, $related_projects) : []
            ),
        ),
    );
}

/**
 * Get all service categories
 */
// function qounam_get_service_categories()
// {
//     $categories = get_terms(array(
//         'taxonomy' => 'service-category',
//         'hide_empty' => false,
//     ));

//     if (is_wp_error($categories)) {
//         return new WP_Error('no_categories', 'No categories found', array('status' => 404));
//     }

//     $formatted_categories = array_map(function ($category) {
//         return array(
//             'id' => $category->term_id,
//             'name' => htmlspecialchars_decode($category->name),
//             'slug' => $category->slug,
//             'count' => $category->count,
//             'description' => $category->description
//         );
//     }, $categories);

//     return array(
//         'success' => true,
//         'categories' => $formatted_categories
//     );
// }

/**
 * Enroll user in service
 */
// function qounam_enroll_service($request)
// {
//     $user_id = qounam_get_current_user_from_jwt();
//     $service_slug = $request['service_slug'];
//     $params = $request->get_json_params();

//     if (!$user_id) {
//         return new WP_Error('unauthorized', 'Unauthorized', array('status' => 401));
//     }

//     // Check if service exists
//     $service = get_page_by_path($service_slug, OBJECT, 'service');
//     if (!$service || $service->post_type !== 'service') {
//         return new WP_Error('service_not_found', 'Service not found', array('status' => 404));
//     }

//     $service_id = $service->ID;

//     $registration_button = get_field('register_button', $service_id);
//     $redirect_url = (!empty($registration_button)) ? $registration_button['url'] : '';
//     // Check if user already has a pending or approved request for this service
//     $existing_request = get_posts(array(
//         'post_type' => 'service-request',
//         'meta_query' => array(
//             'relation' => 'AND',
//             array(
//                 'key' => 'service',
//                 'value' => $service_id,
//                 'compare' => '='
//             ),
//             array(
//                 'key' => 'user_id',
//                 'value' => $user_id,
//                 'compare' => '='
//             )
//         )
//     ));

//     if (!empty($existing_request)) {
//         return new WP_Error('request_exists', 'You have already submitted a request for this service', array('status' => 400));
//     }

//     // Create new service request
//     $request_data = array(
//         'post_title' => 'Service Request - ' . get_the_title($service_id) . ' - ' . $params['first_name'] . ' ' . $params['last_name'],
//         'post_type' => 'service-request',
//         'post_status' => 'publish',
//         'meta_input' => array(
//             'first_name' => sanitize_text_field($params['first_name']),
//             'last_name' => sanitize_text_field($params['last_name']),
//             'email' => sanitize_email($params['email']),
//             'phone_number' => sanitize_text_field($params['phone_number']),
//             'government' => sanitize_text_field($params['government']),
//             'position' => sanitize_text_field($params['position']),
//             'company' => sanitize_text_field($params['company']),
//             'status' => 'pending',
//             'user_id' => $user_id,
//             'service' => $service_id,
//             'request_date' => current_time('mysql')
//         )
//     );

//     $request_id = wp_insert_post($request_data);

//     if (is_wp_error($request_id)) {
//         return new WP_Error('request_failed', 'Failed to submit request', array('status' => 500));
//     }

//     // Send notification email to admin
//     $admin_email = get_option('admin_email');
//     $subject = 'New Service Request: ' . get_the_title($service_id);
//     $message = 'A new service request has been submitted:<br>';
//     $message .= 'Service: ' . get_the_title($service_id) . '<br>';
//     $message .= 'Name: ' . $params['first_name'] . ' ' . $params['last_name'] . '<br>';
//     $message .= 'Email: ' . $params['email'] . '<br>';
//     $message .= 'Phone: ' . $params['phone_number'] . '<br>';
//     $message .= 'Company: ' . $params['company'] . '<br>';
//     $message .= 'Position: ' . $params['position'] . '<br>';
//     $message .= 'Government: ' . $params['government'] . '<br><br>';
//     $message .= 'View request: ' . admin_url('post.php?post=' . $request_id . '&action=edit');

//     $headers = array('Content-Type: text/html; charset=UTF-8');
//     wp_mail($admin_email, $subject, $message, $headers);

//     return array(
//         'success' => true,
//         'message' => 'Your request has been submitted successfully',
//         'request_id' => $request_id,
//         'redirect_url' => $redirect_url
//     );
// }

/**
 * Get user's wishlist
 */
// function qounam_get_wishlist($request)
// {
//     $user_id = qounam_get_current_user_from_jwt();

//     if (!$user_id) {
//         return new WP_Error('unauthorized', 'Unauthorized', array('status' => 401));
//     }

//     $wishlist = get_user_meta($user_id, 'wishlist', true) ?: array();
//     $wishlist = array_map('intval', $wishlist); // Ensure all IDs are integers

//     // Get service details for each item in wishlist
//     $services = array();
//     if (!empty($wishlist)) {
//         $args = array(
//             'post_type' => 'service',
//             'post__in' => $wishlist,
//             'posts_per_page' => -1,
//             'orderby' => 'post__in', // Maintain the order of IDs
//         );

//         $query = new WP_Query($args);
//         if ($query->have_posts()) {
//             while ($query->have_posts()) {
//                 $query->the_post();
//                 $service_id = get_the_ID();
//                 $services[] = array(
//                     'id' => $service_id,
//                     'title' => get_the_title(),
//                     'slug' => get_post_field('post_name'),
//                     'status' => get_field('status', $service_id),
//                     'thumbnail' => get_the_post_thumbnail_url($service_id),
//                     'hover_thumbnail' => get_field('hover_thumbnail', $service_id),
//                     'category' => get_terms(array(
//                         'taxonomy' => 'service-category',
//                         'hide_empty' => true,
//                         'object_ids' => $service_id
//                     ))[0]->name,
//                     'excerpt' => get_the_excerpt($service_id),
//                     'price' => get_field('price', $service_id),
//                     'sale_price' => get_field('sale_price', $service_id),
//                     'seats_available' => get_field('seats_available', $service_id),
//                     'limited_offer' => get_field('limited_offer', $service_id),
//                     'days_per_week' => get_field('days_per_week', $service_id),
//                     'duration_in_weeks' => get_field('duration_in_weeks', $service_id),
//                     'start_from' => get_field('start_from', $service_id),
//                     'ends_at' => get_field('ends_at', $service_id)
//                 );
//             }
//             wp_reset_postdata();
//         }
//     }

//     return array(
//         'success' => true,
//         'count' => count($wishlist),
//         'services' => $services
//     );
// }

/**
 * Add service to wishlist
 */
// function qounam_add_to_wishlist($request)
// {
//     $user_id = qounam_get_current_user_from_jwt();
//     $params = $request->get_json_params();
//     $service_id = isset($params['service_id']) ? intval($params['service_id']) : 0;

//     if (!$user_id) {
//         return new WP_Error('unauthorized', 'Unauthorized', array('status' => 401));
//     }

//     if (!$service_id) {
//         return new WP_Error('missing_service_id', 'Service ID is required', array('status' => 400));
//     }

//     // Check if service exists
//     $service = get_post($service_id);
//     if (!$service || $service->post_type !== 'service') {
//         return new WP_Error('service_not_found', 'Service not found', array('status' => 404));
//     }

//     // Get current wishlist
//     $wishlist = get_user_meta($user_id, 'wishlist', true) ?: array();
//     $wishlist = array_map('intval', $wishlist);

//     // Check if already in wishlist
//     if (in_array($service_id, $wishlist)) {
//         return new WP_Error('already_in_wishlist', 'Service is already in your wishlist', array('status' => 400));
//     }

//     // Add to wishlist
//     $wishlist[] = $service_id;
//     update_user_meta($user_id, 'wishlist', $wishlist);

//     return array(
//         'success' => true,
//         'message' => 'Service added to wishlist',
//         'wishlist_count' => count($wishlist)
//     );
// }

/**
 * Remove service from wishlist
 */
// function qounam_remove_from_wishlist($request)
// {
//     $user_id = qounam_get_current_user_from_jwt();
//     $params = $request->get_json_params();
//     $service_id = isset($params['service_id']) ? intval($params['service_id']) : 0;

//     if (!$user_id) {
//         return new WP_Error('unauthorized', 'Unauthorized', array('status' => 401));
//     }

//     if (!$service_id) {
//         return new WP_Error('missing_service_id', 'Service ID is required', array('status' => 400));
//     }

//     // Get current wishlist
//     $wishlist = get_user_meta($user_id, 'wishlist', true) ?: array();
//     $wishlist = array_map('intval', $wishlist);

//     // Find and remove service from wishlist
//     $index = array_search($service_id, $wishlist);
//     if ($index !== false) {
//         unset($wishlist[$index]);
//         $wishlist = array_values($wishlist); // Reindex array
//         update_user_meta($user_id, 'wishlist', $wishlist);
//     }

//     return array(
//         'success' => true,
//         'message' => 'Service removed from wishlist',
//         'wishlist_count' => count($wishlist)
//     );
// }


/**
 * Get services calendar grouped by month
 */
/**
 * Get services calendar grouped by month then by day-of-month (based on start_from)
 *
 * Response shape:
 * data: {
 *   january: [
 *     { DAYS: 3,  service: [ ...services starting on Jan 3... ] },
 *     { DAYS: 20, service: [ ... ] }
 *   ],
 *   february: [],
 *   ...
 * }
 */
// function qounam_get_services_calendar($request) {

//     $year = (int) ($request->get_param('year') ?: date('Y'));

//     $months_order = [
//         'january','february','march','april','may','june',
//         'july','august','september','october','november','december'
//     ];

//     // Pre-seed all months (in order) with empty arrays
//     $response = [
//         'success' => true,
//         'year'    => $year,
//         'data'    => array_fill_keys($months_order, []),
//     ];

//     $services = get_posts([
//         'post_type'   => 'service',
//         'post_status' => 'publish',
//         'numberposts' => -1,
//         'meta_query'  => [
//             [ 'key' => 'start_from', 'compare' => 'EXISTS' ],
//             [ 'key' => 'ends_at',    'compare' => 'EXISTS' ],
//         ],
//         'orderby'  => 'meta_value',
//         'meta_key' => 'start_from',
//         'order'    => 'ASC',
//     ]);

//     // Internal maps for grouping: month => day => bucket
//     $month_day_map = [];

//     foreach ($services as $service) {

//         $start_raw = get_field('start_from', $service->ID);
//         $end_raw   = get_field('ends_at', $service->ID);
//         if (empty($start_raw) || empty($end_raw)) continue;

//         // Support ACF-style d/m/Y OR ISO-ish dates
//         $start = DateTime::createFromFormat('d/m/Y', $start_raw) ?: new DateTime($start_raw);
//         $end   = DateTime::createFromFormat('d/m/Y', $end_raw)   ?: new DateTime($end_raw);

//         // Only include services whose START date is in requested year
//         if ((int) $start->format('Y') !== $year) continue;

//         $month_key = strtolower($start->format('F'));
//         $day_key   = (int) $start->format('d'); // 1..31

//         // duration in days (inclusive) — keep as metadata
//         $start0 = (clone $start)->setTime(0,0,0);
//         $end0   = (clone $end)->setTime(0,0,0);
//         $days_count = (int) $start0->diff($end0)->format('%a') + 1;

//         if (!isset($month_day_map[$month_key])) {
//             $month_day_map[$month_key] = [];
//         }

//         if (!isset($month_day_map[$month_key][$day_key])) {
//             $month_day_map[$month_key][$day_key] = [
//                 'DAYS'    => $day_key, // grouping by day
//                 'service' => [],
//             ];
//         }

//         $month_day_map[$month_key][$day_key]['service'][] = [
//             'id'          => $service->ID,
//             'title'       => $service->post_title,
//             'slug'        => $service->post_name,
//             'start_date'  => $start->format('Y-m-d'),
//             'end_date'    => $end->format('Y-m-d'),
//             'start_day'   => (int) $start->format('d'),
//             'end_day'     => (int) $end->format('d'),
//             'start_month' => strtolower($start->format('F')),
//             'end_month'   => strtolower($end->format('F')),
//             'year'        => (int) $start->format('Y'),
//             'days_count'  => $days_count, // duration
//             'status' => get_field('status', $service->ID),
//             'thumbnail' => get_the_post_thumbnail_url($service->ID),
//             'hover_thumbnail' => get_field('hover_thumbnail', $service->ID),
//             'category' => get_terms(array(
//                 'taxonomy' => 'service-category',
//                 'hide_empty' => true,
//                 'object_ids' => $service->ID
//             ))[0]->name,
//             'excerpt' => get_the_excerpt($service->ID),
//             'price' => get_field('price', $service->ID),
//             'sale_price' => get_field('sale_price', $service->ID),
//             'seats_available' => get_field('seats_available', $service->ID),
//             'limited_offer' => get_field('limited_offer', $service->ID),
//             'days_per_week' => get_field('days_per_week', $service->ID),
//             'duration_in_weeks' => get_field('duration_in_weeks', $service->ID),
//         ];
//     }

//     // Build response months in fixed order, with day buckets sorted
//     foreach ($months_order as $m) {
//         if (!isset($month_day_map[$m])) {
//             $response['data'][$m] = [];
//             continue;
//         }
//         ksort($month_day_map[$m]);                 // sort by day 1..31
//         $response['data'][$m] = array_values($month_day_map[$m]);
//     }

//     return $response;
// }



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
