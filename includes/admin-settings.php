<?php
/**
 * Admin Settings Page for Delayed Scroll Restoration
 *
 * Implements WordPress Settings API for plugin configuration.
 *
 * @package XWP\ScrollRestoration
 */

namespace XWP\ScrollRestoration;

/**
 * Register the settings page in WordPress admin menu.
 *
 * @return void
 */
function register_settings_page() {
	add_options_page(
		__( 'Delayed Scroll Restoration Settings', 'delayed-scroll-restoration' ),
		__( 'Delayed Scroll Restoration', 'delayed-scroll-restoration' ),
		'manage_options',
		'delayed-scroll-restoration',
		__NAMESPACE__ . '\render_settings_page'
	);
}

/**
 * Register plugin settings using WordPress Settings API.
 *
 * @return void
 */
function register_settings() {
	register_setting(
		'delayed_scroll_restoration_settings',
		'delayed_scroll_restoration_settings',
		[
			'sanitize_callback' => __NAMESPACE__ . '\sanitize_settings',
		]
	);

	add_settings_section(
		'delayed_scroll_restoration_main',
		__( 'Scroll Restoration Configuration', 'delayed-scroll-restoration' ),
		__NAMESPACE__ . '\render_settings_section',
		'delayed-scroll-restoration'
	);

	add_settings_field(
		'target_selectors',
		__( 'Safelist CSS Selectors', 'delayed-scroll-restoration' ),
		__NAMESPACE__ . '\render_target_selectors_field',
		'delayed-scroll-restoration',
		'delayed_scroll_restoration_main'
	);

	add_settings_field(
		'dynamic_selectors',
		__( 'Dynamically Injected Element Selectors', 'delayed-scroll-restoration' ),
		__NAMESPACE__ . '\render_dynamic_selectors_field',
		'delayed-scroll-restoration',
		'delayed_scroll_restoration_main'
	);

	add_settings_field(
		'scroll_delay',
		__( 'Scroll Delay (ms)', 'delayed-scroll-restoration' ),
		__NAMESPACE__ . '\render_scroll_delay_field',
		'delayed-scroll-restoration',
		'delayed_scroll_restoration_main'
	);

	add_settings_field(
		'observer_timeout',
		__( 'Observer Timeout (ms)', 'delayed-scroll-restoration' ),
		__NAMESPACE__ . '\render_observer_timeout_field',
		'delayed-scroll-restoration',
		'delayed_scroll_restoration_main'
	);

	add_settings_field(
		'single_posts_only',
		__( 'Single Posts Only', 'delayed-scroll-restoration' ),
		__NAMESPACE__ . '\render_single_posts_only_field',
		'delayed-scroll-restoration',
		'delayed_scroll_restoration_main'
	);
}

/**
 * Sanitize all plugin settings.
 *
 * @param array $input Raw input from settings form.
 * @return array Sanitized settings.
 */
function sanitize_settings( $input ) {
	if ( ! is_array( $input ) ) {
		$input = [];
	}

	$sanitized = [];

	// Sanitize target selectors (safelist).
	if ( isset( $input['target_selectors'] ) ) {
		$sanitized['target_selectors'] = sanitize_textarea_field( $input['target_selectors'] );
	} else {
		$sanitized['target_selectors'] = '';
	}

	// Sanitize dynamic selectors.
	if ( isset( $input['dynamic_selectors'] ) ) {
		$sanitized['dynamic_selectors'] = sanitize_textarea_field( $input['dynamic_selectors'] );
	} else {
		$sanitized['dynamic_selectors'] = '';
	}

	// Sanitize scroll delay.
	if ( isset( $input['scroll_delay'] ) ) {
		$sanitized['scroll_delay'] = absint( $input['scroll_delay'] );
	} else {
		$sanitized['scroll_delay'] = 100;
	}

	// Sanitize observer timeout.
	if ( isset( $input['observer_timeout'] ) ) {
		$sanitized['observer_timeout'] = absint( $input['observer_timeout'] );
	} else {
		$sanitized['observer_timeout'] = 10000;
	}

	// Sanitize single posts only checkbox.
	$sanitized['single_posts_only'] = ! empty( $input['single_posts_only'] );

	return $sanitized;
}

/**
 * Get plugin settings with defaults.
 *
 * @return array Plugin settings.
 */
function get_settings() {
	$defaults = [
		'target_selectors'   => '',
		'dynamic_selectors'  => '',
		'scroll_delay'       => 100,
		'observer_timeout'   => 10000,
		'single_posts_only'  => false,
	];

	$settings = get_option( 'delayed_scroll_restoration_settings', [] );

	return wp_parse_args( $settings, $defaults );
}

/**
 * Render the main settings page.
 *
 * @return void
 */
function render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Check if settings were saved.
	if ( isset( $_GET['settings-updated'] ) ) {
		add_settings_error(
			'delayed_scroll_restoration_messages',
			'delayed_scroll_restoration_message',
			__( 'Settings Saved', 'delayed-scroll-restoration' ),
			'updated'
		);
	}

	settings_errors( 'delayed_scroll_restoration_messages' );
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<form action="options.php" method="post">
			<?php
			settings_fields( 'delayed_scroll_restoration_settings' );
			do_settings_sections( 'delayed-scroll-restoration' );
			submit_button( __( 'Save Settings', 'delayed-scroll-restoration' ) );
			?>
		</form>
	</div>
	<?php
}

/**
 * Render the settings section description.
 *
 * @return void
 */
function render_settings_section() {
	?>
	<p>
		<?php
		esc_html_e(
			'Configure how scroll position restoration behaves on your site. The plugin waits for dynamic elements to load before restoring scroll position.',
			'delayed-scroll-restoration'
		);
		?>
	</p>
	<?php
}

/**
 * Render the target selectors (safelist) field.
 *
 * @return void
 */
function render_target_selectors_field() {
	$settings = get_settings();
	?>
	<textarea
		id="target_selectors"
		name="delayed_scroll_restoration_settings[target_selectors]"
		rows="5"
		cols="50"
		class="large-text code"
	><?php echo esc_textarea( $settings['target_selectors'] ); ?></textarea>
	<p class="description">
		<?php esc_html_e( 'CSS selectors that, when found on page load, allow immediate scroll restoration. Enter one selector per line.', 'delayed-scroll-restoration' ); ?>
		<br><strong><?php esc_html_e( 'Examples:', 'delayed-scroll-restoration' ); ?></strong><br>
		<code>#account-menu</code><br>
		<code>.ad-free</code><br>
		<code>[data-loaded="true"]</code>
	</p>
	<?php
}

/**
 * Render the dynamic selectors field.
 *
 * @return void
 */
function render_dynamic_selectors_field() {
	$settings = get_settings();
	?>
	<textarea
		id="dynamic_selectors"
		name="delayed_scroll_restoration_settings[dynamic_selectors]"
		rows="5"
		cols="50"
		class="large-text code"
	><?php echo esc_textarea( $settings['dynamic_selectors'] ); ?></textarea>
	<p class="description">
		<?php esc_html_e( 'CSS selectors for dynamically injected elements. The plugin will wait for any of these to appear before restoring scroll. Enter one selector per line.', 'delayed-scroll-restoration' ); ?>
		<br><strong><?php esc_html_e( 'Examples:', 'delayed-scroll-restoration' ); ?></strong><br>
		<code>#ad-container</code><br>
		<code>.lazy-loaded</code><br>
		<code>[data-ad-slot]</code>
		<br><br>
		<em><?php esc_html_e( 'When empty, the delay scroll functionality will not run.', 'delayed-scroll-restoration' ); ?></em>
	</p>
	<?php
}

/**
 * Render the scroll delay field.
 *
 * @return void
 */
function render_scroll_delay_field() {
	$settings = get_settings();
	?>
	<input
		type="number"
		id="scroll_delay"
		name="delayed_scroll_restoration_settings[scroll_delay]"
		value="<?php echo esc_attr( $settings['scroll_delay'] ); ?>"
		min="0"
		step="1"
		class="regular-text"
	/>
	<p class="description">
		<?php
		esc_html_e(
			'Time in milliseconds to wait after the dynamic element appears before scrolling. Default: 100',
			'delayed-scroll-restoration'
		);
		?>
	</p>
	<?php
}

/**
 * Render the observer timeout field.
 *
 * @return void
 */
function render_observer_timeout_field() {
	$settings = get_settings();
	?>
	<input
		type="number"
		id="observer_timeout"
		name="delayed_scroll_restoration_settings[observer_timeout]"
		value="<?php echo esc_attr( $settings['observer_timeout'] ); ?>"
		min="0"
		step="1"
		class="regular-text"
	/>
	<p class="description">
		<?php
		esc_html_e(
			'Maximum time in milliseconds the MutationObserver will watch before disconnecting and restoring scroll anyway. Default: 10000',
			'delayed-scroll-restoration'
		);
		?>
	</p>
	<?php
}

/**
 * Render the single posts only field.
 *
 * @return void
 */
function render_single_posts_only_field() {
	$settings = get_settings();
	?>
	<label>
		<input
			type="checkbox"
			id="single_posts_only"
			name="delayed_scroll_restoration_settings[single_posts_only]"
			value="1"
			<?php checked( $settings['single_posts_only'], true ); ?>
		/>
		<?php esc_html_e( 'Enable delayed scroll restoration only on single posts', 'delayed-scroll-restoration' ); ?>
	</label>
	<p class="description">
		<?php
		esc_html_e(
			'When enabled, the script will only run on single post pages. When disabled, it runs on all pages.',
			'delayed-scroll-restoration'
		);
		?>
	</p>
	<?php
}
