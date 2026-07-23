/* Ask Spartan â€” thin client that renders replies from the server-side
   assistant (includes/chatbot.php via actions/misc/chat_action.php), which
   does fuzzy topic matching and personalizes replies for logged-in students
   using their real application records. Derives its own base URL from the
   <script> tag that loaded it, so it works on any page without that page
   having to define a global APP_URL first. */
(function () {
  var currentScript = document.currentScript;
  var APP_BASE = currentScript ? currentScript.src.replace(/\/assets\/js\/ask-spartan\.js.*$/, '') : '';

  var SUGGESTIONS = ["How do I apply?", "Track application", "Stipend info", "What is BANTOG?", "Contact OCA"];

  function fetchBotResponse(input) {
    return fetch(APP_BASE + '/chat', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ message: input }),
    }).then(function (r) {
      return r.json();
    }).then(function (data) {
      return data.reply || "I'm not sure about that. Type help to see all topics I can assist with.";
    }).catch(function () {
      return "Sorry, I couldn't reach the ARTEMIS assistant just now. Please try again, or contact OCA at oca.arasof@g.batstate-u.edu.ph.";
    });
  }

  function escapeHtml(s) {
    return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  }

  function formatText(text) {
    return text.split('\n').map(function (line) {
      var parts = line.split(/\*\*(.*?)\*\*/g);
      var html = '';
      for (var j = 0; j < parts.length; j++) {
        html += (j % 2 === 1) ? '<strong class="font-semibold">' + escapeHtml(parts[j]) + '</strong>' : escapeHtml(parts[j]);
      }
      return html;
    }).join('<br>');
  }

  function getTime() {
    return new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
  }

  function initSpartan(root) {
    var messagesEl = root.querySelector('.spartan-messages');
    var endRef = root.querySelector('.spartan-end');
    var inputEl = root.querySelector('.spartan-input');
    var sendBtn = root.querySelector('.spartan-send');
    var typingEl = root.querySelector('.spartan-typing');
    var messages = [];
    var typing = false;

    function renderMessage(msg) {
      var isUser = msg.role === 'user';
      var wrap = document.createElement('div');
      wrap.className = 'flex gap-2 ' + (isUser ? 'flex-row-reverse' : 'flex-row');
      wrap.innerHTML =
        '<div class="w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5" style="background:' + (isUser ? '#E5E7EB' : '#1a1a2e') + ';">' +
          (isUser
            ? '<i data-lucide="user" class="w-3 h-3 text-gray-500"></i>'
            : '<i data-lucide="sparkles" class="w-3 h-3" style="color:#D4AF37;"></i>') +
        '</div>' +
        '<div class="flex flex-col gap-0.5 max-w-[80%] ' + (isUser ? 'items-end' : 'items-start') + '">' +
          '<div class="px-3 py-2 rounded-2xl text-xs leading-relaxed" style="' + (isUser
            ? 'background:#1a1a2e;color:#fff;border-bottom-right-radius:4px;'
            : 'background:#fff;color:#1a1a2e;border:1px solid #E5E7EB;border-bottom-left-radius:4px;box-shadow:0 1px 3px rgba(0,0,0,0.05);') + '">' +
            formatText(msg.text) +
          '</div>' +
          '<span class="text-xs px-1" style="color:#4B5563;">' + msg.time + '</span>' +
        '</div>';
      messagesEl.insertBefore(wrap, endRef);
      if (window.lucide) window.lucide.createIcons();
    }

    function scrollToEnd() {
      setTimeout(function () {
        endRef.scrollIntoView({ behavior: 'smooth' });
        inputEl.focus();
      }, 80);
    }

    function setTyping(state) {
      typing = state;
      typingEl.classList.toggle('hidden', !state);
      sendBtn.disabled = typing || !inputEl.value.trim();
      updateSendStyle();
      if (state) scrollToEnd();
    }

    function updateSendStyle() {
      var hasText = !!inputEl.value.trim();
      sendBtn.disabled = !hasText || typing;
      sendBtn.style.background = hasText ? '#1a1a2e' : '#E5E7EB';
      var icon = sendBtn.querySelector('i');
      if (icon) icon.style.color = hasText ? '#D4AF37' : '#9CA3AF';
    }

    function sendMessage(text) {
      text = (text || '').trim();
      if (!text) return;
      messages.push({ role: 'user', text: text, time: getTime() });
      renderMessage(messages[messages.length - 1]);
      inputEl.value = '';
      updateSendStyle();
      scrollToEnd();
      setTyping(true);
      fetchBotResponse(text).then(function (reply) {
        messages.push({ role: 'bot', text: reply, time: getTime() });
        renderMessage(messages[messages.length - 1]);
        setTyping(false);
        scrollToEnd();
      });
    }

    root.querySelectorAll('.spartan-suggestion').forEach(function (btn) {
      btn.addEventListener('click', function () { sendMessage(btn.textContent); });
    });
    sendBtn.addEventListener('click', function () { sendMessage(inputEl.value); });
    inputEl.addEventListener('input', updateSendStyle);
    inputEl.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(inputEl.value); }
    });

    var fab = root.querySelector('.spartan-fab');
    var panel = root.querySelector('.spartan-panel');
    var backdrop = root.querySelector('.spartan-backdrop');
    var closeBtn = root.querySelector('.spartan-close');
    if (!root.classList.contains('spartan-static')) {
      function openPanel() {
        fab.classList.add('hidden');
        panel.classList.remove('hidden');
        backdrop.classList.remove('hidden');
        scrollToEnd();
      }
      function closePanel() {
        fab.classList.remove('hidden');
        panel.classList.add('hidden');
        backdrop.classList.add('hidden');
      }
      fab.addEventListener('click', openPanel);
      closeBtn.addEventListener('click', closePanel);
      backdrop.addEventListener('click', closePanel);
    }

    // seed the initial bot greeting
    messages.push({
      role: 'bot',
      text: "Hi, I'm **Spartan** â€” your ARTEMIS assistant.\n\nI can help with applications, auditions, stipends, PATHFit exemptions, BANTOG recognition, and OCA contact info.\n\nType your question or pick a topic below.",
      time: getTime(),
    });
    renderMessage(messages[0]);
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.ask-spartan-root').forEach(initSpartan);
  });
})();
