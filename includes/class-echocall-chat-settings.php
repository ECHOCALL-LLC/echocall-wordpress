<?php
/**
 * Settings, EchoCall Chat.
 *
 * Two steps on one page: connect with an API key to load the chatbots, then
 * pick one of them. The site can only show chatbots from that list.
 *
 * @package EchoCall_Chat_Widget
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin page and the saved settings.
 */
class EchoCall_Chat_Settings {

	const PAGE   = 'echocall-chat';
	const GROUP  = 'echocall_chat';
	const NOTICE = 'echocall_chat_notice_';

	/** Most addresses the exclude list keeps. */
	const MAX_EXCLUDES = 50;

	/**
	 * Hooks the page into WordPress.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_page' ) );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
		add_action( 'admin_post_echocall_chat_connect', array( __CLASS__, 'handle_connect' ) );
		add_action( 'admin_post_echocall_chat_forget', array( __CLASS__, 'handle_forget' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( ECHOCALL_CHAT_FILE ), array( __CLASS__, 'action_links' ) );
	}

	/**
	 * Adds Settings, EchoCall Chat.
	 */
	public static function add_page() {
		add_options_page(
			__( 'EchoCall Chat', 'echocall-chat-widget' ),
			__( 'EchoCall Chat', 'echocall-chat-widget' ),
			'manage_options',
			self::PAGE,
			array( __CLASS__, 'render' )
		);
	}

	/**
	 * Adds a Settings link to the plugin row.
	 *
	 * @param array $links Existing links.
	 * @return array
	 */
	public static function action_links( $links ) {
		$url = admin_url( 'options-general.php?page=' . self::PAGE );
		array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'echocall-chat-widget' ) . '</a>' );
		return $links;
	}

	/**
	 * Registers the options with their checks. The option names are the same as
	 * in version 1, so an update keeps the settings.
	 */
	public static function register() {
		register_setting(
			self::GROUP,
			'echocall_widget_id',
			array(
				'type'              => 'string',
				'sanitize_callback' => array( __CLASS__, 'sanitize_widget_id' ),
				'default'           => '',
			)
		);
		register_setting(
			self::GROUP,
			'echocall_widget_enabled',
			array(
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
				'default'           => true,
			)
		);
		register_setting(
			self::GROUP,
			'echocall_hide_on_mobile',
			array(
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
				'default'           => false,
			)
		);
		register_setting(
			self::GROUP,
			'echocall_exclude_pages',
			array(
				'type'              => 'string',
				'sanitize_callback' => array( __CLASS__, 'sanitize_excludes' ),
				'default'           => '',
			)
		);
		register_setting(
			self::GROUP,
			'echocall_base_url',
			array(
				'type'              => 'string',
				'sanitize_callback' => array( __CLASS__, 'sanitize_base_url' ),
				'default'           => '',
			)
		);
	}

	/**
	 * Accepts only a chatbot from the loaded list, or none. An id saved by
	 * version 1 stays as long as it is not changed.
	 *
	 * @param mixed $value Submitted value.
	 * @return string
	 */
	public static function sanitize_widget_id( $value ) {
		$current = (string) get_option( 'echocall_widget_id', '' );
		$value   = is_scalar( $value ) ? trim( (string) $value ) : '';

		if ( '' === $value ) {
			return '';
		}
		if ( $value === $current || EchoCall_Chat_Agents::contains( $value ) ) {
			return $value;
		}
		add_settings_error(
			'echocall_widget_id',
			'echocall_widget_id_unknown',
			__( 'This chatbot is not in the list of your account. Load the list again and choose from it.', 'echocall-chat-widget' )
		);
		return $current;
	}

	/**
	 * Keeps one clean path per line.
	 *
	 * @param mixed $value Submitted text.
	 * @return string
	 */
	public static function sanitize_excludes( $value ) {
		$paths = array();
		foreach ( preg_split( '/\R/', is_string( $value ) ? $value : '' ) as $line ) {
			$path = self::normalize_path( $line );
			if ( '' !== $path ) {
				$paths[ $path ] = true;
			}
			if ( count( $paths ) >= self::MAX_EXCLUDES ) {
				break;
			}
		}
		return implode( "\n", array_keys( $paths ) );
	}

	/**
	 * Turns a line into a path such as /shop/cart, or an empty string.
	 *
	 * @param string $line A path or a full address of this site.
	 * @return string
	 */
	public static function normalize_path( $line ) {
		$line = trim( wp_strip_all_tags( (string) $line ) );
		if ( '' === $line ) {
			return '';
		}
		$path = wp_parse_url( $line, PHP_URL_PATH );
		if ( ! is_string( $path ) || '' === $path ) {
			// A bare domain has no path and must not hide the whole site.
			return '';
		}
		$path = '/' . trim( rawurldecode( $path ), '/' );
		// Only path characters, so the list can never carry markup.
		return 1 === preg_match( '#^/[\p{L}\p{N}._~!$&\'()*+,;=:@/-]*$#u', $path ) ? self::lower( $path ) : '';
	}

	/**
	 * Lower case that also works where PHP has no mbstring extension.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	public static function lower( $text ) {
		return function_exists( 'mb_strtolower' ) ? mb_strtolower( $text, 'UTF-8' ) : strtolower( $text );
	}

	/**
	 * Accepts an https address or nothing.
	 *
	 * @param mixed $value Submitted address.
	 * @return string
	 */
	public static function sanitize_base_url( $value ) {
		$value = is_string( $value ) ? trim( $value ) : '';
		if ( '' === $value ) {
			return '';
		}
		$url = esc_url_raw( $value, array( 'https' ) );
		if ( '' === $url || false === wp_http_validate_url( $url ) ) {
			add_settings_error(
				'echocall_base_url',
				'echocall_base_url_invalid',
				__( 'The widget address must be a public https address. The default address is used.', 'echocall-chat-widget' )
			);
			return '';
		}
		return untrailingslashit( $url );
	}

	/**
	 * Loads the chatbots with the pasted key.
	 */
	public static function handle_connect() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You may not change these settings.', 'echocall-chat-widget' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'echocall_chat_connect', 'echocall_connect_nonce' );

		// The key is read once, used for the request and then dropped.
		$key = isset( $_POST['echocall_api_key'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['echocall_api_key'] ) ) ) : '';

		$result = EchoCall_Chat_Agents::load_with_key( $key );
		unset( $key, $_POST['echocall_api_key'] );

		if ( is_wp_error( $result ) ) {
			self::flash( 'error', $result->get_error_message() );
		} elseif ( 0 === $result ) {
			self::flash( 'warning', __( 'Connected, but this account has no chatbots yet. Create one in your EchoCall dashboard and load the list again.', 'echocall-chat-widget' ) );
		} else {
			self::flash(
				'success',
				sprintf(
					/* translators: %d: number of chatbots */
					_n( '%d chatbot loaded. Choose it below and save.', '%d chatbots loaded. Choose one below and save.', $result, 'echocall-chat-widget' ),
					$result
				)
			);
		}

		wp_safe_redirect( admin_url( 'options-general.php?page=' . self::PAGE ) );
		exit;
	}

	/**
	 * Removes the stored list.
	 */
	public static function handle_forget() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You may not change these settings.', 'echocall-chat-widget' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'echocall_chat_forget', 'echocall_forget_nonce' );
		EchoCall_Chat_Agents::forget();
		self::flash( 'success', __( 'The list is removed. The chosen chatbot stays on the website until you change it.', 'echocall-chat-widget' ) );
		wp_safe_redirect( admin_url( 'options-general.php?page=' . self::PAGE ) );
		exit;
	}

	/**
	 * Keeps a message for the next page view of this admin.
	 *
	 * @param string $type    success, warning or error.
	 * @param string $message Text.
	 */
	private static function flash( $type, $message ) {
		set_transient(
			self::NOTICE . get_current_user_id(),
			array(
				'type'    => $type,
				'message' => $message,
			),
			120
		);
	}

	/**
	 * Prints and clears the kept message.
	 */
	private static function print_flash() {
		$key    = self::NOTICE . get_current_user_id();
		$notice = get_transient( $key );
		if ( ! is_array( $notice ) || empty( $notice['message'] ) ) {
			return;
		}
		delete_transient( $key );
		$type = in_array( $notice['type'], array( 'success', 'warning', 'error' ), true ) ? $notice['type'] : 'info';
		printf(
			'<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
			esc_attr( $type ),
			esc_html( $notice['message'] )
		);
	}

	/**
	 * Label of a chatbot in the list.
	 *
	 * @param array $agent id, name, status.
	 * @return string
	 */
	private static function agent_label( array $agent ) {
		$name = '' !== $agent['name'] ? $agent['name'] : sprintf(
			/* translators: %d: chatbot id */
			__( 'Chatbot %d', 'echocall-chat-widget' ),
			$agent['id']
		);
		if ( '' !== $agent['status'] && 'active' !== $agent['status'] ) {
			/* translators: %s: chatbot name */
			$name = sprintf( __( '%s (paused)', 'echocall-chat-widget' ), $name );
		}
		return $name;
	}

	/**
	 * Renders the page.
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$agents    = EchoCall_Chat_Agents::all();
		$loaded_at = EchoCall_Chat_Agents::loaded_at();
		$current   = (string) get_option( 'echocall_widget_id', '' );
		$enabled   = (bool) get_option( 'echocall_widget_enabled', true );
		$hide_mob  = (bool) get_option( 'echocall_hide_on_mobile', false );
		$excludes  = (string) get_option( 'echocall_exclude_pages', '' );
		$base_url  = (string) get_option( 'echocall_base_url', '' );
		$host      = (string) wp_parse_url( home_url(), PHP_URL_HOST );
		$legacy    = '' !== $current && ! EchoCall_Chat_Agents::contains( $current );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<?php
			self::print_flash();
			settings_errors();
			?>

			<h2><?php esc_html_e( '1. Connect your account', 'echocall-chat-widget' ); ?></h2>
			<p>
				<?php
				printf(
					/* translators: %s: link to the Developer Portal */
					esc_html__( 'Paste an API key from the %s of your EchoCall dashboard. The plugin uses it once to load your chatbots and does not save it.', 'echocall-chat-widget' ),
					'<a href="' . esc_url( ECHOCALL_CHAT_PORTAL_URL ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Developer Portal', 'echocall-chat-widget' ) . '</a>'
				);
				?>
			</p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="echocall_chat_connect" />
				<?php wp_nonce_field( 'echocall_chat_connect', 'echocall_connect_nonce' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="echocall-api-key"><?php esc_html_e( 'API key', 'echocall-chat-widget' ); ?></label></th>
						<td>
							<input type="password" id="echocall-api-key" name="echocall_api_key" class="regular-text code" autocomplete="off" spellcheck="false" required aria-describedby="echocall-api-key-help" />
							<p class="description" id="echocall-api-key-help"><?php esc_html_e( 'Starts with eck_live_. Create a new key there if you no longer have one.', 'echocall-chat-widget' ); ?></p>
						</td>
					</tr>
				</table>
				<?php
				// Loading is the main step until a list exists, after that saving is.
				submit_button(
					empty( $agents ) ? __( 'Load chatbots', 'echocall-chat-widget' ) : __( 'Load the list again', 'echocall-chat-widget' ),
					empty( $agents ) ? 'primary' : 'secondary',
					'echocall_connect',
					false
				);
				?>
			</form>
			<?php if ( $loaded_at > 0 ) : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="echocall_chat_forget" />
					<?php wp_nonce_field( 'echocall_chat_forget', 'echocall_forget_nonce' ); ?>
					<p>
						<?php
						printf(
							/* translators: 1: number of chatbots, 2: date and time */
							esc_html( _n( '%1$d chatbot in the list, loaded on %2$s.', '%1$d chatbots in the list, loaded on %2$s.', count( $agents ), 'echocall-chat-widget' ) ),
							(int) count( $agents ),
							esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $loaded_at ) )
						);
						?>
						<button type="submit" class="button-link button-link-delete"><?php esc_html_e( 'Remove list', 'echocall-chat-widget' ); ?></button>
					</p>
				</form>
			<?php endif; ?>

			<h2><?php esc_html_e( '2. Choose the chatbot', 'echocall-chat-widget' ); ?></h2>
			<form method="post" action="options.php">
				<?php settings_fields( self::GROUP ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="echocall-widget-id"><?php esc_html_e( 'Chatbot', 'echocall-chat-widget' ); ?></label></th>
						<td>
							<?php if ( empty( $agents ) && ! $legacy ) : ?>
								<input type="hidden" name="echocall_widget_id" value="<?php echo esc_attr( $current ); ?>" />
								<p><?php esc_html_e( 'Load your chatbots in step 1 first.', 'echocall-chat-widget' ); ?></p>
							<?php else : ?>
								<select id="echocall-widget-id" name="echocall_widget_id">
									<option value=""><?php esc_html_e( 'None', 'echocall-chat-widget' ); ?></option>
									<?php foreach ( $agents as $agent ) : ?>
										<option value="<?php echo esc_attr( (string) $agent['id'] ); ?>" <?php selected( $current, (string) $agent['id'] ); ?>><?php echo esc_html( self::agent_label( $agent ) ); ?></option>
									<?php endforeach; ?>
									<?php if ( $legacy ) : ?>
										<option value="<?php echo esc_attr( $current ); ?>" selected>
											<?php
											/* translators: %s: chatbot id */
											echo esc_html( sprintf( __( 'ID %s, set before version 2', 'echocall-chat-widget' ), $current ) );
											?>
										</option>
									<?php endif; ?>
								</select>
								<?php if ( $legacy ) : ?>
									<p class="description"><?php esc_html_e( 'This ID was typed in by hand. Load your chatbots and choose it from the list to confirm it belongs to your account.', 'echocall-chat-widget' ); ?></p>
								<?php endif; ?>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Website', 'echocall-chat-widget' ); ?></th>
						<td>
							<fieldset>
								<legend class="screen-reader-text"><?php esc_html_e( 'Website', 'echocall-chat-widget' ); ?></legend>
								<label><input type="checkbox" name="echocall_widget_enabled" value="1" <?php checked( $enabled ); ?> /> <?php esc_html_e( 'Show the chat on every page', 'echocall-chat-widget' ); ?></label><br />
								<label><input type="checkbox" name="echocall_hide_on_mobile" value="1" <?php checked( $hide_mob ); ?> /> <?php esc_html_e( 'Hide it on phones', 'echocall-chat-widget' ); ?></label>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="echocall-exclude"><?php esc_html_e( 'Hide on these pages', 'echocall-chat-widget' ); ?></label></th>
						<td>
							<textarea id="echocall-exclude" name="echocall_exclude_pages" rows="4" class="large-text code" aria-describedby="echocall-exclude-help"><?php echo esc_textarea( $excludes ); ?></textarea>
							<p class="description" id="echocall-exclude-help"><?php esc_html_e( 'One address per line, for example /checkout. Pages below it are hidden as well.', 'echocall-chat-widget' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="echocall-base-url"><?php esc_html_e( 'Widget address', 'echocall-chat-widget' ); ?></label></th>
						<td>
							<input type="url" id="echocall-base-url" name="echocall_base_url" class="regular-text code" value="<?php echo esc_attr( $base_url ); ?>" placeholder="<?php echo esc_attr( ECHOCALL_CHAT_DEFAULT_WIDGET_BASE ); ?>" aria-describedby="echocall-base-url-help" />
							<p class="description" id="echocall-base-url-help"><?php esc_html_e( 'Leave empty. Change it only if EchoCall or your provider gives you a different address.', 'echocall-chat-widget' ); ?></p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>

			<h2><?php esc_html_e( 'Inside a page', 'echocall-chat-widget' ); ?></h2>
			<p><?php esc_html_e( 'To show the chat inside a page instead of in the corner, add the shortcode below or use the widget EchoCall Chat in Elementor. Only chatbots from the list can be shown.', 'echocall-chat-widget' ); ?></p>
			<p><code>[echocall_chat]</code> <code>[echocall_chat id="12" height="600px"]</code></p>
			<p class="description">
				<?php
				/* translators: %s: domain of this website */
				echo esc_html( sprintf( __( 'If your chatbot only allows certain websites, add %s to it in your EchoCall dashboard.', 'echocall-chat-widget' ), $host ) );
				?>
			</p>
		</div>
		<?php
	}
}
