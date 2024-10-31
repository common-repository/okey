<?php

function wc_okey_login()
{
    ?>
    <div class="wrap">
        <div id="eokey_loader" class="loader overlay"><img style="width: 75px;" src="<?php echo plugin_dir_url(dirname(__FILE__)) . 'style/loader.gif'?>"/> </div>
        <h2><?php echo __('OKEY - Autentificare', 'wc_okey')?></h2>
        <?php settings_errors(); ?>
        <form action="options.php" method="post" onsubmit="showLoading();">
            <?php settings_fields('wc_okey_plugin_options'); ?>
            <?php do_settings_sections('wc_okey_plugin'); ?>
            <input class="button-primary" name="Submit" type="submit" value="<?php echo __('Autentificare', 'wc_okey')?>" />
        </form>
    </div>
    <?php
    // style files
    wp_enqueue_style('wc_okey_main_style');
    wp_enqueue_script('wc_okey_send_order');
}

function wc_okey_plugin_login_section_text()
{
    echo '<div class="container">
            <img src="'.plugin_dir_url(__FILE__).'../assets/images/okeyLogo.png" class="logo" />
            <div class="message">
                <p>Platforma online de <br> administrare a afacerii tale!</p>
            </div>
          </div>';
}

function wc_okey_settings_display_api_token()
{
    $options = get_option('wc_okey_plugin_options');

    echo '<input id="wc_okey_settings_token" name="wc_okey_plugin_options[token]" type="password" value="'.$options['token'].'" style="width: 400px;" />';
    echo '<div id="anim">
            <div class="tooltip" data-tooltip="Pentru a te conecta cu OKEY descarca API-TOKEN direct din aplicatie, sectiunea Setari/Integrari/Woocommerce.">
                <img src="'.plugin_dir_url(__FILE__).'../assets/images/infoIcon.png" />
            </div>
          </div>';
}

/**
 * This function validate OKEY login form
 *
 * @param  $input
 * @return array
 */
function wc_okey_plugin_options_validate($input)
{
    $options = get_option('wc_okey_plugin_options');

    if($options['token'] !== $input['token']) { $token = $input['token'];
    } else { $token = sanitize_text_field($options['token']);
    }

    $loginResponse = OkeyHelper::postLogin($token);

    if ($loginResponse) {
        $input['token'] = $token;
        add_settings_error('wc_okey_settings_token', '', __('Autentificare realizata cu succes', 'wc_okey'), 'updated');
    }

    return $input;
}
