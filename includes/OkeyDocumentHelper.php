<?php

/**
 * Helper Class OkeyDocumentHelper
 */
class OkeyDocumentHelper
{
    /** @var int */
    const OKEY_INVOICE_DATABASE_TYPE = 2;
    /** @var int */
    const OKEY_PROFORMA_DATABASE_TYPE = 1;
    /** @var int */
    const OKEY_INVOICE_STATUS_EMITTED = 0; //Emitted
    /** @var int */
    const OKEY_ORGANIZATION_TYPE_OFFICE = 1;

    /**
     * This function prepare and return okeyClient content data.
     *
     * @param  $customer
     * @param  $token
     * @return array
     */
    public static function getClientData($customer, $token)
    {
        $okeyClientData = array();

        if(!$customer['isPersoanaFizica']) {
            $cuiNumber = intval(preg_replace('/[^0-9]+/', '', $customer['cif']), 10);
            $cifAf = $words = preg_replace('/\d/', '', $customer['cif']);
            $cifAf = trim($cifAf);
            $cifAf = strtoupper($cifAf);
            if($cifAf == 'R') {
                $cifAf = 'RO';
            } else if($cifAf != 'RO') {
                $cuiNumber = $cifAf . $cuiNumber;
            }
            $okeyClientData['isPf'] = true;
        } else {
            $cifAf = '';
            $cuiNumber = $customer['cif'];
        }

        $country = !empty(WC()->countries->countries[$customer['country']]) ? WC()->countries->countries[$customer['country']] : $customer['country'];

        if($country != 'Romania') {
            // Go to select OKEY application selected country details.
            $okeyCountries = OkeyHelper::getOkeyCountries($token);

            foreach ($okeyCountries as $oc) {
                if (strcmp(strtolower($oc['iso2']), strtolower($customer['country'])) === 0) {
                    $country = $oc;
                }
            }

            $okeyClientData['registeredOffice'] = array(
                'country' => $country,
                'foreignCity' => $customer['city'],
                'street' => trim($customer['address1'].' '.$customer['address2']),
                'zipCode' => $customer['postcode'],
                'nr' => '',
                'ap' => '',
                'addressType' => self::OKEY_ORGANIZATION_TYPE_OFFICE
            );
            $okeyClientData['isForeign'] = true;
        } else {
            $okeyClientData['registeredOffice'] = array(
                'region' => $customer['okeyRegionId'],
                'city' => $customer['okeyCityId'],
                'street' => trim($customer['address1'].' '.$customer['address2']),
                'zipCode' => $customer['postcode'],
                'nr' => '',
                'ap' => '',
                'addressType' => self::OKEY_ORGANIZATION_TYPE_OFFICE
            );
            $okeyClientData['isForeign'] = false;
        }

        $clientName = trim($customer['company']);
        $clientClean = preg_replace('/[^a-z0-9]+/i', '', $clientName);
        $clientCompanyName = empty($clientName) || empty($clientClean) ? $customer['first_name'].' '.$customer['last_name'] : $clientName;

        $okeyClientData['name'] = $clientCompanyName;
        $okeyClientData['cifAf'] = $cifAf;
        $okeyClientData['cifNr'] = $cuiNumber;
        $okeyClientData['documentSendEmailAddresses'] = $customer['email'];
        $okeyClientData['isPersoanaFizica'] = $customer['isPersoanaFizica'];
        $okeyClientData['isPf'] = $customer['isPersoanaFizica'];

        return $okeyClientData;
    }

    /**
     * Function create order products for invoice that will be generated
     *
     * @param  WC_Order        $order
     * @param  $productSettings
     * @return array
     */
    public static function getOrderProducts(WC_Order $order, $productSettings)
    {
        $products = [];
        $orderItems = $order->get_items();

        if (! empty($orderItems)) {
            $productNumber = 1;
            foreach($orderItems as $itemId =>  $sItem) {
                $qty = method_exists($sItem, 'get_quantity') ? $sItem->get_quantity() : $sItem['qty'];

                $orderProduct = self::createOrderProduct($order, $productNumber, $sItem, $qty, $productSettings);
                if (! $orderProduct) { continue;
                }
                $products[] = $orderProduct;

                $productNumber++;
            }
        }

        return $products;
    }

    /**
     * This function prepare each orderProduct with necessary okey content.
     *
     * @param  WC_Order        $order
     * @param  $productNumber
     * @param  $orderItem
     * @param  $quantity
     * @param  $productSettings
     * @return array
     */
    private static function createOrderProduct(WC_Order $order, $productNumber, $orderItem, $quantity, $productSettings)
    {
        $baseItemPrice = $orderItem->get_product()->get_regular_price();
        $baseItemSalePrice = $orderItem->get_product()->get_sale_price();

        // convert string (romanian number) to float
        $baseItemPrice = self::_float($baseItemPrice);
        $product = array();
        $product['measureUnit'] = '';
        $product['description'] = self::getProductVariationName($orderItem);
        $product['qty'] = $quantity;
        $product['unitPrice'] = empty($baseItemSalePrice) ? $baseItemPrice : $baseItemSalePrice;
        $product['number'] = $productNumber;

        $product['vat'] = $productSettings['vat'];
        $noVatPrice = $product['unitPrice'] * $product['qty'];

        if ($productSettings['include_vat']) {
            $product['vatValue'] = $noVatPrice * ((int)$product['vat']['value'] / 100);
        } else {
            $product['vatValue'] = 0;
        }

        $product['value'] = $product['vatValue'] + $noVatPrice;
        $product['totalPrice'] = $product['value'];

        return $product;
    }

    /**
     * @param  $float
     * @return float
     */
    private static function _float($float)
    {
        if (self::_isLocalized($float) ) {
            $find = get_option('woocommerce_price_thousand_sep');
            $float = str_replace($find, '', $float);
            $find = get_option('woocommerce_price_decimal_sep');
            $float = str_replace($find, '.', $float);
        }

        return floatval($float);
    }

    /**
     * @param  $orderItem
     * @return mixed
     */
    private static function getProductVariationName($orderItem)
    {
        $product = apply_filters('woocommerce_order_item_product', $orderItem->get_product(), $orderItem);
        return $product->get_name();

    }

    /**
     * @param  $number
     * @return bool
     */
    private static function _isLocalized($number)
    {
        $find = get_option('woocommerce_price_decimal_sep');
        return false !== strpos($number, $find);
    }
}
