<?php

class GCW_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	public function __construct($plugin_name, $version) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/gestaoclick-public.css', array(), $this->version, 'all');
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'assets/js/gestaoclick-public.js', array('jquery'), $this->version, false);
	}

    public function shortcode_orcamento() {
        return '
			<form method="post">

				<h2>' . __('Institution', 'gestaoclick') . '</h2>
				<section id="gcw-section-institution">
					<div class="gcw-field-wrap">
						<label>' . __('Institution name', 'gestaoclick') . '</label>
						<input type="text" name="institution-name">
					</div>
					<div class="gcw-field-wrap">
						<label>' . __('Register number', 'gestaoclick') . '</label>
						<input type="text" name="institution-cnpj">
					</div>
				</section>

				<h2>' . __('Responsable', 'gestaoclick') . '</h2>
				<section id="gcw-section-responsable">
					<div class="gcw-field-wrap">
						<label>' . __('Name', 'gestaoclick') . '</label>
						<input type="text" name="responsable-name">
					</div>
					<div class="gcw-field-wrap">
						<label>' . __('Email', 'gestaoclick') . '</label>
						<input type="text" name="responsable-email">
					</div>
					<div class="gcw-field-wrap">
						<label>' . __('Phone number', 'gestaoclick') . '</label>
						<input type="text" name="responsable-phone">
					</div>
					<div class="gcw-field-wrap">
						<label>' . __('Position', 'gestaoclick') . '</label>
						<input type="text" name="responsable-position">
					</div>
				</section>

				<h2>' . __('Quote', 'gestaoclick') . '</h2>
				<section id="gcw-section-quote">
				</section>
				<a id="gcw-quote-add-item">' . __('Add', 'gestaoclick') . '</a>
				
			</form>
		';
    }
}