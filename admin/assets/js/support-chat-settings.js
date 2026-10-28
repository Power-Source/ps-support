(function () {
    'use strict';
    const search = document.getElementById('ps-support-staff-search');
    if (!search) return;
    const choices = document.querySelector('#ps-support-staff .ps-support-picked');
    const form = document.getElementById('ps-support-chat-settings-form');
    const feedback = document.getElementById('ps-support-settings-feedback');
    const staffError = document.getElementById('ps-support-staff-error');
    const mode = document.getElementById('ps-support-mode');
    const hours = document.getElementById('ps-support-hours');
    const retention = document.getElementById('ps-support-retention');
    function validate() {
        const active = mode.type === 'checkbox' ? mode.checked : mode.value === 'local';
        const missing = active && !choices.querySelector('input');
        staffError.hidden = !missing;
        staffError.textContent = missing ? psSupportStaff.required : '';
        search.setAttribute('aria-invalid', String(missing));
        search.setAttribute('aria-describedby', 'ps-support-staff-search-status ps-support-staff-error');
        retention.setCustomValidity(Number(retention.value) * 24 < Number(hours.value) ? psSupportStaff.retention : '');
        return !missing;
    }
    form.addEventListener('change', validate);
    hours.addEventListener('input', validate);
    retention.addEventListener('input', validate);
    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        if (!validate()) {
            search.focus();
            return;
        }
        if (!form.reportValidity()) return;
        const submit = form.querySelector('[type="submit"]');
        submit.disabled = true;
        feedback.hidden = true;
        let failure = psSupportStaff.saveError;
        try {
            const response = await fetch(psSupportStaff.url, {
                method: 'POST', credentials: 'same-origin', body: new FormData(form)
            });
            const result = await response.json();
            if (!response.ok || !result.success) {
                failure = result.data && result.data.message ? result.data.message : failure;
                throw new Error(failure);
            }
            feedback.className = 'notice notice-success';
            feedback.querySelector('p').textContent = psSupportStaff.saved;
        } catch (error) {
            feedback.className = 'notice notice-error';
            feedback.querySelector('p').textContent = failure;
        } finally {
            submit.disabled = false;
            feedback.hidden = false;
            feedback.focus();
        }
    });
}());
