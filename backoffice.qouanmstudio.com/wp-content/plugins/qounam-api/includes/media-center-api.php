<?php
/**
 * Media Center API Endpoints
 * 
 * Handles media center content (Blog, Gallery, Videos)
 */

if (!defined('ABSPATH')) {
    exit;
}

// Register custom endpoints
add_action('rest_api_init', function() {
    // Get media center content by type
    register_rest_route('qounam/v1', '/media-center/(?P<type>blog|gallery|videos)', array(
        'methods' => 'GET',
        'callback' => 'qounam_get_media_center',
        'permission_callback' => '__return_true',
        'args' => array(
            'type' => array(
                'validate_callback' => function($param) {
                    return in_array($param, array('blog', 'gallery', 'videos'));
                }
            ),
            'page' => array(
                'validate_callback' => function($param) {
                    return is_numeric($param);
                },
                'default' => 1
            ),
            'per_page' => array(
                'validate_callback' => function($param) {
                    return is_numeric($param);
                },
                'default' => 10
            ),
        ),
    ));

    // Get single blog post
    register_rest_route('qounam/v1', '/blog/(?P<slug>[\w-]+)', array(
        'methods' => 'GET',
        'callback' => 'qounam_get_single_blog',
        'permission_callback' => '__return_true',
    ));
});

/**
 * Get media center content by type
 */
function qounam_get_media_center($request) {
    $type = $request['type'];
    $page = max(1, intval($request->get_param('page')));
    $per_page = intval($request->get_param('per_page'));
    
    switch ($type) {
        case 'blog':
            return qounam_get_blog_posts($page, $per_page);
            
        case 'gallery':
            return qounam_get_gallery();
            
        case 'videos':
            return qounam_get_videos();
            
        default:
            return new WP_Error('invalid_type', 'Invalid media type', array('status' => 400));
    }
}

/**
 * Get blog posts
 */
function qounam_get_blog_posts($page = 1, $per_page = 10) {
    $args = array(
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => $per_page,
        'paged' => $page,
        'orderby' => 'date',
        'order' => 'DESC',
    );

    $query = new WP_Query($args);
    $total_posts = $query->found_posts;
    $total_pages = ceil($total_posts / $per_page);

    $posts = array();
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post = get_post();
            $post_id = $post->ID;
            
            // Get categories
            $categories = get_the_category($post_id);
            $category_list = array();
            if (!empty($categories)) {
                foreach ($categories as $category) {
                    $category_list[] = array(
                        'id' => $category->term_id,
                        'name' => $category->name,
                        'slug' => $category->slug
                    );
                }
            }

            $posts[] = array(
                'id' => $post_id,
                'title' => get_the_title(),
                'slug' => $post->post_name,
                'excerpt' => get_the_excerpt(),
                // 'content' => get_the_content(),
                'thumbnail' => get_the_post_thumbnail_url($post_id, 'medium_large'),
                'date' => get_the_date('Y-m-d H:i:s'),
                'permalink' => get_permalink(),
                'categories' => $category_list,
                'author' => get_the_author_meta('display_name')
            );
        }
        wp_reset_postdata();
    }

    return array(
        'success' => true,
        'type' => 'blog',
        'page' => $page,
        'per_page' => $per_page,
        'total_items' => $total_posts,
        'total_pages' => $total_pages,
        'items' => $posts
    );
}

/**
 * Get gallery images
 */
function qounam_get_gallery() {
    $page_id = 185;
    $gallery = get_field('gallery_images', $page_id);
    
    if (empty($gallery)) {
        return array(
            'success' => true,
            'type' => 'gallery',
            'items' => array(),
            'message' => 'No gallery items found'
        );
    }
    
    $formatted_gallery = array_map(function($image_url) {
        return array(
            'url' => $image_url,
        );
    }, $gallery);
    
    return array(
        'success' => true,
        'type' => 'gallery',
        'items' => $formatted_gallery,
        'count' => count($formatted_gallery)
    );
}

/**
 * Get videos
 */
function qounam_get_videos() {
    $page_id = 185;
    $videos = get_field('videos', $page_id);
    
    if (empty($videos)) {
        return array(
            'success' => true,
            'type' => 'videos',
            'items' => array(),
            'message' => 'No videos found'
        );
    }
    
    $formatted_videos = array_map(function($video_url) {
        return array(
            'url' => $video_url,
        );
    }, $videos);
    
    return array(
        'success' => true,
        'type' => 'videos',
        'items' => $formatted_videos,
        'count' => count($formatted_videos)
    );
}

/**
 * Get single blog post by slug
 */
function qounam_get_single_blog($request) {
    $slug = $request['slug'];
    
    $args = array(
        'name' => $slug,
        'post_type' => 'post',
        'post_status' => 'publish',
        'numberposts' => 1
    );
    
    $posts = get_posts($args);
    
    if (empty($posts)) {
        return new WP_Error('post_not_found', 'Post not found', array('status' => 404));
    }
    
    $post = $posts[0];
    $post_id = $post->ID;
    
    // Get categories
    $categories = get_the_category($post_id);
    $category_list = array();
    if (!empty($categories)) {
        foreach ($categories as $category) {
            $category_list[] = array(
                'id' => $category->term_id,
                'name' => $category->name,
                'slug' => $category->slug
            );
        }
    }
    
    // Get featured image
    $thumbnail = get_the_post_thumbnail_url($post_id, 'full');
    
    // Get author info
    $author_id = $post->post_author;
    $author = array(
        'id' => $author_id,
        'name' => get_the_author_meta('display_name', $author_id),
        'avatar' => get_avatar_url($author_id)
    );
    
    // Get related posts (optional)
    $related_posts = array();
    $related_args = array(
        'category__in' => wp_get_post_categories($post_id),
        'post__not_in' => array($post_id),
        'posts_per_page' => 3,
        'orderby' => 'rand'
    );
    $related_query = new WP_Query($related_args);
    
    if ($related_query->have_posts()) {
        while ($related_query->have_posts()) {
            $related_query->the_post();
            $related_posts[] = array(
                'id' => get_the_ID(),
                'title' => get_the_title(),
                'slug' => $post->post_name,
                'thumbnail' => get_the_post_thumbnail_url(get_the_ID(), 'medium'),
                'date' => get_the_date('Y-m-d H:i:s'),
                'permalink' => get_permalink()
            );
        }
        wp_reset_postdata();
    }
    
    $response = array(
        'success' => true,
        'id' => $post_id,
        'title' => $post->post_title,
        'slug' => $post->post_name,
        'content' => apply_filters('the_content', $post->post_content),
        'excerpt' => get_the_excerpt($post),
        'thumbnail' => $thumbnail,
        'cover' => get_field('cover', $post_id),
        'date' => get_the_date('Y-m-d H:i:s', $post),
        'modified' => $post->post_modified,
        'permalink' => get_permalink($post_id),
        'categories' => $category_list,
        'author' => $author,
        'related_posts' => $related_posts
    );
    
    return $response;
}
