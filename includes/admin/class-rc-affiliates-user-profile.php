<?php
/**
 * User profile fields (estado afiliado, comissÃ£o, PagBank, banco).
 *
 * @package RC_Affiliates
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class RC_Affiliates_User_Profile
 */
class RC_Affiliates_User_Profile {

	/**
	 * Init.
	 */
	public static function init() {
		add_action( 'show_user_profile', array( __CLASS__, 'fields' ) );
		add_action( 'edit_user_profile', array( __CLASS__, 'fields' ) );
		add_action( 'personal_options_update', array( __CLASS__, 'save' ) );
		add_action( 'edit_user_profile_update', array( __CLASS__, 'save' ) );
		add_action( 'admin_notices', array( __CLASS__, 'profile_error_notice' ) );
	}

	/**
	 * Show Account ID validation error after redirect from profile save.
	 */
	public static function profile_error_notice() {
		global $pagenow;
		if ( ! in_array( (string) $pagenow, array( 'profile.php', 'user-edit.php' ), true ) ) {
			return;
		}
		$uid = isset( $_GET['user_id'] ) ? absint( $_GET['user_id'] ) : get_current_user_id(); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! $uid ) {
			return;
		}
		$key = 'pb_aff_profile_err_' . $uid;
		$msg = get_transient( $key );
		if ( ! $msg ) {
			return;
		}
		delete_transient( $key );
		echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $msg ) . '</p></div>';
	}

	/**
	 * @param WP_User $user User.
	 */
	public static function fields( $user ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		$mode   = RC_Affiliates_Settings::get()['payment_mode'] ?? 'manual';
		$status = RC_Affiliates_Role::get_affiliate_status( $user->ID );
		if ( '' === $status ) {
			$status = RC_Affiliates_Role::STATUS_NONE;
		}
		$in_program = RC_Affiliates_Role::user_is_active_or_pending_affiliate( $user->ID );
		?>
		<h2><?php esc_html_e( 'rc affiliates', 'rc-affiliates' ); ?></h2>
		<table class="form-table">
			<tr>
				<th scope="row"><label for="pb_affiliate_status_field"><?php esc_html_e( 'Programa de afiliados', 'rc-affiliates' ); ?></label></th>
				<td>
					<select name="pb_affiliate_status_field" id="pb_affiliate_status_field">
						<option value="none" <?php selected( $status, RC_Affiliates_Role::STATUS_NONE ); ?>><?php esc_html_e( 'NÃ£o participa', 'rc-affiliates' ); ?></option>
						<option value="pending" <?php selected( $status, RC_Affiliates_Role::STATUS_PENDING ); ?>><?php esc_html_e( 'Pendente de aprovaÃ§Ã£o', 'rc-affiliates' ); ?></option>
						<option value="active" <?php selected( $status, RC_Affiliates_Role::STATUS_ACTIVE ); ?>><?php esc_html_e( 'Ativo', 'rc-affiliates' ); ?></option>
					</select>
					<p class="description"><?php esc_html_e( 'NÃ£o utiliza um papel WordPress separado: apenas esta opÃ§Ã£o e o estado (pendente/ativo).', 'rc-affiliates' ); ?></p>
				</td>
			</tr>
			<?php if ( $in_program ) : ?>
				<tr>
					<th><label for="pb_affiliate_code"><?php esc_html_e( 'CÃ³digo pÃºblico (AFF)', 'rc-affiliates' ); ?></label></th>
					<td>
						<input type="text" name="pb_affiliate_code" id="pb_affiliate_code" value="<?php echo esc_attr( get_user_meta( $user->ID, 'pb_affiliate_code', true ) ); ?>" class="regular-text" />
						<p class="description"><?php esc_html_e( 'Ãšnico no site (minÃºsculas, nÃºmeros, _ e -).', 'rc-affiliates' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'ComissÃ£o (sobrescreve padrÃ£o)', 'rc-affiliates' ); ?></th>
					<td>
						<select name="pb_affiliate_commission_type" id="pb_affiliate_commission_type">
							<option value=""><?php esc_html_e( 'â€” PadrÃ£o global â€”', 'rc-affiliates' ); ?></option>
							<option value="percent" <?php selected( get_user_meta( $user->ID, 'pb_affiliate_commission_type', true ), 'percent' ); ?>><?php esc_html_e( 'Percentual', 'rc-affiliates' ); ?></option>
							<option value="fixed" <?php selected( get_user_meta( $user->ID, 'pb_affiliate_commission_type', true ), 'fixed' ); ?>><?php esc_html_e( 'Fixo', 'rc-affiliates' ); ?></option>
						</select>
						<input type="text" name="pb_affiliate_commission_value" value="<?php echo esc_attr( get_user_meta( $user->ID, 'pb_affiliate_commission_value', true ) ); ?>" placeholder="10" />
					</td>
				</tr>
				<?php if ( 'split' === $mode ) : ?>
					<tr>
						<th><label for="pb_affiliate_pagbank_account_id"><?php esc_html_e( 'Account ID PagBank (split)', 'rc-affiliates' ); ?></label></th>
						<td>
							<input type="text" name="pb_affiliate_pagbank_account_id" id="pb_affiliate_pagbank_account_id" class="large-text" value="<?php echo esc_attr( get_user_meta( $user->ID, 'pb_affiliate_pagbank_account_id', true ) ); ?>" placeholder="ACCO_xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx" />
							<p class="description">
								<a href="<?php echo esc_url( RC_Affiliates_Account::get_pagbank_account_id_help_url() ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Qual Ã© meu Account ID?', 'rc-affiliates' ); ?></a>
							</p>
						</td>
					</tr>
				<?php else : ?>
					<tr>
						<th><?php esc_html_e( 'Dados bancÃ¡rios (pagamento manual)', 'rc-affiliates' ); ?></th>
						<td>
							<?php RC_Affiliates_Bank_Combo::render_bank_code_field( (string) get_user_meta( $user->ID, 'pb_affiliate_bank_code', true ), 'admin' ); ?>
							<p><label><?php esc_html_e( 'AgÃªncia', 'rc-affiliates' ); ?> <input type="text" name="pb_affiliate_bank_agency" value="<?php echo esc_attr( get_user_meta( $user->ID, 'pb_affiliate_bank_agency', true ) ); ?>" /></label></p>
							<p><label><?php esc_html_e( 'Conta e dÃ­gito', 'rc-affiliates' ); ?> <input type="text" name="pb_affiliate_bank_account" value="<?php echo esc_attr( get_user_meta( $user->ID, 'pb_affiliate_bank_account', true ) ); ?>" /></label></p>
							<p><label><?php esc_html_e( 'CPF ou CNPJ', 'rc-affiliates' ); ?> <input type="text" name="pb_affiliate_bank_document" id="pb_affiliate_bank_document" class="regular-text" maxlength="18" value="<?php echo esc_attr( get_user_meta( $user->ID, 'pb_affiliate_bank_document', true ) ); ?>" /></label></p>
						</td>
					</tr>
				<?php endif; ?>
			<?php endif; ?>
		</table>
		<?php
	}

	/**
	 * @param int $user_id User ID.
	 */
	public static function save( $user_id ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'update-user_' . $user_id ) ) {
			return;
		}

		if ( isset( $_POST['pb_affiliate_status_field'] ) ) {
			$st = sanitize_key( wp_unslash( $_POST['pb_affiliate_status_field'] ) );
			if ( in_array( $st, array( 'none', 'pending', 'active' ), true ) ) {
				if ( 'none' === $st ) {
					update_user_meta( $user_id, 'pb_affiliate_status', RC_Affiliates_Role::STATUS_NONE );
				} else {
					update_user_meta( $user_id, 'pb_affiliate_status', $st );
					if ( RC_Affiliates_Role::STATUS_ACTIVE === $st ) {
						RC_Affiliates_Role::ensure_affiliate_code( $user_id );
					}
				}
			}
		}

		$in_program = RC_Affiliates_Role::user_is_active_or_pending_affiliate( $user_id );
		if ( ! $in_program ) {
			return;
		}

		if ( isset( $_POST['pb_affiliate_code'] ) ) {
			$code = RC_Affiliates_Attribution::sanitize_affiliate_code( wp_unslash( $_POST['pb_affiliate_code'] ) );
			if ( strlen( $code ) >= 3 && RC_Affiliates_Attribution::is_code_available( $code, $user_id ) ) {
				update_user_meta( $user_id, 'pb_affiliate_code', $code );
			}
		}

		$commission_fields = array(
			'pb_affiliate_commission_type',
			'pb_affiliate_commission_value',
		);
		foreach ( $commission_fields as $f ) {
			if ( isset( $_POST[ $f ] ) ) {
				update_user_meta( $user_id, $f, sanitize_text_field( wp_unslash( $_POST[ $f ] ) ) );
			}
		}

		$mode = RC_Affiliates_Settings::get()['payment_mode'] ?? 'manual';
		if ( 'split' === $mode ) {
			if ( isset( $_POST['pb_affiliate_pagbank_account_id'] ) ) {
				$raw = sanitize_text_field( wp_unslash( $_POST['pb_affiliate_pagbank_account_id'] ) );
				if ( '' === $raw ) {
					update_user_meta( $user_id, 'pb_affiliate_pagbank_account_id', '' );
				} elseif ( RC_Affiliates_Split::is_valid_account_id( $raw ) ) {
					update_user_meta( $user_id, 'pb_affiliate_pagbank_account_id', $raw );
				} else {
					set_transient(
						'pb_aff_profile_err_' . $user_id,
						__( 'Account ID PagBank invÃ¡lido. Use o formato ACCO_ seguido do UUID.', 'rc-affiliates' ),
						45
					);
				}
			}
		} else {
			$bank_fields = array(
				'pb_affiliate_bank_code',
				'pb_affiliate_bank_agency',
				'pb_affiliate_bank_account',
				'pb_affiliate_bank_document',
			);
			foreach ( $bank_fields as $f ) {
				if ( ! isset( $_POST[ $f ] ) ) {
					continue;
				}
				$raw = sanitize_text_field( wp_unslash( $_POST[ $f ] ) );
				if ( 'pb_affiliate_bank_document' === $f ) {
					update_user_meta( $user_id, $f, RC_Affiliates_Account::sanitize_document_digits( $raw ) );
				} else {
					update_user_meta( $user_id, $f, $raw );
				}
			}
		}
	}
}


