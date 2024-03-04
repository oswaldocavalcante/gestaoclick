<?php

require_once plugin_dir_path(dirname(__FILE__)) . 'gestaoclick/class-gcw-gc-api.php';

class GCW_GC_Orcamento extends GCW_GC_Api {
    
    private $api_headers;
    private $api_endpoint;
    
    private $id = null;
    private $data = array();
    private $produtos = array();

    public function __construct($data = null, $cliente_id = null, $context = null | 'form') {
        parent::__construct();
        $this->api_headers  = parent::get_headers();
        $this->api_endpoint = parent::get_endpoint_orcamentos();

        if($context == 'form') {
            $this->produtos = $this->get_form_items($data);
            $this->data = array(
                "tipo"              => "produto",
                "cliente_id"        => $cliente_id,
                "situacao_id"       => get_option("gcw-settings-export-situacao"),
                "nome_canal_venda"  => "Internet",
                "produtos"          => $this->produtos,
            );
        }
    }

    public function get_id() {
        return $this->id;
    }

    public function set_props($props) {
        $this->data = $props;
    }

    public function export(){

        $response = wp_remote_post(
            $this->api_endpoint,
            array_merge($this->api_headers, ["body" => json_encode($this->data)])
        );

        $response = json_decode(wp_remote_retrieve_body($response), true);

        if (is_array($response) && $response["code"] == 200) {
            $this->id = $response["data"]["id"];
            return $this->id;
        } else {
            return new WP_Error("failed", __("GestãoClick: Error on export to GestãoClick.", "gestaoclick"));
        }
    }

    private function get_form_items($orcamento){
        $items = [];
        $item_id = 1;
        for ($i = 6; $i < count($orcamento); $i = $i+4) {
            $items = array_merge($items, [
                "produto" => [
                    "nome_produto"  =>  sanitize_text_field($orcamento["gcw_item_nome-{$item_id}"]) . " - " .
                                        sanitize_text_field($orcamento["gcw_item_descricao-{$item_id}"]),
                    "detalhes"      =>  sanitize_text_field($orcamento["gcw_item_tamanho-{$item_id}"]),
                    "quantidade"    =>  sanitize_text_field($orcamento["gcw_item_quantidade-{$item_id}"]),
                ],
            ]);
            ++$item_id;
        }

        return $items;
    }

    public static function render_form(){
        return '
			<form method="post">

				<h2>' . esc_html(__("Instituição", "gestaoclick")) . '</h2>
				<section id="gcw-section-institution" class="gcw-quote-section">
					<div class="gcw-field-wrap">
						<label>' . esc_html(__("Nome fantasia", "gestaoclick")) . '</label>
						<input type="text" class="gcw-quote-input" name="gcw_cliente_nome" required />
					</div>
					<div class="gcw-field-wrap">
						<label>' . esc_html(__("CNPJ/CPF", "gestaoclick")) . '</label>
						<input type="text" class="gcw-quote-input" name="gcw_cliente_cpf_cnpj" id="gcw-cliente-cpf-cnpj" required />
					</div>
				</section>

				<h2>' . esc_html(__("Responsável", "gestaoclick")) . '</h2>
				<section id="gcw-section-responsable" class="gcw-quote-section">
					<div class="gcw-field-wrap">
						<label>' . esc_html(__("Nome e sobrenome", "gestaoclick")) . '</label>
						<input type="text" name="gcw_contato_nome" class="gcw-quote-input" required />
					</div>
					<div class="gcw-field-wrap">
						<label>' . esc_html(__("Email", "gestaoclick")) . '</label>
						<input type="email" name="gcw_contato_email" class="gcw-quote-input" required />
					</div>
					<div class="gcw-field-wrap">
						<label>' . esc_html(__("Telefone", "gestaoclick")) . '</label>
						<input type="text" name="gcw_contato_telefone" class="gcw-quote-input" required />
					</div>
					<div class="gcw-field-wrap">
						<label>' . esc_html(__("Cargo", "gestaoclick")) . '</label>
						<input type="text" name="gcw_contato_cargo" class="gcw-quote-input" required />
					</div>
				</section>

				<h2>' . esc_html(__("Orçamento", "gestaoclick")) . '</h2>
				<section id="gcw-quote-section-items">
                    <fieldset id="gcw-quote-fieldset-1" class="gcw-quote-fieldset">
                        <legend class="gcw-quote-fieldset-legend">
                            ' . esc_html(__("Item 1", "gestaoclick")) . '
                        </legend>
                        <div class="gcw-field-wrap">
                            <label>' . esc_html(__("Nome", "gestaoclick")) . '</label>
                            <input type="text" class="gcw-quote-name gcw-quote-input" name="gcw_item_nome-1" required />
                        </div>
                        <div class="gcw-field-wrap">
                            <label>' . esc_html(__("Descrição", "gestaoclick")) . '</label>
                            <input type="text" class="gcw-quote-description gcw-quote-input" name="gcw_item_descricao-1" required />
                        </div>
                        <div class="gcw-field-wrap gcw-field-size">
                            <label>' . esc_html(__("Tamanho", "gestaoclick")) . '</label>
                            <select class="gcw-quote-size gcw-quote-input" name="gcw_item_tamanho-1" required>
                                <option value="' . esc_html(__("Selecionar", "gestaoclick")) . '" selected="selected">
                                    ' . esc_html(__("Selecionar", "gestaoclick")) . '
                                </option>
                                <option value="PP">PP</option>
                                <option value="P">P</option>
                                <option value="M">M</option>
                                <option value="G">G</option>
                                <option value="GG">GG</option>
                                <option value="XG">XG</option>
                                <option value="XGG">XGG</option>
                                <option value="PS">Plus Size</option>
                            </select>
                        </div>
                        <div class="gcw-field-wrap gcw-field-quantity">
                            <label>' . esc_html(__("Quantidade", "gestaoclick")) . '</label>
                            <input type="number" class="gcw-quote-quantity gcw-quote-input" name="gcw_item_quantidade-1" required value="10" min="10" inputmode="numeric" pattern="\d*" />
                        </div>
                        <a class="gcw-quote-button-remove" item_id="1">×</a>
                    </fieldset>
				</section>
				<a id="gcw-quote-add-item">' . esc_html(__("Adicionar item", "gestaoclick")) . '</a>

				<button type="submit" id="gcw-quote-send">
                    ' . esc_html(__("Solicitar orçamento", "gestaoclick")) . '
                </button>

			</form>
		';
    }
}