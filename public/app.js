document.addEventListener('DOMContentLoaded', () => {
    document.querySelector('[data-menu]')?.addEventListener('click', event => {
        const button = event.currentTarget;
        const open = document.body.classList.toggle('menu-open');
        button.setAttribute('aria-expanded', String(open));
    });
    document.querySelectorAll('[data-password]').forEach(button => button.addEventListener('click', () => {
        const input = document.getElementById(button.dataset.password);
        input.type = input.type === 'password' ? 'text' : 'password';
        button.setAttribute('aria-label', input.type === 'password' ? 'Tampilkan kata sandi' : 'Sembunyikan kata sandi');
    }));
    document.querySelectorAll('[data-confirm]').forEach(form => form.addEventListener('submit', event => {
        if (form.dataset.confirmed === 'true') { delete form.dataset.confirmed; return; }
        event.preventDefault();
        const submitter = event.submitter;
        const dialog = document.createElement('dialog');
        dialog.className = 'confirm-dialog';
        const title = document.createElement('h2'); title.textContent = 'Konfirmasi tindakan';
        const message = document.createElement('p'); message.textContent = form.dataset.confirm;
        const actions = document.createElement('div'); actions.className = 'actions';
        const cancel = document.createElement('button'); cancel.type = 'button'; cancel.className = 'button'; cancel.textContent = 'Batal';
        const confirm = document.createElement('button'); confirm.type = 'button'; confirm.className = 'button primary'; confirm.textContent = 'Ya, lanjutkan';
        cancel.addEventListener('click', () => { dialog.close(); dialog.remove(); submitter?.focus(); });
        confirm.addEventListener('click', () => { dialog.close(); dialog.remove(); form.dataset.confirmed = 'true'; form.requestSubmit(submitter); });
        dialog.addEventListener('cancel', () => { dialog.remove(); submitter?.focus(); });
        actions.append(cancel, confirm); dialog.append(title, message, actions); document.body.append(dialog); dialog.showModal(); cancel.focus();
    }));
    document.querySelectorAll('input[type="file"]').forEach(input => input.addEventListener('change', () => {
        const target = document.getElementById(input.id + '-selection');
        if (target) target.textContent = Array.from(input.files).map(file => `${file.name} (${(file.size / 1048576).toFixed(2)} MB)`).join(' · ');
        if (input.id === 'attachments') document.getElementById('expected_attachments').value = input.files.length;
    }));
    const decision = document.getElementById('decision');
    const note = document.getElementById('review-note');
    const syncDecision = () => { if (note) note.required = decision.value !== 'approved'; };
    decision?.addEventListener('change', syncDecision);
    if (decision) syncDecision();
});
