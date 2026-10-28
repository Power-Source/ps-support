(function () {
	'use strict';
	document.querySelectorAll('.ps-bot-settings-form').forEach(function (form) {
		var rows = form.querySelector('.ps-bot-topic-rows');
		var feedback = form.parentNode.querySelector('.ps-bot-feedback');
		var add = form.querySelector('.ps-bot-add');
		var next = rows.children.length;
		function show(message, success) {
			feedback.hidden = false;
			feedback.classList.toggle('notice-success', success);
			feedback.classList.toggle('notice-error', !success);
			feedback.querySelector('p').textContent = message;
		}
		function reindex() {
			Array.prototype.forEach.call(rows.children, function (row, index) {
				row.querySelectorAll('[name]').forEach(function (input) {
					input.name = input.name.replace(/bot\[topics\]\[[^\]]+\]/, 'bot[topics][' + index + ']');
				});
			});
			next = rows.children.length;
			add.disabled = next >= 50;
		}
		form.addEventListener('invalid', function (event) {
			show(event.target.validationMessage, false);
		}, true);
		add.addEventListener('click', function () {
			if (next >= 50) { return; }
			var template = form.querySelector('.ps-bot-row-template');
			var holder = document.createElement('div');
			holder.innerHTML = template.innerHTML.replace(/__INDEX__/g, String(next++));
			rows.appendChild(holder.firstElementChild);
			reindex();
			rows.lastElementChild.querySelector('input[type="text"]').focus();
		});
		rows.addEventListener('click', function (event) {
			if (event.target.classList.contains('ps-bot-remove')) {
				event.target.closest('.ps-bot-topic-row').remove();
				reindex();
			}
		});
		form.addEventListener('submit', function (event) {
			var enabled = form.querySelector('[name="bot[enabled_chat]"]').checked ||
				form.querySelector('[name="bot[enabled_tickets]"]').checked;
			if (enabled && !rows.children.length) {
				event.preventDefault();
				show(form.dataset.empty, false);
				add.focus();
				return;
			}
			if (!window.fetch || !window.FormData) { return; }
			event.preventDefault();
			var submit = form.querySelector('[type="submit"]');
			submit.disabled = true;
			fetch(form.dataset.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: new FormData(form) })
				.then(function (response) {
					if (!response.ok) { throw new Error('request'); }
					return response.json();
				})
				.then(function (result) {
					show(result.data && result.data.message ? result.data.message : form.dataset.error, !!result.success);
				})
				.catch(function () { show(form.dataset.error, false); })
				.then(function () { submit.disabled = false; });
		});
		reindex();
	});
}());
