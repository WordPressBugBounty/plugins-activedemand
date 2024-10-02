<?php

namespace ActiveDemand;


use WP_Error;

class FormLinker
{

    public $forms = array();

    public static $customer_actions = array();

    public static $order_status_actions = array();
    public static $last_response;
    public static $form_xml = NULL;

    public static function initialize_class_vars()
    {
        self::$customer_actions = array(
                'Customer Created' => 'woocommerce_created_customer',
                'Customer Updated' => 'profile_updated'
        );

        self::$order_status_actions = array(
                'Order Pending' => 'woocommerce_order_status_pending',
                'Order Processing' => 'woocommerce_order_status_processing',
                'Order Completed' => 'woocommerce_order_status_completed',
                'Order Cancelled' => 'woocommerce_order_status_cancelled',
                'Order Refunded' => 'woocommerce_order_status_refunded'
        );
    }

    public static function load_form_xml()
    {
        if (!isset(self::$form_xml) || !is_a(self::$form_xml, 'SimpleXMLElement')) {
            $url = activedemand_api_url("forms.xml");
            $str = activedemand_getHTML($url, 10);

            self::$form_xml = simplexml_load_string($str);
        }
        return self::$form_xml;
    }

    public static function build_full_linker()
    {
        self::load_form_xml();
        $arr = (array)self::$form_xml->children();
        $ids = array();
        foreach ($arr['form'] as $v) {
            $ids[] = (int)$v->id;
        }
        return new FormLinker($ids);
    }

    public static function form_list_dropdown($name, $atts = array(), $selected = null)
    {
        self::load_form_xml();

        $output = '<select name="' . $name . '"';
        foreach ($atts as $k => $v) {
            $output .= " $k=\"$v\"";
        }
        $output .= '><option value="0"';
        if (!isset($selected) || $selected == 0) $output .= ' selected>';
        $output .= 'None</option>';

        if (false !== self::$form_xml) {
            foreach (self::$form_xml->children() as $child) {
                $id = (int)$child->id;
                $output .= "<option value=\"$id\"";
                if ($id == $selected) $output .= ' selected';
                $output .= ">{$child->name}</option>";
            }
        }

        $output .= '</select>';
        return $output;
    }

    public static function form_link_table($arr, $atts = array())
    {
        $setting = get_option(PREFIX . '_wc_actions_forms');
        $output = "<table";
        foreach ($atts as $k => $v) {
            $output .= " $k=\"$v\"";
        }
        $output .= ">";
        foreach ($arr as $name => $hook) {
            $url = add_query_arg('form_mapper_show_nonce', wp_create_nonce($hook . '-show'),
                    add_query_arg('action', 'show_form_mapper',
                            add_query_arg('action_hook', $hook,
                                    admin_url('admin-ajax.php'))));
            if (isset($setting[$hook]) && $setting[$hook]) {
                $id = $setting[$hook];
                $style = "display:block;";
            } else {
                $id = NULL;
                $style = "display:none;";
            }
            $style .= 'text-decoration:none;color:black;';
            $output .= "<tr><td>$name</td>"
                    . "<td>"
                    . self::form_list_dropdown(PREFIX . "_wc_actions_forms[$hook]",
                            array('class' => 'ad-formlink-dropdown'), $id)
                    . wp_nonce_field($hook . '-reset', "form_mapper_reset_$hook", true, false)
                    . "</td>"
                    . '<td><a class="ad-edit-linkedform ' . $hook . '" style="' . $style . '"'
                    . 'href="' . esc_url($url)
                    . ' .ad_form_mapper" data-featherlight="ajax"><span class="dashicons dashicons-edit"></span></a>'
                    . "</td></tr>";
        }
        $output .= "</table>";
        return $output;
    }

    public static function linked_forms_page()
    {

        ?>
        <h2>Customer Profile Actions</h2>
        <?php echo self::form_link_table(self::$customer_actions, array('class' => 'customer_form_table')); ?>
        <h2>WooCommerce Order Status Changes</h2>
        <?php echo self::form_link_table(self::$order_status_actions, array('class' => 'order_form_table')); ?>
        <?php
    }

    public static function map_field_keys($map, $vars)
    {

        $order = isset($vars['order']) ? $vars['order'] : NULL;
        $cart = isset($vars['cart']) ? $vars['cart'] : NULL;
        $data = array();
        $user = isset($vars['user']) ? $vars['user'] : NULL;
        $user_id = NULL;

        if (isset($cart)) {
            $user_id = $cart['user_id'];
        //   error_log("cart:".$cart['user_id']);
        //   error_log(serialize($cart));
        }

        if (isset($user)){
            $user_id = $user->get_id();
            // error_log("have user, this is the user_id:". $user->get_id());
        }

        if (!isset($order) && !isset($cart)) {
            return $data;
        }

        if ($order) {
            $order_email = $order->get_billing_email();
                $user_id = email_exists($order_email);
            if (!$user_id) {
                $user_id = username_exists($order_email);
            }
            // error_log("order_email:". $order_email);
        }

        // error_log("user_id:". $user_id);
        if ($order && $user_id == false) {
            //guest
            $first_name = $order->get_billing_first_name();
            $last_name = $order->get_billing_last_name();
            $email = $order_email;
            // error_log("guest");
        } else {
            if (!isset($user) && isset($user_id)) {
                $user = new \WC_Customer($user_id);
                // error_log("created user");
            }
            $first_name = $user->get_first_name();
            $last_name = $user->get_last_name();
            $email = $user->get_email();
        }

        // error_log("email:". $email);
        foreach ($map as $name => $arg) {
            $key = str_replace("form[", "[field_", $name);
            // error_log($key . '-' . $arg);
            switch ($arg) {
                case 'username':
                    $data[$key] = $user->get_username();
                    // error_log("key:".$key." data:".$data[$key]);
                    break;
                case 'user_firstname':
                    $data[$key] = $first_name;
                    // error_log("key:".$key." data:".$data[$key]);
                    break;
                case 'user_lastname':
                    $data[$key] = $last_name;
                    // error_log("key:".$key." data:".$data[$key]);
                    break;
                case 'user_email':
                    $data[$key] = $email;
                    // error_log("key:".$key." data:".$data[$key]);
                    break;
                //cart link
                case 'cart_link':
                    if (!empty($order)) {
                        $data[$key] = esc_url(site_url('?recover-order='. $order->get_id()));
                    } elseif (isset($_COOKIE['active_demand_cookie_cart'])) {
                        $data[$key] = esc_url(site_url('?recover-order='. $order->get_id()));
                    } else {
                        if (isset($cart)) {
                            $order_id = !empty($order) ? $order->get_id() : '';
                            $data[$key] = esc_url(site_url('?recover-order='. $order_id));
                        }
                    }
                    // error_log("key:".$key." data:".$data[$key]);
                    break;
                //billing email address
                case 'billing_email':
                    if (!empty($order)) {
                        if (!empty($order->get_billing_email())) {
                            $data[$key] = $order->get_billing_email();
                        }
                    } elseif (!empty($user)) {
                        if (!empty($user->get_billing_email())) {
                            $data[$key] = $user->get_billing_email();
                        }
                    }
                    // error_log("key:".$key." data:".$data[$key]);
                    break;

                //billing address
                case 'billing_company':
                    if (!empty($order)) {
                        if (!empty($order->get_billing_company())) {
                            $data[$key] = $order->get_billing_company();
                        }
                    } elseif (!empty($user)) {
                        if (!empty($user->get_billing_company())) {
                            $data[$key] = $user->get_billing_company();
                        }
                    }
                    // error_log("key:".$key." data:".$data[$key]);
                    break;
                case 'billing_address_1':
                    if (!empty($order)) {
                        if (!empty($order->get_billing_address_1())) {
                            $data[$key] = $order->get_billing_address_1();
                        }
                    } elseif (!empty($user)) {
                        if (!empty($user->get_billing_address_1())) {
                            $data[$key] = $user->get_billing_address_1();
                        }
                    }
                    // error_log("key:".$key." data:".$data[$key]);
                    break;
                case 'billing_postcode':
                    if (!empty($order)) {
                        if (!empty($order->get_billing_postcode())) {
                            $data[$key] = $order->get_billing_postcode();
                        }
                    } elseif (!empty($user)) {
                        if (!empty($user->get_billing_postcode())) {
                            $data[$key] = $user->get_billing_postcode();
                        }
                    }
                    // error_log("key:".$key." data:".$data[$key]);
                    break;
                case 'billing_state':
                    if (!empty($order)) {
                        if (!empty($order->get_billing_state())) {
                            $data[$key] = $order->get_billing_state();
                        }
                    } elseif (!empty($user)) {
                        if (!empty($user->get_billing_state())) {
                            $data[$key] = $user->get_billing_state();
                        }
                    }
                    // error_log("key:".$key." data:".$data[$key]);
                    break;
                case 'billing_city':
                    if (!empty($order)) {
                        if (!empty($order->get_billing_city())) {
                            $data[$key] = $order->get_billing_city();
                        }
                    } elseif (!empty($user)) {
                        if (!empty($user->get_billing_city())) {
                            $data[$key] = $user->get_billing_city();
                        }
                    }
                    // error_log("key:".$key." data:".$data[$key]);
                    break;
                case 'billing_country':
                    if (!empty($order)) {
                        if (!empty($order->get_billing_country())) {
                            $data[$key] = $order->get_billing_country();
                        }
                    } elseif (!empty($user)) {
                        if (!empty($user->get_billing_country())) {
                            $data[$key] = $user->get_billing_country();
                        }
                    }
                    // error_log("key:".$key." data:".$data[$key]);
                    break;
                case 'billing_phone':
                    if (!empty($order)) {
                        if (!empty($order->get_billing_phone())) {
                            $data[$key] = $order->get_billing_phone();
                        }
                    } elseif (!empty($user)) {
                        if (!empty($user->get_billing_phone())) {
                            $data[$key] = $user->get_billing_phone();
                        }
                    }
                    // error_log("key:".$key." data:".$data[$key]);
                    break;
                //shipping address
                case 'shipping_company':
                    if (!empty($order)) {
                        if (!empty($order->get_shipping_company())) {
                            $data[$key] = $order->get_shipping_company();
                        }
                    } elseif (!empty($user)) {
                        if (!empty($user->get_shipping_company())) {
                            $data[$key] = $user->get_shipping_company();
                        }
                    }
                    // error_log("key:".$key." data:".$data[$key]);
                    break;
                case 'shipping_address_1':
                    if (!empty($order)) {
                        if (!empty($order->get_shipping_address_1())) {
                            $data[$key] = $order->get_shipping_address_1();
                        }
                    } elseif (!empty($user)) {
                        if (!empty($user->get_shipping_address_1())) {
                            $data[$key] = $user->get_shipping_address_1();
                        }
                    }
                    // error_log("key:".$key." data:".$data[$key]);
                    break;
                case 'shipping_postcode':
                    if (!empty($order)) {
                        if (!empty($order->get_shipping_postcode())) {
                            $data[$key] = $order->get_shipping_postcode();
                        }
                    } elseif (!empty($user)) {
                        if (!empty($user->get_shipping_postcode())) {
                            $data[$key] = $user->get_shipping_postcode();
                        }
                    }
                    // error_log("key:".$key." data:".$data[$key]);
                    break;
                case 'shipping_state':
                    if (!empty($order)) {
                        if (!empty($order->get_shipping_state())) {
                            $data[$key] = $order->get_shipping_state();
                        }
                    } elseif (!empty($user)) {
                        if (!empty($user->get_shipping_state())) {
                            $data[$key] = $user->get_shipping_state();
                        }
                    }
                    // error_log("key:".$key." data:".$data[$key]);
                    break;
                case 'shipping_city':
                    if (!empty($order)) {
                        if (!empty($order->get_shipping_city())) {
                            $data[$key] = $order->get_shipping_city();
                        }
                    } elseif (!empty($user)) {
                        if (!empty($user->get_shipping_city())) {
                            $data[$key] = $user->get_shipping_city();
                        }
                    }
                    // error_log("key:".$key." data:".$data[$key]);
                    break;
                case 'shipping_country':
                    if (!empty($order)) {
                        if (!empty($order->get_shipping_country())) {
                            $data[$key] = $order->get_shipping_country();
                        }
                    } elseif (!empty($user)) {
                        if (!empty($user->get_shipping_country())) {
                            $data[$key] = $user->get_shipping_country();
                        }
                    }
                    // error_log("key:".$key." data:".$data[$key]);
                    break;
                case 'shipping_phone':
                    if (!empty($order->get_shipping_phone())) {
                        $data[$key] = $order->get_shipping_phone();
                    } elseif (!empty($cart)) {
                        if (!empty($user->get_shipping_phone())) {
                            $data[$key] = $user->get_shipping_phone();
                        }
                    }
                    // error_log("key:".$key." data:".$data[$key]);
                    break;
                case 'product_ids':
                    $ids = array();
                    if (!empty($order)) {
                        foreach ($order->get_items() as $product) {
                            if (!is_a($product, 'WC_Order_Item_Product')) continue;
                            $ids[] = $product->get_product_id();
                        }
                        $data[$key] = \json_encode($ids);
                    }
                    // error_log("key:".$key." data:".$data[$key]);
                    break;
                case 'product_names':
                    if (!empty($order)) {
                        $names = array_map(function ($item) {
                            return $item->get_name();
                        }, $order->get_items());
                        $data[$key] = \json_encode($names);
                    }
                    // error_log("key:".$key." data:".$data[$key]);
                    break;
                case 'product_prices':
                    if (!empty($order)) {
                        $price_map = array();
                        foreach ($order->get_items() as $product) {
                            if (!is_a($product, 'WC_Order_Item_Product')) continue;
                            $price_map[$product->get_name()] = $product->get_subtotal();
                        }
                        $data[$key] = \json_encode($price_map);
                    }
                    // error_log("key:".$key." data:".$data[$key]);
                    break;
                case 'cart_product_data':
                    if ($cart) {
                        $products = isset($cart['cart']['cart']) ? $cart['cart']['cart'] : NULL;
                        if ($products) {
                            $data[$key] = '';
                            foreach ($products as $product) {
                                $product_obj = wc_get_product($product['product_id']);
                                $product_name = get_the_title($product['product_id']);
                                $product_price = $product_obj->get_price();
                                $data[$key] .= "Product Name: $product_name \n"
                                        . "Product price: " . $product_price . "\n"
                                        . 'Product Qty: ' . (isset($product['quantity']) ? $product['quantity'] : '') . "\n"
                                        . 'Total: ' . (isset($product['line_total']) ? $product['line_total'] : '') . "\n\n";
                            }
                        }
                    }
                    // error_log("key:".$key." data:".$data[$key]);
                    break;
                case 'order_id':
                    if (!empty($order)) {
                        $data[$key] = $order->get_id();
                    }
                    // error_log("key:".$key." data:".$data[$key]);
                    break;
                case 'order_value':
                    if (!empty($order)) {
                        $data[$key] = $order->get_total();
                    }
                    // error_log("key:".$key." data:".$data[$key]);
                    break;
                case 'order_state_change':
                    if (!empty($order)) {
                        $data[$key] = $order->get_status();
                    }
                    // error_log("key:".$key." data:".$data[$key]);
                    break;
                default:
                    if (isset($user->ID)) {
                        $data[$key] = get_user_meta($user->get_id(), $arg);
                    }
                //$data[$key]=get_user_meta($customer_id, $arg);

            }
        }

        //print_r($data);exit();
        return $data;
    }

    public static function initialize_hooks()
    {
        //Register all applicable customer actions

        foreach (self::$customer_actions as $hook) {
            $setting = get_option(PREFIX . "_form_$hook");
            if (!$setting || empty($setting)) continue;
            if (!isset($setting['id']) || !isset($setting['map'])) continue;
            add_action($hook, function ($customer_id) use ($setting) {
                $id = $setting['id'];
                $map = $setting['map'];
                $user = $user = new \WC_Customer($customer_id);//get_userdata($customer_id);
                $data = FormLinker::map_field_keys($map, array('user' => $user));
                $url = activedemand_api_url("forms/$id");
                // error_log("post to form data:".serialize($data));
                activedemand_postHTML($url, $data, 20);
            }, 15, 1);

        }

        //Register all applicable Order Status Changes actions
        foreach (self::$order_status_actions as $hook) {
            $setting = get_option(PREFIX . "_form_$hook");
            if (!$setting || empty($setting)) continue;
            if (!isset($setting['id']) || !isset($setting['map'])) continue;
            add_action($hook, function ($orderID) use ($setting) {
                $id = $setting['id'];
                $map = $setting['map'];
                $order = new \WC_Order($orderID);
                $user_id = (int)$order->get_user_id();
                $user = !empty($user_id) ? new \WC_Customer($user_id): NULL; //get_userdata($user_id) : NULL;
                $data = FormLinker::map_field_keys($map, array('user' => $user, 'order' => $order));
                $url = activedemand_api_url("forms/$id");
                // error_log("post to form data:".serialize($data));
                activedemand_postHTML($url, $data, 20);
            }, 15, 1);
        }

    }

    function __construct($ids)
    {
        $collector = ShortCodeCollector::get_instance();
        $collector->reset();
        $collector->server_side = true;
        if (is_array($ids)) {
            foreach ($ids as $id) {
                $collector->add_form($id);
            }
        } else {
            $collector->add_form($ids);
        }
        $reply = (array)\json_decode($collector->get_reply());
        foreach ($reply as $form) {
            $matches = array();
            if (\preg_match('/<form.*form>/s', (string)$form, $matches)) {
                $dom = new \DOMDocument();
                $dom->loadHTML($matches[0]);
                $id = $this->get_form_id($dom);
                $labels = $this->get_form_labels($dom);
                $this->forms[$id] = $labels;
            } else {
                new WP_Error('No Form Found in AD Reply');
            }
        }

    }

    function get_form_labels($form_dom)
    {
        $output = array();
        $labels = $form_dom->getElementsByTagName('label');
        foreach ($labels as $label) {
            $content = $label->textContent;
            $for = $label->attributes->getNamedItem('for')->nodeValue;
            $input = $form_dom->getElementById($for);
            $name = $input->attributes->getNamedItem('name')->nodeValue;
            $output[$name] = $content;
        }
        return $output;
    }

    function get_form_id($form_dom)
    {
        $matches = array();
        $form_array = $form_dom->getElementsByTagName('form');
        $form = $form_array[0];
        $form_attributes = $form->attributes;
        if (isset($form_attributes)) {
            $action = $form_attributes->getNamedItem('action')->nodeValue;
            \preg_match('/\d+$/', $action, $matches);
            return $matches[0];
        } else {
            new WP_Error('Form DOM returned ' . print_r($form_dom->getElementsByTagName, true));
        }
    }

    function get_form_field_dropdown($id, $name, $selected = NULL)
    {
        $labels = $this->forms[$id];
        $output = "<select name=\"$name\">";
        foreach ($labels as $name => $content) {
            $output .= "<option value=\"$label\"";
            if ($selected === $name) $output .= " selected";
            $option .= ">$content</option>";
        }
        $output .= "</select>";
        return $output;
    }

    function form_field_mapper($id, $options, $setting = array())
    {
        $labels = $this->forms[$id];
        $output = "<table>";
        foreach ($labels as $name => $content) {
            $selected = (!empty($setting) && isset($setting['map'][$name])) ?
                    $setting['map'][$name] : NULL;

            $output .= "<tr><td>".wp_kses($content, array())."</td><td>";
            $output .= "<select name=\"map[".wp_kses($name, array())."]\"><option";
            if (!isset($selected)) $output .= ' selected';
            $output .= ">None</option>";

            foreach ($options as $option => $description) {
                $opt_out = wp_kses($option, array());
                $desc_out = wp_kses($description, array());
                $output .= "<option value='$opt_out'";
                if ($option === $selected) $output .= ' selected';
                $output .= ">$desc_out</option>";
            }
            $output .= '</select>';
            $output .= "</td></tr>";
        }
        $output .= "</table>";
        return $output;
    }

    function form_mapper($id, $action)
    {
        $setting = get_option(PREFIX . "_form_$action") ? get_option(PREFIX . "_form_$action") : array();
        $options = array(
                'username' => 'User Name',
                'user_firstname' => 'First Name',
                'user_lastname' => 'Last Name',
                'user_email' => 'Email',
                'cart_link' => 'Cart Link',
                'billing_email' => 'Billing Email',
                'billing_company' => 'Billing Company',
                'billing_address_1' => 'Billing Street Address',
                'billing_postcode' => 'Billing Zipcode',
                'billing_state' => 'Billing State/Province',
                'billing_city' => 'Billing Town/City',
                'billing_country' => 'Billing Country',
                'billing_phone' => 'Billing Phone',
                'shipping_company' => 'Shipping Company',
                'shipping_address_1' => 'Shipping Street Address',
                'shipping_postcode' => 'Shipping Zipcode',
                'shipping_state' => 'Shipping State/Province',
                'shipping_city' => 'Shipping Town/City',
                'shipping_country' => 'Shipping Country'
        );
        if (in_array($action, self::$order_status_actions)) {
            $options = \array_merge($options, array(
                    'product_ids' => 'Product IDs',
                    'product_names' => 'Product Names',
                    'product_prices' => 'Product Prices',
                    'order_id' => 'Order ID',
                    'order_state_change' => 'Order State Change',
                    'order_value' => 'Order Value'
            ));
        }
        if ($action === PREFIX . '_stale_cart_map') {
            $options['cart_product_data'] = "Product Data";
        }
        return '<form class="ad_form_mapper ' . $action . '_mapper">'
                . wp_nonce_field($action . '-' . $id . '-update', 'form_mapper_update_nonce', true, false)
                . $this->form_field_mapper($id, $options, $setting)
                . '<div style="float:right;margin-top:15px;">'
                . '<input type="button" value="Save Changes" class="button-primary" onclick="ad_form_linker_update(event, ' . wp_kses($id, array()) . ', \'' . wp_kses($action, array()) . '\');">'
                . '<input type="button" value="Cancel" class="button-primary" '
                . 'style="color: black;background-color:white;text-shadow:none;border-color: black;margin-left: 5px;box-shadow:none;" '
                . 'onclick="jQuery.featherlight.close();" />'
                . '</div>'
                . '</form>';
    }

}

add_action('init', array(__NAMESPACE__ . '\FormLinker', 'initialize_hooks'));
add_action('plugins_loaded', array(__NAMESPACE__ . '\FormLinker', 'initialize_class_vars'));

add_action('wp_ajax_reset_ad_form_linkage', __NAMESPACE__ . '\ajax_reset_action_form');
add_action('wp_ajax_update_ad_form_linkage', __NAMESPACE__ . '\ajax_update_action_form');
add_action('wp_ajax_show_form_mapper', __NAMESPACE__ . '\ajax_show_form_mapper');

function ajax_reset_action_form()
{
    $action = \filter_var($_POST['action_hook'], \FILTER_SANITIZE_STRING);
    check_ajax_referer($action . '-reset', 'form_mapper_reset_nonce');

    $id = \filter_var($_POST['form_id'], \FILTER_SANITIZE_NUMBER_INT);

    check_ajax_referer($action . '-reset', 'form_mapper_reset_nonce');
    if ($id === 0) {
        delete_option(PREFIX . "_form_$action");
        echo "Form Deleted";
    } else if (update_option(PREFIX . "_form_$action", array('id' => $id))) {
        echo \esc_js(json_encode(array($action => $id)));
    } else {
        new WP_Error("Could not update $action to $id");
    }
    wp_die();
}

function ajax_update_action_form()
{

    $action = \filter_var($_POST['action_hook'], \FILTER_SANITIZE_STRING);
    $id = \filter_var($_POST['form_id'], \FILTER_SANITIZE_NUMBER_INT);

    check_ajax_referer($action . '-' . $id . '-update', 'form_mapper_update_nonce');

    $map = array();
    foreach ($_POST['map'] as $k => $v) {
        $map[sanitize_text_field($k) . ']'] = sanitize_text_field($v);
    }


    $option = array('id' => $id, 'map' => $map);

    if (update_option(PREFIX . "_form_$action", $option)) {
        $option['action'] = $action;
        echo \esc_js(json_encode($option));
    } else {
        new WP_Error("Could not update $action");
    }
    wp_die();
}

function ajax_show_form_mapper()
{
    $action = sanitize_text_field($_GET['action_hook']);
    check_ajax_referer($action . '-show', 'form_mapper_show_nonce');

    $setting = get_option(PREFIX . "_form_$action");
    $id = $setting['id'];
    $linker = new FormLinker($id);
    echo $linker->form_mapper($id, $action);
    wp_die();
}

add_action('admin_enqueue_scripts', function () {
    wp_enqueue_script('featherlight', plugins_url('/includes/featherlight/featherlight.min.js', __FILE__), array('jquery'));
    wp_enqueue_style('featherlight-style', plugins_url('/includes/featherlight/featherlight.min.css', __FILE__));
    wp_enqueue_script('activedemand-formlinker', plugins_url('/includes/activedemand-admin-formlinker.js', __FILE__), array('jquery'), '0.1');

    wp_enqueue_style('activedemand-jquery-modal-style', plugins_url('/includes/jquery.modal.min.css', __FILE__));
    wp_enqueue_script('activedemand-jquery-modal-script', plugins_url('/includes/jquery.modal.min.js', __FILE__), array('jquery'), '0.1');
});

?>
