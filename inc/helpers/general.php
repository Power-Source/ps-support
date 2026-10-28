<?php


/**
 * Translate dates
 * 
 * @param string $date The date
 * @return string Date
 */
function psource_support_get_translated_date( $date, $human_read = false ) {
	// get the date from gmt date in Y-m-d H:i:s
	$date_in_gmt = get_date_from_gmt($date);

	if ( $human_read ) {
		$from = mysql2date( 'U', $date_in_gmt, true );
		$transl_date = human_time_diff( $from, current_time( 'timestamp' ) );
	}
	else {
		$format = get_option("date_format") ." ". get_option("time_format");

		//get it localised
		$transl_date = mysql2date( $format, $date_in_gmt, true );
	}

	$transl_date = apply_filters( 'support_system_get_translated_date', $transl_date, $date, $human_read );
	return $transl_date;
}

function psource_support_get_model() {
	return MU_Support_System_Model::get_instance();
}

function psource_support_priority_dropdown( $args = array() ) {
	$defaults = array(
		'name' => 'ticket-priority',
		'id' => false,
		'show_empty' => __( '-- Priorität wählen --', 'psource-support' ),
		'selected' => null,
		'echo' => true
	);
	$args = wp_parse_args( $args, $defaults );
	$name       = $args['name'];
	$id         = $args['id'];
	$show_empty = $args['show_empty'];
	$selected   = $args['selected'];
	$echo       = $args['echo'];

	if ( ! $id )
		$id = $name;

	if ( ! $echo )
		ob_start();

	$plugin_class = psource_support();
	$priorities = $plugin_class::$ticket_priority;
	?>
		<select name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $id ); ?>">
			<?php if ( ! empty( $show_empty ) ): ?>	
				<option value="" <?php selected( $selected === null ); ?>><?php echo esc_html( $show_empty ); ?></option>
			<?php endif; ?>

			<?php foreach ( $priorities as $key => $value ): ?>
				<option value="<?php echo $key; ?>" <?php selected( $selected === $key ); ?>><?php echo esc_html( $value ); ?></option>
			<?php endforeach; ?>

		</select>
	<?php

	if ( ! $echo )
		return ob_get_clean();
}

function psource_support_super_admins_dropdown( $args = array() ) {
	$defaults = array(
		'name' => 'super-admins',
		'id' => false,
		'show_empty' => __( 'Select a staff', 'psource-support' ),
		'selected' => null,
		'echo' => true,
		'value' => 'username' // Or integer
	);
	$args = wp_parse_args( $args, $defaults );

	$plugin = psource_support();
	$super_admins = MU_Support_System::get_super_admins();
	if ( ! empty( $args['ticket_id'] ) ) {
		$ticket = psource_support_get_ticket( absint( $args['ticket_id'] ) );
		$category = psource_support_get_ticket_category_for_ticket( $ticket );
		if ( psource_support_category_has_team( $category ) ) {
			foreach ( psource_support_get_category_assignee_ids( $category ) as $user_id ) {
				$login = get_userdata( $user_id )->user_login;
				if ( ! in_array( $login, $super_admins, true ) ) {
					$super_admins[] = $login;
				}
			}
		}
	}

	$name       = $args['name'];
	$id         = $args['id'];
	$show_empty = $args['show_empty'];
	$selected   = $args['selected'];
	$echo       = $args['echo'];
	$value      = $args['value'];

	if ( ! $id )
		$id = $name;

	if ( ! $echo )
		ob_start();
	?>
		<select name="<?php echo $name; ?>" id="<?php echo $id; ?>">
			<?php if ( ! empty( $show_empty ) ): ?>	
				<option value="" <?php selected( empty( $selected ) ); ?>><?php echo esc_html( $show_empty ); ?></option>
			<?php endif; ?>
			<?php foreach ( $super_admins as $key => $user_name ): ?>
				<?php $user = get_user_by( 'login', $user_name ); ?>
				<?php $option_value = $value === 'username' ? $user_name : $key; ?>
				<?php $option_selected = selected( $selected, $option_value, false ); ?>
				<option value="<?php echo esc_attr( $option_value ); ?>" <?php echo $option_selected; ?>><?php echo $user->display_name; ?></option>
			<?php endforeach; ?>
		</select>
	<?php

	if ( ! $echo )
		return ob_get_clean();
}

function psource_support_assignees_dropdown( $args = array() ) {
	$defaults = array(
		'name' => 'assignee_user_id',
		'id' => false,
		'show_empty' => __( 'Keine', 'psource-support' ),
		'selected' => 0,
		'echo' => true,
		'multiple' => false,
		'category_assignment' => false,
	);
	$args = wp_parse_args( $args, $defaults );
	if ( $args['multiple'] && $args['category_assignment'] ) {
		$html = psource_support_user_picker( $args['name'], $args['id'] ? $args['id'] : 'category-assignees', (array) $args['selected'], 'category' );
		if ( $args['echo'] ) {
			echo $html;
			return;
		}
		return $html;
	}
	$name       = $args['name'];
	$id         = $args['id'];
	$show_empty = $args['show_empty'];
	$selected   = $args['selected'];
	$echo       = $args['echo'];

	if ( ! $id ) {
		$id = $name;
	}

	$multiple = (bool) $args['multiple'];
	$selected = array_map( 'absint', (array) $selected );
	$assignees = $args['category_assignment'] ? psource_support_get_category_available_assignees() : psource_support_get_available_assignees();

	if ( ! $echo ) {
		ob_start();
	}

	?>
	<select name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $id ); ?>" <?php if ( $multiple ) echo 'multiple size="6"'; ?>>
		<?php if ( ! empty( $show_empty ) && ! $multiple ) : ?>
			<option value="0" <?php selected( in_array( 0, $selected, true ) ); ?>><?php echo esc_html( $show_empty ); ?></option>
		<?php endif; ?>

		<?php foreach ( $assignees as $assignee ) : ?>
			<option value="<?php echo esc_attr( absint( $assignee['user_id'] ) ); ?>" <?php selected( in_array( absint( $assignee['user_id'] ), $selected, true ) ); ?>>
				<?php echo esc_html( $assignee['label'] ); ?>
			</option>
		<?php endforeach; ?>
	</select>
	<?php

	if ( ! $echo ) {
		return ob_get_clean();
	}
}

/**
 * Render only selected people; suggestions are requested while typing.
 *
 * @param string $name Input name.
 * @param string $id Search input ID.
 * @param array  $selected Selected user IDs.
 * @param string $kind Category candidates or existing support staff.
 * @return string
 */
function psource_support_user_picker( $name, $id, $selected, $kind = 'staff' ) {
	ob_start();
	?>
	<div class="ps-support-user-picker" data-name="<?php echo esc_attr( $name ); ?>" data-kind="<?php echo esc_attr( $kind ); ?>">
		<ul class="ps-support-picked" aria-label="<?php esc_attr_e( 'Ausgewähltes Team', 'psource-support' ); ?>">
			<?php foreach ( array_unique( array_map( 'absint', $selected ) ) as $user_id ) :
				$user = get_userdata( $user_id );
				if ( ! $user ) continue;
				$label = $user->display_name . ' (@' . $user->user_login . ')';
				?>
				<li data-user-id="<?php echo (int) $user_id; ?>">
					<input type="hidden" name="<?php echo esc_attr( $name ); ?>" value="<?php echo (int) $user_id; ?>">
					<span><?php echo esc_html( $label ); ?></span>
					<button type="button" class="ps-support-remove" aria-label="<?php echo esc_attr( __( 'Entfernen:', 'psource-support' ) . ' ' . $label ); ?>"><?php esc_html_e( 'Entfernen', 'psource-support' ); ?></button>
				</li>
			<?php endforeach; ?>
		</ul>
		<label class="screen-reader-text" for="<?php echo esc_attr( $id ); ?>"><?php esc_html_e( 'Person zum Team hinzufügen', 'psource-support' ); ?></label>
		<input type="search" class="regular-text ps-support-person-search" id="<?php echo esc_attr( $id ); ?>" placeholder="<?php esc_attr_e( 'Name eingeben und Person hinzufügen', 'psource-support' ); ?>" autocomplete="off" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="<?php echo esc_attr( $id . '-results' ); ?>" aria-describedby="<?php echo esc_attr( $id . '-status' ); ?>">
		<ul class="ps-support-person-results" id="<?php echo esc_attr( $id . '-results' ); ?>" role="listbox" aria-label="<?php esc_attr_e( 'Suchtreffer', 'psource-support' ); ?>" hidden></ul>
		<p class="description ps-support-picker-status" id="<?php echo esc_attr( $id . '-status' ); ?>" role="status"><?php esc_html_e( 'Mindestens zwei Zeichen eingeben. Nur ausgewählte Personen werden dem Team hinzugefügt.', 'psource-support' ); ?></p>
		<noscript><p><?php esc_html_e( 'JavaScript ist zum Hinzufügen und Entfernen von Personen erforderlich. Vorhandene Zuordnungen bleiben erhalten.', 'psource-support' ); ?></p></noscript>
	</div>
	<?php
	return ob_get_clean();
}

function psource_support_get_available_assignee_user_ids() {
	$assignees = psource_support_get_available_assignees();
	return wp_list_pluck( $assignees, 'user_id' );
}

function psource_support_get_available_assignees() {
	global $wpdb;

	$assignees = array();
	$crm_sync_enabled = (bool) psource_support_get_setting( 'psource_support_crm_sync_enabled' );
	$sync_blog_id = $crm_sync_enabled ? psource_support_get_crm_sync_blog_id() : 0;
	$switched = false;

	if ( $crm_sync_enabled && is_multisite() && $sync_blog_id && get_current_blog_id() !== $sync_blog_id ) {
		switch_to_blog( $sync_blog_id );
		$switched = true;
	}

	$agents_table = $wpdb->prefix . 'smartcrm_agents';
	$roles_table = $wpdb->prefix . 'smartcrm_agent_roles';
	$agents_exists = (string) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $agents_table ) );
	$roles_exists = (string) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $roles_table ) );

	if ( $crm_sync_enabled && $agents_exists === $agents_table && $roles_exists === $roles_table ) {
		$crm_agents = $wpdb->get_results(
			"SELECT a.user_id, u.display_name, r.role_name
			 FROM $agents_table a
			 INNER JOIN {$wpdb->users} u ON u.ID = a.user_id
			 LEFT JOIN $roles_table r ON r.id = a.role_id
			 WHERE a.status = 'active'
			 ORDER BY u.display_name ASC"
		);

		if ( ! empty( $crm_agents ) ) {
			foreach ( $crm_agents as $agent ) {
				$role_suffix = ! empty( $agent->role_name ) ? ' - ' . $agent->role_name : '';
				$assignees[] = array(
					'user_id' => absint( $agent->user_id ),
					'label' => $agent->display_name . $role_suffix,
				);
			}
		}

		if ( $switched ) {
			restore_current_blog();
		}

		// CRM-Modus: ausschließlich CRM-Agenten der Sync-Site zulassen
		return $assignees;
	}

	if ( $switched ) {
		restore_current_blog();
	}

	$super_admins = MU_Support_System::get_super_admins();
	foreach ( $super_admins as $user_name ) {
		$user = get_user_by( 'login', $user_name );
		if ( ! $user ) {
			continue;
		}

		$assignees[] = array(
			'user_id' => absint( $user->ID ),
			'label' => $user->display_name,
		);
	}

	return $assignees;
}

function psource_support_get_crm_sync_blog_id() {
	if ( ! is_multisite() ) {
		return get_current_blog_id();
	}

	$override_blog_id = absint( psource_support_get_setting( 'psource_support_crm_sync_blog_id' ) );
	if ( $override_blog_id && get_blog_details( $override_blog_id ) ) {
		return $override_blog_id;
	}

	if ( function_exists( 'get_main_site_id' ) ) {
		return (int) get_main_site_id( get_current_network_id() );
	}

	return defined( 'BLOG_ID_CURRENT_SITE' ) ? absint( BLOG_ID_CURRENT_SITE ) : 1;
}

function psource_support_get_errors($setting = null) {
    global $support_system_errors;

    if (!is_array($support_system_errors) || empty($support_system_errors)) {
        return array();
    }

    if ($setting) {
        $setting_errors = array();
        foreach ($support_system_errors as $details) {
            if (isset($details['setting']) && $setting === $details['setting']) {
                $setting_errors[] = $details;
            }
        }
        return $setting_errors;
    }

    return $support_system_errors;
}

function psource_support_add_error( $setting, $code, $message ) {
	global $support_system_errors;

	$support_system_errors[] = array(
		'setting' => $setting,
		'code'    => $code,
		'message' => $message,
	);
}

function psource_support_get_version() {
	return PSOURCE_SUPPORT_PLUGIN_VERSION;
}


function psource_support_register_main_script() {
	$suffix = '.min';
	if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG )
		$suffix = '';

	wp_register_script( 'support-system', PSOURCE_SUPPORT_PLUGIN_URL . 'assets/js/support-system' . $suffix . '.js', array( 'jquery' ), psource_support_get_version(), true );

	$l10n = array(
		'ajaxurl' => admin_url( 'admin-ajax.php' ),
		'voteFaqNonce' => wp_create_nonce( 'support-system-vote-faq' ),
	);
	wp_localize_script( 'support-system', 'support_system_strings', $l10n );
}

function psource_support_register_admin_script() {
	wp_register_script( 'support-system-admin', PSOURCE_SUPPORT_PLUGIN_URL . 'admin/assets/js/support-admin.js', array( 'jquery' ), psource_support_get_version(), true );

	$l10n = array(
		'attachmentButtonText' => __( 'Dateien hinzufügen...', 'psource-support' ),
		'attachmentRemoveTitle' => __( 'Datei löschen', 'psource-support' ),
		'attachmentRemoveText' => __( 'Datei löschen', 'psource-support' ),
		'deleteConfirm' => __( 'Möchtest Du dieses Ticket wirklich löschen?', 'psource-support' ),
		'ajaxurl' => admin_url( 'admin-ajax.php' ),
		'replyTemplateNonce' => wp_create_nonce( 'psource-support-reply-template' ),
		'replyTemplateEmpty' => __( 'Bitte zuerst ein Template auswählen.', 'psource-support' ),
		'replyTemplateInsert' => __( 'Template einfügen', 'psource-support' ),
		'replyTemplateError' => __( 'Template konnte nicht geladen werden.', 'psource-support' ),
	);

	wp_localize_script( 'support-system-admin', 'support_system_admin_i18n', $l10n );
}

function psource_support_enqueue_admin_script() {
	if ( ! wp_script_is( 'support-system-admin', 'registered' ) )
		psource_support_register_admin_script();

	wp_enqueue_script( 'support-system-admin' );
}

function psource_support_enqueue_main_script() {
	if ( ! wp_script_is( 'support-system', 'registered' ) )
		psource_support_register_main_script();

	wp_enqueue_script( 'support-system' );

}


function psource_support_enqueue_frontend_scripts( $in_footer = true ) {
	unset( $in_footer );

	psource_support_enqueue_main_script();
}

function psource_support_enqueue_foundation_scripts( $in_footer = true ) {
	psource_support_enqueue_frontend_scripts( $in_footer );
}


/**
 * Adds an integration class to Support System
 *
 * @param $classname The class name of the integrator
 */
function psource_support_add_integrator( $classname ) {
	if ( class_exists( $classname ) ) {
		$plugin = psource_support();
		$r = new ReflectionClass( $classname );
		$plugin->add_integrator( $r->newInstance() );
	}
}
