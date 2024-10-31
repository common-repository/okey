<?php
/*
Plugin Name: OKEY
Plugin URI: https://portal.eokey.ro/
Description: Acest modul permite generarea automata de facturi prin OKEY (https://portal.eokey.ro)
Version: 1.0.3
Author: LifeIsHard
Author URI: https://portal.eokey.ro
*/
$pluginDir = plugin_dir_path(__FILE__);
$pluginDirUrl = plugin_dir_url(__FILE__);

// helpers
require_once $pluginDir.'includes/OkeyHelper.php';
require_once $pluginDir.'includes/OkeyDocumentHelper.php';
require_once ABSPATH.'wp-admin/includes/template.php';

// menus
require $pluginDir.'includes/admin_pages.php';


function wc_okey_admin_get_settings()
{
    $settings = false;

    $options = get_option('wc_okey_plugin_options');

    if (!empty($options) && is_array($options) && isset($options['token'])) {
        $token = $options['token'];
        $settings = OkeyHelper::getSettings($token);
    }

    return $settings;
}
// options
add_action('admin_init', 'wc_okey_admin_init');
function wc_okey_admin_init()
{
    if (!empty(sanitize_text_field($_GET['page']))
        && in_array(sanitize_text_field($_GET['page']), array('wc_okey_settings')) 
    ) {
        $settings = wc_okey_admin_get_settings();
    }

    // for login
    register_setting(
        'wc_okey_plugin_options',
        'wc_okey_plugin_options',
        'wc_okey_plugin_options_validate'
    );
    add_settings_section(
        'wc_okey_plugin_login',
        '',
        'wc_okey_plugin_login_section_text',
        'wc_okey_plugin'
    );
    add_settings_field(
        'wc_okey_plugin_options_api_token',
        __('API - TOKEN', 'wc_okey'),
        'wc_okey_settings_display_api_token',
        'wc_okey_plugin',
        'wc_okey_plugin_login'
    );

    // for settings
    register_setting(
        'wc_okey_plugin_options_settings',
        'wc_okey_plugin_options_settings',
        'wc_okey_plugin_options_settings_validate'
    );
    if (!empty($settings)) {
        add_settings_section(
            'wc_okey_plugin_settings',
            '',
            'wc_okey_plugin_settings_section_text',
            'wc_okey_plugin_settings'
        );
    }
    if (isset($settings['accountInfo']['isTaxable']) && !empty($settings['accountInfo']['isTaxable'])) {
        add_settings_section(
            'wc_okey_plugin_settings_vat',
            'Setari TVA',
            'wc_okey_plugin_settings_vat_section_text',
            'wc_okey_plugin_settings'
        );
    }

    if (!empty($settings['accountInfo']) && isset($settings['accountInfo']['isTaxable']) && !empty($settings['accountInfo']['isTaxable'])) {
        add_settings_field(
            'wc_okey_plugin_options_settings_included_vat',
            __('Preturile includ TVA?', 'wc_okey'),
            'wc_okey_settings_display_included_vat',
            'wc_okey_plugin_settings',
            'wc_okey_plugin_settings_vat'
        );
        if(!empty($settings['vats'])) {
            add_settings_field(
                'wc_okey_plugin_options_settings_product_vat',
                __('Cota TVA produse', 'wc_okey'),
                'wc_okey_settings_display_product_vat',
                'wc_okey_plugin_settings',
                'wc_okey_plugin_settings_vat'
            );
        }
    }

    if (!empty($settings)) {
        add_settings_section(
            'wc_okey_plugin_settings_documents',
            'Setari emitere documente',
            'wc_okey_plugin_settings_documents_section_text',
            'wc_okey_plugin_settings'
        );
    }

    if (!empty($settings)) {
        add_settings_field(
            'wc_okey_plugin_options_settings_document_type',
            __('Tipul de document emis in OKEY', 'wc_okey'),
            'wc_okey_settings_display_document_type',
            'wc_okey_plugin_settings',
            'wc_okey_plugin_settings_documents'
        );
        add_settings_field(
            'wc_okey_plugin_options_settings_document_series',
            __('Serie implicita document emis', 'wc_okey'),
            'wc_okey_settings_display_document_series',
            'wc_okey_plugin_settings',
            'wc_okey_plugin_settings_documents'
        );
        add_settings_field(
            'wc_okey_plugin_options_settings_billing_currency',
            __('Moneda documentului emis in OKEY', 'wc_okey'),
            'wc_okey_settings_display_product_currency',
            'wc_okey_plugin_settings',
            'wc_okey_plugin_settings_documents'
        );
        add_settings_field(
            'wc_okey_plugin_options_settings_document_model',
            __('Modelul documentului emis in OKEY', 'wc_okey'),
            'wc_okey_settings_display_document_model',
            'wc_okey_plugin_settings',
            'wc_okey_plugin_settings_documents'
        );
        add_settings_field(
            'wc_okey_plugin_options_settings_document_language',
            __('Limba documentului emis in OKEY', 'wc_okey'),
            'wc_okey_settings_display_document_language',
            'wc_okey_plugin_settings',
            'wc_okey_plugin_settings_documents'
        );
        add_settings_field(
            'wc_okey_plugin_options_settings_document_due_days',
            __('Numarul de zile pana la scadenta', 'wc_okey'),
            'wc_okey_settings_display_document_due_days',
            'wc_okey_plugin_settings',
            'wc_okey_plugin_settings_documents'
        );
        add_settings_field(
            'wc_okey_plugin_options_settings_document_show_client_sold',
            __('Doriti ca soldul clientului sa fie afisat in documentul emis ?', 'wc_okey'),
            'wc_okey_settings_display_document_show_client_sold',
            'wc_okey_plugin_settings',
            'wc_okey_plugin_settings_documents'
        );
    }

    if (isset($settings['accountInfo']['isTaxable']) && !empty($settings['accountInfo']['isTaxable'])) {
        add_settings_field(
            'wc_okey_plugin_options_settings_document_vat_at_payment',
            __('TVA la incasare', 'wc_okey'),
            'wc_okey_settings_display_document_vat_at_payment',
            'wc_okey_plugin_settings',
            'wc_okey_plugin_settings_documents'
        );
    }
}

// option pages
require $pluginDir.'includes/options_main_page.php';
require $pluginDir.'includes/options_login_page.php';
require $pluginDir.'includes/options_settings_page.php';

// woocommerce stuff
require $pluginDir.'includes/woocommerce_functions.php';

// check if woocommerce is active
if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    // afisare buton de emitere factura in comenzi
    add_action('init', 'wc_okey_init_plugin_actions');
    add_action('add_meta_boxes', 'wc_okey_order_details_meta_box');
    wp_register_script('wc_okey_settings_display_document_type', '');
    wp_register_script('wc_okey_send_order', plugin_dir_url(__FILE__) . '/style/auxjava.js');
    wp_register_style('wc_okey_main_style', plugin_dir_url(__FILE__) . '/style/main.css');

    // afisare coloana cu factura in lista de comenzi
    add_filter('manage_edit-shop_order_columns', 'wc_okey_add_invoice_column', 11);
    add_action('manage_shop_order_posts_custom_column', 'wc_okey_add_invoice_column_content', 11, 2);
    add_action('display_errors', 'show_error', 10, 3);
    add_filter('auth_middleware', 'authentication', 12);
}
