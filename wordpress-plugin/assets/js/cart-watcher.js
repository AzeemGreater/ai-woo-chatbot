/**
 * Cart Abandonment Watcher
 * If the customer has items in their WooCommerce cart and is inactive for
 * INACTIVITY_MS milliseconds, nudge them via the chat widget.
 */
(function () {
  'use strict';

  var INACTIVITY_MS = 3 * 60 * 1000; // 3 minutes
  var NUDGE_KEY     = 'aiwc_cart_nudge';
  var timer         = null;

  // Only activate when there are WooCommerce cart items (detected via cookie)
  function hasCartItems() {
    return document.cookie.indexOf('woocommerce_cart_hash') !== -1 ||
           document.cookie.indexOf('wp_woocommerce_session_') !== -1;
  }

  function resetTimer() {
    clearTimeout(timer);
    if (!hasCartItems()) return;
    timer = setTimeout(nudge, INACTIVITY_MS);
  }

  function nudge() {
    if (sessionStorage.getItem(NUDGE_KEY)) return;
    if (!window.AIWC) return;

    sessionStorage.setItem(NUDGE_KEY, '1');
    window.AIWC.showBadge();

    var chatWindow = document.getElementById('aiwc-chat-window');
    if (chatWindow && chatWindow.style.display === 'none') {
      window.AIWC.open();
      window.AIWC.sendMessage('I noticed you have items in your cart — would you like help completing your order? 🛒');
    }
  }

  if (hasCartItems()) {
    ['mousemove', 'keydown', 'scroll', 'touchstart'].forEach(function (event) {
      document.addEventListener(event, resetTimer, { passive: true });
    });
    resetTimer();
  }
}());
