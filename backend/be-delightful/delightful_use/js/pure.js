/**
 * DelightfulPure - Make your web pages clean by automatically closing disruptive elements
 *
 * Features:
 * This script automatically detects and closes various popups, banners, notifications, and cookie prompts on web pages.
 * Applicable to common popup types on most websites, including but not limited to:
 * - Cookie consent prompts
 * - Advertisement popups
 * - Login prompts
 * - News notifications
 * - Various modal windows
 *
 * How it works:
 * 1. After DOM is loaded, the script periodically scans page elements
 * 2. Identifies possible close buttons through various identifiers (text content, class names, attribute values, etc.)
 * 3. Performs click operations on qualifying elements
 * 4. Includes two scan checks to ensure handling of delayed-loading popups
 *
 * Configuration and Extension:
 * - All detection rules are defined in the config object and can be extended as needed
 * - You can adjust keyword lists, class name matching, attribute checking, and other configuration items
 * - Check interval time can be modified via checkIntervalMs
 * - Supports custom rule sets for special handling rules for specific websites
 *
 * Usage:
 * 1. Add this script to the web page
 * 2. The script will run automatically and handle popups
 * 3. No user intervention required
 *
 * Notes:
 * - Script uses self-executing function wrapper, won't pollute global namespace
 * - All operations are logged in the console for easy debugging
 * - Designed with performance and compatibility in mind, avoiding excessive DOM queries
 */

(function () {
    /**
     * Configuration: Define keywords and attributes for finding elements
     */
    const config = {
        // Delay time between two checks (milliseconds)
        checkIntervalMs: 200,

        // Keywords indicating "close" or "skip" intent in text content or attribute values (lowercase)
        closeKeywords: ['skip', 'close', 'dismiss'],
        // Keywords indicating "accept" or "agree" intent in text content or attribute values (lowercase)
        acceptKeywords: ['accept', 'agree', 'got it'],
        // Keywords used in combination with acceptKeywords (e.g. "accept cookie", "accept all")
        acceptModifiers: ['cookie', 'all'],
        // List of attributes to check and their corresponding keywords
        attributesToCheck: {
            'value': ['close'], // value attribute is usually exact match
            'aria-label': ['close', 'dismiss', '×', 'x'],
            'title': ['close', 'dismiss', '×', 'x']
        },
        // Indicative keywords that may be contained in class names (lowercase)
        classKeywords: ['close', 'dismiss', 'accept', 'cookie', 'modal', '__close', '-close', 'overlay-dismiss', 'popup-close', 'popup__close'],
        // Excluded class name keywords, elements containing these keywords will be ignored (to avoid misclicking normal content)
        excludeClassKeywords: ['banner-left', 'banner-item', 'banner-info', 'info', 'content', 'navigation', 'menu', 'nav-item', 'article', 'popup-login', 'login', 'register'],
        // Excluded text content keywords, elements containing these texts will be ignored
        excludeTextKeywords: ['login', 'register', 'sign in', 'sign up'],

        // Custom rule set: Dedicated rules for specific websites
        customRules: [
            {
                // CSDN blog rule - close login popup
                domain: 'blog.csdn.net',
                selectors: [
                    '#passportbox > img',
                    'body > div.passport-login-tip-container.false > span'
                ],
                description: 'CSDN blog login popup close button'
            }
            // More website rules can be added
        ]
    };

    // Execution control variables
    const maxClicks = 2;            // Maximum number of clicks allowed
    const totalDurationMs = 5000;   // Total check duration (milliseconds)

    /**
     * Get the current website domain
     * @returns {string} The current website domain
     */
    function getCurrentDomain() {
        return window.location.hostname;
    }

    /**
     * Check and apply custom rules
     * @returns {boolean} Returns true if custom rule click was successfully triggered, otherwise returns false
     */
    function applyCustomRules() {
        const currentDomain = getCurrentDomain();

        // Find custom rules matching the current domain
        for (const rule of config.customRules) {
            if (currentDomain.includes(rule.domain)) {
                console.log(`Found custom rule matching current domain (${currentDomain}):`, rule.description);

                // Use custom selectors to find elements
                const elements = rule.selectors.map(selector => {
                    const element = document.querySelector(selector);
                    if (!element) {
                        console.log(`Custom rule selector did not find element:`, selector);
                    }
                    return element;
                }).filter(element => element !== null); // Filter out elements not found

                if (elements.length === 0) {
                    console.log(`All selectors for custom rule did not find any available elements`);
                    continue; // Continue checking the next rule
                }

                // Iterate through found elements, try to click the first visible element
                for (const element of elements) {
                    if (typeof element.click === 'function' && isElementVisible(element)) {
                        console.log(`Applying custom rule, clicking element:`, element);
                        try {
                            // Create and dispatch mousedown event
                            const mouseDownEvent = new MouseEvent('mousedown', {
                                bubbles: true,
                                cancelable: true,
                                view: window
                            });
                            element.dispatchEvent(mouseDownEvent);

                            // Create and dispatch mouseup event
                            const mouseUpEvent = new MouseEvent('mouseup', {
                                bubbles: true,
                                cancelable: true,
                                view: window
                            });
                            element.dispatchEvent(mouseUpEvent);

                            // Create and dispatch click event
                            const clickEvent = new MouseEvent('click', {
                                bubbles: true,
                                cancelable: true,
                                view: window
                            });
                            element.dispatchEvent(clickEvent);

                            console.log(`Custom rule element click successful.`);
                            return true; // Click successful, return true
                        } catch (clickError) {
                            console.error(`Error clicking element when applying custom rule:`, element, clickError);
                        }
                    } else {
                        console.log(`Element found but not visible or clickable`);
                    }
                }

                console.log(`Tried all selectors but did not find any visible and clickable element`);
            }
        }

        return false; // No matching rules found or no elements successfully clicked
    }

    /**
     * Check if a single element meets the criteria for auto-close/accept
     * @param {Element} element The element to check
     * @returns {boolean} Returns true if the element meets the criteria
     */
    function elementMatchesCriteria(element) {
        const text = (element.textContent || '').trim().toLowerCase();
        const classListStr = Array.from(element.classList).join(' ').toLowerCase();

        // 0. First check if it contains exclude class keywords, if so, exclude directly
        if (config.excludeClassKeywords.some(kw => classListStr.includes(kw))) {
            return false;
        }

        // 0.1 Check if it contains exclude text keywords, if so, exclude directly
        if (config.excludeTextKeywords && config.excludeTextKeywords.some(kw => text.includes(kw))) {
            return false;
        }

        // 1. Check text content
        if (config.closeKeywords.some(kw => text.includes(kw))) return true;
        const isAcceptKeywordMatch = config.acceptKeywords.some(kw => text.includes(kw));
        if (isAcceptKeywordMatch) {
            // Check if it contains modifiers (cookie/all) or just a standalone accept word
            if (config.acceptModifiers.some(mod => text.includes(mod)) || !text.includes(' ')) {
                return true;
            }
            // Exact match for single accept word (e.g., button only has "Accept")
            if (config.acceptKeywords.some(akw => text === akw)) return true;
        }

        // 2. Check specified attributes
        for (const attrName in config.attributesToCheck) {
            const attrValue = (element.getAttribute(attrName) || '');
            const keywords = config.attributesToCheck[attrName];
            const checkValue = (attrName === 'value') ? attrValue : attrValue.toLowerCase(); // value exact match, others lowercase comparison
            if (keywords.some(kw => checkValue.includes(kw))) {
                return true;
            }
        }

        // 3. Check class list - more precise matching to avoid false positives
        if (config.classKeywords.some(kw => {
            // Only match complete class name parts, e.g., "close" matches "btn-close" and "close-btn", but not "closeable"
            const parts = classListStr.split(' ');
            return parts.some(part =>
                part === kw ||
                part.startsWith(kw + '-') ||
                part.endsWith('-' + kw) ||
                part.includes('-' + kw + '-')
            );
        })) return true;

        return false;
    }

    /**
     * Check if an element and all its ancestor elements are actually visible in the DOM and have size greater than 0.
     * Key point: An element is only considered truly visible when all its ancestor elements are also visible (not hidden by CSS).
     * @param {Element | null} el The element to check
     * @returns {boolean} Returns true if the element and all its ancestors are considered visible
     */
    function isElementVisible(el) {
        if (!el) {
            return false; // Invalid element
        }

        // Check the element itself and its ancestors
        let elementToCheck = el;
        // Traverse up the DOM tree until document.body or no parent element
        while (elementToCheck && elementToCheck !== document.body) {
            const style = window.getComputedStyle(elementToCheck);

            // Check CSS visibility properties: display, visibility, opacity
            // If any ancestor level has these properties in hidden state, the target element is actually not visible
            if (style.display === 'none' || style.visibility === 'hidden' || parseFloat(style.opacity) === 0) {
                // No need to print log here, caller can print as needed
                return false; // Element or its ancestor is hidden by CSS
            }

            // Check size and offsetParent: only perform this check on the original target element
            // Reason: Parent element having size 0 (e.g., height: 0) doesn't necessarily mean child element is not visible (e.g., overflow: visible)
            // But the target element itself must have actual size and be in the layout flow (offsetParent !== null) to be considered visible
            if (elementToCheck === el) {
                const rect = el.getBoundingClientRect();
                // Check if width, height are greater than 0, and if element is in the render tree (offsetParent not null)
                if (!(rect.width > 0 && rect.height > 0 && el.offsetParent !== null)) {
                    // console.log("Element itself not visible (size/offsetParent):", el, rect); // Uncomment when debugging
                    return false;
                }
            }

            // Move to parent element to continue checking
            elementToCheck = elementToCheck.parentElement;
        }

        // If loop completes, it means all elements in the path from element to body have OK CSS visibility, and element itself has OK size
        return true;
    }

    /**
     * Single check and attempt to close annoying elements.
     * Find the first visible and qualifying close/accept button/link on the page and try to click it.
     * @returns {boolean} Returns true if a click was successfully triggered, otherwise returns false.
     */
    function autoCloseAnnoyances() {
        // console.log("Executing single check..."); // Reduce log volume

        try {
            // First try to apply custom rules
            if (applyCustomRules()) {
                return true; // If custom rule successfully applied, return success directly
            }

            // 1. Select candidate elements - more precise selectors to avoid selecting login buttons etc.
            const candidateSelector = 'button, [role="button"], a[class*="close"], a[class*="dismiss"], a[class*="cookie"], a[class*="popup-close"], a[class*="popup__close"], span[class*="close"], span[title*="close"], span[aria-label*="close"], div[class*="close"], div[title*="close"], [class*="close-icon"], [class*="closeButton"]';
            const candidateElements = document.querySelectorAll(candidateSelector);

            // console.log(`Found ${candidateElements.length} candidate elements.`); // Reduce log volume

            // 2. Iterate and find the first visible and qualifying element
            for (const element of candidateElements) {
                // Check if element is valid, visible and meets criteria
                if (element && typeof element.click === 'function' && isElementVisible(element) && elementMatchesCriteria(element)) {
                    // Directly print the found DOM node
                    console.log("Found first visible and qualifying element:", element);
                    // 3. Simulate mouse event click on the first found element
                    try {
                        console.log(`Simulating click on this element:`, element);
                        // Create and dispatch mousedown event
                        const mouseDownEvent = new MouseEvent('mousedown', {
                            bubbles: true,
                            cancelable: true,
                            view: window
                        });
                        element.dispatchEvent(mouseDownEvent);

                        // Create and dispatch mouseup event
                        const mouseUpEvent = new MouseEvent('mouseup', {
                            bubbles: true,
                            cancelable: true,
                            view: window
                        });
                        element.dispatchEvent(mouseUpEvent);

                        // (Optional) Dispatch a click event to ensure compatibility
                        const clickEvent = new MouseEvent('click', {
                            bubbles: true,
                            cancelable: true,
                            view: window
                        });
                        element.dispatchEvent(clickEvent);

                        console.log(`Element simulated click successful.`);
                    } catch (clickError) {
                        console.error(`Error simulating click on element:`, element, clickError);
                    }
                    // Return true immediately after successful click
                    // console.log("Successfully clicked an element in this round of checking."); // Log already handled at upper level
                    return true; // Indicates this check executed a click
                }
            }

            // If the loop ends without finding any qualifying visible element
            // console.log("Did not find any qualifying and visible clickable element in this round of checking.");
            return false; // Indicates this check did not execute a click

        } catch (error) {
            console.error("Error executing query or processing elements:", error);
            return false; // Error is also considered as not clicked
        }
    }

    // === Execution ===
    /**
     * Start periodic checking process
     */
    function startPeriodicChecks() {
        console.log(`DOM loaded, starting periodic check for annoying elements... (checking every ${config.checkIntervalMs}ms, lasting up to ${totalDurationMs / 1000}s, clicking up to ${maxClicks} times)`);

        let clickCounter = 0;
        const startTime = Date.now();
        let intervalId = null; // Used to store the ID returned by setInterval

        /**
         * Execute a single check, and update state or stop checking based on the result
         */
        function performCheck() {
            const elapsedTime = Date.now() - startTime;

            // console.log(`Executing check #${Math.floor(elapsedTime / config.checkIntervalMs) + 1} (elapsed time ${elapsedTime}ms, clicked ${clickCounter} times)`);

            // Try to close annoying elements
            const clickedThisTime = autoCloseAnnoyances();

            if (clickedThisTime) {
                clickCounter++;
                console.log(`Click count increased: ${clickCounter}/${maxClicks}`);
            }

            // Check stop conditions
            const timeLimitReached = elapsedTime >= totalDurationMs;
            const clickLimitReached = clickCounter >= maxClicks;

            if (timeLimitReached || clickLimitReached) {
                clearInterval(intervalId); // Use intervalId to stop the timer
                if (clickLimitReached) {
                    console.log(`Reached maximum click count (${maxClicks}), stopping checks.`);
                }
                if (timeLimitReached) {
                    console.log(`Reached maximum check duration (${totalDurationMs / 1000}s), stopping checks.`);
                }
                console.log(`Total time: ${Date.now() - startTime}ms, total clicks: ${clickCounter}`);
            }
        }

        // Execute first check immediately to avoid initial delay
        // And only set the timer if the first check does not meet stop conditions
        performCheck();
        if (clickCounter < maxClicks && (Date.now() - startTime) < totalDurationMs) {
            intervalId = setInterval(performCheck, config.checkIntervalMs);
        } else {
             // If the first check meets stop conditions, also print final state
             console.log(`Stopped after first check. Total time: ${Date.now() - startTime}ms, total clicks: ${clickCounter}`);
        }
    }

    // Check DOM loading state and schedule execution
    if (document.readyState === 'loading') {
        // If DOM is still loading, wait for DOMContentLoaded
        document.addEventListener('DOMContentLoaded', startPeriodicChecks);
    } else {
        // If DOM has already loaded or in interactive state, schedule execution directly
        startPeriodicChecks();
    }
})();
