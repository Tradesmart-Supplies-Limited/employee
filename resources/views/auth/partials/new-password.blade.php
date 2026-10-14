{{-- New password + confirm, with show/hide and a live checklist --}}
<div class="mb-3">
    <label class="form-label small fw-semibold text-muted" for="password">New Password</label>
    <div class="input-group">
        <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock"></i></span>
        <input type="password" name="password" id="password" class="form-control border-start-0 border-end-0"
               placeholder="At least 8 characters" required autocomplete="new-password">
        <button type="button" class="input-group-text bg-light" id="togglePw" aria-label="Show password"><i class="bi bi-eye"></i></button>
    </div>
    @error('password')<small class="text-danger">{{ $message }}</small>@enderror
    <ul class="list-unstyled small mt-2 mb-0" id="pwRules">
        <li data-rule="len"><i class="bi bi-circle"></i> At least 8 characters</li>
        <li data-rule="letter"><i class="bi bi-circle"></i> Contains a letter</li>
        <li data-rule="number"><i class="bi bi-circle"></i> Contains a number</li>
    </ul>
</div>

<div class="mb-4">
    <label class="form-label small fw-semibold text-muted" for="password_confirmation">Confirm New Password</label>
    <div class="input-group">
        <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock-fill"></i></span>
        <input type="password" name="password_confirmation" id="password_confirmation"
               class="form-control border-start-0" placeholder="Type it again" required autocomplete="new-password">
    </div>
    <small class="text-danger d-none" id="pwMismatch">Passwords don't match yet.</small>
</div>

<script>
(function () {
    const pw = document.getElementById('password'), pc = document.getElementById('password_confirmation');
    const rules = { len: v => v.length >= 8, letter: v => /[A-Za-z]/.test(v), number: v => /\d/.test(v) };
    function check() {
        document.querySelectorAll('#pwRules li').forEach(li => {
            const ok = rules[li.dataset.rule](pw.value);
            li.className = ok ? 'text-success' : 'text-muted';
            li.querySelector('i').className = ok ? 'bi bi-check-circle-fill' : 'bi bi-circle';
        });
        document.getElementById('pwMismatch').classList.toggle('d-none', !pc.value || pc.value === pw.value);
    }
    pw.addEventListener('input', check); pc.addEventListener('input', check);
    document.getElementById('togglePw').addEventListener('click', function () {
        const show = pw.type === 'password';
        pw.type = pc.type = show ? 'text' : 'password';
        this.querySelector('i').className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
    });
})();
</script>
