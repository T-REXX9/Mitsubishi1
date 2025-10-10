/**
 * Universal Mobile Fix JavaScript
 * Enhances mobile functionality for the Mitsubishi Motors website
 */

(function() {
  'use strict';

  // Check if device is mobile
  const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
  const isIOS = /iPhone|iPad|iPod/i.test(navigator.userAgent);
  
  // Fix viewport height on mobile (especially iOS)
  function setViewportHeight() {
    // First we get the viewport height and multiply it by 1% to get a value for a vh unit
    let vh = window.innerHeight * 0.01;
    // Then we set the value in the --vh custom property to the root of the document
    document.documentElement.style.setProperty('--vh', `${vh}px`);
  }

  // Set viewport height on load and resize
  setViewportHeight();
  window.addEventListener('resize', setViewportHeight);
  window.addEventListener('orientationchange', setViewportHeight);

  // Fix iOS bounce scrolling
  if (isIOS) {
    document.addEventListener('touchmove', function(e) {
      if (e.target.closest('nav.active')) {
        // Allow scrolling in navigation menu
        return;
      }
    }, { passive: true });
  }

  // Enhanced smooth scrolling for anchor links
  document.addEventListener('DOMContentLoaded', function() {
    // Smooth scroll for all anchor links
    const anchorLinks = document.querySelectorAll('a[href^="#"]');
    
    anchorLinks.forEach(link => {
      link.addEventListener('click', function(e) {
        e.preventDefault();
        const targetId = this.getAttribute('href');
        
        if (targetId === '#') return;
        
        const targetElement = document.querySelector(targetId);
        if (targetElement) {
          const headerHeight = document.querySelector('header')?.offsetHeight || 70;
          const targetPosition = targetElement.offsetTop - headerHeight - 20;
          
          window.scrollTo({
            top: targetPosition,
            behavior: 'smooth'
          });
        }
      });
    });
  });

  // Fix mobile menu scrolling
  function enhanceMobileMenu() {
    const navMenu = document.getElementById('navMenu');
    const menuToggle = document.querySelector('.menu-toggle');
    const body = document.body;
    
    if (menuToggle && navMenu) {
      menuToggle.addEventListener('click', function() {
        // Toggle body scroll when menu is active
        if (navMenu.classList.contains('active')) {
          body.style.overflow = '';
          body.style.position = '';
        } else {
          // Store current scroll position
          const scrollY = window.scrollY;
          body.style.position = 'fixed';
          body.style.top = `-${scrollY}px`;
          body.style.width = '100%';
        }
      });
      
      // Close menu when clicking outside
      document.addEventListener('click', function(e) {
        if (navMenu.classList.contains('active') && 
            !e.target.closest('nav') && 
            !e.target.closest('.menu-toggle')) {
          navMenu.classList.remove('active');
          menuToggle.classList.remove('active');
          
          // Restore scroll position
          const scrollY = body.style.top;
          body.style.position = '';
          body.style.top = '';
          body.style.width = '';
          window.scrollTo(0, parseInt(scrollY || '0') * -1);
        }
      });
    }
  }

  // Fix touch event delays
  function enhanceTouchEvents() {
    let touchStartY = 0;
    let touchEndY = 0;
    
    document.addEventListener('touchstart', function(e) {
      touchStartY = e.changedTouches[0].screenY;
    }, { passive: true });
    
    document.addEventListener('touchend', function(e) {
      touchEndY = e.changedTouches[0].screenY;
      handleSwipe();
    }, { passive: true });
    
    function handleSwipe() {
      const swipeThreshold = 50;
      const navMenu = document.getElementById('navMenu');
      
      if (touchStartY - touchEndY > swipeThreshold) {
        // Swiped up - hide header on scroll down
        const header = document.querySelector('header');
        if (header && window.scrollY > 100) {
          header.style.transform = 'translateY(-100%)';
          header.style.transition = 'transform 0.3s ease';
        }
      }
      
      if (touchEndY - touchStartY > swipeThreshold) {
        // Swiped down - show header
        const header = document.querySelector('header');
        if (header) {
          header.style.transform = 'translateY(0)';
          header.style.transition = 'transform 0.3s ease';
        }
      }
    }
  }

  // Fix form input zoom on iOS
  if (isIOS) {
    document.addEventListener('DOMContentLoaded', function() {
      const inputs = document.querySelectorAll('input[type="text"], input[type="email"], input[type="tel"], input[type="password"], textarea, select');
      
      inputs.forEach(input => {
        input.addEventListener('focus', function() {
          // Prevent zoom by temporarily changing viewport
          const viewport = document.querySelector('meta[name="viewport"]');
          if (viewport) {
            viewport.setAttribute('content', 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0');
          }
        });
        
        input.addEventListener('blur', function() {
          // Restore viewport
          const viewport = document.querySelector('meta[name="viewport"]');
          if (viewport) {
            viewport.setAttribute('content', 'width=device-width, initial-scale=1.0, maximum-scale=5.0');
          }
        });
      });
    });
  }

  // Fix scroll position on page load
  if ('scrollRestoration' in history) {
    history.scrollRestoration = 'manual';
  }

  // Debounce function for performance
  function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
      const later = () => {
        clearTimeout(timeout);
        func(...args);
      };
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
    };
  }

  // Handle orientation changes
  window.addEventListener('orientationchange', debounce(function() {
    // Force reflow to fix layout issues
    document.body.style.display = 'none';
    document.body.offsetHeight; // Force reflow
    document.body.style.display = '';
    
    // Recalculate viewport height
    setViewportHeight();
    
    // Close mobile menu if open
    const navMenu = document.getElementById('navMenu');
    const menuToggle = document.querySelector('.menu-toggle');
    if (navMenu && navMenu.classList.contains('active')) {
      navMenu.classList.remove('active');
      if (menuToggle) menuToggle.classList.remove('active');
    }
  }, 300));

  // Fix overflow issues on specific elements
  function fixOverflowElements() {
    const problemElements = document.querySelectorAll('.hero-banner, .vehicle-showcase, .services-section');
    
    problemElements.forEach(element => {
      if (element.scrollWidth > element.clientWidth) {
        element.style.overflowX = 'hidden';
      }
    });
  }

  // Initialize all fixes when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  function init() {
    if (isMobile) {
      enhanceMobileMenu();
      enhanceTouchEvents();
      fixOverflowElements();
      
      // Add mobile class to body
      document.body.classList.add('mobile-device');
      
      if (isIOS) {
        document.body.classList.add('ios-device');
      }
    }
    
    // Log successful initialization
    console.log('Mobile fixes initialized successfully');
  }

  // Expose utility functions globally if needed
  window.mobileUtils = {
    isMobile: isMobile,
    isIOS: isIOS,
    setViewportHeight: setViewportHeight
  };

})();