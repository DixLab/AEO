<?php
/**
 * Routine di disinstallazione del plugin.
 *
 * Rimuove le opzioni salvate e, opzionalmente, i file generati.
 *
 * @package AI_Discovery_Manager
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Rimuove le opzioni del plugin.
delete_option( 'adm_settings' );
delete_transient( 'adm_notice' );

// Rimuove i file fisici generati (best effort).
$root = untrailingslashit( ABSPATH );

$files = array(
	$root . '/llms.txt',
	$root . '/skills.md',
	$root . '/.well-known/agent-skills/index.json',
);

foreach ( $files as $file ) {
	if ( file_exists( $file ) ) {
		@unlink( $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	}
}
