<?php
/**
 * Antwort-Templates-Verwaltung in den Einstellungen
 * Variables: $templates (array), $form_url (string)
 */
?>
<div class="support-settings-templates">
	<h2><?php _e( 'Antwort-Templates verwalten', 'psource-support' ); ?></h2>
	<p class="description"><?php _e( 'Vordefinierte Textbausteine, die beim Verfassen von Antworten per Klick eingefügt werden können.', 'psource-support' ); ?></p>

	<form method="post" action="<?php echo esc_url( $form_url ); ?>">
		<?php wp_nonce_field( 'psource-support-templates-nonce' ); ?>
		<table class="form-table">
			<tr>
				<th><label for="template_title"><?php _e( 'Titel', 'psource-support' ); ?></label></th>
				<td><input type="text" name="template_title" id="template_title" class="regular-text" required /></td>
			</tr>
			<tr>
				<th><label for="template_content"><?php _e( 'Inhalt', 'psource-support' ); ?></label></th>
				<td>
					<?php
					wp_editor(
						'',
						'template_content',
						array(
							'media_buttons' => false,
							'textarea_rows' => 8,
							'quicktags'     => false,
						)
					);
					?>
				</td>
			</tr>
		</table>
		<input type="hidden" name="template_id" value="0" />
		<p><input type="submit" name="template_save" class="button button-primary" value="<?php esc_attr_e( 'Template hinzufügen', 'psource-support' ); ?>" /></p>
	</form>

	<?php if ( ! empty( $templates ) ) : ?>
		<hr />
		<h3><?php _e( 'Vorhandene Templates', 'psource-support' ); ?></h3>

		<?php foreach ( $templates as $tpl ) : ?>
			<div class="postbox support-template-edit-box" style="margin-bottom:12px">
				<div class="postbox-header" style="padding:8px 12px;display:flex;justify-content:space-between;align-items:center">
					<strong><?php echo esc_html( $tpl['title'] ); ?></strong>
					<form method="post" action="<?php echo esc_url( $form_url ); ?>" style="display:inline">
						<?php wp_nonce_field( 'psource-support-templates-nonce' ); ?>
						<input type="hidden" name="delete_template" value="<?php echo esc_attr( $tpl['id'] ); ?>" />
						<button type="submit" class="button button-link-delete" onclick="return confirm('<?php esc_attr_e( 'Template wirklich löschen?', 'psource-support' ); ?>')"><?php _e( 'Löschen', 'psource-support' ); ?></button>
					</form>
				</div>
				<div class="inside">
					<form method="post" action="<?php echo esc_url( $form_url ); ?>">
						<?php wp_nonce_field( 'psource-support-templates-nonce' ); ?>
						<table class="form-table">
							<tr>
								<th><label><?php _e( 'Titel', 'psource-support' ); ?></label></th>
								<td><input type="text" name="template_title" class="regular-text" value="<?php echo esc_attr( $tpl['title'] ); ?>" required /></td>
							</tr>
							<tr>
								<th><label><?php _e( 'Inhalt', 'psource-support' ); ?></label></th>
								<td>
									<?php
									wp_editor(
										$tpl['content'],
										'template_content_' . (int) $tpl['id'],
										array(
											'textarea_name' => 'template_content',
											'media_buttons' => false,
											'textarea_rows' => 6,
											'quicktags'     => false,
										)
									);
									?>
								</td>
							</tr>
						</table>
						<input type="hidden" name="template_id" value="<?php echo esc_attr( $tpl['id'] ); ?>" />
						<p><input type="submit" name="template_save" class="button" value="<?php esc_attr_e( 'Speichern', 'psource-support' ); ?>" /></p>
					</form>
				</div>
			</div>
		<?php endforeach; ?>

	<?php else : ?>
		<p><?php _e( 'Noch keine Templates vorhanden.', 'psource-support' ); ?></p>
	<?php endif; ?>
</div>
