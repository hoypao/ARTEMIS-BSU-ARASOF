<?php
/**
 * Full-page loading screen. Included at the very top of <body> on the
 * landing page, login page, and both dashboards — right after <body> opens,
 * so it's opaque and covering content from the very first paint. It must
 * never be invisible-by-default: an invisible overlay still lets the real
 * page underneath become visible while the overlay itself fades in, which
 * defeats the whole point of a *loading* screen.
 *
 * Hides on the browser's real window.load event (fires once everything —
 * images, iframes, fonts — has actually finished loading), with a 5s safety
 * cap in case a slow third-party resource stalls that event. Near-instant
 * loads (e.g. a redirect back to login.php after an invalid password, a
 * ~250ms round trip) get a quick fade instead of the full experience, so a
 * fast action doesn't get dragged out by a lingering animation.
 */
?>
<div id="pageLoadingScreen" style="position:fixed; inset:0; z-index:9999; background:#ffffff; display:flex; align-items:center; justify-content:center; transition:opacity 0.18s cubic-bezier(0.16,1,0.3,1);">
  <img src="<?= e(APP_URL) ?>/assets/images/batstateu-redspartan.png" alt="Loading ARTEMIS" class="loading-shield" style="width:140px; height:auto; animation: loading-pulse 1.8s ease-in-out infinite; transition: transform 0.18s cubic-bezier(0.16,1,0.3,1), opacity 0.18s cubic-bezier(0.16,1,0.3,1);">
</div>
<style>
  @keyframes loading-pulse { 0%, 100% { transform:scale(1); } 50% { transform:scale(1.04); } }
  #pageLoadingScreen.is-hidden { opacity:0 !important; pointer-events:none; }
  #pageLoadingScreen.is-hidden .loading-shield { transform:scale(1.08) !important; opacity:0 !important; animation:none !important; }
</style>
<script>
(function () {
  var screen = document.getElementById('pageLoadingScreen');
  if (!screen) return;
  var hidden = false;
  function hideLoadingScreen() {
    if (hidden) return;
    hidden = true;
    // Hide the moment loading actually finishes — there is deliberately no
    // artificial minimum hold. This used to keep the overlay up for a full
    // 900ms on anything slower than 300ms, which made every navigation feel
    // laggy even when the page was already painted and ready.
    screen.classList.add('is-hidden');
    setTimeout(function () { if (screen && screen.parentNode) screen.parentNode.removeChild(screen); }, 180);
  }
  window.addEventListener('load', hideLoadingScreen);
  // Safety cap if a slow third-party resource stalls window.load.
  setTimeout(hideLoadingScreen, 5000);
})();
</script>
