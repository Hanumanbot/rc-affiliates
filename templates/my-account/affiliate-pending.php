<?php
/**
 * Afiliado aguardando aprovaÃ§Ã£o.
 *
 * @package RC_Affiliates
 */

defined( 'ABSPATH' ) || exit;

if ( function_exists( 'wc_print_notices' ) ) {
	wc_print_notices();
}
?>
<p><?php esc_html_e( 'Seu pedido para participar do programa de afiliados estÃ¡ aguardando aprovaÃ§Ã£o da loja. VocÃª receberÃ¡ um e-mail quando for analisado.', 'rc-affiliates' ); ?></p>

