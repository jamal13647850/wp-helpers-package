/*
Sayyed Jamal Ghasemi — Full-Stack Developer  
📧 info@jamalghasemi.com  
🔗 LinkedIn: https://www.linkedin.com/in/jamal1364/  
📸 Instagram: https://www.instagram.com/jamal13647850  
💬 Telegram: https://t.me/jamal13647850  
🌐 https://jamalghasemi.com  
*/

/**
 * Overlay Mobile Menu Toggle - Alpine.js Fallback Handler
 *
 * Provides vanilla JavaScript fallback functionality for the overlay mobile menu
 * system when Alpine.js is not available or fails to load. This ensures graceful
 * degradation and maintains full menu functionality across all environments.
 *
 * Functionality Overview:
 * - Detects Alpine.js availability and only activates when needed
 * - Manages toggle button and menu overlay states manually
 * - Implements click-outside-to-close behavior
 * - Provides keyboard navigation support (Escape key)
 * - Maintains accessibility attributes (aria-expanded)
 * - Synchronizes CSS classes between button and menu elements
 *
 * Technical Implementation:
 * - Uses IIFE (Immediately Invoked Function Expression) for encapsulation
 * - DOM ready detection for safe initialization
 * - Event delegation for efficient memory usage
 * - State management through closure variables
 * - Cross-browser compatible event handling
 *
 * Compatibility:
 * - Works with all modern browsers (ES5+ compatible)
 * - Gracefully handles missing DOM elements
 * - No external dependencies required
 * - Compatible with WordPress environments
 *
 * @fileoverview Alpine.js fallback for overlay mobile menu functionality
 * @version 1.0.0
 * @author Sayyed Jamal Ghasemi
 * @since 1.0.0
 */

(function () {
    'use strict';

    /**
     * Early exit if Alpine.js is detected and available
     * 
     * Alpine.js provides superior reactive state management, so this fallback
     * only activates when Alpine.js is unavailable. This prevents conflicts
     * and ensures optimal performance when the preferred framework is present.
     */
    if (window.Alpine) {
        return;
    }

    /**
     * Cross-browser DOM ready detection utility
     *
     * Ensures that DOM manipulation code only runs after the document structure
     * is fully loaded, preventing errors from accessing non-existent elements.
     * Handles both already-loaded documents and documents still loading.
     *
     * @param {Function} fn - Callback function to execute when DOM is ready
     * 
     * @example
     * ready(function() {
     *     // DOM manipulation code here
     * });
     */
    function ready(fn) {
        if (document.readyState !== 'loading') {
            // Document already loaded, execute immediately
            fn();
        } else {
            // Document still loading, wait for DOMContentLoaded event
            document.addEventListener('DOMContentLoaded', fn);
        }
    }

    /**
     * Initialize mobile menu fallback functionality
     *
     * Main initialization function that sets up fallback behavior for all
     * overlay mobile navigation components found in the DOM. Each navigation
     * component is handled independently with its own state management.
     */
    ready(function () {
        /**
         * Process each overlay navigation component independently
         *
         * Searches for all elements with the data-overlay-nav attribute and
         * initializes fallback functionality for each one. This allows multiple
         * mobile menu instances to coexist on the same page without conflicts.
         */
        document.querySelectorAll('[data-overlay-nav]').forEach(function (root) {
            // Locate required DOM elements within this navigation component
            var btn = root.querySelector('.mobile-menu-btn');
            var menu = root.querySelector('#mobile-menu');

            /**
             * Validation: Skip initialization if required elements are missing
             * 
             * Gracefully handles incomplete DOM structures by checking for the
             * presence of both toggle button and menu elements before proceeding.
             */
            if (!btn || !menu) {
                return;
            }

            /**
             * Component state management
             * @type {boolean} open - Current open/closed state of the menu
             */
            var open = false;

            /**
             * Centralized state management function
             *
             * Updates all visual and accessibility aspects of the menu system
             * when the state changes. Ensures consistency between button appearance,
             * menu visibility, and screen reader accessibility.
             *
             * State Updates Applied:
             * - Toggle 'active' CSS class on button for visual feedback
             * - Toggle 'active' CSS class on menu for show/hide behavior
             * - Update aria-expanded attribute for screen reader accessibility
             *
             * @param {boolean} newState - New open/closed state for the menu
             *
             * @example
             * setState(true);  // Opens menu and updates all related elements
             * setState(false); // Closes menu and updates all related elements
             */
            var setState = function (newState) {
                open = !!newState; // Ensure boolean value

                // Update visual states with CSS classes
                btn.classList.toggle('active', open);
                menu.classList.toggle('active', open);

                // Update accessibility attributes for screen readers
                btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            };

            /**
             * Toggle button click event handler
             *
             * Handles user interaction with the hamburger/toggle button to
             * open and close the mobile menu. Prevents default button behavior
             * and toggles the menu state.
             *
             * @param {Event} e - Click event object
             */
            btn.addEventListener('click', function (e) {
                e.preventDefault(); // Prevent any default button behavior
                setState(!open);    // Toggle current state
            });

            /**
             * Click-outside-to-close behavior
             *
             * Implements intuitive UX pattern where clicking outside the menu
             * automatically closes it. This provides a natural way for users
             * to dismiss the menu without needing to find the close button.
             *
             * Implementation Details:
             * - Uses event bubbling to capture all document clicks
             * - Checks if click target is outside the navigation component
             * - Only closes menu if it's currently open (performance optimization)
             *
             * @param {Event} e - Click event object from document
             */
            document.addEventListener('click', function (e) {
                // Check if click occurred outside the navigation component
                if (!root.contains(e.target) && open) {
                    setState(false);
                }
            });

            /**
             * Keyboard navigation support
             *
             * Provides accessibility compliance and enhanced UX through keyboard
             * interaction support. Specifically handles the Escape key to close
             * the menu, which is a standard UI pattern for modal-like interfaces.
             *
             * Accessibility Benefits:
             * - Enables keyboard-only navigation users to close the menu
             * - Follows WCAG guidelines for modal-like interface behavior
             * - Provides consistent interaction patterns across the interface
             *
             * @param {KeyboardEvent} e - Keyboard event object
             */
            document.addEventListener('keyup', function (e) {
                // Close menu when Escape key is pressed (if menu is open)
                if (e.key === 'Escape' && open) {
                    setState(false);
                }
            });
        });
    });
})();