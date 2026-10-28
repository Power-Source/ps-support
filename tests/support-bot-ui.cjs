/* Run: node tests/support-bot-ui.cjs (uses an existing jsdom installation). */
'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { JSDOM } = require('jsdom');
const script = (file) => fs.readFileSync(path.join(__dirname, '..', file), 'utf8');
const config = {
	enabled_chat: true, enabled_tickets: true, name: 'Helper', greeting: 'Hello',
	topics: [{ id: 'one', title: 'Billing <img src=x>', answer: 'First line\nSecond line', category_id: 7 }]
};
let assertions = 0;
const check = (condition, message) => { assert.ok(condition, message); assertions++; };
function ticket(admin, richEditor) {
	const subject = admin ? 'subject' : 'support-system-ticket-subject';
	const category = admin ? 'category' : 'support-system-ticket-category';
	const message = admin ? 'message-text' : 'support-system-ticket-message';
	const dom = new JSDOM(`<form>
		<section class="ps-support-bot" data-solved="Solved without ticket" data-handoff="Prepared, not sent">
			<div class="ps-bot-topics"></div><div class="ps-bot-answer"></div>
			<button type="button" data-bot-action="solved">Solved</button>
			<button type="button" data-bot-action="handoff">Handoff</button>
			<button type="button" data-bot-action="reset">Reset</button>
			<button type="button" data-bot-action="skip">Skip</button>
			<p class="ps-bot-status"></p>
		</section>
		<input name="${subject}"><select name="${category}"><option value="0">Default</option><option value="7">Billing</option></select>
		<textarea id="${message}" name="${message}"></textarea><button type="submit">Send</button>
		</form>`, { runScripts: 'outside-only' });
	const { window } = dom;
	const document = window.document;
	const form = document.querySelector('form');
	const root = document.querySelector('.ps-support-bot');
	root.dataset.config = JSON.stringify(config);
	const title = document.querySelector(`input[name="${subject}"]`);
	const body = document.getElementById(message);
	const cat = document.querySelector('select');
	let submissions = 0;
	form.addEventListener('submit', (event) => { event.preventDefault(); submissions++; });
	if (richEditor) {
		let content = '<p>My draft</p>';
		window.tinymce = { get: () => ({
			save: () => { body.value = content; },
			getContent: () => content,
			setContent: (value) => { content = value; }
		}) };
	} else { body.value = 'My draft'; }
	window.eval(script('assets/js/support-bot.js'));
	const click = (action) => root.querySelector(`[data-bot-action="${action}"]`).click();
	check(root.querySelector('[data-bot-action="handoff"]').hidden, 'Handoff hidden until topic selected');
	const before = body.value;
	root.querySelector('.ps-bot-topics button').click();
	check(body.value === before && title.value === '' && cat.value === '0', 'Topic selection never changes ticket fields');
	check(root.querySelector('.ps-bot-answer').textContent === config.topics[0].answer, 'Answer is shown verbatim');
	check(!root.querySelector('img'), 'User strings cannot create HTML');
	click('solved');
	check(body.value === before && root.querySelector('.ps-bot-status').textContent === 'Solved without ticket', 'Solved creates no draft or ticket');
	click('reset');
	click('skip');
	check(body.value === before && document.activeElement === title, 'Skip reaches unchanged default form');
	root.querySelector('.ps-bot-topics button').click();
	click('handoff');
	check(title.value === config.topics[0].title && cat.value === '7', 'Explicit handoff fills title and category');
	check(body.value.includes('My draft') && body.value.includes('Second line'), 'Handoff preserves existing message and adds transcript');
	const handed = body.value;
	click('handoff');
	check(body.value === handed, 'Repeated handoff does not duplicate transcript');
	click('reset');
	title.value = 'Edited title';
	root.querySelector('.ps-bot-topics button').click();
	click('handoff');
	check(title.value === 'Edited title', 'User edits survive repeated handoff');
	check(submissions === 0, 'Bot actions never submit the ticket');
	check(!form.hidden && !title.hidden && !body.hidden, 'Default form remains reachable');
	dom.window.close();
}

async function settings() {
	const dom = new JSDOM(`<div><div class="ps-bot-feedback" hidden><p></p></div>
	<form class="ps-bot-settings-form" data-empty="Add a topic" data-error="Keep draft" data-ajax-url="/save">
	<input name="bot[enabled_chat]" type="checkbox" checked><input name="bot[enabled_tickets]" type="checkbox">
	<input name="bot[name]" required value="Helper"><textarea name="bot[greeting]">Hello</textarea>
	<div class="ps-bot-topic-rows"></div>
	<template class="ps-bot-row-template"><fieldset class="ps-bot-topic-row">
		<input required type="text" name="bot[topics][__INDEX__][title]">
		<textarea required name="bot[topics][__INDEX__][answer]"></textarea>
		<button type="button" class="ps-bot-remove">Remove</button>
	</fieldset></template><button type="button" class="ps-bot-add">Add</button><button type="submit">Save</button>
	</form></div>`, { runScripts: 'outside-only', url: 'https://example.invalid' });
	const { window } = dom;
	let requests = 0;
	window.fetch = async () => { requests++; return { ok: true, json: async () => ({ success: false, data: { message: 'Invalid category' } }) }; };
	window.eval(script('admin/assets/js/support-bot-settings.js'));
	const form = window.document.querySelector('form');
	const submit = () => form.dispatchEvent(new window.Event('submit', { bubbles: true, cancelable: true }));
	submit();
	check(requests === 0 && window.document.querySelector('.ps-bot-feedback p').textContent === 'Add a topic', 'Missing topics rejected inline before request');
	form.querySelector('.ps-bot-add').click();
	const title = form.querySelector('.ps-bot-topic-row input');
	const answer = form.querySelector('.ps-bot-topic-row textarea');
	title.value = 'My topic';
	answer.value = 'My answer';
	submit();
	await new Promise((resolve) => setImmediate(resolve));
	check(requests === 1 && window.document.querySelector('.ps-bot-feedback p').textContent === 'Invalid category', 'Server errors are shown inline');
	check(title.value === 'My topic' && answer.value === 'My answer', 'Server validation preserves every draft field');
	check(!form.querySelector('[type="submit"]').disabled, 'Save recovers after validation failure');
	window.fetch = async () => { throw new Error('offline'); };
	submit();
	await new Promise((resolve) => setImmediate(resolve));
	check(window.document.querySelector('.ps-bot-feedback p').textContent === 'Keep draft' && title.value === 'My topic', 'Network errors keep editable draft inline');
	form.querySelector('.ps-bot-add').click();
	form.querySelector('.ps-bot-remove').click();
	check(form.querySelector('.ps-bot-topic-row input').name === 'bot[topics][0][title]', 'Removing rows reindexes fields safely');
	dom.window.close();
}
(async () => {
	ticket(false, false);
	ticket(true, false);
	ticket(true, true);
	await settings();
	console.log(`Support bot UI: ${assertions} assertions passed.`);
})().catch((error) => { console.error(error); process.exitCode = 1; });
