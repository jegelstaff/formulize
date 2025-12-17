/**
 * Formulize Log Viewer - Client-side Interactions
 * Handles expand/collapse functionality for sessions and events
 */

// Session expand/collapse
function toggleSession(headerElement) {
    const sessionBlock = headerElement.closest('.log-session-block');
    const eventsContainer = sessionBlock.querySelector('.log-session-events');
    const toggle = headerElement.querySelector('.log-session-toggle');

    if(eventsContainer.style.display === 'none' || eventsContainer.style.display === '') {
        // Expanding - show down arrow
        eventsContainer.style.display = 'block';
        toggle.textContent = '▼';
        headerElement.classList.add('expanded');
    } else {
        // Collapsing - show right arrow
        eventsContainer.style.display = 'none';
        toggle.textContent = '▶';
        headerElement.classList.remove('expanded');
    }
}

// Event details expand/collapse
function toggleEventDetails(compactElement) {
    const eventRow = compactElement.closest('.log-event-row');
    const detailsContainer = eventRow.querySelector('.log-event-details');

    if(detailsContainer.style.display === 'none' || detailsContainer.style.display === '') {
        detailsContainer.style.display = 'block';
        compactElement.classList.add('expanded');
    } else {
        detailsContainer.style.display = 'none';
        compactElement.classList.remove('expanded');
    }
}

// Navigate to a specific page offset via POST
function navigateToPage(offset) {
    const form = document.getElementById('log-filter-form');

    // Create or update hidden offset input
    let offsetInput = form.querySelector('input[name="offset"]');
    if (!offsetInput) {
        offsetInput = document.createElement('input');
        offsetInput.type = 'hidden';
        offsetInput.name = 'offset';
        form.appendChild(offsetInput);
    }
    offsetInput.value = offset;

    // Submit the form
    form.submit();
}

// Initialize when DOM is ready
(function() {
    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    function init() {
        // Filter accordion toggle
        const filterToggle = document.getElementById('log-filters-toggle');
        const filterContent = document.getElementById('log-filters-content');

        if(filterToggle && filterContent) {
            filterToggle.addEventListener('click', function() {
                const toggle = this.querySelector('.log-filter-toggle');

                if(filterContent.style.display === 'none' || filterContent.style.display === '') {
                    filterContent.style.display = 'block';
                    filterContent.classList.add('active');
                    toggle.textContent = '▼';
                } else {
                    filterContent.style.display = 'none';
                    filterContent.classList.remove('active');
                    toggle.textContent = '▶';
                }
            });

            // Auto-expand filters if any are active
            const activeCount = filterToggle.querySelector('.log-active-filter-count');
            if(activeCount) {
                filterContent.style.display = 'block';
                filterContent.classList.add('active');
                filterToggle.querySelector('.log-filter-toggle').textContent = '▼';
            }
        }

        // Optional: Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Press 'E' to expand all sessions
            if(e.key === 'E' && !e.ctrlKey && !e.altKey && !e.metaKey) {
                const target = e.target;
                if(target.tagName === 'INPUT' || target.tagName === 'TEXTAREA' || target.tagName === 'SELECT') {
                    return; // Don't trigger if user is typing in a form field
                }
                expandAllSessions();
                e.preventDefault();
            }

            // Press 'C' to collapse all sessions
            if(e.key === 'C' && !e.ctrlKey && !e.altKey && !e.metaKey) {
                const target = e.target;
                if(target.tagName === 'INPUT' || target.tagName === 'TEXTAREA' || target.tagName === 'SELECT') {
                    return; // Don't trigger if user is typing in a form field
                }
                collapseAllSessions();
                e.preventDefault();
            }
        });
    }

    // Expand all sessions
    function expandAllSessions() {
        const headers = document.querySelectorAll('.log-session-header');
        headers.forEach(function(header) {
            if(!header.classList.contains('expanded')) {
                toggleSession(header);
            }
        });
    }

    // Collapse all sessions
    function collapseAllSessions() {
        const headers = document.querySelectorAll('.log-session-header');
        headers.forEach(function(header) {
            if(header.classList.contains('expanded')) {
                toggleSession(header);
            }
        });
    }

    // Make functions available globally for inline onclick handlers
    window.expandAllSessions = expandAllSessions;
    window.collapseAllSessions = collapseAllSessions;
})();
