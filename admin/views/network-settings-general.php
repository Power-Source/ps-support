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
				<input type="number" min="0" step="1" class="small-text" name="crm_sync_blog_id" value="<?php echo esc_attr( absint( $crm_sync_blog_id ) ); ?>">
				<span class="description">
					<?php _e( 'Optionaler Override: Site-ID mit aktivem CRM für Ticket/CRM-Synchronisation. 0 = automatisch (Support-Site, sonst Main-Site).', 'psource-support' ); ?>
				</span>
			<?php $this->render_row( __( 'CRM Sync-Site ID', 'psource-support' ), ob_get_clean() ); ?>
		<?php endif; ?>
	</table>

	<?php do_action( 'support_sytem_general_settings' ); ?>

		
	<?php $this->render_submit_block(); ?>
</form>