<?php
/**
 * Main plugin singleton.
 *
 * @package RC_Affiliates
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class RC_Affiliates
 */
final class RC_Affiliates {

	/**
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * @return self
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * RC_Affiliates constructor.
	 */
	private function __construct() {
		RC_Affiliates_Role::init();
		RC_Affiliates_Tracking::init();
		RC_Affiliates_Order::init();
		RC_Affiliates_Commission::init();
		if ( is_admin() ) {
			RC_Affiliates_Category_Commission::init();
		}
		RC_Affiliates_Coupon::init();
		RC_Affiliates_Split::init();
		RC_Affiliates_Promotional_Materials::init();
		RC_Affiliates_Account::init();
		RC_Affiliates_Bank_Combo::init();
		RC_Affiliates_Public::init();

		if ( is_admin() ) {
			RC_Affiliates_Admin_Materials::init();
			RC_Affiliates_Admin_Settings::init();
			RC_Affiliates_Admin::init();
			RC_Affiliates_User_Profile::init();
			RC_Affiliates_Order_Meta_Box::init();
		}

		add_action( 'woocommerce_init', array( $this, 'woocommerce_init' ) );
	}

	/**
	 * After WooCommerce loads.
	 */
	public function woocommerce_init() {
		add_action( 'admin_notices', array( $this, 'notice_no_pagbank_payment' ) );
	}

	/**
	 * Admin notice when no PagBank method available (storefront still works; split may be limited).
	 */
	public function notice_no_pagbank_payment() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		if ( RC_Affiliates_Dependencies::has_pagbank_payment_available() ) {
			return;
		}
		echo '<div class="notice notice-warning"><p>' . esc_html__( 'rc affiliates: nenhum mÃ©todo de pagamento PagBank estÃ¡ disponÃ­vel no checkout. Ative pelo menos um mÃ©todo no PagBank Connect.', 'rc-affiliates' ) . '</p></div>';
	}
}


