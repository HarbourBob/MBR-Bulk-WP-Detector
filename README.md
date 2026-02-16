# MBR Bulk WP Detector

[![GitHub Release](https://img.shields.io/github/v/release/harbourbob/MBR-Bulk-WP-Detector)](https://github.com/harbourbob/MBR-Bulk-WP-Detector/releases)
[![GitHub Downloads](https://img.shields.io/github/downloads/harbourbob/MBR-Bulk-WP-Detector/total)](https://github.com/harbourbob/MBR-Bulk-WP-Detector/releases)
[![GitHub Stars](https://img.shields.io/github/stars/harbourbob/MBR-Bulk-WP-Detector?style=social)](https://github.com/harbourbob/MBR-Bulk-WP-Detector)
[![GitHub Forks](https://img.shields.io/github/forks/harbourbob/MBR-Bulk-WP-Detector?style=social)](https://github.com/harbourbob/MBR-Bulk-WP-Detector)
[![GitHub Issues](https://img.shields.io/github/issues/harbourbob/MBR-Bulk-WP-Detector)](https://github.com/harbourbob/MBR-Bulk-WP-Detector/issues)

**Transform your lead qualification process with bulk WordPress detection!**

A powerful WordPress plugin designed for agencies, marketers, developers, and freelancers who need to quickly identify which websites are running WordPress. Upload hundreds or thousands of URLs and get instant results with detailed insights about themes, plugins, and versions.

---

## 🎯 Perfect For

- **Marketing Agencies** - Build qualified prospect lists for WordPress-focused campaigns
- **Web Developers** - Find potential clients who need plugin/theme services
- **Freelancers** - Identify WordPress sites for targeted outreach
- **SEO Professionals** - Discover partnership opportunities with WordPress sites
- **Lead Generation** - Qualify leads from any source (ChatGPT, scraping tools, etc.)

---

## ✨ Key Features

### Detection Capabilities

- **6 Detection Methods**: REST API, link headers, HTML markers, login pages, XML-RPC, robots.txt
- **Deep Scan Mode**: Detect WordPress version, themes, and plugins
- **Confidence Scoring**: High/Medium/Low accuracy indicators
- **99%+ Accuracy**: Smart algorithms with multi-method verification

### Bulk Operations

- ✅ **Unlimited URL Checking** - No artificial limits
- 📁 **CSV/TXT Upload** - Import large URL lists easily
- 📋 **Paste URLs** - Line-by-line or comma-separated
- 💾 **Save & Load Lists** - Manage multiple campaigns
- 🔄 **Auto Duplicate Removal** - Clean your lists automatically
- 🌐 **TLD Filtering** - Filter by .com, .org, .edu, etc.

### Performance & Speed

- ⚡ **Concurrent Processing**: 1-20 simultaneous checks
- 🗄️ **Smart Caching**: 7-day retention, 24x faster on repeat checks
- 📊 **Real-time Progress**: Live tracking with time estimates
- ⏱️ **Configurable Delays**: Adjust request timing

### Results Management

- 🔍 **Filter Results**: All/WordPress/Not WP/Inconclusive
- 📊 **Sortable Columns**: URL, status, confidence, cache age
- 📋 **Copy to Clipboard**: WordPress URLs instantly
- 📤 **Export**: CSV or JSON formats
- 📈 **Statistics Dashboard**: Visual metrics and insights
- 🗃️ **Historical Tracking**: Database storage for all checks

---

## 🚀 Why Choose This Plugin?

### vs. Online Checkers

| Feature | MBR Bulk WP Detector | Online Services |
|---------|---------------------|-----------------|
| **Price** | FREE | $29-99/month |
| **URL Limit** | Unlimited | 10-100 URLs |
| **Privacy** | 100% Private | Data collection |
| **Deep Scanning** | ✅ Included | ❌ Rarely offered |
| **Bulk Import** | ✅ CSV support | Limited |
| **Saved Lists** | ✅ Multiple campaigns | Usually not included |
| **Subscription** | ❌ One-time install | ✅ Required |

### Performance Gains

- **100 URLs (First Check)**: 4 min → 2 min (2x faster)
- **100 URLs (Cached)**: 4 min → 10 sec (24x faster)
- **Single Cached URL**: Instant (0.1 seconds)

---

## 📦 Installation

### Automatic Installation

1. Log in to your WordPress admin panel
2. Go to **Plugins → Add New**
3. Search for "MBR Bulk WP Detector"
4. Click **Install Now** and then **Activate**
5. Navigate to **Settings → WP Platform Checker**
6. Start checking websites!

### Manual Installation

1. Download the plugin ZIP file from this repository
2. Log in to your WordPress admin panel
3. Go to **Plugins → Add New → Upload Plugin**
4. Choose the ZIP file and click **Install Now**
5. Activate the plugin
6. Navigate to **Settings → WP Platform Checker**

### Quick Start

1. Go to **Settings → WP Platform Checker**
2. Paste 5-10 test URLs (try: wordpress.org, google.com, facebook.com)
3. Enable "Deep scan" checkbox
4. Click **Check Websites**
5. Review the results and explore the features!

---

## 💡 Real-World Use Cases

### Lead Qualification
Upload 500 prospect URLs, get WordPress sites identified in 2 minutes instead of checking each manually.

### Competitor Analysis
Check 100 competitors, export the themes and plugins they're using for market research.

### Personalized Outreach
Detect themes/plugins to craft personalized pitches instead of generic outreach emails.

### Monthly Re-checks
Load saved URL lists and get instant results from cache - no re-checking needed.

### Client Reporting
Export comprehensive CSV reports with full detection data and statistics.

---

## 🔧 Technical Details

### Detection Methods

1. **REST API Endpoint Check** - Fastest and most reliable method
2. **Link Header Analysis** - Checks for wp-json headers
3. **HTML Content Markers** - Scans for wp-content, wp-includes
4. **Login Page Detection** - Identifies WordPress login patterns
5. **XML-RPC Verification** - Checks for WordPress XML-RPC endpoint
6. **Robots.txt Matching** - Analyzes robots.txt patterns

### Deep Scan Capabilities

When deep scan is enabled, the plugin detects:
- WordPress version number
- Active theme name
- Top 10 installed plugins
- Generator meta tags

### System Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher
- cURL enabled
- file_get_contents enabled
- Basic server resources (standard shared hosting works fine)

---

## 📊 Performance Benchmarks

| URLs | First Check | Cached Check |
|------|-------------|--------------|
| 10 URLs | ~5 seconds | <1 second |
| 100 URLs | ~2 minutes | ~10 seconds |
| 500 URLs | ~8 minutes | ~30 seconds |
| 1000 URLs | ~15 minutes | ~60 seconds |

*Times vary based on concurrency settings and server resources*

---

## 🔒 Security & Privacy

This plugin is designed with security and privacy as top priorities:

- ✅ **100% Private** - No external API calls
- ✅ **Server-side Processing** - All checks happen on your server
- ✅ **Private IP Blocking** - Security protection
- ✅ **WordPress Nonces** - Proper security implementation
- ✅ **Input Sanitization** - Full sanitization of all inputs
- ✅ **Capability Checks** - Proper permission verification

### What This Plugin Does NOT Do:

- ❌ Collect any user data
- ❌ Make external API calls
- ❌ Track usage statistics
- ❌ Phone home to any servers
- ❌ Share information with third parties

All data stays on your WordPress installation.

---

## 📖 Documentation

Full documentation is included with the plugin installation, covering:

- Quick start guide
- Feature explanations
- Optimal settings recommendations
- Troubleshooting tips
- Performance optimization
- Best practices

---

## 🎨 Screenshots

The plugin features a modern, professional interface:

- Two-column layout for easy workflow
- Color-coded status badges for quick identification
- Mobile-responsive design
- Real-time progress tracking
- Visual statistics dashboard

---

## 🔄 Changelog

### Version 2.1.0 - Latest Release
- Added phone & email harvesting capabilities

### Version 2.0.0 - Major Update

**New Features (16)**
- Deep scan mode (detect themes, plugins, WP versions)
- Smart caching system (7-day retention, 24x faster)
- Save/load URL lists for campaigns
- CSV/TXT file upload support
- JSON export option
- Statistics dashboard with visual cards
- Result filtering (All/WordPress/Not WP/Inconclusive)
- Sortable table columns
- URL validation tool
- Automatic duplicate removal
- TLD filtering
- Historical tracking database
- Copy WordPress URLs to clipboard
- Keyboard shortcuts (Ctrl+Enter)
- Concurrency control (1-20 requests)
- Request delay option

**Improvements (20+)**
- Added 2 new detection methods (XML-RPC, robots.txt)
- Complete UI redesign with professional two-column layout
- Color-coded status badges
- Enhanced security
- Private IP blocking
- Mobile-responsive design
- Real-time progress tracking
- Better error handling
- Improved timeout management
- Cache management interface
- PHPDoc comments throughout
- Comprehensive documentation

**Performance Improvements**
- 2x faster URL checking with concurrent requests
- 24x faster with cached results
- Reduced memory usage for large lists
- Optimized database operations

### Version 1.5.2
- Initial public release
- Basic WordPress detection
- Simple bulk checking interface
- CSV export functionality

---

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

---

## 📄 License

This plugin is licensed under the [GNU General Public License v2.0 or later](https://www.gnu.org/licenses/gpl-2.0.html).

You are free to use, modify, and distribute this plugin according to the GPL license terms.

---

## 👨‍💻 Developer

**Robert Palmer**  
[Little Web Shack](https://littlewebshack.com)  
Made by Robert

---

## 📞 Support & Contact

- **Documentation**: Included with plugin installation
- **Issues**: Visit [Little Web Shack](https://littlewebshack.com/contact/) for support
- **GitHub Issues**: Feel free to open an issue on this repository

---

## 🏷️ Keywords

WordPress detector, bulk checker, lead generation, platform detection, site analyzer, WordPress identification, bulk WordPress checker, website analyzer, theme detector, plugin detector, CMS detection, marketing tools, agency tools, lead qualification, prospect research
