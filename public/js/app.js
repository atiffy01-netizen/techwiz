/* ============================================================
   CAMPUS COIN — APP.JS
   Core UI Interactions for Phase 1 Frontend
   ============================================================ */

'use strict';

/* ============================================================
   THEME MANAGER
   ============================================================ */
const ThemeManager = {
  STORAGE_KEY: 'cc-theme',

  init() {
    const saved = localStorage.getItem(this.STORAGE_KEY) || 'light';
    this.apply(saved);
    this.updateToggleIcons(saved);
  },

  apply(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem(this.STORAGE_KEY, theme);
    this.updateToggleIcons(theme);
    this.updateLogos(theme);
  },

  toggle() {
    const current = document.documentElement.getAttribute('data-theme') || 'light';
    const next = current === 'light' ? 'dark' : 'light';
    this.apply(next);
  },

  updateToggleIcons(theme) {
    document.querySelectorAll('.cc-theme-toggle').forEach(btn => {
      const icon = btn.querySelector('i');
      if (!icon) return;
      icon.className = theme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
    });
  },

  updateLogos(theme) {
    const isDark = theme === 'dark';
    const logoSelectors = '.cc-brand-logo, .cc-brand-logo-nav, .cc-brand-logo-auth, .cc-brand-logo-footer, .cc-brand-logo-sm, [data-light-src]';
    document.querySelectorAll(logoSelectors).forEach(img => {
      if (img && img.tagName === 'IMG') {
        const lightSrc = img.getAttribute('data-light-src') || '/images/campus-coin-logo-light.png';
        const darkSrc = img.getAttribute('data-dark-src') || '/images/campus-coin-logo-dark.png';
        const targetSrc = isDark ? darkSrc : lightSrc;
        if (img.src !== targetSrc && !img.src.endsWith(targetSrc)) {
          img.src = targetSrc;
        }
      }
    });
  }
};

/* ============================================================
   FONT SIZE MANAGER
   ============================================================ */
const FontSizeManager = {
  STORAGE_KEY: 'cc-font-size',

  init() {
    const saved = localStorage.getItem(this.STORAGE_KEY) || 'medium';
    this.apply(saved);
    // Sync radio buttons
    const radio = document.querySelector(`input[name="font-size"][value="${saved}"]`);
    if (radio) radio.checked = true;
  },

  apply(size) {
    document.documentElement.setAttribute('data-font-size', size);
    localStorage.setItem(this.STORAGE_KEY, size);
  }
};

/* ============================================================
   TOAST NOTIFICATION SYSTEM
   ============================================================ */
const Toast = {
  container: null,
  DURATION: 3500,

  init() {
    this.container = document.createElement('div');
    this.container.className = 'cc-toast-container';
    document.body.appendChild(this.container);
  },

  show(type, title, message) {
    const icons = {
      success: 'bi-check-circle-fill',
      error:   'bi-x-circle-fill',
      warning: 'bi-exclamation-triangle-fill',
      info:    'bi-info-circle-fill'
    };

    const toast = document.createElement('div');
    toast.className = `cc-toast ${type}`;
    toast.style.setProperty('--toast-duration', `${this.DURATION}ms`);
    toast.innerHTML = `
      <div class="cc-toast-icon"><i class="bi ${icons[type] || icons.info}"></i></div>
      <div style="min-width:0;flex:1;">
        <div class="cc-toast-title">${title}</div>
        ${message ? `<div class="cc-toast-body">${message}</div>` : ''}
      </div>
      <button onclick="this.closest('.cc-toast').remove()" style="background:none;border:none;color:var(--cc-text-muted);cursor:pointer;font-size:.9rem;padding:.125rem;flex-shrink:0;line-height:1;">
        <i class="bi bi-x-lg"></i>
      </button>`;

    this.container.appendChild(toast);
    setTimeout(() => toast.remove(), this.DURATION + 50);
  },

  success(title, message) { this.show('success', title, message); },
  error(title, message)   { this.show('error', title, message); },
  warning(title, message) { this.show('warning', title, message); },
  info(title, message)    { this.show('info', title, message); }
};

/* ============================================================
   MODAL MANAGER
   ============================================================ */
const Modal = {
  open(id) {
    const overlay = document.getElementById(id);
    if (!overlay) return;
    overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
    // Close on overlay click
    overlay.addEventListener('click', (e) => {
      if (e.target === overlay) this.close(id);
    }, { once: true });
  },

  close(id) {
    const overlay = document.getElementById(id);
    if (!overlay) return;
    overlay.classList.remove('active');
    document.body.style.overflow = '';
  }
};

/* ============================================================
   SIDEBAR MANAGER
   ============================================================ */
const SidebarManager = {
  init() {
    const sidebar  = document.querySelector('.cc-sidebar');
    const overlay  = document.querySelector('.cc-sidebar-overlay');
    const menuBtn  = document.querySelector('.cc-mobile-menu-btn');
    if (!sidebar) return;

    if (menuBtn) {
      menuBtn.addEventListener('click', () => this.open(sidebar, overlay));
    }
    if (overlay) {
      overlay.addEventListener('click', () => this.close(sidebar, overlay));
    }

    // ESC key to close
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && sidebar.classList.contains('open')) {
        this.close(sidebar, overlay);
      }
    });
  },

  open(sidebar, overlay) {
    sidebar.classList.add('open');
    if (overlay) overlay.classList.add('visible');
    document.body.style.overflow = 'hidden';
  },

    toggle(sidebar, overlay) {
      if (!sidebar) sidebar = document.querySelector('.cc-sidebar');
      if (!overlay) overlay = document.querySelector('.cc-sidebar-overlay');
      if (!sidebar) return;
      if (sidebar.classList.contains('open')) {
        this.close(sidebar, overlay);
      } else {
        this.open(sidebar, overlay);
      }
    }
  };

  const Sidebar = {
    toggle() {
      SidebarManager.toggle();
    },
    open() {
      const sidebar = document.querySelector('.cc-sidebar');
      const overlay = document.querySelector('.cc-sidebar-overlay');
      SidebarManager.open(sidebar, overlay);
    },
    close() {
      const sidebar = document.querySelector('.cc-sidebar');
      const overlay = document.querySelector('.cc-sidebar-overlay');
      SidebarManager.close(sidebar, overlay);
    }
  };

/* ============================================================
   PASSWORD VISIBILITY TOGGLE
   ============================================================ */
const PasswordToggle = {
  init() {
    document.querySelectorAll('.cc-password-toggle').forEach(btn => {
      btn.addEventListener('click', () => {
        const wrapper = btn.closest('.cc-password-wrapper');
        if (!wrapper) return;
        const input = wrapper.querySelector('input');
        const icon  = btn.querySelector('i');
        if (!input || !icon) return;
        if (input.type === 'password') {
          input.type = 'text';
          icon.className = 'bi bi-eye-slash';
        } else {
          input.type = 'password';
          icon.className = 'bi bi-eye';
        }
      });
    });
  }
};

/* ============================================================
   MODAL CLOSE BUTTONS (data-modal-close)
   ============================================================ */
const ModalCloseButtons = {
  init() {
    document.addEventListener('click', (e) => {
      const btn = e.target.closest('[data-modal-close]');
      if (!btn) return;
      const overlay = btn.closest('.cc-modal-overlay');
      if (overlay) Modal.close(overlay.id);
    });
  }
};

/* ============================================================
   LANDING PAGE: MOBILE NAV
   ============================================================ */
const LandingNav = {
  init() {
    const overlay    = document.querySelector('.cc-mobile-nav-overlay');
    const mobileNav  = document.querySelector('.cc-mobile-nav');
    const toggleBtn  = document.querySelector('.nav-mobile-toggle');
    const closeBtn   = document.querySelector('.mobile-nav-close');
    if (!mobileNav) return;

    const open = () => {
      mobileNav.classList.add('open');
      if (overlay) overlay.classList.add('open');
      document.body.style.overflow = 'hidden';
    };
    const close = () => {
      mobileNav.classList.remove('open');
      if (overlay) overlay.classList.remove('open');
      document.body.style.overflow = '';
    };

    if (toggleBtn) toggleBtn.addEventListener('click', open);
    if (closeBtn)  closeBtn.addEventListener('click', close);
    if (overlay)   overlay.addEventListener('click', close);

    // Smooth scroll for anchor links
    mobileNav.querySelectorAll('a[href^="#"]').forEach(a => {
      a.addEventListener('click', () => { close(); });
    });
  }
};

/* ============================================================
   LANDING PAGE: STICKY NAV SCROLL EFFECT
   ============================================================ */
const NavScroll = {
  init() {
    const nav = document.getElementById('mainNav');
    if (!nav) return;
    const update = () => {
      if (window.scrollY > 40) {
        nav.style.boxShadow = 'var(--cc-shadow-md)';
      } else {
        nav.style.boxShadow = 'none';
      }
    };
    window.addEventListener('scroll', update, { passive: true });
    update();
  }
};

/* ============================================================
   ACTIVE NAV ITEM (app pages)
   ============================================================ */
const ActiveNav = {
  init() {
    const current = window.location.pathname.split('/').pop() || 'index.html';
    document.querySelectorAll('.cc-nav-item').forEach(link => {
      const href = (link.getAttribute('href') || '').split('?')[0];
      if (href === current) {
        link.classList.add('active');
      }
    });
  }
};

/* ============================================================
   SEARCH BAR FILTER (basic client-side filter for transactions)
   ============================================================ */
const SearchFilter = {
  init() {
    const searchInput = document.getElementById('txnSearch');
    if (!searchInput) return;
    searchInput.addEventListener('input', () => {
      const query = searchInput.value.toLowerCase().trim();
      document.querySelectorAll('.cc-table tbody tr').forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = query === '' || text.includes(query) ? '' : 'none';
      });
    });
  }
};

/* ============================================================
   KEYBOARD SHORTCUTS
   ============================================================ */
const Shortcuts = {
  init() {
    document.addEventListener('keydown', (e) => {
      // ESC: close any open modal
      if (e.key === 'Escape') {
        document.querySelectorAll('.cc-modal-overlay.active').forEach(m => {
          Modal.close(m.id);
        });
      }
      // Ctrl/Cmd+K: focus search
      if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        const search = document.querySelector('.cc-topbar-search input');
        if (search) search.focus();
      }
    });
  }
};

/* ============================================================
   LOADING INDICATOR
   ============================================================ */
const LoadingIndicator = {
  show() {
    if (document.getElementById('cc-loader')) return;
    const loader = document.createElement('div');
    loader.id = 'cc-loader';
    loader.style.cssText = `
      position:fixed;top:0;left:0;right:0;height:3px;
      background:var(--cc-primary);z-index:9999;
      animation:loaderProgress 2s ease-in-out infinite;
    `;
    const style = document.createElement('style');
    style.textContent = `
      @keyframes loaderProgress {
        0% { width:0; opacity:1; }
        70% { width:85%; opacity:1; }
        100% { width:100%; opacity:0; }
      }`;
    document.head.appendChild(style);
    document.body.appendChild(loader);
  },
  hide() {
    const loader = document.getElementById('cc-loader');
    if (loader) loader.remove();
  }
};

/* ============================================================
   BOOTSTRAP 5 FORM VALIDATION CLASSES
   ============================================================ */
const FormValidation = {
  init() {
    document.querySelectorAll('form').forEach(form => {
      form.querySelectorAll('input[required], select[required]').forEach(field => {
        field.addEventListener('blur', () => {
          if (!field.value.trim()) {
            field.style.borderColor = 'var(--cc-expense)';
            field.style.boxShadow = '0 0 0 3px var(--cc-expense-bg)';
          } else {
            field.style.borderColor = '';
            field.style.boxShadow = '';
          }
        });
        field.addEventListener('input', () => {
          if (field.value.trim()) {
            field.style.borderColor = '';
            field.style.boxShadow = '';
          }
        });
      });
    });
  }
};

/* ============================================================
   ENTRY POINT
   ============================================================ */
document.addEventListener('DOMContentLoaded', () => {
  ThemeManager.init();
  FontSizeManager.init();
  Toast.init();
  SidebarManager.init();
  PasswordToggle.init();
  ModalCloseButtons.init();
  LandingNav.init();
  NavScroll.init();
  ActiveNav.init();
  SearchFilter.init();
  Shortcuts.init();
  FormValidation.init();

  // Bind all theme toggle buttons
  document.querySelectorAll('.cc-theme-toggle').forEach(btn => {
    btn.addEventListener('click', () => ThemeManager.toggle());
  });
});
