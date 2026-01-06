// ==UserScript==
// @name         Delightful Sohu Ultra Clean
// @namespace    https://dtyq.com/
// @version      1.0
// @description  Clean Sohu website pages, keep only article content, remove ads and other distracting elements
// @author       cc, cc@dtyq.com
// @match        *://www.sohu.com/a/*
// @grant        none
// ==/UserScript==

(function() {
  'use strict';

  // Find element with data-spm="content" attribute
  const contentElement = document.querySelector('div[data-spm="content"]');

  // Ensure element exists
  if (!contentElement) {
    console.error('Failed to find data-spm="content" element');
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

  // 居中定位目标元素
  contentElement.style.position = 'absolute';
  contentElement.style.left = '50%';
  contentElement.style.transform = 'translate(-50%, 0)';
  contentElement.style.zIndex = '9999';
})();
