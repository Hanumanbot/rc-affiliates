<?php
/**
 * Saques manuais: solicitaÃ§Ã£o pelo afiliado, liquidaÃ§Ã£o no admin (comissÃµes congeladas no pedido).
 *
 * @package RC_Affiliates
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class RC_Affiliates_Withdrawal
 */
class RC_Affiliates_Withdrawal {

	/**
	 * Compara valores monetÃ¡rios com tolerÃ¢ncia.
	 *
	 * @param float $a A.
	 * @param float $b B.
	 * @return bool
	 */
	public static function amounts_match( $a, $b ) {
		return abs( (float) $a - (float) $b ) < 0.009;
	}

	/**
	 * IDs de comissÃ£o jÃ¡ reservadas em pedidos de saque ainda pendentes (snapshot no JSON).
	 *
	 * @param int $user_id Affiliate user ID.
	 * @return array<int, int>
	 */
	public static function get_pending_withdrawal_locked_commission_ids( $user_id ) {
		global $wpdb;
		$user_id = (int) $user_id;
		if ( $user_id <= 0 ) {
			return array();
		}
		$table = $wpdb->prefix . 'pagbank_affiliate_withdrawals';
		$jsons = $wpdb->get_col(
			$wpdb->prepare(
				'SELECT commission_ids_json FROM %i WHERE affiliate_id = %d AND status = %s',
				$table,
				$user_id,
				'pending'
			)
		);
		$ids = array();
		foreach ( (array) $jsons as $raw ) {
			if ( empty( $raw ) || ! is_string( $raw ) ) {
				continue;
			}
			$decoded = json_decode( $raw, true );
			if ( ! is_array( $decoded ) ) {
				continue;
			}
			foreach ( $decoded as $cid ) {
				$cid = absint( $cid );
				if ( $cid > 0 ) {
					$ids[ $cid ] = $cid;
				}
			}
		}
		return array_values( $ids );
	}

	/**
	 * ClÃ¡usula SQL e argumentos para excluir IDs (seguro sÃ³ com inteiros).
	 *
	 * @param array<int, int> $ids IDs.
	 * @return array{0:string, 1:array<int,int>} fragmento apÃ³s WHERE base, args vazios se sem exclusÃ£o.
	 */
	private static function sql_exclude_commission_ids( $ids ) {
		$ids = array_values( array_unique( array_filter( array_map( 'absint', (array) $ids ) ) ) );
		if ( empty( $ids ) ) {
			return array( '', array() );
		}
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		return array( " AND id NOT IN ({$placeholders}) ", $ids );
	}

	/**
	 * Saldo disponÃ­vel para saque (comissÃµes manuais pendentes, fora retenÃ§Ã£o e fora saques pendentes).
	 *
	 * @param int $user_id Affiliate user ID.
	 * @return float
	 */
	public static function get_available_balance( $user_id ) {
		global $wpdb;
		$table   = $wpdb->prefix . 'pagbank_affiliate_commissions';
		$now_gmt = gmdate( 'Y-m-d H:i:s' );
		$locked  = self::get_pending_withdrawal_locked_commission_ids( $user_id );
		list( $excl_sql, $excl_args ) = self::sql_exclude_commission_ids( $locked );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- $excl_sql is built from absint IDs as %d placeholders only.
		$sum = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COALESCE(SUM(commission_amount), 0) FROM %i
				WHERE affiliate_id = %d AND status = %s
				AND ( payment_method IS NULL OR payment_method = \'\' OR payment_method = %s )
				AND ( available_at IS NULL OR available_at <= %s )
				' . $excl_sql,
				...array_merge( array( $table, $user_id, 'pending', 'manual', $now_gmt ), $excl_args )
			)
		);
		return (float) $sum;
	}

	/**
	 * Afiliado tem pedido de saque aguardando processamento.
	 *
	 * @param int $user_id User ID.
	 * @return bool
	 */
	public static function has_pending_request( $user_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'pagbank_affiliate_withdrawals';
		$n = (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM %i WHERE affiliate_id = %d AND status = %s',
				$table,
				$user_id,
				'pending'
			)
		);
		return $n > 0;
	}

	/**
	 * ComissÃµes manuais a liquidar (FIFO), jÃ¡ liberadas por available_at.
	 *
	 * @param int $affiliate_id Affiliate.
	 * @return array<int, array{id:int, commission_amount:float}>
	 */
	public static function get_allocatable_commission_rows( $affiliate_id ) {
		global $wpdb;
		$table   = $wpdb->prefix . 'pagbank_affiliate_commissions';
		$now_gmt = gmdate( 'Y-m-d H:i:s' );
		$locked  = self::get_pending_withdrawal_locked_commission_ids( $affiliate_id );
		list( $excl_sql, $excl_args ) = self::sql_exclude_commission_ids( $locked );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT id, commission_amount FROM %i
				WHERE affiliate_id = %d AND status = %s
				AND ( payment_method IS NULL OR payment_method = \'\' OR payment_method = %s )
				AND ( available_at IS NULL OR available_at <= %s )
				' . $excl_sql . '
				ORDER BY id ASC',
				...array_merge( array( $table, $affiliate_id, 'pending', 'manual', $now_gmt ), $excl_args )
			),
			ARRAY_A
		);
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Solicitar saque: valor = saldo total disponÃ­vel; congela IDs de comissÃ£o no pedido.
	 *
	 * @param int   $user_id User ID.
	 * @param float $amount  Deve coincidir com o saldo (total).
	 * @return int|\WP_Error Withdrawal ID.
	 */
	public static function request( $user_id, $amount ) {
		if ( 'manual' !== ( RC_Affiliates_Settings::get()['payment_mode'] ?? 'manual' ) ) {
			return new \WP_Error( 'mode', __( 'Saques sÃ³ estÃ£o disponÃ­veis no modo de pagamento manual.', 'rc-affiliates' ) );
		}
		if ( self::has_pending_request( $user_id ) ) {
			return new \WP_Error( 'pending', __( 'JÃ¡ existe um pedido de saque pendente. Aguarde o processamento.', 'rc-affiliates' ) );
		}

		$settings = RC_Affiliates_Settings::get();
		$min      = (float) ( $settings['manual_min_withdrawal'] ?? 0 );
		$avail    = self::get_available_balance( $user_id );
		$amount   = (float) wc_format_decimal( $amount );

		if ( $avail <= 0 ) {
			return new \WP_Error( 'empty', __( 'NÃ£o hÃ¡ saldo disponÃ­vel para saque.', 'rc-affiliates' ) );
		}
		if ( $amount < $min ) {
			return new \WP_Error( 'min', __( 'Valor abaixo do mÃ­nimo para saque.', 'rc-affiliates' ) );
		}
		if ( ! self::amounts_match( $amount, $avail ) ) {
			return new \WP_Error(
				'full_balance',
				__( 'No momento sÃ³ Ã© possÃ­vel solicitar saque pelo saldo total disponÃ­vel.', 'rc-affiliates' )
			);
		}

		$rows = self::get_allocatable_commission_rows( $user_id );
		$ids  = array();
		$sum  = 0.0;
		foreach ( $rows as $r ) {
			$ids[] = (int) $r['id'];
			$sum  += (float) $r['commission_amount'];
		}
		if ( ! self::amounts_match( $sum, $amount ) ) {
			return new \WP_Error( 'sync', __( 'Saldo alterado. Atualize a pÃ¡gina e tente novamente.', 'rc-affiliates' ) );
		}

		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'pagbank_affiliate_withdrawals',
			array(
				'affiliate_id'         => $user_id,
				'amount'               => $amount,
				'status'               => 'pending',
				'currency'             => get_woocommerce_currency(),
				'created_at'           => current_time( 'mysql' ),
				'commission_ids_json' => wp_json_encode( $ids ),
			),
			array( '%d', '%f', '%s', '%s', '%s', '%s' )
		);
		return (int) $wpdb->insert_id;
	}

	/**
	 * Marcar pedido de saque como pago e liquidar as comissÃµes congeladas no pedido.
	 *
	 * @param int    $withdrawal_id Row id.
	 * @param string $proof_notes    Comprovante / referÃªncia (admin).
	 * @return bool|\WP_Error
	 */
	public static function mark_paid( $withdrawal_id, $proof_notes = '' ) {
		global $wpdb;
		$table = $wpdb->prefix . 'pagbank_affiliate_withdrawals';
		$id    = (int) $withdrawal_id;
		$row   = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', $table, $id ), ARRAY_A );
		if ( ! $row ) {
			return new \WP_Error( 'not_found', __( 'Registro nÃ£o encontrado.', 'rc-affiliates' ) );
		}
		if ( 'pending' !== $row['status'] ) {
			return new \WP_Error( 'invalid', __( 'Apenas pedidos pendentes podem ser marcados como pagos.', 'rc-affiliates' ) );
		}

		$aff_id   = (int) $row['affiliate_id'];
		$target   = (float) $row['amount'];
		$proof_notes = sanitize_textarea_field( (string) $proof_notes );
		$proof_notes = mb_substr( $proof_notes, 0, 4000 );
		if ( '' === trim( $proof_notes ) ) {
			return new \WP_Error( 'proof_required', __( 'Informe o comprovante ou referÃªncia do pagamento antes de marcar como pago.', 'rc-affiliates' ) );
		}

		$ids = array();
		if ( ! empty( $row['commission_ids_json'] ) ) {
			$decoded = json_decode( (string) $row['commission_ids_json'], true );
			if ( is_array( $decoded ) ) {
				$ids = array_map( 'absint', $decoded );
				$ids = array_values( array_filter( $ids ) );
			}
		}

		if ( empty( $ids ) ) {
			return new \WP_Error(
				'legacy',
				__( 'Este pedido de saque nÃ£o associa comissÃµes. Cancele-o e peÃ§a ao afiliado para solicitar novamente, ou liquide em Afiliados â†’ Pedidos.', 'rc-affiliates' )
			);
		}

		$check_sum = 0.0;
		foreach ( $ids as $cid ) {
			$comm_table = $wpdb->prefix . 'pagbank_affiliate_commissions';
			$amt        = $wpdb->get_row( $wpdb->prepare( 'SELECT affiliate_id, commission_amount, status, payment_method FROM %i WHERE id = %d', $comm_table, $cid ), ARRAY_A );
			if ( ! $amt || (int) $amt['affiliate_id'] !== $aff_id || 'pending' !== $amt['status'] ) {
				return new \WP_Error( 'commission', __( 'Uma das comissÃµes deste saque jÃ¡ nÃ£o estÃ¡ pendente. Verifique Afiliados â†’ Pedidos.', 'rc-affiliates' ) );
			}
			$pm = isset( $amt['payment_method'] ) ? (string) $amt['payment_method'] : '';
			if ( 'split' === $pm ) {
				return new \WP_Error( 'split', __( 'ComissÃ£o em modo split nÃ£o pode ser liquidada por este fluxo.', 'rc-affiliates' ) );
			}
			$check_sum += (float) $amt['commission_amount'];
		}
		if ( ! self::amounts_match( $check_sum, $target ) ) {
			return new \WP_Error( 'amount', __( 'A soma das comissÃµes nÃ£o coincide com o valor do saque.', 'rc-affiliates' ) );
		}

		$wpdb->query( 'START TRANSACTION' );

		foreach ( $ids as $cid ) {
			$res = RC_Affiliates_Commission::mark_row_paid( $cid, array( 'send_email' => false ) );
			if ( is_wp_error( $res ) ) {
				$wpdb->query( 'ROLLBACK' );
				return $res;
			}
		}
		$paid_sum = $check_sum;

		$ok = $wpdb->update(
			$table,
			array(
				'status'       => 'paid',
				'processed_at' => current_time( 'mysql' ),
				'notes'        => $proof_notes !== '' ? $proof_notes : null,
			),
			array( 'id' => $id ),
			array( '%s', '%s', '%s' ),
			array( '%d' )
		);
		if ( false === $ok ) {
			$wpdb->query( 'ROLLBACK' );
			return new \WP_Error( 'db', __( 'NÃ£o foi possÃ­vel atualizar o pedido de saque.', 'rc-affiliates' ) );
		}

		$wpdb->query( 'COMMIT' );

		if ( $paid_sum > 0 && apply_filters( 'RC_Affiliates_send_commission_paid_email', true, $aff_id, $paid_sum, $id, $proof_notes ) ) {
			RC_Affiliates_Emails::send_commission_paid( $aff_id, $paid_sum, $proof_notes );
		}

		return true;
	}

	/**
	 * HistÃ³rico de saques jÃ¡ pagos (ex.: Ã¡rea do afiliado).
	 *
	 * @param int $affiliate_id User ID.
	 * @param int $limit      Max rows.
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_paid_withdrawals_for_affiliate( $affiliate_id, $limit = 50 ) {
		global $wpdb;
		$affiliate_id = (int) $affiliate_id;
		$limit        = max( 1, min( 100, (int) $limit ) );
		if ( $affiliate_id <= 0 ) {
			return array();
		}
		$table = $wpdb->prefix . 'pagbank_affiliate_withdrawals';
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM %i WHERE affiliate_id = %d AND status = %s ORDER BY processed_at DESC, id DESC LIMIT %d',
				$table,
				$affiliate_id,
				'paid',
				$limit
			),
			ARRAY_A
		);
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Quantidade de comissÃµes no snapshot JSON do saque.
	 *
	 * @param array<string, mixed>|null $row Linha da tabela de saques ou pelo menos commission_ids_json.
	 * @return int
	 */
	public static function count_commissions_in_withdrawal_row( $row ) {
		if ( ! is_array( $row ) || empty( $row['commission_ids_json'] ) ) {
			return 0;
		}
		$decoded = json_decode( (string) $row['commission_ids_json'], true );
		if ( ! is_array( $decoded ) ) {
			return 0;
		}
		return count( array_filter( array_map( 'absint', $decoded ) ) );
	}

	/**
	 * Texto formatado dos dados bancÃ¡rios do afiliado (modo manual).
	 *
	 * @param int $user_id User ID.
	 * @return array<int, string> Linhas para exibiÃ§Ã£o.
	 */
	public static function get_bank_detail_lines( $user_id ) {
		$user_id = (int) $user_id;
		if ( $user_id <= 0 ) {
			return array();
		}
		$code = (string) get_user_meta( $user_id, 'pb_affiliate_bank_code', true );
		$ag   = (string) get_user_meta( $user_id, 'pb_affiliate_bank_agency', true );
		$acc  = (string) get_user_meta( $user_id, 'pb_affiliate_bank_account', true );
		$doc  = (string) get_user_meta( $user_id, 'pb_affiliate_bank_document', true );
		$lines = array();
		if ( $code !== '' ) {
			$lines[] = sprintf(
				/* translators: 1: bank code */
				__( 'Banco (cÃ³digo): %s', 'rc-affiliates' ),
				$code
			);
		}
		if ( $ag !== '' ) {
			$lines[] = sprintf(
				/* translators: %s: agency */
				__( 'AgÃªncia: %s', 'rc-affiliates' ),
				$ag
			);
		}
		if ( $acc !== '' ) {
			$lines[] = sprintf(
				/* translators: %s: account */
				__( 'Conta: %s', 'rc-affiliates' ),
				$acc
			);
		}
		if ( $doc !== '' ) {
			$lines[] = sprintf(
				/* translators: %s: tax id */
				__( 'CPF/CNPJ: %s', 'rc-affiliates' ),
				$doc
			);
		}
		return $lines;
	}
}

