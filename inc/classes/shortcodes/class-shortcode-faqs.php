<?php

class PSource_Support_FAQs_Shortcode extends PSource_Support_Shortcode {
	public function __construct() {
		if ( !is_admin() ) {
			add_shortcode( 'support-system-faqs', array( $this, 'render' ) );
		}
	}

	public function render( $atts ) {
		$atts = shortcode_atts( array( 'scope' => 'site' ), $atts, 'support-system-faqs' );
		$network_scope = is_multisite() && 'network' === $atts['scope'];
		if ( $network_scope ) {
			$GLOBALS['psource_support_data_blog_id_override'] = 0;
			$query = psource_support()->query;
			$args = array( 'per_page' => -1, 'blog_id' => 0 );
			if ( $query->faq_category_id ) {
				$args['category'] = $query->faq_category_id;
			}
			if ( stripslashes( $query->faqs_search ) ) {
				$args['s'] = stripslashes( $query->faqs_search );
			}
			$query->faqs = psource_support_get_faqs( $args );
			$query->found_faqs = count( $query->faqs );
			$query->remaining_faqs = count( $query->faqs );
			$query->current_faq = -1;
		}
		$this->start();

		if ( ! psource_support_current_user_can( 'read_faq' ) ) {
			if ( ! is_user_logged_in() )
				$message = sprintf( __( 'Du musst <a href="%s">angemeldet</a> sein, um Support zu erhalten', 'psource-support' ), wp_login_url( get_permalink() ) );
			else
				$message = __( 'Du hast nicht genügend Berechtigungen, um Unterstützung zu erhalten', 'psource-support' );
			
			$message = apply_filters( 'support_system_not_allowed_faqs_list_message', $message, 'faq-list' );
			?>
				<div class="support-system-alert warning">
					<?php echo $message; ?>
				</div>
			<?php
			$output = $this->end();
			if ( $network_scope ) {
				unset( $GLOBALS['psource_support_data_blog_id_override'] );
			}
			return $output;
		}

		psource_support_get_template( 'index', 'faqs' );

		$output = $this->end();
		if ( $network_scope ) {
			unset( $GLOBALS['psource_support_data_blog_id_override'] );
		}

		return $output;
	}

}