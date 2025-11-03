/**
 * Comprehensive Mobile Responsiveness Fix - JavaScript
 * Handles dynamic fixes for horizontal overflow and text truncation
 * Version: 2.0
 */

(function() {
  'use strict';

  // Device detection
  const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
  const isTablet = /iPad|Android/i.test(navigator.userAgent) && window.innerWidth >= 768;
  const isIOS = /iPhone|iPad|iPod/i.test(navigator.userAgent);
  const isAndroid = /Android/i.test(navigator.userAgent);

  console.log('Mobile Responsive Fix initializing...');
  console.log('Device info:', { isMobile, isTablet, isIOS, isAndroid });

  /**
   * Fix viewport and prevent horizontal scroll
   */
  function fixViewport() {
    try {
      // Set viewport meta tag
      let viewport = document.querySelector('meta[name="viewport"]');
      if (!viewport) {
        viewport = document.createElement('meta');
        viewport.name = 'viewport';
        document.head.appendChild(viewport);
      }
      viewport.content = 'width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes';

      // Calculate and set viewport height
      const vh = window.innerHeight * 0.01;
      document.documentElement.style.setProperty('--vh', `${vh}px`);

      // Prevent horizontal scroll
      document.documentElement.style.overflowX = 'hidden';
      document.body.style.overflowX = 'hidden';
      
      console.log('Viewport fixed');
    } catch (e) {
      console.error('Error fixing viewport:', e);
    }
  }

  /**
   * Fix all elements that might cause horizontal overflow
   */
  function fixOverflowElements() {
    try {
      // Find all elements that are wider than viewport
      const bodyWidth = document.body.clientWidth;
      const allElements = document.querySelectorAll('*');
      
      allElements.forEach(element => {
        try {
          if (element.scrollWidth > bodyWidth) {
            element.style.maxWidth = '100%';
            element.style.overflowX = 'hidden';
            console.log('Fixed overflow on:', element.tagName, element.className);
          }
        } catch (e) {
          // Skip elements that throw errors
        }
      });

      console.log('Overflow elements fixed');
    } catch (e) {
      console.error('Error fixing overflow elements:', e);
    }
  }

  /**
   * Fix text truncation in all elements
   */
  function fixTextTruncation() {
    try {
      // Fix common text elements
      const textSelectors = [
        'p', 'span', 'div', 'td', 'th', 'li',
        '.text', '.title', '.description',
        '.card-text', '.card-title',
        '.table-text', '.content-text'
      ];

      textSelectors.forEach(selector => {
        try {
          const elements = document.querySelectorAll(selector);
          elements.forEach(element => {
            element.style.wordWrap = 'break-word';
            element.style.overflowWrap = 'break-word';
            element.style.whiteSpace = 'normal';
            element.style.maxWidth = '100%';
          });
        } catch (e) {
          // Skip if selector not found
        }
      });

      console.log('Text truncation fixed');
    } catch (e) {
      console.error('Error fixing text truncation:', e);
    }
  }

  /**
   * Fix tables for mobile responsiveness
   */
  function fixTables() {
    try {
      const tables = document.querySelectorAll('table');
      
      tables.forEach(table => {
        // Wrap table in responsive container if not already wrapped
        if (!table.parentElement.classList.contains('table-responsive')) {
          const wrapper = document.createElement('div');
          wrapper.className = 'table-responsive';
          wrapper.style.width = '100%';
          wrapper.style.overflowX = 'auto';
          wrapper.style.webkitOverflowScrolling = 'touch';
          
          table.parentNode.insertBefore(wrapper, table);
          wrapper.appendChild(table);
        }

        // Fix table cells
        const cells = table.querySelectorAll('td, th');
        cells.forEach(cell => {
          cell.style.whiteSpace = 'normal';
          cell.style.wordWrap = 'break-word';
          cell.style.maxWidth = '300px';
        });

        // Add data labels for mobile view
        if (window.innerWidth <= 991) {
          const headers = Array.from(table.querySelectorAll('th')).map(th => th.textContent.trim());
          const rows = table.querySelectorAll('tbody tr');
          
          rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            cells.forEach((cell, index) => {
              if (headers[index]) {
                cell.setAttribute('data-label', headers[index]);
              }
            });
          });
        }
      });

      console.log('Tables fixed:', tables.length);
    } catch (e) {
      console.error('Error fixing tables:', e);
    }
  }

  /**
   * Fix cards and containers
   */
  function fixCards() {
    try {
      const cardSelectors = [
        '.card', '.card-body', '.card-header',
        '.container', '.wrapper', '.content'
      ];

      cardSelectors.forEach(selector => {
        try {
          const elements = document.querySelectorAll(selector);
          elements.forEach(element => {
            element.style.maxWidth = '100%';
            element.style.width = '100%';
            element.style.boxSizing = 'border-box';
          });
        } catch (e) {
          // Skip if selector not found
        }
      });

      console.log('Cards fixed');
    } catch (e) {
      console.error('Error fixing cards:', e);
    }
  }

  /**
   * Fix forms and inputs
   */
  function fixForms() {
    try {
      const inputs = document.querySelectorAll('input, select, textarea');
      
      inputs.forEach(input => {
        input.style.maxWidth = '100%';
        input.style.width = '100%';
        input.style.boxSizing = 'border-box';
        
        // Prevent zoom on iOS
        if (isIOS) {
          if (input.style.fontSize && parseFloat(input.style.fontSize) < 16) {
            input.style.fontSize = '16px';
          } else if (!input.style.fontSize) {
            input.style.fontSize = '16px';
          }
        }
      });

      console.log('Forms fixed');
    } catch (e) {
      console.error('Error fixing forms:', e);
    }
  }

  /**
   * Fix images and media
   */
  function fixMedia() {
    try {
      const mediaElements = document.querySelectorAll('img, video, iframe, svg');
      
      mediaElements.forEach(element => {
        element.style.maxWidth = '100%';
        element.style.height = 'auto';
      });

      console.log('Media fixed');
    } catch (e) {
      console.error('Error fixing media:', e);
    }
  }

  /**
   * Fix grid layouts
   */
  function fixGrids() {
    try {
      if (window.innerWidth <= 991) {
        const gridSelectors = [
          '.row', '.grid', '.dashboard-grid',
          '.vehicle-grid', '.product-grid'
        ];

        gridSelectors.forEach(selector => {
          try {
            const grids = document.querySelectorAll(selector);
            grids.forEach(grid => {
              grid.style.display = 'flex';
              grid.style.flexDirection = 'column';
              grid.style.width = '100%';
              grid.style.gap = '15px';
            });
          } catch (e) {
            // Skip if selector not found
          }
        });

        // Fix columns
        const columns = document.querySelectorAll('[class*="col-"]');
        columns.forEach(col => {
          col.style.width = '100%';
          col.style.maxWidth = '100%';
          col.style.flex = '0 0 100%';
        });
      }

      console.log('Grids fixed');
    } catch (e) {
      console.error('Error fixing grids:', e);
    }
  }

  /**
   * Fix modals
   */
  function fixModals() {
    try {
      const modals = document.querySelectorAll('.modal, .modal-dialog, .modal-content');
      
      modals.forEach(modal => {
        if (window.innerWidth <= 991) {
          modal.style.width = '95%';
          modal.style.maxWidth = '95%';
          modal.style.margin = '20px auto';
        }
      });

      console.log('Modals fixed');
    } catch (e) {
      console.error('Error fixing modals:', e);
    }
  }

  /**
   * Detect and fix horizontal scroll
   */
  function detectHorizontalScroll() {
    try {
      const bodyWidth = document.body.clientWidth;
      const docWidth = document.documentElement.scrollWidth;
      
      if (docWidth > bodyWidth) {
        console.warn('Horizontal scroll detected! Body width:', bodyWidth, 'Document width:', docWidth);
        
        // Find the culprit element
        const allElements = document.querySelectorAll('*');
        allElements.forEach(element => {
          try {
            const rect = element.getBoundingClientRect();
            if (rect.right > bodyWidth || rect.left < 0) {
              console.warn('Element causing overflow:', element.tagName, element.className, 'Right:', rect.right, 'Left:', rect.left);
              element.style.maxWidth = '100%';
              element.style.overflowX = 'hidden';
            }
          } catch (e) {
            // Skip elements that throw errors
          }
        });
      } else {
        console.log('No horizontal scroll detected');
      }
    } catch (e) {
      console.error('Error detecting horizontal scroll:', e);
    }
  }

  /**
   * Fix header and navigation
   */
  function fixHeader() {
    try {
      const header = document.querySelector('header');
      if (header) {
        header.style.width = '100%';
        header.style.maxWidth = '100vw';
        header.style.overflowX = 'hidden';
      }

      const nav = document.querySelector('nav');
      if (nav && window.innerWidth <= 991) {
        nav.style.width = '100%';
        nav.style.flexDirection = 'column';
      }

      console.log('Header fixed');
    } catch (e) {
      console.error('Error fixing header:', e);
    }
  }

  /**
   * Debounce function
   */
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

  /**
   * Apply all fixes
   */
  function applyAllFixes() {
    console.log('Applying all responsive fixes...');
    
    fixViewport();
    fixOverflowElements();
    fixTextTruncation();
    fixTables();
    fixCards();
    fixForms();
    fixMedia();
    fixGrids();
    fixModals();
    fixHeader();
    detectHorizontalScroll();
    
    console.log('All fixes applied successfully');
  }

  /**
   * Initialize on DOM ready
   */
  function init() {
    try {
      console.log('Initializing mobile responsive fixes...');
      
      // Add device classes to body
      if (isMobile) document.body.classList.add('mobile-device');
      if (isTablet) document.body.classList.add('tablet-device');
      if (isIOS) document.body.classList.add('ios-device');
      if (isAndroid) document.body.classList.add('android-device');

      // Apply fixes
      applyAllFixes();

      // Reapply fixes on resize and orientation change
      const debouncedFixes = debounce(applyAllFixes, 300);
      window.addEventListener('resize', debouncedFixes);
      window.addEventListener('orientationchange', () => {
        setTimeout(applyAllFixes, 100);
      });

      // Fix on scroll (for lazy loaded content)
      let scrollTimeout;
      window.addEventListener('scroll', () => {
        clearTimeout(scrollTimeout);
        scrollTimeout = setTimeout(() => {
          fixMedia();
          fixCards();
        }, 500);
      }, { passive: true });

      // Observe DOM changes and reapply fixes
      if ('MutationObserver' in window) {
        const observer = new MutationObserver(debounce(() => {
          console.log('DOM changed, reapplying fixes...');
          applyAllFixes();
        }, 1000));

        observer.observe(document.body, {
          childList: true,
          subtree: true
        });
      }

      console.log('Mobile responsive fix initialized successfully');
    } catch (e) {
      console.error('Error initializing mobile responsive fix:', e);
    }
  }

  // Wait for DOM to be ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  // Expose utilities globally
  window.mobileResponsiveFix = {
    isMobile,
    isTablet,
    isIOS,
    isAndroid,
    applyAllFixes,
    fixTables,
    fixOverflowElements,
    detectHorizontalScroll
  };

  console.log('Mobile Responsive Fix loaded');
})();
