<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e(APP_NAME) ?> &middot; BatStateU ARASOF-Nasugbu OCA</title>
<link rel="manifest" href="<?= APP_URL ?>/manifest.json">
<link rel="apple-touch-icon" href="<?= APP_URL ?>/assets/images/bsulogo.jpg">
<meta name="theme-color" content="#B11226">
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/tailwind.css">
<script src="<?= APP_URL ?>/assets/js/lucide.js"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/modern.css">
<style>
  body { font-family: 'Inter', system-ui, sans-serif; }
  h1, h2 { font-family: 'Inter', system-ui, sans-serif; letter-spacing: -0.01em; }
  h2 { letter-spacing: -0.02em; }
  @keyframes lp-blob1 { 0%,100% { transform: scale(1); opacity:.08; } 50% { transform: scale(1.15); opacity:.14; } }
  @keyframes lp-blob2 { 0%,100% { transform: scale(1); opacity:.08; } 50% { transform: scale(1.2); opacity:.15; } }
  @keyframes lp-scroll { 0%,100% { transform: translateY(0); } 50% { transform: translateY(6px); } }

  /* Hero slide crossfade — only the incoming slide ever fades (0→1), stacked on
     top via z-index. Outgoing slides stay at opacity:1 underneath and are simply
     covered, never fading themselves down — this keeps combined coverage at a
     constant 100% throughout the transition, so nothing bright behind the
     images (the decorative blur blobs) can flash through mid-fade. */
  .hero-slide { position: absolute; inset: 0; opacity: 0; z-index: 0; transition: opacity 1.6s var(--ease-out-soft); }
  .hero-slide.is-active { opacity: 1; z-index: 1; }
  /* Diagonal wash reads fine on a wide desktop hero (top-left and bottom-right
     are far apart); on a tall narrow mobile hero the same angle barely rotates
     across the viewport and leaves the photo too exposed behind the CTAs, so
     mobile gets a flatter top-to-bottom wash that stays dark all the way down. */
  .hero-overlay { background: linear-gradient(135deg, rgba(177,18,38,0.92) 0%, rgba(30,10,15,0.85) 60%, rgba(0,0,0,0.5) 100%); }
  @media (max-width: 767px) {
    .hero-overlay { background: linear-gradient(180deg, rgba(177,18,38,0.90) 0%, rgba(45,10,16,0.88) 45%, rgba(20,6,10,0.86) 100%); }
  }
  /* Grid-stack all copy slides in the same cell so container height is constant
     (max of all three) regardless of which one is active — prevents the
     buttons/dots below from jumping when slide text length changes. */
  .hero-copy-slides { display: grid; }
  .hero-copy-slide { grid-row: 1; grid-column: 1; opacity: 0; visibility: hidden; pointer-events: none; transition: opacity 0.5s var(--ease-out-soft); align-self: start; }
  .hero-copy-slide.is-active { opacity: 1; visibility: visible; pointer-events: auto; }
  .hero-dot { width: 6px; height: 6px; border-radius: 999px; background: rgba(255,255,255,0.35); transition: all 0.35s var(--ease-out-soft); cursor: pointer; }
  .hero-dot.is-active { width: 22px; background: #D4AF37; }

  /* Service cards */
  .service-card { box-shadow: var(--shadow-sm); transition: transform 0.35s var(--ease-out-soft), box-shadow 0.35s var(--ease-out-soft), border-color 0.35s var(--ease-out-soft); }
  .service-card:hover { transform: translateY(-6px); box-shadow: var(--shadow-lg); border-color: transparent; }
  .service-card-icon { transition: transform 0.35s var(--ease-out-soft); }
  .service-card:hover .service-card-icon { transform: scale(1.1) rotate(-4deg); }

  /* Nav auto-hide on scroll-down, reveal on scroll-up */
  #mainNav.nav-hidden { transform: translateY(-150%); }

  /* Hide the native scrollbar while keeping scroll behavior */
  html, body { scrollbar-width: none; -ms-overflow-style: none; }
  html::-webkit-scrollbar, body::-webkit-scrollbar { display: none; width: 0; height: 0; }

  /* Scroll progress bar — sits above the floating nav, fills with scroll depth */
  #scrollProgressBar { position: fixed; top: 0; left: 0; height: 6px; width: 0%; z-index: 60; background: #B11226; transition: width 0.1s linear; pointer-events: none; }

  /* Bottom-sheet / drawer fade+rise */
  #eventPeekBackdrop { transition: opacity 0.3s var(--ease-out-soft); opacity: 0; }
  #eventPeekBackdrop.is-open { opacity: 1; }
  #eventPeek { transition: transform 0.35s var(--ease-out-soft), opacity 0.35s var(--ease-out-soft); transform: translateY(24px); opacity: 0; }
  #eventPeek.is-open { transform: translateY(0); opacity: 1; }
</style>
</head>
<body class="min-h-screen bg-background pb-16 md:pb-0">
@include('partials.loading_screen')
<div id="scrollProgressBar"></div>

<nav id="mainNav" class="fixed top-3 sm:top-4 left-0 right-0 z-50 px-4 sm:px-6 lg:px-8" style="transition: transform 0.45s var(--ease-out-soft);">
  <div id="navPill" class="max-w-6xl mx-auto rounded-full transition-all duration-300" style="background: rgba(255,255,255,0.06); backdrop-filter: blur(14px) saturate(160%); -webkit-backdrop-filter: blur(14px) saturate(160%); border: 1px solid rgba(255,255,255,0.14); box-shadow: 0 4px 20px rgba(0,0,0,0.1);">
    <div class="flex items-center justify-between h-14 px-4 sm:px-5">
      <div class="flex items-center gap-2">
        <div class="w-7 h-7 rounded-full bg-white flex items-center justify-center overflow-hidden flex-shrink-0"><img src="<?= APP_URL ?>/assets/images/bsulogo.jpg" alt="BatStateU OCA Logo" class="w-full h-full object-cover"></div>
        <span class="font-bold tracking-widest" style="color:#D4AF37; font-size:1rem;">ARTEMIS</span>
      </div>
      <div class="hidden md:flex items-center gap-7">
        <button type="button" class="scroll-to-btn text-xs font-medium bg-transparent border-0 cursor-pointer uppercase tracking-wider transition-opacity hover:opacity-100 opacity-70" style="color:#fff; letter-spacing:0.08em;" data-target="about">About</button>
        <button type="button" class="scroll-to-btn text-xs font-medium bg-transparent border-0 cursor-pointer uppercase tracking-wider transition-opacity hover:opacity-100 opacity-70" style="color:#fff; letter-spacing:0.08em;" data-target="services">Services</button>
        <button type="button" class="scroll-to-btn text-xs font-medium bg-transparent border-0 cursor-pointer uppercase tracking-wider transition-opacity hover:opacity-100 opacity-70" style="color:#fff; letter-spacing:0.08em;" data-target="announcements">Announcements</button>
        <button type="button" class="scroll-to-btn text-xs font-medium bg-transparent border-0 cursor-pointer uppercase tracking-wider transition-opacity hover:opacity-100 opacity-70" style="color:#fff; letter-spacing:0.08em;" data-target="events">Events</button>
      </div>

      <?php if ($isStudent): ?>
      <div class="hidden md:flex items-center gap-2 sm:gap-3">
        <div class="relative">
          <button type="button" id="navProfileBtn" class="flex items-center gap-2 pl-1 pr-1.5 sm:pr-3 py-1 rounded-full transition-colors hover:bg-white/10">
            <?php if ($navPhotoUrl): ?>
              <img src="<?= e($navPhotoUrl) ?>" alt="<?= e($navFullName) ?>" class="w-8 h-8 rounded-full border border-white/30 object-cover flex-shrink-0">
            <?php else: ?>
              <div class="w-8 h-8 rounded-full bg-white/20 border border-white/30 flex items-center justify-center text-white font-bold text-xs flex-shrink-0"><?= e($navInitials) ?></div>
            <?php endif; ?>
            <span class="hidden sm:inline text-xs font-medium text-white max-w-[110px] truncate"><?= e($navFirstName) ?></span>
            <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-white/70 hidden sm:block"></i>
          </button>
          <div id="navProfileDropdown" class="hidden absolute top-11 right-0 w-52 bg-white rounded-2xl border border-gray-100 z-50 overflow-hidden py-1.5" style="box-shadow: 0 16px 40px rgba(0,0,0,0.18);">
            <div class="px-4 py-2.5 border-b border-gray-100">
              <div class="text-xs font-semibold truncate" style="color:#1a1a2e;"><?= e($navFullName) ?></div>
              <div class="text-[11px] text-gray-400 truncate"><?= e($sessionUser['email']) ?></div>
            </div>
            <a href="<?= e(APP_URL) ?>/student/dashboard" class="flex items-center gap-2.5 px-4 py-2.5 text-xs text-gray-600 hover:bg-gray-50 transition-colors">
              <i data-lucide="user" class="w-3.5 h-3.5 text-gray-400"></i> Profile
            </a>
            <a href="<?= e(APP_URL) ?>/logout" class="flex items-center gap-2.5 px-4 py-2.5 text-xs hover:bg-red-50 transition-colors" style="color:#B11226;">
              <i data-lucide="log-out" class="w-3.5 h-3.5"></i> Sign Out
            </a>
          </div>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</nav>

<!-- HERO -->
<section id="heroSection" class="relative min-h-[70vh] md:min-h-screen flex items-center pt-20 overflow-hidden">
  <div class="absolute inset-0">
    <div class="hero-slide is-active">
      <picture>
        <source media="(max-width: 767px)" srcset="<?= APP_URL ?>/assets/images/familypic-mobile.jpg">
        <img src="<?= APP_URL ?>/assets/images/familypic.jpg" alt="BatStateU ARASOF-Nasugbu Culture and Arts Office investiture ceremony" class="w-full h-full object-cover" style="object-position: center 25%;">
      </picture>
      <div class="absolute inset-0 hero-overlay"></div>
    </div>
    <div class="hero-slide">
      <img src="https://images.unsplash.com/photo-1763656448109-033f71551cad?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxjdWx0dXJhbCUyMGRhbmNlJTIwcGVyZm9ybWFuY2UlMjBjb2xvcmZ1bHxlbnwxfHx8fDE3NzQ4NjA4NTl8MA&ixlib=rb-4.1.0&q=80&w=1080" alt="Filipino folk dance performance" class="w-full h-full object-cover">
      <div class="absolute inset-0 hero-overlay"></div>
    </div>
    <div class="hero-slide">
      <img src="https://images.unsplash.com/photo-1762158008445-8355e4137bd0?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxtdXNpYyUyMGNob2lyJTIwc2luZ2luZyUyMHN0dWRlbnRzfGVufDF8fHx8MTc3NDk1MjY5NXww&ixlib=rb-4.1.0&q=80&w=1080" alt="University choir performance" class="w-full h-full object-cover">
      <div class="absolute inset-0 hero-overlay"></div>
    </div>
  </div>
  <div class="absolute top-20 right-20 w-64 h-64 rounded-full bg-yellow-400 blur-3xl" style="animation: lp-blob1 6s ease-in-out infinite;"></div>
  <div class="absolute bottom-20 left-10 w-48 h-48 rounded-full bg-red-300 blur-2xl" style="animation: lp-blob2 8s ease-in-out infinite 2s;"></div>

  <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 md:py-24">
    <div class="max-w-3xl hero-copy">
      <div class="hero-copy-slides">
      <div class="hero-copy-slide is-active" data-slide="0">
        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-xs font-medium mb-6" style="background: rgba(212,175,55,0.2); color:#D4AF37; border:1px solid rgba(212,175,55,0.4);">
          <i data-lucide="star" class="w-3.5 h-3.5"></i> BatStateU ARASOF-Nasugbu &middot; Culture &amp; Arts Office
        </div>
        <h1 class="text-5xl md:text-7xl font-black text-white mb-4 leading-[0.95] tracking-tight">ARTEMIS</h1>
        <p class="text-sm md:text-xl mb-3" style="color:#D4AF37;">Artistic Resource and Talent Enterprise Management using Intelligent Systems</p>
        <p class="text-sm text-gray-300 mb-8 max-w-xl leading-relaxed">A unified digital platform for the Culture and Arts Office — automating auditions, applications, recognitions, and talent management for BatStateU ARASOF-Nasugbu.</p>
      </div>
      <div class="hero-copy-slide" data-slide="1">
        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-xs font-medium mb-6" style="background: rgba(212,175,55,0.2); color:#D4AF37; border:1px solid rgba(212,175,55,0.4);">
          <i data-lucide="sparkles" class="w-3.5 h-3.5"></i> Resident Performing Arts Group
        </div>
        <h1 class="text-5xl md:text-7xl font-black text-white mb-4 leading-[0.95] tracking-tight">ARTEMIS</h1>
        <p class="text-sm md:text-xl mb-3" style="color:#D4AF37;">Celebrating Filipino Culture Through Performance</p>
        <p class="text-sm text-gray-300 mb-8 max-w-xl leading-relaxed">From folk dance to choral music and theater arts — ARTEMIS supports every RPAG performer's journey, from audition to recognition.</p>
      </div>
      <div class="hero-copy-slide" data-slide="2">
        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-xs font-medium mb-6" style="background: rgba(212,175,55,0.2); color:#D4AF37; border:1px solid rgba(212,175,55,0.4);">
          <i data-lucide="layout-dashboard" class="w-3.5 h-3.5"></i> One Platform, Every Application
        </div>
        <h1 class="text-5xl md:text-7xl font-black text-white mb-4 leading-[0.95] tracking-tight">ARTEMIS</h1>
        <p class="text-sm md:text-xl mb-3" style="color:#D4AF37;">Stipends, Exemptions, and Recognition — Managed Intelligently</p>
        <p class="text-sm text-gray-300 mb-8 max-w-xl leading-relaxed">Track every application from OCA review through Chancellor approval, with real-time status updates at each stage.</p>
      </div>
      </div>
      <div class="flex flex-col sm:flex-row gap-3 mb-8 sm:mb-0">
        <?php if ($isStudent): ?>
          <a href="<?= e(APP_URL) ?>/student/dashboard" class="modern-btn px-8 py-3.5 rounded-xl text-sm font-semibold text-white flex items-center justify-center gap-2 w-full sm:w-auto transition-transform hover:scale-105" style="background: linear-gradient(135deg, #D4AF37, #b8932e); box-shadow: var(--shadow-glow-gold);">Go to Dashboard</a>
        <?php else: ?>
          <a href="<?= e(APP_URL) ?>/login" class="modern-btn px-8 py-3.5 rounded-xl text-sm font-semibold text-white flex items-center justify-center gap-2 w-full sm:w-auto transition-transform hover:scale-105" style="background: linear-gradient(135deg, #D4AF37, #b8932e); box-shadow: var(--shadow-glow-gold);">Login to Portal <i data-lucide="chevron-right" class="w-4 h-4"></i></a>
        <?php endif; ?>
      </div>
      <div class="flex items-center gap-2 mt-6" id="heroDots">
        <button type="button" class="hero-dot is-active" data-dot="0" aria-label="Slide 1"></button>
        <button type="button" class="hero-dot" data-dot="1" aria-label="Slide 2"></button>
        <button type="button" class="hero-dot" data-dot="2" aria-label="Slide 3"></button>
      </div>
    </div>
  </div>

  <div class="absolute bottom-8 left-1/2 -translate-x-1/2 flex flex-col items-center gap-1 opacity-60" style="animation: lp-scroll 2s ease-in-out infinite;">
    <span class="text-white text-xs">Scroll down</span>
    <div class="w-0.5 h-8 bg-white/50 rounded"></div>
  </div>
</section>

<!-- WATCH -->
<section id="watch" class="pt-6 pb-8 md:pt-8 md:pb-20 bg-white">
  <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-8 md:mb-12 reveal">
      <h2 class="text-2xl md:text-4xl font-bold mb-3 leading-[1.1]" style="color:#1a1a2e;">Discover BatStateU ARASOF-Nasugbu</h2>
    </div>
    <div class="reveal reveal-2 relative rounded-2xl overflow-hidden" style="aspect-ratio:16/9; box-shadow: 0 24px 60px rgba(0,0,0,0.18); border: 1px solid rgba(212,175,55,0.25);">
      <iframe
        src="https://www.youtube.com/embed/wNd8Xvy4XY4?autoplay=1&mute=1&playsinline=1&controls=0&loop=1&playlist=wNd8Xvy4XY4&rel=0&modestbranding=1&iv_load_policy=3&disablekb=1"
        title="BatStateU ARASOF-Nasugbu"
        class="absolute inset-0 w-full h-full"
        style="border:0; pointer-events:none;"
        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
        referrerpolicy="strict-origin-when-cross-origin"
        allowfullscreen
        loading="lazy"></iframe>
    </div>
  </div>
</section>

<!-- ABOUT -->
<section id="about" class="py-8 md:py-20 bg-white">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 md:gap-10 items-center">
      <div class="text-center lg:text-left reveal">
        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-medium mb-4" style="background:#FEF3C7; color:#92400E;"><i data-lucide="book-open" class="w-3.5 h-3.5"></i> About ARTEMIS</div>
        <h2 class="text-2xl md:text-4xl font-bold mb-4 leading-[1.1]" style="color:#1a1a2e;">Intelligent Management for Culture &amp; Arts</h2>
        <p class="text-gray-600 mb-6 leading-relaxed text-sm md:text-base">The official digital management system of the <strong>BatStateU ARASOF-Nasugbu Culture and Arts Office</strong> — automating auditions, applications, and talent recognition from submission through Chancellor approval.</p>
        <div class="grid grid-cols-2 gap-3">
          <?php foreach ([
              ['users', 'Multi-Role Access', 'Students, OCA staff, admins'],
              ['folder-check', 'Document Management', 'Upload, review, and track'],
              ['bell-ring', 'Real-Time Notifications', 'Instant status updates'],
              ['workflow', 'Automated Workflows', 'End-to-end processing'],
          ] as $fi => [$icon, $text, $desc]): ?>
            <div class="modern-card reveal reveal-<?= $fi + 1 ?> rounded-xl p-3.5 border border-gray-100 flex flex-col items-center lg:items-start text-center lg:text-left" style="background:#FAFAFA;">
              <div class="w-8 h-8 rounded-lg flex items-center justify-center mb-2.5" style="background:#FEE2E2;"><i data-lucide="<?= e($icon) ?>" class="w-4 h-4" style="color:#B11226;"></i></div>
              <div class="text-xs font-semibold text-gray-800 leading-snug mb-0.5"><?= e($text) ?></div>
              <div class="text-xs text-gray-400 leading-snug"><?= e($desc) ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="relative reveal reveal-2">
        <div class="relative rounded-2xl overflow-hidden shadow-2xl">
          <img src="https://images.unsplash.com/photo-1763656448109-033f71551cad?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxjdWx0dXJhbCUyMGRhbmNlJTIwcGVyZm9ybWFuY2UlMjBjb2xvcmZ1bHxlbnwxfHx8fDE3NzQ4NjA4NTl8MA&ixlib=rb-4.1.0&q=80&w=1080" alt="cultural performance" class="w-full h-80 object-cover">
          <div class="absolute inset-0" style="background: linear-gradient(to top, rgba(177,18,38,0.5), transparent);"></div>
        </div>
        <div class="hidden sm:block absolute -bottom-4 -right-4 bg-white rounded-2xl p-4 shadow-xl border border-gray-100">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:#FEE2E2;"><i data-lucide="star" class="w-5 h-5" style="color:#B11226;"></i></div>
            <div><div class="text-xs text-gray-500">Recognition Awards</div><div class="font-bold text-sm" style="color:#B11226;">BANTOG <?= date('Y') ?></div></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- CORE SERVICES -->
<section id="services" class="py-10 md:py-20" style="background:#F5F5F5;">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-8 md:mb-14 reveal">
      <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-medium mb-4" style="background:#FEE2E2; color:#B11226;">Core Services</div>
      <h2 class="text-2xl md:text-4xl font-bold mb-3 leading-[1.1]" style="color:#1a1a2e;">Everything You Need in One Platform</h2>
      <p class="text-gray-500 max-w-xl mx-auto leading-relaxed">ARTEMIS provides a comprehensive suite of tools for students, artists, and OCA administrators.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php foreach ($services as $si => $service): ?>
        <div class="service-card reveal reveal-<?= ($si % 3) + 1 ?> bg-white rounded-2xl p-6 border border-gray-100 cursor-pointer">
          <div class="service-card-icon w-12 h-12 rounded-xl flex items-center justify-center mb-4" style="background: <?= $service['color'] === '#B11226' ? '#FEE2E2' : '#FEF9E7' ?>;">
            <i data-lucide="<?= e($service['icon']) ?>" class="w-6 h-6" style="color:<?= e($service['color']) ?>;"></i>
          </div>
          <h3 class="font-bold text-base mb-2" style="color:#1a1a2e;"><?= e($service['title']) ?></h3>
          <p class="text-sm text-gray-500 leading-relaxed"><?= e($service['desc']) ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ANNOUNCEMENTS + EVENTS -->
<section id="announcements" class="py-10 md:py-20 bg-white">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 md:gap-12">

      <div>
        <div class="flex items-center gap-2 mb-2"><i data-lucide="bell" class="w-4 h-4" style="color:#B11226;"></i><span class="text-xs font-medium uppercase tracking-wider" style="color:#B11226;">Announcements</span></div>
        <h2 class="text-xl md:text-3xl font-bold mb-4" style="color:#1a1a2e;">Latest News &amp; Updates</h2>
        <div class="flex flex-col gap-4">
          <?php if ($isStudent && $personalAlerts): ?>
            <?php foreach ($personalAlerts as $i => $alert): ?>
              <a href="<?= e(APP_URL) ?>/student/dashboard?tab=applications" class="reveal reveal-<?= $i + 1 ?> flex gap-3 p-5 rounded-2xl border cursor-pointer transition-all hover:shadow-lg hover:-translate-y-0.5" style="border-color: <?= $alert['urgent'] ? 'rgba(177,18,38,0.25)' : '#f3f4f6' ?>; background: <?= $alert['urgent'] ? 'rgba(177,18,38,0.03)' : '#fff' ?>; box-shadow: var(--shadow-sm);">
                <div class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0" style="background: <?= e($alert['color']) ?>18;">
                  <i data-lucide="<?= e($alert['icon']) ?>" class="w-4 h-4" style="color: <?= e($alert['color']) ?>;"></i>
                </div>
                <div class="min-w-0">
                  <div class="flex items-center gap-2 mb-1">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase tracking-wide" style="background: <?= e($alert['color']) ?>18; color: <?= e($alert['color']) ?>;"><?= e($alert['badge']) ?></span>
                    <span class="text-xs text-gray-400"><?= format_date($alert['date'], 'F j, Y') ?></span>
                  </div>
                  <div class="font-semibold text-sm mb-1" style="color:#1a1a2e;"><?= e($alert['title']) ?></div>
                  <div class="text-xs text-gray-500 leading-relaxed"><?= e($alert['detail']) ?></div>
                </div>
              </a>
            <?php endforeach; ?>
          <?php endif; ?>

          <?php if (!$announcements && !($isStudent && $personalAlerts)): ?>
            <div class="text-sm text-gray-400 py-6 text-center border border-gray-100 rounded-2xl">No announcements yet.</div>
          <?php endif; ?>
          <?php foreach ($announcements as $i => $a): $accent = $i % 2 === 0 ? '#B11226' : '#D4AF37'; ?>
            <div class="reveal reveal-<?= $i + 1 ?> flex gap-3 p-5 rounded-2xl border border-gray-100 hover:border-red-100 cursor-pointer transition-all hover:shadow-lg hover:-translate-y-0.5" style="box-shadow: var(--shadow-sm);">
              <div class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0" style="background: <?= $accent ?>18;">
                <i data-lucide="megaphone" class="w-4 h-4" style="color: <?= $accent ?>;"></i>
              </div>
              <div class="min-w-0">
                <div class="text-xs text-gray-400 mb-1"><?= format_date($a['created_at'], 'F j, Y') ?></div>
                <div class="font-semibold text-sm mb-1" style="color:#1a1a2e;"><?= e($a['title']) ?></div>
                <div class="text-xs text-gray-500 leading-relaxed"><?= e($a['content']) ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div id="events">
        <div class="flex items-center gap-2 mb-2"><i data-lucide="calendar" class="w-4 h-4" style="color:#D4AF37;"></i><span class="text-xs font-medium uppercase tracking-wider" style="color:#D4AF37;">Upcoming Events</span></div>
        <h2 class="text-xl md:text-3xl font-bold mb-4" style="color:#1a1a2e;">Cultural Calendar</h2>
        <div class="flex flex-col gap-4">
          <?php if (!$upcomingEvents): ?>
            <div class="text-sm text-gray-400 py-6 text-center border border-gray-100 rounded-2xl">No upcoming events yet.</div>
          <?php endif; ?>
          <?php foreach ($upcomingEvents as $i => $ev): $ts = strtotime($ev['event_date']);
            $evJs = $eventsForJs[$i]; ?>
            <div class="event-card reveal reveal-<?= $i + 1 ?> relative rounded-2xl overflow-hidden border transition-all hover:shadow-lg hover:-translate-y-0.5" data-idx="<?= $i ?>" data-event-id="<?= (int) $ev['event_id'] ?>" data-closing-soon="<?= $evJs['closingSoon'] ? '1' : '0' ?>" style="box-shadow: var(--shadow-sm); border-color: <?= $evJs['registered'] ? 'rgba(34,197,94,0.35)' : '#f3f4f6' ?>;">
              <div class="event-peek-trigger p-5 flex items-start gap-4 cursor-pointer group">
                <div class="w-14 h-14 rounded-xl flex flex-col items-center justify-center flex-shrink-0 text-white" style="background:<?= e($ev['color_hex'] ?: '#B11226') ?>;">
                  <span class="text-xl font-bold leading-none"><?= e(date('j', $ts)) ?></span>
                  <span class="text-xs opacity-80"><?= e(date('M', $ts)) ?></span>
                </div>
                <div class="flex-1 min-w-0">
                  <div class="flex items-center flex-wrap gap-1.5 mb-1">
                    <div class="font-semibold text-sm" style="color:#1a1a2e;"><?= e($ev['title']) ?></div>
                    <?php if ($evJs['registered']): ?>
                      <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold" style="background:#BBF7D0; color:#166534;"><i data-lucide="check" class="w-2.5 h-2.5"></i> Registered</span>
                    <?php elseif ($evJs['closingSoon']): ?>
                      <span class="event-closing-badge inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold" style="background:#FEF3C7; color:#92400E;"><i data-lucide="alarm-clock" class="w-2.5 h-2.5"></i> Closing soon</span>
                    <?php endif; ?>
                    <?php if ($evJs['requiresTravel']): ?>
                      <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold" style="background:#FEF3C7; color:#92400E;"><i data-lucide="plane" class="w-2.5 h-2.5"></i> Off-campus travel</span>
                    <?php endif; ?>
                    <?php if ($evJs['requiresTypeName']): ?>
                      <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold" style="background:#EDE9FE; color:#6D28D9;"><i data-lucide="shield-check" class="w-2.5 h-2.5"></i> Approved <?= e($evJs['requiresTypeName']) ?> only</span>
                    <?php endif; ?>
                  </div>
                  <div class="flex items-center gap-1.5 text-xs text-gray-500"><i data-lucide="map-pin" class="w-3 h-3"></i><?= e($ev['location']) ?></div>
                  <div class="flex items-center gap-1.5 text-xs text-gray-400 mt-1"><i data-lucide="calendar" class="w-3 h-3"></i><?= e(date('F j, Y', $ts)) ?></div>
                </div>
                <i data-lucide="chevron-right" class="w-4 h-4 text-gray-300 group-hover:text-red-500 transition-colors mt-1 flex-shrink-0"></i>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <a href="<?= e(APP_URL) ?>/events" class="mt-6 rounded-2xl overflow-hidden relative h-36 cursor-pointer block transition-transform hover:scale-[1.01]">
          <img src="https://images.unsplash.com/photo-1762158008445-8355e4137bd0?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxtdXNpYyUyMGNob2lyJTIwc2luZ2luZyUyMHN0dWRlbnRzfGVufDF8fHx8MTc3NDk1MjY5NXww&ixlib=rb-4.1.0&q=80&w=1080" alt="cultural event" class="w-full h-full object-cover">
          <div class="absolute inset-0 flex items-center justify-center gap-2" style="background: rgba(177,18,38,0.7);">
            <span class="text-white font-bold text-sm">View All Events</span> <i data-lucide="chevron-right" class="w-4 h-4 text-white"></i>
          </div>
        </a>
      </div>
    </div>
  </div>
</section>

<!-- CTA BANNER -->
<section class="py-16 relative overflow-hidden">
  <img src="<?= e(APP_URL) ?>/assets/images/artemislogo.jpg" alt="" class="absolute inset-0 w-full h-full object-cover" loading="lazy">
  <div class="absolute inset-0" style="background: linear-gradient(135deg, rgba(177,18,38,0.92), rgba(122,13,26,0.92));"></div>
  <div class="max-w-4xl mx-auto px-4 text-center reveal relative z-10">
    <h2 class="text-xl md:text-3xl font-bold text-white mb-3">Ready to Join the BatStateU Cultural Community?</h2>
    <p class="text-red-100 mb-8 leading-relaxed">Already applied through ARTEMIS? Check your status with the Culture and Arts Office below.</p>
    <div class="flex flex-wrap gap-4 justify-center">
      <a href="<?= e(APP_URL) ?>/track" class="modern-btn px-8 py-3.5 rounded-xl text-sm font-semibold text-white transition-colors" style="border:2px solid rgba(255,255,255,0.4); background: rgba(255,255,255,0.1);">Track My Application</a>
    </div>
  </div>
</section>

<!-- FOOTER -->
<footer id="siteFooter" class="bg-gray-900 text-white">
  <div class="h-px w-full" style="background: linear-gradient(90deg, transparent, #D4AF37 40%, transparent);"></div>
  <div class="max-w-7xl mx-auto px-5 sm:px-6 lg:px-8 pt-8 md:pt-12 pb-8">
    <div class="flex items-center justify-between mb-6">
      <div class="flex items-center gap-2.5">
        <div class="w-8 h-8 rounded-full bg-white flex items-center justify-center overflow-hidden flex-shrink-0" style="border:1px solid #eee;"><img src="<?= APP_URL ?>/assets/images/bsulogo.jpg" alt="BatStateU OCA Logo" class="w-full h-full object-cover"></div>
        <div><div style="color:#D4AF37; font-size:0.95rem; font-weight:700; line-height:1;">ARTEMIS</div><div class="text-gray-500 mt-0.5" style="font-size:0.65rem;">BatStateU ARASOF-Nasugbu</div></div>
      </div>
      <p class="hidden md:block text-xs text-gray-500 italic">Intelligent Systems for Cultural Excellence</p>
    </div>
    <div class="border-t border-gray-800 mb-5"></div>
    <div class="grid grid-cols-2 gap-x-6 gap-y-6 md:grid-cols-3">
      <div class="col-span-2 md:col-span-1">
        <p class="text-xs text-gray-500 leading-relaxed">Powering the Culture and Arts Office of BatStateU ARASOF-Nasugbu Campus with intelligent digital management systems.</p>
      </div>
      <div>
        <p class="text-xs font-semibold uppercase tracking-widest mb-3" style="color:#D4AF37;">Links</p>
        <div class="flex flex-col gap-2">
          <button type="button" class="scroll-to-btn text-xs text-gray-500 hover:text-white transition-colors text-left bg-transparent border-0 cursor-pointer" data-target="about">About</button>
          <a href="<?= e(APP_URL) ?>/track" class="text-xs text-gray-500 hover:text-white transition-colors">Track Application</a>
          <button type="button" class="scroll-to-btn text-xs text-gray-500 hover:text-white transition-colors text-left bg-transparent border-0 cursor-pointer" data-target="announcements">Announcements</button>
          <a href="<?= e(APP_URL) ?>/events" class="text-xs text-gray-500 hover:text-white transition-colors">Events</a>
        </div>
      </div>
      <div>
        <p class="text-xs font-semibold uppercase tracking-widest mb-3" style="color:#D4AF37;">Contact</p>
        <div class="flex flex-col gap-2.5">
          <a href="mailto:oca.arasof@g.batstate-u.edu.ph" class="flex items-start gap-1.5 text-xs text-gray-500 hover:text-white transition-colors">
            <i data-lucide="mail" class="w-3 h-3 mt-0.5 flex-shrink-0" style="color:#D4AF37;"></i>
            <span class="break-all leading-snug">oca.arasof<wbr>@g.batstate-u<wbr>.edu.ph</span>
          </a>
          <div class="flex items-center gap-1.5 text-xs text-gray-500"><i data-lucide="phone" class="w-3 h-3 flex-shrink-0" style="color:#D4AF37;"></i> (043) 425-0139</div>
          <div class="flex items-start gap-1.5 text-xs text-gray-500 leading-snug"><i data-lucide="map-pin" class="w-3 h-3 mt-0.5 flex-shrink-0" style="color:#D4AF37;"></i><span>Nasugbu, Batangas</span></div>
        </div>
      </div>
    </div>
    <div class="border-t border-gray-800 mt-6 pt-5 flex flex-col items-center gap-3">
      <div class="flex items-center gap-2 flex-wrap justify-center">
        <?php foreach ([
            ['Facebook', 'https://web.facebook.com/BatStateUTheNEU/?_rdc=1&_rdr#', '<path d="M13.5 21v-7.2h2.4l.36-2.8h-2.76v-1.8c0-.8.22-1.36 1.38-1.36h1.48V5.3c-.26-.03-1.13-.11-2.15-.11-2.13 0-3.6 1.3-3.6 3.68v2.13H8.2v2.8h2.41V21h2.89Z" fill="currentColor"/>'],
            ['Instagram', 'https://www.instagram.com/batstateutheneu/', '<rect x="3.5" y="3.5" width="17" height="17" rx="4.5" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="17" cy="7" r="0.6" fill="currentColor"/>'],
            ['X', 'https://x.com/BatStateUTheNEU', '<path d="M13.6 10.6 20 3.5h-2l-5.3 5.9-4.2-5.9H3.5l6.7 9.4-6.9 7.6h2l5.6-6.2 4.5 6.2h5Zm-2 2.2-.6-.9-5.2-7.3h1.9l4.2 5.9.6.9 5.4 7.6h-1.9Z" fill="currentColor"/>'],
            ['YouTube', 'https://www.youtube.com/@BatStateUTheNEU', '<rect x="2.5" y="6" width="19" height="12" rx="3.5" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M10.5 9.8v4.4l4-2.2Z" fill="currentColor"/>'],
            ['LinkedIn', 'https://ph.linkedin.com/school/batstateutheneu/', '<rect x="3" y="3" width="18" height="18" rx="3" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M8 10.5v6M8 7.8v.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M11.5 16.5v-3.7c0-1.3 1-2.1 2.1-2.1 1.1 0 1.9.8 1.9 2.1v3.7" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>'],
            ['TikTok', 'https://www.tiktok.com/@batstateutheneu', '<path d="M16.5 3c.4 2.2 2 3.8 4.2 4.1v3c-1.6 0-3-.5-4.2-1.4v6.6a5.7 5.7 0 1 1-5.7-5.7c.3 0 .6 0 .9.06v3.1a2.6 2.6 0 1 0 1.8 2.5V3h3Z" fill="currentColor"/>'],
            ['Spotify', 'https://open.spotify.com/user/31nbomdr6pbmpwutfsxaidj4lzte', '<circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M7.5 10.2c3-1 6.5-.7 9 .8M8 13.3c2.4-.7 5.2-.5 7.2.7M8.5 16.2c1.9-.5 4-.4 5.5.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>'],
        ] as [$label, $url, $svgInner]): ?>
          <a href="<?= e($url) ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= e($label) ?>" title="<?= e($label) ?>"
            class="w-9 h-9 rounded-full flex items-center justify-center bg-white text-[#1a1a2e] transition-transform hover:scale-110" style="box-shadow: 0 1px 4px rgba(0,0,0,0.3);">
            <svg viewBox="0 0 24 24" class="w-4 h-4"><?= $svgInner ?></svg>
          </a>
        <?php endforeach; ?>
      </div>
      <p class="text-gray-700 italic md:hidden" style="font-size:0.65rem;">Intelligent Systems for Cultural Excellence</p>
    </div>
  </div>
  <div class="bg-white" style="padding-bottom: calc(1rem + env(safe-area-inset-bottom));">
    <p class="text-center py-4 px-4" style="color:#6b7280; font-size:0.7rem;">&copy; <?= date('Y') ?> ARTEMIS — BatStateU ARASOF-Nasugbu OCA. All rights reserved.</p>
  </div>
</footer>

<!-- MOBILE BOTTOM NAV -->
<nav class="md:hidden fixed bottom-0 left-0 right-0 z-40 bg-white border-t border-gray-100" style="box-shadow: 0 -2px 12px rgba(0,0,0,0.07); padding-bottom: env(safe-area-inset-bottom);">
  <div class="flex items-stretch">
    <button type="button" class="scroll-to-btn flex-1 flex flex-col items-center justify-center py-2.5 gap-1 bg-transparent border-0" data-target="about">
      <div class="w-10 h-7 flex items-center justify-center rounded-lg"><i data-lucide="book-open" class="w-4 h-4" style="color:#9CA3AF;"></i></div>
      <span class="text-[10px] font-medium leading-none" style="color:#9CA3AF;">About</span>
    </button>
    <button type="button" class="scroll-to-btn flex-1 flex flex-col items-center justify-center py-2.5 gap-1 bg-transparent border-0" data-target="services">
      <div class="w-10 h-7 flex items-center justify-center rounded-lg"><i data-lucide="star" class="w-4 h-4" style="color:#9CA3AF;"></i></div>
      <span class="text-[10px] font-medium leading-none" style="color:#9CA3AF;">Services</span>
    </button>
    <button type="button" class="scroll-to-btn flex-1 flex flex-col items-center justify-center py-2.5 gap-1 bg-transparent border-0" data-target="announcements">
      <div class="w-10 h-7 flex items-center justify-center rounded-lg"><i data-lucide="bell" class="w-4 h-4" style="color:#9CA3AF;"></i></div>
      <span class="text-[10px] font-medium leading-none" style="color:#9CA3AF;">Notices</span>
    </button>
    <button type="button" class="scroll-to-btn flex-1 flex flex-col items-center justify-center py-2.5 gap-1 bg-transparent border-0" data-target="events">
      <div class="w-10 h-7 flex items-center justify-center rounded-lg"><i data-lucide="calendar" class="w-4 h-4" style="color:#9CA3AF;"></i></div>
      <span class="text-[10px] font-medium leading-none" style="color:#9CA3AF;">Events</span>
    </button>
    <?php if ($isStudent): ?>
      <div class="relative flex-1">
        <button type="button" id="mobileProfileBtn" class="w-full flex flex-col items-center justify-center py-2.5 gap-1">
          <?php if ($navPhotoUrl): ?>
            <img src="<?= e($navPhotoUrl) ?>" alt="<?= e($navFullName) ?>" class="w-7 h-7 rounded-full object-cover">
          <?php else: ?>
            <div class="w-7 h-7 flex items-center justify-center rounded-full text-white font-bold" style="background:#B11226; font-size:10px;"><?= e($navInitials) ?></div>
          <?php endif; ?>
          <span class="text-[10px] font-medium leading-none" style="color:#B11226;">Profile</span>
        </button>
        <div id="mobileProfileDropdown" class="hidden absolute bottom-16 right-2 w-52 bg-white rounded-2xl border border-gray-100 z-50 overflow-hidden py-1.5" style="box-shadow: 0 -8px 30px rgba(0,0,0,0.2);">
          <div class="px-4 py-2.5 border-b border-gray-100">
            <div class="text-xs font-semibold truncate" style="color:#1a1a2e;"><?= e($navFullName) ?></div>
            <div class="text-[11px] text-gray-400 truncate"><?= e($sessionUser['email']) ?></div>
          </div>
          <a href="<?= e(APP_URL) ?>/student/dashboard" class="flex items-center gap-2.5 px-4 py-2.5 text-xs text-gray-600 hover:bg-gray-50 transition-colors">
            <i data-lucide="layout-dashboard" class="w-3.5 h-3.5 text-gray-400"></i> Dashboard
          </a>
          <a href="<?= e(APP_URL) ?>/logout" class="flex items-center gap-2.5 px-4 py-2.5 text-xs hover:bg-red-50 transition-colors" style="color:#B11226;">
            <i data-lucide="log-out" class="w-3.5 h-3.5"></i> Sign Out
          </a>
        </div>
      </div>
    <?php else: ?>
      <a href="<?= e(APP_URL) ?>/login" class="flex-1 flex flex-col items-center justify-center py-2.5 gap-1">
        <div class="w-10 h-7 flex items-center justify-center rounded-lg" style="background:#B11226;"><i data-lucide="users" class="w-4 h-4" style="color:#fff;"></i></div>
        <span class="text-[10px] font-medium leading-none" style="color:#B11226;">Login</span>
      </a>
    <?php endif; ?>
  </div>
</nav>

@include('partials.ask_spartan_widget')

<!-- EVENT PEEK -->
<div id="eventPeekBackdrop" class="hidden fixed inset-0 z-40" style="background: rgba(0,0,0,0.35);"></div>
<div id="eventPeek" class="hidden fixed bottom-0 left-0 right-0 z-50 mx-auto" style="max-width:520px;">
  <div class="bg-white mx-4 mb-4 rounded-2xl overflow-hidden" style="box-shadow: 0 20px 60px rgba(0,0,0,0.25);">
    <div class="px-5 pt-5 pb-4" id="eventPeekHeader">
      <div class="flex items-start justify-between gap-3">
        <div>
          <div class="text-xs font-medium uppercase tracking-wider text-white/70 mb-1">Upcoming Event</div>
          <h3 class="text-white" id="eventPeekTitle" style="font-size:1.15rem; font-weight:700; line-height:1.35;"></h3>
        </div>
        <button type="button" id="eventPeekCloseBtn" class="mt-0.5 flex-shrink-0 w-7 h-7 rounded-full flex items-center justify-center transition-colors" style="background: rgba(255,255,255,0.2);" aria-label="Close event preview"><i data-lucide="x" class="w-3.5 h-3.5 text-white"></i></button>
      </div>
    </div>
    <div class="px-5 py-4">
      <p class="text-gray-600 text-sm leading-relaxed mb-4" id="eventPeekDesc"></p>
      <div class="flex items-center gap-4 text-xs text-gray-400 mb-4">
        <span class="flex items-center gap-1.5"><i data-lucide="calendar" class="w-3.5 h-3.5"></i> <span id="eventPeekDate"></span></span>
        <span class="flex items-center gap-1.5"><i data-lucide="map-pin" class="w-3.5 h-3.5"></i> <span id="eventPeekLocation"></span></span>
      </div>
      <a href="<?= e(APP_URL) ?>/events" id="eventPeekCta" class="w-full py-3 rounded-xl text-sm font-semibold text-white flex items-center justify-center gap-2 transition-opacity active:opacity-80">
        View All Events <i data-lucide="chevron-right" class="w-4 h-4"></i>
      </a>
    </div>
  </div>
</div>

<!-- TRAVEL ACKNOWLEDGMENT (off-campus event RSVP gate) -->
<div id="travelAckBackdrop" class="hidden fixed inset-0" style="background: rgba(0,0,0,0.45); z-index:60;"></div>
<div id="travelAckModal" class="hidden fixed inset-0 items-center justify-center p-4" style="z-index:70;">
  <div class="bg-white w-full rounded-2xl overflow-hidden" style="max-width:420px; box-shadow: 0 24px 60px rgba(0,0,0,0.25);">
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

<script>
var EVENTS = <?= json_encode($eventsForJs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
var APP_URL = <?= json_encode(APP_URL) ?>;
var CSRF_TOKEN = <?= json_encode(csrf_token()) ?>;
lucide.createIcons();

// Scroll-aware navbar — tints on scroll, hides on scroll-down, reveals on scroll-up
var nav = document.getElementById('mainNav');
var navPill = document.getElementById('navPill');
var lastScrollY = window.scrollY;
function updateNav() {
  var y = window.scrollY;
  var scrolled = y > 40;
  navPill.style.background = scrolled ? 'rgba(12,3,5,0.55)' : 'rgba(255,255,255,0.06)';
  navPill.style.borderColor = scrolled ? 'rgba(212,175,55,0.28)' : 'rgba(255,255,255,0.14)';
  navPill.style.boxShadow = scrolled ? '0 8px 28px rgba(0,0,0,0.25)' : '0 4px 20px rgba(0,0,0,0.1)';

  var delta = y - lastScrollY;
  if (y < 80) {
    nav.classList.remove('nav-hidden');
  } else if (delta > 4) {
    nav.classList.add('nav-hidden');
  } else if (delta < -4) {
    nav.classList.remove('nav-hidden');
  }
  lastScrollY = y;
}
window.addEventListener('scroll', updateNav, { passive: true });
updateNav();

// Scroll progress bar — fills with scroll depth, shrinks back on the way up
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

function scrollToSection(id) {
  var el = document.getElementById(id);
  if (!el) return;
  var top = el.getBoundingClientRect().top + window.scrollY - 72;
  window.scrollTo({ top: top, behavior: 'smooth' });
}
document.querySelectorAll('.scroll-to-btn').forEach(function (btn) { btn.addEventListener('click', function () { scrollToSection(btn.dataset.target); }); });

// Logged-in nav profile dropdown
var navProfileBtn = document.getElementById('navProfileBtn');
var navProfileDropdown = document.getElementById('navProfileDropdown');
if (navProfileBtn && navProfileDropdown) {
  navProfileBtn.addEventListener('click', function (e) {
    e.stopPropagation();
    navProfileDropdown.classList.toggle('hidden');
  });
  document.addEventListener('click', function (e) {
    if (!navProfileDropdown.classList.contains('hidden') && !navProfileDropdown.contains(e.target) && e.target !== navProfileBtn) {
      navProfileDropdown.classList.add('hidden');
    }
  });
}

// Mobile bottom-nav profile dropdown — same Dashboard/Sign Out actions as
// the desktop nav's profile button, just anchored to the bottom bar.
var mobileProfileBtn = document.getElementById('mobileProfileBtn');
var mobileProfileDropdown = document.getElementById('mobileProfileDropdown');
if (mobileProfileBtn && mobileProfileDropdown) {
  mobileProfileBtn.addEventListener('click', function (e) {
    e.stopPropagation();
    mobileProfileDropdown.classList.toggle('hidden');
  });
  document.addEventListener('click', function (e) {
    if (!mobileProfileDropdown.classList.contains('hidden') && !mobileProfileDropdown.contains(e.target) && e.target !== mobileProfileBtn) {
      mobileProfileDropdown.classList.add('hidden');
    }
  });
}

// Event peek
var eventPeek = document.getElementById('eventPeek');
var eventPeekBackdrop = document.getElementById('eventPeekBackdrop');
function openEventPeek(idx) {
  var ev = EVENTS[idx];
  if (!ev) return;
  document.getElementById('eventPeekHeader').style.background = ev.color;
  document.getElementById('eventPeekTitle').textContent = ev.title;
  document.getElementById('eventPeekDesc').textContent = ev.desc;
  document.getElementById('eventPeekDate').textContent = ev.date;
  document.getElementById('eventPeekLocation').textContent = ev.location.split(',')[0];
  document.getElementById('eventPeekCta').style.background = ev.color;
  eventPeek.classList.remove('hidden');
  eventPeekBackdrop.classList.remove('hidden');
  requestAnimationFrame(function () { eventPeek.classList.add('is-open'); eventPeekBackdrop.classList.add('is-open'); });
}
function closeEventPeek() {
  eventPeek.classList.remove('is-open');
  eventPeekBackdrop.classList.remove('is-open');
  setTimeout(function () { eventPeek.classList.add('hidden'); eventPeekBackdrop.classList.add('hidden'); }, 350);
}
document.querySelectorAll('.event-peek-trigger').forEach(function (trigger) {
  var card = trigger.closest('.event-card');
  trigger.addEventListener('click', function () { openEventPeek(parseInt(card.dataset.idx, 10)); });
});
document.getElementById('eventPeekCloseBtn').addEventListener('click', closeEventPeek);
eventPeekBackdrop.addEventListener('click', closeEventPeek);

// Register / cancel RSVP inline — no page reload, reuses the same
// endpoint as events.php's full RSVP flow. Off-campus events route through
// the travel-acknowledgment modal first instead of registering immediately.
function performEventRsvp(btn, travelAcknowledged) {
  var card = btn.closest('.event-card');
  var eventId = parseInt(card.dataset.eventId, 10);
  var action = btn.dataset.action;
  btn.disabled = true;
  var originalText = btn.textContent;
  btn.textContent = action === 'register' ? 'Registering...' : 'Cancelling...';

  fetch(APP_URL + '/events/rsvp', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ csrf_token: CSRF_TOKEN, event_id: eventId, action: action, travel_acknowledged: !!travelAcknowledged }),
  })
    .then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); })
    .then(function (res) {
      if (!res.ok) {
        btn.textContent = originalText;
        btn.disabled = false;
        alert(res.data.error || 'Something went wrong. Please try again.');
        return;
      }
      var badgeRow = card.querySelector('.event-peek-trigger .flex-wrap');
      var closingBadge = card.querySelector('.event-closing-badge');
      if (action === 'register') {
        card.style.borderColor = 'rgba(34,197,94,0.35)';
        btn.dataset.action = 'cancel';
        btn.textContent = "You're Registered — Cancel";
        btn.style.background = '#BBF7D0';
        btn.style.color = '#166534';
        btn.style.border = '1px solid rgba(22,101,52,0.35)';
        if (closingBadge) closingBadge.remove(); // superseded by the Registered badge, same as a fresh page load would show
        if (badgeRow && !badgeRow.querySelector('.event-registered-badge')) {
          var badge = document.createElement('span');
          badge.className = 'event-registered-badge inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold';
          badge.style.background = '#BBF7D0'; badge.style.color = '#166534';
          badge.innerHTML = '<i data-lucide="check" class="w-2.5 h-2.5"></i> Registered';
          badgeRow.appendChild(badge);
          lucide.createIcons();
        }
      } else {
        card.style.borderColor = '#f3f4f6';
        btn.dataset.action = 'register';
        btn.textContent = 'Register';
        btn.style.background = '#B11226';
        btn.style.color = '#fff';
        btn.style.border = 'none';
        var badge = card.querySelector('.event-registered-badge');
        if (badge) badge.remove();
        if (card.dataset.closingSoon === '1' && badgeRow && !badgeRow.querySelector('.event-closing-badge')) {
          var reBadge = document.createElement('span');
          reBadge.className = 'event-closing-badge inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold';
          reBadge.style.background = '#FEF3C7'; reBadge.style.color = '#92400E';
          reBadge.innerHTML = '<i data-lucide="alarm-clock" class="w-2.5 h-2.5"></i> Closing soon';
          badgeRow.appendChild(reBadge);
          lucide.createIcons();
        }
      }
      btn.disabled = false;
    })
    .catch(function () {
      btn.textContent = originalText;
      btn.disabled = false;
      alert('Network error. Please try again.');
    });
}

var travelAckModal = document.getElementById('travelAckModal');
var travelAckBackdrop = document.getElementById('travelAckBackdrop');
var travelAckCheckbox = document.getElementById('travelAckCheckbox');
var travelAckConfirmBtn = document.getElementById('travelAckConfirmBtn');
var pendingTravelBtn = null;

function openTravelAck(btn) {
  pendingTravelBtn = btn;
  document.getElementById('travelAckEventTitle').textContent = btn.dataset.title || 'This event';
  document.getElementById('travelAckLocation').textContent = btn.dataset.location || 'an off-site venue';
  travelAckCheckbox.checked = false;
  travelAckConfirmBtn.disabled = true;
  travelAckModal.classList.remove('hidden'); travelAckModal.classList.add('flex');
  travelAckBackdrop.classList.remove('hidden');
}
function closeTravelAck() {
  pendingTravelBtn = null;
  travelAckModal.classList.add('hidden'); travelAckModal.classList.remove('flex');
  travelAckBackdrop.classList.add('hidden');
}
travelAckCheckbox.addEventListener('change', function () { travelAckConfirmBtn.disabled = !travelAckCheckbox.checked; });
document.getElementById('travelAckCancelBtn').addEventListener('click', closeTravelAck);
document.getElementById('travelAckCloseBtn').addEventListener('click', closeTravelAck);
travelAckBackdrop.addEventListener('click', closeTravelAck);
travelAckConfirmBtn.addEventListener('click', function () {
  var btn = pendingTravelBtn;
  closeTravelAck();
  if (btn) performEventRsvp(btn, true);
});

document.querySelectorAll('.event-register-btn').forEach(function (btn) {
  btn.addEventListener('click', function (e) {
    e.stopPropagation();
    if (btn.dataset.action === 'register' && btn.dataset.requiresTravel === '1') {
      openTravelAck(btn);
      return;
    }
    performEventRsvp(btn, false);
  });
});

// Hero rotation — auto-advance, pause on hover, click dots to jump
(function () {
  var slides = document.querySelectorAll('.hero-slide');
  var copySlides = document.querySelectorAll('.hero-copy-slide');
  var dots = document.querySelectorAll('.hero-dot');
  var heroSection = document.getElementById('heroSection');
  if (!slides.length) return;
  var idx = 0, timer = null, zTop = 1;
  function show(n) {
    idx = n;
    // Only the incoming slide gets faded in (on top, via a rising z-index);
    // previously-shown slides are left at is-active/opacity:1 underneath so
    // combined coverage never dips below 100% mid-transition (see CSS note).
    slides[idx].style.zIndex = ++zTop;
    slides[idx].classList.add('is-active');
    copySlides.forEach(function (s, i) { s.classList.toggle('is-active', i === idx); });
    dots.forEach(function (d, i) { d.classList.toggle('is-active', i === idx); });
    lucide.createIcons();
  }
  function next() { show((idx + 1) % slides.length); }
  function start() { timer = setInterval(next, 6500); }
  function stop() { clearInterval(timer); }
  dots.forEach(function (d) { d.addEventListener('click', function () { show(parseInt(d.dataset.dot, 10)); stop(); start(); }); });
  heroSection.addEventListener('mouseenter', stop);
  heroSection.addEventListener('mouseleave', start);
  start();
})();

</script>
<script src="<?= APP_URL ?>/assets/js/modern.js"></script>

</body>
</html>
