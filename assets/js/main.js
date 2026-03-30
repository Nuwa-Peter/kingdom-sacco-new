// main.js
document.addEventListener('DOMContentLoaded', function () {
    // Top Nav behavior (using Bootstrap's JS)
    // Any custom JS for the new top nav would go here

    // Close mobile menu on link click
    const navLinks = document.querySelectorAll('.nav-link:not(.dropdown-toggle), .dropdown-item');
    const menu = document.getElementById('mainNavbar');
    if (menu) {
        const bsCollapse = new bootstrap.Collapse(menu, {toggle: false});
        navLinks.forEach((l) => {
            l.addEventListener('click', () => {
                if (window.innerWidth < 992 && menu.classList.contains('show')) {
                    bsCollapse.hide();
                }
            });
        });
    }

    // PWA Install Prompt
    let deferredPrompt;
    const installBtn = document.getElementById('installApp');

    window.addEventListener('beforeinstallprompt', (e) => {
        // Prevent the mini-infobar from appearing on mobile
        e.preventDefault();
        // Stash the event so it can be triggered later.
        deferredPrompt = e;
        // Update UI notify the user they can install the PWA
        if (installBtn) {
            installBtn.classList.remove('hidden');
        }
    });

    if (installBtn) {
        installBtn.addEventListener('click', async () => {
            if (deferredPrompt) {
                // Show the install prompt
                deferredPrompt.prompt();
                // Wait for the user to respond to the prompt
                const { outcome } = await deferredPrompt.userChoice;
                // We've used the prompt, and can't use it again, throw it away
                deferredPrompt = null;
                // Hide the install button
                installBtn.classList.add('hidden');
            }
        });
    }

    window.addEventListener('appinstalled', (evt) => {
        // Log install to analytics or hide button
        if (installBtn) {
            installBtn.classList.add('hidden');
        }
        console.log('INSTALL: Success');
    });
});
