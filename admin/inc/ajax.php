<?php

class PSource_Support_Admin_Ajax {
	public function __construct() {
		add_action( 'wp_ajax_vote_faq_question', array( $this, 'vote_faq_question' ) );
	}

	/**
	 * Votes a question via AJAX
	 * 
	 * @since 1.8
	 */
	public function vote_faq_question() {
		if ( ! check_ajax_referer( 'support-system-vote-faq', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Ungültige Anfrage.', 'psource-support' ) ), 403 );
		}

		if ( ! psource_support_current_user_can( 'read_faq' ) ) {
			wp_send_json_error( array( 'message' => __( 'Nicht ausreichend Berechtigungen.', 'psource-support' ) ), 403 );
		}

		$faq_id = isset( $_POST['faq_id'] ) ? absint( wp_unslash( $_POST['faq_id'] ) ) : 0;
		$vote = isset( $_POST['vote'] ) ? sanitize_key( wp_unslash( $_POST['vote'] ) ) : '';

		if ( ! $faq_id || ! in_array( $vote, array( 'yes', 'no' ), true ) ) {
			wp_send_json_error( array( 'message' => __( 'Ungültige Daten übergeben.', 'psource-support' ) ), 400 );
		}

		$updated = psource_support_vote_faq( $faq_id, 'yes' === $vote );
		if ( ! $updated ) {
			wp_send_json_error( array( 'message' => __( 'Stimme konnte nicht gespeichert werden.', 'psource-support' ) ), 500 );
		}

		wp_send_json_success();
	}
}

new PSource_Support_Admin_Ajax();
