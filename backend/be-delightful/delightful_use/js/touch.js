/**
 * DelightfulTouch - Advanced web tactile sensing for AI
 * Get all interactive elements from the page and categorize them
 */
(function() {
  'use strict';

  /**
   * Configuration options
   * excludeClassPrefixes: Exclude elements containing specified CSS class name prefixes
   * filterTinyElements: Control threshold for filtering tiny elements
   * maxTextLength: Maximum length of text field for interactive elements
   */
  const config = {
    excludeClassPrefixes: ['delightful-marker-'], // For example, exclude all elements with class starting with delightful-marker-
    filterTinyElements: {
      // Absolute minimum area (pixels squared), elements below this value will be filtered。
      absoluteMinArea: 16,
      // Minimum interactive size (pixels), elements with width or height below this value will be filtered (except for specific element types)。
      minInteractableDimension: 5,
      // Minimum side size for elongated elements (pixels), elements with abnormal aspect ratio but short side less than this value will be filtered。
      minDimensionForLongElements: 3
    },
    maxTextLength: 256 // Maximum length of text field for interactive elements
  };

  /**
   * Get interactive elements from the page
all - get all elements
   * @param {string} type - Specify the main category of elements to retrieve, such as'button'、'link'、'input'、'select'、'other'，'all'indicates to get all types
   * @returns {Object} - Interactive elements object classified by fixed main category
   * @example
   * // Return value example:
   * {
   *   "button": [
   *     {
   *       "name": "Submit",
   *       "name_en": "submit-btn",
   *       "type": "button",
   *       "selector": "#a1b2c3",
   *       "text": "Submit form"
   *     },
   *     // ... Other button elements
   *   ],
   *   "link": [
   *     {
   *       "name": "About Us",
   *       "name_en": "about-link",
   *       "type": "a",
   *       "selector": "#g7h8i9",
   *       "text": "About Us",
   *       "href": "https://example.com/about"
   *     },
   *     // ... Other link elements
   *   ],
   *   "input_and_select": [ // Note: input and select have been merged into input_and_select category
   *     {
   *       "name": "Username",
   *       "name_en": "username",
   *       "type": "text",
   *       "selector": "#j0k1l2",
   *       "value": ""
   *     },
   *     {
   *       "name": "Select city",
   *       "name_en": "city",
   *       "type": "select",
   *       "selector": "#m3n4o5",
   *       "value": "beijing"
   *     },
   *     // ... Other input and select elements
   *   ],
   *   "other": [
   *     {
   *       "name": "Video player",
   *       "name_en": "intro-video",
   *       "type": "video",
   *       "selector": "#p6q7r8"
   *     },
   *     // ... Other interactive elements
   *   ]
   * }
   *
   * Supported element types include：
   * - Common HTML interactive elements: 'a', 'button', 'select', 'textarea', 'summary', 'details', 'video', 'audio'
   * - Input field types: 'text', 'password', 'checkbox', 'radio', 'file', 'submit', 'reset', 'button',
   *   'color', 'date', 'datetime-local', 'email', 'month', 'number', 'range', 'search', 'tel',
   *   'time', 'url', 'week'
   * - Elements with interactive role attributes: 'button', 'link', 'checkbox', 'menuitem', 'menuitemcheckbox', 'menuitemradio',
   *   'option', 'radio', 'searchbox', 'slider', 'spinbutton', 'switch', 'tab', 'textbox'
   * - Other elements with click events or cursor:pointer style
   */

  // Get viewport dimensions
  const viewportWidth = window.innerWidth || document.documentElement.clientWidth;
  const viewportHeight = window.innerHeight || document.documentElement.clientHeight;

  /**
   * Calculate hash value of string
   * @param {string} str - Input string
   * @returns {string} - 8-digit hexadecimal hash value
   */
  function hashString(str) {
    let hash = 0;
    if (str.length === 0) return hash.toString(16).padStart(8, '0');

    for (let i = 0; i < str.length; i++) {
      const char = str.charCodeAt(i);
      hash = ((hash << 5) - hash) + char;
      hash = hash & hash; // Convert to 32-bit integer
    }

    // Convert to 8-digit hexadecimal string
    const hashHex = (hash >>> 0).toString(16).padStart(8, '0');
    return hashHex.slice(-8); // Ensure only 8 digits
  }

  /**
   * Get element XPath
   * @param {Element} element - DOM element
   * @returns {string} - Element XPath
   */
  function getElementXPath(element) {
    if (!element) return '';
    if (element.id) {
      return `//*[@id="${element.id}"]`;
    }

    if (element === document.body) {
      return '/html/body';
    }

    if (!element.parentNode) {
      return '';
    }

    let siblings = Array.from(element.parentNode.children).filter(
      child => child.tagName === element.tagName
    );

    let index = siblings.indexOf(element) + 1;

    // Build ordered identifier combination
    let tag = element.tagName.toLowerCase();
    // Explicitly convert to string, compatible with non-string types like SVGAnimatedString
    let attributes = '';

    // Add common attributes as identifiers
    const importantAttrs = ['type', 'name', 'role', 'data-testid'];
    for (const attr of importantAttrs) {
      if (element.hasAttribute(attr)) {
        attributes += `[@${attr}="${element.getAttribute(attr)}"]`;
      }
    }

    // Return path with tag, index and attributes
    return `${getElementXPath(element.parentNode)}/${tag}${attributes}[${index}]`;
  }

  /**
   * Generate stable ID based on element characteristics
   * Combine XPath path and element characteristics to generate a relatively stable identifier
   * @param {Element} element - DOM element
   * @returns {string} - Generated delightful ID
   */
  function generateDelightfulId(element) {
    if (!element) {
      return 'unknown';
    }

    // Get element XPath
    const xpath = getElementXPath(element);

    // Calculate XPath hash value
    return hashString(xpath);
  }

  // Check if element is in viewport and visible
  function isNodeVisible(node) {
    // Only handle visibility of element nodes
    if (node.nodeType === Node.ELEMENT_NODE) {
      // Get element position information
      const rect = node.getBoundingClientRect();

      // Check if element is in viewport (at least partially visible)
      const isInViewport = !(rect.right < 0 ||
                           rect.bottom < 0 ||
                           rect.left > viewportWidth ||
                           rect.top > viewportHeight);

      // Check if CSS style makes element visible
      const computedStyle = window.getComputedStyle(node);
      const hasVisibleStyle = computedStyle.display !== 'none' &&
                             computedStyle.visibility !== 'hidden' &&
                             parseFloat(computedStyle.opacity) > 0;

      return isInViewport && hasVisibleStyle;
    }

    // Non-element nodes default to false
    return false;
  }

  /**
   * Check overlap between two elements
   *
   * Overlap rules：
   * 1. Calculate the true visible overlap area of the two elements
   * 2. If the overlap area is greater than 80% of the smaller element area, it is considered overlapping
   * 3. For overlapping elements：
   *    - If there is a parent-child relationship, keep the parent element and remove the child element
   *    - If there is no parent-child relationship, keep the larger element and remove the smaller element
   *
   * @param {Element} element1 - First element
   * @param {Element} element2 - Second element
   * @returns {Object} Contains decision and reason whether element should be removed
   */
  function checkElementsOverlap(element1, element2) {
    // Get visible boundaries of element
    const rect1 = element1.getBoundingClientRect();
    const rect2 = element2.getBoundingClientRect();

    // Calculate element area
    const area1 = rect1.width * rect1.height;
    const area2 = rect2.width * rect2.height;

    // Check if there is parent-child relationship
    const isParentChild = element1.contains(element2) || element2.contains(element1);

    // If two elements do not overlap, directly return without removal
    if (rect1.right <= rect2.left || rect1.left >= rect2.right ||
        rect1.bottom <= rect2.top || rect1.top >= rect2.bottom) {
      return { shouldRemove: false, reason: "no-overlap" };
    }

    // Calculate real visible overlap area
    const overlapLeft = Math.max(rect1.left, rect2.left);
    const overlapRight = Math.min(rect1.right, rect2.right);
    const overlapTop = Math.max(rect1.top, rect2.top);
    const overlapBottom = Math.min(rect1.bottom, rect2.bottom);

    const overlapWidth = overlapRight - overlapLeft;
    const overlapHeight = overlapBottom - overlapTop;
    const overlapArea = overlapWidth * overlapHeight;

    // Calculate overlap ratio (percentage of smaller element)
    const smallerArea = Math.min(area1, area2);
    // Avoid division by zero
    if (smallerArea === 0) {
      return { shouldRemove: false, reason: "zero-area-element" };
    }
    const overlapRatio = overlapArea / smallerArea;

    // If the overlap ratio is greater than 80%, determine which element should be removed
    if (overlapRatio > 0.8) {
      // If there is a parent-child relationship, remove the child element
      if (isParentChild) {
        if (element1.contains(element2)) {
          return { shouldRemove: true, element: element2, reason: "child-element" };
        } else {
          return { shouldRemove: true, element: element1, reason: "child-element" };
        }
      }

      // Otherwise remove the smaller element
      if (area1 > area2) {
        return { shouldRemove: true, element: element2, reason: "smaller-element" };
      } else {
        // If the area is equal or area2 is larger, remove element1
        return { shouldRemove: true, element: element1, reason: "smaller-element" };
      }
    }

    return { shouldRemove: false, reason: "overlap-ratio-too-small" };
  }

  /**
   * Filter out elements covered by other elements
   *
   * Filtering rules：
   * 1. Apply overlap detection rules to each pair of elements
   * 2. Remove all elements determined to be removed (smaller or child elements)
   * 3. Optimization: avoid duplicate comparisons and checking elements already marked for removal
   *
   * @param {Array<Element>} nodes - Array of elements to be filtered
   * @returns {Array<Element>} Filtered array of elements
   */
  function filterOverlappingElements(nodes) {
    if (nodes.length < 2) {
      return nodes;
    }

    const nodesToRemove = new Set();

    for (let i = 0; i < nodes.length; i++) {
      if (nodesToRemove.has(nodes[i])) continue;

      for (let j = i + 1; j < nodes.length; j++) {
        if (nodesToRemove.has(nodes[j])) continue;

        // Check overlap
        const result = checkElementsOverlap(nodes[i], nodes[j]);

        if (result.shouldRemove) {
          nodesToRemove.add(result.element);
        }
      }
    }

    return nodes.filter(node => !nodesToRemove.has(node));
  }

  /**
   * Filter out body element and large area elements
   *
   * Filtering rules：
   * 1. Exclude page body element (usually contains entire page content)
   * 2. Exclude elements with area greater than 20% of viewport (large container and background elements)
   *
   * Purpose: Remove large container elements that are unlikely to be specific interactive elements
   *
   * @param {Array<Element>} nodes - Array of elements to be filtered
   * @returns {Array<Element>} Filtered array of elements
   */
  function filterLargeElements(nodes) {
    // Calculate viewport area
    const viewportArea = viewportWidth * viewportHeight;
    // Avoid division by zero
    if (viewportArea === 0) return nodes;

    // Exclude body element and elements with area greater than 20% of viewport
    return nodes.filter(node => {
      // Exclude body element
      if (node.tagName.toLowerCase() === 'body') {
        return false;
      }

      // Calculate element area
      const rect = node.getBoundingClientRect();
      const nodeArea = rect.width * rect.height;

      // If element area is greater than 20% of viewport area, exclude
      return nodeArea <= (viewportArea * 0.2);
    });
  }

  /**
   * Filter out elements with extremely small area or abnormal dimensions
   *
   * Filtering rules：
   * 1. Exclude elements with area less than 16 square pixels (e.g., 4x4 pixels)
   * 2. Exclude elements with width or height less than minimum human-interactive size (e.g., less than 5 pixels)
   * 3. Exclude elements with width or height outside reasonable range but another dimension extremely small (e.g., 1x500 pixel lines)
   *
   * Purpose: Remove elements that are too small to interact with effectively or may be decorative
   *
   * @param {Array<Element>} nodes - Array of elements to be filtered
   * @returns {Array<Element>} Filtered array of elements
   */
  function filterTinyElements(nodes) {
    // Calculate viewport area
    const viewportArea = viewportWidth * viewportHeight;
    // Avoid division by zero
    if (viewportArea === 0) return nodes;

    // Set absolute minimum area (16 square pixels, equivalent to 4x4)
    const absoluteMinArea = config.filterTinyElements.absoluteMinArea;

    // Set minimum human-interactive size (pixels)
    const minInteractableDimension = config.filterTinyElements.minInteractableDimension;

    // For special cases, a threshold where one dimension can be larger but another cannot be too small
    const minDimensionForLongElements = config.filterTinyElements.minDimensionForLongElements;

    // Set area threshold
    const minAreaThreshold = absoluteMinArea;

    return nodes.filter(node => {
      // Get element dimension information
      const rect = node.getBoundingClientRect();
      const nodeWidth = rect.width;
      const nodeHeight = rect.height;
      const nodeArea = nodeWidth * nodeHeight;

      // Check tags and roles, some elements can be exceptions
      const tagName = node.tagName.toLowerCase();
      const role = node.getAttribute('role');

      // For specific types of elements, exceptions are allowed (such as horizontal separators, progress bars, etc.)
      if ((tagName === 'hr' && nodeHeight < minInteractableDimension) ||
          (role === 'separator' && nodeHeight < minInteractableDimension) ||
          (tagName === 'progress' || role === 'progressbar') ||
          (tagName === 'input' && node.type === 'range') ||
          (role === 'slider' || role === 'scrollbar')) {
        return true;
      }

      // If it is an inputable element, it usually needs to be kept
      if (isInputable(node)) {
        return true;
      }

      // Regular checks：
      // 1. Area check
      const areaCheck = nodeArea >= minAreaThreshold;

      // 2. Minimum dimension check - both dimensions cannot be too small
      const minDimensionCheck = nodeWidth >= minInteractableDimension && nodeHeight >= minInteractableDimension;

      // 3. Special check - the smaller side of elongated elements cannot be too small
      const specialShapeCheck = !(
        (nodeWidth > nodeHeight * 5 && nodeHeight < minDimensionForLongElements) ||
        (nodeHeight > nodeWidth * 5 && nodeWidth < minDimensionForLongElements)
      );

      // Only elements that pass all checks will be kept
      return areaCheck && minDimensionCheck && specialShapeCheck;
    });
  }

  /**
   * Filter out elements with abnormal aspect ratio
   *
   * Filtering rules：
   * 1. Exclude extremely elongated elements with aspect ratio exceeding threshold (such as separators, borders)
   * 2. Default threshold is 8:1, i.e., elements with height more than 8 times width
   * 3. Special case: For known deliberately designed elongated interactive elements, they will be retained through special detection
   *
   * Purpose: Remove elements with abnormal shapes that may be decorative or non-primary interaction purposes
   *
   * @param {Array<Element>} nodes - Array of elements to be filtered
   * @param {number} [aspectRatioThreshold=8] - Aspect ratio threshold, elements exceeding this threshold will be filtered
   * @returns {Array<Element>} Filtered array of elements
   */
  function filterAbnormalAspectRatioElements(nodes, aspectRatioThreshold = 8) {
    return nodes.filter(node => {
      // Get element dimensions
      const rect = node.getBoundingClientRect();
      // Avoid division by zero error
      if (rect.width === 0 || rect.height === 0) {
        return false;
      }
      // Calculate height-to-width ratio
      const heightToWidthRatio = rect.height / rect.width;
      // Only filter elements with height too high relative to width, do not filter width too wide relative to height
      return heightToWidthRatio <= aspectRatioThreshold;
    });
  }

  /**
   * Get readable name of element, priority order：
   * aria-label > title > alt > name > id > placeholder > button/link text > label text > tag name
   * @param {Element} element - DOM element
   * @returns {string} - Readable name
   */
  function getReadableName(element) {
    // Try to get various attributes that could be used as names
    const nameAttributes = ['aria-label', 'title', 'alt', 'name', 'id', 'placeholder'];
    for (const attr of nameAttributes) {
      if (element.hasAttribute(attr)) {
        const value = element.getAttribute(attr)?.trim();
        if (value) return value;
      }
    }

    // Get text on button
    if (element.tagName.toLowerCase() === 'button' || element.getAttribute('role') === 'button') {
      const text = element.innerText?.trim();
      if (text) return text;
    }

    // Get link text
    if (element.tagName.toLowerCase() === 'a') {
      const text = element.innerText?.trim();
      if (text) return text;
    }

    // Get label for form element
    if (['input', 'select', 'textarea'].includes(element.tagName.toLowerCase())) {
      // Find associated label through for attribute
      const id = element.id;
      if (id) {
        const label = document.querySelector(`label[for="${id}"]`);
        const text = label?.innerText?.trim();
        if (text) return text;
      }

      // Find label in parent element
      let parent = element.parentElement;
      while (parent) {
        if (parent.tagName.toLowerCase() === 'label') {
           const text = parent.innerText?.trim();
           if (text) return text;
        }
        // Avoid infinite loop, search up at most 5 levels
        let depth = 0;
        if (parent.parentElement && depth < 5) {
            parent = parent.parentElement;
            depth++;
        } else {
            break;
        }
      }
    }

    // If no name found, return element type
    return element.tagName.toLowerCase();
  }

  // Check if element is interactive
  function isInteractive(element) {
    if (element.nodeType !== Node.ELEMENT_NODE) {
      return false;
    }

    const tagName = element.tagName.toLowerCase();
    const computedStyle = window.getComputedStyle(element);

    // Common interactive element tags
    const interactiveTags = ['a', 'button', 'input', 'select', 'textarea', 'summary', 'details', 'video', 'audio'];

    // Check if element is a common interactive tag
    if (interactiveTags.includes(tagName)) {
      return true;
    }

    // Check if element has tabindex attribute (not -1)
    if (element.hasAttribute('tabindex') && element.getAttribute('tabindex') !== '-1') {
      return true;
    }

    // Check if element has common interactive role attributes
    const interactiveRoles = [
      'button', 'link', 'checkbox', 'menuitem', 'menuitemcheckbox', 'menuitemradio',
      'option', 'radio', 'searchbox', 'slider', 'spinbutton', 'switch', 'tab', 'textbox'
    ];
    if (element.hasAttribute('role') && interactiveRoles.includes(element.getAttribute('role'))) {
      return true;
    }

    // Check if cursor style is clickable (pointer)
    if (computedStyle.cursor === 'pointer') {
      return true;
    }

    // Check if element has click event listeners (note: this check is not entirely reliable, can only detect inline and attribute form listeners)
    // Check for inline onclick
    if (element.hasAttribute('onclick')) {
        return true;
    }
    // Try to check events added through addEventListener (limited, may not detect all cases)
    // In real browsers, there is no standard method to directly check all listeners added through addEventListener。
    // This check is heuristic and may not be comprehensive。
    // If relying on dynamically added events, this check may not be sufficient。
    const events = window.getEventListeners?.(element); // Non-standard API, may not exist
    if (events && events.click && events.click.length > 0) {
        return true;
    }

    return false;
  }

  /**
   * Determine if element can accept input content
   *
   * Determination basis：
   * 1. textarea element (not disabled and read-only state)
   * 2. Inputable type input elements (text, number, date, etc., not disabled and read-only state)
   * 3. Elements with contenteditable="true" attribute
   * 4. Elements with editing-related ARIA roles
   * 5. iframe with designMode set to "on"
   *
   * @param {HTMLElement} element - DOM element to check
   * @returns {boolean} - Returns true if element can accept input, otherwise false
   */
  function isInputable(element) {
    // Check if element is null or undefined
    if (!element) {
      return false;
    }

    const tagName = element.tagName.toLowerCase();

    // Check if element is disabled or read-only
    if (element.disabled || element.readOnly) {
        return false;
    }

    // 1. Check if it is textarea
    if (tagName === 'textarea') {
      return true;
    }

    // 2. Check if it is inputable type input
    if (tagName === 'input') {
      const inputableTypes = [
        'text', 'password', 'email', 'number', 'search',
        'tel', 'url', 'date', 'datetime-local', 'time',
        'week', 'month', 'color'
      ];
      return inputableTypes.includes(element.type);
    }

    // 3. Check if element has contenteditable attribute set to true
    if (element.isContentEditable) {
      return true;
    }

    // 4. Check if element has editing-related ARIA role and is not read-only
    const editableRoles = ['textbox', 'searchbox', 'spinbutton'];
    const role = element.getAttribute('role');
    if (role && editableRoles.includes(role)) {
      const ariaReadOnly = element.getAttribute('aria-readonly');
      return ariaReadOnly !== 'true';
    }

    // 5. Check if it is designMode in iframe
    if (tagName === 'iframe' && element.contentDocument) {
      try {
        // Must be in try-catch to prevent cross-domain errors
        return element.contentDocument.designMode === 'on';
      } catch (e) {
        // Ignore cross-domain errors
        return false;
      }
    }

    return false;
  }

  /**
   * Determine element category and specific type
   * Category: button, link, input_and_select, other
   * Type: based on tagName, role, input type, etc.
   *
   * @param {Element} element - DOM element
   * @returns {{category: string, type: string}} - Element category and type
   */
  function getCategoryAndType(element) {
    const tagName = element.tagName.toLowerCase();
    const role = element.getAttribute('role');
    let category = 'other'; // Default category
    let type = role || tagName; // Prioritize role as type, otherwise use tag name

    // ---- Core categorization logic ----

    // 1. Determine category based on Role
    if (role) {
      if (['button', 'menuitem', 'menuitemcheckbox', 'menuitemradio', 'tab', 'switch'].includes(role)) {
        category = 'button';
      } else if (['link'].includes(role)) {
        category = 'link';
      } else if ([
        'checkbox', 'combobox', 'listbox', 'menu', 'menubar', 'navigation',
        'option', 'progressbar', 'radio', 'radiogroup', 'scrollbar',
        'searchbox', 'separator', 'slider', 'spinbutton', 'tablist',
        'textbox', 'timer', 'toolbar', 'tree', 'treegrid', 'treeitem'
      ].includes(role)) {
        // These are usually input, selection or compound components
        category = 'input_and_select';
      }
      // Other roles default to other
    } else {
      // 2. Determine category based on TagName (only when no role)
      if (['button', 'summary', 'details'].includes(tagName)) {
        category = 'button';
      } else if (['a'].includes(tagName)) {
        category = 'link';
      } else if ([
        'input', 'textarea', 'select', 'option', 'optgroup', 'datalist',
        'progress', 'meter', 'output', 'canvas', 'audio', 'video', // These are related to input, selection or display
        'form', 'fieldset', 'legend', 'label', // Form-related structure
        'table', 'th', 'tr', 'td', 'tbody', 'thead', 'tfoot', 'col', 'colgroup', // Table-related
        'ul', 'ol', 'li', 'dl', 'dt', 'dd', // List-related
        'nav', 'menuitem', 'menu' // Navigation and menu-related
      ].includes(tagName)) {
        category = 'input_and_select';
      }
      // Other tagName default categorized as 'other'
    }

    // ---- Type refinement and special handling ----

    // 3. Refine input type
    if (tagName === 'input') {
      const inputType = element.type?.toLowerCase() || 'text';
      type = inputType; // Use input type as element type

      // Special: Submit, reset, button type inputs should be categorized as button
      if (['submit', 'reset', 'button', 'image'].includes(inputType)) {
        category = 'button';
      }
    }

    // 4. Inputable elements forced to be categorized as input_and_select
    if (isInputable(element)) {
      category = 'input_and_select';
      // If type is still default tagName and not textarea or input, refine to textbox
      if (type === tagName && !['textarea', 'input'].includes(tagName)) {
        type = 'textbox';
      }
    }

    // 5. Special ARIA attribute handling (may override previous categorization)
    //    Only when element is not clearly identified as input/select type, categorize as button due to ARIA attributes
    const hasPopup = element.getAttribute('aria-haspopup') && element.getAttribute('aria-haspopup') !== 'false';
    const hasControls = element.hasAttribute('aria-controls');
    const hasExpanded = element.hasAttribute('aria-expanded');

    if ((hasPopup || hasControls || hasExpanded) && category !== 'input_and_select') {
      // Only when it is not input/select box (for example previously determined as other or link, etc.)，
      // then categorize as button because of these ARIA attributes
      category = 'button';
    }

    // 6. Media elements and controls forced categorization 'input_and_select'
    if (['audio', 'video'].includes(tagName) || element.classList.contains('media-control')) {
      category = 'input_and_select';
    }

    // 7. Form-associated elements (if not button previously, categorize as input_and_select)
    if (element.form && category !== 'button') {
       category = 'input_and_select';
    }

    return { category, type };
  }

  /**
   * Get interactive DOM nodes from page
   *
   * Filter process:
   * 1. Traverse DOM to find all basic interactive nodes (isInteractive)
   * 2. Exclude elements with CSS class name prefixes specified in config
   * 3. Whitelist mechanism: preserve important input boxes (isInputable and large size or special type)
   * 4. Apply filtering to non-whitelist elements:
   *    a. Filter out body and large area elements (filterLargeElements)
   *    b. Filter out tiny area elements (filterTinyElements)
   *    c. Filter out abnormal aspect ratio elements (filterAbnormalAspectRatioElements)
   *    d. Filter out obscured elements (filterOverlappingElements)
   * 5. Merge whitelist and filtered elements
   *
all - get all elements
   * @returns {Array<Element>} - Array of filtered interactive DOM nodes
   */
  function getInteractiveDomNodes(scope = 'viewport') {
    const initialNodes = [];

    // Depth-first traverse DOM tree
    function traverse(node) {
      // Check if it is element node
      if (node.nodeType !== Node.ELEMENT_NODE) {
        return;
      }

      // 1. Check if it is basic interactive node
      if (isInteractive(node)) {
        // 2. Check if need to exclude by class name prefix
        let excludedByClass = false;
        if (config.excludeClassPrefixes && config.excludeClassPrefixes.length > 0 && node.classList) {
          for (const className of node.classList) {
            for (const prefix of config.excludeClassPrefixes) {
              if (className.startsWith(prefix)) {
                excludedByClass = true;
                break; // Found matching prefix, no need to check other prefixes
              }
            }
            if (excludedByClass) break; // Found matching class name, no need to check other class names
          }
        }

        // Only elements not excluded by class name and meeting scope conditions are added to initial list
        if (!excludedByClass && (scope === 'all' || (scope === 'viewport' && isNodeVisible(node)))) {
          initialNodes.push(node);
        }
      }

      // Recursively traverse child nodes
      for (const child of node.children) {
        traverse(child);
      }
    }

    // Start traversal from document.body
    if (document.body) {
      traverse(document.body);
    }

    // 3. Whitelist mechanism
    /**
     * Check if element should be added to whitelist (skip most filtering)
     * Whitelist conditions:
     * - Inputable textarea
     * - Inputable contenteditable elements
     * - Inputable input with sufficient size (width > 50px and height > 30px)
     *
     * @param {HTMLElement} element - Element to check
     * @returns {boolean} - Whether should be added to whitelist
     */
    function shouldWhitelist(element) {
      if (isInputable(element)) {
        const tagName = element.tagName.toLowerCase();
        // Special handling for multiline text boxes and rich text editors
        if (tagName === 'textarea' || element.isContentEditable) {
          return true;
        }
        // Check input dimensions
        if (tagName === 'input') {
          const rect = element.getBoundingClientRect();
          // Input boxes with width > 50px and height > 30px are considered valuable
          if (rect.width > 50 && rect.height > 30) {
            return true;
          }
        }
      }
      return false;
    }

    // Divide elements into whitelist and elements to filter
    const whitelisted = initialNodes.filter(shouldWhitelist);
    const toFilter = initialNodes.filter(element => !shouldWhitelist(element));

    // 4. Apply complete filtering process to elements needing filtering
    const filteredBySize = filterLargeElements(toFilter);
    const filteredByMinSize = filterTinyElements(filteredBySize);
    const filteredByAspectRatio = filterAbnormalAspectRatioElements(filteredByMinSize);
    const filteredByOverlap = filterOverlappingElements(filteredByAspectRatio); // Handle overlapping last

    // 5. Final result is whitelist elements plus filtered elements
    const finalNodes = [...whitelisted, ...filteredByOverlap];

    // Add delightful-touch-id to all finally determined nodes before returning
    finalNodes.forEach(element => {
      const delightfulId = generateDelightfulId(element);
      element.setAttribute('delightful-touch-id', delightfulId);
    });

    return finalNodes;
  }

  /**
   * Get interactive elements from page and organize by category all - get all elements
   * @param {string} [categoryFilter='all'] - Specify main category of elements to retrieve, such as button, link, input_and_select, other, or all to get all
   * @returns {Object} - Interactive elements object classified by fixed categories (button, link, input_and_select, other)
   */
  function getInteractiveElements(scope = 'viewport', categoryFilter = 'all') {
    // Initialize result object containing all fixed categories
    const result = {
      button: [],
      link: [],
      input_and_select: [],
      other: []
    };

    // Get filtered interactive DOM nodes
    const nodes = getInteractiveDomNodes(scope);

    // Process each DOM node, convert to structured information and categorize
    for (const element of nodes) {
      // Determine element category and type
      const { category, type: elementType } = getCategoryAndType(element);

      // Get set delightful-touch-id
      const delightfulId = element.getAttribute('delightful-touch-id');
      // If no delightfulId, skip this element or record error
      if (!delightfulId) {
        console.warn("DelightfulTouch: Element missing delightful-touch-id.", element);
        continue; // Skip this element without ID
      }

      // Build element information object
      const elementInfo = {
        name: getReadableName(element), // Get readable name
        name_en: element.id || null,     // Use element ID as English name, null if none
        type: elementType,              // Element specific type
        selector: `[delightful-touch-id="${delightfulId}"]` // Use attribute selector directly
      };

      // Add text content (truncated, applicable to buttons, links, etc)
      const innerText = element.innerText?.trim();
      if (innerText) {
        elementInfo.text = innerText.substring(0, config.maxTextLength); // Use config value to truncate long text
      }

      // Add value (applicable to input boxes, select boxes, etc)
      // Check if value attribute exists and is not null/undefined
      if (element.value !== undefined && element.value !== null) {
        // For password boxes, do not record specific value
        if (element.type?.toLowerCase() === 'password') {
            elementInfo.value = '********';
        } else {
            elementInfo.value = String(element.value).substring(0, 200); // Convert to string and truncate
        }
      }

      // For link elements, add href (filter out data: and javascript: protocols)
      if (category === 'link' && element.hasAttribute('href')) {
        const href = element.getAttribute('href');
        if (href && !href.startsWith('data:') && !href.startsWith('javascript:')) {
          elementInfo.href = href;
        }
      }

      // Add to corresponding category array (ensure category exists)
      if (result[category]) {
        result[category].push(elementInfo);
      } else {
        // If unexpected category appears (theoretically should not happen), put in 'other'
        result.other.push(elementInfo);
        console.warn(`DelightfulTouch: Element with unexpected category '${category}' found. Added to 'other'.`, element);
      }
    }

    // If categoryFilter specified and not all, return only that category elements
    const validCategories = ['button', 'link', 'input_and_select', 'other'];
    if (categoryFilter !== 'all' && validCategories.includes(categoryFilter)) {
      // Return object containing only specified category key-value pairs
      return { [categoryFilter]: result[categoryFilter] };
    }

    // Otherwise return complete result object containing all categories
    return result;
  }

  // Expose core functions to window object
  window.DelightfulTouch = {
    getInteractiveDomNodes: getInteractiveDomNodes,     // Get raw DOM nodes (mainly for internal or debug use)
    getInteractiveElements: getInteractiveElements, // Get structured element information (main API)
  };

  // Can call DelightfulTouch.getInteractiveElements() or DelightfulTouch.getInteractiveElements('all') in console to view results
  // console.log("DelightfulTouch initialized. Call DelightfulTouch.getInteractiveElements() to get elements.");

})();
