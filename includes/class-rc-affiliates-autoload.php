<?php
/**
 * Simple autoload for rc affiliates classes.
 *
 * @package RC_Affiliates
 */

defined( 'ABSPATH' ) || exit;

$pb_aff_files = array(
	'class-rc-affiliates-install.php',
	'class-rc-affiliates.php',
	'class-rc-affiliates-dependencies.php',
	'class-rc-affiliates-settings.php',
	'class-rc-affiliates-emails.php',
	'class-rc-affiliates-role.php',
	'class-rc-affiliates-domain-verify.php',
	'class-rc-affiliates-tracking.php',
	'class-rc-affiliates-attribution.php',
	'class-rc-affiliates-click-log.php',
	'class-rc-affiliates-commission.php',
	'class-rc-affiliates-category-commission.php',
	'class-rc-affiliates-order.php',
	'class-rc-affiliates-coupon.php',
	'class-rc-affiliates-withdrawal.php',
	'class-rc-affiliates-split.php',
	'class-rc-affiliates-promotional-materials.php',
	'class-rc-affiliates-reports.php',
	'class-rc-affiliates-account.php',
	'class-rc-affiliates-zip1.php',
	'class-rc-affiliates-bank-combo.php',
	'admin/class-rc-affiliates-admin-materials.php',
	'admin/class-rc-affiliates-admin-affiliates.php',
	'admin/class-rc-affiliates-admin-payments.php',
	'admin/class-rc-affiliates-admin.php',
	'admin/class-rc-affiliates-admin-settings.php',
	'admin/class-rc-affiliates-admin-user-detail.php',
	'admin/class-rc-affiliates-user-profile.php',
	'admin/class-rc-affiliates-order-meta-box.php',
	'public/class-rc-affiliates-public.php',
);

foreach ( $pb_aff_files as $file ) {
	// Classes under plugin root (e.g. public/) â€” not under includes/.
	if ( 0 === strpos( $file, 'public/', 0 ) ) {
		$path = RC_Affiliates_PATH . $file;
	} else {
		$path = RC_Affiliates_PATH . 'includes/' . $file;
	}
	if ( is_readable( $path ) ) {
		require_once $path;
	}
}


