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

// Notification System Functions
function showNotification(message, type = 'info') {
  const container = document.getElementById('notification-container') || createNotificationContainer();
  
  const notification = document.createElement('div');
  notification.className = `notification notification-${type}`;
  notification.innerHTML = `
    <div class="notification-content">
      <span class="notification-message">${message}</span>
      <button class="notification-close" onclick="closeNotification(this)">&times;</button>
    </div>
  `;
  
  container.appendChild(notification);
  
  // Trigger animation
  setTimeout(() => notification.classList.add('show'), 10);
  
  // Auto-dismiss after 5 seconds
  setTimeout(() => {
    closeNotification(notification.querySelector('.notification-close'));
  }, 5000);
}

function showSuccess(message) {
  showNotification(message, 'success');
}

function showError(message) {
  showNotification(message, 'error');
}

function showWarning(message) {
  showNotification(message, 'warning');
}

function showInfo(message) {
  showNotification(message, 'info');
}

function closeNotification(closeBtn) {
  const notification = closeBtn.closest('.notification');
  notification.classList.add('hide');
  setTimeout(() => {
    if (notification.parentNode) {
      notification.parentNode.removeChild(notification);
    }
  }, 300);
}

function createNotificationContainer() {
  const container = document.createElement('div');
  container.id = 'notification-container';
  container.className = 'notification-container';
  document.body.appendChild(container);
  return container;
}

// Override native alert function
window.alert = function(message) {
  showInfo(message);
};
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
