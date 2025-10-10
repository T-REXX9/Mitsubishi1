/**
 * Custom Notification System
 * Replaces browser alert() with professional, themed notifications
 */

class NotificationSystem {
  constructor() {
    this.container = null;
    this.notifications = new Map();
    this.notificationId = 0;
    this.init();
  }

  /**
   * Initialize the notification system
   */
  init() {
    // Create notification container if it doesn't exist
    if (!document.querySelector('.notification-container')) {
      this.createContainer();
    } else {
      this.container = document.querySelector('.notification-container');
    }
  }

  /**
   * Create the notification container
   */
  createContainer() {
    this.container = document.createElement('div');
    this.container.className = 'notification-container';
    document.body.appendChild(this.container);
  }

  /**
   * Show a notification
   * @param {string} message - The message to display
   * @param {string} type - The type of notification (success, error, warning, info)
   * @param {number} duration - Duration in milliseconds (default: 5000)
   * @param {boolean} closable - Whether the notification can be manually closed (default: true)
   */
  show(message, type = 'info', duration = 5000, closable = true) {
    const id = ++this.notificationId;
    const notification = this.createNotification(id, message, type, closable);
    
    // Add to container
    this.container.appendChild(notification);
    this.notifications.set(id, notification);

    // Trigger show animation
    requestAnimationFrame(() => {
      notification.classList.add('show');
    });

    // Auto-dismiss after duration
    if (duration > 0) {
      setTimeout(() => {
        this.hide(id);
      }, duration);
    }

    return id;
  }

  /**
   * Create a notification element
   * @param {number} id - Unique notification ID
   * @param {string} message - The message to display
   * @param {string} type - The notification type
   * @param {boolean} closable - Whether closable
   */
  createNotification(id, message, type, closable) {
    const notification = document.createElement('div');
    notification.className = `custom-notification ${type}`;
    notification.setAttribute('data-id', id);

    // Create notification content
    const content = document.createElement('div');
    content.className = 'notification-content';
    
    // Add message
const messageSpan = document.createElement('span');
    messageSpan.className = 'notification-message';
    messageSpan.textContent = message;
    content.appendChild(messageSpan);

    notification.appendChild(content);

    // Add close button if closable
    if (closable) {
      const closeBtn = document.createElement('button');
      closeBtn.className = 'notification-close';
      closeBtn.innerHTML = '×';
      closeBtn.setAttribute('aria-label', 'Close notification');
      closeBtn.onclick = () => this.hide(id);
      notification.appendChild(closeBtn);
    }

    // Add progress bar
    const progressBar = document.createElement('div');
    progressBar.className = 'notification-progress';
    notification.appendChild(progressBar);

    return notification;
  }

  /**
   * Hide a notification
   * @param {number} id - The notification ID to hide
   */
  hide(id) {
    const notification = this.notifications.get(id);
    if (!notification) return;

    notification.classList.add('hide');
    
    // Remove from DOM after animation
    setTimeout(() => {
      if (notification.parentNode) {
        notification.parentNode.removeChild(notification);
      }
      this.notifications.delete(id);
    }, 400);
  }

  /**
   * Hide all notifications
   */
  hideAll() {
    this.notifications.forEach((notification, id) => {
      this.hide(id);
    });
  }

  /**
   * Show success notification
   * @param {string} message - The message to display
   * @param {number} duration - Duration in milliseconds
   */
  success(message, duration = 5000) {
    return this.show(message, 'success', duration);
  }

  /**
   * Show error notification
   * @param {string} message - The message to display
   * @param {number} duration - Duration in milliseconds
   */
  error(message, duration = 5000) {
    return this.show(message, 'error', duration);
  }

  /**
   * Show warning notification
   * @param {string} message - The message to display
   * @param {number} duration - Duration in milliseconds
   */
  warning(message, duration = 5000) {
    return this.show(message, 'warning', duration);
  }

  /**
   * Show info notification
   * @param {string} message - The message to display
   * @param {number} duration - Duration in milliseconds
   */
  info(message, duration = 5000) {
    return this.show(message, 'info', duration);
  }
}

// Create global instance
window.notificationSystem = new NotificationSystem();

/**
 * Global notification functions for easy access
 */
window.showNotification = (message, type = 'info', duration = 5000) => {
  return window.notificationSystem.show(message, type, duration);
};

window.showSuccess = (message, duration = 5000) => {
  return window.notificationSystem.success(message, duration);
};

window.showError = (message, duration = 5000) => {
  return window.notificationSystem.error(message, duration);
};

window.showWarning = (message, duration = 5000) => {
  return window.notificationSystem.warning(message, duration);
};

window.showInfo = (message, duration = 5000) => {
  return window.notificationSystem.info(message, duration);
};

/**
 * Replace the native alert function
 * This maintains backward compatibility while using the new notification system
 */
window.originalAlert = window.alert;
window.alert = function(message) {
  // Determine notification type based on message content
  let type = 'info';
  const lowerMessage = message.toLowerCase();
  
  if (lowerMessage.includes('success') || lowerMessage.includes('completed') || 
      lowerMessage.includes('saved') || lowerMessage.includes('updated')) {
    type = 'success';
  } else if (lowerMessage.includes('error') || lowerMessage.includes('failed') || 
             lowerMessage.includes('invalid') || lowerMessage.includes('cannot')) {
    type = 'error';
  } else if (lowerMessage.includes('warning') || lowerMessage.includes('please') || 
             lowerMessage.includes('required') || lowerMessage.includes('must')) {
    type = 'warning';
  }
  
  return window.notificationSystem.show(message, type, 5000);
};

// Initialize when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => {
    window.notificationSystem.init();
  });
} else {
  window.notificationSystem.init();
}

// Handle page visibility changes to pause/resume notifications
document.addEventListener('visibilitychange', () => {
  if (document.hidden) {
    // Pause animations when page is hidden
    document.querySelectorAll('.custom-notification').forEach(notification => {
      notification.style.animationPlayState = 'paused';
    });
  } else {
    // Resume animations when page is visible
    document.querySelectorAll('.custom-notification').forEach(notification => {
      notification.style.animationPlayState = 'running';
    });
  }
});

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
  module.exports = NotificationSystem;
}