<?php
defined( 'ABSPATH' ) || exit;

/**
 * Public, plain-text bot configuration. Scope 0 is central; positive scopes are sites.
 */
class PSource_Support_Bot {
	const OPTION = 'psource_support_bot';

	public static function boot() {
		add_filter( 'support_system_settings_tabs', array( __CLASS__, 'tabs' ) );
		add_filter( 'support_system_render_tab_function', array( __CLASS__, 'render_tab' ) );
		add_action( 'admin_post_ps_support_bot_save', array( __CLASS__, 'save' ) );
		add_action( 'wp_ajax_ps_support_bot_save', array( __CLASS__, 'save' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'ticket_assets' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'ticket_assets' ) );
	}

	public static function tabs( $tabs ) {
		$tabs['supportbot'] = __( 'Support-Bot', 'psource-support' );
		return $tabs;
	}

	public static function render_tab( $callback ) {
		return isset( $_GET['tab'] ) && 'supportbot' === $_GET['tab'] ? array( __CLASS__, 'page' ) : $callback;
	}

	public static function categories( $scope ) {
		return psource_support_get_ticket_categories( array( 'blog_id' => absint( $scope ) ) );
	}

	private static function defaults() {
		return array(
			'enabled_chat' => false, 'enabled_tickets' => false,
			'name' => __( 'Support-Bot', 'psource-support' ),
			'greeting' => __( 'Wobei können wir helfen? Wähle ein Thema oder gehe direkt zum Support.', 'psource-support' ),
			'topics' => array(),
		);
	}

	private static function text( $value, $multiline = false ) {
		if ( ! is_scalar( $value ) ) {
			return '';
		}
		return $multiline ? sanitize_textarea_field( (string) $value ) : sanitize_text_field( (string) $value );
	}

	private static function steps( $raw, $legacy_answer = '' ) {
		$types = array( 'message', 'choice', 'input', 'link', 'handoff', 'end' );
		$steps = array();
		$ids = array();
		$raw = is_array( $raw ) ? array_slice( $raw, 0, 50 ) : array();

		if ( ! $raw && '' !== $legacy_answer ) {
			$raw = array( array( 'id' => 'step-1', 'type' => 'message', 'text' => $legacy_answer ) );
		}

		foreach ( $raw as $index => $step ) {
			if ( ! is_array( $step ) ) {
				continue;
			}
			$type = isset( $step['type'] ) && is_scalar( $step['type'] ) ? sanitize_key( $step['type'] ) : '';
			if ( ! in_array( $type, $types, true ) ) {
				continue;
			}
			$id = isset( $step['id'] ) && is_scalar( $step['id'] ) ? sanitize_key( $step['id'] ) : '';
			if ( ! $id || isset( $ids[ $id ] ) ) {
				$id = 'step-' . ( $index + 1 );
				while ( isset( $ids[ $id ] ) ) {
					$id .= '-next';
				}
			}
			$ids[ $id ] = true;
			$item = array(
				'id' => $id,
				'type' => $type,
				'text' => self::text( isset( $step['text'] ) ? $step['text'] : '', true ),
				'label' => self::text( isset( $step['label'] ) ? $step['label'] : '' ),
				'next_step' => isset( $step['next_step'] ) && is_scalar( $step['next_step'] ) ? sanitize_key( $step['next_step'] ) : '',
			);
			if ( 'link' === $type ) {
				$item['url'] = isset( $step['url'] ) && is_scalar( $step['url'] ) ? esc_url_raw( (string) $step['url'], array( 'http', 'https' ) ) : '';
			}
			if ( 'choice' === $type ) {
				$item['options'] = array();
				foreach ( array_slice( isset( $step['options'] ) && is_array( $step['options'] ) ? $step['options'] : array(), 0, 20 ) as $option ) {
					if ( ! is_array( $option ) ) {
						continue;
					}
					$label = self::text( isset( $option['label'] ) ? $option['label'] : '' );
					if ( '' !== $label ) {
						$item['options'][] = array(
							'label' => $label,
							'next_step' => isset( $option['next_step'] ) && is_scalar( $option['next_step'] ) ? sanitize_key( $option['next_step'] ) : '',
						);
					}
				}
			}
			$steps[] = $item;
		}

		$known = wp_list_pluck( $steps, 'id' );
		foreach ( $steps as &$step ) {
			if ( $step['next_step'] && ! in_array( $step['next_step'], $known, true ) ) {
				$step['next_step'] = '';
			}
			if ( isset( $step['options'] ) ) {
				foreach ( $step['options'] as &$option ) {
					if ( $option['next_step'] && ! in_array( $option['next_step'], $known, true ) ) {
						$option['next_step'] = '';
					}
				}
				unset( $option );
			}
		}
		unset( $step );
		return $steps;
	}

	/**
	 * @param int $scope Zero for central support, otherwise a blog ID.
	 * @return array Public configuration, with no inheritance or private settings.
	 */
	public static function config( $scope = 0 ) {
		$scope = absint( $scope );
		if ( is_multisite() && $scope ) {
			$site = get_site( $scope );
			if ( ! $site || (int) $site->network_id !== get_current_network_id() ) {
				return self::defaults();
			}
		}
		$raw = is_multisite() && ! $scope
			? get_site_option( self::OPTION, array() )
			: ( $scope ? get_blog_option( $scope, self::OPTION, array() ) : get_option( self::OPTION, array() ) );
		return self::sanitize( $raw, $scope );
	}

	private static function sanitize( $raw, $scope ) {
		$raw = is_array( $raw ) ? $raw : array();
		$config = self::defaults();
		foreach ( array( 'enabled_chat', 'enabled_tickets' ) as $key ) {
			$config[ $key ] = isset( $raw[ $key ] ) && in_array( $raw[ $key ], array( true, 1, '1' ), true );
		}
		foreach ( array( 'name', 'greeting' ) as $key ) {
			if ( isset( $raw[ $key ] ) ) {
				$config[ $key ] = self::text( $raw[ $key ], 'greeting' === $key );
			}
		}
		$valid = array_map( 'intval', wp_list_pluck( self::categories( $scope ), 'cat_id' ) );
		$ids = array();
		foreach ( array_slice( isset( $raw['topics'] ) && is_array( $raw['topics'] ) ? $raw['topics'] : array(), 0, 50 ) as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$title = self::text( isset( $row['title'] ) ? $row['title'] : '' );
			$answer = self::text( isset( $row['answer'] ) ? $row['answer'] : '', true );
			$steps = self::steps( isset( $row['steps'] ) ? $row['steps'] : array(), $answer );
			if ( '' === $title || ! $steps ) {
				continue;
			}
			$id = isset( $row['id'] ) && is_scalar( $row['id'] ) ? sanitize_key( $row['id'] ) : '';
			if ( ! $id || isset( $ids[ $id ] ) ) {
				$id = 'topic-' . ( count( $config['topics'] ) + 1 );
				while ( isset( $ids[ $id ] ) ) {
					$id .= '-next';
				}
			}
			$ids[ $id ] = true;
			$category = isset( $row['category_id'] ) && is_scalar( $row['category_id'] ) ? absint( $row['category_id'] ) : 0;
			$config['topics'][] = array(
				'id' => $id, 'title' => $title, 'answer' => $answer,
				'category_id' => in_array( $category, $valid, true ) ? $category : 0,
				'steps' => $steps,
			);
		}
		return $config;
	}

	/** @return array|WP_Error Validated settings; invalid submissions are never persisted. */
	public static function validate( $input, $scope = 0 ) {
		if ( ! is_array( $input ) || ! isset( $input['topics'] ) || ! is_array( $input['topics'] ) ) {
			return new WP_Error( 'bot_topics', __( 'Bitte gültige Themenzeilen angeben.', 'psource-support' ) );
		}
		$config = self::sanitize( $input, $scope );
		if ( ! $config['name'] || ! $config['greeting'] ) {
			return new WP_Error( 'bot_name', __( 'Name und Begrüßung dürfen nicht leer sein.', 'psource-support' ) );
		}
		if ( count( $input['topics'] ) > 50 ) {
			return new WP_Error( 'bot_limit', __( 'Maximal 50 Themen sind möglich.', 'psource-support' ) );
		}
		$valid = array_map( 'intval', wp_list_pluck( self::categories( $scope ), 'cat_id' ) );
		foreach ( $input['topics'] as $row ) {
			if ( ! is_array( $row ) || ! self::text( isset( $row['title'] ) ? $row['title'] : '' ) ) {
				return new WP_Error( 'bot_row', __( 'Jeder Ablauf benötigt einen Titel. Leere Zeilen bitte entfernen.', 'psource-support' ) );
			}
			$steps = self::steps( isset( $row['steps'] ) ? $row['steps'] : array(), self::text( isset( $row['answer'] ) ? $row['answer'] : '', true ) );
			if ( ! $steps ) {
				return new WP_Error( 'bot_steps', __( 'Jeder Ablauf benötigt mindestens einen vollständigen Schritt.', 'psource-support' ) );
			}
			foreach ( $steps as $step ) {
				if ( ( in_array( $step['type'], array( 'message', 'choice', 'input' ), true ) && '' === $step['text'] && '' === $step['label'] )
					|| ( 'choice' === $step['type'] && ! $step['options'] )
					|| ( 'link' === $step['type'] && ( '' === $step['label'] || empty( $step['url'] ) ) ) ) {
					return new WP_Error( 'bot_step', __( 'Bitte alle Schritte vollständig konfigurieren.', 'psource-support' ) );
				}
			}
			$category = isset( $row['category_id'] ) ? $row['category_id'] : '0';
			if ( ! is_scalar( $category ) || ! preg_match( '/^\d+$/', (string) $category ) || ( (int) $category && ! in_array( (int) $category, $valid, true ) ) ) {
				return new WP_Error( 'bot_category', __( 'Bitte eine gültige Kategorie dieses Supportbereichs wählen.', 'psource-support' ) );
			}
		}
		if ( ( $config['enabled_chat'] || $config['enabled_tickets'] ) && ! $config['topics'] ) {
			return new WP_Error( 'bot_empty', __( 'Für einen aktiven Bot ist mindestens ein vollständiges Thema erforderlich.', 'psource-support' ) );
		}
		return $config;
	}

	private static function settings_scope() {
		return is_multisite() && ! is_network_admin() ? get_current_blog_id() : 0;
	}

	private static function url( $network ) {
		$path = 'admin.php?page=' . ( is_multisite() && ! $network ? 'psource-support-site-settings' : 'mu-support-settings' ) . '&tab=supportbot';
		return $network ? network_admin_url( $path ) : admin_url( $path );
	}

	private static function draft_key( $network ) {
		return 'ps_support_bot_draft_' . get_current_user_id() . '_' . ( $network ? 'network' : get_current_blog_id() );
	}

	public static function page() {
		if ( ! current_user_can( is_network_admin() ? 'manage_network_options' : 'manage_options' ) ) {
			return;
		}
		$scope = self::settings_scope();
		$config = self::config( $scope );
		$draft = get_transient( self::draft_key( is_network_admin() ) );
		$error = '';
		if ( is_array( $draft ) ) {
			$config = $draft['input'];
			$error = $draft['error'];
			delete_transient( self::draft_key( is_network_admin() ) );
		}
		$categories = self::categories( $scope );
		wp_enqueue_style( 'ps-support-bot', PSOURCE_SUPPORT_PLUGIN_URL . 'assets/css/support-bot.css', array(), '1.0' );
		wp_enqueue_script( 'ps-support-bot-settings', PSOURCE_SUPPORT_PLUGIN_URL . 'admin/assets/js/support-bot-settings.js', array(), '1.0', true );
		require PSOURCE_SUPPORT_PLUGIN_DIR . 'admin/views/support-bot-settings.php';
	}

	public static function save() {
		$network = isset( $_POST['network'] ) && '1' === $_POST['network'];
		$scope = is_multisite() && ! $network ? get_current_blog_id() : 0;
		$input = isset( $_POST['bot'] ) && is_array( $_POST['bot'] ) ? wp_unslash( $_POST['bot'] ) : array();
		// An empty set of topics cannot be represented by native HTML inputs.
		if ( ! isset( $input['topics'] ) ) {
			$input['topics'] = array();
		}
		$allowed = current_user_can( $network ? 'manage_network_options' : 'manage_options' );
		$nonce = isset( $_POST['_wpnonce'] ) && is_string( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'ps_support_bot_save' );
		$result = $allowed && $nonce ? self::validate( $input, $scope ) : new WP_Error( 'bot_permission', __( 'Keine Berechtigung oder Sitzung abgelaufen. Bitte die Seite neu laden.', 'psource-support' ) );
		if ( ! is_wp_error( $result ) ) {
			$changed = $network && is_multisite() ? update_site_option( self::OPTION, $result ) : update_option( self::OPTION, $result );
			if ( ! $changed && self::config( $scope ) !== $result ) {
				$result = new WP_Error( 'bot_save', __( 'Speichern fehlgeschlagen. Bitte erneut versuchen.', 'psource-support' ) );
			}
		}
		if ( wp_doing_ajax() ) {
			if ( is_wp_error( $result ) ) {
				wp_send_json_error( array( 'message' => $result->get_error_message() ) );
			}
			wp_send_json_success( array( 'message' => __( 'Support-Bot gespeichert.', 'psource-support' ) ) );
		}
		if ( is_wp_error( $result ) && $allowed ) {
			set_transient( self::draft_key( $network ), array( 'input' => $input, 'error' => $result->get_error_message() ), 10 * MINUTE_IN_SECONDS );
		}
		wp_safe_redirect( is_wp_error( $result ) ? self::url( $network ) : add_query_arg( 'saved', '1', self::url( $network ) ) );
		exit;
	}

	/** Load before the document head, including on shortcode-driven frontend pages. */
	public static function ticket_assets() {
		$config = self::config( psource_support_get_data_blog_id() );
		if ( $config['enabled_tickets'] && $config['topics'] && psource_support_current_user_can( 'insert_ticket' ) ) {
			wp_enqueue_style( 'ps-support-bot', PSOURCE_SUPPORT_PLUGIN_URL . 'assets/css/support-bot.css', array(), '1.0' );
			wp_enqueue_script( 'ps-support-bot', PSOURCE_SUPPORT_PLUGIN_URL . 'assets/js/support-bot.js', array(), '1.0', true );
		}
	}

	/** Render only inside an existing, permission-checked ticket creation form. */
	public static function ticket_ui() {
		$config = self::config( psource_support_get_data_blog_id() );
		if ( ! $config['enabled_tickets'] || ! $config['topics'] ) {
			return;
		}
		wp_enqueue_style( 'ps-support-bot', PSOURCE_SUPPORT_PLUGIN_URL . 'assets/css/support-bot.css', array(), '1.0' );
		wp_enqueue_script( 'ps-support-bot', PSOURCE_SUPPORT_PLUGIN_URL . 'assets/js/support-bot.js', array(), '1.0', true );
		echo '<section class="ps-support-bot" aria-label="' . esc_attr( $config['name'] ) . '" data-config="' . esc_attr( wp_json_encode( $config ) ) . '" data-solved="' .
			esc_attr__( 'Als gelöst markiert. Es wurde kein Ticket erstellt. Das Formular bleibt verfügbar.', 'psource-support' ) . '" data-handoff="' .
			esc_attr__( 'Die Zusammenfassung wurde in das bearbeitbare Formular übernommen. Bitte ergänzen und das Ticket selbst absenden.', 'psource-support' ) . '">';
		echo '<h3>' . esc_html( $config['name'] ) . '</h3><p>' . esc_html( $config['greeting'] ) . '</p>';
		echo '<div class="ps-bot-topics"></div><div class="ps-bot-answer" aria-live="polite"></div><div class="ps-bot-actions">';
		foreach ( array( 'solved' => __( 'Gelöst', 'psource-support' ), 'handoff' => __( 'Support kontaktieren / Ticket vorbereiten', 'psource-support' ), 'reset' => __( 'Neu starten', 'psource-support' ), 'skip' => __( 'Direkt zum Ticketformular', 'psource-support' ) ) as $action => $label ) {
			echo '<button type="button" class="button" data-bot-action="' . esc_attr( $action ) . '">' . esc_html( $label ) . '</button> ';
		}
		echo '</div><p class="ps-bot-status" role="status"></p><noscript>' . esc_html__( 'Das Ticketformular steht auch ohne JavaScript zur Verfügung.', 'psource-support' ) . '</noscript></section>';
	}
}
