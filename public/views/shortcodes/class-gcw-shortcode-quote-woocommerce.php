<?php

class GCW_Shortcode_Quote_WooCommmerce
{
    public function render()
    {
        $this->update_quote_quantities();
        wc_print_notices();

        ob_start();
        $quote_items = isset($_SESSION['quote_items']) ? $_SESSION['quote_items'] : array();

        if (is_array($quote_items) && !empty($quote_items)) :
        $quote_subtotal = $this->get_quote_subtotal($quote_items);
        ?>
            <div id="gcw-quote-container">

                <div id="gcw_quote_forms_container">

                <?php if (!is_user_logged_in()) : ?>
                    <form id="gcw_registration_form" method="post" style="display: none">
                        <h2><?php echo esc_html(__("Empresa", "gestaoclick")); ?></h2>
                        <section id="gcw-section-institution" class="gcw-quote-section">
                            <div class="gcw-field-wrap">
                                <label><?php echo esc_html(__("Nome fantasia", "gestaoclick")); ?></label>
                                <input type="text" class="gcw-quote-input" name="gcw_cliente_nome" required />
                            </div>
                            <div class="gcw-field-wrap">
                                <label><?php echo esc_html(__("CNPJ/CPF", "gestaoclick")); ?></label>
                                <input type="text" class="gcw-quote-input" name="gcw_cliente_cpf_cnpj" id="gcw-cliente-cpf-cnpj" required />
                            </div>
                        </section>

                        <h2><?php echo esc_html(__("Responsável", "gestaoclick")); ?></h2>
                        <section id="gcw-section-responsable" class="gcw-quote-section">
                            <div class="gcw-field-wrap" id="gcw_field_name">
                                <label><?php echo esc_html(__("Nome e sobrenome", "gestaoclick")); ?></label>
                                <input type="text" name="gcw_contato_nome" class="gcw-quote-input" required />
                            </div>
                            <div class="gcw-field-wrap">
                                <label><?php echo esc_html(__("Email", "gestaoclick")); ?></label>
                                <input type="email" name="gcw_contato_email" class="gcw-quote-input" required />
                            </div>
                            <div class="gcw-field-wrap">
                                <label><?php echo esc_html(__("Telefone", "gestaoclick")); ?></label>
                                <input type="text" name="gcw_contato_telefone" class="gcw-quote-input" required />
                            </div>
                        </section>

                        <input type="submit" name="gcw_register_submit" value="<?php echo esc_attr__("Criar conta", "gestaoclick"); ?>" />
                    </form>
                <?php endif; ?>

                    <form id="gcw-quote-form" class="woocommerce-cart-form" method="post">
                        <table id="gcw-quote-woocommerce-table" class="shop_table shop_table_responsive cart woocommerce-cart-form__contents" cellspacing="0">

                            <thead>
                                <tr>
                                    <th class="product-remove"> <span class="screen-reader-text"><?php esc_html_e('Remove item', 'woocommerce'); ?></span></th>
                                    <th class="product-thumbnail"> <span class="screen-reader-text"><?php esc_html_e('Thumbnail image', 'woocommerce'); ?></span></th>
                                    <th class="product-name"> <?php esc_html_e('Product', 'woocommerce'); ?></th>
                                    <th class="product-price"> <?php esc_html_e('Price', 'woocommerce'); ?></th>
                                    <th class="product-quantity"> <?php esc_html_e('Quantity', 'woocommerce'); ?></th>
                                    <th class="product-subtotal"> <?php esc_html_e('Subtotal', 'woocommerce'); ?></th>
                                </tr>
                            </thead>

                            <tbody <?php echo esc_html('id=gcw-quote-tbody'); ?>>
                                <?php
                                foreach ($quote_items as $quote_item_key => $quote_item) {

                                    $product_id         = $quote_item['product_id'];
                                    $_product           = wc_get_product($product_id);
                                    $product_name       = get_the_title($product_id);
                                    $product_permalink  = $_product->get_permalink($quote_item);
                                ?>
                                    <tr <?php echo esc_html(sprintf('id=gcw-quote-row-item-%s', $product_id)); ?>>

                                        <td class="product-remove">
                                            <?php
                                            echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                'quote_item_remove_link',
                                                sprintf(
                                                    '<a class="gcw-button-remove" class="remove" aria-label="%s" data-product_id="%s" data-product_sku="%s"></a>',
                                                    // esc_url(wc_get_cart_remove_url($quote_item_key)),
                                                    /* translators: %s is the product name */
                                                    esc_attr(sprintf(__('Remover %s do orçamento', 'gestaoclick'), wp_strip_all_tags($product_name))),
                                                    esc_attr($product_id),
                                                    esc_attr($_product->get_sku())
                                                ),
                                                $quote_item_key
                                            );
                                            ?>
                                        </td>

                                        <td class="product-thumbnail">
                                            <?php
                                            $thumbnail = apply_filters('quote_item_thumbnail', $_product->get_image(), $quote_item, $quote_item_key);

                                            if (!$product_permalink) {
                                                echo $thumbnail; // PHPCS: XSS ok.
                                            } else {
                                                printf('<a href="%s">%s</a>', esc_url($product_permalink), $thumbnail); // PHPCS: XSS ok.
                                            }
                                            ?>
                                        </td>

                                        <td class="product-name" data-title="<?php esc_attr_e('Product', 'woocommerce'); ?>">
                                            <?php
                                            if (!$product_permalink) {
                                                echo wp_kses_post($product_name . '&nbsp;');
                                            } else {
                                                echo wp_kses_post(apply_filters('quote_item_name', sprintf('<a href="%s">%s</a>', esc_url($product_permalink), $_product->get_name()), $quote_item, $quote_item_key));
                                            }

                                            do_action('quote_item_name', $quote_item, $quote_item_key);

                                            // // Meta data.
                                            // echo quote_item_data($quote_item); // PHPCS: XSS ok.

                                            // Backorder notification.
                                            if ($_product->backorders_require_notification() && $_product->is_on_backorder($quote_item['quantity'])) {
                                                echo wp_kses_post(apply_filters('quote_item_backorder_notification', '<p class="backorder_notification">' . esc_html__('Available on backorder', 'woocommerce') . '</p>', $product_id));
                                            }
                                            ?>
                                        </td>

                                        <td class="product-price" data-title="<?php esc_attr_e('Price', 'woocommerce'); ?>">
                                            <?php
                                            echo $_product->get_price(); // PHPCS: XSS ok.
                                            ?>
                                        </td>

                                        <td class="product-quantity" data-title="<?php esc_attr_e('Quantity', 'woocommerce'); ?>">
                                            <?php
                                            if ($_product->is_sold_individually()) {
                                                $min_quantity = 1;
                                                $max_quantity = 1;
                                            } else {
                                                $min_quantity = 0;
                                                $max_quantity = $_product->get_max_purchase_quantity();
                                            }

                                            $product_quantity = woocommerce_quantity_input(
                                                array(
                                                    'input_name'   => "gcw_quote_item_quantity[$product_id]",
                                                    'input_value'  => $quote_item['quantity'],
                                                    'max_value'    => $max_quantity,
                                                    'min_value'    => $min_quantity,
                                                    'product_name' => $product_name,
                                                ),
                                                $_product,
                                                false
                                            );

                                            echo apply_filters('cart_item_quantity', $product_quantity, $quote_item_key, $quote_item); // PHPCS: XSS ok.
                                            ?>
                                            <input type="hidden" name="gcw_quote_item_id[]" value="<?php echo esc_attr($product_id); ?>" />
                                        </td>

                                        <td class="product-subtotal" data-title="<?php esc_attr_e('Subtotal', 'woocommerce'); ?>">
                                            <?php
                                            echo apply_filters('cart_item_subtotal', WC()->cart->get_product_subtotal($_product, $quote_item['quantity']), $quote_item, $quote_item_key); // PHPCS: XSS ok.
                                            ?>
                                        </td>

                                    </tr>
                                <?php
                                }
                                ?>
                                <tr>
                                    <td class="actions" colspan="6">
                                        <button id="gcw-quote-update-button" type="submit" class="button" name="update_quote" value="<?php esc_attr_e('Atualizar orçamento', 'gestaoclick'); ?>"><?php esc_html_e('Atualizar orçamento', 'gestaoclick'); ?></button>
                                    </td>
                                </tr>

                            </tbody>
                        </table>
                    </form>

                </div>

                <div id="gcw-quote-totals">

                    <h2>Total no orçamento</h2>
                    <div id="gcw_quote_totals_subtotal" class="gcw_quote_totals_section">
                        <span><?php echo esc_html_e('Subtotal', 'woocommerce'); ?></span>
                        <span><?php echo wc_price($quote_subtotal); ?></span>
                    </div>
                    <div id="gcw_quote_totals_shipping" class="gcw_quote_totals_section">
                        <p><?php echo esc_html_e('Shipping', 'woocommerce'); ?></p>
                        <div id="gcw_quote_shipping_options">
                            <?php echo isset($_SESSION['shipping_options']) ? $_SESSION['shipping_options'] : ''; ?>
                        </div>
                        <div id="gcw_quote_shipping_address">
                            <?php
                                if (isset($_SESSION['shipping_address'])) {
                                    echo '<p>'.esc_html($_SESSION['shipping_address']).'</p>';
                                }
                            ?>
                        </div>
                        <form method="POST" id="gcw_quote_shipping_form">
                            <input type="text" id="shipping_postcode" name="shipping_postcode" placeholder="Digite seu CEP" 
                                <?php
                                    if (isset($_SESSION['shipping_postcode'])) {
                                        esc_attr_e(sprintf("value=%s", $_SESSION['shipping_postcode']));
                                    }
                                ?> 
                            />
                            <button id="gcw-update-shipping-button" type="button" class="button">Calcular</button>
                        </form>
                    </div>
                    <div id="gcw_quote_totals_total" class="gcw_quote_totals_section">
                        <span><?php esc_html_e('Total', 'woocommerce'); ?></span>
                        <div id="gcw_quote_total_display"></div>
                    </div>
                    <div id="gcw_quote_totals_save">
                        <a id="gcw_save_quote_button">Enviar orçamento</a>
                    </div>

                </div>

            </div>
        <?php
        else :
            echo '<p>Nenhum item encontrado neste orçamento.</p>';
        endif;

        return ob_get_clean();
    }

    /**
     * @Deprecated
     */
    public function get_quote_by_user_id($user_id)
    {
        $args = array(
            'post_type' => 'quote',
            'post_status' => 'draft',
            'author' => $user_id,
            'meta_query' => array(
                array(
                    'key' => 'status',
                    'value' => 'open',
                    'compare' => '='
                )
            )
        );
        $quotes = get_posts($args);

        if (!empty($quotes)) {
            return $quotes[0]; // Retorna a primeira (e provavelmente única) cotação aberta
        }

        return null; // Nenhuma cotação encontrada
    }

    public function update_quote_quantities()
    {
        if (isset($_POST['update_quote'])) {
            $item_ids = $_POST['gcw_quote_item_id'];
            $quantities = $_POST['gcw_quote_item_quantity'];

            $quote_items = array();

            foreach ($item_ids as $product_id) {
                if (isset($quantities[$product_id])) {
                    $quote_items[] = array(
                        'product_id' => intval($product_id),
                        'quantity' => intval($quantities[$product_id])
                    );
                }
            }

            $_SESSION['quote_items'] = $quote_items;

            // Adicione uma mensagem de sucesso
            wc_clear_notices();
            wc_add_notice(__('Orçamento atualizado com sucesso.', 'gestaoclick'), 'success');
        }
    }

    public function get_quote_subtotal($quote_items)
    {
        $subtotal = 0;
        foreach ($quote_items as $item) {
            $price = (int) wc_get_product($item['product_id'])->get_price();
            $subtotal += $price * $item['quantity'];
        }

        return $subtotal;
    }
}
