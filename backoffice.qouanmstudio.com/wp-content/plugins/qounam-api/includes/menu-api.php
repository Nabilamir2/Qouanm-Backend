<?php
/**
 * Menu API Endpoints for Qounam
 */

if (!defined('ABSPATH')) {
    exit;
}

// Register menu endpoints
add_action('rest_api_init', function() {
    // Get menu by slug
    register_rest_route('qounam/v1', '/menu/(?P<slug>[\w-]+)', array(
        'methods' => 'GET',
        'callback' => 'qounam_get_menu_by_slug',
        'permission_callback' => '__return_true',
        'args' => array(
            'slug' => array(
                'required' => true,
                'validate_callback' => function($param) {
                    return is_string($param);
                }
            ),
            'depth' => array(
                'required' => false,
                'default' => 0,
                'validate_callback' => function($param) {
                    return is_numeric($param);
                }
            )
        )
    ));

    // List all menus
    register_rest_route('qounam/v1', '/menus', array(
        'methods' => 'GET',
        'callback' => 'qounam_get_all_menus',
        'permission_callback' => '__return_true'
    ));
});

/**
 * Get menu by slug
 */
function qounam_get_menu_by_slug($request) {
    $slug = $request->get_param('slug');
    $depth = (int) $request->get_param('depth');
    
    if (empty($slug)) {
        return new WP_Error('no_slug', 'Menu slug is required', array('status' => 400));
    }
    
    $locations = get_nav_menu_locations();
    $menu_id = null;
    
    // First try to find by theme location
    if (isset($locations[$slug])) {
        $menu_id = $locations[$slug];
    } 
    // If not found by location, try by slug/name
    else {
        $menu = wp_get_nav_menu_object($slug);
        if ($menu) {
            $menu_id = $menu->term_id;
        }
    }
    
    if (!$menu_id) {
        return new WP_Error('menu_not_found', 'Menu not found', array('status' => 404));
    }
    
    $menu_items = wp_get_nav_menu_items($menu_id, array('update_post_term_cache' => false));
    
    if (is_wp_error($menu_items) || empty($menu_items)) {
        return array('items' => array());
    }
    
    // Format menu items into a hierarchical structure
    $menu_items_by_id = array();
    $menu_items_flat = array();
    
    foreach ($menu_items as $item) {
        $menu_item = array(
            'id' => $item->ID,
            'title' => $item->title,
            'slug' => $item->post_name,
            'url' => $item->url,
            'target' => $item->target,
            'classes' => $item->classes,
            'description' => $item->description,
            'type' => $item->type,
            'type_label' => $item->type_label,
            'menu_order' => $item->menu_order,
            'menu_item_parent' => (int) $item->menu_item_parent,
            'children' => array()
        );
        
        // Add custom fields if ACF is active
        if (function_exists('get_fields')) {
            $menu_item['custom_fields'] = get_fields($item->ID);
        }
        
        $menu_items_by_id[$item->ID] = $menu_item;
        $menu_items_flat[] = &$menu_items_by_id[$item->ID];
    }
    
    // Build the hierarchy
    $menu_tree = array();
    
    foreach ($menu_items_flat as $key => $item) {
        $parent_id = $item['menu_item_parent'];
        
        if ($parent_id === 0) {
            $menu_tree[] = &$menu_items_flat[$key];
        } elseif (isset($menu_items_by_id[$parent_id])) {
            if (!isset($menu_items_by_id[$parent_id]['children'])) {
                $menu_items_by_id[$parent_id]['children'] = array();
            }
            $menu_items_by_id[$parent_id]['children'][] = &$menu_items_flat[$key];
        }
    }
    
    // Limit depth if specified
    if ($depth > 0) {
        $menu_tree = qounam_limit_menu_depth($menu_tree, $depth);
    }
    
    // Get menu object for additional data
    $menu = wp_get_nav_menu_object($menu_id);
    
    return array(
        'id' => $menu->term_id,
        'name' => $menu->name,
        'slug' => $menu->slug,
        'count' => $menu->count,
        'items' => $menu_tree
    );
}

/**
 * Get all registered menus
 */
function qounam_get_all_menus() {
    $menus = wp_get_nav_menus();
    $locations = get_nav_menu_locations();
    
    $formatted_menus = array();
    
    foreach ($menus as $menu) {
        $location = array_search($menu->term_id, $locations);
        
        $formatted_menus[] = array(
            'id' => $menu->term_id,
            'name' => $menu->name,
            'slug' => $menu->slug,
            'count' => $menu->count,
            'location' => $location ? $location : null,
            'items_count' => wp_get_nav_menu_items($menu->term_id) ? count(wp_get_nav_menu_items($menu->term_id)) : 0
        );
    }
    
    return $formatted_menus;
}

/**
 * Limit menu depth
 */
function qounam_limit_menu_depth($items, $depth, $current_level = 0) {
    if ($current_level >= $depth) {
        return array();
    }
    
    foreach ($items as &$item) {
        if (!empty($item['children'])) {
            $item['children'] = qounam_limit_menu_depth($item['children'], $depth, $current_level + 1);
        }
    }
    
    return $items;
}
