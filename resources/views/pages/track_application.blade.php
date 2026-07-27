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
  .spin { animation: track-spin 1s linear infinite; }
  @keyframes track-spin { to { transform: rotate(360deg); } }
  @keyframes tk-fade-up { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: translateY(0); } }
  .tk-card { animation: tk-fade-up 0.5s var(--ease-out-soft, ease-out) both; }
  #resultArea > * { animation: tk-fade-up 0.45s var(--ease-out-soft, ease-out) both; }
  @media (prefers-reduced-motion: reduce) { .tk-card, #resultArea > * { animation: none !important; } }

  /* Scroll progress bar — sits above the sticky nav, fills with scroll depth */
  #scrollProgressBar { position: fixed; top: 0; left: 0; height: 6px; width: 0%; z-index: 40; background: #B11226; transition: width 0.1s linear; pointer-events: none; }
  @media (prefers-reduced-motion: reduce) { #scrollProgressBar { transition: none; } }

  /* Hide the native page scrollbar — the progress bar above is the scroll indicator */
  html, body { scrollbar-width: none; -ms-overflow-style: none; }
  html::-webkit-scrollbar, body::-webkit-scrollbar { display: none; width: 0; height: 0; }
</style>
</head>
<body class="min-h-screen bg-gray-50 flex flex-col">
<div id="scrollProgressBar"></div>

<nav class="sticky top-0 z-30 bg-white border-b border-gray-100" style="box-shadow: 0 1px 8px rgba(0,0,0,0.06);">
  <div class="max-w-2xl mx-auto px-4 sm:px-6 flex items-center justify-between h-16">
    <div class="flex items-center gap-3">
      <a href="<?= e(APP_URL) ?>" class="flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-800 transition-colors mr-1">
        <i data-lucide="chevron-left" class="w-4 h-4"></i> Back
      </a>
      <div class="hidden sm:block h-5 w-px bg-gray-200"></div>
      <div class="hidden sm:flex items-center gap-2.5">
        <div class="w-8 h-8 rounded-full bg-white flex items-center justify-center overflow-hidden flex-shrink-0" style="border:1px solid #eee;"><img src="<?= APP_URL ?>/assets/images/bsulogo.jpg" alt="BatStateU OCA Logo" class="w-full h-full object-cover"></div>
        <div><div class="font-bold text-sm tracking-wider" style="color:#B11226;">ARTEMIS</div><div class="text-xs text-gray-600">Application Tracker</div></div>
      </div>
    </div>
    <a href="<?= e($portalUrl) ?>" class="px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold text-white hover:opacity-90 transition-opacity flex-shrink-0" style="background: linear-gradient(135deg, #B11226, #7a0d1a);"><?= $user ? 'Go to Dashboard' : 'Login to Portal' ?></a>
  </div>
</nav>

<main class="flex-1 max-w-2xl mx-auto w-full px-4 py-10">

  <div class="text-center mb-6">
    <div class="w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-4" style="background:#FEE2E2;"><i data-lucide="search" class="w-6 h-6" style="color:#B11226;"></i></div>
    <h1 class="text-2xl font-bold" style="color:#1a1a2e;">Track Your Application</h1>
  </div>

  <div class="tk-card bg-white rounded-2xl border border-gray-100 p-6 sm:p-7 relative z-10" style="box-shadow: 0 12px 32px rgba(0,0,0,0.1);">
    <form id="trackForm" class="flex flex-col gap-4">
      <div class="relative">
        <label for="appCodeInput" class="sr-only">Application ID or Admission Appeal reference</label>
        <i data-lucide="file-text" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
        <input id="appCodeInput" placeholder="e.g. APP-2026-001 or APPEAL-00001" class="track-input modern-input font-mono w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 bg-white text-sm focus:outline-none transition-colors">
      </div>
      <div class="relative">
        <label for="studentIdInput" class="sr-only">Student ID number, or email for admission appeals</label>
        <i data-lucide="user" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
        <input id="studentIdInput" placeholder="Student ID Number, or email if tracking an appeal" class="track-input modern-input font-mono w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 bg-white text-sm focus:outline-none transition-colors">
      </div>
      <button type="submit" id="trackSubmitBtn" disabled
        class="modern-btn w-full py-3 rounded-xl text-sm font-semibold text-white disabled:opacity-50 transition-opacity flex items-center justify-center"
        style="background: linear-gradient(135deg, #B11226, #7a0d1a);">
        <span id="trackSubmitLabel">Track</span>
      </button>
    </form>

    <p class="text-center text-xs text-gray-400 mt-4">Your Application ID and Student ID Number (or, for admission appeals, your Appeal Reference and email) were provided when you submitted to OCA.</p>
  </div>

  <div id="resultArea" class="mt-6 hidden flex-col gap-4"></div>

  <div id="hintArea">
    <div class="mt-6 text-center">
      <p class="text-xs text-gray-400 mb-2">Need your seeded demo IDs? Try:</p>
      <div class="flex flex-wrap gap-2 justify-center">
        <button type="button" class="track-sample modern-btn px-3 py-1.5 rounded-lg border border-gray-200 bg-white text-xs hover:border-gray-400 transition-colors font-mono" style="color:#4B5563;" data-code="APP-2026-001" data-id="21-10234">APP-2026-001 &middot; 21-10234</button>
        <button type="button" class="track-sample modern-btn px-3 py-1.5 rounded-lg border border-gray-200 bg-white text-xs hover:border-gray-400 transition-colors font-mono" style="color:#4B5563;" data-code="APP-2026-002" data-id="21-10891">APP-2026-002 &middot; 21-10891</button>
      </div>
    </div>

    <div class="mt-10 grid grid-cols-1 sm:grid-cols-2 gap-3">
      <div class="modern-card bg-white rounded-2xl border border-gray-100 p-4 flex items-start gap-3" style="box-shadow: 0 1px 8px rgba(0,0,0,0.05);">
        <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0" style="background:#FEE2E2;"><i data-lucide="file-text" class="w-4 h-4" style="color:#B11226;"></i></div>
        <div><div class="text-xs font-semibold mb-0.5" style="color:#1a1a2e;">Application ID or Appeal Reference</div><div class="text-[11px] text-gray-500 leading-relaxed">The code sent to you when you first submitted, e.g. <span class="font-mono">APP-2026-001</span> or <span class="font-mono">APPEAL-00001</span>.</div></div>
      </div>
      <div class="modern-card bg-white rounded-2xl border border-gray-100 p-4 flex items-start gap-3" style="box-shadow: 0 1px 8px rgba(0,0,0,0.05);">
        <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0" style="background:#FEF9E7;"><i data-lucide="user" class="w-4 h-4" style="color:#D4AF37;"></i></div>
        <div><div class="text-xs font-semibold mb-0.5" style="color:#1a1a2e;">Student ID Number</div><div class="text-[11px] text-gray-500 leading-relaxed">Your official BatStateU ID number, e.g. <span class="font-mono">21-10234</span>. Tracking an admission appeal instead? Enter the email you applied with.</div></div>
      </div>
    </div>
  </div>
</main>

@include('partials.ask_spartan_widget')

<script>
var APP_URL = <?= json_encode(APP_URL) ?>;
var STAGES = <?= json_encode(ARTEMIS_PROGRESS_STAGES) ?>;
var STAGES_BY_TYPE = <?= json_encode(application_progress_stages_by_type()) ?>;
function stagesFor(typeCode) { return STAGES_BY_TYPE[typeCode] || STAGES; }
var STATUS_META = {
  Pending:        { color: '#D97706', bg: '#FEF9C3', icon: 'clock',        label: 'Pending' },
  'Under Review': { color: '#2563EB', bg: '#DBEAFE', icon: 'file-text',    label: 'Under Review' },
  Evaluation:     { color: '#7C3AED', bg: '#EDE9FE', icon: 'search',       label: 'Evaluation' },
  Approved:       { color: '#15803D', bg: '#DCFCE7', icon: 'check-circle', label: 'Approved' },
  Rejected:       { color: '#B91C1C', bg: '#FEE2E2', icon: 'x-circle',     label: 'Rejected' },
};
var STATUS_MESSAGES = {
  Pending: "Your application has been received and is waiting for OCA to begin the review process. You'll be notified when it moves forward.",
  'Under Review': 'The OCA Office is currently reviewing your documents. This typically takes 3–5 business days.',
  Evaluation: 'Your application is being evaluated by the committee. Results will be forwarded to the VPAA/Chancellor soon.',
  Approved: 'Congratulations! Your application has been approved by the Chancellor. Visit the OCA Office for the next steps.',
  Rejected: 'Your application was not approved. Please review the remarks above and contact the OCA Office if you have questions.',
};

// Admission Appeal (Art. IV Sec. 11) status vocabulary — separate from the
// applications table's Pending/Under Review/Evaluation/Approved/Rejected above,
// since appeals track through their own five stages instead: Submitted ->
// Under Review (OCA) -> Evaluation Stage -> For Approval (President via TAO)
// -> Approved/Rejected. The lookup endpoint passes the stored status straight
// through, so these keys must match ARTEMIS_APPEAL_CHAIN exactly.
var APPEAL_STATUS_META = {
  Submitted:                           { color: '#6B7280', bg: '#F3F4F6', icon: 'clock',        label: 'Submitted' },
  'Under Review (OCA)':                { color: '#92400E', bg: '#FEF9C3', icon: 'file-text',    label: 'Under Review (OCA)' },
  'Evaluation Stage':                  { color: '#7C3AED', bg: '#F3E8FF', icon: 'clipboard-check', label: 'Evaluation Stage' },
  'For Approval (President via TAO)':  { color: '#1D4ED8', bg: '#DBEAFE', icon: 'search',       label: 'For Approval (President via TAO)' },
  Approved:                            { color: '#15803D', bg: '#DCFCE7', icon: 'check-circle', label: 'Approved' },
  Rejected:                            { color: '#B91C1C', bg: '#FEE2E2', icon: 'x-circle',     label: 'Rejected' },
};
var APPEAL_STATUS_MESSAGES = {
  Submitted: 'Your appeal has been received. OCA will begin screening it against Art. IV Sec. 11 shortly.',
  'Under Review (OCA)': 'OCA is reviewing your submitted achievements and documents.',
  'Evaluation Stage': 'Your appeal has passed OCA screening and is being evaluated by the Testing and Admission Office — Central Administration.',
  'For Approval (President via TAO)': 'Your appeal has been endorsed through TAO Central for final approval by the University President.',
  Approved: 'Congratulations! Your admission appeal has been approved. The OCA Office will reach out with next steps.',
  Rejected: 'Your admission appeal was not approved. Please review the remarks below and contact the OCA Office if you have questions.',
};

function renderAppealResult(data) {
  var meta = APPEAL_STATUS_META[data.status] || APPEAL_STATUS_META.Submitted;
  var html = '<div class="modern-card bg-white rounded-2xl border border-gray-100 overflow-hidden" style="box-shadow:0 1px 8px rgba(0,0,0,0.06);">'
    + '<div class="h-1.5 w-full" style="background:' + meta.color + ';"></div>'
    + '<div class="p-5"><div class="flex items-start justify-between mb-2">'
    + '<div><div class="text-xs text-gray-400 mb-0.5 font-mono">' + data.reference + '</div><h2 class="font-bold text-base" style="color:#1a1a2e;">' + data.full_name + '</h2><div class="text-xs text-gray-500 mt-0.5">Admission Appeal &middot; ' + data.discipline + '</div></div>'
    + '<div class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold flex-shrink-0" style="background:' + meta.bg + ';color:' + meta.color + ';"><i data-lucide="' + meta.icon + '" class="w-3.5 h-3.5"></i>' + meta.label + '</div></div></div></div>';

  var fields = [
    ['user', 'Applicant', data.full_name],
    ['book-open', 'Secondary School', data.secondary_school],
    ['map-pin', 'Campus', data.campus],
    ['calendar', 'Date Submitted', data.submitted_at],
  ];
  html += '<div class="modern-card bg-white rounded-2xl border border-gray-100 p-5" style="box-shadow:0 1px 8px rgba(0,0,0,0.06);">'
    + '<h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-4">Appeal Details</h3><div class="grid grid-cols-2 gap-3">'
    + fields.map(function (f) {
      return '<div class="bg-gray-50 rounded-xl p-3"><div class="flex items-center gap-1.5 mb-1"><i data-lucide="' + f[0] + '" class="w-3 h-3 text-gray-400"></i><span class="text-[10px] text-gray-400 uppercase tracking-wide">' + f[1] + '</span></div><div class="text-xs font-semibold truncate" style="color:#1a1a2e;">' + (f[2] || '') + '</div></div>';
    }).join('') + '</div></div>';

  if (data.remarks) {
    html += '<div class="modern-card bg-yellow-50 rounded-2xl p-4 border border-yellow-100"><div class="flex items-center gap-2 mb-2"><i data-lucide="message-square" class="w-4 h-4" style="color:#92400E;"></i><span class="text-xs font-semibold" style="color:#92400E;">OCA Remarks</span></div><p class="text-xs text-yellow-800 leading-relaxed">' + data.remarks + '</p></div>';
  }

  html += '<div class="rounded-2xl p-4 border" style="background:' + meta.bg + '80;border-color:' + meta.color + '30;"><p class="text-xs leading-relaxed" style="color:' + meta.color + ';">' + (APPEAL_STATUS_MESSAGES[data.status] || '') + '</p></div>';

  html += '<div class="flex flex-col sm:flex-row gap-2 pb-4">'
    + '<a href="' + APP_URL + '/login" class="modern-btn flex-1 py-3 rounded-xl text-sm font-semibold text-white text-center hover:opacity-90 transition-opacity" style="background: linear-gradient(135deg, #B11226, #7a0d1a);">Sign In for Full Access</a>'
    + '<button type="button" id="trackAnotherBtn" class="modern-btn flex-1 py-3 rounded-xl text-sm font-medium border border-gray-200 text-gray-600 bg-white hover:bg-gray-50 transition-colors">Track Another</button></div>';

  return html;
}

lucide.createIcons();

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

var appCodeInput = document.getElementById('appCodeInput');
var studentIdInput = document.getElementById('studentIdInput');
var submitBtn = document.getElementById('trackSubmitBtn');
function syncSubmitState() { submitBtn.disabled = !appCodeInput.value.trim() || !studentIdInput.value.trim(); }
appCodeInput.addEventListener('input', syncSubmitState);
studentIdInput.addEventListener('input', syncSubmitState);
document.querySelectorAll('.track-input').forEach(function (el) {
  el.addEventListener('focus', function () { el.style.borderColor = '#B11226'; });
  el.addEventListener('blur', function () { el.style.borderColor = '#e5e7eb'; });
});

document.querySelectorAll('.track-sample').forEach(function (btn) {
  btn.addEventListener('click', function () {
    appCodeInput.value = btn.dataset.code;
    studentIdInput.value = btn.dataset.id;
    syncSubmitState();
  });
});

// JS mirror of includes/ui_helpers.php's admin_progress_tracker_html() — kept
// in sync manually since this page renders the tracker client-side from the
// JSON response instead of server-side PHP.
function progressTrackerHtml(stage, status, typeCode) {
  var STAGE_LABELS = stagesFor(typeCode);
  var total = STAGE_LABELS.length;
  var isRejected = status === 'Rejected';
  var fillPct = (Math.min(stage - 1, total - 1) / (total - 1)) * (100 - 100 / total);
  var html = '<div class="w-full"><div class="relative flex items-center w-full" style="height:36px;">';
  html += '<div class="absolute h-0.5 bg-gray-200" style="left:' + (100 / (total * 2)) + '%;right:' + (100 / (total * 2)) + '%;"></div>';
  html += '<div class="absolute h-0.5 transition-all duration-700" style="background:' + (isRejected ? '#EF4444' : 'linear-gradient(90deg,#22C55E,#16a34a)') + ';left:' + (100 / (total * 2)) + '%;width:' + fillPct + '%;"></div>';
  STAGE_LABELS.forEach(function (label, i) {
    var n = i + 1, done = n < stage, active = n === stage;
    var activeColor = (isRejected && active) ? '#EF4444' : '#B11226';
    var bg = done ? '#22C55E' : (active ? activeColor : '#fff');
    var color = (done || active) ? '#fff' : '#4B5563';
    var border = (done || active) ? 'none' : '2px solid #E5E7EB';
    var shadow = active ? ('0 0 0 4px ' + activeColor + '22') : 'none';
    html += '<div class="flex-1 flex justify-center relative z-10"><div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-all duration-500" style="background:' + bg + ';color:' + color + ';border:' + border + ';box-shadow:' + shadow + ';">' + (done ? '<i data-lucide="check-circle" class="w-4 h-4"></i>' : n) + '</div></div>';
  });
  html += '</div><div class="flex mt-2">';
  STAGE_LABELS.forEach(function (label, i) {
    var active = (i + 1) === stage;
    html += '<div class="flex-1 flex justify-center px-0.5"><span class="text-[9px] sm:text-[10px] text-center leading-tight block" style="color:' + (active ? '#B11226' : '#4B5563') + ';font-weight:' + (active ? '600' : '400') + ';">' + label + '</span></div>';
  });
  return html + '</div></div>';
}

function renderNotFound(code) {
  return '<div class="modern-card bg-white rounded-2xl p-8 text-center border border-gray-100" style="box-shadow:0 1px 8px rgba(0,0,0,0.06);">'
    + '<div class="w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-3" style="background:#FEE2E2;"><i data-lucide="x-circle" class="w-6 h-6" style="color:#B11226;"></i></div>'
    + '<h3 class="font-bold text-sm mb-1" style="color:#1a1a2e;">Not Found</h3>'
    + '<p class="text-xs text-gray-500 leading-relaxed max-w-xs mx-auto">No match found for <span class="font-mono font-semibold">' + code + '</span> and that ID/email. Double-check your details or contact the OCA Office.</p></div>';
}

function renderResult(data) {
  var meta = STATUS_META[data.status] || STATUS_META.Pending;
  var stageLabels = stagesFor(data.type_code);
  var pct = Math.round((Math.min(data.stage, stageLabels.length) / stageLabels.length) * 100);
  var html = '<div class="modern-card bg-white rounded-2xl border border-gray-100 overflow-hidden" style="box-shadow:0 1px 8px rgba(0,0,0,0.06);">'
    + '<div class="h-1.5 w-full" style="background:' + meta.color + ';"></div>'
    + '<div class="p-5"><div class="flex items-start justify-between mb-4">'
    + '<div><div class="text-xs text-gray-400 mb-0.5 font-mono">' + data.application_code + '</div><h2 class="font-bold text-base" style="color:#1a1a2e;">' + data.name + '</h2><div class="text-xs text-gray-500 mt-0.5">' + data.type_name + '</div></div>'
    + '<div class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold" style="background:' + meta.bg + ';color:' + meta.color + ';"><i data-lucide="' + meta.icon + '" class="w-3.5 h-3.5"></i>' + meta.label + '</div></div>'
    + '<div class="mb-2"><div class="flex items-center justify-between mb-3"><div class="text-xs font-medium text-gray-500">Application Progress</div><div class="text-[10px] font-semibold" style="color:' + meta.color + ';">' + pct + '% complete</div></div>' + progressTrackerHtml(data.stage, data.status, data.type_code) + '</div></div></div>';

  var fields = [
    ['user', 'Student Name', data.name],
    ['book-open', 'Student ID', data.student_id],
    ['map-pin', 'Course', data.course],
    ['file-text', 'Type', data.type_name],
    ['calendar', 'Date Submitted', data.submitted_at],
    ['clock', 'Current Stage', stageLabels[data.stage - 1]],
  ];
  html += '<div class="modern-card bg-white rounded-2xl border border-gray-100 p-5" style="box-shadow:0 1px 8px rgba(0,0,0,0.06);">'
    + '<h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-4">Application Details</h3><div class="grid grid-cols-2 gap-3">'
    + fields.map(function (f) {
      return '<div class="bg-gray-50 rounded-xl p-3"><div class="flex items-center gap-1.5 mb-1"><i data-lucide="' + f[0] + '" class="w-3 h-3 text-gray-400"></i><span class="text-[10px] text-gray-400 uppercase tracking-wide">' + f[1] + '</span></div><div class="text-xs font-semibold truncate" style="color:#1a1a2e;">' + (f[2] || '') + '</div></div>';
    }).join('') + '</div></div>';

  if (data.remarks) {
    html += '<div class="modern-card bg-yellow-50 rounded-2xl p-4 border border-yellow-100"><div class="flex items-center gap-2 mb-2"><i data-lucide="message-square" class="w-4 h-4" style="color:#92400E;"></i><span class="text-xs font-semibold" style="color:#92400E;">OCA Remarks</span></div><p class="text-xs text-yellow-800 leading-relaxed">' + data.remarks + '</p></div>';
  }

  html += '<div class="rounded-2xl p-4 border" style="background:' + meta.bg + '80;border-color:' + meta.color + '30;"><p class="text-xs leading-relaxed" style="color:' + meta.color + ';">' + (STATUS_MESSAGES[data.status] || '') + '</p></div>';

  html += '<div class="flex flex-col sm:flex-row gap-2 pb-4">'
    + '<a href="' + APP_URL + '/login" class="modern-btn flex-1 py-3 rounded-xl text-sm font-semibold text-white text-center hover:opacity-90 transition-opacity" style="background: linear-gradient(135deg, #B11226, #7a0d1a);">Sign In for Full Access</a>'
    + '<button type="button" id="trackAnotherBtn" class="modern-btn flex-1 py-3 rounded-xl text-sm font-medium border border-gray-200 text-gray-600 bg-white hover:bg-gray-50 transition-colors">Track Another</button></div>';

  return html;
}

document.getElementById('trackForm').addEventListener('submit', function (e) {
  e.preventDefault();
  var code = appCodeInput.value.trim();
  var studentId = studentIdInput.value.trim();
  if (!code || !studentId) return;

  submitBtn.disabled = true;
  document.getElementById('trackSubmitLabel').innerHTML = '<div class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full spin"></div>';

  fetch(APP_URL + '/track/lookup', {
    method: 'POST', headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ application_code: code, id_number: studentId }),
  }).then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); })
    .then(function (res) {
      document.getElementById('trackSubmitLabel').textContent = 'Track';
      syncSubmitState();
      var resultArea = document.getElementById('resultArea');
      document.getElementById('hintArea').classList.add('hidden');
      resultArea.classList.remove('hidden'); resultArea.classList.add('flex');
      resultArea.innerHTML = res.ok ? (res.data.kind === 'appeal' ? renderAppealResult(res.data) : renderResult(res.data)) : renderNotFound(code);
      lucide.createIcons();
      var again = document.getElementById('trackAnotherBtn');
      if (again) again.addEventListener('click', function () {
        appCodeInput.value = ''; studentIdInput.value = '';
        resultArea.classList.add('hidden'); resultArea.classList.remove('flex');
        document.getElementById('hintArea').classList.remove('hidden');
        syncSubmitState();
      });
    });
});
</script>

</body>
</html>
