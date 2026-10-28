<?php

class PSource_Support_Network_Ticket_Categories extends PSource_Support_Admin_Menu {

	public function add_menu() {
		parent::add_submenu_page(
			'ticket-manager',
			__( 'Ticketkategorien', 'psource-support' ),
			__( 'Ticketkategorien', 'psource-support' ), 
			$this->network ? 'manage_network' : 'manage_options'
		);

		if ( isset( $_GET['action'] ) && isset( $_GET['category'] ) && 'edit' === $_GET['action'] ) {
			if ( $ticket_category = psource_support_get_ticket_category( absint( $_GET['category'] ) ) )
				add_filter( 'support_system_admin_page_title', array( $this, 'set_edit_category_page_title' ) );
		}

	}

	public function set_edit_category_page_title( $title ) {
		$ticket_category = psource_support_get_ticket_category( absint( $_GET['category'] ) );
		return '<h2>' . sprintf( _x( 'Bearbeite %s', 'Bearbeite den Titel des Ticketkategorie-Menüs', 'psource-support' ), $ticket_category->cat_name ) . '</h2>';
	}

	public function on_load() {
		$edit = false;
		$add = false;
		if ( ( $edit = isset( $_POST['submit-edit-ticket-category'] ) || $add = isset( $_POST['submit-new-ticket-category'] ) ) ) {
			$edit = isset( $_POST['submit-edit-ticket-category'] );
			$add = isset( $_POST['submit-new-ticket-category'] );
			
			if ( $edit ) {
				if ( ! psource_support_current_user_can( 'update_ticket_category' ) )
					return;

				// Editing a category ?
				$ticket_category_id = absint( $_POST['ticket_cat_id'] );
				$ticket_category = psource_support_get_ticket_category( $ticket_category_id );
				if ( ! $ticket_category )
					return;
				check_admin_referer( 'edit-ticket-category-' . $ticket_category->cat_id );
			}
			elseif ( $add ) {
				if ( ! psource_support_current_user_can( 'insert_ticket_category' ) )
					return;
				
				check_admin_referer( 'add-ticket-category' );
			}
			else {
				return;
			}

			$cat_name = trim( $_POST['cat_name'] );
			if ( empty(  $cat_name ) )
				add_settings_error( 'support_system_submit_category', 'empty-category-name', __( 'Der Kategoriename darf nicht leer sein', 'psource-support' ) );
			else
				$category_name = $_POST['cat_name'];

			$user_id = 0;
			if ( isset( $_POST['assignee_user_id'] ) ) {
				$user_id = absint( $_POST['assignee_user_id'] );
			} elseif ( ! empty( $_POST['super-admins'] ) && $user = get_user_by( 'login', $_POST['super-admins'] ) ) {
				$user_id = absint( $user->ID );
			}

			$allowed_assignees = array_map( 'absint', psource_support_get_available_assignee_user_ids() );
			if ( $user_id && ! in_array( $user_id, $allowed_assignees, true ) ) {
				add_settings_error( 'support_system_submit_category', 'invalid-assignee', __( 'Der gewählte Bearbeiter ist für die CRM-Sync-Site nicht verfügbar.', 'psource-support' ) );
				$user_id = 0;
			}

			$assignee_ids = null;
			if ( isset( $_POST['category_assignees_present'] ) ) {
				$assignee_ids = psource_support_validate_category_assignee_ids(
					isset( $_POST['assignee_user_ids'] ) ? wp_unslash( $_POST['assignee_user_ids'] ) : array()
				);
				if ( false === $assignee_ids ) {
					add_settings_error( 'support_system_submit_category', 'invalid-team', __( 'Bitte wähle nur verfügbare Bearbeiter aus.', 'psource-support' ) );
				}
			}

			if ( ! get_settings_errors( 'support_system_submit_category' ) ) {
				if ( $add ) {
					if ( ! psource_support_insert_ticket_category( $category_name, $user_id, $assignee_ids ) ) {
						add_settings_error( 'support_system_submit_category', 'category-save-failed', __( 'Die Kategorie konnte nicht gespeichert werden.', 'psource-support' ) );
						return;
					}
					$redirect = add_query_arg( 'added', 'true', $this->get_menu_url() );
					wp_redirect( $redirect );
					exit();
				}
				elseif ( $edit ) {
					$args = array( 'cat_name' => $category_name, 'user_id' => $user_id );
					if ( null !== $assignee_ids ) {
						$args['assignee_ids'] = $assignee_ids;
					}
					if ( ! psource_support_update_ticket_category( $ticket_category->cat_id, $args ) ) {
						add_settings_error( 'support_system_submit_category', 'category-save-failed', __( 'Die Kategorie konnte nicht gespeichert werden.', 'psource-support' ) );
						return;
					}
					$redirect = add_query_arg( 
						array( 
							'updated' => 'true',
							'action' => 'edit',
							'category' => $ticket_category->cat_id 
						), $this->get_menu_url() 
					);
					wp_redirect( $redirect );
					exit();
				}
			}
		}
		
	}

	public function render_inner_page() {
		if ( isset( $_GET['category'] ) && $_GET['action'] == 'edit' ) {
			$ticket_category = psource_support_get_ticket_category( absint( $_GET['category'] ) );
			if ( ! $ticket_category )
				wp_die( __( 'Die Kategorie existiert nicht', 'psource-support' ) );

			$category_name = $ticket_category->cat_name;
			if ( ! empty( $_POST['cat_name'] ) && trim( $_POST['cat_name'] ) )
				$category_name = stripslashes_deep( $_POST['cat_name'] );

			$user = get_userdata( $ticket_category->user_id );
			if ( $user )
				$user = absint( $user->ID );
			else
				$user = 0;

			if ( isset( $_POST['assignee_user_id'] ) )
				$user = absint( $_POST['assignee_user_id'] );
			elseif ( isset( $_POST['super-admins'] ) ) {
				$posted_user = get_user_by( 'login', $_POST['super-admins'] );
				$user = $posted_user ? absint( $posted_user->ID ) : 0;
			}

			$selected_ids = psource_support_get_category_assignee_ids( $ticket_category );
			if ( isset( $_POST['category_assignees_present'] ) ) {
				$posted_ids = psource_support_validate_category_assignee_ids( isset( $_POST['assignee_user_ids'] ) ? wp_unslash( $_POST['assignee_user_ids'] ) : array() );
				$selected_ids = false === $posted_ids ? array() : $posted_ids;
			} elseif ( isset( $_POST['assignee_user_id'] ) || isset( $_POST['super-admins'] ) ) {
				$selected_ids = array( $user );
			}
			$super_admins_dropdown = psource_support_assignees_dropdown(
				array( 
					'name' => 'assignee_user_ids[]',
					'id' => 'category-assignees',
					'multiple' => true,
					'category_assignment' => true,
					'show_empty' => __( 'Keine', 'psource-support' ) ,
					'echo' => false,
					'selected' => $selected_ids
				) 
			);

			$updated = isset( $_GET['updated'] );

			settings_errors( 'support_system_submit_category' );
			include_once( 'views/edit-ticket-category.php' );
		}
		else {
			include_once( 'inc/class-table-ticket-categories.php' );
			$cats_table = new PSource_Support_Ticket_Categories_Table();
			$cats_table->prepare_items();

			$category_name = '';
			if ( isset( $_POST['cat_name'] ) )
				$category_name = sanitize_text_field( stripslashes_deep( $_POST['cat_name'] ) );

			$user = '';
			if ( isset( $_POST['assignee_user_id'] ) ) {
				$user = absint( $_POST['assignee_user_id'] );
			} elseif ( isset( $_POST['super-admins'] ) ) {
				$posted_user = get_user_by( 'login', $_POST['super-admins'] );
				$user = $posted_user ? absint( $posted_user->ID ) : 0;
			}

			$selected_ids = array( absint( $user ) );
			if ( isset( $_POST['category_assignees_present'] ) ) {
				$posted_ids = psource_support_validate_category_assignee_ids( isset( $_POST['assignee_user_ids'] ) ? wp_unslash( $_POST['assignee_user_ids'] ) : array() );
				$selected_ids = false === $posted_ids ? array() : $posted_ids;
			}
			$super_admins_dropdown = psource_support_assignees_dropdown(
				array( 
					'name' => 'assignee_user_ids[]',
					'id' => 'category-assignees',
					'multiple' => true,
					'category_assignment' => true,
					'show_empty' => __( 'Keine', 'psource-support' ) ,
					'echo' => false,
					'selected' => $selected_ids
				) 
			);

			$added = isset( $_GET['added'] );

			settings_errors( 'support_system_submit_category' );
			include_once( 'views/network-ticket-categories.php' );
		}
	}


}