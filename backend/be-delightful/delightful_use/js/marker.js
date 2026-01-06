/**
 * DelightfulMaker - Advanced interactive element marking tool
 * Draw colored borders on interactive elements on web pages and add letter+number combination tags in the top-right corner
 *
 * Provides two core methods:
 * - markElements: Mark all interactive elements on the page
 * - unmarkElements: Remove all marks
 *
 * Features:
 * 1. Both methods support repeated calls with no side effects (multiple calls are equivalent to calling once)
 * 2. Marks automatically update position with page scrolling and window resize
 * 3. Dynamically handle DOM changes to ensure newly added interactive elements are correctly marked
 * 4. Each marked element displays a tag with letter+number combination in the top-right corner
 * 5. Intelligent tag position judgment, small elements show tags outside to avoid blocking
 * 6. Use Shadow DOM to avoid style pollution
 *
 * Z-index configuration system:
 * - Supports two strategy modes: fixed (fixed values) and relative (relative to element)
 * - Current mode: relative (z-index relative to marked element)
 * - fixed mode: Mark elements use preset fixed z-index values
 * - relative mode: Mark elements use z-index values relative to the marked element
 * - To modify, adjust the Z_INDEX_CONFIG object
 *
 * Technical solution notes:
 * - Use Shadow DOM to isolate mark element styles and avoid interference with page styles
 * - Use MutationObserver to listen for DOM structure changes, replacing timers for efficient element detection
 * - Use ResizeObserver to listen for window size changes for precise position updates
 * - Implement debouncing and batch processing for performance optimization, avoiding frequent updates
 * - Handle interactive elements throughout the body, marking all visible interactive elements in the current viewport
 * - Achieve efficient visual updates through requestAnimationFrame
 */
// @depends: touch

(function() {
  'use strict';

  // Global constant definitions
  const Z_INDEX = 9999; // Mark hierarchy level
  const BORDER_WIDTH = 1; // Border width (1px)
  const BORDER_OPACITY = 0.5; // Border opacity (0 to 1)
  // Use six high-contrast colors uniformly
  const COLORS = ['#FF0000', '#0000FF', '#FFFF00', '#00FF00', '#FF00FF', '#800080']; // Red, Blue, Yellow, Green, Pink, Purple
  const DEBOUNCE_DELAY = 100; // Debounce delay (milliseconds)

  // Z-index configuration system - Flexible configuration for mark element hierarchy
  /**
   * Z_INDEX_CONFIG - Z-index configuration object for mark elements
   *
   * Supports two strategies:
   * 1. fixed: Use fixed z-index values (global unified values)
   *    - border: Border z-index
   *    - mask: Mask z-index (1 less than border)
   *    - label: Label z-index (1 more than border)
   *
   * 2. relative: Use z-index values relative to the element (currently using this mode)
   *    - border: Same as element z-index (+0)
   *    - mask: 1 less than element z-index (-1)
   *    - label: 1 more than element z-index (+1)
   *
   * To make all mark elements display at the top, change strategy to 'fixed'
   * To adjust relative offset values, modify the values in the relative object
   */
  const Z_INDEX_CONFIG = {
    strategy: 'relative', // Current strategy: 'fixed' (fixed values) or 'relative' (relative to element z-index)
    fixed: {
      border: Z_INDEX,
      mask: Z_INDEX - 1,
      label: Z_INDEX + 1
    },
    relative: {
      border: 0,     // Same as element z-index
      mask: 0,       // Same as element z-index
      label: 0       // Same as element z-index
    }
  };

  // Mark label related constants
  const LABEL_PADDING = 2; // Label padding (reduced)
  const LABEL_FONT_SIZE = 10; // Label font size (reduced)
  const VALID_LETTERS = 'ABCDEFHJKLMNPRSTUVWXYZ'; // Excluded easily confused letters G, I, O, Q
  const VALID_NUMBERS = '23456789'; // Excluded easily confused numbers 0, 1
  const LABEL_AREA_THRESHOLD = 0.3; // Label area to element area ratio threshold, display outside if exceeded
  const LABEL_OUTSIDE_HEIGHT_THRESHOLD = 30; // Elements smaller than this height also show label outside

  // Mark state
  let isMarking = false;
  // Store all created marks
  let markers = [];
  // Debounce processing state
  let updateScheduled = false;
  // Observer instances
  let mutationObserver = null;
  let resizeObserver = null;
  // Shadow DOM related
  let shadowHost = null;
  let shadowRoot = null;

  /**
   * Utility function collection
   * Contains various auxiliary functions such as element position calculation, color generation, visibility detection, etc.
   */
  const utils = {
    /**
     * Create or get Shadow DOM
     * Ensure Shadow DOM container is created only once
     * @return {ShadowRoot} Shadow DOM root node
     */
    getShadowRoot: () => {
      if (!shadowHost) {
        // Create host element
        shadowHost = document.createElement('div');
        shadowHost.id = 'delightful-marker-host';

        // Set basic styles to ensure no impact on page layout
        Object.assign(shadowHost.style, {
          position: 'absolute',
          top: '0',
          left: '0',
          width: '0',
          height: '0',
          border: 'none',
          padding: '0',
          margin: '0',
          pointerEvents: 'none',
          zIndex: 'auto'
        });

        // Add to body
        document.body.appendChild(shadowHost);

        // Create shadow root
        shadowRoot = shadowHost.attachShadow({ mode: 'open' });
      }

      return shadowRoot;
    },

    /**
     * Clean up Shadow DOM
     * Remove all mark elements
     */
    clearShadowRoot: () => {
      if (shadowRoot) {
        // Clear all content in shadow root
        while (shadowRoot.firstChild) {
          shadowRoot.removeChild(shadowRoot.firstChild);
        }
      }
    },

    /**
     * Get element position and size
     * @param {HTMLElement} element - DOM element
     * @return {Object} Object containing element position and size
     */
    getElementRect: (element) => {
      const rect = element.getBoundingClientRect();
      return {
        x: rect.left + window.scrollX,
        y: rect.top + window.scrollY,
        width: rect.width,
        height: rect.height
      };
    },

    /**
     * Get the highest effective z-index value of an element, considering the stacking context of all ancestor elements
     * @param {HTMLElement} element - DOM element
     * @return {number} Highest z-index value at the element's stacking level
     */
    getElementZIndex: (element) => {
      let maxZIndex = 0;
      let currentElement = element;

      while (currentElement && currentElement !== document.body) {
        const style = window.getComputedStyle(currentElement);
        const position = style.position;
        const zIndex = style.zIndex;
        let currentZIndex = 0; // Current node zIndex value, default 0

        // Check if a new stacking context was created
        const createsStackingContext =
          ((position !== 'static' && zIndex !== 'auto') || position === 'fixed' || position === 'sticky');

        if (createsStackingContext && zIndex !== 'auto') {
            currentZIndex = parseInt(zIndex, 10) || 0;
        }

        // Only compare and update maxZIndex on elements that create a stacking context
        // This is because an element's final level is determined by the nearest stacking context it belongs to
        // But we need to find the maximum value among *all* stacking contexts in this chain
        if (createsStackingContext) {
           maxZIndex = Math.max(maxZIndex, currentZIndex);
        }

        // Continue searching upward
        currentElement = currentElement.parentElement;
      }

      // Finally, check the body itself (though z-index is usually not set, just in case)
      if (document.body) {
          const bodyStyle = window.getComputedStyle(document.body);
          const bodyPosition = bodyStyle.position;
          const bodyZIndex = bodyStyle.zIndex;
          if ((bodyPosition !== 'static' && bodyZIndex !== 'auto') || bodyPosition === 'fixed' || bodyPosition === 'sticky') {
              const parsedBodyZIndex = bodyZIndex === 'auto' ? 0 : parseInt(bodyZIndex, 10) || 0;
              maxZIndex = Math.max(maxZIndex, parsedBodyZIndex);
          }
      }

      // Return the highest z-index value found during traversal
      return maxZIndex;
    },

    /**
     * Calculate the z-index of a mark element
     * @param {HTMLElement} element - DOM element to be marked
     * @param {string} type - Mark type ('border', 'mask', 'label')
     * @return {number} Calculated z-index value
     */
    calculateZIndex: (element, type) => {
      if (Z_INDEX_CONFIG.strategy === 'fixed') {
        return Z_INDEX_CONFIG.fixed[type];
      } else {
        const baseZIndex = utils.getElementZIndex(element);
        const offset = Z_INDEX_CONFIG.relative[type];
        return Math.max(0, baseZIndex + offset);
      }
    },

    /**
     * Create a unique ID
     * @return {string} Randomly generated unique identifier
     */
    generateUniqueId: () => {
      return 'marker-' + Math.random().toString(36).substring(2, 8);
    },

    /**
     * Add an X character to the page to change textContent
     * Used to trigger re-rendering in certain specific scenarios
     */
    insertXCharacter: () => {
      const xElement = document.createElement('span');
      xElement.textContent = 'X';
      xElement.style.opacity = '1';
      xElement.style.position = 'absolute';
      xElement.style.visibility = 'hidden';
      xElement.style.pointerEvents = 'none';
      xElement.id = 'delightful-x-marker-' + Date.now();

      // Add X character to Shadow DOM
      const shadow = utils.getShadowRoot();
      shadow.appendChild(xElement);

      // Delay removal to ensure triggering repaint
      setTimeout(() => {
        if (xElement.parentNode) {
          xElement.parentNode.removeChild(xElement);
        }
      }, 100);
    },

    /**
     * Get colors used in a loop
     * @param {number} index - Element index
     * @return {string} Color code
     */
    getColor: (index) => {
      return COLORS[index % COLORS.length];
    },

    /**
     * Generate letter+number combination markers, avoiding easily confused characters
     * @param {number} index - Element index
     * @return {string} Mark text with letter+number combination
     */
    generateLabelText: (index) => {
      const letterIndex = Math.floor(index / VALID_NUMBERS.length) % VALID_LETTERS.length;
      const numberIndex = index % VALID_NUMBERS.length;

      const letter = VALID_LETTERS.charAt(letterIndex);
      const number = VALID_NUMBERS.charAt(numberIndex);

      return letter + number;
    },

    /**
     * Estimate label size
     * @param {string} labelText - Label text
     * @return {Object} Object containing label width, height, and area
     */
    estimateLabelSize: (labelText) => {
      const width = (labelText.length * LABEL_FONT_SIZE) + (LABEL_PADDING * 2);
      const height = LABEL_FONT_SIZE + (LABEL_PADDING * 2);
      return {
        width: width,
        height: height,
        area: width * height
      };
    },

    /**
     * Determine whether the label should be placed outside the element
     * @param {Object} labelSize - Label size information
     * @param {Object} elementRect - Element size information
     * @return {boolean} true means should be placed outside, false means inside
     */
    shouldPlaceLabelOutside: (labelSize, elementRect) => {
      const elementArea = elementRect.width * elementRect.height;
      // New condition: if element height is below threshold, also place outside
      return labelSize.area > (elementArea * LABEL_AREA_THRESHOLD) || elementRect.height < LABEL_OUTSIDE_HEIGHT_THRESHOLD;
    },

    /**
     * Debounce function
     * @param {Function} func - Function to be debounced
     * @param {number} delay - Delay time (milliseconds)
     * @return {Function} Function after debounce processing
     */
    debounce: (func, delay) => {
      let timeoutId;
      return function(...args) {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => {
          func.apply(this, args);
        }, delay);
      };
    },
  };

  /**
   * Creator - Responsible for creating mark elements
   * Contains functions to create borders, masks and labels
   */
  const creator = {
    /**
     * Create border element
     * @param {HTMLElement} element - DOM element to be marked
     * @param {Object} rect - Element position and size
     * @param {string} color - Border color
     * @return {HTMLElement} Created border element
     */
    createBorder: (element, rect, color) => {
      const border = document.createElement('div');
      border.className = 'delightful-marker-border';

      // Calculate z-index
      const borderZIndex = utils.calculateZIndex(element, 'border');

      // Convert color to RGBA and set opacity
      let rgbaColor = color;
      if (color.startsWith('#')) {
        const r = parseInt(color.slice(1, 3), 16);
        const g = parseInt(color.slice(3, 5), 16);
        const b = parseInt(color.slice(5, 7), 16);
        rgbaColor = `rgba(${r}, ${g}, ${b}, ${BORDER_OPACITY})`; // Use configured opacity
      }

      Object.assign(border.style, {
        position: 'absolute',
        zIndex: borderZIndex.toString(),
        top: `${rect.y}px`,
        left: `${rect.x}px`,
        width: `${rect.width}px`,
        height: `${rect.height}px`,
        border: `${BORDER_WIDTH}px solid ${rgbaColor}`, // Use converted color
        boxSizing: 'border-box',
        pointerEvents: 'none',
        boxShadow: `0 0 0 1px rgba(255,255,255,0.5), 0 0 5px rgba(0,0,0,0.3)`
      });

      return border;
    },

    /**
     * Create semi-transparent mask
     * @param {HTMLElement} element - DOM element to be marked
     * @param {Object} rect - Element position and size
     * @param {string} color - Mask color
     * @return {HTMLElement} Created mask element
     */
    createMask: (element, rect, color) => {
      const mask = document.createElement('div');
      mask.className = 'delightful-marker-mask';

      // Calculate z-index
      const maskZIndex = utils.calculateZIndex(element, 'mask');

      // Extract RGB values to set opacity
      let rgbColor = color;
      if (color.startsWith('#')) {
        const r = parseInt(color.slice(1, 3), 16);
        const g = parseInt(color.slice(3, 5), 16);
        const b = parseInt(color.slice(5, 7), 16);
        rgbColor = `rgba(${r}, ${g}, ${b}, 0.15)`; // Low opacity to avoid affecting readability
      }

      Object.assign(mask.style, {
        position: 'absolute',
        zIndex: maskZIndex.toString(),
        top: `${rect.y}px`,
        left: `${rect.x}px`,
        width: `${rect.width}px`,
        height: `${rect.height}px`,
        backgroundColor: rgbColor,
        boxSizing: 'border-box',
        pointerEvents: 'none'
      });

      return mask;
    },

    /**
     * Create base style object for label element
     * @param {HTMLElement} element - DOM element to be marked
     * @param {string} color - Label background color
     * @return {Object} Label base style object
     */
    createLabelBaseStyles: (element, color) => {
      // Calculate z-index
      const labelZIndex = utils.calculateZIndex(element, 'label');

      // Set contrasting text color for specific colors
      let textColor = '#FFFFFF'; // Default white text

      // Light backgrounds use black text
      if (color === '#FFFF00' || color === '#00FF00') { // Yellow and green backgrounds use black text
        textColor = '#000000';
      }

      return {
        position: 'absolute',
        zIndex: labelZIndex.toString(),
        backgroundColor: color,
        color: textColor,
        fontSize: `${LABEL_FONT_SIZE}px`,
        fontWeight: 'bold',
        lineHeight: 1,
        padding: `${LABEL_PADDING}px`,
        borderRadius: '3px',
        boxShadow: '0 0 3px rgba(0,0,0,0.3)',
        pointerEvents: 'none',
        userSelect: 'none',
        minWidth: '12px',
        textAlign: 'center',
        opacity: 0.7
      };
    },

    /**
     * Calculate position styles for inner label
     * @param {Object} rect - Element position and size
     * @return {Object} Inner label position styles
     */
    createInnerLabelPositionStyles: (rect) => {
      return {
        top: `${rect.y}px`,
        left: `${rect.x + rect.width}px`,
        transform: 'translate(-100%, 0)'
      };
    },

    /**
     * Calculate position styles for outer label
     * @param {Object} rect - Element position and size
     * @param {Object} labelSize - Label size information
     * @return {Object} Outer label position styles
     */
    createOuterLabelPositionStyles: (rect, labelSize) => {
      return {
        top: `${rect.y - labelSize.height}px`,
        left: `${rect.x + rect.width}px`,
        transform: 'translate(-100%, 0)'
      };
    },

    /**
     * Create top-right label
     * @param {HTMLElement} element - DOM element to be marked
     * @param {Object} rect - Element position and size
     * @param {number} index - Element index
     * @param {string} color - Label color
     * @return {HTMLElement} Created label element
     */
    createLabel: (element, rect, index, color) => {
      const label = document.createElement('div');
      label.className = 'delightful-marker-label';

      // Generate label text (letter+number combination)
      const labelText = utils.generateLabelText(index);
      label.textContent = labelText;

      // Estimate label size
      const labelSize = utils.estimateLabelSize(labelText);

      // Determine whether the label should be placed outside
      const shouldPlaceOutside = utils.shouldPlaceLabelOutside(labelSize, rect);

      // Create base styles
      const labelStyles = creator.createLabelBaseStyles(element, color);

      // Add different position styles based on placement
      if (shouldPlaceOutside) {
        // Place outside top-right
        Object.assign(labelStyles, creator.createOuterLabelPositionStyles(rect, labelSize));
      } else {
        // Place inside top-right
        Object.assign(labelStyles, creator.createInnerLabelPositionStyles(rect));
      }

      // Apply styles
      Object.assign(label.style, labelStyles);

      // Store label position info for updates
      label.dataset.placement = shouldPlaceOutside ? 'outside' : 'inside';
      label.dataset.estimatedWidth = labelSize.width;
      label.dataset.estimatedHeight = labelSize.height;

      return label;
    }
  };

  /**
   * Marker manager - responsible for creating, updating, and removing marks
   */
  const markerManager = {
    /**
     * Create mark and add to Shadow DOM
     * @param {HTMLElement} element - DOM element to be marked
     * @param {number} index - Element index
     * @return {HTMLElement} Created border element
     */
    createMarker: (element, index) => {
      // Ensure Shadow DOM is created
      const shadow = utils.getShadowRoot();

      const rect = utils.getElementRect(element);
      const color = utils.getColor(index);

      // Create border
      const border = creator.createBorder(element, rect, color);
      shadow.appendChild(border);

      // Create semi-transparent mask
      const mask = creator.createMask(element, rect, color);
      shadow.appendChild(mask);

      // Create label
      const label = creator.createLabel(element, rect, index, color);
      shadow.appendChild(label);

      // Store mapping of DOM element to mark elements
      markers.push({
        element: element,
        border: border,
        mask: mask,
        label: label,
        index: index
      });

      return border;
    },

    /**
     * Clear all marks
     * Remove all mark elements from Shadow DOM
     */
    clearMarkers: () => {
      // Clear all mark elements
      utils.clearShadowRoot();

      // Clear marker array
      markers = [];
    },

    /**
     * Update positions of mark border and mask
     * @param {Object} marker - Mark object
     * @param {Object} rect - New position and size of element
     */
    updateBorderAndMask: (marker, rect) => {
      const border = marker.border;
      const mask = marker.mask;

      // Update border position
      border.style.top = `${rect.y}px`;
      border.style.left = `${rect.x}px`;
      border.style.width = `${rect.width}px`;
      border.style.height = `${rect.height}px`;

      // Update mask position
      if (mask) {
        mask.style.top = `${rect.y}px`;
        mask.style.left = `${rect.x}px`;
        mask.style.width = `${rect.width}px`;
        mask.style.height = `${rect.height}px`;
      }
    },

    /**
     * Update label position
     * @param {Object} marker - Mark object
     * @param {Object} rect - New position and size of element
     */
    updateLabel: (marker, rect) => {
      const label = marker.label;
      if (!label) return;

      // Get label placement info
      const placement = label.dataset.placement;
      const estimatedWidth = parseFloat(label.dataset.estimatedWidth || '0');
      const estimatedHeight = parseFloat(label.dataset.estimatedHeight || '0');

      if (placement === 'outside') {
        // Update outside label position
        label.style.top = `${rect.y - estimatedHeight}px`;
        label.style.left = `${rect.x + rect.width}px`;
        label.style.transform = 'translate(-100%, 0)';
      } else {
        // Update inside label position
        label.style.top = `${rect.y}px`;
        label.style.left = `${rect.x + rect.width}px`;
      }
    },

    /**
     * Update positions of all marks
     * Called when the page scrolls or element positions change
     */
    updateMarkers: () => {
      if (!isMarking || markers.length === 0) return;

      // Use requestAnimationFrame for visual updates to improve performance
      if (!updateScheduled) {
        updateScheduled = true;
        requestAnimationFrame(() => {
          markers.forEach(marker => {
            // Get new element position
            const rect = utils.getElementRect(marker.element);

            // Update border and mask
            markerManager.updateBorderAndMask(marker, rect);

            // Update label
            markerManager.updateLabel(marker, rect);
          });
          updateScheduled = false;
        });
      }
    }
  };

  /**
   * Observer manager - manages observation of DOM changes
   */
  const observerManager = {
    /**
     * Initialize all observers
     * Create and configure MutationObserver and ResizeObserver
     */
    initObservers: () => {
      // Create and configure MutationObserver
      mutationObserver = new MutationObserver(utils.debounce((mutations) => {
        if (isMarking) {
          refreshMarkers();
        }
      }, DEBOUNCE_DELAY));

      // Create and configure ResizeObserver - listen for window size changes
      resizeObserver = new ResizeObserver(utils.debounce(() => {
        if (isMarking) {
          markerManager.updateMarkers();
        }
      }, DEBOUNCE_DELAY));
    },

    /**
     * Start observing
     * Begin listening for DOM changes and window size changes
     */
    startObserving: () => {
      // Observe DOM changes across the whole body
      mutationObserver.observe(document.body, {
        childList: true,
        subtree: true,
        attributes: true,
        characterData: false,
        attributeFilter: ['style', 'class', 'hidden', 'display', 'visibility']
      });

      // Observe window size changes
      resizeObserver.observe(document.documentElement);
    },

    /**
     * Stop observing
     * Disconnect all Observer connections
     */
    stopObserving: () => {
      if (mutationObserver) {
        mutationObserver.disconnect();
      }

      if (resizeObserver) {
        resizeObserver.disconnect();
      }
    }
  };

  /**
   * Event handler - responsible for event listening and handling
   */
  const eventHandler = {
    /**
     * Update marks on scroll
     * Use debounce to reduce frequent updates
     */
    handleScroll: utils.debounce(() => {
      if (isMarking) {
        refreshMarkers();
      }
    }, DEBOUNCE_DELAY),

    /**
     * Initialize event listeners
     * Add scroll event listener
     */
    initEvents: () => {
      window.addEventListener('scroll', eventHandler.handleScroll);
    },

    /**
     * Remove event listeners
     * Clean up scroll event listener
     */
    removeEvents: () => {
      window.removeEventListener('scroll', eventHandler.handleScroll);
    }
  };

  /**
   * Remove marks for disappeared or hidden elements
   * @param {Array} currentElements - All current interactive elements
   * @return {Array} Filtered marks that still exist and are visible
   */
  function removeInvalidMarkers(currentElements) {
    return markers.filter(marker => {
      // Check whether element still exists in DOM and in current interactive list
      const stillExists = document.body.contains(marker.element) &&
                          currentElements.some(el => el === marker.element);

      if (!stillExists) {
        // Remove mark elements from Shadow DOM
        if (marker.border && marker.border.parentNode) {
          marker.border.parentNode.removeChild(marker.border);
        }
        if (marker.mask && marker.mask.parentNode) {
          marker.mask.parentNode.removeChild(marker.mask);
        }
        if (marker.label && marker.label.parentNode) {
          marker.label.parentNode.removeChild(marker.label);
        }
        return false;
      }
      return true;
    });
  }

  /**
   * Create marks for new elements
   * @param {Array} currentElements - All current interactive elements
   * @param {Set} markedElements - Set of already marked elements
   */
  function createMarkersForNewElements(currentElements, markedElements) {
    // Find unmarked new elements; getInteractiveDomNodes('viewport') already filters visible elements
    const newElements = currentElements.filter(el => !markedElements.has(el));

    // Create marks for new elements
    newElements.forEach((element, i) => {
      const index = markers.length + i; // Keep the color sequence continuous
      markerManager.createMarker(element, index);
    });
  }

  /**
   * Refresh all marks
   * Called when the DOM changes to update marks on the page
   */
  function refreshMarkers() {
    if (!isMarking) return;

    // Get all current interactive elements
    const currentElements = window.DelightfulTouch.getInteractiveDomNodes('viewport');

    // Remove marks for elements that have disappeared or become hidden
    markers = removeInvalidMarkers(currentElements);

    // Identify the set of already marked elements
    const markedElements = new Set(markers.map(m => m.element));

    // Create marks for new elements
    createMarkersForNewElements(currentElements, markedElements);

    // Reassign indices and colors for all marks
    reassignColorsAndIndices();

    // Update positions of all marks
    markerManager.updateMarkers();
  }

  /**
   * Reassign indices and colors for all marks
   * Ensure even color distribution
   */
  function reassignColorsAndIndices() {
    // Reassign index and color for each mark
    markers.forEach((marker, newIndex) => {
      // Update index
      marker.index = newIndex;

      // Get new color
      const newColor = utils.getColor(newIndex);

      // Update border color and opacity
      if (marker.border) {
          let newRgbaColor = newColor;
          if (newColor.startsWith('#')) {
            const r = parseInt(newColor.slice(1, 3), 16);
            const g = parseInt(newColor.slice(3, 5), 16);
            const b = parseInt(newColor.slice(5, 7), 16);
            newRgbaColor = `rgba(${r}, ${g}, ${b}, ${BORDER_OPACITY})`; // Use configured opacity
          }
        marker.border.style.border = `${BORDER_WIDTH}px solid ${newRgbaColor}`; // Update entire border style
      }

      // Update label text and style
      if (marker.label) {
        marker.label.textContent = utils.generateLabelText(newIndex);

        // Use createLabelBaseStyles to get label styles
        const labelStyles = creator.createLabelBaseStyles(marker.element, newColor);
        marker.label.style.backgroundColor = labelStyles.backgroundColor;
        marker.label.style.color = labelStyles.color;
      }

      // Update mask color - directly compute RGBA color
      if (marker.mask && newColor.startsWith('#')) {
        // Extract RGB values to set opacity
        const r = parseInt(newColor.slice(1, 3), 16);
        const g = parseInt(newColor.slice(3, 5), 16);
        const b = parseInt(newColor.slice(5, 7), 16);
        const rgbColor = `rgba(${r}, ${g}, ${b}, 0.15)`;
        marker.mask.style.backgroundColor = rgbColor;
      }
    });
  }

  /**
   * Mark all interactive elements on the page
   *
   * Features:
   * - Can be called repeatedly without creating duplicate marks
   * - Marks automatically update position as the page changes
   * - Supports marking dynamically added elements
   * - Each element shows a letter+number label at the top-right corner
   * - Uses Shadow DOM to isolate styles and avoid style pollution
   *
   * @returns {number} Number of marked elements
   */
  function mark() {
    // If already marking, just refresh instead of recreating
    if (isMarking) {
      refreshMarkers();
      return markers.length;
    }

    // Set marking state to active
    isMarking = true;

    // Initialize observers (if not already initialized)
    if (!mutationObserver) {
      observerManager.initObservers();
    }

    // Ensure Shadow DOM is initialized
    utils.getShadowRoot();

    // Clear any existing marks
    markerManager.clearMarkers();

    // Add X character to the page
    utils.insertXCharacter();

    // Get all visible interactive elements and create marks
    const domElements = window.DelightfulTouch.getInteractiveDomNodes('viewport');
    // getInteractiveDomNodes('viewport') already returns visible elements; no need to filter again

    domElements.forEach((domElement, index) => {
      markerManager.createMarker(domElement, index);
    });

    // Start observing
    observerManager.startObserving();

    // Initialize event listeners
    eventHandler.initEvents();

    return markers.length;
  }

  /**
   * Remove all element marks
   *
   * Features:
   * - Can be called repeatedly with no side effects
   * - Cleans up all related resources and event listeners
   * - Removes all mark elements from the Shadow DOM
   */
  function unmark() {
    // If already unmarked, do nothing
    if (!isMarking) return;

    // Set marking state to inactive
    isMarking = false;

    // Stop observing
    observerManager.stopObserving();

    // Remove event listeners
    eventHandler.removeEvents();

    // Clear all marks
    markerManager.clearMarkers();

    // Add X character to the page
    utils.insertXCharacter();

    // Optional: remove Shadow Host (if you do not want to keep the DOM node)
    if (shadowHost && shadowHost.parentNode) {
      shadowHost.parentNode.removeChild(shadowHost);
      shadowHost = null;
      shadowRoot = null;
    }
  }

  /**
  * Find the delightful-touch-id of the corresponding element by label text
   *
   * @param {string} labelText - Label text (e.g., "A2")
   * @returns {string|null} Return the element's delightful-touch-id if found, otherwise null
   */
  function find(labelText) {
    // Directly place the lookup logic here
    if (!isMarking || !labelText || typeof labelText !== 'string') {
      return null;
    }

    // Normalize label text to uppercase
    const normalizedLabelText = labelText.trim().toUpperCase();

    // Use Array.prototype.find to locate matching mark
    const foundMarker = markers.find(marker =>
      marker.label && marker.label.textContent === normalizedLabelText
    );

    // If found, return the element's delightful-touch-id, otherwise null
    return foundMarker ? foundMarker.element.getAttribute('delightful-touch-id') : null;
  }

  // Expose public API
  window.DelightfulMarker = {
    mark: mark,
    unmark: unmark,
    find: find
  };
})();
