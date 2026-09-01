/**
 * Build Alpine.js bundle
 */
const fs = require('fs');
const path = require('path');

// Read Alpine.js from node_modules
const alpinePath = path.join(__dirname, 'node_modules', 'alpinejs', 'dist', 'cdn.min.js');
const alpineContent = fs.readFileSync(alpinePath, 'utf8');

// Custom Alpine components and initialization
const customCode = `
// Alpine.js Global Store
document.addEventListener('alpine:init', () => {
    Alpine.store('app', {
        sidebarOpen: false,
        modalOpen: false,
        notificationCount: 0,
        loading: false,
        
        toggleSidebar() {
            this.sidebarOpen = !this.sidebarOpen;
        },
        
        openModal() {
            this.modalOpen = true;
        },
        
        closeModal() {
            this.modalOpen = false;
        },
    });

    // Dropdown Component
    Alpine.data('dropdown', () => ({
        open: false,
        toggle() { this.open = !this.open; },
        close() { this.open = false; },
    }));

    // Tab Component
    Alpine.data('tabs', (defaultTab = 1) => ({
        activeTab: defaultTab,
        switchTab(tab) { this.activeTab = tab; },
        isActive(tab) { return this.activeTab === tab; },
    }));

    // Modal Component
    Alpine.data('modal', (initialOpen = false) => ({
        open: initialOpen,
        show() {
            this.open = true;
            document.body.style.overflow = 'hidden';
        },
        hide() {
            this.open = false;
            document.body.style.overflow = '';
        },
        toggle() { this.open ? this.hide() : this.show(); },
    }));

    // Toast Component
    Alpine.data('toast', () => ({
        visible: false,
        message: '',
        type: 'info',
        show(message, type = 'info', duration = 3000) {
            this.message = message;
            this.type = type;
            this.visible = true;
            setTimeout(() => this.hide(), duration);
        },
        hide() {
            this.visible = false;
            setTimeout(() => { this.message = ''; }, 300);
        },
    }));

    // Sidebar Component
    Alpine.data('sidebar', () => ({
        open: window.innerWidth >= 1024,
        collapsed: false,
        toggle() { this.open = !this.open; },
        collapse() { this.collapsed = !this.collapsed; },
        close() {
            if (window.innerWidth < 1024) this.open = false;
        },
    }));

    // Accordion Component
    Alpine.data('accordion', (initialOpen = null) => ({
        openItem: initialOpen,
        toggle(item) { this.openItem = this.openItem === item ? null : item; },
        isOpen(item) { return this.openItem === item; },
    }));

    // Search Component
    Alpine.data('search', () => ({
        query: '',
        results: [],
        loading: false,
        async search() {
            if (this.query.length < 2) {
                this.results = [];
                return;
            }
            this.loading = true;
            // Add your search logic here
            this.loading = false;
        },
        clear() {
            this.query = '';
            this.results = [];
        },
    }));
});
`;

// Combine Alpine.js with custom code
const bundle = alpineContent + '\n' + customCode;

// Write to public assets
const outputPath = path.join(__dirname, 'public', 'assets', 'js', 'alpine.min.js');
fs.writeFileSync(outputPath, bundle, 'utf8');

console.log('✅ Alpine.js bundle created successfully at:', outputPath);
