<?php

function psource_support_get_settings() {
	return psource_support()->settings->get_all();
}

function psource_support_get_setting( $name ) {
	return psource_support()->settings->get( $name );
}

function psource_support_get_default_settings() {
	return psource_support()->settings->get_default_settings();
}

function psource_support_update_setting( $name, $value ) {
	psource_support()->settings->set( $name, $value );
}

function psource_support_update_settings( $value ) {
	psource_support()->settings->update( $value );
}

function psource_support_get_data_blog_id( $blog_id = null ) {
	if ( array_key_exists( 'psource_support_data_blog_id_override', $GLOBALS ) ) {
		return absint( $GLOBALS['psource_support_data_blog_id_override'] );
	}
	if ( null !== $blog_id ) {
		return absint( $blog_id );
	}
	if ( ! is_multisite() || is_network_admin() ) {
		return 0;
	}
	if ( is_admin() && psource_support_subsite_settings_available() ) {
		return get_current_blog_id();
	}

	return psource_support_is_subsite_frontend() ? get_current_blog_id() : 0;
}

function psource_support_get_subsite_front_settings( $blog_id = 0 ) {
	if ( ! is_multisite() ) {
		return false;
	}

	$blog_id = $blog_id ? absint( $blog_id ) : get_current_blog_id();
	$subsite_pages = (array) psource_support_get_setting( 'psource_support_subsite_pages' );
	if ( empty( $subsite_pages[ $blog_id ]['active'] ) && empty( $subsite_pages[ $blog_id ]['forced_by'] ) ) {
		return false;
	}

	$config = wp_parse_args( $subsite_pages[ $blog_id ], array(
		'active' => false,
		'forced_by' => array(),
		'forced_features' => array(),
		'tickets_enabled' => false,
		'faqs_enabled' => false,
		'support_page_id' => 0,
		'new_ticket_page_id' => 0,
		'faqs_page_id' => 0,
		'staff_roles' => array(),
	) );
	foreach ( (array) $config['forced_features'] as $features ) {
		$config['tickets_enabled'] = $config['tickets_enabled'] || ! empty( $features['tickets'] );
		$config['faqs_enabled'] = $config['faqs_enabled'] || ! empty( $features['faqs'] );
	}

	return $config;
}

function psource_support_force_subsite_support( $blog_id, $integration, $args = array() ) {
	$blog_id = absint( $blog_id );
	$integration = sanitize_key( $integration );
	if ( ! is_multisite() || ! $blog_id || ! $integration || ! get_blog_details( $blog_id ) ) {
		return new WP_Error( 'invalid_subsite_support', __( 'Die Subsite-Supportkonfiguration ist ungültig.', 'psource-support' ) );
	}

	$all = (array) psource_support_get_setting( 'psource_support_subsite_pages' );
	$current = wp_parse_args( isset( $all[ $blog_id ] ) ? $all[ $blog_id ] : array(), array(
		'active' => false,
		'forced_by' => array(),
		'forced_features' => array(),
		'support_page_id' => 0,
		'new_ticket_page_id' => 0,
		'faqs_page_id' => 0,
		'staff_roles' => array(),
	) );
	$current['forced_by'][ $integration ] = true;
	$current['forced_features'][ $integration ] = array(
		'tickets' => ! empty( $args['tickets_enabled'] ) || ! empty( $args['tickets'] ),
		'faqs' => ! empty( $args['faqs_enabled'] ) || ! empty( $args['faqs'] ),
	);
	foreach ( array( 'support_page_id', 'new_ticket_page_id', 'faqs_page_id' ) as $key ) {
		if ( isset( $args[ $key ] ) ) {
			$current[ $key ] = absint( $args[ $key ] );
		}
	}
	if ( isset( $args['staff_roles'] ) ) {
		$current['staff_roles'] = array_values( array_unique( array_filter( array_map( 'sanitize_key', (array) $args['staff_roles'] ) ) ) );
	}
	$all[ $blog_id ] = $current;
	psource_support_update_setting( 'psource_support_subsite_pages', $all );

	return $current;
}

function psource_support_provision_subsite( $blog_id, $integration = '', $args = array() ) {
	$blog_id = absint( $blog_id );
	if ( ! is_multisite() || ! $blog_id || ! get_blog_details( $blog_id ) ) {
		return new WP_Error( 'invalid_subsite', __( 'Die gewählte Subsite ist ungültig.', 'psource-support' ) );
	}

	$defaults = array(
		'tickets' => true,
		'faqs' => true,
		'create_pages' => true,
	);
	$args = wp_parse_args( $args, $defaults );
	$all = (array) psource_support_get_setting( 'psource_support_subsite_pages' );
	$config = wp_parse_args( isset( $all[ $blog_id ] ) ? $all[ $blog_id ] : array(), array(
		'active' => false,
		'forced_by' => array(),
		'forced_features' => array(),
		'tickets_enabled' => false,
		'faqs_enabled' => false,
		'support_page_id' => 0,
		'new_ticket_page_id' => 0,
		'faqs_page_id' => 0,
		'staff_roles' => array(),
	) );
	$config['tickets_enabled'] = ! empty( $args['tickets'] );
	$config['faqs_enabled'] = ! empty( $args['faqs'] );
	if ( isset( $args['staff_roles'] ) ) {
		$config['staff_roles'] = array_values( array_unique( array_merge( (array) $config['staff_roles'], array_filter( array_map( 'sanitize_key', (array) $args['staff_roles'] ) ) ) ) );
	}

	if ( ! empty( $args['create_pages'] ) ) {
		switch_to_blog( $blog_id );
		if ( $config['tickets_enabled'] ) {
			$config['support_page_id'] = psource_support_ensure_subsite_page( $config['support_page_id'], __( 'Support', 'psource-support' ), '[support-system-tickets-index]' );
			$config['new_ticket_page_id'] = psource_support_ensure_subsite_page( $config['new_ticket_page_id'], __( 'Supportanfrage', 'psource-support' ), '[support-system-submit-ticket-form blog_field="false"]' );
		}
		if ( $config['faqs_enabled'] ) {
			$config['faqs_page_id'] = psource_support_ensure_subsite_page( $config['faqs_page_id'], __( 'Häufige Fragen', 'psource-support' ), '[support-system-faqs]' );
		}
		restore_current_blog();
	}

	$all[ $blog_id ] = $config;
	psource_support_update_setting( 'psource_support_subsite_pages', $all );
	if ( $integration ) {
		return psource_support_force_subsite_support( $blog_id, $integration, $config );
	}

	return $config;
}

function psource_support_ensure_subsite_page( $page_id, $title, $shortcode ) {
	$page_id = absint( $page_id );
	if ( $page_id && 'page' === get_post_type( $page_id ) ) {
		return $page_id;
	}

	$page_id = wp_insert_post( array(
		'post_title' => $title,
		'post_content' => $shortcode,
		'post_status' => 'publish',
		'post_type' => 'page',
	), true );

	return is_wp_error( $page_id ) ? 0 : absint( $page_id );
}

function psource_support_release_subsite_support( $blog_id, $integration ) {
	$blog_id = absint( $blog_id );
	$integration = sanitize_key( $integration );
	$all = (array) psource_support_get_setting( 'psource_support_subsite_pages' );
	if ( isset( $all[ $blog_id ]['forced_by'][ $integration ] ) ) {
		unset( $all[ $blog_id ]['forced_by'][ $integration ] );
		unset( $all[ $blog_id ]['forced_features'][ $integration ] );
		psource_support_update_setting( 'psource_support_subsite_pages', $all );
	}
}

function psource_support_is_subsite_frontend( $blog_id = 0 ) {
	$blog_id = $blog_id ? absint( $blog_id ) : get_current_blog_id();
	$central_blog_id = absint( psource_support_get_setting( 'psource_support_blog_id' ) );

	return $blog_id !== $central_blog_id && false !== psource_support_get_subsite_front_settings( $blog_id );
}

function psource_support_subsite_settings_available( $blog_id = 0 ) {
	$blog_id = $blog_id ? absint( $blog_id ) : get_current_blog_id();
	$config = psource_support_get_subsite_front_settings( $blog_id );

	return (bool) psource_support_get_setting( 'psource_support_allow_subsite_support' )
		|| ( $config && ! empty( $config['forced_by'] ) );
}

function psource_support_ticket_is_available_on_current_frontend( $ticket ) {
	if ( ! $ticket ) {
		return false;
	}

	return ! psource_support_is_subsite_frontend() || (int) $ticket->blog_id === get_current_blog_id();
}

function psource_support_get_support_page_url( $blog_id = 0 ) {
	$blog_id = $blog_id ? absint( $blog_id ) : get_current_blog_id();
	if ( is_multisite() && ! psource_support_get_subsite_front_settings( $blog_id ) ) {
		$blog_id = absint( psource_support_get_setting( 'psource_support_blog_id' ) );
	}
	if ( ! $blog_id ) {
		return false;
	}
	$switched = is_multisite() && $blog_id !== get_current_blog_id();
	if ( $switched ) {
		switch_to_blog( $blog_id );
	}

	$page = psource_support_get_support_page_id( $blog_id );
	$url = 'page' === get_post_type( $page ) ? get_permalink( $page ) : false;

	if ( $switched ) {
		restore_current_blog();
	}

	return $url;
}

function psource_support_get_faqs_page_url( $blog_id = 0, $faq_id = 0 ) {
	$blog_id = $blog_id ? absint( $blog_id ) : get_current_blog_id();
	$subsite = psource_support_get_subsite_front_settings( $blog_id );
	if ( is_multisite() && ! $subsite ) {
		$blog_id = absint( psource_support_get_setting( 'psource_support_blog_id' ) );
	}
	if ( ! $blog_id ) {
		return false;
	}

	$switched = is_multisite() && $blog_id !== get_current_blog_id();
	if ( $switched ) {
		switch_to_blog( $blog_id );
	}
	$page_id = psource_support_get_faqs_page_id( $blog_id );
	$url = 'page' === get_post_type( $page_id ) ? get_permalink( $page_id ) : false;
	if ( $switched ) {
		restore_current_blog();
	}

	return $url && $faq_id ? $url . '#support-faq-' . absint( $faq_id ) : $url;
}

function psource_support_get_faqs_page_id( $blog_id = 0 ) {
	$subsite = psource_support_get_subsite_front_settings( $blog_id );
	$page_id = $subsite ? ( ! empty( $subsite['faqs_enabled'] ) ? absint( $subsite['faqs_page_id'] ) : 0 ) : psource_support()->settings->get( 'psource_support_faqs_page' );

	return apply_filters( 'support_system_faqs_page_id', $page_id );
}

function psource_support_get_support_page_id( $blog_id = 0 ) {
	$subsite = psource_support_get_subsite_front_settings( $blog_id );
	$page_id = $subsite ? ( ! empty( $subsite['tickets_enabled'] ) ? absint( $subsite['support_page_id'] ) : 0 ) : psource_support()->settings->get( 'psource_support_support_page' );

	return apply_filters( 'support_system_support_page_id', $page_id );
}

function psource_support_get_new_ticket_page_id( $blog_id = 0 ) {
	$subsite = psource_support_get_subsite_front_settings( $blog_id );
	$page_id = $subsite ? ( ! empty( $subsite['tickets_enabled'] ) ? absint( $subsite['new_ticket_page_id'] ) : 0 ) : psource_support()->settings->get( 'psource_support_create_new_ticket_page' );

	return apply_filters( 'support_system_new_ticket_page_id', $page_id );
}

