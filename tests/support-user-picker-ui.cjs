'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { JSDOM } = require('jsdom');
const code = fs.readFileSync(path.join(__dirname, '../admin/assets/js/support-user-picker.js'), 'utf8');

async function run() {
    const dom = new JSDOM(`<form><div class="ps-support-user-picker" data-name="assignee_user_ids[]" data-kind="category">
        <ul class="ps-support-picked"></ul>
        <input id="people" class="ps-support-person-search" aria-expanded="false">
        <ul class="ps-support-person-results" hidden></ul><p class="ps-support-picker-status"></p>
        </div></form>`, { runScripts: 'outside-only', url: 'http://example.test/' });
    const { window } = dom;
    const document = window.document;
    window.HTMLElement.prototype.scrollIntoView = function () {};
    window.psSupportPicker = { url: 'http://example.test/ajax', nonce: 'nonce', network: '1',
        empty: 'No matches', hint: 'Type two letters', more: 'Narrow search', error: 'Search failed', remove: 'Remove:' };
    const all = Array.from({ length: 400 }, (_, i) => ({ id: i + 1, label: 'Person ' + (i + 1) }));
    let calls = 0;
    let failure = false;
    window.fetch = async function (url) {
        calls++;
        if (failure) throw new Error('offline');
        const params = new URL(url).searchParams;
        const exclude = params.get('exclude').split(',');
        const matches = all.filter(user => user.label.includes(params.get('q')) && !exclude.includes(String(user.id)));
        return { ok: true, json: async () => ({ success: true, data: { users: matches.slice(0, 20), more: matches.length > 20 } }) };
    };
    window.eval(code);
    const search = document.querySelector('input');
    const results = document.querySelector('.ps-support-person-results');
    const picked = document.querySelector('.ps-support-picked');
    const status = document.querySelector('.ps-support-picker-status');
    const wait = () => new Promise(resolve => setTimeout(resolve, 290));
    const input = async query => {
        search.value = query;
        search.dispatchEvent(new window.Event('input'));
        await wait();
    };
    const key = value => search.dispatchEvent(new window.KeyboardEvent('keydown', { key: value, bubbles: true, cancelable: true }));
    assert.equal(calls, 0, 'No full user list or request on initialization');
    await input('P');
    assert.equal(calls, 0, 'One character never searches');
    await input('Person');
    assert.equal(results.children.length, 20, '400 users produce only 20 suggestions');
    assert.equal(picked.children.length, 0, 'Suggestions are not selected');
    assert.equal(status.textContent, 'Narrow search');
    key('ArrowDown');
    assert.equal(search.getAttribute('aria-activedescendant'), 'people-option-0');
    key('Enter');
    assert.equal(picked.children.length, 1);
    assert.equal(picked.querySelector('input').value, '1');
    assert.equal(search.value, '');
    assert.equal(results.hidden, true);
    await input('Person');
    assert.equal(results.children.length, 20, 'Selected people excluded before limiting results');
    key('ArrowUp');
    assert.equal(search.getAttribute('aria-activedescendant'), 'people-option-19');
    key('Escape');
    assert.equal(results.hidden, true);
    await input('Person 2');
    results.firstElementChild.click();
    assert.equal(picked.children.length, 2, 'Click adds second person');
    assert.equal(new window.FormData(document.querySelector('form')).getAll('assignee_user_ids[]').length, 2);
    await input('Not found');
    assert.equal(status.textContent, 'No matches');
    assert.equal(picked.children.length, 2, 'Selections survive unrelated searches');
    picked.querySelector('button').click();
    assert.equal(picked.children.length, 1, 'Remove deletes submission input');
    failure = true;
    await input('Person');
    assert.equal(status.textContent, 'Search failed');
    assert.equal(picked.children.length, 1, 'Failed search preserves selected users');
    assert.equal(search.hasAttribute('aria-busy'), false);
    dom.window.close();
    console.log('Support user picker: 21 UI assertions passed (400-user fixture).');
}
run().catch(error => { console.error(error); process.exitCode = 1; });
