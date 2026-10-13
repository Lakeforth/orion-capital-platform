/**
 * Google Translate Integration
 * This file handles all the functionality related to the Google Translate integration.
 */

// Set up translation cookies before Google Translate fully loads
(function initTranslationCookies() {
    // Read saved language from localStorage
    var savedLang = localStorage.getItem('selectedLanguage');
    if (savedLang && savedLang !== 'en') {
        // Set a session cookie (will be overwritten by Google Translate later)
        document.cookie = "googtrans=/en/" + savedLang + "; path=/;";

        // Also set domain-specific cookie for broader compatibility
        var hostnameParts = window.location.hostname.split('.');
        if (hostnameParts.length > 1) {
            var domain = hostnameParts.slice(-2).join('.');
            document.cookie = "googtrans=/en/" + savedLang + "; path=/; domain=." + domain + ";";
        }

        // For localhost or development environments
        document.cookie = "googtrans=/en/" + savedLang + "; path=/; domain=" + window.location.hostname + ";";
    }
})();

/**
 * Callback for Google Translate to initialize the translation element
 */
function googleTranslateElementInit2() {
    new google.translate.TranslateElement({
        pageLanguage: 'en',
        autoDisplay: false,
        includedLanguages: 'ar,de,en,es,fr,hi,it,ja,ko,pt,ru,zh-CN',
    }, 'google_translate_element2');
    
    // Prevent Google Translate from interfering with React
    setTimeout(preventReactDOMConflicts, 1000);
}

/**
 * Prevents Google Translate from causing DOM conflicts with React
 */
function preventReactDOMConflicts() {
    // Override removeChild to handle Google Translate conflicts
    const originalRemoveChild = Node.prototype.removeChild;
    Node.prototype.removeChild = function(child) {
        try {
            // If the child is not found, Google Translate might have moved it
            if (child && child.parentNode !== this) {
                if (console) console.warn('DOM manipulation warning: removeChild called on node that is not a child of this parent. Likely Google Translate interference.');
                return child;
            }
            return originalRemoveChild.call(this, child);
        } catch (error) {
            if (console) console.warn('DOM manipulation error prevented (Google Translate conflict):', error);
            return child;
        }
    };

    // Override insertBefore for similar protection
    const originalInsertBefore = Node.prototype.insertBefore;
    Node.prototype.insertBefore = function(newNode, referenceNode) {
        try {
            // Reference node must be a child of this parent
            if (referenceNode && referenceNode.parentNode !== this) {
                if (console) console.warn('DOM manipulation warning: insertBefore called with reference node that is not a child of this parent. Falling back to appendChild.');
                return this.appendChild(newNode);
            }
            return originalInsertBefore.call(this, newNode, referenceNode);
        } catch (error) {
            if (console) console.warn('DOM insertion error prevented (Google Translate conflict):', error);
            // Try to append instead if insertBefore fails
            try {
                return this.appendChild(newNode);
            } catch (secondError) {
                return newNode;
            }
        }
    };
}

/**
 * Changes the current translation to the specified language pair
 * @param {string} lang_pair - Language pair in format "source|target" (e.g. "en|fr")
 */
function doGTranslate(lang_pair) {
    if (lang_pair.value) lang_pair = lang_pair.value;

    // Handle English case - reset all translations
    if (lang_pair == 'en|en') {
        // Clear translations and cookies for English
        var cookieName = 'googtrans';
        document.cookie = cookieName + "=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
        document.cookie = cookieName + "=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; domain=" + document.domain + ";";

        // Clear from root domain if applicable
        var hostnameParts = window.location.hostname.split('.');
        if (hostnameParts.length > 1) {
            var domain = hostnameParts.slice(-2).join('.');
            document.cookie = cookieName + "=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; domain=." + domain + ";";
        }

        localStorage.setItem('selectedLanguage', 'en');
        
        // Use React-safe reload method
        safeReloadForTranslation();
        return;
    }

    // Return if already in the target language
    if (GTranslateGetCurrentLang() == lang_pair.split('|')[1]) return;

    // Store the selection in localStorage
    const targetLang = lang_pair.split('|')[1];
    localStorage.setItem('selectedLanguage', targetLang);

    try {
        // Primary method: Use Google's select element
        var teCombo = document.querySelector('.goog-te-combo');
        if (teCombo) {
            teCombo.value = targetLang;
            if (document.createEvent) {
                var event = document.createEvent('HTMLEvents');
                event.initEvent('change', true, true);
                teCombo.dispatchEvent(event);
            } else {
                teCombo.fireEvent('onchange');
            }
        } else {
            // Fallback to direct cookie manipulation with multiple domain variations
            var now = new Date();
            var expTime = now.getTime() + 31536000000; // 1 year
            now.setTime(expTime);

            // Set path cookies
            document.cookie = "googtrans=/en/" + targetLang + "; expires=" + now.toGMTString() + "; path=/;";

            // Set domain specific cookies (important for cross-subdomain support)
            var hostnameParts = window.location.hostname.split('.');
            if (hostnameParts.length > 1) {
                var domain = hostnameParts.slice(-2).join('.');
                document.cookie = "googtrans=/en/" + targetLang + "; expires=" + now.toGMTString() + "; path=/; domain=." + domain + ";";
            }

            // For development on localhost
            document.cookie = "googtrans=/en/" + targetLang + "; expires=" + now.toGMTString() + "; path=/; domain=" + window.location.hostname + ";";

            // Use React-safe reload method
            safeReloadForTranslation();
        }
    } catch (error) {
        console.error("Translation error:", error);
        // Last resort fallback
        document.cookie = "googtrans=/en/" + targetLang;
        safeReloadForTranslation();
    }
}

/**
 * Safely reloads the page in a way that doesn't cause React DOM conflicts
 */
function safeReloadForTranslation() {
    // Clean up any React-related timers or intervals before reload
    if (window.React && window.React.__SECRET_INTERNALS_DO_NOT_USE_OR_YOU_WILL_BE_FIRED) {
        try {
            // Clear any pending React updates
            const scheduler = window.React.__SECRET_INTERNALS_DO_NOT_USE_OR_YOU_WILL_BE_FIRED.Scheduler;
            if (scheduler && scheduler.unstable_cancelCallback) {
                // This is a more graceful way but may not always be available
            }
        } catch (e) {
            // Ignore errors, this is just cleanup
        }
    }
    
    // Small delay to allow React to clean up
    setTimeout(() => {
        window.location.reload();
    }, 100);
}

/**
 * Gets the current language from the googtrans cookie
 * @returns {string|null} The current target language or null if not set
 */
function GTranslateGetCurrentLang() {
    var keyValue = document.cookie.match('(^|;) ?googtrans=([^;]*)(;|$)');
    return keyValue ? keyValue[2].split('/')[2] : null;
}

// Apply saved language on page load with a retry mechanism
window.addEventListener('load', function() {
    // Wait for React to finish initial render before applying translations
    setTimeout(function() {
        const savedLang = localStorage.getItem('selectedLanguage');
        if (savedLang && savedLang !== 'en') {
            // First check if translation is already active
            const currentLang = GTranslateGetCurrentLang();

            // If not active or not matching saved preference, apply it
            if (currentLang !== savedLang) {
                // Wait for translation element to be fully initialized
                setTimeout(function() {
                    doGTranslate('en|' + savedLang);

                    // Double-check after a delay and retry if needed
                    setTimeout(function() {
                        const checkLang = GTranslateGetCurrentLang();
                        if (checkLang !== savedLang) {
                            console.log("Translation retry needed");
                            doGTranslate('en|' + savedLang);
                        }
                    }, 1500);
                }, 1000);
            }
        }
    }, 500); // Give React time to initialize
});

// Make functions globally available
window.doGTranslate = doGTranslate;
window.GTranslateGetCurrentLang = GTranslateGetCurrentLang;
window.googleTranslateElementInit2 = googleTranslateElementInit2;
