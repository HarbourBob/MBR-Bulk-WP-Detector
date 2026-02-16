<?php
/**
 * Plugin Name:       MBR Bulk WP Detector
 * Description:       Bulk check websites to detect WordPress installations. Find themes, plugins, and versions. Perfect for agencies and marketers targeting WordPress users.
 * Version:           2.1.0
 * Author:            Robert Palmer
 * Author URI:        https://littlewebshack.com
 * Requires at least: 5.6
 * Requires PHP:      7.2
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       robs-bulk-wp-platform-checker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Buy Me a Coffee
add_filter( 'plugin_row_meta', function ( $links, $file, $data ) {
    if ( ! function_exists( 'plugin_basename' ) || $file !== plugin_basename( __FILE__ ) ) {
        return $links;
    }

    $url = 'https://buymeacoffee.com/robertpalmer/';
    $links[] = sprintf(
	// translators: %s: The name of the plugin author.
        '<a href="%s" target="_blank" rel="noopener nofollow" aria-label="%s">☕ %s</a>',
        esc_url( $url ),
		// translators: %s: The name of the plugin author.
        esc_attr( sprintf( __( 'Buy %s a coffee', 'robs-bulk-wp-platform-checker' ), isset( $data['AuthorName'] ) ? $data['AuthorName'] : __( 'the author', 'robs-bulk-wp-platform-checker' ) ) ),
        esc_html__( 'Buy me a coffee', 'robs-bulk-wp-platform-checker' )
    );

    return $links;
}, 10, 3 );


if ( ! class_exists( 'WP_Platform_Checker' ) ) {
	/**
	 * Main plugin class for WP Platform Checker
	 *
	 * @since 2.0.0
	 */
	class WP_Platform_Checker {
		/**
		 * Plugin version
		 *
		 * @var string
		 */
		private $version = '2.1.0';

		/**
		 * Plugin slug
		 *
		 * @var string
		 */
		private $slug = 'robs-bulk-wp-platform-checker';

		/**
		 * Database table name for history
		 *
		 * @var string
		 */
		private $history_table;

		/**
		 * Database table name for saved lists
		 *
		 * @var string
		 */
		private $lists_table;

		/**
		 * Constructor
		 */
		public function __construct() {
			global $wpdb;
			$this->history_table = $wpdb->prefix . 'wppc_history';
			$this->lists_table   = $wpdb->prefix . 'wppc_lists';

			add_action( 'admin_menu', [ $this, 'register_admin_page' ] );
			add_action( 'wp_ajax_wppc_check_site', [ $this, 'ajax_check_site' ] );
			add_action( 'wp_ajax_wppc_save_list', [ $this, 'ajax_save_list' ] );
			add_action( 'wp_ajax_wppc_load_list', [ $this, 'ajax_load_list' ] );
			add_action( 'wp_ajax_wppc_delete_list', [ $this, 'ajax_delete_list' ] );
			add_action( 'wp_ajax_wppc_get_lists', [ $this, 'ajax_get_lists' ] );
			add_action( 'wp_ajax_wppc_clear_cache', [ $this, 'ajax_clear_cache' ] );
			
			// Check if tables exist and create them if needed
			add_action( 'admin_init', [ $this, 'maybe_create_tables' ] );
		}

		/**
		 * Check if tables exist and create them if needed
		 * Runs on admin_init to ensure tables are always available
		 *
		 * @return void
		 */
		public function maybe_create_tables() {
			global $wpdb;
			
			// Check if tables exist
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$history_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $this->history_table ) ) === $this->history_table;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$lists_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $this->lists_table ) ) === $this->lists_table;
			
			// If both exist, we're good
			if ( $history_exists && $lists_exists ) {
				return;
			}
			
			// Create tables if needed
			$this->create_tables();
		}

		/**
		 * Create database tables
		 *
		 * @return void
		 */
		private function create_tables() {
			global $wpdb;
			$charset_collate = $wpdb->get_charset_collate();

			// History table
			$sql_history = "CREATE TABLE IF NOT EXISTS {$this->history_table} (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				url varchar(500) NOT NULL,
				is_wordpress tinyint(1) NOT NULL DEFAULT 0,
				inconclusive tinyint(1) NOT NULL DEFAULT 0,
				confidence varchar(20) DEFAULT NULL,
				reason text DEFAULT NULL,
				theme varchar(200) DEFAULT NULL,
				plugins text DEFAULT NULL,
				wp_version varchar(20) DEFAULT NULL,
				contact_email varchar(200) DEFAULT NULL,
				contact_phone varchar(50) DEFAULT NULL,
				checked_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY url (url(191)),
				KEY checked_at (checked_at)
			) $charset_collate;";

			// Saved lists table
			$sql_lists = "CREATE TABLE IF NOT EXISTS {$this->lists_table} (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				name varchar(200) NOT NULL,
				urls longtext NOT NULL,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id)
			) $charset_collate;";

			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql_history );
			dbDelta( $sql_lists );
		}

		/**
		 * Register admin page
		 *
		 * @return void
		 */
		public function register_admin_page() {
			add_options_page(
				__( 'WP Platform Checker', 'robs-bulk-wp-platform-checker' ),
				__( 'WP Platform Checker', 'robs-bulk-wp-platform-checker' ),
				'manage_options',
				$this->slug,
				[ $this, 'render_admin_page' ]
			);
		}

		/**
		 * Render admin page
		 *
		 * @return void
		 */
		public function render_admin_page() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$nonce = wp_create_nonce( 'wppc_nonce' );
			?>
			<div class="wrap wppc-wrap">
				<h1><?php esc_html_e( 'WP Platform Checker', 'robs-bulk-wp-platform-checker' ); ?></h1>
				<p class="description"><?php esc_html_e( 'Paste one website address per line, upload a CSV, or load a saved list. Click "Check websites" to test whether each site is running on WordPress.', 'robs-bulk-wp-platform-checker' ); ?></p>

				<div class="wppc-container">
					<!-- Left Column: Input & Controls -->
					<div class="wppc-left-column">
						<div class="wppc-card">
							<h3><?php esc_html_e( 'URL Input', 'robs-bulk-wp-platform-checker' ); ?></h3>
							
							<!-- File Upload -->
							<div class="wppc-upload-section">
								<label class="button">
									<input type="file" id="wppc-file-upload" accept=".csv,.txt" style="display:none;">
									<?php esc_html_e( 'Upload CSV/TXT', 'robs-bulk-wp-platform-checker' ); ?>
								</label>
								<span class="description"><?php esc_html_e( 'Import URLs from file', 'robs-bulk-wp-platform-checker' ); ?></span>
							</div>

							<textarea id="wppc-input" class="large-text code" rows="12" placeholder="https://example.com
example.org
http://another-example.net"></textarea>
							
							<div class="wppc-url-stats">
								<span id="wppc-url-count"><?php esc_html_e( '0 URLs', 'robs-bulk-wp-platform-checker' ); ?></span>
								<span id="wppc-duplicate-count" style="display:none;"></span>
								<span id="wppc-invalid-count" style="display:none;"></span>
							</div>

							<!-- Advanced Options -->
							<div class="wppc-advanced-options">
								<h4><?php esc_html_e( 'Detection Options', 'robs-bulk-wp-platform-checker' ); ?></h4>
								<label>
									<input type="checkbox" id="wppc-deep-scan" checked>
									<?php esc_html_e( 'Deep scan (detect theme, plugins, version)', 'robs-bulk-wp-platform-checker' ); ?>
								</label>
								<label>
									<input type="checkbox" id="wppc-harvest-contacts">
									<?php esc_html_e( 'Harvest contact information (email, phone)', 'robs-bulk-wp-platform-checker' ); ?>
									<span class="description" style="display:block; margin-left:20px; font-style:italic;">
										<?php esc_html_e( 'Note: Contact details may not always be available or accurate. This feature searches publicly accessible pages.', 'robs-bulk-wp-platform-checker' ); ?>
									</span>
								</label>
								<label>
									<input type="checkbox" id="wppc-use-cache" checked>
									<?php esc_html_e( 'Use cached results (faster)', 'robs-bulk-wp-platform-checker' ); ?>
								</label>
								<label>
									<input type="checkbox" id="wppc-remove-duplicates" checked>
									<?php esc_html_e( 'Remove duplicate URLs automatically', 'robs-bulk-wp-platform-checker' ); ?>
								</label>
								
								<h4><?php esc_html_e( 'Filters', 'robs-bulk-wp-platform-checker' ); ?></h4>
								<label>
									<?php esc_html_e( 'Only check domains:', 'robs-bulk-wp-platform-checker' ); ?>
									<input type="text" id="wppc-tld-filter" placeholder=".com, .org, .net">
									<span class="description"><?php esc_html_e( 'Comma-separated (leave empty for all)', 'robs-bulk-wp-platform-checker' ); ?></span>
								</label>
								
								<label>
									<?php esc_html_e( 'Request delay (ms):', 'robs-bulk-wp-platform-checker' ); ?>
									<input type="number" id="wppc-delay" value="0" min="0" max="5000" step="100">
									<span class="description"><?php esc_html_e( 'Add delay between requests', 'robs-bulk-wp-platform-checker' ); ?></span>
								</label>

								<label>
									<?php esc_html_e( 'Concurrent requests:', 'robs-bulk-wp-platform-checker' ); ?>
									<input type="number" id="wppc-concurrency" value="8" min="1" max="20" step="1">
									<span class="description"><?php esc_html_e( 'Higher = faster, but may trigger rate limits', 'robs-bulk-wp-platform-checker' ); ?></span>
								</label>
							</div>

							<!-- Action Buttons -->
							<div class="wppc-actions">
								<button id="wppc-validate" class="button">
									<?php esc_html_e( 'Validate URLs', 'robs-bulk-wp-platform-checker' ); ?>
								</button>
								<button id="wppc-run" class="button button-primary">
									<?php esc_html_e( 'Check Websites', 'robs-bulk-wp-platform-checker' ); ?>
								</button>
								<button id="wppc-pause" class="button" style="display:none;">
									<?php esc_html_e( 'Pause', 'robs-bulk-wp-platform-checker' ); ?>
								</button>
								<button id="wppc-clear" class="button">
									<?php esc_html_e( 'Clear', 'robs-bulk-wp-platform-checker' ); ?>
								</button>
							</div>

							<div id="wppc-progress-section" style="display:none;">
								<div class="wppc-progress-bar">
									<div id="wppc-progress-fill"></div>
								</div>
								<div class="wppc-progress-text">
									<span id="wppc-progress-label"></span>
									<span id="wppc-time-estimate"></span>
								</div>
							</div>
						</div>

						<!-- Saved Lists -->
						<div class="wppc-card">
							<h3><?php esc_html_e( 'Saved Lists', 'robs-bulk-wp-platform-checker' ); ?></h3>
							<div class="wppc-list-actions">
								<input type="text" id="wppc-list-name" placeholder="<?php esc_attr_e( 'List name...', 'robs-bulk-wp-platform-checker' ); ?>">
								<button id="wppc-save-list" class="button">
									<?php esc_html_e( 'Save Current List', 'robs-bulk-wp-platform-checker' ); ?>
								</button>
							</div>
							<div id="wppc-saved-lists"></div>
						</div>
					</div>

					<!-- Right Column: Results & Export -->
					<div class="wppc-right-column">
						<div class="wppc-card">
							<div class="wppc-results-header">
								<h3><?php esc_html_e( 'Results', 'robs-bulk-wp-platform-checker' ); ?></h3>
								<div class="wppc-results-actions">
									<button id="wppc-export-csv" class="button" disabled>
										<?php esc_html_e( 'Export CSV', 'robs-bulk-wp-platform-checker' ); ?>
									</button>
									<button id="wppc-export-json" class="button" disabled>
										<?php esc_html_e( 'Export JSON', 'robs-bulk-wp-platform-checker' ); ?>
									</button>
									<button id="wppc-copy-urls" class="button" disabled>
										<?php esc_html_e( 'Copy WP URLs', 'robs-bulk-wp-platform-checker' ); ?>
									</button>
								</div>
							</div>

							<div class="wppc-export-options">
								<label>
									<input type="checkbox" id="wppc-export-wp-only" checked>
									<?php esc_html_e( 'WordPress sites only', 'robs-bulk-wp-platform-checker' ); ?>
								</label>
								<label>
									<input type="checkbox" id="wppc-export-include-deep">
									<?php esc_html_e( 'Include deep scan data (theme, plugins, version, contacts)', 'robs-bulk-wp-platform-checker' ); ?>
								</label>
							</div>

							<!-- Statistics -->
							<div id="wppc-stats" style="display:none;">
								<div class="wppc-stat-grid">
									<div class="wppc-stat">
										<span class="wppc-stat-label"><?php esc_html_e( 'Total Checked', 'robs-bulk-wp-platform-checker' ); ?></span>
										<span class="wppc-stat-value" id="stat-total">0</span>
									</div>
									<div class="wppc-stat wppc-stat-success">
										<span class="wppc-stat-label"><?php esc_html_e( 'WordPress', 'robs-bulk-wp-platform-checker' ); ?></span>
										<span class="wppc-stat-value" id="stat-wp">0</span>
									</div>
									<div class="wppc-stat wppc-stat-danger">
										<span class="wppc-stat-label"><?php esc_html_e( 'Not WordPress', 'robs-bulk-wp-platform-checker' ); ?></span>
										<span class="wppc-stat-value" id="stat-not-wp">0</span>
									</div>
									<div class="wppc-stat wppc-stat-warning">
										<span class="wppc-stat-label"><?php esc_html_e( 'Inconclusive', 'robs-bulk-wp-platform-checker' ); ?></span>
										<span class="wppc-stat-value" id="stat-inconclusive">0</span>
									</div>
								</div>
							</div>

							<!-- Filter Buttons -->
							<div id="wppc-filters" style="display:none;">
								<button class="wppc-filter-btn active" data-filter="all"><?php esc_html_e( 'All', 'robs-bulk-wp-platform-checker' ); ?></button>
								<button class="wppc-filter-btn" data-filter="wordpress"><?php esc_html_e( 'WordPress', 'robs-bulk-wp-platform-checker' ); ?></button>
								<button class="wppc-filter-btn" data-filter="not-wordpress"><?php esc_html_e( 'Not WordPress', 'robs-bulk-wp-platform-checker' ); ?></button>
								<button class="wppc-filter-btn" data-filter="inconclusive"><?php esc_html_e( 'Inconclusive', 'robs-bulk-wp-platform-checker' ); ?></button>
							</div>

							<!-- Results Table -->
							<div class="wppc-table-wrapper">
								<table class="widefat fixed striped wppc-results-table" id="wppc-results" style="display:none;">
									<thead>
										<tr>
											<th class="wppc-sortable" data-sort="url">
												<?php esc_html_e( 'Website', 'robs-bulk-wp-platform-checker' ); ?>
												<span class="wppc-sort-indicator"></span>
											</th>
											<th class="wppc-sortable" data-sort="status">
												<?php esc_html_e( 'Result', 'robs-bulk-wp-platform-checker' ); ?>
												<span class="wppc-sort-indicator"></span>
											</th>
											<th><?php esc_html_e( 'Details', 'robs-bulk-wp-platform-checker' ); ?></th>
											<th class="wppc-sortable" data-sort="confidence">
												<?php esc_html_e( 'Confidence', 'robs-bulk-wp-platform-checker' ); ?>
												<span class="wppc-sort-indicator"></span>
											</th>
										</tr>
									</thead>
									<tbody></tbody>
								</table>
							</div>
						</div>

						<!-- Cache Management -->
						<div class="wppc-card wppc-cache-card">
							<h3><?php esc_html_e( 'Cache Management', 'robs-bulk-wp-platform-checker' ); ?></h3>
							<p class="description">
								<?php esc_html_e( 'Cached results are stored for 7 days to speed up repeated checks.', 'robs-bulk-wp-platform-checker' ); ?>
							</p>
							<button id="wppc-clear-cache" class="button">
								<?php esc_html_e( 'Clear All Cache', 'robs-bulk-wp-platform-checker' ); ?>
							</button>
						</div>
					</div>
				</div>
			</div>

			<style>
			.wppc-wrap {
				max-width: 100%;
				margin: 20px 0;
			}
			.wppc-container {
				display: grid;
				grid-template-columns: 500px 1fr;
				gap: 20px;
				margin-top: 20px;
			}
			@media (max-width: 1200px) {
				.wppc-container {
					grid-template-columns: 1fr;
				}
			}
			.wppc-card {
				background: #fff;
				border: 1px solid #ccd0d4;
				border-radius: 4px;
				padding: 20px;
				margin-bottom: 20px;
				box-shadow: 0 1px 1px rgba(0,0,0,.04);
			}
			.wppc-card h3 {
				margin-top: 0;
				padding-bottom: 10px;
				border-bottom: 1px solid #f0f0f1;
			}
			.wppc-card h4 {
				margin: 15px 0 8px 0;
				font-size: 13px;
				font-weight: 600;
				color: #1d2327;
			}
			.wppc-upload-section {
				margin-bottom: 12px;
				padding: 10px;
				background: #f6f7f7;
				border-radius: 4px;
			}
			.wppc-upload-section .description {
				margin-left: 10px;
				font-size: 12px;
			}
			.wppc-url-stats {
				display: flex;
				gap: 15px;
				margin: 10px 0;
				padding: 8px 0;
				font-size: 13px;
				color: #646970;
			}
			#wppc-url-count {
				font-weight: 600;
				color: #2271b1;
			}
			#wppc-duplicate-count {
				color: #d63638;
			}
			#wppc-invalid-count {
				color: #d63638;
			}
			.wppc-advanced-options {
				margin-top: 15px;
				padding: 15px;
				background: #f6f7f7;
				border-radius: 4px;
			}
			.wppc-advanced-options label {
				display: block;
				margin: 8px 0;
				font-size: 13px;
			}
			.wppc-advanced-options input[type="text"],
			.wppc-advanced-options input[type="number"] {
				width: 100%;
				max-width: 250px;
				margin-top: 4px;
			}
			.wppc-advanced-options .description {
				display: block;
				margin-top: 4px;
				font-size: 12px;
				color: #646970;
			}
			.wppc-actions {
				display: flex;
				gap: 8px;
				flex-wrap: wrap;
				margin-top: 15px;
			}
			.wppc-progress-section {
				margin-top: 15px;
			}
			.wppc-progress-bar {
				width: 100%;
				height: 24px;
				background: #f0f0f1;
				border-radius: 4px;
				overflow: hidden;
				margin-bottom: 8px;
			}
			#wppc-progress-fill {
				height: 100%;
				background: linear-gradient(90deg, #2271b1, #135e96);
				transition: width 0.3s ease;
				display: flex;
				align-items: center;
				justify-content: center;
				color: #fff;
				font-size: 12px;
				font-weight: 600;
			}
			.wppc-progress-text {
				display: flex;
				justify-content: space-between;
				font-size: 13px;
				color: #646970;
			}
			.wppc-list-actions {
				display: flex;
				gap: 8px;
				margin-bottom: 15px;
			}
			#wppc-list-name {
				flex: 1;
			}
			#wppc-saved-lists {
				max-height: 200px;
				overflow-y: auto;
			}
			.wppc-saved-list-item {
				display: flex;
				justify-content: space-between;
				align-items: center;
				padding: 8px;
				margin: 4px 0;
				background: #f6f7f7;
				border-radius: 4px;
				font-size: 13px;
			}
			.wppc-saved-list-item:hover {
				background: #dcdcde;
			}
			.wppc-saved-list-name {
				flex: 1;
				cursor: pointer;
				font-weight: 500;
			}
			.wppc-saved-list-actions {
				display: flex;
				gap: 8px;
			}
			.wppc-results-header {
				display: flex;
				justify-content: space-between;
				align-items: center;
				margin-bottom: 15px;
			}
			.wppc-results-header h3 {
				margin: 0;
				padding: 0;
				border: none;
			}
			.wppc-results-actions {
				display: flex;
				gap: 8px;
			}
			.wppc-export-options {
				margin-bottom: 15px;
				padding: 10px;
				background: #f6f7f7;
				border-radius: 4px;
			}
			.wppc-export-options label {
				display: inline-block;
				margin-right: 15px;
				font-size: 13px;
			}
			.wppc-stat-grid {
				display: grid;
				grid-template-columns: repeat(4, 1fr);
				gap: 15px;
				margin: 15px 0;
			}
			@media (max-width: 1400px) {
				.wppc-stat-grid {
					grid-template-columns: repeat(2, 1fr);
				}
			}
			.wppc-stat {
				text-align: center;
				padding: 15px;
				background: #f6f7f7;
				border-radius: 4px;
				border-left: 4px solid #2271b1;
			}
			.wppc-stat-success {
				border-left-color: #00a32a;
				background: #f0f6f0;
			}
			.wppc-stat-danger {
				border-left-color: #d63638;
				background: #f6f0f0;
			}
			.wppc-stat-warning {
				border-left-color: #dba617;
				background: #f6f4f0;
			}
			.wppc-stat-label {
				display: block;
				font-size: 12px;
				color: #646970;
				margin-bottom: 5px;
			}
			.wppc-stat-value {
				display: block;
				font-size: 24px;
				font-weight: 600;
				color: #1d2327;
			}
			#wppc-filters {
				display: flex;
				gap: 8px;
				margin-bottom: 15px;
				flex-wrap: wrap;
			}
			.wppc-filter-btn {
				padding: 6px 12px;
				border: 1px solid #ccd0d4;
				background: #fff;
				border-radius: 4px;
				cursor: pointer;
				font-size: 13px;
				transition: all 0.2s;
			}
			.wppc-filter-btn:hover {
				background: #f6f7f7;
			}
			.wppc-filter-btn.active {
				background: #2271b1;
				color: #fff;
				border-color: #2271b1;
			}
			.wppc-table-wrapper {
				overflow-x: auto;
			}
			.wppc-results-table {
				margin-top: 15px;
			}
			.wppc-results-table th {
				font-weight: 600;
				white-space: nowrap;
			}
			.wppc-sortable {
				cursor: pointer;
				user-select: none;
			}
			.wppc-sortable:hover {
				background: #f0f0f1;
			}
			.wppc-sort-indicator {
				display: inline-block;
				margin-left: 4px;
				opacity: 0.3;
			}
			.wppc-sortable.sorted-asc .wppc-sort-indicator::after {
				content: '↑';
				opacity: 1;
			}
			.wppc-sortable.sorted-desc .wppc-sort-indicator::after {
				content: '↓';
				opacity: 1;
			}
			.wppc-results-table tr.status-ok td {
				background: #f0fff4;
				border-left: 3px solid #00a32a;
			}
			.wppc-results-table tr.status-warn td {
				background: #fffef0;
				border-left: 3px solid #dba617;
			}
			.wppc-results-table tr.status-bad td {
				background: #fff5f5;
				border-left: 3px solid #d63638;
			}
			.wppc-results-table tr.hidden {
				display: none;
			}
			.wppc-badge {
				display: inline-block;
				padding: 3px 8px;
				border-radius: 3px;
				font-size: 11px;
				font-weight: 600;
				text-transform: uppercase;
			}
			.wppc-badge-success {
				background: #00a32a;
				color: #fff;
			}
			.wppc-badge-warning {
				background: #dba617;
				color: #fff;
			}
			.wppc-badge-danger {
				background: #d63638;
				color: #fff;
			}
			.wppc-badge-high {
				background: #2271b1;
				color: #fff;
			}
			.wppc-badge-medium {
				background: #72aee6;
				color: #fff;
			}
			.wppc-badge-low {
				background: #dcdcde;
				color: #1d2327;
			}
			.wppc-details {
				font-size: 12px;
				line-height: 1.5;
			}
			.wppc-details-item {
				margin: 3px 0;
			}
			.wppc-details-label {
				font-weight: 600;
				color: #646970;
			}
			.wppc-cache-card p {
				margin-bottom: 10px;
			}
			</style>

			<script type="text/javascript">
			(function(){
				const ajaxUrl = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';
				const nonce = '<?php echo esc_js( $nonce ); ?>';

				// DOM elements
				const $ = (sel) => document.querySelector(sel);
				const $$ = (sel) => document.querySelectorAll(sel);

				// Helper function to escape HTML
				const escapeHtml = (text) => {
					const div = document.createElement('div');
					div.textContent = text;
					return div.innerHTML;
				};

				const $input = $('#wppc-input');
				const $fileUpload = $('#wppc-file-upload');
				const $validate = $('#wppc-validate');
				const $run = $('#wppc-run');
				const $pause = $('#wppc-pause');
				const $clear = $('#wppc-clear');
				const $exportCsv = $('#wppc-export-csv');
				const $exportJson = $('#wppc-export-json');
				const $copyUrls = $('#wppc-copy-urls');
				const $table = $('#wppc-results');
				const $tbody = $table.querySelector('tbody');
				const $stats = $('#wppc-stats');
				const $filters = $('#wppc-filters');
				const $progressSection = $('#wppc-progress-section');
				const $progressFill = $('#wppc-progress-fill');
				const $progressLabel = $('#wppc-progress-label');
				const $timeEstimate = $('#wppc-time-estimate');
				const $urlCount = $('#wppc-url-count');
				const $duplicateCount = $('#wppc-duplicate-count');
				const $invalidCount = $('#wppc-invalid-count');
				
				// State
				let rows = [];
				let completed = 0;
				let paused = false;
				let startTime = 0;
				let currentFilter = 'all';
				let sortColumn = null;
				let sortDirection = 'asc';

				/**
				 * Normalize URL
				 */
				function normaliseUrl(u){
					u = (u || '').trim();
					if(!u) return '';
					if(!/^https?:\/\//i.test(u)) u = 'https://' + u;
					try { new URL(u); return u; } catch(e){ return ''; }
				}

				/**
				 * Update URL count
				 */
				function updateUrlCount(){
					const urls = $input.value.split(/\n|\r/).map(u => u.trim()).filter(Boolean);
					$urlCount.textContent = urls.length + ' URL' + (urls.length !== 1 ? 's' : '');
				}

				/**
				 * Validate URLs and show issues
				 */
				function validateUrls(){
					const lines = $input.value.split(/\n|\r/);
					const urls = lines.map(normaliseUrl);
					
					const valid = urls.filter(Boolean);
					const invalid = lines.filter((line, i) => line.trim() && !urls[i]);
					const duplicates = valid.filter((url, i) => valid.indexOf(url) !== i);
					
					let message = `Found ${valid.length} valid URL(s)`;
					
					if(invalid.length > 0){
						message += `\n${invalid.length} invalid URL(s): ${invalid.slice(0, 3).join(', ')}${invalid.length > 3 ? '...' : ''}`;
						$invalidCount.textContent = invalid.length + ' invalid';
						$invalidCount.style.display = '';
					} else {
						$invalidCount.style.display = 'none';
					}
					
					if(duplicates.length > 0){
						message += `\n${duplicates.length} duplicate(s) found`;
						$duplicateCount.textContent = duplicates.length + ' duplicate(s)';
						$duplicateCount.style.display = '';
					} else {
						$duplicateCount.style.display = 'none';
					}
					
					alert(message);
				}

				/**
				 * Remove duplicates from input
				 */
				function removeDuplicates(urls){
					return [...new Set(urls)];
				}

				/**
				 * Filter URLs by TLD
				 */
				function filterByTld(urls, tlds){
					if(!tlds) return urls;
					const tldList = tlds.split(',').map(t => t.trim().toLowerCase()).filter(Boolean);
					if(tldList.length === 0) return urls;
					
					return urls.filter(url => {
						try {
							const hostname = new URL(url).hostname.toLowerCase();
							return tldList.some(tld => hostname.endsWith(tld));
						} catch(e) {
							return false;
						}
					});
				}

				/**
				 * Set progress
				 */
				function setProgress(done, total){
					if(total === 0) {
						$progressSection.style.display = 'none';
						return;
					}
					
					$progressSection.style.display = '';
					const percent = Math.round((done / total) * 100);
					$progressFill.style.width = percent + '%';
					$progressFill.textContent = percent + '%';
					$progressLabel.textContent = `${done}/${total} checked`;
					
					// Estimate time
					if(done > 0 && done < total){
						const elapsed = Date.now() - startTime;
						const avgTime = elapsed / done;
						const remaining = (total - done) * avgTime;
						const minutes = Math.floor(remaining / 60000);
						const seconds = Math.floor((remaining % 60000) / 1000);
						$timeEstimate.textContent = `~${minutes}m ${seconds}s remaining`;
					} else {
						$timeEstimate.textContent = '';
					}
				}

				/**
				 * Update statistics
				 */
				function updateStats(){
					const total = rows.length;
					const wp = rows.filter(r => r.data && r.data.is_wordpress).length;
					const notWp = rows.filter(r => r.data && !r.data.is_wordpress && !r.data.inconclusive).length;
					const inconclusive = rows.filter(r => r.data && r.data.inconclusive).length;
					
					$('#stat-total').textContent = total;
					$('#stat-wp').textContent = wp;
					$('#stat-not-wp').textContent = notWp;
					$('#stat-inconclusive').textContent = inconclusive;
					
					$stats.style.display = total > 0 ? '' : 'none';
					$filters.style.display = total > 0 ? 'flex' : 'none';
				}

				/**
				 * Add row to table
				 */
				function addRow(url){
					const tr = document.createElement('tr');
					tr.innerHTML = `
						<td><a href="${url.replace(/"/g,'&quot;')}" target="_blank" rel="noopener">${url.replace(/</g,'&lt;')}</a></td>
						<td class="wppc-status"><span class="wppc-badge">Pending</span></td>
						<td class="wppc-details">—</td>
						<td class="wppc-confidence">—</td>
					`;
					$tbody.appendChild(tr);
					return tr;
				}

				/**
				 * Update row with results
				 */
				function updateRow(tr, data){
					const statusCell = tr.querySelector('.wppc-status');
					const detailsCell = tr.querySelector('.wppc-details');
					const confidenceCell = tr.querySelector('.wppc-confidence');
					
					// Status badge
					if(data.is_wordpress){
						statusCell.innerHTML = '<span class="wppc-badge wppc-badge-success">WordPress</span>';
						tr.classList.add('status-ok');
					} else if(data.inconclusive){
						statusCell.innerHTML = '<span class="wppc-badge wppc-badge-warning">Inconclusive</span>';
						tr.classList.add('status-warn');
					} else {
						statusCell.innerHTML = '<span class="wppc-badge wppc-badge-danger">Not WordPress</span>';
						tr.classList.add('status-bad');
					}
					
					// Details
					let details = `<div class="wppc-details-item"><span class="wppc-details-label">Reason:</span> ${data.reason || '—'}</div>`;
					if(data.theme){
						details += `<div class="wppc-details-item"><span class="wppc-details-label">Theme:</span> ${data.theme}</div>`;
					}
					if(data.wp_version){
						details += `<div class="wppc-details-item"><span class="wppc-details-label">WP Version:</span> ${data.wp_version}</div>`;
					}
					if(data.plugins && data.plugins.length > 0){
						details += `<div class="wppc-details-item"><span class="wppc-details-label">Plugins:</span> ${data.plugins.join(', ')}</div>`;
					}
					if(data.contact_email){
						details += `<div class="wppc-details-item"><span class="wppc-details-label">Email:</span> <a href="mailto:${escapeHtml(data.contact_email)}">${escapeHtml(data.contact_email)}</a></div>`;
					}
					if(data.contact_phone){
						details += `<div class="wppc-details-item"><span class="wppc-details-label">Phone:</span> ${escapeHtml(data.contact_phone)}</div>`;
					}
					detailsCell.innerHTML = details;
					
					// Confidence
					const conf = (data.confidence || 'low').toUpperCase();
					let badgeClass = 'wppc-badge-low';
					if(conf === 'HIGH') badgeClass = 'wppc-badge-high';
					else if(conf === 'MEDIUM') badgeClass = 'wppc-badge-medium';
					confidenceCell.innerHTML = `<span class="wppc-badge ${badgeClass}">${conf}</span>`;
				}

				/**
				 * Check single site
				 */
				async function checkOne(url, tr, useCache, deepScan, harvestContacts){
					const fd = new FormData();
					fd.append('action', 'wppc_check_site');
					fd.append('_ajax_nonce', nonce);
					fd.append('url', url);
					fd.append('use_cache', useCache ? '1' : '0');
					fd.append('deep_scan', deepScan ? '1' : '0');
					fd.append('harvest_contacts', harvestContacts ? '1' : '0');
					
					try {
						const res = await fetch(ajaxUrl, { 
							method: 'POST', 
							credentials: 'same-origin', 
							body: fd 
						});
						const json = await res.json();
						
						if(json && json.success){
							const data = json.data || {};
							updateRow(tr, data);
							return data;
						} else {
							const errorData = {
								is_wordpress: false,
								inconclusive: true,
								confidence: 'low',
								reason: (json && json.data && json.data.message) ? json.data.message : 'Request failed'
							};
							updateRow(tr, errorData);
							return errorData;
						}
					} catch(e) {
						const errorData = {
							is_wordpress: false,
							inconclusive: true,
							confidence: 'low',
							reason: e && e.message ? e.message : 'Network error'
						};
						updateRow(tr, errorData);
						return errorData;
					}
				}

				/**
				 * Run checks
				 */
				async function runChecks(){
					paused = false;
					completed = 0;
					$tbody.innerHTML = '';
					rows = [];
					$exportCsv.disabled = true;
					$exportJson.disabled = true;
					$copyUrls.disabled = true;
					$run.style.display = 'none';
					$pause.style.display = '';

					let urls = $input.value.split(/\n|\r/).map(normaliseUrl).filter(Boolean);
					
					if(urls.length === 0){
						alert('Please enter at least one valid website address.');
						$run.style.display = '';
						$pause.style.display = 'none';
						return;
					}

					// Remove duplicates if enabled
					if($('#wppc-remove-duplicates').checked){
						urls = removeDuplicates(urls);
					}

					// Filter by TLD if specified
					const tldFilter = $('#wppc-tld-filter').value.trim();
					if(tldFilter){
						urls = filterByTld(urls, tldFilter);
						if(urls.length === 0){
							alert('No URLs match the TLD filter.');
							$run.style.display = '';
							$pause.style.display = 'none';
							return;
						}
					}

					const useCache = $('#wppc-use-cache').checked;
					const deepScan = $('#wppc-deep-scan').checked;
					const harvestContacts = $('#wppc-harvest-contacts').checked;
					const delay = parseInt($('#wppc-delay').value) || 0;
					const concurrency = parseInt($('#wppc-concurrency').value) || 8;

					$table.style.display = '';
					startTime = Date.now();
					
					// Create rows
					urls.forEach(url => {
						const tr = addRow(url);
						rows.push({ url, tr, data: null });
					});
					
					setProgress(0, rows.length);
					updateStats();

					// Process with concurrency limit
					let index = 0;

					async function worker(){
						while(index < rows.length && !paused){
							const current = rows[index++];
							const data = await checkOne(current.url, current.tr, useCache, deepScan, harvestContacts);
							current.data = data;
							
							completed++;
							setProgress(completed, rows.length);
							updateStats();
							applyFilter(currentFilter);
							
							if(delay > 0 && index < rows.length){
								await new Promise(resolve => setTimeout(resolve, delay));
							}
						}
					}

					await Promise.all(Array.from({length: Math.min(concurrency, rows.length)}, worker));
					
					$exportCsv.disabled = false;
					$exportJson.disabled = false;
					$copyUrls.disabled = false;
					$run.style.display = '';
					$pause.style.display = 'none';
					
					if(completed === rows.length){
						$progressLabel.textContent = 'Complete!';
						$timeEstimate.textContent = '';
					}
				}

				/**
				 * Pause checks
				 */
				function pauseChecks(){
					paused = true;
					$run.style.display = '';
					$pause.style.display = 'none';
					$progressLabel.textContent += ' (Paused)';
				}

				/**
				 * Clear all
				 */
				function clearAll(){
					$input.value = '';
					$tbody.innerHTML = '';
					$table.style.display = 'none';
					setProgress(0, 0);
					$stats.style.display = 'none';
					$filters.style.display = 'none';
					$exportCsv.disabled = true;
					$exportJson.disabled = true;
					$copyUrls.disabled = true;
					rows = [];
					completed = 0;
					updateUrlCount();
					$duplicateCount.style.display = 'none';
					$invalidCount.style.display = 'none';
				}

				/**
				 * Apply filter
				 */
				function applyFilter(filter){
					currentFilter = filter;
					
					// Update button states
					$$('.wppc-filter-btn').forEach(btn => {
						btn.classList.toggle('active', btn.dataset.filter === filter);
					});
					
					// Filter rows
					rows.forEach(row => {
						if(!row.data) return;
						
						let show = false;
						if(filter === 'all'){
							show = true;
						} else if(filter === 'wordpress'){
							show = row.data.is_wordpress;
						} else if(filter === 'not-wordpress'){
							show = !row.data.is_wordpress && !row.data.inconclusive;
						} else if(filter === 'inconclusive'){
							show = row.data.inconclusive;
						}
						
						row.tr.classList.toggle('hidden', !show);
					});
				}

				/**
				 * Sort table
				 */
				function sortTable(column){
					if(sortColumn === column){
						sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
					} else {
						sortColumn = column;
						sortDirection = 'asc';
					}
					
					// Update headers
					$$('.wppc-sortable').forEach(th => {
						th.classList.remove('sorted-asc', 'sorted-desc');
						if(th.dataset.sort === column){
							th.classList.add('sorted-' + sortDirection);
						}
					});
					
					// Sort rows
					rows.sort((a, b) => {
						let aVal, bVal;
						
						if(column === 'url'){
							aVal = a.url;
							bVal = b.url;
						} else if(column === 'status'){
							aVal = a.data ? (a.data.is_wordpress ? 2 : a.data.inconclusive ? 1 : 0) : -1;
							bVal = b.data ? (b.data.is_wordpress ? 2 : b.data.inconclusive ? 1 : 0) : -1;
						} else if(column === 'confidence'){
							const confMap = {high: 3, medium: 2, low: 1};
							aVal = a.data ? (confMap[a.data.confidence] || 0) : 0;
							bVal = b.data ? (confMap[b.data.confidence] || 0) : 0;
						}
						
						if(aVal < bVal) return sortDirection === 'asc' ? -1 : 1;
						if(aVal > bVal) return sortDirection === 'asc' ? 1 : -1;
						return 0;
					});
					
					// Reorder DOM
					rows.forEach(row => $tbody.appendChild(row.tr));
				}

				/**
				 * Export to CSV
				 */
				function exportCSV(){
					const wpOnly = $('#wppc-export-wp-only').checked;
					const includeDeep = $('#wppc-export-include-deep').checked;
					
					const header = includeDeep 
						? ['Website','Status','Reason','Confidence','Theme','Plugins','WP Version','Email','Phone']
						: ['Website','Status','Reason','Confidence'];
					
					const lines = [header.join(',')];
					
					rows.forEach(row => {
						if(!row.data) return;
						if(wpOnly && !row.data.is_wordpress) return;
						
						const status = row.data.is_wordpress ? 'WordPress' : (row.data.inconclusive ? 'Inconclusive' : 'Not WordPress');
						const cols = [
							`"${row.url.replace(/"/g,'""')}"`,
							`"${status}"`,
							`"${(row.data.reason || '').replace(/"/g,'""')}"`,
							`"${(row.data.confidence || '').toUpperCase()}"`
						];
						
						if(includeDeep){
							cols.push(`"${row.data.theme || ''}"`);
							cols.push(`"${row.data.plugins ? row.data.plugins.join('; ') : ''}"`);
							cols.push(`"${row.data.wp_version || ''}"`);
							cols.push(`"${row.data.contact_email || ''}"`);
							cols.push(`"${row.data.contact_phone || ''}"`);
						}
						
						lines.push(cols.join(','));
					});
					
					const timestamp = new Date().toISOString().slice(0,10);
					downloadFile(lines.join('\n'), `wp-platform-checker-${timestamp}.csv`, 'text/csv');
				}

				/**
				 * Export to JSON
				 */
				function exportJSON(){
					const wpOnly = $('#wppc-export-wp-only').checked;
					
					const data = rows
						.filter(row => row.data && (!wpOnly || row.data.is_wordpress))
						.map(row => ({
							url: row.url,
							is_wordpress: row.data.is_wordpress,
							inconclusive: row.data.inconclusive,
							confidence: row.data.confidence,
							reason: row.data.reason,
							theme: row.data.theme || null,
							plugins: row.data.plugins || [],
							wp_version: row.data.wp_version || null
						}));
					
					const timestamp = new Date().toISOString().slice(0,10);
					downloadFile(JSON.stringify(data, null, 2), `wp-platform-checker-${timestamp}.json`, 'application/json');
				}

				/**
				 * Copy WordPress URLs to clipboard
				 */
				function copyWpUrls(){
					const wpUrls = rows
						.filter(row => row.data && row.data.is_wordpress)
						.map(row => row.url)
						.join('\n');
					
					if(!wpUrls){
						alert('No WordPress sites found to copy.');
						return;
					}
					
					navigator.clipboard.writeText(wpUrls).then(() => {
						alert(`Copied ${wpUrls.split('\n').length} WordPress URL(s) to clipboard!`);
					}).catch(err => {
						alert('Failed to copy to clipboard: ' + err);
					});
				}

				/**
				 * Download file helper
				 */
				function downloadFile(content, filename, mimeType){
					const blob = new Blob([content], {type: mimeType});
					const url = URL.createObjectURL(blob);
					const a = document.createElement('a');
					a.href = url;
					a.download = filename;
					a.click();
					URL.revokeObjectURL(url);
				}

				/**
				 * File upload handler
				 */
				$fileUpload.addEventListener('change', function(e){
					const file = e.target.files[0];
					if(!file) return;
					
					const reader = new FileReader();
					reader.onload = function(e){
						const content = e.target.result;
						const lines = content.split(/\n|\r/).map(l => l.trim()).filter(Boolean);
						
						if(lines.length > 0){
							// Try to detect if it's a CSV and extract URLs from first column
							const urls = lines.map(line => {
								// Skip header row if it looks like a header
								if(line.toLowerCase().includes('url') || line.toLowerCase().includes('website')){
									return '';
								}
								// Extract first column if comma-separated
								const parts = line.split(',');
								return parts[0].replace(/['"]/g, '').trim();
							}).filter(Boolean);
							
							$input.value = urls.join('\n');
							updateUrlCount();
							alert(`Loaded ${urls.length} URL(s) from file.`);
						}
					};
					reader.readAsText(file);
					
					// Reset input so same file can be loaded again
					$fileUpload.value = '';
				});

				/**
				 * Save list
				 */
				async function saveList(){
					const name = $('#wppc-list-name').value.trim();
					if(!name){
						alert('Please enter a name for this list.');
						return;
					}
					
					const urls = $input.value;
					if(!urls.trim()){
						alert('No URLs to save.');
						return;
					}
					
					const fd = new FormData();
					fd.append('action', 'wppc_save_list');
					fd.append('_ajax_nonce', nonce);
					fd.append('name', name);
					fd.append('urls', urls);
					
					try {
						const res = await fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', body: fd });
						const json = await res.json();
						
						if(json.success){
							alert('List saved successfully!');
							$('#wppc-list-name').value = '';
							loadSavedLists();
						} else {
							alert('Failed to save list: ' + (json.data ? json.data.message : 'Unknown error'));
						}
					} catch(e) {
						alert('Network error: ' + e.message);
					}
				}

				/**
				 * Load saved lists
				 */
				async function loadSavedLists(){
					const fd = new FormData();
					fd.append('action', 'wppc_get_lists');
					fd.append('_ajax_nonce', nonce);
					
					try {
						const res = await fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', body: fd });
						const json = await res.json();
						
						if(json.success && json.data){
							const container = $('#wppc-saved-lists');
							container.innerHTML = '';
							
							if(json.data.length === 0){
								container.innerHTML = '<p style="color:#646970;font-size:13px;font-style:italic;">No saved lists yet.</p>';
								return;
							}
							
							json.data.forEach(list => {
								const div = document.createElement('div');
								div.className = 'wppc-saved-list-item';
								div.innerHTML = `
									<span class="wppc-saved-list-name">${list.name}</span>
									<div class="wppc-saved-list-actions">
										<button class="button button-small" data-id="${list.id}" data-action="load">Load</button>
										<button class="button button-small" data-id="${list.id}" data-action="delete">Delete</button>
									</div>
								`;
								container.appendChild(div);
							});
						}
					} catch(e) {
						console.error('Failed to load saved lists:', e);
					}
				}

				/**
				 * Handle saved list actions
				 */
				$('#wppc-saved-lists').addEventListener('click', async function(e){
					const btn = e.target.closest('button');
					if(!btn) return;
					
					const action = btn.dataset.action;
					const id = btn.dataset.id;
					
					if(action === 'load'){
						const fd = new FormData();
						fd.append('action', 'wppc_load_list');
						fd.append('_ajax_nonce', nonce);
						fd.append('id', id);
						
						try {
							const res = await fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', body: fd });
							const json = await res.json();
							
							if(json.success && json.data){
								$input.value = json.data.urls;
								updateUrlCount();
							} else {
								alert('Failed to load list.');
							}
						} catch(e) {
							alert('Network error: ' + e.message);
						}
					} else if(action === 'delete'){
						if(!confirm('Are you sure you want to delete this list?')) return;
						
						const fd = new FormData();
						fd.append('action', 'wppc_delete_list');
						fd.append('_ajax_nonce', nonce);
						fd.append('id', id);
						
						try {
							const res = await fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', body: fd });
							const json = await res.json();
							
							if(json.success){
								loadSavedLists();
							} else {
								alert('Failed to delete list.');
							}
						} catch(e) {
							alert('Network error: ' + e.message);
						}
					}
				});

				/**
				 * Clear cache
				 */
				async function clearCache(){
					if(!confirm('Are you sure you want to clear all cached results?')) return;
					
					const fd = new FormData();
					fd.append('action', 'wppc_clear_cache');
					fd.append('_ajax_nonce', nonce);
					
					try {
						const res = await fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', body: fd });
						const json = await res.json();
						
						if(json.success){
							alert('Cache cleared successfully!');
						} else {
							alert('Failed to clear cache.');
						}
					} catch(e) {
						alert('Network error: ' + e.message);
					}
				}

				// Event listeners
				$input.addEventListener('input', updateUrlCount);
				$validate.addEventListener('click', validateUrls);
				$run.addEventListener('click', runChecks);
				$pause.addEventListener('click', pauseChecks);
				$clear.addEventListener('click', clearAll);
				$exportCsv.addEventListener('click', exportCSV);
				$exportJson.addEventListener('click', exportJSON);
				$copyUrls.addEventListener('click', copyWpUrls);
				$('#wppc-save-list').addEventListener('click', saveList);
				$('#wppc-clear-cache').addEventListener('click', clearCache);

				// Filter buttons
				$$('.wppc-filter-btn').forEach(btn => {
					btn.addEventListener('click', () => applyFilter(btn.dataset.filter));
				});

				// Sortable columns
				$$('.wppc-sortable').forEach(th => {
					th.addEventListener('click', () => sortTable(th.dataset.sort));
				});

				// Keyboard shortcuts
				document.addEventListener('keydown', function(e){
					// Ctrl/Cmd + Enter to run
					if((e.ctrlKey || e.metaKey) && e.key === 'Enter'){
						e.preventDefault();
						if($run.style.display !== 'none'){
							runChecks();
						}
					}
				});

				// Initialize
				updateUrlCount();
				loadSavedLists();
			})();
			</script>
			<?php
		}

		/**
		 * AJAX: Check a single site
		 *
		 * @return void
		 */
		public function ajax_check_site() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( [ 'message' => __( 'Permission denied.', 'robs-bulk-wp-platform-checker' ) ], 403 );
			}
			check_ajax_referer( 'wppc_nonce' );

			$raw_url          = isset( $_POST['url'] ) ? sanitize_text_field( wp_unslash( $_POST['url'] ) ) : '';
			$url              = $this->normalise_url( $raw_url );
			$use_cache        = ! empty( $_POST['use_cache'] );
			$deep_scan        = ! empty( $_POST['deep_scan'] );
			$harvest_contacts = ! empty( $_POST['harvest_contacts'] );

			if ( ! $url ) {
				wp_send_json_error( [ 'message' => __( 'Invalid URL.', 'robs-bulk-wp-platform-checker' ) ], 400 );
			}

			// Check cache first
			if ( $use_cache ) {
				$cached = $this->get_from_cache( $url );
				if ( $cached ) {
					wp_send_json_success( $cached );
					return;
				}
			}

			// Detect WordPress
			$check = $this->detect_wordpress( $url, $deep_scan );

			// Harvest contacts if enabled and WordPress detected
			if ( $harvest_contacts && $check['is_wordpress'] ) {
				$contacts = $this->harvest_contact_info( $url );
				$check    = array_merge( $check, $contacts );
			}

			// Save to cache and history
			$this->save_to_cache( $url, $check );
			$this->save_to_history( $url, $check );

			wp_send_json_success( $check );
		}

		/**
		 * AJAX: Save list
		 *
		 * @return void
		 */
		public function ajax_save_list() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( [ 'message' => __( 'Permission denied.', 'robs-bulk-wp-platform-checker' ) ], 403 );
			}
			check_ajax_referer( 'wppc_nonce' );

			$name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
			$urls = isset( $_POST['urls'] ) ? sanitize_textarea_field( wp_unslash( $_POST['urls'] ) ) : '';

			if ( empty( $name ) || empty( $urls ) ) {
				wp_send_json_error( [ 'message' => __( 'Name and URLs are required.', 'robs-bulk-wp-platform-checker' ) ] );
			}

			global $wpdb;
			
			// Ensure table exists
			$this->maybe_create_tables();
			
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$result = $wpdb->insert(
				$this->lists_table,
				[
					'name'       => $name,
					'urls'       => $urls,
					'created_at' => current_time( 'mysql' ),
					'updated_at' => current_time( 'mysql' ),
				],
				[ '%s', '%s', '%s', '%s' ]
			);

			if ( $result ) {
				wp_send_json_success();
			} else {
				// Get the actual database error
				$error_message = $wpdb->last_error ? $wpdb->last_error : __( 'Failed to save list.', 'robs-bulk-wp-platform-checker' );
				wp_send_json_error( [ 'message' => $error_message ] );
			}
		}

		/**
		 * AJAX: Load list
		 *
		 * @return void
		 */
		public function ajax_load_list() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( [ 'message' => __( 'Permission denied.', 'robs-bulk-wp-platform-checker' ) ], 403 );
			}
			check_ajax_referer( 'wppc_nonce' );

			$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

			if ( ! $id ) {
				wp_send_json_error( [ 'message' => __( 'Invalid list ID.', 'robs-bulk-wp-platform-checker' ) ] );
			}

			global $wpdb;
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$list = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->lists_table} WHERE id = %d", $id ) );

			if ( $list ) {
				wp_send_json_success( [ 'name' => $list->name, 'urls' => $list->urls ] );
			} else {
				wp_send_json_error( [ 'message' => __( 'List not found.', 'robs-bulk-wp-platform-checker' ) ] );
			}
		}

		/**
		 * AJAX: Delete list
		 *
		 * @return void
		 */
		public function ajax_delete_list() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( [ 'message' => __( 'Permission denied.', 'robs-bulk-wp-platform-checker' ) ], 403 );
			}
			check_ajax_referer( 'wppc_nonce' );

			$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

			if ( ! $id ) {
				wp_send_json_error( [ 'message' => __( 'Invalid list ID.', 'robs-bulk-wp-platform-checker' ) ] );
			}

			global $wpdb;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$result = $wpdb->delete( $this->lists_table, [ 'id' => $id ], [ '%d' ] );

			if ( $result ) {
				wp_send_json_success();
			} else {
				wp_send_json_error( [ 'message' => __( 'Failed to delete list.', 'robs-bulk-wp-platform-checker' ) ] );
			}
		}

		/**
		 * AJAX: Get all lists
		 *
		 * @return void
		 */
		public function ajax_get_lists() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( [ 'message' => __( 'Permission denied.', 'robs-bulk-wp-platform-checker' ) ], 403 );
			}
			check_ajax_referer( 'wppc_nonce' );

			global $wpdb;
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
			$lists = $wpdb->get_results( "SELECT id, name, created_at, updated_at FROM {$this->lists_table} ORDER BY updated_at DESC" );

			wp_send_json_success( $lists );
		}

		/**
		 * AJAX: Clear cache
		 *
		 * @return void
		 */
		public function ajax_clear_cache() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( [ 'message' => __( 'Permission denied.', 'robs-bulk-wp-platform-checker' ) ], 403 );
			}
			check_ajax_referer( 'wppc_nonce' );

			global $wpdb;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_wppc_cache_%' OR option_name LIKE '_transient_timeout_wppc_cache_%'" );

			wp_send_json_success();
		}

		/**
		 * Get cached result
		 *
		 * @param string $url URL to check.
		 * @return array|false
		 */
		private function get_from_cache( $url ) {
			$cache_key = 'wppc_cache_' . md5( $url );
			$cached    = get_transient( $cache_key );
			
			// Validate cached phone data - remove if it contains a dot
			if ( ! empty( $cached['contact_phone'] ) && strpos( $cached['contact_phone'], '.' ) !== false ) {
				$cached['contact_phone'] = null;
			}
			
			return $cached;
		}

		/**
		 * Save to cache
		 *
		 * @param string $url   URL.
		 * @param array  $data  Detection data.
		 * @return void
		 */
		private function save_to_cache( $url, $data ) {
			$cache_key = 'wppc_cache_' . md5( $url );
			set_transient( $cache_key, $data, 7 * DAY_IN_SECONDS );
		}

		/**
		 * Save to history
		 *
		 * @param string $url   URL.
		 * @param array  $data  Detection data.
		 * @return void
		 */
		private function save_to_history( $url, $data ) {
			global $wpdb;
			
			// Ensure table exists
			$this->maybe_create_tables();

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->insert(
				$this->history_table,
				[
					'url'           => $url,
					'is_wordpress'  => ! empty( $data['is_wordpress'] ) ? 1 : 0,
					'inconclusive'  => ! empty( $data['inconclusive'] ) ? 1 : 0,
					'confidence'    => $data['confidence'] ?? null,
					'reason'        => $data['reason'] ?? null,
					'theme'         => $data['theme'] ?? null,
					'plugins'       => ! empty( $data['plugins'] ) ? implode( ',', $data['plugins'] ) : null,
					'wp_version'    => $data['wp_version'] ?? null,
					'contact_email' => $data['contact_email'] ?? null,
					'contact_phone' => $data['contact_phone'] ?? null,
					'checked_at'    => current_time( 'mysql' ),
				],
				[ '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ]
			);
		}

		/**
		 * Detect WordPress on a site
		 *
		 * @param string $url       URL to check.
		 * @param bool   $deep_scan Whether to perform deep scan.
		 * @return array
		 */
		private function detect_wordpress( $url, $deep_scan = false ) {
			$args = [
				'timeout'     => 10,
				'redirection' => 5,
				'user-agent'  => $this->user_agent(),
				'headers'     => [ 'Accept' => 'text/html,application/json;q=0.9,*/*;q=0.8' ],
			];

			$result = [
				'is_wordpress' => false,
				'inconclusive' => false,
				'confidence'   => 'low',
				'reason'       => '',
				'theme'        => null,
				'plugins'      => [],
				'wp_version'   => null,
			];

			// 1) HEAD request: look for Link header to wp-json
			$head = wp_safe_remote_head( $url, $args );
			if ( ! is_wp_error( $head ) ) {
				$status = (int) wp_remote_retrieve_response_code( $head );
				$link   = wp_remote_retrieve_header( $head, 'link' );

				if ( $status && $link && is_string( $link ) && strpos( $link, 'wp-json' ) !== false ) {
					$result['is_wordpress'] = true;
					$result['confidence']   = 'high';
					$result['reason']       = __( 'HTTP Link header advertises the WordPress REST API.', 'robs-bulk-wp-platform-checker' );
					
					if ( $deep_scan ) {
						$this->deep_scan_site( $url, $result, $args );
					}
					
					return $result;
				}
			}

			// 2) REST API endpoint probe
			$rest_urls = [
				rtrim( $url, '/' ) . '/wp-json/',
				rtrim( $url, '/' ) . '/?rest_route=/',
			];
			
			foreach ( $rest_urls as $rest ) {
				$get = wp_safe_remote_get( $rest, $args );
				if ( ! is_wp_error( $get ) ) {
					$code = (int) wp_remote_retrieve_response_code( $get );
					$body = wp_remote_retrieve_body( $get );
					if ( $code >= 200 && $code < 300 && $this->looks_like_wp_rest( $body ) ) {
						$result['is_wordpress'] = true;
						$result['confidence']   = 'high';
						$result['reason']       = __( 'WordPress REST API detected.', 'robs-bulk-wp-platform-checker' );
						
						if ( $deep_scan ) {
							$this->deep_scan_site( $url, $result, $args );
						}
						
						return $result;
					}
				}
			}

			// 3) Home page HTML markers
			$home = wp_safe_remote_get( $url, $args );
			if ( ! is_wp_error( $home ) ) {
				$code = (int) wp_remote_retrieve_response_code( $home );
				$body = wp_remote_retrieve_body( $home );
				
				if ( $code >= 200 && $code < 400 && is_string( $body ) && $body !== '' ) {
					// Check for WordPress markers
					$markers = [
						'#wp-content/#i',
						'#wp-includes/#i',
						'#<meta[^>]+name=["\']generator["\'][^>]+content=["\']WordPress[^>]*>#i',
						'#wp-emoji-release\.min\.js#i',
						'#/wp-json/?#i',
						'#Disallow:\s*/wp-admin/#i', // robots.txt pattern in HTML comments
					];
					
					foreach ( $markers as $re ) {
						if ( preg_match( $re, $body ) ) {
							$result['is_wordpress'] = true;
							$result['confidence']   = 'medium';
							$result['reason']       = __( 'Found common WordPress markers in HTML.', 'robs-bulk-wp-platform-checker' );
							
							if ( $deep_scan ) {
								$this->deep_scan_site( $url, $result, $args, $body );
							}
							
							return $result;
						}
					}
				}
			}

			// 4) Login page patterns
			$login = wp_safe_remote_get( rtrim( $url, '/' ) . '/wp-login.php', $args );
			if ( ! is_wp_error( $login ) ) {
				$code = (int) wp_remote_retrieve_response_code( $login );
				$body = wp_remote_retrieve_body( $login );
				
				if ( $code >= 200 && $code < 400 && 
					 preg_match( '#<form[^>]*id=["\']loginform["\']#i', $body ) && 
					 preg_match( '#name=["\']log["\']#i', $body ) ) {
					$result['is_wordpress'] = true;
					$result['confidence']   = 'medium';
					$result['reason']       = __( 'Default WordPress login form detected.', 'robs-bulk-wp-platform-checker' );
					
					if ( $deep_scan ) {
						$this->deep_scan_site( $url, $result, $args );
					}
					
					return $result;
				}
			}

			// 5) Check for xmlrpc.php
			$xmlrpc = wp_safe_remote_get( rtrim( $url, '/' ) . '/xmlrpc.php', $args );
			if ( ! is_wp_error( $xmlrpc ) ) {
				$code = (int) wp_remote_retrieve_response_code( $xmlrpc );
				$body = wp_remote_retrieve_body( $xmlrpc );
				
				if ( $code === 200 && stripos( $body, 'XML-RPC' ) !== false ) {
					$result['is_wordpress'] = true;
					$result['confidence']   = 'medium';
					$result['reason']       = __( 'WordPress XML-RPC endpoint detected.', 'robs-bulk-wp-platform-checker' );
					
					if ( $deep_scan ) {
						$this->deep_scan_site( $url, $result, $args );
					}
					
					return $result;
				}
			}

			// 6) Check robots.txt
			$robots = wp_safe_remote_get( rtrim( $url, '/' ) . '/robots.txt', $args );
			if ( ! is_wp_error( $robots ) ) {
				$code = (int) wp_remote_retrieve_response_code( $robots );
				$body = wp_remote_retrieve_body( $robots );
				
				if ( $code === 200 && preg_match( '#Disallow:\s*/wp-admin/#i', $body ) ) {
					$result['is_wordpress'] = true;
					$result['confidence']   = 'low';
					$result['reason']       = __( 'WordPress patterns found in robots.txt.', 'robs-bulk-wp-platform-checker' );
					
					if ( $deep_scan ) {
						$this->deep_scan_site( $url, $result, $args );
					}
					
					return $result;
				}
			}

			// If we got this far, either it is not WordPress or we cannot tell
			if ( is_wp_error( $home ) && is_wp_error( $login ) ) {
				$result['inconclusive'] = true;
				$result['reason']       = __( 'The site did not respond or blocked requests. Could not determine.', 'robs-bulk-wp-platform-checker' );
			} else {
				$result['reason'] = __( 'No clear WordPress indicators found.', 'robs-bulk-wp-platform-checker' );
			}

			return $result;
		}

		/**
		 * Perform deep scan to detect theme, plugins, and version
		 *
		 * @param string $url    URL.
		 * @param array  $result Result array to update.
		 * @param array  $args   Request args.
		 * @param string $body   Optional body if already fetched.
		 * @return void
		 */
		private function deep_scan_site( $url, &$result, $args, $body = null ) {
			if ( ! $body ) {
				$response = wp_safe_remote_get( $url, $args );
				if ( is_wp_error( $response ) ) {
					return;
				}
				$body = wp_remote_retrieve_body( $response );
			}

			if ( ! is_string( $body ) || empty( $body ) ) {
				return;
			}

			// Detect WordPress version
			if ( preg_match( '#<meta[^>]+name=["\']generator["\'][^>]+content=["\']WordPress\s+([\d.]+)[^>]*>#i', $body, $matches ) ) {
				$result['wp_version'] = $matches[1];
			}

			// Detect theme
			if ( preg_match( '#/wp-content/themes/([^/\'"]+)#i', $body, $matches ) ) {
				$result['theme'] = $matches[1];
			}

			// Detect plugins
			$plugins = [];
			if ( preg_match_all( '#/wp-content/plugins/([^/\'"]+)#i', $body, $matches ) ) {
				$plugins = array_unique( $matches[1] );
				// Limit to top 10 most common
				$plugins = array_slice( array_count_values( $plugins ), 0, 10 );
				$plugins = array_keys( $plugins );
			}
			$result['plugins'] = $plugins;
		}

		/**
		 * Harvest contact information from a WordPress site
		 *
		 * @param string $url Site URL.
		 * @return array
		 */
		private function harvest_contact_info( $url ) {
			$args = [
				'timeout'     => 10,
				'redirection' => 5,
				'user-agent'  => $this->user_agent(),
				'headers'     => [ 'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8' ],
			];

			$contact_data = [
				'contact_email' => null,
				'contact_phone' => null,
			];

			// Common contact page URLs to check
			$contact_pages = [
				'/contact',
				'/contact-us',
				'/about',
				'/about-us',
			];

			foreach ( $contact_pages as $page ) {
				$page_url  = rtrim( $url, '/' ) . $page;
				$response  = wp_safe_remote_get( $page_url, $args );
				
				if ( is_wp_error( $response ) ) {
					continue;
				}

				$code = (int) wp_remote_retrieve_response_code( $response );
				if ( $code !== 200 ) {
					continue;
				}

				$body = wp_remote_retrieve_body( $response );
				if ( empty( $body ) ) {
					continue;
				}

				// Extract email addresses
				if ( empty( $contact_data['contact_email'] ) && preg_match( '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $body, $email_matches ) ) {
					// Filter out common false positives
					$email = $email_matches[0];
					$excluded_patterns = [
						'example.com',
						'yoursite.com',
						'yourdomain.com',
						'placeholder',
						'noreply@',
						'no-reply@',
					];
					
					$is_valid = true;
					foreach ( $excluded_patterns as $pattern ) {
						if ( stripos( $email, $pattern ) !== false ) {
							$is_valid = false;
							break;
						}
					}
					
					if ( $is_valid ) {
						$contact_data['contact_email'] = sanitize_email( $email );
					}
				}

				// Extract phone numbers (various formats)
				if ( empty( $contact_data['contact_phone'] ) ) {
					// Remove script tags, style tags, and their content to avoid false matches
					$clean_body = preg_replace( '/<script\b[^>]*>(.*?)<\/script>/is', '', $body );
					$clean_body = preg_replace( '/<style\b[^>]*>(.*?)<\/style>/is', '', $clean_body );
					
					// Phone patterns - looking for numbers with at least SOME formatting
					$phone_patterns = [
						// With context label and any common separators
						'/(?:phone|tel|telephone|call|mobile|cell)[\s:]+(\+?[\d\s\-\(\)]{10,20})/i',
						// International with +
						'/(\+\d{1,3}[\s\-\(\)]?\d{2,4}[\s\-\(\)]?\d{3,4}[\s\-\(\)]?\d{3,4})/',
						// US/UK format with brackets (xxx) xxx-xxxx or similar
						'/(\(\d{3,4}\)[\s\-]?\d{3,4}[\s\-]?\d{4})/',
						// Standard with dashes xxx-xxx-xxxx
						'/(\d{3,4}\-\d{3,4}\-\d{4})/',
						// Standard with spaces xxx xxx xxxx
						'/(\d{3,4}\s\d{3,4}\s\d{4})/',
					];

					foreach ( $phone_patterns as $pattern ) {
						if ( preg_match( $pattern, $clean_body, $phone_matches ) ) {
							$phone = isset( $phone_matches[1] ) ? trim( $phone_matches[1] ) : trim( $phone_matches[0] );
							
							// Remove any HTML tags that might have been caught
							$phone = wp_strip_all_tags( $phone );
							$phone = trim( $phone );
							
							// Skip if empty after cleaning
							if ( empty( $phone ) ) {
								continue;
							}
							
							// CRITICAL: No dots allowed (this catches decimals/version numbers)
							if ( strpos( $phone, '.' ) !== false ) {
								continue;
							}
							
							// Clean up excessive whitespace
							$phone = preg_replace( '/\s+/', ' ', $phone );
							
							// Count digits
							$digits_only = preg_replace( '/\D/', '', $phone );
							$digit_count = strlen( $digits_only );
							
							// Valid phone: 7-15 digits
							if ( $digit_count >= 7 && $digit_count <= 15 ) {
								// Must have at least ONE separator character OR start with +
								// This prevents pure digit strings like "8765543345567"
								if ( preg_match( '/[\s\-\(\)]/', $phone ) || substr( $phone, 0, 1 ) === '+' ) {
									$contact_data['contact_phone'] = sanitize_text_field( $phone );
									break;
								}
							}
						}
					}
				}

				// If we found both email and phone, no need to check more pages
				if ( $contact_data['contact_email'] && $contact_data['contact_phone'] ) {
					break;
				}
			}

			// Final validation - remove phone if it contains a dot (safety check)
			if ( ! empty( $contact_data['contact_phone'] ) && strpos( $contact_data['contact_phone'], '.' ) !== false ) {
				$contact_data['contact_phone'] = null;
			}

			return $contact_data;
		}

		/**
		 * Check if response looks like WP REST API
		 *
		 * @param string $body Response body.
		 * @return bool
		 */
		private function looks_like_wp_rest( $body ) {
			if ( ! is_string( $body ) || $body === '' ) {
				return false;
			}
			
			$json = json_decode( $body, true );
			if ( ! is_array( $json ) ) {
				return false;
			}
			
			// Common WP REST root keys: name, description, namespaces, routes
			return isset( $json['namespaces'] ) || isset( $json['routes']['/'] ) || isset( $json['name'] );
		}

		/**
		 * Get user agent string
		 *
		 * @return string
		 */
		private function user_agent() {
			return 'WP-Platform-Checker/' . $this->version . ' (+https://wordpress.org; admin)';
		}

		/**
		 * Normalize URL
		 *
		 * @param string $input Input URL.
		 * @return string
		 */
		private function normalise_url( $input ) {
			$input = trim( $input );
			if ( $input === '' ) {
				return '';
			}
			
			if ( ! preg_match( '#^https?://#i', $input ) ) {
				$input = 'https://' . $input;
			}
			
			$parts = wp_parse_url( $input );
			if ( empty( $parts['host'] ) ) {
				return '';
			}

			// Disallow dangerous schemes and obvious local addresses
			if ( isset( $parts['scheme'] ) && ! in_array( strtolower( $parts['scheme'] ), [ 'http', 'https' ], true ) ) {
				return '';
			}

			$host = $parts['host'];
			if ( filter_var( $host, FILTER_VALIDATE_IP ) ) {
				if ( $this->is_private_ip( $host ) ) {
					return '';
				}
			}

			// Rebuild URL (strip fragments)
			$rebuilt = strtolower( $parts['scheme'] ?? 'https' ) . '://' . $host;
			if ( ! empty( $parts['port'] ) ) {
				$rebuilt .= ':' . (int) $parts['port'];
			}
			$rebuilt .= isset( $parts['path'] ) ? rtrim( $parts['path'], '/' ) : '';
			if ( ! empty( $parts['query'] ) ) {
				$rebuilt .= '?' . $parts['query'];
			}
			
			return $rebuilt;
		}

		/**
		 * Check if IP is private
		 *
		 * @param string $ip IP address.
		 * @return bool
		 */
		private function is_private_ip( $ip ) {
			if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
				$long = ip2long( $ip );
				$private = (
					( $long >= ip2long( '10.0.0.0' ) && $long <= ip2long( '10.255.255.255' ) ) ||
					( $long >= ip2long( '172.16.0.0' ) && $long <= ip2long( '172.31.255.255' ) ) ||
					( $long >= ip2long( '192.168.0.0' ) && $long <= ip2long( '192.168.255.255' ) ) ||
					( $long >= ip2long( '127.0.0.0' ) && $long <= ip2long( '127.255.255.255' ) ) ||
					( $long >= ip2long( '169.254.0.0' ) && $long <= ip2long( '169.254.255.255' ) )
				);
				return $private;
			}
			
			// Basic check for IPv6 loopback/link-local
			if ( $ip === '::1' ) {
				return true;
			}
			if ( stripos( $ip, 'fe80:' ) === 0 ) {
				return true;
			}
			
			return false;
		}
	}

	/**
	 * Activation hook - create database tables
	 */
	function wppc_activate() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		
		$history_table = $wpdb->prefix . 'wppc_history';
		$lists_table = $wpdb->prefix . 'wppc_lists';

		// History table
		$sql_history = "CREATE TABLE IF NOT EXISTS {$history_table} (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			url varchar(500) NOT NULL,
			is_wordpress tinyint(1) NOT NULL DEFAULT 0,
			inconclusive tinyint(1) NOT NULL DEFAULT 0,
			confidence varchar(20) DEFAULT NULL,
			reason text DEFAULT NULL,
			theme varchar(200) DEFAULT NULL,
			plugins text DEFAULT NULL,
			wp_version varchar(20) DEFAULT NULL,
			contact_email varchar(200) DEFAULT NULL,
			contact_phone varchar(50) DEFAULT NULL,
			checked_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY url (url(191)),
			KEY checked_at (checked_at)
		) $charset_collate;";

		// Saved lists table
		$sql_lists = "CREATE TABLE IF NOT EXISTS {$lists_table} (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			name varchar(200) NOT NULL,
			urls longtext NOT NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql_history );
		dbDelta( $sql_lists );
	}

	register_activation_hook( __FILE__, 'wppc_activate' );

	// Instantiate the plugin
	new WP_Platform_Checker();
}
