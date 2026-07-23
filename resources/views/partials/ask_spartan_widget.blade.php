<!-- Ask Spartan floating widget -->
<div class="ask-spartan-root">
  <button type="button" class="spartan-fab fixed bottom-20 right-4 md:bottom-6 md:right-6 z-50 flex items-center justify-center gap-2 w-12 h-12 md:w-auto md:h-auto md:px-4 md:py-2.5 rounded-full text-white text-xs font-semibold transition-transform hover:scale-105 active:scale-95"
    style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); box-shadow: 0 4px 20px rgba(0,0,0,0.25), 0 0 0 1px rgba(212,175,55,0.2);" aria-label="Open Ask Spartan chat">
    <i data-lucide="sparkles" class="w-4 h-4 md:w-3.5 md:h-3.5" style="color:#D4AF37;"></i>
    <span class="hidden md:inline">Ask Spartan</span>
  </button>

  <div class="spartan-backdrop fixed inset-0 z-40 md:hidden hidden" style="background: rgba(0,0,0,0.3);"></div>

  <div class="spartan-panel hidden fixed z-50 flex flex-col overflow-hidden rounded-2xl"
    style="bottom:5rem; right:1rem; left:1rem; max-width:360px; margin-left:auto; height:min(500px, calc(100dvh - 140px)); background:#fff; box-shadow: 0 24px 60px rgba(0,0,0,0.18), 0 0 0 1px rgba(0,0,0,0.06);">

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
      <button type="button" class="spartan-close w-10 h-10 rounded-lg flex items-center justify-center transition-colors hover:bg-white/10 flex-shrink-0" aria-label="Close Ask Spartan">
        <i data-lucide="x" class="w-4 h-4" style="color: rgba(255,255,255,0.6);"></i>
      </button>
    </div>

    <div class="spartan-messages flex-1 overflow-y-auto px-4 py-4 flex flex-col gap-3" style="background:#F8F9FB; min-height:0;">
      <div class="spartan-typing hidden flex gap-2">
        <div class="w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0" style="background:#1a1a2e;">
          <i data-lucide="sparkles" class="w-3 h-3" style="color:#D4AF37;"></i>
        </div>
        <div class="px-3 py-2.5 rounded-2xl bg-white border border-gray-200 flex items-center gap-1" style="border-bottom-left-radius:4px;">
          <span class="w-1.5 h-1.5 rounded-full bg-gray-300 spartan-dot" style="animation: spartan-bounce 0.6s infinite ease-in-out;"></span>
          <span class="w-1.5 h-1.5 rounded-full bg-gray-300 spartan-dot" style="animation: spartan-bounce 0.6s infinite ease-in-out 0.12s;"></span>
          <span class="w-1.5 h-1.5 rounded-full bg-gray-300 spartan-dot" style="animation: spartan-bounce 0.6s infinite ease-in-out 0.24s;"></span>
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

<style>
@keyframes spartan-bounce { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-4px); } }
</style>
<script src="<?= APP_URL ?>/assets/js/ask-spartan.js"></script>
