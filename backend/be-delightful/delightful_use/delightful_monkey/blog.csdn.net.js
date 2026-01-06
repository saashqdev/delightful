// ==UserScript==
// @name         Delightful CSDN Blog Ultra Clean
// @namespace    https://dtyq.com/
// @version      1.0
// @description  Clean up CSDN blog pages, remove ads, login prompts and other distracting content, provide pure reading experience
// @author       cc, cc@delightful.ai
// @match        *://blog.csdn.net/*/article/details/*
// @grant        none
// ==/UserScript==

(function() {
  'use strict';

  // Define a function to clean up CSDN blog pages
  function cleanupCSDN() {
    // Get the main content area
    const mainContent = document.querySelector("#mainBox > main > div.blog-content-box");

    if (!mainContent) {
      console.log("Main content area not found, may not be on CSDN blog page");
      return;
    }

    // Create a function to check if an element should be kept visible
    const shouldKeepVisible = (element) => {
      return element === mainContent ||
             element.contains(mainContent) ||
             mainContent.contains(element) ||
             element === document.body ||
             element === document.documentElement;
    };

    // Use TreeWalker API to efficiently traverse the DOM tree
    const walker = document.createTreeWalker(
      document.body,
      NodeFilter.SHOW_ELEMENT,
      null,
      false
    );

    // Save elements found that need to be hidden
    const elementsToHide = [];

    // Start traversal
    let currentNode = walker.nextNode();
    while (currentNode) {
      if (!shouldKeepVisible(currentNode)) {
        elementsToHide.push(currentNode);
      }
      currentNode = walker.nextNode();
    }

    // Hide elements uniformly to reduce reflow
    elementsToHide.forEach(element => {
      element.style.display = 'none';
    });

    // Ensure target element is visible
    mainContent.style.display = 'block';

    // Ensure all elements on the path from body to target element are visible
    let parent = mainContent.parentElement;
    while (parent) {
      parent.style.display = 'block';
      parent = parent.parentElement;
    }

    // Ensure body and html can scroll normally
    document.body.style.overflow = 'auto';
    document.documentElement.style.overflow = 'auto';
    document.body.style.height = 'auto';
    document.documentElement.style.height = 'auto';
    document.body.style.backgroundColor = '#f5f5f5';

    // Create a container to wrap the content
    const container = document.createElement('div');
    container.style.width = '100%';
    container.style.maxWidth = '900px';
    container.style.margin = '0 auto';
    container.style.padding = '20px';
    container.style.backgroundColor = '#fff';
    container.style.boxSizing = 'border-box';
    container.style.boxShadow = '0 0 10px rgba(0, 0, 0, 0.1)';

    // Move content to new container
    const parent2 = mainContent.parentElement;
    parent2.insertBefore(container, mainContent);
    container.appendChild(mainContent);

    // Reset content styles, use relative positioning instead of absolute
    mainContent.style.position = 'relative';
    mainContent.style.left = 'auto';
    mainContent.style.transform = 'none';
    mainContent.style.zIndex = '1';
    mainContent.style.width = '100%';
    mainContent.style.margin = '0';
    mainContent.style.padding = '0';
    mainContent.style.boxShadow = 'none';

    // Ensure all elements in content are visible
    const allContentElements = mainContent.querySelectorAll('*');
    allContentElements.forEach(element => {
      element.style.display = '';
    });

    // Clean up styles that may interfere with scrolling elsewhere on the page
    const elementsWithFixedPosition = document.querySelectorAll('[style*="position: fixed"]');
    elementsWithFixedPosition.forEach(element => {
      if (!shouldKeepVisible(element)) {
        element.style.display = 'none';
      }
    });

    console.log("CSDN page has been cleaned, showing main content only");
  }

  // Execute cleanup after DOM loading is complete
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", cleanupCSDN);
  } else {
    cleanupCSDN();
  }

  // Execute again after page fully loads to handle delayed-loaded elements
  window.addEventListener("load", cleanupCSDN);
})();
