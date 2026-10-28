(function () {
	'use strict';
	document.querySelectorAll('.ps-support-bot').forEach(function (root) {
		var config;
		try { config = JSON.parse(root.dataset.config); } catch (error) { return; }
		var form = root.closest('form');
		if (!form) { return; }
		var topics = root.querySelector('.ps-bot-topics');
		var answer = root.querySelector('.ps-bot-answer');
		var status = root.querySelector('.ps-bot-status');
		var solved = root.querySelector('[data-bot-action="solved"]');
		var handoff = root.querySelector('[data-bot-action="handoff"]');
		var selected = null;
		var handed = {};
		var subject = form.querySelector('[name="support-system-ticket-subject"], [name="subject"]');
		var message = form.querySelector('[name="support-system-ticket-message"], #message-text');
		var category = form.querySelector('[name="support-system-ticket-category"], [name="category"], [name="ticket-cat"]');
		function editor() {
			return window.tinymce && message ? window.tinymce.get(message.id) : null;
		}
		function reset() {
			selected = null;
			answer.textContent = '';
			status.textContent = '';
			topics.hidden = false;
			solved.hidden = true;
			handoff.hidden = true;
			topics.querySelectorAll('button').forEach(function (button) { button.setAttribute('aria-pressed', 'false'); });
		}
		config.topics.forEach(function (topic) {
			var button = document.createElement('button');
			button.type = 'button';
			button.className = 'button';
			button.textContent = topic.title;
			button.setAttribute('aria-pressed', 'false');
			button.addEventListener('click', function () {
				reset();
				selected = topic;
				button.setAttribute('aria-pressed', 'true');
				answer.textContent = topic.answer;
				solved.hidden = false;
				handoff.hidden = false;
			});
			topics.appendChild(button);
		});
		root.addEventListener('click', function (event) {
			var action = event.target.getAttribute('data-bot-action');
			if (!action) { return; }
			if (action === 'reset') { reset(); return; }
			if (action === 'skip') {
				reset();
				if (subject) { subject.focus(); }
				return;
			}
			if (action === 'solved' && selected) {
				status.textContent = root.dataset.solved;
				solved.hidden = true;
				handoff.hidden = true;
				return;
			}
			if (action !== 'handoff' || !selected || !message) { return; }
			if (!handed[selected.id]) {
				var transcript = config.name + '\n' + selected.title + '\n' + selected.answer;
				var rich = editor();
				if (rich) { rich.save(); }
				message.value += (message.value ? '\n\n' : '') + transcript;
				if (rich) {
					var escaped = document.createElement('div');
					escaped.textContent = transcript;
					rich.setContent(rich.getContent() + '<p>' + escaped.innerHTML.replace(/\n/g, '<br>') + '</p>');
					rich.save();
				}
				if (subject && !subject.value.trim()) {
					subject.value = subject.maxLength > 0 ? selected.title.slice(0, subject.maxLength) : selected.title;
					subject.dispatchEvent(new Event('input', { bubbles: true }));
				}
				if (category && selected.category_id && Array.prototype.some.call(category.options, function (option) {
					return option.value === String(selected.category_id);
				})) {
					category.value = String(selected.category_id);
					category.dispatchEvent(new Event('change', { bubbles: true }));
				}
				message.dispatchEvent(new Event('input', { bubbles: true }));
				handed[selected.id] = true;
			}
			status.textContent = root.dataset.handoff;
			if (subject) { subject.focus(); }
		});
		reset();
	});
}());
