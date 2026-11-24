<?php
/**
 * Removes every setting of the plugin when it is deleted in WordPress.
 *
 * @package EchoCall_Chat_Widget
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Deletes the options of the current site.
 */
function echocall_chat_delete_options() {
	$options = array(
		'echocall_widget_id',
		'echocall_widget_enabled',
		'echocall_hide_on_mobile',
		'echocall_exclude_pages',
		'echocall_base_url',
		'echocall_chatbots',
		// Version 1 only.
		'echocall_widget_position',
	);
	foreach ( $options as $option ) {
		delete_option( $option );
	}
}

if ( is_multisite() ) {
	foreach ( get_sites( array( 'fields' => 'ids', 'number' => 0 ) ) as $echocall_site_id ) {
		switch_to_blog( $echocall_site_id );
		echocall_chat_delete_options();
		restore_current_blog();
	}
} else {
	echocall_chat_delete_options();
}
