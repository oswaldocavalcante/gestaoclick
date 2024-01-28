<?php

class WCK_GC_Api {

	private $access_token;
	private $secret_access_token;
    private $headers;

	private $endpoint_products;
    private $endpoint_categories;
    private $endpoint_attributes;

    public function __construct() {
        
        $this->access_token =           get_option( 'wck-api-access-token' );
        $this->secret_access_token =    get_option( 'wck-api-secret-access-token' );
        $this->headers = array(
            'headers' => array (
                'Content-Type' =>           'application/json',
                'access-token' =>           $this->access_token,
                'secret-access-token' =>    $this->secret_access_token,
            ),
        );

        $this->endpoint_products =      'https://api.gestaoclick.com/api/produtos';
        $this->endpoint_categories =    'https://api.gestaoclick.com/api/grupos_produtos';
        $this->endpoint_attributes =    'https://api.gestaoclick.com/api/grades';
    }

    public function is_connected() {
        $http_code = null;

        $access_token =         get_option( 'wck-api-access-token' );
        $secret_access_token =  get_option( 'wck-api-secret-access-token' );

        if ( ( $this->access_token && $this->secret_access_token ) != '' ) {

            $url = 'https://api.gestaoclick.com/produtos';
            $args = array (
                'headers' => array (
                    'Content-Type' => 'application/json',
                    'access-token' => $access_token,
                    'secret-access-token' => $secret_access_token,
                ),
            );

            $response = wp_remote_get( $url, $args );
            $http_code = wp_remote_retrieve_response_code( $response );
        } else {
            return false;
        }

        if ( $http_code == 200 ) return true;
        else return false;
    }

    public function get_access_token() {
        return $this->access_token;
    }

    public function get_secret_access_token() {
        return $this->secret_access_token;
    }

    public function get_headers() {
        return $this->headers;
    }

    public function get_endpoint_products() {
        return $this->endpoint_products;
    }

    public function get_endpoint_categories() {
        return $this->endpoint_categories;
    }

    public function get_endpoint_attributes() {
        return $this->endpoint_attributes;
    }
}