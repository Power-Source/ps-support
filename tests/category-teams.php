<?php
/**
 * Focused WordPress integration test; run on a development database:
 * wp --skip-plugins --skip-themes eval-file wp-content/plugins/ps-support/tests/category-teams.php
 *
 * Fixtures are removed and outgoing email is intercepted, including on failure.
 */
if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( "Run this test with wp eval-file.\n" );
}

if ( ! function_exists( 'psource_support' ) ) {
	require dirname( __DIR__ ) . '/psource-support.php';
	support_system_init();
	psource_support()->init_plugin();
}

global $wpdb;
$users = array();
$categories = array();
$tickets = array();
$mail = array();
$overrides = array();
$logins = array();
$previous_user = get_current_user_id();
$had_scope = array_key_exists( 'psource_support_data_blog_id_override', $GLOBALS );
$previous_scope = $had_scope ? $GLOBALS['psource_support_data_blog_id_override'] : null;
$assertions = 0;
$assert = function( $condition, $message ) use ( &$assertions ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
	++$assertions;
};
$settings_filter = function( $settings ) use ( &$overrides ) {
	return array_merge( (array) $settings, $overrides );
};
$assignees_filter = function() use ( &$logins ) {
	return $logins;
};
$mail_filter = function( $result, $args ) use ( &$mail ) {
	$mail[] = $args;
	return true;
};
$recipients = function() use ( &$mail ) {
	$to = array();
	foreach ( $mail as $message ) {
		$to = array_merge( $to, (array) $message['to'] );
	}
	sort( $to );
	return $to;
};
add_filter( 'site_option_psource_support_settings', $settings_filter );
add_filter( 'option_psource_support_settings', $settings_filter );
add_filter( 'psource_support_get_super_admins', $assignees_filter );
add_filter( 'pre_wp_mail', $mail_filter, 10, 2 );

try {
	$GLOBALS['psource_support_data_blog_id_override'] = 0;
	$prefix = 'ps-support-team-test-' . wp_generate_password( 8, false );
	for ( $i = 0; $i < 4; ++$i ) {
		$id = wp_insert_user( array(
			'user_login' => $prefix . '-' . $i,
			'user_email' => $prefix . '-' . $i . '@example.invalid',
			'user_pass' => wp_generate_password( 24 ),
			'role' => 'subscriber',
		) );
		$assert( ! is_wp_error( $id ), 'Fixture user creation failed.' );
		$users[] = (int) $id;
	}
	$logins = array( get_userdata( $users[0] )->user_login, get_userdata( $users[1] )->user_login );
	$overrides = array(
		'psource_support_crm_sync_enabled' => false,
		'psource_support_main_super_admin' => $logins[0],
		'psource_ticket_privacy' => 'requestor',
		'psource_support_staff_roles' => array( 'administrator', 'editor' ),
		'psource_support_tickets_role' => array( 'subscriber' ),
	);
	wp_set_current_user( $users[2] );
	$cat_id = psource_support_insert_ticket_category( $prefix, 0, array( $users[0], $users[1], $users[0] ) );
	$assert( (bool) $cat_id, 'Team category creation failed.' );
	$categories[] = $cat_id;
	$category = psource_support_get_ticket_category( $cat_id );
	$assert( 0 === $category->user_id, 'Teams must not pick a primary user.' );
	$assert( psource_support_get_category_assignee_ids( $category ) === array( $users[0], $users[1] ), 'Team IDs must be unique.' );
	if ( class_exists( 'PSource_Support_Chat_Settings' ) ) {
		$chat_staff = PSource_Support_Chat_Settings::staff_ids( 0 );
		$assert( in_array( $users[0], $chat_staff, true ) && in_array( $users[1], $chat_staff, true ), 'Explicit category members qualify as supportchat staff without a staff role.' );
		$assert( ! in_array( $users[2], $chat_staff, true ), 'Unassigned subscribers do not qualify as supportchat staff.' );
	}
	wp_set_current_user( 0 );
	$assert( array( $users[0], $users[1] ) === psource_support_get_category_assignee_ids( $category ), 'Anonymous requests must resolve category teams.' );
	wp_set_current_user( $users[2] );
	if ( is_multisite() ) {
		$other_sites = get_sites( array( 'fields' => 'ids', 'number' => 1, 'site__not_in' => array( get_current_blog_id() ) ) );
		if ( $other_sites ) {
			switch_to_blog( $other_sites[0] );
			try {
				if ( class_exists( 'PSource_Support_Chat_Settings' ) ) {
					$assert( ! in_array( $users[0], PSource_Support_Chat_Settings::staff_ids( get_current_blog_id() ), true ), 'Central category membership does not grant local fallback eligibility on another site.' );
				}
				wp_set_current_user( 0 );
				$assert( array( $users[0], $users[1] ) === psource_support_get_category_assignee_ids( $category ), 'Originating subsite membership must not limit central teams.' );
			} finally {
				restore_current_blog();
				wp_set_current_user( $users[2] );
			}
		}
	}
	$dropdown = psource_support_assignees_dropdown( array( 'name' => 'assignee_user_ids[]', 'multiple' => true, 'selected' => $users, 'echo' => false ) );
	$assert( false !== strpos( $dropdown, 'multiple size="6"' ), 'Team selection must allow multiple users.' );
	$assert( 2 === substr_count( $dropdown, ' selected' ), 'Both selected team members must render selected.' );
	$picker = psource_support_assignees_dropdown( array( 'name' => 'assignee_user_ids[]', 'multiple' => true, 'category_assignment' => true, 'selected' => array( $users[0], $users[1], $users[0] ), 'echo' => false ) );
	$assert( false === strpos( $picker, '<select' ) && false !== strpos( $picker, 'role="combobox"' ), 'Category teams use autocomplete, not a multi-select.' );
	$assert( 2 === substr_count( $picker, 'type="hidden" name="assignee_user_ids[]"' ), 'Only selected unique category members are rendered as submission inputs.' );
	$assert( false === strpos( $picker, get_userdata( $users[2] )->user_login ), 'Unselected users are not rendered in the category picker.' );
	if ( class_exists( 'PSource_Support_Chat_Settings' ) ) {
		$matches = PSource_Support_Chat_Settings::staff_search( 0, get_userdata( $users[3] )->user_login, 'category' );
		$assert( is_multisite() ? count( $matches['users'] ) === 1 : ! $matches['users'], 'Autocomplete retains existing category eligibility, including explicit network assignment.' );
		$matches = PSource_Support_Chat_Settings::staff_search( 0, get_userdata( $users[3] )->user_login, 'category', array( $users[3] ) );
		$assert( ! $matches['users'], 'Already selected people are excluded before the search result limit.' );
	}
	$single_dropdown = psource_support_assignees_dropdown( array( 'selected' => $users[1], 'echo' => false ) );
	$assert( false === strpos( $single_dropdown, 'multiple' ), 'Existing single-user dropdowns must remain single-select.' );
	$assert( 1 === substr_count( $single_dropdown, ' selected' ), 'Existing single-user selection must be preserved.' );
	$assert( psource_support_user_can_handle_category( $users[1], $category ), 'Second member must be eligible.' );
	$assert( ! psource_support_user_can_handle_category( $users[3], $category ), 'Nonmembers must not be eligible.' );
	$assert( false === psource_support_validate_category_assignee_ids( array( 2147483647 ) ), 'Unavailable assignees must be rejected.' );
	if ( is_multisite() ) {
		$assert( array( $users[3] ) === psource_support_validate_category_assignee_ids( array( $users[3] ) ), 'Ordinary registered network users must be selectable.' );
		$assert( ! is_super_admin( $users[1] ), 'Team membership must not promote users.' );
	}
	$assert( false === psource_support_validate_category_assignee_ids( array( array( $users[0] ) ) ), 'Nested input must be rejected.' );
	$assert( false === psource_support_update_ticket_category( $cat_id, array( 'assignee_ids' => array( 2147483647 ) ) ), 'Invalid update must fail.' );
	$assert( psource_support_get_category_assignee_ids( $category ) === array( $users[0], $users[1] ), 'Invalid updates must preserve team.' );
	$assert( psource_support_update_ticket_category( $cat_id, array( 'assignee_ids' => array( $users[0], $users[1] ) ) ), 'Unchanged team updates must succeed.' );
	$foreign = clone $category;
	$foreign->site_id = $category->site_id + 100000;
	$assert( array() === psource_support_get_category_assignee_ids( $foreign ), 'Foreign-network category must not expose local team.' );
	$assert( array() === psource_support_get_category_assignee_ids( false ), 'Invalid category must return no users.' );

	$mail = array();
	$ticket_id = psource_support_insert_ticket( array(
		'cat_id' => $cat_id, 'user_id' => $users[2], 'title' => $prefix, 'message' => 'Team ticket test.',
	) );
	$assert( ! is_wp_error( $ticket_id ), 'Ticket creation failed.' );
	$tickets[] = $ticket_id;
	$ticket = psource_support_get_ticket( $ticket_id );
	$assert( 0 === $ticket->admin_id, 'Team tickets must remain unassigned.' );
	$expected = array_map( function( $id ) { return get_userdata( $id )->user_email; }, array_slice( $users, 0, 3 ) );
	sort( $expected );
	$assert( $recipients() === $expected, 'New ticket must notify both team members and its creator exactly once.' );
	$assert( psource_support_user_can( $users[1], 'update_ticket', $ticket_id ), 'Second team member must handle ticket.' );
	$assert( ! psource_support_user_can( $users[1], 'update_ticket' ), 'Membership must not grant global ticket editing.' );
	$assert( ! psource_support_user_can( $users[1], 'delete_ticket', $ticket_id ), 'Membership must not grant deletion.' );
	$overrides['psource_support_tickets_role'] = array( 'administrator' );
	$assert( psource_support_user_can( $users[1], 'read_ticket', $ticket_id ), 'Category membership must allow scoped reading without a global ticket role.' );
	$assert( ! psource_support_user_can( $users[1], 'read_ticket' ), 'Category membership must not grant global ticket reading.' );
	$overrides['psource_support_tickets_role'] = array( 'subscriber' );
	$assert( ! psource_support_user_can( $users[3], 'update_ticket', $ticket_id ), 'Unrelated user must not handle ticket.' );
	$assert( 2 === psource_support_get_ticket_status_after_reply( $ticket, $users[1] ), 'Team replies must wait for requester.' );
	$assert( $users[1] === psource_support_get_valid_ticket_admin_id( $logins[1], $ticket ), 'Team member must be assignable.' );
	$assert( false === psource_support_get_valid_ticket_admin_id( get_userdata( $users[3] )->user_login, $ticket ), 'Nonmember must not be assignable.' );

	wp_set_current_user( $users[1] );
	$args = psource_support()->settings->filter_query( array( 'category' => $cat_id, 'per_page' => -1 ) );
	$visible = wp_list_pluck( psource_support_get_tickets( $args ), 'ticket_id' );
	$assert( in_array( $ticket_id, $visible, true ), 'Team ticket must be visible with requester privacy.' );
	$assert( count( $visible ) === (int) psource_support_get_tickets_count( $args ), 'List and count visibility must agree.' );
	$overrides['psource_ticket_privacy'] = 'all';
	$public_args = psource_support()->settings->filter_query( array() );
	$assert( ! isset( $public_args['user_in'] ), 'Public-ticket visibility must remain unchanged.' );
	$overrides['psource_ticket_privacy'] = 'requestor';

	wp_set_current_user( $users[2] );
	$mail = array();
	$reply_id = psource_support_insert_ticket_reply( $ticket_id, array( 'poster_id' => $users[2], 'message' => 'Requester reply.' ) );
	$assert( (bool) $reply_id, 'Requester reply insertion failed.' );
	$expected_team = array( get_userdata( $users[0] )->user_email, get_userdata( $users[1] )->user_email );
	sort( $expected_team );
	$assert( $recipients() === $expected_team, 'Unassigned replies must notify every team member.' );
	$mail = array();
	psource_support_insert_ticket_reply( $ticket_id, array( 'poster_id' => $users[1], 'message' => 'Internal note.', 'is_internal' => 1 ) );
	$assert( $recipients() === array( get_userdata( $users[0] )->user_email ), 'Internal team notes must not mail creator or author.' );
	psource_support_update_ticket( $ticket_id, array( 'admin_id' => $users[1] ) );
	$mail = array();
	psource_support_insert_ticket_reply( $ticket_id, array( 'poster_id' => $users[2], 'message' => 'Assigned reply.' ) );
	$assert( $recipients() === array( get_userdata( $users[1] )->user_email ), 'Claimed ticket replies must notify its assignee only.' );

	$logins = array( $logins[0] );
	if ( is_multisite() ) {
		remove_user_from_blog( $users[1], get_current_blog_id() );
	}
	$assert( psource_support_get_category_assignee_ids( $category ) === array( $users[0] ), 'Unavailable team member must be excluded.' );
	$assert( psource_support_category_has_team( $category ), 'Filtering to one member must not silently change routing mode.' );
	$logins = array();
	if ( is_multisite() ) {
		remove_user_from_blog( $users[0], get_current_blog_id() );
	}
	$assert( array() === psource_support_get_category_assignee_ids( $category ), 'An unavailable team must return no members.' );
	$mail = array();
	psource_support_send_admin_new_ticket_mail( $ticket_id );
	$assert( array() === $recipients(), 'Unavailable teams must not notify a fallback administrator.' );
	$logins = array( get_userdata( $users[0] )->user_login );
	$logins[] = get_userdata( $users[1] )->user_login;
	if ( is_multisite() ) {
		add_user_to_blog( get_current_blog_id(), $users[0], 'subscriber' );
		add_user_to_blog( get_current_blog_id(), $users[1], 'subscriber' );
	}

	$assert( psource_support_update_ticket_category( $cat_id, array( 'assignee_ids' => array( $users[1] ) ) ), 'Team-to-single update failed.' );
	$category = psource_support_get_ticket_category( $cat_id );
	$assert( $users[1] === $category->user_id && ! psource_support_category_has_team( $category ), 'One selection must retain legacy user_id.' );
	$assert( array( $users[1] ) === psource_support_get_category_assignee_ids( $category ), 'Legacy fallback must work.' );
	$single_ticket = psource_support_insert_ticket( array( 'cat_id' => $cat_id, 'user_id' => $users[2], 'title' => $prefix . '-single', 'message' => 'Single assignment.' ) );
	$assert( ! is_wp_error( $single_ticket ), 'Single ticket creation failed.' );
	$tickets[] = $single_ticket;
	$assert( $users[1] === psource_support_get_ticket( $single_ticket )->admin_id, 'Legacy single routing must be preserved.' );
	$assert( psource_support_update_ticket_category( $cat_id, array( 'assignee_ids' => array() ) ), 'Clearing assignment failed.' );
	$assert( array() === psource_support_get_category_assignee_ids( psource_support_get_ticket_category( $cat_id ) ), 'Cleared category must have no assignees.' );

	if ( is_multisite() ) {
		$GLOBALS['psource_support_data_blog_id_override'] = get_current_blog_id();
		$local_id = psource_support_insert_ticket_category( $prefix, 0, array( $users[0], $users[1] ) );
		$assert( (bool) $local_id, 'Subsite team creation failed.' );
		$categories[] = $local_id;
		$local = psource_support_get_ticket_category( $local_id );
		$assert( $local->blog_id === get_current_blog_id(), 'Subsite team must use local data scope.' );
		$assert( false === psource_support_get_ticket_category( $cat_id ), 'Network category must not resolve in local scope.' );
		$assert( ! psource_support_user_handles_team_ticket( $users[1], $ticket ), 'Local scope must not grant handling of network category ticket.' );
		$assert( array( $users[0], $users[1] ) === psource_support_get_category_assignee_ids( $local ), 'Subsite team must resolve its own IDs.' );
		$local_admin = new WP_User( $users[3] );
		$local_admin->set_role( 'administrator' );
		$assert( psource_support_user_can( $users[3], 'update_ticket_category' ), 'Subsite administrator must manage local categories.' );
		$GLOBALS['psource_support_data_blog_id_override'] = 0;
		$assert( ! psource_support_user_can( $users[3], 'update_ticket_category' ), 'Local administration must not grant network category management.' );
	}
	echo "PASS: $assertions category-team integration assertions.\n";
} finally {
	if ( isset( $prefix ) ) {
		$fixture_tickets = $wpdb->get_col( $wpdb->prepare(
			'SELECT ticket_id FROM ' . psource_support()->model->tickets_table . ' WHERE title LIKE %s',
			$wpdb->esc_like( $prefix ) . '%'
		) );
		$tickets = array_unique( array_merge( $tickets, $fixture_tickets ) );
	}
	foreach ( $tickets as $ticket_id ) {
		psource_support_delete_ticket( $ticket_id );
		$wpdb->delete( psource_support()->model->ticketmeta, array( 'ticket_id' => $ticket_id ), array( '%d' ) );
		$wpdb->delete( psource_support()->model->ticket_labels_table, array( 'ticket_id' => $ticket_id ), array( '%d' ) );
	}
	foreach ( $categories as $cat_id ) {
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . psource_support_get_ticket_category_table() . ' WHERE cat_id = %d', $cat_id ) );
		if ( $row ) {
			$category = psource_support_get_ticket_category( $row );
			delete_network_option( $category->site_id, psource_support_category_team_option_name( $category ) );
			psource_support_delete_ticket_category_row( $cat_id );
			psource_support_clean_ticket_category_cache( $category );
		}
	}
	require_once ABSPATH . 'wp-admin/includes/user.php';
	require_once ABSPATH . 'wp-admin/includes/ms.php';
	foreach ( $users as $user_id ) {
		is_multisite() ? wpmu_delete_user( $user_id ) : wp_delete_user( $user_id );
	}
	remove_filter( 'site_option_psource_support_settings', $settings_filter );
	remove_filter( 'option_psource_support_settings', $settings_filter );
	remove_filter( 'psource_support_get_super_admins', $assignees_filter );
	remove_filter( 'pre_wp_mail', $mail_filter );
	wp_set_current_user( $previous_user );
	if ( $had_scope ) {
		$GLOBALS['psource_support_data_blog_id_override'] = $previous_scope;
	} else {
		unset( $GLOBALS['psource_support_data_blog_id_override'] );
	}
}
