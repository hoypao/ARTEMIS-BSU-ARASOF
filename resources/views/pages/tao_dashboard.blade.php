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
  .tao-input:focus { border-color:#B11226; }

  /* Sidebar nav — matches the admin shell's hover + active accent. */
  .tao-nav-btn { position: relative; transition: background 0.25s var(--ease-out-soft), color 0.25s var(--ease-out-soft), padding-left 0.25s var(--ease-out-soft); }
  .tao-nav-btn::before { content: ''; position: absolute; left: -4px; top: 50%; transform: translateY(-50%); width: 3px; height: 0; border-radius: 999px; background: #D4AF37; transition: height 0.25s var(--ease-out-soft); }
  .tao-nav-btn[data-active="true"]::before { height: 60%; }
  .tao-nav-btn:not([data-active="true"]):hover { background: rgba(255,255,255,0.1) !important; padding-left: 14px; }

  /* Queue / Decided sub-tabs */
  .tao-subtab { transition: background 0.2s var(--ease-out-soft), color 0.2s var(--ease-out-soft); }
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
          <div class="text-red-200 text-xs truncate">TAO Central Reviewer</div>
        </div>
      </div>
      <div class="mt-2 inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium" style="background: rgba(212,175,55,0.2); color: #D4AF37;">
        <span class="w-1.5 h-1.5 rounded-full" style="background: #D4AF37;"></span>
        Testing &amp; Admission Office
      </div>
    </div>

    <nav class="flex-1 px-4 py-4 flex flex-col gap-1">
      <button type="button" data-section="dashboard" data-active="true" class="tao-nav-btn flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm text-left" style="background: rgba(255,255,255,0.2); color:#fff; font-weight:600;">
        <i data-lucide="layout-dashboard" class="w-4 h-4"></i> Dashboard
      </button>
      <button type="button" data-section="appeals" class="tao-nav-btn flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm text-left" style="color: rgba(255,255,255,0.7);">
        <i data-lucide="user-plus" class="w-4 h-4"></i> Admission Appeals
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
            <h1 id="pageTitleDesktop" class="font-bold text-base" style="color:#1a1a2e;">Dashboard</h1>
            <p class="text-xs text-gray-500"><?= e(date('l, F j, Y')) ?></p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <!-- Bell, badge and dropdown copied from the OCA admin shell
               (admin_dashboard.blade.php) so the two read as the same control.
               The badge is populated by JS here rather than PHP, because the
               count changes as appeals are forwarded or ruled on without a
               page load. -->
          <div class="relative">
            <button type="button" id="notifBtn" aria-label="Appeals waiting on TAO Central" class="relative w-10 h-10 flex items-center justify-center rounded-xl bg-gray-50 border border-gray-200">
              <i data-lucide="bell" class="w-4 h-4 text-gray-600"></i>
              <span id="notifBadge" class="hidden absolute -top-0.5 -right-0.5 w-4 h-4 rounded-full text-white flex items-center justify-center" style="background:#B11226; font-size:9px;"></span>
            </button>
            <div id="notifDropdown" class="hidden absolute top-10 right-0 w-72 max-w-[calc(100vw-2rem)] bg-white rounded-2xl border border-gray-100 z-50 overflow-hidden" style="box-shadow: 0 16px 40px rgba(0,0,0,0.14);">
              <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                <span class="font-semibold text-sm" style="color:#1a1a2e;">Notifications</span>
                <div class="flex items-center gap-2">
                  <button type="button" id="notifCloseBtn" aria-label="Close notifications"><i data-lucide="x" class="w-4 h-4 text-gray-600"></i></button>
                </div>
              </div>
              <div id="notifList"></div>
            </div>
          </div>
          <div class="lg:hidden">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-white font-bold text-xs" style="background:#B11226;"><?= e($initials) ?></div>
          </div>
        </div>
      </div>
    </header>

    <main class="flex-1 p-4 sm:p-6 flex flex-col gap-4 sm:gap-6">
      <div id="jsFlash"></div>

      <!-- ============ DASHBOARD (overview only, no actions) ============ -->
      <div class="tao-section flex flex-col gap-4 sm:gap-6" data-section="dashboard">
        <div class="modern-card rounded-2xl p-5 sm:p-6 text-white" style="background: linear-gradient(135deg, #B11226 0%, #7a0d1a 100%);">
          <h2 class="text-lg sm:text-xl font-bold mb-1">Welcome back, <?= e($reviewer['first_name']) ?></h2>
          <p class="text-red-100 text-sm">Admission appeals endorsed by the OCA for TAO Central evaluation and the University President's approval (Art. IV Sec. 11-C).</p>
        </div>

        <!-- Three equal columns at every width. The third card used to be
             col-span-2 under the 2-column mobile grid, which is what made it
             read as an oversized odd one out; padding and type step down on
             small screens instead so all three still fit on one row. -->
        <div class="grid grid-cols-3 gap-2 sm:gap-4">
          <div class="modern-card bg-white rounded-2xl p-3 sm:p-5 border border-gray-100" style="box-shadow: 0 1px 4px rgba(0,0,0,0.06);">
            <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center mb-2" style="background:#F3E8FF;"><i data-lucide="clipboard-check" class="w-4 h-4 sm:w-5 sm:h-5" style="color:#7C3AED;"></i></div>
            <div class="text-lg sm:text-2xl font-bold mb-0.5" style="color:#1a1a2e;"><?= (int) $evaluationCount ?></div>
            <div class="text-[11px] sm:text-xs text-gray-500 leading-tight">In Evaluation Stage</div>
          </div>
          <div class="modern-card bg-white rounded-2xl p-3 sm:p-5 border border-gray-100" style="box-shadow: 0 1px 4px rgba(0,0,0,0.06);">
            <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center mb-2" style="background:#DBEAFE;"><i data-lucide="stamp" class="w-4 h-4 sm:w-5 sm:h-5" style="color:#1D4ED8;"></i></div>
            <div class="text-lg sm:text-2xl font-bold mb-0.5" style="color:#1a1a2e;"><?= (int) $forApprovalCount ?></div>
            <div class="text-[11px] sm:text-xs text-gray-500 leading-tight">Awaiting President's Approval</div>
          </div>
          <div class="modern-card bg-white rounded-2xl p-3 sm:p-5 border border-gray-100" style="box-shadow: 0 1px 4px rgba(0,0,0,0.06);">
            <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center mb-2" style="background:#DCFCE7;"><i data-lucide="check-circle" class="w-4 h-4 sm:w-5 sm:h-5" style="color:#22C55E;"></i></div>
            <div class="text-lg sm:text-2xl font-bold mb-0.5" style="color:#1a1a2e;"><?= (int) $decidedThisYear ?></div>
            <div class="text-[11px] sm:text-xs text-gray-500 leading-tight">Decided This Year</div>
          </div>
        </div>

        <div class="modern-card bg-white rounded-2xl border border-gray-100 p-5" style="box-shadow: 0 1px 6px rgba(0,0,0,0.06);">
          <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-sm" style="color:#1a1a2e;">Recent Activity</h3>
            <button type="button" data-section-link="appeals" class="text-xs flex items-center gap-1 hover:opacity-80" style="color:#B11226;">Open the queue &rarr;</button>
          </div>
          <?php if (!$activity): ?>
            <div class="text-sm text-gray-400 py-4 text-center">Nothing has reached TAO Central yet.</div>
          <?php else: ?>
            <div class="flex flex-col">
              <?php foreach ($activity as $i => $act):
                  $decided = in_array($act['status'], ['Approved', 'Rejected'], true);
                  $meta = [
                      'Evaluation Stage' => ['clipboard-check', '#7C3AED', '#F3E8FF', 'arrived for evaluation'],
                      'For Approval (President via TAO)' => ['stamp', '#1D4ED8', '#DBEAFE', 'forwarded for the President\'s approval'],
                      'Approved' => ['check-circle', '#15803D', '#DCFCE7', 'approved'],
                      'Rejected' => ['x-circle', '#B91C1C', '#FEE2E2', 'rejected'],
                  ][$act['status']] ?? ['circle', '#6B7280', '#F3F4F6', 'updated'];
              ?>
              <div class="flex items-start gap-3 py-2.5<?= $i > 0 ? ' border-t border-gray-100' : '' ?>">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5" style="background:<?= $meta[2] ?>;">
                  <i data-lucide="<?= $meta[0] ?>" class="w-4 h-4" style="color:<?= $meta[1] ?>;"></i>
                </div>
                <div class="min-w-0 flex-1">
                  <div class="text-sm" style="color:#1a1a2e;">
                    <span class="font-semibold"><?= e($act['full_name']) ?></span>
                    <span class="text-gray-500"> &mdash; <?= e($meta[3]) ?></span>
                  </div>
                  <div class="text-[11px] text-gray-400 mt-0.5">
                    <span class="font-mono">APPEAL-<?= str_pad((string) $act['appeal_id'], 5, '0', STR_PAD_LEFT) ?></span>
                    <?= $act['discipline'] ? ' &middot; ' . e($act['discipline']) : '' ?>
                    &middot; <?= e(format_date($act['activity_at'], 'M j, Y')) ?>
                  </div>
                </div>
                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full flex-shrink-0" style="background:<?= $meta[2] ?>; color:<?= $meta[1] ?>;"><?= $decided ? e($act['status']) : 'In queue' ?></span>
              </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- ============ ADMISSION APPEALS (working queue) ============ -->
      <div class="tao-section hidden flex-col gap-4 sm:gap-6" data-section="appeals">
        <div id="queueFilterBar" class="modern-card bg-white rounded-2xl border border-gray-100 p-3 sm:p-4" style="box-shadow: 0 1px 6px rgba(0,0,0,0.06);">
          <div class="flex flex-col sm:flex-row gap-2.5">
            <div class="relative flex-1">
              <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2" style="color:#9CA3AF;"></i>
              <input id="appealSearch" type="search" placeholder="Search by applicant name or discipline..." class="tao-input modern-input w-full pl-9 pr-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none">
            </div>
            <select id="disciplineFilter" class="tao-input modern-input px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none sm:w-52">
              <option value="">All disciplines</option>
              <?php foreach (ARTEMIS_APPEAL_DISCIPLINES as $d): ?>
                <option value="<?= e($d) ?>"><?= e($d) ?></option>
              <?php endforeach; ?>
            </select>
            <select id="stageFilter" class="tao-input modern-input px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none sm:w-56">
              <option value="">All my stages</option>
              <?php foreach (ARTEMIS_APPEAL_TAO_STAGES as $s): ?>
                <option value="<?= e($s) ?>"><?= e($s) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <button type="button" class="tao-subtab px-4 py-2 rounded-xl text-sm font-semibold" data-subtab="queue" style="background:#B11226; color:#fff;">
            Queue <span id="queueCount" class="opacity-80"></span>
          </button>
          <button type="button" class="tao-subtab px-4 py-2 rounded-xl text-sm font-semibold" data-subtab="decided" style="background:#fff; color:#6B7280; border:1px solid #E5E7EB;">
            Decided <span id="decidedCount" class="opacity-80"></span>
          </button>
        </div>

        <div class="flex flex-col gap-4" id="appealsList"></div>
        <div class="flex flex-col gap-4 hidden" id="decidedList"></div>
      </div>
    </main>
  </div>

  <!-- MOBILE BOTTOM NAV -->
  <nav class="shell-mobilenav float-glass float-shadow lg:hidden fixed z-30">
    <div class="flex items-stretch">
      <button type="button" data-section-link="dashboard" class="tao-mobile-nav-btn flex-1 flex flex-col items-center justify-center py-2.5 gap-1" data-sec="dashboard">
        <div class="mobile-nav-icon-wrap w-10 h-8 flex items-center justify-center rounded-xl transition-all" style="background:#FEE2E2;"><i data-lucide="layout-dashboard" class="w-4 h-4" style="color:#B11226;"></i></div>
        <span class="mobile-nav-label text-[10px] font-medium leading-none" style="color:#B11226;">Dashboard</span>
      </button>
      <button type="button" data-section-link="appeals" class="tao-mobile-nav-btn flex-1 flex flex-col items-center justify-center py-2.5 gap-1" data-sec="appeals">
        <div class="mobile-nav-icon-wrap w-10 h-8 flex items-center justify-center rounded-xl transition-all"><i data-lucide="user-plus" class="w-4 h-4" style="color:#4B5563;"></i></div>
        <span class="mobile-nav-label text-[10px] font-medium leading-none" style="color:#4B5563;">Appeals</span>
      </button>
    </div>
  </nav>
</div>

<!-- Decision modal — the Art. IV Sec. 11-C note recorded on the President's behalf -->
<div id="decisionBackdrop" class="hidden fixed inset-0 z-40" style="background:rgba(15,15,20,0.45);"></div>
<div id="decisionModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
  <div class="modern-card bg-white rounded-2xl w-full max-w-lg p-5" style="box-shadow:0 20px 50px rgba(0,0,0,0.25);">
    <h3 id="decisionTitle" class="font-bold text-base mb-1" style="color:#1a1a2e;">Record Decision</h3>
    <p id="decisionApplicant" class="text-xs text-gray-500 mb-4"></p>

    <label for="decisionNote" class="text-xs font-semibold block mb-1" style="color:#1a1a2e;">
      Decision on behalf of the University President <span style="color:#B11226;">*</span>
    </label>
    <p class="text-[11px] text-gray-500 mb-2">
      Required. Recorded against this appeal as the authority for the ruling (Art. IV Sec. 11-C) and kept
      separately from evaluation remarks for audit.
    </p>
    <textarea id="decisionNote" rows="4" class="tao-input modern-input w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none" placeholder="e.g. Approved by the University President per endorsement dated ... , on the recommendation of TAO Central."></textarea>
    <p id="decisionError" class="hidden text-[11px] mt-1.5 font-medium" style="color:#B91C1C;"></p>

    <div class="flex justify-end gap-2 mt-4">
      <button type="button" id="decisionCancel" class="px-4 py-2 rounded-xl text-sm font-semibold" style="background:#F3F4F6; color:#374151;">Cancel</button>
      <button type="button" id="decisionConfirm" class="px-4 py-2 rounded-xl text-sm font-semibold text-white" style="background:#B11226;">Record Decision</button>
    </div>
  </div>
</div>

<script>
  var CSRF_TOKEN = <?= json_encode(csrf_token()) ?>;
  var APP_URL = <?= json_encode(APP_URL) ?>;

  lucide.createIcons();

  // Mirrors ARTEMIS_APPEAL_CHAIN / ARTEMIS_APPEAL_TAO_STAGES in
  // app/Support/ui_helpers.php — emitted from PHP so they cannot drift apart.
  var APPEAL_CHAIN = <?= json_encode(ARTEMIS_APPEAL_CHAIN) ?>;
  var TAO_STAGES = <?= json_encode(ARTEMIS_APPEAL_TAO_STAGES) ?>;
  var STAGE_EVALUATION = TAO_STAGES[0];
  var STAGE_FOR_APPROVAL = TAO_STAGES[1];

  // The same 5-circle tracker the rest of ARTEMIS uses, pre-rendered by
  // admin_progress_tracker_html() once per stage and keyed 1..5.
  var APPEAL_TRACKERS = <?= json_encode($appealTrackerHtml, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;

<?php
    $mapAppeal = static function (array $a): array {
        return [
            'id' => (int) $a['appeal_id'],
            'reference' => 'APPEAL-' . str_pad((string) $a['appeal_id'], 5, '0', STR_PAD_LEFT),
            'fullName' => $a['full_name'], 'email' => $a['email'], 'contactNumber' => $a['contact_number'],
            'secondarySchool' => $a['secondary_school'], 'campus' => $a['campus'],
            'discipline' => $a['discipline'], 'achievements' => $a['achievements_summary'],
            'academicStanding' => $a['academic_standing_note'],
            'certificatesUrl' => $a['certificates_path'] ? APP_URL . '/' . $a['certificates_path'] : null,
            'recommendationUrl' => $a['recommendation_letter_path'] ? APP_URL . '/' . $a['recommendation_letter_path'] : null,
            'schoolStatementUrl' => $a['school_statement_path'] ? APP_URL . '/' . $a['school_statement_path'] : null,
            'status' => $a['status'],
            'ocaRemarks' => $a['remarks'],
            'taoRemarks' => $a['tao_remarks'] ?? null,
            'decisionNote' => $a['presidential_decision_note'] ?? null,
            'submittedAt' => format_date($a['submitted_at'], 'M j, Y'),
            'decidedAt' => $a['decided_at'] ? format_date($a['decided_at'], 'M j, Y') : null,
        ];
    };
?>
  var APPEALS = <?= json_encode(array_map($mapAppeal, $appeals), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
  var DECIDED = <?= json_encode(array_map($mapAppeal, $decidedAppeals), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;

  var STATUS_COLORS = {
    'Evaluation Stage': ['#F3E8FF', '#7C3AED'],
    'For Approval (President via TAO)': ['#DBEAFE', '#1D4ED8'],
    'Approved': ['#DCFCE7', '#15803D'],
    'Rejected': ['#FEE2E2', '#B91C1C'],
  };

  function appealStage(status) {
    var idx = APPEAL_CHAIN.indexOf(status);
    return idx === -1 ? APPEAL_CHAIN.length + 1 : idx + 1;
  }

  function escapeHtml(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  function showFlash(message, type) {
    var el = document.getElementById('jsFlash');
    var bg = type === 'error' ? '#FEE2E2' : '#DCFCE7';
    var border = type === 'error' ? '#EF4444' : '#22C55E';
    var color = type === 'error' ? '#B91C1C' : '#15803D';
    el.innerHTML = '<div class="rounded-2xl p-4 border text-sm font-medium" style="background:' + bg + '; border-color:' + border + '; color:' + color + ';">' + escapeHtml(message) + '</div>';
    el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }

  // ---------- Shared card pieces ----------
  function attachmentsHtml(a) {
    var labels = {
      certificatesUrl: 'Certificates / Awards',
      recommendationUrl: 'Recommendation Letter',
      schoolStatementUrl: 'School Statement',
    };
    var links = Object.keys(labels).map(function (key) {
      if (!a[key]) {
        return '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-medium mr-1.5 mb-1.5" style="background:#F9FAFB; color:#9CA3AF; border:1px dashed #E5E7EB;">'
          + '<i data-lucide="file-x" class="w-3 h-3"></i> ' + labels[key] + ' — not uploaded</span>';
      }
      return '<a href="' + a[key] + '" target="_blank" rel="noopener" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-medium mr-1.5 mb-1.5 hover:opacity-80" style="background:#F3F4F6; color:#374151;">'
        + '<i data-lucide="paperclip" class="w-3 h-3"></i> ' + labels[key] + '</a>';
    }).join('');

    return '<div class="mb-3">'
      + '<div class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 mb-1.5">Attachments</div>'
      + '<div class="flex flex-wrap">' + links + '</div>'
      + '</div>';
  }

  /* OCA's screening note is shown read-only, above the reviewer's own field —
     TAO evaluates on top of what OCA already found, not blind to it. It lives
     in its own column, so nothing written here overwrites it. */
  function ocaRemarksHtml(a) {
    if (!a.ocaRemarks) {
      return '<div class="text-xs rounded-lg p-2.5 mb-2" style="background:#F9FAFB; color:#9CA3AF; border:1px dashed #E5E7EB;">'
        + 'No screening remarks were recorded by the OCA.</div>';
    }
    return '<div class="text-xs rounded-lg p-2.5 mb-2" style="background:#FEF9C3; color:#92400E;">'
      + '<span class="font-semibold">OCA screening remarks (read-only):</span> ' + escapeHtml(a.ocaRemarks) + '</div>';
  }

  function detailsHtml(a) {
    return '<div class="text-xs text-gray-600 leading-relaxed mb-2"><span class="font-semibold" style="color:#1a1a2e;">Achievements:</span> ' + escapeHtml(a.achievements) + '</div>'
      + (a.academicStanding ? '<div class="text-xs text-gray-600 leading-relaxed mb-2"><span class="font-semibold" style="color:#1a1a2e;">Academic Standing:</span> ' + escapeHtml(a.academicStanding) + '</div>' : '');
  }

  function headerHtml(a) {
    var colors = STATUS_COLORS[a.status] || ['#F3F4F6', '#6B7280'];
    return '<div class="flex items-start justify-between gap-2 mb-2">'
      + '<div>'
        + '<div class="text-[11px] text-gray-400 font-mono mb-0.5">' + escapeHtml(a.reference) + '</div>'
        + '<div class="text-sm font-semibold" style="color:#1a1a2e;">' + escapeHtml(a.fullName) + '</div>'
        + '<div class="text-xs text-gray-400">' + escapeHtml(a.secondarySchool) + ' &middot; ' + escapeHtml(a.email) + (a.contactNumber ? ' &middot; ' + escapeHtml(a.contactNumber) : '') + '</div>'
        + '<div class="text-[11px] text-gray-400 mt-0.5">Submitted ' + escapeHtml(a.submittedAt) + ' &middot; ' + escapeHtml(a.campus) + (a.decidedAt ? ' &middot; Decided ' + escapeHtml(a.decidedAt) : '') + '</div>'
        + (a.discipline ? '<span class="inline-block mt-1 px-2 py-0.5 rounded-md text-[10px] font-semibold" style="background:#FEE2E2; color:#B11226;">' + escapeHtml(a.discipline) + '</span>' : '')
      + '</div>'
      + '<span class="px-2.5 py-1 rounded-full text-[10px] font-semibold flex-shrink-0" style="background:' + colors[0] + '; color:' + colors[1] + ';">' + escapeHtml(a.status) + '</span>'
    + '</div>';
  }

  // ---------- Queue ----------
  function filteredQueue() {
    var q = document.getElementById('appealSearch').value.trim().toLowerCase();
    var disc = document.getElementById('disciplineFilter').value;
    var stage = document.getElementById('stageFilter').value;

    return APPEALS.filter(function (a) {
      if (disc && a.discipline !== disc) return false;
      if (stage && a.status !== stage) return false;
      if (!q) return true;
      return (a.fullName || '').toLowerCase().indexOf(q) !== -1
        || (a.discipline || '').toLowerCase().indexOf(q) !== -1;
    });
  }

  function renderQueue() {
    var el = document.getElementById('appealsList');
    var rows = filteredQueue();

    document.getElementById('queueCount').textContent = '(' + APPEALS.length + ')';
    renderNotifications();

    if (!rows.length) {
      el.innerHTML = '<div class="modern-card bg-white rounded-2xl border border-gray-100 p-8 text-center text-sm text-gray-400">'
        + (APPEALS.length
            ? 'No appeals match this search.'
            : 'No appeals are waiting on TAO Central right now. They appear here once the OCA endorses them to the Evaluation Stage.')
        + '</div>';
      lucide.createIcons();
      return;
    }

    el.innerHTML = rows.map(function (a) {
      var atEvaluation = a.status === STAGE_EVALUATION;

      // The action offered is the one this stage actually permits: evaluate and
      // hand on at Evaluation Stage, rule at For Approval. Showing both
      // everywhere would invite a 403 from the controller's stage check.
      var actions = atEvaluation
        ? '<button type="button" class="appeal-forward-btn px-3 py-1.5 rounded-lg text-xs font-semibold inline-flex items-center gap-1.5" style="background:#DBEAFE; color:#1D4ED8;" data-id="' + a.id + '">'
            + '<i data-lucide="send" class="w-3.5 h-3.5"></i> Evaluate &amp; Forward</button>'
        : '<button type="button" class="appeal-approve-btn px-3 py-1.5 rounded-lg text-xs font-semibold inline-flex items-center gap-1.5" style="background:#DCFCE7; color:#15803D;" data-id="' + a.id + '">'
            + '<i data-lucide="check" class="w-3.5 h-3.5"></i> Approve</button>'
          + '<button type="button" class="appeal-reject-btn px-3 py-1.5 rounded-lg text-xs font-semibold inline-flex items-center gap-1.5" style="background:#FEE2E2; color:#B91C1C;" data-id="' + a.id + '">'
            + '<i data-lucide="x" class="w-3.5 h-3.5"></i> Reject</button>';

      return '<div class="modern-card bg-white rounded-2xl border border-gray-100 p-4 sm:p-5" data-appeal-id="' + a.id + '" style="box-shadow: 0 1px 6px rgba(0,0,0,0.06);">'
        + headerHtml(a)
        + '<div class="mb-3 pt-1">' + (APPEAL_TRACKERS[appealStage(a.status)] || '') + '</div>'
        + detailsHtml(a)
        + attachmentsHtml(a)
        + ocaRemarksHtml(a)
        + '<div class="mb-1">'
          + '<label class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 block mb-1" for="taoNote-' + a.id + '">Your evaluation remarks</label>'
          + '<textarea id="taoNote-' + a.id + '" rows="2" class="tao-note-input tao-input modern-input w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:outline-none" data-id="' + a.id + '" placeholder="Evaluation notes for this appeal...">' + escapeHtml(a.taoRemarks || '') + '</textarea>'
          + '<div class="flex justify-end mt-1.5">'
            + '<button type="button" class="appeal-savenote-btn px-3 py-1 rounded-lg text-[11px] font-semibold" style="background:#FEF9C3; color:#92400E;" data-id="' + a.id + '">Save Remarks</button>'
          + '</div>'
        + '</div>'
        + '<div class="flex flex-wrap gap-2 mt-2 pt-3 border-t border-gray-100">' + actions + '</div>'
      + '</div>';
    }).join('');

    lucide.createIcons();
  }

  // ---------- Decided ----------
  function renderDecided() {
    var el = document.getElementById('decidedList');
    document.getElementById('decidedCount').textContent = '(' + DECIDED.length + ')';

    if (!DECIDED.length) {
      el.innerHTML = '<div class="modern-card bg-white rounded-2xl border border-gray-100 p-8 text-center text-sm text-gray-400">No appeals have been ruled on yet.</div>';
      return;
    }

    el.innerHTML = DECIDED.map(function (a) {
      return '<div class="modern-card bg-white rounded-2xl border border-gray-100 p-4 sm:p-5" style="box-shadow: 0 1px 6px rgba(0,0,0,0.06);">'
        + headerHtml(a)
        + '<div class="mb-3 pt-1">' + (APPEAL_TRACKERS[appealStage(a.status)] || '') + '</div>'
        + detailsHtml(a)
        + attachmentsHtml(a)
        + (a.taoRemarks ? '<div class="text-xs rounded-lg p-2.5 mb-2" style="background:#F3E8FF; color:#6B21A8;"><span class="font-semibold">TAO evaluation remarks:</span> ' + escapeHtml(a.taoRemarks) + '</div>' : '')
        + (a.decisionNote
            ? '<div class="text-xs rounded-lg p-2.5" style="background:#EFF6FF; color:#1D4ED8; border-left:3px solid #1D4ED8;"><span class="font-semibold">Decision on behalf of the University President:</span> ' + escapeHtml(a.decisionNote) + '</div>'
            : '<div class="text-xs rounded-lg p-2.5" style="background:#F9FAFB; color:#9CA3AF; border:1px dashed #E5E7EB;">No presidential decision note on record — this appeal was decided before the note became required.</div>')
      + '</div>';
    }).join('');

    lucide.createIcons();
  }

  // ---------- Top-bar queue indicator ----------
  /* Rendered from APPEALS rather than server-side, so forwarding or ruling on an
     appeal drops it out of the bell in the same pass that drops it from the
     queue — the badge can never disagree with the list beneath it. */
  function renderNotifications() {
    var badge = document.getElementById('notifBadge');
    var list = document.getElementById('notifList');
    if (!badge || !list) return;

    // The admin badge is a fixed 16px circle, so anything past a single digit
    // has to collapse rather than widen it.
    badge.textContent = APPEALS.length > 9 ? '9+' : String(APPEALS.length);
    badge.classList.toggle('hidden', APPEALS.length === 0);

    if (!APPEALS.length) {
      list.innerHTML = '<div class="px-4 py-6 text-center text-xs text-gray-600">You\'re all caught up.</div>';
      return;
    }

    // Row markup mirrors the admin dropdown's .notif-item: tinted icon circle,
    // message + timestamp line, unread dot, faint blue-white row background.
    list.innerHTML = APPEALS.map(function (a) {
      var atEvaluation = a.status === STAGE_EVALUATION;
      var color = atEvaluation ? '#7C3AED' : '#1D4ED8';
      var icon = atEvaluation ? 'clipboard-check' : 'stamp';
      var what = atEvaluation
        ? ' is awaiting your evaluation'
        : " is awaiting the President's approval";

      return '<div class="notif-item px-4 py-3 border-b border-gray-50 flex gap-3 cursor-pointer transition-colors hover:bg-gray-100" data-id="' + a.id + '" style="background:#FAFBFF;">'
        + '<div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0" style="background:' + color + '20;">'
          + '<i data-lucide="' + icon + '" class="w-4 h-4" style="color:' + color + ';"></i></div>'
        + '<div class="flex-1 min-w-0">'
          + '<div class="text-xs font-medium leading-snug notif-msg" style="color:#1a1a2e;">' + escapeHtml(a.fullName) + what + '</div>'
          + '<div class="text-xs text-gray-400 mt-0.5">' + escapeHtml(a.reference) + '</div>'
        + '</div>'
        + '<div class="notif-dot w-2 h-2 rounded-full flex-shrink-0 mt-1" style="background:#B11226;"></div>'
      + '</div>';
    }).join('');

    lucide.createIcons();
  }

  var notifBtn = document.getElementById('notifBtn');
  var notifDropdown = document.getElementById('notifDropdown');
  notifBtn.addEventListener('click', function (e) {
    e.stopPropagation();
    notifDropdown.classList.toggle('hidden');
  });
  document.getElementById('notifCloseBtn').addEventListener('click', function () {
    notifDropdown.classList.add('hidden');
  });
  document.addEventListener('click', function (e) {
    if (!notifDropdown.classList.contains('hidden') && !notifDropdown.contains(e.target) && !notifBtn.contains(e.target)) {
      notifDropdown.classList.add('hidden');
    }
  });
  document.getElementById('notifList').addEventListener('click', function (e) {
    var item = e.target.closest('.notif-item');
    if (!item) return;
    notifDropdown.classList.add('hidden');
    setSection('appeals');
    setSubtab('queue');
    var card = document.querySelector('[data-appeal-id="' + item.dataset.id + '"]');
    if (card) card.scrollIntoView({ behavior: 'smooth', block: 'center' });
  });

  // ---------- Section + sub-tab navigation ----------
  var TITLES = { dashboard: 'Dashboard', appeals: 'Admission Appeals' };

  function setSection(sec) {
    document.querySelectorAll('.tao-section').forEach(function (p) {
      var active = p.dataset.section === sec;
      p.classList.toggle('hidden', !active);
      p.classList.toggle('flex', active);
    });
    document.querySelectorAll('.tao-nav-btn').forEach(function (b) {
      var active = b.dataset.section === sec;
      b.style.background = active ? 'rgba(255,255,255,0.2)' : '';
      b.style.color = active ? '#fff' : 'rgba(255,255,255,0.7)';
      b.style.fontWeight = active ? '600' : '400';
      if (active) b.setAttribute('data-active', 'true'); else b.removeAttribute('data-active');
    });
    document.querySelectorAll('.tao-mobile-nav-btn').forEach(function (b) {
      var active = b.dataset.sec === sec;
      var wrap = b.querySelector('.mobile-nav-icon-wrap');
      if (wrap) {
        wrap.style.background = active ? '#FEE2E2' : 'transparent';
        var icon = wrap.querySelector('svg, i');
        if (icon) icon.style.color = active ? '#B11226' : '#4B5563';
      }
      var label = b.querySelector('.mobile-nav-label');
      if (label) label.style.color = active ? '#B11226' : '#4B5563';
    });
    document.getElementById('pageTitleDesktop').textContent = TITLES[sec] || 'Dashboard';
  }

  document.querySelectorAll('.tao-nav-btn').forEach(function (b) {
    b.addEventListener('click', function () { setSection(b.dataset.section); });
  });
  document.querySelectorAll('[data-section-link]').forEach(function (b) {
    b.addEventListener('click', function () { setSection(b.dataset.sectionLink); });
  });

  function setSubtab(name) {
    document.getElementById('appealsList').classList.toggle('hidden', name !== 'queue');
    document.getElementById('decidedList').classList.toggle('hidden', name !== 'decided');
    // The filter bar drives the queue only; Decided is a plain history list.
    document.getElementById('queueFilterBar').classList.toggle('hidden', name !== 'queue');
    document.querySelectorAll('.tao-subtab').forEach(function (b) {
      var active = b.dataset.subtab === name;
      b.style.background = active ? '#B11226' : '#fff';
      b.style.color = active ? '#fff' : '#6B7280';
      b.style.border = active ? 'none' : '1px solid #E5E7EB';
    });
  }
  document.querySelectorAll('.tao-subtab').forEach(function (b) {
    b.addEventListener('click', function () { setSubtab(b.dataset.subtab); });
  });

  ['appealSearch', 'disciplineFilter', 'stageFilter'].forEach(function (id) {
    document.getElementById(id).addEventListener('input', renderQueue);
  });

  // ---------- Actions ----------
  function appealAction(id, action, extra) {
    var body = Object.assign({ csrf_token: CSRF_TOKEN, appeal_id: id, action: action }, extra || {});
    return fetch(APP_URL + '/tao/admission-appeals', {
      method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body),
    }).then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); });
  }

  /* An appeal that leaves this office's two stages leaves the queue — it is
     finished, and belongs under Decided instead. */
  function moveOutOfQueue(id, newStatus, decisionNote) {
    var idx = APPEALS.findIndex(function (x) { return x.id === id; });
    if (idx === -1) return;

    if (TAO_STAGES.indexOf(newStatus) !== -1) {
      APPEALS[idx].status = newStatus;
      renderQueue();
      return;
    }

    var moved = APPEALS.splice(idx, 1)[0];
    moved.status = newStatus;
    moved.decidedAt = new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    if (decisionNote) moved.decisionNote = decisionNote;
    DECIDED.unshift(moved);
    renderQueue();
    renderDecided();
  }

  function noteFor(id) {
    var box = document.getElementById('taoNote-' + id);
    return box ? box.value.trim() : '';
  }

  // ---- Decision modal ----
  var decisionBackdrop = document.getElementById('decisionBackdrop');
  var decisionModal = document.getElementById('decisionModal');
  var decisionNoteBox = document.getElementById('decisionNote');
  var decisionError = document.getElementById('decisionError');
  var pendingDecision = null;

  function openDecision(id, mode) {
    var a = APPEALS.find(function (x) { return x.id === id; });
    if (!a) return;
    pendingDecision = { id: id, mode: mode };
    decisionNoteBox.value = '';
    decisionError.classList.add('hidden');
    document.getElementById('decisionTitle').textContent = mode === 'approve' ? 'Approve Admission Appeal' : 'Reject Admission Appeal';
    document.getElementById('decisionApplicant').textContent = a.reference + ' — ' + a.fullName;
    decisionBackdrop.classList.remove('hidden');
    decisionModal.classList.remove('hidden');
    decisionNoteBox.focus();
  }
  function closeDecision() {
    pendingDecision = null;
    decisionBackdrop.classList.add('hidden');
    decisionModal.classList.add('hidden');
  }
  document.getElementById('decisionCancel').addEventListener('click', closeDecision);
  decisionBackdrop.addEventListener('click', closeDecision);

  document.getElementById('decisionConfirm').addEventListener('click', function () {
    if (!pendingDecision) return;
    var note = decisionNoteBox.value.trim();
    if (note === '') {
      decisionError.textContent = 'A decision note on behalf of the University President is required.';
      decisionError.classList.remove('hidden');
      return;
    }
    var id = pendingDecision.id;
    var mode = pendingDecision.mode;
    var evaluationNote = noteFor(id);
    closeDecision();

    appealAction(id, mode, { decision_note: note, remarks: evaluationNote })
      .then(function (res) {
        if (!res.ok) { showFlash(res.data.error || 'Failed to record the decision.', 'error'); return; }
        moveOutOfQueue(id, res.data.status, note);
        showFlash('Appeal ' + res.data.status.toLowerCase() + '. The applicant has been notified.', 'success');
      });
  });

  document.getElementById('appealsList').addEventListener('click', function (e) {
    var fwd = e.target.closest('.appeal-forward-btn');
    var save = e.target.closest('.appeal-savenote-btn');
    var app = e.target.closest('.appeal-approve-btn');
    var rej = e.target.closest('.appeal-reject-btn');

    if (fwd) {
      var fwdId = parseInt(fwd.dataset.id, 10);
      fwd.disabled = true;
      appealAction(fwdId, 'advance', { tao_remarks: noteFor(fwdId) }).then(function (res) {
        fwd.disabled = false;
        if (!res.ok) { showFlash(res.data.error || 'Failed to forward this appeal.', 'error'); return; }
        var a = APPEALS.find(function (x) { return x.id === fwdId; });
        if (a) a.taoRemarks = res.data.tao_remarks || a.taoRemarks;
        moveOutOfQueue(fwdId, res.data.status);
        showFlash('Evaluation recorded and forwarded for the President\'s approval.', 'success');
      });
    } else if (save) {
      var saveId = parseInt(save.dataset.id, 10);
      var text = noteFor(saveId);
      if (text === '') { showFlash('Enter your evaluation remarks before saving.', 'error'); return; }
      save.disabled = true;
      appealAction(saveId, 'remark', { remarks: text }).then(function (res) {
        save.disabled = false;
        if (!res.ok) { showFlash(res.data.error || 'Failed to save remarks.', 'error'); return; }
        var a = APPEALS.find(function (x) { return x.id === saveId; });
        if (a) a.taoRemarks = text;
        showFlash('Evaluation remarks saved.', 'success');
      });
    } else if (app) {
      openDecision(parseInt(app.dataset.id, 10), 'approve');
    } else if (rej) {
      openDecision(parseInt(rej.dataset.id, 10), 'reject');
    }
  });

  renderQueue();
  renderDecided();
  setSubtab('queue');
</script>
</body>
</html>
