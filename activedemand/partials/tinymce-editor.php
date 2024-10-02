<?php
namespace ActiveDemand;

// define(__NAMESPACE__ . "\API_URL", 'https://api.activedemand.com/v1/');

function activedemand_partial_getHTML($url, $timeout)
{
    $result = '';    
    $response = wp_remote_get($url,
        array(
            'timeout'   => $timeout,
            'sslverify' => true,
        )
    );

    if ( is_array($response) && isset($response['body']) && isset($response['response']['code']) && (int)$response['response']['code'] == 200 ) {
        $result = $response['body'];
    }

    return $result;
}

$options = get_option('activedemand_options_field');
$activedemand_appkey = $options["activedemand_appkey"];

if ("" != $activedemand_appkey) {
    //get Forms
    $url = API_URL . "forms.xml?api-key=" . $activedemand_appkey . "";
    $str = activedemand_partial_getHTML($url, 10);
    $form_xml = simplexml_load_string($str);

    //get Blocks
    $url = API_URL . "smart_blocks.xml?api-key=" . $activedemand_appkey . "";
    $str = activedemand_partial_getHTML($url, 10);
    $block_xml = simplexml_load_string($str);

    //get Storyboard
    $url = API_URL . "dynamic_story_boards.xml?api-key=" . $activedemand_appkey . "";
    $str = activedemand_partial_getHTML($url, 10);
    $storyboard_xml = simplexml_load_string($str);
}
?>
<div id="activedemand_editor" class="shortcode_editor" title="Insert ActiveDEMAND Shortcode"
     style="display:none;height:500px">
    <?php if (""!=$form_xml) { ?>
        <h3>Available ActiveDEMAND Web Forms:</h3>
        <style scoped="scoped" type="text/css">
            div.ad-form-list {
            }

            div.ad-form-list ul li span {
                margin-left: 20px;
                font-size: 1.2em;
                font-weight: bold;
            }
        </style>
        <div class="ad-form-list">
            <ul>
                <?php
                foreach ($form_xml->children() as $child) {
                    echo "<li>";
                    echo "<input type='radio' name='form_id' value='";
                    echo '[activedemand_form id="';
                    echo wp_kses($child->id, array());
                    echo '"]';
                    echo "'/>";
                    echo "<span>";
                    echo wp_kses($child->name, array());
                    echo "</span>";
                    echo "</li>";
                }
                ?>
            </ul>
        </div>
    <?php } else { ?>
        <h2>No Web Forms Configured</h2>
        <p>To use the ActiveDEMAND web form shortcodes, you will first have to add some web forms to your account in
            ActiveDEMAND. Once you do have web forms configured, the available shortcodes will be displayed here.</p>
    <?php } ?>
    <br/>
    <?php if ("" != $block_xml) { ?>
        <h3>Available ActiveDEMAND Content Blocks:</h3>
        <style scoped="scoped" type="text/css">
            div.ad-form-list {
            }

            div.ad-form-list ul li span {
                margin-left: 20px;
                font-size: 1.2em;
                font-weight: bold;
            }
        </style>
        <div class="ad-form-list">
            <ul>
                <?php
                foreach ($block_xml->children() as $child) {
                    echo "<li>";
                    echo "<input type='radio' name='form_id' value='";
                    echo '[activedemand_block id="';
                    echo wp_kses($child->id, array());
                    echo '"]';
                    echo "'/>";
                    echo "<span>";
                    echo wp_kses($child->name, array());
                    echo "</span>";
                    echo "</li>";
                }
                ?>
            </ul>
        </div>
    <?php } else { ?>
        <h2>No Content Blocks Configured</h2>
        <p>To use the ActiveDEMAND Dynamic Content Block shortcodes, you will first have to add content blocks to your account
            in
            ActiveDEMAND. Once you do have content blocks configured, the available shortcodes will be displayed
            here.</p>
    <?php } ?>
    <br />
    <?php if (""!=$storyboard_xml) { ?>
        <h3>Available ActiveDEMAND Story Board:</h3>
        <style scoped="scoped" type="text/css">
            div.ad-form-list {
            }

            div.ad-form-list ul li span {
                margin-left: 20px;
                font-size: 1.2em;
                font-weight: bold;
            }
        </style>
        <div class="ad-form-list">
            <ul>
                <?php
                foreach ($storyboard_xml->children() as $child) {
                    echo "<li>";
                    echo "<input type='radio' name='form_id' value='";
                    echo '[activedemand_storyboard id="';
                    echo wp_kses($child->id, array());
                    echo '"]';
                    echo "'/>";
                    echo "<span>";
                    echo wp_kses($child->name, array());
                    echo "</li>";
                }
                ?>
            </ul>
        </div>
    <?php } else { ?>
        <h2>No Story Board Configured</h2>
        <p>To use the ActiveDEMAND story board shortcodes, you will first have to add some web forms to your account in
            ActiveDEMAND. Once you do have story board configured, the available shortcodes will be displayed here.</p>
    <?php } ?>

</div>