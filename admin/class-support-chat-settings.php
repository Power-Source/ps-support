<?php
defined( 'ABSPATH' ) || exit;

class PSource_Support_Chat_Settings {
	const OPTION = 'psource_chat_support';

	public static function boot() {
		add_action( 'admin_menu', array( __CLASS__, 'menus' ), 40 );
		add_action( 'network_admin_menu', array( __CLASS__, 'menus' ), 40 );
		add_action( 'admin_post_ps_chat_support_settings', array( __CLASS__, 'save' ) );
		add_filter( 'support_system_settings_tabs', array( __CLASS__, 'tabs' ) );
		add_filter( 'support_system_render_tab_function', array( __CLASS__, 'render_tab' ) );
		add_action( 'admin_init', array( __CLASS__, 'redirect_legacy_page' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'wp_ajax_ps_support_chat_staff', array( __CLASS__, 'search_staff' ) );
		add_action( 'wp_ajax_ps_chat_support_settings', array( __CLASS__, 'save' ) );
	}

	public static function assets() {
		if ( isset( $_GET['page'] ) && ( $_GET['page'] === 'ticket-categories' ||
			( isset( $_GET['tab'] ) && $_GET['tab'] === 'supportchat' && in_array( $_GET['page'], array( 'mu-support-settings', 'psource-support-site-settings' ), true ) ) ) ) {
			wp_enqueue_style( 'support-menu-styles', plugins_url( 'assets/css/support-menu.css', __FILE__ ), array(),
				filemtime( __DIR__ . '/assets/css/support-menu.css' ) );
			wp_enqueue_script( 'ps-support-user-picker', plugins_url( 'assets/js/support-user-picker.js', __FILE__ ), array(),
				filemtime( __DIR__ . '/assets/js/support-user-picker.js' ), true );
			wp_localize_script( 'ps-support-user-picker', 'psSupportPicker', array(
				'url' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( 'ps_support_chat_staff' ),
				'network' => is_network_admin() ? '1' : '0',
				'empty' => __( 'Keine passenden Personen gefunden.', 'psource-support' ),
				'error' => __( 'Die Suche ist fehlgeschlagen. Bitte erneut versuchen.', 'psource-support' ),
				'hint' => __( 'Mindestens zwei Zeichen eingeben.', 'psource-support' ),
				'more' => __( 'Weitere Treffer vorhanden. Bitte die Suche eingrenzen.', 'psource-support' ),
				'remove' => __( 'Entfernen:', 'psource-support' ),
			) );
		}
		if ( isset( $_GET['page'] ) && $_GET['page'] === 'psource-support-site-settings' ) {
			wp_enqueue_style( 'support-menu-styles', plugins_url( 'assets/css/support-menu.css', __FILE__ ), array(),
				filemtime( __DIR__ . '/assets/css/support-menu.css' ) );
		}
		if ( isset( $_GET['page'], $_GET['tab'] ) && in_array( $_GET['page'], array( 'mu-support-settings', 'psource-support-site-settings' ), true ) && $_GET['tab'] === 'supportchat' ) {
			wp_enqueue_script( 'ps-support-chat-settings', plugins_url( 'assets/js/support-chat-settings.js', __FILE__ ), array(),
				filemtime( __DIR__ . '/assets/js/support-chat-settings.js' ), true );
			wp_localize_script( 'ps-support-chat-settings', 'psSupportStaff', array(
				'url' => admin_url( 'admin-ajax.php' ),
				'required' => __( 'Bitte mindestens einen Support-Mitarbeiter auswählen.', 'psource-support' ),
				'retention' => __( 'Die Löschfrist muss mindestens so lang sein wie der Browser-Zugang.', 'psource-support' ),
				'saved' => __( 'Supportchat-Einstellungen gespeichert.', 'psource-support' ),
				'saveError' => __( 'Speichern fehlgeschlagen. Deine Eingaben bleiben erhalten. Bitte erneut versuchen.', 'psource-support' ),
			) );
		}
	}

	public static function url( $network = null ) {
		$network = $network === null ? is_network_admin() : $network;
		$page = ! $network && is_multisite() ? 'psource-support-site-settings' : 'mu-support-settings';
		$path = 'admin.php?page=' . $page . '&tab=supportchat';
		return $network ? network_admin_url( $path ) : admin_url( $path );
	}

	public static function tabs( $tabs ) {
		$tabs['supportchat'] = __( 'Supportchat', 'psource-support' );
		return $tabs;
	}

	public static function render_tab( $callback ) {
		return isset( $_GET['tab'] ) && $_GET['tab'] === 'supportchat' ? array( __CLASS__, 'page' ) : $callback;
	}

	public static function redirect_legacy_page() {
		if ( isset( $_GET['page'] ) && $_GET['page'] === 'ps-chat-support-settings' ) {
			wp_safe_redirect( self::url() );
			exit;
		}
	}

	public static function settings( $network = false ) {
		$value = $network ? get_site_option( self::OPTION, array() ) : get_option( self::OPTION, array() );
		return wp_parse_args( is_array( $value ) ? $value : array(), array(
			'mode' => 'disabled', 'allow_subsite_chat' => false,
			'placement' => 'floating', 'label' => __( 'Support', 'psource-support' ),
			'intro_text' => '', 'require_name' => false, 'require_email' => false,
			'session_hours' => 168, 'retention_days' => 30, 'fallback_users' => array(),
		) );
	}

	public static function central_blog_id() {
		if ( ! is_multisite() ) {
			return get_current_blog_id();
		}
		$id = absint( psource_support_get_setting( 'psource_support_blog_id' ) );
		$site = $id ? get_site( $id ) : null;
		return $site && (int) $site->network_id === get_current_network_id() ? $id : get_main_site_id();
	}

	public static function menus() {
		add_submenu_page( 'ticket-manager', __( 'Supportchats', 'psource-support' ),
			__( 'Supportchats', 'psource-support' ), 'read', 'ps-chat-support-inbox', array( __CLASS__, 'inbox' ) );
	}

	/**
	 * Existing support roles and explicitly assigned category teams, never all visitors.
	 *
	 * @param int $scope Zero for central support, otherwise the local blog ID.
	 * @return int[]
	 */
	public static function staff_ids( $scope = 0 ) {
		if ( psource_support_get_setting( 'psource_support_crm_sync_enabled' ) ) {
			return array_map( 'absint', wp_list_pluck( psource_support_get_available_assignees(), 'user_id' ) );
		}
		$roles = (array) psource_support_get_setting( 'psource_support_staff_roles' );
		if ( $scope && is_multisite() ) {
			$config = psource_support_get_subsite_front_settings( $scope );
			if ( $config && ! empty( $config['staff_roles'] ) ) {
				$roles = (array) $config['staff_roles'];
			}
		}
		$roles[] = 'administrator';
		$blogs = ! $scope && is_multisite()
			? get_sites( array( 'network_id' => get_current_network_id(), 'fields' => 'ids', 'number' => 0 ) )
			: array( $scope ? $scope : get_current_blog_id() );
		$ids = array();
		foreach ( $blogs as $blog_id ) {
			$ids = array_merge( $ids, get_users( array( 'blog_id' => $blog_id, 'role__in' => $roles, 'fields' => 'ID' ) ) );
		}
		if ( ! $scope && is_multisite() ) {
			foreach ( get_super_admins() as $login ) {
				$user = get_user_by( 'login', $login );
				if ( $user ) {
					$ids[] = $user->ID;
				}
			}
		}
		global $wpdb;
		$categories = psource_support_get_ticket_category_rows( $wpdb->prepare(
			'WHERE site_id = %d AND blog_id = %d', get_current_network_id(), $scope
		) );
		foreach ( $categories as $category ) {
			$team = get_network_option( get_current_network_id(), psource_support_category_team_option_name( $category ), array() );
			foreach ( $team ? $team : array( $category->user_id ) as $id ) {
				if ( get_userdata( $id ) && ( ! is_multisite() || is_super_admin( $id ) ||
					( $scope ? is_user_member_of_blog( $id, $scope ) : array_intersect( array_keys( get_blogs_of_user( $id ) ), $blogs ) ) ) ) {
					$ids[] = $id;
				}
			}
		}
		return array_values( array_unique( array_map( 'absint', $ids ) ) );
	}

	public static function search_staff() {
		check_ajax_referer( 'ps_support_chat_staff', 'nonce' );
		$network = isset( $_GET['network'] ) && $_GET['network'] === '1';
		if ( ! current_user_can( $network ? 'manage_network_options' : 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Keine Berechtigung.', 'psource-support' ) ), 403 );
		}
		$query = isset( $_GET['q'] ) && is_string( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
		$scope = $network || ! is_multisite() ? 0 : get_current_blog_id();
		$kind = isset( $_GET['kind'] ) && $_GET['kind'] === 'category' ? 'category' : 'staff';
		if ( strlen( $query ) < 2 ) {
			wp_send_json_success( array( 'users' => array(), 'more' => false ) );
		}
		$exclude = isset( $_GET['exclude'] ) && is_string( $_GET['exclude'] ) ? array_map( 'absint', explode( ',', $_GET['exclude'] ) ) : array();
		wp_send_json_success( self::staff_search( $scope, $query, $kind, $exclude ) );
	}

	/**
	 * @param int    $scope Support scope.
	 * @param string $query Display name or login search.
	 * @return array
	 */
	public static function staff_search( $scope, $query, $kind = 'staff', $exclude = array() ) {
		$ids = $kind === 'category' ? array_map( 'absint', wp_list_pluck( psource_support_get_category_available_assignees( $scope ), 'user_id' ) ) : self::staff_ids( $scope );
		$ids = array_values( array_diff( $ids, $exclude ) );
		$users = $ids ? get_users( array(
			'blog_id' => 0, 'include' => $ids, 'number' => 21,
			'search' => $query === '' ? '' : '*' . $query . '*',
			'search_columns' => array( 'display_name', 'user_login' ),
			'orderby' => 'display_name', 'order' => 'ASC',
		) ) : array();
		return array(
			'more' => count( $users ) > 20,
			'users' => array_map( function ( $user ) {
				return array( 'id' => $user->ID, 'label' => $user->display_name . ' (@' . $user->user_login . ')' );
			}, array_slice( $users, 0, 20 ) ),
		);
	}

	public static function page() {
		$network = is_network_admin();
		if ( ! current_user_can( $network ? 'manage_network_options' : 'manage_options' ) ) {
			wp_die( esc_html__( 'Keine Berechtigung.', 'psource-support' ) );
		}
		$settings = self::settings( $network );
		$draft_key = 'ps_chat_settings_' . get_current_user_id() . '_' . get_current_blog_id() . '_' . (int) $network;
		$draft = get_transient( $draft_key );
		$form_error = '';
		if ( is_array( $draft ) ) {
			delete_transient( $draft_key );
			$settings = array_merge( $settings, $draft['input'] );
			$form_error = $draft['error'];
		}
		$central_blog_id = self::central_blog_id();
		$central = is_multisite() && ! $network && get_current_blog_id() === $central_blog_id;
		$network_settings = self::settings( true );
		$eligible = self::staff_ids( $network || ! is_multisite() ? 0 : get_current_blog_id() );
		require __DIR__ . '/views/support-chat-settings.php';
	}

	public static function inbox() {
		if ( class_exists( 'PSource_Chat_Support' ) ) {
			PSource_Chat_Support::inbox_page();
		} else {
			echo '<div class="wrap"><h1>' . esc_html__( 'Supportchats', 'psource-support' ) . '</h1><p>' .
				esc_html__( 'PS Chat ist auf dieser Site nicht aktiv. Aktiviere das Plugin, um Supportchats zu bearbeiten.', 'psource-support' ) . '</p></div>';
		}
	}

	public static function validate( $input, $network = false ) {
		$error = function ( $message ) {
			return new WP_Error( 'ps_chat_support_settings', $message, array( 'status' => 400 ) );
		};
		$modes = $network ? array( 'disabled', 'network' ) : ( is_multisite() ? array( 'disabled', 'local', 'network' ) : array( 'disabled', 'local' ) );
		if ( isset( $input['fallback_users'] ) && is_array( $input['fallback_users'] ) ) {
			foreach ( $input['fallback_users'] as $id ) {
				if ( ! is_scalar( $id ) || ! ctype_digit( (string) $id ) || (int) $id < 1 ) {
					return $error( __( 'Bitte gültige Mitarbeiter auswählen.', 'psource-support' ) );
				}
			}
			$input['fallback_users'] = implode( ',', $input['fallback_users'] );
		}
		$intro = isset( $input['intro_text'] ) ? $input['intro_text'] : '';
		if ( ! is_string( $intro ) || strlen( $intro ) > 8000 || ( function_exists( 'mb_strlen' ) ? mb_strlen( $intro ) : strlen( $intro ) ) > 2000 ) {
			return $error( __( 'Der Besucher-Hinweis darf maximal 2000 Zeichen enthalten.', 'psource-support' ) );
		}
		if ( ! isset( $input['mode'], $input['placement'], $input['label'], $input['session_hours'], $input['retention_days'], $input['fallback_users'] )
			|| ! in_array( $input['mode'], $modes, true ) || ! in_array( $input['placement'], array( 'floating', 'embedded' ), true )
			|| ! is_string( $input['label'] ) || ! trim( sanitize_text_field( $input['label'] ) ) || strlen( $input['label'] ) > 100
			|| ! is_scalar( $input['session_hours'] ) || ! ctype_digit( (string) $input['session_hours'] )
			|| ! is_scalar( $input['retention_days'] ) || ! ctype_digit( (string) $input['retention_days'] )
			|| ! is_string( $input['fallback_users'] ) || ! preg_match( '/^\s*(?:\d+(?:\s*,\s*\d+)*)?\s*$/D', $input['fallback_users'] ) ) {
			return $error( __( 'Bitte Auswahl, Chatname, Mitarbeiter und Fristen prüfen.', 'psource-support' ) );
		}
		foreach ( array( 'require_name', 'require_email', 'allow_subsite_chat' ) as $flag ) {
			if ( isset( $input[ $flag ] ) && ! in_array( $input[ $flag ], array( '0', '1', 0, 1, false, true ), true ) ) {
				return $error( __( 'Ungültige Supportchat-Auswahl.', 'psource-support' ) );
			}
		}
		$hours = (int) $input['session_hours'];
		$days = (int) $input['retention_days'];
		if ( $hours < 1 || $hours > 2160 || $days < 1 || $days > 365 || $days * 24 < $hours ) {
			return $error( __( 'Browser-Zugang: 1–2160 Stunden. Löschfrist: 1–365 Tage und mindestens so lang wie der Browser-Zugang.', 'psource-support' ) );
		}
		$ids = array_values( array_unique( array_filter( array_map( 'absint', explode( ',', $input['fallback_users'] ) ) ) ) );
		$eligible = self::staff_ids( $network || ! is_multisite() ? 0 : get_current_blog_id() );
		foreach ( $ids as $id ) {
			if ( ! in_array( $id, $eligible, true ) ) {
				return $error( __( 'Bitte nur Support-Mitarbeiter auswählen. Die Zuständigkeit wird über Mitarbeiter-Rollen, Ticketkategorien oder die CRM-Anbindung festgelegt.', 'psource-support' ) );
			}
		}
		if ( ( $input['mode'] === 'local' || $network && $input['mode'] === 'network' ) && ! $ids ) {
			return $error( __( 'Bitte mindestens einen Mitarbeiter für Anfragen ohne Kategorie-Zuständigkeit auswählen.', 'psource-support' ) );
		}
		return array(
			'mode' => $input['mode'], 'allow_subsite_chat' => $network && ! empty( $input['allow_subsite_chat'] ),
			'placement' => $input['placement'], 'label' => sanitize_text_field( $input['label'] ),
			'intro_text' => sanitize_textarea_field( $intro ),
			'require_name' => ! empty( $input['require_name'] ), 'require_email' => ! empty( $input['require_email'] ),
			'session_hours' => $hours, 'retention_days' => $days, 'fallback_users' => $ids,
		);
	}

	public static function save() {
		$network = ! empty( $_POST['network'] );
		if ( ! current_user_can( $network ? 'manage_network_options' : 'manage_options' ) ) {
			if ( wp_doing_ajax() ) {
				wp_send_json_error( array( 'message' => __( 'Keine Berechtigung. Bitte erneut anmelden.', 'psource-support' ) ), 403 );
			}
			wp_die( esc_html__( 'Keine Berechtigung.', 'psource-support' ) );
		}
		if ( wp_doing_ajax() && ! check_ajax_referer( 'ps_chat_support_settings', '_wpnonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Die Sitzung ist abgelaufen. Bitte die Seite neu laden.', 'psource-support' ) ), 403 );
		}
		check_admin_referer( 'ps_chat_support_settings' );
		$input = isset( $_POST['support'] ) && is_array( $_POST['support'] ) ? wp_unslash( $_POST['support'] ) : array();
		$settings = self::validate( $input, $network );
		if ( ! is_wp_error( $settings ) && ! $network && is_multisite() ) {
			$policy = self::settings( true );
			if ( get_current_blog_id() === self::central_blog_id() ) {
				$settings = new WP_Error( 'policy', __( 'Der zentrale Supportchat wird in der Netzwerkverwaltung unter Support → Einstellungen → Supportchat konfiguriert.', 'psource-support' ) );
			} elseif ( $settings['mode'] !== 'disabled' && ! $policy['allow_subsite_chat'] ) {
				$settings = new WP_Error( 'policy', __( 'Supportchats auf Subsites sind in der Netzwerkverwaltung nicht freigegeben.', 'psource-support' ) );
			} elseif ( $settings['mode'] === 'network' && $policy['mode'] !== 'network' ) {
				$settings = new WP_Error( 'policy', __( 'Der Netzwerk-Supportchat ist nicht aktiviert.', 'psource-support' ) );
			}
		}
		if ( ! is_wp_error( $settings ) ) {
			$changed = $network ? update_site_option( self::OPTION, $settings ) : update_option( self::OPTION, $settings );
			if ( ! $changed && self::settings( $network ) !== $settings ) {
				$settings = new WP_Error( 'save', __( 'Die Supportchat-Einstellungen konnten nicht gespeichert werden.', 'psource-support' ) );
			}
		}
		if ( wp_doing_ajax() ) {
			if ( is_wp_error( $settings ) ) {
				wp_send_json_error( array( 'message' => $settings->get_error_message() ), 400 );
			}
			wp_send_json_success();
		}
		if ( is_wp_error( $settings ) ) {
			$draft = self::settings( $network );
			foreach ( $draft as $key => $value ) {
				if ( $key === 'fallback_users' ) {
					$raw = isset( $input[ $key ] ) ? $input[ $key ] : array();
					$draft[ $key ] = array_values( array_filter( array_map( 'absint', array_filter( is_array( $raw ) ? $raw : explode( ',', (string) $raw ), 'is_scalar' ) ) ) );
				} elseif ( is_bool( $value ) ) {
					$draft[ $key ] = ! empty( $input[ $key ] );
				} elseif ( isset( $input[ $key ] ) && is_scalar( $input[ $key ] ) ) {
					$draft[ $key ] = sanitize_textarea_field( (string) $input[ $key ] );
				}
			}
			set_transient( 'ps_chat_settings_' . get_current_user_id() . '_' . get_current_blog_id() . '_' . (int) $network,
				array( 'input' => $draft, 'error' => $settings->get_error_message() ), 10 * MINUTE_IN_SECONDS );
			wp_safe_redirect( self::url( $network ) );
			exit;
		}
		$url = add_query_arg( 'saved', '1', self::url( $network ) );
		wp_safe_redirect( $url );
		exit;
	}
}
