/* =============================================================
   SAIGON BISTRO - MAIN JAVASCRIPT
   ============================================================= */
$(document).ready(function () {

  /* ── PRELOADER ─────────────────────────────────────────── */
  setTimeout(function () {
    $('#preloader').addClass('hidden');
  }, 1200);

  /* ── HEADER SCROLL ──────────────────────────────────────── */
  $(window).on('scroll', function () {
    var scrollTop = $(this).scrollTop();
    if (scrollTop > 60) {
      $('#mainNav').addClass('scrolled');
      $('#backToTop').addClass('show');
    } else {
      $('#mainNav').removeClass('scrolled');
      $('#backToTop').removeClass('show');
    }
  });

  /* ── BACK TO TOP ────────────────────────────────────────── */
  $('#backToTop').on('click', function () {
    $('html, body').animate({ scrollTop: 0 }, 500);
  });

  /* ── FLASH AUTO HIDE ────────────────────────────────────── */
  if ($('#flashMsg').length) {
    setTimeout(function () {
      $('#flashMsg').fadeOut(400, function () { $(this).remove(); });
    }, 4000);
  }

  /* ── ANIMATE ON SCROLL ──────────────────────────────────── */
  function animateOnScroll() {
    $('[data-aos]').each(function () {
      var el = $(this);
      var elTop = el.offset().top;
      var winBottom = $(window).scrollTop() + $(window).height();
      if (elTop < winBottom - 60 && !el.hasClass('aos-animated')) {
        el.addClass('animate__animated animate__' + el.attr('data-aos') + ' aos-animated');
      }
    });
  }
  $(window).on('scroll', animateOnScroll);
  animateOnScroll();

  /* ── COUNTER ANIMATION ──────────────────────────────────── */
  function animateCounter($el) {
    var target = parseInt($el.data('target')) || 0;
    var duration = 1500;
    var step = target / (duration / 16);
    var current = 0;
    var timer = setInterval(function () {
      current += step;
      if (current >= target) { current = target; clearInterval(timer); }
      $el.text(Math.round(current).toLocaleString('vi-VN') + ($el.data('suffix') || ''));
    }, 16);
  }
  var countersStarted = false;
  $(window).on('scroll', function () {
    if (!countersStarted && $('.counter-num').length) {
      var elTop = $('.counter-num').first().offset().top;
      if ($(window).scrollTop() + $(window).height() > elTop) {
        countersStarted = true;
        $('.counter-num').each(function () { animateCounter($(this)); });
      }
    }
  });

  /* ── ADD TO CART ─────────────────────────────────────────── */
  $(document).on('click', '.btn-add-cart', function () {
    var btn = $(this);
    var id = btn.data('id');
    var name = btn.data('name');
    var price = btn.data('price');
    var img = btn.data('img') || '';

    btn.html('<i class="bi bi-check-lg"></i>').css('background', '#52b788');
    setTimeout(function () {
      btn.html('<i class="bi bi-plus-lg"></i>').css('background', '');
    }, 1000);

    $.ajax({
      url: SITE_URL + '/cart-action.php',
      method: 'POST',
      data: { action: 'add', id: id, name: name, price: price, img: img },
      dataType: 'json',
      success: function (res) {
        if (res.success) {
          var $badge = $('.cart-badge');
          if ($badge.length) {
            $badge.text(res.count).addClass('bump');
            setTimeout(function () { $badge.removeClass('bump'); }, 400);
          } else {
            $('.cart-btn').append('<span class="cart-badge">' + res.count + '</span>');
          }
          showToast('Đã thêm vào giỏ hàng!', 'success');
        }
      }
    });
  });

  /* ── QTY CONTROL (CART PAGE) ────────────────────────────── */
  $(document).on('click', '.qty-btn', function () {
    var btn = $(this);
    var id = btn.data('id');
    var action = btn.data('action');
    $.ajax({
      url: SITE_URL + '/cart-action.php',
      method: 'POST',
      data: { action: action, id: id },
      dataType: 'json',
      success: function (res) {
        if (res.success) location.reload();
      }
    });
  });

  /* ── REMOVE FROM CART ───────────────────────────────────── */
  $(document).on('click', '.remove-item', function () {
    var id = $(this).data('id');
    if (!confirm('Xóa món này khỏi giỏ hàng?')) return;
    $.ajax({
      url: SITE_URL + '/cart-action.php',
      method: 'POST',
      data: { action: 'remove', id: id },
      dataType: 'json',
      success: function (res) {
        if (res.success) location.reload();
      }
    });
  });

  /* ── MENU FILTER ─────────────────────────────────────────── */
  $(document).on('click', '.cat-btn', function () {
    var cat = $(this).data('cat');
    $('.cat-btn').removeClass('active');
    $(this).addClass('active');
    if (cat === 'all') {
      $('.dish-item').parent().fadeIn(300);
    } else {
      $('.dish-item').each(function () {
        var $wrap = $(this).closest('[data-category-col]') || $(this).parent().parent();
        if ($(this).data('cat') == cat) {
          $wrap.fadeIn(300);
        } else {
          $wrap.fadeOut(200);
        }
      });
    }
  });

  /* ── SEARCH FILTER ───────────────────────────────────────── */
  $('#searchInput').on('input', function () {
    var q = $(this).val().toLowerCase().trim();
    $('.dish-item').each(function () {
      var name = $(this).data('name').toLowerCase();
      var $parent = $(this).closest('.dish-col');
      if (!q || name.includes(q)) {
        $parent.fadeIn(200);
      } else {
        $parent.fadeOut(200);
      }
    });
  });

  /* ── AI CHATBOT ─────────────────────────────────────────── */
  var chatOpen = false;

  $('#chatbotToggle').on('click', function () {
    chatOpen = !chatOpen;
    if (chatOpen) {
      $('#chatbotPanel').addClass('open');
      $(this).find('i').removeClass('bi-robot').addClass('bi-x-lg');
    } else {
      $('#chatbotPanel').removeClass('open');
      $(this).find('i').removeClass('bi-x-lg').addClass('bi-robot');
    }
  });

  $('#chatbotClose').on('click', function () {
    chatOpen = false;
    $('#chatbotPanel').removeClass('open');
    $('#chatbotToggle i').removeClass('bi-x-lg').addClass('bi-robot');
  });

  function sendMessage(text) {
    if (!text.trim()) return;
    appendMsg('user', text);
    $('#chatbotInput').val('');
    showTyping();

    $.ajax({
      url: SITE_URL + '/chatbot.php',
      method: 'POST',
      contentType: 'application/json',
      data: JSON.stringify({ message: text }),
      dataType: 'json',
      success: function (res) {
        removeTyping();
        appendMsg('bot', res.reply || 'Xin lỗi, có lỗi xảy ra.');
      },
      error: function () {
        removeTyping();
        appendMsg('bot', 'Kết nối gặp sự cố. Vui lòng thử lại sau!');
      }
    });
  }

  function appendMsg(role, text) {
    var avatar = role === 'bot'
      ? '<div class="msg-avatar"><i class="bi bi-robot"></i></div>'
      : '<div class="msg-avatar"><i class="bi bi-person"></i></div>';
    var html = '<div class="msg ' + role + '">' + avatar + '<div class="msg-bubble">' + text + '</div></div>';
    $('#chatbotMessages').append(html);
    scrollChat();
  }

  function showTyping() {
    var html = '<div class="msg bot msg-typing" id="typingIndicator">'
      + '<div class="msg-avatar"><i class="bi bi-robot"></i></div>'
      + '<div class="msg-bubble"><div class="typing-dots"><span></span><span></span><span></span></div></div></div>';
    $('#chatbotMessages').append(html);
    scrollChat();
  }

  function removeTyping() { $('#typingIndicator').remove(); }
  function scrollChat() {
    var $msgs = $('#chatbotMessages');
    $msgs.scrollTop($msgs[0].scrollHeight);
  }

  $('#chatbotSend').on('click', function () { sendMessage($('#chatbotInput').val()); });
  $('#chatbotInput').on('keypress', function (e) { if (e.which === 13) sendMessage($(this).val()); });

  /* ── TOAST NOTIFICATION ─────────────────────────────────── */
  function showToast(msg, type) {
    var $toast = $('<div class="toast-notify toast-' + type + '">'
      + '<i class="bi bi-' + (type === 'success' ? 'check-circle' : 'exclamation-circle') + '-fill"></i> '
      + msg + '</div>');
    if (!$('#toastContainer').length) {
      $('body').append('<div id="toastContainer" style="position:fixed;bottom:90px;right:24px;z-index:9998;display:flex;flex-direction:column;gap:8px;"></div>');
    }
    $('#toastContainer').append($toast);
    setTimeout(function () { $toast.fadeOut(400, function () { $(this).remove(); }); }, 2500);
  }

  /* ── SMOOTH SCROLL ──────────────────────────────────────── */
  $('a[href^="#"]').on('click', function (e) {
    var target = $(this.getAttribute('href'));
    if (target.length) {
      e.preventDefault();
      $('html,body').animate({ scrollTop: target.offset().top - 80 }, 500);
    }
  });
});

/* ── GLOBAL: SUGGEST BTN ────────────────────────────────────── */
function sendSuggestion(btn) {
  var text = $(btn).text().replace(/^[^\w\s]+\s*/, '').trim();
  $('#chatbotInput').val(text);
  $(btn).closest('.chatbot-suggestions').fadeOut(200);
  if (typeof $ !== 'undefined') {
    setTimeout(function () { $('#chatbotSend').trigger('click'); }, 100);
  }
}

/* CSS for toast */
var toastCSS = `
.toast-notify {
  padding: 12px 18px; border-radius: 8px; font-size: 13px; font-weight: 500;
  display: flex; align-items: center; gap: 8px;
  animation: slideInRight 0.3s ease; box-shadow: 0 4px 16px rgba(0,0,0,0.4);
}
.toast-success { background: #0f2d1a; color: #52b788; border: 1px solid #1a5c33; }
.toast-error   { background: #2d0f0f; color: #e05252; border: 1px solid #5c1a1a; }
@keyframes slideInRight { from { transform: translateX(60px); opacity: 0; } }
.bump { animation: badgeBump 0.3s ease; }
@keyframes badgeBump { 0%,100% { transform: scale(1); } 50% { transform: scale(1.4); } }
`;
var styleTag = document.createElement('style');
styleTag.textContent = toastCSS;
document.head.appendChild(styleTag);
