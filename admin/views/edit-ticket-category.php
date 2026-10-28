<?php if ( $updated ): ?>
	<div class="updated"><p><?php _e( 'Kategorie aktualisiert', 'psource-support' ); ?></p></div>
<?php endif; ?>

<form id="categories-table-form" action="" method="post">
	<table class="form-table">
		<?php ob_start(); ?>
			<input type="text" name="cat_name" value="<?php echo esc_attr( $category_name ); ?>">
		<?php $this->render_row( __( 'Kategoriename', 'psource-support' ), ob_get_clean() ); ?>
		<?php $this->render_row( __( 'Bearbeiter / Team', 'psource-support' ), $super_admins_dropdown . '<p class="description">' . esc_html__( 'Eine Person: automatische Zuweisung. Mehrere Personen: Team benachrichtigen, Ticket bleibt bis zur Übernahme unzugewiesen.', 'psource-support' ) . '</p>' ); ?>
	</table>
	<input type="hidden" name="ticket_cat_id" value="<?php echo esc_attr( $ticket_category->cat_id ); ?>">
	<input type="hidden" name="category_assignees_present" value="1">
	<?php wp_nonce_field( 'edit-ticket-category-' . $ticket_category->cat_id, '_wpnonce' ); ?>
	<?php submit_button( null, 'primary', 'submit-edit-ticket-category' ); ?>
</form>