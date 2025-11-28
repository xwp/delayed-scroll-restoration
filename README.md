# Delayed Scroll Restoration

A WordPress plugin that improves page navigation by intelligently managing scroll position restoration. The plugin waits for dynamic elements to load before restoring scroll position, preventing layout shift and improving user experience.

## Features

- **Intelligent Scroll Restoration**: Waits for dynamic elements (like ads, lazy-loaded content) to load before restoring scroll position
- **Safelist Support**: Define elements that, when present, allow immediate scroll restoration
- **BFCache Compatible**: Uses modern browser APIs (`pagehide`, `visibilitychange`) for proper Back-Forward Cache support
- **Performance Optimized**: Minimal impact on Core Web Vitals (INP, CLS)
- **Flexible Configuration**: Control timing, selectors, and page targeting from WordPress admin

## Requirements

- **PHP**: 8.1 or higher
- **WordPress**: 6.3 or higher
- Compatible with WordPress VIP coding standards

## Installation

1. Upload the `delayed-scroll-restoration` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure settings at **Settings → Delayed Scroll Restoration**

### Composer Installation

To install the plugin via Composer, follow these steps:

1. **Add the Repository:**
   - Open your project's `composer.json` file.
   - Add the following under the `repositories` section:

     ```json
     "repositories": [
         {
             "type": "vcs",
             "url": "https://github.com/xwp/delayed-scroll-restoration"
         }
     ]
     ```

2. **Require the Plugin:**
   - Run the following command in your terminal:

     ```bash
     composer require xwp/delayed-scroll-restoration
     ```

3. **Activate the Plugin:**
   - Once installed, activate the plugin through the 'Plugins' menu in WordPress.

## Configuration

Navigate to **Settings → Delayed Scroll Restoration** in your WordPress admin to configure:

### Safelist CSS Selectors
CSS selectors that, when found on page load, allow immediate scroll restoration. This is useful for elements that are always present and indicate the page is ready.

**Examples:**
```
#header
.nav-menu
[data-loaded="true"]
```

### Dynamically Injected Element Selectors
CSS selectors for elements that are injected dynamically (ads, lazy-loaded content). The plugin waits for any of these to appear before restoring scroll.

**Examples:**
```
#ad-container
.lazy-loaded
[data-ad-slot]
.content-loader
```

> **Note:** When this field is empty, the script will not output at all.

### Scroll Delay
Time in milliseconds to wait after the dynamic element appears before scrolling. Default: `100`

### Observer Timeout
Maximum time in milliseconds the MutationObserver will watch before disconnecting and restoring scroll anyway. Default: `10000`

### Single Posts Only
When enabled, delayed scroll restoration only applies to single post pages. When disabled, it applies to all pages.

## How It Works

1. **On Page Load**: The plugin disables the browser's automatic scroll restoration and resets scroll to top
2. **Priority Check**: Checks if any safelist selectors are present
   - If found: Restores scroll immediately (after configured delay)
   - If not found: Proceeds to observe for dynamic selectors
3. **Dynamic Observation**: Uses MutationObserver to watch for dynamically injected elements
4. **Scroll Restoration**: Once a dynamic element appears (or timeout occurs), waits for the configured delay, then smoothly restores scroll position
5. **On Navigation**: Saves scroll position to sessionStorage using BFCache-compatible events

## Technical Details

- **Storage**: Uses `sessionStorage` with page URL as key
- **Selectors**: Combines multiple selectors using `:is()` syntax for optimal performance
- **Events**: Uses `pagehide` for BFCache compatibility with `visibilitychange` fallback
- **Smooth Scrolling**: Provides smooth scroll behavior with fallbacks for older browsers
- **Anchor Links**: Handles hash fragments when no saved scroll position exists
- **Auto-cleanup**: Observer automatically disconnects after finding elements or reaching timeout

## Performance Considerations

- Inline script in `<head>` for immediate execution
- No external HTTP requests
- Optimized DOM checking using combined selectors
- Early exit patterns in mutation observer
- Automatic cleanup via observer disconnection
- Minimal impact on Interaction to Next Paint (INP)

## Browser Compatibility

- Modern browsers with MutationObserver support
- BFCache-compatible browsers (Chrome 96+, Firefox 86+, Safari 10.1+)
- Graceful degradation for older browsers

## Support

For issues, feature requests, or contributions, visit [XWP](https://xwp.co/)

## License

See [LICENSE](./LICENSE) file for details.

## Credits

Developed by [XWP](https://xwp.co/)
