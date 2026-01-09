// ==UserScript==
// @name         Delightful Baidu Search Ultra Clean
// @namespace    https://bedelightful.ai/
// @version      1.0
// @description  Clean Baidu search pages, remove right-side ads, bottom user info bar and footer, provide a pure search experience
// @author       cc, cc@delightful.ai
// @match        *://www.baidu.com/s*
// @grant        none
// ==/UserScript==

(function() {
    'use strict';

    // Create a function to remove specific elements
    function removeElements() {
        // Remove right-side content (ads, etc.)
        const rightContent = document.getElementById('content_right');
        if (rightContent) {
            rightContent.remove();
            console.log('Right-side content box removed');
        }

        // Remove bottom user info bar
        const userBar = document.getElementById('u');
        if (userBar) {
            userBar.remove();
            console.log('Bottom user info bar removed');
        }

        // Remove footer
        const footerElements = document.querySelectorAll('[tpl="app/footer"]');
        footerElements.forEach(element => {
            element.remove();
            console.log('Footer element removed');
        });
    }

    removeElements();
})();
