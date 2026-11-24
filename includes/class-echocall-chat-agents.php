<?php
/**
 * The list of chatbots this site may show.
 *
 * The list is read once from the EchoCall API with an API key the admin pastes
 * in. Only id, name and status are kept. The key itself is never stored,
 * logged or sent anywhere else, because it gives full access to the account.
 *
 * @package EchoCall_Chat_Widget
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reads, stores and checks the chatbots of the connected account.
 */
class EchoCall_Chat_Agents {

	const OPTION = 'echocall_chatbots';

	/** Largest page the API serves. */
	const PER_PAGE = 100;

	/** Upper bound for pages, so a broken answer cannot loop. */
	const MAX_PAGES = 5;

	/**
	 * The stored list.
	 *
	 * @return array<int, array{id:int, name:string, status:string}>
	 */
	public static function all() {
		$stored = get_option( self::OPTION, array() );
		if ( ! is_array( $stored ) || empty( $stored['items'] ) || ! is_array( $stored['items'] ) ) {
			return array();
		}
		$items = array();
		foreach ( $stored['items'] as $item ) {
			if ( is_array( $item ) && isset( $item['id'] ) && self::is_valid_id( $item['id'] ) ) {
				$items[] = array(
					'id'     => (int) $item['id'],
					'name'   => isset( $item['name'] ) ? (string) $item['name'] : '',
					'status' => isset( $item['status'] ) ? (string) $item['status'] : '',
				);
			}
		}
		return $items;
	}

	/**
	 * When the list was loaded, as a Unix time stamp, or 0.
	 *
	 * @return int
	 */
	public static function loaded_at() {
		$stored = get_option( self::OPTION, array() );
		return ( is_array( $stored ) && isset( $stored['loaded'] ) ) ? (int) $stored['loaded'] : 0;
	}

	/**
	 * Whether a value looks like a chatbot id.
	 *
	 * @param mixed $id Value to check.
	 * @return bool
	 */
	public static function is_valid_id( $id ) {
		if ( is_int( $id ) ) {
			return $id > 0 && $id < 10000000000;
		}
		return is_string( $id ) && 1 === preg_match( '/^[1-9][0-9]{0,9}$/', $id );
	}

	/**
	 * Whether the id belongs to the loaded list.
	 *
	 * @param mixed $id Chatbot id.
	 * @return bool
	 */
	public static function contains( $id ) {
		if ( ! self::is_valid_id( $id ) ) {
			return false;
		}
		foreach ( self::all() as $item ) {
			if ( $item['id'] === (int) $id ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * The name of a chatbot from the list.
	 *
	 * @param int $id Chatbot id.
	 * @return string Empty when the id is not in the list.
	 */
	public static function name_of( $id ) {
		foreach ( self::all() as $item ) {
			if ( $item['id'] === (int) $id ) {
				return $item['name'];
			}
		}
		return '';
	}

	/**
	 * Removes the list. The chosen chatbot keeps working on the site.
	 */
	public static function forget() {
		delete_option( self::OPTION );
	}

	/**
	 * Whether a string has the shape of an EchoCall API key.
	 *
	 * @param string $key Value to check.
	 * @return bool
	 */
	public static function looks_like_key( $key ) {
		return 1 === preg_match( '/^eck_[a-z]{2,10}_[A-Za-z0-9]{16,128}$/', $key );
	}

	/**
	 * Loads the chatbots of the account the key belongs to and stores them.
	 *
	 * @param string $key API key, used for this request only.
	 * @return int|WP_Error Number of chatbots stored, or the reason it failed.
	 */
	public static function load_with_key( $key ) {
		if ( ! self::looks_like_key( $key ) ) {
			return new WP_Error(
				'echocall_key_format',
				__( 'This does not look like an EchoCall API key. Copy the whole key from the Developer Portal, it starts with eck_.', 'echocall-chat-widget' )
			);
		}

		$items = array();
		$page  = 1;
		do {
			$response = wp_safe_remote_get(
				add_query_arg(
					array(
						'page'    => $page,
						'perPage' => self::PER_PAGE,
					),
					echocall_chat_api_base() . '/chatbots'
				),
				array(
					'timeout'     => 15,
					'redirection' => 0,
					'user-agent'  => 'EchoCall-WordPress/' . ECHOCALL_CHAT_VERSION,
					'headers'     => array(
						'Authorization' => 'Bearer ' . $key,
						'Accept'        => 'application/json',
						// Keys limited to certain domains check this header.
						'Referer'       => home_url( '/' ),
					),
				)
			);

			if ( is_wp_error( $response ) ) {
				return new WP_Error(
					'echocall_unreachable',
					__( 'EchoCall could not be reached. Check that this server may open https connections and try again.', 'echocall-chat-widget' )
				);
			}

			$status = (int) wp_remote_retrieve_response_code( $response );
			$body   = json_decode( (string) wp_remote_retrieve_body( $response ), true );
			if ( 200 !== $status ) {
				return self::error_for( $status, is_array( $body ) ? $body : array() );
			}
			if ( ! is_array( $body ) || ! isset( $body['data'] ) || ! is_array( $body['data'] ) ) {
				return new WP_Error(
					'echocall_bad_answer',
					__( 'EchoCall sent an answer the plugin does not understand. Try again later.', 'echocall-chat-widget' )
				);
			}

			foreach ( $body['data'] as $bot ) {
				if ( ! is_array( $bot ) || ! isset( $bot['id'] ) || ! self::is_valid_id( $bot['id'] ) ) {
					continue;
				}
				$name = '';
				if ( isset( $bot['name'] ) && is_string( $bot['name'] ) ) {
					$name = $bot['name'];
				} elseif ( isset( $bot['chatbotDisplayName'] ) && is_string( $bot['chatbotDisplayName'] ) ) {
					$name = $bot['chatbotDisplayName'];
				}
				$items[ (int) $bot['id'] ] = array(
					'id'     => (int) $bot['id'],
					'name'   => mb_substr( sanitize_text_field( $name ), 0, 120 ),
					'status' => isset( $bot['status'] ) && is_string( $bot['status'] ) ? sanitize_key( $bot['status'] ) : '',
				);
			}

			$total = isset( $body['pagination']['total'] ) ? (int) $body['pagination']['total'] : 0;
			$more  = $page * self::PER_PAGE < $total && count( $body['data'] ) > 0;
			++$page;
		} while ( $more && $page <= self::MAX_PAGES );

		update_option(
			self::OPTION,
			array(
				'items'  => array_values( $items ),
				'loaded' => time(),
			),
			false
		);

		return count( $items );
	}

	/**
	 * Turns an API error into a message the admin can act on.
	 *
	 * @param int   $status HTTP status.
	 * @param array $body   Decoded answer.
	 * @return WP_Error
	 */
	private static function error_for( $status, array $body ) {
		$code = isset( $body['error']['code'] ) && is_string( $body['error']['code'] ) ? $body['error']['code'] : '';

		if ( 401 === $status ) {
			return new WP_Error(
				'echocall_key_rejected',
				__( 'EchoCall did not accept this key. It may be incomplete, deleted or expired. Create a new key in the Developer Portal.', 'echocall-chat-widget' )
			);
		}
		if ( 403 === $status && 'no_active_subscription' === $code ) {
			return new WP_Error(
				'echocall_no_plan',
				__( 'Your EchoCall account has no active package. Choose a package in your dashboard, the trial counts as well.', 'echocall-chat-widget' )
			);
		}
		if ( 403 === $status && 'ip_not_allowed' === $code ) {
			return new WP_Error(
				'echocall_ip',
				__( 'This key only works from certain IP addresses. Allow the address of this web server for the key, or use a key without that limit.', 'echocall-chat-widget' )
			);
		}
		if ( 403 === $status && 'domain_not_allowed' === $code ) {
			return new WP_Error(
				'echocall_domain',
				sprintf(
					/* translators: %s: domain of this website */
					__( 'This key only works for certain domains. Add %s to the key, or use a key without that limit.', 'echocall-chat-widget' ),
					(string) wp_parse_url( home_url(), PHP_URL_HOST )
				)
			);
		}
		if ( 403 === $status ) {
			return new WP_Error(
				'echocall_forbidden',
				__( 'This key may not read chatbots. Use a key of the account that owns the chatbots.', 'echocall-chat-widget' )
			);
		}
		if ( 429 === $status ) {
			return new WP_Error(
				'echocall_rate',
				__( 'Too many attempts in a short time. Wait a minute and try again.', 'echocall-chat-widget' )
			);
		}
		return new WP_Error(
			'echocall_status',
			sprintf(
				/* translators: %d: HTTP status code */
				__( 'EchoCall answered with status %d. Try again later.', 'echocall-chat-widget' ),
				$status
			)
		);
	}
}
