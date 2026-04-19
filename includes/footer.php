<!-- Global Item Details Modal -->
<div class="modal-overlay" id="itemDetailsModal">
  <div class="modal-box" style="max-width:500px;text-align:center;">
    <button class="modal-close" onclick="closeModal('itemDetailsModal')" style="position:absolute;top:10px;right:15px;">&times;</button>
    <img id="itemModalImg" src="" alt="" style="width:100%;height:250px;object-fit:cover;border-radius:var(--radius-sm);margin-bottom:1rem;display:none;">
    <h3 id="itemModalTitle" style="font-family:'Cinzel',serif;font-weight:700;margin-bottom:0.5rem;font-size:1.5rem;"></h3>
    <p id="itemModalDesc" style="color:var(--text-muted);margin-bottom:1.5rem;font-size:0.95rem;line-height:1.6;"></p>
    <div style="display:flex;justify-content:center;gap:1.5rem;align-items:center;margin-bottom:1rem;">
      <div id="itemModalPrice" style="font-size:1.2rem;font-weight:700;color:var(--flame-orange);"></div>
      <div id="itemModalBadge" style="display:none;" class="badge-orange">DEAL</div>
    </div>
  </div>
</div>

<footer class="qoyla-footer">
  <div class="footer-inner">
    <div>
      <div class="footer-brand-name">QOYLA</div>
      <p class="footer-tagline">Authentic desi flavours, crafted over charcoal.<br>Multan's finest tandoor experience since day one.</p>
      <div class="social-row">
        <a href="#" class="social-btn" rel="noopener noreferrer" title="Instagram"><i class="fab fa-instagram"></i></a>
        <a href="#" class="social-btn" rel="noopener noreferrer" title="Facebook"><i class="fab fa-facebook-f"></i></a>
        <a href="#" class="social-btn" rel="noopener noreferrer" title="TikTok"><i class="fab fa-tiktok"></i></a>
        <a href="#" class="social-btn" rel="noopener noreferrer" title="WhatsApp"><i class="fab fa-whatsapp"></i></a>
      </div>
    </div>
    <div>
      <div class="footer-col-title">Navigate</div>
      <div class="footer-links">
        <a href="<?= BASE_URL ?>index.php">Home</a>
        <a href="<?= BASE_URL ?>menu.php">Menu</a>
        <a href="<?= BASE_URL ?>gallery.php">Gallery</a>
        <a href="<?= BASE_URL ?>about.php">About</a>
        <a href="<?= BASE_URL ?>contact.php">Contact</a>
      </div>
    </div>
    <div>
      <div class="footer-col-title">Reach Us</div>
      <div class="footer-contact-item"><i class="fas fa-phone"></i><span>+92 300 1234567</span></div>
      <div class="footer-contact-item"><i class="fas fa-envelope"></i><span>info@qoyla.pk</span></div>
      <div class="footer-contact-item"><i class="fab fa-whatsapp"></i><span>WhatsApp Us</span></div>
      <div class="footer-contact-item"><i class="fas fa-map-marker-alt"></i><span>Multan, Punjab, Pakistan</span></div>
    </div>
    <div>
      <div class="footer-col-title">Loyalty Club</div>
      <p style="font-size:0.88rem; line-height:1.8; margin-bottom:1.2rem;">Earn points on every visit. Redeem for discounts & special deals.</p>
      <a href="<?= BASE_URL ?>auth/signup.php" class="btn-qoyla" style="font-size:0.8rem; padding:0.55rem 1.3rem;">Join Now <i class="fas fa-arrow-right"></i></a>
    </div>
  </div>
  <div class="footer-bottom">
    <p>&copy; 2026 Qoyla Restaurant. All rights reserved.</p>
    <p>Made with <i class="fas fa-fire" style="color:var(--flame-orange);"></i> in Multan</p>
  </div>
</footer>
<script src="<?= BASE_URL ?>assets/js/main.js"></script>
<script>
function fetchAndShowItem(type, id) {
  // Show loading state if needed
  document.getElementById('itemModalTitle').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
  document.getElementById('itemModalDesc').textContent = '';
  document.getElementById('itemModalPrice').textContent = '';
  document.getElementById('itemModalImg').style.display = 'none';
  document.getElementById('itemModalBadge').style.display = 'none';
  
  openModal('itemDetailsModal');
  
  const endpoint = type === 'deal' ? `<?= BASE_URL ?>get_deal.php?id=${id}` : `<?= BASE_URL ?>get_item.php?id=${id}`;
  
  fetch(endpoint)
    .then(res => res.json())
    .then(data => {
      if(data.error) {
        document.getElementById('itemModalTitle').textContent = 'Cannot load details.';
        return;
      }
      const title = data.title || data.name;
      document.getElementById('itemModalTitle').textContent = title;
      document.getElementById('itemModalDesc').textContent = data.description || '';
      
      const imgPath = data.image_path ? data.image_path : `https://placehold.co/400x300/1A1A1A/E8500A?text=${encodeURIComponent(title)}`;
      const imgEl = document.getElementById('itemModalImg');
      imgEl.src = imgPath;
      imgEl.style.display = 'block';
      
      if(type === 'deal') {
         document.getElementById('itemModalBadge').style.display = 'inline-block';
         if(data.discount_percent > 0) {
            document.getElementById('itemModalPrice').textContent = `${data.discount_percent}% OFF`;
         } else if (data.points_multiplier > 1) {
            document.getElementById('itemModalPrice').textContent = `${data.points_multiplier}x Points`;
         } else {
            document.getElementById('itemModalPrice').textContent = '';
         }
      } else {
         document.getElementById('itemModalPrice').textContent = 'Rs. ' + parseInt(data.price).toLocaleString();
      }
    })
    .catch(err => {
      document.getElementById('itemModalTitle').textContent = 'Error loading item.';
    });
}
</script>
</body>
</html>