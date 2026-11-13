<?php
/**
 * Uninstall rc affiliates.
 *
 * @package RC_Affiliates
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}pagbank_affiliate_commissions" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}pagbank_affiliate_withdrawals" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}pagbank_affiliate_click_log" );

delete_option( 'RC_Affiliates_settings' );
delete_option( 'RC_Affiliates_db_version' );


