<?php

function psource_support_get_ticket_category_table() {
	return psource_support()->model->tickets_cats_table;
}

function psource_support_get_ticket_category_rows( $where, $order = '', $limit = '', $count = false ) {
	global $wpdb;
	$table = psource_support_get_ticket_category_table();
	if ( $count ) {
		return $wpdb->get_var( "SELECT COUNT(cat_id) FROM $table $where" );
	}
	return $wpdb->get_results( "SELECT cat_id, site_id, blog_id, cat_name, defcat, user_id FROM $table $where $order $limit" );
}

function psource_support_insert_ticket_category_row( $insert ) {
	global $wpdb;
	$res = $wpdb->insert(
		psource_support_get_ticket_category_table(),
		$insert,
		array( '%s', '%d', '%d', '%d' )
	);
	return $res ? $wpdb->insert_id : false;
}

function psource_support_update_ticket_category_row( $cat_id, $update, $wildcards ) {
	global $wpdb;
	return $wpdb->update(
		psource_support_get_ticket_category_table(),
		$update,
		array( 'cat_id' => $cat_id ),
		$wildcards,
		array( '%d' )
	);
}

function psource_support_delete_ticket_category_row( $cat_id ) {
	global $wpdb;
	return $wpdb->query(
		$wpdb->prepare( "DELETE FROM " . psource_support_get_ticket_category_table() . " WHERE cat_id = %d", $cat_id )
	);
}

function psource_support_sanitize_ticket_category_fields( $cat ) {
	$int_fields = array( 'cat_id', 'user_id', 'site_id', 'blog_id' );

	foreach ( get_object_vars( $cat ) as $name => $value ) {
		if ( in_array( $name, $int_fields ) )
			$value = intval( $value );

		$cat->$name = $value;
	}

	$cat = apply_filters( 'support_system_sanitize_ticket_category_fields', $cat );

	return $cat;
}

function psource_support_get_ticket_category( $cat ) {
	$cat = PSource_Support_Ticket_Category::get_instance( $cat );
	$cat = apply_filters( 'support_system_get_ticket_category', $cat );
	return $cat;
}

/**
 * Category teams live outside the legacy table, separately for each network and scope.
 *
 * @param PSource_Support_Ticket_Category $category Category.
 * @return string
 */
function psource_support_category_team_option_name( $category ) {
	return 'psource_support_category_team_' . absint( $category->blog_id ) . '_' . absint( $category->cat_id );
}

/**
 * @param PSource_Support_Ticket_Category $category Category.
 * @return bool
 */
function psource_support_category_has_team( $category ) {
	$network_id = is_multisite() ? get_current_network_id() : 1;
	return $category instanceof PSource_Support_Ticket_Category
		&& (int) $category->site_id === (int) $network_id
		&& (bool) get_network_option( $network_id, psource_support_category_team_option_name( $category ), array() );
}

/**
 * Category-only candidates. Central categories can explicitly name network users;
 * local categories and CRM-managed installations retain their existing pool.
 *
 * @param int|null $blog_id Category data scope, or the current scope.
 * @return array
 */
function psource_support_get_category_available_assignees( $blog_id = null ) {
	$blog_id = null === $blog_id ? psource_support_get_data_blog_id() : absint( $blog_id );
	if ( ! is_multisite() || $blog_id || psource_support_get_setting( 'psource_support_crm_sync_enabled' ) ) {
		return psource_support_get_available_assignees();
	}

	$assignees = array();
	foreach ( psource_support_get_available_assignees() as $assignee ) {
		$assignees[ absint( $assignee['user_id'] ) ] = $assignee;
	}
	$site_ids = get_sites( array( 'network_id' => get_current_network_id(), 'fields' => 'ids', 'number' => 0 ) );
	foreach ( $site_ids as $site_id ) {
		foreach ( get_users( array( 'blog_id' => $site_id, 'fields' => array( 'ID', 'display_name' ) ) ) as $user ) {
			$assignees[ (int) $user->ID ] = array( 'user_id' => (int) $user->ID, 'label' => $user->display_name );
		}
	}
	return array_values( $assignees );
}

/**
 * Get eligible team members, or the existing single assignee for a legacy category.
 *
 * @param PSource_Support_Ticket_Category $category Category.
 * @return int[] Unique existing user IDs. An unavailable team does not fall back to user_id.
 */
function psource_support_get_category_assignee_ids( $category ) {
	$network_id = is_multisite() ? get_current_network_id() : 1;
	if ( ! $category instanceof PSource_Support_Ticket_Category || (int) $category->site_id !== (int) $network_id ) {
		return array();
	}

	if ( psource_support_category_has_team( $category ) ) {
		$ids = (array) get_network_option( $network_id, psource_support_category_team_option_name( $category ), array() );
		$allowed = wp_list_pluck( psource_support_get_category_available_assignees( $category->blog_id ), 'user_id' );
		$ids = array_intersect( array_map( 'absint', $ids ), array_map( 'absint', $allowed ) );
	} else {
		$ids = array( absint( $category->user_id ) );
	}

	return array_values( array_unique( array_filter( $ids, function( $id ) {
		return $id > 0 && (bool) get_userdata( $id );
	} ) ) );
}

/**
 * Membership is category-specific; it does not make the user global support staff.
 *
 * @param int                            $user_id User ID.
 * @param PSource_Support_Ticket_Category $category Category.
 * @return bool
 */
function psource_support_user_can_handle_category( $user_id, $category ) {
	return in_array( absint( $user_id ), psource_support_get_category_assignee_ids( $category ), true );
}

/**
 * Validate assignments before either the category row or its team option is changed.
 *
 * @param array    $ids Selected IDs.
 * @param int|null $blog_id Category data scope, or the current scope.
 * @return int[]|false
 */
function psource_support_validate_category_assignee_ids( $ids, $blog_id = null ) {
	if ( ! is_array( $ids ) ) {
		return false;
	}
	foreach ( $ids as $id ) {
		if ( ! is_scalar( $id ) || ! ctype_digit( (string) $id ) ) {
			return false;
		}
	}
	$ids = array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );
	$allowed = array_map( 'absint', wp_list_pluck( psource_support_get_category_available_assignees( $blog_id ), 'user_id' ) );
	foreach ( $ids as $id ) {
		if ( ! in_array( $id, $allowed, true ) || ! get_userdata( $id ) ) {
			return false;
		}
	}
	return $ids;
}

function psource_support_save_category_team( $category, $ids ) {
	$name = psource_support_category_team_option_name( $category );
	if ( count( $ids ) > 1 ) {
		update_network_option( $category->site_id, $name, $ids );
	} else {
		delete_network_option( $category->site_id, $name );
	}
}

/**
 * Categories a user handles in the current data scope.
 *
 * @param int $user_id User ID.
 * @return int[]
 */
function psource_support_get_user_category_ids( $user_id ) {
	$ids = array();
	foreach ( psource_support_get_ticket_categories() as $category ) {
		if ( psource_support_user_can_handle_category( $user_id, $category ) ) {
			$ids[] = (int) $category->cat_id;
		}
	}
	return $ids;
}

function psource_support_get_ticket_categories( $args = array() ) {
	global $wpdb, $current_site;

	$defaults = array(
		'orderby' => 'cat_name',
		'order' => 'asc',
		'per_page' => -1,
		'count' => false,
		'page' => 1,
		'defcat' => null,
		'blog_id' => psource_support_get_data_blog_id(),
	);

	$args = wp_parse_args( $args, $defaults );
	$orderby  = $args['orderby'];
	$order    = $args['order'];
	$per_page = $args['per_page'];
	$count    = $args['count'];
	$page     = $args['page'];
	$defcat   = $args['defcat'];
	$blog_id  = absint( $args['blog_id'] );

	$current_site_id = ! empty ( $current_site ) ? $current_site->id : 1;

	// WHERE
	$where = array();
	$where[] = $wpdb->prepare( "site_id = %d", $current_site_id );
	$where[] = $wpdb->prepare( "blog_id = %d", $blog_id );

	if ( $defcat !== null ) {
		// This is an enum field type!!
		if ( $defcat )
			$where[] = "defcat = 2";
		else
			$where[] = "defcat = 1";
	}	

	$where = "WHERE " . implode( " AND ", $where );

	// ORDER
	$order = strtoupper( $order );
	$order = "ORDER BY $orderby $order";

	$limit = '';
	if ( $per_page > -1 )
		$limit = $wpdb->prepare( "LIMIT %d, %d", intval( ( $page - 1 ) * $per_page ), intval( $per_page ) );

	if ( $count ) {
		$results = psource_support_get_ticket_category_rows( $where, '', '', true );
		$cats = apply_filters( 'support_system_get_ticket_categories_count', $results, $args );
	}
	else {
		$_cats = psource_support_get_ticket_category_rows( $where, $order, $limit );
		$cats = array_map( 'psource_support_get_ticket_category', $_cats );
		
		if ( empty( $cats ) )
			$cats = array();

		$cats = apply_filters( 'support_system_get_ticket_categories', $cats, $args );
	}

	return $cats;
}

function psource_support_get_ticket_categories_count( $args = array() ) {
	$args['count'] = true;
	$args['per_page'] = -1;
	return psource_support_get_ticket_categories( $args );
}

function psource_support_ticket_categories_dropdown( $args = array() ) {
	$defaults = array(
		'name' => 'ticket-cat',
		'id' => false,
		'show_empty' => __( '-- Wähle eine Kategorie --', 'psource-support' ),
		'selected' => '',
		'class' => '',
		'echo' => true
	);
	$args = wp_parse_args( $args, $defaults );
	$name       = $args['name'];
	$id         = $args['id'];
	$show_empty = $args['show_empty'];
	$selected   = $args['selected'];
	$class      = $args['class'];
	$echo       = $args['echo'];

	if ( ! $id )
		$id = $name;

	if ( ! $echo )
		ob_start();

	$cats = psource_support_get_ticket_categories();

	?>
		<select class="<?php echo esc_attr( $class ); ?>" name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $id ); ?>">
			<?php if ( ! empty( $show_empty ) ): ?>	
				<option value="" <?php selected( empty( $selected ) ); ?>><?php echo esc_html( $show_empty ); ?></option>
			<?php endif; ?>

			<?php foreach ( $cats as $cat ): ?>
				<option value="<?php echo esc_attr( $cat->cat_id ); ?>" <?php selected( $selected == $cat->cat_id ); ?>><?php echo esc_html( $cat->cat_name ); ?></option>
			<?php endforeach; ?>

		</select>
	<?php

	if ( ! $echo )
		return ob_get_clean();
}

function psource_support_insert_ticket_category( $name, $user_id = false, $assignee_ids = null ) {
	global $current_site;

	$current_site_id = ! empty ( $current_site ) ? $current_site->id : 1;

	$user_id = $user_id === false ? get_current_user_id() : absint( $user_id );
	if ( null !== $assignee_ids ) {
		$assignee_ids = psource_support_validate_category_assignee_ids( $assignee_ids );
		if ( false === $assignee_ids ) {
			return false;
		}
		$user_id = count( $assignee_ids ) === 1 ? $assignee_ids[0] : 0;
	}
	
	$name = trim( wp_unslash( $name ) );
	if ( empty( $name ) )
		return false;

	$ticket_category = psource_support_get_ticket_category( $name );
	if ( $ticket_category )
		return false;

	$cat_id = psource_support_insert_ticket_category_row( array(
		'cat_name' => $name,
		'site_id'  => $current_site_id,
		'blog_id'  => psource_support_get_data_blog_id(),
		'user_id'  => $user_id,
	) );

	if ( ! $cat_id )
		return false;

	if ( null !== $assignee_ids ) {
		psource_support_save_category_team( psource_support_get_ticket_category( $cat_id ), $assignee_ids );
	}

	do_action( 'support_system_insert_ticket_category', $cat_id );

	return $cat_id;
}

function psource_support_update_ticket_category( $ticket_category_id, $args = array() ) {
	$ticket_category = psource_support_get_ticket_category( $ticket_category_id );
	if ( ! $ticket_category )
		return false;

	$single_assignment = array_key_exists( 'user_id', $args ) && ! array_key_exists( 'assignee_ids', $args );
	$defaults = array(
		'cat_name' => $ticket_category->cat_name,
		'user_id' => $ticket_category->user_id,
		'defcat' => $ticket_category->defcat
	);

	$args = wp_parse_args( $args, $defaults );
	$cat_name = $args['cat_name'];
	$user_id  = $args['user_id'];
	$defcat   = $args['defcat'];
	if ( array_key_exists( 'assignee_ids', $args ) ) {
		$assignee_ids = psource_support_validate_category_assignee_ids( $args['assignee_ids'], $ticket_category->blog_id );
		if ( false === $assignee_ids ) {
			return false;
		}
		$user_id = count( $assignee_ids ) === 1 ? $assignee_ids[0] : 0;
	}

	$cat_name = trim( $cat_name );
	if ( empty( $cat_name ) )
		return false;

	$update = array();
	$update_wildcards = array();

	$update['cat_name'] = wp_unslash( $cat_name );
	$update_wildcards[] = '%s';

	$update['user_id'] = $user_id;
	$update_wildcards[] = '%d';

	if ( $defcat )
		psource_support_set_default_ticket_category( $ticket_category_id );
	

	$result = psource_support_update_ticket_category_row( $ticket_category_id, $update, $update_wildcards );

	if ( false === $result )
		return false;

	if ( isset( $assignee_ids ) ) {
		psource_support_save_category_team( $ticket_category, $assignee_ids );
	} elseif ( $single_assignment ) {
		delete_network_option( $ticket_category->site_id, psource_support_category_team_option_name( $ticket_category ) );
	}

	psource_support_clean_ticket_category_cache( $ticket_category_id );

	$old_ticket_category = $ticket_category;
	do_action( 'support_system_update_ticket_category', $ticket_category_id, $args, $old_ticket_category );

	return true;
}

function psource_support_get_default_ticket_category() {
	$network_id = is_multisite() ? get_current_network_id() : 1;
	$cache_key = 'support_system_default_ticket_category_' . $network_id . '_' . psource_support_get_data_blog_id();
	$default_category = wp_cache_get( $cache_key, 'support_system_ticket_categories' );

	if ( $default_category )
		return $default_category;

	$results = psource_support_get_ticket_categories( array( 'per_page' => 1, 'defcat' => 1 ) );

	if ( isset( $results[0] ) ) {
		wp_cache_set( $cache_key, $results[0], 'support_system_ticket_categories' );
		return $results[0];
	}

	$default_name = __( 'Allgemein', 'psource-support' );
	psource_support_insert_ticket_category( $default_name, 0 );
	$default_category = psource_support_get_ticket_category( $default_name );
	if ( $default_category ) {
		psource_support_update_ticket_category_row( $default_category->cat_id, array( 'defcat' => 2 ), array( '%d' ) );
		psource_support_clean_ticket_category_cache( $default_category->cat_id );
		return psource_support_get_ticket_category( $default_category->cat_id );
	}

	return false;
}

function psource_support_set_default_ticket_category( $ticket_category_id ) {
	global $wpdb;

	$ticket_category = psource_support_get_ticket_category( $ticket_category_id );
	if ( ! $ticket_category )
		return false;

	$default_category = psource_support_get_default_ticket_category();
	if ( $default_category ) {
		$wpdb->query( $wpdb->prepare(
			"UPDATE " . psource_support_get_ticket_category_table() . " SET defcat = 1 WHERE site_id = %d AND blog_id = %d",
			$ticket_category->site_id,
			$ticket_category->blog_id
		) );
	}

	$result = psource_support_update_ticket_category_row(
		$ticket_category_id,
		array( 'defcat' => 2 ), // enum type field!!
		array( '%d' )
	);

	wp_cache_delete( 'support_system_default_ticket_category_' . $ticket_category->site_id . '_' . $ticket_category->blog_id, 'support_system_ticket_categories' );
	psource_support_clean_ticket_category_cache( $ticket_category_id );
	psource_support_clean_ticket_category_cache( $default_category->cat_id );

	if ( ! $result )
		return false;

	return true;

}

function psource_support_delete_ticket_category( $ticket_category_id ) {
	$ticket_category = psource_support_get_ticket_category( $ticket_category_id );
	if ( ! $ticket_category )
		return false;

	// Don't allow to remove the default category
	if ( $ticket_category->defcat )
		return false;

	$default_category = psource_support_get_default_ticket_category();

	$category_tickets = psource_support_get_tickets(
		array(
			'per_page' => -1,
			'category' => $ticket_category_id
		)
	);
	if ( $category_tickets && $default_category ) {
		foreach ( $category_tickets as $ticket )
			psource_support_update_ticket( $ticket->ticket_id, array( 'cat_id' => $default_category->cat_id ) );
	}

	psource_support_delete_ticket_category_row( $ticket_category_id );
	delete_network_option( $ticket_category->site_id, psource_support_category_team_option_name( $ticket_category ) );

	psource_support_clean_ticket_category_cache( $ticket_category_id );

	$old_ticket_category = $ticket_category;
	do_action( 'support_system_delete_ticket_category', $ticket_category_id, $old_ticket_category );

	return true;
}

function psource_support_clean_ticket_category_cache( $ticket_category ) {
	$ticket_category = psource_support_get_ticket_category( $ticket_category );

	if ( empty( $ticket_category ) )
		return;

	wp_cache_delete( $ticket_category->cat_id, 'support_system_ticket_categories' );
	wp_cache_delete( $ticket_category->cat_id, 'support_system_ticket_categories_counts' );
	wp_cache_delete( 'support_system_default_ticket_category_' . $ticket_category->site_id . '_' . $ticket_category->blog_id, 'support_system_ticket_categories' );

	do_action( 'support_system_clean_ticket_category_cache', $ticket_category );
}