(function($, window, document, undefined) {
	'use strict';

	var i18n = window.support_system_admin_i18n || {};

	/**
	 * Zeigt eine temporäre Inline-Notice über einem Element an.
	 * type: 'success' | 'error' | 'info'
	 */
	function showNotice( $anchor, message, type, duration ) {
		type = type || 'error';
		duration = duration || 4000;

		var typeClass = 'notice-' + type;
		var $notice = $( '<div class="notice ' + typeClass + ' support-inline-notice is-dismissible"><p></p></div>' );
		$notice.find( 'p' ).text( message );

		var $existing = $anchor.siblings( '.support-inline-notice' );
		if ( $existing.length ) {
			$existing.remove();
		}

		$anchor.before( $notice );

		$notice.on( 'click', '.notice-dismiss', function() {
			$notice.fadeOut( 200, function() { $( this ).remove(); } );
		} );

		if ( duration > 0 ) {
			window.setTimeout( function() {
				$notice.fadeOut( 400, function() { $( this ).remove(); } );
			}, duration );
		}
	}

	function buildAttachmentsOptions() {
		return {
			attachments: {
				container_selector: '.support-attachments',
				button_text: i18n.attachmentButtonText || 'Dateien hinzufügen...',
				button_class: 'button-secondary',
				remove_file_title: i18n.attachmentRemoveTitle || 'Datei löschen',
				remove_link_class: 'button-secondary',
				remove_link_text: i18n.attachmentRemoveText || 'Datei löschen'
			}
		};
	}

	function initSupportSystem() {
		if ( typeof $.fn.support_system !== 'function' ) {
			return;
		}

		if ( $( '.support-attachments' ).length ) {
			$( '.support-system-admin' ).support_system( buildAttachmentsOptions() );
			return;
		}

		if ( $( '.faq-category-wrap' ).length ) {
			$( '.support-system-admin' ).support_system();
		}
	}

	function initDeleteConfirmation() {
		var message = i18n.deleteConfirm || 'Möchtest Du dieses Ticket wirklich löschen?';

		$( document ).on( 'click', 'span.delete > a', function( e ) {
			if ( ! window.confirm( message ) ) {
				e.preventDefault();
				return false;
			}
		} );
	}

	function togglePageSelectorButtons() {
		$( '.support-page-selector-wrap' ).each( function() {
			var container = $( this );
			var selectBox = container.find( 'select' ).first();
			var createButton = container.find( '.support-create-page' );
			var viewButton = container.find( '.support-view-page' );

			createButton.hide();
			viewButton.hide();

			if ( ! selectBox.val() ) {
				createButton.css( 'display', 'inline-block' );
			} else {
				viewButton.css( 'display', 'inline-block' );
			}
		} );
	}

	function initFrontSettings() {
		if ( ! $( '#front-options' ).length ) {
			return;
		}

		togglePageSelectorButtons();

		$( document ).on( 'change', '.support-page-selector-wrap select', togglePageSelectorButtons );

		$( document ).on( 'change', 'input[name="activate_front"]', function() {
			$( '#front-options' ).toggleClass( 'disabled', ! $( this ).is( ':checked' ) );
		} );
	}

	function insertIntoEditor( content ) {
		var editor = window.tinymce && window.tinymce.get( 'message-text' );
		var textarea = $( '#message-text' );

		if ( editor && ! editor.isHidden() ) {
			editor.focus();
			editor.execCommand( 'mceInsertContent', false, content );
			return;
		}

		if ( textarea.length ) {
			var element = textarea.get( 0 );
			var value = textarea.val();
			var start = element.selectionStart || 0;
			var end = element.selectionEnd || 0;
			var nextValue = value.substring( 0, start ) + content + value.substring( end );

			textarea.val( nextValue ).trigger( 'change' );
			element.focus();
			element.selectionStart = start + content.length;
			element.selectionEnd = start + content.length;
		}
	}

	function initReplyTemplates() {
		$( document ).on( 'click', '.support-insert-reply-template', function() {
			var $button = $( this );
			var select = $( '#support-reply-template-select' );
			var templateId = select.val();

			if ( ! templateId ) {
				showNotice( $button.closest( '.support-reply-template-picker' ), i18n.replyTemplateEmpty || 'Bitte zuerst ein Template auswählen.', 'info', 4000 );
				return;
			}

			$button.prop( 'disabled', true );

			$.post( i18n.ajaxurl || window.ajaxurl, {
				action: 'psource_support_get_reply_template',
				nonce: i18n.replyTemplateNonce,
				template_id: templateId
			} ).done( function( response ) {
				if ( ! response || ! response.success || ! response.data ) {
					showNotice( $button.closest( '.support-reply-template-picker' ), i18n.replyTemplateError || 'Template konnte nicht geladen werden.', 'error', 5000 );
					return;
				}

				insertIntoEditor( response.data.content || '' );
				showNotice( $button.closest( '.support-reply-template-picker' ), ( response.data.title || 'Template' ) + ' eingefügt.', 'success', 3000 );
			} ).fail( function() {
				showNotice( $button.closest( '.support-reply-template-picker' ), i18n.replyTemplateError || 'Template konnte nicht geladen werden.', 'error', 5000 );
			} ).always( function() {
				$button.prop( 'disabled', false );
			} );
		} );
	}

	$( function() {
		initSupportSystem();
		initDeleteConfirmation();
		initFrontSettings();
		initReplyTemplates();
	} );
}(jQuery, window, window.document));