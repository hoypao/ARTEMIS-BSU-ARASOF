<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?> &middot; <?= e(APP_NAME) ?></title>
<link rel="apple-touch-icon" href="<?= APP_URL ?>/assets/images/bsulogo.jpg">
<meta name="theme-color" content="#B11226">
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/tailwind.css">
<script src="<?= APP_URL ?>/assets/js/lucide.js"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/modern.css">
<style>
  body { font-family: 'Inter', system-ui, sans-serif; }
  .spin { animation: artemis-spin 1s linear infinite; }
  @keyframes artemis-spin { to { transform: rotate(360deg); } }
  @keyframes login-rise { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: translateY(0); } }
  .login-rise { animation: login-rise 0.6s cubic-bezier(0.16,1,0.3,1) both; }
</style>
</head>
<body class="min-h-screen bg-gray-50">

<div class="min-h-screen flex flex-col items-center justify-center px-6 py-10">
  <div class="w-full max-w-md login-rise">

    <div class="flex items-center gap-3 mb-8 justify-center">
      <div class="w-10 h-10 rounded-full bg-white flex items-center justify-center overflow-hidden flex-shrink-0" style="border:1px solid #eee;">
        <img src="<?= APP_URL ?>/assets/images/bsulogo.jpg" alt="BatStateU OCA Logo" class="w-full h-full object-cover">
      </div>
      <div>
        <div class="font-bold text-sm" style="color:#B11226;">ARTEMIS</div>
        <div class="text-xs text-gray-500">BatStateU ARASOF-Nasugbu</div>
      </div>
    </div>

    <?php if (!$tokenValid): ?>
      <div class="bg-white rounded-2xl border border-gray-100 p-8 text-center" style="box-shadow: 0 1px 6px rgba(0,0,0,0.06);">
        <div class="w-14 h-14 rounded-full flex items-center justify-center mx-auto mb-4" style="background:#FEF2F2;">
          <i data-lucide="link-2-off" class="w-7 h-7" style="color:#B11226;"></i>
        </div>
        <h1 class="text-lg font-bold mb-2" style="color:#1a1a2e;">Link expired or invalid</h1>
        <p class="text-sm text-gray-500 mb-6 leading-relaxed">This password reset link has already been used, has expired, or never existed. Request a new one from the login page.</p>
        <a href="<?= e(APP_URL) ?>/login" class="modern-btn inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl text-sm font-semibold text-white transition-all" style="background: linear-gradient(135deg, #B11226, #7a0d1a); box-shadow: var(--shadow-glow-maroon);">Back to Login</a>
      </div>
    <?php else: ?>
      <div class="bg-white rounded-2xl border border-gray-100 p-8" style="box-shadow: 0 1px 6px rgba(0,0,0,0.06);">
        <h1 class="text-2xl font-bold mb-1" style="color:#1a1a2e;">Set a new password</h1>
        <p class="text-gray-500 text-sm mb-6">Choose a strong password you haven't used before.</p>

        <?php if ($error): ?>
          <div class="mb-5 rounded-xl p-4 border" style="background:#FEF2F2; border-color:#B11226;">
            <p class="text-xs font-medium" style="color:#B11226;"><?= e($error) ?></p>
          </div>
        <?php endif; ?>

        <form method="POST" action="<?= e(APP_URL) ?>/reset-password" id="resetForm" class="flex flex-col gap-4">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="token" value="<?= e($token) ?>">

          <div>
            <label for="password" class="text-xs font-medium text-gray-600 mb-1.5 block">New Password</label>
            <div class="relative">
              <i data-lucide="lock" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
              <input type="password" id="password" name="password" placeholder="At least 8 characters" minlength="8"
                class="login-input modern-input w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 text-sm bg-white focus:outline-none transition-colors" required>
            </div>
          </div>

          <div>
            <label for="confirmPassword" class="text-xs font-medium text-gray-600 mb-1.5 block">Confirm New Password</label>
            <div class="relative">
              <i data-lucide="lock" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
              <input type="password" id="confirmPassword" name="confirm_password" placeholder="Re-enter your new password" minlength="8"
                class="login-input modern-input w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 text-sm bg-white focus:outline-none transition-colors" required>
            </div>
            <p id="matchError" class="hidden text-xs mt-1.5" style="color:#B11226;">Passwords don't match.</p>
          </div>

          <button type="submit" id="submitBtn"
            class="modern-btn w-full py-3.5 rounded-xl text-sm font-semibold text-white mt-2 flex items-center justify-center gap-2 transition-all"
            style="background: linear-gradient(135deg, #B11226, #7a0d1a); box-shadow: var(--shadow-glow-maroon);">
            <span id="submitLabel">Reset Password</span>
            <i data-lucide="chevron-right" class="w-4 h-4" id="submitIcon"></i>
          </button>
        </form>
      </div>
    <?php endif; ?>

    <div class="mt-5 text-center">
      <a href="<?= e(APP_URL) ?>/login" class="text-xs text-gray-500 hover:text-gray-700 transition-colors">&larr; Back to Login</a>
    </div>
  </div>
</div>

<script>
lucide.createIcons();
document.querySelectorAll('.login-input').forEach(function (input) {
  input.addEventListener('focus', function () { input.style.borderColor = '#B11226'; });
  input.addEventListener('blur', function () { input.style.borderColor = '#e5e7eb'; });
});

var form = document.getElementById('resetForm');
if (form) {
  form.addEventListener('submit', function (e) {
    var pw = document.getElementById('password').value;
    var confirm = document.getElementById('confirmPassword').value;
    var matchError = document.getElementById('matchError');
    if (pw !== confirm) {
      e.preventDefault();
      matchError.classList.remove('hidden');
      return;
    }
    matchError.classList.add('hidden');
    document.getElementById('submitBtn').setAttribute('disabled', 'disabled');
    document.getElementById('submitLabel').textContent = 'Resetting...';
    document.getElementById('submitIcon').outerHTML = '<div class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full spin"></div>';
  });
}
</script>

</body>
</html>
