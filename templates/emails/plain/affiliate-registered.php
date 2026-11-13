<?php
/**
 * Email plain: affiliate registered.
 *
 * @package RC_Affiliates
 * @var WP_User $user User.
 * @var bool    $pending Pending.
 */

defined( 'ABSPATH' ) || exit;

if ( $pending ) {
	echo esc_html(
		sprintf(
			/* translators: %s: user display name */
			__( 'OlÃ¡ %s, seu cadastro de afiliado foi recebido e aguarda aprovaÃ§Ã£o.', 'rc-affiliates' ),
			$user->display_name
		)
	);
} else {
	echo esc_html(
		sprintf(
			/* translators: %s: user display name */
			__( 'OlÃ¡ %s, seu cadastro de afiliado estÃ¡ ativo.', 'rc-affiliates' ),
			$user->display_name
		)
	);
}

