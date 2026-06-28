(() => {
            const nav = document.getElementById('navLinks');
            const menu = document.querySelector('[data-menu]');
            const topBtn = document.querySelector('[data-top]');
            const tiles = [...document.querySelectorAll('.tile')];
            const motionTargets = [...document.querySelectorAll('.motion-stack, .motion-card')];
            const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            if (!reducedMotion) {
                const glow = document.createElement('div');
                glow.className = 'global-cursor-glow';
                document.body.appendChild(glow);

                let mouseX = window.innerWidth / 2;
                let mouseY = window.innerHeight / 2;
                let glowX = mouseX;
                let glowY = mouseY;

                document.addEventListener('mousemove', (e) => {
                    mouseX = e.clientX;
                    mouseY = e.clientY;
                });

                const renderGlow = () => {
                    glowX += (mouseX - glowX) * 0.12;
                    glowY += (mouseY - glowY) * 0.12;
                    glow.style.transform = `translate(calc(${glowX}px - 50%), calc(${glowY}px - 50%))`;
                    requestAnimationFrame(renderGlow);
                };
                renderGlow();
            }

            const startIntroMotion = () => {
                if (reducedMotion) {
                    document.body.classList.add('is-ready');
                    motionTargets.forEach((item) => item.classList.add('is-inview'));
                    return;
                }

                window.setTimeout(() => {
                    document.body.classList.add('is-ready');
                }, 220);
            };

            if (menu && nav) {
                menu.addEventListener('click', () => {
                    const open = nav.classList.toggle('is-open');
                    menu.classList.toggle('is-open', open);
                    menu.setAttribute('aria-expanded', String(open));
                });

                nav.querySelectorAll('a').forEach((link) => {
                    link.addEventListener('click', () => {
                        nav.classList.remove('is-open');
                        menu.classList.remove('is-open');
                        menu.setAttribute('aria-expanded', 'false');
                    });
                });
            }

            const spyLinks = [...document.querySelectorAll('[data-spy]')];
            const sections = spyLinks
                .map((link) => ({ link, section: document.querySelector(link.getAttribute('href')) }))
                .filter((item) => item.section);

            if ('IntersectionObserver' in window && sections.length) {
                const spyObserver = new IntersectionObserver((entries) => {
                    const visible = entries
                        .filter((entry) => entry.isIntersecting)
                        .sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0];

                    if (!visible) return;
                    sections.forEach((item) => {
                        item.link.classList.toggle('is-active', item.section === visible.target);
                    });
                }, { rootMargin: '-40% 0px -50% 0px', threshold: [0, 0.25, 0.5, 1] });

                sections.forEach((item) => spyObserver.observe(item.section));
            }

            const sysClock = document.getElementById('sys-realtime');
            if (sysClock) {
                const updateClock = () => {
                    const now = new Date();
                    const mm = String(now.getMonth() + 1).padStart(2, '0');
                    const yyyy = now.getFullYear();
                    const hh = String(now.getHours()).padStart(2, '0');
                    const min = String(now.getMinutes()).padStart(2, '0');
                    sysClock.innerText = mm + '/' + yyyy + ' ' + hh + ':' + min;
                };
                setInterval(updateClock, 1000);
                updateClock();
            }

            const updateTopButton = () => {
                document.body.classList.toggle('is-scrolled', window.scrollY > 24);
                if (!topBtn) return;
                topBtn.classList.toggle('is-visible', window.scrollY > 700);
            };

            updateTopButton();
            window.addEventListener('scroll', updateTopButton, { passive: true });
            topBtn?.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));

            if ('IntersectionObserver' in window && motionTargets.length && !reducedMotion) {
                const revealObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach((entry) => {
                        if (!entry.isIntersecting) {
                            return;
                        }

                        entry.target.classList.add('is-inview');
                        


                        if (entry.target.classList.contains('quote-strip') && !reducedMotion) {
                            const strong = entry.target.querySelector('strong');
                            const quoteText = strong ? strong.querySelector('.quote-text') : null;
                            if (quoteText && !quoteText.dataset.typed) {
                                quoteText.dataset.typed = 'true';
                                strong.classList.add('is-typing');
                                const text = quoteText.textContent;
                                quoteText.textContent = '';
                                
                                const cursor = document.createElement('span');
                                cursor.textContent = '|';
                                cursor.classList.add('type-cursor', 'is-start');
                                
                                const spans = Array.from(text).map(char => {
                                    const span = document.createElement('span');
                                    span.textContent = char;
                                    span.classList.add('type-char', 'is-hidden');
                                    quoteText.appendChild(span);
                                    return span;
                                });
                                
                                if (spans.length > 0) {
                                    spans[0].classList.add('is-active');
                                    spans[0].appendChild(cursor);
                                }
                                
                                let i = 0;
                                const typeChar = () => {
                                    if (i < spans.length) {
                                        spans[i].classList.remove('is-hidden');
                                        spans[i].classList.add('is-active');
                                        
                                        cursor.classList.remove('is-start');
                                        spans[i].appendChild(cursor);
                                        
                                        if (i > 0) spans[i-1].classList.remove('is-active');
                                        
                                        i++;
                                        const nextDelay = Math.random() * 30 + 15;
                                        setTimeout(typeChar, nextDelay);
                                    } else {
                                        setTimeout(() => cursor.classList.add('is-hidden'), 2000);
                                    }
                                };
                                setTimeout(typeChar, 500);
                            }
                        }

                        if (entry.target.classList.contains('skill-card') && !reducedMotion) {
                            const percentEl = entry.target.querySelector('.skill-percent');
                            const barFill = entry.target.querySelector('.skill-bar-fill');
                            
                            if (barFill && !barFill.dataset.animated) {
                                barFill.dataset.animated = 'true';
                                setTimeout(() => {
                                    barFill.style.width = barFill.dataset.target + '%';
                                }, 100);
                            }

                            if (percentEl && !percentEl.dataset.animated) {
                                percentEl.dataset.animated = 'true';
                                const targetNumber = parseInt(percentEl.dataset.target, 10);
                                const duration = 1500;
                                const startTime = performance.now();

                                const updateCounter = (currentTime) => {
                                    const elapsed = currentTime - startTime;
                                    const progress = Math.min(elapsed / duration, 1);
                                    const easeProgress = progress === 1 ? 1 : 1 - Math.pow(2, -10 * progress);
                                    percentEl.textContent = Math.floor(easeProgress * targetNumber) + '%';

                                    if (progress < 1) {
                                        requestAnimationFrame(updateCounter);
                                    } else {
                                        percentEl.textContent = targetNumber + '%';
                                    }
                                };
                                requestAnimationFrame(updateCounter);
                            }
                        }

                        observer.unobserve(entry.target);
                    });
                }, { rootMargin: '0px 0px -12% 0px', threshold: 0.16 });

                motionTargets.forEach((item) => revealObserver.observe(item));
            } else {
                motionTargets.forEach((item) => item.classList.add('is-inview'));
            }

            if (!reducedMotion && tiles.length) {
                const randomBetween = (min, max) => Math.floor(Math.random() * (max - min + 1)) + min;

                const scheduleTileBlink = (tile) => {
                    const waitMs = randomBetween(1000, 5000);
                    window.setTimeout(() => {
                        tile.classList.add('is-blinking');
                        const blinkMs = randomBetween(220, 650);
                        window.setTimeout(() => {
                            tile.classList.remove('is-blinking');
                            scheduleTileBlink(tile);
                        }, blinkMs);
                    }, waitMs);
                };

                tiles.forEach((tile) => {
                    scheduleTileBlink(tile);
                });
            }

            // Animate numbers
            if (!reducedMotion) {
                const animateNumbers = () => {
                    const numberElements = document.querySelectorAll('.hero-proof strong');
                    numberElements.forEach((el) => {
                        const originalText = el.textContent;
                        // Extract numeric part and suffix
                        const match = originalText.match(/^(\d+)(.*)$/);
                        if (match) {
                            const targetNumber = parseInt(match[1], 10);
                            const suffix = match[2];
                            let start = 0;
                            const duration = 1500; // 1.5s
                            const startTime = performance.now();

                            const updateCounter = (currentTime) => {
                                const elapsed = currentTime - startTime;
                                const progress = Math.min(elapsed / duration, 1);
                                // easeOutExpo
                                const easeProgress = progress === 1 ? 1 : 1 - Math.pow(2, -10 * progress);
                                const currentNumber = Math.floor(easeProgress * targetNumber);
                                el.textContent = currentNumber + suffix;

                                if (progress < 1) {
                                    requestAnimationFrame(updateCounter);
                                } else {
                                    el.textContent = targetNumber + suffix;
                                }
                            };
                            requestAnimationFrame(updateCounter);
                        }
                    });
                };
                
                // Animate when intro is starting
                window.addEventListener('load', animateNumbers, { once: true });
                if (document.readyState === 'complete') animateNumbers();
            }

            if (document.readyState === 'complete') {
                startIntroMotion();
            } else {
                window.addEventListener('load', startIntroMotion, { once: true });
            }
        })();