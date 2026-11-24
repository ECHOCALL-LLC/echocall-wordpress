<?php
/**
 * Elementor widget EchoCall Chat.
 *
 * Shows a chatbot inside the page. The chatbot is picked from the list loaded
 * under Settings, EchoCall Chat, the same rule as for the shortcode.
 *
 * @package EchoCall_Chat_Widget
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The widget.
 */
class EchoCall_Chat_Elementor_Widget extends \Elementor\Widget_Base {

	/**
	 * Internal name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'echocall_chat';
	}

	/**
	 * Name in the widget panel.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return esc_html__( 'EchoCall Chat', 'echocall-chat-widget' );
	}

	/**
	 * Icon in the widget panel.
	 *
	 * @return string
	 */
	public function get_icon(): string {
		return 'eicon-comments';
	}

	/**
	 * Panel group.
	 *
	 * @return array
	 */
	public function get_categories(): array {
		return array( 'general' );
	}

	/**
	 * Search words in the widget panel.
	 *
	 * @return array
	 */
	public function get_keywords(): array {
		return array( 'echocall', 'chat', 'agent', 'bot' );
	}

	/**
	 * Style sheet the widget needs on the page.
	 *
	 * @return array
	 */
	public function get_style_depends(): array {
		return array( EchoCall_Chat_Frontend::STYLE );
	}

	/**
	 * Controls in the panel.
	 */
	protected function register_controls(): void {
		$options = array( '' => esc_html__( 'Chatbot from the settings', 'echocall-chat-widget' ) );
		foreach ( EchoCall_Chat_Agents::all() as $agent ) {
			$options[ (string) $agent['id'] ] = '' !== $agent['name'] ? $agent['name'] : (string) $agent['id'];
		}

		$this->start_controls_section(
			'section_chat',
			array( 'label' => esc_html__( 'Chat', 'echocall-chat-widget' ) )
		);

		$this->add_control(
			'chat_id',
			array(
				'label'       => esc_html__( 'Chatbot', 'echocall-chat-widget' ),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'options'     => $options,
				'default'     => '',
				'description' => esc_html__( 'The list comes from Settings, EchoCall Chat. Load it again there after you add a chatbot.', 'echocall-chat-widget' ),
			)
		);

		$this->add_control(
			'height',
			array(
				'label'      => esc_html__( 'Height', 'echocall-chat-widget' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'vh' ),
				'range'      => array(
					'px' => array(
						'min' => 300,
						'max' => 1200,
					),
					'vh' => array(
						'min' => 30,
						'max' => 100,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 600,
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Output on the page.
	 */
	protected function render(): void {
		$settings = $this->get_settings_for_display();
		$id       = isset( $settings['chat_id'] ) ? (string) $settings['chat_id'] : '';
		$height   = '600px';
		if ( isset( $settings['height']['size'], $settings['height']['unit'] ) && '' !== (string) $settings['height']['size'] ) {
			$height = (string) $settings['height']['size'] . (string) $settings['height']['unit'];
		}
		// render_inline checks the id against the list and escapes everything.
		echo EchoCall_Chat_Frontend::render_inline( $id, $height, '100%' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
