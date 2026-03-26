<?php

/**
 * Get all labels for a given site.
 *
 * @param int $site_id
 * @return array
 */
function psource_support_get_labels( $site_id = 0 ) {
	global $wpdb, $current_site;

	if ( ! $site_id ) {
		$site_id = ! empty( $current_site ) ? (int) $current_site->id : 1;
	}

	$table = psource_support()->model->labels_table;
	$results = $wpdb->get_results(
		$wpdb->prepare( "SELECT * FROM $table WHERE site_id = %d ORDER BY label_name ASC", $site_id )
	);

	return $results ? $results : array();
}

/**
 * Get a single label by ID.
 *
 * @param int $label_id
 * @return object|false
 */
function psource_support_get_label( $label_id ) {
	global $wpdb;

	$table = psource_support()->model->labels_table;
	return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE label_id = %d LIMIT 1", absint( $label_id ) ) );
}

/**
 * Insert a new label.
 *
 * @param string $name
 * @param string $color Hex color e.g. "#ff5733"
 * @param int    $site_id
 * @return int|false label_id on success, false on failure
 */
function psource_support_insert_label( $name, $color = '#607d8b', $site_id = 0 ) {
	global $wpdb, $current_site;

	$name = sanitize_text_field( $name );
	if ( empty( $name ) ) {
		return false;
	}

	// Basic hex color validation and fallback
	if ( ! preg_match( '/^#[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/', $color ) ) {
		$color = '#607d8b';
	}

	if ( ! $site_id ) {
		$site_id = ! empty( $current_site ) ? (int) $current_site->id : 1;
	}

	$table = psource_support()->model->labels_table;
	$result = $wpdb->insert(
		$table,
		array(
			'site_id'    => $site_id,
			'label_name' => $name,
			'label_color' => $color,
		),
		array( '%d', '%s', '%s' )
	);

	if ( ! $result ) {
		return false;
	}

	return (int) $wpdb->insert_id;
}

/**
 * Update an existing label.
 *
 * @param int    $label_id
 * @param string $name
 * @param string $color
 * @return bool
 */
function psource_support_update_label( $label_id, $name, $color = '#607d8b' ) {
	global $wpdb;

	$name = sanitize_text_field( $name );
	if ( empty( $name ) ) {
		return false;
	}

	if ( ! preg_match( '/^#[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/', $color ) ) {
		$color = '#607d8b';
	}

	$table = psource_support()->model->labels_table;
	$result = $wpdb->update(
		$table,
		array( 'label_name' => $name, 'label_color' => $color ),
		array( 'label_id' => absint( $label_id ) ),
		array( '%s', '%s' ),
		array( '%d' )
	);

	return $result !== false;
}

/**
 * Delete a label and remove all its ticket assignments.
 *
 * @param int $label_id
 * @return bool
 */
function psource_support_delete_label( $label_id ) {
	global $wpdb;

	$label_id = absint( $label_id );
	if ( ! $label_id ) {
		return false;
	}

	$labels_table        = psource_support()->model->labels_table;
	$ticket_labels_table = psource_support()->model->ticket_labels_table;

	$wpdb->delete( $ticket_labels_table, array( 'label_id' => $label_id ), array( '%d' ) );
	$result = $wpdb->delete( $labels_table, array( 'label_id' => $label_id ), array( '%d' ) );

	return (bool) $result;
}

/**
 * Get all label IDs assigned to a ticket.
 *
 * @param int $ticket_id
 * @return int[]
 */
function psource_support_get_ticket_label_ids( $ticket_id ) {
	global $wpdb;

	$ticket_labels_table = psource_support()->model->ticket_labels_table;
	$rows = $wpdb->get_col(
		$wpdb->prepare( "SELECT label_id FROM $ticket_labels_table WHERE ticket_id = %d", absint( $ticket_id ) )
	);

	return array_map( 'intval', $rows );
}

/**
 * Get full label objects assigned to a ticket.
 *
 * @param int $ticket_id
 * @return array
 */
function psource_support_get_ticket_labels( $ticket_id ) {
	$ids = psource_support_get_ticket_label_ids( $ticket_id );
	if ( empty( $ids ) ) {
		return array();
	}

	global $wpdb;
	$labels_table = psource_support()->model->labels_table;
	$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
	$query = $wpdb->prepare(
		"SELECT * FROM $labels_table WHERE label_id IN ($placeholders)",
		...$ids
	);

	return $wpdb->get_results( $query );
}

/**
 * Set labels for a ticket (replaces all existing assignments).
 *
 * @param int   $ticket_id
 * @param int[] $label_ids
 * @return bool
 */
function psource_support_set_ticket_labels( $ticket_id, $label_ids ) {
	global $wpdb;

	$ticket_id = absint( $ticket_id );
	if ( ! $ticket_id ) {
		return false;
	}

	$ticket_labels_table = psource_support()->model->ticket_labels_table;

	// Remove old assignments
	$wpdb->delete( $ticket_labels_table, array( 'ticket_id' => $ticket_id ), array( '%d' ) );

	if ( empty( $label_ids ) ) {
		return true;
	}

	// Validate and insert new ones
	$all_label_ids = wp_list_pluck( psource_support_get_labels(), 'label_id' );
	foreach ( $label_ids as $label_id ) {
		$label_id = absint( $label_id );
		if ( in_array( $label_id, array_map( 'intval', $all_label_ids ) ) ) {
			$wpdb->insert(
				$ticket_labels_table,
				array( 'ticket_id' => $ticket_id, 'label_id' => $label_id ),
				array( '%d', '%d' )
			);
		}
	}

	return true;
}
