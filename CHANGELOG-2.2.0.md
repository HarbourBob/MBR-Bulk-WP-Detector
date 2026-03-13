# MBR Bulk WP Detector - Version 2.2.0 Changelog

## New Features

### Contact Information Harvesting
- Added optional contact harvesting feature for discovered WordPress sites
- When enabled, the plugin attempts to extract:
  - Contact name
  - Email address
  - Phone number

### How It Works
- **Toggle Control**: New checkbox "Harvest contact information (name, email, phone)" in Detection Options
- **Smart Extraction**: Searches common pages (/contact, /contact-us, /about, /about-us)
- **Pattern Matching**: Uses context-aware regex patterns to identify email addresses, phone numbers, and contact names
- **Validation**: Filters out common false positives (example.com, noreply@, placeholder emails, version numbers)
- **International Support**: Recognizes phone number formats from US/CA, UK, and international formats

### Database Updates
- Added three new columns to the history table:
  - `contact_name` (varchar 200)
  - `contact_email` (varchar 200)
  - `contact_phone` (varchar 50)

### UI Enhancements
- Contact information displays in the results table when available
- Email addresses are clickable mailto: links
- Added friendly disclaimer: "Note: Contact details may not always be available or accurate. This feature searches publicly accessible pages."
- Contact fields included in CSV exports (when "Include deep scan data" is checked)
- Export options now always visible (no longer hidden)
- Export buttons now work with single click (improved UX)

### CSV Export Updates
- Added three new columns to CSV export:
  - Contact Name
  - Email
  - Phone
- Contact data only included when "Include deep scan data" option is enabled
- Updated export checkbox label to clarify included data

## Bug Fixes

### Phone Number Extraction
- Fixed issue where version numbers (e.g., "2.333333") were incorrectly identified as phone numbers
- Improved regex patterns to look for phone context (phone:, tel:, call:, etc.)
- Added validation to exclude decimal numbers and version strings
- Better digit count validation (7-15 digits only)

### CSV Export UX
- Fixed confusing double-click requirement for CSV export
- Export options now always visible instead of hidden toggle
- Single click now triggers export immediately
- Clearer checkbox labels showing what data is included

## Technical Details

### Contact Extraction Logic
1. Checks multiple common contact pages per site
2. Stops searching once all three contact fields are found
3. Sanitizes all extracted data
4. Validates email formats and phone number structures
5. Excludes common placeholder/spam emails
6. Uses context-aware phone number detection

### Phone Number Improvements
- Looks for phone numbers near labels like "phone:", "tel:", "call:", "contact:"
- Validates against decimal/version number patterns
- Supports multiple international formats with proper validation
- Cleans up whitespace while preserving formatting

### Performance Considerations
- Contact harvesting only runs when explicitly enabled via checkbox
- Uses same timeout settings as main detection (10 seconds)
- Checks up to 4 common pages per site
- Early termination when all contact info is found

## Important Notes

### Privacy & Accuracy
- **Public Data Only**: This feature only extracts information that is publicly accessible on websites
- **No Guarantees**: Contact information may not always be available or may be incomplete
- **Accuracy Varies**: Extraction accuracy depends on website structure and formatting
- **User Discretion**: Users should verify extracted contact information before use

### Compliance Considerations
- Contact information is scraped from public pages
- Users should ensure compliance with applicable privacy laws (GDPR, etc.)
- Recommended to add a disclaimer when using this data for outreach

## Upgrade Notes

### Database Migration
- Existing tables will be automatically updated with new columns
- Existing records will have NULL values for contact fields
- No data loss will occur during upgrade

### Backward Compatibility
- All existing features remain unchanged
- Contact harvesting is OFF by default
- CSV exports without deep scan data remain unchanged

## Files Modified
- `mbr-bulk-wp-checker.php` - Main plugin file
  - Added `harvest_contact_info()` method with improved phone detection
  - Updated database schema
  - Enhanced AJAX handler
  - Modified CSV export function
  - Updated UI with new toggle and disclaimer
  - Fixed export button UX issues

## Version Information
- **Version**: 2.2.0
- **Release Date**: March 14, 2026
- **Tested Up To**: WordPress 6.9
- **Requires PHP**: 7.2+
