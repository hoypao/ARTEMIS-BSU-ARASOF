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
<script src="<?= APP_URL ?>/assets/js/qrcode-core.js"></script>
<script src="<?= APP_URL ?>/assets/js/qrcode-utf8.js"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/modern.css">
<script src="<?= APP_URL ?>/assets/js/shell-scroll.js" defer></script>
<style>
  /* Scroll progress bar — sits above the sidebar/header, fills with scroll depth */

  /* Hide the native scrollbar while keeping scroll behavior — the bar above is the scroll indicator, matching the landing page.
     `clip` rather than `hidden`: both stop sideways scrolling, but `hidden`
     makes html/body a scroll container, which silently kills position:sticky
     for every descendant — the top bar would just scroll away with the
     content. `clip` has no scrollport, so the floating bar actually sticks. */
  html, body { overflow-x: clip; scrollbar-width: none; -ms-overflow-style: none; }
  html::-webkit-scrollbar, body::-webkit-scrollbar { display: none; width: 0; height: 0; }
  body { font-family: 'Inter', system-ui, sans-serif; background:#F7F5F2; }
  .dash-input:focus { border-color:#B11226; }

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
<div id="scrollProgressBar"></div>

<div class="min-h-screen flex bg-background">
  <!-- Sidebar - desktop only -->
  <aside class="shell-sidebar float-shadow-brand hidden lg:flex flex-col w-64 pt-0 fixed z-30" style="background: linear-gradient(180deg, #B11226 0%, #7a0d1a 100%);">
    <div class="px-6 py-4 border-b border-white/10">
      <div class="flex items-center gap-3">
        <?php if ($profilePhotoUrl): ?>
          <img src="<?= e($profilePhotoUrl) ?>" alt="<?= e($fullName) ?>" class="w-10 h-10 rounded-xl object-cover flex-shrink-0">
        <?php else: ?>
          <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center text-white font-bold text-sm flex-shrink-0"><?= e($initials) ?></div>
        <?php endif; ?>
        <div class="flex-1 min-w-0">
          <div class="text-white text-sm font-semibold truncate"><?= e($fullName) ?></div>
          <div class="text-red-200 text-xs truncate"><?= e($user['course'] ?? '') ?></div>
        </div>
      </div>
      <div class="mt-2 inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium"
        style="background: <?= $profile && $profile['active_member'] ? 'rgba(34,197,94,0.2)' : 'rgba(156,163,175,0.2)' ?>; color: <?= $profile && $profile['active_member'] ? '#86EFAC' : '#D1D5DB' ?>;">
        <span class="w-1.5 h-1.5 rounded-full" style="background: <?= $profile && $profile['active_member'] ? '#4ade80' : '#9ca3af' ?>;"></span>
        <?= $profile && $profile['active_member'] ? 'Active Artist' : 'Cultural Artist' ?>
      </div>
    </div>

    <nav class="flex-1 px-4 py-4 flex flex-col gap-1">
      <button data-tab="dashboard" class="dash-nav-btn flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm transition-all text-left" style="background: rgba(255,255,255,0.2); color:#fff; font-weight:600;">
        <i data-lucide="home" class="w-4 h-4"></i> Dashboard
      </button>
      <button data-tab="applications" class="dash-nav-btn flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm transition-all text-left" style="color: rgba(255,255,255,0.7);">
        <i data-lucide="file-text" class="w-4 h-4"></i> Applications
      </button>
      <button data-tab="profile" class="dash-nav-btn flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm transition-all text-left" style="color: rgba(255,255,255,0.7);">
        <i data-lucide="user" class="w-4 h-4"></i> Profile
      </button>
    </nav>

    <div class="px-4 pb-4 flex flex-col gap-2 border-t border-white/10 pt-4">
      <button type="button" id="sidebarApplyBtn" class="modern-btn flex items-center gap-2 px-3 py-2.5 rounded-xl text-sm font-semibold text-white transition-all" style="background:#D4AF37;">
        <i data-lucide="plus" class="w-4 h-4"></i> Submit Application
      </button>
      <div class="grid grid-cols-2 gap-2">
        <a href="<?= e(APP_URL) ?>" class="flex items-center justify-center gap-1.5 px-3 py-2.5 rounded-xl text-xs font-medium transition-all hover:bg-white/10" style="color: rgba(255,255,255,0.7); border:1px solid rgba(255,255,255,0.15);">
          <i data-lucide="home" class="w-3.5 h-3.5"></i> Home
        </a>
        <a href="<?= e(APP_URL) ?>/logout" class="flex items-center justify-center gap-1.5 px-3 py-2.5 rounded-xl text-xs font-medium transition-all hover:bg-white/10" style="color: rgba(255,255,255,0.7); border:1px solid rgba(255,255,255,0.15);">
          <i data-lucide="log-out" class="w-3.5 h-3.5"></i> Sign Out
        </a>
      </div>
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
            <h1 class="font-bold text-base" style="color:#1a1a2e;" id="pageTitleDesktop">Student Dashboard</h1>
            <p class="text-xs text-gray-500"><?= e(date('l, F j, Y')) ?></p>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <div class="relative hidden sm:block">
            <label for="appSearchInput" class="sr-only">Search applications</label>
            <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
            <input id="appSearchInput" placeholder="Search applications..." class="pl-9 pr-4 py-2 rounded-xl bg-gray-50 border border-gray-200 text-sm focus:outline-none w-48">
          </div>

          <button type="button" id="mobileApplyBtn" class="lg:hidden w-10 h-10 flex items-center justify-center rounded-xl text-white" style="background:#D4AF37;" aria-label="New application">
            <i data-lucide="plus" class="w-4 h-4"></i>
          </button>

          <div class="relative">
            <button type="button" id="notifBtn" class="relative w-10 h-10 flex items-center justify-center rounded-xl bg-gray-50 border border-gray-200">
              <i data-lucide="bell" class="w-4 h-4 text-gray-600"></i>
              <?php if ($notifications): ?>
                <span id="notifBadge" class="absolute -top-0.5 -right-0.5 w-4 h-4 rounded-full text-white flex items-center justify-center" style="background:#B11226; font-size:9px;"><?= count($notifications) ?></span>
              <?php endif; ?>
            </button>

            <div id="notifDropdown" class="hidden absolute top-10 right-0 w-72 max-w-[calc(100vw-2rem)] bg-white rounded-2xl border border-gray-100 z-50 overflow-hidden" style="box-shadow: 0 8px 32px rgba(0,0,0,0.12);">
              <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                <span class="font-semibold text-sm" style="color:#1a1a2e;">Notifications</span>
                <button type="button" id="notifCloseBtn" aria-label="Close notifications"><i data-lucide="x" class="w-4 h-4 text-gray-400"></i></button>
              </div>
              <?php if (!$notifications): ?>
                <div class="px-4 py-6 text-center text-xs text-gray-400">You're all caught up.</div>
              <?php endif; ?>
              <?php foreach ($notifications as $n): ?>
                <div class="notif-item px-4 py-3 border-b border-gray-50 flex gap-3 hover:bg-gray-50 cursor-pointer"
                  data-app-code="<?= e($n['appCode'] ?? '') ?>" data-ann-id="<?= e((string) ($n['announcementId'] ?? '')) ?>">
                  <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0" style="background: <?= e($n['color']) ?>20;">
                    <i data-lucide="<?= e($n['icon']) ?>" class="w-4 h-4" style="color: <?= e($n['color']) ?>;"></i>
                  </div>
                  <div>
                    <div class="text-xs font-medium" style="color:#1a1a2e;"><?= e($n['msg']) ?></div>
                    <div class="text-xs text-gray-400"><?= e($n['time']) ?></div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

        </div>
      </div>

      <div class="lg:hidden px-4 pb-2.5 flex items-center justify-between">
        <div>
          <p class="font-bold text-sm" style="color:#1a1a2e;" id="pageTitleMobile">Student Dashboard</p>
          <p class="text-xs text-gray-400"><?= e(date('D, F j, Y')) ?></p>
        </div>
      </div>
    </header>

    <main class="flex-1 p-4 sm:p-6 lg:p-8 pb-24 lg:pb-8">

      <?php if ($flashSuccess = flash_get('success')): ?>
        <div class="rounded-2xl p-4 mb-4 sm:mb-6 border text-sm font-medium" style="background:#DCFCE7; border-color:#22C55E; color:#15803D;"><?= e($flashSuccess) ?></div>
      <?php endif; ?>
      <?php if ($flashError = flash_get('error')): ?>
        <div class="rounded-2xl p-4 mb-4 sm:mb-6 border text-sm font-medium" style="background:#FEE2E2; border-color:#EF4444; color:#B91C1C;"><?= e($flashError) ?></div>
      <?php endif; ?>

      <!-- DASHBOARD TAB -->
      <div class="tab-panel flex flex-col gap-4 sm:gap-6" data-panel="dashboard">
        <div class="rounded-2xl p-5 sm:p-6 text-white relative overflow-hidden" style="background: linear-gradient(135deg, #B11226 0%, #7a0d1a 100%);">
          <div class="absolute right-0 top-0 bottom-0 opacity-10">
            <i data-lucide="palette" class="w-40 h-40 sm:w-48 sm:h-48 -mt-8 -mr-8"></i>
          </div>
          <div class="relative">
            <div class="text-red-200 text-xs sm:text-sm mb-0.5">Welcome back,</div>
            <h2 class="text-xl sm:text-2xl font-bold text-white mb-0.5"><?= e($fullName) ?></h2>
            <div class="text-red-100 text-xs sm:text-sm"><?= e($user['course'] ?? '') ?> &middot; Student ID: <?= e($user['id_number'] ?? '') ?></div>
            <div class="flex gap-2 mt-4">
              <button type="button" class="apply-trigger modern-btn px-3.5 py-2 rounded-xl text-xs sm:text-sm font-semibold flex items-center gap-1.5" style="background:#D4AF37; color:#1a1a2e;">
                <i data-lucide="plus" class="w-3.5 h-3.5"></i> New Application
              </button>
              <button type="button" data-tab-link="applications" class="px-3.5 py-2 rounded-xl text-xs sm:text-sm font-semibold text-white flex items-center gap-1.5" style="background: rgba(255,255,255,0.15); border:1px solid rgba(255,255,255,0.2);">
                View All
              </button>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
          <div class="modern-card bg-white rounded-2xl p-4 sm:p-5 border border-gray-100" style="box-shadow: 0 1px 4px rgba(0,0,0,0.06);">
            <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center mb-3" style="background:#DBEAFE;"><i data-lucide="file-text" class="w-4 h-4 sm:w-5 sm:h-5" style="color:#3B82F6;"></i></div>
            <div class="text-xl sm:text-2xl font-bold mb-0.5" style="color:#1a1a2e;"><?= $statTotal ?></div>
            <div class="text-xs text-gray-500 leading-tight">Total Applications</div>
          </div>
          <div class="modern-card bg-white rounded-2xl p-4 sm:p-5 border border-gray-100" style="box-shadow: 0 1px 4px rgba(0,0,0,0.06);">
            <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center mb-3" style="background:#FEF9C3;"><i data-lucide="clock" class="w-4 h-4 sm:w-5 sm:h-5" style="color:#F59E0B;"></i></div>
            <div class="text-xl sm:text-2xl font-bold mb-0.5" style="color:#1a1a2e;"><?= $statPending ?></div>
            <div class="text-xs text-gray-500 leading-tight">Pending</div>
          </div>
          <div class="modern-card bg-white rounded-2xl p-4 sm:p-5 border border-gray-100" style="box-shadow: 0 1px 4px rgba(0,0,0,0.06);">
            <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center mb-3" style="background:#DCFCE7;"><i data-lucide="check-circle" class="w-4 h-4 sm:w-5 sm:h-5" style="color:#22C55E;"></i></div>
            <div class="text-xl sm:text-2xl font-bold mb-0.5" style="color:#1a1a2e;"><?= $statApproved ?></div>
            <div class="text-xs text-gray-500 leading-tight">Approved</div>
          </div>
          <div class="modern-card bg-white rounded-2xl p-4 sm:p-5 border border-gray-100" style="box-shadow: 0 1px 4px rgba(0,0,0,0.06);">
            <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center mb-3" style="background:#FEE2E2;"><i data-lucide="x-circle" class="w-4 h-4 sm:w-5 sm:h-5" style="color:#EF4444;"></i></div>
            <div class="text-xl sm:text-2xl font-bold mb-0.5" style="color:#1a1a2e;"><?= $statRejected ?></div>
            <div class="text-xs text-gray-500 leading-tight">Rejected</div>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="modern-card bg-white rounded-2xl border border-gray-100 p-5" style="box-shadow: 0 1px 4px rgba(0,0,0,0.06);">
            <h3 class="font-semibold text-sm mb-3" style="color:#1a1a2e;">Application Status</h3>
            <div class="flex items-center gap-4">
              <div style="width:110px;height:110px;"><canvas id="statusDonut" width="110" height="110"></canvas></div>
              <div class="flex flex-col gap-1.5 flex-1">
                <?php foreach ([['Approved','#22C55E'], ['Pending','#F59E0B'], ['Under Review','#3B82F6'], ['Evaluation','#A855F7'], ['Rejected','#EF4444']] as [$label, $color]): ?>
                  <div class="flex items-center justify-between">
                    <div class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full" style="background:<?= e($color) ?>;"></span><span class="text-xs text-gray-500"><?= e($label) ?></span></div>
                    <span class="text-xs font-semibold" style="color:#1a1a2e;"><?= (int) $statusCounts[$label] ?></span>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

          <div class="modern-card bg-white rounded-2xl border border-gray-100 p-5" style="box-shadow: 0 1px 4px rgba(0,0,0,0.06);">
            <h3 class="font-semibold text-sm mb-3" style="color:#1a1a2e;">My Applications by Type</h3>
            <div style="height:110px;"><canvas id="typeBar"></canvas></div>
          </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-4 sm:gap-6">
          <div class="modern-card xl:col-span-2 bg-white rounded-2xl border border-gray-100 overflow-hidden" style="box-shadow: 0 1px 4px rgba(0,0,0,0.06);">
            <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-100">
              <h3 class="font-semibold text-sm" style="color:#1a1a2e;">Recent Applications</h3>
              <button type="button" data-tab-link="applications" class="text-xs flex items-center gap-1" style="color:#B11226;">View All <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i></button>
            </div>

            <?php if (!$applications): ?>
              <div class="px-5 py-6 text-center text-xs text-gray-400">You haven't submitted any applications yet.</div>
            <?php endif; ?>

            <div class="sm:hidden flex flex-col divide-y divide-gray-50">
              <?php foreach (array_slice($applications, 0, 4) as $app): ?>
                <div class="px-5 py-3.5 flex items-center justify-between gap-3">
                  <div class="flex-1 min-w-0">
                    <div class="text-xs font-medium truncate mb-0.5" style="color:#1a1a2e;"><?= e($app['type_name']) ?></div>
                    <div class="text-xs text-gray-400 font-mono"><?= e($app['application_code']) ?> &middot; <?= format_date($app['submitted_at']) ?></div>
                  </div>
                  <div class="flex items-center gap-2 flex-shrink-0">
                    <?= status_badge_html($app['status']) ?>
                    <button type="button" class="view-app-btn p-1.5 rounded-lg" style="background:#FEE2E2; color:#B11226;" data-app="<?= (int) $app['application_id'] ?>" aria-label="View application"><i data-lucide="eye" class="w-3.5 h-3.5"></i></button>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>

            <?php if ($applications): ?>
            <div class="hidden sm:block overflow-x-auto">
              <table class="w-full text-sm">
                <thead><tr style="background:#F9FAFB;"><th class="text-left px-4 py-3 text-xs font-medium text-gray-500">App ID</th><th class="text-left px-4 py-3 text-xs font-medium text-gray-500">Type</th><th class="text-left px-4 py-3 text-xs font-medium text-gray-500">Date</th><th class="text-left px-4 py-3 text-xs font-medium text-gray-500">Status</th><th class="text-left px-4 py-3 text-xs font-medium text-gray-500"></th></tr></thead>
                <tbody>
                  <?php foreach (array_slice($applications, 0, 4) as $app): ?>
                    <tr class="border-t border-gray-50 hover:bg-gray-50 transition-colors">
                      <td class="px-4 py-3 text-xs font-mono text-gray-600"><?= e($app['application_code']) ?></td>
                      <td class="px-4 py-3 text-xs font-medium" style="color:#1a1a2e;"><?= e($app['type_name']) ?></td>
                      <td class="px-4 py-3 text-xs text-gray-500"><?= format_date($app['submitted_at']) ?></td>
                      <td class="px-4 py-3"><?= status_badge_html($app['status']) ?></td>
                      <td class="px-4 py-3"><button type="button" class="view-app-btn text-xs p-1.5 rounded-lg hover:bg-red-50 transition-colors" style="color:#B11226;" data-app="<?= (int) $app['application_id'] ?>" aria-label="View application"><i data-lucide="eye" class="w-3.5 h-3.5"></i></button></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php endif; ?>
          </div>

          <div class="flex flex-col gap-4">
            <div class="modern-card bg-white rounded-2xl border border-gray-100" style="box-shadow: 0 1px 4px rgba(0,0,0,0.06);">
              <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-100">
                <h3 class="font-semibold text-sm" style="color:#1a1a2e;">Announcements</h3>
                <i data-lucide="bell" class="w-4 h-4 text-gray-400"></i>
              </div>
              <div class="flex flex-col divide-y divide-gray-50">
                <?php if (!$announcements): ?><div class="px-5 py-4 text-xs text-gray-400">No announcements yet.</div><?php endif; ?>
                <?php foreach ($announcements as $ann): ?>
                  <div class="ann-row px-5 py-3 hover:bg-gray-50 cursor-pointer transition-all duration-500" data-ann-id="<?= (int) $ann['announcement_id'] ?>">
                    <?php if ($ann['is_urgent']): ?>
                      <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full mb-1.5" style="background:#FEE2E2; color:#B91C1C;"><i data-lucide="alert-circle" class="w-3 h-3"></i> Action Required</span>
                    <?php endif; ?>
                    <div class="text-xs font-medium mb-0.5" style="color:#1a1a2e;"><?= e($ann['title']) ?></div>
                    <div class="text-xs text-gray-400"><?= format_date($ann['created_at'], 'M j') ?></div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>

            <div class="modern-card bg-white rounded-2xl border border-gray-100" style="box-shadow: 0 1px 4px rgba(0,0,0,0.06);">
              <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-100">
                <h3 class="font-semibold text-sm" style="color:#1a1a2e;">Upcoming Events</h3>
                <i data-lucide="calendar" class="w-4 h-4 text-gray-400"></i>
              </div>
              <div class="flex flex-col divide-y divide-gray-50">
                <?php if (!$upcomingEvents): ?><div class="px-5 py-4 text-xs text-gray-400">No upcoming events.</div><?php endif; ?>
                <?php foreach ($upcomingEvents as $ev): ?>
                  <div class="px-5 py-3 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl flex flex-col items-center justify-center flex-shrink-0 text-white text-xs" style="background:#B11226;">
                      <span class="font-bold"><?= e(date('j', strtotime($ev['event_date']))) ?></span>
                      <span class="opacity-80"><?= e(date('M', strtotime($ev['event_date']))) ?></span>
                    </div>
                    <div>
                      <div class="text-xs font-medium" style="color:#1a1a2e;"><?= e($ev['title']) ?></div>
                      <div class="text-xs text-gray-400"><?= e($ev['location']) ?></div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>

        <div class="modern-card bg-white rounded-2xl border border-gray-100 p-5 sm:p-6" style="box-shadow: 0 1px 4px rgba(0,0,0,0.06);">
          <h3 class="font-semibold text-sm mb-4" style="color:#1a1a2e;">Quick Actions</h3>
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <button type="button" class="apply-trigger flex flex-col items-center gap-2 p-4 rounded-xl border border-gray-100 active:scale-95 transition-all hover:border-gray-200 hover:bg-gray-50">
              <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:#FEE2E2;"><i data-lucide="plus" class="w-5 h-5" style="color:#B11226;"></i></div>
              <span class="text-[11px] font-medium text-center leading-tight" style="color:#1a1a2e;">New Application</span>
            </button>
            <button type="button" data-tab-link="applications" class="flex flex-col items-center gap-2 p-4 rounded-xl border border-gray-100 active:scale-95 transition-all hover:border-gray-200 hover:bg-gray-50">
              <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:#DBEAFE;"><i data-lucide="eye" class="w-5 h-5" style="color:#3B82F6;"></i></div>
              <span class="text-[11px] font-medium text-center leading-tight" style="color:#1a1a2e;">My Applications</span>
            </button>
            <button type="button" data-tab-link="profile" class="flex flex-col items-center gap-2 p-4 rounded-xl border border-gray-100 active:scale-95 transition-all hover:border-gray-200 hover:bg-gray-50">
              <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:#FEF9C3;"><i data-lucide="user" class="w-5 h-5" style="color:#D4AF37;"></i></div>
              <span class="text-[11px] font-medium text-center leading-tight" style="color:#1a1a2e;">My Profile</span>
            </button>
            <button type="button" id="helpQuickBtn" class="flex flex-col items-center gap-2 p-4 rounded-xl border border-gray-100 active:scale-95 transition-all hover:border-gray-200 hover:bg-gray-50">
              <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:#DCFCE7;"><i data-lucide="help-circle" class="w-5 h-5" style="color:#22C55E;"></i></div>
              <span class="text-[11px] font-medium text-center leading-tight" style="color:#1a1a2e;">Help & Support</span>
            </button>
          </div>
        </div>
      </div>

      <!-- APPLICATIONS TAB -->
      <div class="tab-panel hidden flex-col gap-4 sm:gap-6" data-panel="applications">
        <div class="flex items-center justify-between">
          <h2 class="font-bold text-base sm:text-lg" style="color:#1a1a2e;">My Applications</h2>
          <button type="button" class="apply-trigger modern-btn flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs sm:text-sm font-semibold text-white" style="background:#B11226;"><i data-lucide="plus" class="w-3.5 h-3.5"></i> New</button>
        </div>

        <?php if (!$applications): ?>
          <div class="modern-card bg-white rounded-2xl border border-gray-100 p-8 text-center text-sm text-gray-400">You haven't submitted any applications yet.</div>
        <?php endif; ?>

        <div class="sm:hidden flex flex-col gap-3">
          <?php foreach ($applications as $app): ?>
            <div class="app-row modern-card bg-white rounded-2xl border border-gray-100 p-4 transition-all duration-500" data-code="<?= e($app['application_code']) ?>" style="box-shadow: 0 1px 4px rgba(0,0,0,0.06);">
              <div class="flex items-start justify-between gap-2 mb-3">
                <div>
                  <div class="text-sm font-semibold leading-tight mb-0.5" style="color:#1a1a2e;"><?= e($app['type_name']) ?></div>
                  <div class="text-xs text-gray-400 font-mono"><?= e($app['application_code']) ?></div>
                </div>
                <?= status_badge_html($app['status']) ?>
              </div>
              <div class="flex items-center gap-1 mb-1.5">
                <?php for ($s = 1; $s <= 5; $s++): ?>
                  <div class="h-1.5 flex-1 rounded-full" style="background: <?= $s <= $app['current_stage'] ? '#B11226' : '#E5E7EB' ?>;"></div>
                <?php endfor; ?>
              </div>
              <div class="flex items-center justify-between">
                <div class="text-xs text-gray-400"><?= format_date($app['submitted_at']) ?> &middot; <?= e(application_progress_stages($app['type_code'])[$app['current_stage'] - 1]) ?></div>
                <button type="button" class="view-app-btn flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-medium" style="background:#FEE2E2; color:#B11226;" data-app="<?= (int) $app['application_id'] ?>"><i data-lucide="eye" class="w-3 h-3"></i> View</button>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <?php if ($applications): ?>
        <div class="modern-card hidden sm:block bg-white rounded-2xl border border-gray-100 overflow-hidden" style="box-shadow: 0 1px 4px rgba(0,0,0,0.06);">
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead><tr style="background:#F9FAFB;">
                <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500">Application ID</th>
                <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500">Type</th>
                <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500">Date Submitted</th>
                <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500">Status</th>
                <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500">Progress</th>
                <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500">Action</th>
              </tr></thead>
              <tbody>
                <?php foreach ($applications as $app): ?>
                  <tr class="app-row border-t border-gray-50 hover:bg-gray-50 transition-all duration-500" data-code="<?= e($app['application_code']) ?>">
                    <td class="px-5 py-4 text-xs font-mono text-gray-600"><?= e($app['application_code']) ?></td>
                    <td class="px-5 py-4 text-xs font-medium" style="color:#1a1a2e;"><?= e($app['type_name']) ?></td>
                    <td class="px-5 py-4 text-xs text-gray-500"><?= format_date($app['submitted_at']) ?></td>
                    <td class="px-5 py-4"><?= status_badge_html($app['status']) ?></td>
                    <td class="px-5 py-4 w-48">
                      <div class="flex items-center gap-1">
                        <?php for ($s = 1; $s <= 5; $s++): ?>
                          <div class="h-1.5 flex-1 rounded-full" style="background: <?= $s <= $app['current_stage'] ? '#B11226' : '#E5E7EB' ?>;"></div>
                        <?php endfor; ?>
                      </div>
                      <div class="text-xs text-gray-400 mt-0.5"><?= e(application_progress_stages($app['type_code'])[$app['current_stage'] - 1]) ?></div>
                    </td>
                    <td class="px-5 py-4"><button type="button" class="view-app-btn flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition-all hover:opacity-90" style="background:#FEE2E2; color:#B11226;" data-app="<?= (int) $app['application_id'] ?>"><i data-lucide="eye" class="w-3.5 h-3.5"></i> View</button></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
        <?php endif; ?>

        <?php if ($benefits): ?>
        <div class="flex items-center justify-between mt-2">
          <h2 class="font-bold text-base sm:text-lg" style="color:#1a1a2e;">My Benefits</h2>
        </div>
        <div class="flex flex-col gap-3">
          <?php foreach ($benefits as $b): ?>
            <div class="modern-card bg-white rounded-2xl border border-gray-100 p-4 sm:p-5" style="box-shadow: 0 1px 4px rgba(0,0,0,0.06);">
              <div class="flex items-start justify-between gap-2 mb-2">
                <div>
                  <div class="text-sm font-semibold" style="color:#1a1a2e;"><?= e($b['benefit_type']) ?></div>
                  <div class="text-xs text-gray-400"><?= e($b['academic_year']) ?><?= $b['semester'] ? ' &middot; ' . e($b['semester']) . ' Sem' : '' ?> &middot; Granted <?= format_date($b['granted_at'], 'M j, Y') ?></div>
                </div>
                <span class="px-2.5 py-1 rounded-full text-[10px] font-semibold" style="background: <?= $b['status'] === 'Active' ? '#DCFCE7' : '#F3F4F6' ?>; color: <?= $b['status'] === 'Active' ? '#15803D' : '#6B7280' ?>;"><?= e($b['status']) ?></span>
              </div>
              <?php if ($b['amount'] !== null): ?>
                <div class="text-xs text-gray-600 mb-2">Amount: <span class="font-semibold" style="color:#B11226;">Php <?= number_format((float) $b['amount'], 2) ?></span></div>
              <?php elseif ($b['grade'] !== null): ?>
                <div class="text-xs text-gray-600 mb-2">Grade: <span class="font-semibold" style="color:#B11226;"><?= number_format((float) $b['grade'], 2) ?></span></div>
              <?php endif; ?>

              <?php if ($b['benefit_type'] === 'Stipend'): ?>
                <?php if ($b['completion_submitted_at']): ?>
                  <div class="rounded-xl p-3 text-xs" style="background:#F0FDF4; border:1px solid #DCFCE7;">
                    <div class="flex items-center gap-1.5 font-semibold mb-1" style="color:#15803D;"><i data-lucide="check-circle" class="w-3.5 h-3.5"></i> Completion report submitted <?= format_date($b['completion_submitted_at'], 'M j, Y') ?></div>
                    <p class="text-gray-600 leading-relaxed"><?= nl2br(e($b['completion_report'])) ?></p>
                    <?php if ($b['completion_file_path']): ?>
                      <a href="<?= e(APP_URL . '/' . $b['completion_file_path']) ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-1 mt-2 text-xs font-medium" style="color:#B11226;"><i data-lucide="paperclip" class="w-3 h-3"></i> View attachment</a>
                    <?php endif; ?>
                  </div>
                <?php else: ?>
                  <details class="border-t border-gray-100 pt-2 mt-1">
                    <summary class="text-xs font-medium cursor-pointer" style="color:#B11226;">Submit end-of-semester completion report (Art. IX Sec. 34)</summary>
                    <form method="POST" action="<?= e(APP_URL) ?>/benefits/report" enctype="multipart/form-data" class="flex flex-col gap-2 mt-3">
                      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                      <input type="hidden" name="benefit_id" value="<?= (int) $b['benefit_id'] ?>">
                      <textarea name="completion_report" rows="3" required placeholder="Describe the competitions/activities completed and how the stipend contributed to your artistic development." class="dash-input modern-input w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none"></textarea>
                      <input type="file" name="completion_file" accept=".pdf,.jpg,.jpeg,.png,.mp4,.mov" class="text-xs text-gray-500">
                      <p class="text-[10px] text-gray-400">Optional photo, video, or PDF documentation. Max 20MB.</p>
                      <button type="submit" class="modern-btn self-start px-4 py-2 rounded-xl text-xs font-semibold text-white" style="background:#B11226;">Submit Report</button>
                    </form>
                  </details>
                <?php endif; ?>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- PROFILE TAB -->
      <div class="tab-panel hidden flex-col gap-4 sm:gap-5 max-w-2xl" data-panel="profile">
        <div class="modern-card bg-white rounded-2xl p-5 sm:p-6 border border-gray-100" style="box-shadow: 0 1px 4px rgba(0,0,0,0.06);">
          <div class="flex items-center gap-4">
            <div class="relative flex-shrink-0">
              <?php if ($profilePhotoUrl): ?>
                <img src="<?= e($profilePhotoUrl) ?>" alt="<?= e($fullName) ?>" class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl object-cover">
              <?php else: ?>
                <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl flex items-center justify-center text-white text-xl sm:text-2xl font-bold" style="background: linear-gradient(135deg, #B11226, #7a0d1a);"><?= e($initials) ?></div>
              <?php endif; ?>
              <form method="POST" action="<?= e(APP_URL) ?>/profile/photo" enctype="multipart/form-data" id="photoUploadForm">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <label for="photoUploadInput" class="absolute -bottom-1 -right-1 w-7 h-7 rounded-full flex items-center justify-center cursor-pointer border-2 border-white hover:opacity-90 transition-opacity" style="background:#B11226;" aria-label="Change profile photo" title="Change profile photo">
                  <i data-lucide="camera" class="w-3.5 h-3.5 text-white"></i>
                </label>
                <input type="file" id="photoUploadInput" name="photo" accept="image/jpeg,image/png,image/webp" class="hidden">
              </form>
            </div>
            <div>
              <h2 class="text-lg sm:text-xl font-bold" style="color:#1a1a2e;"><?= e($fullName) ?></h2>
              <div class="text-sm text-gray-500"><?= e($user['course'] ?? '') ?> &middot; Student ID: <?= e($user['id_number'] ?? '') ?></div>
              <div class="mt-1.5 inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs" style="background: <?= $profile && $profile['active_member'] ? '#DCFCE7' : '#F3F4F6' ?>; color: <?= $profile && $profile['active_member'] ? '#15803D' : '#6B7280' ?>;">
                <span class="w-1.5 h-1.5 rounded-full" style="background: <?= $profile && $profile['active_member'] ? '#4ade80' : '#9ca3af' ?>;"></span>
                <?= $profile && $profile['active_member'] ? 'Active Cultural Artist' : 'Cultural Artist' ?>
              </div>
            </div>
          </div>
        </div>

        <div class="modern-card bg-white rounded-2xl p-5 sm:p-6 border border-gray-100 flex flex-col items-center text-center" style="box-shadow: 0 1px 4px rgba(0,0,0,0.06);">
          <h3 class="font-semibold text-sm mb-1 self-start" style="color:#1a1a2e;">Event Check-In QR Code</h3>
          <p class="text-xs text-gray-500 mb-4 self-start">Ipakita ito sa OCA staff sa pasukan ng event para awtomatikong ma-mark na "Attended" ang pagdalo mo.</p>
          <div id="checkinQrBox" class="p-3 bg-white rounded-xl border border-gray-200 inline-block"></div>
          <p class="text-[10px] text-gray-400 mt-3">Personal at hindi dapat ipa-screenshot o ipasa sa iba &mdash; ikaw lang ang gagamit nito.</p>
        </div>

        <div class="modern-card bg-white rounded-2xl p-5 sm:p-6 border border-gray-100" style="box-shadow: 0 1px 4px rgba(0,0,0,0.06);">
          <h3 class="font-semibold text-sm mb-1" style="color:#1a1a2e;">Academic Standing</h3>
          <p class="text-xs text-gray-500 mb-4">Submitted to the Head of OCA at midterm and finals each semester (Art. V Sec. 15-C.1.a, 15-C.3).</p>

          <?php if ($isOnProbation): ?>
            <div class="rounded-xl p-3.5 mb-4" style="background:#FEE2E2; border:1px solid #FCA5A5;">
              <div class="flex items-center gap-1.5 font-semibold text-sm mb-1" style="color:#B91C1C;"><i data-lucide="alert-triangle" class="w-4 h-4"></i> On Academic Probation</div>
              <p class="text-xs leading-relaxed" style="color:#7F1D1D;"><?= e($profile['probation_reason'] ?? '') ?></p>
              <p class="text-xs mt-1.5" style="color:#7F1D1D;">Per Art. V Sec. 15-D, you're not eligible for Stipend, PATHFit Exemption, or BANTOG Recognition benefits until this is cleared. Submit a new report once you've obtained a passing grade to lift this automatically.</p>
            </div>
          <?php else: ?>
            <div class="rounded-xl p-3 mb-4 flex items-center gap-1.5" style="background:#F0FDF4; border:1px solid #DCFCE7;">
              <i data-lucide="check-circle" class="w-4 h-4" style="color:#15803D;"></i> <span class="text-xs font-medium" style="color:#15803D;">Good standing — no active probation.</span>
            </div>
          <?php endif; ?>

          <?php if ($activeMentorships): ?>
            <div class="rounded-xl p-3 mb-4" style="background:#FAFAFA; border:1px solid #F0F0F0;">
              <div class="text-xs font-semibold mb-1.5" style="color:#1a1a2e;">Assigned Mentor(s)</div>
              <?php foreach ($activeMentorships as $m): ?>
                <div class="text-xs text-gray-600"><?= e($m['mentor_name']) ?> <span class="text-gray-400">(<?= e($m['reason']) ?>)</span></div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <details>
            <summary class="text-xs font-medium cursor-pointer" style="color:#B11226;">Submit grade report (Midterm/Final)</summary>
            <form method="POST" action="<?= e(APP_URL) ?>/academic/report" class="flex flex-col gap-3 mt-3">
              <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="text-xs text-gray-500 block mb-1">Term</label>
                  <select name="term" required class="dash-input modern-input w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none bg-white">
                    <option value="Midterm">Midterm</option>
                    <option value="Final">Final</option>
                  </select>
                </div>
                <div>
                  <label class="text-xs text-gray-500 block mb-1">Semester</label>
                  <select name="semester" required class="dash-input modern-input w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none bg-white">
                    <option value="1st">1st</option>
                    <option value="2nd">2nd</option>
                    <option value="Summer">Summer</option>
                  </select>
                </div>
              </div>
              <div><label class="text-xs text-gray-500 block mb-1">Academic Year</label><input type="text" name="academic_year" required placeholder="e.g. 2026-2027" class="dash-input modern-input w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none"></div>
              <div><label class="text-xs text-gray-500 block mb-1">GWA <span class="text-gray-400">(optional)</span></label><input type="number" step="0.01" min="1" max="5" name="gwa" class="dash-input modern-input w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none"></div>
              <label class="flex items-start gap-2.5 cursor-pointer">
                <input type="checkbox" name="has_failing_grade" value="1" class="mt-0.5">
                <span class="text-xs text-gray-600">I have a failing grade in at least one course this term.</span>
              </label>
              <button type="submit" class="modern-btn self-start px-4 py-2 rounded-xl text-xs font-semibold text-white" style="background:#B11226;">Submit Report</button>
            </form>
          </details>

          <?php if ($academicReports): ?>
            <div class="mt-4 pt-3 border-t border-gray-100">
              <div class="text-xs font-semibold mb-2" style="color:#1a1a2e;">Report History</div>
              <div class="flex flex-col gap-1.5">
                <?php foreach ($academicReports as $r): ?>
                  <div class="flex items-center justify-between text-xs">
                    <span class="text-gray-500"><?= e($r['term']) ?> &middot; AY <?= e($r['academic_year']) ?> (<?= e($r['semester']) ?> Sem)</span>
                    <span class="font-medium" style="color: <?= $r['has_failing_grade'] ? '#B91C1C' : '#15803D' ?>;"><?= $r['has_failing_grade'] ? 'Failing grade reported' : 'Passing' ?></span>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>
        </div>

        <div class="modern-card bg-white rounded-2xl p-5 sm:p-6 border border-gray-100" style="box-shadow: 0 1px 4px rgba(0,0,0,0.06);">
          <h3 class="font-semibold mb-4 text-sm" style="color:#1a1a2e;">Personal Information</h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
            <?php foreach ([
                ['Full Name', $fullName],
                ['Student ID', $user['id_number'] ?? ''],
                ['Course & Year', $user['course'] ?? ''],
                ['Email', $user['email']],
                ['Contact Number', $user['contact_number'] ?? 'Not provided'],
                ['GWA', $profile['gwa'] ?? 'N/A'],
            ] as [$label, $value]): ?>
              <div>
                <label class="text-xs text-gray-400 block mb-1"><?= e($label) ?></label>
                <div class="px-3 py-2.5 bg-gray-50 rounded-xl text-sm font-medium truncate" style="color:#1a1a2e;"><?= e((string) $value) ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="modern-card bg-white rounded-2xl p-5 sm:p-6 border border-gray-100" style="box-shadow: 0 1px 4px rgba(0,0,0,0.06);">
          <h3 class="font-semibold mb-4 text-sm" style="color:#1a1a2e;">Performer Profile</h3>
          <div class="flex flex-wrap gap-2">
            <?php if (!$talents): ?>
              <span class="text-xs text-gray-400">No talents recorded yet.</span>
            <?php endif; ?>
            <?php foreach ($talents as $tag): ?>
              <span class="px-3 py-1.5 rounded-xl text-xs font-medium" style="background:#FEE2E2; color:#B11226;"><?= e($tag) ?></span>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="lg:hidden flex flex-col gap-2">
          <a href="<?= e(APP_URL) ?>" class="flex items-center justify-center gap-2 py-3 rounded-2xl border border-gray-200 text-sm font-medium text-gray-500">
            <i data-lucide="home" class="w-4 h-4"></i> Back to Home
          </a>
          <a href="<?= e(APP_URL) ?>/logout" class="flex items-center justify-center gap-2 py-3 rounded-2xl border border-gray-200 text-sm font-medium text-gray-500">
            <i data-lucide="log-out" class="w-4 h-4"></i> Sign Out
          </a>
        </div>
      </div>
    </main>

    <!-- Mobile Bottom Navigation -->
    <nav class="shell-mobilenav float-glass float-shadow lg:hidden fixed z-30">
      <div class="flex items-stretch">
        <button type="button" data-tab-link="dashboard" class="mobile-nav-btn flex-1 flex flex-col items-center justify-center py-2.5 gap-1 transition-all" data-tab="dashboard">
          <div class="mobile-nav-icon-wrap w-10 h-8 flex items-center justify-center rounded-xl transition-all" style="background:#FEE2E2;"><i data-lucide="home" class="w-4 h-4 transition-all" style="color:#B11226;"></i></div>
          <span class="mobile-nav-label text-[10px] font-medium leading-none" style="color:#B11226;">Dashboard</span>
        </button>
        <button type="button" data-tab-link="applications" class="mobile-nav-btn flex-1 flex flex-col items-center justify-center py-2.5 gap-1 transition-all" data-tab="applications">
          <div class="mobile-nav-icon-wrap w-10 h-8 flex items-center justify-center rounded-xl transition-all"><i data-lucide="file-text" class="w-4 h-4 transition-all" style="color:#4B5563;"></i></div>
          <span class="mobile-nav-label text-[10px] font-medium leading-none" style="color:#4B5563;">Applications</span>
        </button>
        <button type="button" data-tab-link="profile" class="mobile-nav-btn flex-1 flex flex-col items-center justify-center py-2.5 gap-1 transition-all" data-tab="profile">
          <div class="mobile-nav-icon-wrap w-10 h-8 flex items-center justify-center rounded-xl transition-all"><i data-lucide="user" class="w-4 h-4 transition-all" style="color:#4B5563;"></i></div>
          <span class="mobile-nav-label text-[10px] font-medium leading-none" style="color:#4B5563;">Profile</span>
        </button>
        <button type="button" class="apply-trigger flex-1 flex flex-col items-center justify-center py-2.5 gap-1">
          <div class="w-10 h-8 flex items-center justify-center rounded-xl" style="background:#D4AF37;"><i data-lucide="plus" class="w-4 h-4 text-white"></i></div>
          <span class="text-[10px] font-medium leading-none" style="color:#4B5563;">Apply</span>
        </button>
      </div>
    </nav>
  </div>
</div>

<!-- Application Type Modal (step 1) -->
<div id="applyTypeModal" class="hidden fixed inset-0 z-50 items-end sm:items-center justify-center sm:p-4 bg-black/50">
  <div class="bg-white rounded-t-3xl sm:rounded-2xl w-full sm:max-w-2xl overflow-hidden relative" style="max-height:90vh;">
    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
      <div class="sm:hidden absolute top-2.5 left-1/2 -translate-x-1/2 w-10 h-1 bg-gray-200 rounded-full"></div>
      <div>
        <h3 class="font-bold text-sm sm:text-base" style="color:#1a1a2e;">Submit New Application</h3>
        <p class="text-xs text-gray-500">Choose the type of application</p>
      </div>
      <button type="button" class="apply-modal-close" aria-label="Close application form"><i data-lucide="x" class="w-5 h-5 text-gray-400"></i></button>
    </div>
    <div class="p-4 sm:p-6 grid grid-cols-1 sm:grid-cols-2 gap-2.5 overflow-y-auto" style="max-height:70vh;">
      <?php foreach ($applicationTypes as $type): ?>
        <button type="button" class="apptype-btn flex items-start gap-3 p-4 rounded-xl border-2 border-gray-100 hover:border-red-200 hover:bg-red-50 transition-all text-left"
          data-type-id="<?= (int) $type['type_id'] ?>" data-type-code="<?= e($type['code']) ?>" data-type-name="<?= e($type['name']) ?>" data-type-icon="<?= e($typeIcons[$type['code']] ?? 'file-text') ?>">
          <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0" style="background:#FEE2E2;"><i data-lucide="<?= e($typeIcons[$type['code']] ?? 'file-text') ?>" class="w-5 h-5" style="color:#B11226;"></i></div>
          <div>
            <div class="text-sm font-semibold" style="color:#1a1a2e;"><?= e($type['name']) ?></div>
            <div class="text-xs text-gray-500"><?= e($type['description']) ?></div>
          </div>
        </button>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Application Form Modal (step 2) -->
<div id="applyFormModal" class="hidden fixed inset-0 z-50 items-end sm:items-center justify-center sm:p-4 bg-black/50">
  <div class="bg-white rounded-t-3xl sm:rounded-2xl w-full sm:max-w-xl overflow-hidden relative" style="max-height:95vh;">
    <form method="POST" action="<?= e(APP_URL) ?>/applications/apply" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="type_id" id="selectedTypeId">
      <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100" style="background: linear-gradient(135deg, #B11226, #7a0d1a);">
        <div class="sm:hidden absolute top-2.5 left-1/2 -translate-x-1/2 w-10 h-1 bg-white/30 rounded-full"></div>
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center"><i data-lucide="file-text" id="selectedTypeIcon" class="w-4 h-4 text-white"></i></div>
          <div>
            <h3 class="font-bold text-white text-sm" id="selectedTypeName">Application</h3>
            <p class="text-red-200 text-xs">Fill in all required fields</p>
          </div>
        </div>
        <button type="button" class="apply-modal-close" aria-label="Close application form"><i data-lucide="x" class="w-5 h-5 text-white/70"></i></button>
      </div>

      <div class="px-5 py-3 bg-gray-50 border-b border-gray-100"><?= progress_tracker_html(1) ?></div>

      <div class="px-5 py-4 flex flex-col gap-3.5 overflow-y-auto" style="max-height:60vh;">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <?php foreach ([
              ['First Name', $user['first_name']],
              ['Last Name', $user['last_name']],
              ['Student ID', $user['id_number'] ?? ''],
              ['Course & Year', $user['course'] ?? ''],
              ['Contact Number', $user['contact_number'] ?? ''],
              ['GWA', $profile['gwa'] ?? ''],
          ] as [$label, $value]): ?>
            <div>
              <label class="text-xs font-medium text-gray-600 block mb-1"><?= e($label) ?></label>
              <input readonly value="<?= e((string) $value) ?>" class="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm bg-gray-50 focus:outline-none">
            </div>
          <?php endforeach; ?>
        </div>

        <div>
          <label class="text-xs font-medium text-gray-600 block mb-1" id="detailsLabel">Reason / Description</label>
          <textarea name="details" id="detailsInput" rows="3" placeholder="Briefly describe your application..." class="dash-input modern-input w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none resize-none"></textarea>
        </div>

        <div id="profileFieldsContainer" class="hidden flex-col gap-3 p-3 rounded-xl" style="background:#FAFAFA; border:1px solid #F0F0F0;">
          <div class="text-xs font-semibold" style="color:#1a1a2e;">Performer Profile</div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="text-xs font-medium text-gray-600 block mb-1">Talent / Discipline</label>
              <select name="talent_category_id" class="dash-input modern-input w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none bg-white">
                <option value="">Select a discipline&hellip;</option>
                <?php foreach ($talentCategories as $cat): ?>
                  <option value="<?= (int) $cat['category_id'] ?>"><?= e($cat['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label class="text-xs font-medium text-gray-600 block mb-1">Proficiency Level</label>
              <select name="proficiency_level" class="dash-input modern-input w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none bg-white">
                <?php foreach (['Beginner', 'Intermediate', 'Advanced', 'Expert'] as $level): ?>
                  <option value="<?= e($level) ?>" <?= $level === 'Intermediate' ? 'selected' : '' ?>><?= e($level) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div>
            <label for="portfolioNoteInput" class="text-xs font-medium text-gray-600 block mb-1">Portfolio / Notes <span class="text-gray-400">(optional)</span></label>
            <input type="text" id="portfolioNoteInput" name="portfolio_note" placeholder="Link to a reel, or a short note about your experience" class="dash-input modern-input w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none">
          </div>
        </div>

        <div id="stipendFieldsContainer" class="hidden flex-col gap-3 p-3 rounded-xl" style="background:#FAFAFA; border:1px solid #F0F0F0;">
          <div class="text-xs font-semibold" style="color:#1a1a2e;">Stipend Details</div>
          <div>
            <label for="hoursClaimedInput" class="text-xs font-medium text-gray-600 block mb-1">Hours of Regular/Special Training &amp; Performances Claimed</label>
            <input type="number" id="hoursClaimedInput" name="hours_claimed" min="0" step="0.5" placeholder="e.g. 24" class="dash-input modern-input w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none">
            <p class="text-xs text-gray-400 mt-1">Stipend is computed at Php 60.00/hour (Art. IX, Sec. 29 of the Culture and Arts Development Manual).</p>
          </div>
        </div>

        <div id="bantogFieldsContainer" class="hidden flex-col gap-3 p-3 rounded-xl" style="background:#FAFAFA; border:1px solid #F0F0F0;">
          <div class="text-xs font-semibold" style="color:#1a1a2e;">BANTOG Category</div>
          <div>
            <label class="text-xs font-medium text-gray-600 block mb-1">Larangan (Discipline Applied For)</label>
            <select name="bantog_category" class="dash-input modern-input w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none bg-white">
              <option value="">Select a discipline&hellip;</option>
              <?php foreach ([
                  'BANTOG sa Larangan ng Katutubong Sayaw',
                  'BANTOG sa Larangan ng Modernong Sayaw',
                  'BANTOG sa Larangan ng Teatro',
                  'BANTOG sa Larangan ng Koro',
                  'BANTOG sa Larangan ng Rondalla',
                  'BANTOG sa Larangan ng Banda',
                  'BANTOG sa Larangan ng Sining Biswal',
                  'BANTOG sa Larangan ng Arkitektura',
                  'BANTOG sa Larangan ng Panitikan',
              ] as $bantogCat): ?>
                <option value="<?= e($bantogCat) ?>"><?= e($bantogCat) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div id="pathfitFieldsContainer" class="hidden flex-col gap-3 p-3 rounded-xl" style="background:#FAFAFA; border:1px solid #F0F0F0;">
          <div class="text-xs font-semibold" style="color:#1a1a2e;">PATHFit Instructor</div>
          <div>
            <label class="text-xs font-medium text-gray-600 block mb-1">Who is your current PATHFit instructor?</label>
            <select name="pathfit_faculty_id" class="dash-input modern-input w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none bg-white">
              <option value="">Select your instructor&hellip;</option>
              <?php foreach ($pathfitFaculty as $pf): ?>
                <option value="<?= (int) $pf['user_id'] ?>"><?= e($pf['first_name'] . ' ' . $pf['last_name']) ?></option>
              <?php endforeach; ?>
            </select>
            <p class="text-xs text-gray-400 mt-1">Your instructor will review this request directly (WI-OCA-04).</p>
          </div>
        </div>

        <div id="requiredDocsContainer" class="flex flex-col gap-3"></div>

        <div>
          <label class="text-xs font-medium text-gray-600 block mb-2">Other Supporting Document <span class="text-gray-400">(optional)</span></label>
          <label class="border-2 border-dashed rounded-xl p-5 flex flex-col items-center gap-2 cursor-pointer hover:bg-red-50 transition-colors" style="border-color:#E5E7EB;">
            <input type="file" name="documents[]" id="documentsInput" multiple accept=".pdf,.jpg,.jpeg,.png" class="hidden">
            <i data-lucide="upload" class="w-7 h-7 text-gray-300"></i>
            <span class="text-xs text-gray-500" id="documentsLabel">Tap to upload PDF or images</span>
            <span class="text-xs text-gray-400">Max file size: 10MB</span>
          </label>
        </div>
      </div>

      <div class="flex items-center gap-3 px-5 py-4 border-t border-gray-100" style="padding-bottom: calc(1rem + env(safe-area-inset-bottom));">
        <button type="button" id="applyBackBtn" class="flex-1 py-2.5 rounded-xl text-sm font-medium border border-gray-200 text-gray-600">Back</button>
        <button type="submit" class="modern-btn flex-1 py-2.5 rounded-xl text-sm font-semibold text-white flex items-center justify-center gap-2" style="background: linear-gradient(135deg, #B11226, #7a0d1a);"><i data-lucide="send" class="w-4 h-4"></i> Submit</button>
      </div>
    </form>
  </div>
</div>

<!-- View Application Modal -->
<div id="viewAppModal" class="hidden fixed inset-0 z-50 items-end sm:items-center justify-center sm:p-4 bg-black/50">
  <div class="bg-white rounded-t-3xl sm:rounded-2xl w-full sm:max-w-lg overflow-hidden relative">
    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
      <div class="sm:hidden absolute top-2.5 left-1/2 -translate-x-1/2 w-10 h-1 bg-gray-200 rounded-full"></div>
      <div>
        <h3 class="font-bold text-sm sm:text-base" style="color:#1a1a2e;">Application Details</h3>
        <p class="text-xs text-gray-500" id="viewAppId"></p>
      </div>
      <button type="button" class="view-modal-close" aria-label="Close application details"><i data-lucide="x" class="w-5 h-5 text-gray-400"></i></button>
    </div>
    <div class="p-5 flex flex-col gap-5">
      <div class="flex items-center justify-between">
        <div>
          <div class="text-xs text-gray-500 mb-1">Application Type</div>
          <div class="font-semibold text-sm" style="color:#1a1a2e;" id="viewAppType"></div>
        </div>
        <div id="viewAppStatus"></div>
      </div>
      <div>
        <div class="text-xs font-medium text-gray-500 mb-3">Application Progress</div>
        <div id="viewAppProgress"></div>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div class="bg-gray-50 rounded-xl p-3"><div class="text-xs text-gray-400 mb-0.5">Date Submitted</div><div class="text-xs font-semibold" style="color:#1a1a2e;" id="viewAppDate"></div></div>
        <div class="bg-gray-50 rounded-xl p-3"><div class="text-xs text-gray-400 mb-0.5">Current Stage</div><div class="text-xs font-semibold" style="color:#1a1a2e;" id="viewAppStage"></div></div>
        <div class="bg-gray-50 rounded-xl p-3"><div class="text-xs text-gray-400 mb-0.5">Applicant</div><div class="text-xs font-semibold" style="color:#1a1a2e;"><?= e($fullName) ?></div></div>
        <div class="bg-gray-50 rounded-xl p-3"><div class="text-xs text-gray-400 mb-0.5">Student ID</div><div class="text-xs font-semibold" style="color:#1a1a2e;"><?= e($user['id_number'] ?? '') ?></div></div>
      </div>
      <div id="viewAppRemarks" class="hidden bg-red-50 rounded-xl p-3">
        <div class="text-xs text-gray-500 mb-0.5">Remarks</div>
        <div class="text-xs font-medium" style="color:#B91C1C;" id="viewAppRemarksText"></div>
      </div>
      <div id="viewAppDocs" class="hidden">
        <div class="text-xs text-gray-500 mb-1.5">Documents</div>
        <div class="flex flex-col gap-1" id="viewAppDocsList"></div>
      </div>
    </div>
    <div class="px-5 pb-5" style="padding-bottom: calc(1.25rem + env(safe-area-inset-bottom));">
      <button type="button" class="view-modal-close w-full py-3 rounded-xl text-sm font-semibold text-white" style="background:#B11226;">Close</button>
    </div>
  </div>
</div>

@include('partials.ask_spartan_widget')

<script>
var APPLICATIONS = <?= json_encode(array_map(function ($a) {
    return [
        'id' => (int) $a['application_id'],
        'code' => $a['application_code'],
        'type' => $a['type_name'],
        'typeCode' => $a['type_code'],
        'status' => $a['status'],
        'stage' => (int) $a['current_stage'],
        'date' => format_date($a['submitted_at']),
        'remarks' => $a['remarks'],
        'docs' => array_map(function ($d) { return ['name' => $d['file_name'], 'type' => $d['document_type'], 'url' => APP_URL . '/' . $d['file_path']]; }, $documentsByApp[$a['application_id']] ?? []),
    ];
}, $applications), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
var STATUS_COLORS = { Approved: '#22C55E', Pending: '#F59E0B', 'Under Review': '#3B82F6', Evaluation: '#A855F7', Rejected: '#EF4444' };
var STATUS_BG = { Approved: '#DCFCE7', Pending: '#FEF9C3', 'Under Review': '#DBEAFE', Evaluation: '#F3E8FF', Rejected: '#FEE2E2' };
var STATUS_TEXT = { Approved: '#15803D', Pending: '#92400E', 'Under Review': '#1D4ED8', Evaluation: '#7C3AED', Rejected: '#B91C1C' };
var PROGRESS_STAGES = <?= json_encode(ARTEMIS_PROGRESS_STAGES) ?>;
var PROGRESS_STAGES_BY_TYPE = <?= json_encode(application_progress_stages_by_type()) ?>;
function stagesFor(typeCode) { return PROGRESS_STAGES_BY_TYPE[typeCode] || PROGRESS_STAGES; }
var DOC_CATALOG = <?= json_encode(ARTEMIS_DOCUMENT_CATALOG) ?>;
var DOC_REQUIREMENTS = <?= json_encode(application_document_requirements()) ?>;

lucide.createIcons();

// Event Check-In QR (Art. XII attendance) — encodes the account's unguessable
// check-in token; OCA staff scan this at the venue to mark real attendance.
(function () {
  var box = document.getElementById('checkinQrBox');
  if (!box) return;
  var qr = qrcode(0, 'M');
  qr.addData(<?= json_encode($user['qr_token']) ?>);
  qr.make();
  box.innerHTML = qr.createSvgTag(6, 0);
})();

// Tab switching
var titles = { dashboard: 'Student Dashboard', applications: 'My Applications', profile: 'My Profile' };
function setTab(tab) {
  document.querySelectorAll('.tab-panel').forEach(function (p) {
    var active = p.dataset.panel === tab;
    p.classList.toggle('hidden', !active);
    p.classList.toggle('flex', active);
  });
  document.querySelectorAll('.dash-nav-btn').forEach(function (b) {
    var active = b.dataset.tab === tab;
    b.style.background = active ? 'rgba(255,255,255,0.2)' : '';
    b.style.color = active ? '#fff' : 'rgba(255,255,255,0.7)';
    b.style.fontWeight = active ? '600' : '400';
  });
  document.querySelectorAll('.mobile-nav-btn').forEach(function (b) {
    var active = b.dataset.tab === tab;
    var iconWrap = b.querySelector('.mobile-nav-icon-wrap');
    if (iconWrap) {
      iconWrap.style.background = active ? '#FEE2E2' : 'transparent';
      var icon = iconWrap.querySelector('svg, i');
      if (icon) icon.style.color = active ? '#B11226' : '#4B5563';
    }
    var label = b.querySelector('.mobile-nav-label');
    if (label) label.style.color = active ? '#B11226' : '#4B5563';
  });
  document.getElementById('pageTitleDesktop').textContent = titles[tab];
  document.getElementById('pageTitleMobile').textContent = titles[tab];
}
document.querySelectorAll('[data-tab-link]').forEach(function (b) { b.addEventListener('click', function () { setTab(b.dataset.tabLink); }); });
document.querySelectorAll('.dash-nav-btn').forEach(function (b) { b.addEventListener('click', function () { setTab(b.dataset.tab); }); });
document.querySelectorAll('.mobile-nav-btn').forEach(function (b) { b.addEventListener('click', function () { setTab(b.dataset.tab); }); });

// Deep-link support: ?tab=applications from the personalized home page's alerts
var requestedTab = new URLSearchParams(window.location.search).get('tab');
if (requestedTab && titles[requestedTab]) { setTab(requestedTab); }

// Notifications dropdown
var notifBtn = document.getElementById('notifBtn');
var notifDropdown = document.getElementById('notifDropdown');
if (notifBtn) {
  notifBtn.addEventListener('click', function () {
    notifDropdown.classList.toggle('hidden');
    var badge = document.getElementById('notifBadge');
    if (badge) badge.remove();
  });
  var notifCloseBtn = document.getElementById('notifCloseBtn');
  if (notifCloseBtn) notifCloseBtn.addEventListener('click', function () { notifDropdown.classList.add('hidden'); });

  function highlightRows(selector) {
    var rows = document.querySelectorAll(selector);
    rows.forEach(function (row) {
      var cls = row.tagName === 'TR' ? 'notif-highlight-row' : 'notif-highlight-card';
      row.classList.remove('notif-highlight-row', 'notif-highlight-card');
      void row.offsetWidth; // restart the animation even if this row was just highlighted
      row.classList.add(cls);
    });
    setTimeout(function () {
      var visible = Array.prototype.find.call(rows, function (r) { return r.offsetParent !== null; });
      if (visible) visible.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }, 120);
    setTimeout(function () {
      rows.forEach(function (row) { row.classList.remove('notif-highlight-row', 'notif-highlight-card'); });
    }, 2500);
  }

  document.querySelectorAll('.notif-item').forEach(function (el) {
    el.addEventListener('click', function () {
      notifDropdown.classList.add('hidden');
      var badge = document.getElementById('notifBadge');
      if (badge) badge.remove();
      if (el.dataset.appCode) {
        setTab('applications');
        setTimeout(function () { highlightRows('.app-row[data-code="' + el.dataset.appCode + '"]'); }, 50);
      } else if (el.dataset.annId) {
        setTab('dashboard');
        setTimeout(function () { highlightRows('.ann-row[data-ann-id="' + el.dataset.annId + '"]'); }, 50);
      }
    });
  });
}

// Help & Support quick action -> open Ask Spartan
var helpBtn = document.getElementById('helpQuickBtn');
if (helpBtn) helpBtn.addEventListener('click', function () { document.querySelector('.spartan-fab').click(); });

// Apply modal flow
var applyTypeModal = document.getElementById('applyTypeModal');
var applyFormModal = document.getElementById('applyFormModal');
function openApplyFlow() {
  applyTypeModal.classList.remove('hidden'); applyTypeModal.classList.add('flex');
  applyFormModal.classList.add('hidden'); applyFormModal.classList.remove('flex');
}
function closeApplyFlow() {
  applyTypeModal.classList.add('hidden'); applyTypeModal.classList.remove('flex');
  applyFormModal.classList.add('hidden'); applyFormModal.classList.remove('flex');
}
document.querySelectorAll('.apply-trigger, #sidebarApplyBtn, #mobileApplyBtn').forEach(function (b) { b.addEventListener('click', openApplyFlow); });
document.querySelectorAll('.apply-modal-close').forEach(function (b) { b.addEventListener('click', closeApplyFlow); });

function renderRequiredDocs(typeCode) {
  var container = document.getElementById('requiredDocsContainer');
  container.innerHTML = '';
  var reqs = DOC_REQUIREMENTS[typeCode] || {};
  Object.keys(reqs).forEach(function (key) {
    var required = reqs[key];
    var label = DOC_CATALOG[key] || key;
    var wrap = document.createElement('div');
    wrap.innerHTML =
      '<label class="text-xs font-medium text-gray-600 block mb-2">' + label + (required ? ' <span style="color:#B11226;">*</span>' : ' <span class="text-gray-400">(optional)</span>') + '</label>' +
      '<label class="border-2 border-dashed rounded-xl p-4 flex items-center gap-3 cursor-pointer hover:bg-red-50 transition-colors" style="border-color:#E5E7EB;">' +
        '<i data-lucide="upload" class="w-5 h-5 text-gray-300 flex-shrink-0"></i>' +
        '<span class="text-xs text-gray-500 doc-slot-label">Tap to upload PDF or image</span>' +
        '<input type="file" name="doc_' + key + '" accept=".pdf,.jpg,.jpeg,.png" class="hidden doc-slot-input"' + (required ? ' required' : '') + '>' +
      '</label>';
    var input = wrap.querySelector('.doc-slot-input');
    var slotLabel = wrap.querySelector('.doc-slot-label');
    input.addEventListener('change', function () {
      slotLabel.textContent = this.files.length ? this.files[0].name : 'Tap to upload PDF or image';
    });
    container.appendChild(wrap);
  });
  lucide.createIcons();
}

document.querySelectorAll('.apptype-btn').forEach(function (btn) {
  btn.addEventListener('click', function () {
    var typeCode = btn.dataset.typeCode;
    document.getElementById('selectedTypeId').value = btn.dataset.typeId;
    document.getElementById('selectedTypeName').textContent = btn.dataset.typeName;
    document.getElementById('selectedTypeIcon').setAttribute('data-lucide', btn.dataset.typeIcon);

    var profileFields = document.getElementById('profileFieldsContainer');
    if (typeCode === 'audition_recruitment') { profileFields.classList.remove('hidden'); profileFields.classList.add('flex'); }
    else { profileFields.classList.add('hidden'); profileFields.classList.remove('flex'); }

    var stipendFields = document.getElementById('stipendFieldsContainer');
    if (typeCode === 'stipend') { stipendFields.classList.remove('hidden'); stipendFields.classList.add('flex'); }
    else { stipendFields.classList.add('hidden'); stipendFields.classList.remove('flex'); }

    var bantogFields = document.getElementById('bantogFieldsContainer');
    if (typeCode === 'bantog_recognition') { bantogFields.classList.remove('hidden'); bantogFields.classList.add('flex'); }
    else { bantogFields.classList.add('hidden'); bantogFields.classList.remove('flex'); }

    var pathfitFields = document.getElementById('pathfitFieldsContainer');
    if (typeCode === 'pathfit_exemption') { pathfitFields.classList.remove('hidden'); pathfitFields.classList.add('flex'); }
    else { pathfitFields.classList.add('hidden'); pathfitFields.classList.remove('flex'); }

    renderRequiredDocs(typeCode);

    applyTypeModal.classList.add('hidden'); applyTypeModal.classList.remove('flex');
    applyFormModal.classList.remove('hidden'); applyFormModal.classList.add('flex');
    lucide.createIcons();
  });
});
document.getElementById('applyBackBtn').addEventListener('click', function () {
  applyFormModal.classList.add('hidden'); applyFormModal.classList.remove('flex');
  applyTypeModal.classList.remove('hidden'); applyTypeModal.classList.add('flex');
});
document.getElementById('documentsInput').addEventListener('change', function () {
  document.getElementById('documentsLabel').textContent = this.files.length ? this.files.length + ' file(s) selected' : 'Tap to upload PDF or images';
});

// Profile photo — picking a file uploads it immediately, no separate "Save" step.
var photoUploadInput = document.getElementById('photoUploadInput');
if (photoUploadInput) {
  photoUploadInput.addEventListener('change', function () {
    if (this.files && this.files.length) {
      document.getElementById('photoUploadForm').submit();
    }
  });
}
document.querySelectorAll('.dash-input').forEach(function (el) {
  el.addEventListener('focus', function () { el.style.borderColor = '#B11226'; });
  el.addEventListener('blur', function () { el.style.borderColor = '#e5e7eb'; });
});

// View application modal
var viewAppModal = document.getElementById('viewAppModal');
function progressTrackerHtml(stage, typeCode) {
  var stages = stagesFor(typeCode);
  var html = '<div class="flex items-center gap-0 w-full">';
  stages.forEach(function (label, i) {
    var n = i + 1;
    var bg = n < stage ? '#22C55E' : (n === stage ? '#B11226' : '#E5E7EB');
    var color = n <= stage ? '#fff' : '#4B5563';
    var labelStyle = n === stage ? 'color:#B11226;font-weight:600;' : '';
    html += '<div class="flex items-center flex-1"><div class="flex flex-col items-center flex-1">'
      + '<div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold" style="background:' + bg + ';color:' + color + ';">' + (n < stage ? '&#10003;' : n) + '</div>'
      + '<span class="text-[9px] text-gray-500 mt-1 text-center leading-tight hidden sm:block" style="' + labelStyle + '">' + label + '</span></div>';
    if (i < stages.length - 1) {
      html += '<div class="h-0.5 flex-1 -mt-4" style="background:' + (n < stage ? '#22C55E' : '#E5E7EB') + ';"></div>';
    }
    html += '</div>';
  });
  return html + '</div>';
}
document.querySelectorAll('.view-app-btn').forEach(function (btn) {
  btn.addEventListener('click', function () {
    var app = APPLICATIONS.find(function (a) { return a.id === parseInt(btn.dataset.app, 10); });
    if (!app) return;
    document.getElementById('viewAppId').textContent = app.code;
    document.getElementById('viewAppType').textContent = app.type;
    document.getElementById('viewAppStatus').innerHTML = '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium" style="background:' + STATUS_BG[app.status] + ';color:' + STATUS_TEXT[app.status] + ';"><span class="w-1.5 h-1.5 rounded-full" style="background:' + STATUS_COLORS[app.status] + ';"></span>' + app.status + '</span>';
    document.getElementById('viewAppProgress').innerHTML = progressTrackerHtml(app.stage, app.typeCode);
    document.getElementById('viewAppDate').textContent = app.date;
    document.getElementById('viewAppStage').textContent = stagesFor(app.typeCode)[app.stage - 1];
    var remarksBox = document.getElementById('viewAppRemarks');
    if (app.remarks) { remarksBox.classList.remove('hidden'); document.getElementById('viewAppRemarksText').textContent = app.remarks; }
    else remarksBox.classList.add('hidden');
    var docsBox = document.getElementById('viewAppDocs');
    if (app.docs.length) {
      docsBox.classList.remove('hidden');
      document.getElementById('viewAppDocsList').innerHTML = app.docs.map(function (d) {
        return '<a href="' + d.url + '" target="_blank" class="text-xs flex items-center gap-1" style="color:#B11226;">' +
          (d.type ? '<span class="text-gray-400">' + d.type + ':</span> ' : '') + d.name + '</a>';
      }).join('');
    } else docsBox.classList.add('hidden');
    viewAppModal.classList.remove('hidden'); viewAppModal.classList.add('flex');
  });
});
document.querySelectorAll('.view-modal-close').forEach(function (b) { b.addEventListener('click', function () { viewAppModal.classList.add('hidden'); viewAppModal.classList.remove('flex'); }); });

// Charts
new Chart(document.getElementById('statusDonut'), {
  type: 'doughnut',
  data: {
    labels: ['Approved', 'Pending', 'Under Review', 'Evaluation', 'Rejected'],
    datasets: [{ data: [<?= (int) $statusCounts['Approved'] ?>, <?= (int) $statusCounts['Pending'] ?>, <?= (int) $statusCounts['Under Review'] ?>, <?= (int) $statusCounts['Evaluation'] ?>, <?= (int) $statusCounts['Rejected'] ?>], backgroundColor: ['#22C55E', '#F59E0B', '#3B82F6', '#A855F7', '#EF4444'], borderWidth: 0 }],
  },
  options: { responsive: true, maintainAspectRatio: false, cutout: '60%', plugins: { legend: { display: false } } },
});
new Chart(document.getElementById('typeBar'), {
  type: 'bar',
  data: {
    labels: <?= json_encode(array_keys($typeCounts)) ?>,
    datasets: [{ data: <?= json_encode(array_values($typeCounts)) ?>, backgroundColor: ['#B11226', '#D4AF37', '#3B82F6', '#22C55E', '#A855F7', '#F59E0B'], borderRadius: 4, maxBarThickness: 24 }],
  },
  options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { ticks: { font: { size: 9 }, color: '#4B5563' }, grid: { display: false } }, y: { display: false } } },
});
</script>
<script src="<?= APP_URL ?>/assets/js/modern.js"></script>

</body>
</html>
