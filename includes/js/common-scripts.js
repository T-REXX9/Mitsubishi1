function toggleSidebar() {
  const sidebar = document.getElementById('sidebar');
  const overlay = document.querySelector('.sidebar-overlay');
  
  sidebar.classList.toggle('active');
  overlay.classList.toggle('active');
}

// Dropdown toggling
document.addEventListener('DOMContentLoaded', function() {
  const notificationBtn = document.getElementById('notificationBtn');
  const notificationDropdown = document.getElementById('notificationDropdown');
  const profileBtn = document.getElementById('profileBtn');
  const profileDropdown = document.getElementById('profileDropdown');

  if (notificationBtn && notificationDropdown) {
    notificationBtn.addEventListener('click', function(e) {
      e.stopPropagation();
      notificationDropdown.style.display = notificationDropdown.style.display === 'block' ? 'none' : 'block';
      if (profileDropdown) profileDropdown.style.display = 'none';
    });
  }

  if (profileBtn && profileDropdown) {
    profileBtn.addEventListener('click', function(e) {
      e.stopPropagation();
      profileDropdown.style.display = profileDropdown.style.display === 'block' ? 'none' : 'block';
      if (notificationDropdown) notificationDropdown.style.display = 'none';
    });
  }

  // Hide dropdowns when clicking outside
  document.addEventListener('click', function() {
    if (notificationDropdown) notificationDropdown.style.display = 'none';
    if (profileDropdown) profileDropdown.style.display = 'none';
  });
});

// Close sidebar when clicking on menu items on mobile
document.querySelectorAll('.menu-item').forEach(item => {
  item.addEventListener('click', () => {
    if (window.innerWidth <= 575) {
      toggleSidebar();
    }
  });
});
