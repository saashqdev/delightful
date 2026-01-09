// ==UserScript==
// @name         Delightful 36Kr Ultra Clean
// @namespace    https://bedelightful.ai/
// @version      1.0
// @description  Clean 36Kr website pages, keep only article content area, provide a cleaner reading experience
// @author       cc, cc@delightful.ai
// @match        *://36kr.com/p/*
// @grant        none
// ==/UserScript==

(function() {
  'use strict';

  // Find element with .article-content class
  const contentElement = document.querySelector('.article-content');

  // Ensure element exists
  if (!contentElement) {
    console.error('Failed to find .article-content element');
    return;
  }

  // Create a function to check if an element should remain visible
  const shouldKeepVisible = (element) => {
    return element === contentElement ||
           element.contains(contentElement) ||
           contentElement.contains(element) ||
           element === document.body ||
           element === document.documentElement;
  };

  // Use TreeWalker API to efficiently traverse DOM tree
  const walker = document.createTreeWalker(
    document.body,
    NodeFilter.SHOW_ELEMENT,
    null,
    false
  );

  // Save found elements that need to be hidden
  const elementsToHide = [];

  // Start traversing
  let currentNode = walker.nextNode();
  while (currentNode) {
    if (!shouldKeepVisible(currentNode)) {
      elementsToHide.push(currentNode);
    }
    currentNode = walker.nextNode();
  }

  // Hide elements uniformly, reduce reflow
  elementsToHide.forEach(element => {
    element.style.display = 'none';
  });

  // Ensure target element is visible
  contentElement.style.display = 'block';

  // Ensure all elements on path from body to target element are visible
  let parent = contentElement.parentElement;
  while (parent) {
    parent.style.display = '';
    parent = parent.parentElement;
  }

  // Ensure body and html can scroll normally
  document.body.style.overflow = 'auto';
  document.documentElement.style.overflow = 'auto';
  document.body.style.height = 'auto';
  document.documentElement.style.height = 'auto';

  // Create a container to wrap content
  const container = document.createElement('div');
  container.style.width = '100%';
  container.style.maxWidth = '800px';
  container.style.margin = '0 auto';
  container.style.padding = '20px';
  container.style.backgroundColor = '#fff';
  container.style.boxSizing = 'border-box';

  // Move content to new container
  const parent2 = contentElement.parentElement;
  parent2.insertBefore(container, contentElement);
  container.appendChild(contentElement);

  // Reset content styles, use relative positioning rather than absolute
  contentElement.style.position = 'relative';
  contentElement.style.left = 'auto';
  contentElement.style.transform = 'none';
  contentElement.style.zIndex = '9999';
  contentElement.style.width = '100%';

  // Clean up styles elsewhere on the page that might interfere with scrolling
  const elementsWithFixedPosition = document.querySelectorAll('[style*="position: fixed"]');
  elementsWithFixedPosition.forEach(element => {
    if (!shouldKeepVisible(element)) {
      element.style.display = 'none';
    }
  });
})();
