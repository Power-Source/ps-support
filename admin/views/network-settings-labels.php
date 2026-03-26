<?php
/**
 * Labels-Verwaltung in den Einstellungen
 * Variables: $labels (array), $form_url (string)
 */
?>
<div class="support-settings-labels">
	<h2><?php _e( 'Ticket-Labels verwalten', 'psource-support' ); ?></h2>
	<p class="description"><?php _e( 'Labels können Tickets zur freien Kategorisierung zugewiesen werden.', 'psource-support' ); ?></p>

	<form method="post" action="<?php echo esc_url( $form_url ); ?>">
		<?php wp_nonce_field( 'psource-support-labels-nonce' ); ?>
		<table class="form-table">
			<tr>
				<th><label for="label_name"><?php _e( 'Name', 'psource-support' ); ?></label></th>
				<td><input type="text" name="label_name" id="label_name" class="regular-text" required /></td>
			</tr>
			<tr>
				<th><label for="label_color"><?php _e( 'Farbe', 'psource-support' ); ?></label></th>
				<td><input type="color" name="label_color" id="label_color" value="#607d8b" /></td>
			</tr>
			<input type="hidden" name="label_id" value="0" />
		</table>
		<p><input type="submit" name="label_save" class="button button-primary" value="<?php esc_attr_e( 'Label hinzufügen', 'psource-support' ); ?>" /></p>
	</form>

	<?php if ( ! empty( $labels ) ) : ?>
		<hr />
		<h3><?php _e( 'Vorhandene Labels', 'psource-support' ); ?></h3>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php _e( 'Label', 'psource-support' ); ?></th>
					<th><?php _e( 'Farbe', 'psource-support' ); ?></th>
					<th style="width:120px"><?php _e( 'Aktionen', 'psource-support' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $labels as $label ) : ?>
					<tr>
						<td>
							<span class="support-ticket-label" style="background:<?php echo esc_attr( $label->label_color ); ?>">
								<?php echo esc_html( $label->label_name ); ?>
							</span>
						</td>
						<td>
							<form method="post" action="<?php echo esc_url( $form_url ); ?>" style="display:inline-flex;gap:8px;align-items:center">
								<?php wp_nonce_field( 'psource-support-labels-nonce' ); ?>
								<input type="text" name="label_name" value="<?php echo esc_attr( $label->label_name ); ?>" class="regular-text" />
								<input type="color" name="label_color" value="<?php echo esc_attr( $label->label_color ); ?>" />
								<input type="hidden" name="label_id" value="<?php echo esc_attr( $label->label_id ); ?>" />
								<input type="submit" name="label_save" class="button" value="<?php esc_attr_e( 'Speichern', 'psource-support' ); ?>" />
							</form>
						</td>
						<td>
							<form method="post" action="<?php echo esc_url( $form_url ); ?>" style="display:inline">
								<?php wp_nonce_field( 'psource-support-labels-nonce' ); ?>
								<input type="hidden" name="delete_label" value="<?php echo esc_attr( $label->label_id ); ?>" />
								<button type="submit" class="button button-link-delete" onclick="return confirm('<?php esc_attr_e( 'Label wirklich löschen?', 'psource-support' ); ?>')"><?php _e( 'Löschen', 'psource-support' ); ?></button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php else : ?>
		<p><?php _e( 'Noch keine Labels vorhanden.', 'psource-support' ); ?></p>
	<?php endif; ?>
</div>
