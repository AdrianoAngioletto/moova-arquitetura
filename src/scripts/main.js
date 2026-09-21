/* MOVA Arquitetura */
(function () {
  'use strict';

  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var $  = function (s, c) { return (c || document).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };

  // Scroll
  var scrollHandlers = [];
  var ticking = false;

  function onScroll(fn) {
    scrollHandlers.push(fn);
    fn(window.scrollY);
  }

  window.addEventListener('scroll', function () {
    if (ticking) return;
    ticking = true;
    requestAnimationFrame(function () {
      var y = window.scrollY;
      for (var i = 0; i < scrollHandlers.length; i++) scrollHandlers[i](y);
      ticking = false;
    });
  }, { passive: true });

  // Preloader
  (function preloader() {
    var loader = $('#loader');
    var count  = $('#loaderCount');
    if (!loader) return;

    var MIN_VISIBLE = reduced ? 0 : 1700; // tempo do traço do M (1.6s) + folga
    var start = performance.now();

    var n = 0;
    var tick = setInterval(function () {
      n = Math.min(n + Math.ceil(Math.random() * 7), 99);
      if (count) count.textContent = n;
    }, 60);

    function finish() {
      clearInterval(tick);
      if (count) count.textContent = '100';
      setTimeout(function () {
        loader.classList.add('is-done');
        document.body.classList.remove('is-locked');
      }, reduced ? 0 : 420);
    }

    function done() {
      var wait = Math.max(0, MIN_VISIBLE - (performance.now() - start));
      setTimeout(finish, wait);
    }

    document.body.classList.add('is-locked');
    if (document.readyState === 'complete') done();
    else window.addEventListener('load', done);
    setTimeout(done, 4500);
  })();

  // Header
  (function header() {
    var el = $('#header');
    if (!el) return;
    var last = 0;

    onScroll(function (y) {
      el.classList.toggle('is-stuck', y > 40);
      el.classList.toggle('is-hidden', y > 420 && y > last && !$('#nav').classList.contains('is-open'));
      last = y;
    });
  })();

  // Menu mobile
  (function menu() {
    var burger = $('#burger');
    var nav    = $('#nav');
    if (!burger || !nav) return;

    function close() {
      nav.classList.remove('is-open');
      burger.setAttribute('aria-expanded', 'false');
      burger.setAttribute('aria-label', 'Abrir menu');
      document.body.classList.remove('is-locked');
    }

    burger.addEventListener('click', function () {
      var open = nav.classList.toggle('is-open');
      burger.setAttribute('aria-expanded', String(open));
      burger.setAttribute('aria-label', open ? 'Fechar menu' : 'Abrir menu');
      document.body.classList.toggle('is-locked', open);
    });

    $$('a', nav).forEach(function (a) { a.addEventListener('click', close); });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && nav.classList.contains('is-open')) close();
    });
  })();

  // Slideshow do hero
  (function heroSlides() {
    var slides = $$('.hero__slide', $('#heroBg'));
    if (slides.length < 2) return;

    var hero  = $('.hero');
    var title = $('#heroTitle');
    var desc  = $('#heroDesc');
    var current = 0;

    setInterval(function () {
      slides[current].classList.remove('is-active');
      current = (current + 1) % slides.length;
      var next = slides[current];
      next.classList.add('is-active');

      if (title) title.classList.add('is-swapping');
      if (desc)  desc.classList.add('is-swapping');

      setTimeout(function () {
        if (title && next.dataset.title) title.textContent = next.dataset.title;
        if (desc  && next.dataset.desc)  desc.textContent  = next.dataset.desc;
        if (title) title.classList.remove('is-swapping');
        if (desc)  desc.classList.remove('is-swapping');
        if (hero)  hero.classList.toggle('is-align-right', next.dataset.align === 'right');
      }, 450);
    }, 6000);
  })();

  (function reveal() {
    var items = $$('.reveal');
    if (!items.length) return;

    if (reduced || !('IntersectionObserver' in window)) {
      items.forEach(function (el) { el.classList.add('is-in'); });
      return;
    }

    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        var el = entry.target;
        var siblings = el.parentElement ? $$('.reveal', el.parentElement) : [];
        var i = Math.max(0, siblings.indexOf(el));
        el.style.transitionDelay = Math.min(i * 90, 450) + 'ms';
        el.classList.add('is-in');
        io.unobserve(el);
      });
    }, { rootMargin: '0px 0px -12% 0px', threshold: 0.08 });

    items.forEach(function (el) { io.observe(el); });
  })();

  // Contadores
  (function counters() {
    var nums = $$('[data-count]');
    if (!nums.length) return;

    function run(el) {
      var target = parseFloat(el.dataset.count) || 0;
      var suffix = el.dataset.suffix || '';
      if (reduced) { el.textContent = target + suffix; return; }

      var start = performance.now();
      var dur = 1500;
      (function step(now) {
        var p = Math.min((now - start) / dur, 1);
        var eased = 1 - Math.pow(1 - p, 3);
        el.textContent = Math.round(target * eased) + suffix;
        if (p < 1) requestAnimationFrame(step);
      })(start);
    }

    if (!('IntersectionObserver' in window)) { nums.forEach(run); return; }
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (!e.isIntersecting) return;
        run(e.target);
        io.unobserve(e.target);
      });
    }, { threshold: 0.5 });
    nums.forEach(function (el) { io.observe(el); });
  })();

  // Accordion de serviços
  (function accordion() {
    var acc = $('#acc');
    if (!acc) return;
    var items = $$('.acc__item', acc);

    items.forEach(function (item) {
      var head  = $('.acc__head', item);
      var panel = $('.acc__panel', item);

      head.addEventListener('click', function () {
        var isOpen = head.getAttribute('aria-expanded') === 'true';

        items.forEach(function (other) {
          var h = $('.acc__head', other), p = $('.acc__panel', other);
          h.setAttribute('aria-expanded', 'false');
          other.classList.remove('is-open');
          p.style.height = '0px';
        });

        if (!isOpen) {
          head.setAttribute('aria-expanded', 'true');
          item.classList.add('is-open');
          panel.style.height = $('.acc__body', item).offsetHeight + 'px';
        }
      });
    });

    var t;
    window.addEventListener('resize', function () {
      clearTimeout(t);
      t = setTimeout(function () {
        var open = $('.acc__item.is-open', acc);
        if (open) $('.acc__panel', open).style.height = $('.acc__body', open).offsetHeight + 'px';
      }, 160);
    });
  })();

  // Parallax leve (só transform)
  (function parallax() {
    var els = $$('[data-parallax]');
    if (!els.length || reduced) return;

    onScroll(function (y) {
      els.forEach(function (el) {
        var speed = parseFloat(el.dataset.parallax) || 0.1;
        var rect  = el.getBoundingClientRect();
        if (rect.bottom < -200 || rect.top > window.innerHeight + 200) return;
        var offset = (rect.top + window.scrollY - y) * -speed;
        el.style.transform = 'translate3d(0,' + offset.toFixed(2) + 'px,0)';
      });
    });
  })();

  // Botão whatsapp
  (function whats() {
    var el = $('.whats');
    if (!el) return;
    onScroll(function (y) { el.classList.toggle('is-visible', y > 600); });
  })();

  // Formulário
  (function form() {
    var f   = $('#form');
    if (!f) return;
    var msg = $('#formMsg');
    var btn = $('#submit');
    var btnLabel = btn ? $('span', btn).textContent : '';

    var tel = $('#telefone');
    if (tel) {
      tel.addEventListener('input', function () {
        var v = tel.value.replace(/\D/g, '').slice(0, 11);
        if (v.length > 10)      v = v.replace(/(\d{2})(\d{5})(\d{0,4})/, '($1) $2-$3');
        else if (v.length > 6)  v = v.replace(/(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
        else if (v.length > 2)  v = v.replace(/(\d{2})(\d{0,5})/, '($1) $2');
        else if (v.length)      v = v.replace(/(\d{0,2})/, '($1');
        tel.value = v;
      });
    }

    function setMsg(text, isError) {
      if (!msg) return;
      msg.textContent = text;
      msg.classList.toggle('is-error', !!isError);
    }

    function validate() {
      var ok = true;
      $$('[required]', f).forEach(function (input) {
        var field = input.closest('.field');
        var valid = input.value.trim() !== '' &&
                    (input.type !== 'email' || /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(input.value));
        if (field) field.classList.toggle('has-error', !valid);
        if (!valid) ok = false;
      });
      return ok;
    }

    $$('input,select,textarea', f).forEach(function (input) {
      input.addEventListener('input', function () {
        var field = input.closest('.field');
        if (field) field.classList.remove('has-error');
      });
    });

    f.addEventListener('submit', function (e) {
      e.preventDefault();
      setMsg('');

      if (!validate()) { setMsg('Preencha os campos obrigatórios corretamente.', true); return; }

      f.classList.add('is-sending');
      if (btn) $('span', btn).textContent = 'Enviando...';

      fetch(f.action, { method: 'POST', body: new FormData(f), headers: { 'Accept': 'application/json' } })
        .then(function (r) { return r.json().catch(function () { throw new Error('resposta inválida'); }); })
        .then(function (data) {
          if (data && data.ok) {
            f.reset();
            setMsg(data.message || 'Mensagem enviada. Retornamos em até 1 dia útil.', false);
          } else {
            setMsg((data && data.message) || 'Não foi possível enviar. Tente novamente.', true);
          }
        })
        .catch(function () {
          setMsg('Falha de conexão. Fale conosco por WhatsApp: (11) 98341-9654.', true);
        })
        .finally(function () {
          f.classList.remove('is-sending');
          if (btn) $('span', btn).textContent = btnLabel;
        });
    });
  })();

  // Trilha do formulário
  (function ambientAudio() {
    var audio = $('#ambient');
    var toggle = $('#soundToggle');
    var form = $('#form');
    if (!audio || !toggle || !form) return;

    var TARGET = 0.2;
    var FADE_MS = 900;
    var muted = localStorage.getItem('mova:muted') === '1';
    var fadeTimer = null;

    function fadeTo(target, done) {
      clearInterval(fadeTimer);
      var from = audio.volume;
      var start = performance.now();

      fadeTimer = setInterval(function () {
        var p = Math.min((performance.now() - start) / FADE_MS, 1);
        audio.volume = Math.max(0, Math.min(1, from + (target - from) * p));
        if (p === 1) {
          clearInterval(fadeTimer);
          if (done) done();
        }
      }, 40);
    }

    function showToggle() {
      toggle.hidden = false;
      requestAnimationFrame(function () { toggle.classList.add('is-visible'); });
    }

    function setPressed(on) {
      toggle.setAttribute('aria-pressed', String(on));
      toggle.setAttribute('aria-label', on ? 'Desativar música' : 'Ativar música');
    }

    var started = false;

    function begin() {
      if (muted || started) return;

      audio.volume = 0;
      var p = audio.play();

      function ok() {
        started = true;
        fadeTo(TARGET);
        showToggle();
        setPressed(true);
      }

      if (p && p.then) p.then(ok).catch(function () { started = false; });
      else ok();
    }

    $$('[data-audio-unlock]').forEach(function (el) {
      el.addEventListener('click', begin);
    });

    form.addEventListener('input', begin);
    form.addEventListener('focusin', begin);

    if ('IntersectionObserver' in window) {
      var section = $('#contato');
      if (section) {
        new IntersectionObserver(function (entries) {
          entries.forEach(function (e) {
            if (e.isIntersecting || !started || audio.paused) return;
            fadeTo(0, function () { audio.pause(); started = false; setPressed(false); });
          });
        }, { threshold: 0.05 }).observe(section);
      }
    }

    toggle.addEventListener('click', function () {
      muted = !muted;
      localStorage.setItem('mova:muted', muted ? '1' : '0');

      if (muted) {
        fadeTo(0, function () { audio.pause(); });
        setPressed(false);
      } else {
        audio.play().catch(function () {});
        fadeTo(TARGET);
        setPressed(true);
      }
    });

    document.addEventListener('visibilitychange', function () {
      if (document.hidden && !audio.paused) audio.pause();
      else if (!document.hidden && started && !muted) audio.play().catch(function () {});
    });
  })();

  // Ano no rodapé
  (function year() {
    var el = $('#year');
    if (el) el.textContent = new Date().getFullYear();
  })();

  // Filtro + busca da página de projetos
  (function projFilter() {
    var bar    = $('#projFilter');
    var grid   = $('#projGrid');
    var search = $('#projSearch');
    if (!bar || !grid) return;

    var btns  = $$('.proj-filter__btn', bar);
    var cards = $$('.proj-card', grid);
    var activeFilter = 'all';

    function apply() {
      var term = search ? search.value.trim().toLowerCase() : '';
      cards.forEach(function (card) {
        var matchesCategory = activeFilter === 'all' || card.dataset.category === activeFilter;
        var name = (card.querySelector('b') || {}).textContent || '';
        var matchesSearch = !term || name.toLowerCase().indexOf(term) !== -1;
        card.hidden = !(matchesCategory && matchesSearch);
      });
    }

    bar.addEventListener('click', function (e) {
      var btn = e.target.closest ? e.target.closest('.proj-filter__btn') : null;
      if (!btn) return;

      activeFilter = btn.dataset.filter;
      btns.forEach(function (b) { b.classList.toggle('is-active', b === btn); });
      apply();
    });

    if (search) search.addEventListener('input', apply);
  })();

})();