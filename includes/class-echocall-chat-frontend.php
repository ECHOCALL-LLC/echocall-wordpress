<?php
/**
 * The chat on the website: the floating chat in the corner and the shortcode
 * [echocall_chat] for a chat inside a page.
 *
 * @package EchoCall_Chat_Widget
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Front end output.
 */
class EchoCall_Chat_Frontend {

	const STYLE = 'echocall-chat-widget';

	/**
	 * Hooks the output into WordPress.
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_action( 'wp_footer', array( __CLASS__, 'print_loader' ), 50 );
		add_filter( 'body_class', array( __CLASS__, 'body_class' ) );
		add_shortcode( 'echocall_chat', array( __CLASS__, 'shortcode' ) );
	}

	/**
	 * The chatbot id saved in the settings, or an empty string.
	 *
	 * @return string
	 */
	public static function saved_id() {
		$id = (string) get_option( 'echocall_widget_id', '' );
		return EchoCall_Chat_Agents::is_valid_id( $id ) ? $id : '';
	}

	/**
	 * Whether the floating chat belongs on this request.
	 *
	 * @return bool
	 */
	public static function should_show_floating() {
		$id = self::saved_id();
		if ( '' === $id || ! get_option( 'echocall_widget_enabled', true ) ) {
			return false;
		}
		if ( is_admin() || is_feed() || wp_doing_ajax() || is_embed() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return false;
		}
		// The Elementor editor preview would stack the chat over the canvas.
		if ( isset( $_GET['elementor-preview'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read only, no action is taken.
			return false;
		}
		if ( self::is_excluded( self::request_path() ) ) {
			return false;
		}
		/**
		 * Lets a consent or cookie tool decide whether the chat is loaded.
		 *
		 * @param bool   $show Whether the chat is shown.
		 * @param string $id   Chatbot id.
		 */
		return (bool) apply_filters( 'echocall_chat_widget_enabled', true, $id );
	}

	/**
	 * The path of this request in the form the exclude list uses.
	 *
	 * @return string
	 */
	private static function request_path() {
		$uri  = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- only compared, never printed.
		$path = wp_parse_url( (string) $uri, PHP_URL_PATH );
		$path = is_string( $path ) ? rawurldecode( $path ) : '/';
		return EchoCall_Chat_Settings::lower( '/' . trim( $path, '/' ) );
	}

	/**
	 * Whether a path is on the exclude list, or below an entry of it.
	 *
	 * @param string $path Request path.
	 * @return bool
	 */
	public static function is_excluded( $path ) {
		$list = (string) get_option( 'echocall_exclude_pages', '' );
		if ( '' === $list ) {
			return false;
		}
		foreach ( preg_split( '/\R/', $list ) as $line ) {
			$entry = EchoCall_Chat_Settings::normalize_path( $line );
			if ( '' === $entry ) {
				continue;
			}
			if ( '/' === $entry || $path === $entry || 0 === strpos( $path, $entry . '/' ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Registers the style sheet and loads it when the floating chat is hidden
	 * on phones. A page with the shortcode loads it from the shortcode.
	 */
	public static function enqueue() {
		wp_register_style( self::STYLE, ECHOCALL_CHAT_URL . 'assets/echocall-chat.css', array(), ECHOCALL_CHAT_VERSION );
		if ( get_option( 'echocall_hide_on_mobile', false ) && self::should_show_floating() ) {
			wp_enqueue_style( self::STYLE );
		}
	}

	/**
	 * Prints the loader script for the floating chat.
	 *
	 * widget.js reads data-id from its own tag, so the tag is printed by
	 * WordPress itself (wp_print_script_tag escapes every attribute and passes
	 * them through the filter wp_script_attributes, which consent tools use).
	 */
	public static function print_loader() {
		if ( ! self::should_show_floating() ) {
			return;
		}
		wp_print_script_tag(
			array(
				'id'      => 'echocall-widget-loader',
				'src'     => echocall_chat_widget_base() . '/widget.js',
				'data-id' => self::saved_id(),
				'async'   => true,
			)
		);
	}

	/**
	 * widget.js adds its frame directly to body. This class lets the style
	 * sheet hide that frame on phones without touching anything else.
	 *
	 * @param array $classes Body classes.
	 * @return array
	 */
	public static function body_class( $classes ) {
		if ( get_option( 'echocall_hide_on_mobile', false ) && self::should_show_floating() ) {
			$classes[] = 'echocall-hide-mobile';
		}
		return $classes;
	}

	/**
	 * [echocall_chat id="12" height="600px" width="100%"]
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public static function shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'     => '',
				'height' => '600px',
				'width'  => '100%',
			),
			is_array( $atts ) ? $atts : array(),
			'echocall_chat'
		);
		return self::render_inline( (string) $atts['id'], (string) $atts['height'], (string) $atts['width'] );
	}

	/**
	 * The chat inside a page. Shared by the shortcode and the Elementor widget.
	 *
	 * @param string $id     Chatbot id, empty for the one in the settings.
	 * @param string $height CSS height such as 600px.
	 * @param string $width  CSS width such as 100%.
	 * @return string HTML.
	 */
	public static function render_inline( $id, $height = '600px', $width = '100%' ) {
		$id = trim( $id );
		if ( '' === $id ) {
			$id = self::saved_id();
		}
		// Only the chosen chatbot or one from the loaded list, so an editor
		// cannot place a chat of another account on the site.
		$allowed = '' !== $id && ( $id === self::saved_id() || EchoCall_Chat_Agents::contains( $id ) );
		if ( ! $allowed ) {
			if ( current_user_can( 'edit_posts' ) ) {
				wp_enqueue_style( self::STYLE );
				return '<p class="echocall-chat-notice">' . esc_html__( 'EchoCall Chat: this chatbot is not in the list of this website. An admin can load the list under Settings, EchoCall Chat.', 'echocall-chat-widget' ) . '</p>';
			}
			return '';
		}

		wp_enqueue_style( self::STYLE );
		$src = add_query_arg(
			array(
				'id'    => rawurlencode( $id ),
				'embed' => 'true',
			),
			echocall_chat_widget_base() . '/widget.html'
		);
		$name  = EchoCall_Chat_Agents::name_of( (int) $id );
		$title = '' !== $name
			/* translators: %s: chatbot name */
			? sprintf( __( 'Chat with %s', 'echocall-chat-widget' ), $name )
			: __( 'Chat window', 'echocall-chat-widget' );

		return sprintf(
			'<div class="echocall-chat-embed" style="%1$s"><iframe src="%2$s" title="%3$s" loading="lazy" allow="microphone" referrerpolicy="strict-origin-when-cross-origin"></iframe></div>',
			esc_attr( 'height:' . self::css_size( $height, '600px' ) . ';width:' . self::css_size( $width, '100%' ) ),
			esc_url( $src ),
			esc_attr( $title )
		);
	}

	/**
	 * A size that is a number with px, %, vh or rem, or the fallback.
	 *
	 * @param string $value    Size from the shortcode.
	 * @param string $fallback Size used when the value is not allowed.
	 * @return string
	 */
	public static function css_size( $value, $fallback ) {
		$value = strtolower( trim( $value ) );
		if ( 1 === preg_match( '/^[0-9]{1,4}$/', $value ) ) {
			$value .= 'px';
		}
		return 1 === preg_match( '/^[0-9]{1,4}(\.[0-9]{1,2})?(px|%|vh|rem)$/', $value ) ? $value : $fallback;
	}
}
