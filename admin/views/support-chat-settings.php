<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap">
	<h1><?php esc_html_e( 'Privater Supportchat', 'psource-support' ); ?></h1>
	<?php if ( ! class_exists( 'PSource_Chat_Support' ) ) : ?>
		<div class="notice notice-warning"><p><?php esc_html_e( 'PS Chat ist auf dieser Site nicht aktiv. Die Einstellungen können gespeichert werden; der Chat erscheint erst, wenn PS Chat hier aktiviert ist.', 'psource-support' ); ?></p></div>
	<?php endif; ?>
	<?php if ( $central ) : ?>
		<p><?php esc_html_e( 'Diese Site ist die zentrale Support-Seite. Der Netzwerk-Supportchat erscheint hier automatisch, sobald er im Netzwerk aktiviert ist und PS Chat auf dieser Site aktiv ist.', 'psource-support' ); ?></p>
		<?php if ( current_user_can( 'manage_network_options' ) ) : ?>
			<p><a class="button button-primary" href="<?php echo esc_url( PSource_Support_Chat_Settings::url( true ) ); ?>"><?php esc_html_e( 'Netzwerk-Supportchat konfigurieren', 'psource-support' ); ?></a></p>
		<?php endif; ?>
		<?php return; ?>
	<?php endif; ?>
	<?php if ( $network ) : ?>
		<p><?php esc_html_e( 'Zentrale Support-Seite:', 'psource-support' ); ?> <a href="<?php echo esc_url( get_home_url( $central_blog_id, '/' ) ); ?>"><?php echo esc_html( get_home_url( $central_blog_id, '/' ) ); ?></a>. <?php esc_html_e( 'Die Seite wird in den allgemeinen Frontend-Einstellungen von PS Support festgelegt. PS Chat muss dort aktiv sein.', 'psource-support' ); ?></p>
	<?php elseif ( is_multisite() && ! $network_settings['allow_subsite_chat'] ) : ?>
		<div class="notice notice-warning"><p><?php esc_html_e( 'Supportchats auf Subsites sind vom Netzwerk nicht freigegeben. Du kannst Einstellungen vorbereiten, den Chat aber erst nach der Freigabe aktivieren.', 'psource-support' ); ?></p></div>
	<?php endif; ?>
	<?php if ( isset( $_GET['saved'] ) ) : ?>
		<div class="notice notice-success"><p><?php esc_html_e( 'Supportchat-Einstellungen gespeichert.', 'psource-support' ); ?></p></div>
	<?php endif; ?>
	<p><?php esc_html_e( 'Dieser Chat ist kein öffentlicher Raum: Jeder Besucher erhält eine private Sitzung. Kategorien und deren Bearbeiter oder Teams stammen aus PS Support. Eine E-Mail wird erst mit der ersten Nachricht versendet, nicht beim Öffnen des Fensters.', 'psource-support' ); ?></p>
	<form id="ps-support-chat-settings-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<div id="ps-support-settings-feedback" class="notice notice-error" role="alert" tabindex="-1" <?php if ( ! $form_error ) echo 'hidden'; ?>><p><?php echo esc_html( $form_error ); ?></p></div>
		<input type="hidden" name="action" value="ps_chat_support_settings">
		<input type="hidden" name="network" value="<?php echo $network ? '1' : '0'; ?>">
		<?php wp_nonce_field( 'ps_chat_support_settings' ); ?>
		<table class="form-table" role="presentation">
			<?php if ( $network ) : ?>
			<tr><th><?php esc_html_e( 'Supportchat auf Subsites', 'psource-support' ); ?></th><td>
				<label><input id="ps-support-allow-subsites" type="checkbox" name="support[allow_subsite_chat]" value="1" <?php checked( $settings['allow_subsite_chat'] ); ?>> <?php esc_html_e( 'Subsites dürfen Supportchats nutzen', 'psource-support' ); ?></label>
				<p class="description"><?php esc_html_e( 'Zusätzlich muss PS Chat auf der jeweiligen Subsite aktiv sein und der Site-Administrator den Chat dort einschalten. Die Freigabe gilt für eigenen und Netzwerk-Support; die zentrale Support-Seite ist davon unabhängig.', 'psource-support' ); ?></p>
			</td></tr>
			<?php endif; ?>
			<?php if ( $network || ! is_multisite() ) : ?>
			<tr><th><?php esc_html_e( 'Supportchat', 'psource-support' ); ?></th><td>
				<input type="hidden" name="support[mode]" value="disabled">
				<label><input id="ps-support-mode" type="checkbox" name="support[mode]" value="<?php echo $network ? 'network' : 'local'; ?>" <?php checked( $settings['mode'], $network ? 'network' : 'local' ); ?>> <?php esc_html_e( 'Supportchat aktivieren', 'psource-support' ); ?></label>
			</td></tr>
			<?php else : ?>
			<tr><th><label for="ps-support-mode"><?php esc_html_e( 'Wer beantwortet die Anfragen?', 'psource-support' ); ?></label></th><td>
				<select id="ps-support-mode" name="support[mode]">
					<option value="disabled" <?php selected( $settings['mode'], 'disabled' ); ?>><?php esc_html_e( 'Kein Chat – ausgeschaltet', 'psource-support' ); ?></option>
					<?php if ( ! $network ) : ?>
						<option value="local" <?php selected( $settings['mode'], 'local' ); ?> <?php disabled( is_multisite() && ! $network_settings['allow_subsite_chat'] ); ?>><?php esc_html_e( 'Unser Team auf dieser Website', 'psource-support' ); ?></option>
					<?php endif; ?>
					<?php if ( is_multisite() ) : ?>
						<option value="network" <?php selected( $settings['mode'], 'network' ); ?> <?php disabled( ! $network && ( ! $network_settings['allow_subsite_chat'] || $network_settings['mode'] !== 'network' ) ); ?>><?php esc_html_e( 'Das zentrale Supportteam des Netzwerks', 'psource-support' ); ?></option>
					<?php endif; ?>
				</select>
				<p class="description"><?php esc_html_e( 'Beim zentralen Team gelten dessen Mitarbeiter, Besucher-Hinweis und Chatregeln. Chatname und Platzierung kannst du hier ändern.', 'psource-support' ); ?></p>
			</td></tr>
			<?php endif; ?>
			<tr><th><label for="ps-support-placement"><?php esc_html_e( 'Platzierung', 'psource-support' ); ?></label></th><td>
				<select id="ps-support-placement" name="support[placement]">
					<option value="floating" <?php selected( $settings['placement'], 'floating' ); ?>><?php esc_html_e( 'Schwebendes Support-Icon', 'psource-support' ); ?></option>
					<option value="embedded" <?php selected( $settings['placement'], 'embedded' ); ?>><?php esc_html_e( 'Nur Shortcode / Widget', 'psource-support' ); ?></option>
				</select>
				<p class="description"><?php esc_html_e( 'Shortcode: [ps_chat_support] oder Widget „PS Chat: Privater Support“. Pro Seite wird nur eine Instanz angezeigt; ein eingebetteter Chat ersetzt dort das schwebende Icon. Ausschließlich private Support-Unterhaltungen.', 'psource-support' ); ?></p>
			</td></tr>
			<tr><th><label for="ps-support-label"><?php esc_html_e( 'Chatname', 'psource-support' ); ?></label></th><td>
				<input id="ps-support-label" name="support[label]" value="<?php echo esc_attr( $settings['label'] ); ?>" maxlength="100" required>
			</td></tr>
			<tr><th><label for="ps-support-intro"><?php esc_html_e( 'Hinweis für Besucher', 'psource-support' ); ?></label></th><td>
				<textarea id="ps-support-intro" name="support[intro_text]" class="large-text" rows="4" maxlength="2000"><?php echo esc_textarea( $settings['intro_text'] ); ?></textarea>
				<p class="description"><?php esc_html_e( 'Optionaler eigener Text vor Beginn des Chats, z. B. zum Ablauf oder zu Antwortzeiten. Leer lassen, um keinen Hinweis anzuzeigen. Bei Netzwerk-Support gilt der Text aus der Netzwerkverwaltung.', 'psource-support' ); ?></p>
			</td></tr>
			<tr><th><?php esc_html_e( 'Wer erhält Anfragen ohne zuständigen Bearbeiter?', 'psource-support' ); ?></th><td>
				<fieldset id="ps-support-staff">
					<legend class="screen-reader-text"><?php esc_html_e( 'Mitarbeiter auswählen', 'psource-support' ); ?></legend>
					<input type="hidden" name="support[fallback_users]" value="">
					<?php echo psource_support_user_picker( 'support[fallback_users][]', 'ps-support-staff-search', $settings['fallback_users'] ); ?>
					<?php if ( array_diff( array_map( 'absint', $settings['fallback_users'] ), $eligible ) ) : ?>
						<p class="notice notice-warning"><?php esc_html_e( 'Eine ausgewählte Person gehört nicht mehr zum Support-Team. Bitte entfernen oder zuerst in PS Support zuordnen.', 'psource-support' ); ?></p>
					<?php endif; ?>
					<p id="ps-support-staff-error" class="ps-support-field-error" role="alert" hidden></p>
					<p class="description"><?php esc_html_e( 'Dieses Standardteam übernimmt Chats ohne Kategorie-Zuständigkeit. Zur Auswahl stehen Support-Mitarbeiter und Administratoren, bei CRM-Anbindung nur CRM-Agenten.', 'psource-support' ); ?></p>
					<p class="description"><?php esc_html_e( 'Mitarbeiter-Rollen: allgemeine Support-Einstellungen. Themen-Teams: Ticketkategorien.', 'psource-support' ); ?></p>
				</fieldset>
			</td></tr>
			<tr><th><?php esc_html_e( 'Gastangaben', 'psource-support' ); ?></th><td>
				<label><input type="checkbox" name="support[require_name]" value="1" <?php checked( $settings['require_name'] ); ?>> <?php esc_html_e( 'Name vor Beginn verpflichtend', 'psource-support' ); ?></label><br>
				<label><input type="checkbox" name="support[require_email]" value="1" <?php checked( $settings['require_email'] ); ?>> <?php esc_html_e( 'E-Mail vor Beginn verpflichtend', 'psource-support' ); ?></label>
				<p class="description"><?php esc_html_e( 'Ohne diese Häkchen sind beide Angaben freiwillig. Es wird kein Benutzerkonto erstellt und keine E-Mail-Adresse verifiziert. Die Adresse ist eine Kontaktangabe, kein Nachweis der Identität.', 'psource-support' ); ?></p>
			</td></tr>
			<tr><th><label for="ps-support-hours"><?php esc_html_e( 'Wie lange können Besucher den Chat fortsetzen? (Stunden)', 'psource-support' ); ?></label></th><td>
				<input type="number" id="ps-support-hours" name="support[session_hours]" min="1" max="2160" required value="<?php echo (int) $settings['session_hours']; ?>">
				<p class="description"><?php esc_html_e( '168 Stunden entsprechen 7 Tagen. Besucher können die Unterhaltung im selben Browser wieder öffnen.', 'psource-support' ); ?></p>
			</td></tr>
			<tr><th><label for="ps-support-retention"><?php esc_html_e( 'Chatverlauf automatisch löschen nach (Tagen)', 'psource-support' ); ?></label></th><td>
				<input type="number" id="ps-support-retention" name="support[retention_days]" min="1" max="365" required value="<?php echo (int) $settings['retention_days']; ?>">
				<p class="description"><?php esc_html_e( 'Standard: 30 Tage. Muss mindestens so lang sein wie der Browser-Zugang. Fristen gelten für neue Sitzungen. Nachrichten und Kontaktangaben werden durch den stündlichen WP-Cron-Lauf gelöscht; für verlässliche Ausführung einen System-Cron einrichten.', 'psource-support' ); ?></p>
			</td></tr>
		</table>
		<?php submit_button(); ?>
	</form>
	<p><?php esc_html_e( 'Mitarbeiter öffnen „Supportchats“ oder den Link aus der E-Mail und melden sich regulär an. Die explizite Übernahme ist exklusiv; ein E-Mail-Link ist kein Zugangstoken. Fehlgeschlagene E-Mail-Benachrichtigungen sind im Posteingang sichtbar und können erneut versendet werden.', 'psource-support' ); ?></p>
</div>
