<?php
/**
 * FAQ API Endpoints
 */

if (!defined('ABSPATH')) {
    exit;
}

// Register FAQ endpoints
add_action('rest_api_init', function() {
    // Get all FAQs
    register_rest_route('qounam/v1', '/faqs', array(
        'methods' => 'GET',
        'callback' => 'qounam_get_faqs',
        'permission_callback' => '__return_true'
    ));
});

/**
 * Get all FAQs with ACF fields
 */
function qounam_get_faqs() {
    // Check if ACF is active
    if (!function_exists('get_field')) {
        return new WP_REST_Response(array(
            'success' => false,
            'message' => 'ACF is not active',
            'data' => array()
        ), 500);
    }

    // Get the FAQ section group
    $faq_section = get_field('faqs_section', 'option');
    
    if (!$faq_section) {
        return new WP_REST_Response(array(
            'success' => true,
            'data' => array(
                'red_label_text' => '',
                'title' => '',
                'image' => '',
                'faqs' => array()
            )
        ));
    }

    // Format the response
    $response = array(
        'red_label_text' => $faq_section['red_label_text'] ?? '',
        'title' => $faq_section['title'] ?? '',
        'image' => $faq_section['image'] ? $faq_section['image'] : '',
        'faqs' => array()
    );

    // Process FAQ items if they exist
    if (!empty($faq_section['faqs'])) {
        foreach ($faq_section['faqs'] as $index => $faq) {
            $response['faqs'][] = array(
                'id' => $index + 1,
                'question' => $faq['question'] ?? '',
                'answer' => $faq['answer'] ?? '',
                'featured' => (bool) ($faq['featured'] ?? false)
            );
        }
    }

    return new WP_REST_Response(array(
        'success' => true,
        'data' => $response
    ));
}
