<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?> &middot; <?= e(APP_NAME) ?></title>
<link rel="manifest" href="<?= APP_URL ?>/manifest.json">
<link rel="apple-touch-icon" href="<?= APP_URL ?>/assets/images/bsulogo.jpg">
<meta name="theme-color" content="#B11226">
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/tailwind.css">
<script src="<?= APP_URL ?>/assets/js/lucide.js"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/modern.css">
<?php if (recaptcha_enabled()): ?>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<?php endif; ?>
<style>
  body { font-family: 'Inter', system-ui, sans-serif; }
  .spin { animation: artemis-spin 1s linear infinite; }
  @keyframes artemis-spin { to { transform: rotate(360deg); } }
  @keyframes login-rise { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: translateY(0); } }
  .login-rise { animation: login-rise 0.6s cubic-bezier(0.16,1,0.3,1) both; }
</style>
</head>
<body class="min-h-screen">
@include('partials.loading_screen')

<div class="min-h-screen flex">

  <!-- LEFT PANEL - branding, desktop only -->
  <div class="hidden lg:flex lg:w-1/2 relative overflow-hidden">
    <img src="<?= APP_URL ?>/assets/images/familypic.jpg" alt="BatStateU ARASOF-Nasugbu Culture and Arts Office" class="absolute inset-0 w-full h-full object-cover" style="object-position: center 25%;">
    <div class="absolute inset-0" style="background: linear-gradient(160deg, rgba(177,18,38,0.92) 0%, rgba(20,5,10,0.88) 100%);"></div>
    <div class="relative z-10 flex flex-col justify-between p-12 w-full">
      <div class="flex items-center gap-3">
        <div class="w-12 h-12 rounded-full bg-white flex items-center justify-center overflow-hidden flex-shrink-0" style="border: 1px solid rgba(255,255,255,0.4);">
          <img src="<?= APP_URL ?>/assets/images/bsulogo.jpg" alt="BatStateU OCA Logo" class="w-full h-full object-cover">
        </div>
        <div>
          <div class="font-bold text-white tracking-wider">ARTEMIS</div>
          <div class="text-xs" style="color:#D4AF37;">BatStateU ARASOF-Nasugbu</div>
        </div>
      </div>

      <div>
        <h2 class="text-4xl font-bold text-white mb-4 leading-snug">
          Culture, Talent &<br>Excellence Managed<br>Intelligently.
        </h2>
        <p class="text-red-100 text-sm leading-relaxed max-w-sm">
          The official portal of the BatStateU ARASOF-Nasugbu Culture and Arts Office. Apply, track, and manage your cultural journey in one place.
        </p>
        <div class="mt-8 flex flex-col gap-3">
          <?php foreach ([
              'Submit applications online — no paperwork',
              'Track status from OCA to Chancellor approval',
              'Get notified instantly on every update',
          ] as $feat): ?>
            <div class="flex items-center gap-3">
              <div class="w-5 h-5 rounded-full flex items-center justify-center flex-shrink-0" style="background:#D4AF37;">
                <i data-lucide="chevron-right" class="w-3 h-3 text-white"></i>
              </div>
              <span class="text-sm text-red-100"><?= e($feat) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="text-xs text-red-300">&copy; 2026 ARTEMIS — Culture and Arts Office, BatStateU ARASOF-Nasugbu</div>
    </div>
  </div>

  <!-- RIGHT PANEL - login form -->
  <div class="w-full lg:w-1/2 flex flex-col bg-gray-50 overflow-y-auto">
    <div class="flex-1 flex flex-col lg:items-center lg:justify-center px-6 py-8">
      <div class="w-full max-w-md login-rise">

        <a href="<?= e(APP_URL) ?>" class="flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 transition-colors mb-8">
          <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Home
        </a>

        <!-- Mobile logo -->
        <div class="lg:hidden flex items-center gap-3 mb-4">
          <div class="w-10 h-10 rounded-full bg-white flex items-center justify-center overflow-hidden flex-shrink-0" style="border:1px solid #eee;">
            <img src="<?= APP_URL ?>/assets/images/bsulogo.jpg" alt="BatStateU OCA Logo" class="w-full h-full object-cover">
          </div>
          <div>
            <div class="font-bold text-sm" style="color:#B11226;">ARTEMIS</div>
            <div class="text-xs text-gray-500">BatStateU ARASOF-Nasugbu</div>
          </div>
        </div>

        <div class="mb-6">
          <h1 class="text-3xl font-bold mb-1" style="color:#1a1a2e;">Welcome back</h1>
          <p class="text-gray-500 text-sm">Sign in to your ARTEMIS account to continue.</p>
        </div>

        <!-- Role selector (visual only - actual role comes from your account) -->
        <div class="flex bg-white rounded-xl p-1 border border-gray-200 mb-6" id="roleSelector">
          <button type="button" data-role="student" class="role-btn flex-1 py-2.5 rounded-lg text-sm font-medium transition-all capitalize" style="background:#B11226; color:#fff;">👨‍🎓 Student</button>
          <button type="button" data-role="admin" class="role-btn flex-1 py-2.5 rounded-lg text-sm font-medium transition-all capitalize" style="color:#6b7280;">🧑‍💼 Admin</button>
        </div>

        <?php if ($success): ?>
          <div class="mb-5 rounded-xl p-4 border flex items-center gap-2" style="background:#F0FDF4; border-color:#22C55E;">
            <i data-lucide="check-circle" class="w-4 h-4 flex-shrink-0" style="color:#22C55E;"></i>
            <p class="text-xs font-medium" style="color:#15803D;"><?= e($success) ?></p>
          </div>
        <?php endif; ?>

        <?php if ($error): ?>
          <div class="mb-5 rounded-xl p-4 border" style="background:#FEF2F2; border-color:#B11226;">
            <p class="text-xs font-medium" style="color:#B11226;"><?= e($error) ?></p>
          </div>
        <?php endif; ?>

        <!-- Demo credentials -->
        <div id="demoBox" class="mb-5 rounded-xl p-4 border overflow-hidden" style="background:#FEF9E7; border-color:#D4AF37;">
          <div class="flex items-start justify-between gap-2">
            <div class="flex items-start gap-2">
              <i data-lucide="info" class="w-4 h-4 flex-shrink-0 mt-0.5" style="color:#D4AF37;"></i>
              <div>
                <div class="text-xs font-semibold mb-2" style="color:#92400E;">Demo Credentials</div>
                <div class="flex flex-wrap gap-2">
                  <button type="button" class="demo-fill-btn modern-btn text-xs px-3 py-1.5 rounded-lg font-medium hover:opacity-90 transition-opacity" style="background:#D4AF37; color:#fff;"
                    data-role="student" data-email="juan.delacruz@g.batstate-u.edu.ph" data-password="student123">Student Demo</button>
                  <button type="button" class="demo-fill-btn modern-btn text-xs px-3 py-1.5 rounded-lg font-medium hover:opacity-90 transition-opacity" style="background:#B11226; color:#fff;"
                    data-role="admin" data-email="admin@batstate-u.edu.ph" data-password="admin123">Admin Demo</button>
                </div>
              </div>
            </div>
            <button type="button" id="dismissDemoBtn" class="text-gray-400 hover:text-gray-600" aria-label="Dismiss demo credentials">
              <i data-lucide="eye-off" class="w-4 h-4"></i>
            </button>
          </div>
        </div>

        <!-- Form -->
        <form method="POST" action="<?= e(APP_URL) ?>/login" id="loginForm" class="flex flex-col gap-4">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <div>
            <label for="email" class="text-xs font-medium text-gray-600 mb-1.5 block">Email Address</label>
            <div class="relative">
              <i data-lucide="mail" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
              <input type="email" id="email" name="email" value="<?= e($oldEmail) ?>" placeholder="yourname@batstate-u.edu.ph"
                class="login-input modern-input w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 text-sm bg-white focus:outline-none transition-colors" required autofocus>
            </div>
          </div>

          <div>
            <label for="password" class="text-xs font-medium text-gray-600 mb-1.5 block">Password</label>
            <div class="relative">
              <i data-lucide="lock" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
              <input type="password" id="password" name="password" placeholder="Enter your password"
                class="login-input modern-input w-full pl-10 pr-10 py-3 rounded-xl border border-gray-200 text-sm bg-white focus:outline-none transition-colors" required>
              <button type="button" id="togglePasswordBtn" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600" aria-label="Show password" aria-pressed="false">
                <i data-lucide="eye" class="w-4 h-4"></i>
              </button>
            </div>
          </div>

          <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 cursor-pointer">
              <input type="checkbox" name="remember" value="1" <?= $rememberChecked ? 'checked' : '' ?> class="w-3.5 h-3.5 rounded accent-red-700">
              <span class="text-xs text-gray-500">Remember me</span>
            </label>
            <button type="button" id="forgotBtn" class="text-xs font-medium" style="color:#B11226;">Forgot Password?</button>
          </div>

          <?php if (recaptcha_enabled()): ?>
            <div class="g-recaptcha" data-sitekey="<?= e(RECAPTCHA_SITE_KEY) ?>"></div>
          <?php endif; ?>

          <button type="submit" id="submitBtn"
            class="modern-btn w-full py-3.5 rounded-xl text-sm font-semibold text-white mt-2 flex items-center justify-center gap-2 transition-all"
            style="background: linear-gradient(135deg, #B11226, #7a0d1a); box-shadow: var(--shadow-glow-maroon);">
            <span id="submitLabel">Sign In to ARTEMIS</span>
            <i data-lucide="chevron-right" class="w-4 h-4" id="submitIcon"></i>
          </button>
        </form>

        <div class="mt-5 text-center">
          <span class="text-xs text-gray-500">Don't have an account? </span>
          <button type="button" id="applyInfoBtn" class="text-xs font-medium" style="color:#B11226;">Apply Now</button>
        </div>
        <div class="mt-3 text-center">
          <a href="<?= e(APP_URL) ?>/track" class="text-xs text-gray-400 hover:text-gray-600 underline">Track My Application (No Login Required)</a>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- FORGOT PASSWORD MODAL -->
<div id="forgotModal" class="fixed inset-0 z-50 hidden items-end sm:items-center justify-center sm:p-4 bg-black/50 backdrop-blur-sm">
  <div class="bg-white w-full sm:rounded-2xl sm:max-w-sm overflow-hidden" style="box-shadow:0 24px 60px rgba(0,0,0,0.2); border-radius:1.25rem 1.25rem 0 0;">
    <div class="sm:hidden flex justify-center pt-3 pb-1">
      <div class="w-10 h-1 bg-gray-200 rounded-full"></div>
    </div>
    <div class="px-6 py-4 flex items-start justify-between" style="background: linear-gradient(135deg, #B11226, #7a0d1a);">
      <div>
        <h3 class="font-bold text-white">Reset Password</h3>
        <p class="text-red-200 text-xs mt-0.5">We'll send a reset link to your email.</p>
      </div>
      <button type="button" class="modal-close-btn w-10 h-10 rounded-full bg-white/20 flex items-center justify-center hover:bg-white/30 transition-colors flex-shrink-0" data-target="forgotModal" aria-label="Close reset password dialog">
        <i data-lucide="x" class="w-3.5 h-3.5 text-white"></i>
      </button>
    </div>
    <div class="p-6 flex flex-col gap-4" style="padding-bottom: calc(env(safe-area-inset-bottom) + 1.5rem);">
      <div id="forgotFormState" class="flex flex-col gap-4">
        <div>
          <label for="forgotEmail" class="text-xs font-medium text-gray-600 block mb-1.5">Email Address</label>
          <div class="relative">
            <i data-lucide="mail" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
            <input type="email" id="forgotEmail" placeholder="yourname@batstate-u.edu.ph"
              class="login-input modern-input w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 text-sm focus:outline-none transition-colors">
          </div>
        </div>
        <button type="button" id="sendResetBtn" disabled class="modern-btn"
          class="w-full py-3 rounded-xl text-sm font-semibold text-white disabled:opacity-40 transition-opacity hover:opacity-90"
          style="background: linear-gradient(135deg, #B11226, #7a0d1a);">Send Reset Link</button>
      </div>
      <div id="forgotSentState" class="hidden text-center py-4">
        <div class="w-14 h-14 rounded-full flex items-center justify-center mx-auto mb-3" style="background:#DCFCE7;">
          <i data-lucide="mail" class="w-7 h-7" style="color:#15803D;"></i>
        </div>
        <div class="font-semibold text-sm mb-1" style="color:#1a1a2e;">Reset link sent!</div>
        <div class="text-xs text-gray-500 leading-relaxed">Check your email at <strong id="forgotSentEmail"></strong> for the reset instructions.</div>
      </div>
      <button type="button" class="modal-close-btn text-xs text-gray-400 text-center hover:text-gray-600 transition-colors" data-target="forgotModal" id="forgotCancelBtn">Cancel</button>
    </div>
  </div>
</div>

<!-- APPLY NOW INFO MODAL -->
<div id="applyModal" class="fixed inset-0 z-50 hidden items-end sm:items-center justify-center sm:p-4 bg-black/50 backdrop-blur-sm">
  <div class="bg-white w-full sm:rounded-2xl sm:max-w-sm overflow-hidden" style="box-shadow:0 24px 60px rgba(0,0,0,0.2); border-radius:1.25rem 1.25rem 0 0;">
    <div class="sm:hidden flex justify-center pt-3 pb-1">
      <div class="w-10 h-1 bg-gray-200 rounded-full"></div>
    </div>
    <div class="px-6 py-4 flex items-start justify-between" style="background: linear-gradient(135deg, #D4AF37, #b8932e);">
      <div>
        <h3 class="font-bold text-white">How to Apply</h3>
        <p class="text-yellow-100 text-xs mt-0.5">Joining BatStateU Culture & Arts</p>
      </div>
      <button type="button" class="modal-close-btn w-10 h-10 rounded-full bg-white/20 flex items-center justify-center hover:bg-white/30 transition-colors flex-shrink-0" data-target="applyModal" aria-label="Close how-to-apply dialog">
        <i data-lucide="x" class="w-3.5 h-3.5 text-white"></i>
      </button>
    </div>
    <div class="p-6 flex flex-col gap-3" style="padding-bottom: calc(env(safe-area-inset-bottom) + 1.5rem);">
      <p class="text-xs text-gray-500 leading-relaxed">
        Applications for the BatStateU ARASOF-Nasugbu Culture and Arts troupe are processed directly through the OCA Office.
      </p>
      <?php foreach ([
          ['1', 'Visit the OCA Office at Room 201, Admin Building'],
          ['2', 'Fill out the physical application form'],
          ['3', 'Once registered, your account will be created for you'],
          ['4', 'Log in using the credentials provided by OCA'],
      ] as [$step, $text]): ?>
        <div class="flex items-start gap-3">
          <div class="w-7 h-7 rounded-full flex items-center justify-center flex-shrink-0 text-xs font-bold text-white" style="background:#D4AF37;"><?= e($step) ?></div>
          <span class="text-xs text-gray-600 mt-1.5 leading-relaxed"><?= e($text) ?></span>
        </div>
      <?php endforeach; ?>
      <button type="button" class="modal-close-btn modern-btn mt-2 w-full py-3 rounded-xl text-sm font-semibold text-white hover:opacity-90 transition-opacity" data-target="applyModal"
        style="background: linear-gradient(135deg, #B11226, #7a0d1a);">Got it</button>
    </div>
  </div>
</div>

<script>
var APP_URL = <?= json_encode(APP_URL) ?>;
lucide.createIcons();

document.querySelectorAll('.login-input').forEach(function (input) {
  input.addEventListener('focus', function () { input.style.borderColor = '#B11226'; });
  input.addEventListener('blur', function () { input.style.borderColor = '#e5e7eb'; });
});

// Role selector (visual + demo matching only; real role comes from the account itself)
document.querySelectorAll('.role-btn').forEach(function (btn) {
  btn.addEventListener('click', function () {
    document.querySelectorAll('.role-btn').forEach(function (b) {
      b.style.background = ''; b.style.color = '#6b7280';
    });
    btn.style.background = '#B11226';
    btn.style.color = '#fff';
  });
});

// Demo credential quick-fill
document.querySelectorAll('.demo-fill-btn').forEach(function (btn) {
  btn.addEventListener('click', function () {
    document.getElementById('email').value = btn.dataset.email;
    document.getElementById('password').value = btn.dataset.password;
    document.querySelector('.role-btn[data-role="' + btn.dataset.role + '"]').click();
  });
});

document.getElementById('dismissDemoBtn').addEventListener('click', function () {
  document.getElementById('demoBox').style.display = 'none';
});

// Password visibility toggle
var passwordInput = document.getElementById('password');
var togglePasswordBtn = document.getElementById('togglePasswordBtn');
togglePasswordBtn.addEventListener('click', function () {
  var showing = passwordInput.type === 'text';
  passwordInput.type = showing ? 'password' : 'text';
  togglePasswordBtn.innerHTML = '<i data-lucide="' + (showing ? 'eye' : 'eye-off') + '" class="w-4 h-4"></i>';
  togglePasswordBtn.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
  togglePasswordBtn.setAttribute('aria-pressed', showing ? 'false' : 'true');
  lucide.createIcons();
});

// Loading state on submit (real POST navigates away on success)
document.getElementById('loginForm').addEventListener('submit', function () {
  document.getElementById('submitBtn').setAttribute('disabled', 'disabled');
  document.getElementById('submitBtn').style.background = '#9CA3AF';
  document.getElementById('submitBtn').style.boxShadow = 'none';
  document.getElementById('submitLabel').textContent = 'Signing in...';
  document.getElementById('submitIcon').outerHTML = '<div class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full spin"></div>';
});

// Modal open/close helpers
function openModal(id) {
  var modal = document.getElementById(id);
  modal.classList.remove('hidden');
  modal.classList.add('flex');
}
function closeModal(id) {
  var modal = document.getElementById(id);
  modal.classList.add('hidden');
  modal.classList.remove('flex');
}
document.querySelectorAll('.modal-close-btn').forEach(function (btn) {
  btn.addEventListener('click', function () { closeModal(btn.dataset.target); });
});
document.getElementById('forgotModal').addEventListener('click', function (e) {
  if (e.target === this) closeModal('forgotModal');
});
document.getElementById('applyModal').addEventListener('click', function (e) {
  if (e.target === this) closeModal('applyModal');
});

document.getElementById('forgotBtn').addEventListener('click', function () {
  document.getElementById('forgotEmail').value = document.getElementById('email').value;
  document.getElementById('forgotFormState').classList.remove('hidden');
  document.getElementById('forgotSentState').classList.add('hidden');
  document.getElementById('sendResetBtn').disabled = !document.getElementById('forgotEmail').value.trim();
  document.getElementById('forgotCancelBtn').textContent = 'Cancel';
  openModal('forgotModal');
});
document.getElementById('forgotEmail').addEventListener('input', function () {
  document.getElementById('sendResetBtn').disabled = !this.value.trim();
});
document.getElementById('sendResetBtn').addEventListener('click', function () {
  var email = document.getElementById('forgotEmail').value.trim();
  if (!email) return;
  var btn = this;
  var originalLabel = btn.textContent;
  btn.disabled = true;
  btn.textContent = 'Sending...';
  var csrfToken = document.querySelector('#loginForm input[name="csrf_token"]').value;

  fetch(APP_URL + '/forgot-password', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ csrf_token: csrfToken, email: email }),
  })
    .then(function (r) { return r.json(); })
    .then(function () {
      document.getElementById('forgotSentEmail').textContent = email;
      document.getElementById('forgotFormState').classList.add('hidden');
      document.getElementById('forgotSentState').classList.remove('hidden');
      document.getElementById('forgotCancelBtn').textContent = 'Close';
    })
    .catch(function () {
      btn.textContent = 'Network error — try again';
    })
    .finally(function () {
      btn.disabled = false;
      if (btn.textContent === 'Sending...') btn.textContent = originalLabel;
    });
});

document.getElementById('applyInfoBtn').addEventListener('click', function () { openModal('applyModal'); });
</script>
<script src="<?= APP_URL ?>/assets/js/modern.js"></script>

</body>
</html>
