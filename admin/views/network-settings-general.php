<?php if ( $errors ): ?>
	<?php foreach ( $errors as $error ): ?>
		<div class="error">
			<p><?php echo esc_html( $error['message'] ); ?></p>
		</div>
	<?php endforeach; ?>
<?php endif; ?>

<form method="post" action="">
	<table class="form-table">
		<?php
			ob_start();
		    ?>
				<input type="text" class="regular-text" name="menu_name" value="<?php echo esc_attr( $menu_name ); ?>">
				<span class="description"><?php _e("Ändere den Text des Menüelements <strong>Support</strong> nach Bedarf.", 'psource-support'); ?></span>
		    <?php
		    $this->render_row( __( 'Name des Support-Menüs', 'psource-support' ), ob_get_clean() );

			if ( is_multisite() ) {
				ob_start();
				?>
					<input type="text" class="regular-text" name="network_faq_name" value="<?php echo esc_attr( $network_faq_name ); ?>">
					<span class="description"><?php _e( 'Benennt nur den zentralen FAQ-Bereich im Netzwerkadmin. Subsite-FAQs und vorhandene Inhalte bleiben unverändert.', 'psource-support' ); ?></span>
				<?php
				$this->render_row( __( 'Name des Netzwerk-FAQs', 'psource-support' ), ob_get_clean() );
			}

		    ob_start();
		    ?>
				<input type="text" class="regular-text" name="from_name" value="<?php echo esc_attr( $from_name ); ?>">
				<span class="description"><?php _e("Support Mail von Namen.", 'psource-support'); ?></span>
		    <?php
		    $this->render_row( __( 'Support von Namen', 'psource-support' ), ob_get_clean() );

		    ob_start();
		    ?>
				<input type="text" class="regular-text" name="from_mail" value="<?php echo esc_attr( $from_email ); ?>">
				<span class="description"><?php _e("Support-Mail von Adresse.", 'psource-support'); ?></span>
		    <?php
		    $this->render_row( __( 'Support von E-Mail', 'psource-support' ), ob_get_clean() );

		    ob_start(); 
		    ?>
		    	<?php echo $staff_dropdown; ?>
		    	<span class="description"> <?php _e( 'Wenn das Ticket keinem Mitarbeiter zugewiesen ist, ist dies der Administrator, der alle E-Mails mit Aktualisierungen des Tickets erhält', 'psource-support' ); ?></span>
		    <?php $this->render_row( __( 'Hauptadministrator', 'psource-support' ), ob_get_clean() ); ?>
	</table>

	<h3><?php _e( 'Berechtigungseinstellungen', 'psource-support' ); ?></h3>
	<table class="form-table">
	    
	    <?php ob_start(); ?>
		
	    	<?php foreach ( $roles as $key => $value ): if( $key == 'support-guest' ) continue;	?>
	    		<label for="tickets_role_<?php echo $key; ?>">						    		
    				<input type="checkbox" value="<?php echo $key; ?>" id="tickets_role_<?php echo $key; ?>" name="tickets_role[]" <?php checked( in_array( $key, $tickets_role ) ); ?> /> <?php echo $value; ?><br/>
	    		</label>
	    	<?php endforeach; ?>

	    <?php $this->render_row( __( 'Benutzerrollen, die Tickets öffnen/anzeigen können.', 'psource-support' ), ob_get_clean() );

	    	ob_start();
	    ?>
	    	<?php foreach ( $roles as $key => $value ): ?>
	    		<label for="faqs_role_<?php echo $key; ?>">
    				<input type="checkbox" value="<?php echo $key; ?>" id="faqs_role_<?php echo $key; ?>" name="faqs_role[]" <?php checked( in_array( $key, $faqs_role ) ); ?> /> <?php echo $value; ?><br/>
	    		</label>
	    	<?php endforeach; ?>

	    <?php $this->render_row( __( 'Benutzerrollen, die die FAQs anzeigen können<span class="description">(Deaktiviere alle, um diese Funktion zu deaktivieren)</span>', 'psource-support' ), ob_get_clean() ); ?>

	</table>

	<h3><?php _e( 'Mitarbeiter-Berechtigungen', 'psource-support' ); ?></h3>
	<p class="description"><?php _e( 'Lege pro Aktion fest, welche Rollen als Staff gelten und die Aktion ausführen dürfen. Super-Admins können immer alles.', 'psource-support' ); ?></p>
	<table class="form-table">

		<?php ob_start(); ?>
			<?php foreach ( $roles as $key => $value ): if( $key == 'support-guest' ) continue; ?>
				<label for="staff_role_<?php echo $key; ?>">
					<input type="checkbox" value="<?php echo $key; ?>" id="staff_role_<?php echo $key; ?>" name="staff_roles[]" <?php checked( in_array( $key, $staff_roles ) ); ?> /> <?php echo $value; ?><br/>
				</label>
			<?php endforeach; ?>
		<?php $this->render_row( __( 'Mitarbeiter-Rollen (Antworten, Zuweisen, Bearbeiten, Labels)', 'psource-support' ), ob_get_clean() ); ?>

		<?php ob_start(); ?>
			<?php foreach ( $roles as $key => $value ): if( $key == 'support-guest' ) continue; ?>
				<label for="close_ticket_role_<?php echo $key; ?>">
					<input type="checkbox" value="<?php echo $key; ?>" id="close_ticket_role_<?php echo $key; ?>" name="close_ticket_roles[]" <?php checked( in_array( $key, $close_ticket_roles ) ); ?> /> <?php echo $value; ?><br/>
				</label>
			<?php endforeach; ?>
		<?php $this->render_row( __( 'Rollen, die Tickets schließen/öffnen dürfen', 'psource-support' ), ob_get_clean() ); ?>

		<?php ob_start(); ?>
			<?php foreach ( $roles as $key => $value ): if( $key == 'support-guest' ) continue; ?>
				<label for="delete_ticket_role_<?php echo $key; ?>">
					<input type="checkbox" value="<?php echo $key; ?>" id="delete_ticket_role_<?php echo $key; ?>" name="delete_ticket_roles[]" <?php checked( in_array( $key, $delete_ticket_roles ) ); ?> /> <?php echo $value; ?><br/>
				</label>
			<?php endforeach; ?>
		<?php $this->render_row( __( 'Rollen, die Tickets löschen dürfen', 'psource-support' ), ob_get_clean() ); ?>

	</table>


	<h3><?php _e( 'Privatsphäreeinstellungen', 'psource-support' ); ?></h3>
	<table class="form-table">
		<?php ob_start(); ?>
	    	<select name="privacy" id="privacy">
	    		<?php foreach ( MU_Support_System::$privacy as $key => $value ): ?>
	    			<option value="<?php echo $key; ?>" <?php selected( $ticket_privacy, $key ); ?>><?php echo $value; ?></option>
	    		<?php endforeach; ?>
	    	</select>
	    <?php $this->render_row( __( 'Privatsphäre', 'psource-support' ), ob_get_clean() ); ?>

		<?php if ( is_multisite() ) : ?>
			<?php ob_start(); ?>
				<label>
					<input type="checkbox" name="allow_subsite_support" value="1" <?php checked( $allow_subsite_support ); ?>>
					<?php _e( 'Eigenständige Ticket- und FAQ-Systeme auf Subsites erlauben', 'psource-support' ); ?>
				</label>
				<span class="description"><?php _e( 'Die Administratoren richten ihren Support direkt auf der jeweiligen Subsite ein. Ist dies deaktiviert, bleiben nur durch Integrationen wie MarketPress erzwungene Shop-Funktionen verfügbar.', 'psource-support' ); ?></span>
			<?php $this->render_row( __( 'Subsite-Support', 'psource-support' ), ob_get_clean() ); ?>

			<?php ob_start(); ?>
				<label>
					<input type="checkbox" id="crm-sync-enabled" name="crm_sync_enabled" value="1" <?php checked( $crm_sync_enabled ); ?>>
					<?php _e( 'CRM-Synchronisation aktivieren', 'psource-support' ); ?>
				</label>
			<?php $this->render_row( __( 'CRM Sync', 'psource-support' ), ob_get_clean() ); ?>

			<?php ob_start(); ?>
				<?php $selected_crm_site = isset( $crm_sync_sites[ $crm_sync_blog_id ] ) ? $crm_sync_sites[ $crm_sync_blog_id ] : ''; ?>
				<input type="search" id="crm-sync-site-search" class="regular-text" list="crm-sync-sites" autocomplete="off" value="<?php echo esc_attr( $selected_crm_site ); ?>" placeholder="<?php esc_attr_e( 'Website suchen …', 'psource-support' ); ?>">
				<input type="hidden" id="crm-sync-blog-id" name="crm_sync_blog_id" value="<?php echo esc_attr( $crm_sync_blog_id ); ?>">
				<datalist id="crm-sync-sites">
					<?php foreach ( $crm_sync_sites as $site_id => $site_label ) : ?>
						<option value="<?php echo esc_attr( $site_label ); ?>" data-blog-id="<?php echo esc_attr( $site_id ); ?>"></option>
					<?php endforeach; ?>
				</datalist>
				<p class="description"><?php _e( 'Vorausgewählt ist der in der Netzwerkkonfiguration festgelegte Hauptblog. Die gewählte Website muss ein aktives CRM enthalten.', 'psource-support' ); ?></p>
				<script>
					document.addEventListener('DOMContentLoaded', function () {
						var enabled = document.getElementById('crm-sync-enabled');
						var search = document.getElementById('crm-sync-site-search');
						var blogId = document.getElementById('crm-sync-blog-id');
						var options = Array.prototype.slice.call(document.querySelectorAll('#crm-sync-sites option'));
						function updateState() {
							search.disabled = !enabled.checked;
						}
						search.addEventListener('input', function () {
							var match = options.find(function (option) { return option.value === search.value; });
							blogId.value = match ? match.dataset.blogId : '';
						});
						enabled.addEventListener('change', updateState);
						updateState();
					});
				</script>
			<?php $this->render_row( __( 'CRM-Site', 'psource-support' ), ob_get_clean() ); ?>
		<?php endif; ?>
	</table>

	<?php if ( is_multisite() ) : ?>
		<h3><?php esc_html_e( 'Netzwerk-FAQ im Dashboard', 'psource-support' ); ?></h3>
		<table class="form-table">
			<?php ob_start(); ?>
				<label><input type="checkbox" name="network_faq_dashboard_enabled" value="1" <?php checked( $network_faq_dashboard_enabled ); ?>> <?php esc_html_e( 'Auf den Site-Dashboards anzeigen', 'psource-support' ); ?></label>
			<?php $this->render_row( __( 'Dashboard-Box', 'psource-support' ), ob_get_clean() ); ?>

			<?php ob_start(); ?>
				<select name="network_faq_dashboard_mode">
					<option value="latest" <?php selected( $network_faq_dashboard_mode, 'latest' ); ?>><?php esc_html_e( 'Neueste Netzwerk-FAQs', 'psource-support' ); ?></option>
					<option value="sticky" <?php selected( $network_faq_dashboard_mode, 'sticky' ); ?>><?php esc_html_e( 'Angeheftete Netzwerk-FAQs', 'psource-support' ); ?></option>
				</select>
			<?php $this->render_row( __( 'Inhalt', 'psource-support' ), ob_get_clean() ); ?>

			<?php ob_start(); ?>
				<input type="number" name="network_faq_dashboard_count" min="1" max="10" value="<?php echo esc_attr( $network_faq_dashboard_count ); ?>">
			<?php $this->render_row( __( 'Anzahl', 'psource-support' ), ob_get_clean() ); ?>

			<?php ob_start(); ?>
				<select name="network_faq_dashboard_sticky_ids[]" multiple size="6">
					<?php foreach ( $network_faqs as $faq ) : ?>
						<option value="<?php echo esc_attr( $faq->faq_id ); ?>" <?php selected( in_array( (int) $faq->faq_id, $network_faq_dashboard_sticky_ids, true ) ); ?>><?php echo esc_html( $faq->question ); ?></option>
					<?php endforeach; ?>
				</select>
				<p class="description"><?php esc_html_e( 'Wird nur im Modus „Angeheftete Netzwerk-FAQs“ verwendet.', 'psource-support' ); ?></p>
			<?php $this->render_row( __( 'Angeheftete FAQs', 'psource-support' ), ob_get_clean() ); ?>
		</table>
	<?php endif; ?>

	<?php do_action( 'support_sytem_general_settings' ); ?>

		
	<?php $this->render_submit_block(); ?>
</form>