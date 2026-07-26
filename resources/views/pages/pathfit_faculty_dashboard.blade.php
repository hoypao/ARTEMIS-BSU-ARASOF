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
<style>
  #scrollProgressBar { position: fixed; top: 0; left: 0; height: 3px; width: 0%; z-index: 50; background: #B11226; transition: width 0.1s linear; pointer-events: none; }
  @media (prefers-reduced-motion: reduce) { #scrollProgressBar { transition: none; } }
  html, body { overflow-x: hidden; scrollbar-width: none; -ms-overflow-style: none; }
  html::-webkit-scrollbar, body::-webkit-scrollbar { display: none; width: 0; height: 0; }
  body { font-family: 'Inter', system-ui, sans-serif; background:#F7F5F2; }
  .dash-input:focus { border-color:#B11226; }
  pre.justification { white-space: pre-wrap; font-family: inherit; }
</style>
</head>
<body>
@include('partials.loading_screen')
<div id="scrollProgressBar"></div>

<div class="min-h-screen flex bg-background">
  <!-- Sidebar - desktop only -->
  <aside class="hidden lg:flex flex-col w-64 min-h-screen pt-0 fixed left-0 top-0 bottom-0 z-30" style="background: linear-gradient(180deg, #B11226 0%, #7a0d1a 100%);">
    <div class="px-6 py-4 border-b border-white/10">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center text-white font-bold text-sm flex-shrink-0"><?= e($initials) ?></div>
        <div class="flex-1 min-w-0">
          <div class="text-white text-sm font-semibold truncate"><?= e($fullName) ?></div>
          <div class="text-red-200 text-xs truncate">PATHFit Faculty</div>
        </div>
      </div>
      <div class="mt-2 inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium" style="background: rgba(212,175,55,0.2); color: #D4AF37;">
        <span class="w-1.5 h-1.5 rounded-full" style="background: #D4AF37;"></span>
        PATHFit Faculty
      </div>
    </div>

    <nav class="flex-1 px-4 py-4 flex flex-col gap-1">
      <button class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm transition-all text-left" style="background: rgba(255,255,255,0.2); color:#fff; font-weight:600;">
        <i data-lucide="home" class="w-4 h-4"></i> Dashboard
      </button>
    </nav>

    <div class="px-4 pb-4 flex flex-col gap-2 border-t border-white/10 pt-4">
      <a href="<?= e(APP_URL) ?>/logout" class="flex items-center gap-2 px-3 py-2.5 rounded-xl text-sm transition-all w-full hover:bg-white/10" style="color: rgba(255,255,255,0.6);">
        <i data-lucide="log-out" class="w-4 h-4"></i> Sign Out
      </a>
    </div>
  </aside>

  <!-- Main Content -->
  <div class="flex-1 lg:ml-64 flex flex-col min-h-screen">
    <header class="sticky top-0 z-20 bg-white border-b border-gray-100" style="box-shadow: 0 1px 4px rgba(0,0,0,0.06);">
      <div class="flex items-center justify-between px-4 sm:px-6 py-3">
        <div class="flex items-center gap-3">
          <div class="lg:hidden flex items-center gap-2">
            <div class="w-7 h-7 rounded-full bg-white flex items-center justify-center overflow-hidden flex-shrink-0" style="border:1px solid #eee;">
              <img src="<?= APP_URL ?>/assets/images/bsulogo.jpg" alt="BatStateU OCA Logo" class="w-full h-full object-cover">
            </div>
            <span class="font-bold text-sm" style="color:#B11226;">ARTEMIS</span>
          </div>
          <div class="hidden lg:block">
            <h1 class="font-bold text-base" style="color:#1a1a2e;">PATHFit Faculty Dashboard</h1>
            <p class="text-xs text-gray-500"><?= e(date('l, F j, Y')) ?></p>
          </div>
        </div>
        <?php if ($fullName): ?>
        <div class="flex items-center gap-2 lg:hidden">
          <div class="w-8 h-8 rounded-full flex items-center justify-center text-white font-bold text-xs" style="background:#B11226;"><?= e($initials) ?></div>
        </div>
        <?php endif; ?>
      </div>
    </header>

    <main class="flex-1 p-4 sm:p-6 flex flex-col gap-4 sm:gap-6">
      <?php if ($flashSuccess = flash_get('success')): ?>
        <div class="rounded-2xl p-4 border text-sm font-medium" style="background:#DCFCE7; border-color:#22C55E; color:#15803D;"><?= e($flashSuccess) ?></div>
      <?php endif; ?>
      <?php if ($flashError = flash_get('error')): ?>
        <div class="rounded-2xl p-4 border text-sm font-medium" style="background:#FEE2E2; border-color:#EF4444; color:#B91C1C;"><?= e($flashError) ?></div>
      <?php endif; ?>
      <div id="jsFlash"></div>

      <div class="modern-card rounded-2xl p-5 sm:p-6 text-white" style="background: linear-gradient(135deg, #B11226 0%, #7a0d1a 100%);">
        <h2 class="text-lg sm:text-xl font-bold mb-1">Welcome back, <?= e($faculty['first_name']) ?></h2>
        <p class="text-red-100 text-sm">Students named you as their PATHFit instructor when requesting a training equivalency exemption (Art. X, WI-OCA-04). Review each request below.</p>
      </div>

      <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <div class="modern-card bg-white rounded-2xl p-4 sm:p-5 border border-gray-100" style="box-shadow: 0 1px 4px rgba(0,0,0,0.06);">
          <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center mb-3" style="background:#DBEAFE;"><i data-lucide="file-text" class="w-4 h-4 sm:w-5 sm:h-5" style="color:#3B82F6;"></i></div>
          <div class="text-xl sm:text-2xl font-bold mb-0.5" style="color:#1a1a2e;"><?= count($applications) ?></div>
          <div class="text-xs text-gray-500 leading-tight">Assigned to Me</div>
        </div>
        <div class="modern-card bg-white rounded-2xl p-4 sm:p-5 border border-gray-100" style="box-shadow: 0 1px 4px rgba(0,0,0,0.06);">
          <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center mb-3" style="background:#FEF9C3;"><i data-lucide="clock" class="w-4 h-4 sm:w-5 sm:h-5" style="color:#F59E0B;"></i></div>
          <div class="text-xl sm:text-2xl font-bold mb-0.5" style="color:#1a1a2e;"><?= $pendingCount ?></div>
          <div class="text-xs text-gray-500 leading-tight">Awaiting My Review</div>
        </div>
        <div class="modern-card bg-white rounded-2xl p-4 sm:p-5 border border-gray-100" style="box-shadow: 0 1px 4px rgba(0,0,0,0.06);">
          <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center mb-3" style="background:#DCFCE7;"><i data-lucide="check-circle" class="w-4 h-4 sm:w-5 sm:h-5" style="color:#22C55E;"></i></div>
          <div class="text-xl sm:text-2xl font-bold mb-0.5" style="color:#1a1a2e;"><?= $approvedCount ?></div>
          <div class="text-xs text-gray-500 leading-tight">Granted</div>
        </div>
        <div class="modern-card bg-white rounded-2xl p-4 sm:p-5 border border-gray-100" style="box-shadow: 0 1px 4px rgba(0,0,0,0.06);">
          <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center mb-3" style="background:#FEE2E2;"><i data-lucide="x-circle" class="w-4 h-4 sm:w-5 sm:h-5" style="color:#EF4444;"></i></div>
          <div class="text-xl sm:text-2xl font-bold mb-0.5" style="color:#1a1a2e;"><?= $rejectedCount ?></div>
          <div class="text-xs text-gray-500 leading-tight">Denied</div>
        </div>
      </div>

      <div class="flex flex-col gap-4">
        <?php if (!$applications): ?>
          <div class="modern-card bg-white rounded-2xl border border-gray-100 p-8 text-center text-sm text-gray-400">No students have named you as their PATHFit instructor yet.</div>
        <?php endif; ?>

        <?php foreach ($applications as $app): ?>
          <div class="modern-card bg-white rounded-2xl border border-gray-100 overflow-hidden" style="box-shadow: 0 1px 4px rgba(0,0,0,0.06);" data-app-id="<?= (int) $app['application_id'] ?>">
            <div class="flex flex-wrap items-center justify-between gap-2 px-5 py-3.5 border-b border-gray-100">
              <div>
                <div class="font-semibold text-sm" style="color:#1a1a2e;"><?= e($app['first_name'] . ' ' . $app['last_name']) ?> <span class="text-gray-400 font-normal">&middot; <?= e($app['id_number'] ?? '—') ?></span></div>
                <div class="text-xs text-gray-500"><?= e($app['course'] ?? '—') ?> &middot; <?= e($app['application_code']) ?> &middot; Submitted <?= e(format_date($app['submitted_at'])) ?></div>
              </div>
              <span class="app-status-badge inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium"
                style="background:<?= $app['status'] === 'Approved' ? '#DCFCE7' : ($app['status'] === 'Rejected' ? '#FEE2E2' : '#FEF9C3') ?>; color:<?= $app['status'] === 'Approved' ? '#16A34A' : ($app['status'] === 'Rejected' ? '#DC2626' : '#B45309') ?>;">
                <?= e($app['status']) ?>
              </span>
            </div>

            <div class="p-5 flex flex-col gap-4">
              <div>
                <div class="text-xs font-semibold mb-2" style="color:#1a1a2e;">Eligibility Check (Art. X Sec. 35)</div>
                <div class="flex flex-col gap-1.5">
                  <?php foreach ($app['eligibility']['checks'] as $check): ?>
                    <div class="flex items-start gap-2 text-xs">
                      <?php if ($check['pass'] === true): ?>
                        <i data-lucide="check-circle" class="w-3.5 h-3.5 flex-shrink-0 mt-0.5" style="color:#22C55E;"></i>
                      <?php elseif ($check['pass'] === false): ?>
                        <i data-lucide="x-circle" class="w-3.5 h-3.5 flex-shrink-0 mt-0.5" style="color:#EF4444;"></i>
                      <?php else: ?>
                        <i data-lucide="minus-circle" class="w-3.5 h-3.5 flex-shrink-0 mt-0.5" style="color:#9CA3AF;"></i>
                      <?php endif; ?>
                      <span><span class="font-medium" style="color:#1a1a2e;"><?= e($check['label']) ?>:</span> <span class="text-gray-500"><?= e($check['detail']) ?></span></span>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>

              <details>
                <summary class="text-xs font-semibold cursor-pointer select-none" style="color:#B11226;">View auto-generated equivalency justification</summary>
                <pre class="justification text-xs text-gray-600 mt-2 p-3 rounded-xl" style="background:#F9FAFB;"><?= e($app['justification']) ?></pre>
              </details>

              <?php if (in_array($app['status'], ['Pending', 'Under Review', 'Evaluation'], true)): ?>
                <div class="review-actions flex flex-col gap-2 pt-3 border-t border-gray-100">
                  <div class="flex items-center gap-2">
                    <button type="button" class="approve-btn flex-1 py-2 rounded-xl text-sm font-semibold text-white transition-all" style="background:#22C55E;">Approve Exemption</button>
                    <button type="button" class="reject-toggle-btn flex-1 py-2 rounded-xl text-sm font-semibold text-white transition-all" style="background:#EF4444;">Deny Exemption</button>
                  </div>
                  <div class="reject-panel hidden flex-col gap-2">
                    <textarea class="reject-remarks dash-input w-full px-3 py-2 rounded-xl border border-gray-200 text-sm focus:outline-none" rows="2" placeholder="Reason for denial (required)"></textarea>
                    <button type="button" class="reject-confirm-btn py-2 rounded-xl text-sm font-semibold text-white transition-all" style="background:#B91C1C;">Confirm Denial</button>
                  </div>
                </div>
              <?php else: ?>
                <div class="text-xs text-gray-400 pt-3 border-t border-gray-100">Decided <?= e(format_date($app['decided_at'])) ?><?= $app['remarks'] ? ' — ' . e($app['remarks']) : '' ?></div>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="lg:hidden flex flex-col gap-2">
        <a href="<?= e(APP_URL) ?>/logout" class="flex items-center justify-center gap-2 py-3 rounded-2xl border border-gray-200 text-sm font-medium text-gray-500">
          <i data-lucide="log-out" class="w-4 h-4"></i> Sign Out
        </a>
      </div>
    </main>
  </div>
</div>

<script>
  var CSRF_TOKEN = <?= json_encode(csrf_token()) ?>;
  var APP_URL = <?= json_encode(APP_URL) ?>;

  lucide.createIcons();
  (function () {
    var bar = document.getElementById('scrollProgressBar');
    var ticking = false;
    function update() {
      var doc = document.documentElement;
      var scrollTop = doc.scrollTop || document.body.scrollTop;
      var height = (doc.scrollHeight - doc.clientHeight) || 1;
      bar.style.width = Math.min(100, (scrollTop / height) * 100) + '%';
      ticking = false;
    }
    window.addEventListener('scroll', function () {
      if (!ticking) { window.requestAnimationFrame(update); ticking = true; }
    });
    update();
  })();

  function showFlash(message, type) {
    var el = document.getElementById('jsFlash');
    var bg = type === 'error' ? '#FEE2E2' : '#DCFCE7';
    var border = type === 'error' ? '#EF4444' : '#22C55E';
    var color = type === 'error' ? '#B91C1C' : '#15803D';
    el.innerHTML = '<div class="rounded-2xl p-4 border text-sm font-medium" style="background:' + bg + '; border-color:' + border + '; color:' + color + ';">' + message + '</div>';
  }

  function reviewAction(applicationId, action, remarks) {
    return fetch(APP_URL + '/applications/review', {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ csrf_token: CSRF_TOKEN, application_id: applicationId, action: action, remarks: remarks || '' }),
    }).then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); });
  }

  document.querySelectorAll('.approve-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (!confirm('Approve this PATHFit exemption? The student will be granted a grade of 1.00 per Art. X Sec. 38.')) return;
      var card = btn.closest('[data-app-id]');
      var appId = parseInt(card.dataset.appId, 10);
      btn.disabled = true;
      reviewAction(appId, 'approve').then(function (res) {
        if (!res.ok) { showFlash(res.data.error || 'Failed to approve.', 'error'); btn.disabled = false; return; }
        showFlash('Exemption approved.', 'success');
        setTimeout(function () { window.location.reload(); }, 600);
      });
    });
  });

  document.querySelectorAll('.reject-toggle-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var panel = btn.closest('.review-actions').querySelector('.reject-panel');
      panel.classList.toggle('hidden');
      panel.classList.toggle('flex');
    });
  });

  document.querySelectorAll('.reject-confirm-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var card = btn.closest('[data-app-id]');
      var appId = parseInt(card.dataset.appId, 10);
      var remarks = card.querySelector('.reject-remarks').value.trim();
      if (!remarks) { showFlash('Please enter a reason for denial.', 'error'); return; }
      btn.disabled = true;
      reviewAction(appId, 'reject', remarks).then(function (res) {
        if (!res.ok) { showFlash(res.data.error || 'Failed to deny.', 'error'); btn.disabled = false; return; }
        showFlash('Exemption denied.', 'success');
        setTimeout(function () { window.location.reload(); }, 600);
      });
    });
  });
</script>
</body>
</html>
