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
		var step = null;
		var transcript = [];
		var handed = false;
		var subject = form.querySelector('[name="support-system-ticket-subject"], [name="subject"]');
		var message = form.querySelector('[name="support-system-ticket-message"], #message-text');
		var category = form.querySelector('[name="support-system-ticket-category"], [name="category"], [name="ticket-cat"]');

		function editor() {
			return window.tinymce && message ? window.tinymce.get(message.id) : null;
		}
		function steps() {
			if (!selected) { return []; }
			if (Array.isArray(selected.steps) && selected.steps.length) { return selected.steps; }
			return selected.answer ? [{ id: 'legacy-answer', type: 'message', text: selected.answer }] : [];
		}
		function find(id) {
			return steps().find(function (item) { return item.id === id; });
		}
		function button(label, callback) {
			var node = document.createElement('button');
			node.type = 'button';
			node.className = 'button';
			node.textContent = label;
			node.addEventListener('click', callback);
			return node;
		}
		function reset() {
			selected = null;
			step = null;
			transcript = [];
			handed = false;
			answer.replaceChildren();
			status.textContent = '';
			topics.hidden = false;
			solved.hidden = true;
			handoff.hidden = true;
			topics.querySelectorAll('button').forEach(function (item) { item.setAttribute('aria-pressed', 'false'); });
		}
		function append(value) {
			if (value) { transcript.push(value); }
		}
		function terminal() {
			solved.hidden = false;
			handoff.hidden = false;
		}
		function show(next) {
			step = next;
			answer.replaceChildren();
			if (!step) {
				terminal();
				return;
			}
			if (step.text) {
				var text = document.createElement('p');
				text.className = 'ps-bot-answer';
				text.textContent = step.text;
				answer.appendChild(text);
				append(step.text);
			}
			if (step.type === 'choice') {
				(step.options || []).forEach(function (option) {
					answer.appendChild(button(option.label, function () {
						append(option.label);
						show(find(option.next_step));
					}));
				});
				return;
			}
			if (step.type === 'input') {
				var label = document.createElement('label');
				var input = document.createElement('textarea');
				label.textContent = step.label || step.text;
				input.rows = 3;
				input.maxLength = 1000;
				input.required = true;
				answer.append(label, input, button('Weiter', function () {
					if (!input.value.trim()) { input.focus(); return; }
					append((step.label || 'Antwort') + ': ' + input.value.trim());
					show(find(step.next_step));
				}));
				return;
			}
			if (step.type === 'link') {
				var link = document.createElement('a');
				link.href = step.url;
				link.textContent = step.label;
				link.target = '_blank';
				link.rel = 'noopener noreferrer';
				answer.appendChild(link);
				append(step.label + ': ' + step.url);
			}
			if (step.type === 'handoff') {
				handoff.click();
				return;
			}
			if (step.type === 'end') {
				status.textContent = step.text || root.dataset.solved;
				return;
			}
			if (step.next_step) {
				answer.appendChild(button('Weiter', function () { show(find(step.next_step)); }));
			} else {
				terminal();
			}
		}
		function handoffToSupport() {
			if (!selected || !message) { return; }
			if (!handed) {
				var summary = config.name + '\n' + selected.title + '\n' + transcript.join('\n');
				var rich = editor();
				if (rich) { rich.save(); }
				message.value += (message.value ? '\n\n' : '') + summary;
				if (rich) {
					var escaped = document.createElement('div');
					escaped.textContent = summary;
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
				handed = true;
			}
			status.textContent = root.dataset.handoff;
			if (subject) { subject.focus(); }
		}
		(config.topics || []).forEach(function (topic) {
			var item = button(topic.title, function () {
				reset();
				selected = topic;
				append(topic.title);
				item.setAttribute('aria-pressed', 'true');
				show((Array.isArray(topic.steps) && topic.steps.length ? topic.steps : (topic.answer ? [{ id: 'legacy-answer', type: 'message', text: topic.answer }] : []))[0]);
			});
			item.setAttribute('aria-pressed', 'false');
			topics.appendChild(item);
		});
		root.addEventListener('click', function (event) {
			var action = event.target.getAttribute('data-bot-action');
			if (!action) { return; }
			if (action === 'reset') { reset(); return; }
			if (action === 'skip') { reset(); if (subject) { subject.focus(); } return; }
			if (action === 'solved' && selected) {
				status.textContent = root.dataset.solved;
				solved.hidden = true;
				handoff.hidden = true;
				return;
			}
			if (action === 'handoff') { handoffToSupport(); }
		});
		reset();
	});
}());
