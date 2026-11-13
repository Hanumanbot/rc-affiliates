<?php
/**
 * Links de afiliados: identificador, URL de indicaÃ§Ã£o, domÃ­nios de referÃªncia.
 *
 * @package RC_Affiliates
 * @var string $link Referral link.
 * @var string $code Affiliate public code.
 * @var array  $affiliate_domains DomÃ­nios de referÃªncia (meta pb_affiliate_verified_domains).
 * @var bool   $pb_aff_has_promo_materials Exibe atalho para materiais promocionais.
 * @var string $pb_aff_materials_url URL do endpoint affiliate-materials.
 * @var string $pb_aff_nav_active links (para o partial de navegaÃ§Ã£o).
 * @var bool $pb_aff_zip1_enabled Exibe caixa zip1.io (admin).
 */

defined( 'ABSPATH' ) || exit;

// Algumas themes/caches nÃ£o exibem `wc_print_notices()` nos endpoints customizados do WooCommerce.
// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Notice keys are read-only (redirect); values sanitized below.
$pb_aff_area_notice_key  = isset( $_GET['pb_aff_area_notice'] ) ? sanitize_key( wp_unslash( $_GET['pb_aff_area_notice'] ) ) : '';
$pb_aff_area_notice_type = isset( $_GET['pb_aff_area_notice_type'] ) ? sanitize_key( wp_unslash( $_GET['pb_aff_area_notice_type'] ) ) : '';
// phpcs:enable WordPress.Security.NonceVerification.Recommended

$pb_aff_area_notice_type = in_array( $pb_aff_area_notice_type, array( 'error', 'success' ), true ) ? $pb_aff_area_notice_type : '';

$pb_has_wc_notices = false;
if ( function_exists( 'wc_get_notices' ) ) {
	$pb_notices = wc_get_notices();
	if ( is_array( $pb_notices ) && ! empty( $pb_notices ) ) {
		$pb_has_wc_notices = true;
	}
}

if ( $pb_has_wc_notices && function_exists( 'wc_print_notices' ) ) {
	wc_print_notices();
} else {
	$pb_msg = '';
	$type   = $pb_aff_area_notice_type ? $pb_aff_area_notice_type : 'success';
	switch ( (string) $pb_aff_area_notice_key ) {
		case 'join_terms_required':
			$pb_msg = __( 'Aceite os termos para continuar.', 'rc-affiliates' );
			break;
		case 'join_not_allowed':
			$pb_msg = __( 'NÃ£o Ã© possÃ­vel concluir o pedido.', 'rc-affiliates' );
			break;
		case 'join_pending':
			$pb_msg = __( 'Pedido enviado. Aguarde a aprovaÃ§Ã£o da loja.', 'rc-affiliates' );
			break;
		case 'join_active':
			$pb_msg = __( 'Bem-vindo ao programa de afiliados! Seu link estÃ¡ disponÃ­vel abaixo.', 'rc-affiliates' );
			break;
		case 'code_not_active':
			$pb_msg = __( 'Apenas afiliados ativos podem alterar o identificador.', 'rc-affiliates' );
			$type   = 'error';
			break;
		case 'code_too_short':
			$pb_msg = __( 'O identificador deve ter pelo menos 3 caracteres (letras minÃºsculas, nÃºmeros, _ ou -).', 'rc-affiliates' );
			$type   = 'error';
			break;
		case 'code_in_use':
			$pb_msg = __( 'Este identificador jÃ¡ estÃ¡ em uso. Escolha outro.', 'rc-affiliates' );
			$type   = 'error';
			break;
		case 'code_updated':
			$pb_msg = __( 'Identificador atualizado.', 'rc-affiliates' );
			break;
		case 'withdraw_not_active':
			$pb_msg = __( 'Apenas afiliados ativos podem solicitar saque.', 'rc-affiliates' );
			$type   = 'error';
			break;
		case 'withdraw_error':
			$pb_msg = __( 'NÃ£o foi possÃ­vel concluir o pedido de saque.', 'rc-affiliates' );
			$type   = 'error';
			break;
		case 'withdraw_requested':
			$pb_msg = __( 'Pedido de saque enviado. A loja irÃ¡ processar e registrar o pagamento.', 'rc-affiliates' );
			break;
		default:
			break;
	}
	if ( '' !== $pb_msg ) {
		$ul_class = 'error' === $type ? 'woocommerce-error' : 'woocommerce-message';
		echo '<div class="woocommerce-notices-wrapper" aria-label="' . esc_attr__( 'Notices', 'rc-affiliates' ) . '">';
		echo '<ul class="' . esc_attr( $ul_class ) . '" role="alert"><li>' . wp_kses_post( $pb_msg ) . '</li></ul>';
		echo '</div>';
	}
}

$affiliate_domains = isset( $affiliate_domains ) && is_array( $affiliate_domains ) ? $affiliate_domains : array();
$pb_aff_nav_active = isset( $pb_aff_nav_active ) ? (string) $pb_aff_nav_active : 'links';
?>
<div class="pb-aff-hub pb-aff-hub--subpage">
	<?php include RC_Affiliates_PATH . 'templates/my-account/parts/affiliate-hub-nav.php'; ?>

	<h2 class="pb-aff-links-page-title"><?php esc_html_e( 'Links de afiliados', 'rc-affiliates' ); ?></h2>

	<h3><?php esc_html_e( 'Identificador de afiliado', 'rc-affiliates' ); ?></h3>
	<form method="post" class="pb-aff-change-code">
		<?php wp_nonce_field( 'pb_aff_area', 'pb_aff_area_nonce' ); ?>
		<input type="hidden" name="pb_aff_area_action" value="change_code" />
		<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
			<label for="pb_aff_new_code"><?php esc_html_e( 'Identificador pÃºblico (usado na URL de indicaÃ§Ã£o)', 'rc-affiliates' ); ?></label>
			<input type="text" name="pb_aff_new_code" id="pb_aff_new_code" class="woocommerce-Input input-text" value="<?php echo esc_attr( (string) $code ); ?>" autocomplete="off" maxlength="40" />
		</p>
		<p>
			<button type="submit" class="button woocommerce-Button"><?php esc_html_e( 'Salvar identificador', 'rc-affiliates' ); ?></button>
		</p>
		<p class="description"><?php esc_html_e( 'Apenas letras minÃºsculas, nÃºmeros, hÃ­fen e sublinhado; mÃ­nimo 3 caracteres. Deve ser Ãºnico na loja.', 'rc-affiliates' ); ?></p>
	</form>

	<h3><?php esc_html_e( 'Seu link de afiliado', 'rc-affiliates' ); ?></h3>
	<div class="pb-aff-link-row">
		<input type="text" id="pb-aff-referral-link" readonly="readonly" class="pb-aff-link-row__input large-text woocommerce-Input" value="<?php echo esc_attr( $link ); ?>" onclick="this.select();" aria-label="<?php esc_attr_e( 'Link de afiliado', 'rc-affiliates' ); ?>" />
		<button type="button" class="button" id="pb-aff-copy-link-btn" data-label-done="<?php echo esc_attr( __( 'Copiado!', 'rc-affiliates' ) ); ?>">
			<?php esc_html_e( 'Copiar link', 'rc-affiliates' ); ?>
		</button>
	</div>

	<section class="pb-aff-link-builder pb-aff-zip1-box" aria-labelledby="pb-aff-lb-title">
		<h3 id="pb-aff-lb-title"><?php esc_html_e( 'Compartilhe um produto, pÃ¡gina ou categoria', 'rc-affiliates' ); ?></h3>
		<p class="description"><?php esc_html_e( 'Cole o endereÃ§o de um produto, de uma categoria ou de qualquer pÃ¡gina desta loja. O sistema acrescenta o seu cÃ³digo de afiliado ao link.', 'rc-affiliates' ); ?></p>
		<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
			<label for="pb-aff-lb-paste"><?php esc_html_e( 'URL da pÃ¡gina', 'rc-affiliates' ); ?></label>
			<input type="text" id="pb-aff-lb-paste" class="woocommerce-Input input-text large-text" autocomplete="off" placeholder="<?php esc_attr_e( 'https://â€¦ ou /caminho/relativo', 'rc-affiliates' ); ?>" />
		</p>
		<p>
			<button type="button" class="button woocommerce-Button pb-aff-btn--primary" id="pb-aff-lb-generate"><?php esc_html_e( 'Gerar link de afiliado', 'rc-affiliates' ); ?></button>
		</p>
		<div id="pb-aff-lb-notice" class="pb-aff-lb-notice" role="alert" hidden></div>
		<div id="pb-aff-lb-result" class="pb-aff-lb-result" hidden>
			<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
				<label for="pb-aff-lb-out"><?php esc_html_e( 'Link com o seu cÃ³digo', 'rc-affiliates' ); ?></label>
			</p>
			<div class="pb-aff-link-row">
				<input type="text" id="pb-aff-lb-out" readonly="readonly" class="pb-aff-link-row__input large-text woocommerce-Input" value="" onclick="this.select();" aria-label="<?php esc_attr_e( 'Link de afiliado gerado', 'rc-affiliates' ); ?>" />
				<button type="button" class="button" id="pb-aff-lb-copy" data-label-done="<?php echo esc_attr( __( 'Copiado!', 'rc-affiliates' ) ); ?>"><?php esc_html_e( 'Copiar', 'rc-affiliates' ); ?></button>
			</div>
		</div>
	</section>

	<?php if ( ! empty( $pb_aff_zip1_enabled ) ) : ?>
		<?php
		$pb_z1_short       = '';
		$pb_z1_stats       = '';
		$pb_z1_has         = false;
		$pb_z1_source_init = $link;
		?>
	<section class="pb-aff-zip1-box" data-pb-aff-zip1="1" aria-labelledby="pb-aff-zip1-title">
		<h3 id="pb-aff-zip1-title"><?php esc_html_e( 'Link curto', 'rc-affiliates' ); ?></h3>

		<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide pb-aff-zip1-source-row">
			<label for="pb-aff-zip1-source-url"><?php esc_html_e( 'URL a encurtar', 'rc-affiliates' ); ?></label>
			<input type="text" id="pb-aff-zip1-source-url" class="woocommerce-Input input-text large-text" value="<?php echo esc_attr( $pb_z1_source_init ); ?>" autocomplete="off" aria-label="<?php esc_attr_e( 'URL de destino a encurtar', 'rc-affiliates' ); ?>" />
		</p>

		<div id="pb-aff-zip1-notice" class="pb-aff-zip1-notice" role="alert" hidden></div>
		<div id="pb-aff-zip1-panel" class="pb-aff-zip1-panel"<?php echo $pb_z1_has ? '' : ' hidden'; ?>>
			<div class="pb-aff-link-row pb-aff-zip1-row">
				<input type="text" id="pb-aff-zip1-short-input" readonly="readonly" class="pb-aff-link-row__input large-text woocommerce-Input" value="<?php echo esc_attr( $pb_z1_short ); ?>" onclick="this.select();" aria-label="<?php esc_attr_e( 'Link curto gerado', 'rc-affiliates' ); ?>" />
				<button type="button" class="button" id="pb-aff-zip1-copy" data-label-done="<?php echo esc_attr( __( 'Copiado!', 'rc-affiliates' ) ); ?>"><?php esc_html_e( 'Copiar link curto', 'rc-affiliates' ); ?></button>
			</div>
			<p class="pb-aff-zip1-stats-line"<?php echo $pb_z1_stats ? '' : ' hidden'; ?>>
				<a id="pb-aff-zip1-stats-link" href="<?php echo $pb_z1_stats ? esc_url( $pb_z1_stats ) : '#'; ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'EstatÃ­sticas no zip1.io', 'rc-affiliates' ); ?></a>
			</p>
			<p class="pb-aff-zip1-replace-line">
				<button type="button" class="button" id="pb-aff-zip1-replace"><?php esc_html_e( 'Gerar outro link curto', 'rc-affiliates' ); ?></button>
			</p>
		</div>
		<div id="pb-aff-zip1-form-panel" class="pb-aff-zip1-form-panel"<?php echo $pb_z1_has ? ' hidden' : ''; ?>>
			<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
				<label class="pb-aff-zip1-alias-label-visible" for="pb-aff-zip1-alias"><?php esc_html_e( 'Personalizar endereÃ§o (opcional)', 'rc-affiliates' ); ?></label>
				<span class="pb-aff-zip1-alias-wrap">
					<span class="pb-aff-zip1-alias-prefix" aria-hidden="true">https://zip1.io/</span>
					<input type="text" id="pb-aff-zip1-alias" class="woocommerce-Input input-text pb-aff-zip1-alias-input" maxlength="16" autocomplete="off" placeholder="<?php esc_attr_e( 'Opcional: 3â€“16 caracteres (aâ€“z, Aâ€“Z, 0â€“9, hÃ­fen) ou um emoji.', 'rc-affiliates' ); ?>" />
				</span>
			</p>
			<p>
				<button type="button" class="button woocommerce-Button pb-aff-btn--primary" id="pb-aff-zip1-submit"><?php esc_html_e( 'Gerar link curto', 'rc-affiliates' ); ?></button>
			</p>
		</div>
	</section>
	<?php endif; ?>

	<h3><?php esc_html_e( 'DomÃ­nio de referÃªncia (Referer)', 'rc-affiliates' ); ?></h3>
	<?php
	$pb_aff_referer_hint = __( 'Lembre-se de nÃ£o colocar rel="noreferrer" em seus links.', 'rc-affiliates' );
	?>
	<p class="pb-aff-domain-intro">
		<?php esc_html_e( 'Tem um site ou blog? Informe o URL aqui.', 'rc-affiliates' ); ?><br />
		<?php esc_html_e( 'Toda vez que um link for clicado a partir do seu site, automaticamente saberemos que foi sua indicaÃ§Ã£o, sem sequer precisar informar o seu cÃ³digo de afiliado.', 'rc-affiliates' ); ?>
		<span class="pb-aff-domain-info" tabindex="0" title="<?php echo esc_attr( $pb_aff_referer_hint ); ?>" aria-label="<?php echo esc_attr( $pb_aff_referer_hint ); ?>"><?php echo esc_html( 'â„¹' ); ?></span>
	</p>
	<form method="post" class="pb-aff-domain-form">
		<?php wp_nonce_field( 'pb_aff_domain', 'pb_aff_domain_nonce' ); ?>
		<input type="hidden" name="pb_aff_domain_action" value="add" />
		<p>
			<label for="pb_aff_domain_url"><?php esc_html_e( 'URL do seu site (domÃ­nio ou subdomÃ­nio)', 'rc-affiliates' ); ?></label>
			<input type="url" name="pb_aff_domain_url" id="pb_aff_domain_url" class="woocommerce-Input input-text" placeholder="https://exemplo.com.br" />
		</p>
		<button type="submit" class="button"><?php esc_html_e( 'Adicionar', 'rc-affiliates' ); ?></button>
	</form>

	<?php if ( ! empty( $affiliate_domains ) ) : ?>
		<h4 class="pb-aff-domain-heading"><?php esc_html_e( 'Seus sites', 'rc-affiliates' ); ?></h4>
		<table class="shop_table shop_table_responsive pb-aff-domain-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Site', 'rc-affiliates' ); ?></th>
					<th><?php esc_html_e( 'Status', 'rc-affiliates' ); ?></th>
					<th class="pb-aff-domain-table__action"><?php esc_html_e( 'AÃ§Ãµes', 'rc-affiliates' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ( $affiliate_domains as $pb_aff_d ) {
					$pb_aff_host = isset( $pb_aff_d['host'] ) ? (string) $pb_aff_d['host'] : '';
					if ( '' === $pb_aff_host ) {
						continue;
					}
					$pb_aff_verified = ! empty( $pb_aff_d['verified'] );
					$pb_aff_token          = isset( $pb_aff_d['token'] ) ? (string) $pb_aff_d['token'] : '';
					$pb_aff_file_path      = $pb_aff_token ? 'https://' . $pb_aff_host . '/.well-known/pb-affiliate-' . $pb_aff_token . '.txt' : '';
					$pb_aff_download_name  = $pb_aff_token ? 'pb-affiliate-' . $pb_aff_token . '.txt' : '';
					$pb_aff_download_href  = $pb_aff_token ? 'data:text/plain;charset=utf-8,' . rawurlencode( $pb_aff_token ) : '';
					$pb_aff_remove_confirm = __( 'Remover este site da lista?', 'rc-affiliates' );
					?>
				<tr>
					<td data-title="<?php esc_attr_e( 'Site', 'rc-affiliates' ); ?>"><?php echo esc_html( $pb_aff_host ); ?></td>
					<td data-title="<?php esc_attr_e( 'Status', 'rc-affiliates' ); ?>">
						<?php
						if ( $pb_aff_verified ) {
							esc_html_e( 'Validado', 'rc-affiliates' );
						} else {
							esc_html_e( 'Pendente de validaÃ§Ã£o', 'rc-affiliates' );
						}
						?>
					</td>
					<td class="pb-aff-domain-table__action" data-title="<?php esc_attr_e( 'AÃ§Ãµes', 'rc-affiliates' ); ?>">
						<div class="pb-aff-domain-actions">
							<?php if ( ! $pb_aff_verified && $pb_aff_token && $pb_aff_download_href ) : ?>
								<form method="post" class="pb-aff-domain-validate-form">
									<?php wp_nonce_field( 'pb_aff_domain', 'pb_aff_domain_nonce' ); ?>
									<input type="hidden" name="pb_aff_domain_action" value="validate" />
									<input type="hidden" name="pb_aff_domain_host" value="<?php echo esc_attr( $pb_aff_host ); ?>" />
									<button type="submit" class="button woocommerce-Button pb-aff-btn--primary"><?php esc_html_e( 'Validar', 'rc-affiliates' ); ?></button>
								</form>
								<a class="button woocommerce-Button pb-aff-btn--secondary" href="<?php echo esc_attr( $pb_aff_download_href ); ?>" download="<?php echo esc_attr( $pb_aff_download_name ); ?>"><?php esc_html_e( 'Baixar arquivo', 'rc-affiliates' ); ?></a>
							<?php endif; ?>
							<form method="post" class="pb-aff-domain-remove-form" onsubmit="return window.confirm(<?php echo wp_json_encode( $pb_aff_remove_confirm ); ?>);">
								<?php wp_nonce_field( 'pb_aff_domain', 'pb_aff_domain_nonce' ); ?>
								<input type="hidden" name="pb_aff_domain_action" value="remove" />
								<input type="hidden" name="pb_aff_domain_host" value="<?php echo esc_attr( $pb_aff_host ); ?>" />
								<button type="submit" class="button woocommerce-Button pb-aff-btn--danger"><?php esc_html_e( 'Excluir', 'rc-affiliates' ); ?></button>
							</form>
						</div>
					</td>
				</tr>
					<?php if ( ! $pb_aff_verified && $pb_aff_file_path && $pb_aff_token ) : ?>
				<tr class="pb-aff-domain-table__instructions">
					<td colspan="3">
						<p class="description">
							<?php esc_html_e( 'Arquivo (URL exata):', 'rc-affiliates' ); ?>
							<a href="<?php echo esc_url( $pb_aff_file_path ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $pb_aff_file_path ); ?></a>
						</p>
						<p class="description">
							<?php esc_html_e( 'ConteÃºdo do arquivo (texto puro, uma linha):', 'rc-affiliates' ); ?>
							<code><?php echo esc_html( $pb_aff_token ); ?></code>
						</p>
					</td>
				</tr>
					<?php endif; ?>
					<?php
				}
				?>
			</tbody>
		</table>
	<?php endif; ?>
</div>

<script>
(function(){
	var btn = document.getElementById('pb-aff-copy-link-btn');
	var inp = document.getElementById('pb-aff-referral-link');
	if (!btn || !inp) return;
	btn.addEventListener('click', function() {
		inp.focus();
		inp.select();
		inp.setSelectionRange(0, 99999);
		var done = btn.getAttribute('data-label-done') || 'OK';
		var orig = btn.textContent;
		function feedback() {
			btn.textContent = done;
			btn.disabled = true;
			setTimeout(function() {
				btn.textContent = orig;
				btn.disabled = false;
			}, 2000);
		}
		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(inp.value).then(feedback).catch(function() {
				try {
					document.execCommand('copy');
					feedback();
				} catch (e) {}
			});
		} else {
			try {
				document.execCommand('copy');
				feedback();
			} catch (e) {}
		}
	});
})();
</script>

