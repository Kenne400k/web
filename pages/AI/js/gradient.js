/**
 * Gradient Border TTS - JavaScript
 * Dropdown toggle functionality
 */

function toggleDropdown(btn) {
    const content = btn.nextElementSibling;
    const arrow = btn.querySelector('.arrow');
    
    content.classList.toggle('active');
    arrow.classList.toggle('rotate');
}

// Optional: Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const dropdowns = document.querySelectorAll('.dropdown');
    
    dropdowns.forEach(dropdown => {
        const btn = dropdown.querySelector('.dropdown-btn');
        const content = dropdown.querySelector('.dropdown-content');
        const arrow = dropdown.querySelector('.arrow');
        
        if (!dropdown.contains(event.target)) {
            content.classList.remove('active');
            arrow.classList.remove('rotate');
        }
    });
});