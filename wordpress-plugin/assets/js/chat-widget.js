/**
 * AI WooCommerce Chatbot — Main Chat Widget
 * Vanilla JavaScript, no framework dependencies.
 */
(function () {
  'use strict';

  // -------------------------------------------------------------------------
  // Configuration injected by WordPress via wp_localize_script
  // -------------------------------------------------------------------------
  var cfg = window.AIWC_Config || {};
  var REST_URL    = (cfg.restUrl  || '').replace(/\/$/, '');
  var NONCE       = cfg.nonce     || '';
  var BOT_NAME    = cfg.botName   || 'ShopBot';
  var WELCOME_MSG = cfg.welcomeMessage || 'Hi! 👋 How can I help you today?';
  var PRIMARY_CLR = cfg.primaryColor  || '#4f46e5';
  var POSITION    = cfg.position      || 'bottom-right';
  var AVATAR_URL  = cfg.avatarUrl     || '';

  // -------------------------------------------------------------------------
  // State
  // -------------------------------------------------------------------------
  var SESSION_KEY   = 'aiwc_session_id';
  var HISTORY_KEY   = 'aiwc_history';
  var OPENED_KEY    = 'aiwc_opened';
  var sessionId     = localStorage.getItem(SESSION_KEY) || generateId();
  var history       = [];
  var isOpen        = false;
  var isTyping      = false;

  localStorage.setItem(SESSION_KEY, sessionId);

  // Restore history
  try {
    var stored = localStorage.getItem(HISTORY_KEY);
    if (stored) history = JSON.parse(stored);
  } catch (e) {}

  // -------------------------------------------------------------------------
  // DOM references
  // -------------------------------------------------------------------------
  var wrapper     = document.getElementById('aiwc-wrapper');
  var toggleBtn   = document.getElementById('aiwc-toggle-btn');
  var chatWindow  = document.getElementById('aiwc-chat-window');
  var closeBtn    = document.getElementById('aiwc-close-btn');
  var messagesDiv = document.getElementById('aiwc-messages');
  var typingDiv   = document.getElementById('aiwc-typing');
  var qrDiv       = document.getElementById('aiwc-quick-replies');
  var inputEl     = document.getElementById('aiwc-input');
  var sendBtn     = document.getElementById('aiwc-send-btn');
  var badgeEl     = document.getElementById('aiwc-unread-badge');

  if (!wrapper) return; // Widget not rendered

  // -------------------------------------------------------------------------
  // Apply theme colour
  // -------------------------------------------------------------------------
  var root = document.documentElement;
  root.style.setProperty('--aiwc-primary', PRIMARY_CLR);

  // Position
  if (POSITION === 'bottom-left') {
    wrapper.classList.add('aiwc-left');
  }

  // -------------------------------------------------------------------------
  // Open / close
  // -------------------------------------------------------------------------
  toggleBtn.addEventListener('click', function () {
    isOpen ? closeChat() : openChat();
  });

  closeBtn.addEventListener('click', closeChat);

  function openChat() {
    isOpen = true;
    chatWindow.style.display = 'flex';
    toggleBtn.setAttribute('aria-expanded', 'true');
    badgeEl.style.display = 'none';
    inputEl.focus();

    // Show welcome message on very first open
    if (!localStorage.getItem(OPENED_KEY)) {
      localStorage.setItem(OPENED_KEY, '1');
      appendBotMessage(WELCOME_MSG, [
        { label: '🛍️ Browse Products',  payload: 'Show me your products' },
        { label: '📦 Track My Order',   payload: 'I want to track my order' },
        { label: '❓ FAQs',             payload: 'What are the most common questions?' },
      ]);
    } else {
      renderStoredHistory();
    }
    scrollToBottom();
  }

  function closeChat() {
    isOpen = false;
    chatWindow.style.display = 'none';
    toggleBtn.setAttribute('aria-expanded', 'false');
  }

  // -------------------------------------------------------------------------
  // Sending messages
  // -------------------------------------------------------------------------
  sendBtn.addEventListener('click', handleSend);
  inputEl.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      handleSend();
    }
  });

  function handleSend() {
    var text = inputEl.value.trim();
    if (!text || isTyping) return;
    inputEl.value = '';
    sendMessage(text);
  }

  function sendMessage(text) {
    appendUserMessage(text);
    clearQuickReplies();
    showTyping();

    var payload = {
      session_id:          sessionId,
      message:             text,
      history:             history.slice(-10),
      current_product_id:  cfg.currentProductId || null,
      cart_items:          [],
    };

    fetch(REST_URL + '/chat', {
      method:  'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce':   NONCE,
      },
      body: JSON.stringify(payload),
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        hideTyping();
        appendBotMessage(data.reply || '…', data.quick_replies || []);
        if (data.product_cards && data.product_cards.length) {
          appendProductCards(data.product_cards);
        }
        if (data.handoff) {
          appendBotMessage('Connecting you to a human agent — please hold on! 🙋', []);
        }
        // Persist history
        history.push({ role: 'user',      content: text });
        history.push({ role: 'assistant', content: data.reply || '' });
        saveHistory();
      })
      .catch(function (err) {
        hideTyping();
        appendBotMessage('Oops! Something went wrong. Please try again.', []);
        console.error('[AIWC]', err);
      });
  }

  // -------------------------------------------------------------------------
  // Message rendering helpers
  // -------------------------------------------------------------------------
  function appendUserMessage(text) {
    var div = document.createElement('div');
    div.className = 'aiwc-msg aiwc-msg-user';
    div.textContent = text;
    messagesDiv.appendChild(div);
    scrollToBottom();
  }

  function appendBotMessage(text, quickReplies) {
    var wrap = document.createElement('div');
    wrap.className = 'aiwc-msg-wrap';

    var avatar = document.createElement('img');
    avatar.src = AVATAR_URL;
    avatar.alt = BOT_NAME;
    avatar.className = 'aiwc-msg-avatar';

    var div = document.createElement('div');
    div.className = 'aiwc-msg aiwc-msg-bot';
    div.innerHTML = simpleMarkdown(text);

    wrap.appendChild(avatar);
    wrap.appendChild(div);
    messagesDiv.appendChild(wrap);

    if (quickReplies && quickReplies.length) {
      renderQuickReplies(quickReplies);
    }
    scrollToBottom();
  }

  function appendProductCards(cards) {
    var row = document.createElement('div');
    row.className = 'aiwc-product-row';

    cards.forEach(function (card) {
      var el = document.createElement('div');
      el.className = 'aiwc-product-card';
      el.innerHTML =
        '<a href="' + escAttr(card.permalink) + '" target="_blank" rel="noopener">' +
          (card.image_url
            ? '<img src="' + escAttr(card.image_url) + '" alt="' + escAttr(card.name) + '" loading="lazy" />'
            : '') +
        '</a>' +
        '<div class="aiwc-product-info">' +
          '<a href="' + escAttr(card.permalink) + '" class="aiwc-product-name" target="_blank" rel="noopener">' + escHtml(card.name) + '</a>' +
          '<span class="aiwc-product-price">' + escHtml(card.price) + '</span>' +
          (card.in_stock
            ? '<button class="aiwc-add-to-cart-btn" role="button" aria-label="Add ' + escAttr(card.name) + ' to cart" data-product-id="' + escAttr(String(card.id)) + '">🛒 Add to Cart</button>'
            : '<span class="aiwc-out-of-stock">Out of Stock</span>') +
          '<a href="' + escAttr(card.permalink) + '" class="aiwc-view-details" target="_blank" rel="noopener">View Details →</a>' +
        '</div>';

      row.appendChild(el);
    });

    messagesDiv.appendChild(row);
    scrollToBottom();

    // Wire up add-to-cart buttons
    row.querySelectorAll('.aiwc-add-to-cart-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var pid = btn.getAttribute('data-product-id');
        addToCart(pid, btn);
      });
    });
  }

  function renderQuickReplies(replies) {
    clearQuickReplies();
    replies.forEach(function (qr) {
      var btn = document.createElement('button');
      btn.className = 'aiwc-qr-btn';
      btn.textContent = qr.label;
      btn.setAttribute('role', 'button');
      btn.setAttribute('aria-label', qr.label);
      btn.addEventListener('click', function () {
        sendMessage(qr.payload);
      });
      qrDiv.appendChild(btn);
    });
  }

  function clearQuickReplies() {
    qrDiv.innerHTML = '';
  }

  // -------------------------------------------------------------------------
  // Typing indicator
  // -------------------------------------------------------------------------
  function showTyping() {
    isTyping = true;
    typingDiv.style.display = 'flex';
    typingDiv.removeAttribute('aria-hidden');
    scrollToBottom();
  }

  function hideTyping() {
    isTyping = false;
    typingDiv.style.display = 'none';
    typingDiv.setAttribute('aria-hidden', 'true');
  }

  // -------------------------------------------------------------------------
  // Add to cart via REST
  // -------------------------------------------------------------------------
  function addToCart(productId, btn) {
    btn.disabled = true;
    btn.textContent = '…';

    fetch(REST_URL + '/cart/add', {
      method:  'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce':   NONCE,
      },
      body: JSON.stringify({ product_id: parseInt(productId, 10), quantity: 1 }),
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.success) {
          btn.textContent = '✅ Added!';
          // Build the "Added to cart" message using DOM API to avoid XSS
          var cartUrl = data.cart_url || '/cart';
          var msgText = 'Added to your cart! 🎉 ';
          var link = document.createElement('a');
          link.href = cartUrl;
          link.textContent = 'View Cart →';
          link.target = '_blank';
          link.rel = 'noopener noreferrer';
          var wrap = document.createElement('div');
          wrap.className = 'aiwc-msg-wrap';
          var avatarEl = document.createElement('img');
          avatarEl.src = AVATAR_URL;
          avatarEl.alt = BOT_NAME;
          avatarEl.className = 'aiwc-msg-avatar';
          var msgDiv = document.createElement('div');
          msgDiv.className = 'aiwc-msg aiwc-msg-bot';
          msgDiv.textContent = msgText;
          msgDiv.appendChild(link);
          wrap.appendChild(avatarEl);
          wrap.appendChild(msgDiv);
          messagesDiv.appendChild(wrap);
          scrollToBottom();
        } else {
          btn.textContent = '❌ Failed';
          btn.disabled = false;
        }
      })
      .catch(function () {
        btn.textContent = '❌ Error';
        btn.disabled = false;
      });
  }

  // -------------------------------------------------------------------------
  // History persistence
  // -------------------------------------------------------------------------
  function saveHistory() {
    try {
      localStorage.setItem(HISTORY_KEY, JSON.stringify(history.slice(-20)));
    } catch (e) {}
  }

  function renderStoredHistory() {
    if (!messagesDiv.children.length) {
      history.forEach(function (msg) {
        if (msg.role === 'user') {
          appendUserMessage(msg.content);
        } else if (msg.role === 'assistant') {
          appendBotMessage(msg.content, []);
        }
      });
    }
  }

  // -------------------------------------------------------------------------
  // Utilities
  // -------------------------------------------------------------------------
  function scrollToBottom() {
    setTimeout(function () { messagesDiv.scrollTop = messagesDiv.scrollHeight; }, 50);
  }

  function generateId() {
    return 'aiwc-' + Math.random().toString(36).slice(2) + Date.now().toString(36);
  }

  function escHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function escAttr(str) {
    return escHtml(str);
  }

  /**
   * Convert a small subset of Markdown to safe HTML for bot messages.
   * Supports: **bold**, *italic*, [text](url) links, and newlines.
   * All text content is escaped before transformation so no user-supplied
   * HTML can leak through.
   */
  function simpleMarkdown(text) {
    // Escape all HTML first to prevent XSS
    var safe = escHtml(String(text));
    // **bold**
    safe = safe.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
    // *italic* — use negative lookahead/lookbehind to skip double-asterisk bold markers
    safe = safe.replace(/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/g, '<em>$1</em>');
    // [text](url) — only allow http/https URLs
    safe = safe.replace(
      /\[([^\]]+)\]\((https?:\/\/[^)]+)\)/g,
      '<a href="$2" target="_blank" rel="noopener noreferrer">$1</a>'
    );
    // Line breaks
    safe = safe.replace(/\n/g, '<br>');
    return safe;
  }

  // -------------------------------------------------------------------------
  // Expose for exit-intent / cart-watcher integrations
  // -------------------------------------------------------------------------
  window.AIWC = {
    open:        openChat,
    close:       closeChat,
    sendMessage: sendMessage,
    showBadge:   function () { badgeEl.style.display = 'flex'; },
  };

}());
