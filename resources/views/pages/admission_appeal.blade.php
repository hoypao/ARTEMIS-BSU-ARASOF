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
  @keyframes ap-fade-up { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: translateY(0); } }
  .ap-card { animation: ap-fade-up 0.5s var(--ease-out-soft, ease-out) both; }
  @media (prefers-reduced-motion: reduce) { .ap-card { animation: none !important; } }
  html, body { scrollbar-width: none; -ms-overflow-style: none; }
  html::-webkit-scrollbar, body::-webkit-scrollbar { display: none; width: 0; height: 0; }
</style>
</head>
<body class="min-h-screen bg-gray-50 flex flex-col">

<nav class="sticky top-0 z-30 bg-white border-b border-gray-100" style="box-shadow: 0 1px 8px rgba(0,0,0,0.06);">
  <div class="max-w-2xl mx-auto px-4 sm:px-6 flex items-center justify-between h-16">
    <div class="flex items-center gap-3">
      <a href="<?= e(APP_URL) ?>" class="flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-800 transition-colors mr-1">
        <i data-lucide="chevron-left" class="w-4 h-4"></i> Back
      </a>
      <div class="hidden sm:block h-5 w-px bg-gray-200"></div>
      <div class="hidden sm:flex items-center gap-2.5">
        <div class="w-8 h-8 rounded-full bg-white flex items-center justify-center overflow-hidden flex-shrink-0" style="border:1px solid #eee;"><img src="<?= APP_URL ?>/assets/images/bsulogo.jpg" alt="BatStateU OCA Logo" class="w-full h-full object-cover"></div>
        <div><div class="font-bold text-sm tracking-wider" style="color:#B11226;">ARTEMIS</div><div class="text-xs text-gray-600">Admission Appeal</div></div>
      </div>
    </div>
    <a href="<?= e($portalUrl) ?>" class="px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold text-white hover:opacity-90 transition-opacity flex-shrink-0" style="background: linear-gradient(135deg, #B11226, #7a0d1a);"><?= $user ? 'Go to Dashboard' : 'Login to Portal' ?></a>
  </div>
</nav>

<main class="flex-1 max-w-2xl mx-auto w-full px-4 py-10">

  <div class="text-center mb-6">
    <div class="w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-4" style="background:#FEE2E2;"><i data-lucide="user-plus" class="w-6 h-6" style="color:#B11226;"></i></div>
    <h1 class="text-2xl font-bold" style="color:#1a1a2e;">Admission Appeal &mdash; Cultural &amp; Artistic Excellence</h1>
    <p class="text-sm text-gray-500 mt-2 max-w-lg mx-auto">For incoming students who excel in culture and arts but did not initially meet the University's admission cut-off (Art. IV Sec. 11, per BOR Resolution No. 44 S. 2024). You do not need an ARTEMIS account to apply &mdash; this form is for prospective students only.</p>
  </div>

  <?php if ($success): ?>
    <div class="ap-card bg-white rounded-2xl border border-gray-100 p-6 sm:p-7 text-center" style="box-shadow: 0 12px 32px rgba(0,0,0,0.1);">
      <div class="w-14 h-14 rounded-full flex items-center justify-center mx-auto mb-4" style="background:#DCFCE7;"><i data-lucide="check-circle" class="w-7 h-7" style="color:#15803D;"></i></div>
      <h2 class="font-bold text-lg mb-2" style="color:#1a1a2e;">Appeal Submitted</h2>
      <p class="text-sm text-gray-600 leading-relaxed mb-3"><?= e($success) ?></p>
      <?php if ($referenceId): ?>
        <div class="inline-block px-4 py-2 rounded-xl font-mono text-sm font-semibold" style="background:#FEF9C3; color:#92400E;">Reference: <?= e($referenceId) ?></div>
        <p class="text-xs text-gray-400 mt-3">Please save this reference number for your records.</p>
      <?php endif; ?>
    </div>
  <?php else: ?>

  <?php if ($error): ?>
    <div class="ap-card mb-4 p-4 rounded-xl text-sm flex items-start gap-2.5" style="background:#FEE2E2; color:#B91C1C;">
      <i data-lucide="alert-circle" class="w-4 h-4 mt-0.5 flex-shrink-0"></i> <span><?= e($error) ?></span>
    </div>
  <?php endif; ?>

  <div class="ap-card bg-white rounded-2xl border border-gray-100 p-6 sm:p-7" style="box-shadow: 0 12px 32px rgba(0,0,0,0.1);">
    <form method="POST" action="<?= e(APP_URL) ?>/appeal/apply" enctype="multipart/form-data" class="flex flex-col gap-4">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div><label for="full_name" class="text-xs font-medium text-gray-600 block mb-1">Full Name</label><input id="full_name" name="full_name" required class="modern-input w-full px-3.5 py-3 rounded-xl border border-gray-200 text-sm focus:outline-none"></div>
        <div><label for="email" class="text-xs font-medium text-gray-600 block mb-1">Email Address</label><input type="email" id="email" name="email" required class="modern-input w-full px-3.5 py-3 rounded-xl border border-gray-200 text-sm focus:outline-none"></div>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div><label for="contact_number" class="text-xs font-medium text-gray-600 block mb-1">Contact Number <span class="text-gray-400">(optional)</span></label><input id="contact_number" name="contact_number" class="modern-input w-full px-3.5 py-3 rounded-xl border border-gray-200 text-sm focus:outline-none"></div>
        <div><label for="secondary_school" class="text-xs font-medium text-gray-600 block mb-1">Secondary School</label><input id="secondary_school" name="secondary_school" required class="modern-input w-full px-3.5 py-3 rounded-xl border border-gray-200 text-sm focus:outline-none"></div>
      </div>

      <div>
        <label for="achievements_summary" class="text-xs font-medium text-gray-600 block mb-1">Cultural/Artistic Achievements (Sec. 11-A.1)</label>
        <textarea id="achievements_summary" name="achievements_summary" rows="3" required placeholder="Competitions joined, awards/distinctions received, membership in your school's culture and arts organization..." class="modern-input w-full px-3.5 py-3 rounded-xl border border-gray-200 text-sm focus:outline-none"></textarea>
      </div>
      <div>
        <label for="discipline" class="text-xs font-medium text-gray-600 block mb-1">Discipline (Sec. 12)</label>
        <select id="discipline" name="discipline" required class="modern-input w-full px-3.5 py-3 rounded-xl border border-gray-200 text-sm focus:outline-none bg-white">
          <option value="" disabled selected>Select the field that matches your achievements above</option>
          <?php foreach (ARTEMIS_APPEAL_DISCIPLINES as $d): ?>
            <option value="<?= e($d) ?>"><?= e($d) ?></option>
          <?php endforeach; ?>
        </select>
        <p class="text-[11px] text-gray-400 mt-1">Choose whichever field matches what you described above — e.g. select Dance if your achievements are in dance.</p>
      </div>
      <div>
        <label for="academic_standing_note" class="text-xs font-medium text-gray-600 block mb-1">Academic Standing <span class="text-gray-400">(optional)</span></label>
        <textarea id="academic_standing_note" name="academic_standing_note" rows="2" placeholder="e.g. general average, class rank, any relevant notes on your secondary school academic record" class="modern-input w-full px-3.5 py-3 rounded-xl border border-gray-200 text-sm focus:outline-none"></textarea>
      </div>

      <div class="border-t border-gray-100 pt-4 flex flex-col gap-3">
        <p class="text-xs font-semibold" style="color:#1a1a2e;">Required Documents (Sec. 11-B)</p>
        <div>
          <label for="certificates" class="text-xs text-gray-600 block mb-1">Certificates, medals, or awards from competitions</label>
          <div class="flex items-center gap-3 flex-wrap">
            <label for="certificates" id="certificates-btn" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-semibold border border-gray-200 text-gray-700 hover:bg-gray-50 transition-colors cursor-pointer flex-shrink-0">
              <i data-lucide="upload" class="w-3.5 h-3.5"></i> Choose File
            </label>
            <span id="certificates-name" class="text-xs text-gray-400">No file chosen</span>
          </div>
          <input type="file" id="certificates" name="certificates" accept=".pdf,.jpg,.jpeg,.png" required class="hidden">
        </div>
        <div>
          <label for="recommendation_letter" class="text-xs text-gray-600 block mb-1">Letter of recommendation from a trainer</label>
          <div class="flex items-center gap-3 flex-wrap">
            <label for="recommendation_letter" id="recommendation_letter-btn" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-semibold border border-gray-200 text-gray-700 hover:bg-gray-50 transition-colors cursor-pointer flex-shrink-0">
              <i data-lucide="upload" class="w-3.5 h-3.5"></i> Choose File
            </label>
            <span id="recommendation_letter-name" class="text-xs text-gray-400">No file chosen</span>
          </div>
          <input type="file" id="recommendation_letter" name="recommendation_letter" accept=".pdf,.jpg,.jpeg,.png" required class="hidden">
        </div>
        <div>
          <label for="school_statement" class="text-xs text-gray-600 block mb-1">Statement from your secondary school's culture and arts department</label>
          <div class="flex items-center gap-3 flex-wrap">
            <label for="school_statement" id="school_statement-btn" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-semibold border border-gray-200 text-gray-700 hover:bg-gray-50 transition-colors cursor-pointer flex-shrink-0">
              <i data-lucide="upload" class="w-3.5 h-3.5"></i> Choose File
            </label>
            <span id="school_statement-name" class="text-xs text-gray-400">No file chosen</span>
          </div>
          <input type="file" id="school_statement" name="school_statement" accept=".pdf,.jpg,.jpeg,.png" required class="hidden">
        </div>
        <p class="text-[11px] text-gray-400">PDF or image (jpg, png), max 10MB each.</p>
      </div>

      <button type="submit" class="modern-btn w-full py-3 rounded-xl text-sm font-semibold text-white transition-opacity flex items-center justify-center gap-2" style="background: linear-gradient(135deg, #B11226, #7a0d1a);">
        <i data-lucide="send" class="w-4 h-4"></i> Submit Appeal
      </button>
    </form>
  </div>
  <?php endif; ?>

</main>

<script>
lucide.createIcons();
['certificates', 'recommendation_letter', 'school_statement'].forEach(function (id) {
  var input = document.getElementById(id);
  var nameEl = document.getElementById(id + '-name');
  var btn = document.getElementById(id + '-btn');
  if (!input) return;
  input.addEventListener('change', function () {
    var file = input.files && input.files[0];
    nameEl.textContent = file ? file.name : 'No file chosen';
    nameEl.style.color = file ? '#15803D' : '';
    if (btn) { btn.style.borderColor = file ? '#15803D' : ''; btn.style.color = file ? '#15803D' : ''; }
  });
});
</script>
</body>
</html>
