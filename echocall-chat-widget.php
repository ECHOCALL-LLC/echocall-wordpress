<?php
/**
 * Plugin Name:       EchoCall Chat Widget
 * Plugin URI:        https://github.com/ECHOCALL-LLC/echocall-wordpress
 * Description:       Adds your EchoCall chatbot to your website. Connect once with an API key and pick the chatbot from your own list.
 * Version:           2.0.0
 * Requires at least: 6.8
 * Requires PHP:      7.4
 * Author:            EchoCall
 * Author URI:        https://echocall.de
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       echocall-chat-widget
 * Domain Path:       /languages
 * Update URI:        https://github.com/ECHOCALL-LLC/echocall-wordpress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ECHOCALL_CHAT_VERSION', '2.0.0' );
define( 'ECHOCALL_CHAT_FILE', __FILE__ );
define( 'ECHOCALL_CHAT_DIR', plugin_dir_path( __FILE__ ) );
define( 'ECHOCALL_CHAT_URL', plugin_dir_url( __FILE__ ) );
define( 'ECHOCALL_CHAT_DEFAULT_WIDGET_BASE', 'https://cdn.echocall.de' );
define( 'ECHOCALL_CHAT_DEFAULT_API_BASE', 'https://hub.echocall.de/api/v1' );
define( 'ECHOCALL_CHAT_PORTAL_URL', 'https://hub.echocall.de/developer' );

require_once ECHOCALL_CHAT_DIR . 'includes/class-echocall-chat-agents.php';
require_once ECHOCALL_CHAT_DIR . 'includes/class-echocall-chat-settings.php';
require_once ECHOCALL_CHAT_DIR . 'includes/class-echocall-chat-frontend.php';

/**
 * Loads the translations of this plugin.
 *
 * WordPress only finds a file whose name matches the locale exactly, so de_AT
 * or fr_CA would fall back to English. Those get the closest translation that
 * ships with the plugin instead.
 */
function echocall_chat_load_textdomain() {
	$domain = 'echocall-chat-widget';
	$dir    = ECHOCALL_CHAT_DIR . 'languages/';
	$locale = determine_locale();

	$candidates = array( $locale );
	$prefix     = strtolower( substr( $locale, 0, 2 ) );
	if ( 'de' === $prefix ) {
		$formal       = ( false !== strpos( $locale, '_formal' ) ) || 'de_CH' === $locale;
		$candidates[] = $formal ? 'de_DE_formal' : 'de_DE';
	} elseif ( 'fr' === $prefix ) {
		$candidates[] = 'fr_FR';
	}

	foreach ( array_unique( $candidates ) as $candidate ) {
		// WordPress 6.5 and later prefers the .l10n.php file next to this name.
		if ( is_readable( $dir . $domain . '-' . $candidate . '.l10n.php' )
			&& load_textdomain( $domain, $dir . $domain . '-' . $candidate . '.mo', $locale ) ) {
			return;
		}
	}
}
add_action( 'init', 'echocall_chat_load_textdomain' );

/**
 * The address widget.js and the chat window are loaded from.
 *
 * @return string Address without a trailing slash, always https.
 */
function echocall_chat_widget_base() {
	$base = (string) get_option( 'echocall_base_url', '' );
	if ( '' === $base || 0 !== strpos( $base, 'https://' ) ) {
		$base = ECHOCALL_CHAT_DEFAULT_WIDGET_BASE;
	}
	return untrailingslashit( $base );
}

/**
 * The EchoCall REST API the plugin reads the chatbots from.
 *
 * Partners can point it at their own address with the filter
 * echocall_chat_api_base. Only https addresses are used.
 *
 * @return string
 */
function echocall_chat_api_base() {
	$base = (string) apply_filters( 'echocall_chat_api_base', ECHOCALL_CHAT_DEFAULT_API_BASE );
	if ( 0 !== strpos( $base, 'https://' ) || false === wp_http_validate_url( $base ) ) {
		$base = ECHOCALL_CHAT_DEFAULT_API_BASE;
	}
	return untrailingslashit( $base );
}

EchoCall_Chat_Settings::init();
EchoCall_Chat_Frontend::init();

/**
 * Elementor loads its widgets late, so the widget class is only read when
 * Elementor is active and asks for it.
 *
 * @param object $widgets_manager Elementor widgets manager.
 */
function echocall_chat_register_elementor_widget( $widgets_manager ) {
	require_once ECHOCALL_CHAT_DIR . 'includes/class-echocall-chat-elementor-widget.php';
	$widgets_manager->register( new EchoCall_Chat_Elementor_Widget() );
}
add_action( 'elementor/widgets/register', 'echocall_chat_register_elementor_widget' );
