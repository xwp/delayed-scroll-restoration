<?php
/**
 * Plugin Name: Delayed Scroll Restoration
 * Plugin URI: https://xwp.co/
 * Description: Delaying scroll restoration until dynamic elements load, to improve user experience through avoidance of layout instability ( CLS optimization ).
 * Version: 1.0.0
 * Requires PHP: 8.1
 * Requires at least: 6.3
 * Author: XWP
 * Author URI: https://xwp.co/
 *
 * @package XWP\ScrollRestoration
 */

namespace XWP\ScrollRestoration;

const MAIN_DIR = __DIR__;
const VERSION = '1.0.0';

// Include admin settings.
require_once MAIN_DIR . '/includes/admin-settings.php';

// Register hooks.
add_action(  'admin_menu', __NAMESPACE__ . '\register_settings_page'  );
add_action(  'admin_init', __NAMESPACE__ . '\register_settings'  );
add_action(  'wp_head', __NAMESPACE__ . '\output_scroll_script', PHP_INT_MAX  );
add_filter(  'plugin_action_links_' . plugin_basename(  __FILE__  ), __NAMESPACE__ . '\add_settings_link'  );

/**
 * Add "Settings" link to plugin action links.
 *
 * @param array $links Existing plugin action links.
 * @return array Modified plugin action links.
 */
function add_settings_link(  $links  ) {
	$settings_link = sprintf( 
		'<a href="%s">%s</a>',
		esc_url(  admin_url(  'options-general.php?page=delayed-scroll-restoration'  )  ),
		esc_html__(  'Settings', 'delayed-scroll-restoration'  )
	 );
	array_unshift(  $links, $settings_link  );
	return $links;
}

/**
 * Check if the scroll restoration script should be output.
 *
 * @return bool True if script should output, false otherwise.
 */
function should_output_script() {
	$settings = get_settings();

	// Don't output if no dynamic selectors configured.
	if (  empty(  trim(  $settings['dynamic_selectors']  )  )  ) {
		return false;
	}

	// Check single posts only setting.
	if (  $settings['single_posts_only'] && ! is_single()  ) {
		return false;
	}

	return true;
}

/**
 * Parse textarea input into array of selectors.
 *
 * @param string $selectors_text Newline-separated selectors.
 * @return array Array of non-empty, trimmed selectors.
 */
function get_parsed_selectors(  $selectors_text  ) {
	if (  empty(  $selectors_text  )  ) {
		return [];
	}

	$selectors = explode(  "\n", $selectors_text  );
	$selectors = array_map(  'trim', $selectors  );
	$selectors = array_filter(  $selectors, 'strlen'  );

	return array_values(  $selectors  );
}

/**
 * Build combined CSS selector using :is() syntax.
 *
 * @param array $selectors Array of CSS selectors.
 * @return string Combined selector or empty string.
 */
function build_combined_selector(  $selectors  ) {
	if (  empty(  $selectors  )  ) {
		return '';
	}

	if (  count(  $selectors  ) === 1  ) {
		return $selectors[0];
	}

	return ':is( ' . implode(  ', ', $selectors  ) . ' )';
}

/**
 * Output the scroll restoration script in the head.
 *
 * @return void
 */
function output_scroll_script() {
	// CHANGED: Added is_admin() check — Prevents script output in admin context where it's unnecessary and could interfere with admin functionality
	if ( is_admin() || ! should_output_script() ) {
		return;
	}

	$settings = get_settings();

	// Parse selectors.
	$dynamic_selectors = get_parsed_selectors(  $settings['dynamic_selectors']  );
	$target_selectors  = get_parsed_selectors(  $settings['target_selectors']  );

	// Build combined selectors.
	$combined_dynamic_selector = build_combined_selector(  $dynamic_selectors  );
	$combined_target_selector  = build_combined_selector(  $target_selectors  );

	// CHANGED: Added selector validation — Prevents potential CSS injection by ensuring selectors don't contain script tags or dangerous patterns
	if ( preg_match( '/<script|javascript:|data:/i', $combined_dynamic_selector . $combined_target_selector ) ) {
		return;
	}

	// Get timing values.
	$scroll_delay     = absint(  $settings['scroll_delay']  );
	$observer_timeout = absint(  $settings['observer_timeout']  );

	// Output inline script.
	?>
	<script id="delayed-scroll-restoration">
	( function() {
		'use strict';
		const SCROLL_TIMEOUT_MS = <?php echo esc_js(  $scroll_delay  ); ?>;
		const OBSERVER_TIMEOUT_MS = <?php echo esc_js(  $observer_timeout  ); ?>;
		const COMBINED_DYNAMIC_SELECTOR = <?php echo wp_json_encode(  $combined_dynamic_selector  ); ?>;
		const COMBINED_TARGET_SELECTOR = <?php echo wp_json_encode(  $combined_target_selector  ); ?>;
		const pageUrl = window.location.href;
		const savedScrollTop = sessionStorage.getItem( `scrollPosition_${pageUrl}` ) || 0;

		// Prevent browser's automatic scroll restoration
		if ( 'scrollRestoration' in history ) {
			history.scrollRestoration = 'manual';
		}
		window.scrollTo( 0, 0 );

		// Save scroll position
		function saveScrollPosition() {
			sessionStorage.setItem( `scrollPosition_${pageUrl}`, window.scrollY || document.documentElement.scrollTop );
		}

		window.addEventListener( 'pagehide', saveScrollPosition, { capture: true, once: true } );
		document.addEventListener( 'visibilitychange', () => {
			if ( document.visibilityState === 'hidden' ) saveScrollPosition();
		} );

		// Restore scroll position, or to anchor in URL if no saved scroll position.
		function restoreScroll() {
			const scrollTop = parseInt( savedScrollTop, 10 );
			if ( ! scrollTop ) {
				// Handle anchor links
				const target = window.location.hash && document.querySelector( window.location.hash );
				if ( target ) target.scrollIntoView( { behavior: 'smooth' } );
				return;
			}
			window.scrollTo( { top: scrollTop, behavior: 'smooth' } );
		}

		// Initialize with safelist check, fallback to observer
		function init() {
			if ( COMBINED_TARGET_SELECTOR && document.querySelector( COMBINED_TARGET_SELECTOR ) || document.querySelector( COMBINED_DYNAMIC_SELECTOR ) ) {
				setTimeout( restoreScroll, SCROLL_TIMEOUT_MS );
				return;
			}
			const observer = new MutationObserver( ( mutations ) => {
				for ( const mutation of mutations ) {
					for ( const node of mutation.addedNodes ) {
						if ( node.nodeType === Node.ELEMENT_NODE && 
							( node.matches( COMBINED_DYNAMIC_SELECTOR ) || node.querySelector( COMBINED_DYNAMIC_SELECTOR ) ) ) {
							observer.disconnect();
							clearTimeout( timeoutId );
							setTimeout( restoreScroll, SCROLL_TIMEOUT_MS );
							return;
						}
					}
				}
			} );
			observer.observe( document.documentElement, { childList: true, subtree: true } );
			const timeoutId = setTimeout( () => {
				observer.disconnect();
				setTimeout( restoreScroll, SCROLL_TIMEOUT_MS );
			}, OBSERVER_TIMEOUT_MS );
		}
		if ( document.readyState === 'loading' ) {
			document.addEventListener( 'DOMContentLoaded', init );
		} else {
			init();
		}
	} )();
	</script>
	<?php
}
