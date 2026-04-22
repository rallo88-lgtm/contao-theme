/**
 * RCT – Rallos Contao Toolbox
 * Theme JavaScript v2.7.0 - "Layout-Switcher Edition"
 *
 * v2.5: Sidebar-State nach Seitenwechsel korrekt synchronisieren.
 * v2.6: Rechte Sidebar persistiert. Beide FOUC-Guards sauber entfernt.
 * v2.7: Layout-Switcher. Gemeinsamer Dropdown-Listener. Kein Race-Condition.
 */
(function () {
  'use strict';

  /* Hilfsfunktion */
  function isMobile() {
    return window.innerWidth <= 1024;
  }

  function isWebGLSupported() {
    try {
      const c = document.createElement('canvas');
      return !!(c.getContext('webgl') || c.getContext('experimental-webgl'));
    } catch (e) {
      return false;
    }
  }

  document.addEventListener('DOMContentLoaded', function () {

    /* ------------------------------------------------------------------
       1. LINKE SIDEBAR (#left)
       State-Logik:
         Desktop: body.rct-sidebar-closed  = Sidebar weg
                  (kein Klassenname)        = Sidebar sichtbar (default)
         Mobile:  body.rct-sidebar-open    = Sidebar eingeflogen
                  (kein Klassenname)        = Sidebar weg (default)
    ------------------------------------------------------------------ */

    const sidebarLeft   = document.getElementById('left');
    const toggleBtnLeft = document.getElementById('rct-nav-toggle');
    const closeBtnLeft  = document.querySelector('.rct-sidebar-close-btn');
    const body          = document.body;

    function syncSidebarLeft() {
      // Alle FOUC-Guards entfernen – JS übernimmt jetzt den Zustand
      document.documentElement.classList.remove('rct-sidebar-initial-closed');
      document.documentElement.classList.remove('rct-right-initial-open');
      document.documentElement.classList.add('rct-js-ready');

      if (isMobile()) {
        // Mobile: keine Persistenz, immer geschlossen beim Laden
        body.classList.remove('rct-sidebar-closed');
        body.classList.remove('rct-sidebar-open');
        // Layout immer auf sidebar zurücksetzen
        document.documentElement.removeAttribute('data-layout');
        return;
      }

      // Linke Sidebar
      const savedState = localStorage.getItem('rct-sidebar-state');
      if (savedState === 'closed') {
        body.classList.add('rct-sidebar-closed');
      } else {
        body.classList.remove('rct-sidebar-closed');
      }

      // Rechte Sidebar – nicht bei topnav
      const currentLyt = localStorage.getItem('rct-layout') || 'sidebar';
      if (currentLyt !== 'topnav' && localStorage.getItem('rct-sidebar-right-state') === 'open') {
        body.classList.add('rct-right-open');
      } else {
        body.classList.remove('rct-right-open');
      }
    }

    // Hilfsfunktion: aktuelles Layout abfragen
    function currentLayout() {
      return document.documentElement.getAttribute('data-layout') || 'sidebar';
    }

    // Toggle-Button (Hamburger im Header)
    if (toggleBtnLeft) {
      toggleBtnLeft.addEventListener('click', function (e) {
        e.preventDefault();

        if (isMobile()) {
          body.classList.toggle('rct-sidebar-open');
        } else if (currentLayout() === 'topnav') {
          // Topnav: Sidebar temporär einblenden via rct-sidebar-open, kein Storage
          body.classList.toggle('rct-sidebar-open');
        } else {
          const isClosed = body.classList.toggle('rct-sidebar-closed');
          localStorage.setItem('rct-sidebar-state', isClosed ? 'closed' : 'open');
        }
        document.dispatchEvent(new Event('rct:layout-change'));
      });
    }



    // Schließen-Button (Doppelpfeil innerhalb der Sidebar)
    if (closeBtnLeft) {
      closeBtnLeft.addEventListener('click', function () {
        if (!isMobile()) {
          body.classList.add('rct-sidebar-closed');
          localStorage.setItem('rct-sidebar-state', 'closed');
        } else {
          body.classList.remove('rct-sidebar-open');
        }
        document.dispatchEvent(new Event('rct:layout-change'));
      });
    }

    // pageshow feuert auch bei bfcache (Browser-Back) – wichtig!
    window.addEventListener('pageshow', syncSidebarLeft);
    syncSidebarLeft();


    /* ------------------------------------------------------------------
       2. RECHTE SIDEBAR (#right)
       JS-Klasse: body.rct-right-open
    ------------------------------------------------------------------ */

    const sidebarRight       = document.getElementById('right');
    const toggleBtnRight     = document.getElementById('rct-right-toggle');
    const toggleBtnMenuRight = document.getElementById('rct-menu-right-toggle');

    function toggleRight() {
      // Im sidebar-right Layout ist #right per CSS standardmäßig offen.
      // Schließen läuft dort über body.rct-right-closed (nicht rct-right-open).
      if (currentLayout() === 'sidebar-right') {
        const isClosed = body.classList.toggle('rct-right-closed');
        localStorage.setItem('rct-sidebar-right-state', isClosed ? 'closed' : 'open');
      } else if (currentLayout() === 'topnav') {
        // Topnav: rechte Sidebar temporär einblenden, kein Storage-Schreiben
        body.classList.toggle('rct-right-open');
      } else {
        const isOpen = body.classList.toggle('rct-right-open');
        localStorage.setItem('rct-sidebar-right-state', isOpen ? 'open' : 'closed');
      }
      document.dispatchEvent(new Event('rct:layout-change'));
    }

    if (toggleBtnRight && sidebarRight) {
      toggleBtnRight.addEventListener('click', function (e) {
        e.preventDefault();
        toggleRight();
      });
    }

    if (toggleBtnMenuRight && sidebarRight) {
      toggleBtnMenuRight.addEventListener('click', function (e) {
        e.preventDefault();
        toggleRight();
      });
    }


    /* ------------------------------------------------------------------
       3. OVERLAY-KLICK (schließt offene Sidebars auf Mobile)
    ------------------------------------------------------------------ */

    document.addEventListener('click', function (e) {
      if (!isMobile()) return;

      const leftOpen  = body.classList.contains('rct-sidebar-open');
      const rightOpen = body.classList.contains('rct-right-open');
      if (!leftOpen && !rightOpen) return;

      const clickedInsideLeft  = sidebarLeft  && sidebarLeft.contains(e.target);
      const clickedInsideRight = sidebarRight && sidebarRight.contains(e.target);
      const clickedToggleLeft  = toggleBtnLeft  && toggleBtnLeft.contains(e.target);
      const clickedToggleRight = toggleBtnRight && toggleBtnRight.contains(e.target);

      if (!clickedInsideLeft && !clickedInsideRight &&
          !clickedToggleLeft && !clickedToggleRight) {
        body.classList.remove('rct-sidebar-open');
        body.classList.remove('rct-right-open');
      }
    });

    

    /* ------------------------------------------------------------------
       4. NAVIGATION ACCORDION (Recursive Lvl 1-4)
    ------------------------------------------------------------------ */

    const initNavigation = () => {
      const submenus = document.querySelectorAll('#left .mod_navigation li.submenu, #right .mod_navigation li.submenu');

      submenus.forEach(li => {
        const trigger = li.querySelector(':scope > a, :scope > span');

        if (trigger) {
          trigger.addEventListener('click', function(e) {
            if (this.tagName === 'SPAN' || this.getAttribute('href') === '#' || this.getAttribute('href').includes('javascript:void(0)')) {
              e.preventDefault();
              li.classList.toggle('is-open');
            }
          });
        }
      });

      // AUTO-OPEN: Wenn eine Seite aktiv ist, klappen wir den Baum dorthin auf
      const trails = document.querySelectorAll('.mod_navigation li.trail, .mod_navigation li.active');
      trails.forEach(t => t.classList.add('is-open'));
    };

    initNavigation();

/* ------------------------------------------------------------------
      GLOBAL OVERLAY LOGIK (Suche & Artikel-Popup)
    ------------------------------------------------------------------ */
    const overlay = document.getElementById('globalOverlay');
    const overlayContainer = overlay?.querySelector('.rct-overlay-content');

    // 1. Artikel-Popup Initialisierung
    const popupArticle = document.querySelector('.mod_article.rct-popup-content');

    if (popupArticle && overlayContainer) {
      const popupId = 'rct_popup_' + (popupArticle.id || 'general');
    
      if (!localStorage.getItem(popupId)) {
        overlayContainer.innerHTML = '';
        const clonedArticle = popupArticle.cloneNode(true);
        clonedArticle.classList.remove('rct-popup-content');
        overlayContainer.appendChild(clonedArticle);
        
        setTimeout(() => {
            overlay.classList.add('is-active');
            // Original entfernen, um ID-Konflikte zu vermeiden
            popupArticle.remove(); 
        }, 2000);
      } else {
        popupArticle.remove();
      }
    }

    // 2. Suche-Trigger
    const searchTrigger = document.getElementById('rct-nav-search');
    if (searchTrigger && overlayContainer) {
        searchTrigger.addEventListener('click', (e) => {
            e.preventDefault();
            overlayContainer.innerHTML = `
                <div class="rct-search-box">
                    <h2>Suche</h2>
                    <form action="suche" method="get">
                        <input type="text" name="keywords" class="rct-input" placeholder="Suchbegriff..." autofocus>
                        <button type="submit" class="rct-btn">Suchen</button>
                    </form>
                </div>
            `;
            overlay.classList.add('is-active');
        });
    }

    // 3. Login-Trigger
    const loginTrigger = document.getElementById('rct-nav-login');

    function openLoginOverlay() {
        const loginSource = document.getElementById('rct-login');
        if (!loginSource || !overlayContainer) return;
        overlayContainer.innerHTML = '';
        const cloned = loginSource.cloneNode(true);
        cloned.removeAttribute('id');
        cloned.classList.add('rct-login-in-overlay');
        overlayContainer.appendChild(cloned);
        overlay.classList.add('is-active');
        // Submit-Flag setzen damit wir nach Seiten-Reload wissen ob Login fehlschlug
        const form = overlayContainer.querySelector('form');
        if (form) {
            form.addEventListener('submit', () => {
                sessionStorage.setItem('rct-login-attempted', '1');
            });
        }
    }

    if (loginTrigger) {
        loginTrigger.addEventListener('click', (e) => {
            e.preventDefault();
            openLoginOverlay();
        });
    }

    // Auto-Reopen nach fehlgeschlagenem Login (Seite wurde neu geladen)
    if (sessionStorage.getItem('rct-login-attempted') === '1') {
        sessionStorage.removeItem('rct-login-attempted');
        const loginSource = document.getElementById('rct-login');
        // Nur öffnen wenn Loginformular noch da ist (= Login fehlgeschlagen)
        if (loginSource && loginSource.querySelector('form')) {
            openLoginOverlay();
        }
    }

    // 4. Zentrale Klick-Steuerung für das Overlay (Schließen & Speichern)
    overlay?.addEventListener('click', (e) => {
      const isCloseBtn = e.target.classList.contains('rct-overlay-close-btn');
      const isBackground = e.target === overlay;
      const isConfirmBtn = e.target.id === 'confirmPopup';

      // FALL A: Verstanden-Button geklickt
      if (isConfirmBtn) {
          const dontShowCheckbox = document.getElementById('dontShowAgain');
          if (dontShowCheckbox && dontShowCheckbox.checked) {
              const activeArticle = overlay.querySelector('.mod_article');
              const pId = 'rct_popup_' + (activeArticle?.id || 'general');
              localStorage.setItem(pId, 'true');
          }
          overlay.classList.remove('is-active');
          return; // Abbruch hier
      }

      // FALL B: Normales Schließen (X oder Background)
      if (isCloseBtn || isBackground) {
          overlay.classList.remove('is-active');
          // Inhalt nach dem Ausfaden leeren
          setTimeout(() => { overlayContainer.innerHTML = ''; }, 300);
      }
    });

    /* ------------------------------------------------------------------
       5. DOTS-ANIMATION
    ------------------------------------------------------------------ */

    const rctCanvasEnabled = window.rctCanvasEnabled !== false;
    const rctDotsEnabled   = window.rctDotsEnabled   !== false;
    const rctAuroraSpeed   = typeof window.rctAuroraSpeed === 'number' ? window.rctAuroraSpeed : 1.0;

    const dotsCanvas = document.getElementById('rct-dots-canvas');
    const main       = document.getElementById('main');
    const dotsState  = {
      dots: [], mouse: { x: -9999, y: -9999 },
      spacing: 40, width: 0, height: 0,
      dotColor: 'rgba(255, 255, 255, 0.45)'
    };

    if (rctDotsEnabled && dotsCanvas && main) {
      const dctx = dotsCanvas.getContext('2d');

      function resizeDots() {
        dotsState.width  = dotsCanvas.width  = window.innerWidth;
        dotsState.height = dotsCanvas.height = window.innerHeight;
        dotsState.dots   = [];
        for (let x = dotsState.spacing / 2; x < dotsState.width; x += dotsState.spacing) {
          for (let y = dotsState.spacing / 2; y < dotsState.height; y += dotsState.spacing) {
            dotsState.dots.push({ x, y, bx: x, by: y, vx: 0, vy: 0 });
          }
        }
      }

      function animateDots() {
        dctx.clearRect(0, 0, dotsState.width, dotsState.height);
        dctx.fillStyle = dotsState.dotColor;
        dctx.beginPath();
        const maxDistSq = 130 * 130;
        for (let i = 0; i < dotsState.dots.length; i++) {
          const d  = dotsState.dots[i];
          const dx = dotsState.mouse.x - d.x;
          const dy = dotsState.mouse.y - d.y;
          const distSq = dx * dx + dy * dy;
          if (distSq < maxDistSq) {
            const dist  = Math.sqrt(distSq);
            const force = (130 - dist) / 130;
            d.vx -= dx * force * 0.4;
            d.vy -= dy * force * 0.4;
          }
          d.vx += (d.bx - d.x) * 0.12;
          d.vy += (d.by - d.y) * 0.12;
          d.vx *= 0.8; d.vy *= 0.8;
          d.x  += d.vx; d.y  += d.vy;
          dctx.moveTo(d.x + 1.1, d.y);
          dctx.arc(d.x, d.y, 1.1, 0, Math.PI * 2);
        }
        dctx.fill();
        requestAnimationFrame(animateDots);
      }

      window.addEventListener('mousemove', function (e) {
        dotsState.mouse.x = e.clientX;
        dotsState.mouse.y = e.clientY;
      });
      window.addEventListener('resize', resizeDots);
      resizeDots();
      requestAnimationFrame(animateDots);

      window.updateDotColor = function () {
        dotsState.dotColor =
          getComputedStyle(document.documentElement)
            .getPropertyValue('--rct-bg-dots').trim() ||
          'rgba(255,255,255,0.45)';
      };
      window.updateDotColor();
    }


    /* ------------------------------------------------------------------
       6. THEME SWITCHER
    ------------------------------------------------------------------ */

    const switcher   = document.getElementById('themeSwitcher');
    const gradCanvas = document.querySelector('#gradient-canvas');

    window.applyTheme = function (theme, withFade) {
      if (!gradCanvas || !rctCanvasEnabled) return;

      // 1. Attribute setzen
      if (theme === 'default' || !theme) {
        document.documentElement.removeAttribute('data-theme');
        document.body.removeAttribute('data-theme');
      } else {
        document.documentElement.setAttribute('data-theme', theme);
        document.body.setAttribute('data-theme', theme);
      }

      // 2. Dots Update (falls vorhanden)
      if (window.updateDotColor) window.updateDotColor();

      // 3. UI-Anzeige aktualisieren (Name & Punkt im Header)
      const nameDisplay = document.querySelector('.current-theme-name');
      const dotDisplay  = document.querySelector('.current-theme-dot');

      if (nameDisplay) {
        const labels = {
          default:             'Standard',
          lime:                'Lime Tech',
          purple:              'Deep Purple',
          'dark-cherry-bloom': 'Baccara Rose',
          'dark-cherry':       'Dark Cherry',
          'honey-moon':        'Honey Moon',
          'candy-chaos':       'Candy Chaos',
          'sparta':            'Sparta',
          'sparta2':           'Sparta II',
          'toxic-green':       'Toxic Green',
          'claudy-sky':        'Claudy Sky',
          'glass-tank':        'Glass Tank',
          'neon-grid':         'Neon Grid',
          'magnetic-field':    'Magnetic Field',
          'baker-street':      'Baker Street',
        };
        nameDisplay.textContent = labels[theme] || 'Standard';
      }

      if (dotDisplay) {
        dotDisplay.className = 'current-theme-dot ' + (theme || 'default');
      }

      // 3b. Neural-Logo-Farben zurücksetzen (Inline-Styles leeren → CSS-Vars übernehmen)
      (function resetNeuralColors() {
        var ids = ['hellblauRechtsOben','hellblauRechtsUnten','dunkeblauUnten',
                   'dunkelblauRechtsOben','grauLinksUnten','grauRechtsOben_1_'];
        document.querySelectorAll('.rct-sidebar-logo').forEach(function(logo) {
          ids.forEach(function(id) {
            var p = logo.querySelector('#' + id);
            if (p) { p.style.fill = ''; p.style.transition = ''; }
          });
        });
      })();

      // 4. Kern-Logik für den Canvas-Update
      const executeCanvasUpdate = () => {
        // Kein GL-Canvas für diese Themes — Shader stopp, is-visible für Fade-in
        if (theme === 'sparta' || theme === 'sparta2' || theme === 'baker-street') {
          if (theme === 'baker-street') {
            gradCanvas.style.backgroundImage = '';
            setTimeout(() => gradCanvas.classList.add('is-visible'), 150);
          }
          return;
        }

        if (typeof Gradient !== 'undefined' && isWebGLSupported()) {

          // Farb-Konverter: Hex/RGB → [r, g, b] normalisiert 0..1
          function parseColor(c) {
            if (Array.isArray(c)) return c;
            const d = document.createElement('div');
            d.style.color = c;
            document.body.appendChild(d);
            const rgb = getComputedStyle(d).color;
            document.body.removeChild(d);
            const m = rgb.match(/\d+/g);
            return m ? [+m[0]/255, +m[1]/255, +m[2]/255] : [1,1,1];
          }

          // Speed-Normalisierung: 1.0 = normale Geschwindigkeit
          // Interne Faktoren pro Shader-Typ damit 1.0 überall gleich "fühlt"
          const SPEED_SCALE = {
            vertex:   0.001,   // vertexShader_timeSpeed
            fragment: 0.02,    // fragmentShader_lineSpeed — 1.0 = sehr langsam
          };

          // Config aus rct-canvas-config.js lesen
          const cfgSource = (typeof RCT_CANVAS_CONFIG !== 'undefined')
            ? RCT_CANVAS_CONFIG
            : {};
          const p = cfgSource[theme] || cfgSource['default'] || {};

          // Alten Shader sauber beenden + akkumulierten MouseMove-Handler entfernen
          if (window.gradient) {
            if (window.gradient.pause) window.gradient.pause();
            window.removeEventListener('mousemove', window.gradient.handleMouseMove);
          }

          window.gradient           = new Gradient();
          // Vertex Shader
          window.gradient.amp       = p.vertexShader_amp       ?? 320;
          window.gradient.seed      = p.vertexShader_seed      ?? 5;
          window.gradient.freqX     = p.vertexShader_freqX     ?? 14e-5;
          window.gradient.freqY     = p.vertexShader_freqY     ?? 29e-5;
          window.gradient.freqDelta = p.vertexShader_freqDelta ?? 1e-5;
          window.gradient.timeSpeed = (p.vertexShader_timeSpeed ?? 1.0) * SPEED_SCALE.vertex * 1000 * rctAuroraSpeed;
          window.gradient.bgColor   = parseColor(p.vertexShader_bgColor || '#0F0F14');
          // Fragment Shader
          window.gradient.lineMode  = p.shaderMode             ?? 0;
          window.gradient.lineCount = p.fragmentShader_lineCount ?? 20;
          window.gradient.lineWidth = p.fragmentShader_lineWidth ?? 0.1;
          window.gradient.lineSpeed = (p.fragmentShader_lineSpeed ?? 0) * SPEED_SCALE.fragment;
          window.gradient.lineColor = parseColor(p.fragmentShader_lineColor || '#CCBB66');
          window.gradient.waveStrength = p.fragmentShader_waveStrength ?? 1.0;
          window.gradient.slimeMode   = p.fragmentShader_slimeMode   ?? 0.0;
          window.gradient.frameSkip      = p.frameSkip ?? 2;
          window.gradient.mouseParallax  = p.mouseParallax ?? false;
          // Kombination
          window.gradient.fragmentWithVertex = p.fragmentWithVertex ?? true;
          window.gradient.mouse     = [0.5, 0.5];

          window.gradient.initGradient('#gradient-canvas');

          // Mouse tracking
          if (!window._rctMouseHandler) {
            window._rctMouseHandler = function(e) {
              if (!window.gradient || !window.gradient.mesh) return;
              const mx = e.clientX / window.innerWidth;
              const my = 1.0 - (e.clientY / window.innerHeight);
              window.gradient.mouse = [mx, my];
              // mouseParallax: false → Mitte halten → kein Parallax-Offset im Shader
              const isP8 = window.gradient.lineMode === 8;
              const val  = (!isP8 || window.gradient.mouseParallax) ? [mx, my] : [0.5, 0.5];
              window.gradient.mesh.material.uniforms.u_mouse.value = val;
            };
            window.addEventListener('mousemove', window._rctMouseHandler);
          }

          // density + activeColors nach init
          if (window.gradient.mesh) {
            window.gradient.conf.density = p.vertexShader_density || [0.06, 0.16];
            window.gradient.mesh.material.uniforms.u_active_colors.value =
              p.vertexShader_activeColors || [1,1,1,1];
          }

          setTimeout(() => gradCanvas.classList.add('is-visible'), 150);

          // Hintergrundbild auf Canvas setzen wenn konfiguriert
          if (p.bgImage) {
            gradCanvas.style.backgroundImage = `url('${p.bgImage}')`;
            gradCanvas.style.backgroundSize  = 'cover';
            gradCanvas.style.backgroundPosition = 'center';
          } else {
            gradCanvas.style.backgroundImage = '';
          }
        } else {
          // Fallback kein WebGL: statische Hintergrundfarbe
          gradCanvas.style.backgroundColor = p.vertexShader_bgColor || '#0F0F14';
          gradCanvas.style.backgroundImage = '';
          setTimeout(() => gradCanvas.classList.add('is-visible'), 150);
        }
      };

      // 5. Fade-Handling
      if (withFade) {
        // Immer sofort stoppen — verhindert Shader-Überlagerung beim Wechsel
        const isFastOut = window.gradient && window.gradient.lineMode === 6;
        if (window.gradient && window.gradient.pause) window.gradient.pause();
        gradCanvas.style.transition = isFastOut ? 'opacity 0.25s ease' : '';
        gradCanvas.classList.remove('is-visible');
        setTimeout(() => {
          gradCanvas.style.transition = '';
          executeCanvasUpdate();
        }, isFastOut ? 350 : 1600);
      } else {
        executeCanvasUpdate();
      }
    };


    /* ------------------------------------------------------------------
       7. FULLSCREEN TOGGLE
    ------------------------------------------------------------------ */

    const fsBtn = document.getElementById('rct-fullscreen-toggle');
    if (fsBtn) {
      fsBtn.addEventListener('click', function () {
        if (!document.fullscreenElement) {
          document.documentElement.requestFullscreen().catch(err => {
            console.error('Fullscreen-Fehler:', err.message);
          });
        } else {
          if (document.exitFullscreen) document.exitFullscreen();
        }
      });
      document.addEventListener('fullscreenchange', () => {
        fsBtn.classList.toggle('is-active', !!document.fullscreenElement);
      });
    }


    /* ------------------------------------------------------------------
       8. THEME INITIALISIERUNG & SWITCHER-EVENTS
    ------------------------------------------------------------------ */

    const isFixed      = document.documentElement.hasAttribute('data-fixed');
    const allowedThemes = (window.rctAllowedThemes && window.rctAllowedThemes.length) ? window.rctAllowedThemes : null;
    const savedTheme   = localStorage.getItem('user-theme');

    if (allowedThemes && allowedThemes.length === 1) {
      applyTheme(allowedThemes[0], false);
      if (switcher) switcher.style.display = 'none';
    } else {
      const validTheme = (allowedThemes && savedTheme && !allowedThemes.includes(savedTheme))
        ? allowedThemes[0]
        : (savedTheme || 'default');
      applyTheme(validTheme, false);

      if (switcher) {
        if (allowedThemes) {
          switcher.querySelectorAll('.theme-opt-btn').forEach(btn => {
            if (!allowedThemes.includes(btn.getAttribute('data-set-theme') || '')) {
              btn.closest('li').remove();
            }
          });
        }

        const themeTrigger = switcher.querySelector('.theme-trigger');
        const themeOptions = switcher.querySelectorAll('.theme-opt-btn');
        const saveCheck    = document.getElementById('save-theme-pref');

        if (saveCheck) {
          const isPersistent = localStorage.getItem('theme-persistence');
          saveCheck.checked = (isPersistent === 'true' || isPersistent === null);
        }

        if (themeTrigger) {
          themeTrigger.addEventListener('click', (e) => {
            e.stopPropagation();
            switcher.classList.toggle('is-open');
            if (layoutSwitcher) layoutSwitcher.classList.remove('is-open');
          });
        }

        themeOptions.forEach(btn => {
          btn.addEventListener('click', function (e) {
            e.stopPropagation();
            const newTheme     = this.getAttribute('data-set-theme') || 'default';
            const currentTheme = document.documentElement.getAttribute('data-theme') || 'default';

            if (newTheme === currentTheme) {
              switcher.classList.remove('is-open');
              return;
            }

            applyTheme(newTheme, true);

            if (saveCheck && saveCheck.checked) {
              localStorage.setItem('user-theme', newTheme);
              localStorage.setItem('theme-persistence', 'true');
            } else {
              localStorage.removeItem('user-theme');
              localStorage.setItem('theme-persistence', 'false');
            }
            switcher.classList.remove('is-open');
          });
        });
      }
    }


    /* ------------------------------------------------------------------
       9. NAVBAR – Scroll-Shadow + Mobile Toggle
    ------------------------------------------------------------------ */

    const navbar            = document.getElementById('navbar');
    const navbarMobileToggle = document.getElementById('rct-navbar-mobile-toggle');
    const navbarMenu        = document.querySelector('.rct-navbar-menu');

    const pageHeader = document.getElementById('header');
    if (navbar || pageHeader) {
      window.addEventListener('scroll', function () {
        const scrolled = window.scrollY > 16;
        if (navbar)      navbar.classList.toggle('is-scrolled', scrolled);
        if (pageHeader)  pageHeader.classList.toggle('is-scrolled', scrolled);
      }, { passive: true });
    }

    if (navbarMobileToggle && navbarMenu) {
      navbarMobileToggle.addEventListener('click', function () {
        navbarMenu.classList.toggle('is-open');
      });
    }


    /* ------------------------------------------------------------------
       10. LAYOUT SWITCHER
    ------------------------------------------------------------------ */

    const layoutSwitcher = document.getElementById('layoutSwitcher');
    const layoutLabels   = {
      sidebar:         'Sidebar L',
      'sidebar-right': 'Sidebar R',
      topnav:          'Top Nav',
      full:            'Fullscreen'
    };

window.applyLayout = function (layout) {
  // data-layout setzen
  if (layout && layout !== 'sidebar') {
    document.documentElement.setAttribute('data-layout', layout);
  } else {
    document.documentElement.removeAttribute('data-layout');
  }

  // JS-States synchronisieren
  const body = document.body;

  // Beim Layout-Wechsel immer alle Sidebar-Klassen sauber zuruecksetzen
  body.classList.remove('rct-right-open', 'rct-right-closed', 'rct-sidebar-closed');

  if (layout === 'sidebar-right') {
    // Nur rechte Sidebar offen, linke zu
    body.classList.add('rct-sidebar-closed');
    body.classList.remove('rct-right-closed');
    localStorage.setItem('rct-sidebar-state', 'closed');
    localStorage.setItem('rct-sidebar-right-state', 'open');
  } else if (layout === 'sidebar') {
    // Nur linke Sidebar offen, rechte zu
    body.classList.remove('rct-sidebar-closed');
    body.classList.add('rct-right-closed');
    localStorage.setItem('rct-sidebar-state', 'open');
    localStorage.setItem('rct-sidebar-right-state', 'closed');
  } else if (layout === 'topnav') {
    // Beide Sidebars zu – Toggles können sie temporär öffnen ohne Storage
    body.classList.add('rct-sidebar-closed');
    body.classList.remove('rct-sidebar-open');
    body.classList.remove('rct-right-open');
  }

  localStorage.setItem('rct-layout', layout || 'sidebar');
  const nameEl = document.querySelector('.current-layout-name');
  if (nameEl) nameEl.textContent = layoutLabels[layout] || 'Sidebar';

  // Aktiven Button markieren + erstes rect Accent-Farbe
  document.querySelectorAll('.layout-opt-btn').forEach(btn => {
    const isActive = (btn.getAttribute('data-set-layout') || 'sidebar') === (layout || 'sidebar');
    btn.classList.toggle('is-active', isActive);
    const rect = btn.querySelector('svg rect:first-child');
    if (rect) rect.style.fill = isActive ? 'var(--rct-accent)' : '';
  });

  // Trigger-SVG: identisch zum aktiven layout-opt-btn SVG setzen
  const activeBtn = document.querySelector('.layout-opt-btn.is-active');
  const trigger   = document.querySelector('.layout-trigger');
  if (activeBtn && trigger) {
    const activeSvg = activeBtn.querySelector('svg');
    const triggerSvg = trigger.querySelector('svg');
    if (activeSvg && triggerSvg) {
      triggerSvg.innerHTML = activeSvg.innerHTML;
      // Größe des Triggers beibehalten (16x16 statt 14x14 der Options)
      triggerSvg.setAttribute('width', '16');
      triggerSvg.setAttribute('height', '16');
    }
  }

  document.dispatchEvent(new Event('rct:layout-change'));
};

    // Beim Laden wiederherstellen – bei fixed-Layout-Seiten den fest gesetzten Wert nehmen
    const savedLayout = isFixed
      ? (document.documentElement.getAttribute('data-layout') || 'sidebar')
      : localStorage.getItem('rct-layout');
    if (savedLayout) window.applyLayout(savedLayout);

    if (layoutSwitcher) {
      const layoutTrigger = layoutSwitcher.querySelector('.layout-trigger');
      const layoutOptions = layoutSwitcher.querySelectorAll('.layout-opt-btn');

      if (layoutTrigger) {
        layoutTrigger.addEventListener('click', (e) => {
          e.stopPropagation();
          layoutSwitcher.classList.toggle('is-open');
          // Theme-Switcher schließen
          if (switcher) switcher.classList.remove('is-open');
        });
      }

      layoutOptions.forEach(btn => {
        btn.addEventListener('click', function (e) {
          e.stopPropagation();
          const newLayout = this.getAttribute('data-set-layout') || 'sidebar';
          window.applyLayout(newLayout);
          layoutSwitcher.classList.remove('is-open');
        });
      });
    }


    /* ------------------------------------------------------------------
       11. GEMEINSAMER DROPDOWN-LISTENER
       Schließt alle offenen Dropdowns beim Klick außerhalb
    ------------------------------------------------------------------ */

    document.addEventListener('click', (e) => {
      if (switcher && !switcher.contains(e.target)) {
        switcher.classList.remove('is-open');
      }
      if (layoutSwitcher && !layoutSwitcher.contains(e.target)) {
        layoutSwitcher.classList.remove('is-open');
      }
    });


    /* ------------------------------------------------------------------
       9. SCROLL-TO-TOP BUTTON
    ------------------------------------------------------------------ */
    let bottomBar = document.getElementById('bottom');
    if (!bottomBar) {
      bottomBar = document.createElement('div');
      bottomBar.id = 'bottom';
      document.body.appendChild(bottomBar);
    }
    const scrollTopBtn = document.createElement('button');
    scrollTopBtn.id = 'rct-scroll-top';
    scrollTopBtn.setAttribute('aria-label', 'Zurück nach oben');
    scrollTopBtn.innerHTML =
      '<svg width="24" height="24" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
        '<polygon points="9,2 1,9.5 6,9.5 6,16 12,16 12,9.5 17,9.5"/>' +
      '</svg>';
    bottomBar.appendChild(scrollTopBtn);

    // Privacy-Button (Klaro) direkt links neben Scroll-to-Top
    const privacyBtn = document.createElement('button');
    privacyBtn.id = 'rct-privacy-btn';
    privacyBtn.setAttribute('aria-label', 'Datenschutzeinstellungen');
    privacyBtn.setAttribute('title', 'Datenschutzeinstellungen');
    privacyBtn.innerHTML =
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
        '<path d="M12 2L4 6v6c0 5.5 3.8 10.7 8 12 4.2-1.3 8-6.5 8-12V6l-8-4z"/>' +
      '</svg>';
    privacyBtn.addEventListener('click', function () {
      if (window.klaro) window.klaro.show();
    });
    bottomBar.appendChild(privacyBtn);

    function alignScrollTopBtn() {
      const cssVars   = getComputedStyle(document.documentElement);
      const maxWidth  = parseFloat(cssVars.getPropertyValue('--rct-max-width'))          || 1280;
      const sbLeft    = parseFloat(cssVars.getPropertyValue('--rct-sidebar-left-width'))  || 260;
      const sbRight   = parseFloat(cssVars.getPropertyValue('--rct-sidebar-right-width')) || 260;
      const layout    = document.documentElement.getAttribute('data-layout') || 'sidebar';

      // Wie viel Platz nehmen die Sidebars vom Viewport weg?
      let padLeft  = 0;
      let padRight = 0;

      if (layout === 'sidebar' || layout === 'sidebar-right') {
        if (!body.classList.contains('rct-sidebar-closed')) padLeft = sbLeft;
      }
      if (layout === 'sidebar-right') {
        if (!body.classList.contains('rct-right-closed'))   padRight = sbRight;
      } else if (layout !== 'topnav') {
        if (body.classList.contains('rct-right-open'))      padRight = sbRight;
      }

      // Breite von #mainContainer → #main (flex:1, padding 40px je Seite)
      const mainInner = window.innerWidth - padLeft - padRight - 80; // 2×40px #main padding

      // rct-content-inner: max-width zentriert → ggf. Zentrierung
      const centering = Math.max(0, (mainInner - maxWidth) / 2);

      // right = rechte Sidebar + #main-padding-right + Zentrierung + content-inner-padding-right
      const rightPos = padRight + 40 + centering + 20;
      scrollTopBtn.style.right = rightPos + 'px';
      privacyBtn.style.right   = (rightPos + 34) + 'px'; // 28px Btn + 6px Gap
    }
    alignScrollTopBtn();
    window.addEventListener('resize', alignScrollTopBtn, { passive: true });
    document.addEventListener('rct:layout-change', alignScrollTopBtn);

    function updateScrollTop() {
      scrollTopBtn.classList.toggle('is-at-top', window.scrollY < 40);
    }
    updateScrollTop();
    window.addEventListener('scroll', updateScrollTop, { passive: true });

    scrollTopBtn.addEventListener('click', function () {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });

  }); // Ende DOMContentLoaded


  /* ------------------------------------------------------------------
     8. RIPPLE-ANIMATION STYLE INJEKTION
  ------------------------------------------------------------------ */
  if (!document.getElementById('rct-ripple-style')) {
    const style = document.createElement('style');
    style.id = 'rct-ripple-style';
    style.textContent =
      '@keyframes rct-ripple{0%{transform:scale(0);opacity:0.6}100%{transform:scale(1.8);opacity:0}}' +
      '.rct-ripple{position:absolute;border-radius:50%;background:rgba(255,255,255,0.18);' +
      'animation:rct-ripple 0.5s ease-out;pointer-events:none;transform:scale(0);}';
    document.head.appendChild(style);
  }

  function createRipple(event) {
    const button = event.currentTarget;
    const ripple = document.createElement('span');
    const rect = button.getBoundingClientRect();
    const size = Math.min(Math.max(rect.width, rect.height), 48); // max 48px
    ripple.style.width = ripple.style.height = `${size}px`;
    ripple.style.left = `${event.clientX - rect.left - size / 2}px`;
    ripple.style.top  = `${event.clientY - rect.top  - size / 2}px`;
    ripple.classList.add('rct-ripple');
    button.appendChild(ripple);
    setTimeout(() => ripple.remove(), 500);
  }

  // Ripple nur auf Header-Buttons
  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll(
      '#rct-nav-toggle, #rct-fullscreen-toggle, #rct-nav-search, #rct-right-toggle, ' +
      '#rct-scroll-top, .theme-trigger, .layout-trigger'
    ).forEach(el => el.addEventListener('click', createRipple));
  });

  // ── Chart Bars: Intersection Observer + Counter Animation ────────────────
  (function initChartBars() {
    if (!window.IntersectionObserver) return;

    function animateCounter(el, target, duration) {
      var start     = null;
      var startVal  = 0;
      function step(ts) {
        if (!start) start = ts;
        var progress = Math.min((ts - start) / duration, 1);
        // ease-out cubic
        var eased = 1 - Math.pow(1 - progress, 3);
        el.textContent = Math.round(startVal + (target - startVal) * eased);
        if (progress < 1) requestAnimationFrame(step);
      }
      requestAnimationFrame(step);
    }

    var observer = new IntersectionObserver(function(entries) {
      entries.forEach(function(entry) {
        if (!entry.isIntersecting) return;
        var chart = entry.target;
        chart.classList.add('is-animated');

        // Counter für alle sichtbaren Value-Spans
        chart.querySelectorAll('.rct-chart-bar-value').forEach(function(span) {
          var target  = parseInt(span.getAttribute('data-target'), 10) || 0;
          var barEl   = span.closest('.rct-chart-bar');
          var delay   = parseFloat(getComputedStyle(barEl).getPropertyValue('--bar-delay')) || 0;
          setTimeout(function() {
            animateCounter(span, target, 850);
          }, delay * 1000);
        });

        observer.unobserve(chart);
      });
    }, { threshold: 0.2 });

    function observeCharts() {
      document.querySelectorAll('.rct-chart-bars:not(.is-animated)').forEach(function(el) {
        observer.observe(el);
      });
    }

    // Sofort auslösen wenn DOM schon bereit, sonst auf DOMContentLoaded warten
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', observeCharts);
    } else {
      observeCharts();
    }
    document.addEventListener('rct:page-ready', observeCharts);
  })();

  // ── Timeline: Intersection Observer — Items einzeln einblenden ──────────
  (function initTimeline() {
    if (!window.IntersectionObserver) return;

    var observer = new IntersectionObserver(function(entries) {
      entries.forEach(function(entry) {
        if (!entry.isIntersecting) return;
        entry.target.classList.add('is-animated');
        observer.unobserve(entry.target);
      });
    }, { threshold: 0.15 });

    function observeTimeline() {
      document.querySelectorAll('.rct-timeline-item:not(.is-animated)').forEach(function(el) {
        observer.observe(el);
      });
    }

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', observeTimeline);
    } else {
      observeTimeline();
    }
    document.addEventListener('rct:page-ready', observeTimeline);
  })();

  // ── Stat Box: Intersection Observer + Counter Animation ──────────────────
  (function initStatBoxes() {
    if (!window.IntersectionObserver) return;

    function animateStatCounter(el, target, decimals, duration) {
      var start = null;
      function step(ts) {
        if (!start) start = ts;
        var progress = Math.min((ts - start) / duration, 1);
        var eased    = 1 - Math.pow(1 - progress, 3); // ease-out cubic
        var current  = target * eased;
        el.textContent = decimals > 0
          ? current.toFixed(decimals)
          : Math.round(current).toLocaleString('de-DE');
        if (progress < 1) requestAnimationFrame(step);
        else el.textContent = decimals > 0
          ? target.toFixed(decimals)
          : target.toLocaleString('de-DE');
      }
      requestAnimationFrame(step);
    }

    var observer = new IntersectionObserver(function(entries) {
      entries.forEach(function(entry) {
        if (!entry.isIntersecting) return;
        var box      = entry.target;
        var target   = parseFloat(box.getAttribute('data-stat-value'))   || 0;
        var decimals = parseInt(box.getAttribute('data-stat-decimals'), 10) || 0;
        var delay    = parseFloat(getComputedStyle(box).getPropertyValue('--stat-delay')) || 0;

        box.classList.add('is-animated');

        var counter = box.querySelector('.rct-stat-counter');
        if (counter) {
          // Startfähiger Wert damit's nicht leer aussieht
          counter.textContent = decimals > 0 ? (0).toFixed(decimals) : '0';
          setTimeout(function() {
            animateStatCounter(counter, target, decimals, 1400);
          }, delay * 1000 + 150); // leichter Offset nach der Entrance-Anim
        }

        observer.unobserve(box);
      });
    }, { threshold: 0.25 });

    function observeStatBoxes() {
      document.querySelectorAll('.rct-stat-box:not(.is-animated)').forEach(function(el) {
        observer.observe(el);
      });
    }

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', observeStatBoxes);
    } else {
      observeStatBoxes();
    }
    document.addEventListener('rct:page-ready', observeStatBoxes);
  })();

  // ── Alert Dismiss ──────────────────────────────────────────────
  (function() {
    function initAlerts() {
      document.querySelectorAll('.rct-alert-dismiss').forEach(function(btn) {
        if (btn._rctBound) return;
        btn._rctBound = true;
        btn.addEventListener('click', function() {
          var alert = btn.closest('.rct-alert');
          if (!alert) return;
          alert.classList.add('is-dismissed');
          setTimeout(function() { alert.remove(); }, 350);
        });
      });
    }
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', initAlerts);
    } else {
      initAlerts();
    }
    document.addEventListener('rct:page-ready', initAlerts);
  })();

  // ── Tabs ───────────────────────────────────────────────────────
  (function() {
    function initTabs() {
      document.querySelectorAll('.rct-tabs:not([data-rct-tabs])').forEach(function(widget) {
        widget.setAttribute('data-rct-tabs', '1');
        var btns   = widget.querySelectorAll('.rct-tabs-btn');
        var panels = widget.querySelectorAll('.rct-tabs-panel');

        btns.forEach(function(btn, i) {
          btn.addEventListener('click', function() {
            if (btn.classList.contains('is-active')) return;

            // deactivate all
            btns.forEach(function(b) {
              b.classList.remove('is-active');
              b.setAttribute('aria-selected', 'false');
            });
            panels.forEach(function(p) {
              p.classList.remove('is-active');
              p.setAttribute('hidden', '');
            });

            // activate clicked
            btn.classList.add('is-active');
            btn.setAttribute('aria-selected', 'true');
            var panel = panels[i];
            if (panel) {
              panel.classList.add('is-active');
              panel.removeAttribute('hidden');
            }
          });
        });
      });
    }

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', initTabs);
    } else {
      initTabs();
    }
    document.addEventListener('rct:page-ready', initTabs);
  })();


  /* ── Sidebar Logo Neural-Network-Animation ──────────────────
     Bei jedem mouseenter: 6 Paths je 3x durch zufällige Theme-
     Farben schicken (gestaffelt), auf Endfarbe stehen bleiben.
     Kein Reset auf Originalfarben — Netz bleibt "wach".
  ──────────────────────────────────────────────────────────── */
  (function initNeuralLogo() {
    var pathIds = [
      'hellblauRechtsOben', 'hellblauRechtsUnten',
      'dunkeblauUnten',     'dunkelblauRechtsOben',
      'grauLinksUnten',     'grauRechtsOben_1_'
    ];
    var stagger = [0, 55, 30, 100, 70, 130]; /* ms Versatz pro Path */

    function getColors() {
      var cs = getComputedStyle(document.documentElement);
      return [
        cs.getPropertyValue('--rct-accent').trim()      || '#27c4f4',
        cs.getPropertyValue('--rct-primary').trim()     || '#2950c7',
        cs.getPropertyValue('--rct-logo-muted').trim()  || '#C8CDD0'
      ];
    }

    function rnd(arr) { return arr[Math.floor(Math.random() * arr.length)]; }

    function fireNeural(logo) {
      var theme = document.documentElement.getAttribute('data-theme') || 'default';
      var cfg = (typeof RCT_CANVAS_CONFIG !== 'undefined' && RCT_CANVAS_CONFIG[theme]) || {};
      if (cfg.neuralLogo === false) return;
      var colors = getColors();
      pathIds.forEach(function(id, i) {
        var path = logo.querySelector('#' + id);
        if (!path) return;
        setTimeout(function() {
          path.style.transition = 'fill 0.11s ease-in-out';
          path.style.fill = rnd(colors);
          setTimeout(function() {
            path.style.fill = rnd(colors);
            setTimeout(function() {
              path.style.fill = rnd(colors);
            }, 120);
          }, 120);
        }, stagger[i]);
      });
    }

    function bindNeural() {
      document.querySelectorAll('#left .mod_navigation a, #right .mod_navigation a').forEach(function(link) {
        link.addEventListener('mouseenter', function() {
          var logo = this.closest('#left, #right').querySelector('.rct-sidebar-logo');
          if (logo) fireNeural(logo);
        });
      });
    }

    if (document.readyState !== 'loading') {
      bindNeural();
    } else {
      document.addEventListener('DOMContentLoaded', bindNeural);
    }
    document.addEventListener('rct:page-ready', bindNeural);
  })();


  /* ------------------------------------------------------------------
     SIDEBAR NAV-HÖHE RESERVIEREN
     Klappt alle Submenus unsichtbar auf, misst scrollHeight,
     setzt FESTE height auf .inside.sitenavigation.
     Submenus können danach overflow:visible nach unten wachsen —
     Logo sitzt fest darunter, springt nie.
  ------------------------------------------------------------------ */
  (function initSidebarNavReserve() {

    function measureMaxNavHeight(nav) {
      // Alle Submenus über CSS-Klasse unsichtbar aufklappen
      nav.classList.add('rct-measuring');
      // Reflow erzwingen
      var h = nav.scrollHeight;
      nav.classList.remove('rct-measuring');
      return h;
    }

    function reserveNavHeight() {
      ['#left', '#right'].forEach(function(sel) {
        var sidebar = document.querySelector(sel);
        if (!sidebar) return;
        var nav = sidebar.querySelector('.inside.sitenavigation');
        if (!nav) return;

        // Höhe zurücksetzen für saubere Messung
        nav.style.height = '';
        var h = measureMaxNavHeight(nav);
        if (h > 0) nav.style.height = h + 'px';
      });
    }

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', reserveNavHeight);
    } else {
      reserveNavHeight();
    }
    document.addEventListener('rct:page-ready', reserveNavHeight);
  })();


})();