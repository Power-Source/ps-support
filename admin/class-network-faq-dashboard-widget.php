<?php

class PSource_Support_Network_FAQ_Dashboard_Widget {

	public function __construct() {
		if ( is_multisite() && ! is_network_admin() ) {
			add_action( 'wp_dashboard_setup', array( $this, 'register_widget' ) );
		}
	}

	public function register_widget() {
		if ( ! psource_support_get_setting( 'psource_support_network_faq_dashboard_enabled' ) || ! psource_support_current_user_can( 'read_faq' ) ) {
			return;
		}

		wp_add_dashboard_widget(
			'psource-support-network-faq',
			psource_support_get_setting( 'psource_support_network_faq_name' ),
			array( $this, 'render_widget' )
		);
	}

	public function render_widget() {
		$settings = psource_support_get_settings();
		$count = max( 1, min( 10, absint( $settings['psource_support_network_faq_dashboard_count'] ) ) );
		$faqs = psource_support_get_faqs( array(
			'blog_id' => 0,
			'per_page' => 'latest' === $settings['psource_support_network_faq_dashboard_mode'] ? $count : -1,
			'orderby' => 'faq_id',
			'order' => 'desc',
		) );

		if ( 'sticky' === $settings['psource_support_network_faq_dashboard_mode'] ) {
			$faqs_by_id = array();
			foreach ( $faqs as $faq ) {
				$faqs_by_id[ (int) $faq->faq_id ] = $faq;
			}
			$sticky_faqs = array();
			foreach ( array_map( 'absint', (array) $settings['psource_support_network_faq_dashboard_sticky_ids'] ) as $faq_id ) {
				if ( isset( $faqs_by_id[ $faq_id ] ) ) {
					$sticky_faqs[] = $faqs_by_id[ $faq_id ];
				}
			}
			$faqs = array_slice( $sticky_faqs, 0, $count );
		}

		$page_url = $this->get_page_url();
		if ( ! $faqs ) {
			echo '<p>' . esc_html__( 'Derzeit sind keine Netzwerk-FAQs für das Dashboard ausgewählt.', 'psource-support' ) . '</p>';
			return;
		}

		echo '<div class="activity-block"><ul>';
		foreach ( $faqs as $faq ) {
			$faq_url = $page_url ? $page_url . '#support-faq-' . absint( $faq->faq_id ) : '';
			echo '<li><strong>';
			if ( $faq_url ) {
				echo '<a href="' . esc_url( $faq_url ) . '">' . esc_html( $faq->question ) . '</a>';
			} else {
				echo esc_html( $faq->question );
			}
			echo '</strong><p>' . esc_html( wp_trim_words( wp_strip_all_tags( $faq->answer ), 24 ) ) . '</p></li>';
		}
		echo '</ul></div>';
		if ( $page_url ) {
			echo '<p class="community-events-footer"><a href="' . esc_url( $page_url ) . '">' . esc_html__( 'Alle Netzwerk-FAQs ansehen', 'psource-support' ) . '</a></p>';
		}
	}

	private function get_page_url() {
		$main_site_id = function_exists( 'get_main_site_id' ) ? get_main_site_id( get_current_network_id() ) : ( defined( 'BLOG_ID_CURRENT_SITE' ) ? BLOG_ID_CURRENT_SITE : 1 );
		switch_to_blog( $main_site_id );
		$page_id = psource_support_get_faqs_page_id( $main_site_id );
		$url = 'page' === get_post_type( $page_id ) ? get_permalink( $page_id ) : false;
		restore_current_blog();

		return $url;
	}
}