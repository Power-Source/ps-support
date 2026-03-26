<?php
$base_overview_url = remove_query_arg( array( 'ticket_status', 'status', 'priority' ) );

$overview_cards = array(
	'active' => array(
		'label' => __( 'Offen', 'psource-support' ),
		'count' => $ticket_overview['active'],
		'url' => add_query_arg( 'status', 'active', $base_overview_url ),
	),
	'unassigned' => array(
		'label' => __( 'Nicht zugewiesen', 'psource-support' ),
		'count' => $ticket_overview['unassigned'],
		'url' => add_query_arg(
			array(
				'status' => 'active',
				'assignment' => 'unassigned',
			),
			$base_overview_url
		),
	),
	'waiting_admin' => array(
		'label' => __( 'Warten auf Admin', 'psource-support' ),
		'count' => $ticket_overview['waiting_admin'],
		'url' => add_query_arg(
			array(
				'status' => 'active',
				'ticket_status' => 3,
			),
			$base_overview_url
		),
	),
	'waiting_user' => array(
		'label' => __( 'Warten auf Benutzer', 'psource-support' ),
		'count' => $ticket_overview['waiting_user'],
		'url' => add_query_arg(
			array(
				'status' => 'active',
				'ticket_status' => 2,
			),
			$base_overview_url
		),
	),
	'urgent' => array(
		'label' => __( 'Dringend', 'psource-support' ),
		'count' => $ticket_overview['urgent'],
		'url' => add_query_arg(
			array(
				'status' => 'active',
				'priority' => 4,
			),
			$base_overview_url
		),
	),
	'my_queue' => array(
		'label' => __( 'Meine Queue', 'psource-support' ),
		'count' => $ticket_overview['my_queue'],
		'url' => add_query_arg(
			array(
				'status'      => 'active',
				'assignment'  => 'mine',
				'ticket_status' => 3,
			),
			$base_overview_url
		),
	),
	'closed' => array(
		'label' => __( 'Geschlossen', 'psource-support' ),
		'count' => $ticket_overview['closed'],
		'url' => add_query_arg( 'status', 'archive', $base_overview_url ),
	),
);
?>

<div class="support-ticket-overview">
	<?php foreach ( $overview_cards as $overview_key => $overview_card ) : ?>
		<a class="support-ticket-overview-card support-ticket-overview-card-<?php echo esc_attr( $overview_key ); ?>" href="<?php echo esc_url( $overview_card['url'] ); ?>">
			<span class="support-ticket-overview-count"><?php echo number_format_i18n( $overview_card['count'] ); ?></span>
			<span class="support-ticket-overview-label"><?php echo esc_html( $overview_card['label'] ); ?></span>
		</a>
	<?php endforeach; ?>
</div>
