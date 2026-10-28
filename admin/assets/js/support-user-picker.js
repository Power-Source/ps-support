(function () {
    'use strict';
    document.querySelectorAll('.ps-support-user-picker').forEach(function (root) {
        const search = root.querySelector('.ps-support-person-search');
        const picked = root.querySelector('.ps-support-picked');
        const results = root.querySelector('.ps-support-person-results');
        const status = root.querySelector('.ps-support-picker-status');
        let revision = 0;
        let timer;
        let active = -1;
        let candidates = [];
        function close() {
            results.hidden = true;
            search.setAttribute('aria-expanded', 'false');
            search.removeAttribute('aria-activedescendant');
            active = -1;
        }
        function changed() {
            root.dispatchEvent(new Event('change', { bubbles: true }));
        }
        function remove(button) {
            button.closest('li').remove();
            changed();
            search.focus();
        }
        picked.addEventListener('click', function (event) {
            const button = event.target.closest('.ps-support-remove');
            if (button) remove(button);
        });
        function choose(user) {
            if (Array.from(picked.querySelectorAll('input')).some(input => input.value === String(user.id))) return;
            const item = document.createElement('li');
            item.dataset.userId = user.id;
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = root.dataset.name;
            input.value = user.id;
            const label = document.createElement('span');
            label.textContent = user.label;
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'ps-support-remove';
            button.textContent = psSupportPicker.remove.replace(/:$/, '');
            button.setAttribute('aria-label', psSupportPicker.remove + ' ' + user.label);
            item.append(input, label, button);
            picked.append(item);
            ++revision;
            search.value = '';
            close();
            status.textContent = psSupportPicker.hint;
            changed();
            search.focus();
        }
        async function load() {
            const current = ++revision;
            const url = new URL(psSupportPicker.url);
            url.search = new URLSearchParams({
                action: 'ps_support_chat_staff', nonce: psSupportPicker.nonce,
                network: psSupportPicker.network, kind: root.dataset.kind, q: search.value.trim(),
                exclude: Array.from(picked.querySelectorAll('input'), input => input.value).join(',')
            }).toString();
            search.setAttribute('aria-busy', 'true');
            try {
                const response = await fetch(url, { credentials: 'same-origin' });
                const data = await response.json();
                if (!response.ok || !data.success) throw new Error(psSupportPicker.error);
                if (current !== revision) return;
                const selected = new Set(Array.from(picked.querySelectorAll('input'), input => input.value));
                candidates = data.data.users.filter(user => !selected.has(String(user.id)));
                results.replaceChildren();
                candidates.forEach(function (user, index) {
                    const item = document.createElement('li');
                    item.id = search.id + '-option-' + index;
                    item.setAttribute('role', 'option');
                    item.setAttribute('aria-selected', 'false');
                    item.textContent = user.label;
                    item.addEventListener('mousedown', event => event.preventDefault());
                    item.addEventListener('pointerdown', event => event.preventDefault());
                    item.addEventListener('click', () => choose(user));
                    results.append(item);
                });
                results.hidden = !candidates.length;
                search.setAttribute('aria-expanded', String(!!candidates.length));
                active = -1;
                status.textContent = data.data.more ? psSupportPicker.more : candidates.length ? '' : psSupportPicker.empty;
            } catch (error) {
                if (current === revision) {
                    close();
                    status.textContent = psSupportPicker.error;
                }
            } finally {
                if (current === revision) search.removeAttribute('aria-busy');
            }
        }
        search.addEventListener('input', function () {
            ++revision;
            clearTimeout(timer);
            close();
            search.removeAttribute('aria-busy');
            if (search.value.trim().length < 2) {
                status.textContent = psSupportPicker.hint;
                return;
            }
            timer = setTimeout(load, 250);
        });
        search.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                ++revision;
                clearTimeout(timer);
                close();
                return;
            }
            if (results.hidden) return;
            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                active = active < 0 ? (event.key === 'ArrowDown' ? 0 : candidates.length - 1) :
                    (active + (event.key === 'ArrowDown' ? 1 : -1) + candidates.length) % candidates.length;
                Array.from(results.children).forEach(function (item, index) {
                    item.setAttribute('aria-selected', String(index === active));
                });
                search.setAttribute('aria-activedescendant', results.children[active].id);
                results.children[active].scrollIntoView({ block: 'nearest' });
            } else if (event.key === 'Enter') {
                event.preventDefault();
                if (active >= 0) choose(candidates[active]);
            }
        });
        search.addEventListener('blur', function () {
            ++revision;
            clearTimeout(timer);
            close();
            search.removeAttribute('aria-busy');
        });
    });
}());
