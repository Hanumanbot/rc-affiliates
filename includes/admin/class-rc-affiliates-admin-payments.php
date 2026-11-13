<?php
/**
 * Admin: repasse manual (saque agregado) + pedidos de saque. Split PagBank nÃ£o passa por esta fila.
 *
 * @package RC_Affiliates
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class RC_Affiliates_Admin_Payments
 */
class RC_Affiliates_Admin_Payments {

	/**
	 * Marcar saque como pago (com comprovante).
	 */
	public static function handle_post() {
		if ( ! isset( $_GET['page'] ) || 'rc-affiliates-payments' !== $_GET['page'] ) {
			return;
		}
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		if ( ! isset( $_POST['pb_aff_payment_action'], $_POST['_wpnonce'], $_POST['pb_aff_withdrawal_id'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'pb_aff_payment_action' ) ) {
			return;
		}
		if ( 'mark_paid' !== sanitize_key( wp_unslash( $_POST['pb_aff_payment_action'] ) ) ) {
			return;
		}
		$id    = absint( $_POST['pb_aff_withdrawal_id'] );
		$proof = isset( $_POST['pb_aff_payment_proof'] )
			? sanitize_textarea_field( wp_unslash( $_POST['pb_aff_payment_proof'] ) )
			: '';
		$res   = RC_Affiliates_Withdrawal::mark_paid( $id, $proof );
		$tab   = isset( $_POST['pb_aff_tab'] ) ? sanitize_key( wp_unslash( $_POST['pb_aff_tab'] ) ) : 'pending';
		if ( ! in_array( $tab, array( 'pending', 'paid' ), true ) ) {
			$tab = 'pending';
		}
		if ( is_wp_error( $res ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=rc-affiliates-payments&tab=' . $tab . '&pb_aff_err=' . rawurlencode( $res->get_error_code() ) ) );
		} else {
			wp_safe_redirect( admin_url( 'admin.php?page=rc-affiliates-payments&tab=paid&pb_aff_notice=paid' ) );
		}
		exit;
	}

	/**
	 * Mensagem para cÃ³digo de erro da query string.
	 *
	 * @param string $code Code.
	 * @return string
	 */
	private static function error_message_for_code( $code ) {
		switch ( $code ) {
			case 'not_found':
				return __( 'Registro nÃ£o encontrado.', 'rc-affiliates' );
			case 'invalid':
				return __( 'OperaÃ§Ã£o invÃ¡lida para este registro.', 'rc-affiliates' );
			case 'proof_required':
				return __( 'Preencha o comprovante ou referÃªncia do pagamento.', 'rc-affiliates' );
			case 'legacy':
				return __( 'Este pedido de saque Ã© antigo e nÃ£o associa comissÃµes. PeÃ§a ao afiliado para solicitar um novo saque ou liquide em Afiliados â†’ Pedidos.', 'rc-affiliates' );
			case 'split':
				return __( 'ComissÃ£o em modo split nÃ£o pode ser liquidada por este fluxo.', 'rc-affiliates' );
			case 'commission':
				return __( 'Uma das comissÃµes deste saque jÃ¡ nÃ£o estÃ¡ pendente. Verifique Afiliados â†’ Pedidos.', 'rc-affiliates' );
			case 'amount':
				return __( 'A soma das comissÃµes nÃ£o coincide com o valor do saque.', 'rc-affiliates' );
			default:
				return __( 'NÃ£o foi possÃ­vel concluir a operaÃ§Ã£o.', 'rc-affiliates' );
		}
	}

	/**
	 * Comprovante em linha (aba Pagos): prÃ©-visualizaÃ§Ã£o curta com expansÃ£o.
	 *
	 * @param string $notes_raw Texto do comprovante.
	 */
	private static function render_paid_proof_cell( $notes_raw ) {
		$notes = (string) $notes_raw;
		if ( '' === $notes ) {
			echo 'â€”';
			return;
		}
		$cut = 120;
		$len = function_exists( 'mb_strlen' ) ? mb_strlen( $notes ) : strlen( $notes );
		if ( $len <= $cut ) {
			printf(
				'<div class="pb-aff-proof-cell" style="max-width:24rem;white-space:pre-wrap;">%s</div>',
				esc_html( $notes )
			);
			return;
		}
		$preview = function_exists( 'mb_substr' ) ? mb_substr( $notes, 0, $cut ) : substr( $notes, 0, $cut );
		echo '<details class="pb-aff-proof-expand" style="max-width:24rem;">';
		printf(
			'<summary style="cursor:pointer;list-style-position:outside;">%sâ€¦</summary>',
			esc_html( $preview )
		);
		printf(
			'<div style="margin-top:0.5em;white-space:pre-wrap;border-left:3px solid #c3c4c7;padding-left:8px;">%s</div>',
			esc_html( $notes )
		);
		echo '</details>';
	}

	/**
	 * Render da pÃ¡gina.
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Payments list: GET is only for tab and flash notices; POST uses nonce in handle_post().
		global $wpdb;
		$table   = $wpdb->prefix . 'pagbank_affiliate_withdrawals';
		$mode    = RC_Affiliates_Settings::get()['payment_mode'] ?? 'manual';
		$now_gmt = gmdate( 'Y-m-d H:i:s' );

		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'pending';
		if ( ! in_array( $tab, array( 'pending', 'paid' ), true ) ) {
			$tab = 'pending';
		}

		$status = 'pending' === $tab ? 'pending' : 'paid';
		$rows   = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM %i WHERE status = %s ORDER BY id DESC LIMIT 200',
				$table,
				$status
			),
			ARRAY_A
		);

		if ( ! empty( $_GET['pb_aff_notice'] ) && 'paid' === sanitize_key( wp_unslash( $_GET['pb_aff_notice'] ) ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Pagamento registrado como concluÃ­do.', 'rc-affiliates' ) . '</p></div>';
		}
		if ( ! empty( $_GET['pb_aff_err'] ) ) {
			$code = sanitize_key( wp_unslash( $_GET['pb_aff_err'] ) );
			$msg  = self::error_message_for_code( $code );
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $msg ) . '</p></div>';
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$aggregated = 'pending' === $tab
			? RC_Affiliates_Reports::get_admin_manual_pending_by_affiliate_display( $now_gmt )
			: array();
		$orders_url = admin_url( 'admin.php?page=rc-affiliates' );
		?>
		<div class="wrap woocommerce">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Pagamentos', 'rc-affiliates' ); ?></h1>
			<hr class="wp-header-end" />

			<?php if ( 'split' === $mode ) : ?>
				<div class="notice notice-info">
					<p>
						<?php esc_html_e( 'A loja estÃ¡ em modo split (PagBank): o repasse das comissÃµes ocorre automaticamente conforme custÃ³dia e regras do conector. Esta tela sÃ³ trata de pagamentos manuais e pedidos de saque; comissÃµes split nÃ£o entram na fila â€œpendente de transferÃªnciaâ€ abaixo.', 'rc-affiliates' ); ?>
					</p>
				</div>
			<?php else : ?>
				<div class="notice notice-info" style="margin-top:1em;">
					<p>
						<?php esc_html_e( 'Modo manual: o afiliado solicita saque na Ã¡rea â€œAfiliadoâ€; vocÃª transfere o valor e marca o pedido como pago informando um comprovante.', 'rc-affiliates' ); ?>
					</p>
				</div>
			<?php endif; ?>

			<h2 class="nav-tab-wrapper">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=rc-affiliates-payments&tab=pending' ) ); ?>" class="nav-tab <?php echo 'pending' === $tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Pendentes', 'rc-affiliates' ); ?></a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=rc-affiliates-payments&tab=paid' ) ); ?>" class="nav-tab <?php echo 'paid' === $tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Pagos', 'rc-affiliates' ); ?></a>
			</h2>
			<p class="description">
				<?php esc_html_e( 'Pedidos de saque consolidam vÃ¡rias comissÃµes manuais em uma Ãºnica linha para repasse. Saldo ainda nÃ£o solicitado aparece agrupado por afiliado.', 'rc-affiliates' ); ?>
				<?php
				echo ' ';
				echo wp_kses_post(
					sprintf(
						/* translators: %s: URL Afiliados > Pedidos */
						__( 'O detalhe por venda continua em %s.', 'rc-affiliates' ),
						'<a href="' . esc_url( $orders_url ) . '">' . esc_html__( 'Afiliados â†’ Pedidos', 'rc-affiliates' ) . '</a>'
					)
				);
				?>
			</p>

			<?php if ( 'pending' === $tab && 'manual' === $mode ) : ?>
				<h2 style="margin-top:1.5em"><?php esc_html_e( 'Saldo manual ainda nÃ£o pedido em saque', 'rc-affiliates' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Valores jÃ¡ liberados (apÃ³s retenÃ§Ã£o) que o afiliado ainda nÃ£o incluiu em um pedido de saque. Quando ele solicitar o saque, as linhas somem daqui e passam para a tabela de pedidos abaixo.', 'rc-affiliates' ); ?></p>
				<table class="wp-list-table widefat fixed striped table-view-list" style="margin-top:0.5em;">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Afiliado', 'rc-affiliates' ); ?></th>
							<th><?php esc_html_e( 'ComissÃµes', 'rc-affiliates' ); ?></th>
							<th><?php esc_html_e( 'Total', 'rc-affiliates' ); ?></th>
							<th><?php esc_html_e( 'Reservado em saque', 'rc-affiliates' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $aggregated ) ) : ?>
							<tr><td colspan="4"><?php esc_html_e( 'Nenhum saldo manual livre aguardando solicitaÃ§Ã£o de saque.', 'rc-affiliates' ); ?></td></tr>
						<?php else : ?>
							<?php foreach ( $aggregated as $arow ) : ?>
								<?php
								$aff_aid   = (int) $arow['affiliate_id'];
								$u_a       = $aff_aid ? get_userdata( $aff_aid ) : false;
								$curr      = get_woocommerce_currency();
								$lock_t    = isset( $arow['locked_in_withdrawal_total'] ) ? (float) $arow['locked_in_withdrawal_total'] : 0.0;
								$lock_c    = isset( $arow['locked_in_withdrawal_count'] ) ? (int) $arow['locked_in_withdrawal_count'] : 0;
								$lock_cell = 'â€”';
								if ( $lock_t > 0.009 || $lock_c > 0 ) {
									$lock_cell = wp_kses_post(
										wc_price( $lock_t, array( 'currency' => $curr ) )
										. ' Â· ' .
										sprintf(
											/* translators: %d: commission count */
											_n( '%d comissÃ£o', '%d comissÃµes', $lock_c, 'rc-affiliates' ),
											$lock_c
										)
									);
								}
								?>
								<tr>
									<td>
										<?php if ( $u_a && $aff_aid ) : ?>
											<a href="<?php echo esc_url( RC_Affiliates_Admin_User_Detail::url( $aff_aid ) ); ?>"><?php echo esc_html( $u_a->display_name ); ?></a>
											<br /><span class="description"><?php echo esc_html( $u_a->user_email ); ?></span>
										<?php else : ?>
											<?php echo esc_html( $aff_aid ? '#' . $aff_aid : 'â€”' ); ?>
										<?php endif; ?>
									</td>
									<td><?php echo esc_html( (string) (int) $arow['pending_count'] ); ?></td>
									<td><?php echo wp_kses_post( wc_price( (float) $arow['pending_total'], array( 'currency' => $curr ) ) ); ?></td>
									<td><?php echo wp_kses_post( $lock_cell ); ?></td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			<?php elseif ( 'pending' === $tab && 'split' === $mode ) : ?>
				<p class="description" style="margin-top:1em;">
					<?php esc_html_e( 'NÃ£o hÃ¡ saldo manual neste modo; use a tabela de pedidos de saque apenas se existir fluxo misto ou registros antigos.', 'rc-affiliates' ); ?>
				</p>
			<?php endif; ?>

			<h2 style="margin-top:2em"><?php esc_html_e( 'Pedidos de saque', 'rc-affiliates' ); ?></h2>
			<p class="description">
				<?php if ( 'paid' === $tab ) : ?>
					<?php esc_html_e( 'HistÃ³rico de saques marcados como pagos (comprovante na Ãºltima coluna, quando informado).', 'rc-affiliates' ); ?>
				<?php else : ?>
					<?php esc_html_e( 'Transfira o valor ao afiliado, preencha o comprovante e marque como pago â€” todas as comissÃµes do pedido serÃ£o liquidadas de uma vez.', 'rc-affiliates' ); ?>
				<?php endif; ?>
			</p>

			<table class="wp-list-table widefat fixed striped table-view-list" style="margin-top:1em;">
				<thead>
					<tr>
						<th><?php esc_html_e( 'ID', 'rc-affiliates' ); ?></th>
						<th><?php esc_html_e( 'Afiliado', 'rc-affiliates' ); ?></th>
						<th><?php esc_html_e( 'Dados para repasse', 'rc-affiliates' ); ?></th>
						<th><?php esc_html_e( 'Valor', 'rc-affiliates' ); ?></th>
						<th><?php esc_html_e( 'ComissÃµes', 'rc-affiliates' ); ?></th>
						<th><?php esc_html_e( 'Estado', 'rc-affiliates' ); ?></th>
						<th><?php esc_html_e( 'Pedido em', 'rc-affiliates' ); ?></th>
						<th><?php esc_html_e( 'Pago em', 'rc-affiliates' ); ?></th>
						<th style="min-width:14rem;"><?php esc_html_e( 'Comprovante / aÃ§Ã£o', 'rc-affiliates' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $rows ) ) : ?>
						<tr><td colspan="9"><?php esc_html_e( 'Nenhum registro.', 'rc-affiliates' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $rows as $row ) : ?>
							<?php
							$aff_id   = (int) $row['affiliate_id'];
							$u        = get_userdata( $aff_id );
							$label    = $u ? $u->display_name . ' (' . $u->user_email . ')' : '#' . $aff_id;
							$cc       = RC_Affiliates_Withdrawal::count_commissions_in_withdrawal_row( $row );
							$bank_lines = RC_Affiliates_Withdrawal::get_bank_detail_lines( $aff_id );
							$bank_block = '';
							if ( ! empty( $bank_lines ) ) {
								$bank_block = '<ul style="margin:0.35em 0 0 1.25em;"><li>' . implode( '</li><li>', array_map( 'esc_html', $bank_lines ) ) . '</li></ul>';
							} else {
								$bank_block = '<span class="description">' . esc_html__( 'Sem dados bancÃ¡rios no perfil. Modo split: ver Account ID em Detalhes da conta.', 'rc-affiliates' ) . '</span>';
							}
							$acc_id_pb = (string) get_user_meta( $aff_id, 'pb_affiliate_pagbank_account_id', true );
							if ( 'split' === $mode && $acc_id_pb !== '' ) {
								$bank_block .= '<p style="margin:0.5em 0 0;"><strong>PagBank Account ID:</strong> <code>' . esc_html( $acc_id_pb ) . '</code></p>';
							}
							?>
							<tr>
								<td><?php echo (int) $row['id']; ?></td>
								<td>
									<?php if ( $u ) : ?>
										<a href="<?php echo esc_url( get_edit_user_link( $aff_id ) ); ?>"><?php echo esc_html( $label ); ?></a>
									<?php else : ?>
										<?php echo esc_html( $label ); ?>
									<?php endif; ?>
								</td>
								<td>
									<details>
										<summary style="cursor:pointer;"><?php esc_html_e( 'Ver dados', 'rc-affiliates' ); ?></summary>
										<div style="margin-top:0.5em;"><?php echo wp_kses_post( $bank_block ); ?></div>
									</details>
								</td>
								<td><?php echo wp_kses_post( wc_price( (float) $row['amount'], array( 'currency' => $row['currency'] ? $row['currency'] : get_woocommerce_currency() ) ) ); ?></td>
								<td><?php echo esc_html( (string) (int) $cc ); ?></td>
								<td><?php echo esc_html( $row['status'] ); ?></td>
								<td><?php echo esc_html( $row['created_at'] ? mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $row['created_at'] ) : 'â€”' ); ?></td>
								<td><?php echo ! empty( $row['processed_at'] ) ? esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $row['processed_at'] ) ) : 'â€”'; ?></td>
								<td>
									<?php if ( 'pending' === $row['status'] ) : ?>
										<form method="post" class="pb-aff-withdrawal-pay-form" onsubmit="return confirm(<?php echo wp_json_encode( __( 'Confirmar que o valor foi transferido ao afiliado?', 'rc-affiliates' ) ); ?>);">
											<?php wp_nonce_field( 'pb_aff_payment_action' ); ?>
											<input type="hidden" name="pb_aff_tab" value="<?php echo esc_attr( $tab ); ?>" />
											<input type="hidden" name="pb_aff_withdrawal_id" value="<?php echo (int) $row['id']; ?>" />
											<input type="hidden" name="pb_aff_payment_action" value="mark_paid" />
											<p style="margin:0 0 0.35em;">
												<label>
													<span class="screen-reader-text"><?php esc_html_e( 'Comprovante', 'rc-affiliates' ); ?></span>
													<textarea name="pb_aff_payment_proof" rows="2" class="large-text" required placeholder="<?php echo esc_attr__( 'Ex.: PIX, Ãºltimos dÃ­gitos, ID da transaÃ§Ã£o, arquivoâ€¦', 'rc-affiliates' ); ?>"></textarea>
												</label>
											</p>
											<button type="submit" class="button button-primary"><?php esc_html_e( 'Marcar como pago', 'rc-affiliates' ); ?></button>
										</form>
									<?php else : ?>
										<?php self::render_paid_proof_cell( isset( $row['notes'] ) ? (string) $row['notes'] : '' ); ?>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}
}

