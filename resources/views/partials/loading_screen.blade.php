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
<div id="pageLoadingScreen" style="position:fixed; inset:0; z-index:9999; background:#ffffff; display:flex; align-items:center; justify-content:center; transition:opacity 0.3s ease;">
  <img src="<?= e(APP_URL) ?>/assets/images/batstateu-redspartan.png" alt="Loading ARTEMIS" class="loading-shield" style="width:140px; height:auto; animation: loading-pulse 1.8s ease-in-out infinite; transition: transform 0.3s ease, opacity 0.3s ease;">
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
  var FAST_THRESHOLD_MS = 300;  // real loads under this feel instant — skip the lingering minimum
  var MIN_VISIBLE_MS = 900;     // genuinely slower loads stay up at least this long
  var startedAt = Date.now();
  var hidden = false;
  function finalizeHide() {
    screen.classList.add('is-hidden');
    setTimeout(function () { if (screen && screen.parentNode) screen.parentNode.removeChild(screen); }, 300);
  }
  function hideLoadingScreen() {
    if (hidden) return;
    hidden = true;
    var elapsed = Date.now() - startedAt;
    if (elapsed < FAST_THRESHOLD_MS) {
      finalizeHide();
    } else {
      var remaining = MIN_VISIBLE_MS - elapsed;
      if (remaining > 0) setTimeout(finalizeHide, remaining);
      else finalizeHide();
    }
  }
  window.addEventListener('load', hideLoadingScreen);
  setTimeout(hideLoadingScreen, 5000);
})();
</script>
