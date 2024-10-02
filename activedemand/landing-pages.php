<?php

namespace ActiveDemand;

define(__NAMESPACE__ . '\LANDING_META', 'active_demand_landing_page');

define(__NAMESPACE__ . '\LANDING_ID_URL', activedemand_api_url('cta_pages.json'));

define(__NAMESPACE__ . '\LANDING_HTML_URL', activedemand_api_url('cta_pages/'));


add_action('admin_enqueue_scripts', __NAMESPACE__ . '\activedemand_landingpage_scripts');


/*
 * Enqueue Scripts unique to the Landing Page
 */
function activedemand_landingpage_scripts()
{
    wp_enqueue_script('jquery');
    wp_enqueue_script('activedemand-admin-landing-page', plugins_url('/includes/activedemand-landing-pages.js', __FILE__), array('jquery'), '1.0');
    wp_localize_script('activedemand-admin-landing-page', 'adAjax', array('ajax_url' => admin_url('admin-ajax.php'), 'ad_prefix' => PREFIX));
}

/**
 * Function to override wordpress with activedemand landing page
 * @return none
 */


function activedemand_return_landing_page()
{
    $options = retrieve_activedemand_options();
    if (!array_key_exists(PREFIX . '_appkey', $options)) return;
    $lp = _activedemand_return_landing_page();
    if ($lp) {
        //define anti-caching constants
        if (!defined('DONOTCACHEPAGE')) define('DONOTCACHEPAGE', TRUE);
        if (!defined('DONOTCDN')) define('DONOTCDN', TRUE);
        if (!defined('DONOTCACHEDB')) define('DONOTCACHEDB', TRUE);
        if (!defined('DONOTMINIFY')) define('DONOTMINIFY', TRUE);
        if (!defined('DONOTCACHEOBJECT')) define('DONOTCACHEOBJECT', TRUE);
        // TODO: this is a full html/css/javascript landing page from out system        
        die($lp);
    }
}


add_action('wp', __NAMESPACE__ . '\activedemand_return_landing_page', 1);

/**
 * Formatting function for page url
 * @param string $url
 * @param int $id
 * @return string
 */
function activedemand_append_pageid($url, $id)
{
    return $url . $id . '.html';
}


function _activedemand_return_landing_page()
{
    global $wp_query;
    if (!$wp_query->is_page) return FALSE;
    $post_id = get_queried_object_id();
    $landing_page_id = get_post_meta($post_id, LANDING_META, TRUE);
    if ($landing_page_id === FALSE) return FALSE;
    $html = (string)activedemand_get_landing_html($landing_page_id);
    return $html;

}


/**
 * Retrieves landing Page using ActiveDemand API
 * @param int $id
 * @return string html of landing page
 */

function activedemand_get_landing_html($id)
{
    if (isset($id) && !(empty($id))) {
        $url = activedemand_append_pageid(LANDING_HTML_URL, $id);
        $html = activedemand_getHTML($url, 10);
    } else {
        $html = "";
    }
    return $html;
}


/**
 *
 * @return array of Landing Pages
 */

function activedemand_get_landing_ids()
{
    $json = activedemand_getHTML(LANDING_ID_URL, 10);
    $arr = json_decode($json, TRUE);
    return $arr;
}

add_action('wp_ajax_get_' . PREFIX . '_landing_html', __NAMESPACE__ . '\activedemand_ajax_get_landing_html');


function activedemand_ajax_get_landing_html()
{
    $nonce_name = PREFIX . '-landing-nonce';
    if (!isset($_POST[$nonce_name]) || !isset($_POST['page'])) wp_die('Wrong Post');
    $page_id = filter_var($_POST['page'], FILTER_SANITIZE_NUMBER_INT);
    $action = 'ad_landing-' . $page_id;
    if (!check_ajax_referer($action, $nonce_name)) wp_die('bad nonce');
    $lp_id = filter_var($_POST['activedemand_landing_id'], FILTER_SANITIZE_NUMBER_INT);
    if (!empty($lp_id)) {
        $html = activedemand_get_landing_html($lp_id);
        // TODO: this is a full html/css/javascript landing page from out system
        die($html);
    }

    wp_die();

}


/**
 * Include the ActiveDEMNAND Landing Page Meta box on Pages
 */
add_action('add_meta_boxes', __NAMESPACE__ . '\activedemand_landingpage_metaboxes');
function activedemand_landingpage_metaboxes()
{
    //activedemand guard statements
    $options = retrieve_activedemand_options();
    if (!is_array($options)) return;
    if (!array_key_exists(PREFIX . '_appkey', $options)) return;
    add_meta_box(PREFIX . '-landing-page', PLUGIN_VENDOR . ' Landing Page',
            __NAMESPACE__ . '\activedemand_landing_metabox',
            'page',
            'side',
            'low');
}


function activedemand_landing_metabox($post)
{
    $lp_id = get_post_meta($post->ID, LANDING_META, TRUE);
    $lps = activedemand_get_landing_ids();
    if (!is_array($lps)) {
        echo "No Landing Pages Configured";
        return;
    }
    $output = '<h4>' . PLUGIN_VENDOR . ' Landing Page</h4>';
    $output .= wp_nonce_field('ad_landing-' . $post->ID, PREFIX . '-landing-nonce');
    $output .= '<input type="checkbox" id="is-' . PREFIX . '-landing" name="is-' . PREFIX . '-landing" value="true"';
    if ($lp_id) $output .= ' checked';
    $output .= '><label for="is-activedemand-landing">'
            . 'Set as ' . PLUGIN_VENDOR . ' Landing Page</label><br>'
            . '<select name="' . PREFIX . '-landing-id" id="' . PREFIX . '-landing-id"><option> </option>';

    foreach ($lps as $l) {
        $output .= '<option value="' . wp_kses($l['id'], array()) . '"';
        if ($l['id'] == $lp_id) $output .= ' selected';
        $output .= '>' . wp_kses($l['name'], array()) . '</option>';
    }
    $output .= '</select>';
    echo $output;
}

add_action('save_post', __NAMESPACE__ . '\activedemand_save_landing_page');


function activedemand_save_landing_page($post_id)
{
    if (!isset($_POST[PREFIX . '-landing-nonce'])) return;
    if (!wp_verify_nonce(sanitize_text_field($_POST[PREFIX . '-landing-nonce']), 'ad_landing-' . $post_id)) return;
    $index = 'is-' . PREFIX . '-landing';
    $is_landing = isset($_POST[$index]) ? filter_var($_POST[$index], FILTER_VALIDATE_BOOLEAN) : FALSE;
    $page_id = filter_var($_POST[PREFIX . '-landing-id'], FILTER_SANITIZE_NUMBER_INT);
    if ($is_landing) {
        update_post_meta($post_id, LANDING_META, $page_id);
    } else {
        delete_post_meta($post_id, LANDING_META);
    }
}
