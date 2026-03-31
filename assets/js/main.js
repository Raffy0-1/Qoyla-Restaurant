/* ================================================================
   QOYLA RESTAURANT — GLOBAL JAVASCRIPT
   assets/js/main.js
   ================================================================ */

/* ---- Navbar hamburger toggle ---- */
const hamburger = document.getElementById('navHamburger');
const mobileNav = document.getElementById('navMobile');
if (hamburger && mobileNav) {
  hamburger.addEventListener('click', () => {
    mobileNav.classList.toggle('open');
  });
}

/* ---- AOS (Animate on Scroll) manual init fallback ---- */
function initAOS() {
  const items = document.querySelectorAll('[data-aos]');
  if (!items.length) return;
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(e => {
      if (e.isIntersecting) {
        const delay = e.target.dataset.aosDelay || 0;
        setTimeout(() => e.target.classList.add('aos-animate'), parseInt(delay));
        observer.unobserve(e.target);
      }
    });
  }, { threshold: 0.12 });
  items.forEach(el => observer.observe(el));
}
document.addEventListener('DOMContentLoaded', initAOS);

/* ---- Menu filter tabs ---- */
function initMenuFilter() {
  const btns = document.querySelectorAll('.menu-filter-btn');
  const cards = document.querySelectorAll('.menu-item-wrapper');
  if (!btns.length) return;
  btns.forEach(btn => {
    btn.addEventListener('click', function () {
      btns.forEach(b => b.classList.remove('active'));
      this.classList.add('active');
      const cat = this.dataset.filter;
      cards.forEach(card => {
        if (cat === 'all' || card.dataset.category === cat) {
          card.style.display = '';
        } else {
          card.style.display = 'none';
        }
      });
    });
  });
}
document.addEventListener('DOMContentLoaded', initMenuFilter);

/* ---- Gallery filter ---- */
function initGalleryFilter() {
  const btns = document.querySelectorAll('.gallery-filter-btn');
  const items = document.querySelectorAll('.gallery-item-wrap');
  if (!btns.length) return;
  btns.forEach(btn => {
    btn.addEventListener('click', function () {
      btns.forEach(b => b.classList.remove('active'));
      this.classList.add('active');
      const cat = this.dataset.filter;
      items.forEach(item => {
        if (cat === 'all' || item.dataset.category === cat) {
          item.style.display = '';
        } else {
          item.style.display = 'none';
        }
      });
    });
  });
}
document.addEventListener('DOMContentLoaded', initGalleryFilter);

/* ---- Admin live search ---- */
function initAdminSearch() {
  const searchInput = document.getElementById('adminSearch');
  if (!searchInput) return;
  searchInput.addEventListener('input', function () {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.admin-table tbody tr').forEach(row => {
      row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
  });
}
document.addEventListener('DOMContentLoaded', initAdminSearch);

/* ---- Modal helpers ---- */
function openModal(id) {
  const m = document.getElementById(id);
  if (m) m.classList.add('open');
}
function closeModal(id) {
  const m = document.getElementById(id);
  if (m) m.classList.remove('open');
}
// Close on overlay click
document.addEventListener('click', function (e) {
  if (e.target.classList.contains('modal-overlay')) {
    e.target.classList.remove('open');
  }
});

/* ---- Password show/hide ---- */
function togglePassword(inputId, iconId) {
  const inp = document.getElementById(inputId);
  const ico = document.getElementById(iconId);
  if (!inp) return;
  if (inp.type === 'password') {
    inp.type = 'text';
    if (ico) ico.className = 'fas fa-eye-slash';
  } else {
    inp.type = 'password';
    if (ico) ico.className = 'fas fa-eye';
  }
}

/* ---- Form submit loading state ---- */
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('form[data-loading]').forEach(form => {
    form.addEventListener('submit', function () {
      const btn = this.querySelector('button[type="submit"]');
      if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right:6px;"></i>Please wait...';
      }
    });
  });
});

/* ---- Admin tab navigation (inventory / workers) ---- */
function switchAdminTab(tabId, btn) {
  document.querySelectorAll('.admin-tab-content').forEach(t => t.style.display = 'none');
  document.querySelectorAll('.admin-tab-btn').forEach(b => b.classList.remove('active'));
  const target = document.getElementById(tabId);
  if (target) target.style.display = 'block';
  if (btn) btn.classList.add('active');
}
document.addEventListener('DOMContentLoaded', function () {
  const firstTab = document.querySelector('.admin-tab-btn');
  if (firstTab) firstTab.click();
});
