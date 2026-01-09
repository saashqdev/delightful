// ==UserScript==
// @name         Maiji DongFang Caifuhao Page Cleaner
// @namespace    https://bedelightful.ai/
// @version      1.1
// @description  Clean DongFang Caifuhao website pages, focus on article content, remove distraction elements.
// @author       cc, cc@delightful.ai
// @match        *://caifuhao.eastmoney.com/news/*
// @grant        none
// ==/UserScript==

(function() {
  'use strict';

  // --- 1. Locate core content element ---
  // Try generic selector first, then try more precise selector if failed
  let contentElement = document.querySelector('div.article.page-article') ||
                       document.querySelector('#main > div.grid_wrapper > div.grid > div.g_content > div.article.page-article');

  // If both methods cannot find the element, exit the script
  if (!contentElement) {
    console.error('Maiji cleaner script: Failed to find target article element.');
    return;
  }

  // --- 2. Define retention rules ---
  // Check if an element should be kept: is the target element itself or its inner element
  const shouldKeepVisible = (element) => {
    return element === contentElement || contentElement.contains(element);
  };

  // --- 3. Iterate and collect elements to hide ---
  const walker = document.createTreeWalker(
    document.body,
    NodeFilter.SHOW_ELEMENT, // Only care about element nodes
    null,
    false
  );

  const elementsToHide = [];
  let currentNode = walker.nextNode();
  while (currentNode) {
    // If current node should not be kept, add to elements to hide list
    if (!shouldKeepVisible(currentNode)) {
      elementsToHide.push(currentNode);
    }
    currentNode = walker.nextNode();
  }

  // --- 4. Execute hiding uniformly ---
  // Batch hiding can slightly improve performance, reduce page repaint/reflow times
  elementsToHide.forEach(element => {
    // For safety, confirm again that body and html are not hidden (although theoretically shouldKeepVisible will exclude them)
    if (element !== document.body && element !== document.documentElement) {
        element.style.display = 'none';
        element.style.visibility = 'hidden';
    }
  });

  // --- 5. Ensure target element and all its ancestors are visible ---
  // Previous hiding operations may affect ancestors of target element, need to forcefully restore their visibility
  let ancestor = contentElement;
  while (ancestor && ancestor !== document.documentElement) { // Traverse up until parent of <html> (null)
    ancestor.style.display = ''; // Clear possible display:none
    ancestor.style.visibility = 'visible'; // Ensure visible

    // Restore default scroll behavior of body and html (if previously hidden)
    if (ancestor === document.body || ancestor === document.documentElement) {
        ancestor.style.overflow = '';
    }
    ancestor = ancestor.parentElement;
  }
  // Ensure html element is separately visible (loop ends here)
  if (document.documentElement) {
      document.documentElement.style.display = '';
      document.documentElement.style.visibility = 'visible';
      document.documentElement.style.overflow = '';
  }


  // --- 6. Apply styles, beautify and center target element ---
  // Use Flexbox to center on parent element
  const parentElement = contentElement.parentElement;
  if (parentElement) {
    parentElement.style.display = 'flex';
    parentElement.style.justifyContent = 'center'; // Horizontal centering
    parentElement.style.alignItems = 'flex-start';  // Vertical top alignment
    parentElement.style.minHeight = '100vh';       // Parent element at least fills viewport height
    parentElement.style.padding = '40px 10px';     // Parent element top/bottom padding 40px, left/right 10px
    parentElement.style.boxSizing = 'border-box';  // Padding does not increase parent element size
    parentElement.style.width = '100%';          // Parent element fills available width
  }

  // Set target element's own style
  Object.assign(contentElement.style, {
    display: 'block',
    width: '90%',            // Slightly wider to accommodate parent element left/right padding
    maxWidth: '800px',
    margin: '0',             // Remove margin, controlled by parent Flexbox
    padding: '25px 30px',    // Inner padding
    backgroundColor: 'white',
    boxShadow: '0 3px 12px rgba(0,0,0,0.1)', // Adjust shadow
    border: '1px solid #eee', // Add thin border
    borderRadius: '4px',      // Subtle rounded corners
    // Clean up possible residual positioning and size constraints
    position: '',
    left: '',
    top: '',
    transform: '',
    zIndex: '',
    height: '',
    overflowY: ''
  });

  console.log('Maiji DongFang Caifuhao cleaner optimized script completed');
})();
