/* MinhHuyDev Portfolio v2026 — main.js */
(function () {
    'use strict';

    document.documentElement.classList.remove('is-loading');

    /* ---------- Theme toggle ---------- */
    const STORAGE_KEY = 'portfolio-theme';
    const themeMeta = document.getElementById('theme-color-meta');
    const themeBtn = document.querySelector('[data-theme-toggle]');

    function applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        document.documentElement.setAttribute('data-theme-source', 'user');
        if (themeMeta) themeMeta.setAttribute('content', theme === 'dark' ? '#0a0d18' : '#f6f7fb');
        try { localStorage.setItem(STORAGE_KEY, theme); } catch (_) {}
        if (themeBtn) themeBtn.setAttribute('aria-pressed', String(theme === 'dark'));
    }

    if (themeBtn) {
        themeBtn.addEventListener('click', function () {
            const current = document.documentElement.getAttribute('data-theme') || 'dark';
            applyTheme(current === 'dark' ? 'light' : 'dark');
        });
    }

    /* ---------- Mobile menu ---------- */
    const menuBtn = document.querySelector('[data-menu-toggle]');
    const navLinks = document.getElementById('navLinks');

    if (menuBtn && navLinks) {
        const closeMenu = function () {
            navLinks.classList.remove('is-open');
            menuBtn.setAttribute('aria-expanded', 'false');
        };
        menuBtn.addEventListener('click', function () {
            const open = navLinks.classList.toggle('is-open');
            menuBtn.setAttribute('aria-expanded', String(open));
        });
        navLinks.querySelectorAll('a').forEach(function (a) {
            a.addEventListener('click', closeMenu);
        });
        document.addEventListener('click', function (e) {
            if (!navLinks.contains(e.target) && !menuBtn.contains(e.target)) closeMenu();
        });
    }

    /* ---------- Reveal on scroll ---------- */
    const revealEls = document.querySelectorAll('[data-reveal]');
    if ('IntersectionObserver' in window && revealEls.length) {
        const io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    io.unobserve(entry.target);
                }
            });
        }, { rootMargin: '-40px 0px', threshold: 0.08 });
        revealEls.forEach(function (el) { io.observe(el); });
    } else {
        revealEls.forEach(function (el) { el.classList.add('is-visible'); });
    }

    /* ---------- Skill bars + percent counter ---------- */
    const skillCards = document.querySelectorAll('.skill-card');
    if ('IntersectionObserver' in window && skillCards.length) {
        const so = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                const card = entry.target;
                card.classList.add('is-visible');

                const pctEl = card.querySelector('.skill-card__pct');
                if (pctEl) {
                    const target = parseInt(pctEl.getAttribute('data-target') || '0', 10);
                    const duration = 1100;
                    const startTime = performance.now();
                    const tick = function (now) {
                        const t = Math.min(1, (now - startTime) / duration);
                        const eased = 1 - Math.pow(1 - t, 3);
                        pctEl.textContent = Math.round(target * eased) + '%';
                        if (t < 1) requestAnimationFrame(tick);
                    };
                    requestAnimationFrame(tick);
                }

                so.unobserve(card);
            });
        }, { threshold: 0.25 });
        skillCards.forEach(function (c) { so.observe(c); });
    }

    /* ---------- Subtle parallax on hero card ---------- */
    const heroCard = document.querySelector('.hero__card');
    if (heroCard && window.matchMedia('(hover: hover)').matches) {
        const max = 6;
        heroCard.addEventListener('mousemove', function (e) {
            const rect = heroCard.getBoundingClientRect();
            const x = ((e.clientX - rect.left) / rect.width - 0.5) * 2;
            const y = ((e.clientY - rect.top) / rect.height - 0.5) * 2;
            heroCard.style.transform = 'perspective(900px) rotateX(' + (-y * max) + 'deg) rotateY(' + (x * max) + 'deg)';
        });
        heroCard.addEventListener('mouseleave', function () {
            heroCard.style.transform = '';
        });
    }

    /* ---------- Page loader ---------- */
    const hideLoader = function () {
        document.body.classList.add('is-loaded');
    };
    if (document.readyState === 'complete') {
        setTimeout(hideLoader, 250);
    } else {
        window.addEventListener('load', function () { setTimeout(hideLoader, 250); });
        setTimeout(hideLoader, 2500); // safety net
    }

    /* ---------- Scroll spy: active nav link ---------- */
    const navLinkEls = document.querySelectorAll('[data-nav-link]');
    const sections = [];
    navLinkEls.forEach(function (a) {
        const id = (a.getAttribute('href') || '').replace('#', '');
        const sec = id ? document.getElementById(id) : null;
        if (sec) sections.push({ link: a, section: sec });
    });

    if (sections.length && 'IntersectionObserver' in window) {
        const setActive = function (id) {
            sections.forEach(function (item) {
                item.link.classList.toggle('is-active', item.section.id === id);
            });
        };
        const spy = new IntersectionObserver(function (entries) {
            const visible = entries
                .filter(function (e) { return e.isIntersecting; })
                .sort(function (a, b) { return b.intersectionRatio - a.intersectionRatio; });
            if (visible.length) setActive(visible[0].target.id);
        }, { rootMargin: '-45% 0px -45% 0px', threshold: [0, 0.25, 0.5, 1] });
        sections.forEach(function (item) { spy.observe(item.section); });
    }

    /* ---------- Back to top ---------- */
    const toTopBtn = document.querySelector('[data-to-top]');
    if (toTopBtn) {
        const onScroll = function () {
            if (window.scrollY > 500) toTopBtn.removeAttribute('hidden');
            else toTopBtn.setAttribute('hidden', '');
        };
        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();
        toTopBtn.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    /* ---------- Copy to clipboard + toast ---------- */
    const toastEl = document.querySelector('[data-toast]');
    let toastTimer = null;
    const showToast = function (msg) {
        if (!toastEl) return;
        toastEl.textContent = msg;
        toastEl.removeAttribute('hidden');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function () {
            toastEl.setAttribute('hidden', '');
        }, 2200);
    };

    document.querySelectorAll('[data-copy]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const text = btn.getAttribute('data-copy') || '';
            const done = function () {
                btn.classList.add('is-copied');
                const label = btn.querySelector('[data-copy-label]');
                const original = label ? label.textContent : null;
                if (label) label.textContent = 'Đã copy!';
                showToast('Đã sao chép: ' + text);
                setTimeout(function () {
                    btn.classList.remove('is-copied');
                    if (label && original) label.textContent = original;
                }, 1800);
            };
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(done).catch(function () {
                    fallbackCopy(text); done();
                });
            } else {
                fallbackCopy(text); done();
            }
        });
    });

    function fallbackCopy(text) {
        const ta = document.createElement('textarea');
        ta.value = text; ta.setAttribute('readonly', '');
        ta.style.position = 'absolute'; ta.style.left = '-9999px';
        document.body.appendChild(ta);
        ta.select();
        try { document.execCommand('copy'); } catch (_) {}
        document.body.removeChild(ta);
    }
})();
