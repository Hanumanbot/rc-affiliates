<?php
/**
 * Order admin: affiliate info (classic CPT + HPOS), sidebar meta box.
 *
 * @package RC_Affiliates
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class RC_Affiliates_Order_Meta_Box
 */
class RC_Affiliates_Order_Meta_Box {

	/**
	 * Init.
	 */
	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'register_meta_box' ), 50, 2 );
	}

	/**
	 * Registra meta box na coluna lateral (igual a notas / histÃ³rico do cliente).
	 *
	 * @param string         $screen_id_or_type Screen ID (HPOS) ou post type (CPT).
	 * @param WC_Order|WP_Post|null $post_or_order     Pedido (HPOS) ou post (shop_order).
	 */
	public static function register_meta_box( $screen_id_or_type, $post_or_order = null ) {
		$order = null;
		$screen = null;

		if ( $post_or_order instanceof WC_Order ) {
			$sid = (string) $screen_id_or_type;
			if ( false === strpos( $sid, 'wc-orders' ) ) {
				return;
			}
			$order  = $post_or_order;
			$screen = $screen_id_or_type;
		} elseif ( 'shop_order' === $screen_id_or_type && $post_or_order instanceof WP_Post ) {
			$order = wc_get_order( $post_or_order );
			$screen = 'shop_order';
		}

		if ( ! $order instanceof WC_Order ) {
			return;
		}

		if ( ! (int) $order->get_meta( '_pb_affiliate_id' ) ) {
			return;
		}

		add_meta_box(
			'rc-affiliates-order-summary',
			__( 'rc affiliates', 'rc-affiliates' ),
			array( __CLASS__, 'render_meta_box' ),
			$screen,
			'side',
			'default'
		);
	}

	/**
	 * ConteÃºdo da meta box.
	 *
	 * @param WC_Order|WP_Post $post_or_order Objeto passado pelo core / WooCommerce.
	 * @param array            $box           DefiniÃ§Ã£o da meta box (nÃ£o usado).
	 */
	public static function render_meta_box( $post_or_order, $box = array() ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$order = $post_or_order instanceof WC_Order ? $post_or_order : wc_get_order( $post_or_order );
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		$aid = (int) $order->get_meta( '_pb_affiliate_id' );
		if ( ! $aid ) {
			return;
		}

		$code   = (string) $order->get_meta( '_pb_affiliate_code' );
		$via    = (string) $order->get_meta( '_pb_attribution_source' );
		$amount = $order->get_meta( '_pb_commission_amount' );

		$via_label = '' === $via || 'unknown' === $via
			? __( 'Desconhecido', 'rc-affiliates' )
			: $via;

		$aff_detail = RC_Affiliates_Admin_User_Detail::url( $aid );
		$aff_user   = get_userdata( $aid );
		$aff_name   = $aff_user ? $aff_user->display_name : sprintf(
			/* translators: %d: user ID */
			__( 'UsuÃ¡rio #%d', 'rc-affiliates' ),
			$aid
		);

		?>
		<div class="pb-aff-order-sidebar customer-history order-attribution-metabox">
			<h4><?php esc_html_e( 'Afiliado', 'rc-affiliates' ); ?></h4>
			<span class="pb-aff-order-sidebar__value">
				<a href="<?php echo esc_url( $aff_detail ); ?>"><?php echo esc_html( $aff_name ); ?></a>
				<?php if ( $aff_user ) : ?>
					<span class="description"> â€”
					<?php
					/* translators: %d: WordPress user ID */
					echo esc_html( sprintf( __( 'ID %d', 'rc-affiliates' ), $aid ) );
					?>
					</span>
				<?php endif; ?>
			</span>

			<h4><?php esc_html_e( 'CÃ³digo', 'rc-affiliates' ); ?></h4>
			<span class="pb-aff-order-sidebar__value"><code><?php echo esc_html( $code ); ?></code></span>

			<h4><?php esc_html_e( 'Origem', 'rc-affiliates' ); ?></h4>
			<span class="pb-aff-order-sidebar__value"><?php echo esc_html( $via_label ); ?></span>

			<?php if ( $amount !== '' && $amount !== null ) : ?>
				<h4><?php esc_html_e( 'ComissÃ£o', 'rc-affiliates' ); ?></h4>
				<span class="pb-aff-order-sidebar__value">
					<?php echo wp_kses_post( wc_price( (float) $amount, array( 'currency' => $order->get_currency() ) ) ); ?>
				</span>
			<?php endif; ?>
		</div>
		<?php
	}
}


