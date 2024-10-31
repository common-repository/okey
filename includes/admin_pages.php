<?php
add_action('admin_menu', 'wc_okey_add_menu');

function wc_okey_add_menu()
{
    add_menu_page(
        __('OKEY', 'wc_okey'),
        __('OKEY', 'wc_okey'),
        'manage_options',
        'wc_okey_main',
        'wc_okey_main',
        plugin_dir_url(__FILE__).'../assets/images/dashIcon.png'
    );

    add_submenu_page(
        'wc_okey_main',
        __('Autentificare', 'wc_okey'),
        __('Autentificare', 'wc_okey'),
        'manage_options',
        'wc_okey_login',
        'wc_okey_login'
    );

    add_submenu_page(
        'wc_okey_main',
        __('Setari', 'wc_okey'),
        __('Setari', 'wc_okey'),
        'manage_options',
        'wc_okey_settings',
        'wc_okey_settings'
    );
}
