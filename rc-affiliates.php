<?php
/**
 * Plugin Name:       rc affiliates
 * Plugin URI:        https://pbintegracoes.com/
 * Description:       Programa de afiliados para WooCommerce com integraÃ§Ã£o PagBank Connect. Exige PagBank Connect ativo com pelo menos um mÃ©todo de pagamento disponÃ­vel.
 * Version:           1.0.0
 * Requires at least: 6.2
 * Requires PHP:      7.4
 * Author:            Ricardo Martins
 * License:           GPL-3.0
 * License URI:       https://opensource.org/license/gpl-3-0
 * Requires Plugins:  woocommerce, pagbank-connect
 * Text Domain:       rc-affiliates
 * Domain Path:       /languages
 *
 * @package RC_Affiliates
 */

defined( 'ABSPATH' ) || exit;

define( 'RC_Affiliates_VERSION', '1.0.0' );
define( 'RC_Affiliates_FILE', __FILE__ );
define( 'RC_Affiliates_PATH', plugin_dir_path( __FILE__ ) );
define( 'RC_Affiliates_URL', plugin_dir_url( __FILE__ ) );
define( 'RC_Affiliates_BASENAME', plugin_basename( __FILE__ ) );

require_once RC_Affiliates_PATH . 'includes/class-rc-affiliates-install.php';
require_once RC_Affiliates_PATH . 'includes/class-rc-affiliates-autoload.php';

register_activation_hook( __FILE__, array( 'RC_Affiliates_Install', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'RC_Affiliates_Install', 'deactivate' ) );

add_action( 'plugins_loaded', 'RC_Affiliates_boot', 20 );

/**
 * Bootstrap plugin.
 */
function RC_Affiliates_boot() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'RC_Affiliates_notice_woocommerce' );
		return;
	}
	if ( ! defined( 'WC_PAGSEGURO_CONNECT_VERSION' ) && ! class_exists( 'RM_PagBank\Connect' ) ) {
		add_action( 'admin_notices', 'RC_Affiliates_notice_pagbank' );
		return;
	}
	if ( ! RC_Affiliates_Install::is_hpos_order_storage_enabled() ) {
		add_action( 'admin_notices', 'RC_Affiliates_notice_hpos' );
		return;
	}

	RC_Affiliates_Install::maybe_upgrade();
	RC_Affiliates::instance();
	add_filter( 'plugin_action_links_' . RC_Affiliates_BASENAME, 'RC_Affiliates_plugin_action_links' );
}

/**
 * Plugin list row: settings and documentation (same pattern as PagBank Connect).
 *
 * @param array<string> $links Existing action links.
 * @return array<string>
 */
function RC_Affiliates_plugin_action_links( $links ) {
	$plugin_links   = array();
	$plugin_links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=rc-affiliates-settings' ) ) . '">' . esc_html__( 'ConfiguraÃ§Ãµes', 'rc-affiliates' ) . '</a>';
	$plugin_links[] = '<a href="' . esc_url( 'https://ajuda.pbintegracoes.com/hc/pt-br/sections/44721017341069-PB-Afiliados' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'DocumentaÃ§Ã£o', 'rc-affiliates' ) . '</a>';
	return array_merge( $plugin_links, $links );
}

/**
 * Admin notice: WooCommerce missing.
 */
function RC_Affiliates_notice_woocommerce() {
	echo '<div class="notice notice-error"><p>' . esc_html__( 'rc affiliates requer o WooCommerce instalado e ativo.', 'rc-affiliates' ) . '</p></div>';
}

/**
 * Admin notice: PagBank Connect missing.
 */
function RC_Affiliates_notice_pagbank() {
	echo '<div class="notice notice-error"><p>' . esc_html__( 'rc affiliates requer o plugin PagBank Connect instalado e ativo.', 'rc-affiliates' ) . '</p></div>';
}

/**
 * Admin notice: HPOS (custom order tables) must be the active order storage.
 */
function RC_Affiliates_notice_hpos() {
	echo '<div class="notice notice-error"><p>';
	echo wp_kses_post(
		__( 'rc affiliates exige o armazenamento de pedidos de alto desempenho (HPOS) ativo. Em WooCommerce &gt; ConfiguraÃ§Ãµes &gt; AvanÃ§ado &gt; Recursos, ative as tabelas personalizadas de pedidos.', 'rc-affiliates' )
	);
	echo '</p></div>';
}


