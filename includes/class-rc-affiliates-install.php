<?php
/**
 * Install: DB tables, version option.
 *
 * @package RC_Affiliates
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class RC_Affiliates_Install
 */
class RC_Affiliates_Install {

	/**
	 * VersÃ£o do esquema guardada em `RC_Affiliates_db_version`.
	 * Alinhada ao semver do plugin; incremente quando houver mudanÃ§a de tabelas ou migraÃ§Ã£o de dados.
	 * O upgrade corre quando o valor guardado Ã© diferente desta constante (nÃ£o usa sÃ³ version_compare >=),
	 * o que permite renumerar a linha de versÃµes sem ficar preso a 1.1.x.
	 */
	const DB_VERSION = '1.0.0';

	/**
	 * A loja usa armazenamento de pedidos HPOS (tabelas dedicadas) como modo ativo.
	 * Usa a mesma opÃ§Ã£o que o WooCommerce; legÃ­vel no hook de ativaÃ§Ã£o sem o container WC.
	 *
	 * @return bool
	 */
	public static function is_hpos_order_storage_enabled() {
		return get_option( 'woocommerce_custom_orders_table_enabled' ) === 'yes';
	}

	/**
	 * Atualiza tabelas e aplica migraÃ§Ãµes de dados quando `RC_Affiliates_db_version` â‰  DB_VERSION.
	 */
	public static function maybe_upgrade() {
		$current = (string) get_option( 'RC_Affiliates_db_version', '0' );
		if ( $current === self::DB_VERSION ) {
			return;
		}

		self::create_tables();
		self::run_data_migrations();

		update_option( 'RC_Affiliates_db_version', self::DB_VERSION );
		flush_rewrite_rules( false );
	}

	/**
	 * MigraÃ§Ãµes idempotentes (dados em posts/opÃ§Ãµes). Acrescente passos aqui e suba DB_VERSION.
	 */
	private static function run_data_migrations() {
		self::migrate_promo_material_post_type_slug();
	}

	/**
	 * CPT legado tinha 21 caracteres; o WordPress permite no mÃ¡ximo 20 desde 4.2.
	 */
	private static function migrate_promo_material_post_type_slug() {
		global $wpdb;
		$wpdb->update(
			$wpdb->posts,
			array( 'post_type' => 'pb_aff_promo_mat' ),
			array( 'post_type' => 'pb_aff_promo_material' ),
			array( '%s' ),
			array( '%s' )
		);
	}

	/**
	 * Activation.
	 */
	public static function activate() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			deactivate_plugins( RC_Affiliates_BASENAME );
			wp_die(
				esc_html__( 'rc affiliates requer WooCommerce.', 'rc-affiliates' ),
				'',
				array( 'back_link' => true )
			);
		}
		if ( ! defined( 'WC_PAGSEGURO_CONNECT_VERSION' ) && ! class_exists( 'RM_PagBank\Connect' ) ) {
			deactivate_plugins( RC_Affiliates_BASENAME );
			wp_die(
				esc_html__( 'rc affiliates requer PagBank Connect.', 'rc-affiliates' ),
				'',
				array( 'back_link' => true )
			);
		}
		if ( ! self::is_hpos_order_storage_enabled() ) {
			deactivate_plugins( RC_Affiliates_BASENAME );
			wp_die(
				wp_kses_post(
					__( 'rc affiliates exige que o WooCommerce utilize o <strong>armazenamento de pedidos de alto desempenho (HPOS)</strong>. Em WooCommerce &gt; ConfiguraÃ§Ãµes &gt; AvanÃ§ado &gt; Recursos, ative as tabelas personalizadas de pedidos e conclua a migraÃ§Ã£o antes de ativar este plugin.', 'rc-affiliates' )
				),
				'',
				array( 'back_link' => true )
			);
		}
		self::create_tables();
		update_option( 'RC_Affiliates_db_version', self::DB_VERSION );
		if ( ! get_option( 'RC_Affiliates_settings' ) ) {
			update_option( 'RC_Affiliates_settings', self::default_settings() );
		}
		flush_rewrite_rules();
	}

	/**
	 * Deactivation.
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}

	/**
	 * Default settings.
	 *
	 * @return array
	 */
	public static function default_settings() {
		return array(
			'referral_param'           => 'pid',
			'cookie_days'              => 30,
			'default_commission_type'  => 'percent',
			'default_commission_value' => 10,
			'attribution'              => 'last',
			'exclude_shipping'         => 'yes',
			'exclude_fees'             => 'yes',
			'payment_mode'             => 'manual',
			'split_release_days'       => 7,
			'manual_min_withdrawal'    => 0,
			'manual_retention_days'    => 0,
			'terms_page_id'            => 0,
			'affiliate_registration'   => 'auto',
			'commission_recurring'      => 'no',
			'zip1_shorten_enabled'      => 'yes',
		);
	}

	/**
	 * Create custom tables.
	 */
	public static function create_tables() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$table           = $wpdb->prefix . 'pagbank_affiliate_commissions';

		$sql = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			order_id bigint(20) unsigned NOT NULL,
			affiliate_id bigint(20) unsigned NOT NULL,
			commission_amount decimal(19,4) NOT NULL DEFAULT 0,
			commission_base decimal(19,4) NOT NULL DEFAULT 0,
			commission_type varchar(20) NOT NULL DEFAULT 'percent',
			attributed_via varchar(32) NOT NULL DEFAULT '',
			coupon_id bigint(20) unsigned NULL,
			status varchar(20) NOT NULL DEFAULT 'pending',
			currency varchar(10) NOT NULL DEFAULT '',
			created_at datetime NOT NULL,
			available_at datetime NULL,
			paid_at datetime NULL,
			payment_method varchar(20) NOT NULL DEFAULT '',
			PRIMARY KEY  (id),
			KEY order_id (order_id),
			KEY affiliate_id (affiliate_id),
			KEY status (status)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		$table_w = $wpdb->prefix . 'pagbank_affiliate_withdrawals';
		$sql2    = "CREATE TABLE {$table_w} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			affiliate_id bigint(20) unsigned NOT NULL,
			amount decimal(19,4) NOT NULL DEFAULT 0,
			status varchar(20) NOT NULL DEFAULT 'pending',
			currency varchar(10) NOT NULL DEFAULT '',
			notes text NULL,
			commission_ids_json longtext NULL,
			created_at datetime NOT NULL,
			processed_at datetime NULL,
			PRIMARY KEY  (id),
			KEY affiliate_id (affiliate_id),
			KEY status (status)
		) $charset_collate;";
		dbDelta( $sql2 );

		$table_c = $wpdb->prefix . 'pagbank_affiliate_click_log';
		$sql3    = "CREATE TABLE {$table_c} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			affiliate_id bigint(20) unsigned NOT NULL,
			hit_at datetime NOT NULL,
			via varchar(32) NOT NULL DEFAULT '',
			client_ip varchar(45) NOT NULL DEFAULT '',
			visited_url text NULL,
			referer_host varchar(190) NOT NULL DEFAULT '',
			PRIMARY KEY  (id),
			KEY affiliate_id (affiliate_id),
			KEY hit_at (hit_at),
			KEY referer_host (referer_host)
		) $charset_collate;";
		dbDelta( $sql3 );
	}

}


