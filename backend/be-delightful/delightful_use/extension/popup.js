// popup.js

document.addEventListener('DOMContentLoaded', () => {
  const extractBtn = document.getElementById('extract-btn');
  const importBtn = document.getElementById('import-btn');
  const importFileLabel = document.querySelector('.file-label');
  const importFileInput = document.getElementById('import-file');
  const importArea = document.getElementById('import-area');
  const statusArea = document.getElementById('status-area');
  const progressBarContainer = document.querySelector('.progress-bar-container');
  const progressBar = document.querySelector('.progress-bar');
  const extractLocalStorageCheckbox = document.getElementById('extract-localStorage');
  const extractCookiesCheckbox = document.getElementById('extract-cookies');

  // --- Status and Progress --- //

  function showStatus(message, type = 'success') { // type: 'success', 'error', 'warning'
    statusArea.textContent = message;
    statusArea.className = type; // Reset and apply class
    statusArea.style.display = 'block';
    setTimeout(() => {
      statusArea.style.display = 'none';
      statusArea.className = '';
    }, 5000);
  }

  function showProgress(show, percent = 0) {
    if (show) {
      progressBarContainer.style.display = 'block';
      progressBar.style.width = `${percent}%`;
    } else {
      progressBarContainer.style.display = 'none';
      progressBar.style.width = `0%`;
    }
  }

  function disableButtons(disabled = true) {
      extractBtn.disabled = disabled;
      importBtn.disabled = disabled;
      importFileLabel.style.pointerEvents = disabled ? 'none' : 'auto';
      importFileLabel.style.opacity = disabled ? '0.6' : '1';
  }

  // --- Helper: Get Active Tab --- //
  async function getActiveTab() {
    const tabs = await chrome.tabs.query({ active: true, currentWindow: true });
    if (!tabs || tabs.length === 0) {
      throw new Error("Unable to get current active tab.");
    }
    return tabs[0];
  }

  // --- Helper: Inject Content Script and Send Message --- //
  async function sendMessageToContentScript(tabId, message) {
    try {
      const response = await chrome.tabs.sendMessage(tabId, message);
      return response;
    } catch (error) {
       // Check if the error is because the content script isn't injected yet
       if (error.message.includes("Could not establish connection") || error.message.includes("Receiving end does not exist")) {
           console.log("Content script not ready or injected, attempting injection...");
           try {
                await chrome.scripting.executeScript({
                    target: { tabId: tabId },
                    files: ['content_script.js']
                });
                // Wait a very short moment for the script to initialize
                await new Promise(resolve => setTimeout(resolve, 100));
                // Retry sending the message
                const retryResponse = await chrome.tabs.sendMessage(tabId, message);
                console.log("Message sent successfully after injection.");
                return retryResponse;
           } catch (injectionError) {
                console.error("Error injecting content script:", injectionError);
                throw new Error(`Unable to inject or connect to content script: ${injectionError.message}`);
           }
       } else {
          console.error("Error sending message to content script:", error);
          throw new Error(`Failed to communicate with content script: ${error.message}`);
       }
    }
  }

  // --- Helper: SameSite Mapping --- //
  const SameSiteStatus = {
      NO_RESTRICTION: 'no_restriction',
      LAX: 'lax',
      STRICT: 'strict',
      UNSPECIFIED: 'unspecified' // Though get returns no_restriction for None usually
  };

  const PlaywrightSameSite = {
      NONE: 'None',
      LAX: 'Lax',
      STRICT: 'Strict'
  };

  function mapChromeSameSiteToPlaywright(chromeStatus) {
    switch(chromeStatus) {
      case SameSiteStatus.STRICT:
        return PlaywrightSameSite.STRICT;
      case SameSiteStatus.LAX:
        return PlaywrightSameSite.LAX;
      case SameSiteStatus.NO_RESTRICTION:
      case SameSiteStatus.UNSPECIFIED: // Treat unspecified as None
      default:
        return PlaywrightSameSite.NONE;
    }
  }

  function mapPlaywrightSameSiteToChromeSet(playwrightStatus) {
    switch(playwrightStatus) {
      case PlaywrightSameSite.STRICT:
        return SameSiteStatus.STRICT;
      case PlaywrightSameSite.LAX:
        return SameSiteStatus.LAX;
      case PlaywrightSameSite.NONE:
      default: // Treat unknown as no_restriction
        return SameSiteStatus.NO_RESTRICTION;
        // Note: chrome.cookies.set technically accepts 'unspecified' but defaults to no_restriction behavior
    }
  }

  // --- Extraction Logic --- //

  async function handleExtract() {
    disableButtons(true);
    showProgress(true, 0);
    showStatus("Extracting data...", 'warning');

    try {
      const activeTab = await getActiveTab();
      const tabId = activeTab.id;
      const tabUrl = activeTab.url;

      if (!tabUrl || !tabId) {
        throw new Error("Unable to get valid tab information.");
      }

      // Ensure URL is http/https for cookie/scripting access
      if (!tabUrl.startsWith('http://') && !tabUrl.startsWith('https://')) {
          throw new Error("This extension can only run on HTTP or HTTPS pages.");
      }

      let localStorageData = {};
      let cookieItems = [];

      // 1. Get localStorage (if checked)
      if (extractLocalStorageCheckbox.checked) {
        showProgress(true, 10);
        console.log("Requesting localStorage from content script...");
        const response = await sendMessageToContentScript(tabId, { action: "getLocalStorage" });
        console.log("Received localStorage response:", response);
        if (response && response.success) {
          // Format for storage_state
          localStorageData = Object.entries(response.data || {}).map(([name, value]) => ({ name, value }));
        } else {
          console.warn("Failed to get localStorage from content script:", response ? response.error : 'Unknown error');
          // Proceed without localStorage, maybe show warning later
        }
      } else {
          localStorageData = []; // Ensure it's an empty array if not checked
      }

      showProgress(true, 40);

      // 2. Get Cookies (if checked)
      if (extractCookiesCheckbox.checked) {
         console.log(`Requesting cookies for URL: ${tabUrl}`);
         try {
            // chrome.cookies.getAll requires a URL or domain
            const cookies = await chrome.cookies.getAll({ url: tabUrl });
            console.log(`Got ${cookies.length} cookies.`);
            // Map to storage_state format
            cookieItems = cookies.map(cookie => ({
              name: cookie.name,
              value: cookie.value,
              domain: cookie.domain,
              path: cookie.path,
              expires: cookie.expirationDate ? cookie.expirationDate : -1, // Convert to seconds since epoch or -1
              httpOnly: cookie.httpOnly,
              secure: cookie.secure,
              sameSite: mapChromeSameSiteToPlaywright(cookie.sameSite)
            }));
         } catch (cookieError) {
             console.error("Error getting Cookies:", cookieError);
             throw new Error(`Failed to get Cookies:  ${cookieError.message}`);
         }
      } else {
          cookieItems = []; // Ensure it's an empty array if not checked
      }

      showProgress(true, 80);

      // 3. Combine data
      const browserData = {
        cookies: cookieItems,
        origins: [
          {
            origin: new URL(tabUrl).origin,
            localStorage: localStorageData
          }
        ]
      };

      // 4. Convert to JSON and Download
      const dataStr = JSON.stringify(browserData, null, 2);
      const blob = new Blob([dataStr], { type: 'application/json;charset=utf-8' });
      const downloadUrl = URL.createObjectURL(blob);

      const safeHostname = new URL(tabUrl).hostname.replace(/[^a-z0-9.-]/gi, '_');
      const timestamp = new Date().toISOString().slice(0, 19).replace(/[:T]/g, '-');
      const filename = `storage_state-${safeHostname}-${timestamp}.json`;

      console.log(`Triggering download for: ${filename}`);
      try {
          await chrome.downloads.download({
            url: downloadUrl,
            filename: filename,
            saveAs: true // Prompt user where to save
          });
          // Note: We don't revokeObjectURL here immediately,
          // as the download might take time. Browser usually handles it.
          showProgress(true, 100);
          showStatus(`Successfully extracted ${localStorageData.length} localStorage items and ${cookieItems.length} cookies. File download started.`, 'success');

      } catch (downloadError) {
          console.error("Error downloading file:", downloadError);
          showStatus(`Extraction successful, but file download failed: ${downloadError.message}`, 'error');
          // Clean up blob URL if download fails
          URL.revokeObjectURL(downloadUrl);
      }

    } catch (error) {
      console.error("Error in extraction process:", error);
      showStatus(`Extraction failed: ${error.message}`, 'error');
      showProgress(false);
    } finally {
      disableButtons(false);
       // Ensure progress bar hides even on error, after a short delay
      setTimeout(() => showProgress(false), 500);
    }
  }

  // --- Import Logic (Placeholder) --- //

  async function handleImport(dataStr) {
     disableButtons(true);
     showProgress(true, 0);
     showStatus("Importing data...", 'warning');
     console.log("Import requested...");

     if (!dataStr) {
        showStatus("No data to import.", 'error');
        disableButtons(false);
        showProgress(false);
        return;
     }

     try {
         const browserData = JSON.parse(dataStr);
         const activeTab = await getActiveTab();
         const tabId = activeTab.id;
         const tabUrl = activeTab.url;
         const currentOrigin = new URL(tabUrl).origin;

         if (!tabUrl || !tabId || (!tabUrl.startsWith('http://') && !tabUrl.startsWith('https://'))) {
             throw new Error("Cannot import data on current tab (requires HTTP/HTTPS page).");
         }

         let totalOperations = 0;
         let completedOperations = 0;
         const errors = [];
         const warnings = [];

         // 1. Import LocalStorage
         let localStorageToImport = [];
         if (browserData.origins && Array.isArray(browserData.origins)) {
            const originData = browserData.origins.find(o => o.origin === currentOrigin);
            if (originData && originData.localStorage && Array.isArray(originData.localStorage)) {
                localStorageToImport = originData.localStorage;
                totalOperations += localStorageToImport.length;
            } else {
                warnings.push(`No localStorage data matching current origin ${currentOrigin} found for import.`);
            }
         } else {
             warnings.push("Data format error: missing origins array.");
         }

         if (localStorageToImport.length > 0) {
            console.log(`Sending ${localStorageToImport.length} localStorage items to content script...`);
            const response = await sendMessageToContentScript(tabId, { action: "setLocalStorage", data: localStorageToImport });
            console.log("setLocalStorage response:", response);
            if (response && response.success) {
                completedOperations += response.count || 0;
                showProgress(true, (completedOperations / totalOperations) * 100 * 0.5); // LS is 50% of progress
            } else {
                errors.push(...(response.errors || ['Unknown error setting localStorage.']));
                completedOperations += response.count || 0; // Count successful ones even if some failed
            }
            if (response && response.errors && response.errors.length > 0) {
                warnings.push(...response.errors.map(e => `LocalStorage setting failed: ${e}`));
            }
         }

         // 2. Import Cookies
         let cookiesToImport = [];
         if (browserData.cookies && Array.isArray(browserData.cookies)) {
             cookiesToImport = browserData.cookies;
             totalOperations += cookiesToImport.length;
         } else {
             warnings.push("Data format error: missing cookies array.");
         }

         for (const cookie of cookiesToImport) {
            if (typeof cookie !== 'object' || cookie === null || !cookie.name) {
                warnings.push(`Skipping invalid Cookie object: ${JSON.stringify(cookie)}`);
                completedOperations++; // Count as processed
                continue;
            }

            const cookieDetails = {
                url: tabUrl, // Crucial: Cookie URL must match the domain/path
                name: cookie.name,
                value: cookie.value || "", // Ensure value is a string
                domain: cookie.domain,
                path: cookie.path,
                secure: cookie.secure,
                httpOnly: cookie.httpOnly,
                // Handle expirationDate: needs to be epoch seconds
                expirationDate: (cookie.expires && cookie.expires > 0) ? cookie.expires : undefined,
                // Apply mapping here for import before setting
                sameSite: mapPlaywrightSameSiteToChromeSet(cookie.sameSite)
            };

            // Clean up details: remove undefined keys which chrome.cookies.set doesn't like
            Object.keys(cookieDetails).forEach(key => {
                if (cookieDetails[key] === undefined || cookieDetails[key] === null) {
                    delete cookieDetails[key];
                }
            });
            // Ensure URL is set for context
            if (!cookieDetails.url) {
                cookieDetails.url = tabUrl;
            }

            // Attempt to remove existing cookie first to ensure overwrite
            try {
                await chrome.cookies.remove({ url: cookieDetails.url, name: cookieDetails.name });
            } catch (removeError) {
                // Ignore error if cookie didn't exist
                if (!removeError.message.includes("No cookie found")) {
                    console.warn(`Error removing old cookie '${cookieDetails.name}' (may not exist):`, removeError);
                }
            }

            // Set the new cookie
            try {
                console.log("Setting cookie:", cookieDetails);
                await chrome.cookies.set(cookieDetails);
            } catch (setCookieError) {
                console.error(`Error setting cookie '${cookieDetails.name}' error:`, setCookieError, "Details:", cookieDetails);
                errors.push(`Failed to set Cookie '${cookieDetails.name}' error: ${setCookieError.message}`);
            }
            completedOperations++;
            showProgress(true, 50 + (completedOperations / totalOperations) * 100 * 0.5); // Cookies are second 50%
         }

         // 3. Final Status
         showProgress(true, 100);
         let finalMessage = `Import complete.`;
         let finalType = 'success';

         if (warnings.length > 0) {
             finalMessage += ` (${warnings.length} warnings)`;
             finalType = 'warning';
             console.warn("Import warnings:", warnings);
         }
         if (errors.length > 0) {
             finalMessage = `Encountered ${errors.length} errors during import. Check console.`;
             finalType = 'error';
             console.error("Import errors:", errors);
         }
         showStatus(finalMessage, finalType);

     } catch (error) {
         console.error("Error in import process:", error);
         showStatus(`Import failed: ${error.message}`, 'error');
         showProgress(false);
         if (error instanceof SyntaxError) {
             showStatus(`Import failed: Invalid JSON data.`, 'error');
         }
     } finally {
         disableButtons(false);
         // Ensure progress bar hides even on error, after a short delay
         setTimeout(() => showProgress(false), 500);
     }

  }

  // --- Event Listeners --- //

  if (extractBtn) {
    extractBtn.addEventListener('click', handleExtract);
  }

  if (importBtn) {
    importBtn.addEventListener('click', () => {
        const dataStr = importArea.value;
        handleImport(dataStr);
    });
  }

  // Trigger hidden file input click when label is clicked
  if (importFileLabel && importFileInput) {
    importFileLabel.addEventListener('click', () => {
      importFileInput.click();
    });

    importFileInput.addEventListener('change', (event) => {
      const file = event.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = (e) => {
          importArea.value = e.target.result; // Load file content into textarea
          showStatus('File loaded into text box. Click \"Import Data\" to continue', 'success');
        };
        reader.onerror = (e) => {
          console.error("File read error:", e);
          showStatus(`Failed to read file: ${e.target.error}`, 'error');
        };
        reader.readAsText(file);
      }
      // Reset file input so the same file can be selected again
      event.target.value = null;
    });
  }
});
