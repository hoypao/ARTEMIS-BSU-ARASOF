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
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Fraunces:opsz,wght@9..144,600;9..144,700;9..144,800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/modern.css">
<style>
  html, body { overflow-x: hidden; }
  body { font-family: 'Inter', system-ui, sans-serif; background:#F7F5F2; }
  .adm-input:focus { border-color:#B11226; }
  h1, h2 { font-family: 'Fraunces', Georgia, serif; letter-spacing: -0.01em; }

  /* Sidebar nav — smooth hover + left accent bar on the active item */
  .admin-nav-btn, .admin-module-btn { position: relative; transition: background 0.25s var(--ease-out-soft), color 0.25s var(--ease-out-soft), padding-left 0.25s var(--ease-out-soft); }
  .admin-nav-btn::before, .admin-module-btn::before { content: ''; position: absolute; left: -4px; top: 50%; transform: translateY(-50%); width: 3px; height: 0; border-radius: 999px; background: #D4AF37; transition: height 0.25s var(--ease-out-soft); }
  .admin-nav-btn[data-active="true"]::before { height: 60%; }
  .admin-nav-btn:not([data-active="true"]):hover, .admin-module-btn:hover { background: rgba(255,255,255,0.1) !important; padding-left: 14px; }

  /* Modal pop-in — replays automatically each time an element goes from display:none to visible */
  @keyframes modalBackdropIn { from { opacity: 0; } to { opacity: 1; } }
  @keyframes modalPanelIn { from { opacity: 0; transform: translateY(16px) scale(0.98); } to { opacity: 1; transform: translateY(0) scale(1); } }
  .modal-backdrop-anim { animation: modalBackdropIn 0.25s ease-out both; }
  .modal-panel-anim { animation: modalPanelIn 0.3s var(--ease-out-soft) both; }

  @media (prefers-reduced-motion: reduce) {
    .modal-backdrop-anim, .modal-panel-anim { animation: none !important; }
  }

  /* "Jump to me" highlight for notification-triggered navigation — a brief brand-red
     pulse that fades out on its own, instead of a flat color swap. */
  @keyframes notifHighlightCard {
    0%   { background-color: rgba(177,18,38,0.09); box-shadow: 0 0 0 3px rgba(177,18,38,0.28), 0 8px 24px rgba(177,18,38,0.14); }
    65%  { background-color: rgba(177,18,38,0.05); box-shadow: 0 0 0 3px rgba(177,18,38,0.14), 0 8px 24px rgba(177,18,38,0.08); }
    100% { background-color: transparent; box-shadow: 0 0 0 0 rgba(177,18,38,0); }
  }
  @keyframes notifHighlightRow {
    0%   { background-color: rgba(177,18,38,0.09); box-shadow: inset 3px 0 0 #B11226; }
    65%  { background-color: rgba(177,18,38,0.05); box-shadow: inset 3px 0 0 #B11226; }
    100% { background-color: transparent; box-shadow: inset 3px 0 0 rgba(177,18,38,0); }
  }
  .notif-highlight-card { animation: notifHighlightCard 2.4s var(--ease-out-soft) 1 both; }
  .notif-highlight-row  { animation: notifHighlightRow 2.4s var(--ease-out-soft) 1 both; }
  @media (prefers-reduced-motion: reduce) {
    .notif-highlight-card, .notif-highlight-row { animation: none !important; background-color: rgba(177,18,38,0.08) !important; }
  }
</style>
</head>
<body>
@include('partials.loading_screen')

<div class="min-h-screen flex bg-background">
  <!-- Desktop Sidebar -->
  <aside class="hidden lg:flex flex-col w-64 min-h-screen fixed left-0 top-0 bottom-0 z-30" style="background: linear-gradient(180deg, #B11226 0%, #7a0d1a 100%);">
    <div class="px-6 py-5 border-b border-white/10">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center text-white font-bold text-sm">OA</div>
        <div><div class="text-white text-sm font-semibold">OCA Administrator</div><div class="text-red-200 text-xs">Admin &middot; OCA Head</div></div>
      </div>
    </div>
    <nav class="flex-1 px-4 py-4 flex flex-col gap-1 overflow-y-auto">
      <button data-section="dashboard" data-active="true" class="admin-nav-btn flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm transition-all text-left" style="background: rgba(255,255,255,0.2); color:#fff; font-weight:600;"><i data-lucide="layout-dashboard" class="w-4 h-4"></i> Dashboard</button>
      <button data-section="applications" class="admin-nav-btn flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm transition-all text-left" style="color: rgba(255,255,255,0.7);"><i data-lucide="file-text" class="w-4 h-4"></i> Applications</button>
      <button data-section="trainers" class="admin-nav-btn flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm transition-all text-left" style="color: rgba(255,255,255,0.7);"><i data-lucide="graduation-cap" class="w-4 h-4"></i> Trainers</button>
      <button data-section="talent" class="admin-nav-btn flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm transition-all text-left" style="color: rgba(255,255,255,0.7);"><i data-lucide="target" class="w-4 h-4"></i> Talent Match</button>
      <button data-section="compliance" class="admin-nav-btn flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm transition-all text-left" style="color: rgba(255,255,255,0.7);"><i data-lucide="shield-alert" class="w-4 h-4"></i> Compliance</button>
      <button data-section="kpi" class="admin-nav-btn flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm transition-all text-left" style="color: rgba(255,255,255,0.7);"><i data-lucide="trending-up" class="w-4 h-4"></i> QEO KPI</button>
      <button data-section="events" class="admin-nav-btn flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm transition-all text-left" style="color: rgba(255,255,255,0.7);"><i data-lucide="calendar" class="w-4 h-4"></i> Events</button>
      <button data-section="announcements" class="admin-nav-btn flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm transition-all text-left" style="color: rgba(255,255,255,0.7);"><i data-lucide="bell" class="w-4 h-4"></i> Announcements</button>
      <button data-section="reports" class="admin-nav-btn flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm transition-all text-left" style="color: rgba(255,255,255,0.7);"><i data-lucide="bar-chart-3" class="w-4 h-4"></i> Reports</button>
      <button data-section="settings" class="admin-nav-btn flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm transition-all text-left" style="color: rgba(255,255,255,0.7);"><i data-lucide="settings" class="w-4 h-4"></i> Settings</button>

      <div class="mt-3 mb-1 px-3"><span class="text-xs uppercase tracking-wider text-red-300">Application Modules</span></div>
      <?php foreach ($modules as $mod): ?>
        <button data-module="<?= e($mod['code']) ?>" class="admin-module-btn flex items-center gap-3 px-3 py-2 rounded-xl text-sm transition-all text-left" style="color: rgba(255,255,255,0.6);">
          <i data-lucide="<?= e($mod['icon']) ?>" class="w-3.5 h-3.5"></i> <span class="text-xs"><?= e($mod['label']) ?></span>
        </button>
      <?php endforeach; ?>
    </nav>
    <div class="px-4 pb-4 border-t border-white/10 pt-4">
      <a href="<?= e(APP_URL) ?>/logout" class="flex items-center gap-2 px-3 py-2.5 rounded-xl text-sm transition-all w-full hover:bg-white/10" style="color: rgba(255,255,255,0.6);"><i data-lucide="log-out" class="w-4 h-4"></i> Sign Out</a>
    </div>
  </aside>

  <!-- Main -->
  <div class="flex-1 lg:ml-64 flex flex-col min-h-screen">
    <header class="sticky top-0 z-20 bg-white border-b border-gray-100" style="box-shadow: 0 1px 4px rgba(0,0,0,0.06);">
      <div class="flex items-center justify-between px-4 sm:px-6 py-3">
        <div class="flex items-center gap-3">
          <div class="lg:hidden flex items-center gap-2">
            <div class="w-7 h-7 rounded-full bg-white flex items-center justify-center overflow-hidden flex-shrink-0" style="border:1px solid #eee;"><img src="<?= APP_URL ?>/assets/images/bsulogo.jpg" alt="BatStateU OCA Logo" class="w-full h-full object-cover"></div>
            <span class="font-bold text-sm" style="color:#B11226;">ARTEMIS</span>
          </div>
          <div class="hidden lg:block">
            <h1 class="font-bold text-base" style="color:#1a1a2e;" id="pageTitleDesktop">Admin Dashboard</h1>
            <p class="text-xs text-gray-500">OCA — BatStateU ARASOF-Nasugbu &middot; <?= e(date('F j, Y')) ?></p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <div class="relative hidden md:block">
            <label for="searchInput" class="sr-only">Search students or applications</label>
            <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-600"></i>
            <input id="searchInput" placeholder="Search students, apps..." class="adm-input modern-input pl-9 pr-4 py-2 rounded-xl bg-gray-50 border border-gray-200 text-sm focus:outline-none w-52">
          </div>
          <div class="relative">
            <button type="button" id="notifBtn" class="relative w-10 h-10 flex items-center justify-center rounded-xl bg-gray-50 border border-gray-200">
              <i data-lucide="bell" class="w-4 h-4 text-gray-600"></i>
              <?php if ($notifItems): ?><span id="notifBadge" class="absolute -top-0.5 -right-0.5 w-4 h-4 rounded-full text-white flex items-center justify-center" style="background:#B11226; font-size:9px;"><?= count($notifItems) ?></span><?php endif; ?>
            </button>
            <div id="notifDropdown" class="hidden absolute top-10 right-0 w-72 max-w-[calc(100vw-2rem)] bg-white rounded-2xl border border-gray-100 z-50 overflow-hidden" style="box-shadow: 0 16px 40px rgba(0,0,0,0.14);">
              <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                <span class="font-semibold text-sm" style="color:#1a1a2e;">Notifications</span>
                <div class="flex items-center gap-2">
                  <button type="button" id="markAllReadBtn" class="text-xs hover:opacity-70 transition-opacity" style="color:#B11226;">Mark all read</button>
                  <button type="button" id="notifCloseBtn" aria-label="Close notifications"><i data-lucide="x" class="w-4 h-4 text-gray-600"></i></button>
                </div>
              </div>
              <?php if (!$notifItems): ?><div class="px-4 py-6 text-center text-xs text-gray-600">You're all caught up.</div><?php endif; ?>
              <?php foreach ($notifItems as $i => $n): ?>
                <div class="notif-item px-4 py-3 border-b border-gray-50 flex gap-3 cursor-pointer transition-colors hover:bg-gray-100"
                  data-idx="<?= $i ?>" data-section="<?= e($n['section']) ?>" data-filter="<?= e($n['filter']) ?>" data-module="<?= e($n['module']) ?>" data-app-code="<?= e($n['appCode']) ?>" style="background:#FAFBFF;">
                  <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0" style="background:<?= e($n['color']) ?>20;"><i data-lucide="<?= e($n['icon']) ?>" class="w-4 h-4" style="color:<?= e($n['color']) ?>;"></i></div>
                  <div class="flex-1 min-w-0"><div class="text-xs font-medium leading-snug notif-msg" style="color:#1a1a2e;"><?= e($n['msg']) ?></div><div class="text-xs text-gray-400 mt-0.5"><?= e($n['time'] ?? '') ?></div></div>
                  <div class="notif-dot w-2 h-2 rounded-full flex-shrink-0 mt-1" style="background:#B11226;"></div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
      <div class="lg:hidden px-4 pb-2.5">
        <p class="font-bold text-sm" style="color:#1a1a2e;" id="pageTitleMobile">Admin Dashboard</p>
        <p class="text-xs text-gray-600">OCA &middot; <?= e(date('F j, Y')) ?></p>
      </div>
    </header>

    <main class="flex-1 p-4 sm:p-6 lg:p-8 pb-24 lg:pb-8">

      <!-- DASHBOARD -->
      <div class="admin-section flex flex-col gap-6" data-section="dashboard">
        <div class="rounded-2xl p-4 sm:p-6 text-white relative overflow-hidden" style="background: linear-gradient(135deg, #B11226 0%, #7a0d1a 100%);">
          <div class="absolute right-0 top-0 bottom-0 opacity-5"><i data-lucide="palette" class="w-64 h-64 -mt-8 -mr-8"></i></div>
          <div class="relative">
            <div class="text-red-200 text-sm mb-1">Good day,</div>
            <h2 class="text-2xl font-bold text-white">OCA Administrator</h2>
            <p class="text-red-100 text-sm mt-1">There are <span class="font-semibold text-yellow-300"><?= (int) $statusCounts['Pending'] ?> pending</span> applications requiring your attention.</p>
            <button type="button" data-section-link="applications" class="admin-nav-jump modern-btn mt-4 px-4 py-2 rounded-xl text-sm font-semibold flex items-center gap-2 w-fit transition-opacity hover:opacity-90" style="background:#D4AF37; color:#1a1a2e;">Review Applications <i data-lucide="chevron-down" class="w-4 h-4" style="transform:rotate(-90deg);"></i></button>
          </div>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
          <div class="modern-card bg-white rounded-2xl p-3 sm:p-5 border border-gray-100" style="box-shadow: 0 1px 6px rgba(0,0,0,0.06);">
            <div class="flex items-center justify-between mb-2 sm:mb-3"><div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center" style="background:#DBEAFE;"><i data-lucide="file-text" class="w-4 h-4 sm:w-5 sm:h-5" style="color:#3B82F6;"></i></div></div>
            <div class="count-up text-xl sm:text-2xl font-bold mb-0.5" style="color:#1a1a2e;" data-count="<?= (int) $statTotal ?>">0</div><div class="text-[10px] sm:text-xs text-gray-500 leading-tight">Total Applications</div>
          </div>
          <div class="modern-card bg-white rounded-2xl p-3 sm:p-5 border border-gray-100" style="box-shadow: 0 1px 6px rgba(0,0,0,0.06);">
            <div class="flex items-center justify-between mb-2 sm:mb-3"><div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center" style="background:#FEF9C3;"><i data-lucide="clock" class="w-4 h-4 sm:w-5 sm:h-5" style="color:#F59E0B;"></i></div></div>
            <div class="count-up text-xl sm:text-2xl font-bold mb-0.5" style="color:#1a1a2e;" data-count="<?= (int) $statPending ?>">0</div><div class="text-[10px] sm:text-xs text-gray-500 leading-tight">Pending Review</div>
          </div>
          <div class="modern-card bg-white rounded-2xl p-3 sm:p-5 border border-gray-100" style="box-shadow: 0 1px 6px rgba(0,0,0,0.06);">
            <div class="flex items-center justify-between mb-2 sm:mb-3"><div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center" style="background:#DCFCE7;"><i data-lucide="check-circle" class="w-4 h-4 sm:w-5 sm:h-5" style="color:#22C55E;"></i></div></div>
            <div class="count-up text-xl sm:text-2xl font-bold mb-0.5" style="color:#1a1a2e;" data-count="<?= (int) $statApproved ?>">0</div><div class="text-[10px] sm:text-xs text-gray-500 leading-tight">Approved</div>
          </div>
          <div class="modern-card bg-white rounded-2xl p-3 sm:p-5 border border-gray-100" style="box-shadow: 0 1px 6px rgba(0,0,0,0.06);">
            <div class="flex items-center justify-between mb-2 sm:mb-3"><div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center" style="background:#FEE2E2;"><i data-lucide="x-circle" class="w-4 h-4 sm:w-5 sm:h-5" style="color:#EF4444;"></i></div></div>
            <div class="count-up text-xl sm:text-2xl font-bold mb-0.5" style="color:#1a1a2e;" data-count="<?= (int) $statRejected ?>">0</div><div class="text-[10px] sm:text-xs text-gray-500 leading-tight">Rejected</div>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <div class="modern-card bg-white rounded-2xl border border-gray-100 p-5" style="box-shadow: 0 1px 6px rgba(0,0,0,0.06);">
            <div class="flex items-start justify-between gap-2 mb-4">
              <div><h3 class="font-semibold text-sm" style="color:#1a1a2e;">Monthly Submissions</h3><p class="text-xs text-gray-600 mt-0.5"><?= e($monthlyData[0]['month']) ?> &ndash; <?= e($monthlyData[count($monthlyData) - 1]['month']) ?> <?= date('Y') ?></p></div>
              <div class="flex items-center gap-2.5 text-xs text-gray-500 flex-shrink-0"><span class="flex items-center gap-1"><span class="w-2 h-2 rounded-sm inline-block" style="background:#64748B;"></span>Sub</span><span class="flex items-center gap-1"><span class="w-2 h-2 rounded-sm inline-block" style="background:#22C55E;"></span>Apv</span></div>
            </div>
            <div style="height:180px;"><canvas id="monthlyChart"></canvas></div>
          </div>
          <div class="modern-card bg-white rounded-2xl border border-gray-100 p-5" style="box-shadow: 0 1px 6px rgba(0,0,0,0.06);">
            <h3 class="font-semibold text-sm" style="color:#1a1a2e;">Application Status</h3><p class="text-xs text-gray-600 mt-0.5 mb-4">Current distribution</p>
            <div class="flex items-center gap-4">
              <div style="width:150px;height:150px;"><canvas id="statusDonut"></canvas></div>
              <div class="flex flex-col gap-2 flex-1">
                <?php foreach ([['Approved','#22C55E'],['Pending','#F59E0B'],['Under Review','#3B82F6'],['Evaluation','#A855F7'],['Rejected','#EF4444']] as [$label, $color]): ?>
                  <div class="flex items-center justify-between"><div class="flex items-center gap-2"><span class="w-2 h-2 rounded-full flex-shrink-0" style="background:<?= e($color) ?>;"></span><span class="text-xs text-gray-600"><?= e($label) ?></span></div><span class="text-xs font-semibold" style="color:#1a1a2e;"><?= (int) $statusCounts[$label] ?></span></div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
          <div class="modern-card xl:col-span-2 bg-white rounded-2xl border border-gray-100 overflow-hidden" style="box-shadow: 0 1px 6px rgba(0,0,0,0.06);">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
              <h3 class="font-semibold text-sm" style="color:#1a1a2e;">Recent Applications</h3>
              <button type="button" data-section-link="applications" class="admin-nav-jump text-xs flex items-center gap-1 hover:opacity-80" style="color:#B11226;">View All &rarr;</button>
            </div>
            <div class="sm:hidden flex flex-col divide-y divide-gray-50">
              <?php foreach (array_slice($applications, 0, 5) as $app): $nameInit = strtoupper(mb_substr($app['first_name'],0,1) . mb_substr($app['last_name'],0,1)); ?>
                <div class="px-4 py-3 flex items-center gap-3">
                  <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0" style="background:#B11226;"><?= e($nameInit) ?></div>
                  <div class="flex-1 min-w-0"><div class="text-xs font-semibold truncate" style="color:#1a1a2e;"><?= e($app['first_name'] . ' ' . $app['last_name']) ?></div><div class="text-xs text-gray-600 truncate"><?= e($app['type_name']) ?></div></div>
                  <div class="flex items-center gap-2 flex-shrink-0"><?= status_badge_html($app['status']) ?><button type="button" class="view-app-btn p-1.5 rounded-lg" style="background:#DBEAFE; color:#2563EB;" data-app="<?= e($app['application_code']) ?>" aria-label="View application"><i data-lucide="eye" class="w-3.5 h-3.5"></i></button></div>
                </div>
              <?php endforeach; ?>
            </div>
            <div class="hidden sm:block overflow-x-auto">
              <table class="w-full text-sm">
                <thead><tr style="background:#F9FAFB;"><th class="text-left px-4 py-3 text-xs font-medium text-gray-500">App ID</th><th class="text-left px-4 py-3 text-xs font-medium text-gray-500">Student</th><th class="text-left px-4 py-3 text-xs font-medium text-gray-500">Type</th><th class="text-left px-4 py-3 text-xs font-medium text-gray-500">Status</th><th class="text-left px-4 py-3 text-xs font-medium text-gray-500">Action</th></tr></thead>
                <tbody>
                  <?php foreach (array_slice($applications, 0, 5) as $app): ?>
                    <tr class="border-t border-gray-50 hover:bg-gray-50 transition-colors">
                      <td class="px-4 py-3 text-xs font-mono text-gray-500"><?= e($app['application_code']) ?></td>
                      <td class="px-4 py-3"><div class="text-xs font-medium" style="color:#1a1a2e;"><?= e($app['first_name'] . ' ' . $app['last_name']) ?></div><div class="text-xs text-gray-600"><?= e($app['id_number'] ?? '') ?></div></td>
                      <td class="px-4 py-3 text-xs text-gray-600"><?= e($app['type_name']) ?></td>
                      <td class="px-4 py-3"><?= status_badge_html($app['status']) ?></td>
                      <td class="px-4 py-3"><button type="button" class="view-app-btn text-xs px-2.5 py-1.5 rounded-lg hover:bg-red-50 transition-colors" style="color:#B11226;" data-app="<?= e($app['application_code']) ?>" aria-label="View application"><i data-lucide="eye" class="w-3.5 h-3.5"></i></button></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>

          <div class="flex flex-col gap-4">
            <div class="modern-card bg-white rounded-2xl border border-gray-100 p-5" style="box-shadow: 0 1px 6px rgba(0,0,0,0.06);">
              <h3 class="font-semibold text-sm mb-4" style="color:#1a1a2e;">Application Modules</h3>
              <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                <?php foreach ($modules as $mod): ?>
                  <button type="button" class="admin-module-jump flex flex-col items-center gap-1.5 p-3 rounded-xl hover:bg-red-50 transition-all border border-gray-100" data-module="<?= e($mod['code']) ?>">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background:#FEE2E2;"><i data-lucide="<?= e($mod['icon']) ?>" class="w-4 h-4" style="color:#B11226;"></i></div>
                    <span class="text-xs text-gray-600 text-center leading-tight"><?= e($mod['label']) ?></span>
                  </button>
                <?php endforeach; ?>
              </div>
            </div>
            <div class="modern-card bg-white rounded-2xl border border-gray-100 p-5" style="box-shadow: 0 1px 6px rgba(0,0,0,0.06);">
              <h3 class="font-semibold text-sm mb-4" style="color:#1a1a2e;">Status Overview</h3>
              <?php foreach ([['Approved','#22C55E'],['Pending','#F59E0B'],['Under Review','#3B82F6'],['Evaluation','#A855F7'],['Rejected','#EF4444']] as [$label, $color]):
                  $count = $statusCounts[$label]; $pct = $statTotal > 0 ? ($count / $statTotal) * 100 : 0; ?>
                <div class="mb-3">
                  <div class="flex items-center justify-between mb-1"><span class="text-xs text-gray-600"><?= e($label) ?></span><span class="text-xs font-semibold" style="color:#1a1a2e;"><?= $count ?></span></div>
                  <div class="h-2 bg-gray-100 rounded-full overflow-hidden"><div class="h-full rounded-full" style="background:<?= e($color) ?>; width:<?= $pct ?>%;"></div></div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>

      <!-- APPLICATIONS -->
      <div class="admin-section hidden flex-col gap-6" data-section="applications">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div><h2 class="sr-only" id="appsSectionTitle">Applications</h2><p class="text-xs text-gray-500" id="appsCount"><?= $statTotal ?> applications found</p></div>
          <div class="flex items-center gap-2 flex-wrap">
            <button type="button" id="clearModuleBtn" class="hidden items-center gap-1 px-3 py-2 rounded-xl border border-red-200 bg-red-50 text-xs" style="color:#B11226;"><i data-lucide="x" class="w-3 h-3"></i> Clear Filter</button>
            <div class="flex items-center gap-1 bg-white rounded-xl border border-gray-200 px-3 py-2">
              <label for="statusFilterSelect" class="sr-only">Filter by status</label>
              <i data-lucide="filter" class="w-3.5 h-3.5 text-gray-600"></i>
              <select id="statusFilterSelect" class="text-xs text-gray-600 focus:outline-none bg-transparent">
                <?php foreach (['All','Pending','Under Review','Evaluation','Approved','Rejected'] as $s): ?><option><?= e($s) ?></option><?php endforeach; ?>
              </select>
            </div>
            <button type="button" id="exportCsvBtn" class="flex items-center gap-1.5 px-3 py-2 rounded-xl border border-gray-200 bg-white text-xs text-gray-600 hover:bg-gray-50 transition-colors"><i data-lucide="download" class="w-3.5 h-3.5"></i> Export CSV</button>
          </div>
        </div>

        <div class="relative md:hidden">
          <label for="searchInputMobile" class="sr-only">Search students or applications</label>
          <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-600"></i>
          <input id="searchInputMobile" placeholder="Search students or apps..." class="adm-input modern-input w-full pl-9 pr-4 py-2.5 rounded-xl bg-white border border-gray-200 text-sm focus:outline-none">
        </div>

        <div id="appsMobileList" class="sm:hidden flex flex-col gap-3"></div>
        <div class="modern-card hidden sm:block bg-white rounded-2xl border border-gray-100 overflow-hidden" style="box-shadow: 0 1px 6px rgba(0,0,0,0.06);">
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead><tr style="background:#F9FAFB;"><th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500">App ID</th><th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500">Student Name</th><th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500">Student ID</th><th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500">Type</th><th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500">Date</th><th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500">Status</th><th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500">Actions</th></tr></thead>
              <tbody id="appsTableBody"></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- TRAINERS -->
      <div class="admin-section hidden flex-col gap-6" data-section="trainers">
        <div><span class="text-[10px] font-semibold uppercase tracking-wider block mb-1" style="color:#B11226;">Trainer Evaluation</span><h2 class="font-bold text-base sm:text-lg" style="color:#1a1a2e;">Trainer Level Equivalency Engine</h2><p class="text-xs text-gray-500 mt-0.5">Rubric-based level recommendation (Art. VI Sec. 17-B) plus an honorarium advisor (Sec. 17-B.7, 9&ndash;10).</p></div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <div class="modern-card bg-white rounded-2xl border border-gray-100 p-5" style="box-shadow: 0 1px 6px rgba(0,0,0,0.06);">
            <h3 class="font-semibold text-sm mb-4" style="color:#1a1a2e;">New Evaluation</h3>
            <div class="flex flex-col gap-3">
              <div><label for="trainerNameInput" class="text-xs text-gray-600 block mb-1">Trainer Name</label><input id="trainerNameInput" class="adm-input modern-input w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none" placeholder="e.g. Juan Dela Cruz"></div>
              <div>
                <label for="trainerDisciplineInput" class="text-xs text-gray-600 block mb-1">Discipline</label>
                <input id="trainerDisciplineInput" list="trainerDisciplineList" class="adm-input modern-input w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none" placeholder="e.g. Folkloric Dance (Diwayanis Dance Theater)">
                <datalist id="trainerDisciplineList"><?php foreach ($talentCategoriesList as $c): ?><option value="<?= e($c['name']) ?>"><?php endforeach; ?></datalist>
              </div>
              <?php foreach (ARTEMIS_TRAINER_RUBRIC_CRITERIA as $key => $label): ?>
              <div>
                <div class="flex items-center justify-between mb-1"><label for="trainerScore_<?= e($key) ?>" class="text-xs text-gray-500"><?= e($label) ?></label><span class="text-xs font-semibold" style="color:#B11226;" id="trainerScoreVal_<?= e($key) ?>">3</span></div>
                <input type="range" min="1" max="5" value="3" id="trainerScore_<?= e($key) ?>" class="trainer-score-slider w-full" data-key="<?= e($key) ?>">
              </div>
              <?php endforeach; ?>
              <button type="button" id="evaluateTrainerBtn" class="modern-btn mt-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-white hover:opacity-90 transition-opacity flex items-center justify-center gap-2" style="background:#B11226;"><i data-lucide="calculator" class="w-4 h-4"></i> Compute Recommended Level</button>
              <div id="trainerResultBox" class="hidden bg-red-50 border border-red-100 rounded-xl p-3 text-xs" style="color:#7a0d1a;"></div>
            </div>
          </div>

          <div class="modern-card bg-white rounded-2xl border border-gray-100 p-5" style="box-shadow: 0 1px 6px rgba(0,0,0,0.06);">
            <h3 class="font-semibold text-sm mb-4" style="color:#1a1a2e;">Qualification Reference (Art. VI Sec. 17-A)</h3>
            <div class="flex flex-col gap-2 text-xs">
              <?php foreach (ARTEMIS_TRAINER_LEVELS as $name => $level): ?>
              <div class="border border-gray-100 rounded-xl p-3">
                <div class="flex items-center justify-between"><span class="font-semibold" style="color:#1a1a2e;"><?= e($name) ?></span><span class="text-gray-600">SG <?= (int) $level['salary_grade'] ?> &middot; Score <?= (int) $level['min_score'] ?>&ndash;<?= (int) $level['max_score'] ?></span></div>
                <p class="text-gray-500 mt-1"><?= e($level['education']) ?> &middot; <?= e($level['experience']) ?></p>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <div class="modern-card bg-white rounded-2xl border border-gray-100 overflow-hidden" style="box-shadow: 0 1px 6px rgba(0,0,0,0.06);">
          <div class="px-5 py-4 border-b border-gray-100"><h3 class="font-semibold text-sm" style="color:#1a1a2e;">Evaluation History &amp; Honorarium Advisor</h3><p class="text-xs text-gray-600 mt-0.5">Enter the current DBM salary-grade hourly rate and hours rendered (per DTR) to compute pay for the trainer's contract of service (Art. VI Sec. 17-B.7, 9&ndash;10).</p></div>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead><tr style="background:#F9FAFB;"><th class="text-left px-4 py-3 text-xs font-semibold text-gray-500">Trainer</th><th class="text-left px-4 py-3 text-xs font-semibold text-gray-500">Discipline</th><th class="text-left px-4 py-3 text-xs font-semibold text-gray-500">Score</th><th class="text-left px-4 py-3 text-xs font-semibold text-gray-500">Level</th><th class="text-left px-4 py-3 text-xs font-semibold text-gray-500">Hourly Rate</th><th class="text-left px-4 py-3 text-xs font-semibold text-gray-500">Hours</th><th class="text-left px-4 py-3 text-xs font-semibold text-gray-500">Honorarium</th><th class="text-left px-4 py-3 text-xs font-semibold text-gray-500">Action</th></tr></thead>
              <tbody id="trainerEvalTableBody"></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- TALENT MATCH -->
      <div class="admin-section hidden flex-col gap-6" data-section="talent">
        <div><span class="text-[10px] font-semibold uppercase tracking-wider block mb-1" style="color:#B11226;">Talent Match</span><h2 class="font-bold text-base sm:text-lg" style="color:#1a1a2e;">RPAG / Discipline Recommender</h2><p class="text-xs text-gray-500 mt-0.5">Ranks each performer's recorded talents by proficiency, active membership, and tenure to suggest their best-fit Resident Performing Arts Group (Art. IV Sec. 12).</p></div>
        <div id="talentMatchList" class="flex flex-col gap-3"></div>
      </div>

      <!-- COMPLIANCE -->
      <div class="admin-section hidden flex-col gap-6" data-section="compliance">
        <div><span class="text-[10px] font-semibold uppercase tracking-wider block mb-1" style="color:#B11226;">Compliance</span><h2 class="font-bold text-base sm:text-lg" style="color:#1a1a2e;">Faculty Non-Compliance Pattern Flag</h2><p class="text-xs text-gray-500 mt-0.5">Logging a complaint against a faculty member already on file auto-escalates from a Dean's written warning to a Grievance Board referral (WI-OCA-09).</p></div>

        <div class="modern-card bg-white rounded-2xl border border-gray-100 p-5" style="box-shadow: 0 1px 6px rgba(0,0,0,0.06);">
          <h3 class="font-semibold text-sm mb-4" style="color:#1a1a2e;">Log a Complaint</h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div><label for="complaintFacultyInput" class="text-xs text-gray-600 block mb-1">Faculty Name</label><input id="complaintFacultyInput" class="adm-input modern-input w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none" placeholder="e.g. Prof. Dela Cruz"></div>
            <div><label for="complaintCollegeInput" class="text-xs text-gray-600 block mb-1">College</label><input id="complaintCollegeInput" class="adm-input modern-input w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none" placeholder="e.g. College of Informatics and Computing Sciences"></div>
            <div class="sm:col-span-2">
              <label for="complaintGroupInput" class="text-xs text-gray-600 block mb-1">RPAG Group (optional)</label>
              <input id="complaintGroupInput" list="complaintGroupList" class="adm-input modern-input w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none" placeholder="e.g. Choir (Adlibitum Chorus)">
              <datalist id="complaintGroupList"><?php foreach ($talentCategoriesList as $c): ?><option value="<?= e($c['name']) ?>"><?php endforeach; ?></datalist>
            </div>
            <div class="sm:col-span-2"><label for="complaintDescriptionInput" class="text-xs text-gray-600 block mb-1">Description of the Incident</label><textarea id="complaintDescriptionInput" rows="3" class="adm-input modern-input w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none resize-none" placeholder="Describe the non-compliance with the academic support policy (Art. V Sec. 15-16)..."></textarea></div>
          </div>
          <button type="button" id="logComplaintBtn" class="modern-btn mt-3 px-4 py-2.5 rounded-xl text-sm font-semibold text-white hover:opacity-90 transition-opacity flex items-center justify-center gap-2" style="background:#B11226;"><i data-lucide="flag" class="w-4 h-4"></i> Log Complaint</button>
          <div id="complaintResultBox" class="hidden mt-3 bg-red-50 border border-red-100 rounded-xl p-3 text-xs" style="color:#7a0d1a;"></div>
        </div>

        <div class="modern-card bg-white rounded-2xl border border-gray-100 overflow-hidden" style="box-shadow: 0 1px 6px rgba(0,0,0,0.06);">
          <div class="px-5 py-4 border-b border-gray-100"><h3 class="font-semibold text-sm" style="color:#1a1a2e;">Complaint Log</h3></div>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead><tr style="background:#F9FAFB;"><th class="text-left px-4 py-3 text-xs font-semibold text-gray-500">Faculty</th><th class="text-left px-4 py-3 text-xs font-semibold text-gray-500">College</th><th class="text-left px-4 py-3 text-xs font-semibold text-gray-500">Escalation</th><th class="text-left px-4 py-3 text-xs font-semibold text-gray-500">Status</th><th class="text-left px-4 py-3 text-xs font-semibold text-gray-500">Filed</th></tr></thead>
              <tbody id="complaintsTableBody"></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- QEO KPI TRACKER -->
      <div class="admin-section hidden flex-col gap-6" data-section="kpi">
        <div><h2 class="sr-only">QEO KPI Tracker</h2><p class="text-xs text-gray-500 mt-0.5">Live progress against the Office of Culture and Arts' <?= (int) $qeoKpis['year'] ?> Quality/Educational Organizations Objectives (BatStateU-QEO-OCA-03), projected to year-end at the current pace (<?= e($qeoKpis['paceFraction']) ?>% of the year elapsed). Metrics not tracked by ARTEMIS (equipment procurement, satisfaction surveys, etc.) are intentionally omitted rather than estimated.</p></div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <?php foreach ($qeoKpis['metrics'] as $m):
              $statusBg = $m['status'] === 'Behind Pace' ? '#FEE2E2' : ($m['status'] === 'Slightly Behind' ? '#FEF9C3' : '#DCFCE7');
              $statusText = $m['status'] === 'Behind Pace' ? '#B91C1C' : ($m['status'] === 'Slightly Behind' ? '#92400E' : '#15803D');
          ?>
          <div class="modern-card bg-white rounded-2xl border border-gray-100 p-5" style="box-shadow: 0 1px 6px rgba(0,0,0,0.06);">
            <div class="flex items-center justify-between mb-1">
              <h3 class="font-semibold text-sm" style="color:#1a1a2e;"><?= e($m['label']) ?></h3>
              <span class="text-[10px] font-semibold px-2 py-1 rounded-full" style="background:<?= $statusBg ?>;color:<?= $statusText ?>;"><?= e($m['status']) ?></span>
            </div>
            <p class="text-xs text-gray-600 mb-3"><?= e($m['citation']) ?></p>
            <div class="flex items-end gap-2 mb-2">
              <span class="text-2xl font-bold" style="color:#1a1a2e;"><?= (int) $m['actual'] ?></span>
              <span class="text-xs text-gray-600 mb-1">/ <?= (int) $m['target'] ?> target (<?= (int) $m['pctOfTarget'] ?>%)</span>
            </div>
            <div class="h-2 bg-gray-100 rounded-full overflow-hidden mb-2">
              <div class="h-full rounded-full" style="background:#B11226; width:<?= min(100, (int) $m['pctOfTarget']) ?>%;"></div>
            </div>
            <p class="text-xs text-gray-500">Projected year-end: <span class="font-semibold" style="color:#1a1a2e;"><?= (int) $m['projectedYearEnd'] ?></span> &middot; Expected by now: <?= e($m['expectedByNow']) ?></p>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- REPORTS -->
      <div class="admin-section hidden flex-col gap-6" data-section="reports">
        <div class="flex items-center justify-between">
          <div><h2 class="sr-only">Reports & Analytics</h2><p class="text-xs text-gray-600 mt-0.5">Academic Year <?= e($pdo->query("SELECT academic_year FROM benefit_records LIMIT 1")->fetchColumn() ?: '2025 – 2026') ?> &middot; OCA Overview</p></div>
          <button type="button" id="exportCsvBtn2" class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-white hover:opacity-90 transition-opacity" style="background:#B11226;"><i data-lucide="download" class="w-4 h-4"></i> Export CSV</button>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div class="modern-card bg-white rounded-2xl p-5 border border-gray-100" style="box-shadow: 0 1px 6px rgba(0,0,0,0.06);"><div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3" style="background:#FEE2E2;"><i data-lucide="file-text" class="w-5 h-5" style="color:#B11226;"></i></div><div class="count-up text-2xl font-bold" style="color:#1a1a2e;" data-count="<?= (int) array_sum(array_column($monthlyData, 'submitted')) ?>">0</div><div class="text-xs font-medium text-gray-600 mt-0.5">Total Submissions</div><div class="text-xs text-gray-600"><?= e($monthlyData[0]['month']) ?> &ndash; <?= e($monthlyData[count($monthlyData) - 1]['month']) ?> <?= date('Y') ?></div></div>
          <div class="modern-card bg-white rounded-2xl p-5 border border-gray-100" style="box-shadow: 0 1px 6px rgba(0,0,0,0.06);"><div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3" style="background:#DCFCE7;"><i data-lucide="check-circle" class="w-5 h-5" style="color:#22C55E;"></i></div><div class="count-up text-2xl font-bold" style="color:#1a1a2e;" data-count="<?= (int) ($statTotal > 0 ? round($statApproved / $statTotal * 100) : 0) ?>" data-suffix="%">0</div><div class="text-xs font-medium text-gray-600 mt-0.5">Approval Rate</div><div class="text-xs text-gray-600">of all reviewed</div></div>
          <div class="modern-card bg-white rounded-2xl p-5 border border-gray-100" style="box-shadow: 0 1px 6px rgba(0,0,0,0.06);"><div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3" style="background:#DBEAFE;"><i data-lucide="users" class="w-5 h-5" style="color:#3B82F6;"></i></div><div class="count-up text-2xl font-bold" style="color:#1a1a2e;" data-count="<?= (int) $activeArtists ?>">0</div><div class="text-xs font-medium text-gray-600 mt-0.5">Active Artists</div><div class="text-xs text-gray-600">enrolled members</div></div>
          <div class="modern-card bg-white rounded-2xl p-5 border border-gray-100" style="box-shadow: 0 1px 6px rgba(0,0,0,0.06);"><div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3" style="background:#FEF9C3;"><i data-lucide="calendar" class="w-5 h-5" style="color:#D4AF37;"></i></div><div class="count-up text-2xl font-bold" style="color:#1a1a2e;" data-count="<?= (int) $upcomingEventCount ?>">0</div><div class="text-xs font-medium text-gray-600 mt-0.5">Upcoming Events</div><div class="text-xs text-gray-600">scheduled</div></div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <div class="modern-card bg-white rounded-2xl border border-gray-100 p-5" style="box-shadow: 0 1px 6px rgba(0,0,0,0.06);">
            <h3 class="font-semibold text-sm mb-1" style="color:#1a1a2e;">Approval Rate Trend</h3><p class="text-xs text-gray-600 mb-4">Monthly approval %</p>
            <div style="height:200px;"><canvas id="approvalTrendChart"></canvas></div>
          </div>
          <div class="modern-card bg-white rounded-2xl border border-gray-100 p-5" style="box-shadow: 0 1px 6px rgba(0,0,0,0.06);">
            <h3 class="font-semibold text-sm mb-1" style="color:#1a1a2e;">Applications by Type</h3><p class="text-xs text-gray-600 mb-4">Breakdown by module</p>
            <div style="height:200px;"><canvas id="typeBarChart"></canvas></div>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <div class="modern-card bg-white rounded-2xl border border-gray-100 p-5" style="box-shadow: 0 1px 6px rgba(0,0,0,0.06);">
            <h3 class="font-semibold text-sm mb-1" style="color:#1a1a2e;">Monthly Volume</h3><p class="text-xs text-gray-600 mb-4">Submitted vs Approved vs Rejected</p>
            <div style="height:200px;"><canvas id="monthlyVolumeChart"></canvas></div>
          </div>
          <div class="modern-card bg-white rounded-2xl border border-gray-100 p-5" style="box-shadow: 0 1px 6px rgba(0,0,0,0.06);">
            <h3 class="font-semibold text-sm mb-4" style="color:#1a1a2e;">Status Breakdown</h3>
            <div class="flex flex-col gap-2">
              <?php foreach (['Approved','Pending','Under Review','Evaluation','Rejected'] as $status):
                  $count = $statusCounts[$status]; $pct = $statTotal > 0 ? ($count / $statTotal) * 100 : 0;
                  $color = ['Approved'=>'#22C55E','Pending'=>'#F59E0B','Under Review'=>'#3B82F6','Evaluation'=>'#A855F7','Rejected'=>'#EF4444'][$status]; ?>
                <div>
                  <div class="flex items-center justify-between mb-1"><div class="flex items-center gap-2"><span class="w-2 h-2 rounded-full" style="background:<?= $color ?>;"></span><span class="text-xs text-gray-600"><?= e($status) ?></span></div><div class="flex items-center gap-2"><span class="text-xs font-mono text-gray-600"><?= round($pct) ?>%</span><span class="text-xs font-semibold w-4 text-right" style="color:#1a1a2e;"><?= $count ?></span></div></div>
                  <div class="h-2 bg-gray-100 rounded-full overflow-hidden"><div class="h-full rounded-full" style="background:<?= $color ?>; width:<?= $pct ?>%;"></div></div>
                </div>
              <?php endforeach; ?>
            </div>
            <div class="mt-5 pt-4 border-t border-gray-100 grid grid-cols-2 gap-3">
              <div class="text-center"><div class="count-up text-xl font-bold" style="color:#1a1a2e;" data-count="<?= (int) $statTotal ?>">0</div><div class="text-xs text-gray-600 flex items-center justify-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full inline-block" style="background:#B11226;"></span>Total Applications</div></div>
              <div class="text-center"><div class="count-up text-xl font-bold" style="color:#1a1a2e;" data-count="<?= (int) ($statTotal > 0 ? round($statApproved / $statTotal * 100) : 0) ?>" data-suffix="%">0</div><div class="text-xs text-gray-600 flex items-center justify-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full inline-block" style="background:#22C55E;"></span>Overall Approval Rate</div></div>
            </div>
          </div>
        </div>

        <div class="modern-card bg-white rounded-2xl border border-gray-100 p-5" style="box-shadow: 0 1px 6px rgba(0,0,0,0.06);">
          <h3 class="font-semibold text-sm mb-1" style="color:#1a1a2e;">BANTOG Composite Ranking</h3><p class="text-xs text-gray-600 mb-4">Training (20) + Production (40) + Award (40) = 100, per Art. VIII Sec. 23. Highest score is flagged as the Pinaka-BANTOG candidate; a score without matching evidence is flagged for review.</p>
          <div id="bantogRankingList" class="flex flex-col gap-2"></div>
        </div>
      </div>

      <!-- EVENTS -->
      <div class="admin-section hidden flex-col gap-6" data-section="events">
        <div class="flex items-center justify-between">
          <div><h2 class="sr-only">Events Management</h2></div>
          <button type="button" id="addEventBtn" class="modern-btn flex items-center gap-2 px-3 sm:px-4 py-2.5 rounded-xl text-sm font-semibold text-white hover:opacity-90 transition-opacity" style="background:#B11226;"><i data-lucide="plus" class="w-4 h-4"></i><span class="hidden sm:inline">Add Event</span></button>
        </div>
        <div id="eventsGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"></div>
      </div>

      <!-- ANNOUNCEMENTS -->
      <div class="admin-section hidden flex-col gap-6" data-section="announcements">
        <div class="flex items-center justify-between">
          <div><h2 class="sr-only">Announcements</h2></div>
          <button type="button" id="addAnnBtn" class="modern-btn flex items-center gap-2 px-3 sm:px-4 py-2.5 rounded-xl text-sm font-semibold text-white hover:opacity-90 transition-opacity" style="background:#B11226;"><i data-lucide="plus" class="w-4 h-4"></i><span class="hidden sm:inline">New Announcement</span></button>
        </div>
        <div id="annList" class="flex flex-col gap-4"></div>
      </div>

      <!-- SETTINGS -->
      <div class="admin-section hidden flex-col gap-6 max-w-2xl" data-section="settings">
        <div><h2 class="sr-only">System Settings</h2></div>
        <div class="modern-card bg-white rounded-2xl p-6 border border-gray-100" style="box-shadow: 0 1px 6px rgba(0,0,0,0.06);">
          <h3 class="font-semibold text-sm mb-4" style="color:#1a1a2e;">System Information</h3>
          <div class="flex flex-col gap-3">
            <div><label for="set_systemName" class="text-xs text-gray-600 block mb-1">System Name</label><input id="set_systemName" class="adm-input modern-input w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none"></div>
            <div><label for="set_institution" class="text-xs text-gray-600 block mb-1">Institution</label><input id="set_institution" class="adm-input modern-input w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none"></div>
            <div><label for="set_office" class="text-xs text-gray-600 block mb-1">Office</label><input id="set_office" class="adm-input modern-input w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none"></div>
            <div><label for="set_academicYear" class="text-xs text-gray-600 block mb-1">Academic Year</label><input id="set_academicYear" class="adm-input modern-input w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none"></div>
          </div>
        </div>
        <div class="modern-card bg-white rounded-2xl p-6 border border-gray-100" style="box-shadow: 0 1px 6px rgba(0,0,0,0.06);">
          <h3 class="font-semibold text-sm mb-4" style="color:#1a1a2e;">Admin Account</h3>
          <div class="flex flex-col gap-3">
            <div><label for="set_adminName" class="text-xs text-gray-600 block mb-1">Administrator Name</label><input id="set_adminName" class="adm-input modern-input w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none"></div>
            <div><label for="set_email" class="text-xs text-gray-600 block mb-1">Email</label><input id="set_email" class="adm-input modern-input w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none"></div>
            <div><label for="set_role" class="text-xs text-gray-600 block mb-1">Role</label><input id="set_role" class="adm-input modern-input w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none"></div>
          </div>
        </div>
        <button type="button" id="saveSettingsBtn" class="modern-btn px-6 py-3 rounded-xl text-sm font-semibold text-white w-fit flex items-center gap-2 hover:opacity-90 transition-opacity" style="background:#B11226;"><i data-lucide="save" class="w-4 h-4"></i> <span id="saveSettingsLabel">Save Changes</span></button>
      </div>
    </main>

    <!-- MOBILE BOTTOM NAV -->
    <nav class="lg:hidden fixed bottom-0 left-0 right-0 z-30 bg-white border-t border-gray-100" style="box-shadow: 0 -2px 12px rgba(0,0,0,0.06); padding-bottom: env(safe-area-inset-bottom);">
      <div class="flex items-stretch">
        <button type="button" data-section-link="dashboard" class="admin-mobile-nav-btn flex-1 flex flex-col items-center justify-center py-2.5 gap-1" data-sec="dashboard"><div class="mobile-nav-icon-wrap w-10 h-8 flex items-center justify-center rounded-xl transition-all" style="background:#FEE2E2;"><i data-lucide="layout-dashboard" class="w-4 h-4" style="color:#B11226;"></i></div><span class="mobile-nav-label text-[10px] font-medium leading-none" style="color:#B11226;">Dashboard</span></button>
        <button type="button" data-section-link="applications" class="admin-mobile-nav-btn flex-1 flex flex-col items-center justify-center py-2.5 gap-1" data-sec="applications"><div class="mobile-nav-icon-wrap w-10 h-8 flex items-center justify-center rounded-xl transition-all"><i data-lucide="file-text" class="w-4 h-4" style="color:#4B5563;"></i></div><span class="mobile-nav-label text-[10px] font-medium leading-none" style="color:#4B5563;">Apps</span></button>
        <button type="button" data-section-link="events" class="admin-mobile-nav-btn flex-1 flex flex-col items-center justify-center py-2.5 gap-1" data-sec="events"><div class="mobile-nav-icon-wrap w-10 h-8 flex items-center justify-center rounded-xl transition-all"><i data-lucide="calendar" class="w-4 h-4" style="color:#4B5563;"></i></div><span class="mobile-nav-label text-[10px] font-medium leading-none" style="color:#4B5563;">Events</span></button>
        <button type="button" data-section-link="announcements" class="admin-mobile-nav-btn flex-1 flex flex-col items-center justify-center py-2.5 gap-1" data-sec="announcements"><div class="mobile-nav-icon-wrap w-10 h-8 flex items-center justify-center rounded-xl transition-all"><i data-lucide="bell" class="w-4 h-4" style="color:#4B5563;"></i></div><span class="mobile-nav-label text-[10px] font-medium leading-none" style="color:#4B5563;">Notices</span></button>
        <button type="button" id="moreMenuBtn" class="flex-1 flex flex-col items-center justify-center py-2.5 gap-1"><div class="w-10 h-8 flex items-center justify-center rounded-xl" id="moreMenuIconWrap"><i data-lucide="more-horizontal" class="w-4 h-4" style="color:#4B5563;"></i></div><span class="text-[10px] font-medium leading-none" style="color:#4B5563;" id="moreMenuLabel">More</span></button>
      </div>
    </nav>
  </div>
</div>

<!-- MORE MENU SHEET -->
<div id="moreMenuBackdrop" class="hidden fixed inset-0 bg-black/40 z-40 lg:hidden"></div>
<div id="moreMenuSheet" class="hidden fixed bottom-0 left-0 right-0 z-50 bg-white rounded-t-3xl lg:hidden" style="padding-bottom: calc(env(safe-area-inset-bottom) + 0.5rem);">
  <div class="w-10 h-1 bg-gray-200 rounded-full mx-auto mt-3 mb-4"></div>
  <div class="px-5 pb-2">
    <p class="text-xs text-gray-600 mb-3 uppercase tracking-wider font-medium">More Options</p>
    <button type="button" data-section-link="trainers" class="more-menu-item flex items-center gap-3 w-full px-3 py-3.5 rounded-xl mb-1 transition-all" style="color:#374151;"><i data-lucide="graduation-cap" class="w-5 h-5"></i><span class="text-sm font-medium">Trainer Evaluation</span></button>
    <button type="button" data-section-link="talent" class="more-menu-item flex items-center gap-3 w-full px-3 py-3.5 rounded-xl mb-1 transition-all" style="color:#374151;"><i data-lucide="target" class="w-5 h-5"></i><span class="text-sm font-medium">Talent Match</span></button>
    <button type="button" data-section-link="compliance" class="more-menu-item flex items-center gap-3 w-full px-3 py-3.5 rounded-xl mb-1 transition-all" style="color:#374151;"><i data-lucide="shield-alert" class="w-5 h-5"></i><span class="text-sm font-medium">Compliance</span></button>
    <button type="button" data-section-link="kpi" class="more-menu-item flex items-center gap-3 w-full px-3 py-3.5 rounded-xl mb-1 transition-all" style="color:#374151;"><i data-lucide="trending-up" class="w-5 h-5"></i><span class="text-sm font-medium">QEO KPI Tracker</span></button>
    <button type="button" data-section-link="reports" class="more-menu-item flex items-center gap-3 w-full px-3 py-3.5 rounded-xl mb-1 transition-all" style="color:#374151;"><i data-lucide="bar-chart-3" class="w-5 h-5"></i><span class="text-sm font-medium">Reports & Analytics</span></button>
    <button type="button" data-section-link="settings" class="more-menu-item flex items-center gap-3 w-full px-3 py-3.5 rounded-xl mb-1 transition-all" style="color:#374151;"><i data-lucide="settings" class="w-5 h-5"></i><span class="text-sm font-medium">System Settings</span></button>
    <div class="border-t border-gray-100 mt-2 pt-3">
      <a href="<?= e(APP_URL) ?>/logout" class="flex items-center gap-3 w-full px-3 py-3.5 rounded-xl text-gray-500"><i data-lucide="log-out" class="w-5 h-5"></i><span class="text-sm font-medium">Sign Out</span></a>
    </div>
  </div>
</div>

<!-- VIEW APPLICATION MODAL -->
<div id="viewAppModal" class="hidden fixed inset-0 z-50 items-end sm:items-center justify-center sm:p-4 bg-black/50 modal-backdrop-anim">
  <div class="bg-white rounded-t-3xl sm:rounded-2xl w-full sm:max-w-2xl overflow-hidden relative modal-panel-anim" style="max-height:92vh; box-shadow: 0 24px 60px rgba(0,0,0,0.22);">
    <div class="sm:hidden w-10 h-1 bg-gray-200 rounded-full mx-auto mt-3"></div>
    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100" style="background: linear-gradient(135deg, #B11226, #7a0d1a);">
      <div><h3 class="font-bold text-white text-sm sm:text-base">Application Details</h3><p class="text-red-200 text-xs" id="viewAppCode"></p></div>
      <button type="button" class="view-app-close w-10 h-10 -mr-2 rounded-full flex items-center justify-center hover:bg-white/10 transition-colors flex-shrink-0" aria-label="Close application details"><i data-lucide="x" class="w-5 h-5 text-white/70"></i></button>
    </div>
    <div class="p-5 flex flex-col gap-4 overflow-y-auto" style="max-height:70vh;">
      <div><div class="text-xs font-medium text-gray-500 mb-3">Application Progress</div><div id="viewAppProgress"></div></div>
      <div class="grid grid-cols-2 gap-2">
        <div class="bg-gray-50 rounded-xl p-3 min-w-0"><div class="text-xs text-gray-600 mb-0.5">Student Name</div><div class="text-xs font-semibold truncate" style="color:#1a1a2e;" id="viewAppName"></div></div>
        <div class="bg-gray-50 rounded-xl p-3 min-w-0"><div class="text-xs text-gray-600 mb-0.5">Student ID</div><div class="text-xs font-semibold truncate" style="color:#1a1a2e;" id="viewAppStudentId"></div></div>
        <div class="bg-gray-50 rounded-xl p-3 min-w-0"><div class="text-xs text-gray-600 mb-0.5">Course</div><div class="text-xs font-semibold truncate" style="color:#1a1a2e;" id="viewAppCourse"></div></div>
        <div class="bg-gray-50 rounded-xl p-3 min-w-0"><div class="text-xs text-gray-600 mb-0.5">Application Type</div><div class="text-xs font-semibold truncate" style="color:#1a1a2e;" id="viewAppType"></div></div>
        <div class="bg-gray-50 rounded-xl p-3 min-w-0"><div class="text-xs text-gray-600 mb-0.5">Date Submitted</div><div class="text-xs font-semibold truncate" style="color:#1a1a2e;" id="viewAppDate"></div></div>
        <div class="bg-gray-50 rounded-xl p-3 min-w-0"><div class="text-xs text-gray-600 mb-0.5">Current Status</div><div class="text-xs font-semibold truncate" id="viewAppStatus"></div></div>
      </div>
      <div id="viewAppDetailsBox" class="hidden bg-gray-50 rounded-xl p-3">
        <div class="text-xs font-medium text-gray-500 mb-1 flex items-center gap-1"><i data-lucide="align-left" class="w-3 h-3"></i> Student's Description</div>
        <p class="text-xs text-gray-700" id="viewAppDetailsText" style="white-space:pre-wrap;"></p>
      </div>
      <div id="viewAppDocsBox" class="hidden">
        <div class="text-xs font-medium text-gray-500 mb-2 flex items-center gap-1"><i data-lucide="paperclip" class="w-3 h-3"></i> Submitted Documents</div>
        <div class="flex flex-col gap-1.5" id="viewAppDocsList"></div>
      </div>
      <div id="viewAppEligibilityBox" class="hidden rounded-xl p-3 border">
        <div class="text-xs font-medium mb-2 flex items-center gap-1" style="color:#374151;"><i data-lucide="shield-check" class="w-3 h-3"></i> Eligibility Check <span id="viewAppEligibilityVerdict" class="ml-auto text-[10px] font-semibold px-2 py-0.5 rounded-full"></span></div>
        <div class="flex flex-col gap-1.5" id="viewAppEligibilityList"></div>
      </div>
      <div id="viewAppPlacementBox" class="hidden bg-gray-50 rounded-xl p-3">
        <div class="text-xs font-medium text-gray-500 mb-2 flex items-center gap-1"><i data-lucide="target" class="w-3 h-3"></i> Suggested RPAG Placement (Art. IV Sec. 12)</div>
        <div class="flex flex-col gap-1.5" id="viewAppPlacementList"></div>
      </div>
      <div id="viewAppPathfitBox" class="hidden bg-gray-50 rounded-xl p-3">
        <div class="text-xs font-medium text-gray-500 mb-2 flex items-center gap-1"><i data-lucide="book-open" class="w-3 h-3"></i> PATHFit Equivalency (Art. X)</div>
        <div class="flex flex-col gap-2 text-xs text-gray-600" id="viewAppPathfitMatrix"></div>
        <button type="button" id="insertPathfitJustificationBtn" class="mt-2 px-3 py-1.5 rounded-lg text-xs font-semibold" style="background:#B11226; color:#fff;">Insert Justification into Remarks</button>
      </div>
      <div id="viewAppStipendBox" class="hidden bg-gray-50 rounded-xl p-3">
        <div class="text-xs font-medium text-gray-500 mb-1 flex items-center gap-1"><i data-lucide="clock" class="w-3 h-3"></i> Stipend (Art. IX Sec. 29 — Php 60.00/hour)</div>
        <p class="text-xs text-gray-700">Hours claimed: <span class="font-semibold" id="viewAppHours"></span> &rarr; Computed amount: <span class="font-semibold" style="color:#B11226;" id="viewAppStipendAmount"></span></p>
      </div>
      <div id="viewAppBantogBox" class="hidden bg-gray-50 rounded-xl p-3">
        <div class="text-xs font-medium text-gray-500 mb-2 flex items-center gap-1"><i data-lucide="award" class="w-3 h-3"></i> BANTOG Evaluation — <span id="viewAppBantogCategory"></span></div>
        <div class="grid grid-cols-3 gap-2 mb-2">
          <div><label for="bantogScoreTraining" class="text-[10px] text-gray-500 block mb-1">Training/Seminar (0-20)</label><input type="number" id="bantogScoreTraining" min="0" max="20" class="w-full px-2 py-1.5 rounded-lg border border-gray-200 text-xs"></div>
          <div><label for="bantogScoreProduction" class="text-[10px] text-gray-500 block mb-1">Production (0-40)</label><input type="number" id="bantogScoreProduction" min="0" max="40" class="w-full px-2 py-1.5 rounded-lg border border-gray-200 text-xs"></div>
          <div><label for="bantogScoreAward" class="text-[10px] text-gray-500 block mb-1">Award Achieved (0-40)</label><input type="number" id="bantogScoreAward" min="0" max="40" class="w-full px-2 py-1.5 rounded-lg border border-gray-200 text-xs"></div>
        </div>
        <div class="flex items-center justify-between">
          <p class="text-xs text-gray-600">Total: <span class="font-semibold" style="color:#B11226;" id="bantogScoreTotal">0</span>/100</p>
          <button type="button" id="saveBantogScoreBtn" class="px-3 py-1.5 rounded-lg text-xs font-semibold" style="background:#B11226; color:#fff;">Save Score</button>
        </div>
      </div>
      <div id="viewAppBenefitBox" class="hidden bg-green-50 rounded-xl p-3 border border-green-100">
        <div class="text-xs font-medium text-green-700 mb-2 flex items-center gap-1"><i data-lucide="gift" class="w-3 h-3"></i> Granted Benefit — <span id="benefitTypeLabel"></span></div>
        <div class="grid grid-cols-2 gap-2 mb-2">
          <div id="benefitAmountWrap"><label for="benefitAmount" class="text-[10px] text-gray-500 block mb-1">Amount (Php)</label><input type="number" id="benefitAmount" step="0.01" min="0" class="w-full px-2 py-1.5 rounded-lg border border-gray-200 text-xs"></div>
          <div id="benefitGradeWrap"><label for="benefitGrade" class="text-[10px] text-gray-500 block mb-1">Grade</label><input type="number" id="benefitGrade" step="0.01" min="1" max="5" class="w-full px-2 py-1.5 rounded-lg border border-gray-200 text-xs"></div>
          <div><label for="benefitSemester" class="text-[10px] text-gray-500 block mb-1">Semester</label><select id="benefitSemester" class="w-full px-2 py-1.5 rounded-lg border border-gray-200 text-xs bg-white"><option value="">&mdash;</option><option value="1st">1st</option><option value="2nd">2nd</option><option value="Summer">Summer</option></select></div>
          <div><label for="benefitStatus" class="text-[10px] text-gray-500 block mb-1">Status</label><select id="benefitStatus" class="w-full px-2 py-1.5 rounded-lg border border-gray-200 text-xs bg-white"><option value="Active">Active</option><option value="Expired">Expired</option><option value="Revoked">Revoked</option></select></div>
        </div>
        <div class="flex justify-end"><button type="button" id="saveBenefitBtn" class="px-3 py-1.5 rounded-lg text-xs font-semibold" style="background:#15803D; color:#fff;">Save Benefit</button></div>
      </div>
      <div id="viewAppRemarksBox" class="hidden bg-yellow-50 rounded-xl p-3 border border-yellow-100">
        <div class="text-xs font-medium text-yellow-700 mb-1 flex items-center gap-1"><i data-lucide="message-square" class="w-3 h-3"></i> Admin Remark</div>
        <p class="text-xs text-yellow-800" id="viewAppRemarksText"></p>
      </div>
      <div class="flex flex-col gap-2 pt-2">
        <div class="flex items-center gap-2">
          <button type="button" id="viewApproveBtn" class="flex-1 py-2.5 rounded-xl text-sm font-semibold flex items-center justify-center gap-2 disabled:opacity-40 disabled:cursor-not-allowed hover:opacity-90 transition-opacity" style="background:#DCFCE7; color:#15803D;"><i data-lucide="thumbs-up" class="w-4 h-4"></i> Approve</button>
          <button type="button" id="viewRejectBtn" class="flex-1 py-2.5 rounded-xl text-sm font-semibold flex items-center justify-center gap-2 disabled:opacity-40 disabled:cursor-not-allowed hover:opacity-90 transition-opacity" style="background:#FEE2E2; color:#B91C1C;"><i data-lucide="thumbs-down" class="w-4 h-4"></i> Reject</button>
        </div>
        <button type="button" id="viewAddRemarkBtn" class="w-full py-2.5 rounded-xl text-sm font-semibold flex items-center justify-center gap-2 hover:opacity-90 transition-opacity" style="background:#FEF9C3; color:#92400E;"><i data-lucide="message-square" class="w-4 h-4"></i> Add Remark</button>
      </div>
    </div>
  </div>
</div>

<!-- REMARKS MODAL -->
<div id="remarksModal" class="hidden fixed inset-0 z-50 items-end sm:items-center justify-center sm:p-4 bg-black/50 modal-backdrop-anim">
  <div class="bg-white rounded-t-3xl sm:rounded-2xl w-full sm:max-w-md overflow-hidden relative modal-panel-anim" style="box-shadow: 0 24px 60px rgba(0,0,0,0.22);">
    <div class="sm:hidden w-10 h-1 bg-gray-200 rounded-full mx-auto mt-3"></div>
    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
      <div><h3 class="font-bold text-sm sm:text-base" style="color:#1a1a2e;">Add / Edit Remark</h3><p class="text-xs text-gray-500" id="remarksAppLabel"></p></div>
      <button type="button" id="remarksCloseBtn" class="w-10 h-10 -mr-2 rounded-full flex items-center justify-center hover:bg-gray-100 transition-colors flex-shrink-0" aria-label="Close remarks"><i data-lucide="x" class="w-5 h-5 text-gray-600"></i></button>
    </div>
    <div class="p-6 flex flex-col gap-4">
      <div><label for="remarksText" class="text-xs font-medium text-gray-600 block mb-1.5">Remarks / Notes</label><textarea id="remarksText" rows="5" placeholder="Enter your remarks or feedback for this application..." class="adm-input modern-input w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none resize-none"></textarea></div>
      <div class="flex gap-3">
        <button type="button" id="remarksCancelBtn" class="flex-1 py-2.5 rounded-xl text-sm font-medium border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors">Cancel</button>
        <button type="button" id="remarksSaveBtn" class="flex-1 py-2.5 rounded-xl text-sm font-semibold text-white flex items-center justify-center gap-2 disabled:opacity-40 hover:opacity-90 transition-opacity" style="background:#B11226;"><i data-lucide="send" class="w-4 h-4"></i> Save Remark</button>
      </div>
    </div>
  </div>
</div>

<!-- DELETE EVENT CONFIRM -->
<div id="deleteEventModal" class="hidden fixed inset-0 z-[60] items-center justify-center p-4 bg-black/50 modal-backdrop-anim">
  <div class="bg-white rounded-2xl p-6 w-full max-w-sm modal-panel-anim" style="box-shadow: 0 24px 60px rgba(0,0,0,0.22);">
    <div class="w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-4" style="background:#FEE2E2;"><i data-lucide="trash-2" class="w-5 h-5" style="color:#B11226;"></i></div>
    <h3 class="font-bold text-center text-sm mb-1" style="color:#1a1a2e;">Delete Event?</h3>
    <p class="text-xs text-gray-500 text-center mb-5" id="deleteEventLabel">This event will be permanently removed.</p>
    <div class="flex gap-3">
      <button type="button" id="deleteEventCancelBtn" class="flex-1 py-2.5 rounded-xl text-sm font-medium border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors">Cancel</button>
      <button type="button" id="deleteEventConfirmBtn" class="flex-1 py-2.5 rounded-xl text-sm font-semibold text-white hover:opacity-90 transition-opacity" style="background:#B11226;">Delete</button>
    </div>
  </div>
</div>

<!-- GENERIC CONFIRM (reject application / delete announcement, etc.) -->
<div id="confirmActionModal" class="hidden fixed inset-0 z-[60] items-center justify-center p-4 bg-black/50 modal-backdrop-anim">
  <div class="bg-white rounded-2xl p-6 w-full max-w-sm modal-panel-anim" style="box-shadow: 0 24px 60px rgba(0,0,0,0.22);">
    <div class="w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-4" style="background:#FEE2E2;"><i data-lucide="alert-triangle" class="w-5 h-5" style="color:#B11226;"></i></div>
    <h3 class="font-bold text-center text-sm mb-1" style="color:#1a1a2e;" id="confirmActionTitle">Are you sure?</h3>
    <p class="text-xs text-gray-500 text-center mb-5" id="confirmActionMessage"></p>
    <div class="flex gap-3">
      <button type="button" id="confirmActionCancelBtn" class="flex-1 py-2.5 rounded-xl text-sm font-medium border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors">Cancel</button>
      <button type="button" id="confirmActionConfirmBtn" class="flex-1 py-2.5 rounded-xl text-sm font-semibold text-white hover:opacity-90 transition-opacity" style="background:#B11226;">Confirm</button>
    </div>
  </div>
</div>

<!-- ADD/EDIT EVENT MODAL -->
<div id="eventModal" class="hidden fixed inset-0 z-50 items-end sm:items-center justify-center sm:p-4 bg-black/50 modal-backdrop-anim">
  <div class="bg-white rounded-t-3xl sm:rounded-2xl w-full sm:max-w-md overflow-hidden relative modal-panel-anim" style="max-height:92vh; box-shadow: 0 24px 60px rgba(0,0,0,0.22);">
    <div class="sm:hidden w-10 h-1 bg-gray-200 rounded-full mx-auto mt-3"></div>
    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100" style="background: linear-gradient(135deg, #B11226, #7a0d1a);">
      <h3 class="font-bold text-white text-sm" id="eventModalTitle">Add New Event</h3>
      <button type="button" id="eventModalCloseBtn" class="w-10 h-10 -mr-2 rounded-full flex items-center justify-center hover:bg-white/10 transition-colors flex-shrink-0" aria-label="Close event form"><i data-lucide="x" class="w-5 h-5 text-white/70"></i></button>
    </div>
    <div class="p-5 flex flex-col gap-3.5 overflow-y-auto" style="max-height:70vh;">
      <input type="hidden" id="eventIdInput">
      <div><label for="eventTitleInput" class="text-xs text-gray-500 block mb-1">Event Title</label><input id="eventTitleInput" placeholder="e.g. Cultural Night 2026" class="adm-input modern-input w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none"></div>
      <div><label for="eventTypeInput" class="text-xs text-gray-500 block mb-1">Event Type</label>
        <select id="eventTypeInput" class="adm-input modern-input w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none bg-white">
          <?php foreach (['Cultural Night', 'Festival', 'Awards Night', 'Competition', 'Seminar/Workshop', 'Conference', 'Other'] as $t): ?><option><?= e($t) ?></option><?php endforeach; ?>
        </select>
        <p class="text-xs text-gray-400 mt-1">"Competition" and "Seminar/Workshop" events (once marked Completed) feed the QEO Obj. 6 &amp; 7 KPI counts.</p>
      </div>
      <div><label for="eventDateInput" class="text-xs text-gray-500 block mb-1">Date</label><input id="eventDateInput" type="date" class="adm-input modern-input w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none"></div>
      <div><label for="eventLocationInput" class="text-xs text-gray-500 block mb-1">Location</label><input id="eventLocationInput" placeholder="e.g. OCA Hall" class="adm-input modern-input w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none"></div>
      <div><label for="eventAttendeesInput" class="text-xs text-gray-500 block mb-1">Expected Attendees</label><input id="eventAttendeesInput" type="number" min="0" placeholder="e.g. 120" class="adm-input modern-input w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none"></div>
      <div><label for="eventStatusInput" class="text-xs text-gray-500 block mb-1">Status</label>
        <select id="eventStatusInput" class="adm-input modern-input w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none bg-white">
          <?php foreach (['Upcoming','Planning','Ongoing','Completed','Cancelled'] as $s): ?><option><?= e($s) ?></option><?php endforeach; ?>
        </select>
      </div>
      <label class="flex items-start gap-2.5 p-3 rounded-xl border border-gray-200 cursor-pointer" style="background:#FFFBEB;">
        <input type="checkbox" id="eventRequiresTravelInput" class="mt-0.5">
        <span class="text-xs text-gray-700"><span class="font-semibold" style="color:#92400E;">Requires off-campus travel</span><br>Check this if the venue is on a different campus or off-site. Students will be asked to acknowledge travel/logistics terms before they can RSVP, instead of a one-click registration.</span>
      </label>
      <div>
        <label for="eventRequiresTypeInput" class="text-xs text-gray-500 block mb-1">Restrict RSVP to approved applicants of</label>
        <select id="eventRequiresTypeInput" class="adm-input modern-input w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none bg-white">
          <option value="">No restriction — open to all students</option>
          <?php foreach ($modules as $mod): ?><option value="<?= e($mod['code']) ?>"><?= e($mod['label']) ?> (only students with an Approved application)</option><?php endforeach; ?>
        </select>
        <p class="text-xs text-gray-400 mt-1">For "By Invitation" events like BANTOG Awards Night — only students whose linked application was approved can register.</p>
      </div>
      <div class="flex gap-3 pt-1">
        <button type="button" id="eventCancelBtn" class="flex-1 py-2.5 rounded-xl text-sm font-medium border border-gray-200 text-gray-600 hover:bg-gray-50">Cancel</button>
        <button type="button" id="eventSaveBtn" class="modern-btn flex-1 py-2.5 rounded-xl text-sm font-semibold text-white flex items-center justify-center gap-2 disabled:opacity-40 hover:opacity-90" style="background:#B11226;"><i data-lucide="save" class="w-4 h-4"></i> <span id="eventSaveLabel">Add Event</span></button>
      </div>
    </div>
  </div>
</div>

<!-- ADD/EDIT ANNOUNCEMENT MODAL -->
<div id="annModal" class="hidden fixed inset-0 z-50 items-end sm:items-center justify-center sm:p-4 bg-black/50 modal-backdrop-anim">
  <div class="bg-white rounded-t-3xl sm:rounded-2xl w-full sm:max-w-md overflow-hidden relative modal-panel-anim" style="box-shadow: 0 24px 60px rgba(0,0,0,0.22);">
    <div class="sm:hidden w-10 h-1 bg-gray-200 rounded-full mx-auto mt-3"></div>
    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100" style="background: linear-gradient(135deg, #B11226, #7a0d1a);">
      <h3 class="font-bold text-white text-sm" id="annModalTitle">New Announcement</h3>
      <button type="button" id="annModalCloseBtn" class="w-10 h-10 -mr-2 rounded-full flex items-center justify-center hover:bg-white/10 transition-colors flex-shrink-0" aria-label="Close announcement form"><i data-lucide="x" class="w-5 h-5 text-white/70"></i></button>
    </div>
    <div class="p-5 flex flex-col gap-3.5" style="padding-bottom: calc(1.25rem + env(safe-area-inset-bottom));">
      <input type="hidden" id="annIdInput">
      <div><label for="annTitleInput" class="text-xs text-gray-500 block mb-1">Title</label><input id="annTitleInput" placeholder="Announcement title..." class="adm-input modern-input w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none"></div>
      <div><label for="annTypeInput" class="text-xs text-gray-500 block mb-1">Type</label>
        <select id="annTypeInput" class="adm-input modern-input w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none bg-white">
          <?php foreach (['Audition','Stipend','Academic','General','Event'] as $t): ?><option><?= e($t) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div><label for="annAudienceInput" class="text-xs text-gray-500 block mb-1">Target Audience</label>
        <select id="annAudienceInput" class="adm-input modern-input w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none bg-white">
          <?php foreach (['All Students','Cultural Artists','Active Members','Trainers','All Staff'] as $a): ?><option><?= e($a) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="flex gap-3 pt-1">
        <button type="button" id="annCancelBtn" class="flex-1 py-2.5 rounded-xl text-sm font-medium border border-gray-200 text-gray-600 hover:bg-gray-50">Cancel</button>
        <button type="button" id="annSaveBtn" class="flex-1 py-2.5 rounded-xl text-sm font-semibold text-white flex items-center justify-center gap-2 disabled:opacity-40 hover:opacity-90" style="background:#B11226;"><i data-lucide="send" class="w-4 h-4"></i> <span id="annSaveLabel">Publish</span></button>
      </div>
    </div>
  </div>
</div>

<!-- TOAST -->
<div id="toast" class="hidden fixed bottom-24 left-1/2 z-[100] px-5 py-3 rounded-xl text-white text-sm font-medium items-center gap-2" style="transform: translateX(-50%); box-shadow: 0 8px 24px rgba(0,0,0,0.18);">
  <i data-lucide="check-circle" id="toastIcon" class="w-4 h-4"></i>
  <span id="toastMsg"></span>
</div>

<script>
var CSRF_TOKEN = <?= json_encode(csrf_token()) ?>;
var APP_URL = <?= json_encode(APP_URL) ?>;
var STATUS_BG = { Approved: '#DCFCE7', Pending: '#FEF9C3', 'Under Review': '#DBEAFE', Evaluation: '#F3E8FF', Rejected: '#FEE2E2' };
var STATUS_TEXT = { Approved: '#15803D', Pending: '#92400E', 'Under Review': '#1D4ED8', Evaluation: '#7C3AED', Rejected: '#B91C1C' };
var STATUS_DOT = { Approved: '#22C55E', Pending: '#F59E0B', 'Under Review': '#3B82F6', Evaluation: '#A855F7', Rejected: '#EF4444' };
var PROGRESS_STAGES = <?= json_encode(ARTEMIS_PROGRESS_STAGES) ?>;
var PROGRESS_STAGES_BY_TYPE = <?= json_encode(application_progress_stages_by_type()) ?>;
function stagesFor(typeCode) { return PROGRESS_STAGES_BY_TYPE[typeCode] || PROGRESS_STAGES; }
var MODULES = <?= json_encode($modules) ?>;

var APPLICATIONS = <?= json_encode(array_map(function ($a) use ($documentsByApp, $benefitByApp, $eligibilityByApp, $profilesByUser, $placementByUser) {
    $benefit = $benefitByApp[$a['application_id']] ?? null;
    return [
        'appId' => (int) $a['application_id'],
        'code' => $a['application_code'],
        'name' => $a['first_name'] . ' ' . $a['last_name'],
        'studentId' => $a['id_number'],
        'type' => $a['type_name'],
        'typeCode' => $a['type_code'],
        'date' => format_date($a['submitted_at']),
        'status' => $a['status'],
        'stage' => (int) $a['current_stage'],
        'course' => $a['course'],
        'remarks' => $a['remarks'],
        'details' => $a['details'],
        'hoursClaimed' => $a['hours_claimed'] !== null ? (float) $a['hours_claimed'] : null,
        'bantogCategory' => $a['bantog_category'],
        'bantogScoreTraining' => $a['bantog_score_training'] !== null ? (int) $a['bantog_score_training'] : null,
        'bantogScoreProduction' => $a['bantog_score_production'] !== null ? (int) $a['bantog_score_production'] : null,
        'bantogScoreAward' => $a['bantog_score_award'] !== null ? (int) $a['bantog_score_award'] : null,
        'eligibility' => $eligibilityByApp[$a['application_id']] ?? ['eligible' => true, 'checks' => []],
        'profileTroupe' => $profilesByUser[(int) $a['user_id']]['troupe_name'] ?? null,
        'placement' => $placementByUser[(int) $a['user_id']] ?? [],
        'benefit' => $benefit ? [
            'id' => (int) $benefit['benefit_id'],
            'type' => $benefit['benefit_type'],
            'academicYear' => $benefit['academic_year'],
            'semester' => $benefit['semester'],
            'amount' => $benefit['amount'] !== null ? (float) $benefit['amount'] : null,
            'grade' => $benefit['grade'] !== null ? (float) $benefit['grade'] : null,
            'status' => $benefit['status'],
        ] : null,
        'docs' => array_map(function ($d) { return ['name' => $d['file_name'], 'type' => $d['document_type'] ?? null, 'url' => APP_URL . '/' . $d['file_path']]; }, $documentsByApp[$a['application_id']] ?? []),
    ];
}, $applications), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;

var EVENTS = <?= json_encode(array_map(function ($e) {
    return ['id' => (int) $e['event_id'], 'title' => $e['title'], 'eventType' => $e['event_type'], 'date' => format_date($e['event_date'], 'F j, Y'), 'rawDate' => $e['event_date'], 'location' => $e['location'], 'attendees' => (int) $e['expected_attendees'], 'status' => $e['status'], 'requiresTravel' => (bool) $e['requires_travel'], 'requiresTypeCode' => $e['requires_type_code']];
}, $events), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;

var ANNOUNCEMENTS = <?= json_encode(array_map(function ($a) {
    return ['id' => (int) $a['announcement_id'], 'title' => $a['title'], 'date' => format_date($a['created_at'], 'F j, Y'), 'type' => $a['tag'], 'audience' => $a['audience'] ?? 'All Students'];
}, $announcements), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;

var TRAINER_RUBRIC = <?= json_encode(ARTEMIS_TRAINER_RUBRIC_CRITERIA) ?>;
var TRAINER_EVALUATIONS = <?= json_encode(array_map(function ($t) {
    return [
        'id' => (int) $t['evaluation_id'],
        'trainerName' => $t['trainer_name'],
        'discipline' => $t['discipline'],
        'totalScore' => (int) $t['total_score'],
        'level' => $t['recommended_level'],
        'salaryGrade' => (int) $t['recommended_salary_grade'],
        'hourlyRate' => $t['hourly_rate'] !== null ? (float) $t['hourly_rate'] : null,
        'hoursRendered' => $t['hours_rendered'] !== null ? (float) $t['hours_rendered'] : null,
        'honorarium' => $t['computed_honorarium'] !== null ? (float) $t['computed_honorarium'] : null,
    ];
}, $trainerEvaluations), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
var PATHFIT_MATRIX = <?= json_encode($pathfitMatrixJs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;

var PERFORMERS = <?= json_encode($performersWithPlacement, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
var FACULTY_COMPLAINTS = <?= json_encode(array_map(function ($c) {
    return [
        'id' => (int) $c['complaint_id'],
        'facultyName' => $c['faculty_name'],
        'college' => $c['college'],
        'rpagGroup' => $c['rpag_group'],
        'description' => $c['description'],
        'escalationLevel' => $c['escalation_level'],
        'status' => $c['status'],
        'filedDate' => format_date($c['created_at']),
    ];
}, $facultyComplaints), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;

lucide.createIcons();

// ---------- Animated stat counters ----------
function runCountUps(scope) {
  (scope || document).querySelectorAll('.count-up').forEach(function (el) {
    if (el.dataset.counted === 'true') return;
    el.dataset.counted = 'true';
    var target = parseInt(el.dataset.count, 10) || 0;
    var suffix = el.dataset.suffix || '';
    var duration = 900;
    var start = null;
    function tick(ts) {
      if (start === null) start = ts;
      var progress = Math.min((ts - start) / duration, 1);
      var eased = 1 - Math.pow(1 - progress, 3);
      el.textContent = Math.round(eased * target) + suffix;
      if (progress < 1) requestAnimationFrame(tick);
      else el.textContent = target + suffix;
    }
    requestAnimationFrame(tick);
  });
}
runCountUps(document.querySelector('.admin-section[data-section="dashboard"]'));

// ---------- Section navigation ----------
var titles = { dashboard: 'Admin Dashboard', applications: 'Applications', trainers: 'Trainer Evaluation', talent: 'Talent Match', compliance: 'Compliance', kpi: 'QEO KPI Tracker', reports: 'Reports & Analytics', events: 'Events Management', announcements: 'Announcements', settings: 'System Settings' };
var state = { section: 'dashboard', module: '', filterStatus: 'All', search: '', highlightedCode: null, processingCode: null };

function setSection(sec) {
  state.section = sec;
  document.querySelectorAll('.admin-section').forEach(function (p) {
    var active = p.dataset.section === sec;
    p.classList.toggle('hidden', !active);
    p.classList.toggle('flex', active);
    if (active) runCountUps(p);
  });
  document.querySelectorAll('.admin-nav-btn').forEach(function (b) {
    var active = b.dataset.section === sec;
    b.style.background = active ? 'rgba(255,255,255,0.2)' : '';
    b.style.color = active ? '#fff' : 'rgba(255,255,255,0.7)';
    b.style.fontWeight = active ? '600' : '400';
    if (active) b.setAttribute('data-active', 'true'); else b.removeAttribute('data-active');
  });
  document.querySelectorAll('.admin-mobile-nav-btn').forEach(function (b) {
    var active = b.dataset.sec === sec;
    var iconWrap = b.querySelector('.mobile-nav-icon-wrap');
    if (iconWrap) {
      iconWrap.style.background = active ? '#FEE2E2' : 'transparent';
      var icon = iconWrap.querySelector('svg, i');
      if (icon) icon.style.color = active ? '#B11226' : '#4B5563';
    }
    var label = b.querySelector('.mobile-nav-label');
    if (label) label.style.color = active ? '#B11226' : '#4B5563';
  });
  var moreActive = sec === 'reports' || sec === 'settings' || sec === 'trainers' || sec === 'talent' || sec === 'compliance' || sec === 'kpi';
  var moreIconWrap = document.getElementById('moreMenuIconWrap');
  if (moreIconWrap) {
    moreIconWrap.style.background = moreActive ? '#FEE2E2' : 'transparent';
    var moreIcon = moreIconWrap.querySelector('svg, i');
    if (moreIcon) moreIcon.style.color = moreActive ? '#B11226' : '#4B5563';
  }
  var moreLabel = document.getElementById('moreMenuLabel');
  if (moreLabel) moreLabel.style.color = moreActive ? '#B11226' : '#4B5563';

  var titleText = titles[sec] + (sec === 'applications' && state.module ? ' — ' + moduleLabel(state.module) : '');
  document.getElementById('pageTitleDesktop').textContent = titleText;
  document.getElementById('pageTitleMobile').textContent = titleText;

  if (sec === 'applications') renderApplications();
  if (sec === 'events') renderEvents();
  if (sec === 'announcements') renderAnnouncements();
}
function moduleLabel(code) { var m = MODULES.find(function (x) { return x.code === code; }); return m ? m.label : ''; }

document.querySelectorAll('.admin-nav-btn').forEach(function (b) { b.addEventListener('click', function () { state.module = ''; state.filterStatus = 'All'; document.getElementById('statusFilterSelect').value = 'All'; setSection(b.dataset.section); }); });
document.querySelectorAll('[data-section-link]').forEach(function (b) { b.addEventListener('click', function () { setSection(b.dataset.sectionLink); }); });
document.querySelectorAll('.admin-mobile-nav-btn').forEach(function (b) { b.addEventListener('click', function () { setSection(b.dataset.sec); }); });
document.querySelectorAll('.admin-module-btn, .admin-module-jump').forEach(function (b) { b.addEventListener('click', function () { state.module = b.dataset.module; setSection('applications'); }); });

// More menu sheet
var moreMenuSheet = document.getElementById('moreMenuSheet');
var moreMenuBackdrop = document.getElementById('moreMenuBackdrop');
document.getElementById('moreMenuBtn').addEventListener('click', function () { moreMenuSheet.classList.remove('hidden'); moreMenuBackdrop.classList.remove('hidden'); });
moreMenuBackdrop.addEventListener('click', function () { moreMenuSheet.classList.add('hidden'); moreMenuBackdrop.classList.add('hidden'); });
document.querySelectorAll('.more-menu-item').forEach(function (b) { b.addEventListener('click', function () { moreMenuSheet.classList.add('hidden'); moreMenuBackdrop.classList.add('hidden'); }); });

// ---------- Notifications ----------
var notifBtn = document.getElementById('notifBtn');
var notifDropdown = document.getElementById('notifDropdown');
notifBtn.addEventListener('click', function () { notifDropdown.classList.toggle('hidden'); });
document.getElementById('notifCloseBtn').addEventListener('click', function () { notifDropdown.classList.add('hidden'); });
document.getElementById('markAllReadBtn').addEventListener('click', function () {
  document.querySelectorAll('.notif-item').forEach(function (el) { el.style.background = '#fff'; el.querySelector('.notif-msg').style.color = '#6B7280'; var dot = el.querySelector('.notif-dot'); if (dot) dot.remove(); });
  var badge = document.getElementById('notifBadge'); if (badge) badge.remove();
  this.style.display = 'none';
});
document.querySelectorAll('.notif-item').forEach(function (el) {
  el.addEventListener('click', function () {
    el.style.background = '#fff'; el.querySelector('.notif-msg').style.color = '#6B7280';
    var dot = el.querySelector('.notif-dot'); if (dot) dot.remove();
    notifDropdown.classList.add('hidden');
    if (el.dataset.module) state.module = el.dataset.module;
    if (el.dataset.filter) { state.filterStatus = el.dataset.filter; document.getElementById('statusFilterSelect').value = el.dataset.filter; }
    else { state.filterStatus = 'All'; document.getElementById('statusFilterSelect').value = 'All'; }
    setSection(el.dataset.section || 'applications');
    if (el.dataset.appCode) {
      state.highlightedCode = el.dataset.appCode;
      renderApplications();
      setTimeout(function () {
        var row = document.getElementById('app-row-' + el.dataset.appCode);
        if (row) row.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }, 120);
      setTimeout(function () { state.highlightedCode = null; renderApplications(); }, 2500);
    }
  });
});

// ---------- Applications rendering ----------
function filteredApps() {
  return APPLICATIONS.filter(function (a) {
    var matchStatus = state.filterStatus === 'All' || a.status === state.filterStatus;
    var matchModule = !state.module || a.typeCode === state.module;
    var q = state.search.toLowerCase();
    var matchSearch = !q || a.name.toLowerCase().indexOf(q) !== -1 || a.code.toLowerCase().indexOf(q) !== -1 || a.type.toLowerCase().indexOf(q) !== -1;
    return matchStatus && matchModule && matchSearch;
  });
}
function initialsOf(name) { return name.split(' ').map(function (n) { return n[0]; }).join('').slice(0, 2); }
function statusBadgeHtml(status) {
  return '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium" style="background:' + STATUS_BG[status] + ';color:' + STATUS_TEXT[status] + ';"><span class="w-1.5 h-1.5 rounded-full" style="background:' + STATUS_DOT[status] + ';"></span>' + status + '</span>';
}
function renderApplications() {
  var list = filteredApps();
  document.getElementById('appsSectionTitle').textContent = 'Applications' + (state.module ? ' — ' + moduleLabel(state.module) : '');
  document.getElementById('appsCount').textContent = list.length + ' applications found';
  document.getElementById('clearModuleBtn').classList.toggle('hidden', !state.module);
  document.getElementById('clearModuleBtn').classList.toggle('flex', !!state.module);

  var mobile = document.getElementById('appsMobileList');
  var resolvedStatuses = ['Approved', 'Rejected'];
  if (!list.length) {
    mobile.innerHTML = '<div class="py-12 text-center text-sm text-gray-600">No applications found.</div>';
  } else {
    mobile.innerHTML = list.map(function (app) {
      var highlighted = state.highlightedCode === app.code;
      var resolved = resolvedStatuses.indexOf(app.status) !== -1;
      return '<div id="app-row-' + app.code + '" class="rounded-2xl border border-gray-100 bg-white p-4 transition-all duration-500' + (highlighted ? ' notif-highlight-card' : '') + '" style="box-shadow:0 1px 4px rgba(0,0,0,0.06);">'
        + '<div class="flex items-start gap-3 mb-3"><div class="w-9 h-9 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0" style="background:#B11226;">' + initialsOf(app.name) + '</div>'
        + '<div class="flex-1 min-w-0"><div class="text-sm font-semibold" style="color:#1a1a2e;">' + app.name + '</div><div class="text-xs text-gray-600">' + app.studentId + ' &middot; ' + app.course + '</div></div>' + statusBadgeHtml(app.status) + '</div>'
        + '<div class="text-xs text-gray-500 mb-0.5">' + app.type + '</div><div class="text-xs text-gray-600 mb-3">' + app.code + ' &middot; ' + app.date + '</div>'
        + '<div class="border-t border-gray-100 pt-3 flex items-center justify-around">'
        + '<button class="app-view-trigger flex flex-col items-center gap-1 transition-opacity active:scale-95" data-app="' + app.code + '"><div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background:#FEE2E2;"><i data-lucide="eye" class="w-4 h-4" style="color:#B11226;"></i></div><span class="text-[10px] font-medium" style="color:#4B5563;">View</span></button>'
        + '<button class="app-approve-trigger flex flex-col items-center gap-1 disabled:opacity-30 disabled:cursor-not-allowed transition-opacity active:scale-95" data-app="' + app.code + '" ' + (resolved ? 'disabled' : '') + '><div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background:#F0FDF4;"><i data-lucide="thumbs-up" class="w-4 h-4" style="color:#15803D;"></i></div><span class="text-[10px] font-medium" style="color:#4B5563;">Approve</span></button>'
        + '<button class="app-reject-trigger flex flex-col items-center gap-1 disabled:opacity-30 disabled:cursor-not-allowed transition-opacity active:scale-95" data-app="' + app.code + '" ' + (resolved ? 'disabled' : '') + '><div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background:#FFF1F1;"><i data-lucide="thumbs-down" class="w-4 h-4" style="color:#B91C1C;"></i></div><span class="text-[10px] font-medium" style="color:#4B5563;">Reject</span></button>'
        + '<button class="app-remark-trigger flex flex-col items-center gap-1 transition-opacity active:scale-95" data-app="' + app.code + '"><div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background:#FFFBEB;"><i data-lucide="message-square" class="w-4 h-4" style="color:#92400E;"></i></div><span class="text-[10px] font-medium" style="color:#4B5563;">Note</span></button>'
        + '</div></div>';
    }).join('');
  }

  var tbody = document.getElementById('appsTableBody');
  if (!list.length) {
    tbody.innerHTML = '<tr><td colspan="7" class="px-5 py-10 text-center text-sm text-gray-600">No applications found.</td></tr>';
  } else {
    tbody.innerHTML = list.map(function (app) {
      var highlighted = state.highlightedCode === app.code;
      var resolved = resolvedStatuses.indexOf(app.status) !== -1;
      return '<tr id="app-row-' + app.code + '" class="border-t border-gray-50 transition-all duration-500' + (highlighted ? ' notif-highlight-row' : '') + '">'
        + '<td class="px-5 py-4 text-xs font-mono text-gray-500">' + app.code + '</td>'
        + '<td class="px-5 py-4"><div class="flex items-center gap-2"><div class="w-7 h-7 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0" style="background:#B11226;">' + initialsOf(app.name) + '</div><div><div class="text-xs font-semibold" style="color:#1a1a2e;">' + app.name + '</div><div class="text-xs text-gray-600">' + app.course + '</div></div></div></td>'
        + '<td class="px-5 py-4 text-xs text-gray-500">' + app.studentId + '</td>'
        + '<td class="px-5 py-4 text-xs text-gray-600">' + app.type + '</td>'
        + '<td class="px-5 py-4 text-xs text-gray-500">' + app.date + '</td>'
        + '<td class="px-5 py-4">' + statusBadgeHtml(app.status) + '</td>'
        + '<td class="px-5 py-4"><div class="flex items-center gap-1">'
        + '<button class="app-view-trigger w-9 h-9 rounded-lg flex items-center justify-center hover:bg-red-50 hover:scale-110 transition-all" data-app="' + app.code + '" title="View application" aria-label="View application"><i data-lucide="eye" class="w-3.5 h-3.5" style="color:#B11226;"></i></button>'
        + '<button class="app-approve-trigger w-9 h-9 rounded-lg flex items-center justify-center hover:bg-green-50 hover:scale-110 transition-all disabled:opacity-30 disabled:cursor-not-allowed disabled:hover:scale-100" data-app="' + app.code + '" ' + (resolved ? 'disabled' : '') + ' title="Approve" aria-label="Approve application"><i data-lucide="thumbs-up" class="w-3.5 h-3.5 text-green-500"></i></button>'
        + '<button class="app-reject-trigger w-9 h-9 rounded-lg flex items-center justify-center hover:bg-red-50 hover:scale-110 transition-all disabled:opacity-30 disabled:cursor-not-allowed disabled:hover:scale-100" data-app="' + app.code + '" ' + (resolved ? 'disabled' : '') + ' title="Reject" aria-label="Reject application"><i data-lucide="thumbs-down" class="w-3.5 h-3.5 text-red-500"></i></button>'
        + '<button class="app-remark-trigger w-9 h-9 rounded-lg flex items-center justify-center hover:bg-yellow-50 hover:scale-110 transition-all" data-app="' + app.code + '" title="Remark" aria-label="Add remark"><i data-lucide="message-square" class="w-3.5 h-3.5 text-yellow-500"></i></button>'
        + '</div></td></tr>';
    }).join('');
  }
  lucide.createIcons();
  wireAppRowButtons();
}
function wireAppRowButtons() {
  document.querySelectorAll('.app-view-trigger').forEach(function (b) { b.addEventListener('click', function () { openViewApp(b.dataset.app); }); });
  document.querySelectorAll('.app-approve-trigger').forEach(function (b) { b.addEventListener('click', function () { approveApp(b.dataset.app); }); });
  document.querySelectorAll('.app-reject-trigger').forEach(function (b) { b.addEventListener('click', function () { rejectApp(b.dataset.app); }); });
  document.querySelectorAll('.app-remark-trigger').forEach(function (b) { b.addEventListener('click', function () { openRemarks(b.dataset.app); }); });
}

document.getElementById('statusFilterSelect').addEventListener('change', function () { state.filterStatus = this.value; renderApplications(); });
document.getElementById('clearModuleBtn').addEventListener('click', function () { state.module = ''; renderApplications(); document.getElementById('appsSectionTitle').textContent = 'Applications'; });
function syncSearch(v) { state.search = v; renderApplications(); }
document.getElementById('searchInput').addEventListener('input', function () { document.getElementById('searchInputMobile').value = this.value; syncSearch(this.value); });
document.getElementById('searchInputMobile').addEventListener('input', function () { document.getElementById('searchInput').value = this.value; syncSearch(this.value); });

// ---------- Toast ----------
var toastTimer = null;
function showToast(message, type) {
  var el = document.getElementById('toast');
  var colors = { success: '#22C55E', error: '#EF4444', info: '#3B82F6' };
  var icons = { success: 'check-circle', error: 'x-circle', info: 'alert-circle' };
  el.style.background = colors[type || 'success'];
  document.getElementById('toastIcon').setAttribute('data-lucide', icons[type || 'success']);
  document.getElementById('toastMsg').textContent = message;
  el.classList.remove('hidden'); el.classList.add('flex');
  lucide.createIcons();
  if (toastTimer) clearTimeout(toastTimer);
  toastTimer = setTimeout(function () { el.classList.add('hidden'); el.classList.remove('flex'); }, 3000);
}

// ---------- Application actions ----------
function findApp(code) { return APPLICATIONS.find(function (a) { return a.code === code; }); }
function reviewAction(code, action, remarks) {
  return fetch(APP_URL + '/admin/applications/review', {
    method: 'POST', headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ csrf_token: CSRF_TOKEN, application_id: findApp(code).appId, action: action, remarks: remarks || '' }),
  }).then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); });
}
function approveApp(code) {
  if (state.processingCode) return;
  state.processingCode = code;
  reviewAction(code, 'approve').then(function (res) {
    state.processingCode = null;
    if (!res.ok) { showToast(res.data.error || 'Failed to approve.', 'error'); return; }
    var app = findApp(code); app.status = 'Approved'; app.stage = 5;
    if (res.data.benefit) { app.benefit = res.data.benefit; }
    renderApplications();
    document.getElementById('viewAppModal').classList.add('hidden'); document.getElementById('viewAppModal').classList.remove('flex');
    showToast(app.name + "'s application has been approved.", 'success');
  });
}
function rejectApp(code) {
  if (state.processingCode) return;
  var app = findApp(code);
  openConfirm('Reject Application?', (app ? app.name : 'This applicant') + "'s application will be marked as rejected. This cannot be undone from here.", function () { doRejectApp(code); });
}
function doRejectApp(code) {
  if (state.processingCode) return;
  state.processingCode = code;
  reviewAction(code, 'reject', 'Rejected by OCA administrator.').then(function (res) {
    state.processingCode = null;
    if (!res.ok) { showToast(res.data.error || 'Failed to reject.', 'error'); return; }
    var app = findApp(code); app.status = 'Rejected'; app.stage = 5; app.remarks = 'Rejected by OCA administrator.';
    renderApplications();
    document.getElementById('viewAppModal').classList.add('hidden'); document.getElementById('viewAppModal').classList.remove('flex');
    showToast(app.name + "'s application has been rejected.", 'error');
  });
}

// ---------- View app modal ----------
function progressTrackerHtml(stage, typeCode) {
  var STAGE_LABELS = stagesFor(typeCode);
  var total = STAGE_LABELS.length;
  var fillPct = (Math.min(stage - 1, total - 1) / (total - 1)) * (100 - 100 / total);
  var html = '<div class="w-full"><div class="relative flex items-center w-full" style="height:32px;">';
  html += '<div class="absolute h-0.5 bg-gray-200" style="left:' + (100 / (total * 2)) + '%;right:' + (100 / (total * 2)) + '%;"></div>';
  html += '<div class="absolute h-0.5" style="background:linear-gradient(90deg,#22C55E,#16a34a);left:' + (100 / (total * 2)) + '%;width:' + fillPct + '%;"></div>';
  STAGE_LABELS.forEach(function (label, i) {
    var n = i + 1, done = n < stage, active = n === stage;
    var bg = done ? '#22C55E' : (active ? '#B11226' : '#fff');
    var color = (done || active) ? '#fff' : '#4B5563';
    var border = (done || active) ? 'none' : '2px solid #E5E7EB';
    var shadow = active ? '0 0 0 3px rgba(177,18,38,0.15)' : 'none';
    html += '<div class="flex-1 flex justify-center relative z-10"><div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold" style="background:' + bg + ';color:' + color + ';border:' + border + ';box-shadow:' + shadow + ';">' + (done ? '&#10003;' : n) + '</div></div>';
  });
  html += '</div><div class="flex mt-1.5">';
  STAGE_LABELS.forEach(function (label, i) {
    var active = (i + 1) === stage;
    html += '<div class="flex-1 flex justify-center px-0.5"><span class="text-[9px] sm:text-[10px] text-center leading-tight block" style="color:' + (active ? '#B11226' : '#4B5563') + ';font-weight:' + (active ? '600' : '400') + ';">' + label + '</span></div>';
  });
  return html + '</div></div>';
}
var currentViewCode = null;
function openViewApp(code) {
  var app = findApp(code);
  if (!app) return;
  currentViewCode = code;
  document.getElementById('viewAppCode').textContent = app.code;
  document.getElementById('viewAppProgress').innerHTML = progressTrackerHtml(app.stage, app.typeCode);
  document.getElementById('viewAppName').textContent = app.name;
  document.getElementById('viewAppStudentId').textContent = app.studentId;
  document.getElementById('viewAppCourse').textContent = app.course;
  document.getElementById('viewAppType').textContent = app.type;
  document.getElementById('viewAppDate').textContent = app.date;
  document.getElementById('viewAppStatus').innerHTML = statusBadgeHtml(app.status);
  var resolved = app.status === 'Approved' || app.status === 'Rejected';
  document.getElementById('viewApproveBtn').disabled = resolved;
  document.getElementById('viewRejectBtn').disabled = resolved;
  var remarksBox = document.getElementById('viewAppRemarksBox');
  if (app.remarks) { remarksBox.classList.remove('hidden'); document.getElementById('viewAppRemarksText').textContent = app.remarks; }
  else remarksBox.classList.add('hidden');
  var detailsBox = document.getElementById('viewAppDetailsBox');
  if (app.details) { detailsBox.classList.remove('hidden'); document.getElementById('viewAppDetailsText').textContent = app.details; }
  else detailsBox.classList.add('hidden');
  var docsBox = document.getElementById('viewAppDocsBox');
  if (app.docs && app.docs.length) {
    docsBox.classList.remove('hidden');
    document.getElementById('viewAppDocsList').innerHTML = app.docs.map(function (d) {
      return '<a href="' + d.url + '" target="_blank" class="text-xs flex items-center gap-1 hover:underline" style="color:#B11226;">' +
        '<i data-lucide="file-text" class="w-3 h-3 flex-shrink-0"></i>' +
        (d.type ? '<span class="text-gray-500">' + d.type + ':</span> ' : '') + d.name + '</a>';
    }).join('');
  } else {
    docsBox.classList.add('hidden');
  }

  var eligBox = document.getElementById('viewAppEligibilityBox');
  if (app.eligibility && app.eligibility.checks && app.eligibility.checks.length) {
    eligBox.classList.remove('hidden');
    var verdictEl = document.getElementById('viewAppEligibilityVerdict');
    verdictEl.textContent = app.eligibility.eligible ? 'Eligible' : 'Not Eligible';
    verdictEl.style.background = app.eligibility.eligible ? '#DCFCE7' : '#FEE2E2';
    verdictEl.style.color = app.eligibility.eligible ? '#15803D' : '#B91C1C';
    eligBox.style.background = app.eligibility.eligible ? '#F0FDF4' : '#FEF2F2';
    eligBox.style.borderColor = app.eligibility.eligible ? '#DCFCE7' : '#FEE2E2';
    document.getElementById('viewAppEligibilityList').innerHTML = app.eligibility.checks.map(function (c) {
      var color = c.pass === true ? '#15803D' : (c.pass === false ? '#B91C1C' : '#4B5563');
      var icon = c.pass === true ? 'check-circle' : (c.pass === false ? 'x-circle' : 'help-circle');
      return '<div class="flex items-start gap-1.5"><i data-lucide="' + icon + '" class="w-3 h-3 flex-shrink-0 mt-0.5" style="color:' + color + ';"></i><div><span class="text-xs font-medium" style="color:' + color + ';">' + c.label + '</span><p class="text-[10px] text-gray-500">' + c.detail + '</p></div></div>';
    }).join('');
  } else {
    eligBox.classList.add('hidden');
  }

  var placementBox = document.getElementById('viewAppPlacementBox');
  if (app.typeCode === 'audition_recruitment' && app.placement && app.placement.length) {
    placementBox.classList.remove('hidden');
    document.getElementById('viewAppPlacementList').innerHTML = app.placement.map(function (pl) {
      return '<div class="flex items-center justify-between"><span class="text-xs" style="color:#1a1a2e;">#' + pl.rank + ' ' + pl.category + ' <span class="text-gray-600">(' + pl.proficiency + ')</span></span><span class="text-[10px] text-gray-600">' + pl.role + '</span></div>';
    }).join('');
  } else {
    placementBox.classList.add('hidden');
  }

  var pathfitBox = document.getElementById('viewAppPathfitBox');
  if (app.typeCode === 'pathfit_exemption') {
    pathfitBox.classList.remove('hidden');
    document.getElementById('viewAppPathfitMatrix').innerHTML = PATHFIT_MATRIX.map(function (row) {
      return '<div><span class="font-semibold" style="color:#1a1a2e;">' + row.standard + ':</span> ' + row.cultureArts + '</div>';
    }).join('');
  } else {
    pathfitBox.classList.add('hidden');
  }

  var stipendBox = document.getElementById('viewAppStipendBox');
  if (app.typeCode === 'stipend' && app.hoursClaimed !== null) {
    stipendBox.classList.remove('hidden');
    document.getElementById('viewAppHours').textContent = app.hoursClaimed;
    document.getElementById('viewAppStipendAmount').textContent = 'Php ' + (app.hoursClaimed * 60).toFixed(2);
  } else {
    stipendBox.classList.add('hidden');
  }

  var bantogBox = document.getElementById('viewAppBantogBox');
  if (app.typeCode === 'bantog_recognition') {
    bantogBox.classList.remove('hidden');
    document.getElementById('viewAppBantogCategory').textContent = app.bantogCategory || 'No category selected';
    document.getElementById('bantogScoreTraining').value = app.bantogScoreTraining !== null ? app.bantogScoreTraining : '';
    document.getElementById('bantogScoreProduction').value = app.bantogScoreProduction !== null ? app.bantogScoreProduction : '';
    document.getElementById('bantogScoreAward').value = app.bantogScoreAward !== null ? app.bantogScoreAward : '';
    updateBantogTotal();
  } else {
    bantogBox.classList.add('hidden');
  }

  var benefitBox = document.getElementById('viewAppBenefitBox');
  if (app.benefit) {
    benefitBox.classList.remove('hidden');
    document.getElementById('benefitTypeLabel').textContent = app.benefit.type + ' (' + app.benefit.academicYear + ')';
    document.getElementById('benefitAmount').value = app.benefit.amount !== null ? app.benefit.amount : '';
    document.getElementById('benefitGrade').value = app.benefit.grade !== null ? app.benefit.grade : '';
    document.getElementById('benefitSemester').value = app.benefit.semester || '';
    document.getElementById('benefitStatus').value = app.benefit.status || 'Active';
    document.getElementById('benefitAmountWrap').classList.toggle('hidden', app.benefit.type !== 'Stipend');
    document.getElementById('benefitGradeWrap').classList.toggle('hidden', app.benefit.type !== 'PATHFit Exemption');
  } else {
    benefitBox.classList.add('hidden');
  }

  var modal = document.getElementById('viewAppModal'); modal.classList.remove('hidden'); modal.classList.add('flex');
  lucide.createIcons();
}
document.getElementById('insertPathfitJustificationBtn').addEventListener('click', function () {
  if (!currentViewCode) return;
  var app = findApp(currentViewCode);
  var lines = ['PATHFit Equivalency Justification — ' + app.name + (app.profileTroupe ? ' (' + app.profileTroupe + ')' : ''), 'Per Art. X Training Equivalency Program Matrix:'];
  PATHFIT_MATRIX.forEach(function (row) { lines.push('- ' + row.standard + ': ' + row.cultureArts); });
  if (app.eligibility && app.eligibility.checks && app.eligibility.checks.length) {
    lines.push('');
    lines.push('Eligibility check (Sec. 35):');
    app.eligibility.checks.forEach(function (c) {
      var mark = c.pass === true ? 'PASS' : (c.pass === false ? 'FAIL' : 'UNVERIFIED');
      lines.push('  [' + mark + '] ' + c.label + ' — ' + c.detail);
    });
  }
  lines.push('');
  lines.push('Recommendation: rigorous RPAG training/rehearsal fulfills the physical education requirements outlined in the PATHFit syllabi (Sec. 35-c). Grade of 1.00 upon submission of all required documentation (Sec. 38-a).');
  var vModal = document.getElementById('viewAppModal'); vModal.classList.add('hidden'); vModal.classList.remove('flex');
  openRemarks(currentViewCode);
  document.getElementById('remarksText').value = lines.join('\n');
});
document.getElementById('saveBenefitBtn').addEventListener('click', function () {
  if (!currentViewCode) return;
  var app = findApp(currentViewCode);
  if (!app.benefit) return;
  var payload = {
    csrf_token: CSRF_TOKEN, action: 'update_benefit', benefit_id: app.benefit.id,
    amount: document.getElementById('benefitAmount').value,
    grade: document.getElementById('benefitGrade').value,
    semester: document.getElementById('benefitSemester').value,
    status: document.getElementById('benefitStatus').value,
  };
  fetch(APP_URL + '/admin/applications/review', {
    method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload),
  }).then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); }).then(function (res) {
    if (!res.ok) { showToast(res.data.error || 'Failed to save benefit.', 'error'); return; }
    app.benefit.amount = payload.amount !== '' ? parseFloat(payload.amount) : null;
    app.benefit.grade = payload.grade !== '' ? parseFloat(payload.grade) : null;
    app.benefit.semester = payload.semester || null;
    app.benefit.status = payload.status;
    showToast('Benefit record updated.', 'success');
  });
});
function updateBantogTotal() {
  var t = parseInt(document.getElementById('bantogScoreTraining').value, 10) || 0;
  var p = parseInt(document.getElementById('bantogScoreProduction').value, 10) || 0;
  var a = parseInt(document.getElementById('bantogScoreAward').value, 10) || 0;
  document.getElementById('bantogScoreTotal').textContent = (t + p + a);
}
['bantogScoreTraining', 'bantogScoreProduction', 'bantogScoreAward'].forEach(function (id) {
  document.getElementById(id).addEventListener('input', updateBantogTotal);
});
document.getElementById('saveBantogScoreBtn').addEventListener('click', function () {
  if (!currentViewCode) return;
  var app = findApp(currentViewCode);
  var training = parseInt(document.getElementById('bantogScoreTraining').value, 10) || 0;
  var production = parseInt(document.getElementById('bantogScoreProduction').value, 10) || 0;
  var award = parseInt(document.getElementById('bantogScoreAward').value, 10) || 0;
  fetch(APP_URL + '/admin/applications/review', {
    method: 'POST', headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ csrf_token: CSRF_TOKEN, application_id: app.appId, action: 'score', score_training: training, score_production: production, score_award: award }),
  }).then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); }).then(function (res) {
    if (!res.ok) { showToast(res.data.error || 'Failed to save score.', 'error'); return; }
    app.bantogScoreTraining = training; app.bantogScoreProduction = production; app.bantogScoreAward = award;
    renderBantogRanking();
    showToast('BANTOG score saved (' + res.data.total + '/100).', 'success');
  });
});

// ---------- BANTOG Composite Ranking Engine (Art. VIII Sec. 23) ----------
function renderBantogRanking() {
  var el = document.getElementById('bantogRankingList');
  if (!el) return;
  var docTypeAward = 'Awards / Certificates of Recognition';
  var entries = APPLICATIONS.filter(function (a) {
    return a.typeCode === 'bantog_recognition' && (a.bantogScoreTraining !== null || a.bantogScoreProduction !== null || a.bantogScoreAward !== null);
  }).map(function (a) {
    var t = a.bantogScoreTraining || 0, p = a.bantogScoreProduction || 0, w = a.bantogScoreAward || 0;
    var hasAwardDoc = a.docs.some(function (d) { return d.type === docTypeAward; });
    return { code: a.code, name: a.name, category: a.bantogCategory || 'Uncategorized', total: t + p + w, training: t, production: p, award: w, mismatch: w > 0 && !hasAwardDoc };
  }).sort(function (a, b) { return b.total - a.total; });

  if (!entries.length) { el.innerHTML = '<p class="text-xs text-gray-600">No scored BANTOG submissions yet.</p>'; return; }
  el.innerHTML = entries.map(function (e, i) {
    var badge = i === 0 ? '<span class="text-[10px] font-bold px-2 py-0.5 rounded-full" style="background:#FEF9C3; color:#92400E;">Pinaka-BANTOG</span>' : '';
    var mismatchBadge = e.mismatch ? '<span class="text-[10px] font-semibold px-2 py-0.5 rounded-full ml-1" style="background:#FEE2E2; color:#B91C1C;">Score/evidence mismatch</span>' : '';
    return '<div class="flex items-center justify-between p-3 rounded-xl border border-gray-100 flex-wrap gap-2">' +
      '<div><div class="text-xs font-semibold flex items-center gap-1 flex-wrap" style="color:#1a1a2e;">' + (i + 1) + '. ' + e.name + ' ' + badge + mismatchBadge + '</div>' +
      '<div class="text-[10px] text-gray-600">' + e.code + ' &middot; ' + e.category + '</div></div>' +
      '<div class="text-right"><div class="text-sm font-bold" style="color:#B11226;">' + e.total + '/100</div>' +
      '<div class="text-[10px] text-gray-600">T ' + e.training + ' + P ' + e.production + ' + A ' + e.award + '</div></div></div>';
  }).join('');
}
renderBantogRanking();

// ---------- Trainer Level Equivalency Engine (Art. VI Sec. 17) ----------
document.querySelectorAll('.trainer-score-slider').forEach(function (s) {
  s.addEventListener('input', function () { document.getElementById('trainerScoreVal_' + s.dataset.key).textContent = s.value; });
});
function renderTrainerEvaluations() {
  var body = document.getElementById('trainerEvalTableBody');
  if (!body) return;
  if (!TRAINER_EVALUATIONS.length) { body.innerHTML = '<tr><td colspan="8" class="px-4 py-6 text-center text-xs text-gray-600">No evaluations yet.</td></tr>'; return; }
  body.innerHTML = TRAINER_EVALUATIONS.map(function (t) {
    return '<tr class="border-t border-gray-100">' +
      '<td class="px-4 py-3 text-xs font-medium" style="color:#1a1a2e;">' + t.trainerName + '</td>' +
      '<td class="px-4 py-3 text-xs text-gray-500">' + (t.discipline || '&mdash;') + '</td>' +
      '<td class="px-4 py-3 text-xs text-gray-500">' + t.totalScore + '/25</td>' +
      '<td class="px-4 py-3 text-xs text-gray-500">' + t.level + ' (SG ' + t.salaryGrade + ')</td>' +
      '<td class="px-4 py-3 text-xs"><input type="number" step="0.01" min="0" class="trainer-rate-input w-20 px-2 py-1 rounded-lg border border-gray-200 text-xs" data-id="' + t.id + '" value="' + (t.hourlyRate !== null ? t.hourlyRate : '') + '"></td>' +
      '<td class="px-4 py-3 text-xs"><input type="number" step="0.01" min="0" class="trainer-hours-input w-16 px-2 py-1 rounded-lg border border-gray-200 text-xs" data-id="' + t.id + '" value="' + (t.hoursRendered !== null ? t.hoursRendered : '') + '"></td>' +
      '<td class="px-4 py-3 text-xs font-semibold" style="color:#B11226;">' + (t.honorarium !== null ? 'Php ' + t.honorarium.toFixed(2) : '&mdash;') + '</td>' +
      '<td class="px-4 py-3 text-xs"><button type="button" class="trainer-compute-btn px-2 py-1 rounded-lg text-xs font-semibold" style="background:#DCFCE7; color:#15803D;" data-id="' + t.id + '">Compute</button></td>' +
      '</tr>';
  }).join('');
}
renderTrainerEvaluations();

document.getElementById('evaluateTrainerBtn').addEventListener('click', function () {
  var name = document.getElementById('trainerNameInput').value.trim();
  if (!name) { showToast('Enter the trainer\'s name.', 'error'); return; }
  var discipline = document.getElementById('trainerDisciplineInput').value.trim();
  var payload = { csrf_token: CSRF_TOKEN, action: 'evaluate', trainer_name: name, discipline: discipline };
  Object.keys(TRAINER_RUBRIC).forEach(function (key) { payload['score_' + key] = document.getElementById('trainerScore_' + key).value; });
  fetch(APP_URL + '/admin/trainer-evaluations', {
    method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload),
  }).then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); }).then(function (res) {
    if (!res.ok) { showToast(res.data.error || 'Failed to evaluate.', 'error'); return; }
    var resultBox = document.getElementById('trainerResultBox');
    resultBox.classList.remove('hidden');
    resultBox.innerHTML = '<strong>' + res.data.level.name + '</strong> (SG ' + res.data.level.salary_grade + ') &mdash; cumulative score ' + res.data.total_score + '/25.<br>' + res.data.level.education + ' &middot; ' + res.data.level.experience;
    TRAINER_EVALUATIONS.unshift({
      id: res.data.evaluation_id, trainerName: name, discipline: discipline || null,
      totalScore: res.data.total_score, level: res.data.level.name, salaryGrade: res.data.level.salary_grade,
      hourlyRate: null, hoursRendered: null, honorarium: null,
    });
    renderTrainerEvaluations();
    document.getElementById('trainerNameInput').value = '';
    document.getElementById('trainerDisciplineInput').value = '';
    showToast('Evaluation recorded.', 'success');
  });
});
document.getElementById('trainerEvalTableBody').addEventListener('click', function (e) {
  var btn = e.target.closest('.trainer-compute-btn');
  if (!btn) return;
  var id = parseInt(btn.dataset.id, 10);
  var t = TRAINER_EVALUATIONS.find(function (x) { return x.id === id; });
  if (!t) return;
  var rateInput = document.querySelector('.trainer-rate-input[data-id="' + id + '"]');
  var hoursInput = document.querySelector('.trainer-hours-input[data-id="' + id + '"]');
  fetch(APP_URL + '/admin/trainer-evaluations', {
    method: 'POST', headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ csrf_token: CSRF_TOKEN, action: 'set_honorarium', evaluation_id: id, hourly_rate: rateInput.value, hours_rendered: hoursInput.value }),
  }).then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); }).then(function (res) {
    if (!res.ok) { showToast(res.data.error || 'Failed to compute honorarium.', 'error'); return; }
    t.hourlyRate = rateInput.value !== '' ? parseFloat(rateInput.value) : null;
    t.hoursRendered = hoursInput.value !== '' ? parseFloat(hoursInput.value) : null;
    t.honorarium = res.data.computed_honorarium;
    renderTrainerEvaluations();
    showToast('Honorarium computed.', 'success');
  });
});

// ---------- Talent Match (RPAG/Discipline Recommender) ----------
function renderTalentMatch() {
  var el = document.getElementById('talentMatchList');
  if (!PERFORMERS.length) { el.innerHTML = '<div class="text-center text-xs text-gray-600 py-10">No performer profiles yet.</div>'; return; }
  el.innerHTML = PERFORMERS.map(function (p) {
    var placementHtml = p.placement.length ? p.placement.map(function (pl) {
      return '<div class="flex items-center justify-between px-3 py-2 rounded-lg" style="background:' + (pl.rank === 1 ? '#FEF9C3' : '#F9FAFB') + ';">' +
        '<div><span class="text-xs font-semibold" style="color:#1a1a2e;">#' + pl.rank + ' ' + pl.category + '</span><span class="text-[10px] text-gray-600 block">' + pl.role + ' &middot; ' + pl.proficiency + '</span></div>' +
        '<span class="text-xs font-mono text-gray-500">' + pl.score.toFixed(1) + '</span></div>';
    }).join('') : '<div class="text-xs text-gray-600 px-3 py-2">No recorded talents yet.</div>';
    return '<div class="modern-card bg-white rounded-2xl border border-gray-100 p-4" style="box-shadow:0 1px 6px rgba(0,0,0,0.06);">' +
      '<div class="flex items-center justify-between mb-2"><div><span class="text-sm font-semibold" style="color:#1a1a2e;">' + p.name + '</span><span class="text-xs text-gray-600 block">' + (p.studentId || '') + ' &middot; ' + (p.course || '') + '</span></div>' +
      (p.activeMember ? '<span class="text-[10px] font-medium px-2 py-1 rounded-full" style="background:#DCFCE7;color:#15803D;">Active Member</span>' : '<span class="text-[10px] font-medium px-2 py-1 rounded-full" style="background:#F3F4F6;color:#6B7280;">Inactive</span>') +
      '</div><div class="flex flex-col gap-1.5">' + placementHtml + '</div></div>';
  }).join('');
  lucide.createIcons();
}
renderTalentMatch();

// ---------- Compliance (Faculty Non-Compliance Pattern Flag) ----------
var ESCALATION_BG = { 'First Violation': '#FEF9C3', 'Repeated Violation': '#FEE2E2' };
var ESCALATION_TEXT = { 'First Violation': '#92400E', 'Repeated Violation': '#B91C1C' };
var COMPLAINT_STATUSES = ['Submitted', 'Dean Review', 'Written Warning Issued', 'Grievance Board', 'Resolved'];
function renderComplaints() {
  var body = document.getElementById('complaintsTableBody');
  if (!FACULTY_COMPLAINTS.length) { body.innerHTML = '<tr><td colspan="5" class="px-4 py-6 text-center text-xs text-gray-600">No complaints logged.</td></tr>'; return; }
  body.innerHTML = FACULTY_COMPLAINTS.map(function (c) {
    var statusOptions = COMPLAINT_STATUSES.map(function (s) { return '<option value="' + s + '"' + (s === c.status ? ' selected' : '') + '>' + s + '</option>'; }).join('');
    return '<tr class="border-t border-gray-100">' +
      '<td class="px-4 py-3 text-xs font-medium" style="color:#1a1a2e;">' + c.facultyName + '</td>' +
      '<td class="px-4 py-3 text-xs text-gray-500">' + (c.college || '&mdash;') + '</td>' +
      '<td class="px-4 py-3"><span class="text-[10px] font-semibold px-2 py-1 rounded-full" style="background:' + ESCALATION_BG[c.escalationLevel] + ';color:' + ESCALATION_TEXT[c.escalationLevel] + ';">' + c.escalationLevel + '</span></td>' +
      '<td class="px-4 py-3 text-xs"><select class="complaint-status-select px-2 py-1 rounded-lg border border-gray-200 text-xs bg-white" data-id="' + c.id + '">' + statusOptions + '</select></td>' +
      '<td class="px-4 py-3 text-xs text-gray-600">' + c.filedDate + '</td>' +
      '</tr>';
  }).join('');
}
renderComplaints();

document.getElementById('logComplaintBtn').addEventListener('click', function () {
  var name = document.getElementById('complaintFacultyInput').value.trim();
  var description = document.getElementById('complaintDescriptionInput').value.trim();
  if (!name || !description) { showToast('Faculty name and a description are required.', 'error'); return; }
  var payload = {
    csrf_token: CSRF_TOKEN, action: 'log', faculty_name: name,
    college: document.getElementById('complaintCollegeInput').value.trim(),
    rpag_group: document.getElementById('complaintGroupInput').value.trim(),
    description: description,
  };
  fetch(APP_URL + '/admin/faculty-complaints', {
    method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload),
  }).then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); }).then(function (res) {
    if (!res.ok) { showToast(res.data.error || 'Failed to log complaint.', 'error'); return; }
    var resultBox = document.getElementById('complaintResultBox');
    resultBox.classList.remove('hidden');
    resultBox.innerHTML = '<strong>' + res.data.escalation_level + '</strong><br>' + res.data.recommended_action;
    FACULTY_COMPLAINTS.unshift({
      id: res.data.complaint_id, facultyName: name, college: payload.college || null, rpagGroup: payload.rpag_group || null,
      description: description, escalationLevel: res.data.escalation_level, status: 'Submitted', filedDate: 'Just now',
    });
    renderComplaints();
    document.getElementById('complaintFacultyInput').value = '';
    document.getElementById('complaintCollegeInput').value = '';
    document.getElementById('complaintGroupInput').value = '';
    document.getElementById('complaintDescriptionInput').value = '';
    showToast('Complaint logged (' + res.data.escalation_level + ').', res.data.escalation_level === 'Repeated Violation' ? 'error' : 'info');
  });
});
document.getElementById('complaintsTableBody').addEventListener('change', function (e) {
  var sel = e.target.closest('.complaint-status-select');
  if (!sel) return;
  var id = parseInt(sel.dataset.id, 10);
  var newStatus = sel.value;
  fetch(APP_URL + '/admin/faculty-complaints', {
    method: 'POST', headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ csrf_token: CSRF_TOKEN, action: 'update_status', complaint_id: id, status: newStatus }),
  }).then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); }).then(function (res) {
    if (!res.ok) { showToast(res.data.error || 'Failed to update status.', 'error'); return; }
    var c = FACULTY_COMPLAINTS.find(function (x) { return x.id === id; });
    if (c) c.status = newStatus;
    showToast('Status updated.', 'success');
  });
});

document.querySelectorAll('.view-app-close').forEach(function (b) { b.addEventListener('click', function () { var m = document.getElementById('viewAppModal'); m.classList.add('hidden'); m.classList.remove('flex'); }); });
document.getElementById('viewApproveBtn').addEventListener('click', function () { if (currentViewCode) approveApp(currentViewCode); });
document.getElementById('viewRejectBtn').addEventListener('click', function () { if (currentViewCode) rejectApp(currentViewCode); });
document.getElementById('viewAddRemarkBtn').addEventListener('click', function () { var m = document.getElementById('viewAppModal'); m.classList.add('hidden'); m.classList.remove('flex'); openRemarks(currentViewCode); });

// Wire dashboard's static "recent applications" view buttons (server-rendered, not in filteredApps loop)
document.querySelectorAll('.view-app-btn').forEach(function (b) { b.addEventListener('click', function () { openViewApp(b.dataset.app); }); });

// ---------- Remarks modal ----------
var remarksModal = document.getElementById('remarksModal');
var currentRemarksCode = null;
function openRemarks(code) {
  var app = findApp(code);
  if (!app) return;
  currentRemarksCode = code;
  document.getElementById('remarksAppLabel').textContent = app.code + ' — ' + app.name;
  document.getElementById('remarksText').value = app.remarks || '';
  remarksModal.classList.remove('hidden'); remarksModal.classList.add('flex');
}
document.getElementById('remarksCloseBtn').addEventListener('click', function () { remarksModal.classList.add('hidden'); remarksModal.classList.remove('flex'); });
document.getElementById('remarksCancelBtn').addEventListener('click', function () { remarksModal.classList.add('hidden'); remarksModal.classList.remove('flex'); });
document.getElementById('remarksSaveBtn').addEventListener('click', function () {
  var text = document.getElementById('remarksText').value.trim();
  if (!text || !currentRemarksCode) return;
  reviewAction(currentRemarksCode, 'remark', text).then(function (res) {
    if (!res.ok) { showToast(res.data.error || 'Failed to save remark.', 'error'); return; }
    findApp(currentRemarksCode).remarks = text;
    renderApplications();
    showToast('Remark saved and sent to student.', 'info');
    remarksModal.classList.add('hidden'); remarksModal.classList.remove('flex');
  });
});

// ---------- CSV export ----------
function exportCsv() {
  var headers = ['App ID', 'Student Name', 'Student ID', 'Type', 'Date', 'Status', 'Course', 'Remarks'];
  var rows = filteredApps().map(function (a) { return [a.code, a.name, a.studentId, a.type, a.date, a.status, a.course, a.remarks || ''].join(','); });
  var csv = [headers.join(','), rows.join('\n')].join('\n');
  var blob = new Blob([csv], { type: 'text/csv' });
  var url = URL.createObjectURL(blob);
  var link = document.createElement('a');
  link.href = url; link.download = 'ARTEMIS_Applications_' + new Date().toISOString().split('T')[0] + '.csv';
  link.click();
  URL.revokeObjectURL(url);
  showToast('Applications exported as CSV.', 'info');
}
document.getElementById('exportCsvBtn').addEventListener('click', exportCsv);
document.getElementById('exportCsvBtn2').addEventListener('click', exportCsv);

// ---------- Events ----------
function renderEvents() {
  var grid = document.getElementById('eventsGrid');
  if (!EVENTS.length) { grid.innerHTML = '<div class="col-span-full py-16 text-center text-gray-600 text-sm">No events yet. Click "Add Event" to create one.</div>'; return; }
  grid.innerHTML = EVENTS.map(function (ev) {
    var upcoming = ev.status === 'Upcoming';
    return '<div class="modern-card bg-white rounded-2xl p-5 border border-gray-100" style="box-shadow:0 1px 6px rgba(0,0,0,0.06);">'
      + '<div class="flex items-start justify-between mb-3"><div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:#FEE2E2;"><i data-lucide="calendar" class="w-5 h-5" style="color:#B11226;"></i></div>'
      + '<span class="text-xs px-2.5 py-1 rounded-full" style="background:' + (upcoming ? '#DBEAFE' : '#FEF9C3') + ';color:' + (upcoming ? '#1D4ED8' : '#92400E') + ';">' + ev.status + '</span></div>'
      + '<h4 class="font-semibold text-sm mb-1" style="color:#1a1a2e;">' + ev.title + '</h4><p class="text-xs text-gray-500 mb-1">' + ev.date + '</p><p class="text-xs text-gray-600 mb-1">' + (ev.location || '') + '</p>'
      + (ev.requiresTravel ? '<p class="text-[10px] font-semibold mb-2 flex items-center gap-1" style="color:#92400E;"><i data-lucide="plane" class="w-3 h-3"></i> Requires off-campus travel</p>' : '<div class="mb-2"></div>')
      + '<div class="flex items-center justify-between"><span class="text-xs text-gray-500">' + ev.attendees + ' expected</span>'
      + '<div class="flex items-center gap-2"><button class="event-edit-btn text-xs flex items-center gap-1 hover:opacity-80" style="color:#B11226;" data-id="' + ev.id + '"><i data-lucide="pencil" class="w-3 h-3"></i> Edit</button>'
      + '<button class="event-delete-btn text-xs flex items-center gap-1 text-gray-600 hover:text-red-500" data-id="' + ev.id + '" aria-label="Delete event"><i data-lucide="trash-2" class="w-3 h-3"></i></button></div></div></div>';
  }).join('');
  lucide.createIcons();
  document.querySelectorAll('.event-edit-btn').forEach(function (b) { b.addEventListener('click', function () { openEditEvent(parseInt(b.dataset.id, 10)); }); });
  document.querySelectorAll('.event-delete-btn').forEach(function (b) { b.addEventListener('click', function () { openDeleteEvent(parseInt(b.dataset.id, 10)); }); });
}
var eventModal = document.getElementById('eventModal');
document.getElementById('addEventBtn').addEventListener('click', function () {
  document.getElementById('eventModalTitle').textContent = 'Add New Event';
  document.getElementById('eventSaveLabel').textContent = 'Add Event';
  document.getElementById('eventIdInput').value = ''; document.getElementById('eventTitleInput').value = ''; document.getElementById('eventDateInput').value = '';
  document.getElementById('eventTypeInput').value = 'Cultural Night';
  document.getElementById('eventLocationInput').value = ''; document.getElementById('eventAttendeesInput').value = ''; document.getElementById('eventStatusInput').value = 'Upcoming';
  document.getElementById('eventRequiresTravelInput').checked = false;
  document.getElementById('eventRequiresTypeInput').value = '';
  eventModal.classList.remove('hidden'); eventModal.classList.add('flex');
});
function openEditEvent(id) {
  var ev = EVENTS.find(function (e) { return e.id === id; });
  if (!ev) return;
  document.getElementById('eventModalTitle').textContent = 'Edit Event';
  document.getElementById('eventSaveLabel').textContent = 'Update';
  document.getElementById('eventIdInput').value = ev.id;
  document.getElementById('eventTitleInput').value = ev.title;
  document.getElementById('eventTypeInput').value = ev.eventType || 'Cultural Night';
  document.getElementById('eventDateInput').value = ev.rawDate;
  document.getElementById('eventLocationInput').value = ev.location || '';
  document.getElementById('eventAttendeesInput').value = ev.attendees;
  document.getElementById('eventStatusInput').value = ev.status;
  document.getElementById('eventRequiresTravelInput').checked = !!ev.requiresTravel;
  document.getElementById('eventRequiresTypeInput').value = ev.requiresTypeCode || '';
  eventModal.classList.remove('hidden'); eventModal.classList.add('flex');
}
document.getElementById('eventModalCloseBtn').addEventListener('click', function () { eventModal.classList.add('hidden'); eventModal.classList.remove('flex'); });
document.getElementById('eventCancelBtn').addEventListener('click', function () { eventModal.classList.add('hidden'); eventModal.classList.remove('flex'); });
document.getElementById('eventSaveBtn').addEventListener('click', function () {
  var title = document.getElementById('eventTitleInput').value.trim();
  var date = document.getElementById('eventDateInput').value;
  if (!title || !date) return;
  var id = parseInt(document.getElementById('eventIdInput').value, 10) || 0;
  var payload = {
    csrf_token: CSRF_TOKEN, action: 'save', event_id: id, title: title, date: date,
    event_type: document.getElementById('eventTypeInput').value,
    location: document.getElementById('eventLocationInput').value.trim(),
    attendees: document.getElementById('eventAttendeesInput').value,
    status: document.getElementById('eventStatusInput').value,
    requires_travel: document.getElementById('eventRequiresTravelInput').checked,
    requires_type_code: document.getElementById('eventRequiresTypeInput').value,
  };
  fetch(APP_URL + '/admin/events/save', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) })
    .then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); })
    .then(function (res) {
      if (!res.ok) { showToast(res.data.error || 'Failed to save event.', 'error'); return; }
      var formatted = new Date(date + 'T00:00:00').toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
      if (id) {
        var ev = EVENTS.find(function (e) { return e.id === id; });
        ev.title = title; ev.eventType = payload.event_type; ev.date = formatted; ev.rawDate = date; ev.location = payload.location; ev.attendees = parseInt(payload.attendees, 10) || 0; ev.status = payload.status; ev.requiresTravel = payload.requires_travel; ev.requiresTypeCode = payload.requires_type_code || null;
        showToast('Event updated successfully.', 'success');
      } else {
        EVENTS.push({ id: res.data.event_id, title: title, eventType: payload.event_type, date: formatted, rawDate: date, location: payload.location, attendees: parseInt(payload.attendees, 10) || 0, status: payload.status, requiresTravel: payload.requires_travel, requiresTypeCode: payload.requires_type_code || null });
        showToast('New event added successfully.', 'success');
      }
      renderEvents();
      eventModal.classList.add('hidden'); eventModal.classList.remove('flex');
    });
});
var deleteEventModal = document.getElementById('deleteEventModal');
var pendingDeleteEventId = null;
function openDeleteEvent(id) {
  var ev = EVENTS.find(function (e) { return e.id === id; });
  pendingDeleteEventId = id;
  document.getElementById('deleteEventLabel').textContent = (ev ? ev.title : 'This event') + ' will be permanently removed.';
  deleteEventModal.classList.remove('hidden'); deleteEventModal.classList.add('flex');
}
document.getElementById('deleteEventCancelBtn').addEventListener('click', function () { deleteEventModal.classList.add('hidden'); deleteEventModal.classList.remove('flex'); });
document.getElementById('deleteEventConfirmBtn').addEventListener('click', function () {
  if (pendingDeleteEventId === null) return;
  fetch(APP_URL + '/admin/events/save', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ csrf_token: CSRF_TOKEN, action: 'delete', event_id: pendingDeleteEventId }) })
    .then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); })
    .then(function (res) {
      if (!res.ok) { showToast(res.data.error || 'Failed to delete event.', 'error'); return; }
      EVENTS = EVENTS.filter(function (e) { return e.id !== pendingDeleteEventId; });
      renderEvents();
      showToast('Event deleted.', 'info');
      deleteEventModal.classList.add('hidden'); deleteEventModal.classList.remove('flex');
    });
});

// ---------- Generic confirm modal (reject application, delete announcement, etc.) ----------
var confirmActionModal = document.getElementById('confirmActionModal');
var pendingConfirmAction = null;
function openConfirm(title, message, onConfirm) {
  document.getElementById('confirmActionTitle').textContent = title;
  document.getElementById('confirmActionMessage').textContent = message;
  pendingConfirmAction = onConfirm;
  confirmActionModal.classList.remove('hidden'); confirmActionModal.classList.add('flex');
}
function closeConfirm() { confirmActionModal.classList.add('hidden'); confirmActionModal.classList.remove('flex'); pendingConfirmAction = null; }
document.getElementById('confirmActionCancelBtn').addEventListener('click', closeConfirm);
document.getElementById('confirmActionConfirmBtn').addEventListener('click', function () {
  var action = pendingConfirmAction;
  closeConfirm();
  if (action) action();
});

// ---------- Announcements ----------
function renderAnnouncements() {
  var list = document.getElementById('annList');
  if (!ANNOUNCEMENTS.length) { list.innerHTML = '<div class="py-16 text-center text-gray-600 text-sm">No announcements yet.</div>'; return; }
  list.innerHTML = ANNOUNCEMENTS.map(function (ann) {
    return '<div class="modern-card bg-white rounded-2xl p-4 sm:p-5 border border-gray-100 flex items-start justify-between gap-3" style="box-shadow:0 1px 6px rgba(0,0,0,0.06);">'
      + '<div class="flex items-start gap-3"><div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center flex-shrink-0" style="background:#FEE2E2;"><i data-lucide="bell" class="w-4 h-4 sm:w-5 sm:h-5" style="color:#B11226;"></i></div>'
      + '<div class="min-w-0"><h4 class="font-semibold text-sm mb-1 leading-snug" style="color:#1a1a2e;">' + ann.title + '</h4>'
      + '<div class="flex items-center gap-2 text-xs text-gray-500 flex-wrap"><span>' + ann.date + '</span><span class="px-2 py-0.5 rounded-full" style="background:#FEE2E2;color:#B11226;">' + ann.type + '</span><span class="hidden sm:inline">' + ann.audience + '</span></div></div></div>'
      + '<div class="flex items-center gap-1.5 flex-shrink-0"><button class="ann-edit-btn w-10 h-10 flex items-center justify-center rounded-lg hover:opacity-80" style="background:#FEE2E2;color:#B11226;" data-id="' + ann.id + '" aria-label="Edit announcement"><i data-lucide="pencil" class="w-3.5 h-3.5"></i></button>'
      + '<button class="ann-delete-btn w-10 h-10 flex items-center justify-center rounded-lg text-gray-600 hover:text-red-500 hover:bg-red-50" data-id="' + ann.id + '" aria-label="Delete announcement"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button></div></div>';
  }).join('');
  lucide.createIcons();
  document.querySelectorAll('.ann-edit-btn').forEach(function (b) { b.addEventListener('click', function () { openEditAnn(parseInt(b.dataset.id, 10)); }); });
  document.querySelectorAll('.ann-delete-btn').forEach(function (b) { b.addEventListener('click', function () { deleteAnn(parseInt(b.dataset.id, 10)); }); });
}
var annModal = document.getElementById('annModal');
document.getElementById('addAnnBtn').addEventListener('click', function () {
  document.getElementById('annModalTitle').textContent = 'New Announcement';
  document.getElementById('annSaveLabel').textContent = 'Publish';
  document.getElementById('annIdInput').value = ''; document.getElementById('annTitleInput').value = '';
  document.getElementById('annTypeInput').value = 'Audition'; document.getElementById('annAudienceInput').value = 'All Students';
  annModal.classList.remove('hidden'); annModal.classList.add('flex');
});
function openEditAnn(id) {
  var ann = ANNOUNCEMENTS.find(function (a) { return a.id === id; });
  if (!ann) return;
  document.getElementById('annModalTitle').textContent = 'Edit Announcement';
  document.getElementById('annSaveLabel').textContent = 'Update';
  document.getElementById('annIdInput').value = ann.id;
  document.getElementById('annTitleInput').value = ann.title;
  document.getElementById('annTypeInput').value = ann.type;
  document.getElementById('annAudienceInput').value = ann.audience;
  annModal.classList.remove('hidden'); annModal.classList.add('flex');
}
document.getElementById('annModalCloseBtn').addEventListener('click', function () { annModal.classList.add('hidden'); annModal.classList.remove('flex'); });
document.getElementById('annCancelBtn').addEventListener('click', function () { annModal.classList.add('hidden'); annModal.classList.remove('flex'); });
document.getElementById('annSaveBtn').addEventListener('click', function () {
  var title = document.getElementById('annTitleInput').value.trim();
  if (!title) return;
  var id = parseInt(document.getElementById('annIdInput').value, 10) || 0;
  var type = document.getElementById('annTypeInput').value;
  var audience = document.getElementById('annAudienceInput').value;
  fetch(APP_URL + '/admin/announcements', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ csrf_token: CSRF_TOKEN, action: 'save', announcement_id: id, title: title, type: type, audience: audience }) })
    .then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); })
    .then(function (res) {
      if (!res.ok) { showToast(res.data.error || 'Failed to save announcement.', 'error'); return; }
      var today = new Date().toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
      if (id) {
        var ann = ANNOUNCEMENTS.find(function (a) { return a.id === id; });
        ann.title = title; ann.type = type; ann.audience = audience;
        showToast('Announcement updated.', 'success');
      } else {
        ANNOUNCEMENTS.unshift({ id: res.data.announcement_id, title: title, date: today, type: type, audience: audience });
        showToast('Announcement published.', 'success');
      }
      renderAnnouncements();
      annModal.classList.add('hidden'); annModal.classList.remove('flex');
    });
});
function deleteAnn(id) {
  openConfirm('Delete Announcement?', 'This announcement will be permanently removed.', function () { doDeleteAnn(id); });
}
function doDeleteAnn(id) {
  fetch(APP_URL + '/admin/announcements', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ csrf_token: CSRF_TOKEN, action: 'delete', announcement_id: id }) })
    .then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); })
    .then(function (res) {
      if (!res.ok) { showToast(res.data.error || 'Failed to delete announcement.', 'error'); return; }
      ANNOUNCEMENTS = ANNOUNCEMENTS.filter(function (a) { return a.id !== id; });
      renderAnnouncements();
      showToast('Announcement deleted.', 'info');
    });
}

// ---------- Settings (client-side only, matches original design) ----------
var defaultSettings = { systemName: 'ARTEMIS', institution: 'BatStateU ARASOF-Nasugbu', office: 'Culture and Arts Office', academicYear: '2025 – 2026', adminName: 'OCA Head', email: 'admin@batstate-u.edu.ph', role: 'OCA Administrator' };
function loadSettings() {
  var saved = {};
  try { saved = JSON.parse(localStorage.getItem('artemis_settings') || '{}'); } catch (e) {}
  var values = Object.assign({}, defaultSettings, saved);
  Object.keys(values).forEach(function (key) { var el = document.getElementById('set_' + key); if (el) el.value = values[key]; });
}
loadSettings();
document.getElementById('saveSettingsBtn').addEventListener('click', function () {
  var values = {};
  Object.keys(defaultSettings).forEach(function (key) { var el = document.getElementById('set_' + key); values[key] = el ? el.value : defaultSettings[key]; });
  try { localStorage.setItem('artemis_settings', JSON.stringify(values)); } catch (e) {}
  document.getElementById('saveSettingsLabel').textContent = 'Saved!';
  this.style.background = '#22C55E';
  showToast('Settings saved successfully.', 'success');
  var btn = this;
  setTimeout(function () { document.getElementById('saveSettingsLabel').textContent = 'Save Changes'; btn.style.background = '#B11226'; }, 2000);
});

// ---------- Charts ----------
// Chart color roles: slate = neutral volume (not a status), green/red/amber/blue/purple = reserved status hues
// (reused consistently from status_badge_html()), maroon = single-series brand identity only.
var CHART_INK = '#1a1a2e', CHART_MUTED = '#4B5563', CHART_GRID = '#f0f0f0', CHART_SURFACE = '#ffffff';
var CHART_NEUTRAL = '#64748B', CHART_GOOD = '#22C55E', CHART_CRITICAL = '#EF4444', CHART_BRAND = '#B11226';
var topBarRadius = { topLeft: 4, topRight: 4, bottomLeft: 0, bottomRight: 0 };
var rightBarRadius = { topLeft: 0, bottomLeft: 0, topRight: 4, bottomRight: 4 };
Chart.defaults.font.family = "'Inter', system-ui, sans-serif";
Chart.defaults.color = CHART_MUTED;
Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(26,26,46,0.94)';
Chart.defaults.plugins.tooltip.titleColor = '#fff';
Chart.defaults.plugins.tooltip.bodyColor = 'rgba(255,255,255,0.85)';
Chart.defaults.plugins.tooltip.padding = 10;
Chart.defaults.plugins.tooltip.cornerRadius = 8;
Chart.defaults.plugins.tooltip.boxPadding = 4;
Chart.defaults.plugins.tooltip.titleFont = { size: 12, weight: '600' };
Chart.defaults.plugins.tooltip.bodyFont = { size: 12 };
Chart.defaults.plugins.tooltip.displayColors = true;
Chart.defaults.plugins.tooltip.boxWidth = 8;
Chart.defaults.plugins.tooltip.boxHeight = 8;

var monthlyLabels = <?= json_encode(array_column($monthlyData, 'month')) ?>;
new Chart(document.getElementById('monthlyChart'), {
  type: 'bar',
  data: { labels: monthlyLabels, datasets: [
    { label: 'Submitted', data: <?= json_encode(array_column($monthlyData, 'submitted')) ?>, backgroundColor: CHART_NEUTRAL, borderRadius: topBarRadius, borderSkipped: false, maxBarThickness: 10 },
    { label: 'Approved', data: <?= json_encode(array_column($monthlyData, 'approved')) ?>, backgroundColor: CHART_GOOD, borderRadius: topBarRadius, borderSkipped: false, maxBarThickness: 10 },
  ] },
  options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { ticks: { font: { size: 11 }, color: CHART_MUTED }, grid: { display: false } }, y: { beginAtZero: true, ticks: { font: { size: 11 }, color: CHART_MUTED, precision: 0 }, grid: { color: CHART_GRID } } } },
});
new Chart(document.getElementById('statusDonut'), {
  type: 'doughnut',
  data: { labels: ['Approved', 'Pending', 'Under Review', 'Evaluation', 'Rejected'], datasets: [{ data: [<?= (int) $statusCounts['Approved'] ?>, <?= (int) $statusCounts['Pending'] ?>, <?= (int) $statusCounts['Under Review'] ?>, <?= (int) $statusCounts['Evaluation'] ?>, <?= (int) $statusCounts['Rejected'] ?>], backgroundColor: [CHART_GOOD, '#F59E0B', '#3B82F6', '#A855F7', CHART_CRITICAL], borderColor: CHART_SURFACE, borderWidth: 3, hoverOffset: 6, hoverBorderWidth: 3 }] },
  options: { responsive: true, maintainAspectRatio: false, cutout: '68%', plugins: { legend: { display: false } } },
  plugins: [{
    id: 'donutCenterLabel',
    afterDraw: function (chart) {
      var total = chart.data.datasets[0].data.reduce(function (a, b) { return a + b; }, 0);
      var ctx = chart.ctx, w = chart.width, h = chart.height;
      ctx.save();
      ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
      ctx.font = "700 20px 'Inter', system-ui, sans-serif"; ctx.fillStyle = CHART_INK;
      ctx.fillText(total, w / 2, h / 2 - 8);
      ctx.font = "500 10px 'Inter', system-ui, sans-serif"; ctx.fillStyle = CHART_MUTED;
      ctx.fillText('TOTAL', w / 2, h / 2 + 12);
      ctx.restore();
    },
  }],
});
new Chart(document.getElementById('approvalTrendChart'), {
  type: 'line',
  data: { labels: monthlyLabels, datasets: [{ label: 'Approval Rate', data: <?= json_encode(array_column($monthlyData, 'rate')) ?>, borderColor: CHART_BRAND, borderWidth: 2, backgroundColor: 'rgba(177,18,38,0.08)', fill: true, tension: 0.35, pointRadius: 4, pointBackgroundColor: CHART_BRAND, pointBorderColor: CHART_SURFACE, pointBorderWidth: 2, pointHoverRadius: 6 }] },
  options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { callbacks: { label: function (ctx) { return ctx.parsed.y + '% approval rate'; } } } }, scales: { y: { min: 0, max: 100, ticks: { callback: function (v) { return v + '%'; }, color: CHART_MUTED, font: { size: 11 } }, grid: { color: CHART_GRID } }, x: { ticks: { color: CHART_MUTED, font: { size: 11 } }, grid: { display: false } } } },
});
new Chart(document.getElementById('typeBarChart'), {
  type: 'bar',
  data: { labels: MODULES.map(function (m) { return m.label; }), datasets: [{ label: 'Applications', data: MODULES.map(function (m) { return APPLICATIONS.filter(function (a) { return a.typeCode === m.code; }).length; }), backgroundColor: CHART_BRAND, borderRadius: rightBarRadius, borderSkipped: false, maxBarThickness: 14 }] },
  options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { font: { size: 11 }, color: CHART_MUTED, precision: 0 }, grid: { color: CHART_GRID } }, y: { ticks: { font: { size: 10 }, color: '#6B7280' }, grid: { display: false } } } },
});
new Chart(document.getElementById('monthlyVolumeChart'), {
  type: 'bar',
  data: { labels: monthlyLabels, datasets: [
    { label: 'Submitted', data: <?= json_encode(array_column($monthlyData, 'submitted')) ?>, backgroundColor: CHART_NEUTRAL, borderRadius: topBarRadius, borderSkipped: false, maxBarThickness: 8 },
    { label: 'Approved', data: <?= json_encode(array_column($monthlyData, 'approved')) ?>, backgroundColor: CHART_GOOD, borderRadius: topBarRadius, borderSkipped: false, maxBarThickness: 8 },
    { label: 'Rejected', data: <?= json_encode(array_column($monthlyData, 'rejected')) ?>, backgroundColor: CHART_CRITICAL, borderRadius: topBarRadius, borderSkipped: false, maxBarThickness: 8 },
  ] },
  options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { font: { size: 11 }, boxWidth: 8, boxHeight: 8, color: '#6B7280', usePointStyle: true, pointStyle: 'circle' } } }, scales: { x: { ticks: { font: { size: 11 }, color: CHART_MUTED }, grid: { display: false } }, y: { beginAtZero: true, ticks: { font: { size: 11 }, color: CHART_MUTED, precision: 0 }, grid: { color: CHART_GRID } } } },
});

// Initial render
renderApplications();
</script>

</body>
</html>
