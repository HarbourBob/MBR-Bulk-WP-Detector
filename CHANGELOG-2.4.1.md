# MBR Bulk WP Detector - Version 2.4.1 Changelog

## Bug Fixes

### Bricks: Hex Element IDs Filtered
Bricks assigns every element on a page a unique 6-character identifier, and emits this identifier as a CSS class alongside the widget-type class. For example, a heading element renders with class `brxe-71510a brxe-heading`. Before this release, the detector captured both `brxe-` classes indiscriminately, so widget results were polluted with dozens of meaningless hex strings like `71510a`, `bdb11b`, `5349b0`, `6ba9d6`, etc. alongside the real widget types.

This release filters out anything matching the pattern `/^[0-9a-f]{6}$/` after Bricks extraction. This catches the vast majority of Bricks element IDs without risk of false positives, because no standard Bricks widget type name consists of 6 pure-hex characters (all real types contain letters outside `a-f`, e.g. `rating`, `slider`, `toggle`, `button`, `header`, `footer`).

### WPBakery: Timestamp IDs, Grid Classes, Utility Classes Filtered
WPBakery / Visual Composer emits several categories of non-widget CSS classes starting with `vc_` that were leaking into results:

- **Custom shortcode IDs** (`vc_custom_1749109182801`): WPBakery auto-generates millisecond-timestamp identifiers for custom shortcodes. These now filtered via pattern `/^custom_\d{6,}$/`.
- **Bootstrap 2 grid classes** (`vc_span1` through `vc_span12`): These are column width utilities from WPBakery's grid system, not widget types. Now filtered via pattern `/^span\d+$/`.
- **Utility/structural classes**: `responsive`, `hidden`, `shortcodes`, `shortcode`, `wrapper`, `content`, `element`, `general`, `clearfix` added to the exclude list.

### Cache Keyed by Scan Options
Previously, the cache key was derived only from the URL. This meant that if a user scanned a URL with deep scan on but inner pages off, then re-scanned the same URL with inner pages on, the cache returned the previous result (with no inner page data) instead of re-fetching.

Cache keys now include a deterministic options string covering deep scan, harvest contacts, and scan inner pages state. Different option combinations cache independently, eliminating this source of confusion.

**Note on existing caches**: Entries cached under the old key format (from v2.3.0 or v2.4.0) will become inaccessible but will be cleaned up naturally by WordPress's transient expiry (7 days). Users who want immediate fresh scans after upgrade can click "Clear All Cache" in the plugin's Cache Management section.

## Known Limitations

### Bricks Non-Hex Element IDs
Some Bricks installations (particularly newer versions) use 6-character alphanumeric element IDs that include letters beyond `a-f` (e.g. `crkler`, `oddoqh`). These are not filtered by the hex pattern and may still appear in results as noise.

Distinguishing them reliably from legitimate 6-character widget names (`rating`, `slider`, `toggle`, `button`, etc.) without over-filtering is non-trivial. A future release may add a secondary heuristic (e.g. syllable/vowel-density check) if this proves to be a widespread issue. In practice, a small number of IDs in results remains interpretable — the widget types are now clearly the dominant content of the list.

## Technical Details

### Bricks Filter Implementation
```php
$bricks = array_values( array_filter( $bricks, function ( $n ) {
    return ! preg_match( '/^[0-9a-f]{6}$/i', $n );
} ) );
```

### WPBakery Filter Implementation
Two-stage: expanded static exclude list plus pattern-based post-filter:
```php
$wpbakery = array_values( array_filter( $wpbakery, function ( $n ) {
    if ( preg_match( '/^custom_\d{6,}$/', $n ) ) return false;
    if ( preg_match( '/^span\d+$/', $n ) ) return false;
    return true;
} ) );
```

### Cache Options Key
```php
private function build_options_key( $deep_scan, $harvest_contacts, $scan_inner_pages ) {
    return 'd' . ( $deep_scan ? '1' : '0' )
        . 'h' . ( $harvest_contacts ? '1' : '0' )
        . 'i' . ( $scan_inner_pages ? '1' : '0' );
}
```

Cache key is then `md5( $url . '|' . $options_key )` prefixed with `wppc_cache_`.

## Files Modified
- `mbr-bulk-wp-checker.php` - Main plugin file
  - Added hex-ID filter to Bricks detection branch
  - Expanded WPBakery exclude list
  - Added pattern-based post-filter for WPBakery timestamp IDs and grid classes
  - `get_from_cache()` and `save_to_cache()` now accept `$options_key` parameter
  - Added `build_options_key()` helper
  - `ajax_check_site()` builds and passes options key to cache functions

## Upgrade Notes

### Backward Compatibility
- All existing features remain unchanged
- No database schema changes
- Cache entries from v2.4.0 and earlier become inaccessible (different key format) but will be cleaned up by transient expiry within 7 days
- Users can manually clear the cache to free storage immediately

## Regression Testing
The release was tested against the exact noise strings reported by users: 27 Bricks hex IDs and 6 WPBakery timestamp IDs were all correctly filtered, while 8 legitimate Bricks widget types and 5 legitimate WPBakery widget types were preserved. Separate sanity tests confirmed legitimate 6-character widget names (rating, slider, toggle, search, header, footer, button) are not filtered by the Bricks hex pattern.

## Version Information
- **Version**: 2.4.1
- **Release Date**: April 23, 2026
- **Tested Up To**: WordPress 6.9
- **Requires PHP**: 7.2+
