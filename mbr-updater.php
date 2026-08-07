<?php
/**
 * MBR Plugin Updater
 *
 * Self-hosted update bootstrap for the MBR plugin suite. Wraps the
 * Plugin Update Checker (PUC) library so update notices appear on the
 * standard WordPress Plugins page for plugins distributed off wordpress.org.
 *
 * Usage:
 *   1. Drop the unpacked `plugin-update-checker` directory next to this file.
 *   2. require_once this file from your main plugin file.
 *   3. Call mbr_register_updates() with your plugin's details (see bottom).
 *
 * @package MBR
 * @author  Robert Palmer (Made by Robert)
 * @license GPL-2.0-or-later
 */

// No direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/plugin-update-checker/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

if ( ! function_exists( 'mbr_register_updates' ) ) {

	/**
	 * Register a self-hosted update source for an MBR plugin.
	 *
	 * @param array $args {
	 *     @type string $source     'json' (self-hosted manifest) or 'github'.
	 *     @type string $url        Manifest URL (json) or repo URL (github).
	 *     @type string $file       Absolute path to the main plugin file (__FILE__).
	 *     @type string $slug       Plugin slug, e.g. 'mbr-bulk-wp-detector'.
	 *     @type string $branch     Optional. GitHub branch for stable releases.
	 *     @type bool   $assets      Optional. GitHub: use release ZIP assets.
	 * }
	 * @return \YahnisElsts\PluginUpdateChecker\v5p4\Plugin\UpdateChecker|null
	 */
	function mbr_register_updates( array $args ) {

		$args = wp_parse_args(
			$args,
			array(
				'source' => 'json',
				'url'    => '',
				'file'   => '',
				'slug'   => '',
				'branch' => 'main',
				'assets' => true,
			)
		);

		if ( empty( $args['url'] ) || empty( $args['file'] ) || empty( $args['slug'] ) ) {
			return null;
		}

		$checker = PucFactory::buildUpdateChecker(
			$args['url'],
			$args['file'],
			$args['slug']
		);

		// GitHub-specific configuration.
		if ( 'github' === $args['source'] ) {
			$api = $checker->getVcsApi();

			if ( ! empty( $args['branch'] ) ) {
				$checker->setBranch( $args['branch'] );
			}

			// Serve the ZIP attached to each GitHub release rather than a
			// source-code tarball. Recommended if you attach a built ZIP.
			if ( ! empty( $args['assets'] ) ) {
				$api->enableReleaseAssets();
			}
		}

		return $checker;
	}
}

/*
 * --------------------------------------------------------------------------
 * Per-plugin registration
 * --------------------------------------------------------------------------
 * Paste ONE of the blocks below into your plugin's main file (after the
 * require_once for this file), and edit the values to match the plugin.
 */

/* Self-hosted JSON manifest (default — best for plugins wp.org won't carry):

mbr_register_updates(
	array(
		'source' => 'json',
		'url'    => 'https://littlewebshack.com/updates/mbr-bulk-wp-detector.json',
		'file'   => __FILE__,
		'slug'   => 'mbr-bulk-wp-detector',
	)
);
*/

/* GitHub releases (lower maintenance if you already tag releases):

mbr_register_updates(
	array(
		'source' => 'github',
		'url'    => 'https://github.com/HarbourBob/mbr-bulk-wp-detector/',
		'file'   => __FILE__,
		'slug'   => 'mbr-bulk-wp-detector',
		'branch' => 'main',
		'assets' => true,
	)
);
*/
