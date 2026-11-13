<?php
/**
 * Assets + markup for bank code combobox (api-bancos JSON).
 *
 * @package RC_Affiliates
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class RC_Affiliates_Bank_Combo
 */
class RC_Affiliates_Bank_Combo {

	/**
	 * Lista oficial (JSON) â€” api-bancos.
	 */
	const BANK_JSON_URL = 'https://cdn.jsdelivr.net/gh/r-martins/api-bancos@master/json/all.json';

	/**
	 * Init hooks.
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_front' ), 25 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin' ), 25 );
	}

	/**
	 * @return string
	 */
	public static function get_bank_json_url() {
		/**
		 * URL do JSON de bancos para o combobox.
		 *
		 * @param string $url Default CDN api-bancos.
		 */
		return apply_filters( 'RC_Affiliates_bank_json_url', self::BANK_JSON_URL );
	}

	/**
	 * Front: Minha conta â†’ editar conta, modo manual, afiliado ativo.
	 */
	public static function enqueue_front() {
		if ( ! function_exists( 'is_account_page' ) || ! is_account_page() ) {
			return;
		}
		if ( function_exists( 'is_cart' ) && is_cart() ) {
			return;
		}
		if ( function_exists( 'is_checkout' ) && is_checkout() ) {
			return;
		}
		if ( ! is_user_logged_in() ) {
			return;
		}
		if ( 'manual' !== ( RC_Affiliates_Settings::get()['payment_mode'] ?? 'manual' ) ) {
			return;
		}
		if ( ! RC_Affiliates_Account::user_can_edit_payment_fields( get_current_user_id() ) ) {
			return;
		}
		// NÃ£o exigir is_wc_endpoint_url( 'edit-account' ): em alguns temas/permalinks falha e o JS nÃ£o carrega.
		self::enqueue_assets();
	}

	/**
	 * Admin: editar usuÃ¡rio (dados bancÃ¡rios modo manual).
	 *
	 * @param string $hook_suffix Current admin page.
	 */
	public static function enqueue_admin( $hook_suffix ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		global $pagenow;
		// $hook_suffix varia entre versÃµes WP; $pagenow Ã© fiÃ¡vel.
		if ( ! in_array( (string) $pagenow, array( 'user-edit.php', 'profile.php' ), true ) ) {
			return;
		}
		if ( 'manual' !== ( RC_Affiliates_Settings::get()['payment_mode'] ?? 'manual' ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		self::enqueue_assets();
	}

	/**
	 * Register scripts/styles.
	 */
	private static function enqueue_assets() {
		wp_enqueue_style(
			'pb-aff-bank-combo',
			RC_Affiliates_URL . 'assets/css/bank-combobox.css',
			array(),
			RC_Affiliates_VERSION
		);
		wp_enqueue_script(
			'pb-aff-bank-combo',
			RC_Affiliates_URL . 'assets/js/bank-combobox.js',
			array(),
			RC_Affiliates_VERSION,
			true
		);
		wp_localize_script(
			'pb-aff-bank-combo',
			'pbAffBankCombo',
			array(
				'url'     => esc_url_raw( self::get_bank_json_url() ),
				'loading' => __( 'Carregando bancosâ€¦', 'rc-affiliates' ),
				'error'   => __( 'NÃ£o foi possÃ­vel carregar a lista de bancos.', 'rc-affiliates' ),
				'empty'   => __( 'Nenhum banco encontrado.', 'rc-affiliates' ),
			)
		);
		wp_enqueue_script(
			'pb-aff-cpf-cnpj-mask',
			RC_Affiliates_URL . 'assets/js/cpf-cnpj-mask.js',
			array(),
			RC_Affiliates_VERSION,
			true
		);
	}

	/**
	 * Markup do campo cÃ³digo do banco (hidden + busca).
	 *
	 * @param string $stored_code Valor salvo (apenas cÃ³digo).
	 * @param string $context     front|admin.
	 */
	public static function render_bank_code_field( $stored_code, $context = 'front' ) {
		$stored_code = (string) $stored_code;
		$is_front    = 'front' === $context;
		$label_for   = 'pb_affiliate_bank_code_display';
		$input_class = $is_front
			? 'woocommerce-Input woocommerce-Input--text input-text pb-aff-bank-search'
			: 'regular-text pb-aff-bank-search';

		if ( $is_front ) {
			?>
			<div class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide pb-aff-bank-form-row">
				<label for="<?php echo esc_attr( $label_for ); ?>"><?php esc_html_e( 'NÃºmero do banco', 'rc-affiliates' ); ?></label>
				<div class="pb-aff-bank-field" data-pb-bank-combo>
					<input type="hidden" name="pb_affiliate_bank_code" id="pb_affiliate_bank_code" value="<?php echo esc_attr( $stored_code ); ?>" />
					<input type="text" id="<?php echo esc_attr( $label_for ); ?>" class="<?php echo esc_attr( $input_class ); ?>" value="" autocomplete="off" placeholder="<?php esc_attr_e( 'Buscar por cÃ³digo ou nomeâ€¦', 'rc-affiliates' ); ?>" />
					<ul class="pb-aff-bank-dropdown" hidden role="listbox"></ul>
				</div>
			</div>
			<?php
			return;
		}

		?>
		<div class="pb-aff-bank-admin-row">
			<label for="<?php echo esc_attr( $label_for ); ?>"><?php esc_html_e( 'NÃºmero do banco', 'rc-affiliates' ); ?></label><br />
			<div class="pb-aff-bank-field" data-pb-bank-combo>
				<input type="hidden" name="pb_affiliate_bank_code" id="pb_affiliate_bank_code" value="<?php echo esc_attr( $stored_code ); ?>" />
				<input type="text" id="<?php echo esc_attr( $label_for ); ?>" class="<?php echo esc_attr( $input_class ); ?>" value="" autocomplete="off" placeholder="<?php esc_attr_e( 'Buscar por cÃ³digo ou nomeâ€¦', 'rc-affiliates' ); ?>" />
				<ul class="pb-aff-bank-dropdown" hidden role="listbox"></ul>
			</div>
		</div>
		<?php
	}
}

