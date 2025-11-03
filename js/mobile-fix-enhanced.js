/**
 * Enhanced Mobile Fix JavaScript
 * Improves mobile functionality for the Mitsubishi Motors website with better cross-browser compatibility
 */

(function() {
  'use strict';

  // Check if device is mobile
  const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
  const isIOS = /iPhone|iPad|iPod/i.test(navigator.userAgent);
  
  // Log initialization
  console.log('Enhanced Mobile Fix initializing...');
  console.log('Mobile device detected:', isMobile);
  console.log('iOS device detected:', isIOS);
  
  // Fix viewport height on mobile (especially iOS)
  function setViewportHeight() {
    try {
      // First we get the viewport height and multiply it by 1% to get a value for a vh unit
      let vh = window.innerHeight * 0.01;
      // Then we set the value in the --vh custom property to the root of the document
      document.documentElement.style.setProperty('--vh', `${vh}px`);
      console.log('Viewport height set:', vh + 'px');
    } catch (e) {
      console.error('Error setting viewport height:', e);
    }
  }

  // Set viewport height on load and resize
  try {
    setViewportHeight();
    window.addEventListener('resize', setViewportHeight);
    window.addEventListener('orientationchange', setViewportHeight);
  } catch (e) {
    console.error('Error setting viewport height event listeners:', e);
  }

  // Fix iOS bounce scrolling
  if (isIOS) {
    try {
      document.addEventListener('touchmove', function(e) {
        if (e.target.closest('nav.active')) {
          // Allow scrolling in navigation menu
          return;
        }
      }, { passive: true });
    } catch (e) {
      console.error('Error setting iOS touchmove listener:', e);
    }
  }

  // Enhanced smooth scrolling for anchor links
  document.addEventListener('DOMContentLoaded', function() {
    try {
      // Smooth scroll for all anchor links
      const anchorLinks = document.querySelectorAll('a[href^="#"]');
      
      anchorLinks.forEach(link => {
        link.addEventListener('click', function(e) {
          try {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            
            if (targetId === '#') return;
            
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
              const headerHeight = document.querySelector('header')?.offsetHeight || 70;
              const targetPosition = targetElement.offsetTop - headerHeight - 20;
              
              // Use modern scroll behavior if available
              if (window.scrollTo) {
                window.scrollTo({
                  top: targetPosition,
                  behavior: 'smooth'
                });
              } else {
                // Fallback for older browsers
                window.scrollTop = targetPosition;
              }
            }
          } catch (e) {
            console.error('Error in anchor link handler:', e);
          }
        });
      });
    } catch (e) {
      console.error('Error setting up anchor link handlers:', e);
    }
  });

  // Fix mobile menu scrolling
  function enhanceMobileMenu() {
    try {
      const navMenu = document.getElementById('navMenu');
      const menuToggle = document.querySelector('.menu-toggle');
      const body = document.body;
      
      if (menuToggle && navMenu) {
        console.log('Mobile menu elements found, setting up event listeners');
        
        menuToggle.addEventListener('click', function() {
          try {
            console.log('Menu toggle clicked');
            // Toggle body scroll when menu is active
            if (navMenu.classList.contains('active')) {
              console.log('Menu closing');
              body.style.overflow = '';
              body.style.position = '';
              body.style.top = '';
              body.style.width = '';
            } else {
              console.log('Menu opening');
              // Store current scroll position
              const scrollY = window.scrollY || window.pageYOffset;
              body.style.position = 'fixed';
              body.style.top = `-${scrollY}px`;
              body.style.width = '100%';
              body.style.overflow = 'hidden';
            }
            
            // Toggle active classes
            navMenu.classList.toggle('active');
            menuToggle.classList.toggle('active');
            console.log('Menu state toggled');
          } catch (e) {
            console.error('Error in menu toggle handler:', e);
          }
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
          try {
            if (navMenu.classList.contains('active') && 
                !e.target.closest('nav') && 
                !e.target.closest('.menu-toggle')) {
              console.log('Closing menu due to outside click');
              navMenu.classList.remove('active');
              menuToggle.classList.remove('active');
              
              // Restore scroll position
              const scrollY = body.style.top;
              body.style.position = '';
              body.style.top = '';
              body.style.width = '';
              body.style.overflow = '';
              
              if (scrollY) {
                const scrollPosition = parseInt(scrollY || '0') * -1;
                window.scrollTo(0, scrollPosition);
              }
            }
          } catch (e) {
            console.error('Error in outside click handler:', e);
          }
        });
      } else {
        console.warn('Mobile menu elements not found');
      }
    } catch (e) {
      console.error('Error in enhanceMobileMenu:', e);
    }
  }

  // Fix touch event delays
  function enhanceTouchEvents() {
    try {
      let touchStartY = 0;
      let touchEndY = 0;
      
      document.addEventListener('touchstart', function(e) {
        try {
          touchStartY = e.changedTouches[0].screenY;
        } catch (e) {
          console.error('Error in touchstart handler:', e);
        }
      }, { passive: true });
      
      document.addEventListener('touchend', function(e) {
        try {
          touchEndY = e.changedTouches[0].screenY;
          handleSwipe();
        } catch (e) {
          console.error('Error in touchend handler:', e);
        }
      }, { passive: true });
      
      function handleSwipe() {
        try {
          const swipeThreshold = 50;
          const navMenu = document.getElementById('navMenu');
          
          if (touchStartY - touchEndY > swipeThreshold) {
            // Swiped up - hide header on scroll down
            const header = document.querySelector('header');
            if (header && window.scrollY > 100) {
              header.style.transform = 'translateY(-100%)';
              header.style.webkitTransform = 'translateY(-100%)';
              header.style.mozTransform = 'translateY(-100%)';
              header.style.msTransform = 'translateY(-100%)';
              header.style.transition = 'transform 0.3s ease';
              header.style.webkitTransition = 'transform 0.3s ease';
            }
          }
          
          if (touchEndY - touchStartY > swipeThreshold) {
            // Swiped down - show header
            const header = document.querySelector('header');
            if (header) {
              header.style.transform = 'translateY(0)';
              header.style.webkitTransform = 'translateY(0)';
              header.style.mozTransform = 'translateY(0)';
              header.style.msTransform = 'translateY(0)';
              header.style.transition = 'transform 0.3s ease';
              header.style.webkitTransition = 'transform 0.3s ease';
            }
          }
        } catch (e) {
          console.error('Error in handleSwipe:', e);
        }
      }
    } catch (e) {
      console.error('Error in enhanceTouchEvents:', e);
    }
  }

  // Fix form input zoom on iOS
  if (isIOS) {
    try {
      document.addEventListener('DOMContentLoaded', function() {
        const inputs = document.querySelectorAll('input[type="text"], input[type="email"], input[type="tel"], input[type="password"], textarea, select');
        
        inputs.forEach(input => {
          try {
            input.addEventListener('focus', function() {
              try {
                // Prevent zoom by temporarily changing viewport
                const viewport = document.querySelector('meta[name="viewport"]');
                if (viewport) {
                  viewport.setAttribute('content', 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0');
                }
              } catch (e) {
                console.error('Error in input focus handler:', e);
              }
            });
            
            input.addEventListener('blur', function() {
              try {
                // Restore viewport
                const viewport = document.querySelector('meta[name="viewport"]');
                if (viewport) {
                  viewport.setAttribute('content', 'width=device-width, initial-scale=1.0, maximum-scale=5.0');
                }
              } catch (e) {
                console.error('Error in input blur handler:', e);
              }
            });
          } catch (e) {
            console.error('Error setting up input event listeners:', e);
          }
        });
      });
    } catch (e) {
      console.error('Error in iOS input zoom fix:', e);
    }
  }

  // Fix scroll position on page load
  try {
    if ('scrollRestoration' in history) {
      history.scrollRestoration = 'manual';
    }
  } catch (e) {
    console.error('Error setting scroll restoration:', e);
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
  try {
    window.addEventListener('orientationchange', debounce(function() {
      try {
        console.log('Orientation changed, applying fixes');
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
      } catch (e) {
        console.error('Error in orientation change handler:', e);
      }
    }, 300));
  } catch (e) {
    console.error('Error setting orientation change handler:', e);
  }

  // Fix overflow issues on specific elements
  function fixOverflowElements() {
    try {
      const problemElements = document.querySelectorAll('.hero-banner, .vehicle-showcase, .services-section');
      
      problemElements.forEach(element => {
        try {
          if (element.scrollWidth > element.clientWidth) {
            element.style.overflowX = 'hidden';
          }
        } catch (e) {
          console.error('Error fixing overflow for element:', e);
        }
      });
    } catch (e) {
      console.error('Error in fixOverflowElements:', e);
    }
  }

  // Initialize all fixes when DOM is ready
  function init() {
    try {
      console.log('Initializing mobile fixes...');
      if (isMobile) {
        enhanceMobileMenu();
        enhanceTouchEvents();
        fixOverflowElements();
        
        // Add mobile class to body
        document.body.classList.add('mobile-device');
        
        if (isIOS) {
          document.body.classList.add('ios-device');
        }
        
        console.log('Mobile fixes initialized successfully');
      } else {
        console.log('Non-mobile device detected, skipping mobile fixes');
      }
    } catch (e) {
      console.error('Error in init function:', e);
    }
  }

  // Wait for DOM to be ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    // DOM is already ready
    init();
  }

  // Expose utility functions globally if needed
  window.mobileUtils = {
    isMobile: isMobile,
    isIOS: isIOS,
    setViewportHeight: setViewportHeight
  };

  console.log('Enhanced Mobile Fix loaded successfully');
})();