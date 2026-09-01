<?php

class PSource_Support_Admin_Support_Menu extends PSource_Support_Parent_Support_Menu {

	public function add_menu() {
		$settings = psource_support_get_settings();
		$menu_title = esc_html( $settings['psource_support_menu_name'] );

		parent::add_menu_page(
			$menu_title,
			$menu_title, 
			'read',
			'dashicons-sos'
		);

		add_action( 'load-' . $this->page_id, array( $this, 'maybe_insert_new_ticket' ) );
		add_action( 'load-' . $this->page_id, array( $this, 'set_filters' ) );

		if ( psource_support_current_user_can( 'insert_ticket' ) ) {
			add_submenu_page( 
				$this->slug, 
				__( 'Neues Ticket erstellen', 'psource-support' ), 
				__( 'Neues Ticket erstellen', 'psource-support' ), 
				'read', 
				"admin.php?page=$this->slug&action=add" 
			);
		}

		if ( psource_support_subsite_settings_available() && current_user_can( 'manage_options' ) ) {
			$page_id = add_submenu_page(
				$this->slug,
				__( 'Support-Einstellungen', 'psource-support' ),
				__( 'Support-Einstellungen', 'psource-support' ),
				'manage_options',
				'psource-support-site-settings',
				array( $this, 'render_site_settings' )
			);
			add_action( 'load-' . $page_id, array( $this, 'save_site_settings' ) );
		}
	}

	public function render_site_settings() {
		$blog_id = get_current_blog_id();
		$config = psource_support_get_subsite_front_settings( $blog_id );
		if ( ! $config ) {
			$config = array(
				'active' => false, 'forced_by' => array(), 'forced_features' => array(), 'tickets_enabled' => false, 'faqs_enabled' => false,
				'support_page_id' => 0, 'new_ticket_page_id' => 0, 'faqs_page_id' => 0,
				'staff_roles' => array(),
			);
		}
		$page_dropdowns = array();
		foreach ( array( 'support_page_id', 'new_ticket_page_id', 'faqs_page_id' ) as $page_key ) {
			$page_dropdowns[ $page_key ] = wp_dropdown_pages( array(
				'selected' => absint( $config[ $page_key ] ), 'show_option_none' => __( '-- Automatisch erstellen --', 'psource-support' ),
				'name' => $page_key, 'echo' => false,
			) );
		}
		$roles = MU_Support_System::get_roles();
		include( 'views/admin-support-settings.php' );
	}

	public function save_site_settings() {
		if ( empty( $_POST['save-site-support-settings'] ) ) {
			return;
		}
		check_admin_referer( 'save-site-support-settings' );
		if ( ! current_user_can( 'manage_options' ) || ! psource_support_subsite_settings_available() ) {
			wp_die( esc_html__( 'Nicht ausreichend Berechtigungen.', 'psource-support' ) );
		}

		$blog_id = get_current_blog_id();
		$all = (array) psource_support_get_setting( 'psource_support_subsite_pages' );
		$previous = isset( $all[ $blog_id ] ) ? (array) $all[ $blog_id ] : array();
		$forced_by = isset( $previous['forced_by'] ) ? (array) $previous['forced_by'] : array();
		$forced_features = isset( $previous['forced_features'] ) ? (array) $previous['forced_features'] : array();
		$forced_tickets = false;
		$forced_faqs = false;
		foreach ( $forced_features as $features ) {
			$forced_tickets = $forced_tickets || ! empty( $features['tickets'] );
			$forced_faqs = $forced_faqs || ! empty( $features['faqs'] );
		}
		$config = array(
			'active' => psource_support_get_setting( 'psource_support_allow_subsite_support' ) && ! empty( $_POST['active'] ),
			'forced_by' => $forced_by,
			'forced_features' => $forced_features,
			'tickets_enabled' => $forced_tickets || ! empty( $_POST['tickets_enabled'] ),
			'faqs_enabled' => $forced_faqs || ! empty( $_POST['faqs_enabled'] ),
			'support_page_id' => isset( $_POST['support_page_id'] ) ? absint( $_POST['support_page_id'] ) : 0,
			'new_ticket_page_id' => isset( $_POST['new_ticket_page_id'] ) ? absint( $_POST['new_ticket_page_id'] ) : 0,
			'faqs_page_id' => isset( $_POST['faqs_page_id'] ) ? absint( $_POST['faqs_page_id'] ) : 0,
			'staff_roles' => array_values( array_filter( array_map( 'sanitize_key', isset( $_POST['staff_roles'] ) ? (array) $_POST['staff_roles'] : array() ) ) ),
		);
		if ( ! empty( $_POST['create_pages'] ) ) {
			if ( $config['tickets_enabled'] ) {
				$config['support_page_id'] = psource_support_ensure_subsite_page( $config['support_page_id'], __( 'Support', 'psource-support' ), '[support-system-tickets-index]' );
				$config['new_ticket_page_id'] = psource_support_ensure_subsite_page( $config['new_ticket_page_id'], __( 'Supportanfrage', 'psource-support' ), '[support-system-submit-ticket-form blog_field="false"]' );
			}
			if ( $config['faqs_enabled'] ) {
				$config['faqs_page_id'] = psource_support_ensure_subsite_page( $config['faqs_page_id'], __( 'Häufige Fragen', 'psource-support' ), '[support-system-faqs]' );
			}
		}
		$all[ $blog_id ] = $config;
		psource_support_update_setting( 'psource_support_subsite_pages', $all );
		wp_safe_redirect( add_query_arg( 'updated', 'true', admin_url( 'admin.php?page=psource-support-site-settings' ) ) );
		exit;
	}

	public function set_filters() {
		if ( ! isset( $_GET['action'] ) && psource_support_current_user_can( 'insert_ticket' ) )
			add_filter( 'support_system_admin_page_title', array( $this, 'add_new_ticket_link' ) );

		if ( isset( $_GET['action'] ) && isset( $_GET['tid'] ) && 'edit' === $_GET['action'] )
			add_filter( 'support_system_admin_page_title', '__return_empty_string' );

		if ( isset( $_GET['action'] ) && 'add' === $_GET['action'] )
			add_filter( 'support_system_admin_page_title', array( $this, 'add_new_ticket_title' ) );

		// Tickets table filters
		add_filter( 'support_system_tickets_table_query_args', array( $this, 'set_tickets_table_query_args' ) );
		add_filter( 'support_system_support_menu_counts_args', array( $this, 'set_counts_args' ) );
		add_filter( 'support_network_ticket_columns', array( $this, 'set_tickets_table_columns' ) );
	}

	public function add_new_ticket_link( $title ) {
		$settings = psource_support_get_settings();
		$menu_title = esc_html( $settings['psource_support_menu_name'] );
		$add_new_link = add_query_arg( 'action', 'add', $this->get_menu_url() );
		return '<h2>'. $menu_title . ' <a href="' . esc_url( $add_new_link ) . '" class="add-new-h2">' . esc_html__( 'Neues Ticket erstellen', 'psource-support' ) . '</a></h2>';
	}

	public function add_new_ticket_title( $title ) {
		return '<h2>' . __( 'Neues Ticket erstellen', 'psource-support' ) . '</h2>';
	}

	public function render_inner_page() {

		$action = isset( $_GET['action'] ) ? $_GET['action'] : false;

		if ( 'edit' == $action && isset( $_GET['tid'] ) ) {
			$this->render_inner_page_details();
		}
		elseif ( 'add' == $action && psource_support_current_user_can( 'insert_ticket' ) ) {

			$priority = 0;
			if ( isset( $_POST['priority'] ) )
				$priority = absint( $_POST['priority'] );

			
			if ( isset( $_POST['category'] ) && $selected_category = psource_sbe_get_ticket_category( absint( $_POST['category'] ) ) ) {
				$category = $selected_category;
			}
			else {
				$category = psource_support_get_default_ticket_category();
			}

			// Priorities dropdown
			$priorities_dropdown = psource_support_priority_dropdown(
				array( 
					'show_empty' => false,
					'echo' => false,
					'selected' => $priority
				) 
			);

			// Categories dropdown
			$categories_dropdown = psource_support_ticket_categories_dropdown(
				array( 
					'show_empty' => false,
					'echo' => false,
					'selected' => $category->cat_id
				) 
			);

			$message = '';
			if ( ! empty( $_POST['message-text'] ) )
				$message = stripslashes_deep( $_POST['message-text'] );

			$subject = '';
			if ( ! empty( $_POST['subject'] ) )
				$subject = strip_tags( stripslashes_deep( $_POST['subject'] ) );
			
			include( 'views/add-new-ticket.php' );
		}		
		else {

			$this->render_inner_page_tickets_table();
		}

	}

	public function maybe_insert_new_ticket() {
		if ( isset( $_POST['submit-new-ticket'] ) ) {
			check_admin_referer( 'add-new-ticket' );

			$args = array();

			if ( empty( $_POST['message-text'] ) )
				add_settings_error( 'support_system_submit_new_ticket', 'empty_message', __( 'Die Ticketnachricht darf nicht leer sein', 'psource-support' ) );
			else
				$args['message'] = wpautop( stripslashes_deep( $_POST['message-text'] ) );

			$title = strip_tags( stripslashes_deep( $_POST['subject'] ) );
			if ( empty( $title ) )
				add_settings_error( 'support_system_submit_new_ticket', 'empty_subject', __( 'Der Betreff des Tickets darf nicht leer sein', 'psource-support' ) );
			else
				$args['title'] = $title;

			$category = psource_support_get_ticket_category( absint( $_POST['ticket-cat'] ) );
			if ( ! $category ) {
				add_settings_error( 'support_system_submit_new_ticket', 'wrong_category', __( 'Die ausgewählte Kategorie ist nicht gültig', 'psource-support' ) );
			}
			else {
				$args['cat_id'] = $category->cat_id;
			}

			$args['ticket_priority'] = isset( $_POST['ticket-priority'] ) ? absint( $_POST['ticket-priority'] ) : 0;

			if ( ! empty( $_FILES['support-attachment'] ) ) {
				$files_uploaded = psource_support_upload_ticket_attachments( $_FILES['support-attachment'] );					

				if ( ! $files_uploaded['error'] && ! empty( $files_uploaded['result'] ) ) {
					$args['attachments'] = wp_list_pluck( $files_uploaded['result'], 'url' );
				}
				elseif ( $files_uploaded['error'] && ! empty( $files_uploaded['result'] ) ) {
					foreach ( $files_uploaded['result'] as $error ) {
						add_settings_error( 'support_system_submit_new_ticket', 'file_upload_error', $error );			
					}
				}
			}

			if ( ! get_settings_errors( 'support_system_submit_new_ticket' ) ) {
				if ( is_super_admin() )
					$args['view_by_superadmin'] = 1;

				$result = psource_support_insert_ticket( $args );
				if ( is_wp_error( $result ) ) {
					add_settings_error( 'support_system_submit_new_ticket', 'insert_error', $result->get_error_message() );
				}
				else {
					$redirect_to = add_query_arg(
						array(
							'action' => 'edit',
							'tid' => $result,
						),
						$this->get_menu_url()
					);
					wp_redirect( $redirect_to );
					exit();
				}
			}
		}
	}


	public function set_tickets_table_query_args( $args ) {
		$settings = psource_support_get_settings();

		$category = $this->get_filter( 'category' );
		$priority = $this->get_filter( 'priority' );
		$s = $this->get_filter( 's' );
		$assignment = $this->get_assignment_filter();
		$ticket_status = $this->get_ticket_status_filter();

		$args['priority'] = $priority;
		$args['category'] = $category;
		$args['s'] = $s;
		$args['blog_id'] = get_current_blog_id();
		$args['ticket_status'] = $ticket_status;

		if ( 'mine' === $assignment )
			$args['admin_id'] = get_current_user_id();
		elseif ( 'unassigned' === $assignment )
			$args['admin_id'] = 0;
		elseif ( 'assigned' === $assignment )
			$args['has_admin'] = true;

		return $args;
	}

	public function set_counts_args( $args ) {
		$settings = psource_support_get_settings();

		$args['blog_id'] = get_current_blog_id();

		if ( 'requestor' === $settings['psource_ticket_privacy'] && ! psource_support_current_user_can( 'manage_options' ) )
			$args['user_in'] = array( get_current_user_id() );

		return $args;
	}

	public function set_tickets_table_columns( $columns ) {
		unset( $columns['submitted'] );
		return $columns;
	}


}