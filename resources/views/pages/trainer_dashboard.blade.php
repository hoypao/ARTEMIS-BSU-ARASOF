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
          <div class="text-red-200 text-xs truncate" title="<?= e($disciplineLabel) ?>"><?= e($disciplineLabel) ?></div>
        </div>
      </div>
      <div class="mt-2 inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium" style="background: rgba(212,175,55,0.2); color: #D4AF37;">
        <span class="w-1.5 h-1.5 rounded-full" style="background: #D4AF37;"></span>
        Trainer / Coach
      </div>
    </div>

    <nav class="flex-1 px-4 py-4 flex flex-col gap-1">
      <button data-tab="dashboard" class="dash-nav-btn flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm transition-all text-left" style="background: rgba(255,255,255,0.2); color:#fff; font-weight:600;">
        <i data-lucide="home" class="w-4 h-4"></i> Dashboard
      </button>
      <button data-tab="applications" class="dash-nav-btn flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm transition-all text-left" style="color: rgba(255,255,255,0.7);">
        <i data-lucide="file-text" class="w-4 h-4"></i> My Members' Applications
      </button>
      <button data-tab="evaluation" class="dash-nav-btn flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm transition-all text-left" style="color: rgba(255,255,255,0.7);">
        <i data-lucide="award" class="w-4 h-4"></i> My Evaluation
      </button>
      <button data-tab="auditions" class="dash-nav-btn flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm transition-all text-left" style="color: rgba(255,255,255,0.7);">
        <i data-lucide="star" class="w-4 h-4"></i> Audition Ratings
        <?php if ($auditionPendingCount > 0): ?>
          <span class="ml-auto inline-flex items-center justify-center min-w-5 h-5 px-1 rounded-full text-[10px] font-bold" style="background:#D4AF37; color:#1a1a2e;"><?= (int) $auditionPendingCount ?></span>
        <?php endif; ?>
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
            <h1 class="font-bold text-base" style="color:#1a1a2e;" id="pageTitleDesktop">Dashboard</h1>
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

    <main class="flex-1 p-4 sm:p-6 pb-24 lg:pb-6 flex flex-col gap-4 sm:gap-6">
      <?php if ($flashSuccess = flash_get('success')): ?>
        <div class="rounded-2xl p-4 border text-sm font-medium" style="background:#DCFCE7; border-color:#22C55E; color:#15803D;"><?= e($flashSuccess) ?></div>
      <?php endif; ?>
      <?php if ($flashError = flash_get('error')): ?>
        <div class="rounded-2xl p-4 border text-sm font-medium" style="background:#FEE2E2; border-color:#EF4444; color:#B91C1C;"><?= e($flashError) ?></div>
      <?php endif; ?>

      <!-- DASHBOARD TAB -->
      <div class="tab-panel flex flex-col gap-4 sm:gap-6" data-panel="dashboard">
        <div class="modern-card rounded-2xl p-5 sm:p-6 text-white" style="background: linear-gradient(135deg, #B11226 0%, #7a0d1a 100%);">
          <h2 class="text-lg sm:text-xl font-bold mb-1">Welcome back, <?= e($trainer['first_name']) ?></h2>
          <p class="text-red-100 text-sm">Here's a snapshot of your RPAG members and your training work this period.</p>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
          <div class="modern-card bg-white rounded-2xl p-4 sm:p-5 border border-gray-100" style="box-shadow: 0 1px 4px rgba(0,0,0,0.06);">
            <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center mb-3" style="background:#DBEAFE;"><i data-lucide="users" class="w-4 h-4 sm:w-5 sm:h-5" style="color:#3B82F6;"></i></div>
            <div class="text-xl sm:text-2xl font-bold mb-0.5" style="color:#1a1a2e;"><?= count($members) ?></div>
            <div class="text-xs text-gray-500 leading-tight">RPAG Members</div>
          </div>
          <div class="modern-card bg-white rounded-2xl p-4 sm:p-5 border border-gray-100" style="box-shadow: 0 1px 4px rgba(0,0,0,0.06);">
            <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center mb-3" style="background:#DCFCE7;"><i data-lucide="check-circle" class="w-4 h-4 sm:w-5 sm:h-5" style="color:#22C55E;"></i></div>
            <div class="text-xl sm:text-2xl font-bold mb-0.5" style="color:#1a1a2e;"><?= $activeCount ?></div>
            <div class="text-xs text-gray-500 leading-tight">Active Members</div>
          </div>
          <div class="modern-card bg-white rounded-2xl p-4 sm:p-5 border border-gray-100" style="box-shadow: 0 1px 4px rgba(0,0,0,0.06);">
            <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center mb-3" style="background:#FEF9C3;"><i data-lucide="award" class="w-4 h-4 sm:w-5 sm:h-5" style="color:#D4AF37;"></i></div>
            <div class="text-lg sm:text-xl font-bold mb-0.5 leading-tight" style="color:#1a1a2e;"><?= e($latestEvaluation['recommended_level'] ?? '—') ?></div>
            <div class="text-xs text-gray-500 leading-tight">Trainer Level</div>
          </div>
          <div class="modern-card bg-white rounded-2xl p-4 sm:p-5 border border-gray-100" style="box-shadow: 0 1px 4px rgba(0,0,0,0.06);">
            <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center mb-3" style="background:#FEE2E2;"><i data-lucide="wallet" class="w-4 h-4 sm:w-5 sm:h-5" style="color:#B11226;"></i></div>
            <div class="text-lg sm:text-xl font-bold mb-0.5 leading-tight" style="color:#1a1a2e;"><?= $latestEvaluation && $latestEvaluation['computed_honorarium'] !== null ? '₱' . number_format((float) $latestEvaluation['computed_honorarium'], 2) : '—' ?></div>
            <div class="text-xs text-gray-500 leading-tight">Honorarium</div>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="modern-card bg-white rounded-2xl border border-gray-100 p-5" style="box-shadow: 0 1px 4px rgba(0,0,0,0.06);">
            <h3 class="font-semibold text-sm mb-3" style="color:#1a1a2e;">My Members' Applications by Status</h3>
            <?php if (!$memberApplications): ?>
              <div class="text-center text-sm text-gray-400 py-6">No applications yet.</div>
            <?php else: ?>
              <div class="flex items-center gap-4">
                <div style="width:110px;height:110px;"><canvas id="statusDonut" width="110" height="110"></canvas></div>
                <div class="flex flex-col gap-1.5 flex-1">
                  <?php foreach ([['Approved', '#22C55E'], ['Pending', '#F59E0B'], ['Under Review', '#3B82F6'], ['Evaluation', '#A855F7'], ['Rejected', '#EF4444']] as [$label, $color]): ?>
                    <div class="flex items-center justify-between text-xs">
                      <span class="flex items-center gap-1.5 text-gray-500"><span class="w-2 h-2 rounded-full" style="background:<?= $color ?>;"></span><?= e($label) ?></span>
                      <span class="font-semibold" style="color:#1a1a2e;"><?= (int) $memberStatusCounts[$label] ?></span>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endif; ?>
          </div>

          <div class="modern-card bg-white rounded-2xl border border-gray-100 p-5" style="box-shadow: 0 1px 4px rgba(0,0,0,0.06);">
            <h3 class="font-semibold text-sm mb-3" style="color:#1a1a2e;">My Members' Applications by Type</h3>
            <?php if (!$memberApplications): ?>
              <div class="text-center text-sm text-gray-400 py-6">No applications yet.</div>
            <?php else: ?>
              <div style="height:110px;"><canvas id="typeBar"></canvas></div>
            <?php endif; ?>
          </div>
        </div>

        <div class="modern-card bg-white rounded-2xl border border-gray-100 overflow-hidden" style="box-shadow: 0 1px 4px rgba(0,0,0,0.06);">
          <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-100">
            <h3 class="font-semibold text-sm" style="color:#1a1a2e;">My RPAG Members</h3>
          </div>
          <?php if (!$members): ?>
            <div class="p-8 text-center text-sm text-gray-400">No RPAG members are assigned to you yet.</div>
          <?php else: ?>
            <div class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead><tr style="background:#F9FAFB;">
                  <th class="text-left px-5 py-2.5 font-medium text-gray-500 text-xs">Name</th>
                  <th class="text-left px-5 py-2.5 font-medium text-gray-500 text-xs">ID Number</th>
                  <th class="text-left px-5 py-2.5 font-medium text-gray-500 text-xs">Course</th>
                  <th class="text-left px-5 py-2.5 font-medium text-gray-500 text-xs">Troupe</th>
                  <th class="text-left px-5 py-2.5 font-medium text-gray-500 text-xs">Years Active</th>
                  <th class="text-left px-5 py-2.5 font-medium text-gray-500 text-xs">Status</th>
                </tr></thead>
                <tbody>
                  <?php foreach ($members as $m): ?>
                  <tr class="border-t border-gray-50">
                    <td class="px-5 py-3 font-medium" style="color:#1a1a2e;"><?= e($m['first_name'] . ' ' . $m['last_name']) ?></td>
                    <td class="px-5 py-3 text-gray-500"><?= e($m['id_number'] ?? '—') ?></td>
                    <td class="px-5 py-3 text-gray-500"><?= e($m['course'] ?? '—') ?></td>
                    <td class="px-5 py-3 text-gray-500"><?= e($m['troupe_name'] ?? '—') ?></td>
                    <td class="px-5 py-3 text-gray-500"><?= $m['years_active'] !== null ? (int) $m['years_active'] : '—' ?></td>
                    <td class="px-5 py-3">
                      <?php if ($m['active_member']): ?>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium" style="background:#DCFCE7; color:#16A34A;">Active</span>
                      <?php else: ?>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium" style="background:#F3F4F6; color:#6B7280;">Inactive</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- APPLICATIONS TAB -->
      <div class="tab-panel hidden flex-col gap-4 sm:gap-6" data-panel="applications">
        <div class="modern-card bg-white rounded-2xl border border-gray-100 overflow-hidden" style="box-shadow: 0 1px 4px rgba(0,0,0,0.06);">
          <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-100">
            <h3 class="font-semibold text-sm" style="color:#1a1a2e;">My Members' Applications</h3>
          </div>
          <?php if (!$memberApplications): ?>
            <div class="p-8 text-center text-sm text-gray-400">Your RPAG members haven't submitted any applications yet.</div>
          <?php else: ?>
            <div class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead><tr style="background:#F9FAFB;">
                  <th class="text-left px-5 py-2.5 font-medium text-gray-500 text-xs">Member</th>
                  <th class="text-left px-5 py-2.5 font-medium text-gray-500 text-xs">Application</th>
                  <th class="text-left px-5 py-2.5 font-medium text-gray-500 text-xs">Type</th>
                  <th class="text-left px-5 py-2.5 font-medium text-gray-500 text-xs">Status</th>
                  <th class="text-left px-5 py-2.5 font-medium text-gray-500 text-xs">Endorsement</th>
                </tr></thead>
                <tbody>
                  <?php foreach ($memberApplications as $app): ?>
                  <tr class="border-t border-gray-50">
                    <td class="px-5 py-3 font-medium" style="color:#1a1a2e;"><?= e($app['first_name'] . ' ' . $app['last_name']) ?></td>
                    <td class="px-5 py-3 text-gray-500"><?= e($app['application_code']) ?></td>
                    <td class="px-5 py-3 text-gray-500"><?= e($app['type_name']) ?></td>
                    <td class="px-5 py-3">
                      <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                        style="background:<?= $app['status'] === 'Approved' ? '#DCFCE7' : ($app['status'] === 'Rejected' ? '#FEE2E2' : '#FEF9C3') ?>; color:<?= $app['status'] === 'Approved' ? '#16A34A' : ($app['status'] === 'Rejected' ? '#DC2626' : '#B45309') ?>;">
                        <?= e($app['status']) ?>
                      </span>
                    </td>
                    <td class="px-5 py-3">
                      <?php if (!$app['requires_endorsement']): ?>
                        <span class="text-xs text-gray-400">Not required</span>
                      <?php elseif ($app['endorsed']): ?>
                        <span class="inline-flex items-center gap-1 text-xs font-medium" style="color:#16A34A;"><i data-lucide="check-circle" class="w-3.5 h-3.5"></i> Endorsed <?= e(format_date($app['endorsed_at'])) ?></span>
                      <?php else: ?>
                        <form method="POST" action="<?= e(APP_URL) ?>/trainer/endorse" class="inline">
                          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                          <input type="hidden" name="application_id" value="<?= (int) $app['application_id'] ?>">
                          <button type="submit" class="px-3 py-1 rounded-full text-xs font-semibold text-white transition-all" style="background:#D4AF37;">Endorse</button>
                        </form>
                      <?php endif; ?>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- EVALUATION TAB -->
      <div class="tab-panel hidden flex-col gap-4 sm:gap-6" data-panel="evaluation">
        <div class="modern-card bg-white rounded-2xl border border-gray-100 p-5 sm:p-6" style="box-shadow: 0 1px 4px rgba(0,0,0,0.06);">
          <h3 class="font-semibold text-sm mb-4" style="color:#1a1a2e;">My Trainer Level Equivalency Evaluation</h3>
          <?php if (!$latestEvaluation): ?>
            <div class="text-center text-sm text-gray-400 py-6">OCA hasn't recorded a Trainer Level Equivalency evaluation for you yet.</div>
          <?php else: ?>
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-5">
              <div class="text-center p-3 rounded-xl" style="background:#F9FAFB;">
                <div class="text-lg font-bold" style="color:#1a1a2e;"><?= (int) $latestEvaluation['score_experience'] ?></div>
                <div class="text-[11px] text-gray-500">Experience</div>
              </div>
              <div class="text-center p-3 rounded-xl" style="background:#F9FAFB;">
                <div class="text-lg font-bold" style="color:#1a1a2e;"><?= (int) $latestEvaluation['score_recognition'] ?></div>
                <div class="text-[11px] text-gray-500">Recognition</div>
              </div>
              <div class="text-center p-3 rounded-xl" style="background:#F9FAFB;">
                <div class="text-lg font-bold" style="color:#1a1a2e;"><?= (int) $latestEvaluation['score_contributions'] ?></div>
                <div class="text-[11px] text-gray-500">Contributions</div>
              </div>
              <div class="text-center p-3 rounded-xl" style="background:#F9FAFB;">
                <div class="text-lg font-bold" style="color:#1a1a2e;"><?= (int) $latestEvaluation['score_skills'] ?></div>
                <div class="text-[11px] text-gray-500">Skills</div>
              </div>
              <div class="text-center p-3 rounded-xl" style="background:#F9FAFB;">
                <div class="text-lg font-bold" style="color:#1a1a2e;"><?= (int) $latestEvaluation['score_credentials'] ?></div>
                <div class="text-[11px] text-gray-500">Credentials</div>
              </div>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm mb-5">
              <div><div class="text-gray-500 text-xs mb-0.5">Total Score</div><div class="font-semibold" style="color:#1a1a2e;"><?= (int) $latestEvaluation['total_score'] ?> / 25</div></div>
              <div><div class="text-gray-500 text-xs mb-0.5">Recommended Level</div><div class="font-semibold" style="color:#1a1a2e;"><?= e($latestEvaluation['recommended_level']) ?></div></div>
              <div><div class="text-gray-500 text-xs mb-0.5">Salary Grade</div><div class="font-semibold" style="color:#1a1a2e;">SG-<?= (int) $latestEvaluation['recommended_salary_grade'] ?></div></div>
              <div><div class="text-gray-500 text-xs mb-0.5">Hourly Rate</div><div class="font-semibold" style="color:#1a1a2e;"><?= $latestEvaluation['hourly_rate'] !== null ? '₱' . number_format((float) $latestEvaluation['hourly_rate'], 2) : '—' ?></div></div>
            </div>
            <form method="POST" action="<?= e(APP_URL) ?>/trainer/hours" class="flex flex-col sm:flex-row items-start sm:items-end gap-3 pt-4 border-t border-gray-100">
              <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
              <div class="flex-1 w-full sm:max-w-xs">
                <label for="hoursRenderedInput" class="block text-xs font-medium text-gray-500 mb-1">Log my training hours rendered (this period)</label>
                <input type="number" step="0.5" min="0" name="hours_rendered" id="hoursRenderedInput" value="<?= e($latestEvaluation['hours_rendered'] ?? '') ?>" placeholder="e.g. 48" class="w-full px-3 py-2 rounded-xl border border-gray-200 text-sm dash-input focus:outline-none">
              </div>
              <button type="submit" class="modern-btn px-4 py-2 rounded-xl text-sm font-semibold text-white transition-all" style="background:#B11226;">Update Hours</button>
            </form>
          <?php endif; ?>
        </div>
      </div>

      <!-- AUDITIONS TAB -->
      <div class="tab-panel hidden flex-col gap-4 sm:gap-6" data-panel="auditions">
        <div class="modern-card bg-white rounded-2xl border border-gray-100 p-5 sm:p-6" style="box-shadow: 0 1px 4px rgba(0,0,0,0.06);">
          <h3 class="font-semibold text-sm mb-1" style="color:#1a1a2e;">Audition / Recruitment Ratings</h3>
          <p class="text-xs text-gray-500">Rate every audition applicant with a score and a Pass/Fail decision. Ratings feed the RPAG deliberation OCA runs before final admission (WI-OCA-03).</p>
        </div>

        <?php if (!$auditionApplications): ?>
          <div class="modern-card bg-white rounded-2xl border border-gray-100 p-8 text-center text-sm text-gray-400" style="box-shadow: 0 1px 4px rgba(0,0,0,0.06);">No Audition/Recruitment applications have been submitted yet.</div>
        <?php else: ?>
          <div class="flex flex-col gap-4">
            <?php foreach ($auditionApplications as $a): ?>
            <div class="modern-card bg-white rounded-2xl border border-gray-100 p-5" style="box-shadow: 0 1px 4px rgba(0,0,0,0.06);">
              <div class="flex items-start justify-between gap-3 flex-wrap mb-3">
                <div>
                  <div class="font-semibold text-sm" style="color:#1a1a2e;"><?= e($a['first_name'] . ' ' . $a['last_name']) ?></div>
                  <div class="text-xs text-gray-500 mt-0.5"><?= e($a['id_number'] ?? '—') ?> &middot; <?= e($a['course'] ?? '—') ?></div>
                  <div class="text-xs text-gray-500 mt-0.5">Declared discipline: <span class="font-medium" style="color:#1a1a2e;"><?= e($a['talent_categories'] ?: 'Not specified') ?></span></div>
                </div>
                <div class="flex flex-col items-end gap-1.5">
                  <span class="text-xs text-gray-400"><?= e($a['application_code']) ?></span>
                  <?php if ($a['rating_id'] !== null): ?>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium" style="background:<?= $a['decision'] === 'Pass' ? '#DCFCE7' : '#FEE2E2' ?>; color:<?= $a['decision'] === 'Pass' ? '#16A34A' : '#DC2626' ?>;">
                      <i data-lucide="check-circle" class="w-3.5 h-3.5"></i> Rated: <?= e($a['decision']) ?>
                    </span>
                  <?php else: ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium" style="background:#FEF9C3; color:#B45309;">Not yet rated</span>
                  <?php endif; ?>
                </div>
              </div>
              <form method="POST" action="<?= e(APP_URL) ?>/trainer/audition/rate" class="flex flex-col sm:flex-row items-start sm:items-end gap-3 pt-3 border-t border-gray-100">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="application_id" value="<?= (int) $a['application_id'] ?>">
                <div class="w-full sm:w-28">
                  <label class="block text-xs font-medium text-gray-500 mb-1">Score (0–100)</label>
                  <input type="number" step="0.01" min="0" max="100" name="score" value="<?= e($a['score'] ?? '') ?>" class="w-full px-3 py-2 rounded-xl border border-gray-200 text-sm dash-input focus:outline-none">
                </div>
                <div class="w-full sm:w-36">
                  <label class="block text-xs font-medium text-gray-500 mb-1">Decision</label>
                  <select name="decision" class="w-full px-3 py-2 rounded-xl border border-gray-200 text-sm dash-input focus:outline-none" required>
                    <option value="">Select…</option>
                    <option value="Pass" <?= $a['decision'] === 'Pass' ? 'selected' : '' ?>>Pass</option>
                    <option value="Fail" <?= $a['decision'] === 'Fail' ? 'selected' : '' ?>>Fail</option>
                  </select>
                </div>
                <div class="flex-1 w-full">
                  <label class="block text-xs font-medium text-gray-500 mb-1">Remarks (optional)</label>
                  <input type="text" name="remarks" value="<?= e($a['remarks'] ?? '') ?>" placeholder="Notes for the RPAG deliberation" class="w-full px-3 py-2 rounded-xl border border-gray-200 text-sm dash-input focus:outline-none">
                </div>
                <button type="submit" class="modern-btn px-4 py-2 rounded-xl text-sm font-semibold text-white transition-all whitespace-nowrap" style="background:#B11226;"><?= $a['rating_id'] !== null ? 'Update Rating' : 'Save Rating' ?></button>
              </form>
            </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <div class="lg:hidden flex flex-col gap-2">
        <a href="<?= e(APP_URL) ?>/logout" class="flex items-center justify-center gap-2 py-3 rounded-2xl border border-gray-200 text-sm font-medium text-gray-500">
          <i data-lucide="log-out" class="w-4 h-4"></i> Sign Out
        </a>
      </div>
    </main>

    <!-- Mobile Bottom Navigation -->
    <nav class="shell-mobilenav float-glass float-shadow lg:hidden fixed z-30">
      <div class="flex items-stretch">
        <button type="button" class="mobile-nav-btn flex-1 flex flex-col items-center justify-center py-2.5 gap-1 transition-all" data-tab="dashboard">
          <div class="mobile-nav-icon-wrap w-10 h-8 flex items-center justify-center rounded-xl transition-all" style="background:#FEE2E2;"><i data-lucide="home" class="w-4 h-4 transition-all" style="color:#B11226;"></i></div>
          <span class="mobile-nav-label text-[10px] font-medium leading-none" style="color:#B11226;">Dashboard</span>
        </button>
        <button type="button" class="mobile-nav-btn flex-1 flex flex-col items-center justify-center py-2.5 gap-1 transition-all" data-tab="applications">
          <div class="mobile-nav-icon-wrap w-10 h-8 flex items-center justify-center rounded-xl transition-all"><i data-lucide="file-text" class="w-4 h-4 transition-all" style="color:#4B5563;"></i></div>
          <span class="mobile-nav-label text-[10px] font-medium leading-none" style="color:#4B5563;">Applications</span>
        </button>
        <button type="button" class="mobile-nav-btn flex-1 flex flex-col items-center justify-center py-2.5 gap-1 transition-all" data-tab="evaluation">
          <div class="mobile-nav-icon-wrap w-10 h-8 flex items-center justify-center rounded-xl transition-all"><i data-lucide="award" class="w-4 h-4 transition-all" style="color:#4B5563;"></i></div>
          <span class="mobile-nav-label text-[10px] font-medium leading-none" style="color:#4B5563;">Evaluation</span>
        </button>
        <button type="button" class="mobile-nav-btn flex-1 flex flex-col items-center justify-center py-2.5 gap-1 transition-all" data-tab="auditions">
          <div class="mobile-nav-icon-wrap w-10 h-8 flex items-center justify-center rounded-xl transition-all"><i data-lucide="star" class="w-4 h-4 transition-all" style="color:#4B5563;"></i></div>
          <span class="mobile-nav-label text-[10px] font-medium leading-none" style="color:#4B5563;">Auditions</span>
        </button>
      </div>
    </nav>
  </div>
</div>

<script>
  lucide.createIcons();

  // Tab switching
  var titles = { dashboard: 'Dashboard', applications: "My Members' Applications", evaluation: 'My Evaluation', auditions: 'Audition Ratings' };
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
    var titleEl = document.getElementById('pageTitleDesktop');
    if (titleEl) titleEl.textContent = titles[tab];
  }
  document.querySelectorAll('.dash-nav-btn').forEach(function (b) { b.addEventListener('click', function () { setTab(b.dataset.tab); }); });
  document.querySelectorAll('.mobile-nav-btn').forEach(function (b) { b.addEventListener('click', function () { setTab(b.dataset.tab); }); });

  // Deep-link support: ?tab=evaluation after logging hours, ?tab=applications after endorsing.
  var requestedTab = new URLSearchParams(window.location.search).get('tab');
  if (requestedTab && titles[requestedTab]) { setTab(requestedTab); }

  <?php if ($memberApplications): ?>
  new Chart(document.getElementById('statusDonut'), {
    type: 'doughnut',
    data: {
      labels: ['Approved', 'Pending', 'Under Review', 'Evaluation', 'Rejected'],
      datasets: [{ data: [<?= (int) $memberStatusCounts['Approved'] ?>, <?= (int) $memberStatusCounts['Pending'] ?>, <?= (int) $memberStatusCounts['Under Review'] ?>, <?= (int) $memberStatusCounts['Evaluation'] ?>, <?= (int) $memberStatusCounts['Rejected'] ?>], backgroundColor: ['#22C55E', '#F59E0B', '#3B82F6', '#A855F7', '#EF4444'], borderWidth: 0 }],
    },
    options: { responsive: true, maintainAspectRatio: false, cutout: '60%', plugins: { legend: { display: false } } },
  });
  new Chart(document.getElementById('typeBar'), {
    type: 'bar',
    data: {
      labels: <?= json_encode(array_keys($memberTypeCounts)) ?>,
      datasets: [{ data: <?= json_encode(array_values($memberTypeCounts)) ?>, backgroundColor: ['#B11226', '#D4AF37', '#3B82F6', '#22C55E', '#A855F7', '#F59E0B'], borderRadius: 4, maxBarThickness: 24 }],
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { ticks: { font: { size: 9 }, color: '#4B5563' }, grid: { display: false } }, y: { display: false } } },
  });
  <?php endif; ?>
</script>
</body>
</html>
