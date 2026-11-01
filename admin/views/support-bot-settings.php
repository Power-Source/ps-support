<?php
defined( 'ABSPATH' ) || exit;
$config = is_array( $config ) ? $config : array();
$rows   = isset( $config['topics'] ) && is_array( $config['topics'] ) ? $config['topics'] : array();
$field  = function( $row, $key ) {
	return isset( $row[ $key ] ) && is_scalar( $row[ $key ] ) ? (string) $row[ $key ] : '';
};
$step_types = array(
	'message' => __( 'Nachricht', 'psource-support' ),
	'choice'  => __( 'Auswahl', 'psource-support' ),
	'input'   => __( 'Eingabe', 'psource-support' ),
	'link'    => __( 'Link', 'psource-support' ),
	'handoff' => __( 'An Support übergeben', 'psource-support' ),
	'end'     => __( 'Ablauf beenden', 'psource-support' ),
);
$render_step = function( $step, $topic_index, $step_index, $all_steps ) use ( $field, $step_types ) {
	$step   = is_array( $step ) ? $step : array();
	$prefix = 'bot[topics][' . $topic_index . '][steps][' . $step_index . ']';
	$type   = $field( $step, 'type' );
	$type   = isset( $step_types[ $type ] ) ? $type : 'message';
	?>
	<fieldset class="ps-bot-step" data-step>
		<legend><?php esc_html_e( 'Schritt', 'psource-support' ); ?></legend>
		<label><?php esc_html_e( 'Schritt-ID', 'psource-support' ); ?>
			<input required type="text" class="ps-bot-step-id" name="<?php echo esc_attr( $prefix ); ?>[id]" value="<?php echo esc_attr( $field( $step, 'id' ) ); ?>" placeholder="<?php esc_attr_e( 'z. B. willkommen', 'psource-support' ); ?>">
		</label>
		<label><?php esc_html_e( 'Typ', 'psource-support' ); ?>
			<select class="ps-bot-step-type" name="<?php echo esc_attr( $prefix ); ?>[type]">
				<?php foreach ( $step_types as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $type, $value ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
		<label class="ps-bot-step-field" data-types="message choice input link handoff end"><?php esc_html_e( 'Text', 'psource-support' ); ?>
			<textarea rows="3" name="<?php echo esc_attr( $prefix ); ?>[text]"><?php echo esc_textarea( $field( $step, 'text' ) ); ?></textarea>
		</label>
		<label class="ps-bot-step-field" data-types="input link"><?php esc_html_e( 'Beschriftung', 'psource-support' ); ?>
			<input type="text" name="<?php echo esc_attr( $prefix ); ?>[label]" value="<?php echo esc_attr( $field( $step, 'label' ) ); ?>">
		</label>
		<label class="ps-bot-step-field" data-types="link"><?php esc_html_e( 'URL', 'psource-support' ); ?>
			<input type="url" name="<?php echo esc_attr( $prefix ); ?>[url]" value="<?php echo esc_attr( $field( $step, 'url' ) ); ?>" placeholder="https://">
		</label>
		<label class="ps-bot-step-field" data-types="message choice input link handoff"><?php esc_html_e( 'Nächster Schritt', 'psource-support' ); ?>
			<select class="ps-bot-next-step" name="<?php echo esc_attr( $prefix ); ?>[next_step]" data-selected="<?php echo esc_attr( $field( $step, 'next_step' ) ); ?>"></select>
		</label>
		<div class="ps-bot-step-options ps-bot-step-field" data-types="choice">
			<strong><?php esc_html_e( 'Auswahlmöglichkeiten', 'psource-support' ); ?></strong>
			<div class="ps-bot-option-rows">
				<?php foreach ( isset( $step['options'] ) && is_array( $step['options'] ) ? $step['options'] : array() as $option_index => $option ) : ?>
					<?php $option_prefix = $prefix . '[options][' . absint( $option_index ) . ']'; ?>
					<p class="ps-bot-option">
						<label><?php esc_html_e( 'Beschriftung', 'psource-support' ); ?><input type="text" name="<?php echo esc_attr( $option_prefix ); ?>[label]" value="<?php echo esc_attr( $field( $option, 'label' ) ); ?>"></label>
						<label><?php esc_html_e( 'Nächster Schritt', 'psource-support' ); ?><select class="ps-bot-option-next-step" name="<?php echo esc_attr( $option_prefix ); ?>[next_step]" data-selected="<?php echo esc_attr( $field( $option, 'next_step' ) ); ?>"></select></label>
						<button type="button" class="button-link-delete ps-bot-remove-option"><?php esc_html_e( 'Option entfernen', 'psource-support' ); ?></button>
					</p>
				<?php endforeach; ?>
			</div>
			<button type="button" class="button ps-bot-add-option"><?php esc_html_e( 'Option hinzufügen', 'psource-support' ); ?></button>
		</div>
		<button type="button" class="button-link-delete ps-bot-remove-step"><?php esc_html_e( 'Schritt entfernen', 'psource-support' ); ?></button>
	</fieldset>
	<?php
};
$render_row = function( $row, $index ) use ( $categories, $field, $render_step ) {
	$row = is_array( $row ) ? $row : array();
	$prefix = 'bot[topics][' . $index . ']';
	$steps = isset( $row['steps'] ) && is_array( $row['steps'] ) ? $row['steps'] : array();
	if ( ! $steps && '' !== $field( $row, 'answer' ) ) {
		$steps = array( array( 'id' => 'step-1', 'type' => 'message', 'text' => $field( $row, 'answer' ) ) );
	}
	if ( ! $steps ) {
		$steps = array( array( 'id' => 'step-1', 'type' => 'message' ) );
	}
	$legacy_answer = $field( $row, 'answer' );
	if ( '' === $legacy_answer ) {
		foreach ( $steps as $step ) {
			$legacy_answer = $field( is_array( $step ) ? $step : array(), 'text' );
			if ( '' !== $legacy_answer ) {
				break;
			}
		}
	}
	?>
	<fieldset class="ps-bot-topic-row">
		<legend><?php esc_html_e( 'Thema', 'psource-support' ); ?></legend>
		<input type="hidden" name="<?php echo esc_attr( $prefix ); ?>[id]" value="<?php echo esc_attr( $field( $row, 'id' ) ); ?>">
		<input type="hidden" class="ps-bot-legacy-answer" name="<?php echo esc_attr( $prefix ); ?>[answer]" value="<?php echo esc_attr( $legacy_answer ); ?>">
		<label><?php esc_html_e( 'Frage / Titel', 'psource-support' ); ?>
			<input required type="text" name="<?php echo esc_attr( $prefix ); ?>[title]" value="<?php echo esc_attr( $field( $row, 'title' ) ); ?>">
		</label>
		<label><?php esc_html_e( 'Kategorie bei Übergabe', 'psource-support' ); ?>
			<select name="<?php echo esc_attr( $prefix ); ?>[category_id]">
				<option value="0"><?php esc_html_e( 'Standard / keine Vorauswahl', 'psource-support' ); ?></option>
				<?php foreach ( $categories as $category ) : ?>
					<option value="<?php echo esc_attr( $category->cat_id ); ?>" <?php selected( $field( $row, 'category_id' ), $category->cat_id ); ?>><?php echo esc_html( $category->cat_name ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
		<div class="ps-bot-steps">
			<h3><?php esc_html_e( 'Ablauf', 'psource-support' ); ?></h3>
			<div class="ps-bot-step-rows"><?php foreach ( $steps as $step_index => $step ) { $render_step( $step, $index, absint( $step_index ), $steps ); } ?></div>
			<button type="button" class="button ps-bot-add-step"><?php esc_html_e( 'Schritt hinzufügen', 'psource-support' ); ?></button>
		</div>
		<button type="button" class="button ps-bot-remove"><?php esc_html_e( 'Thema entfernen', 'psource-support' ); ?></button>
	</fieldset>
	<?php
};
?>
<div class="wrap ps-bot-settings">
	<h1><?php esc_html_e( 'Support-Bot', 'psource-support' ); ?></h1>
	<p><?php echo esc_html( $scope ? __( 'Diese Einstellungen gelten nur für den eigenen Support dieser Site.', 'psource-support' ) : __( 'Diese Einstellungen gelten für den zentralen Support.', 'psource-support' ) ); ?></p>
	<?php if ( ! $error && isset( $_GET['saved'] ) && '1' === $_GET['saved'] ) : ?><div class="notice notice-success"><p><?php esc_html_e( 'Support-Bot gespeichert.', 'psource-support' ); ?></p></div><?php endif; ?>
	<div class="notice <?php echo $error ? 'notice-error' : 'notice-success'; ?> ps-bot-feedback" role="status" <?php echo $error ? '' : 'hidden'; ?>><p><?php echo esc_html( $error ); ?></p></div>
	<form class="ps-bot-settings-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-ajax-url="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" data-error="<?php esc_attr_e( 'Speichern fehlgeschlagen. Deine Eingaben bleiben erhalten. Bitte erneut versuchen.', 'psource-support' ); ?>" data-empty="<?php esc_attr_e( 'Für einen aktiven Bot ist mindestens ein vollständiges Thema erforderlich.', 'psource-support' ); ?>" data-end="<?php esc_attr_e( 'Ende', 'psource-support' ); ?>" data-option-label="<?php esc_attr_e( 'Beschriftung', 'psource-support' ); ?>" data-next-label="<?php esc_attr_e( 'Nächster Schritt', 'psource-support' ); ?>" data-remove-option="<?php esc_attr_e( 'Option entfernen', 'psource-support' ); ?>">
		<input type="hidden" name="action" value="ps_support_bot_save"><input type="hidden" name="network" value="<?php echo is_network_admin() ? '1' : '0'; ?>"><?php wp_nonce_field( 'ps_support_bot_save' ); ?>
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
