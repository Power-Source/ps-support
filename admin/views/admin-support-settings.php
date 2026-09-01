<div class="wrap">
	<h1><?php esc_html_e( 'Support-Einstellungen dieser Site', 'psource-support' ); ?></h1>
	<?php if ( isset( $_GET['updated'] ) ): ?><div class="notice notice-success"><p><?php esc_html_e( 'Einstellungen gespeichert.', 'psource-support' ); ?></p></div><?php endif; ?>
	<?php if ( ! empty( $config['forced_by'] ) ): ?>
		<div class="notice notice-info"><p><strong><?php esc_html_e( 'Erzwungen durch:', 'psource-support' ); ?></strong> <?php echo esc_html( implode( ', ', array_keys( array_filter( $config['forced_by'] ) ) ) ); ?>. <?php esc_html_e( 'Die dafür benötigten Funktionen können lokal erweitert, aber nicht deaktiviert werden.', 'psource-support' ); ?></p></div>
	<?php endif; ?>
	<form method="post">
		<?php wp_nonce_field( 'save-site-support-settings' ); ?>
		<table class="form-table">
			<tr><th><?php esc_html_e( 'Eigenständiger Support', 'psource-support' ); ?></th><td><label><input type="checkbox" name="active" value="1" <?php checked( ! empty( $config['active'] ) ); ?> <?php disabled( ! psource_support_get_setting( 'psource_support_allow_subsite_support' ) ); ?>> <?php esc_html_e( 'Für diese Site aktivieren', 'psource-support' ); ?></label></td></tr>
			<tr><th><?php esc_html_e( 'Funktionen', 'psource-support' ); ?></th><td>
				<label><input type="checkbox" name="tickets_enabled" value="1" <?php checked( ! empty( $config['tickets_enabled'] ) ); ?>> <?php esc_html_e( 'Tickets', 'psource-support' ); ?></label><br>
				<label><input type="checkbox" name="faqs_enabled" value="1" <?php checked( ! empty( $config['faqs_enabled'] ) ); ?>> <?php esc_html_e( 'FAQs', 'psource-support' ); ?></label>
			</td></tr>
			<tr><th><?php esc_html_e( 'Seiten', 'psource-support' ); ?></th><td>
				<p><label><?php esc_html_e( 'Ticketübersicht', 'psource-support' ); ?><br><?php echo $page_dropdowns['support_page_id']; ?></label></p>
				<p><label><?php esc_html_e( 'Ticketformular', 'psource-support' ); ?><br><?php echo $page_dropdowns['new_ticket_page_id']; ?></label></p>
				<p><label><?php esc_html_e( 'FAQ-Seite', 'psource-support' ); ?><br><?php echo $page_dropdowns['faqs_page_id']; ?></label></p>
				<label><input type="checkbox" name="create_pages" value="1" checked> <?php esc_html_e( 'Fehlende Seiten automatisch erstellen', 'psource-support' ); ?></label>
			</td></tr>
			<tr><th><?php esc_html_e( 'Mitarbeiter-Rollen', 'psource-support' ); ?></th><td>
				<?php foreach ( $roles as $role => $label ): ?><label><input type="checkbox" name="staff_roles[]" value="<?php echo esc_attr( $role ); ?>" <?php checked( in_array( $role, (array) $config['staff_roles'], true ) ); ?>> <?php echo esc_html( $label ); ?></label><br><?php endforeach; ?>
				<p class="description"><?php esc_html_e( 'Ohne Auswahl gelten die globalen Mitarbeiter-Rollen.', 'psource-support' ); ?></p>
			</td></tr>
		</table>
		<?php submit_button( __( 'Support-Einstellungen speichern', 'psource-support' ), 'primary', 'save-site-support-settings' ); ?>
	</form>
</div>
