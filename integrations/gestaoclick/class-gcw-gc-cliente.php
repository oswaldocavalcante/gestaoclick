<?php

require_once plugin_dir_path(dirname(__FILE__)) . 'gestaoclick/class-gcw-gc-api.php';

class GCW_GC_Cliente extends GCW_GC_Api {

    private $id = null;

    private $api_headers;
    private $api_endpoint;

    /**
     * Stores client data.
     * @var array
     */
    private $data = array(
        'tipo_pessoa'   => '',
        'nome'          => '',
        'cpf'           => '',
        'cnpj'          => '',
        'email'         => '',
        'telefone'      => '',
        'contatos'      => array(
            'contato'   => array(
                'nome'          => '',
                'cargo'         => '',
                'observacao'    => ''
            )
        ),
        'enderecos'     => array(
            'endereco'  => array(
                'cep'           => '',
                'logradouro'    => '',
                'complemento'   => '',
                'pais'          => '',
                'nome_cidade'   => '',
                'estado'        => '',
            )
        ),
    );

    /** 
     * @param object|array  $data Data core to create a new client and export to GestãoClick
     * @param string $context Context to select which way the client should be created
    */
    public function __construct($data = null, $context = null | 'woocommerce' | 'form') {
        parent::__construct();
        $this->api_headers =    parent::get_headers();
        $this->api_endpoint =   parent::get_endpoint_clients();

        if($context == 'woocommerce') {
            $this->data = array(
                'tipo_pessoa'   => 'PF',
                'nome'          => $data->get_first_name() . ' ' . $data->get_last_name(),
                'cpf'           => $data->get_meta('billing_cpf'),
                'email'         => $data->get_email(),
                'telefone'      => $data->get_billing_phone(),
                'enderecos'     => array(
                    'endereco'  => array(
                        'cep'           => $data->get_billing_postcode(),
                        'logradouro'    => $data->get_billing_address_1(),
                        'complemento'   => $data->get_billing_address_2(),
                        'pais'          => $data->get_billing_country(),
                        'nome_cidade'   => $data->get_billing_city(),
                        'estado'        => $data->get_billing_state(),
                    )
                ),
            );

            if(!$this->get_cliente_by_cpf_cnpj($this->data['cpf'])){
                $this->export();
            }
        } elseif ($context == 'form') {
            $cliente_cpf_cnpj = sanitize_text_field($data["gcw_cliente_cpf_cnpj"]);

            $this->data = array(
                "tipo_pessoa"   => strlen($cliente_cpf_cnpj) == 18 ? "PJ" : "PF",
                "cnpj"          => strlen($cliente_cpf_cnpj) == 18 ? $cliente_cpf_cnpj : "",
                "cpf"           => strlen($cliente_cpf_cnpj) == 14 ? $cliente_cpf_cnpj : "",
                "nome"          => sanitize_text_field($data["gcw_cliente_nome"]),
                "contatos"      => [
                    "contato" => [
                        "nome"          => sanitize_text_field($data["gcw_contato_nome"]),
                        "cargo"         => sanitize_text_field($data["gcw_contato_cargo"]),
                        "observacao"    => sanitize_email($data["gcw_contato_email"]) . " / " . 
                                        sanitize_text_field($data["gcw_contato_telefone"]),
                    ],
                ],
            );

            if ($this->data['tipo_pessoa'] == 'PJ' && !$this->get_cliente_by_cpf_cnpj($this->data['cnpj'])) {
                $this->export();
            }
            elseif ($this->data['tipo_pessoa'] == 'PF' && !$this->get_cliente_by_cpf_cnpj($this->data['cpf'])) {
                $this->export();
            }
        }
    }

    public function get_id() {
        return $this->id;
    }

    public function export() {
        
        $response = wp_remote_post( 
            $this->api_endpoint, 
            array_merge(
                $this->api_headers,
                array( 'body' => json_encode($this->data) ),
            ) 
        );

        $response_body = json_decode(wp_remote_retrieve_body( $response ), true);

        if( $response_body['code'] == 200 ) {
            $this->id = $response_body['data']['id'];
            return $this->id;
        } else {
            return new WP_Error( 'failed', __( 'GestãoClick: Error on export client to GestãoClick.', 'gestaoclick' ) );
        }
    }

    public function get_cliente_by_cpf_cnpj($cpf_cnpj) {

        $body = wp_remote_retrieve_body(
            wp_remote_get($this->api_endpoint . '?cpf_cnpj=' . $cpf_cnpj, $this->api_headers)
        );

        $body = json_decode($body, true);

        if (is_array($body['data'])) {
            $this->id = $body['data'][0]['id'];
            return $body['data'][0];
        } else {
            return false;
        }
    }

    public function set_props($data){
        $this->data = $data;
    }
}