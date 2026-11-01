(function () {
	'use strict';

	document.querySelectorAll('.ps-bot-settings-form').forEach(function (form) {
		var rows = form.querySelector('.ps-bot-topic-rows');
		var feedback = form.parentNode.querySelector('.ps-bot-feedback');
		var add = form.querySelector('.ps-bot-add');

		function show(message, success) {
			feedback.hidden = false;
			feedback.classList.toggle('notice-success', success);
			feedback.classList.toggle('notice-error', !success);
			feedback.querySelector('p').textContent = message;
		}

		function updateNextSteps(row) {
			var steps = Array.prototype.slice.call(row.querySelectorAll('.ps-bot-step'));
			var ids = steps.map(function (step, index) {
				var input = step.querySelector('.ps-bot-step-id');
				return { id: input.value.trim(), fallback: 'step-' + (index + 1) };
			}).filter(function (step) {
				return step.id;
			});

			row.querySelectorAll('.ps-bot-next-step, .ps-bot-option-next-step').forEach(function (select) {
				var selected = select.value || select.dataset.selected || '';
				select.replaceChildren();
				var empty = document.createElement('option');
				empty.value = '';
				empty.textContent = '— ' + form.dataset.end + ' —';
				select.appendChild(empty);
				ids.forEach(function (step) {
					var option = document.createElement('option');
					option.value = step.id;
					option.textContent = step.id;
					option.selected = step.id === selected;
					select.appendChild(option);
				});
				delete select.dataset.selected;
			});
		}

		function updateStepFields(step) {
			var type = step.querySelector('.ps-bot-step-type').value;
			step.querySelectorAll('.ps-bot-step-field').forEach(function (field) {
				field.hidden = field.dataset.types.split(' ').indexOf(type) === -1;
			});
		}

		function syncLegacyAnswer(row) {
			var legacy = row.querySelector('.ps-bot-legacy-answer');
			if (!legacy) {
				return;
			}
			var steps = Array.prototype.slice.call(row.querySelectorAll('.ps-bot-step'));
			var message = steps.find(function (step) {
				return step.querySelector('.ps-bot-step-type').value === 'message';
			}) || steps[0];
			legacy.value = message ? message.querySelector('textarea').value : '';
		}

		function reindex() {
			Array.prototype.forEach.call(rows.children, function (row, topicIndex) {
				row.querySelectorAll('[name]').forEach(function (input) {
					input.name = input.name.replace(/bot\[topics\]\[[^\]]+\]/, 'bot[topics][' + topicIndex + ']');
				});
				row.querySelectorAll('.ps-bot-step').forEach(function (step, stepIndex) {
					step.querySelectorAll('[name]').forEach(function (input) {
						input.name = input.name.replace(/bot\[topics\]\[[^\]]+\]\[steps\]\[[^\]]+\]/, 'bot[topics][' + topicIndex + '][steps][' + stepIndex + ']');
					});
					step.querySelectorAll('.ps-bot-option').forEach(function (option, optionIndex) {
						option.querySelectorAll('[name]').forEach(function (input) {
							input.name = input.name.replace(/\[options\]\[[^\]]+\]/, '[options][' + optionIndex + ']');
						});
					});
					updateStepFields(step);
				});
				updateNextSteps(row);
				syncLegacyAnswer(row);
			});
			add.disabled = rows.children.length >= 50;
		}

		function addStep(row) {
			var steps = row.querySelector('.ps-bot-step-rows');
			var first = steps.querySelector('.ps-bot-step');
			var step = first.cloneNode(true);
			step.querySelectorAll('input, textarea').forEach(function (input) {
				if (input.type !== 'hidden') {
					input.value = '';
				}
			});
			step.querySelector('.ps-bot-step-id').value = 'step-' + (steps.children.length + 1);
			step.querySelector('.ps-bot-step-type').value = 'message';
			step.querySelector('.ps-bot-option-rows').replaceChildren();
			steps.appendChild(step);
			reindex();
			step.querySelector('.ps-bot-step-id').focus();
		}

		function addOption(step) {
			var options = step.querySelector('.ps-bot-option-rows');
			var name = step.querySelector('.ps-bot-step-id').name.replace(/\[id\]$/, '[options][0]');
			var option = document.createElement('p');
			option.className = 'ps-bot-option';
			var labelLabel = document.createElement('label');
			labelLabel.textContent = form.dataset.optionLabel;
			var labelInput = document.createElement('input');
			labelInput.type = 'text';
			labelInput.name = name + '[label]';
			labelLabel.appendChild(labelInput);
			var nextLabel = document.createElement('label');
			nextLabel.textContent = form.dataset.nextLabel;
			var nextSelect = document.createElement('select');
			nextSelect.className = 'ps-bot-option-next-step';
			nextSelect.name = name + '[next_step]';
			nextLabel.appendChild(nextSelect);
			var remove = document.createElement('button');
			remove.type = 'button';
			remove.className = 'button-link-delete ps-bot-remove-option';
			remove.textContent = form.dataset.removeOption;
			option.append(labelLabel, nextLabel, remove);
			options.appendChild(option);
			reindex();
			labelInput.focus();
		}

		form.addEventListener('invalid', function (event) {
			show(event.target.validationMessage, false);
		}, true);

		add.addEventListener('click', function () {
			if (rows.children.length >= 50) {
				return;
			}
			var template = form.querySelector('.ps-bot-row-template');
			var fragment = template.content.cloneNode(true);
			rows.appendChild(fragment);
			reindex();
			rows.lastElementChild.querySelector('input[type="text"]').focus();
		});

		rows.addEventListener('input', function (event) {
			if (event.target.matches('.ps-bot-step-id, .ps-bot-step textarea')) {
				reindex();
			}
		});

		rows.addEventListener('change', function (event) {
			if (event.target.matches('.ps-bot-step-type')) {
				updateStepFields(event.target.closest('.ps-bot-step'));
				syncLegacyAnswer(event.target.closest('.ps-bot-topic-row'));
			}
		});

		rows.addEventListener('click', function (event) {
			var target = event.target;
			if (target.classList.contains('ps-bot-remove')) {
				target.closest('.ps-bot-topic-row').remove();
				reindex();
			} else if (target.classList.contains('ps-bot-add-step')) {
				addStep(target.closest('.ps-bot-topic-row'));
			} else if (target.classList.contains('ps-bot-remove-step')) {
				target.closest('.ps-bot-step').remove();
				reindex();
			} else if (target.classList.contains('ps-bot-add-option')) {
				addOption(target.closest('.ps-bot-step'));
			} else if (target.classList.contains('ps-bot-remove-option')) {
				target.closest('.ps-bot-option').remove();
				reindex();
			}
		});

		form.addEventListener('submit', function (event) {
			var enabled = form.querySelector('[name="bot[enabled_chat]"]').checked ||
				form.querySelector('[name="bot[enabled_tickets]"]').checked;
			reindex();
			if (enabled && !rows.children.length) {
				event.preventDefault();
				show(form.dataset.empty, false);
				add.focus();
				return;
			}
			if (!window.fetch || !window.FormData) {
				return;
			}
			event.preventDefault();
			var submit = form.querySelector('[type="submit"]');
			submit.disabled = true;
			fetch(form.dataset.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: new FormData(form) })
				.then(function (response) {
					if (!response.ok) {
						throw new Error('request');
					}
					return response.json();
				})
				.then(function (result) {
					show(result.data && result.data.message ? result.data.message : form.dataset.error, !!result.success);
				})
				.catch(function () {
					show(form.dataset.error, false);
				})
				.then(function () {
					submit.disabled = false;
				});
		});

		reindex();
	});
}());
