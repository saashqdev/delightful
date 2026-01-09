// ==UserScript==
// @name         Delightful East Money Finance Ultra Clean
// @namespace    https://bedelightful.ai/
// @version      1.6
// @description  Clean East Money Finance website pages, focus on article content and title, remove distracting elements. Auto-close specific popups.
// @author       Gemini & DTYQ
// @match        *://finance.eastmoney.com/a/*
// @grant        none
// ==/UserScript==

(function() {
  'use strict';

  // --- 1. Locate core element ---
  let titleElement = document.querySelector('#topbox'); // Locate title area
  let mainContentContainer = document.querySelector('div.mainleft'); // **New: Prioritize locating .mainleft**
  let contentElement = null; // Will search for specific content element in mainContentContainer later

  // If .mainleft cannot be found, try original strategy to find content element
  if (!mainContentContainer) {
      console.warn('Delightful script: Failed to find .mainleft container, trying to find internal content element...');
      contentElement = document.querySelector('div.newsContent') ||
                         document.querySelector('div.article_body') ||
                         document.querySelector('#ContentBody');
  } else {
      // If .mainleft found, search for specific content element within it (optional, mainly for styling)
      contentElement = mainContentContainer.querySelector('div.newsContent') ||
                         mainContentContainer.querySelector('div.article_body') ||
                         mainContentContainer.querySelector('#ContentBody') ||
                         mainContentContainer; // If no specific element found, use .mainleft itself as content element
  }

  // Determine primary styling target (prioritize .mainleft)
  const primaryContentTarget = mainContentContainer || contentElement;

  // If no form of content area found, exit
  if (!primaryContentTarget) {
    console.error('Delightful script: Failed to find any target content element (finance.eastmoney.com).');
    return;
  }
  // If title not found, also print info, but don't exit
  if (!titleElement) {
      console.warn('Delightful script: Failed to find title element #topbox, will only keep article content.');
  }

  // --- 2. Define retention rules ---
  const shouldKeepVisible = (element) => {
    // Keep title area and internal elements
    if (titleElement && (element === titleElement || titleElement.contains(element))) {
        return true;
    }
    // **Modified: Keep main content container and internal elements**
    if (primaryContentTarget && (element === primaryContentTarget || primaryContentTarget.contains(element))) {
        return true;
    }
    return false;
  };

  // --- 3. Traverse and collect elements to hide ---
  const walker = document.createTreeWalker(
    document.body,
    NodeFilter.SHOW_ELEMENT,
    null,
    false
  );

  const elementsToHide = [];
  let currentNode = walker.nextNode();
  while (currentNode) {
    // Skip elements that might be managed by scripts (although this script doesn't inject)
    if (currentNode.closest && currentNode.closest('[data-userscript-managed]')) {
        currentNode = walker.nextNode();
        continue;
    }

    // If current node should not be kept, add to hide list
    if (!shouldKeepVisible(currentNode)) {
      elementsToHide.push(currentNode);
    }
    currentNode = walker.nextNode();
  }

  // --- 4. Execute hiding uniformly ---
  elementsToHide.forEach(element => {
    // Again confirm not hiding body and html
    if (element !== document.body && element !== document.documentElement) {
        element.style.setProperty('display', 'none', 'important'); // Use !important to increase priority
        element.style.setProperty('visibility', 'hidden', 'important');
    }
  });

  // --- 5. Ensure target element and all ancestors are visible ---
  const ensureVisible = (targetElement) => {
      if (!targetElement) return; // Skip if element doesn't exist
      let ancestor = targetElement;
      while (ancestor && ancestor !== document.documentElement) {
        ancestor.style.setProperty('display', '', ''); // Clear possible display:none
        ancestor.style.setProperty('visibility', 'visible', 'important'); // Force visible

        // Restore body and html default scroll behavior
        if (ancestor === document.body || ancestor === document.documentElement) {
            ancestor.style.overflow = '';
        }
        ancestor = ancestor.parentElement;
      }
  };

  ensureVisible(titleElement); // Ensure title is visible
  ensureVisible(primaryContentTarget); // **Modified: Ensure main content container is visible**

  // Separately ensure html element is visible (most cases already handled by ensureVisible logic)
  if (document.documentElement) {
      document.documentElement.style.setProperty('display', '', '');
      document.documentElement.style.setProperty('visibility', 'visible', 'important');
      document.documentElement.style.overflow = '';
  }


  // --- 6. Apply styles, beautify and center target element ---

  // **New: Forcefully reset body's critical styles to prevent interference**
  document.body.style.setProperty('width', 'auto', 'important');
  document.body.style.setProperty('margin', '0', 'important');
  document.body.style.setProperty('position', 'relative', 'important'); // Reset possible absolute/fixed
  document.body.style.setProperty('float', 'none', 'important'); // Clear possible float

  // Apply our expected body styles as flex container
  const bodyStyle = {
      display: 'flex',
      flexDirection: 'column',
      alignItems: 'center',
      minHeight: '100vh',
      padding: '40px 10px',
      boxSizing: 'border-box',
      backgroundColor: '#f0f2f5'
  };
  // Apply styles using setProperty to increase priority
  for (const [key, value] of Object.entries(bodyStyle)) {
      // Convert camelCase to kebab-case
      const kebabKey = key.replace(/[A-Z]/g, match => `-${match.toLowerCase()}`);
      document.body.style.setProperty(kebabKey, value, 'important');
  }


  // Set title area styles
  if (titleElement) {
      Object.assign(titleElement.style, {
        display: 'block',
        visibility: 'visible',
        width: '90%',
        maxWidth: '850px',
        margin: '0 0 20px 0',
        padding: '20px 30px',
        backgroundColor: 'white',
        boxShadow: '0 2px 8px rgba(0,0,0,0.1)',
        border: '1px solid #e8e8e8',
        borderRadius: '5px',
        position: 'relative',
        float: 'none',
        left: '',
        top: '',
        zIndex: 'auto'
      });
  }

  // **Modified: Set .mainleft or fallback content container styles**
  Object.assign(primaryContentTarget.style, {
    display: 'block',
    visibility: 'visible',
    width: '90%',
    maxWidth: '850px',
    margin: '0', // **Modified: Remove margin: 0 auto to let body's align-items take effect**
    padding: '30px 40px',
    backgroundColor: 'white',
    boxShadow: '0 4px 15px rgba(0,0,0,0.12)',
    border: '1px solid #e8e8e8',
    borderRadius: '5px',
    position: 'relative',
    float: 'none', // **New: Clear float**
    left: '',
    top: '',
    transform: '',
    zIndex: 'auto',
    height: '',
    overflow: 'visible' // **Modified: Allow content to overflow container (if needed) or set to auto**
  });

  // If contentElement is an element inside .mainleft, may need to reset its margin/padding
  if (mainContentContainer && contentElement && contentElement !== mainContentContainer) {
      contentElement.style.margin = '0';
      contentElement.style.padding = '0';
  }

  console.log('Delightful East Money Finance Ultra Clean script (v1.5) styles applied');

  // --- 7. Listen and auto-close specific popups ---
  // **Modified: Select only based on src contains close.png, removed onclick requirement.**
  // **Warning: This selector might be too broad, if page elsewhere has non-close image with src containing close.png, it may also be clicked.**
  const closeButtonSelector = 'img[src*="close.png"]';

  const observerCallback = function(mutationsList, observer) {
      for(const mutation of mutationsList) {
          if (mutation.type === 'childList') {
              // Check if any new nodes were added
              mutation.addedNodes.forEach(node => {
                  // Check if added node itself or descendants match close button
                  if (node.nodeType === Node.ELEMENT_NODE) {
                      let closeButton = null;
                      if (node.matches(closeButtonSelector)) {
                          closeButton = node;
                      } else if (node.querySelector) { // Element might not have querySelector (e.g., text node)
                          closeButton = node.querySelector(closeButtonSelector);
                      }

                      if (closeButton && closeButton.offsetParent !== null) { // Check if button is visible (not display:none and has size)
                          console.log('Delightful clean: Detected popup close button, attempting auto-close...');
                          closeButton.click();
                          // Optional: Can disconnect after finding, if popup appears only once
                          // observer.disconnect();
                          // console.log('Delightful clean: Popup closed and monitoring stopped.');
                      }
                  }
              });
          }
      }
  };

  // Create an observer instance and pass callback function
  const observer = new MutationObserver(observerCallback);

  // Configure observer options:
  const config = { childList: true, subtree: true }; // Listen to child node changes and all descendant node changes

  // Select target node to start observing (usually body)
  const targetNode = document.body;
  if (targetNode) {
      observer.observe(targetNode, config);
      console.log('Delightful clean: Started popup close button listener (based on src*="close.png").');
  }

  // Initial check once, in case popup loaded before script ran
  const initialCloseButton = document.querySelector(closeButtonSelector);
  if (initialCloseButton && initialCloseButton.offsetParent !== null) {
      console.log('Delightful clean: Detected initial popup close button (based on src*="close.png"), attempting auto-close...');
      initialCloseButton.click();
  }

})();
