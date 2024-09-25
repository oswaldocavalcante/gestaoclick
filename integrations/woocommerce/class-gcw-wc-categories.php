<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * Sync products to WooCommerce from GestãoClick API
 *
 * @package    Wooclick
 * @subpackage Wooclick/admin
 * @author     Oswaldo Cavalcante <contato@oswaldocavalcante.com>
 */

require_once GCW_ABSPATH . 'integrations/gestaoclick/class-gcw-gc-api.php';

class GCW_WC_Categories extends GCW_GC_Api {

    private $api_endpoint;
    private $api_headers;

    private $fetched_categories = array();

    public function __construct() 
    {
        parent::__construct();
        
        $this->api_endpoint = parent::get_endpoint_categories();
        $this->api_headers =  parent::get_headers();
    }

    public function fetch_api()
    {
        $categories = [];
        $proxima_pagina = 1;

        do 
        {
            $response = wp_remote_retrieve_body( 
                wp_remote_get( $this->api_endpoint . '?pagina=' . $proxima_pagina, $this->api_headers )
            );

            $response = json_decode($response, true);

            if(is_array($response) && $response['code'] == 200)
            {
                $proxima_pagina = $response['meta']['proxima_pagina'];
                $categories = array_merge($categories,$response['data']);
            }
        } 
        while ( $proxima_pagina != null );

        $this->fetched_categories = $categories;

        return $categories;
    }

    public function import($categories_ids)
    {
        $categories             = $this->fetch_api();
        $categories_selection   = get_option('gcw-settings-categories-selection');

        if( $categories_selection ) 
        {
            $filtered_categories = array_filter($categories, function ($item) use ($categories_selection) {
                return (in_array($item['id'], $categories_selection));
            });

            $categories = $filtered_categories;
        }

        // Filtering selected categories
        $selected_categories = array();
        if (is_array($categories_ids))
        {
            $selected_categories = array_filter($categories, function ($item) use ($categories_ids) {
                return in_array($item['id'], $categories_ids);
            });
        } 
        elseif ($categories_ids == 'all') {
            $selected_categories = $categories;
        }

        // Runs 1x for registry and 2x for set parent categories
        foreach ($selected_categories as $category ) {
            $this->save($category);
        }

        foreach ($selected_categories as $category) {
            $this->save($category);
        }

        wp_admin_notice(sprintf('GestãoClick: %d categorias importadas com sucesso.', count($selected_categories)), array('type' => 'success', 'dismissible' => true));
    }

    private function save($category)
    {
        $taxonomy = 'product_cat';
        
        // Buscar a categoria pelo meta dado 'gc_category_id'
        $category_term = get_terms(array(
            'taxonomy' => $taxonomy,
            'meta_query' => array
            (
                array
                (
                    'key' => 'gc_category_id',
                    'value' => $category['id'],
                    'compare' => '='
                )
            ),
            'hide_empty' => false,
        ));

        // Se a categoria foi encontrada
        if (!empty($category_term) && !is_wp_error($category_term)) 
        {
            $category_term = $category_term[0];
            $parent_term_id = 0;

            if ($category['grupo_pai_id']) {
                $parent_term_id = $this->get_category_parent_id($this->fetched_categories, $category, $taxonomy);
            }

            // Atualizar a categoria existente
            wp_update_term
            (
                $category_term->term_id,
                $taxonomy,
                array(
                    'name' => $category['nome'],
                    'slug' => sanitize_title($category['nome']),
                    'description' => $category['meta_descricao'],
                    'parent' => $parent_term_id,
                )
            );
        } 
        else 
        {
            // Criar uma nova categoria
            $new_category = wp_insert_term
            (
                $category['nome'],
                $taxonomy,
                array(
                    'slug' => sanitize_title($category['nome']),
                    'description' => $category['meta_descricao'],
                    'parent' => $this->get_category_parent_id($this->fetched_categories, $category, $taxonomy),
                )
            );

            if (!is_wp_error($new_category)) {
                // Adicionar o meta dado 'gc_category_id' à nova categoria
                add_term_meta($new_category['term_id'], 'gc_category_id', $category['id'], true);
            }
        }
    }

    public function get_category_parent_id($gc_categories, $gc_category, $taxonomy)
    {
        foreach ($gc_categories as $parent_candidate)
        {
            if($gc_category['grupo_pai_id'] == $parent_candidate['id'])
            {
                $parent_terms = get_terms(array(
                    'taxonomy' => $taxonomy,
                    'meta_query' => array
                    (
                        array
                        (
                            'key' => 'gc_category_id',
                            'value' => $parent_candidate['id'],
                            'compare' => '='
                        )
                    ),
                    'hide_empty' => false,
                ));
                
                return $parent_terms[0]->term_id;
            }
        }

        return false;
    }

    public function get_options_for_settings()
    {
        $categorias = $this->fetch_api();
        $array_options = [];

        foreach ($categorias as $categoria) {
            $array_options[$categoria['id']] = $categoria['nome'];
        }

        return $array_options;
    }
}