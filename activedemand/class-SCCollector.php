<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ActiveDemand;

class ShortCodeCollector{
    public $url;
    public $server_side;
  	public $posts_processed=array();
    private $blocks=array();
    public $forms=array();
    public $storyboards=array();
    private $show_popups;
    private $has_fired;
    private $guid_value;
    private $reply;

    public static function get_instance(){
        static $instance=NULL;
        if(!isset($instance)){
            $instance=new ShortCodeCollector();
        }
        return $instance;
    }


    private function __construct() {
        $this->url = activedemand_api_url('smart_blocks/show_all');
        $options = retrieve_activedemand_options();
        $show = get_option(PREFIX.'_server_showpopups');
        $this->show_popups=(is_array($options) && array_key_exists(PREFIX.'_appkey', $options) && $show);
        $this->server_side=get_option(PREFIX.'_server_side', TRUE);
        if (!isset($this->server_side)) {
            $this->server_side=TRUE;
        }
        $this->has_fired=FALSE;
    }
    public function reset(){
        $this->has_fired=FALSE;
        $this->blocks=array();
	    $this->forms=array();
        $this->storyboards=array();
    }

    private function add_shortcode($id, $slug){
        $div='activedemand_'.$slug.'_'.count($this->$slug);
        $this->$slug[$div]=$id;
        return $div;
    }

    public function has_content(){
        return (count($this->blocks) + count($this->forms) + count($this->storyboards) >0) || $this->show_popups;
    }

    public function add_block($id){
        $div='activedemand_blocks_'.count($this->blocks);
        $this->blocks[$div]=$id;
        return $div;
    }

    public function add_form($id){
        $div='activedemand_forms_'.count($this->forms);
        $this->forms[$div]=$id;
        return $div;
    }

    public function add_storyboard($id){
        $div='activedemand_storyboard_'.count($this->storyboards);
        $this->storyboards[$div]=$id;
        return $div;
    }

    public function make_args(){
        $options = retrieve_activedemand_options();
        $activedemand_ignore_block_style = false;
        $activedemand_ignore_form_style = false;
        if (array_key_exists(PREFIX.'_ignore_block_style', $options)) {
            $activedemand_ignore_block_style = $options[PREFIX.'_ignore_block_style'];
        }
        if (array_key_exists(PREFIX.'_ignore_form_style', $options)) {
            $activedemand_ignore_form_style = $options[PREFIX.'_ignore_form_style'];
        }
        return array(
            'exclude_block_css'=>$activedemand_ignore_block_style,
            'exclude_form_css'=>$activedemand_ignore_form_style,
            'shortcodes'=> $this->get_codes(),
            PREFIX.'_session_guid' => activedemand_get_cookie_value()
        );
    }

    public function post_codes(){

        if(!$this->server_side){
            throw new \Exception('Method must be Server Side for ShortCodeCollector to POST');
        }

        $args= $this->make_args();
        $timeout=10;
        $response= activedemand_postHTML($this->url, $args, $timeout);
        $this->has_fired=TRUE;
        $this->reply=$response;
        return $response;
    }
    public function get_reply(){
        if(!$this->has_fired) $this->post_codes();
        return $this->reply;
    }

    public function get_codes(){
        return json_encode((object) array('forms'=> (object) $this->forms,
                                    'popups'=> $this->show_popups,
                                    'blocks'=> (object) $this->blocks,
                                    'storyboards'=> (object) $this->storyboards
                                    ));
    }

}

add_shortcode(PREFIX.'_block', __NAMESPACE__.'\activedemand_process_block_shortcode');

function activedemand_process_block_shortcode($atts, $content = null){

    $id = "";
    //$id exists after this call.
    extract(shortcode_atts(array('id' => ''), $atts));
    $collector= ShortCodeCollector::get_instance();

    $div_id=$collector->add_block($id);
    $html= '';
    return "<div id='$div_id'>$html</div>";
}

add_shortcode(PREFIX.'_form', __NAMESPACE__.'\activedemand_process_form_shortcode');

function activedemand_process_form_shortcode($atts, $content = null){

    $id = "";
    //$id exists after this call.
    extract(shortcode_atts(array('id' => ''), $atts));
    $collector= ShortCodeCollector::get_instance();
    $div_id=$collector->add_form($id);
    $html= '';
    return "<div id='$div_id'></div>";
}


add_shortcode(PREFIX.'_storyboard', __NAMESPACE__.'\activedemand_process_storyboard_shortcode');

function activedemand_process_storyboard_shortcode($atts, $content = null){

    $id = "";
    //$id exists after this call.
    extract(shortcode_atts(array('id' => ''), $atts));
    $collector= ShortCodeCollector::get_instance();
    $div_id=$collector->add_storyboard($id);
    $html= '';
    return "<div id='$div_id'>$html</div>";
}

//enqueue jQuery for popup purposes
add_action('wp_enqueue_scripts', __NAMESPACE__.'\activedemand_scripts');

function activedemand_scripts(){
    wp_enqueue_script('jquery');
}


function match_replacement($matches){
    switch($matches[1]){
        case PREFIX.'_block':
            $function=__NAMESPACE__.'\activedemand_process_block_shortcode';
            break;
        case PREFIX.'_form':
            $function=__NAMESPACE__.'\activedemand_process_form_shortcode';
            break;
        case PREFIX.'_storyboard':
            $function=__NAMESPACE__.'\activedemand_process_storyboard_shortcode';
            break;
        default:
            return "";
    }
    $args="array('id'=>$matches[3])";
    return "<!-- mfunc " . W3TC_DYNAMIC_SECURITY . " ".$function."($args) -->"
                . '<!--/mfunc ' . W3TC_DYNAMIC_SECURITY . ' -->';
}

function prefilter_content($content){
    if(!defined('W3TC_DYNAMIC_SECURITY') || !function_exists('w3tc_fragmentcache_start')){
        return $content;
    }
    else{

        $shortcodes = array(PREFIX.'_form', PREFIX.'_block',PREFIX.'_storyboard');
        foreach ($shortcodes as $sc) {
            $pattern="/\[($sc).*?id=('|\")(\d+)('|\").*\]/";
            $content= preg_replace_callback($pattern, __NAMESPACE__.'\match_replacement', $content);
        }

        return $content;
    }
}

add_filter('the_content', __NAMESPACE__.'\prefilter_content',1);
//remove_filter('the_content', 'wpautop');

add_filter('widget_text', __NAMESPACE__.'\prefilter_content');



function footer_script(){
    if(!defined('W3TC_DYNAMIC_SECURITY') || !function_exists('w3tc_fragmentcache_start')){
        $process_code=process_shortcodes_script();
    } else{

        $process_code='<!--mfunc '. W3TC_DYNAMIC_SECURITY . ' '.__NAMESPACE__.'\process_shortcodes_script() -->'
            . '<!--/mfunc '.W3TC_DYNAMIC_SECURITY. ' -->';
    }
    // the script is escaped in process_shortcodes_script
    echo esc_html('').$process_code;
}

add_action('wp_footer', __NAMESPACE__.'\footer_script', 900);


function process_shortcodes_script(){
    $collector= ShortCodeCollector::get_instance();
    $server_side=$collector->server_side;

    if(!$collector->has_content()) return;

    $script_tag = "<script type='text/javascript'>\n";

    $script =  "    function cycleAndReplace(obj){\n";
    $script .= "        for(var property in obj){\n";
    $script .= "            if(!obj.hasOwnProperty(property) || property=='popup' || property=='contact_id') continue;\n";
    $script .= "            var id='#'+property;\n";
    $script .= "            jQuery(id).html(obj[property]);\n";
    $script .= "        }\n";
    $script .= "    }\n";
    $script .= "    function prefixThePopup(popup){\n";
    $script .= "        jQuery(document).ready(function(){\n";
    $script .= "            jQuery('body').prepend(popup);\n";
    $script .= "        });\n";
    $script .= "    }\n";


    if($server_side){
        $arr=json_decode($collector->get_reply(), TRUE);
        $json= json_encode($arr, JSON_HEX_TAG || JSON_HEX_QUOT);
        if(empty($arr)) $json='{}';
        $name=PREFIX.'_shortcodes';
        $script .= "var $name=$json;\n";
        $script .= "cycleAndReplace($name);\n";
        $script .= "if($name.popup) prefixThePopup($name.popup);\n";
    } else{
        $script.= add_client_rider();
    }

    $script_tag .= $script;

    $script_tag .= "</script>\n";

    return $script_tag;
}

function get_collector_content($div_id){
    $collector= ShortCodeCollector::get_instance();
    return $collector->get_content($div_id);
}

function add_client_rider(){
    $collector= ShortCodeCollector::get_instance();
    $args=$collector->make_args();
	$short_code_args = http_build_query($args);
	$version = activedemand_version();
    $script    = "
        jQuery(document).ready(function(){
	        if (typeof JD == 'undefined') JD = {};
	        JD.wp_replacements = '$short_code_args&wp_version=$version';
	         
            var load_data = function(){
                if (JD.version > '2.2.27') return;
                var data = JD.wp_replacements + '&client_side=1&version=' + JD.version;
                try {
                    AD.session();
                    data = data + '&activedemand_session_guid=' + AD.jQuery.cookie('activedemand_session_guid');
                    if (JD) data += '&' + JD.stats_internal({}, AD);
                    data += '&' + AD.extra_params();
                } catch(e) {}
				jQuery.ajax({
					url: AD.host_ssl + 'submit/content?' + data,
					dataType: 'jsonp',
					type: 'GET'
            	}).always(function () {
                    var obj=AD.dynamic_content;
                    if(!obj) return;
                    cycleAndReplace(obj);
                    if(obj.popup) prefixThePopup(obj.popup);
                    if(obj.contact_id && typeof AD != 'undefined') AD.contact_id = obj.contact_id;
                    if(typeof AD != 'undefined' && AD.setup_forms) AD.setup_forms();
                    if(typeof AD != 'undefined' && AD.setup_forms) AD.setup_ad_paging();
            	});
             }
	         
             if (typeof AD == 'undefined') AD = {};
             if (!AD.ready_fns) AD.ready_fns = [];
             if (!AD.is_ready) AD.is_ready = false;
             if (!AD.execute_functions) AD.execute_functions = function () {
                    for (var i = 0; i < AD.ready_fns.length; i++) {
                        if (AD.ready_fns[i]) {
                            AD.ready_fns[i](AD.jQuery);
                        }
                    }
                    AD.ready_fns = [];
                };
             if (!AD.ready) AD.ready = function (fn) {
                    AD.ready_fns.push(fn);
                    if (AD.is_ready) {
                        AD.execute_functions();
                    }
                };
             AD.ready(load_data);
        });";

    return $script;
}
