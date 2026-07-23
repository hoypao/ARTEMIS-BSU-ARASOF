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
  @keyframes spartan-bounce { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-4px); } }
</style>
</head>
<body class="min-h-screen" style="background:#F5F5F5;">

<nav class="sticky top-0 z-30 bg-white/95 backdrop-blur-md border-b border-gray-100" style="box-shadow: 0 1px 8px rgba(0,0,0,0.06);">
  <div class="max-w-3xl mx-auto px-4 sm:px-6 flex items-center h-16 gap-3">
    <a href="<?= e(APP_URL) ?>" class="flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-800 transition-colors">
      <i data-lucide="chevron-left" class="w-4 h-4"></i> Back
    </a>
  </div>
</nav>

<div class="max-w-3xl mx-auto px-4 sm:px-6 py-8 sm:py-12">
  <div class="text-center mb-6">
    <div class="w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-4" style="background: rgba(212,175,55,0.15); border:1px solid rgba(212,175,55,0.25);">
      <i data-lucide="sparkles" class="w-6 h-6" style="color:#D4AF37;"></i>
    </div>
    <h1 class="text-2xl font-bold mb-2" style="color:#1a1a2e;">Ask Spartan</h1>
    <p class="text-sm text-gray-500 max-w-sm mx-auto leading-relaxed">Your ARTEMIS assistant for applications, auditions, stipends, PATHFit exemptions, BANTOG recognition, and OCA contact info.</p>
  </div>

  <div class="ask-spartan-root spartan-static">
    <div class="spartan-panel flex flex-col overflow-hidden rounded-2xl mx-auto" style="width:100%; max-width:480px; height:min(560px, 70dvh); background:#fff; box-shadow: 0 24px 60px rgba(0,0,0,0.18), 0 0 0 1px rgba(0,0,0,0.06);">
      <div class="flex items-center justify-between px-4 py-3 flex-shrink-0" style="background:#1a1a2e; border-bottom:1px solid rgba(212,175,55,0.15);">
        <div class="flex items-center gap-2.5">
          <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0" style="background: rgba(212,175,55,0.15); border:1px solid rgba(212,175,55,0.25);">
            <i data-lucide="sparkles" class="w-3.5 h-3.5" style="color:#D4AF37;"></i>
          </div>
          <div>
            <div class="text-white text-sm font-semibold leading-none mb-0.5">Spartan</div>
            <div class="text-xs" style="color: rgba(255,255,255,0.45);">ARTEMIS AI Assistant</div>
          </div>
        </div>
      </div>

      <div class="spartan-messages flex-1 overflow-y-auto px-4 py-4 flex flex-col gap-3" style="background:#F8F9FB; min-height:0;">
        <div class="spartan-typing hidden flex gap-2">
          <div class="w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0" style="background:#1a1a2e;">
            <i data-lucide="sparkles" class="w-3 h-3" style="color:#D4AF37;"></i>
          </div>
          <div class="px-3 py-2.5 rounded-2xl bg-white border border-gray-200 flex items-center gap-1" style="border-bottom-left-radius:4px;">
            <span class="w-1.5 h-1.5 rounded-full bg-gray-300" style="animation: spartan-bounce 0.6s infinite ease-in-out;"></span>
            <span class="w-1.5 h-1.5 rounded-full bg-gray-300" style="animation: spartan-bounce 0.6s infinite ease-in-out 0.12s;"></span>
            <span class="w-1.5 h-1.5 rounded-full bg-gray-300" style="animation: spartan-bounce 0.6s infinite ease-in-out 0.24s;"></span>
          </div>
        </div>
        <div class="spartan-end"></div>
      </div>

      <div class="px-3 py-2 flex gap-1.5 overflow-x-auto flex-shrink-0 border-t border-gray-100 bg-white">
        <button type="button" class="spartan-suggestion flex-shrink-0 px-3 py-1.5 rounded-full text-xs font-medium border border-gray-200 text-gray-600 hover:border-gray-400 hover:text-gray-900 transition-colors whitespace-nowrap bg-white">How do I apply?</button>
        <button type="button" class="spartan-suggestion flex-shrink-0 px-3 py-1.5 rounded-full text-xs font-medium border border-gray-200 text-gray-600 hover:border-gray-400 hover:text-gray-900 transition-colors whitespace-nowrap bg-white">Track application</button>
        <button type="button" class="spartan-suggestion flex-shrink-0 px-3 py-1.5 rounded-full text-xs font-medium border border-gray-200 text-gray-600 hover:border-gray-400 hover:text-gray-900 transition-colors whitespace-nowrap bg-white">Stipend info</button>
        <button type="button" class="spartan-suggestion flex-shrink-0 px-3 py-1.5 rounded-full text-xs font-medium border border-gray-200 text-gray-600 hover:border-gray-400 hover:text-gray-900 transition-colors whitespace-nowrap bg-white">What is BANTOG?</button>
        <button type="button" class="spartan-suggestion flex-shrink-0 px-3 py-1.5 rounded-full text-xs font-medium border border-gray-200 text-gray-600 hover:border-gray-400 hover:text-gray-900 transition-colors whitespace-nowrap bg-white">Contact OCA</button>
      </div>

      <div class="flex items-center gap-2 px-3 py-2.5 border-t border-gray-100 bg-white flex-shrink-0">
        <label for="spartanChatInput" class="sr-only">Ask a question</label>
        <input type="text" id="spartanChatInput" class="spartan-input flex-1 px-3 py-2 rounded-xl text-xs border border-gray-200 focus:outline-none bg-gray-50 focus:border-gray-400 transition-colors" placeholder="Ask a question..." style="font-family:inherit;">
        <button type="button" class="spartan-send w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 transition-colors" style="background:#E5E7EB;" disabled aria-label="Send message">
          <i data-lucide="send" class="w-3.5 h-3.5" style="color:#9CA3AF;"></i>
        </button>
      </div>
    </div>
  </div>
</div>

<script src="<?= APP_URL ?>/assets/js/ask-spartan.js"></script>
<script>lucide.createIcons();</script>

</body>
</html>
