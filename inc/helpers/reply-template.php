<?php

/**
 * Option key for storing all reply templates.
 */
define( 'PSOURCE_SUPPORT_REPLY_TEMPLATES_OPTION', 'psource_support_reply_templates' );

/**
 * Get all reply templates.
 *
 * @return array  Array of template objects with keys: id, title, content
 */
function psource_support_get_reply_templates() {
	$templates = get_site_option( PSOURCE_SUPPORT_REPLY_TEMPLATES_OPTION, array() );
	if ( ! is_array( $templates ) ) {
		$templates = array();
	}
	return $templates;
}

/**
 * Get a single reply template by ID.
 *
 * @param int $template_id
 * @return array|false
 */
function psource_support_get_reply_template( $template_id ) {
	$templates = psource_support_get_reply_templates();
	foreach ( $templates as $tpl ) {
		if ( (int) $tpl['id'] === (int) $template_id ) {
			return $tpl;
		}
	}
	return false;
}

/**
 * Insert a new reply template.
 *
 * @param string $title
 * @param string $content  HTML content
 * @return int|false  New template ID or false on failure
 */
function psource_support_insert_reply_template( $title, $content ) {
	$title = sanitize_text_field( $title );
	if ( empty( $title ) ) {
		return false;
	}

	$templates = psource_support_get_reply_templates();

	// Get next sequential ID
	$max_id = 0;
	foreach ( $templates as $tpl ) {
		if ( (int) $tpl['id'] > $max_id ) {
			$max_id = (int) $tpl['id'];
		}
	}
	$new_id = $max_id + 1;

	$templates[] = array(
		'id'      => $new_id,
		'title'   => $title,
		'content' => wp_kses_post( $content ),
	);

	update_site_option( PSOURCE_SUPPORT_REPLY_TEMPLATES_OPTION, $templates );

	return $new_id;
}

/**
 * Update an existing reply template.
 *
 * @param int    $template_id
 * @param string $title
 * @param string $content
 * @return bool
 */
function psource_support_update_reply_template( $template_id, $title, $content ) {
	$title = sanitize_text_field( $title );
	if ( empty( $title ) ) {
		return false;
	}

	$templates = psource_support_get_reply_templates();
	$found     = false;

	foreach ( $templates as &$tpl ) {
		if ( (int) $tpl['id'] === (int) $template_id ) {
			$tpl['title']   = $title;
			$tpl['content'] = wp_kses_post( $content );
			$found = true;
			break;
		}
	}
	unset( $tpl );

	if ( ! $found ) {
		return false;
	}

	update_site_option( PSOURCE_SUPPORT_REPLY_TEMPLATES_OPTION, $templates );

	return true;
}

/**
 * Delete a reply template.
 *
 * @param int $template_id
 * @return bool
 */
function psource_support_delete_reply_template( $template_id ) {
	$templates = psource_support_get_reply_templates();
	$new_list  = array();

	foreach ( $templates as $tpl ) {
		if ( (int) $tpl['id'] !== (int) $template_id ) {
			$new_list[] = $tpl;
		}
	}

	if ( count( $new_list ) === count( $templates ) ) {
		return false; // Not found
	}

	update_site_option( PSOURCE_SUPPORT_REPLY_TEMPLATES_OPTION, $new_list );

	return true;
}
