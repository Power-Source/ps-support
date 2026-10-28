<?php
/**
 * wp --skip-plugins --skip-themes eval-file wp-content/plugins/ps-support/tests/support-bot.php
 * Development database only. Every option/category/user fixture is restored in finally.
 */
if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( "Run with wp eval-file.\n" );
}
if ( ! function_exists( 'psource_support' ) ) {
	require dirname( __DIR__ ) . '/psource-support.php';
	support_system_init();
	psource_support()->init_plugin();
}
$scope = get_current_blog_id();
$local_scope = is_multisite() ? $scope : 0;
$missing = 'ps-support-bot-missing-' . wp_generate_uuid4();
$old_network = get_site_option( PSource_Support_Bot::OPTION, $missing );
$old_local = get_option( PSource_Support_Bot::OPTION, $missing );
$had_scope = array_key_exists( 'psource_support_data_blog_id_override', $GLOBALS );
$old_scope = $had_scope ? $GLOBALS['psource_support_data_blog_id_override'] : null;
$old_user = get_current_user_id();
$categories = array();
$user = 0;
$count = 0;
$assert = function( $condition, $message ) use ( &$count ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
	++$count;
};
try {
	delete_site_option( PSource_Support_Bot::OPTION );
	delete_option( PSource_Support_Bot::OPTION );
	$assert( ! PSource_Support_Bot::config()['enabled_chat'] && ! PSource_Support_Bot::config()['enabled_tickets'], 'Bot must default off.' );
	$prefix = 'bot-test-' . wp_generate_password( 8, false );
	$GLOBALS['psource_support_data_blog_id_override'] = 0;
	$central = psource_support_insert_ticket_category( $prefix . '-central', 0 );
	$categories[] = $central;
	$GLOBALS['psource_support_data_blog_id_override'] = $local_scope;
	$local = psource_support_insert_ticket_category( $prefix . '-local', 0 );
	$categories[] = $local;
	$assert( $central && $local, 'Category fixtures must exist.' );
	$raw = array(
		'enabled_chat' => '1', 'enabled_tickets' => '1', 'name' => '<b>Helper</b>',
		'greeting' => "Hello\nChoose a topic", 'private' => 'not public',
		'topics' => array( array( 'id' => 'Billing!', 'title' => '<b>Billing</b>', 'answer' => "Read <b>this</b>\nThen ask us.", 'category_id' => $central ) ),
	);
	$valid = PSource_Support_Bot::validate( $raw, 0 );
	$assert( ! is_wp_error( $valid ), 'Central categories must validate regardless of current scope override.' );
	$assert( 'Helper' === $valid['name'] && 'billing' === $valid['topics'][0]['id'] && false === strpos( $valid['topics'][0]['answer'], '<b>' ), 'Public strings must be plain text.' );
	$assert( array_keys( $valid ) === array( 'enabled_chat', 'enabled_tickets', 'name', 'greeting', 'topics' ), 'Public contract must expose exact keys.' );
	update_site_option( PSource_Support_Bot::OPTION, $raw );
	$local_raw = $raw;
	$local_raw['name'] = 'Local';
	$local_raw['enabled_chat'] = false;
	$local_raw['topics'][0]['category_id'] = $local;
	update_option( PSource_Support_Bot::OPTION, $local_raw );
	if ( is_multisite() ) {
		$assert( 'Helper' === PSource_Support_Bot::config( 0 )['name'], 'Central bot uses network option.' );
		$assert( 'Local' === PSource_Support_Bot::config( $scope )['name'] && ! PSource_Support_Bot::config( $scope )['enabled_chat'], 'Local bot must not inherit central settings.' );
		$assert( ! PSource_Support_Bot::config( 999999999 )['enabled_chat'], 'Nonexistent sites must not expose configuration.' );
	}
	if ( is_multisite() ) {
		$assert( is_wp_error( PSource_Support_Bot::validate( $raw, $scope ) ), 'Cross-scope category must be rejected on save.' );
	}
	$foreign = psource_support_insert_ticket_category_row( array(
		'cat_name' => $prefix . '-foreign-network',
		'site_id' => ( is_multisite() ? get_current_network_id() : 1 ) + 99999,
		'blog_id' => 0, 'user_id' => 0,
	) );
	$categories[] = $foreign;
	$assert( (bool) $foreign, 'Foreign-network fixture must exist.' );
	$bad = $raw;
	$bad['topics'][0]['category_id'] = $foreign;
	$assert( is_wp_error( PSource_Support_Bot::validate( $bad ) ), 'Foreign-network category must be rejected.' );
	$bad = $raw;
	$bad['topics'][0]['category_id'] = 999999999;
	$assert( is_wp_error( PSource_Support_Bot::validate( $bad ) ), 'Missing category must be rejected on save.' );
	update_site_option( PSource_Support_Bot::OPTION, $bad );
	$assert( 0 === PSource_Support_Bot::config( 0 )['topics'][0]['category_id'], 'Deleted categories must normalize to default at read time.' );
	foreach ( array( '', '-1', 'abc', array() ) as $category ) {
		$bad['topics'][0]['category_id'] = $category;
		$assert( is_wp_error( PSource_Support_Bot::validate( $bad ) ), 'Malformed category must be rejected.' );
	}
	$bad = $raw;
	$bad['topics'][0]['answer'] = ' ';
	$assert( is_wp_error( PSource_Support_Bot::validate( $bad ) ), 'Empty answer must be rejected.' );
	$bad = $raw;
	$bad['topics'] = array();
	$assert( is_wp_error( PSource_Support_Bot::validate( $bad ) ), 'Enabled bot requires topics.' );
	$bad['enabled_chat'] = false;
	$bad['enabled_tickets'] = false;
	$assert( ! is_wp_error( PSource_Support_Bot::validate( $bad ) ), 'Disabled bot can have no topics.' );
	$bad['name'] = array();
	$assert( is_wp_error( PSource_Support_Bot::validate( $bad ) ), 'Malformed name must not cause a PHP error.' );
	$bad = $raw;
	$bad['topics'] = array_fill( 0, 51, $raw['topics'][0] );
	$assert( is_wp_error( PSource_Support_Bot::validate( $bad ) ), 'Topic limit must be enforced.' );
	$bad['topics'] = array_fill( 0, 2, $raw['topics'][0] );
	$deduped = PSource_Support_Bot::validate( $bad );
	$assert( $deduped['topics'][0]['id'] !== $deduped['topics'][1]['id'], 'Topic IDs must be unique.' );
	$user = wp_insert_user( array( 'user_login' => $prefix, 'user_email' => $prefix . '@example.invalid', 'user_pass' => wp_generate_password( 24 ), 'role' => 'administrator' ) );
	$assert( ! is_wp_error( $user ), 'Admin fixture must exist.' );
	wp_set_current_user( $user );
	update_option( PSource_Support_Bot::OPTION, $local_raw );
	$GLOBALS['psource_support_data_blog_id_override'] = $local_scope;
	PSource_Support_Bot::ticket_assets();
	$assert( wp_style_is( 'ps-support-bot', 'enqueued' ) && wp_script_is( 'ps-support-bot', 'enqueued' ), 'UI assets must enqueue before head output.' );
	ob_start();
	PSource_Support_Bot::ticket_ui();
	$html = ob_get_clean();
	$assert( false !== strpos( $html, 'data-config=' ) && false !== strpos( $html, 'data-bot-action="handoff"' ), 'Enabled UI must provide explicit handoff.' );
	$local_raw['enabled_tickets'] = false;
	update_option( PSource_Support_Bot::OPTION, $local_raw );
	ob_start();
	PSource_Support_Bot::ticket_ui();
	$assert( '' === ob_get_clean(), 'Disabled ticket bot must render nothing.' );
	$draft_key = 'ps_support_bot_draft_' . $user . '_' . $scope;
	set_transient( $draft_key, array(
		'input' => array_merge( $local_raw, array( 'name' => 'Draft <not-saved>', 'topics' => array( array( 'title' => 'Retained question', 'answer' => 'Retained answer', 'category_id' => 999999999 ) ) ) ),
		'error' => 'Inline validation test',
	), 60 );
	ob_start();
	PSource_Support_Bot::page();
	$draft_html = ob_get_clean();
	$assert( false !== strpos( $draft_html, 'Inline validation test' ) && false !== strpos( $draft_html, 'Draft &lt;not-saved&gt;' ) && false !== strpos( $draft_html, 'Retained answer' ), 'Non-JS validation redirect must show inline error and retain escaped draft fields.' );
	$assert( false !== strpos( $draft_html, 'selected value="999999999"' ), 'Invalid category draft must remain visible for correction.' );
	$assert( false === get_transient( $draft_key ), 'Validation draft is consumed after display.' );
	$assert( 'Local' === get_option( PSource_Support_Bot::OPTION )['name'], 'Displaying invalid draft must not persist it.' );
	if ( ! defined( 'DOING_AJAX' ) ) {
		define( 'DOING_AJAX', true );
	}
	$die_handler = function() {
		return function() { throw new RuntimeException( 'bot-json-complete' ); };
	};
	add_filter( 'wp_die_ajax_handler', $die_handler );
	$old_post = $_POST;
	$save = function( $post ) {
		$_POST = $post;
		ob_start();
		try {
			PSource_Support_Bot::save();
		} catch ( RuntimeException $e ) {
			if ( 'bot-json-complete' !== $e->getMessage() ) {
				ob_end_clean();
				throw $e;
			}
		}
		return json_decode( ob_get_clean(), true );
	};
	try {
		$post = array( 'network' => '0', '_wpnonce' => 'invalid', 'bot' => $local_raw );
		$result = $save( $post );
		$assert( false === $result['success'], 'Invalid nonce must return inline JSON error.' );
		$assert( 'Local' === get_option( PSource_Support_Bot::OPTION )['name'], 'Invalid nonce must not change settings.' );
		$post['_wpnonce'] = wp_create_nonce( 'ps_support_bot_save' );
		$post['bot']['name'] = 'Saved via AJAX';
		$post['bot']['topics'][0]['answer'] = '';
		$result = $save( $post );
		$assert( false === $result['success'] && ! empty( $result['data']['message'] ), 'Server validation must provide an inline message.' );
		$assert( 'Local' === get_option( PSource_Support_Bot::OPTION )['name'], 'Invalid settings must never be persisted.' );
		$post['bot']['topics'][0]['answer'] = 'Valid answer';
		$result = $save( $post );
		$assert( true === $result['success'] && 'Saved via AJAX' === get_option( PSource_Support_Bot::OPTION )['name'], 'Valid AJAX save persists local settings.' );
		if ( is_multisite() ) {
			$post['network'] = '1';
			$result = $save( $post );
			$assert( false === $result['success'], 'Site admin cannot write central network bot configuration.' );
		}
	} finally {
		$_POST = $old_post;
		remove_filter( 'wp_die_ajax_handler', $die_handler );
	}
	echo "Support bot: $count assertions passed.\n";
} finally {
	global $wpdb;
	foreach ( $categories as $id ) {
		$wpdb->delete( psource_support_get_ticket_category_table(), array( 'cat_id' => $id ), array( '%d' ) );
		wp_cache_delete( $id, 'support_system_ticket_categories' );
	}
	if ( $old_network === $missing ) {
		delete_site_option( PSource_Support_Bot::OPTION );
	} else {
		update_site_option( PSource_Support_Bot::OPTION, $old_network );
	}
	if ( $old_local === $missing ) {
		delete_option( PSource_Support_Bot::OPTION );
	} else {
		update_option( PSource_Support_Bot::OPTION, $old_local );
	}
	wp_set_current_user( $old_user );
	if ( $user && ! is_wp_error( $user ) ) {
		require_once ABSPATH . 'wp-admin/includes/user.php';
		wp_delete_user( $user );
	}
	if ( $had_scope ) {
		$GLOBALS['psource_support_data_blog_id_override'] = $old_scope;
	} else {
		unset( $GLOBALS['psource_support_data_blog_id_override'] );
	}
}
