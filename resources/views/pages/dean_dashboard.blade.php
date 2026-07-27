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
<script src="<?= APP_URL ?>/assets/js/shell-scroll.js" defer></script>
<style>
  /* `clip` rather than `hidden`: both stop sideways scrolling, but `hidden`
     makes html/body a scroll container, which silently kills position:sticky
     for every descendant — the top bar just scrolls away with the content.
     `clip` has no scrollport, so the floating bar actually sticks. */
  html, body { overflow-x: clip; scrollbar-width: none; -ms-overflow-style: none; }
  html::-webkit-scrollbar, body::-webkit-scrollbar { display: none; width: 0; height: 0; }
  body { font-family: 'Inter', system-ui, sans-serif; background:#F7F5F2; }
  .dash-input:focus { border-color:#B11226; }
</style>
</head>
<body>
@include('partials.loading_screen')
<div id="scrollProgressBar"></div>

<div class="min-h-screen flex bg-background">
  <!-- Sidebar - desktop only -->
  <aside class="shell-sidebar float-shadow-brand hidden lg:flex flex-col w-64 pt-0 fixed z-30" style="background: linear-gradient(180deg, #B11226 0%, #7a0d1a 100%);">
    <div class="px-6 py-4 border-b border-white/10">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center text-white font-bold text-sm flex-shrink-0"><?= e($initials) ?></div>
        <div class="flex-1 min-w-0">
          <div class="text-white text-sm font-semibold truncate"><?= e($fullName) ?></div>
          <div class="text-red-200 text-xs truncate" title="<?= e($dean['college'] ?? '') ?>"><?= e($dean['college'] ?? 'College Dean') ?></div>
        </div>
      </div>
      <div class="mt-2 inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium" style="background: rgba(212,175,55,0.2); color: #D4AF37;">
        <span class="w-1.5 h-1.5 rounded-full" style="background: #D4AF37;"></span>
        College Dean
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
  <div class="shell-main flex-1 flex flex-col min-h-screen">
    <header id="shellTopbar" class="shell-topbar float-glass float-shadow z-20">
      <div class="flex items-center justify-between px-4 sm:px-6 py-3">
        <div class="flex items-center gap-3">
          <div class="lg:hidden flex items-center gap-2">
            <div class="w-7 h-7 rounded-full bg-white flex items-center justify-center overflow-hidden flex-shrink-0" style="border:1px solid #eee;">
              <img src="<?= APP_URL ?>/assets/images/bsulogo.jpg" alt="BatStateU OCA Logo" class="w-full h-full object-cover">
            </div>
            <span class="font-bold text-sm" style="color:#B11226;">ARTEMIS</span>
          </div>
          <div class="hidden lg:block">
            <h1 class="font-bold text-base" style="color:#1a1a2e;">College Dean Dashboard</h1>
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
      <div id="jsFlash"></div>

      <div class="modern-card rounded-2xl p-5 sm:p-6 text-white" style="background: linear-gradient(135deg, #B11226 0%, #7a0d1a 100%);">
        <h2 class="text-lg sm:text-xl font-bold mb-1">Welcome back, <?= e($dean['first_name']) ?></h2>
        <p class="text-red-100 text-sm">Faculty non-compliance complaints filed against <?= e($dean['college'] ?? 'your college') ?> (WI-OCA-09, Art. V Sec. 15-16).</p>
      </div>

      <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <div class="modern-card bg-white rounded-2xl p-4 sm:p-5 border border-gray-100" style="box-shadow: 0 1px 4px rgba(0,0,0,0.06);">
          <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center mb-3" style="background:#DBEAFE;"><i data-lucide="flag" class="w-4 h-4 sm:w-5 sm:h-5" style="color:#3B82F6;"></i></div>
          <div class="text-xl sm:text-2xl font-bold mb-0.5" style="color:#1a1a2e;"><?= count($complaints) ?></div>
          <div class="text-xs text-gray-500 leading-tight">Total Complaints</div>
        </div>
        <div class="modern-card bg-white rounded-2xl p-4 sm:p-5 border border-gray-100" style="box-shadow: 0 1px 4px rgba(0,0,0,0.06);">
          <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center mb-3" style="background:#FEF9C3;"><i data-lucide="alert-triangle" class="w-4 h-4 sm:w-5 sm:h-5" style="color:#F59E0B;"></i></div>
          <div class="text-xl sm:text-2xl font-bold mb-0.5" style="color:#1a1a2e;"><?= $firstViolationCount ?></div>
          <div class="text-xs text-gray-500 leading-tight">First Violations (Mine to Act On)</div>
        </div>
        <div class="modern-card bg-white rounded-2xl p-4 sm:p-5 border border-gray-100" style="box-shadow: 0 1px 4px rgba(0,0,0,0.06);">
          <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center mb-3" style="background:#FEE2E2;"><i data-lucide="gavel" class="w-4 h-4 sm:w-5 sm:h-5" style="color:#B11226;"></i></div>
          <div class="text-xl sm:text-2xl font-bold mb-0.5" style="color:#1a1a2e;"><?= $repeatedCount ?></div>
          <div class="text-xs text-gray-500 leading-tight">Escalated to Grievance Board</div>
        </div>
        <div class="modern-card bg-white rounded-2xl p-4 sm:p-5 border border-gray-100" style="box-shadow: 0 1px 4px rgba(0,0,0,0.06);">
          <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center mb-3" style="background:#DCFCE7;"><i data-lucide="check-circle" class="w-4 h-4 sm:w-5 sm:h-5" style="color:#22C55E;"></i></div>
          <div class="text-xl sm:text-2xl font-bold mb-0.5" style="color:#1a1a2e;"><?= $resolvedCount ?></div>
          <div class="text-xs text-gray-500 leading-tight">Resolved</div>
        </div>
      </div>

      <div class="flex flex-col gap-4">
        <?php if (!$complaints): ?>
          <div class="modern-card bg-white rounded-2xl border border-gray-100 p-8 text-center text-sm text-gray-400">No complaints have been filed against faculty in your college.</div>
        <?php endif; ?>

        <?php foreach ($complaints as $c): ?>
          <div class="modern-card bg-white rounded-2xl border border-gray-100 overflow-hidden" style="box-shadow: 0 1px 4px rgba(0,0,0,0.06);" data-complaint-id="<?= (int) $c['complaint_id'] ?>">
            <div class="flex flex-wrap items-center justify-between gap-2 px-5 py-3.5 border-b border-gray-100">
              <div>
                <div class="font-semibold text-sm" style="color:#1a1a2e;"><?= e($c['faculty_name']) ?></div>
                <div class="text-xs text-gray-500"><?= e($c['rpag_group'] ?: 'No RPAG group specified') ?> &middot; Filed <?= e(format_date($c['created_at'])) ?></div>
              </div>
              <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium" style="background:<?= $c['escalation_level'] === 'Repeated Violation' ? '#FEE2E2' : '#FEF9C3' ?>; color:<?= $c['escalation_level'] === 'Repeated Violation' ? '#DC2626' : '#B45309' ?>;"><?= e($c['escalation_level']) ?></span>
                <span class="complaint-status-badge inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium" style="background:<?= $c['status'] === 'Resolved' ? '#DCFCE7' : '#DBEAFE' ?>; color:<?= $c['status'] === 'Resolved' ? '#16A34A' : '#1D4ED8' ?>;"><?= e($c['status']) ?></span>
              </div>
            </div>

            <div class="p-5 flex flex-col gap-3">
              <div>
                <div class="text-xs font-semibold mb-1" style="color:#1a1a2e;">Description of the Incident</div>
                <p class="text-sm text-gray-600"><?= e($c['description']) ?></p>
              </div>
              <div class="text-xs p-3 rounded-xl" style="background:#F9FAFB; color:#4B5563;">
                <span class="font-semibold" style="color:#1a1a2e;">Recommended action:</span> <?= e($c['recommended_action']) ?>
              </div>

              <?php if ($c['dean_actionable']): ?>
                <div class="flex flex-wrap items-center gap-2 pt-3 border-t border-gray-100">
                  <label class="text-xs font-medium text-gray-500">Update status:</label>
                  <select class="dean-status-select dash-input px-3 py-2 rounded-xl border border-gray-200 text-sm focus:outline-none bg-white">
                    <?php foreach (['Submitted', 'Dean Review', 'Written Warning Issued', 'Resolved'] as $s): ?>
                      <option value="<?= e($s) ?>" <?= $s === $c['status'] ? 'selected' : '' ?> <?= $s === 'Submitted' ? 'disabled' : '' ?>><?= e($s) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <button type="button" class="dean-status-save-btn px-4 py-2 rounded-xl text-sm font-semibold text-white transition-all" style="background:#B11226;">Save</button>
                </div>
              <?php elseif ($c['escalation_level'] === 'Repeated Violation'): ?>
                <div class="text-xs text-gray-400 pt-3 border-t border-gray-100">This is a repeated violation — outside the College Dean's authority. It has been escalated to a Grievance Board chaired by the Head of HRMO.</div>
              <?php else: ?>
                <div class="text-xs text-gray-400 pt-3 border-t border-gray-100">Resolved.</div>
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

  function showFlash(message, type) {
    var el = document.getElementById('jsFlash');
    var bg = type === 'error' ? '#FEE2E2' : '#DCFCE7';
    var border = type === 'error' ? '#EF4444' : '#22C55E';
    var color = type === 'error' ? '#B91C1C' : '#15803D';
    el.innerHTML = '<div class="rounded-2xl p-4 border text-sm font-medium" style="background:' + bg + '; border-color:' + border + '; color:' + color + ';">' + message + '</div>';
  }

  document.querySelectorAll('.dean-status-save-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var card = btn.closest('[data-complaint-id]');
      var id = parseInt(card.dataset.complaintId, 10);
      var status = card.querySelector('.dean-status-select').value;
      btn.disabled = true;
      fetch(APP_URL + '/faculty-complaints/update', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ csrf_token: CSRF_TOKEN, action: 'update_status', complaint_id: id, status: status }),
      }).then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); })
        .then(function (res) {
          btn.disabled = false;
          if (!res.ok) { showFlash(res.data.error || 'Failed to update status.', 'error'); return; }
          showFlash('Status updated to ' + status + '.', 'success');
          setTimeout(function () { window.location.reload(); }, 600);
        });
    });
  });
</script>
</body>
</html>
