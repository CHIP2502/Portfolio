const themeToggle = document.querySelector('.theme-toggle');
const themeTransitionLayer = document.querySelector('.theme-transition-layer');
const themeColorMeta = document.getElementById('theme-color-meta');
const themeStorageKey = 'portfolio-theme';
const menuToggle = document.querySelector('.menu-toggle');
const siteNav = document.querySelector('.site-nav');
const isLoaderEnabled = document.documentElement.getAttribute('data-loader-enabled') !== 'false';
const shouldForceTopOnLoad = !window.location.hash;

if ('scrollRestoration' in window.history) {
    window.history.scrollRestoration = 'manual';
}

let hasStartedIntro = false;

const activatePageIntro = () => {
    if (hasStartedIntro) {
        return;
    }

    hasStartedIntro = true;
    document.body.classList.add('is-loaded');

    window.requestAnimationFrame(() => {
        if (isLoaderEnabled) {
            document.documentElement.classList.remove('is-loading');
        }

        if (shouldForceTopOnLoad) {
            window.scrollTo({ top: 0, left: 0, behavior: 'auto' });
            window.setTimeout(() => window.scrollTo(0, 0), 80);
        }
    });
};

const prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

if (!isLoaderEnabled || prefersReducedMotion) {
    activatePageIntro();
} else if (document.readyState === 'complete') {
    activatePageIntro();
} else {
    window.addEventListener('load', () => {
        window.setTimeout(activatePageIntro, 120);
    }, { once: true });

    window.setTimeout(activatePageIntro, 2400);
}

const applyTheme = (theme, shouldPersist = true) => {
    const normalizedTheme = theme === 'dark' ? 'dark' : 'light';
    const isDarkTheme = normalizedTheme === 'dark';

    document.documentElement.setAttribute('data-theme', normalizedTheme);

    if (themeColorMeta) {
        themeColorMeta.setAttribute('content', isDarkTheme ? '#0b1220' : '#f5efe4');
    }

    if (themeToggle) {
        themeToggle.dataset.theme = normalizedTheme;
        themeToggle.setAttribute('aria-pressed', isDarkTheme ? 'true' : 'false');
        themeToggle.setAttribute('aria-label', isDarkTheme ? 'Chuyển sang giao diện sáng' : 'Chuyển sang giao diện tối');
    }

    if (shouldPersist) {
        document.documentElement.setAttribute('data-theme-source', 'user');

        try {
            localStorage.setItem(themeStorageKey, normalizedTheme);
        } catch (_) {
        }
    }
};

const THEME_MORPH_DURATION_MS = 760;
const THEME_TRANSITION_DURATION_MS = 2000;
const THEME_TRANSITION_APPLY_MS = 960;
let isThemeTransitioning = false;
let themeMorphTimerId = null;
let themeApplyTimerId = null;
let themeTransitionTimerId = null;

const finishThemeTransition = () => {
    document.documentElement.classList.remove('is-theme-transitioning');
    document.documentElement.removeAttribute('data-theme-target');
    document.documentElement.removeAttribute('data-theme-from');

    if (themeToggle) {
        themeToggle.disabled = false;
        themeToggle.classList.remove('is-morphing');
    }

    isThemeTransitioning = false;
    themeMorphTimerId = null;
    themeApplyTimerId = null;
    themeTransitionTimerId = null;
};

const switchThemeWithMorph = (nextTheme) => {
    const currentTheme = document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';

    if (currentTheme === nextTheme) {
        return;
    }

    if (!themeToggle || !themeTransitionLayer || prefersReducedMotion) {
        applyTheme(nextTheme);
        return;
    }

    if (isThemeTransitioning) {
        return;
    }

    isThemeTransitioning = true;
    themeToggle.disabled = true;
    themeToggle.classList.add('is-morphing');
    document.documentElement.classList.add('is-theme-transitioning');
    document.documentElement.setAttribute('data-theme-from', currentTheme);
    document.documentElement.setAttribute('data-theme-target', nextTheme);

    if (themeMorphTimerId !== null) {
        window.clearTimeout(themeMorphTimerId);
    }

    themeMorphTimerId = window.setTimeout(() => {
        if (themeToggle) {
            themeToggle.classList.remove('is-morphing');
        }
        themeMorphTimerId = null;
    }, THEME_MORPH_DURATION_MS);

    if (themeApplyTimerId !== null) {
        window.clearTimeout(themeApplyTimerId);
    }

    themeApplyTimerId = window.setTimeout(() => {
        applyTheme(nextTheme);
        themeApplyTimerId = null;
    }, THEME_TRANSITION_APPLY_MS);

    if (themeTransitionTimerId !== null) {
        window.clearTimeout(themeTransitionTimerId);
    }

    themeTransitionTimerId = window.setTimeout(() => {
        finishThemeTransition();
    }, THEME_TRANSITION_DURATION_MS);
};

const currentTheme = document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
applyTheme(currentTheme, false);

if (themeToggle) {
    themeToggle.addEventListener('click', () => {
        const nextTheme = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        switchThemeWithMorph(nextTheme);
    });
}

const shouldFollowSystemTheme = document.documentElement.getAttribute('data-theme-source') === 'system';

if (shouldFollowSystemTheme && window.matchMedia) {
    const systemThemeQuery = window.matchMedia('(prefers-color-scheme: dark)');

    const handleSystemThemeChange = (event) => {
        applyTheme(event.matches ? 'dark' : 'light', false);
    };

    if (typeof systemThemeQuery.addEventListener === 'function') {
        systemThemeQuery.addEventListener('change', handleSystemThemeChange);
    } else if (typeof systemThemeQuery.addListener === 'function') {
        systemThemeQuery.addListener(handleSystemThemeChange);
    }
}

if (menuToggle && siteNav) {
    menuToggle.addEventListener('click', () => {
        const isOpen = siteNav.classList.toggle('is-open');
        menuToggle.classList.toggle('is-open', isOpen);
        menuToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });

    siteNav.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => {
            siteNav.classList.remove('is-open');
            menuToggle.classList.remove('is-open');
            menuToggle.setAttribute('aria-expanded', 'false');
        });
    });
}

const sectionNavLinks = Array.from(document.querySelectorAll('.site-nav .section-link[href^="#"]'));
const sectionNavMap = sectionNavLinks
    .map((link) => {
        const selector = link.getAttribute('href');

        if (!selector) {
            return null;
        }

        const section = document.querySelector(selector);

        if (!section) {
            return null;
        }

        return {
            link,
            section,
        };
    })
    .filter(Boolean);

const contactNavLink = document.querySelector('.site-nav .nav-cta[href^="#"]');
const contactSection = (() => {
    if (!contactNavLink) {
        return null;
    }

    const selector = contactNavLink.getAttribute('href');

    if (!selector) {
        return null;
    }

    return document.querySelector(selector);
})();

const setActiveSectionLink = (sectionId = '') => {
    sectionNavMap.forEach((item) => {
        const isActive = sectionId !== '' && item.section.id === sectionId;
        item.link.classList.toggle('is-active', isActive);
    });
};

const activeHashItem = sectionNavMap.find((item) => item.link.getAttribute('href') === window.location.hash);

if (activeHashItem) {
    setActiveSectionLink(activeHashItem.section.id);
}

sectionNavMap.forEach((item) => {
    item.link.addEventListener('click', () => {
        setActiveSectionLink(item.section.id);
    });
});

if (contactNavLink) {
    contactNavLink.addEventListener('click', () => {
        setActiveSectionLink('');
    });
}

if (sectionNavMap.length) {
    let isScrollSyncQueued = false;

    const syncActiveSectionByScroll = () => {
        const markerLine = window.innerHeight * 0.34;
        let activeItem = sectionNavMap[0];

        if (contactSection) {
            const contactRect = contactSection.getBoundingClientRect();
            const isContactAtMarker = contactRect.top <= markerLine && contactRect.bottom >= markerLine;

            if (isContactAtMarker) {
                setActiveSectionLink('');
                isScrollSyncQueued = false;
                return;
            }
        }

        for (const item of sectionNavMap) {
            const rect = item.section.getBoundingClientRect();

            if (rect.top <= markerLine && rect.bottom >= markerLine) {
                activeItem = item;
                break;
            }

            if (rect.top <= markerLine) {
                activeItem = item;
            }
        }

        const isNearPageBottom = window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 4;

        if (isNearPageBottom && contactSection) {
            setActiveSectionLink('');
            isScrollSyncQueued = false;
            return;
        }

        if (isNearPageBottom) {
            activeItem = sectionNavMap[sectionNavMap.length - 1];
        }

        if (activeItem) {
            setActiveSectionLink(activeItem.section.id);
        }

        isScrollSyncQueued = false;
    };

    const queueScrollSync = () => {
        if (isScrollSyncQueued) {
            return;
        }

        isScrollSyncQueued = true;
        window.requestAnimationFrame(syncActiveSectionByScroll);
    };

    window.addEventListener('scroll', queueScrollSync, { passive: true });
    window.addEventListener('resize', queueScrollSync, { passive: true });
    queueScrollSync();
}

const statValueNodes = Array.from(document.querySelectorAll('.stat-value'));
let hasInitializedStatCounters = false;

const parseCssTimeToMs = (timeValue) => {
    if (!timeValue) {
        return 0;
    }

    const firstToken = timeValue.split(',')[0].trim();

    if (firstToken.endsWith('ms')) {
        const numeric = Number(firstToken.slice(0, -2));
        return Number.isFinite(numeric) ? numeric : 0;
    }

    if (firstToken.endsWith('s')) {
        const numeric = Number(firstToken.slice(0, -1));
        return Number.isFinite(numeric) ? numeric * 1000 : 0;
    }

    return 0;
};

const parseStatTarget = (valueNode) => {
    if (!valueNode.dataset.statTargetText) {
        valueNode.dataset.statTargetText = valueNode.textContent.trim();
    }

    const rawValue = valueNode.dataset.statTargetText;
    const parsedValue = rawValue.match(/^([0-9]+(?:[.,][0-9]+)?)(.*)$/);

    if (!parsedValue) {
        return null;
    }

    const numberText = parsedValue[1];
    const suffixText = parsedValue[2] || '';
    const targetValue = Number(numberText.replace(',', '.'));

    if (!Number.isFinite(targetValue)) {
        return null;
    }

    const decimalMatch = numberText.match(/[.,](\d+)/);

    return {
        numberText,
        suffixText,
        targetValue,
        decimalPlaces: decimalMatch ? decimalMatch[1].length : 0,
        decimalSeparator: numberText.includes(',') ? ',' : '.'
    };
};

const resetStatValueToStart = (valueNode) => {
    const target = parseStatTarget(valueNode);

    if (target) {
        valueNode.textContent = `0${target.suffixText}`;
    }
};

const animateStatValue = (valueNode, options = {}) => {
    const {
        onComplete = null,
        warmupDelayMs = null
    } = options;
    let hasCompleted = false;

    const complete = () => {
        if (hasCompleted) {
            return;
        }

        hasCompleted = true;

        if (typeof onComplete === 'function') {
            onComplete();
        }
    };

    if (valueNode.dataset.countAnimated === 'true') {
        complete();
        return;
    }

    const target = parseStatTarget(valueNode);

    if (!target) {
        valueNode.dataset.countAnimated = 'true';
        complete();
        return;
    }

    const {
        numberText,
        suffixText,
        targetValue,
        decimalPlaces,
        decimalSeparator
    } = target;

    valueNode.dataset.countAnimated = 'true';

    if (prefersReducedMotion) {
        valueNode.textContent = `${numberText}${suffixText}`;
        complete();
        return;
    }

    valueNode.textContent = `0${suffixText}`;

    const statCardNode = valueNode.closest('.stat-card');
    let introDelayMs = 0;

    if (statCardNode && document.body.classList.contains('is-loaded')) {
        const cardStyle = window.getComputedStyle(statCardNode);

        if (cardStyle.animationName && cardStyle.animationName !== 'none') {
            introDelayMs = parseCssTimeToMs(cardStyle.animationDelay);
        }
    }

    const warmupDelay = Number.isFinite(warmupDelayMs) ? Math.max(0, warmupDelayMs) : introDelayMs + 120;
    const duration = 1600 + Math.min(targetValue * 2.4, 1000);
    const startedAt = performance.now() + warmupDelay;

    const easeInOutCubic = (progress) => {
        if (progress < 0.5) {
            return 4 * progress * progress * progress;
        }

        return 1 - Math.pow(-2 * progress + 2, 3) / 2;
    };

    const tick = (timestamp) => {
        if (timestamp < startedAt) {
            valueNode.textContent = `0${suffixText}`;
            window.requestAnimationFrame(tick);
            return;
        }

        const progress = Math.min(1, (timestamp - startedAt) / duration);
        const easedProgress = easeInOutCubic(progress);
        const currentValue = targetValue * easedProgress;

        let formattedValue;

        if (decimalPlaces > 0) {
            formattedValue = currentValue.toFixed(decimalPlaces);

            if (decimalSeparator === ',') {
                formattedValue = formattedValue.replace('.', ',');
            }
        } else {
            formattedValue = Math.floor(currentValue).toString();
        }

        valueNode.textContent = `${formattedValue}${suffixText}`;

        if (progress < 1) {
            window.requestAnimationFrame(tick);
        } else {
            valueNode.textContent = `${numberText}${suffixText}`;
            complete();
        }
    };

    window.requestAnimationFrame(tick);
};

const initializeStatCounters = () => {
    if (hasInitializedStatCounters || !statValueNodes.length) {
        return;
    }

    if (!document.body.classList.contains('is-loaded')) {
        window.setTimeout(initializeStatCounters, 160);
        return;
    }

    hasInitializedStatCounters = true;

    const shouldAnimateCountersTogether = window.matchMedia && window.matchMedia('(max-width: 760px), (hover: none), (pointer: coarse)').matches;

    if (shouldAnimateCountersTogether) {
        statValueNodes.forEach((valueNode) => resetStatValueToStart(valueNode));
        statValueNodes.forEach((valueNode) => animateStatValue(valueNode, {
            warmupDelayMs: 90
        }));
        return;
    }

    if (prefersReducedMotion || !('IntersectionObserver' in window)) {
        statValueNodes.forEach((valueNode) => animateStatValue(valueNode));
        return;
    }

    const counterObserver = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                animateStatValue(entry.target);
                counterObserver.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.45
    });

    statValueNodes.forEach((valueNode) => {
        counterObserver.observe(valueNode);
    });
};

initializeStatCounters();

const skillRows = Array.from(document.querySelectorAll('#skills .skill-row'));
const skillItems = skillRows
    .map((row) => {
        const bar = row.querySelector('.skill-bar[data-target]');
        const percent = row.querySelector('.skill-percent[data-target]');
        const target = Number((bar && bar.dataset.target) || (percent && percent.dataset.target) || 0);

        if (!bar || !percent || !Number.isFinite(target)) {
            return null;
        }

        return {
            bar,
            percent,
            target
        };
    })
    .filter(Boolean);

let hasAnimatedSkills = false;

const setSkillValue = (item, value) => {
    const clampedValue = Math.max(0, Math.min(item.target, Math.round(value)));
    item.bar.style.width = `${clampedValue}%`;
    item.percent.textContent = `${clampedValue}%`;
};

const animateSkillsToTarget = () => {
    if (hasAnimatedSkills || !skillItems.length) {
        return;
    }

    hasAnimatedSkills = true;

    if (prefersReducedMotion) {
        skillItems.forEach((item) => setSkillValue(item, item.target));
        return;
    }

    skillItems.forEach((item, index) => {
        const delay = index * 90;
        const duration = 860 + Math.min(item.target * 9, 520);
        const startAt = performance.now() + delay;

        const tick = (timestamp) => {
            if (timestamp < startAt) {
                window.requestAnimationFrame(tick);
                return;
            }

            const progress = Math.min(1, (timestamp - startAt) / duration);
            const easedProgress = 1 - Math.pow(1 - progress, 3);
            setSkillValue(item, item.target * easedProgress);

            if (progress < 1) {
                window.requestAnimationFrame(tick);
            } else {
                setSkillValue(item, item.target);
            }
        };

        window.requestAnimationFrame(tick);
    });
};

if (!prefersReducedMotion) {
    skillItems.forEach((item) => setSkillValue(item, 0));
}

const skillsSection = document.getElementById('skills');

if (skillsSection && skillItems.length) {
    if (prefersReducedMotion || !('IntersectionObserver' in window)) {
        animateSkillsToTarget();
    } else {
        const skillObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    animateSkillsToTarget();
                    skillObserver.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.34,
            rootMargin: '0px 0px -12% 0px'
        });

        skillObserver.observe(skillsSection);
    }
}

const smokeCanvas = document.getElementById('cursor-smoke');
const canUseCursorSmoke =
    smokeCanvas &&
    window.matchMedia &&
    window.matchMedia('(hover: hover) and (pointer: fine)').matches &&
    !window.matchMedia('(prefers-reduced-motion: reduce)').matches;

if (canUseCursorSmoke) {
    const smokeContext = smokeCanvas.getContext('2d');

    if (smokeContext) {
        const smokeParticles = [];
        const maxSmokeParticles = 88;
        const pointerState = {
            lastX: 0,
            lastY: 0,
            isActive: false,
            hasMoved: false
        };

        let smokeWidth = window.innerWidth;
        let smokeHeight = window.innerHeight;
        let smokeDpr = Math.min(window.devicePixelRatio || 1, 2);
        let smokeFrameId = 0;
        let lastSmokeFrame = performance.now();
        let lastSmokeSpawn = 0;

        const resizeSmokeLayer = () => {
            smokeWidth = window.innerWidth;
            smokeHeight = window.innerHeight;
            smokeDpr = Math.min(window.devicePixelRatio || 1, 2);

            smokeCanvas.width = Math.floor(smokeWidth * smokeDpr);
            smokeCanvas.height = Math.floor(smokeHeight * smokeDpr);
            smokeCanvas.style.width = `${smokeWidth}px`;
            smokeCanvas.style.height = `${smokeHeight}px`;
            smokeContext.setTransform(smokeDpr, 0, 0, smokeDpr, 0, 0);
        };

        const getSmokePalette = () => {
            const isDarkTheme = document.documentElement.getAttribute('data-theme') === 'dark';

            return isDarkTheme
                ? [[188, 223, 255], [96, 211, 198], [223, 182, 146]]
                : [[255, 255, 255], [188, 215, 238], [165, 201, 230]];
        };

        const spawnSmoke = (x, y, velocityX, velocityY) => {
            const palette = getSmokePalette();
            const color = palette[Math.floor(Math.random() * palette.length)];
            const speed = Math.min(Math.hypot(velocityX, velocityY), 72);

            smokeParticles.push({
                x,
                y,
                vx: velocityX * 0.01 + (Math.random() - 0.5) * 0.06,
                vy: velocityY * 0.01 + (Math.random() - 0.5) * 0.06 - 0.04,
                driftX: (Math.random() - 0.5) * 0.0018,
                driftY: -0.001 + Math.random() * 0.0012,
                ttl: 520 + Math.random() * 560 + speed * 3.2,
                life: 0,
                radius: 10 + Math.random() * 12 + speed * 0.08,
                fade: 0.34 + Math.random() * 0.25,
                r: color[0],
                g: color[1],
                b: color[2]
            });

            if (smokeParticles.length > maxSmokeParticles) {
                smokeParticles.splice(0, smokeParticles.length - maxSmokeParticles);
            }
        };

        const drawSmokeParticle = (particle, deltaMs) => {
            particle.life += deltaMs;

            if (particle.life >= particle.ttl) {
                return false;
            }

            const progress = particle.life / particle.ttl;
            const fade = 1 - progress;
            const alpha = particle.fade * fade * fade;

            particle.x += particle.vx * deltaMs;
            particle.y += particle.vy * deltaMs;
            particle.vx = particle.vx * 0.985 + particle.driftX * deltaMs;
            particle.vy = particle.vy * 0.982 + particle.driftY * deltaMs;

            const currentRadius = particle.radius * (0.58 + progress * 0.62);
            const gradient = smokeContext.createRadialGradient(
                particle.x,
                particle.y,
                currentRadius * 0.14,
                particle.x,
                particle.y,
                currentRadius
            );

            gradient.addColorStop(0, `rgba(${particle.r}, ${particle.g}, ${particle.b}, ${Math.min(alpha * 1.15, 0.42)})`);
            gradient.addColorStop(0.56, `rgba(${particle.r}, ${particle.g}, ${particle.b}, ${alpha * 0.58})`);
            gradient.addColorStop(1, `rgba(${particle.r}, ${particle.g}, ${particle.b}, 0)`);

            smokeContext.fillStyle = gradient;
            smokeContext.beginPath();
            smokeContext.arc(particle.x, particle.y, currentRadius, 0, Math.PI * 2);
            smokeContext.fill();

            return true;
        };

        const animateSmoke = (timestamp) => {
            const deltaMs = Math.min(34, Math.max(10, timestamp - lastSmokeFrame));
            lastSmokeFrame = timestamp;

            smokeContext.clearRect(0, 0, smokeWidth, smokeHeight);

            for (let i = smokeParticles.length - 1; i >= 0; i--) {
                if (!drawSmokeParticle(smokeParticles[i], deltaMs)) {
                    smokeParticles.splice(i, 1);
                }
            }

            if (smokeParticles.length === 0) {
                smokeFrameId = 0;

                if (!pointerState.isActive) {
                    smokeCanvas.classList.remove('is-active');
                }

                return;
            }

            smokeFrameId = window.requestAnimationFrame(animateSmoke);
        };

        const startSmokeAnimation = () => {
            if (!smokeFrameId) {
                lastSmokeFrame = performance.now();
                smokeFrameId = window.requestAnimationFrame(animateSmoke);
            }
        };

        const handlePointerMove = (event) => {
            const currentX = event.clientX;
            const currentY = event.clientY;

            if (!pointerState.hasMoved) {
                pointerState.lastX = currentX;
                pointerState.lastY = currentY;
                pointerState.hasMoved = true;
            }

            const velocityX = currentX - pointerState.lastX;
            const velocityY = currentY - pointerState.lastY;

            pointerState.lastX = currentX;
            pointerState.lastY = currentY;
            pointerState.isActive = true;

            smokeCanvas.classList.add('is-active');

            const now = performance.now();

            if (now - lastSmokeSpawn > 14) {
                spawnSmoke(currentX, currentY, velocityX, velocityY);

                if (Math.hypot(velocityX, velocityY) > 14) {
                    spawnSmoke(
                        currentX + (Math.random() - 0.5) * 8,
                        currentY + (Math.random() - 0.5) * 8,
                        velocityX * 0.8,
                        velocityY * 0.8
                    );
                }

                lastSmokeSpawn = now;
            }

            startSmokeAnimation();
        };

        const handlePointerExit = () => {
            pointerState.isActive = false;
        };

        resizeSmokeLayer();

        window.addEventListener('resize', resizeSmokeLayer, { passive: true });
        window.addEventListener('pointermove', handlePointerMove, { passive: true });
        window.addEventListener('pointerdown', handlePointerMove, { passive: true });
        document.addEventListener('mouseleave', handlePointerExit);
        window.addEventListener('blur', handlePointerExit);
    }
}

const textLoadSelectors = [
    '.section-heading h2',
    '.section-heading p',
    '.panel h3',
    '.panel p',
    '.project-body h3',
    '.project-body p',
    '.project-link',
    '.project-note',
    '.contact-panel h2',
    '.contact-panel p',
    '.contact-card strong',
    '.contact-card small',
    '.visual-large h2',
    '.visual-large p',
    '.stat-label'
].join(', ');

document.querySelectorAll(textLoadSelectors).forEach((node) => {
    node.classList.add('text-load');
});

const activateTextLoadIn = (container) => {
    const loadNodes = Array.from(container.querySelectorAll('.text-load'));

    if (container.classList && container.classList.contains('text-load')) {
        loadNodes.unshift(container);
    }

    loadNodes.forEach((node, index) => {
        if (node.dataset.textLoaded === 'true') {
            return;
        }

        node.style.setProperty('--text-load-delay', `${Math.min(index * 70, 420)}ms`);
        node.classList.add('is-loaded');
        node.dataset.textLoaded = 'true';
    });
};

const revealElements = document.querySelectorAll('[data-reveal]');

if ('IntersectionObserver' in window) {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                activateTextLoadIn(entry.target);
                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.18,
        rootMargin: '0px 0px -30px 0px'
    });

    revealElements.forEach((element) => observer.observe(element));
} else {
    revealElements.forEach((element) => {
        element.classList.add('is-visible');
        activateTextLoadIn(element);
    });
}

window.setTimeout(() => {
    revealElements.forEach((element) => {
        const rect = element.getBoundingClientRect();
        const isOnScreen = rect.top < window.innerHeight * 0.92 && rect.bottom > 0;

        if (isOnScreen) {
            element.classList.add('is-visible');
            activateTextLoadIn(element);
        }
    });
}, 900);

const yearNode = document.getElementById('year');

if (yearNode) {
    yearNode.textContent = new Date().getFullYear();
}

/* ===== Contact form AJAX submit (added) ===== */
(function () {
    var form = document.querySelector('[data-contact-form]');
    if (!form) return;
    var statusEl = form.querySelector('[data-contact-status]');
    var btn = form.querySelector('button[type="submit"]');

    function setStatus(msg, kind) {
        if (!statusEl) return;
        statusEl.textContent = msg || '';
        statusEl.classList.remove('is-ok', 'is-err');
        if (kind) statusEl.classList.add('is-' + kind);
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        if (btn) { btn.disabled = true; btn.dataset.originalText = btn.textContent; btn.textContent = 'Đang gửi...'; }
        setStatus('', null);

        var fd = new FormData(form);
        fetch(form.action, {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            credentials: 'same-origin'
        })
        .then(function (r) { return r.json().then(function (j) { return { status: r.status, body: j }; }); })
        .then(function (res) {
            if (res.body && res.body.ok) {
                setStatus(res.body.message || 'Đã gửi.', 'ok');
                form.reset();
            } else {
                setStatus((res.body && res.body.message) || 'Có lỗi xảy ra.', 'err');
            }
        })
        .catch(function () { setStatus('Lỗi mạng. Vui lòng thử lại.', 'err'); })
        .finally(function () {
            if (btn) { btn.disabled = false; btn.textContent = btn.dataset.originalText || 'Gửi tin nhắn'; }
        });
    });

    // Show flash from URL param after non-AJAX redirect
    try {
        var params = new URLSearchParams(window.location.search);
        var flag = params.get('contact');
        if (flag === 'sent') setStatus('Cảm ơn bạn! Tin nhắn đã được gửi.', 'ok');
        else if (flag === 'error') setStatus('Có lỗi xảy ra khi gửi.', 'err');
    } catch (_) {}
})();

/* ===== Projects auto-scroll + drag marquee (optimized) ===== */
(function () {
    var marquee = document.querySelector('[data-projects-marquee]');
    var track   = document.querySelector('[data-projects-track]');
    if (!marquee || !track) return;

    var prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (prefersReduced) {
        // Native horizontal scroll fallback (CSS handles it)
        return;
    }

    var SPEED_PX_PER_SEC = 28;     // base auto-scroll speed (refresh-rate independent)
    var MOMENTUM_DECAY   = 0.92;   // per-frame velocity decay after drag release
    var DRAG_THRESHOLD   = 6;      // px before treating pointer move as a drag
    var halfWidth = 0;
    var offset = 0;
    var renderedOffset = NaN;       // last value written to DOM (avoid redundant writes)
    var paused = false;             // hover/focus
    var inView = false;             // off-screen pause via IO
    var visible = !document.hidden; // tab visibility pause
    var dragging = false;
    var pointerActive = false;
    var dragStartX = 0;
    var dragStartOffset = 0;
    var dragMoved = 0;
    var velocity = 0;               // px / frame (for momentum)
    var lastDragX = 0;
    var lastDragT = 0;
    var lastFrameT = 0;
    var rafId = null;

    function recalc() {
        // Track contains 2 identical sets; one set width = scrollWidth / 2
        halfWidth = track.scrollWidth / 2;
    }

    function normalize() {
        if (halfWidth <= 0) return;
        if (offset <= -halfWidth) offset += halfWidth;
        else if (offset > 0)      offset -= halfWidth;
    }

    function render() {
        if (offset === renderedOffset) return;
        renderedOffset = offset;
        track.style.transform = 'translate3d(' + offset.toFixed(2) + 'px,0,0)';
    }

    function shouldRun() {
        return inView && visible && !document.hidden;
    }

    function tick(now) {
        var dt = lastFrameT ? (now - lastFrameT) : 16.67;
        if (dt > 100) dt = 16.67;   // tab was idle; clamp
        lastFrameT = now;

        if (!dragging && shouldRun()) {
            if (!paused) {
                offset -= SPEED_PX_PER_SEC * (dt / 1000);
            }
            if (Math.abs(velocity) > 0.05) {
                offset += velocity * (dt / 16.67);
                velocity *= Math.pow(MOMENTUM_DECAY, dt / 16.67);
            }
            normalize();
            render();
        }
        rafId = requestAnimationFrame(tick);
    }

    function startLoop() {
        if (rafId !== null) return;
        lastFrameT = 0;
        rafId = requestAnimationFrame(tick);
    }
    function stopLoop() {
        if (rafId === null) return;
        cancelAnimationFrame(rafId);
        rafId = null;
    }

    // Pause flags
    marquee.addEventListener('mouseenter', function () { paused = true;  });
    marquee.addEventListener('mouseleave', function () { paused = false; });
    marquee.addEventListener('focusin',    function () { paused = true;  });
    marquee.addEventListener('focusout',   function () { paused = false; });

    // Tab visibility — full stop when hidden (saves CPU)
    document.addEventListener('visibilitychange', function () {
        visible = !document.hidden;
        if (shouldRun()) startLoop(); else stopLoop();
    });

    // IntersectionObserver — only animate when in viewport
    if ('IntersectionObserver' in window) {
        var io = new IntersectionObserver(function (entries) {
            inView = entries[0].isIntersecting;
            if (shouldRun()) startLoop(); else stopLoop();
        }, { rootMargin: '120px 0px' });
        io.observe(marquee);
    } else {
        inView = true;
        startLoop();
    }

    // Drag (pointer events)
    function onDown(e) {
        if (e.pointerType === 'mouse' && e.button !== 0) return;
        pointerActive = true;
        dragging = false;          // becomes true after threshold exceeded
        dragMoved = 0;
        dragStartX = e.clientX;
        dragStartOffset = offset;
        lastDragX = e.clientX;
        lastDragT = performance.now();
        velocity = 0;
        try { marquee.setPointerCapture(e.pointerId); } catch (_) {}
    }
    function onMove(e) {
        if (!pointerActive) return;
        var dx = e.clientX - dragStartX;
        var adx = Math.abs(dx);
        if (!dragging) {
            if (adx < DRAG_THRESHOLD) return;
            dragging = true;
            marquee.classList.add('is-dragging');
        }
        dragMoved = adx;
        offset = dragStartOffset + dx;
        normalize();
        render();
        var now = performance.now();
        var dt = Math.max(1, now - lastDragT);
        velocity = (e.clientX - lastDragX) / dt * 16; // ~px/frame
        lastDragX = e.clientX;
        lastDragT = now;
    }
    function onUp(e) {
        if (!pointerActive) return;
        pointerActive = false;
        if (dragging) {
            dragging = false;
            marquee.classList.remove('is-dragging');
        }
        try { marquee.releasePointerCapture(e.pointerId); } catch (_) {}
    }

    marquee.addEventListener('pointerdown',   onDown);
    marquee.addEventListener('pointermove',   onMove, { passive: true });
    marquee.addEventListener('pointerup',     onUp);
    marquee.addEventListener('pointercancel', onUp);

    // Suppress click after a real drag (prevent accidental link nav)
    marquee.addEventListener('click', function (e) {
        if (dragMoved > DRAG_THRESHOLD) {
            e.preventDefault();
            e.stopPropagation();
            dragMoved = 0;
        }
    }, true);

    // Horizontal wheel / trackpad
    marquee.addEventListener('wheel', function (e) {
        if (Math.abs(e.deltaX) > Math.abs(e.deltaY)) {
            offset -= e.deltaX;
            normalize();
            render();
            e.preventDefault();
        }
    }, { passive: false });

    // ResizeObserver if available, otherwise window resize
    if ('ResizeObserver' in window) {
        var ro = new ResizeObserver(recalc);
        ro.observe(track);
    } else {
        window.addEventListener('resize', recalc);
    }

    // Init: wait for fonts/images so scrollWidth is accurate
    function init() {
        recalc();
        // Recalc again once images/fonts are settled
        if (document.fonts && document.fonts.ready) {
            document.fonts.ready.then(recalc);
        }
        window.setTimeout(recalc, 600);
    }
    if (document.readyState === 'complete') init();
    else window.addEventListener('load', init);
})();
