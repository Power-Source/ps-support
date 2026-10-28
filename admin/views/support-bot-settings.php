<?php
defined( 'ABSPATH' ) || exit;
$config = is_array( $config ) ? $config : array();
$rows = isset( $config['topics'] ) && is_array( $config['topics'] ) ? $config['topics'] : array();
$field = function( $row, $key ) {
	return isset( $row[ $key ] ) && is_scalar( $row[ $key ] ) ? (string) $row[ $key ] : '';
};
$render_row = function( $row, $index ) use ( $categories, $field ) {
	$row = is_array( $row ) ? $row : array();
	$prefix = 'bot[topics][' . $index . ']';
	?>
	<fieldset class="ps-bot-topic-row">
		<legend><?php esc_html_e( 'Thema', 'psource-support' ); ?></legend>
		<input type="hidden" name="<?php echo esc_attr( $prefix ); ?>[id]" value="<?php echo esc_attr( $field( $row, 'id' ) ); ?>">
		<label><?php esc_html_e( 'Frage / Titel', 'psource-support' ); ?>
			<input required type="text" name="<?php echo esc_attr( $prefix ); ?>[title]" value="<?php echo esc_attr( $field( $row, 'title' ) ); ?>">
		</label>
		<label><?php esc_html_e( 'Antwort (Klartext)', 'psource-support' ); ?>
			<textarea required rows="4" name="<?php echo esc_attr( $prefix ); ?>[answer]"><?php echo esc_textarea( $field( $row, 'answer' ) ); ?></textarea>
		</label>
		<label><?php esc_html_e( 'Kategorie bei Übergabe', 'psource-support' ); ?>
			<select name="<?php echo esc_attr( $prefix ); ?>[category_id]">
				<option value="0"><?php esc_html_e( 'Standard / keine Vorauswahl', 'psource-support' ); ?></option>
				<?php
				$selected_category = $field( $row, 'category_id' );
				if ( '' !== $selected_category && '0' !== $selected_category && ! in_array( (int) $selected_category, array_map( 'intval', wp_list_pluck( $categories, 'cat_id' ) ), true ) ) :
					?>
					<option selected value="<?php echo esc_attr( $selected_category ); ?>"><?php echo esc_html( sprintf( __( 'Ungültige Kategorie (%s) – bitte neu wählen', 'psource-support' ), $selected_category ) ); ?></option>
				<?php endif; ?>
				<?php foreach ( $categories as $category ) : ?>
					<option value="<?php echo esc_attr( $category->cat_id ); ?>" <?php selected( $field( $row, 'category_id' ), $category->cat_id ); ?>><?php echo esc_html( $category->cat_name ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
		<button type="button" class="button ps-bot-remove"><?php esc_html_e( 'Thema entfernen', 'psource-support' ); ?></button>
	</fieldset>
	<?php
};
?>
<div class="wrap ps-bot-settings">
	<h1><?php esc_html_e( 'Support-Bot', 'psource-support' ); ?></h1>
	<p><?php echo esc_html( $scope ? __( 'Diese Einstellungen gelten nur für den eigenen Support dieser Site.', 'psource-support' ) : __( 'Diese Einstellungen gelten für den zentralen Support.', 'psource-support' ) ); ?></p>
	<p><?php esc_html_e( 'Einfache Themenkarten statt Ablaufeditor: Thema wählen, Antwort lesen, als gelöst markieren oder bewusst an den Support übergeben. Antwort-Templates bleiben unverändert. Ohne aktivierten Bot bleiben die bisherigen Formulare unverändert.', 'psource-support' ); ?></p>
	<?php if ( ! $error && isset( $_GET['saved'] ) && '1' === $_GET['saved'] ) : ?>
		<div class="notice notice-success"><p><?php esc_html_e( 'Support-Bot gespeichert.', 'psource-support' ); ?></p></div>
	<?php endif; ?>
	<div class="notice <?php echo $error ? 'notice-error' : 'notice-success'; ?> ps-bot-feedback" role="status" <?php echo $error ? '' : 'hidden'; ?>><p><?php echo esc_html( $error ); ?></p></div>
	<form class="ps-bot-settings-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-ajax-url="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" data-error="<?php esc_attr_e( 'Speichern fehlgeschlagen. Deine Eingaben bleiben erhalten. Bitte erneut versuchen.', 'psource-support' ); ?>" data-empty="<?php esc_attr_e( 'Für einen aktiven Bot ist mindestens ein vollständiges Thema erforderlich.', 'psource-support' ); ?>">
		<input type="hidden" name="action" value="ps_support_bot_save">
		<input type="hidden" name="network" value="<?php echo is_network_admin() ? '1' : '0'; ?>">
		<?php wp_nonce_field( 'ps_support_bot_save' ); ?>
		<p><label><input type="checkbox" name="bot[enabled_chat]" value="1" <?php checked( ! empty( $config['enabled_chat'] ) ); ?>> <?php esc_html_e( 'Im Supportchat aktivieren (benötigt PS Chat)', 'psource-support' ); ?></label></p>
		<p><label><input type="checkbox" name="bot[enabled_tickets]" value="1" <?php checked( ! empty( $config['enabled_tickets'] ) ); ?>> <?php esc_html_e( 'Vor Ticketformularen aktivieren', 'psource-support' ); ?></label></p>
		<label><?php esc_html_e( 'Bot-Name', 'psource-support' ); ?><input required type="text" name="bot[name]" value="<?php echo esc_attr( $field( $config, 'name' ) ); ?>"></label>
		<label><?php esc_html_e( 'Begrüßung', 'psource-support' ); ?><textarea required rows="3" name="bot[greeting]"><?php echo esc_textarea( $field( $config, 'greeting' ) ); ?></textarea></label>
		<h2><?php esc_html_e( 'Themen (maximal 50)', 'psource-support' ); ?></h2>
		<div class="ps-bot-topic-rows"><?php foreach ( $rows as $index => $row ) { $render_row( $row, absint( $index ) ); } ?></div>
		<template class="ps-bot-row-template"><?php $render_row( array(), '__INDEX__' ); ?></template>
		<p><button type="button" class="button ps-bot-add"><?php esc_html_e( 'Thema hinzufügen', 'psource-support' ); ?></button></p>
		<?php submit_button( __( 'Support-Bot speichern', 'psource-support' ) ); ?>
	</form>
</div>
