/**
 * Exit-intent detection — opens the chat with a special offer message
 * when the user moves the mouse toward the top of the viewport (desktop).
 * Fires only once per session.
 */
(function () {
  'use strict';

  var TRIGGER_KEY = 'aiwc_exit_triggered';
  var THRESHOLD   = 20; // px from viewport top

  if (localStorage.getItem(TRIGGER_KEY)) return;
  if (!window.AIWC) return;

  document.addEventListener('mousemove', function handleMouseMove(e) {
    if (e.clientY < THRESHOLD) {
      localStorage.setItem(TRIGGER_KEY, '1');
      document.removeEventListener('mousemove', handleMouseMove);

      setTimeout(function () {
        if (!document.getElementById('aiwc-chat-window') ||
            document.getElementById('aiwc-chat-window').style.display !== 'none') {
          return;
        }
        window.AIWC.open();
        window.AIWC.sendMessage('I was about to leave — do you have any special offers?');
      }, 300);
    }
  });
}());
