=== MBR Bulk WP Detector ===
Contributors: robertpalmer
Donate link: https://littlewebshack.com/about/
Tags: wordpress detector, bulk checker, lead generation, platform detection, site analyzer
Requires at least: 5.0
Tested up to: 6.9
Stable tag: 2.3.0
Requires PHP: 7.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Bulk detect WordPress installations across unlimited URLs. Perfect for agencies, marketers, and developers to qualify leads and build prospect lists.

== Description ==

**Transform your lead qualification process with bulk WordPress detection!**

MBR Bulk WP Detector is a powerful tool designed for agencies, marketers, developers, and freelancers who need to quickly identify which websites are running WordPress. Upload hundreds or thousands of URLs and get instant results with detailed insights about themes, plugins, widgets, and versions. It also scans for company name, phone number and email address.

= Perfect For =

* **Marketing Agencies** - Build qualified prospect lists for WordPress-focused campaigns
* **Web Developers** - Find potential clients who need plugin/theme services
* **Freelancers** - Identify WordPress sites for targeted outreach
* **SEO Professionals** - Discover partnership opportunities with WordPress sites
* **Lead Generation** - Qualify leads from any source (ChatGPT, scraping tools, etc.)

= Key Features =

**Detection Capabilities:**

* 6 detection methods (REST API, link headers, HTML markers, login pages, XML-RPC, robots.txt)
* Deep scan mode for WordPress version, theme, and plugin detection
* Confidence level scoring (High/Medium/Low)
* Smart detection algorithms with 99%+ accuracy

**Bulk Operations:**

* Unlimited URL checking
* CSV/TXT file upload support
* Paste URLs directly (line-by-line or comma-separated)
* Save and load URL lists for repeat campaigns
* Remove duplicates automatically
* Filter by TLD (.com, .org, .edu, etc.)

**Performance & Speed:**

* Concurrent request processing (1-20 simultaneous checks)
* Smart caching system (7-day retention)
* 24x faster on cached results
* Configurable request delays
* Real-time progress tracking with time estimates

**Results Management:**

* Filter results (All/WordPress/Not WP/Inconclusive)
* Sortable columns (URL, status, confidence, cache age)
* Copy WordPress URLs to clipboard instantly
* Export to CSV or JSON
* Statistics dashboard with visual metrics
* Historical tracking database

**Professional Interface:**

* Modern two-column layout
* Color-coded status badges
* Mobile-responsive design
* Keyboard shortcuts (Ctrl+Enter to check)
* URL validation before checking
* Cache management controls

**Security & Privacy:**

* 100% private - no external API calls
* All processing happens on your server
* Private IP blocking for security
* Proper WordPress nonces
* Full input sanitization
* Capability checks on all actions

= Why Choose This Plugin? =

**vs. Online Checkers:**

* **FREE** (they charge $29-99/month)
* **Unlimited URLs** (they limit to 10-100)
* **100% Private** (they collect your data)
* **Deep Scanning** (they rarely offer this)
* **Bulk Import** (CSV support included)
* **Saved Lists** (manage multiple campaigns)
* **No Subscriptions** (one-time install)

**Performance Gains:**

* Check 100 URLs: 4 min → 2 min (2x faster)
* Re-check 100 URLs: 4 min → 10 sec (24x faster with cache)
* Single cached URL: Instant (0.1 seconds)

= Real-World Use Cases =

**Lead Qualification:**
Upload 500 prospect URLs, get WordPress sites identified in 2 minutes instead of checking each manually.

**Competitor Analysis:**
Check 100 competitors, export the themes and plugins they're using for market research.

**Personalized Outreach:**
Detect themes/plugins to craft personalized pitches instead of generic outreach emails.

**Monthly Re-checks:**
Load saved URL lists and get instant results from cache - no re-checking needed.

**Client Reporting:**
Export comprehensive CSV reports with full detection data and statistics.

= Technical Details =

**Detection Methods:**

1. REST API endpoint check
2. Link header analysis (wp-json)
3. HTML content markers (wp-content, wp-includes)
4. Login page pattern detection
5. XML-RPC endpoint verification
6. Robots.txt pattern matching

**Deep Scan Detection:**

* WordPress version number
* Active theme name
* Top 10 installed plugins
* Widgets in use (classic sidebar widgets, Gutenberg blocks, and page builder widgets for Elementor, Bricks, Beaver Builder, Divi, WPBakery, and Oxygen)
* Generator meta tags

**System Requirements:**

* WordPress 5.0 or higher
* PHP 7.2 or higher
* cURL enabled
* file_get_contents enabled
* Basic server resources (standard shared hosting works fine)

= Documentation =

Full documentation included with installation covering:

* Quick start guide
* Feature explanations
* Optimal settings recommendations
* Troubleshooting tips
* Performance optimization
* Best practices

== Installation ==

= Automatic Installation =

1. Log in to your WordPress admin panel
2. Go to Plugins → Add New
3. Search for "MBR Bulk WP Detector"
4. Click "Install Now" and then "Activate"
5. Navigate to Settings → WP Platform Checker
6. Start checking websites!

= Manual Installation =

1. Download the plugin ZIP file
2. Log in to your WordPress admin panel
3. Go to Plugins → Add New → Upload Plugin
4. Choose the ZIP file and click "Install Now"
5. Activate the plugin
6. Navigate to Settings → WP Platform Checker

= First Use =

1. Go to Settings → WP Platform Checker
2. Paste 5-10 test URLs (try: wordpress.org, google.com, facebook.com)
3. Enable "Deep scan" checkbox
4. Click "Check Websites"
5. Review the results and explore the features!

== Frequently Asked Questions ==

= How many URLs can I check at once? =

Unlimited! There's no hard limit. However, for best performance, we recommend batches of 500-1000 URLs at a time. Larger lists work fine but may take longer to process.

= Is this plugin secure? =

Absolutely. All processing happens on your server with no external API calls. We implement proper WordPress security practices including nonces, capability checks, input sanitization, and private IP blocking.

= How accurate is the detection? =

Very accurate! Our multi-method approach achieves 99%+ accuracy on identifying WordPress sites. Each result includes a confidence level (High/Medium/Low) so you know how reliable it is.

= Will this slow down my site? =

No! The plugin only runs when you're actively using it on the admin page. It doesn't affect your frontend performance at all. Caching ensures repeat checks are instant.

= Can I export the results? =

Yes! Export to CSV or JSON format. You can also copy just the WordPress URLs to your clipboard with one click.

= How does caching work? =

When you check a URL, the result is cached for 7 days. Re-checking that URL within 7 days returns instant results from cache. You can clear the cache anytime from the settings.

= What if a site blocks my checks? =

Some sites may block automated requests. The plugin handles timeouts gracefully and marks those sites as "Inconclusive" so you can manually verify them if needed.

= Can I save my URL lists? =

Yes! Save lists with custom names and load them later for repeat campaigns. Perfect for monthly re-checks or multiple client campaigns.

= Does deep scan work on all sites? =

Deep scan attempts to detect WordPress version, theme, plugins, and widgets. Success depends on whether the site exposes this information. Many sites do, but some hide it for security.

= Which widgets can this plugin detect? =

Deep scan identifies three categories of widgets: classic sidebar widgets (Search, Recent Posts, Categories, Nav Menu, etc.), Gutenberg blocks (including core and third-party namespaced blocks like `kadence/rowlayout`), and page builder widgets for the six major builders: Elementor, Bricks, Beaver Builder, Divi, WPBakery, and Oxygen. Results appear in a dedicated Widgets column and are included in CSV/JSON exports when "Include deep scan data" is enabled.

Note: classic sidebar widgets typically only render on blog/archive pages, so on sites with a static homepage the "Classic" group may come back empty. Gutenberg blocks and page builder widgets are detected reliably from the homepage because they render inline with the main content.

= What's the concurrency setting? =

Concurrency controls how many URLs are checked simultaneously. Higher values (8-15) are faster but use more resources. Lower values (1-5) are safer for shared hosting.

= Can I use this for other platforms? =

This plugin is specifically designed for WordPress detection. While it accurately identifies WordPress sites, it doesn't detect other CMSs like Joomla, Drupal, etc.

= Is there a URL limit per check? =

No artificial limits! The only limitation is your server resources. Standard shared hosting easily handles 500-1000 URLs per batch.

== Changelog ==
= 2.3.0 - Widget Detection =

* New: Deep scan now detects widgets in use on each site, covering classic sidebar widgets, Gutenberg blocks, and page builder widgets for Elementor, Bricks, Beaver Builder, Divi, WPBakery, and Oxygen
* New: Dedicated Widgets column in the results table, grouped by source with collapsible counts
* New: Widgets included in CSV exports (single column, pipe-separated by source) and JSON exports (structured object)
* New: `widgets` column added to the history table (automatic migration for existing installs)
* Zero extra HTTP requests: widget detection reuses the homepage HTML already fetched for theme and plugin detection, so scan times are unchanged
* Each page builder is gated by a signature check to reduce false positives from unrelated CSS classes
* Structural/grid classes filtered out (e.g. `et_pb_section`, `vc_col-md-6`, `fl-module-content`) so only real widget types are reported
* Widget types capped at 25 per source, sorted by frequency of appearance

= 2.2.0 - Added Phone & Email Harvesting

= 2.0.0 - November 2025 =

**Major Update - Complete Transformation**

*New Features (16):*

* Deep scan mode (detect themes, plugins, WP versions)
* Smart caching system (7-day retention, 24x faster)
* Save/load URL lists for campaigns
* CSV/TXT file upload support
* JSON export option
* Statistics dashboard with visual cards
* Result filtering (All/WordPress/Not WP/Inconclusive)
* Sortable table columns
* URL validation tool
* Automatic duplicate removal
* TLD filtering (.com, .org, .edu, etc.)
* Historical tracking database
* Copy WordPress URLs to clipboard
* Keyboard shortcuts (Ctrl+Enter)
* Concurrency control (1-20 requests)
* Request delay option

*Improvements (20+):*

* Added 2 new detection methods (XML-RPC, robots.txt)
* Complete UI redesign with professional two-column layout
* Color-coded status badges for quick identification
* Enhanced security (better sanitization, nonce verification)
* Private IP blocking for security
* Mobile-responsive design
* Real-time progress tracking with time estimates
* Better error handling and user feedback
* Improved timeout management
* Cache management interface
* PHPDoc comments throughout
* Comprehensive documentation
* Better URL parsing and normalization
* Enhanced confidence level calculations
* Optimized database queries
* Better memory management for large lists

*Bug Fixes (7):*

* Fixed fatal syntax error on line 264
* Fixed version number mismatch between header and class
* Improved URL validation and handling
* Fixed timeout issues with slow sites
* Better handling of malformed URLs
* Fixed export file naming
* Resolved cache clearing issues

*Performance:*

* 2x faster URL checking with concurrent requests
* 24x faster with cached results
* Reduced memory usage for large lists
* Optimized database operations

= 1.5.2 =
* Initial public release
* Basic WordPress detection
* Simple bulk checking interface
* CSV export functionality

== Upgrade Notice ==

= 2.3.0 =
Adds widget detection to deep scan. Identifies classic sidebar widgets, Gutenberg blocks, and page builder widgets (Elementor, Bricks, Beaver Builder, Divi, WPBakery, Oxygen) in a new Widgets column. Database migration runs automatically. Zero performance impact on scan times.

= 2.0.0 =
MAJOR UPDATE! Complete transformation with 16 new features, professional UI redesign, smart caching (24x faster!), deep scanning, and critical bug fixes. Backup recommended before upgrading. This is a game-changing update!

== Additional Information ==

= Performance Benchmarks =

* 10 URLs: ~5 seconds (first check), <1 second (cached)
* 100 URLs: ~2 minutes (first check), ~10 seconds (cached)
* 500 URLs: ~8 minutes (first check), ~30 seconds (cached)
* 1000 URLs: ~15 minutes (first check), ~60 seconds (cached)

*Times vary based on concurrency settings and server resources*

= Browser Compatibility =

* Chrome/Edge (recommended)
* Firefox
* Safari
* Opera
* All modern mobile browsers

= Server Requirements =

* Standard shared hosting works fine
* Dedicated/VPS for optimal performance with large lists
* 64MB PHP memory limit minimum (128MB+ recommended for 1000+ URLs)
* 30 second PHP execution time minimum (adjustable in settings)

= Privacy Policy =

This plugin does NOT:

* Collect any user data
* Make external API calls
* Track usage statistics
* Phone home to any servers
* Share information with third parties

All data stays on your WordPress installation.

= Support =

* Documentation: Included with plugin installation
* Issues: Visit https://littlewebshack.com for support

= Credits =

Developed by Robert Palmer at Little Web Shack
Visit: https://littlewebshack.com

= License =

This plugin is licensed under GPLv2 or later. You are free to use, modify, and distribute this plugin according to the GPL license terms.

== Developer Notes ==

= Hooks & Filters =

The plugin provides several hooks for developers:

**Actions:**
* `mbr_bulk_wp_detector_before_check` - Fires before URL checking begins
* `mbr_bulk_wp_detector_after_check` - Fires after URL checking completes
* `mbr_bulk_wp_detector_cache_cleared` - Fires when cache is cleared

**Filters:**
* `mbr_bulk_wp_detector_timeout` - Modify request timeout (default: 10 seconds)
* `mbr_bulk_wp_detector_user_agent` - Customize User-Agent string
* `mbr_bulk_wp_detector_cache_duration` - Adjust cache duration (default: 7 days)
* `mbr_bulk_wp_detector_detection_methods` - Add/modify detection methods

= Database Tables =

The plugin creates these custom tables:

* `{prefix}_mbr_wp_detector_results` - Stores detection results
* `{prefix}_mbr_wp_detector_cache` - Caches URL check results
* `{prefix}_mbr_wp_detector_lists` - Stores saved URL lists

