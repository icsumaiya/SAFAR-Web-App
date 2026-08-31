/**
 * Shared wishlist heart-button logic.
 *
 * Talks to /wishlist-toggle.php (session-authenticated), which reuses the
 * same WishlistService/WishlistValidator as the JWT api/traveler/wishlist.php
 * endpoint.
 *
 * Expects two globals to be set by the page before this file loads:
 *   window.SAFAR_BASE_URL   - same value as the PHP BASE_URL constant
 *   window.SAFAR_IS_TRAVELER - true if a traveler is logged in this session
 */
const SafarWishlist = (() => {
    let savedIds = new Set();
    let loaded = false;

    function endpoint(path) {
        const base = window.SAFAR_BASE_URL || '';
        return `${base}${path}`;
    }

    // Fetches the traveler's current wishlist package IDs once per page
    // load, so heart buttons can render as already-saved.
    function loadSavedIds() {
        if (!window.SAFAR_IS_TRAVELER) {
            return Promise.resolve(savedIds);
        }

        return fetch(endpoint('/wishlist-toggle.php'), { credentials: 'same-origin' })
            .then((res) => res.json())
            .then((data) => {
                if (data.success) {
                    savedIds = new Set(data.package_ids.map(Number));
                }
                loaded = true;
                return savedIds;
            })
            .catch(() => {
                loaded = true;
                return savedIds;
            });
    }

    function isSaved(packageId) {
        return savedIds.has(Number(packageId));
    }

    // Renders a heart button; caller places it wherever it's needed
    // (card thumbnail, details page, etc.). Handles its own click.
    function renderButton(packageId, options = {}) {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'wishlist-heart-btn';
        btn.dataset.packageId = packageId;
        btn.setAttribute('aria-label', 'Save to wishlist');
        applyStyles(btn, options.size || 38);

        const icon = document.createElement('i');
        icon.className = isSaved(packageId) ? 'fas fa-heart' : 'far fa-heart';
        btn.appendChild(icon);
        setActiveStyle(btn, isSaved(packageId));

        btn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            handleClick(packageId, btn, icon);
        });

        return btn;
    }

    // Wires click handling + icon state onto a heart button already
    // present in the DOM (server-rendered PHP markup), as opposed to
    // renderButton() which creates one from scratch for JS-built cards
    // (see explore.js). Safe to call more than once — already-wired
    // buttons are skipped.
    function attachAll(selector = '.wishlist-heart-btn[data-package-id]') {
        document.querySelectorAll(selector).forEach((btn) => {
            if (btn.dataset.wishlistBound) return;
            btn.dataset.wishlistBound = '1';

            const packageId = btn.dataset.packageId;
            let icon = btn.querySelector('i');
            if (!icon) {
                icon = document.createElement('i');
                btn.appendChild(icon);
            }
            setActiveStyle(btn, isSaved(packageId));

            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                handleClick(packageId, btn, icon);
            });
        });
    }

    function applyStyles(btn, size) {
        Object.assign(btn.style, {
            position: 'absolute',
            top: '12px',
            right: '12px',
            width: `${size}px`,
            height: `${size}px`,
            borderRadius: '50%',
            border: 'none',
            background: 'rgba(255,255,255,0.9)',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            cursor: 'pointer',
            boxShadow: '0 2px 6px rgba(0,0,0,0.15)',
            transition: 'transform 0.15s ease',
            zIndex: '3',
        });
        btn.addEventListener('mouseenter', () => (btn.style.transform = 'scale(1.1)'));
        btn.addEventListener('mouseleave', () => (btn.style.transform = 'scale(1)'));
    }

    function setActiveStyle(btn, active) {
        const icon = btn.querySelector('i');
        icon.className = active ? 'fas fa-heart' : 'far fa-heart';
        icon.style.color = active ? '#ef4444' : '#475569';
    }

    function handleClick(packageId, btn, icon) {
        if (!window.SAFAR_IS_TRAVELER) {
            window.location.href = endpoint('/login.php');
            return;
        }

        const action = isSaved(packageId) ? 'remove' : 'add';
        btn.disabled = true;

        fetch(endpoint('/wishlist-toggle.php'), {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action, package_id: packageId }),
        })
            .then((res) => res.json())
            .then((data) => {
                if (!data.success) {
                    console.error('Wishlist error:', data.error);
                    return;
                }
                if (data.action === 'added') {
                    savedIds.add(Number(packageId));
                } else {
                    savedIds.delete(Number(packageId));
                }
                setActiveStyle(btn, isSaved(packageId));
            })
            .catch((err) => console.error('Wishlist request failed:', err))
            .finally(() => {
                btn.disabled = false;
            });
    }

    return { loadSavedIds, renderButton, attachAll, isSaved, get loaded() { return loaded; } };
})();