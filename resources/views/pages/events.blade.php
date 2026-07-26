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
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/modern.css">
<script src="<?= APP_URL ?>/assets/js/lucide.js"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
  body { font-family: 'Inter', system-ui, sans-serif; }
  @keyframes ev-blob { 0%,100% { transform: scale(1); opacity: .08; } 50% { transform: scale(1.15); opacity: .15; } }

  /* Entrance animation for JS-rendered event cards — staggered via inline animation-delay */
  @keyframes ev-card-in { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: translateY(0); } }
  .event-card { animation: ev-card-in 0.5s var(--ease-out-soft, ease-out) both; }
  .event-card .view-details-arrow { transition: transform 0.25s var(--ease-out-soft, ease-out); }
  .event-card:hover .view-details-arrow { transform: translateX(3px); }

  /* Filter bar sticks below the nav once scrolled past; gains depth when stuck */
  #filterBar { position: sticky; top: 4.25rem; z-index: 20; transition: box-shadow 0.25s var(--ease-out-soft, ease-out); }
  #filterBar.is-stuck { box-shadow: 0 8px 24px rgba(0,0,0,0.09); }

  /* Modal + travel-ack pop transition */
  #eventModal .modal-panel, #travelAckModal .modal-panel {
    transition: transform 0.32s var(--ease-out-soft, ease-out), opacity 0.32s var(--ease-out-soft, ease-out);
    transform: translateY(24px) scale(0.98);
    opacity: 0;
  }
  #eventModal.is-open .modal-panel, #travelAckModal.is-open .modal-panel { transform: translateY(0) scale(1); opacity: 1; }
  #eventModal, #travelAckBackdrop, #travelAckModal { transition: opacity 0.25s var(--ease-out-soft, ease-out); }

  /* Toast slide + fade */
  #toast { transition: opacity 0.3s var(--ease-out-soft, ease-out), transform 0.3s var(--ease-out-soft, ease-out); opacity: 0; transform: translateX(-50%) translateY(10px); }
  #toast.is-open { opacity: 1; transform: translateX(-50%) translateY(0); }

  /* Active-filter checkmark */
  .filter-opt .filter-opt-check { opacity: 0; transition: opacity 0.15s var(--ease-out-soft, ease-out); }
  .filter-opt.is-active { font-weight: 600; color: #B11226; background: rgba(177,18,38,0.06); }
  .filter-opt.is-active .filter-opt-check { opacity: 1; }

  @media (prefers-reduced-motion: reduce) {
    .event-card, #eventModal .modal-panel, #travelAckModal .modal-panel, #toast { animation: none !important; transition: none !important; }
  }

  /* Scroll progress bar — sits above the sticky nav, fills with scroll depth */
  #scrollProgressBar { position: fixed; top: 0; left: 0; height: 6px; width: 0%; z-index: 40; background: #B11226; transition: width 0.1s linear; pointer-events: none; }
  @media (prefers-reduced-motion: reduce) { #scrollProgressBar { transition: none; } }

  /* Hide the native page scrollbar — the progress bar above is the scroll indicator */
  html, body { scrollbar-width: none; -ms-overflow-style: none; }
  html::-webkit-scrollbar, body::-webkit-scrollbar { display: none; width: 0; height: 0; }
</style>
</head>
<body class="min-h-screen pb-20 sm:pb-0" style="background:#F5F5F5;">
<div id="scrollProgressBar"></div>

<nav class="sticky top-0 z-30 bg-white/95 backdrop-blur-md border-b border-gray-100" style="box-shadow: 0 1px 8px rgba(0,0,0,0.06);">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between h-16">
    <div class="flex items-center gap-3">
      <a href="<?= e(APP_URL) ?>" class="hidden sm:flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-800 transition-colors mr-2">
        <i data-lucide="chevron-left" class="w-4 h-4"></i> Back
      </a>
      <div class="hidden sm:block h-5 w-px bg-gray-200"></div>
      <div class="flex items-center gap-2.5">
        <div class="w-8 h-8 rounded-full bg-white flex items-center justify-center overflow-hidden flex-shrink-0" style="border:1px solid #eee;"><img src="<?= APP_URL ?>/assets/images/bsulogo.jpg" alt="BatStateU OCA Logo" class="w-full h-full object-cover"></div>
        <div><div class="font-bold text-sm tracking-wider" style="color:#B11226;">ARTEMIS</div><div class="text-xs text-gray-600 hidden sm:block">Events & Cultural Calendar</div></div>
      </div>
    </div>
    <a href="<?= e($portalUrl) ?>" class="hidden sm:block px-4 py-2 rounded-xl text-sm font-semibold text-white hover:opacity-90 transition-opacity" style="background: linear-gradient(135deg, #B11226, #7a0d1a);"><?= $user ? 'Go to Dashboard' : 'Login to Portal' ?></a>
  </div>
</nav>

<div class="relative overflow-hidden">
  <img src="<?= APP_URL ?>/assets/images/familypic.jpg" alt="" class="absolute inset-0 w-full h-full object-cover">
  <div class="absolute inset-0" style="background: linear-gradient(135deg, rgba(177,18,38,0.92) 0%, rgba(122,13,26,0.92) 100%);"></div>
  <div class="absolute top-0 right-0 w-72 h-72 rounded-full opacity-10" style="background:#D4AF37; filter:blur(60px); transform:translate(30%,-30%); animation: ev-blob 7s ease-in-out infinite;"></div>
  <div class="absolute bottom-0 left-0 w-56 h-56 rounded-full opacity-10" style="background:#fff; filter:blur(50px); transform:translate(-30%,30%); animation: ev-blob 9s ease-in-out infinite 2s;"></div>

  <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14 sm:py-20 text-center">
    <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-medium mb-5" style="background: rgba(212,175,55,0.18); color:#D4AF37; border:1px solid rgba(212,175,55,0.35);">
      <i data-lucide="sparkles" class="w-3.5 h-3.5"></i> OCA Cultural Calendar <?= date('Y') ?>
    </div>
    <h1 class="text-4xl sm:text-5xl font-bold text-white mb-4 tracking-tight">Cultural Events</h1>
    <p class="text-red-100 max-w-lg mx-auto text-sm sm:text-base leading-relaxed">Explore all upcoming performances, competitions, exhibitions, and cultural celebrations by the BatStateU ARASOF-Nasugbu Culture and Arts Office.</p>

    <div class="flex items-center justify-center gap-0 mt-10">
      <div class="flex items-center"><div class="text-center px-6 sm:px-10"><div class="text-3xl sm:text-4xl font-bold text-white"><?= count(array_filter($eventsData, fn($e) => $e['status'] === 'Upcoming')) ?></div><div class="text-xs text-red-200 mt-0.5 uppercase tracking-wider">Upcoming</div></div><div class="w-px h-10 bg-white/15 flex-shrink-0"></div></div>
      <div class="flex items-center"><div class="text-center px-6 sm:px-10"><div class="text-3xl sm:text-4xl font-bold text-white"><?= count(array_filter($eventsData, fn($e) => $e['status'] === 'Planning')) ?></div><div class="text-xs text-red-200 mt-0.5 uppercase tracking-wider">In Planning</div></div><div class="w-px h-10 bg-white/15 flex-shrink-0"></div></div>
      <div class="flex items-center"><div class="text-center px-6 sm:px-10"><div class="text-3xl sm:text-4xl font-bold text-white"><?= count($eventsData) ?></div><div class="text-xs text-red-200 mt-0.5 uppercase tracking-wider">Total Events</div></div></div>
    </div>
  </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

  <div id="stickySentinel" style="height:1px;"></div>
  <div id="filterBar" class="bg-white rounded-2xl p-3 sm:p-4 border border-gray-100 flex flex-col gap-3" style="box-shadow: 0 2px 12px rgba(0,0,0,0.06);">
    <div class="relative">
      <label for="searchInput" class="sr-only">Search events by name or venue</label>
      <i data-lucide="search" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-600"></i>
      <input id="searchInput" placeholder="Search events by name or venue&hellip;" class="ev-input modern-input w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none transition-colors">
    </div>

    <div class="flex items-center gap-2">
      <div class="relative flex-1" id="typeDropdownWrap">
        <button type="button" id="typeDropdownBtn" class="filter-dropdown-btn modern-btn w-full flex items-center justify-between gap-1 px-3 py-2 rounded-xl border text-xs font-medium transition-all" style="background:#F9FAFB; border-color:#E5E7EB; color:#374151;">
          <span class="truncate">Type</span><i data-lucide="chevron-down" class="w-3 h-3 flex-shrink-0" style="color:#9CA3AF;"></i>
        </button>
        <div id="typeDropdown" class="hidden absolute top-full mt-2 left-0 z-50 bg-white rounded-2xl border border-gray-100 py-1.5 min-w-[140px]" style="box-shadow: 0 8px 32px rgba(0,0,0,0.12);">
          <button type="button" class="filter-opt is-active w-full text-left px-4 py-2.5 text-xs flex items-center justify-between gap-2" data-group="type" data-value="All">All<i data-lucide="check" class="filter-opt-check w-3 h-3 flex-shrink-0"></i></button>
          <?php foreach ($eventTypes as $t): ?><button type="button" class="filter-opt w-full text-left px-4 py-2.5 text-xs flex items-center justify-between gap-2" data-group="type" data-value="<?= e($t) ?>"><?= e($t) ?><i data-lucide="check" class="filter-opt-check w-3 h-3 flex-shrink-0"></i></button><?php endforeach; ?>
        </div>
      </div>
      <div class="relative flex-1" id="monthDropdownWrap">
        <button type="button" id="monthDropdownBtn" class="filter-dropdown-btn modern-btn w-full flex items-center justify-between gap-1 px-3 py-2 rounded-xl border text-xs font-medium transition-all" style="background:#F9FAFB; border-color:#E5E7EB; color:#374151;">
          <span class="truncate">Month</span><i data-lucide="chevron-down" class="w-3 h-3 flex-shrink-0" style="color:#9CA3AF;"></i>
        </button>
        <div id="monthDropdown" class="hidden absolute top-full mt-2 left-0 z-50 bg-white rounded-2xl border border-gray-100 py-1.5 min-w-[140px]" style="box-shadow: 0 8px 32px rgba(0,0,0,0.12);">
          <button type="button" class="filter-opt is-active w-full text-left px-4 py-2.5 text-xs flex items-center justify-between gap-2" data-group="month" data-value="All Months">All Months<i data-lucide="check" class="filter-opt-check w-3 h-3 flex-shrink-0"></i></button>
          <?php foreach ($months as $m): ?><button type="button" class="filter-opt w-full text-left px-4 py-2.5 text-xs flex items-center justify-between gap-2" data-group="month" data-value="<?= e($m) ?>"><?= e($m) ?><i data-lucide="check" class="filter-opt-check w-3 h-3 flex-shrink-0"></i></button><?php endforeach; ?>
        </div>
      </div>
      <div class="relative flex-1" id="statusDropdownWrap">
        <button type="button" id="statusDropdownBtn" class="filter-dropdown-btn modern-btn w-full flex items-center justify-between gap-1 px-3 py-2 rounded-xl border text-xs font-medium transition-all" style="background:#F9FAFB; border-color:#E5E7EB; color:#374151;">
          <span class="truncate">Status</span><i data-lucide="chevron-down" class="w-3 h-3 flex-shrink-0" style="color:#9CA3AF;"></i>
        </button>
        <div id="statusDropdown" class="hidden absolute top-full mt-2 left-0 z-50 bg-white rounded-2xl border border-gray-100 py-1.5 min-w-[140px]" style="box-shadow: 0 8px 32px rgba(0,0,0,0.12);">
          <?php foreach (['All','Upcoming','Planning','Ongoing','Completed'] as $s): ?><button type="button" class="filter-opt<?= $s === 'All' ? ' is-active' : '' ?> w-full text-left px-4 py-2.5 text-xs flex items-center justify-between gap-2" data-group="status" data-value="<?= e($s) ?>"><?= e($s) ?><i data-lucide="check" class="filter-opt-check w-3 h-3 flex-shrink-0"></i></button><?php endforeach; ?>
        </div>
      </div>

      <button type="button" id="clearFiltersBtn" class="hidden modern-btn w-10 h-10 items-center justify-center rounded-xl border border-red-200 hover:bg-red-50 transition-colors flex-shrink-0" style="color:#B11226;" aria-label="Clear filters"><i data-lucide="x" class="w-3.5 h-3.5"></i></button>
      <div id="viewToggleWrap" class="flex items-center gap-0.5 bg-gray-100 rounded-xl p-1 flex-shrink-0">
        <button type="button" id="gridViewBtn" class="modern-btn w-9 h-9 rounded-lg flex items-center justify-center transition-all" style="background:#B11226; color:#fff;" aria-label="Grid view" aria-pressed="true">
          <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 16 16"><rect x="1" y="1" width="6" height="6" rx="1.5"></rect><rect x="9" y="1" width="6" height="6" rx="1.5"></rect><rect x="1" y="9" width="6" height="6" rx="1.5"></rect><rect x="9" y="9" width="6" height="6" rx="1.5"></rect></svg>
        </button>
        <button type="button" id="listViewBtn" class="modern-btn w-9 h-9 rounded-lg flex items-center justify-center transition-all" style="color:#6B7280;" aria-label="List view" aria-pressed="false">
          <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 16 16"><rect x="1" y="2" width="14" height="2.5" rx="1.25"></rect><rect x="1" y="6.75" width="14" height="2.5" rx="1.25"></rect><rect x="1" y="11.5" width="14" height="2.5" rx="1.25"></rect></svg>
        </button>
      </div>
    </div>
  </div>

  <div class="mt-4 mb-5 flex items-center justify-between">
    <p class="text-xs text-gray-600"><span class="font-semibold" id="resultCount" style="color:#1a1a2e;"><?= count($eventsData) ?></span> of <?= count($eventsData) ?> events</p>
    <button type="button" id="clearFiltersLink" class="hidden text-xs font-medium hover:opacity-70 transition-opacity" style="color:#B11226;">Clear filters</button>
  </div>

  <div id="emptyState" class="hidden bg-white rounded-2xl p-16 text-center border border-gray-100" style="box-shadow: 0 2px 12px rgba(0,0,0,0.06);">
    <i data-lucide="calendar" class="w-12 h-12 text-gray-200 mx-auto mb-3"></i>
    <p class="text-gray-600 text-sm">No events match your filters.</p>
    <button type="button" id="clearFiltersEmptyBtn" class="mt-3 text-sm font-medium hover:opacity-80" style="color:#B11226;">Clear all filters</button>
  </div>

  <div id="gridView" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5"></div>
  <div id="listView" class="hidden flex-col gap-3"></div>

  <div class="mt-12 rounded-2xl overflow-hidden relative" style="background: linear-gradient(135deg, #B11226 0%, #7a0d1a 100%);">
    <div class="absolute -right-12 -bottom-12 w-48 h-48 rounded-full opacity-10" style="background:#D4AF37; filter:blur(40px); animation: ev-blob 6s ease-in-out infinite;"></div>
    <div class="relative p-8 sm:p-10 text-center text-white">
      <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-medium mb-4" style="background: rgba(212,175,55,0.2); color:#D4AF37; border:1px solid rgba(212,175,55,0.3);"><i data-lucide="sparkles" class="w-3 h-3"></i> Join the Cultural Community</div>
      <h3 class="text-2xl sm:text-3xl font-bold mb-3">Want to participate in these events?</h3>
      <p class="text-red-100 text-sm max-w-md mx-auto mb-7 leading-relaxed">Log in to ARTEMIS to submit your audition application, register for events, and track your cultural journey.</p>
      <a href="<?= e($portalUrl) ?>" class="hidden sm:inline-flex items-center gap-2 px-8 py-3.5 rounded-xl text-sm font-semibold hover:opacity-90 transition-opacity" style="background:#D4AF37; color:#1a1a2e; box-shadow: 0 4px 16px rgba(212,175,55,0.3);">Apply Now via ARTEMIS <i data-lucide="arrow-right" class="w-4 h-4"></i></a>
      <p class="sm:hidden text-xs text-red-200 mt-1">Use the Login button below to apply.</p>
    </div>
  </div>

  <div class="pb-4"></div>
</div>

<!-- Event Detail Modal -->
<div id="eventModal" class="hidden fixed inset-0 z-50 items-end sm:items-center justify-center sm:p-4 bg-black/60 backdrop-blur-sm">
  <div class="modal-panel bg-white w-full sm:rounded-2xl sm:max-w-2xl overflow-hidden flex flex-col relative" style="box-shadow: 0 32px 80px rgba(0,0,0,0.22); max-height:92dvh; border-radius: 1.25rem 1.25rem 0 0;">
    <div class="sm:hidden flex justify-center pt-3 pb-1 flex-shrink-0"><div class="w-10 h-1 bg-gray-200 rounded-full"></div></div>
    <div class="relative h-44 sm:h-52 overflow-hidden flex-shrink-0">
      <img id="modalImage" src="" alt="" class="w-full h-full object-cover">
      <div class="absolute inset-0" style="background: linear-gradient(to top, rgba(0,0,0,0.75) 0%, rgba(0,0,0,0.1) 60%);"></div>
      <button type="button" id="modalCloseBtn" class="absolute top-4 right-4 w-10 h-10 rounded-full flex items-center justify-center bg-black/40 hover:bg-black/60 transition-colors" aria-label="Close event details"><i data-lucide="x" class="w-4 h-4 text-white"></i></button>
      <div class="absolute bottom-0 left-0 right-0 p-5">
        <div class="flex items-end justify-between gap-3">
          <h2 class="text-lg sm:text-xl font-bold text-white leading-snug" id="modalTitle"></h2>
          <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium flex-shrink-0" id="modalStatusBadge"></span>
        </div>
      </div>
    </div>
    <div class="overflow-y-auto flex-1 p-5 sm:p-6 flex flex-col gap-4">
      <div class="flex items-center gap-2 flex-wrap">
        <span class="text-xs px-3 py-1 rounded-full font-medium" id="modalTypeTag"></span>
        <span class="hidden items-center gap-1 text-[11px] px-2.5 py-1 rounded-full font-semibold" id="modalTravelBadge" style="background:#FEF3C7;color:#92400E;"><i data-lucide="plane" class="w-3 h-3"></i> Requires off-campus travel</span>
        <span class="hidden items-center gap-1 text-[11px] px-2.5 py-1 rounded-full font-semibold" id="modalRestrictedBadge" style="background:#EDE9FE;color:#6D28D9;"><i data-lucide="shield-check" class="w-3 h-3"></i> <span id="modalRestrictedBadgeText"></span></span>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-2" id="modalMetaGrid"></div>
      <div class="rounded-xl p-4 border border-gray-100" style="background:#FAFAFA;">
        <div class="text-[10px] uppercase tracking-wide font-semibold text-gray-600 mb-2">About this Event</div>
        <p class="text-sm text-gray-600 leading-relaxed" id="modalDescription"></p>
      </div>
      <div class="hidden rounded-xl p-4 border" id="modalResultBox" style="background:#FEF9C3; border-color:#FDE68A;">
        <div class="text-[10px] uppercase tracking-wide font-semibold mb-2 flex items-center gap-1.5" style="color:#92400E;"><i data-lucide="trophy" class="w-3.5 h-3.5"></i> Result (Art. XII Sec. 48)</div>
        <p class="text-sm leading-relaxed" style="color:#78350F;" id="modalResultText"></p>
      </div>
      <div class="flex gap-3">
        <button type="button" id="modalCloseBtn2" class="flex-1 py-3 rounded-xl text-sm font-medium border border-gray-200 text-gray-500 hover:bg-gray-50 transition-colors">Close</button>
        <button type="button" id="modalCtaBtn" class="flex-1 py-3 rounded-xl text-sm font-semibold text-white flex items-center justify-center gap-2 hover:opacity-90 transition-opacity" style="background: linear-gradient(135deg, #B11226, #7a0d1a); box-shadow: 0 4px 16px rgba(177,18,38,0.25);">
          <i data-lucide="calendar" class="w-4 h-4"></i> <span id="modalCtaLabel">Add to Calendar</span>
        </button>
      </div>
    </div>
  </div>
</div>

<!-- TRAVEL ACKNOWLEDGMENT (off-campus event RSVP gate) -->
<div id="travelAckBackdrop" class="hidden fixed inset-0" style="background: rgba(0,0,0,0.45); z-index:60;"></div>
<div id="travelAckModal" class="hidden fixed inset-0 items-center justify-center p-4" style="z-index:70;">
  <div class="modal-panel bg-white w-full rounded-2xl overflow-hidden" style="max-width:420px; box-shadow: 0 24px 60px rgba(0,0,0,0.25);">
    <div class="px-5 pt-5 pb-4" style="background: linear-gradient(135deg, #92400E, #B45309);">
      <div class="flex items-start justify-between gap-3">
        <div class="flex items-center gap-2.5">
          <div class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0" style="background: rgba(255,255,255,0.2);"><i data-lucide="plane" class="w-4 h-4 text-white"></i></div>
          <h3 class="text-white font-bold text-sm">Off-Campus Travel Notice</h3>
        </div>
        <button type="button" id="travelAckCloseBtn" class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0 transition-colors" style="background: rgba(255,255,255,0.2);" aria-label="Close travel notice">
          <i data-lucide="x" class="w-4 h-4 text-white"></i>
        </button>
      </div>
    </div>
    <div class="px-5 py-4">
      <p class="text-sm text-gray-700 leading-relaxed mb-3"><span id="travelAckEventTitle" class="font-semibold"></span> is held at <span id="travelAckLocation" class="font-semibold"></span> — a different campus or off-site venue. By registering, you confirm you will coordinate transportation and logistics with the OCA and comply with applicable off-campus activity requirements.</p>
      <label class="flex items-start gap-2.5 mb-4 cursor-pointer">
        <input type="checkbox" id="travelAckCheckbox" class="mt-0.5">
        <span class="text-xs text-gray-600">I understand and accept these terms.</span>
      </label>
      <div class="flex gap-3">
        <button type="button" id="travelAckCancelBtn" class="flex-1 py-2.5 rounded-xl text-sm font-medium border border-gray-200 text-gray-600 hover:bg-gray-50">Cancel</button>
        <button type="button" id="travelAckConfirmBtn" disabled class="flex-1 py-2.5 rounded-xl text-sm font-semibold text-white disabled:opacity-40 transition-opacity hover:opacity-90" style="background:#B11226;">Acknowledge &amp; Register</button>
      </div>
    </div>
  </div>
</div>

<!-- COMPETITION CODE OF CONDUCT (competition-type event RSVP gate, Art. XII Sec. 47/49) -->
<div id="conductAckBackdrop" class="hidden fixed inset-0" style="background: rgba(0,0,0,0.45); z-index:60;"></div>
<div id="conductAckModal" class="hidden fixed inset-0 items-center justify-center p-4" style="z-index:70;">
  <div class="modal-panel bg-white w-full rounded-2xl overflow-hidden" style="max-width:440px; box-shadow: 0 24px 60px rgba(0,0,0,0.25);">
    <div class="px-5 pt-5 pb-4" style="background: linear-gradient(135deg, #B11226, #7a0d1a);">
      <div class="flex items-start justify-between gap-3">
        <div class="flex items-center gap-2.5">
          <div class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0" style="background: rgba(255,255,255,0.2);"><i data-lucide="shield-check" class="w-4 h-4 text-white"></i></div>
          <h3 class="text-white font-bold text-sm">Competition Representation &amp; Conduct</h3>
        </div>
        <button type="button" id="conductAckCloseBtn" class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0 transition-colors" style="background: rgba(255,255,255,0.2);" aria-label="Close notice">
          <i data-lucide="x" class="w-4 h-4 text-white"></i>
        </button>
      </div>
    </div>
    <div class="px-5 py-4">
      <p class="text-sm text-gray-700 leading-relaxed mb-2">By registering for <span id="conductAckEventTitle" class="font-semibold"></span>, you agree to (Art. XII):</p>
      <ul class="text-xs text-gray-600 leading-relaxed mb-3 flex flex-col gap-1.5 list-disc pl-4">
        <li>Conduct yourself with professionalism and integrity, on and off the competition venue (Sec. 47).</li>
        <li>Respect cultural diversity and avoid cultural appropriation, offensive content, or misrepresentation of indigenous traditions (Sec. 47).</li>
        <li>Represent BatStateU ARASOF-Nasugbu using only OCA-approved logos, banners, or identifying symbols (Sec. 47).</li>
        <li>Understand that plagiarism, disrespect towards other participants/organizers, or unethical behavior leads to disqualification and possible disciplinary action (Sec. 49).</li>
      </ul>
      <label class="flex items-start gap-2.5 mb-4 cursor-pointer">
        <input type="checkbox" id="conductAckCheckbox" class="mt-0.5">
        <span class="text-xs text-gray-600">I have read and agree to the Representation Guidelines and Code of Conduct.</span>
      </label>
      <div class="flex gap-3">
        <button type="button" id="conductAckCancelBtn" class="flex-1 py-2.5 rounded-xl text-sm font-medium border border-gray-200 text-gray-600 hover:bg-gray-50">Cancel</button>
        <button type="button" id="conductAckConfirmBtn" disabled class="flex-1 py-2.5 rounded-xl text-sm font-semibold text-white disabled:opacity-40 transition-opacity hover:opacity-90" style="background:#B11226;">Agree &amp; Register</button>
      </div>
    </div>
  </div>
</div>

<!-- MOBILE BOTTOM CTA BAR -->
<div class="sm:hidden fixed bottom-0 left-0 right-0 z-30 bg-white border-t border-gray-100 px-4 py-3" style="box-shadow: 0 -2px 16px rgba(0,0,0,0.08); padding-bottom: calc(env(safe-area-inset-bottom) + 0.75rem);">
  <div class="flex items-center gap-3">
    <a href="<?= e(APP_URL) ?>" class="w-11 h-11 flex items-center justify-center rounded-xl border border-gray-200 flex-shrink-0 hover:bg-gray-50 transition-colors"><i data-lucide="chevron-left" class="w-5 h-5 text-gray-500"></i></a>
    <a href="<?= e($portalUrl) ?>" class="flex-1 h-11 flex items-center justify-center gap-2 rounded-xl text-sm font-semibold text-white" style="background: linear-gradient(135deg, #B11226, #7a0d1a); box-shadow: 0 4px 16px rgba(177,18,38,0.3);"><i data-lucide="users" class="w-4 h-4"></i> <?= $user ? 'Go to Dashboard' : 'Login to Portal' ?></a>
    <a href="<?= e($portalUrl) ?>" class="w-11 h-11 flex items-center justify-center rounded-xl flex-shrink-0" style="background:#D4AF37; box-shadow: 0 4px 12px rgba(212,175,55,0.3);"><i data-lucide="calendar" class="w-4 h-4 text-white"></i></a>
  </div>
</div>

<div id="toast" class="hidden fixed bottom-24 left-1/2 z-[100] px-5 py-3 rounded-xl text-white text-sm font-medium items-center gap-2" style="transform: translateX(-50%); box-shadow: 0 8px 24px rgba(0,0,0,0.18);">
  <i data-lucide="check-circle" id="toastIcon" class="w-4 h-4"></i>
  <span id="toastMsg"></span>
</div>

<script>
var EVENTS = <?= json_encode($eventsData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
var CSRF_TOKEN = <?= json_encode(csrf_token()) ?>;
var APP_URL = <?= json_encode(APP_URL) ?>;
var IS_STUDENT = <?= $user && $user['role'] === 'student' ? 'true' : 'false' ?>;
var IS_LOGGED_IN = <?= $user ? 'true' : 'false' ?>;
var STATUS_COLORS = { Upcoming: { bg: '#DBEAFE', text: '#1D4ED8' }, Planning: { bg: '#FEF9C3', text: '#92400E' }, Ongoing: { bg: '#DCFCE7', text: '#15803D' }, Completed: { bg: '#F3F4F6', text: '#6B7280' }, Cancelled: { bg: '#F3F4F6', text: '#6B7280' } };

lucide.createIcons();

var state = { search: '', type: 'All', month: 'All Months', status: 'All', listView: false };

function hasFilters() { return state.search || state.type !== 'All' || state.month !== 'All Months' || state.status !== 'All'; }
function filteredEvents() {
  var q = state.search.toLowerCase();
  return EVENTS.filter(function (ev) {
    var matchSearch = !q || ev.title.toLowerCase().indexOf(q) !== -1 || ev.location.toLowerCase().indexOf(q) !== -1;
    var matchType = state.type === 'All' || ev.type === state.type;
    var matchMonth = state.month === 'All Months' || ev.month === state.month;
    var matchStatus = state.status === 'All' || ev.status === state.status;
    return matchSearch && matchType && matchMonth && matchStatus;
  });
}

function statusExtraBadge(ev) {
  var registered = ev.myStatus === 'Registered' || ev.myStatus === 'Attended';
  if (registered) return '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold" style="background:#BBF7D0;color:#166534;"><i data-lucide="check" class="w-2.5 h-2.5"></i> Registered</span>';
  if (ev.closingSoon) return '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold" style="background:#FEF3C7;color:#92400E;"><i data-lucide="alarm-clock" class="w-2.5 h-2.5"></i> Closing soon</span>';
  return '';
}
function eventCardHtml(ev, idx) {
  var sc = STATUS_COLORS[ev.status] || STATUS_COLORS.Upcoming;
  var delay = Math.min(idx, 8) * 0.05;
  return '<div class="event-card modern-card bg-white rounded-2xl overflow-hidden border border-gray-100 cursor-pointer group" tabindex="0" role="button" aria-label="View details for ' + ev.title + '" style="box-shadow:0 2px 12px rgba(0,0,0,0.06); animation-delay:' + delay + 's;" data-id="' + ev.id + '">'
    + '<div class="relative h-44 overflow-hidden"><img src="' + ev.image + '" alt="' + ev.title + '" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">'
    + '<div class="absolute inset-0" style="background:linear-gradient(to top, rgba(0,0,0,0.6) 0%, transparent 55%);"></div>'
    + '<div class="absolute top-3 left-3 w-12 h-12 rounded-xl flex flex-col items-center justify-center text-white" style="background:' + ev.color + '; box-shadow:0 4px 12px ' + ev.color + '50;"><span class="text-lg font-bold leading-none">' + ev.day + '</span><span class="text-[10px] opacity-80 uppercase tracking-wide">' + ev.month.slice(0, 3) + '</span></div>'
    + '<div class="absolute top-3 right-3 flex flex-col items-end gap-1"><span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium" style="background:' + sc.bg + ';color:' + sc.text + ';"><span class="w-1.5 h-1.5 rounded-full" style="background:' + sc.text + ';"></span>' + ev.status + '</span>'
    + statusExtraBadge(ev)
    + (ev.requiresTravel ? '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold" style="background:#FEF3C7;color:#92400E;"><i data-lucide="plane" class="w-2.5 h-2.5"></i> Off-campus</span>' : '')
    + (ev.requiresTypeName ? '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold" style="background:#EDE9FE;color:#6D28D9;"><i data-lucide="shield-check" class="w-2.5 h-2.5"></i> Restricted</span>' : '') + '</div>'
    + '<div class="absolute bottom-3 left-3"><span class="text-xs text-white/90 font-medium bg-black/30 px-2 py-0.5 rounded-full backdrop-blur-sm">' + ev.type + '</span></div></div>'
    + '<div class="p-5"><h3 class="font-bold text-sm mb-2.5 leading-snug group-hover:text-red-700 transition-colors" style="color:#1a1a2e;">' + ev.title + '</h3>'
    + '<div class="flex flex-col gap-1.5 mb-4">'
    + '<div class="flex items-center gap-1.5 text-xs text-gray-500"><i data-lucide="clock" class="w-3.5 h-3.5 flex-shrink-0" style="color:' + ev.color + ';"></i>' + ev.date + ' &middot; ' + ev.time + '</div>'
    + '<div class="flex items-center gap-1.5 text-xs text-gray-500"><i data-lucide="map-pin" class="w-3.5 h-3.5 flex-shrink-0" style="color:' + ev.color + ';"></i>' + ev.location + '</div>'
    + '<div class="flex items-center gap-1.5 text-xs text-gray-500"><i data-lucide="users" class="w-3.5 h-3.5 flex-shrink-0" style="color:' + ev.color + ';"></i>' + ev.registered + ' registered / ' + ev.expectedAttendees + ' expected</div>'
    + '</div><button class="w-full flex items-center justify-center gap-1.5 py-2.5 rounded-xl text-xs font-semibold transition-all" style="background:' + ev.color + '15;color:' + ev.color + ';">View Details <i data-lucide="arrow-right" class="view-details-arrow w-3.5 h-3.5"></i></button></div></div>';
}
function eventListItemHtml(ev, idx) {
  var sc = STATUS_COLORS[ev.status] || STATUS_COLORS.Upcoming;
  var delay = Math.min(idx, 8) * 0.05;
  return '<div class="event-card modern-card bg-white rounded-2xl border border-gray-100 overflow-hidden cursor-pointer hover:border-red-100 transition-colors group" tabindex="0" role="button" aria-label="View details for ' + ev.title + '" style="box-shadow:0 2px 12px rgba(0,0,0,0.06); animation-delay:' + delay + 's;" data-id="' + ev.id + '">'
    + '<div class="flex items-stretch"><div class="w-1 flex-shrink-0" style="background:' + ev.color + ';"></div>'
    + '<div class="w-16 sm:w-20 flex flex-col items-center justify-center py-4 flex-shrink-0 border-r border-gray-50" style="background:' + ev.color + '0d;"><span class="text-xl sm:text-2xl font-bold leading-none" style="color:' + ev.color + ';">' + ev.day + '</span><span class="text-[10px] uppercase tracking-wide text-gray-600 mt-1">' + ev.month.slice(0, 3) + '</span></div>'
    + '<div class="flex-1 p-3 sm:p-4 flex flex-col gap-2 min-w-0"><div class="flex items-start justify-between gap-2"><div class="min-w-0 flex-1"><h3 class="font-bold text-sm group-hover:text-red-700 transition-colors truncate" style="color:#1a1a2e;">' + ev.title + '</h3>'
    + '<div class="flex items-center gap-1.5 mt-1 flex-wrap"><span class="text-xs px-2 py-0.5 rounded-full font-medium" style="background:' + sc.bg + ';color:' + sc.text + ';">' + ev.status + '</span><span class="text-xs px-2 py-0.5 rounded-full hidden sm:inline-flex font-medium" style="background:' + ev.color + '15;color:' + ev.color + ';">' + ev.type + '</span>'
    + statusExtraBadge(ev)
    + (ev.requiresTravel ? '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold" style="background:#FEF3C7;color:#92400E;"><i data-lucide="plane" class="w-2.5 h-2.5"></i> Off-campus</span>' : '')
    + (ev.requiresTypeName ? '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold" style="background:#EDE9FE;color:#6D28D9;"><i data-lucide="shield-check" class="w-2.5 h-2.5"></i> Restricted</span>' : '') + '</div></div>'
    + '<button class="flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-semibold flex-shrink-0 hover:opacity-90 transition-opacity" style="background:' + ev.color + ';color:#fff;"><span class="hidden sm:inline">Details</span><i data-lucide="arrow-right" class="view-details-arrow w-3.5 h-3.5"></i></button></div>'
    + '<div class="flex flex-col sm:flex-row sm:flex-wrap gap-y-1 sm:gap-x-4"><span class="flex items-center gap-1 text-xs text-gray-500"><i data-lucide="clock" class="w-3 h-3 flex-shrink-0"></i>' + ev.time + '</span><span class="flex items-center gap-1 text-xs text-gray-500 truncate"><i data-lucide="map-pin" class="w-3 h-3 flex-shrink-0"></i><span class="truncate">' + ev.location + '</span></span><span class="flex items-center gap-1 text-xs text-gray-500"><i data-lucide="users" class="w-3 h-3 flex-shrink-0"></i>' + ev.registered + '/' + ev.expectedAttendees + ' pax</span></div></div></div></div>';
}

function render() {
  var list = filteredEvents();
  document.getElementById('resultCount').textContent = list.length;
  var showClear = hasFilters();
  document.getElementById('clearFiltersBtn').classList.toggle('hidden', !showClear);
  document.getElementById('clearFiltersBtn').classList.toggle('flex', showClear);
  document.getElementById('viewToggleWrap').classList.toggle('hidden', showClear);
  document.getElementById('clearFiltersLink').classList.toggle('hidden', !showClear);

  var gridView = document.getElementById('gridView');
  var listView = document.getElementById('listView');
  var emptyState = document.getElementById('emptyState');

  if (!list.length) {
    emptyState.classList.remove('hidden'); gridView.classList.add('hidden'); listView.classList.add('hidden');
  } else {
    emptyState.classList.add('hidden');
    if (state.listView) {
      gridView.classList.add('hidden'); listView.classList.remove('hidden'); listView.classList.add('flex');
      listView.innerHTML = list.map(eventListItemHtml).join('');
    } else {
      listView.classList.add('hidden'); listView.classList.remove('flex'); gridView.classList.remove('hidden');
      gridView.innerHTML = list.map(eventCardHtml).join('');
    }
  }
  lucide.createIcons();
  document.querySelectorAll('.event-card').forEach(function (card) {
    card.addEventListener('click', function () { openEventModal(parseInt(card.dataset.id, 10)); });
    card.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openEventModal(parseInt(card.dataset.id, 10)); }
    });
  });
}

// Search
document.getElementById('searchInput').addEventListener('input', function () { state.search = this.value; render(); });

// Dropdown filters
function setupDropdown(btnId, dropId, group) {
  var btn = document.getElementById(btnId), drop = document.getElementById(dropId);
  btn.addEventListener('click', function (e) {
    e.stopPropagation();
    document.querySelectorAll('[id$="Dropdown"]').forEach(function (d) { if (d !== drop) d.classList.add('hidden'); });
    drop.classList.toggle('hidden');
  });
}
setupDropdown('typeDropdownBtn', 'typeDropdown', 'type');
setupDropdown('monthDropdownBtn', 'monthDropdown', 'month');
setupDropdown('statusDropdownBtn', 'statusDropdown', 'status');
document.addEventListener('click', function () { document.querySelectorAll('[id$="Dropdown"]').forEach(function (d) { d.classList.add('hidden'); }); });

document.querySelectorAll('.filter-opt').forEach(function (btn) {
  btn.addEventListener('click', function (e) {
    e.stopPropagation();
    state[btn.dataset.group] = btn.dataset.value;
    var btnLabelEl = document.getElementById(btn.dataset.group + 'DropdownBtn').querySelector('span');
    var isActive = btn.dataset.value !== 'All' && btn.dataset.value !== 'All Months';
    btnLabelEl.textContent = isActive ? btn.dataset.value : (btn.dataset.group === 'month' ? 'Month' : btn.dataset.group.charAt(0).toUpperCase() + btn.dataset.group.slice(1));
    var dropdownBtn = document.getElementById(btn.dataset.group + 'DropdownBtn');
    dropdownBtn.style.background = isActive ? '#B11226' : '#F9FAFB';
    dropdownBtn.style.borderColor = isActive ? '#B11226' : '#E5E7EB';
    dropdownBtn.style.color = isActive ? '#fff' : '#374151';
    var dropdownIcon = dropdownBtn.querySelector('svg, i');
    if (dropdownIcon) dropdownIcon.style.color = isActive ? '#fff' : '#9CA3AF';
    document.querySelectorAll('#' + btn.dataset.group + 'Dropdown .filter-opt').forEach(function (opt) {
      opt.classList.toggle('is-active', opt === btn);
    });
    document.getElementById(btn.dataset.group + 'Dropdown').classList.add('hidden');
    render();
  });
});

function clearFilters() {
  state = { search: '', type: 'All', month: 'All Months', status: 'All', listView: state.listView };
  document.getElementById('searchInput').value = '';
  ['type', 'month', 'status'].forEach(function (g) {
    var btn = document.getElementById(g + 'DropdownBtn');
    btn.querySelector('span').textContent = g === 'month' ? 'Month' : g.charAt(0).toUpperCase() + g.slice(1);
    btn.style.background = '#F9FAFB'; btn.style.borderColor = '#E5E7EB'; btn.style.color = '#374151';
    var clearIcon = btn.querySelector('svg, i');
    if (clearIcon) clearIcon.style.color = '#9CA3AF';
    document.querySelectorAll('#' + g + 'Dropdown .filter-opt').forEach(function (opt) {
      opt.classList.toggle('is-active', opt.dataset.value === 'All' || opt.dataset.value === 'All Months');
    });
  });
  render();
}
document.getElementById('clearFiltersBtn').addEventListener('click', clearFilters);
document.getElementById('clearFiltersLink').addEventListener('click', clearFilters);
document.getElementById('clearFiltersEmptyBtn').addEventListener('click', clearFilters);

// View toggle
document.getElementById('gridViewBtn').addEventListener('click', function () {
  state.listView = false;
  this.style.background = '#B11226'; this.style.color = '#fff';
  var lb = document.getElementById('listViewBtn'); lb.style.background = ''; lb.style.color = '#6B7280';
  render();
});
document.getElementById('listViewBtn').addEventListener('click', function () {
  state.listView = true;
  this.style.background = '#B11226'; this.style.color = '#fff';
  var gb = document.getElementById('gridViewBtn'); gb.style.background = ''; gb.style.color = '#6B7280';
  render();
});

// ---------- Event detail modal ----------
var eventModal = document.getElementById('eventModal');
var currentEventId = null;
function openEventModal(id) {
  var ev = EVENTS.find(function (e) { return e.id === id; });
  if (!ev) return;
  currentEventId = id;
  var sc = STATUS_COLORS[ev.status] || STATUS_COLORS.Upcoming;
  document.getElementById('modalImage').src = ev.image;
  document.getElementById('modalImage').alt = ev.title;
  document.getElementById('modalTitle').textContent = ev.title;
  document.getElementById('modalStatusBadge').innerHTML = '<span class="w-1.5 h-1.5 rounded-full inline-block mr-1.5" style="background:' + sc.text + ';"></span>' + ev.status;
  document.getElementById('modalStatusBadge').style.background = sc.bg;
  document.getElementById('modalStatusBadge').style.color = sc.text;
  document.getElementById('modalTypeTag').textContent = ev.type;
  document.getElementById('modalTypeTag').style.background = ev.color + '18';
  document.getElementById('modalTypeTag').style.color = ev.color;
  document.getElementById('modalTravelBadge').classList.toggle('hidden', !ev.requiresTravel);
  document.getElementById('modalTravelBadge').classList.toggle('flex', !!ev.requiresTravel);
  document.getElementById('modalRestrictedBadge').classList.toggle('hidden', !ev.requiresTypeName);
  document.getElementById('modalRestrictedBadge').classList.toggle('flex', !!ev.requiresTypeName);
  document.getElementById('modalRestrictedBadgeText').textContent = ev.requiresTypeName ? ('Approved ' + ev.requiresTypeName + ' only') : '';
  document.getElementById('modalDescription').textContent = ev.description;
  var hasResult = ev.status === 'Completed' && !!ev.competitionResult;
  document.getElementById('modalResultBox').classList.toggle('hidden', !hasResult);
  if (hasResult) document.getElementById('modalResultText').textContent = ev.competitionResult;
  lucide.createIcons();

  var metaFields = [
    ['calendar', 'Date', ev.date],
    ['clock', 'Time', ev.time],
    ['map-pin', 'Venue', ev.location],
    ['users', 'Expected', ev.registered + ' registered / ' + ev.expectedAttendees + ' expected'],
    ['tag', 'Audience', ev.audience],
  ];
  document.getElementById('modalMetaGrid').innerHTML = metaFields.map(function (f) {
    return '<div class="flex items-center gap-3 bg-gray-50 rounded-xl p-3"><div class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0" style="background:' + ev.color + '15;"><i data-lucide="' + f[0] + '" class="w-4 h-4" style="color:' + ev.color + ';"></i></div><div class="min-w-0"><div class="text-[10px] uppercase tracking-wide text-gray-600 font-medium">' + f[1] + '</div><div class="text-xs font-semibold truncate" style="color:#1a1a2e;">' + f[2] + '</div></div></div>';
  }).join('');

  updateModalCta(ev);
  eventModal.classList.remove('hidden'); eventModal.classList.add('flex');
  requestAnimationFrame(function () { eventModal.classList.add('is-open'); });
  lucide.createIcons();
}
function closeEventModal() {
  eventModal.classList.remove('is-open');
  setTimeout(function () { eventModal.classList.add('hidden'); eventModal.classList.remove('flex'); }, 300);
}
function updateModalCta(ev) {
  var btn = document.getElementById('modalCtaBtn');
  var label = document.getElementById('modalCtaLabel');
  var closed = ev.status === 'Completed' || ev.status === 'Cancelled';
  btn.disabled = false;
  btn.style.opacity = '1';
  btn.onclick = null;
  if (!IS_LOGGED_IN) {
    label.textContent = 'Login to RSVP';
    btn.onclick = function () { window.location.href = APP_URL + '/login'; };
  } else if (!IS_STUDENT) {
    label.textContent = 'Add to Calendar';
  } else if (closed) {
    label.textContent = 'RSVP Closed';
    btn.disabled = true; btn.style.opacity = '0.5';
  } else if (ev.myStatus === 'Registered' || ev.myStatus === 'Attended') {
    label.textContent = "You're Registered — Cancel";
    btn.onclick = function () { doRsvp(ev.id, 'cancel', false); };
  } else if (!ev.eligible) {
    label.textContent = 'Approved ' + ev.requiresTypeName + ' applicants only';
    btn.disabled = true; btn.style.opacity = '0.5';
  } else if (ev.isCompetition) {
    // Competition conduct acknowledgment gates first; if the event is also
    // off-campus, the travel acknowledgment follows once conduct is agreed to.
    label.textContent = 'RSVP to this Event';
    btn.onclick = function () { openConductAck(ev); };
  } else if (ev.requiresTravel) {
    label.textContent = 'RSVP to this Event';
    btn.onclick = function () { openTravelAck(ev); };
  } else {
    label.textContent = 'RSVP to this Event';
    btn.onclick = function () { doRsvp(ev.id, 'register', false, false); };
  }
}
var toastTimer = null;
var toastHideTimer = null;
function showToast(message, type) {
  var el = document.getElementById('toast');
  var colors = { success: '#22C55E', error: '#EF4444', info: '#3B82F6' };
  var icons = { success: 'check-circle', error: 'x-circle', info: 'alert-circle' };
  el.style.background = colors[type || 'success'];
  document.getElementById('toastIcon').setAttribute('data-lucide', icons[type || 'success']);
  document.getElementById('toastMsg').textContent = message;
  if (toastTimer) clearTimeout(toastTimer);
  if (toastHideTimer) clearTimeout(toastHideTimer);
  el.classList.remove('hidden'); el.classList.add('flex');
  lucide.createIcons();
  requestAnimationFrame(function () { el.classList.add('is-open'); });
  toastTimer = setTimeout(function () {
    el.classList.remove('is-open');
    toastHideTimer = setTimeout(function () { el.classList.add('hidden'); el.classList.remove('flex'); }, 300);
  }, 3000);
}
function doRsvp(eventId, action, travelAcknowledged, conductAcknowledged) {
  var btn = document.getElementById('modalCtaBtn');
  var label = document.getElementById('modalCtaLabel');
  var originalLabel = label.textContent;
  btn.disabled = true;
  label.textContent = action === 'register' ? 'Registering…' : 'Cancelling…';
  fetch(APP_URL + '/events/rsvp', {
    method: 'POST', headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ csrf_token: CSRF_TOKEN, event_id: eventId, action: action, travel_acknowledged: !!travelAcknowledged, conduct_acknowledged: !!conductAcknowledged }),
  }).then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); })
    .then(function (res) {
      if (!res.ok) {
        showToast(res.data.error || 'Something went wrong.', 'error');
        btn.disabled = false; label.textContent = originalLabel;
        return;
      }
      var ev = EVENTS.find(function (e) { return e.id === eventId; });
      if (action === 'register') { ev.myStatus = 'Registered'; ev.registered += 1; showToast("You're registered for this event.", 'success'); }
      else { if (ev.myStatus === 'Registered' || ev.myStatus === 'Attended') ev.registered = Math.max(0, ev.registered - 1); ev.myStatus = null; showToast('Your RSVP has been cancelled.', 'info'); }
      updateModalCta(ev);
      render();
    })
    .catch(function () {
      showToast('Network error. Please try again.', 'error');
      btn.disabled = false; label.textContent = originalLabel;
    });
}

// Off-campus/inter-campus events route through this acknowledgment step
// instead of registering immediately (server-side gate lives in rsvp_action.php).
var travelAckModal = document.getElementById('travelAckModal');
var travelAckBackdrop = document.getElementById('travelAckBackdrop');
var travelAckCheckbox = document.getElementById('travelAckCheckbox');
var travelAckConfirmBtn = document.getElementById('travelAckConfirmBtn');
var pendingTravelEvent = null;
function openTravelAck(ev) {
  pendingTravelEvent = ev;
  document.getElementById('travelAckEventTitle').textContent = ev.title;
  document.getElementById('travelAckLocation').textContent = ev.location || 'an off-site venue';
  travelAckCheckbox.checked = false;
  travelAckConfirmBtn.disabled = true;
  travelAckModal.classList.remove('hidden'); travelAckModal.classList.add('flex');
  travelAckBackdrop.classList.remove('hidden');
  requestAnimationFrame(function () { travelAckModal.classList.add('is-open'); travelAckBackdrop.classList.add('is-open'); });
}
function closeTravelAck() {
  pendingTravelEvent = null;
  travelAckModal.classList.remove('is-open'); travelAckBackdrop.classList.remove('is-open');
  setTimeout(function () {
    travelAckModal.classList.add('hidden'); travelAckModal.classList.remove('flex');
    travelAckBackdrop.classList.add('hidden');
  }, 250);
}
travelAckCheckbox.addEventListener('change', function () { travelAckConfirmBtn.disabled = !travelAckCheckbox.checked; });
document.getElementById('travelAckCancelBtn').addEventListener('click', closeTravelAck);
document.getElementById('travelAckCloseBtn').addEventListener('click', closeTravelAck);
travelAckBackdrop.addEventListener('click', closeTravelAck);
travelAckConfirmBtn.addEventListener('click', function () {
  var ev = pendingTravelEvent;
  closeTravelAck();
  if (ev) doRsvp(ev.id, 'register', true, !!ev.isCompetition);
});

// Competition events route through this acknowledgment first (Art. XII Sec.
// 47/49); off-campus competitions then chain into the travel acknowledgment.
var conductAckModal = document.getElementById('conductAckModal');
var conductAckBackdrop = document.getElementById('conductAckBackdrop');
var conductAckCheckbox = document.getElementById('conductAckCheckbox');
var conductAckConfirmBtn = document.getElementById('conductAckConfirmBtn');
var pendingConductEvent = null;
function openConductAck(ev) {
  pendingConductEvent = ev;
  document.getElementById('conductAckEventTitle').textContent = ev.title;
  conductAckCheckbox.checked = false;
  conductAckConfirmBtn.disabled = true;
  conductAckModal.classList.remove('hidden'); conductAckModal.classList.add('flex');
  conductAckBackdrop.classList.remove('hidden');
  requestAnimationFrame(function () { conductAckModal.classList.add('is-open'); conductAckBackdrop.classList.add('is-open'); });
}
function closeConductAck() {
  pendingConductEvent = null;
  conductAckModal.classList.remove('is-open'); conductAckBackdrop.classList.remove('is-open');
  setTimeout(function () {
    conductAckModal.classList.add('hidden'); conductAckModal.classList.remove('flex');
    conductAckBackdrop.classList.add('hidden');
  }, 250);
}
conductAckCheckbox.addEventListener('change', function () { conductAckConfirmBtn.disabled = !conductAckCheckbox.checked; });
document.getElementById('conductAckCancelBtn').addEventListener('click', closeConductAck);
document.getElementById('conductAckCloseBtn').addEventListener('click', closeConductAck);
conductAckBackdrop.addEventListener('click', closeConductAck);
conductAckConfirmBtn.addEventListener('click', function () {
  var ev = pendingConductEvent;
  closeConductAck();
  if (!ev) return;
  if (ev.requiresTravel) { openTravelAck(ev); } else { doRsvp(ev.id, 'register', false, true); }
});

document.getElementById('modalCloseBtn').addEventListener('click', closeEventModal);
document.getElementById('modalCloseBtn2').addEventListener('click', closeEventModal);
eventModal.addEventListener('click', function (e) { if (e.target === eventModal) closeEventModal(); });

document.querySelectorAll('.ev-input').forEach(function (el) {
  el.addEventListener('focus', function () { el.style.borderColor = '#B11226'; });
  el.addEventListener('blur', function () { el.style.borderColor = '#e5e7eb'; });
});

// Sticky filter bar gains depth once it docks below the nav
var stickySentinel = document.getElementById('stickySentinel');
var filterBar = document.getElementById('filterBar');
if (stickySentinel && filterBar && 'IntersectionObserver' in window) {
  new IntersectionObserver(function (entries) {
    filterBar.classList.toggle('is-stuck', !entries[0].isIntersecting);
  }, { rootMargin: '-69px 0px 0px 0px', threshold: 0 }).observe(stickySentinel);
}

// Scroll progress bar — fills with scroll depth, same behavior as the landing page
var scrollProgressBar = document.getElementById('scrollProgressBar');
var progressTicking = false;
function updateScrollProgress() {
  var scrollTop = window.scrollY || document.documentElement.scrollTop;
  var docHeight = document.documentElement.scrollHeight - window.innerHeight;
  var pct = docHeight > 0 ? Math.min(100, Math.max(0, (scrollTop / docHeight) * 100)) : 0;
  scrollProgressBar.style.width = pct + '%';
  progressTicking = false;
}
window.addEventListener('scroll', function () {
  if (!progressTicking) {
    requestAnimationFrame(updateScrollProgress);
    progressTicking = true;
  }
}, { passive: true });
updateScrollProgress();

render();
</script>

@include('partials.ask_spartan_widget')

</body>
</html>
