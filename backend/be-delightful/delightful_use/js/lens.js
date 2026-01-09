/**
 * @fileoverview DelightfulLens - Intelligent Web Content to Markdown Tool
 *
 * This script analyzes the web page DOM structure, extracts main content, and converts it to Markdown format.
 * It can handle various HTML elements, including headings, paragraphs, lists, links, images, code blocks, and tables.
 * The script applies complex visibility and viewport filtering logic during extraction to ensure only content
 * of actual interest to users is extracted.
 *
 * @version 1.5.1  // Adapted for structured links (e.g., article cards)
 * @license MIT
 * @author Your Name/Team
 */

/**
 * =========================================================================
 * **Critical Core Design Principles and Comments - Required Reading for Maintainers!**
 * =========================================================================
 *
 * **Background**:
 * During development, we encountered a tricky issue: when using 'viewport' mode,
 * even though content on the page was visually within the viewport, the script sometimes failed to extract it, or extracted
 * too much content outside the viewport. This problem was particularly prominent on pages with complex layouts, scrolling containers, or lazy-loaded
 * content.
 *
 * **Root Cause**:
 * In the initial implementation, visibility (including viewport checking) was performed recursively
 * when processing each node. If a parent container element (like `<div>`) was judged as "not in viewport"
 * because it partially exceeded viewport boundaries, all its descendant nodes (regardless of whether
 * they were in the viewport) would be skipped directly, causing content loss. Conversely, if a large
 * parent container element had even a small overlap with the viewport, all its child nodes' content
 * (including those outside the viewport) could be included. This "one-size-fits-all" logic couldn't
 * accurately reflect the actual content users see.
 *
 * **Final Solution: "Minimum Content Unit Viewport Filtering Principle"**:
 * To solve the above problem and ensure accuracy and robustness of extraction results (we prefer "better to recognize more than
 * miss important information"), we adopted a viewport filtering strategy based on "minimum content units".
 * The core idea is:
 *
 * 1.  **Hard Filtering First**: `processNode` function as the processing entry, performs hard filtering first.
 *     Only when an element is "absolutely invisible" (e.g., CSS set to `display:none`,
 *     `visibility:hidden`, `opacity:0`, or has no actual dimensions `width/height <= 1px`,
 *     or is an ignored tag defined in `IGNORED_TAGS`), will it **completely stop** processing that node
 *     and all its descendant nodes. This is the most basic filtering layer, excluding structurally or stylistically
 *     explicitly hidden elements.
 *
 * 2.  **Viewport Check Sinking**: **`processNode` itself no longer performs any viewport checks** (`isElementInViewport`).
 *     The logic for determining whether within viewport is **sunk down** to more specific, element-type-specific
 *     `processXYZ` functions. This avoids erroneously excluding entire subtrees early in node tree processing due to parent container viewport
 *     state.
 *
 * 3.  **Container Elements Don't Check Viewport**: For **container-type** elements (like `<div>`, `<p>`, `<h1>-<h6>`,
 *     `<li>`, `<strong>`, `<em>`, etc.), their processing functions (`processParagraph`,
 *     `processHeading`, `processGenericBlock`, etc.) **no longer perform viewport checks**.
 *     Their main responsibility is to unconditionally recursively process child nodes (calling `_processAndCombineChildren`),
 *     then wrap the **already viewport-filtered** Markdown content returned by child nodes with their own
 *     Markdown structure (like `#`, `**`, list markers). This ensures that even if containers
 *     span viewport boundaries, their internal truly visible content can be correctly extracted and formatted.
 *
 * 4.  **Leaf/Atomic/Structural Elements Check Own Viewport**: For elements that **directly produce content** or are **structurally indivisible**
 *     (which we call "minimum units" or "atomic units"), their processing functions (`processImage`,
 *     `processLink`, `processCodeBlock`, `processTable`, `processHorizontalRule`,
 *     `processInlineCode`) **must** use `utils.isElementInViewport` in `scope='viewport'` mode to
 *     check whether **themselves** are within viewport (and meet visibility thresholds).
 *     If not in viewport, directly return `null`, filtering out that element. This ensures only actually visible
 *     atomic content like images, links, code blocks, tables, etc. are extracted.
 *
 * 5.  **Special Handling of Text Nodes**: Text nodes (`Node.TEXT_NODE`) cannot directly obtain precise
 *     bounding boxes or apply `isElementInViewport`. Therefore, `processTextNode` function uses an
 *     **approximation strategy**: in `scope='viewport'` mode, it checks whether its **direct parent element
 *     (`parentElement`)** meets `utils.isElementInViewport` conditions. Only
 *     when the parent element is considered within viewport will the text node be processed. This is the most practical and
 *     relatively reliable method closest to the "minimum unit" principle when unable to get precise text rendering range. Although
 *     not perfect (may include cases where parent is in viewport but text itself scrolled out), it is a
 *     result of balancing accuracy and implementation complexity.
 *
 * 6.  **`utils.isElementInViewport` Robustness**: This function not only checks element bounding box
 *     (`getBoundingClientRect`) overlap with viewport (`window.innerWidth/Height`),
 *     but also introduces **minimum visible absolute pixel area** (`MIN_ABSOLUTE_AREA_IN_VIEWPORT`)
 *     and **minimum visible relative area ratio** (`MIN_AREA_RATIO_IN_VIEWPORT`) thresholds.
 *     Only when an element's visible portion is sufficiently "significant" (meets either threshold) is it considered in viewport.
 *     This effectively avoids large elements with only a few pixels overlapping viewport being incorrectly judged as visible,
 *     thereby improving viewport judgment accuracy and robustness.
 *
 * **Maintenance Warning**:
 * **Please be sure to retain and deeply understand this comment!** The above viewport filtering logic is one of the core competencies of this tool,
 * the key design for solving complex web content extraction accuracy issues, validated through extensive test scenarios and multiple rounds of
 * debugging iterations before being finalized.
 *
 * **Never** casually modify the following content unless you fully understand its design intent and potential impact on the overall process:
 *   - `processNode` basic filtering logic (hard filtering).
 *   - Responsibility allocation in various `processXYZ` functions regarding **whether** to perform `isElementInViewport` checks.
 *     (Containers don't check, atomics check, text checks parent).
 *   - `utils.isElementInViewport` core judgment criteria (overlap check + dual thresholds).
 *
 * Incorrect modifications may very likely cause the previously solved "viewport content extraction inaccuracy" problem to recur, or introduce new,
 * more subtle bugs. Before making any related adjustments, be sure to thoroughly test various edge cases.
 * =========================================================================
 */

(function() {
  'use strict'; // Enable strict mode to help catch common errors

  // ==========================================================================
  // § 1. Global Constants & Configuration (Global Constants & Configuration)
  // ==========================================================================

  /** @constant {number} Default base font size (px)，used for some style inference */
  const DEFAULT_BASE_FONT_SIZE = 16;
  /** @constant {number} Minimum ratio threshold of heading font size relative to base font size, used to infer heading level */
  const HEADING_FONT_SIZE_RATIO = 1.2;
  /** @constant {number} Markdown Maximum heading level supported by specification */
  const HEADING_LEVELS = 6;
  /** @constant {number} Minimum character length considered valid text content */
  const MIN_TEXT_LENGTH = 2; // Filter out overly short, meaningless text fragments
  /** @constant {number} Maximum allowed URL length to prevent abnormal data */
  const MAX_URL_LENGTH = 1000;
  /** @constant {number} Maximum allowed DATA URL length to prevent abnormal data */
  const MAX_DATA_URL_LENGTH = 100;
  /** @constant {number} Maximum allowed image alt text length */
  const MAX_ALT_LENGTH = 250;
  /** @constant {string} Data URI standard prefix */
  const DATA_URI_PREFIX = 'data:';
  /**
   * @constant {number} Minimum absolute pixel area threshold that element must occupy in viewport (px²)。
   * Used by `isElementInViewport` function to increase viewport judgment robustness, filtering out those
   * large elements with only minor edge or corner overlap with viewport.
   * (This value can be fine-tuned based on actual test results)
   */
  const MIN_ABSOLUTE_AREA_IN_VIEWPORT = 50; // For example: at least 50 square pixels must be in viewport
  /**
   * @constant {number} Minimum relative area ratio threshold that element must occupy in viewport (0 to 1)。
   * Used by `isElementInViewport` function, same logic as above, providing another dimension of filtering.
   * (This value can be fine-tuned based on actual test results)
   */
  const MIN_AREA_RATIO_IN_VIEWPORT = 0.2; // For example: 20% of element's own area must be in viewport

  /**
   * @constant {number} Maximum allowed Data URI length.
   * For oversized Data URIs (usually embedded large images or files), direct processing may consume too much
   * resources or cause Markdown file to be too large, so set an upper limit.
   * Especially in image lazy loading scenarios, sometimes short placeholder Data URIs are used.
   */
  const MAX_DATA_URI_LENGTH_FOR_LAZY_LOAD = 2048;

  /**
   * @constant {Set<string>} Hard ignore rule: these tags and all their descendant nodes will never be processed.
   * Mainly includes scripts, styles, meta information, headers, navigation, footers, sidebars, form elements, iframes, SVG, etc.
   * tags that typically do not belong to main reading content. `I` tag is often used for icon fonts and is also excluded.
   */
  const IGNORED_TAGS = new Set([
      'SCRIPT', 'STYLE', 'NOSCRIPT', 'META', 'LINK', 'HEAD',
      'NAV', 'FOOTER', 'ASIDE', 'FORM', 'BUTTON', 'INPUT',
      'TEXTAREA', 'SELECT', 'OPTION', 'IFRAME', 'SVG', 'I'
  ]);

  /**
   * @constant {RegExp} Regex to match class names of marker elements that should be ignored.
   * Currently matches all class names with 'delightful-marker-' prefix, which are usually elements added by marking tools on the page.
   */
  const IGNORED_CLASS_PATTERN = /delightful-marker-/;

  /**
   * @constant {Set<string>} Used for quick determination of common HTML block-level element tags.
   * This helps `isBlockElement` make quick judgments, and is also used by `contentFinder` to identify potential content blocks.
   */
  const BLOCK_TAGS_FOR_ALL_SCOPE = new Set([
      'P', 'DIV', 'H1', 'H2', 'H3', 'H4', 'H5', 'H6', 'LI',
      'BLOCKQUOTE', 'PRE', 'ARTICLE', 'SECTION', 'MAIN', 'TABLE',
      'FIGURE', 'UL', 'OL', 'DETAILS', 'SUMMARY', 'HR'
  ]);

  /**
   * @constant {Set<string>} Used for quick determination of common HTML inline element tags.
   * This helps `isInlineElement` make quick judgments.
   */
   const INLINE_TAGS = new Set([
       'SPAN', 'A', 'IMG', 'CODE', 'STRONG', 'B', 'EM', 'I', // 'I' is ignored, but theoretically is inline
       'SUB', 'SUP', 'MARK', 'SMALL', 'Q', 'CITE', 'ABBR',
       'TIME', 'VAR', 'KBD', 'SAMP', 'BR', 'WBR'
   ]);

  // ==========================================================================
  // § 2. Utility Functions (Utility Functions)
  //    Contains basic functions for text processing, style retrieval, visibility determination, URL processing, etc.
  // ==========================================================================
  const utils = {
    /**
     * Clean text string:
     * 1. Replace multiple consecutive whitespace characters (spaces, tabs, line breaks, etc.) with a single space.
     * 2. Remove leading and trailing whitespace from string.
     * @param {string | null | undefined} text - Input text.
     * @returns {string} Cleaned text, returns empty string if input is invalid.
     */
    cleanText: (text) => {
      if (!text) {
          return '';
      }
      // \s matches any whitespace character，+ matches one or more
      return text.replace(/\s+/g, ' ').trim();
    },

    /**
     * Determine if input is blank string (null, undefined, or string containing only whitespace)。
     * @param {string | null | undefined} text - Input text.
     * @returns {boolean} Returns true if text is blank。
     */
    isEmptyText: (text) => {
      // Check for null/undefined, or length is 0 after trim
      return !text || text.trim().length === 0;
    },

    /**
     * Safely get computed style of element (Computed Style)。
     * Uses `window.getComputedStyle` with error handling。
     * @param {Element} element - Target DOM element。
     * @returns {CSSStyleDeclaration | null} Computed style object, or null on failure。
     */
    getStyle: (element) => {
      try {
        // Ensure passed value is a valid Element object
        if (!(element instanceof Element)) {
            return null;
        }
        return window.getComputedStyle(element);
      } catch (e) {
        // May throw exception in edge cases (e.g., element removed before getting style)
        // console.warn("DelightfulLens: Failed to get computed style for element:", element, e); // Can uncomment for debugging
        return null;
      }
    },

    /**
     * Based on
     * This is the first step of hard filtering in `processNode`。
     * Check key CSS properties：`display`, `visibility`, `opacity`, `clip`, `clip-path`。
     * @param {Element} element - Target DOM element。
     * @returns {boolean} Return
     */
    isCssVisible: (element) => {
      // Get
      if (!element || typeof element.getBoundingClientRect !== 'function') {
          return false;
      }
      const style = utils.getStyle(element);
      if (!style) {
          return false; // Process
      }

      // Check various CSS situations causing element not visible
      const isInvisible =
          style.display === 'none' ||          // display: none
          style.visibility === 'hidden' ||     // visibility: hidden
          style.opacity === '0' ||             // opacity: 0 (string)
          parseFloat(style.opacity) === 0 ||   // opacity: 0 (numeric value)
          style.clip === 'rect(0px, 0px, 0px, 0px)' || // Old clip property hiding
          style.clipPath === 'inset(100%)';    // clip-path completely clipped and hidden

      return !isInvisible; // If no not-visible rules are hit, consider CSS visible
    },

    /**
     * Determine if element has actual render dimensions (hard rule, viewport not considered)。
     * This is the second step of hard filtering in `processNode`。
     * check's  `offsetWidth/Height` or `getBoundingClientRect` `width/height` whether greater than 1px。
     * Contains fallback logic: even if element itself has no dimensions, if it directly contains meaningful text content, it is still considered to have dimensions。
     * @param {Element} element - Target DOM element。
     * @returns {boolean} Return
     */
    hasPositiveDimensions: (element) => {
      // Get
      if (!element || typeof element.getBoundingClientRect !== 'function') {
          return false;
      }

      const rect = element.getBoundingClientRect();
      // Check
      // use > 1 to avoid in some cases 1px borderor placeholderelement
      const hasExplicitSize = (element.offsetWidth > 1 && element.offsetHeight > 1) ||
                              (rect.width > 1 && rect.height > 1);

      // Check
      // check its textContent whether contains at least MIN_TEXT_LENGTH non-whitespace characters
      const directTextContent = element.textContent || '';
      const hasMeaningfulText = !utils.isEmptyText(directTextContent) &&
                                directTextContent.length >= MIN_TEXT_LENGTH;

      // As long as it has clear dimensions or contains meaningful text, consider it to have positive dimensions
      return hasExplicitSize || hasMeaningfulText;
    },

    /**
     * [Determine
     * This is the key function implementing the "minimum content unit viewport filtering principle"。
     * Determine
     * @param {Element} element - Target DOM element。
     * @returns {boolean} Return
     */
    isElementInViewport: (element) => {
      // Get
      if (!element || typeof element.getBoundingClientRect !== 'function') {
        return false;
      }

      const rect = element.getBoundingClientRect();
      const viewportWidth = window.innerWidth || document.documentElement.clientWidth;
      const viewportHeight = window.innerHeight || document.documentElement.clientHeight;

      // --- 1. Check
      // check's edge boundaries whether at least part is within viewport coordinate range
      const overlaps =
          rect.bottom > 0 &&              // 's bottom is below viewport top
          rect.top < viewportHeight &&    // 's top is above viewport bottom
          rect.right > 0 &&             // 's right is to the right of viewport left
          rect.left < viewportWidth;      // 's left is to the left of viewport right

      if (!overlaps) {
        // console.debug(`DelightfulLens (isElementInViewport): Basic overlap check failed. Element:`, element); // can be enabled for debugging
        return false; // No overlap at all, directly determine not in viewport
      }

      // --- 2. Calculate the actual rectangle and area of the visible region ---
      // Actual coordinates of the overlapping part between element and viewport
      const visibleLeft = Math.max(0, rect.left);
      const visibleTop = Math.max(0, rect.top);
      const visibleRight = Math.min(viewportWidth, rect.right);
      const visibleBottom = Math.min(viewportHeight, rect.bottom);

      // Calculate width and height of visible part, ensure non-negative values
      const visibleWidth = Math.max(0, visibleRight - visibleLeft);
      const visibleHeight = Math.max(0, visibleBottom - visibleTop);

      // Calculate area of visible part
      const visibleArea = visibleWidth * visibleHeight;

      // --- 3. Calculate total element area and visible area ratio ---
      const totalArea = rect.width * rect.height;
      // Calculate ratio of visible area to total area, avoid division by zero
      const visibleRatio = totalArea > 0 ? visibleArea / totalArea : 0;

      // --- 4. Determine
      // As long as either minimum absolute area or minimum relative ratio is satisfied, consider the element sufficiently significant in viewport
      const meetsThreshold =
          visibleArea >= MIN_ABSOLUTE_AREA_IN_VIEWPORT ||
          visibleRatio >= MIN_AREA_RATIO_IN_VIEWPORT;

      /* // Detailed debug log, can be enabled as needed
      console.debug(`DelightfulLens (isElementInViewport): Element:`, element,
                    `Rect: {T:${rect.top.toFixed(0)}, L:${rect.left.toFixed(0)}, B:${rect.bottom.toFixed(0)}, R:${rect.right.toFixed(0)}, W:${rect.width.toFixed(0)}, H:${rect.height.toFixed(0)}}`,
                    `Viewport: {W:${viewportWidth}, H:${viewportHeight}}`,
                    `Visible Rect: {T:${visibleTop.toFixed(0)}, L:${visibleLeft.toFixed(0)}, B:${visibleBottom.toFixed(0)}, R:${visibleRight.toFixed(0)}, W:${visibleWidth.toFixed(0)}, H:${visibleHeight.toFixed(0)}}`,
                    `Visible Area: ${visibleArea.toFixed(1)} (Min: ${MIN_ABSOLUTE_AREA_IN_VIEWPORT})`,
                    `Visible Ratio: ${visibleRatio.toFixed(2)} (Min: ${MIN_AREA_RATIO_IN_VIEWPORT})`,
                    `Meets Threshold: ${meetsThreshold}`);
      */

      return meetsThreshold;
    },

    /**
     * [Determine
     * Check
     * `contentFinder` Process
     * @param {Element} element - Target DOM element。
     * @param {'all' | 'viewport'} [scope='viewport'] - filtering scope。
     * @returns {boolean} Determine
     */
    isEffectivelyVisible: (element, scope = "viewport") => {
      // Basic hard filters must pass
      if (!utils.isCssVisible(element) || !utils.hasPositiveDimensions(element)) {
          return false;
      }

      // Check if class name matches patterns that should be ignored
      if (element.className && typeof element.className === 'string' && IGNORED_CLASS_PATTERN.test(element.className)) {
          return false;
      }

      // Check
      if (scope === 'viewport' && !utils.isElementInViewport(element)) {
          return false;
      }
      // Check
      return true;
    },

    /**
     * Extract
     * check：
     * 1. whether tag name is 'IMG'。
     * 2. whether CSS visible and has positive dimensions。
     * 3. Get
     * @param {HTMLImageElement} imgElement - targetimageelement。
     * @returns {boolean} Return
     */
    isValidImage: (imgElement) => {
      // Check
      if (!imgElement || imgElement.tagName !== 'IMG') {
          return false;
      }
      // Check
      if (!utils.isCssVisible(imgElement) || !utils.hasPositiveDimensions(imgElement)) {
          return false;
      }
      // Check
      const imageUrl = utils.getImageUrl(imgElement);
      if (!imageUrl || imageUrl.length > MAX_URL_LENGTH || utils.isPlaceholderSvgDataUri(imageUrl)) {
          // Invalid URL, too long, or known SVG placeholder
          return false;
      }
      // Check
      return true;
    },

    /**
     * Check if the given URL is a common 1x1 SVG placeholder Data URI。
     * Such placeholders are commonly used for lazy loading or other purposes and should not be considered as true image content。
     * @param {string} url - image URL。
     * @returns {boolean} Return
     */
    isPlaceholderSvgDataUri: (url) => {
      if (!url || !url.startsWith('data:image/svg+xml')) {
          return false; // Not an SVG Data URI
      }
      try {
        let svgContent = '';
        const base64Marker = ';base64,';
        const commaIndex = url.indexOf(',');
        if (commaIndex === -1) return false; // formaterror

        const header = url.substring(0, commaIndex);
        const data = url.substring(commaIndex + 1);

        // Decode SVG content (may be Base64 or URL encoded)
        if (header.includes(base64Marker)) {
          svgContent = atob(data);
        } else {
          svgContent = decodeURIComponent(data);
        }

        // Check
        if (!svgContent.toLowerCase().includes('<svg')) {
            return true;
        }

        // Check
        const svgTagMatch = svgContent.match(/<svg[^>]*>/i);
        if (svgTagMatch) {
          const svgTag = svgTagMatch[0];
          // Check
          const widthMatch = /\bwidth\s*=\s*["\']?\s*1(px)?\s*["\']?/i.test(svgTag);
          const heightMatch = /\bheight\s*=\s*["\']?\s*1(px)?\s*["\']?/i.test(svgTag);
          // If both width=1 and height=1 are matched, consider it a placeholder
          return widthMatch && heightMatch;
        }
      } catch (e) {
        // Process
        // console.warn("Error checking SVG placeholder:", e, url); // can be enabled for debugging
      }
      // Default is not a placeholder
      return false;
    },

    /**
     * Get the valid final URL of the image。
     * Detection order：
     * 1. `element.currentSrc` (Browser current actual rendered src, most reliable)
     * 2. Common lazy loading attributes (`data-src`, `data-original`, etc.)
     * 3. Standard `element.src` attribute
     * Check
     * @param {HTMLImageElement} imgElement - targetimageelement。
     * @returns {string | null} Return
     */
    getImageUrl: (imgElement) => {
      if (!(imgElement instanceof HTMLImageElement)) {
          return null;
      }

      let potentialPlaceholderSrc = null; // Used to record possible placeholders in case no better selection later

      // 1. Try `currentSrc` (highest priority)
      const currentSrc = imgElement.currentSrc;
      if (currentSrc && currentSrc !== window.location.href && currentSrc !== 'about:blank') {
          if (!utils.isPlaceholderSvgDataUri(currentSrc)) {
              const resolvedUrl = utils.resolveUrl(currentSrc);
              if (resolvedUrl) {
                  return resolvedUrl; // Found valid currentSrc
              }
          } else {
              potentialPlaceholderSrc = currentSrc; // Record placeholder currentSrc
          }
      }

      // 2. Probe common lazy loading attributes
      const lazyLoadAttributes = [
          'data-src', 'data-original', 'data-original-src', 'data-lazy-src',
          'data-lazy', 'lazy-src', 'data-url'
      ];
      for (const attr of lazyLoadAttributes) {
          const attrValue = imgElement.getAttribute(attr);
          // Must have value, non-empty, not Data URI (lazy loading usually uses real URLs), not about:blank
          if (attrValue && attrValue.trim() && !attrValue.startsWith(DATA_URI_PREFIX) && attrValue !== 'about:blank') {
              if (!utils.isPlaceholderSvgDataUri(attrValue)) {
                  const resolvedUrl = utils.resolveUrl(attrValue);
                  if (resolvedUrl) {
                      return resolvedUrl; // Found valid lazy load src
                  }
              }
              // Note: Do not record placeholders in lazy load attributes, because src attribute has higher priority
          }
      }

      // 3. Try standard `src` attribute (lowest priority)
      const standardSrc = imgElement.getAttribute('src') || '';
      if (standardSrc && standardSrc !== window.location.href && standardSrc !== 'about:blank') {
          if (!utils.isPlaceholderSvgDataUri(standardSrc)) {
              // Process
              const isPotentiallyValidDataUri = standardSrc.startsWith(DATA_URI_PREFIX) &&
                                               standardSrc.length < MAX_DATA_URI_LENGTH_FOR_LAZY_LOAD;
              if (!standardSrc.startsWith(DATA_URI_PREFIX) || isPotentiallyValidDataUri) {
                  const resolvedUrl = utils.resolveUrl(standardSrc);
                  if (resolvedUrl) {
                      return resolvedUrl; // Found valid standard src
                  }
              }
          } else if (!potentialPlaceholderSrc) { // Only record src placeholder if no placeholder was recorded before
              potentialPlaceholderSrc = standardSrc;
          }
      }

      // Return
      // Return
      return null;
    },

    /**
     * Parse relative URL or protocol-relative URL (//...) into absolute URL。
     * Use `URL` constructor function and `document.baseURI`。
     * Restrict to allowed protocols (http, https, ftp, mailto, data)。
     * Restriction on Data URI length.
     * @param {string | null | undefined} url - Input URL string.
     * @returns {string | null} Return
     */
    resolveUrl: (url) => {
      if (!url || typeof url !== 'string') {
          return null;
      }
      const trimmedUrl = url.trim();
      // Filter out empty strings and javascript: pseudo-protocol
      if (trimmedUrl === '' || trimmedUrl.toLowerCase().startsWith('javascript:')) {
          return null;
      }

      try {
        let absoluteUrlStr = trimmedUrl;
        // Handle protocol-relative URLs (starting with //)
        if (absoluteUrlStr.startsWith('//')) {
          absoluteUrlStr = window.location.protocol + absoluteUrlStr;
        }

        // Use URL constructor function to parse, second parameter provides base URL
        const absoluteUrl = new URL(absoluteUrlStr, document.baseURI || window.location.href);

        // Restrict to allowed protocols
        const allowedProtocols = ['http:', 'https:', 'ftp:', 'mailto:', 'data:'];
        if (!allowedProtocols.includes(absoluteUrl.protocol)) {
          // console.warn("DelightfulLens: Ignoring URL with disallowed protocol:", absoluteUrl.protocol, absoluteUrl.href); // can be enabled for debugging
          return null;
        }

        // Apply length restriction to Data URI
        if (absoluteUrl.protocol === 'data:' && absoluteUrl.href.length > MAX_DATA_URL_LENGTH) {
          // console.warn("DelightfulLens: Ignoring long data URI:", absoluteUrl.href.substring(0, 50) + "..."); // can be enabled for debugging
          return null;
        }

        // Return the parsed absolute URL string
        return absoluteUrl.href;
      } catch (e) {
        // URL parsing failed (invalid URL format)
        // console.warn("DelightfulLens: Failed to resolve URL:", url, e); // can be enabled for debugging
        return null;
      }
    },

    /**
     * Get image alt text (Alt Text)。
     * Try order：
     * 1. `alt` attribute
     * 2. `title` attribute
     * 3. `aria-label` attribute
     * 4. Content of `<figcaption>` inside parent `<figure>`
     * 5. Extract
     * Extract
     * Return
     * Return
     * @param {HTMLImageElement} imgElement - targetimageelement。
     * @returns {string} Retrieved alt text (after cleaning and length restriction)。
     */
     getImageAlt: (imgElement) => {
      if (!(imgElement instanceof HTMLImageElement)) {
        return 'Return';
      }

      let alt = '';

      // Get
      const sources = [
        () => imgElement.getAttribute('alt'),      // 1. alt attribute
        () => imgElement.getAttribute('title'),     // 2. title attribute
        () => imgElement.getAttribute('aria-label'),// 3. aria-label attribute
        () => {                                      // 4. figcaption
          const figure = imgElement.closest('figure');
          const figcaption = figure ? figure.querySelector('figcaption') : null;
          return figcaption ? figcaption.textContent : null;
        },
        () => {                                      // 5. Extract
          const src = utils.getImageUrl(imgElement); // Get
          // Ignore data:, about:, javascript: protocols
          const protocolStopRegex = /^(data:|about:blank|javascript:)/i;
          if (src && !protocolStopRegex.test(src)) {
            try {
              const urlObject = new URL(src);
              // Get last part of path as filename
              const filename = urlObject.pathname.split('/').pop();
              if (filename) {
                // Decode, remove extension name, replace separator with space, merge spaces
                return decodeURIComponent(filename)
                  .replace(/\.\w+$/, '') // Remove common file extension
                  .replace(/[-_]+/g, ' ') // Replace - and _ with space
                  .replace(/\s+/g, ' ')  // Merge multiple spaces
                  .trim();
              }
            } catch (e) { /* Process
          }
          return null; // Extract
        }
      ];

      // Regular expression for common meaningless or placeholder alt text (case insensitive)
      // Including: common words, numeric IDs, UUIDs
      const commonJunkRegex = /^(image|img|logo|icon|banner|spacer|loading|photo|picture|bild|foto|spacer|transparent|empty|blank|placeholder|avatar|figure|grafik|_\d+|-\d+)$|^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i;

      // Traverse sources to find first valid and meaningful alt text
      for (const source of sources) {
        const potentialAlt = (source() || '').trim(); // Get and clean leading/trailing whitespace
        // Must meet minimum length and not match garbage text patterns
        if (potentialAlt.length >= MIN_TEXT_LENGTH && !commonJunkRegex.test(potentialAlt)) {
          alt = potentialAlt;
          break; // Found satisfactory alt, stop searching
        }
      }

      // If alt is still empty after loop ends, use default value
      if (!alt) {
        alt = 'Image';
      }

      // Apply length restriction to final result
      return alt.length > MAX_ALT_LENGTH ? alt.substring(0, MAX_ALT_LENGTH) + '...' : alt;
    },


    /**
     * Extract
     * check：
     * 1. whether tag name is 'A'。
     * 2. whether CSS visible and has positive dimensions。
     * 3. `href` whether attribute parses to valid, non-in-page anchor, non-JS URL。
     * 4. whether link contains meaningful text content or contains a valid image。
     * @param {HTMLAnchorElement} linkElement - targetlinkelement。
     * @returns {boolean} Return
     */
    isValidLink: (linkElement) => {
      // Check
      if (!linkElement || linkElement.tagName !== 'A') {
          return false;
      }
      // Check
      if (!utils.isCssVisible(linkElement) || !utils.hasPositiveDimensions(linkElement)) {
          return false;
      }

      // check href attribute
      const href = linkElement.getAttribute('href');
      const resolvedHref = utils.resolveUrl(href); // Check
      if (!resolvedHref ||           // Invalid or blocked URL
          resolvedHref.startsWith('#') || // Page-internal anchor
          resolvedHref.toLowerCase().startsWith('javascript:') // JavaScript pseudo-protocol
         ) {
          return false;
      }
      // check URL length
      if (resolvedHref.length > MAX_URL_LENGTH) {
          return false;
      }

      // Check link content: must contain valid text or valid image
      // 1. checktextcontent
      const textContent = utils.cleanText(linkElement.textContent);
      const hasMeaningfulText = textContent.length >= MIN_TEXT_LENGTH;

      // 2. Check whether contains valid image as child element
      // Use Array.from to convert HTMLCollection to array for using .some()
      const hasVisibleImage = Array.from(linkElement.children).some(child =>
          child.tagName === 'IMG' && utils.isValidImage(/** @type {HTMLImageElement} */(child))
      );

      // As long as there is text or image, consider it a valid link
      return hasMeaningfulText || hasVisibleImage;
    },

    /**
     * Determine whether element is a block-level element (Block-level element)。
     * Check
     * Check
     * @param {Element} element - Target DOM element。
     * @returns {boolean} Return
     */
    isBlockElement: (element) => {
      if (!element || typeof element.tagName !== 'string') {
          return false;
      }
      // Check
      if (BLOCK_TAGS_FOR_ALL_SCOPE.has(element.tagName.toUpperCase())) {
          return true;
      }
      // Determine
      const style = utils.getStyle(element);
      if (!style) {
          return false; // Determine
      }
      // Check common block-level display values
      const blockDisplays = [
          'block', 'flex', 'grid', 'table', 'list-item',
          'flow-root', 'article', 'section', 'main', 'figure', 'details'
          // Note: 'inline-block' is not purely block-level, not included here
      ];
      return blockDisplays.includes(style.display);
    },

    /**
     * Determine whether element is an inline-level element (Inline-level element)。
     * Check
     * Check
     * @param {Element} element - Target DOM element。
     * @returns {boolean} Return
     */
    isInlineElement: (element) => {
      if (!element || typeof element.tagName !== 'string') {
          return false;
      }
      // Check
      if (INLINE_TAGS.has(element.tagName.toUpperCase())) {
          return true;
      }
      // Determine
      const style = utils.getStyle(element);
      if (!style) {
          return false; // Determine
      }
      // Check if display starts with 'inline'
      return style.display.startsWith('inline');
    },

    /**
     * Get document base font size (usually body font-size)。
     * Used by `styleAnalyzer.getHeadingLevel` to calculate relative font size。
     * @returns {number} Return
     */
    getBaseFontSize: () => {
      try {
        const bodyStyle = utils.getStyle(document.body);
        // Parse body font-size, use default value if parsing fails or invalid
        return parseFloat(bodyStyle?.fontSize) || DEFAULT_BASE_FONT_SIZE;
      } catch (e) {
        // console.warn("DelightfulLens: Failed to get base font size."); // can be enabled for debugging
        return DEFAULT_BASE_FONT_SIZE;
      }
    },

    /**
     * [Determine
     * Used by `contentFinder` TreeWalker filter to quickly identify potential top-level content blocks。
     * checkcondition：
     * 1. Is an Element node.
     * 2. Not an ignored tag (`IGNORED_TAGS`)。
     * 3. Does not have ignored class name pattern (like `delightful-marker-*`)。
     * 4. Contains meaningful direct text child nodes。
     * 5. Or contains valid direct image child element。
     * 6. Or is a common container tag (like ARTICLE, P, DIV, LI, etc.)。
     * @param {Node} node - target DOM node。
     * @param {'all' | 'viewport'} [scope='viewport'] - Filtering scope (although this function currently does not directly use scope, maintain interface consistency)。
     * @returns {boolean} Return
     */
    isPotentiallyContentElement: (node, scope = "viewport") => {
      // Must be an Element node
      if (node.nodeType !== Node.ELEMENT_NODE || !node.tagName || typeof node.hasChildNodes !== 'function') {
        return false;
      }

      const element = /** @type {Element} */ (node);
      const tagName = element.tagName.toUpperCase();

      // 1. Filter out ignored tags
      if (IGNORED_TAGS.has(tagName)) {
        return false;
      }

      // 2. Filter out elements with ignored class name patterns
      if (element.className && typeof element.className === 'string' && IGNORED_CLASS_PATTERN.test(element.className)) {
        return false;
      }

      // 3. Check if has direct meaningful text content
      let hasDirectMeaningfulText = false;
      if (element.hasChildNodes()) {
        for (const childNode of element.childNodes) {
          // Check
          if (childNode.nodeType === Node.TEXT_NODE) {
            const cleanedText = utils.cleanText(childNode.textContent);
            if (cleanedText.length >= MIN_TEXT_LENGTH) {
              hasDirectMeaningfulText = true;
              break; // Finding one is enough
            }
          }
        }
      }

      // 4. Check if has direct valid image child element
      const hasVisibleDirectImage = Array.from(element.children).some(child =>
        child.tagName === 'IMG' && utils.isValidImage(/** @type {HTMLImageElement} */(child))
      );

      // 5. Check if is a common main content container tag
      const majorContainerTags = [
          'ARTICLE', 'MAIN', 'SECTION', 'P', 'DIV', 'BLOCKQUOTE',
          'LI', 'TD', 'TH', 'FIGURE', 'PRE', 'DETAILS', 'SUMMARY'
          // Determine
      ];
      const isMajorContainer = majorContainerTags.includes(tagName);

      // Meeting any condition is sufficient: has text, has image, is main container
      return hasDirectMeaningfulText || hasVisibleDirectImage || isMajorContainer;
    },

    /**
     * Determine whether node is empty whitespace text node or comment node。
     * Process
     * @param {Node} node - target DOM node。
     * @returns {boolean} Return
     */
    isWhitespaceOrCommentNode: (node) => {
      return (
          // Is a comment node
          node.nodeType === Node.COMMENT_NODE ||
          // Is text node and its content is empty whitespace
          (node.nodeType === Node.TEXT_NODE && utils.isEmptyText(node.textContent))
      );
    },

    /**
     * Strip common Markdown markings from Markdown text, attempt to restore to plain text。
     * Determine
     * Note: This stripping process may not be perfect, especially for nested or complex Markdown。
     * @param {string | null | undefined} markdownText - Input Markdown text.
     * @returns {string} Text after attempting to strip markings。
     */
    stripMarkdown: (markdownText) => {
        if (!markdownText) return '';
        let text = markdownText;

        // Remove code blocks (```...```, ~~~...~~~)
        text = text.replace(/^(?:```|~~~)[^\n]*\n[\s\S]*?^(?:```|~~~)\n*/gm, '');
        // Remove block quote markings (>)
        text = text.replace(/^(?:>\s*)+/gm, '');
        // Remove list markings (- * + 1.)
        text = text.replace(/^(\s*(?:[-*+]|\d+\.)\s+)/gm, '');
        // Remove horizontal lines
        text = text.replace(/^(?:-{3,}|_{3,}|\*{3,})\s*$/gm, '');
        // Remove image markings (![alt](url))
        text = text.replace(/!\[.*?]\(.*?\)/g, '');
        // Process
        for (let i = 0; i < 3; i++) {
            text = text.replace(/\[(.*?)\]\(.*?\)/g, '$1');
        }
        // removebold (**text**, __text__)
        text = text.replace(/(\*\*|__)(.*?)\1/g, '$2');
        // removeitalic (*text*, _text_)
        text = text.replace(/(\*|_)(.*?)\1/g, '$2');
        // Remove strikethrough (~~text~~)
        text = text.replace(/~~(.*?)~~/g, '$1');
        // Remove inline code (`code`)
        text = text.replace(/`(.+?)`/g, '$1');
        // Remove heading markings (# heading)
        text = text.replace(/^#+\s*/gm, '');
        // Remove HTML tags (may remain)
        text = text.replace(/<[^>]*>/g, '');
        // Merge multiple line breaks into one
        text = text.replace(/\n{2,}/g, '\n');
        // Remove extra spaces at line start/end, merge extra spaces in text
        text = text.replace(/[ \t\v\f\r]+/g, ' ');
        text = text.replace(/\n /g, '\n'); // Clean spaces after line breaks

        return text.trim();
    }
  }; // --- End of utils ---

  // ==========================================================================
  // § 3. Style Analyzer (Style Analyzer)
  //    Based on
  // ==========================================================================
  const styleAnalyzer = {
      /**
       * Infer heading level (1-6) based on tag name or computed style (font size, font weight)。
       * Prioritize using H1-H6 tag names。
       * Determine
       * @param {Element} element - Target DOM element。
       * @returns {number} Return
       */
      getHeadingLevel: (element) => {
          if (!element || typeof element.tagName !== 'string') {
              return 0;
          }

          // 1. Prioritize using H1-H6 tag names
          const tagName = element.tagName.toLowerCase();
          if (tagName.match(/^h([1-6])$/)) {
              return parseInt(tagName.substring(1));
          }

          // 2. Based on
          if (!utils.isBlockElement(element)) {
              return 0; // Inline elements are unlikely to be headings
          }

          try {
              const style = utils.getStyle(element);
              if (!style) {
                  return 0; // Get
              }

              const fontSize = parseFloat(style.fontSize);
              const baseFontSize = utils.getBaseFontSize();
              const fontWeightStr = style.fontWeight;

              // Parse font weight (normal=400, bold=700)
              let fontWeight = 400;
              if (fontWeightStr === 'bold') {
                  fontWeight = 700;
              } else if (!isNaN(parseInt(fontWeightStr))) {
                  fontWeight = parseInt(fontWeightStr);
              }

              // Must have valid font size and greater than base font size
              if (!fontSize || !baseFontSize || fontSize <= baseFontSize) {
                  return 0;
              }

              // Calculate font size ratio
              const sizeRatio = fontSize / baseFontSize;

              // Satisfy both font size ratio threshold and minimum font weight threshold (e.g., >= 600)
              if (sizeRatio >= HEADING_FONT_SIZE_RATIO && fontWeight >= 600) {
                  // Estimate level based on font size ratio (these ratios are empirical values and may need adjustment)
                  let level;
                  if (sizeRatio >= 2.0) level = 1;
                  else if (sizeRatio >= 1.5) level = 2;
                  else if (sizeRatio >= 1.3) level = 3;
                  else if (sizeRatio >= 1.17) level = 4;
                  else level = 5; // Minimum satisfying condition is set to H5

                  // If font weight is very high (>= 700), can appropriately increase level (but cannot exceed H1)
                  if (fontWeight >= 700 && level > 1) {
                      level--;
                  }

                  // Ensure level is between 1-6
                  return Math.min(HEADING_LEVELS, Math.max(1, level));
              }
          } catch (e) {
              // console.warn("DelightfulLens: Error analyzing heading style:", element, e); // can be enabled for debugging
              return 0; // Return
          }

          return 0; // Does not meet conditions, not a heading
      },

      /**
       * Determine whether computed style is bold。
       * @param {Element} element - Target DOM element。
       * @returns {boolean} Return
       */
      isBold: (element) => {
          try {
              const style = utils.getStyle(element);
              if (!style) return false;
              const fontWeightStr = style.fontWeight;
              // Check if fontWeight is 'bold' or numeric value >= 600
              return fontWeightStr === 'bold' || (parseInt(fontWeightStr) >= 600);
          } catch (e) {
              return false; // Return
          }
      },

      /**
       * Determine whether computed style is italic。
       * @param {Element} element - Target DOM element。
       * @returns {boolean} Return
       */
      isItalic: (element) => {
          try {
              const style = utils.getStyle(element);
              if (!style) return false;
              // Check if fontStyle is 'italic' or 'oblique'
              return style.fontStyle === 'italic' || style.fontStyle === 'oblique';
          } catch (e) {
              return false; // Return
          }
      },
  }; // --- End of styleAnalyzer ---

  // ==========================================================================
  // § 4. Markdown Generator (Markdown Generator)
  //    Process
  // ==========================================================================
  const markdownGenerator = {
      /** Generate Markdown heading */
      heading: (text, level) => {
          // Ensure level is between 1-6
          const validLevel = Math.max(1, Math.min(HEADING_LEVELS, level));
          return `${'#'.repeat(validLevel)} ${text.trim()}\n\n`; // Add two line breaks to form paragraph
      },
      /** Generate Markdown paragraph */
      paragraph: (text) => {
          return `${text.trim()}\n\n`; // Add two line breaks
      },
      /** Generate Markdown bold */
      bold: (text) => {
          return `**${text.trim()}**`;
      },
      /** Generate Markdown italic */
      italic: (text) => {
          return `*${text.trim()}*`;
      },
      /** Generate Markdown image */
      image: (alt, url) => {
          // Process
          return `![${alt.trim()}](${url})\n\n`; // Image is block-level element, add two line breaks
      },
      /** Generate Markdown link */
      link: (text, url) => {
          return `[${text.trim()}](${url})`;
      },
      /** Generate Markdown list item */
      listItem: (text, level = 0, isOrdered = false, index = 1) => {
          // Calculate indent (2 spaces per level)
          const indent = '  '.repeat(level);
          // Determine list marker (ordered or unordered)
          const marker = isOrdered ? `${index}. ` : '- ';
          // text Process
          return `${indent}${marker}${text}`;
      },
      /** Generate Markdown blockquote */
      blockquote: (text) => {
          // Add "> " prefix to each line
          const lines = text.trim().split('\n');
          const quotedLines = lines.map(line => `> ${line}`);
          return `${quotedLines.join('\n')}\n\n`; // Add two line breaks at the end
      },
      /** Generate Markdown code block */
      codeBlock: (text, language = '') => {
          // Ensure language identifier does not contain spaces or special characters (optional enhancement)
          const langIdentifier = language.trim().split(/\s+/)[0] || '';
          return `\`\`\`${langIdentifier}\n${text.trim()}\n\`\`\`\n\n`; // Add two line breaks at the end
      },
      /** Generate Markdown inline code */
      inlineCode: (text) => {
          // For inline code, need to pay special attention to whether content contains ` character, theoretically needs escaping
          // But for simplicity, only do trim here
          return `\`${text.trim()}\``;
      },
      /** Generate Markdown horizontal line */
      horizontalRule: () => {
          return `---\n\n`; // Add two line breaks at the end
      },
      /** Generate Markdown strikethrough */
      strikethrough: (text) => {
          return `~~${text.trim()}~~`;
      }
  }; // --- End of markdownGenerator ---

  // ==========================================================================
  // § 5. Extract
  //    Based on
  // ==========================================================================
  const contentExtractor = {
    /** Process
    processedNodes: new WeakSet(),

    /**
     * @typedef {object} ProcessResult Process
     * @property {string} markdown - Markdown string fragment generated by this node and its child nodes。
     * @property {boolean} isBlock - Mark whether this result represents a block-level Markdown element
     *                             (affects line break logic during subsequent concatenation)。
     * @property {Element} [processedNode] - (Process
     */

    /**
     * Main entry function: accepts a top-level element array, generates complete Markdown document。
     * @param {Element[]} elements - Top-level related element array found by `contentFinder`。
     * @param {object} globalContext - Global context, contains `scope` ('all' or 'viewport')。
     * @returns {string} Final Markdown string after concatenation and cleaning。
     */
    generateMarkdownFromElements: function(elements, globalContext = {}) {
      this.processedNodes = new WeakSet(); // Reset on each call to prevent interference across calls
      let combinedMarkdown = '';
      let lastElementResult = null; // Determine

      for (let i = 0; i < elements.length; i++) {
        const element = elements[i];
        // Process
        const elementResult = this.processNode(element, {
            ...globalContext,
            isTopLevelElement: true // Process
        });

        // Return
        if (!elementResult || utils.isEmptyText(elementResult.markdown)) {
          continue;
        }

        // --- Intelligent Markdown concatenation logic ---
        if (combinedMarkdown === '') {
          // First valid result, assign directly
          combinedMarkdown = elementResult.markdown;
        } else {
          // Determine whether to add block-level separator (usually two line breaks) between current result and previous result
          // Need separator when: current result is block-level, or previous result is block-level
          const requiresBlockSeparator = elementResult.isBlock || (lastElementResult && lastElementResult.isBlock);

          if (requiresBlockSeparator) {
            // Need block-level separation: ensure previous markdown ends with two line breaks, then append new markdown (remove its possible leading line breaks)
            combinedMarkdown = combinedMarkdown.replace(/(\n{2,})?$/, '\n\n') + elementResult.markdown.replace(/^\n+/, '');
          } else {
            // No need for block-level separation (two inline elements adjacent):
            // Check if space is needed at join. If either edge ends/starts with whitespace, don't add; else add one space。
            const endsWithSpaceOrNL = /[\s\n]$/.test(combinedMarkdown);
            const startsWithSpaceOrNL = /^[\s\n]/.test(elementResult.markdown);
            const separator = (endsWithSpaceOrNL || startsWithSpaceOrNL) ? '' : ' ';
            // Based on
            combinedMarkdown = combinedMarkdown.trimEnd() + separator + elementResult.markdown.trimStart();
          }
        }
        // updatepreviousvalidresult
        lastElementResult = elementResult;
      }

      // Final cleaning:
      // 1. Merge three or more consecutive line breaks into two line breaks。
      // 2. Remove leading/trailing whitespace from final result。
      return combinedMarkdown.replace(/\n{3,}/g, '\n\n').trim();
    },

    /**
     * [Process
     * Main responsibilities:
     * 1. Check
     * 2. Hard filters (ignored tags, CSS not visible, no dimensions)。
     * 3. Dispatch to `processElementNode` or `processTextNode` based on node type。
     * 4. Process
     * **Check
     * @param {Node} node - Process
     * @param {object} context - Process
     * @returns {ProcessResult | null} Return
     */
    processNode: function(node, context = {}) {
      // 1. Process
      if (!node || this.processedNodes.has(node)) {
          // console.debug("Node skipped (null or already processed)");
          return null;
      }
      // Skip empty whitespace text nodes and comment nodes
      if (utils.isWhitespaceOrCommentNode(node)) {
          // console.debug("Node skipped (whitespace or comment)");
          return null;
      }

      // 2. Execute hard filters on Element node (CSS visibility, dimensions, ignored tags)
      if (node.nodeType === Node.ELEMENT_NODE) {
        const element = /** @type {Element} */ (node);
        const tagName = element.tagName.toUpperCase();

        // 2a. Check if is ignored tag
        if (IGNORED_TAGS.has(tagName)) {
          // console.debug(`Node skipped (ignored tag: ${tagName})`, element);
          this.processedNodes.add(node); // Process
          return null; // Process
        }

        // 2b. Check if element class name matches patterns to ignore
        if (element.className && typeof element.className === 'string' && IGNORED_CLASS_PATTERN.test(element.className)) {
          // console.debug(`Node skipped (ignored class pattern: ${element.className})`, element);
          this.processedNodes.add(node); // Process
          return null; // Hard stop
        }

        // 2c. Check CSS visibility and whether has positive dimensions
        const cssVisible = utils.isCssVisible(element);
        const hasDimensions = utils.hasPositiveDimensions(element);
        if (!cssVisible || !hasDimensions) {
          // console.debug(`Node skipped (CSS hidden=${!cssVisible}, no dimensions=${!hasDimensions})`, element);
          this.processedNodes.add(node); // Process
          return null; // Hard stop
        }
        // --- Hard filters passed ---
      }

      // 3. Process
      //    Process
      this.processedNodes.add(node);

      // 4. Process
      let result = null;
      let caughtError = null;
      try {
        if (node.nodeType === Node.ELEMENT_NODE) {
          // Process Element node
          result = this.processElementNode(/** @type {Element} */(node), context);
        } else if (node.nodeType === Node.TEXT_NODE) {
          // Process text node
          result = this.processTextNode(/** @type {Text} */(node), context);
        }
        // Other node types (like comment nodes already filtered) are directly ignored, result remains null
      } catch (e) {
        // Process
        console.error("DelightfulLens: Error processing node:", node, e);
        caughtError = e;
        result = null; // Result invalid when error occurs
      } finally {
        // 5. Check
        if (result && utils.isEmptyText(result.markdown) && !caughtError) {
          // Return
          // Process
          result = null;
        }
        // Return
      }

      return result; // return ProcessResult or null
    },

    /**
     * [Process
     * Main logic:
     * 1. Check
     *    Return
     * 2. cleantextcontent (`utils.cleanText`)。
     * 3. Return
     * 4. Return
     * @param {Text} node - textnode。
     * @param {object} context - Process
     * @returns {ProcessResult | null} Processing result or null。
     */
    processTextNode: function(node, context) {
      // 1. Check
      if (context.scope === 'viewport') {
          const parentElement = node.parentElement;
          // Must have parent element, and parent element is in viewport
          if (!parentElement || !utils.isElementInViewport(parentElement)) {
              // parent elementInvalidBased on
              // console.debug("Text node skipped (parent element is outside viewport or null)", node); // can be enabled for debugging
              return null;
          }
          // --- parent elementProcess
      }

      // 2. cleantextcontent
      const text = utils.cleanText(node.textContent);

      // 3. Check if text is empty after cleaning
      if (utils.isEmptyText(text)) {
          // console.debug("Text node skipped (empty after cleaning)", node); // can be enabled for debugging
          return null;
      }

      // 4. returnValidtextresult
      return {
          markdown: text,
          isBlock: false // Text node itself is inline in nature
      };
    },

    /**
     * [Process
     * Process
     * Determine
     * Determine
     * @param {Element} element - Element node。
     * @param {object} context - Process
     * @returns {ProcessResult | null} Process
     */
    processElementNode: function(element, context) {
      const tagName = element.tagName.toUpperCase();
      // Create child context, inherit from parent context, and set isTopLevelElement to false
      const childContext = { ...context, isTopLevelElement: false };
      let result = null;

      // --- Process
      // If is A tag, and its direct child elements contain block-level elements (like h1-6, p, div, etc.)
      if (tagName === 'A' && Array.from(element.children).some(child => utils.isBlockElement(child))) {
          // Process
          result = this.processStructuredLink(element, childContext);
          // Process
          if (result) {
              return result;
          }
          // Return
          // Process
          // Based on
          // Process
          return null;
      }
      // --- Process

      // Based on
      switch (tagName) {
        // --- heading ---
        case 'H1': case 'H2': case 'H3': case 'H4': case 'H5': case 'H6':
          result = this.processHeading(element, childContext, parseInt(tagName.substring(1)));
          break;
        // --- paragraph ---
        case 'P':
          result = this.processParagraph(element, childContext);
          break;
        // --- link ---
        case 'A':
          // Process
          result = this.processLink(element, childContext); // *Check
          break;
        // --- image ---
        case 'IMG':
          result = this.processImage(element, childContext); // *Check
          break;
        // --- list ---
        case 'UL': // Unordered list
          result = this.processList(element, childContext, false); // isOrdered = false
          break;
        case 'OL': // Ordered list
          result = this.processList(element, childContext, true);  // isOrdered = true
          break;
        // --- List items ---
        case 'LI':
          // Get
          const listLevel = context.listLevel || 0;
          const isOrderedList = context.isOrderedList || false;
          const listItemIndex = context.listItemIndex || 1;
          result = this.processListItem(element, childContext, listLevel, isOrderedList, listItemIndex);
          break;
        // --- Block quotes ---
        case 'BLOCKQUOTE':
          result = this.processBlockquote(element, childContext);
          break;
        // --- Code blocks ---
        case 'PRE':
          result = this.processCodeBlock(element, childContext); // *Check
          break;
        // --- Inline code ---
        case 'CODE':
          // Process
          // (PRE Process
          result = element.closest('pre') ? null : this.processInlineCode(element, childContext); // *Check
          break;
        // --- line break ---
        case 'BR':
          // Convert
          result = { markdown: '  \n', isBlock: false }; // Check
          break;
        // --- Horizontal line ---
        case 'HR':
          result = this.processHorizontalRule(element, childContext); // *Check
          break;
        // --- Inline style ---
        case 'STRONG': case 'B': // bold
          result = this.processStyledInline(element, childContext, markdownGenerator.bold);
          break;
        case 'EM': case 'I': // italic (note: I tag if not excluded by IGNORED_TAGS)
          result = this.processStyledInline(element, childContext, markdownGenerator.italic);
          break;
        case 'S': case 'STRIKE': case 'DEL': // Strikethrough
          result = this.processStyledInline(element, childContext, markdownGenerator.strikethrough);
          break;
        // --- table ---
        case 'TABLE':
          // --- Determine
          if (this.isLayoutTable(/** @type {HTMLTableElement} */(element))) {
            // Process
            // console.debug("DelightfulLens: Treating table as layout block:", element); // Optional debug log
            result = this.processGenericBlock(element, childContext);
          } else {
            // Process
            // console.debug("DelightfulLens: Treating table as data table:", element); // Optional debug log
            result = this.processTable(element, childContext);
          }
          // --- endmodify ---
          break;
        // Table internal structure elements (TR, THEAD, TBODY, TFOOT):
        // These elements do not directly generate Markdown but serve as containers.
        // Process
        case 'TR': case 'TBODY': case 'THEAD': case 'TFOOT':
          result = this.processGenericBlock(element, childContext);
          break;
        // Table cells (TD, TH):
        // Process
        case 'TD': case 'TH':
          result = this.processParagraph(element, childContext); // Process
          break;
        // --- Other common block-level/containers ---
        case 'FIGURE':    // Usually contains IMG and FIGCAPTION
        case 'DETAILS':   // Collapsible area
          result = this.processGenericBlock(element, childContext); // Process
          break;
        case 'FIGCAPTION': // imageheading
        case 'SUMMARY':    // Heading of DETAILS
          result = this.processParagraph(element, childContext); // Process
          break;

        // --- Process
        default:
          // 1. Determine
          const headingLevel = styleAnalyzer.getHeadingLevel(element);
          if (headingLevel > 0) {
            result = this.processHeading(element, childContext, headingLevel);
          }
          // 2. Check if bold is implemented via CSS style
          else if (styleAnalyzer.isBold(element)) {
            result = this.processStyledInline(element, childContext, markdownGenerator.bold);
          }
          // 3. Determine
          else if (utils.isBlockElement(element)) {
            // Process
            result = this.processGenericBlock(element, childContext);
          } else {
            // Process
            result = this.processGenericInline(element, childContext);
          }
          break;
      } // --- End of switch ---

      return result; // Process
    },

    /**
     * [Process
     * @param {Element} element - parent element。
     * @param {object} context - Process
     *                         Add `skipNode` option to skip specific child nodes.
     * @returns {ProcessResult | null} Result object containing Markdown from concatenation of all valid child nodes,
     *                                Return
     *                                `isBlock` attribute indicates whether child nodes contain block-level elements.
     */
    _processAndCombineChildren: function(element, context) {
      let combinedMarkdown = '';
      let lastChildResult = null; // Record previous valid child result
      let listItemCounter = 1; // Used for index counting of ordered list items
      let containsBlockChild = false; // Mark whether child nodes contain block-level elements

      // Traverse all child nodes (including text nodes and Element nodes)
      for (const child of element.childNodes) {
        // --- New: Skip specified nodes ---
        if (context.skipNode && child === context.skipNode) {
            continue;
        }
        // --- End new ---

        // Create specific context for list items (LI), pass index
        let nodeContext = context;
        if (child.nodeType === Node.ELEMENT_NODE && /** @type {Element} */ (child).tagName === 'LI' && context.isOrderedList) {
          nodeContext = { ...context, listItemIndex: listItemCounter };
        }

        // Process
        const childResult = this.processNode(child, nodeContext);

        // Ignore invalid or empty child results
        if (!childResult || utils.isEmptyText(childResult.markdown)) {
          continue;
        }

        // If child result is block-level, then mark parent aggregation result should also be considered block-level (affects subsequent concatenation)）
        if (childResult.isBlock) {
          containsBlockChild = true;
        }

        // --- Intelligent concatenation logic (similar to generateMarkdownFromElements)) ---
        if (combinedMarkdown === '') {
          combinedMarkdown = childResult.markdown;
        } else {
          // Previous block-level separator logic may be too loose, causing too many line breaks, especially between inline elements。
          // Optimization: Only need block-level separator when either one is block-level。
          const requiresBlockSeparator = childResult.isBlock || (lastChildResult && lastChildResult.isBlock);

          if (requiresBlockSeparator) {
            // Ensure previous markdown ends with appropriate line breaks
            // If previous one is not block-level, may only need one line break (more conservative to keep integrity)
            combinedMarkdown = combinedMarkdown.replace(/(\n{1,2})?$/, '\n\n') + childResult.markdown.replace(/^\n+/, '');
          } else {
            // Two inline elements adjacent
            const endsWithSpaceOrNL = /[\s\n]$/.test(combinedMarkdown);
            const startsWithSpaceOrNL = /^[\s\n]/.test(childResult.markdown);
            const separator = (endsWithSpaceOrNL || startsWithSpaceOrNL) ? '' : ' ';
            // Based on
            combinedMarkdown = combinedMarkdown.trimEnd() + separator + childResult.markdown.trimStart();
          }
        }
        lastChildResult = childResult; // Update previous valid child result

        // Process
        if (child.nodeType === Node.ELEMENT_NODE && /** @type {Element} */ (child).tagName === 'LI' && context.isOrderedList) {
          listItemCounter++;
        }
      } // --- End of childNodes loop ---

      // If after traversing all child nodes, no valid Markdown content was collected
      if (utils.isEmptyText(combinedMarkdown)) {
        return null; // return null indicates this element has no valid content
      }

      // Return result object containing aggregated Markdown and block-level marker
      // Optimization: If parent element itself is an inline tag (e.g., span, a) and child elements do not contain block-level, then overall should be inline
      const parentIsInline = utils.isInlineElement(element);
      return {
        markdown: combinedMarkdown,
        isBlock: containsBlockChild || !parentIsInline // Contains block child element, or when parent element is not inline element, overall considered as block
      };
    },

    /**
     * [Process
     * It will use the first heading inside the link as link text, generate Markdown heading with link，
     * Process
     * @param {Element} element - Structured link A element。
     * @param {object} context - Process
     * @returns {ProcessResult | null} Processing result or null。
     */
    processStructuredLink: function(element, context) {
        // 1. Check
        if (context.scope === 'viewport' && !context.isTopLevelElement && !utils.isElementInViewport(element)) {
            return null;
        }

        // 2. Get URL
        const rawHref = element.getAttribute('href');
        const url = utils.resolveUrl(rawHref);
        if (!url) {
            return null; // URL Invalid
        }

        // 3. Find first heading element (h1-h6) inside link
        const headingElement = element.querySelector('h1, h2, h3, h4, h5, h6');
        if (!headingElement) {
            // console.warn("DelightfulLens (StructuredLink): No heading found inside the link, cannot process as structured link.", element);
            return null; // Process
        }
        const headingLevel = parseInt(headingElement.tagName.substring(1));

        // 4. Extract heading text
        // Get
        const headingContentResult = this._processAndCombineChildren(headingElement, { ...context, isInHeading: true });
        let titleText = '';
        if (headingContentResult && !utils.isEmptyText(headingContentResult.markdown)) {
            titleText = utils.stripMarkdown(headingContentResult.markdown); // Strip Markdown
        }
        if (utils.isEmptyText(titleText)) {
            // console.warn("DelightfulLens (StructuredLink): Heading found, but its text content is empty.", headingElement);
            return null; // Process
        }

        // 5. Generate Markdown heading with link
        // format: ## [Title](url)
        const linkedHeadingMd = `${'#'.repeat(headingLevel)} [${titleText}](${url})\n\n`;

        // 6. Process
        const remainingContext = { ...context, skipNode: headingElement };
        const remainingChildrenResult = this._processAndCombineChildren(element, remainingContext);

        let remainingContentMd = '';
        if (remainingChildrenResult && !utils.isEmptyText(remainingChildrenResult.markdown)) {
            remainingContentMd = remainingChildrenResult.markdown.trimStart(); // Remove possible leading whitespace/line breaks
        }

        // 7. Aggregate final Markdown
        const finalMd = linkedHeadingMd + remainingContentMd;

        return {
            markdown: finalMd.trim(), // Final cleaning of leading/trailing whitespace
            isBlock: true, // Structured link usually represents a block-level content
            processedNode: headingElement // Process
        };
    },

    /**
     * [Check
     * Get
     * Process
     * Get
     * @param {Element} element - headingelement。
     * @param {object} context - Process
     * @param {number} level - Heading level (1-6)。
     * @returns {ProcessResult | null} Processing result or null。
     */
    processHeading: function(element, context, level) {
      // Special case: heading content is only a link
      const significantChildren = Array.from(element.childNodes).filter(n => !utils.isWhitespaceOrCommentNode(n));
      const firstSignificantChild = significantChildren.length === 1 ? significantChildren[0] : null;

      if (firstSignificantChild &&
          firstSignificantChild.nodeType === Node.ELEMENT_NODE &&
          /**@type{Element}*/(firstSignificantChild).tagName === 'A' &&
          utils.isValidLink(/**@type{HTMLAnchorElement}*/(firstSignificantChild)))
      {
          const linkElement = /**@type{HTMLAnchorElement}*/(firstSignificantChild);
          // --- Check
          const isStructured = Array.from(linkElement.children).some(child => utils.isBlockElement(child));
          let linkResult = null;
          if (isStructured) {
              // Process
              linkResult = this.processStructuredLink(linkElement, { ...context, isInHeading: true });
          } else {
              // Process
              linkResult = this.processLink(linkElement, { ...context, isInHeading: true });
          }
          // --- endmodify ---

          if (linkResult && !utils.isEmptyText(linkResult.markdown)) {
              // --- Process
              if (isStructured) {
                  return linkResult; // Return
              } else {
              // --- endmodify ---
                  // If it is a regular link, wrap with H tag
                  return {
                      markdown: markdownGenerator.heading(linkResult.markdown, level),
                      isBlock: true
                  };
              }
          }
      }

      // Process
      const childrenResult = this._processAndCombineChildren(element, { ...context, isInHeading: true });
      if (!childrenResult || utils.isEmptyText(childrenResult.markdown)) {
          return null; // No valid child content
      }

      // Strip markers from aggregated Markdown of child nodes to get pure text heading content
      const plainText = utils.stripMarkdown(childrenResult.markdown);
      if (utils.isEmptyText(plainText)) {
          return null; // Empty after stripping, also considered invalid
      }

      // Use pure text to generate Markdown heading
      return {
          markdown: markdownGenerator.heading(plainText, level),
          isBlock: true
      };
    },

    /**
     * [Check
     * Get
     * After cleaning internal excess line breaks from aggregated Markdown of child nodes, format with `markdownGenerator.paragraph`。
     * @param {Element} element - Paragraph or similar element。
     * @param {object} context - Process
     * @returns {ProcessResult | null} Processing result or null。
     */
    processParagraph: function(element, context) {
      // Process all child nodes
      const childrenResult = this._processAndCombineChildren(element, { ...context, isInParagraph: true });
      if (!childrenResult || utils.isEmptyText(childrenResult.markdown)) {
          return null; // No valid child content
      }

      // Clean excess line breaks in aggregated Markdown of child nodes (paragraphs usually do not need multiple consecutive line breaks internally)
      const cleanedContent = childrenResult.markdown
          .replace(/\n{2,}/g, '\n') // Merge multiple line breaks into one
          .trim(); // Remove leading and trailing whitespace

      if (utils.isEmptyText(cleanedContent)) {
          return null; // Empty after cleaning, considered invalid
      }

      // Use cleaned content to generate Markdown paragraph
      return {
          markdown: markdownGenerator.paragraph(cleanedContent),
          isBlock: true // Paragraph is block-level
      };
    },

    /**
     * [Check
     * 1. Check
     * 2. Check
     * 3. Get `href` attribute and parse as absolute URL。
     * 4. Determine link text：
     *    - Prefer to use alt text of unique valid image inside。
     *    - Process
     * 5. If URL and text are both valid, use `markdownGenerator.link` to format.
     * @param {Element} element - linkelement。
     * @param {object} context - Process
     * @returns {ProcessResult | null} Processing result or null。
     */
    processLink: function(element, context) {
      // 1. Check
      if (!utils.isValidLink(/** @type {HTMLAnchorElement} */(element))) {
          // console.debug("Link skipped (invalid)", element);
          return null;
      }

      // 2. Check
      //    Check
      //    Check
      if (context.scope === 'viewport' && !context.isTopLevelElement && !utils.isElementInViewport(element)) {
          // console.debug("Link skipped (outside viewport)", element);
          return null;
      }
      // --- Check

      // 3. Get and parse URL
      const rawHref = element.getAttribute('href');
      const url = utils.resolveUrl(rawHref);
      if (!url) {
          // console.debug("Link skipped (failed to resolve URL)", element);
          return null; // Parse failed or URL is blocked
      }

      // 4. Determine link textcontent
      let linkText = '';
      const directChildren = Array.from(element.children);
      // Check if only contains one direct child element, and that child element is a valid image
      const directImg = /** @type {HTMLImageElement | null} */ (
          directChildren.length === 1 &&
          directChildren[0].tagName === 'IMG' &&
          utils.isValidImage(/**@type {HTMLImageElement}*/(directChildren[0]))
          ? directChildren[0] : null
      );

      if (directImg) {
          // If it is an image link, use image alt text as link text
          linkText = utils.getImageAlt(directImg);
          // console.debug("Link text from image alt:", linkText);
      }

      // Get
      if (utils.isEmptyText(linkText)) {
          const childrenResult = this._processAndCombineChildren(element, { ...context, isInLink: true });
          if (childrenResult && !utils.isEmptyText(childrenResult.markdown)) {
              // Strip markers from aggregated Markdown of child nodes
              linkText = utils.stripMarkdown(childrenResult.markdown);
              // console.debug("Link text from children:", linkText);
          }
      }

      // Get
      if (utils.isEmptyText(linkText)) {
          // console.debug("Link skipped (no valid text content)", element);
          return null;
      }

      // 5. Generate Markdown link
      return {
          markdown: markdownGenerator.link(linkText, url),
          isBlock: false // Link is inline element
      };
    },

    /**
     * [Check
     * 1. Check
     * 2. Check
     * 3. Get
     * 4. Get
     * 5. If Alt and URL are both valid, use `markdownGenerator.image` to format.
     * @param {Element} element - imageelement。
     * @param {object} context - Process
     * @returns {ProcessResult | null} Processing result or null。
     */
    processImage: function(element, context) {
      // 1. Check
      if (!utils.isValidImage(/** @type {HTMLImageElement} */(element))) {
          // console.debug("Image skipped (invalid)", element);
          return null;
      }

      // 2. Check
      if (context.scope === 'viewport' && !context.isTopLevelElement && !utils.isElementInViewport(element)) {
          // console.debug("Image skipped (outside viewport)", element);
          return null;
      }
      // --- Check

      // 3. Get Alt text
      const alt = utils.getImageAlt(/** @type {HTMLImageElement} */(element));
      // 4. Get URL
      const url = utils.getImageUrl(/** @type {HTMLImageElement} */(element));

      // 5. Must have valid URL simultaneously (alt can be default value "Image")
      if (!url) {
          // console.debug("Image skipped (no valid URL found)", element);
          return null;
      }

      // 6. Generate Markdown image
      return {
          markdown: markdownGenerator.image(alt, url),
          isBlock: true // Image in Markdown is usually considered a block-level element (on a separate line)
      };
    },

    /**
     * [Check
     * 1. Determine list level (`context.listLevel`)。
     * 2. Process
     * 3. For each `<li>` call `processListItem`, pass updated context (increase level, set isOrdered, index)。
     * 4. Collect all valid list item Markdown results。
     * 5. Return
     * 6. Join all list item Markdown with line break and add two line breaks at the end。
     * @param {Element} element - listelement (UL or OL)。
     * @param {object} context - Process
     * @param {boolean} isOrdered - Mark whether it is an ordered list (OL)。
     * @returns {ProcessResult | null} Processing result or null。
     */
    processList: function(element, context, isOrdered) {
      const listLevel = context.listLevel || 0; // Get current nesting level, default is 0
      // Create child context, increase list level, and pass in whether it is ordered marker
      const childContext = {
          ...context,
          listLevel: listLevel + 1,
          isInList: true, // Mark current inside list
          isOrderedList: isOrdered
      };

      const listItemsResults = []; // Store valid list item results
      let itemIndex = 1; // Index counter for ordered list

      // Traverse direct child elements
      Array.from(element.children).forEach(child => {
          // Process
          if (child.tagName === 'LI') {
              // Process
              const itemResult = this.processListItem(
                  /** @type {Element} */(child),
                  { ...childContext, listItemIndex: itemIndex } // Pass current item index
              );
              // Process
              if (itemResult && !utils.isEmptyText(itemResult.markdown)) {
                  listItemsResults.push(itemResult); // Collect result
                  itemIndex++; // Increment index (even for unordered list, though not used)
              } else {
                // console.debug("List item skipped (empty or invalid)", child);
              }
          } else {
              // Encountered non-LI direct child element in UL/OL, usually invalid HTML, ignore it
              // console.warn("DelightfulLens: Non-LI element found directly inside a list, skipped:", child);
          }
      });

      // If no valid list items collected
      if (listItemsResults.length === 0) {
          // console.debug("List skipped (no valid list items)", element);
          return null;
      }

      // Join all list item Markdown with line breaks
      // Return
      const combinedListContent = listItemsResults.map(item => item.markdown).join('\n');

      // List as a whole is a block-level element, needs two line breaks at the end
      return {
          markdown: combinedListContent.replace(/(\n{2,})?$/, '\n\n'),
          isBlock: true
      };
    },

    /**
     * [Check
     * 1. Get
     * 2. Process
     *    - Get first line content.
     *    - Use `markdownGenerator.listItem` to format first line (add indent and marker).
     *    - Add correct indent to subsequent lines.
     * 3. Return `ProcessResult` containing formatted list item content.
     * @param {Element} element - List item element (LI).
     * @param {object} context - Process
     * @param {number} level - Current list item nesting level.
     * @param {boolean} isOrdered - Whether parent list is ordered.
     * @param {number} index - Current list item index in ordered list.
     * @returns {ProcessResult | null} Processing result or null。
     */
    processListItem: function(element, context, level, isOrdered, index) {
      // Process all child nodes of list item
      const childrenResult = this._processAndCombineChildren(element, { ...context, isInsideListItem: true });
      if (!childrenResult || utils.isEmptyText(childrenResult.markdown)) {
          return null; // No valid child content
      }

      // Get aggregated Markdown of child nodes and split by lines
      const contentMarkdown = childrenResult.markdown.trim(); // Trim to remove leading and trailing whitespace/line breaks
      const lines = contentMarkdown.split('\n');

      // Process first line
      const firstLineText = lines[0].trim(); // Get first line and trim
      // Use generator to format first line, add indent and list marker
      const firstLineMD = markdownGenerator.listItem(firstLineText, level, isOrdered, index);

      // Process subsequent lines (if exists)
      const subsequentLinesMD = lines.slice(1) // Start from second line
          .map(line => {
              const trimmedLine = line.trim();
              if (trimmedLine.length === 0) {
                  return ''; // Skip empty lines
              }
              // Calculate indent for subsequent lines: current level indent + list marker width
              // Ordered list marker width may vary (e.g. 1. vs 10.), unordered list fixed at 2 ('- ')
              const markerWidth = isOrdered ? String(index).length + 2 : 2; // e.g., "1. " is 3 wide, "- " is 2 wide
              const indentSpaces = ' '.repeat(level * 2) + ' '.repeat(markerWidth);
              return `${indentSpaces}${trimmedLine}`; // addindent
          })
          .filter(line => line.length > 0) // Process
          .join('\n'); // Rejoin with line breaks

      // Aggregate final list item Markdown
      const fullListItemMarkdown = firstLineMD + (subsequentLinesMD ? '\n' + subsequentLinesMD : '');

      return {
          markdown: fullListItemMarkdown,
          // List item itself can be considered block-level, because it occupies a separate line in the list and may contain nested blocks
          isBlock: true
      };
    },

    /**
     * [Check
     * Get
     * Format aggregated Markdown of child nodes with `markdownGenerator.blockquote` (add `>` prefix).
     * @param {Element} element - Blockquote element.
     * @param {object} context - Process
     * @returns {ProcessResult | null} Processing result or null。
     */
    processBlockquote: function(element, context) {
      // Process all child nodes
      const childrenResult = this._processAndCombineChildren(element, { ...context, isInBlockquote: true });
      if (!childrenResult || utils.isEmptyText(childrenResult.markdown)) {
          return null; // No valid child content
      }

      // Get cleaned child node Markdown
      const cleanedContent = childrenResult.markdown.trim();
      if (utils.isEmptyText(cleanedContent)) {
          return null; // Empty after cleaning
      }

      // Use generator to format as block quote
      return {
          markdown: markdownGenerator.blockquote(cleanedContent),
          isBlock: true // Block quote is block-level element
      };
    },

    /**
     * [Check
     * 1. Check
     * 2. Extract
     * 3. Extract
     * 4. Extract
     * 5. Use `markdownGenerator.codeBlock` to format.
     * @param {Element} element - Pre element。
     * @param {object} context - Process
     * @returns {ProcessResult | null} Processing result or null。
     */
    processCodeBlock: function(element, context) {
      // 1. Check
      if (context.scope === 'viewport' && !context.isTopLevelElement && !utils.isElementInViewport(element)) {
          // console.debug("Code block (pre) skipped (outside viewport)", element);
          return null;
      }
      // --- Check

      // 2. Determine content source (internal code or pre itself)
      const codeElement = element.querySelector('code');
      const targetElement = codeElement || element; // Prefer code

      // 3. Extract code content, preserve format
      const rawText = this.getCodeContentWithNewlines(targetElement);
      if (rawText === null || utils.isEmptyText(rawText.trim())) { // trim()Determine
          // console.debug("Code block (pre) skipped (empty content)", element);
          return null; // No valid content
      }

      // 4. Extract
      let language = '';
      const langElement = codeElement || element; // Find class from element containing content
      if (langElement.className) {
          // match 'lang-xxx' or 'language-xxx' format
          const langMatch = langElement.className.match(/(?:lang-|language-)(\S+)/);
          if (langMatch && langMatch[1]) {
              language = langMatch[1];
          }
      }

      // 5. Clean and generate Markdown code block
      const cleanedText = rawText.replace(/\r/g, '').trim(); // Remove CR characters and trim leading/trailing whitespace/line breaks
      return {
          markdown: markdownGenerator.codeBlock(cleanedText, language),
          isBlock: true // Code block is block-level element
      };
    },

    /**
     * [Check
     * 1. Check
     * 2. Get `textContent`。
     * 3. Clean text content (merge whitespace)。
     * 4. If cleaned content is valid, use `markdownGenerator.inlineCode` to format.
     * @param {Element} element - Code element。
     * @param {object} context - Process
     * @returns {ProcessResult | null} Processing result or null。
     */
    processInlineCode: function(element, context) {
      // 1. Check
      if (context.scope === 'viewport' && !context.isTopLevelElement && !utils.isElementInViewport(element)) {
          // console.debug("Inline code skipped (outside viewport)", element);
          return null;
      }
      // --- Check

      // 2. Get text content
      const content = element.textContent;
      if (utils.isEmptyText(content)) {
          // console.debug("Inline code skipped (empty content)", element);
          return null;
      }

      // 3. Clean text (replace internal consecutive whitespace with single space, and trim)
      const cleanedText = content.replace(/\s+/g, ' ').trim();
      if (utils.isEmptyText(cleanedText)) {
          // console.debug("Inline code skipped (empty after cleaning)", element);
          return null;
      }

      // 4. Generate Markdown inline code
      return {
          markdown: markdownGenerator.inlineCode(cleanedText),
          isBlock: false // Inline code is inline element
      };
    },

    /**
     * [Check
     * Get
     * After cleaning line breaks and excess whitespace from aggregated Markdown of child nodes, format with passed-in `generator` (such as `markdownGenerator.bold`)。
     * @param {Element} element - Inline style element (STRONG, EM, S, etc.)。
     * @param {object} context - Process
     * @param {function(string): string} generator - Markdown generator function for formatting content。
     * @returns {ProcessResult | null} Processing result or null。
     */
    processStyledInline: function(element, context, generator) {
      // Process all child nodes
      const childrenResult = this._processAndCombineChildren(element, context);
      if (!childrenResult || utils.isEmptyText(childrenResult.markdown)) {
          return null; // No valid child content
      }

      // Clean line breaks and excess whitespace from aggregated Markdown of child nodes
      const cleanedContent = childrenResult.markdown
          .replace(/[\n\r]+/g, ' ') // Replace line break with space
          .replace(/\s+/g, ' ')    // Merge multiple spaces into one
          .trim();                 // Remove leading and trailing whitespace

      if (utils.isEmptyText(cleanedContent)) {
          return null; // Empty after cleaning
      }

      // Format with passed-in generator
      return {
          markdown: generator(cleanedContent),
          isBlock: false // These are all inline elements
      };
    },

    /**
     * [Check
     * 1. Check
     * 2. Find all row elements (`<tr>` in `<thead>`, `<tbody>`, `<tfoot>`, or direct child)。
     * 3. Traverse each row, find all cells (`<th>`, `<td>`)。
     * 4. Get
     * 5. Build Markdown table row string (`| cell1 | cell2 |`)。
     * 6. Determine maximum column count。
     * 7. Generate header separator (`|---|---|`)。
     * 8. If actual header exists (`<thead>` or `<th>`), insert separator; else, add placeholder header and separator。
     * 9. Aggregate all rows and separator to generate final Markdown table。
     * @param {Element} element - Table element。
     * @param {object} context - Process
     * @returns {ProcessResult | null} Processing result or null。
     */
     processTable: function(element, context) {
        // 1. Check
        if (context.scope === 'viewport' && !context.isTopLevelElement && !utils.isElementInViewport(element)) {
            // console.debug("Table skipped (outside viewport)", element);
            return null;
        }
        // --- Check

        const rowsMarkdown = []; // Store Markdown string for each row
        let headerSeparator = ''; // Header separator
        let columnCount = 0;      // Maximum column count of table
        let hasActualHeader = false; // Mark whether thead or th exists

        // 2. Find all row elements (more robust selector)
        const rowElements = Array.from(element.querySelectorAll(
            ':scope > thead > tr, :scope > tbody > tr, :scope > tfoot > tr, :scope > tr'
        ));
        if (rowElements.length === 0) {
            // console.debug("Table skipped (no rows found)", element);
            return null; // No rows, invalid table
        }

        // 3. Traverse each row
        rowElements.forEach((rowElement) => {
            const cellsContent = []; // Store cleaned content of each cell in current row
            // Find all cells (th or td) under current row
            const cellElements = Array.from(rowElement.querySelectorAll(':scope > th, :scope > td'));
            if (cellElements.length === 0) {
                return; // Skip rows with no cells
            }

            // Determine whether current row is header row
            const isHeaderRow = rowElement.closest('thead') !== null || cellElements.some(cell => cell.tagName === 'TH');
            if (isHeaderRow) {
                hasActualHeader = true;
            }

            // 4. Traverse each cell of current row
            cellElements.forEach(cellElement => {
                const cellContext = { ...context, isInTableCell: true };
                // Process
                const cellResult = this.processNode(cellElement, cellContext);
                let cellContentClean = '';
                if (cellResult && !utils.isEmptyText(cellResult.markdown)) {
                    // Get cell Markdown, strip markers, escape pipe characters, remove line breaks
                    cellContentClean = utils.stripMarkdown(cellResult.markdown)
                        .replace(/\|/g, '\\|') // Escape | in content
                        .replace(/[\n\r]+/g, ' ') // Replace line break with space
                        .trim();
                }
                cellsContent.push(cellContentClean); // Add to current row content array
            }); // --- End of cell loop ---

            // 5. Build Markdown string for current row
            rowsMarkdown.push(`| ${cellsContent.join(' | ')} |`);
            // Update maximum column count
            columnCount = Math.max(columnCount, cellsContent.length);
        }); // --- End of row loop ---

        // 6. Check
        // If no rows collected, or column count is 0, or all row content is empty
        if (rowsMarkdown.length === 0 || columnCount === 0 || rowsMarkdown.every(row => utils.isEmptyText(row.replace(/\|/g,'').trim()))) {
            // console.debug("Table skipped (empty or invalid content)", element);
            return null;
        }

        // 7. Generate header separator
        // Create an array containing columnCount of '---', joined with '|'
        headerSeparator = `|${Array(columnCount).fill('---').join('|')}|`;

        // 8. Insert separator (and possible placeholder header)
        if (hasActualHeader) {
            // If actual header row exists, insert separator after first row
            // (falseAssume first row is header, which is true in most cases but not guaranteed)
            rowsMarkdown.splice(1, 0, headerSeparator);
        } else {
            // If no header row detected, add an empty header row and separator at the front
            const placeholderHeader = `|${Array(columnCount).fill('   ').join('|')}|`; // Use space as placeholder
            rowsMarkdown.unshift(placeholderHeader, headerSeparator);
        }

        // 9. Aggregate final Markdown table
        // Join all rows with line breaks, and add two line breaks at the end to indicate block end
        return {
            markdown: rowsMarkdown.join('\n') + '\n\n',
            isBlock: true // Table is block-level element
        };
      },


    /**
     * [Check
     * These elements have no specific Markdown mapping, mainly serve as content containers。
     * Get
     * @param {Element} element - Generic block-level element。
     * @param {object} context - Process
     * @returns {ProcessResult | null} Processing result or null。
     */
    processGenericBlock: function(element, context) {
      // Process all child nodes
      const childrenResult = this._processAndCombineChildren(element, context);
      if (!childrenResult || utils.isEmptyText(childrenResult.markdown)) {
          return null; // No valid child content
      }
      // Return
      // trim() Return
      return {
          markdown: childrenResult.markdown.trim(),
          isBlock: childrenResult.isBlock // Inherit block-level state from child nodes
      };
    },

    /**
     * [Check
     * These elements are usually used to wrap text fragments and apply certain styles but have no specific Markdown mapping。
     * Get
     * Return
     * @param {Element} element - Generic inline element。
     * @param {object} context - Process
     * @returns {ProcessResult | null} Processing result or null。
     */
    processGenericInline: function(element, context) {
      // Check if it is CSS bold
      if (styleAnalyzer.isBold(element)) {
        return this.processStyledInline(element, context, markdownGenerator.bold);
      }

      // Check if it is CSS italic
      if (styleAnalyzer.isItalic(element)) {
        return this.processStyledInline(element, context, markdownGenerator.italic);
      }

      // Process all child nodes
      const childrenResult = this._processAndCombineChildren(element, context);
      if (!childrenResult || utils.isEmptyText(childrenResult.markdown)) {
          return null; // No valid child content
      }

      // Clean line breaks and excess whitespace from aggregated Markdown of child nodes
      const cleanedContent = childrenResult.markdown
          .replace(/[\n\r]+/g, ' ') // Replace line break with space
          .replace(/\s+/g, ' ')    // Merge multiple spaces
          .trim();

      if (utils.isEmptyText(cleanedContent)) {
          return null; // Empty after cleaning
      }

      // Return
      return {
          markdown: cleanedContent,
          isBlock: false
      };
    },

    /**
     * [Check
     * 1. Check
     * 2. use `markdownGenerator.horizontalRule` Generate Markdown horizontal line。
     * @param {Element} element - HR element。
     * @param {object} context - Process
     * @returns {ProcessResult | null} Processing result or null。
     */
    processHorizontalRule: function(element, context) {
       // 1. Check
       if (context.scope === 'viewport' && !context.isTopLevelElement && !utils.isElementInViewport(element)) {
           // console.debug("Horizontal rule skipped (outside viewport)", element);
           return null;
       }
       // --- Check

       // 2. Generate Markdown horizontal line
       return {
           markdown: markdownGenerator.horizontalRule(),
           isBlock: true // Horizontal line is block-level element
       };
    },

    /**
     * [Extract
     * Traverse all child nodes of target：
     * - If it is text node, directly append its content。
     * - If it is `<br>` element, append a line break character `\n`。
     * - Process
     * @param {Element} element - Target element (usually PRE or CODE)。
     * @returns {string | null} Return
     */
    getCodeContentWithNewlines: function(element) {
        if (!element) {
            return null;
        }
        let content = '';
        const childNodes = element.childNodes;

        for (let i = 0; i < childNodes.length; i++) {
            const node = childNodes[i];
            if (node.nodeType === Node.TEXT_NODE) {
                // Directly add text node content
                content += node.textContent;
            } else if (node.nodeType === Node.ELEMENT_NODE) {
                const elementNode = /** @type {Element} */ (node);
                if (elementNode.tagName.toUpperCase() === 'BR') {
                    // BR Convert
                    content += '\n';
                } else {
                    // Process
                    const nestedContent = this.getCodeContentWithNewlines(elementNode);
                    if (nestedContent !== null) {
                        content += nestedContent;
                    }
                }
            }
            // Ignore comment node and other types
        }
        return content;
    },

    /**
     * [Determine
     * Use simple and clear heuristic rules。
     * @param {HTMLTableElement} tableElement - tableelement。
     * @returns {boolean} Return
     */
    isLayoutTable: function(tableElement) {
        if (!tableElement || tableElement.tagName !== 'TABLE') {
            return false; // Not a table element
        }

        // Check
        const role = tableElement.getAttribute('role');
        if (role === 'presentation' || role === 'none') {
            // console.debug("Table identified as layout by role:", tableElement);
            return true;
        }

        // Check
        const borderAttribute = tableElement.getAttribute('border');
        const style = utils.getStyle(tableElement);
        const hasZeroBorder = borderAttribute === '0' ||
                             (style && (style.borderStyle === 'none' || style.borderStyle === 'hidden' || parseFloat(style.borderWidth) === 0));
        if (hasZeroBorder) {
             // Pure no-border could be layout table or no-border data table, this rule is not strong enough，Comment out for now or lower weight
             // console.debug("Table identified as potentially layout by zero border:", tableElement);
             // return true; // Determine
        }

        // Check
        // This is a strong indicator, data table usually has at least one <th>
        const hasThElement = tableElement.querySelector('th') !== null;
        if (!hasThElement) {
            // console.debug("Table identified as potentially layout due to lack of <th>:", tableElement);
            return true; // No <th>, very likely a layout table
        }

        // If the above explicit rules all do not match, default as data table
        return false;
    },

  }; // --- End of contentExtractor ---

  // ==========================================================================
  // § 6. Content discovery and filtering (Content Finder)
  //    Responsible for finding and filtering top-level element blocks that may contain main content starting from specified root element。
  //    Process
  // ==========================================================================
  const contentFinder = {
      /**
       * Return
       * Use `TreeWalker` for efficient DOM traversal。
       * Determine
       * Process
       * @param {Element} [rootElement=document.body] - Root element to start finding from。
       * @param {'all' | 'viewport'} [scope='viewport'] - Filtering scope, affects `isEffectivelyVisible`。
       * @returns {Element[]} An array of relevant top-level content blocks sorted by document order。
       */
      findRelevantElements: function(rootElement = document.body, scope = "viewport") {
          const candidateElements = []; // Store initially screened candidate elements

          // Use TreeWalker for efficient traversal of DOM subtree
          // NodeFilter.SHOW_ELEMENT: Only access Element nodes
          const walker = document.createTreeWalker(
              rootElement,
              NodeFilter.SHOW_ELEMENT,
              { // Custom node filter
                  acceptNode: (node) => {
                      const element = /** @type {Element} */ (node);

                      // 1. Check
                      if (IGNORED_TAGS.has(element.tagName.toUpperCase())) {
                          // If it is an ignored tag, reject this node and all its descendants
                          return NodeFilter.FILTER_REJECT;
                      }

                      // 2. Check
                      //    Process
                      if (!utils.isEffectivelyVisible(element, scope)) {
                          // Determine
                          return NodeFilter.FILTER_SKIP; // or FILTER_REJECT, depending on whether you want to completely exclude
                      }

                      // 3. Check if it is a possible content container or contains direct content
                      if (utils.isPotentiallyContentElement(element, scope)) {
                          // If meets potential content condition, accept this node
                          // TreeWalker will continue to access descendants of this node
                          return NodeFilter.FILTER_ACCEPT;
                      }

                      // 4. Other cases (not ignored, visible, but not potential content element)
                      //    Check
                      //    For example, a <div> with no direct content, its child nodes might be <p>
                      return NodeFilter.FILTER_SKIP;
                  }
              }
              // false // (Optional parameter, whether entity extension reference, usually false
          );

          // Traverse TreeWalker and collect all accepted (FILTER_ACCEPT) nodes
          let currentNode;
          while (currentNode = walker.nextNode()) {
              candidateElements.push(/** @type {Element} */ (currentNode));
          }

          // --- Process
          const relevantElements = []; // Final filtered top-level relevant elements
          candidateElements.forEach(candidate => {
              // Check if current candidate element is contained by other elements in `relevantElements`
              const isContained = relevantElements.some(selected =>
                  selected !== candidate && selected.contains(candidate)
              );

              // If current candidate element is not contained
              if (!isContained) {
                  // Check
                  const childrenToRemove = relevantElements.filter(selected =>
                      selected !== candidate && candidate.contains(selected)
                  );
                  childrenToRemove.forEach(child => {
                      const index = relevantElements.indexOf(child);
                      if (index > -1) {
                          relevantElements.splice(index, 1); // Remove contained child element from result
                      }
                  });
                  // Add current candidate element (as a more outer element) to result
                  relevantElements.push(candidate);
              }
              // If current candidate element is already contained, ignore it
          });

          // Process
          relevantElements.sort((a, b) => {
              const position = a.compareDocumentPosition(b);
              if (position & Node.DOCUMENT_POSITION_FOLLOWING) {
                  return -1; // a is before b
              } else if (position & Node.DOCUMENT_POSITION_PRECEDING) {
                  return 1;  // a is after b
              } else {
                  return 0;  // Same node or cannot compare
              }
          });

          // console.debug(`DelightfulLens (contentFinder): Found ${candidateElements.length} candidates, filtered to ${relevantElements.length} relevant top-level elements for scope '${scope}'.`, relevantElements); // can be enabled for debugging

          return relevantElements; // Return final filtered and sorted top-level element array
      },
  }; // --- End of contentFinder ---

  // ==========================================================================
  // § 7. Convert
  // ==========================================================================
  /**
   * Convert
   * @param {'all' | 'viewport'} [scope='viewport'] - Extract
   *   - 'all': Extract all content satisfying basic visibility rules (CSS visible, has dimension, non-ignored tag).
   *   - 'viewport': (Extract
   * @returns {string} Convert
   *                   Return
   */
  function readAsMarkdown(scope = "viewport") {
    // Normalize scope parameter, ensure it is all or viewport'
    scope = (scope === "all") ? "all" : "viewport";
    // Usually start finding content from document.body
    const rootElement = document.body;
    let elementsToProcess = []; // Process

    // console.log(`DelightfulLens: Starting Markdown conversion with scope "${scope}".`); // can be enabled for debugging

    try {
      // 1. Use contentFinder to find top-level relevant elements
      //    Based on
      elementsToProcess = contentFinder.findRelevantElements(rootElement, scope);
      // console.log(`DelightfulLens: Found ${elementsToProcess.length} top-level element(s) for processing.`); // can be enabled for debugging

      // 2. Use contentExtractor to generate Markdown starting from found top-level elements
      //    This step applies the detailed "minimum unit filtering principle""。
      const markdownResult = contentExtractor.generateMarkdownFromElements(
          elementsToProcess,
          { scope: scope } // Pass scope to extractor global context
      );
      // console.log("DelightfulLens: Markdown generation complete."); // can be enabled for debugging

      // Return final generated Markdown result
      return markdownResult;

    } catch (e) {
      // Capture unexpected top-level errors not caught in main flow
      console.error("DelightfulLens: Critical error during Markdown conversion process:", e);
      // Return a comment containing error information，Easy for caller to understand the issue
      return `/* DelightfulLens Error: Conversion failed unexpectedly. ${e.message || e} */`;
    }
  }

  // --- Export interface ---
  // Mount DelightfulLens object to global window object so it can be called by external scripts.
  // For example: `let markdown = window.DelightfulLens.readAsMarkdown('viewport');`
  window.DelightfulLens = {
    readAsMarkdown: readAsMarkdown,
    // If needed, can expose other public methods or info on this object
    getVersion: function() { return '1.5.1'; } // Get
  };

  // Output a message to console indicating script has been successfully loaded and initialized
  console.log("DelightfulLens v1.5.1 initialized successfully.");

})(); // Immediately invoke function (IIFE) end
