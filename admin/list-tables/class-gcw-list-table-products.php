<?php

// Tutorial: https://supporthost.com/wp-list-table-tutorial/

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

require_once GCW_ABSPATH . 'integrations/woocommerce/class-gcw-wc-products.php';

class GCW_List_Table_Products extends WP_List_Table {

    private $table_data;

    function __construct() 
    {
        global $status, $page;

        parent::__construct( array
        (
          'singular' => 'product',
          'plural' => 'products',
          'ajax' => false
        )); 
    }

   function column_default($item, $column_name) 
   {
        switch ($column_name) 
        {
            case 'codigo_barra':
            case 'nome':
            case 'estoque':
            case 'valor_venda':
                return $item[$column_name];
            default:
                return print_r($item,true);
        }
    }

    function column_cb($item) 
    {
        return sprintf('<input type="checkbox" name="bulk-action[]" value="%1$s" />', $item['codigo_barra']);
    }    

    function get_columns()
    {
        $columns = array
        (
            'cb'            => '<input type="checkbox" />',
            'codigo_barra'  => __('Código', 'gestaoclick'),
            'nome'          => __('Nome', 'gestaoclick'),
            'estoque'       => __('Estoque', 'gestaoclick'),
            'valor_venda'   => __('Preço', 'gestaoclick')
        );

        return $columns;
    }

    function get_sortable_columns() 
    {
        $sortable_columns = array
        (
            'id'            => array('id', false),
            'nome'          => array('nome', false),
            'grupo_pai_id'  => array('estoque', false),
            'grupo_pai_id'  => array('valor_venda', false)
        );

        return $sortable_columns;
    }

    function get_bulk_actions() 
    {
        $actions = array
        (
            'import' => 'Importar selecionados',
            'import_all' => 'Importar todos'
        );

        return $actions;
    }

    function process_bulk_action(GCW_WC_Products $products) 
    {
        if('import' === $this->current_action()) 
        {
            if(isset($_POST['gcw_nonce_products']) && wp_verify_nonce($_POST['gcw_nonce_products'], 'gcw_form_products'))
            {
                $selected_items = isset($_POST['bulk-action']) ? $_POST['bulk-action'] : array();
                $products->import($selected_items);
            }
        }

        if( 'import_all' === $this->current_action() ) {
            $products->import('all');
        }
    }

    // Sorting function
    function usort_reorder($a, $b)
    {
        // If no sort, default to user_login
        $orderby = (!empty($_GET['orderby'])) ? $_GET['orderby'] : 'nome'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

        // If no order, default to asc
        $order = (!empty($_GET['order'])) ? $_GET['order'] : 'asc'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

        // Determine sort order
        $result = strcmp($a[$orderby], $b[$orderby]);

        // Send final sort direction to usort
        return ($order === 'asc') ? $result : -$result;
    }

    public function prepare_items()
    {
        $products = new GCW_WC_Products();
        $this->table_data = $products->fetch_api();
        
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $primary  = 'name';
        $this->_column_headers = array($columns, $hidden, $sortable, $primary);
        $this->process_bulk_action($products);
        
        /* pagination */
        $total_items = count($this->table_data);
        $per_page = 100;
        $current_page = $this->get_pagenum();
        $this->table_data = array_slice($this->table_data, (($current_page - 1) * $per_page), $per_page);
        $this->set_pagination_args(array
        (
            'total_items' => $total_items, // total number of items
            'per_page'    => $per_page, // items to show on a page
            'total_pages' => ceil( $total_items / $per_page ) // use ceil to round up
        ));

        usort($this->table_data, array(&$this, 'usort_reorder'));

        $this->items = $this->table_data;
    }
}