<?php

/**
 * Connector Class OkeyHelper
 */
class OkeyHelper
{
    const OKEY_BASE_API_URL = 'https://portal.eokey.ro/api';
    const OKEY_LOGIN_URL = self::OKEY_BASE_API_URL . '/external/v1/account/information';
    const OKEY_SETTINGS_URL = self::OKEY_BASE_API_URL . '/external/v1/invoice/all/data';
    const OKEY_REGIONS_URL = self::OKEY_BASE_API_URL . '/external/v1/regions';
    const OKEY_CITIES_URL = self::OKEY_BASE_API_URL . '/external/v1/cities/%s';
    const OKEY_COUNTRIES_URL = self::OKEY_BASE_API_URL . '/external/v1/countries';
    const OKEY_NEXT_SERIAL_NUMBER_URL = self::OKEY_BASE_API_URL . '/external/v1/next-invoice-serial-number/%d';
    const OKEY_EMIT_DOCUMENT_URL = self::OKEY_BASE_API_URL . '/external/v1/invoices';
    const OKEY_DOCUMENT_PDF_URL = self::OKEY_BASE_API_URL . '/external/v1/invoices/%d/pdf';

    const DEFAULT_LOGIN_ERROR = 'Autentificare esuata. Va rugam verificati datele si incercati din nou.';
    const CERT_URL_ERROR = 'Accesul catre serviciul OKEY este restrictionat. Va rugam verificati configuratia serverului si incercati din nou.';
    const SERVER_ERROR = 'A intervenit o eroare la comunicarea cu OKEY. Va rugam verificati datele de conectare / reincercati o noua autentificare cu datele existente.';

    /**
     * This function check authentication api token
     *
     * @param  $token
     * @return array|mixed|object
     */
    public static function postLogin( $token )
    {
        $args = array(
            'headers' => self::setHeaders($token)
        );

        $loginResponse = wp_remote_get(self::OKEY_LOGIN_URL, $args);
        return self::checkResponse($loginResponse);
    }

    /**
     * This function returns an array that contains all necessary OKEY settings
     *
     * @param  $token
     * @return array|mixed|object
     */
    public static function getSettings( $token )
    {
        $args = array(
            'headers' => self::setHeaders($token)
        );

        $settingsResponse = wp_remote_get(self::OKEY_SETTINGS_URL, $args);

        return self::checkResponse($settingsResponse);
    }

    /**
     * This function returns available OKEY application countries
     *
     * @param  $token
     * @return array|mixed|object
     */
    public static function getOkeyCountries( $token )
    {
        $args = array(
            'headers' => self::setHeaders($token)
        );

        $response = wp_remote_get(self::OKEY_COUNTRIES_URL, $args);

        return self::checkResponse($response);
    }

    /**
     * This function returns available OKEY application regions
     *
     * @param  $token
     * @return array|mixed|object
     */
    public static function getOkeyRegions( $token )
    {
        $args = array(
            'headers' => self::setHeaders($token)
        );

        $response = wp_remote_get(self::OKEY_REGIONS_URL, $args);

        return self::checkResponse($response);
    }

    /**
     * This function returns available OKEY application cities
     * based on saved Region
     *
     * @param  $token
     * @param  $city
     * @return array|mixed|object
     */
    public static function getOkeyCities( $token, $city )
    {
        $args = array(
            'headers' => self::setHeaders($token)
        );

        $response = wp_remote_get(sprintf(self::OKEY_CITIES_URL, $city), $args);

        return self::checkResponse($response);
    }

    /**
     * This function returns nextSerialNumber for emitted invoice
     *
     * @param  $token
     * @param  $range
     * @return array|mixed|object
     */
    public static function getNextSerialNumberRange( $token, $range )
    {
        $args = array(
            'headers' => self::setHeaders($token)
        );

        $response = wp_remote_get(sprintf(self::OKEY_NEXT_SERIAL_NUMBER_URL, $range), $args);

        return self::checkResponse($response);
    }

    /**
     * This function set general Headers for Http request
     *
     * @param  $token
     * @return array
     */
    public static function setHeaders( $token )
    {
        return  array(
          'Content-Type' => 'application/json; charset=utf-8',
          'Accept'       => 'application/json',
          'Access-token' => $token
        );
    }

    /**
     * This function post created invoice to OKEY application
     *
     * @param  $token
     * @param  $postData
     * @return array|mixed|object
     */
    public static function postOkeyInvoice( $token, $postData)
    {
        $args = array(
            'headers' => self::setHeaders($token),
            'body' => json_encode($postData),
            'method' => 'POST',
            'data_format' => 'body',
        );

        $response = wp_remote_post(self::OKEY_EMIT_DOCUMENT_URL, $args);

        return self::checkResponse($response);
    }

    /**
     * This function retrieve the response code from the HTTP request using the GET or POST method.
     *
     * @param  $data
     * @return array|mixed|object
     */
    public static function checkResponse( $data )
    {
        $response_code = wp_remote_retrieve_response_code($data);

        if ($response_code == 403) {
            do_action('display_errors', '403', self::CERT_URL_ERROR);
            return false;
        } else if ($response_code >= 500) {
            do_action('display_errors', '500', self::SERVER_ERROR);
            return false;
        } else if ($response_code == 200) {
            return json_decode(wp_remote_retrieve_body($data), true);
        } else {
            do_action('display_errors', 'error', json_decode(wp_remote_retrieve_body($data), true)['message']);
            return false;
        }

        return false;
    }
}
