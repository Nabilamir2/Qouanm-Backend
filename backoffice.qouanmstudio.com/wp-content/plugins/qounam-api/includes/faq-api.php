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
 * Get all FAQs with ACF fields (including categories)
 */
function qounam_get_faqs() {
    if (!function_exists('get_field')) {
        return new WP_REST_Response(array(
            'success' => false,
            'message' => 'ACF is not active',
            'data' => array()
        ), 500);
    }

    $faq_section = get_field('faqs_section', 'option');

    if (!$faq_section) {
        return new WP_REST_Response(array(
            'success' => true,
            'data' => array(
                'label' => '',
                'title' => '',
                'categories' => array(
                    array('value' => 'all', 'label' => 'All FAQs')
                ),
                'faqs' => array()
            )
        ));
    }

    /**
     * Get category choices from the ACF field definition
     * (faqs_section -> faqs (repeater) -> category (select))
     */
    $category_choices = array();
    $faq_section_field_obj = get_field_object('faqs_section', 'option');

    if (!empty($faq_section_field_obj['sub_fields'])) {
        foreach ($faq_section_field_obj['sub_fields'] as $sub) {
            // Find the repeater field "faqs"
            if (!empty($sub['name']) && $sub['name'] === 'faqs' && !empty($sub['sub_fields'])) {
                foreach ($sub['sub_fields'] as $faq_sub) {
                    // Find the select field "category"
                    if (!empty($faq_sub['name']) && $faq_sub['name'] === 'category' && !empty($faq_sub['choices'])) {
                        $category_choices = $faq_sub['choices']; // value => label
                        break 2;
                    }
                }
            }
        }
    }

    $response = array(
        'label' => $faq_section['label'] ?? '',
        'title' => $faq_section['title'] ?? '',
        'categories' => array(), 
        'faqs' => array()
    );

    // Collect categories that actually appear in FAQ rows
    $used_category_values = array();

    if (!empty($faq_section['faqs'])) {
        foreach ($faq_section['faqs'] as $index => $faq) {
            $cat_values = isset($faq['category']) ? (array) $faq['category'] : array();
            $cat_values = array_values(array_filter(array_map('sanitize_text_field', $cat_values)));

            foreach ($cat_values as $cv) {
                $used_category_values[$cv] = true;
            }

            $response['faqs'][] = array(
                'id' => $index + 1,
                'question' => $faq['question'] ?? '',
                'answer' => $faq['answer'] ?? '',
                'featured' => (bool) ($faq['featured'] ?? false),

                // return both raw values + labels
                'categories' => array_map(function($val) use ($category_choices) {
                    return array(
                        'value' => $val,
                        'label' => $category_choices[$val] ?? $val,
                    );
                }, $cat_values),
            );
        }
    }

    // Build categories list for filters (All FAQs + only categories used in data)
    $response['categories'][] = array('value' => 'all', 'label' => 'All FAQs');

    $used_values = array_keys($used_category_values);

    if (!empty($category_choices)) {
        foreach ($category_choices as $value => $label) {
            if (in_array((string) $value, $used_values, true)) {
                $response['categories'][] = array(
                    'value' => (string) $value,
                    'label' => (string) $label
                );
            }
        }
    } else {
        foreach ($used_values as $value) {
            $response['categories'][] = array(
                'value' => (string) $value,
                'label' => (string) $value
            );
        }
    }

    return new WP_REST_Response(array(
        'success' => true,
        'data' => $response
    ));
}