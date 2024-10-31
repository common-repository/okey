<?php

function wc_okey_order_details_meta_box()
{
    add_meta_box(
      'wc_okey_meta_box',
      'Facturare Okey',
      'wc_okey_order_details_invoice_box',
      'shop_order',
      'side',
      'high'
    );
}

/**
 * @param $post
 */
function wc_okey_order_details_invoice_box($post)
{
    echo '<div id="eokey_loader" class="loader overlay"><img style="width: 75px;" src="'.plugin_dir_url(dirname(__FILE__)).'style/loader.gif"'.'/> </div>';
    if(apply_filters( 'auth_middleware', 9)) {
        $series = get_post_meta($post->ID, 'okey_series_name', true);
        $number = get_post_meta($post->ID, 'okey_document_number', true);
        $invoiceId = get_post_meta($post->ID, 'okey_invoice_id', true);
        $link = get_post_meta($post->ID, 'okey_private_link', true);

        if ( !empty($link) ) {
            # style files
            wp_enqueue_style( 'wc_okey_main_style' );

            # vizualizeaza factura
            echo '<h4>' . $series . ' ' . $number . '</h4>';
            echo '<p>
            <a class="button tips"
               data-tip="' . __('Vizualizeaza in OKEY', 'wc_okey') . '"
               href="' . wc_okey_generate_url('okey-view-pdf',
                    $invoiceId) . '" target="_blank">' . __('Vizualizeaza in OKEY', 'wc_okey') . '</a>
            </p>';
        } else {
            # style files
            wp_enqueue_style( 'wc_okey_main_style' );

            # genereaza factura
            echo '<p>
            <button class="okey-custom-modal button tips" data-processing="false" data-url="' . wc_okey_generate_url('okey-create',
                $post->ID) . '">' . __('Emite in OKEY', 'wc_okey') . '</button>
            <div class="okey-modal-wrapper">
                <div class="okey-inner">
                    <div class="modal-header">
                        Pentru a putea emite o factura in OKEY <br/>
                        Te rugam sa selectezi o optiune!
                    </div>
                    <div class="okey-modal-body">
                        <input id="okeyIsPersoanaFizica" type="checkbox" name="okeyIsPersoanaFizica">Facturati la persoana fizica<br>
                        <input id="okeyIsClient" type="checkbox" name="okeyIsClient">Facturati la client<br>
                        <div class="okeyCuiContent">
                            <label id="okeyCuiLabel">Te rugam sa introduci un Cui valid</label>
                            <input type="text" id="okeyCui" name="okeyCui"/>
                        </div>
                    </div>
                    <div class="okey-modal-footer">
                        <button class="okey-modal-cancel">Cancel</button>
                        <button class="okey-modal-ok">Save</button>
                    </div>
                </div>
            </div>
        </p>';
            # script files
            wp_enqueue_script( 'wc_okey_send_order' );
        }
    }
}

/**
 * @param $arg_name
 * @param $orderId
 * @return string
 */
function wc_okey_generate_url($arg_name, $orderId)
{
    $action_url = add_query_arg($arg_name, $orderId);
    $complete_url = wp_nonce_url($action_url);

    return esc_url($complete_url);
}

function wc_okey_init_plugin_actions() {
    # links
    if (isset($_GET['okey-view-pdf'])) {
        wc_okey_view_invoice_pdf(sanitize_text_field($_GET['okey-view-pdf']));
    } else if (isset($_GET['okey-create'])) {
        $isPf = (intval(sanitize_text_field($_GET['isPf'])) == 1) ? true : false;
        wc_okey_create_invoice(sanitize_text_field($_GET['okey-create']), sanitize_text_field($_GET['cui']), $isPf);
    }
}

/**
 * Function return only OKEY invoice pdf content
 *
 * @param $invoiceId
 * @return array|WP_Error
 */
function wc_okey_get_invoice_pdf($invoiceId) {
    $loginOptions = get_option('wc_okey_plugin_options');
    $token = $loginOptions['token'];
    $args = array(
        'headers' => array(
            'Content-Type' => 'application/json; charset=utf-8',
            'Content-type' => 'application/pdf',
            'Access-token' =>  $token
        )
    );

    $pdfReturn = wp_remote_get(sprintf(OkeyHelper::OKEY_DOCUMENT_PDF_URL, $invoiceId), $args);

    return $pdfReturn;
}

/**
 * @param $invoiceId
 */
function wc_okey_view_invoice_pdf($invoiceId) {
    $pdfReturn = wc_okey_get_invoice_pdf($invoiceId);

    header('Content-type: application/pdf');
    echo wp_remote_retrieve_body($pdfReturn);
}

/**
 * @param $invoiceId
 * @param $orderId
 */
function wc_save_okey_invoice_pdf($invoiceId, $orderId) {
    $pdfReturn = wc_okey_get_invoice_pdf($invoiceId);
    file_put_contents(sprintf('%s/uploads/invoices/invoice_%d.pdf',WP_CONTENT_DIR , $orderId), $pdfReturn['body']);
}

/**
 * @param $orderId
 * @param $cui
 * @param $isPf
 */
function wc_okey_create_invoice($orderId, $cui, $isPf)
{
    $order = new WC_Order($orderId);
    $orderMeta = get_post_meta($orderId);
    $error = new WP_Error();

    ob_start();

    if(!empty($orderMeta)) {
        // get options
        $loginOptions = get_option('wc_okey_plugin_options');
        $token = $loginOptions['token'];

        # build custom fields
        $customer = array(
            'isPersoanaFizica' => $isPf,
            'cif' => $cui,
            'company' => method_exists($order,
                'get_billing_company') ? $order->get_billing_company('') : (isset($orderMeta['_billing_company']) ? $orderMeta['_billing_company'][0] : ''),
            'first_name' => method_exists($order,
                'get_billing_first_name') ? $order->get_billing_first_name('') : (isset($orderMeta['_billing_first_name']) ? $orderMeta['_billing_first_name'][0] : ''),
            'last_name' => method_exists($order,
                'get_billing_last_name') ? $order->get_billing_last_name('') : (isset($orderMeta['_billing_last_name']) ? $orderMeta['_billing_last_name'][0] : ''),
            'email' => method_exists($order,
                'get_billing_email') ? $order->get_billing_email('') : (isset($orderMeta['_billing_email']) ? $orderMeta['_billing_email'][0] : ''),
            'phone' => method_exists($order,
                'get_billing_phone') ? $order->get_billing_phone('') : (isset($orderMeta['_billing_phone']) ? $orderMeta['_billing_phone'][0] : ''),
            'country' => 'Romania',
            'address1' => method_exists($order,
                'get_billing_address1') ? $order->get_billing_address1('') : (isset($orderMeta['_billing_address_1']) ? $orderMeta['_billing_address_1'][0] : ''),
            'address2' => method_exists($order,
                'get_billing_address2') ? $order->get_billing_address2('') : (isset($orderMeta['_billing_address_2']) ? $orderMeta['_billing_address_2'][0] : ''),
            'city' => method_exists($order,
                'get_billing_city') ? $order->get_billing_city('') : (isset($orderMeta['_billing_city']) ? $orderMeta['_billing_city'][0] : ''),
            'state' => method_exists($order,
                'get_billing_state') ? $order->get_billing_state('') : (isset($orderMeta['_billing_state']) ? $orderMeta['_billing_state'][0] : ''),
            'postcode' => method_exists($order,
                'get_billing_postcode') ? $order->get_billing_postcode('') : (isset($orderMeta['_billing_postcode']) ? $orderMeta['_billing_postcode'][0] : ''),
            'okeyRegionId' => null,
            'okeyCityId' => null
        );

        // Go to select OKEY application region and city address codes.
        $okeyRegions = OkeyHelper::getOkeyRegions($token);

        foreach ($okeyRegions as $region) {
            if (strcmp(strtolower($customer['state']), strtolower($region['code'])) === 0) {
                $customer['okeyRegionId'] = $region['id'];
                break;
            }
        }
        // get all cities for selected Country (orase)
        if ($customer['okeyRegionId'] !== null) {
            $okeyCities = OkeyHelper::getOkeyCities($token, $customer['state']);

            $customerCity = strtolower(remove_accents($customer['city']));

            foreach ($okeyCities as $city) {
                if(levenshtein($customerCity, strtolower($city['name'])) === 0) {
                    $customer['okeyCityId'] = $city['id'];
                    break;
                } else if (levenshtein($customerCity, strtolower($city['name'])) <= 2) {
                    $customer['okeyCityId'] = $city['id'];
                }
            }
        }

        # verificam daca exista deja
        //$link = get_post_meta($orderId, 'okey_private_link', true);
        //if (!empty($link)) {
        //    header('Location: ' . $link);
        //    exit;
        //}

        // Get DocumentSettings
        $documentSettings = okeyGetInvoiceSettings();
        // Get Client Organization Details
        $clientOrganizationDetails = OkeyDocumentHelper::getClientData($customer, $token);
        // Get Woocommerce order products items
        $existingProducts = OkeyDocumentHelper::getOrderProducts($order, $documentSettings);
        // Get document next serial number

        $number = OkeyHelper::getNextSerialNumberRange($token, $documentSettings['range']);

        $okeyInvoice = [
          'currency' => $documentSettings['currency'],
          'date' => date('d-m-Y'),
          'dueDate' => $documentSettings['dueDate'],
          'invoiceModel' => $documentSettings['invoiceModel'],
          'items' => $existingProducts,
          'languageCode' => $documentSettings['languageCode'],
          'organization' => $clientOrganizationDetails,
          'range' => $documentSettings['range'],
          'series' => $documentSettings['series'],
          'status' => $documentSettings['status'],
          'type' => $documentSettings['type'],
          'showClientSold' => $documentSettings['showClientSold'],
          'vatAtPayment' => $documentSettings['vatAtPayment'],
          'number' => (string)$number,
          'useProductsFromStock' => true
        ];

        $invoiceResponse = OKEYHelper::postOkeyInvoice($token, $okeyInvoice);

        if((isset($invoiceResponse['id'])) && (isset($invoiceResponse['number'])) && (isset($invoiceResponse['series']))){
            # add order note
            $order = new WC_Order($orderId);
            $order->add_order_note(__('Documentul OKEY ' . $invoiceResponse['series'] . ' ' . $invoiceResponse['number'] . ' a fost creat', 'wc_okey'));

            wc_save_okey_invoice_pdf($invoiceResponse['id'], $orderId);

            # add meta
            add_post_meta(
              $orderId,
              'okey_document_number',
              $invoiceResponse['number']
            );
            add_post_meta(
              $orderId,
              'okey_series_name',
              $invoiceResponse['series']
            );
            add_post_meta(
              $orderId,
              'okey_private_link',
              sprintf(OkeyHelper::OKEY_DOCUMENT_PDF_URL, $invoiceResponse['id'])
            // wc_okey_generate_url('okey-view-pdf',$invoiceId)
            );
            add_post_meta(
              $orderId,
              'okey_invoice_id',
              $invoiceResponse['id']
            );
        }
    } else {
        do_action('display_errors', 'oder_error', 'Eroare: Te rugam mai intai creeaza o Comanda');
    }

    $content = ob_get_clean();
    if (is_admin()) {
        echo $content;
    }
}

/**
 * @return array
 */
function okeyGetInvoiceSettings(){
    $options = get_option('wc_okey_plugin_options_settings');
    // Prepare invoiceUserSettings
    $documentSettings = array(
      'include_vat' => $options['included_vat'],
      'vat' => get_okey_invoice_product_vat($options['product_vat']),
      'type' => (int)$options['document_type'],
      'status' => OkeyDocumentHelper::OKEY_INVOICE_STATUS_EMITTED,
      'series' => !empty($options['document_series']) ? explode('|', $options['document_series'])[1] : '',
      'range' => !empty($options['document_series']) ? explode('|', $options['document_series'])[0] : 0,
      'currency' => (int)$options['product_currency'],
      'invoiceModel' => (string)$options['document_model'],
      'languageCode' => (string)$options['document_language'],
      'dueDate' => date('d-m-Y', time() + (int)$options['due_days'] * 24 * 3600),
      'vatAtPayment' => (bool)$options['vat_at_payment'],
      'showClientSold' => (bool)$options['show_client_sold'],
    );

    return $documentSettings;

}

/**
 * @param $savedProductVat
 * @return array
 */
function get_okey_invoice_product_vat($savedProductVat) {
    $option = get_option('wc_okey_plugin_options_settings');
    $okeyProductVats = $option['okeyCompleteProductVatRates'];
    $okeyCompleteSelectedProductVat = array();
    foreach ($okeyProductVats as $productVat) {
        if($productVat['id'] === (int)$savedProductVat) {
            $okeyCompleteSelectedProductVat = $productVat;
        }
    }
    return $okeyCompleteSelectedProductVat;
}

/**
 * @param $columns
 * @return mixed
 */
function wc_okey_add_invoice_column($columns)
{
    $columns['okey_invoice'] = 'OKEY';
    return $columns;
}

/**
 * @param $column
 */
function wc_okey_add_invoice_column_content($column)
{
    global $post;
    switch ($column) {
        case 'okey_invoice':
            $series = get_post_meta($post->ID, 'okey_series_name', true);
            $number = get_post_meta($post->ID, 'okey_document_number', true);
            $link = get_post_meta($post->ID, 'okey_private_link', true);

            if (!empty($series) && !empty($number) && !empty($link)) {
                echo '<a href="'.$link.'" target="_blank">'.$series.' '.$number.'</a>';
            }
            break;

        case 'wc_actions':
        case 'order_actions':
            // wc_okey_order_details_invoice_column_button($post);
            break;
    }
}

/**
 * @return bool
 */
function authentication() {
    $options = get_option('wc_okey_plugin_options');
    if (!empty($options) && is_array($options) && !empty($options['token'])) {
        $loginResponse = OkeyHelper::postLogin( $options['token'] );
        if($loginResponse && !empty($loginResponse)) {
            return true;
        }
    }

    $options['token'] = '';
    update_option('wc_okey_plugin_options', $options);
    return false;
}

function show_error($setting, $message, $type = 'error') {
    add_settings_error($setting, '', $message, $type);
    settings_errors($setting);
}

add_action('wc_okey_create_invoice', 'wc_okey_create_invoice', 10, 3);
