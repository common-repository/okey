<?php

function wc_okey_settings()
{
    if(!apply_filters('auth_middleware', 9)) {
        wp_safe_redirect(esc_url(admin_url('admin.php')).'?page=wc_okey_login');
    }
    // verificare daca este autentificat sau nu
    $options = get_option('wc_okey_plugin_options');
    if (!empty($options) && is_array($options) && !empty($options['token'])) {
        $token = $options['token'];
        // luam setarile json
        $settingsResponse = OkeyHelper::getSettings($token);
        if (!empty($settingsResponse)) {
            $options = get_option('wc_okey_plugin_options_settings');
            $options['okey_settings'] = $settingsResponse;
            update_option('wc_okey_plugin_options_settings', $options);
            if (!empty($settingsResponse) && is_array($settingsResponse) && isset($settingsResponse['accountInfo']['isTaxable']) && !$settingsResponse['accountInfo']['isTaxable']) {
                if (!empty($settingsResponse) && is_array($settingsResponse) && isset($settingsResponse['vats'])) {
                    $options['included_vat'] = false;
                    $options['product_vat'] = $settingsResponse['vats'][0]['id'];
                    $options['vat_at_payment'] = false;
                    $options['okeyCompleteProductVatRates'] = $settingsResponse['vats'];
                    update_option('wc_okey_plugin_options_settings', $options);
                }
            }
        }
    } else {
        $token = '';
    }
    ?>
    <?php if(!empty($settingsResponse)) { ?>
    <div class="wrap">
        <div id="eokey_loader" class="loader overlay"><img style="width: 75px;" src="<?php echo plugin_dir_url(dirname(__FILE__)) . 'style/loader.gif'?>"/> </div>
        <h2><?php echo __('OKEY - Setari', 'wc_okey')?></h2>
        <?php settings_errors(); ?>
        <form action="options.php" method="post" onsubmit="showLoading();">
            <?php settings_fields('wc_okey_plugin_options_settings'); ?>
            <?php do_settings_sections('wc_okey_plugin_settings'); ?>
            <?php if (!empty($token)) : ?>
            <input class="button-primary" name="Submit" type="submit" value="<?php echo __('Salveaza modificarile', 'wc_okey')?>" style="font-size: 16px;" />
            <?php endif; ?>
        </form>
    </div>
    <?php } ?>
    <?php
    // style files
    wp_enqueue_style('wc_okey_main_style');
    wp_enqueue_script('wc_okey_send_order');
}

function wc_okey_plugin_settings_section_text()
{
    // verificare daca este autentificat sau nu
    $options = get_option('wc_okey_plugin_options');
    if (!empty($options) && is_array($options) && !empty($options['token'])) {
        $token = $options['token'];
    } else {
        $token = '';
    }

    if (empty($token)) {
        ?>
        <p><?php echo __('Nu sunteti conectat la OKEY', 'wc_okey')?>.</p>
        <p><?php echo __('Accesati sectiunea OKEY', 'wc_okey')?> > <a href="<?php echo esc_url(admin_url('admin.php'))?>?page=wc_okey_login"><?php echo __('Autentificare', 'wc_okey')?></a>.</p>
        <p><?php echo __('Conectati-va cu datele contului de OKEY', 'wc_okey')?>.</p>
        <br />
        <p><?php echo __('Nu aveti cont OKEY?', 'wc_okey')?><br /><?php echo __('Inregistrati-va GRATUIT <a href="https://portal.eokey.ro/inregistrare/" target="_blank">aici</a>', 'wc_okey')?>.</p>
        <?php
    }
}

function wc_okey_plugin_settings_vat_section_text()
{
    // nothing here
}

function wc_okey_plugin_settings_documents_section_text()
{
    // nothing here
}

function wc_okey_settings_display_included_vat()
{
    $options = get_option('wc_okey_plugin_options_settings');
    if (!empty($options) && is_array($options) && isset($options['included_vat'])) {
        $included_vat = $options['included_vat'];
    } else {
        $included_vat = 0;
    }

    echo '
    <select name="wc_okey_plugin_options_settings[included_vat]" style="width: 400px;">
        <option value="1" '.(!empty($included_vat)?'selected':'').'>'.__('Da', 'wc_okey').'</option>
        <option value="0" '.(empty($included_vat)?'selected':'').'>'.__('Nu', 'wc_okey').'</option>
    </select>';
    echo '<p class="description">'.__('Daca vrei ca preturile sa fie transmise din WooCommerce catre OKEY cu TVA inclus', 'wc_okey').'</p>';
}

function wc_okey_settings_display_product_vat()
{
    $options = get_option('wc_okey_plugin_options_settings');
    if (!empty($options) && is_array($options) && isset($options['product_vat'])) {
        $product_vat = $options['product_vat'];
    } else {
        $product_vat = 0;
    }

    $okey_settings = $options['okey_settings'];

    $options['okeyCompleteProductVatRates'] = $okey_settings['vats'];
    update_option('wc_okey_plugin_options_settings', $options);

    echo '
    <select id="wc_okey_plugin_options_settings_product_vats" name="wc_okey_plugin_options_settings[product_vat]" style="width: 400px;">
        <option value="" '.(($product_vat == 0) ? 'selected' : '').'>'.__('Alegeti Cota TVA', 'wc_okey').'</option>
    ';
    foreach ($okey_settings['vats'] as $s) {
        echo '<option value="'.$s['id'].'" '.($product_vat == $s['id'] ? 'selected' : '').'>'.$s['name'].'</option>';
    }
    echo '</select>';

    echo '<p class="description">'.__('Ce cota TVA se va aplica produselor pe documentul emis in OKEY', 'wc_okey').'</p>';
}

function wc_okey_settings_display_document_type()
{
    $options = get_option('wc_okey_plugin_options_settings');
    if (!empty($options) && is_array($options) && isset($options['document_type'])) {
        $document_type = $options['document_type'];
    } else {
        $document_type = OkeyDocumentHelper::OKEY_INVOICE_DATABASE_TYPE;
    }

    if (!empty($options) && is_array($options) && isset($options['document_series'])) {
        $document_series = $options['document_series'];
    } else {
        $document_series = '';
    }

    $okey_settings = $options['okey_settings'];

    $htmlFactura = '<option value="" '.(empty($document_series) ? 'selected' : '').'>'.__('Alegeti seria', 'wc_okey').'</option>';
    $htmlProforma = '<option value="" '.(empty($document_series) ? 'selected' : '').'>'.__('Alegeti seria', 'wc_okey').'</option>';

    if (!empty($okey_settings['fiscalInvoiceSeries'])) {
        foreach ($okey_settings['fiscalInvoiceSeries'] as $s) {
            $htmlFactura .= '<option value="'.$s['id'].'|'.$s['series'].'" '.($document_series == $s['id'].'|'.$s['series'] ? 'selected' : '').'>'.$s['series'].'</option>';
        }
    }

    if (!empty($okey_settings['proformaInvoiceSeries'])) {
        foreach ($okey_settings['proformaInvoiceSeries'] as $s) {
            $htmlProforma .= '<option value="'.$s['id'].'|'.$s['series'].'" '.($document_series == $s['id'].'|'.$s['series'] ? 'selected' : '').'>'.$s['series'].'</option>';
        }
    }

    $inlineJs = "function changeOkeyDocumentType(type){
        var htmlFactura =  '$htmlFactura';
        var htmlProforma = '$htmlProforma';
        if (type === '2') {
            document.getElementById('wc_okey_plugin_options_settings_document_series').innerHTML = htmlFactura;
        } else {
            document.getElementById('wc_okey_plugin_options_settings_document_series').innerHTML = htmlProforma;
            }
        };";

    wp_enqueue_script('wc_okey_settings_display_document_type');
    wp_add_inline_script('wc_okey_settings_display_document_type', $inlineJs);


    echo '
    <select id="wc_okey_plugin_options_settings_document_type" name="wc_okey_plugin_options_settings[document_type]" onchange="changeOkeyDocumentType(this.value)" style="width: 400px;">
        <option value="'.OkeyDocumentHelper::OKEY_INVOICE_DATABASE_TYPE.'" '.(($document_type == OkeyDocumentHelper::OKEY_INVOICE_DATABASE_TYPE) ? 'selected' : '').'>'.__('Factura', 'wc_okey').'</option>
        <option value="'.OkeyDocumentHelper::OKEY_PROFORMA_DATABASE_TYPE.'" '.(($document_type == OkeyDocumentHelper::OKEY_PROFORMA_DATABASE_TYPE)? 'selected' : '').'>'.__('Proforma', 'wc_okey').'</option>
    </select>';
}

function wc_okey_settings_display_document_series()
{
    $options = get_option('wc_okey_plugin_options_settings');
    if (!empty($options) && is_array($options) && isset($options['document_series'])) {
        $document_series = $options['document_series'];
    } else {
        $document_series = '';
    }

    // tip document
    if (!empty($options) && is_array($options) && isset($options['document_type'])) {
        $document_type = $options['document_type'];
    } else {
        $document_type = OkeyDocumentHelper::OKEY_INVOICE_DATABASE_TYPE;
    }

    $okey_settings = $options['okey_settings'];

    echo '
    <select id="wc_okey_plugin_options_settings_document_series" name="wc_okey_plugin_options_settings[document_series]" style="width: 400px;">
        <option value="" '.(empty($document_series) ? 'selected' : '').'>'.__('Alegeti seria', 'wc_okey').'</option>
    ';
    if ($document_type == OkeyDocumentHelper::OKEY_INVOICE_DATABASE_TYPE) {
        foreach ($okey_settings['fiscalInvoiceSeries'] as $s) {
            echo '<option value="'.$s['id'].'|'.$s['series'].'" '.($document_series == $s['id'].'|'.$s['series'] ? 'selected' : '').'>'.$s['series'].'</option>';
        }
    } else {
        foreach ($okey_settings['proformaInvoiceSeries'] as $s) {
            echo '<option value="'.$s['id'].'|'.$s['series'].'" '.($document_series == $s['id'].'|'.$s['series'] ? 'selected' : '').'>'.$s['series'].'</option>';
        }
    }
    echo '</select>';
}

function wc_okey_settings_display_product_currency()
{
    $options = get_option('wc_okey_plugin_options_settings');
    if (!empty($options) && is_array($options) && isset($options['product_currency'])) {
        $product_currency = $options['product_currency'];
    } else {
        $product_currency = '';
    }

    $okey_settings = $options['okey_settings'];

    echo '
    <select name="wc_okey_plugin_options_settings[product_currency]" style="width: 400px;">
        <option value="" '.(empty($product_currency) ? 'selected' : '').'>'.__('Alegeti moneda', 'wc_okey').'</option>
    ';
    if (!empty($okey_settings['currencies'])) {
        foreach ($okey_settings['currencies'] as $currency) {
            echo '<option value="'.$currency['id'].'" '.($currency['id'] == $product_currency ? 'selected' : '').'>'.$currency['name'].'</option>';
        }
    }
    echo '</select>';
    echo '<p class="description">'.__('Moneda aceasta se va prelua pe documentul emis in OKEY', 'wc_okey').'</p>';
}

function wc_okey_settings_display_document_model()
{
    $options = get_option('wc_okey_plugin_options_settings');
    if (!empty($options) && is_array($options) && isset($options['document_model'])) {
        $document_model = $options['document_model'];
    } else {
        $document_model = 0;
    }

    $okey_settings = $options['okey_settings'];

    if (!empty($okey_settings['models'])) {

        echo '<select name="wc_okey_plugin_options_settings[document_model]" style="width: 400px;">';

        foreach ($okey_settings['models'] as $model) {
            echo '<option value="'.$model['id'].'" '.($model['id'] == $document_model ? 'selected' : '').'>'.$model['name'].'</option>';
        }

        echo '</select>';
    }
}

function wc_okey_settings_display_document_language()
{
    $options = get_option('wc_okey_plugin_options_settings');
    if (!empty($options) && is_array($options) && isset($options['document_language'])) {
        $document_language = $options['document_language'];
    } else {
        $document_language = 0;
    }

    $okey_settings = $options['okey_settings'];

    if (!empty($okey_settings['languages'])) {

        echo '<select name="wc_okey_plugin_options_settings[document_language]" style="width: 400px;">';

        foreach ($okey_settings['languages'] as $language) {
            echo '<option value="'.$language['value'].'" '.($language['value'] == $document_language ? 'selected' : '').'>'.$language['label'].'</option>';
        }

        echo '</select>';
    }
}

function wc_okey_settings_display_document_due_days()
{
    $options = get_option('wc_okey_plugin_options_settings');
    if (!empty($options) && is_array($options) && isset($options['due_days'])) {
        $document_due_days = $options['due_days'];
    } else {
        $document_due_days = 0;
    }
    echo '<input type="text" name="wc_okey_plugin_options_settings[due_days]" style="width: 400px;" value="'.esc_attr($document_due_days).'">';
}

function wc_okey_settings_display_document_show_client_sold()
{
    $options = get_option('wc_okey_plugin_options_settings');
    if (!empty($options) && is_array($options) && isset($options['show_client_sold'])) {
        $show_client_sold = $options['show_client_sold'];
    } else {
        $show_client_sold = 0;
    }

    echo '
    <select name="wc_okey_plugin_options_settings[show_client_sold]" style="width: 400px;">
        <option value="0" '.(empty($show_client_sold) ? 'selected' : '').'>'.__('Nu', 'wc_okey').'</option>
        <option value="1" '.(!empty($show_client_sold) ? 'selected' : '').'>'.__('Da', 'wc_okey').'</option>
    </select>';
}

function wc_okey_settings_display_document_vat_at_payment()
{
    $options = get_option('wc_okey_plugin_options_settings');
    if (!empty($options) && is_array($options) && isset($options['vat_at_payment'])) {
        $vat_at_payment = $options['vat_at_payment'];
    } else {
        $vat_at_payment = 0;
    }

    echo '
    <select name="wc_okey_plugin_options_settings[vat_at_payment]" style="width: 400px;">
        <option value="0" '.(empty($vat_at_payment) ? 'selected' : '').'>'.__('Nu', 'wc_okey').'</option>
        <option value="1" '.(!empty($vat_at_payment) ? 'selected' : '').'>'.__('Da', 'wc_okey').'</option>
    </select>';
}

/**
 * This function validate OKEY settings form
 *
 * @param  $input
 * @return mixed
 */
function wc_okey_plugin_options_settings_validate($input)
{
    // verificam valorile
    if (!in_array($input['included_vat'], array(0, 1))) {
        $input['included_vat'] = 0;
    }

    if ((empty($input['product_vat']) || $input['product_vat'] == 0) && ($input['included_vat'] != 0)) {
        add_settings_error('wc_okey_settings_product_vat', '', __('Trebuie sa alegi o valoare pentru "Cota TVA"', 'wc_okey'), 'error');
    } else if (!in_array($input['document_type'], array(OkeyDocumentHelper::OKEY_INVOICE_DATABASE_TYPE, OkeyDocumentHelper::OKEY_PROFORMA_DATABASE_TYPE))) {
        $input['document_type'] = OkeyDocumentHelper::OKEY_INVOICE_DATABASE_TYPE;
    } else if (empty($input['document_series'])) {
        add_settings_error('wc_okey_settings_document_series', '', __('Trebuie sa alegi o valoare pentru "Serie implicita document emis"', 'wc_okey'), 'error');
    } else if (empty($input['product_currency'])) {
        add_settings_error('wc_okey_settings_product_currency', '', __('Trebuie sa alegi o valoare pentru "Moneda"', 'wc_okey'), 'error');
    } else if (empty($input['due_days']) || !is_numeric($input['due_days'])) {
        add_settings_error('wc_okey_settings_due_days', '', __('Numarul de zile pana la scadenta trebuie sa fie un numar valid.', 'wc_okey'), 'error');
    } else {
        add_settings_error('wc_okey_settings_saved', '', __('Setarile au fost salvate', 'wc_okey'), 'updated');
    }
    return $input;
}
