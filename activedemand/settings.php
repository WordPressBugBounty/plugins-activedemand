<?php


namespace ActiveDemand;

function activedemand_no_account_text()
{
    ?>
    <h2>Your <?php echo PLUGIN_VENDOR ?> Account</h2><br/>
    You will require an <a
        href="<?php echo PLUGIN_VENDOR_LINK ?>"><?php echo PLUGIN_VENDOR ?></a> account to use this plugin. With an
    <?php echo PLUGIN_VENDOR ?> account you will be able
    to:<br/>
    <ul style="list-style-type:circle;  margin-left: 50px;">
        <li>Build Webforms for your pages, posts, sidebars, etc</li>
        <li>Build Dynamic Content Blocks for your pages, posts, sidebars, etc</li>
        <ul style="list-style-type:square;  margin-left: 50px;">
            <li>Dynamically swap content based on GEO-IP data</li>
            <li>Automatically change banners based on campaign duration</li>
            <li>Stop showing forms to people who have already subscribed</li>
        </ul>
        <li>Deploy Popups and Subscriber bars</li>
        <li>Automatically send emails to those who fill out your web forms</li>
        <li>Automatically send emails to you when a form is filled out</li>
        <li>Send email campaigns to your subscribers</li>
        <li>Build your individual blog posts and have them automatically be posted on a schedule</li>
        <li>Bulk import blog posts and have them post on a defined set of times and days</li>
    </ul>

    <div>
        <h3>To sign up for your <?php echo PLUGIN_VENDOR ?> account, click <a
                    href="<?php echo PLUGIN_VENDOR_LINK ?>"><strong>here</strong></a>
        </h3>

        <p>
            You will need to enter your application key in order to enable the form shortcodes. Your can find
            your
            <?php echo PLUGIN_VENDOR ?> API key in your account settings:

        </p>

        <p>
            <img src="<?php echo esc_url(get_base_url()) ?>/images/Screenshot2.png"/>
        </p>
    </div>
    <?php
}

function activedemand_carts($options)
{
    ?>

    <div class="tab">
        <button class="tablinks active" onclick="adShowTab(event, 'automation')">Automation</button>
        <button class="tablinks" onclick="adShowTab(event, 'cart_recovery')">Cart Recovery</button>
    </div>
    <form method="post" action="options.php" class="ad-settings-form">
        <?php settings_fields(PREFIX . '_woocommerce_options'); ?>
        <div class="tabcontent" id="automation" style="display:block;"><?php FormLinker::linked_forms_page(); ?></div>
        <div class="tabcontent" id="cart_recovery"
             style="display:none;"><?php activedemand_stale_cart_form($options); ?></div>
        <input type="submit" value="Save Changes" class="button-primary ad-setting-save">
    </form>
    <?php
}

function activedemand_stale_cart_form($options)
{
    $activedemand_form_id = isset($options[PREFIX . "_woocommerce_stalecart_form_id"]) ?
            $options[PREFIX . "_woocommerce_stalecart_form_id"] : 0;
    $hours = isset($options['woocommerce_stalecart_hours']) ? $options['woocommerce_stalecart_hours'] : 2;

    ?>
    <h2>WooCommerce Carts</h2>
    <table>
        <tr>
            <th>Process Stale Carts</th>
            <td><?php
                echo FormLinker::form_link_table(array('' => PREFIX . '_stale_cart_map'));
                ?>
            </td>
        </tr>
        <tr>
            <th>
                Send Stale carts to <?php echo PLUGIN_VENDOR ?><br/> after it has sat for:
            </th>
            <td style="padding-left:8px;">
                <input type="number" min="1"
                       name="<?php echo PREFIX ?>_woocommerce_options_field[woocommerce_stalecart_hours]"
                       value="<?php echo wp_kses($hours, array()); ?>"> hours

            </td>
        </tr>
    </table>

    <?php

}

function activedemand_plugin_options()
{
    $woo_commerce_installed = \class_exists('WooCommerce');

    $options = retrieve_activedemand_options();
    $storyboard_xml = "";
    $block_xml = $form_xml = "";

    if (!array_key_exists(PREFIX . '_appkey', $options)) {
        $options[PREFIX . '_appkey'] = "";
    }

    $activedemand_appkey = $options[PREFIX . '_appkey'];

    if ("" != $activedemand_appkey) {
        //get Forms
        $form_xml = FormLinker::load_form_xml();
        $url = activedemand_api_url("smart_blocks.xml");
        $str = activedemand_getHTML($url, 10);
        $block_xml = simplexml_load_string($str);

        $url_sb = activedemand_api_url("dynamic_story_boards.xml");
        $str_sb = activedemand_getHTML($url_sb, 10);
        $storyboard_xml = simplexml_load_string($str_sb);
    }

    if (!array_key_exists(PREFIX . '_ignore_form_style', $options)) {
        $options[PREFIX . '_ignore_form_style'] = 0;
    }
    if (!array_key_exists(PREFIX . '_ignore_block_style', $options)) {
        $options[PREFIX . '_ignore_block_style'] = 0;
    }
    if (!array_key_exists(PREFIX . '_multi_account_site', $options)) {
        $options[PREFIX . '_multi_account_site'] = 0;
    }

    ?>

    <div class="wrap">
    <h2></h2>
    <img src="<?php echo esc_url(get_base_url()) ?>/images/ActiveDEMAND-Transparent.png"/>


    <?php if ("" == $activedemand_appkey || !isset($activedemand_appkey)) {
    activedemand_no_account_text();
} else { ?>
    <p> The <a href="<?php echo PLUGIN_VENDOR_LINK ?>"><?php echo PLUGIN_VENDOR ?></a> plugin adds a
        tracking script to your
        WordPress
        pages. This plugin offers the ability to use web form and content block shortcodes on your pages,
        posts, and
        sidebars
        that
        will render an <?php echo PLUGIN_VENDOR ?> Web Form/Dynamic Content block. This allows you to maintain your
        dynamic
        content, form styling, and
        configuration
        within
        <?php echo PLUGIN_VENDOR ?>.
    </p>
<?php } ?>
    <?php settings_errors(PREFIX . '_options'); ?>
    <?php $form_view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : null; ?>
    <h2 class="nav-tab-wrapper">
        <a href="?page=<?php echo PREFIX ?>_options"
           class="nav-tab <?php echo $form_view === null ? 'nav-tab-active' : '' ?>">Options</a>
        <?php if ("" != $activedemand_appkey): ?>
            <a href="?page=<?php echo PREFIX ?>_options&view=content"
               class="nav-tab <?php echo $form_view === 'content' ? 'nav-tab-active' : '' ?>">Content</a>
        <?php endif; ?>
        <?php if ($woo_commerce_installed && "" != $activedemand_appkey): ?>
            <a href="?page=<?php echo PREFIX ?>_options&view=woo"
               class="nav-tab <?php echo $form_view === 'woo' ? 'nav-tab-active' : '' ?>">WooCommerce</a>
        <?php endif; ?>
        <?php if ("" != $activedemand_appkey): ?>
            <a href="?page=<?php echo PREFIX ?>_options&view=access"
               class="nav-tab <?php echo $form_view === 'access' ? 'nav-tab-active' : '' ?>">Access Control</a>
        <?php endif; ?>
    </h2>

    <?php
    switch ($form_view):
        case 'woo':
            activedemand_carts($options);
            break;
        case 'access':
            $url_fields = "https://api.activedemand.com/v1/contacts/fields.json";
            $contacts_fields = json_decode(activedemand_getHTML($url_fields, 10));
            $enable_access_control = get_option(PREFIX . '_enable_access_control', false);
            $has_password_fields = false;
            if (isset($contacts_fields)) {
                foreach ($contacts_fields as $key => $object):
                    if ($object->type == 'password') {
                        $has_password_fields = true;
                    };
                endforeach;
            }

            ?>
            <h2>Using <?php echo PLUGIN_VENDOR ?> Access Control</h2>


            <p>
                You can use <?php echo PLUGIN_VENDOR ?> password custom field objects to control access to areas of your
                website.
                For a given password custom field, you can create rules for for each object to control which pages will
                require a valid login
                to access. Thus you are able to create multiple unique membership areas within your WordPress site.
            </p>
            <div class="notice inline notice-error  no_pwd_custom_fields"
                 style="display:<?php echo ($has_password_fields) ? 'none;' : 'block;' ?>">
                <h2>No Password Custom Fields Configured</h2>
                <p>To use the <?php echo PLUGIN_VENDOR ?> access control system on your website, you will first have
                    to add at least one contact password custom field to your <?php echo PLUGIN_VENDOR ?> account.
                    Once you do have a contact password custom field created, you will be able to configure the
                    access rules here</p>
            </div>
            <div class="has_custom_fields_defined"
                 style="display:<?php echo ($has_password_fields) ? 'block;' : 'none;' ?>">
                <div class="checkbox_enable_disable_access_control">

                    <input type="checkbox" style="margin-top: 0px;"
                           name=<?php echo PREFIX . "_enable_access_control"; ?> value="1" <?php checked($enable_access_control, 1) ?>/>
                    <label for="enable_access_control">Enable access control</label></br>
                </div>
                <div class="access_control" style="margin-top: 20px;">


                    <div class="notice notice-error inline is-dismissible activedemand_notice" style="display:none;">
                        <p>This is an invalid rule.</p>
                    </div>

                    <label>Visitors require a valid </label>
                    <select name="access_control_content">
                        <?php
                        foreach ($contacts_fields as $key => $object):
                            if ($object->type == 'password'): ?>
                                <option value="<?php echo wp_kses($object->key, array()); ?>"><?php echo wp_kses($object->label, array()); ?></option>
                            <?php endif;
                        endforeach;
                        ?>
                    </select>
                    <label>password to access any page that</label>
                    <select name="access_control_password">
                        <option value="1">matches</option>
                        <option value="0">does not match</option>
                    </select>
                    <label>any of the following rules.<br/><br/>The rest of the website will not be restricted by this
                        password
                        field.</label>

                    <div id="cover-spin"></div>
                    <!-- <form method="post" class="ad-settings-form"> -->
                    <div class="container" style="margin-top: 20px;">
                        <div class="table-responsive">
                            <table class="table table-bordered custom_content_feild" style="border: 1px solid black;">
                                <thead>
                                <tr>
                                    <th style="padding: 10px" class="text-left">URLs for the <span
                                                class="valid_point_feild"> </span> custom
                                        field.
                                    </th>
                                    <th class="text-center"></th>
                                </tr>
                                </thead>
                                <tbody id="tbody_custom_url_content">

                                </tbody>
                            </table>
                        </div>
                    </div>
                    <a href="#addBtn" rel="modal:open"><span class="addBtn" onclick="addBtn_row()">&#43;</span></a>
                    <div class="modal fade in" id="addBtn" tabindex="-1" role="dialog" style="display: none;">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h4 class="modal-title">Create Rule</h4>
                            </div>
                            <div class="modal-body">
                                <div class="form-group">
                                    <label class="modal-label" for="custom_url_content_data">Rule URL</label>
                                    <input type="text" class="custom_url_content_data modal-form-control"
                                           placeholder="/some-path (regular expressions are supported)">
                                </div>

                            </div>
                            <div class="modal-footer">
                                <button type="submit" id="add-role-btn" onclick="save_url()"
                                        class="button-primary save_changes_custom_content">Add Rule
                                </button>
                                <a href="#close-modal" rel="modal:close" type="button" class="button btn-default"
                                   data-dismiss="modal">Cancel</a>
                            </div>
                        </div>
                    </div>

                    <input type="submit" value="Save Changes" class="button-primary ad-custom-content-url-save">
                    <!-- </form> -->
                </div>
            </div>
            <!-- <a href="#addBtn" rel="modal:open"><span class="addBtn" onclick="addBtn_row()">&#43;</span></a> -->


            <?php break;
        case 'content':
            ?>
            <div>


            <h2>Using <?php echo PLUGIN_VENDOR ?> Web Forms and Dynamic Content Blocks</h2>


            <p>
                You can insert content into your Pages/Posts by copy/pasting the appropriate short code below, or you
                can use the custom Gutenberg (look for <?php echo PLUGIN_VENDOR ?> Blocks), or via your visual editor,
                look for the 'Insert <?php echo PLUGIN_VENDOR ?> Shortcode' button:<br/>
                <img src="<?php echo esc_url(get_base_url()) ?>/images/Screenshot3.png"/>
            </p>
            <p>
                The tables below are cached. If you added or removed blocks or forms in <?php echo PLUGIN_VENDOR ?>, you
                may want to clear the cache.
            </p>
            <div class="cache">
                <form method="post">
                    <?php wp_nonce_field('reset-cache-nonce'); ?>
                    <input name="clear-activedemand-cache" type="submit" value="Clear cache"
                           class="button-primary ad-setting-save">
                </form>
                <?php
                if (isset($_POST['clear-activedemand-cache']) && isset($_POST['reset-cache-nonce']) && wp_verify_nonce(sanitize_text_field($_POST['reset-cache-nonce']))) {
                    delete_option('activedemand_blocks');
                    delete_option('activedemand_forms');
                    delete_option('activedemand_storyboard');
                    ?>
                    <div class="notice notice-success is-dismissible">
                        <p><strong>Cache cleared successfully.</strong></p>
                        <button type="button" class="notice-dismiss">
                            <span class="screen-reader-text">Dismiss this notice.</span>
                        </button>
                    </div>
                <?php } ?>
            </div>
            <table>
            <tr>
            <td style="padding:15px;vertical-align: top;">
                <?php if ("" != $form_xml) { ?>
                    <h3>Available Web Form Shortcodes</h3>


                    <table id="shrtcodetbl" style="width:100%">
                        <tr>
                            <th>Form Name</th>
                            <th>Shortcode</th>
                        </tr>
                        <?php
                        foreach ($form_xml->children() as $child) {
                            echo "<tr><td>";
                            echo wp_kses($child->name, array());
                            echo "</td>";
                            echo "<td>[" . PREFIX . "_form id='";
                            echo wp_kses($child->id, array());
                            echo "']</td>";
                        }
                        ?>
                    </table>


                <?php } else { ?>
                    <h2>No Web Forms Configured</h2>
                    <p>To use the <?php echo PLUGIN_VENDOR ?> web form shortcodes, you will first have to add some Web
                        Forms
                        to
                        your
                        account in <?php echo PLUGIN_VENDOR ?>. Once you do have Web Forms configured, the available
                        shortcodes
                        will
                        be
                        displayed here.</p>

                <?php } ?>
            </td>

            <td style="padding:15px;vertical-align: top;">
                <?php if ("" != $storyboard_xml) { ?>
                    <h3>Available Story board Shortcodes</h3>


                    <table id="shrtcodetbl" style="width:100%">
                        <tr>
                            <th>Storyboard Name</th>
                            <th>Shortcode</th>
                        </tr>
                        <?php
                        foreach ($storyboard_xml->children() as $child) {
                            echo "<tr><td>";
                            echo wp_kses($child->name, array());
                            echo "</td>";
                            echo "<td>[" . PREFIX . "_storyboard id='";
                            echo wp_kses($child->id, array());
                            echo "']</td>";
                        }
                        ?>
                    </table>


                <?php } else { ?>
                    <h2>No story board Configured</h2>
                    <p>To use the <?php echo PLUGIN_VENDOR ?> story board shortcodes, you will first have to add some
                        Web
                        Forms
                        to
                        your
                        account in <?php echo PLUGIN_VENDOR ?>. Once you do have Web Forms configured, the available
                        shortcodes
                        will
                        be
                        displayed here.</p>

                <?php } ?>
            </td>
            <td style="padding:15px;vertical-align: top;">
            <?php if ("" != $block_xml) { ?>
            <h3>Available Dynamic Content Block Shortcodes</h3>

            <table id="shrtcodetbl" style="width:100%">
                <tr>
                    <th>Block Name</th>
                    <th>Shortcode</th>
                </tr>
                <?php
                foreach ($block_xml->children() as $child) {
                    echo "<tr><td>";
                    echo wp_kses($child->name, array());
                    echo "</td>";
                    echo "<td>[" . PREFIX . "_block id='";
                    echo wp_kses($child->id, array());
                    echo "']</td>";
                }
                ?>
            </table>


        <?php } else { ?>
            <h2>No Dynamic Blocks Configured</h2>
            <p>To use the <?php echo PLUGIN_VENDOR ?> Dynamic Content Block shortcodes, you will first have to add
                some Dynamic Content Blocks
                to
                your
                account in <?php echo PLUGIN_VENDOR ?>. Once you do have Dynamic Blocks configured, the available
                shortcodes
                will
                be
                displayed here.</p>

        <?php } ?>

            <?php break;
        default:
            ?>
            <form method="post" action="options.php" class="ad-settings-form">
                <?php settings_fields(PREFIX . '_options'); ?>


                <h3><?php echo PLUGIN_VENDOR ?> Plugin Options</h3>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php echo PLUGIN_VENDOR ?> API Key</th>
                        <td><input style="width:400px" type='text'
                                   name=<?php echo "\"" . PREFIX . "_options_field[" . PREFIX . "_appkey]\""; ?>
                                   value="<?php echo wp_kses($activedemand_appkey, array()); ?>"/></td>
                    </tr>
                    <?php if ("" != $activedemand_appkey) {
                        //get Forms
                        $show_popup = get_option(PREFIX . '_server_showpopups', FALSE);
                        $show_tinymce = get_option(PREFIX . '_show_tinymce', TRUE);
                        $show_gutenberg_blocks = get_option(PREFIX . '_show_gutenberg_blocks', TRUE);
                        $multi_account_website = get_option(PREFIX . '_multi_account_website', FALSE);
                        ?>
                        <tr valign="top">
                            <th scope="row">Enable Popup Pre-Loading?</th>
                            <td><input type="checkbox" name=<?php echo PREFIX . "_server_showpopups"; ?> value="1"
                                        <?php checked($show_popup, 1); ?> /></td>
                        </tr>
                        <?php $server_side = get_option(PREFIX . '_server_side', TRUE); ?>
                        <tr valign="top">
                            <th scope="row">Enable Content Pre-Loading? (uncheck this if you use caching)</th>
                            <td><input type="checkbox" name=<?php echo PREFIX . "_server_side"; ?> value="1"
                                        <?php checked($server_side, 1); ?> /></td>
                        </tr>


                        <?php if (function_exists('register_block_type')) { ?>
                            <tr>
                                <th scope="row">Show <?php echo PLUGIN_VENDOR ?> Gutenberg Blocks ?</th>
                                <td>
                                    <input type="checkbox"
                                           name=<?php echo PREFIX . "_show_gutenberg_blocks"; ?> value="1"
                                            <?php checked($show_gutenberg_blocks, 1) ?>/>
                                </td>
                            </tr>
                        <?php } ?>
                        <tr>
                            <th scope="row">Show <?php echo PLUGIN_VENDOR ?> Button on Post/Page editors?</th>
                            <td>
                                <input type="checkbox" name=<?php echo PREFIX . "_show_tinymce"; ?> value="1"
                                        <?php checked($show_tinymce, 1) ?>/>
                            </td>
                        </tr>


                    <?php } ?>

                    <?php if ("" != $form_xml) { ?>
                        <tr valign="top">
                            <th scope="row">Use Theme CSS for <?php echo PLUGIN_VENDOR ?> Forms</th>
                            <td>
                                <input type="checkbox"
                                       name=<?php echo PREFIX . "_options_field[" . PREFIX . "_ignore_form_style]"; ?>
                                       value="1" <?php checked($options[PREFIX . '_ignore_form_style'], 1); ?> />
                            </td>
                        </tr>

                    <?php } ?>

                    <?php

                    if ("" != $block_xml) { ?>
                        <tr valign="top">
                            <th scope="row">Use Theme CSS for <?php echo PLUGIN_VENDOR ?> Dynamic Blocks</th>
                            <td>
                                <input type="checkbox"
                                       name=<?php echo PREFIX . "_options_field[" . PREFIX . "_ignore_block_style]"; ?>
                                       value="1" <?php checked($options[PREFIX . '_ignore_block_style'], 1); ?> />
                            </td>
                        </tr>
                    <?php } ?>

                    <?php if ("" != $activedemand_appkey) { ?>
                    <tr valign="top">
                        <th scope="row">This is a multi account website</th>
                        <td>
                            <input type="checkbox"
                                   name=<?php echo PREFIX . "_options_field[" . PREFIX . "_multi_account_site]"; ?>
                                   value="1" <?php checked($options[PREFIX . '_multi_account_site'], 1); ?> />
                        </td>
                    </tr>
                    <?php } ?>
                </table>
                <input type="submit" value="Save Changes" class="button-primary ad-setting-save">
            </form>
        <?php endswitch; ?>
    <?php activedemand_settings_styles(); ?>
    <?php activedemand_settings_javascript(); ?>
<?php }


function activedemand_settings_styles()
{
    ?>
    <style type="text/css">
        * {
            box-sizing: border-box
        }

        /* Style the tab */
        .tab {
            float: left;
            border: 1px solid #ccc;
            background-color: #f1f1f1;
            width: 30%;
        }

        .tab, .tabcontent {
            height: 400px;
        }

        /* Style the buttons that are used to open the tab content */
        .tab button {
            display: block;
            background-color: inherit;
            color: black;
            padding: 22px 16px;
            width: 100%;
            border: none;
            outline: none;
            text-align: left;
            cursor: pointer;
            transition: 0.3s;
        }

        /* Change background color of buttons on hover */
        .tab button:hover {
            background-color: #ddd;
        }

        /* Create an active/current "tab button" class */
        .tab button.active {
            background-color: #ccc;
        }

        /* Style the tab content */
        .tabcontent {
            float: left;
            padding: 0px 12px;
            border: 1px solid #ccc;
            width: 70%;
            border-left: none;
        }

        table.wootbl th {

            padding: 5px;
        }

        table.wootbl td {

            padding: 5px;
        }
    </style>

    <style scoped="scoped" type="text/css">
        table#shrtcodetbl {
            border: 1px solid black;
        }

        table#shrtcodetbl tr {
            background-color: #ffffff;
        }

        table#shrtcodetbl tr:nth-child(even) {
            background-color: #eeeeee;
        }

        table.custom_content_feild tr:nth-child(even) {
            background-color: #eeeeee;
        }

        table.custom_content_feild tr {
            background-color: #ffffff;
        }

        table.custom_content_feild {
            width: 65%;
        }


        table.custom_content_feild th {
            color: white;
            background-color: black;
            padding: 10px;
        }

        table#shrtcodetbl tr td {
            padding: 10px;

        }

        .save_custom_content {
            background: #2271b1;
            border-color: #2271b1;
            color: #fff;
            text-decoration: none;
            text-shadow: none !important;
            padding: 10px;
            /* margin-left: 9%; */
            border-radius: 4px;
            margin-top: 27px;
        }

        table#shrtcodetbl th {
            color: white;
            background-color: black;
            padding: 10px;
        }

        .button-primary.ad-setting-save {
            margin-top: 15px;
        }

        ::placeholder {
            color: LightGrey;
            opacity: 1; /* Firefox */
        }

        :-ms-input-placeholder { /* Internet Explorer 10-11 */
            color: LightGrey;
        }

        ::-ms-input-placeholder { /* Microsoft Edge */
            color: LightGrey;
        }

        .url_row {
            padding: 10px;
        }

        .remove_btn {
            cursor: pointer;
            padding-top: 5px;
            padding-bottom: 6px;
            background: #3c372feb;
            border-radius: 50%;
            padding-right: 10px;
            padding-left: 10px;
            color: white;
            font-size: 12px;
        }

        .remove_btn:hover {
            transition: all 0.5s cubic-bezier(.25, .8, .25, 1);
            box-shadow: 2px 3px 2px rgb(0 0 0 / 25%), 0 4px 10px rgb(0 0 0 / 22%);
            border-radius: 50%;
        }


        span.addBtn {
            cursor: pointer;
            padding-top: 5px;
            padding-bottom: 10px;
            background: #ec6e3aed;
            border-radius: 50%;
            padding-right: 8px;
            padding-left: 8px;
            color: white;
            font-size: 25px;
            margin-top: 15px;
            margin-bottom: 15px;
            left: 25%;
            position: absolute;
        }

        .addBtn:hover {
            transition: all 0.5s cubic-bezier(.25, .8, .25, 1);
            box-shadow: 2px 3px 2px rgb(0 0 0 / 25%), 0 4px 10px rgb(0 0 0 / 22%);
            border-radius: 50%;
            background-color: #19a889;

        }

        .modal .close-modal {
            position: absolute;
            top: -12.5px;
            right: -12.5px;
            display: block;
            width: 30px;
            height: 30px;
            text-indent: -9999px;
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center center;
            background-image: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADwAAAA8CAYAAAA6/NlyAAAAAXNSR0IArs4c6QAAA3hJREFUaAXlm8+K00Acx7MiCIJH/yw+gA9g25O49SL4AO3Bp1jw5NvktC+wF88qevK4BU97EmzxUBCEolK/n5gp3W6TTJPfpNPNF37MNsl85/vN/DaTmU6PknC4K+pniqeKJ3k8UnkvDxXJzzy+q/yaxxeVHxW/FNHjgRSeKt4rFoplzaAuHHDBGR2eS9G54reirsmienDCTRt7xwsp+KAoEmt9nLaGitZxrBbPFNaGfPloGw2t4JVamSt8xYW6Dg1oCYo3Yv+rCGViV160oMkcd8SYKnYV1Nb1aEOjCe6L5ZOiLfF120EjWhuBu3YIZt1NQmujnk5F4MgOpURzLfAwOBSTmzp3fpDxuI/pabxpqOoz2r2HLAb0GMbZKlNV5/Hg9XJypguryA7lPF5KMdTZQzHjqxNPhWhzIuAruOl1eNqKEx1tSh5rfbxdw7mOxCq4qS68ZTjKS1YVvilu559vWvFHhh4rZrdyZ69Vmpgdj8fJbDZLJpNJ0uv1cnr/gjrUhQMuI+ANjyuwftQ0bbL6Erp0mM/ny8Fg4M3LtdRxgMtKl3jwmIHVxYXChFy94/Rmpa/pTbNUhstKV+4Rr8lLQ9KlUvJKLyG8yvQ2s9SBy1Jb7jV5a0yapfF6apaZLjLLcWtd4sNrmJUMHyM+1xibTjH82Zh01TNlhsrOhdKTe00uAzZQmN6+KW+sDa/JD2PSVQ873m29yf+1Q9VDzfEYlHi1G5LKBBWZbtEsHbFwb1oYDwr1ZiF/2bnCSg1OBE/pfr9/bWx26UxJL3ONPISOLKUvQza0LZUxSKyjpdTGa/vDEr25rddbMM0Q3O6Lx3rqFvU+x6UrRKQY7tyrZecmD9FODy8uLizTmilwNj0kraNcAJhOp5aGVwsAGD5VmJBrWWbJSgWT9zrzWepQF47RaGSiKfeGx6Szi3gzmX/HHbihwBser4B9UJYpFBNX4R6vTn3VQnez0SymnrHQMsRYGTr1dSk34ljRqS/EMd2pLQ8YBp3a1PLfcqCpo8gtHkZFHKkTX6fs3MY0blKnth66rKCnU0VRGu37ONrQaA4eZDFtWAu2fXj9zjFkxTBOo8F7t926gTp/83Kyzzcy2kZD6xiqxTYnHLRFm3vHiRSwNSjkz3hoIzo8lCKWUlg/YtGs7tObunDAZfpDLbfEI15zsEIY3U/x/gHHc/G1zltnAgAAAABJRU5ErkJggg==);
        }

        }

        .modal {
            display: none;
            vertical-align: middle;
            position: relative;
            z-index: 2;
            max-width: 500px;
            box-sizing: border-box;
            width: 90%;
            background: #fff;
            padding: 15px 15px;
            -webkit-border-radius: 8px;
            -moz-border-radius: 8px;
            -o-border-radius: 8px;
            -ms-border-radius: 8px;
            border-radius: 8px;
            -webkit-box-shadow: 0 0 10px #000;
            -moz-box-shadow: 0 0 10px #000;
            -o-box-shadow: 0 0 10px #000;
            -ms-box-shadow: 0 0 10px #000;
            box-shadow: 0 0 10px #000;
            text-align: left;
        }

        .modal-body {
            position: relative;
            text-align: left;
            padding-top: 15px;
            padding-right: 15px;
            padding-bottom: 15px;
            padding-left: 15px;
        }

        .modal-form-control {
            box-shadow: none;
            height: 34px;
            border-radius: 0;
            border: 1px solid #ccc !important;
            padding: 6px 12px !important;
            width: 100%;
        }

        .modal-label {
            display: block;
            padding-bottom: 5px;
        }

        .modal-footer {
            padding-top: 15px;
            padding-right: 0px;
            padding-bottom: 10px;
            padding-left: 15px;
            text-align: right;
            border-top: 1px solid #e5e5e5;
        }

        .modal-header {
            text-align: left;
            min-height: 16.43px;
            padding-top: 10px;
            padding-right: 15px;
            padding-bottom: 10px;
            padding-left: 0px;
            border-bottom: 1px solid #e5e5e5;
        }

        .modal-title {
            margin: 0;
            line-height: 1.42857143;
        }

        #cover-spin {
            position: fixed;
            width: 100%;
            left: 0;
            right: 0;
            top: 0;
            bottom: 0;
            background-color: rgba(255, 255, 255, 0.7);
            z-index: 9999;
            display: none;
        }

        @-webkit-keyframes spin {
            from {
                -webkit-transform: rotate(0deg);
            }
            to {
                -webkit-transform: rotate(360deg);
            }
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }
            to {
                transform: rotate(360deg);
            }
        }

        #cover-spin::after {
            content: '';
            display: block;
            position: absolute;
            left: 48%;
            top: 40%;
            width: 40px;
            height: 40px;
            border-style: solid;
            border-color: black;
            border-top-color: transparent;
            border-width: 4px;
            border-radius: 50%;
            -webkit-animation: spin .8s linear infinite;
            animation: spin .8s linear infinite;
        }

        input.button-primary.ad-custom-content-url-save {
            margin-top: 45px;
        }

        .checkbox_enable_disable_access_control {
            margin-top: 20px;
        }

    </style>
    <?php
}

function activedemand_settings_javascript()
{ ?>
    <script type="text/javascript">
        jQuery(document).ready(function () {
            jQuery(".ad-custom-content-url-save").click(function () {
                var custom_url_content = [];
                var access_object_key = jQuery('select[name="access_control_content"] option:checked').val();
                var access_match = jQuery('select[name="access_control_password"] option:checked').val();
                jQuery(".custom_url_content").each(function (index) {
                    var custom_url = jQuery(this).text().replace(/\s+/g, '');
                    var id_rule = jQuery(this).attr('data-id_rule');
                    custom_url_content.push({
                        custom_url: custom_url,
                        id_rule: id_rule,
                    });
                });

                jQuery.post(ajaxurl, {
                    access_match: access_match,
                    access_object_key: access_object_key,
                    custom_url_content: custom_url_content,
                    action: "activedemand_access_rules_save",
                    method: "activedemand_save_rules"
                }, function (response) {
                    tabel_content();
                });
            });

            if (jQuery('input[name="activedemand_enable_access_control"]').is(":checked")) {
                jQuery('.access_control').show();
                tabel_content();
            } else {
                jQuery('.access_control').hide();
            }

            jQuery('input[name="activedemand_enable_access_control"]').click(function () {
                if (jQuery('input[name="activedemand_enable_access_control"]').is(":checked")) {
                    var activedemand_enable_access_control = 1;
                    jQuery.post(ajaxurl, {
                        activedemand_enable_access_control: activedemand_enable_access_control,
                        action: "activedemand_access_rules_save",
                        method: "activedemand_enable_access_control"
                    }, function (response) {
                        jQuery('.access_control').show();
                        tabel_content();
                    });
                } else {
                    var activedemand_enable_access_control = 0;
                    jQuery.post(ajaxurl, {
                        activedemand_enable_access_control: activedemand_enable_access_control,
                        action: "activedemand_access_rules_save",
                        method: "activedemand_enable_access_control"
                    }, function (response) {
                        jQuery('.access_control').hide();
                    });
                }
            });


            var rowIdx = 0;
            jQuery('#tbody_custom_url_content').on('click', '.remove_btn', function () {

                jQuery(this).closest('tr').remove();

                var id_rule = jQuery(this).closest('tr').find('.custom_url_content').attr('data-id_rule');

                console.log(id_rule);

                jQuery.post(ajaxurl, {
                    id_rule: id_rule,
                    action: "activedemand_delete_custom_url_content"
                }, function (response) {

                });

                jQuery(this).closest('tr').remove();

                rowIdx--;
            });


        });

        jQuery('select[name="access_control_content"]').change(function () {
            tabel_content();
        });

        function save_url() {
            var custom_url_content_data = jQuery('.custom_url_content_data').val();

            if (/\bwp-admin.*\b/i.test(custom_url_content_data) || custom_url_content_data == '/wp-admin' || custom_url_content_data == '/wp-login' || /\bwp-login.*\b/i.test(custom_url_content_data)) {
                jQuery('.activedemand_notice').show();
            } else {
                jQuery('.activedemand_notice').hide();
                jQuery('.has_custom_fields_defined').show();
                var access_control_password = jQuery('select[name="access_control_password"] option:checked').val();
                var rowIdx = 0;
                jQuery('#tbody_custom_url_content').append('<tr class="row_table">\
                <td class="row-index url_row custom_url_content"><span>' + custom_url_content_data + '</span></td>\
                 <td class="text-center" style="text-align: center; padding: 10px;">\
                  <span class="remove_btn">x</span>\
                  </td>\
                 </tr>');
            }
            jQuery("a.close-modal").click();

        }

        function tabel_content() {
            jQuery('#cover-spin').show();
            var valid_content = jQuery('select[name="access_control_content"] option:selected').val();
            var valid_point_name = jQuery('select[name="access_control_content"] option:selected').text();
            jQuery.post(ajaxurl, {
                valid_content: valid_content,
                action: "activedemand_access_rules_save",
                method: "get_url_object_key"
            }, function (response) {
                var resp = jQuery.parseJSON(response);
                var html = '';
                setTimeout(function () {
                    jQuery('#cover-spin').hide();
                }, 3000);
                if (resp != false) {
                    jQuery('.has_custom_fields_defined').show();
                    jQuery("select[name='access_control_password']").find('option[selected="selected"]').each(function (index) {
                        jQuery(this).removeAttr('selected');
                        jQuery(this).prop('selected', false);
                    });
                    jQuery.each(resp, function (index, value) {
                        jQuery("select[name='access_control_password'] option[value='" + value.match + "']").attr("selected", "selected");

                        jQuery("select[name='access_control_password']").find('option[selected="selected"]').each(function () {
                            jQuery(this).prop('selected', true);
                        });
                        html += '<tr class="row_table">\
                            <td class="row-index url_row custom_url_content" data-id_rule ="' + value.id_rule + '">\
                              <span>' + value.url + '</span></td>\
                            <td class="text-center" style="text-align: center; padding: 10px;">\
                            <span class="remove_btn">x</span>\
                            </td>\
                            </tr>';
                    });
                }
                jQuery('span.valid_point_feild').html(valid_point_name);
                jQuery('#tbody_custom_url_content').html(html);
            });
        }

        function addBtn_row() {
            jQuery('.custom_url_content_data').val('');
        }
    </script> <?php
}
