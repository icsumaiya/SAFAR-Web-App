document.addEventListener('DOMContentLoaded', () => {
    const filterForm = document.getElementById('explore-filter-form');
    const filterType = document.getElementById('filter-type');
    const filterLocation = document.getElementById('filter-location');
    const filterPrice = document.getElementById('filter-price');
    const priceDisplay = document.getElementById('price-display');
    const exploreGrid = document.getElementById('explore-grid');
    const resultsCount = document.getElementById('results-count');

    // Fallback images pool — different image for each card
    const fallbackImages = [
        'https://images.unsplash.com/photo-1476514525535-07fb3b4ae5f1?w=500&q=60',
        'https://images.unsplash.com/photo-1501785888041-af3ef285b470?w=500&q=60',
        'https://images.unsplash.com/photo-1441974231531-c6227db76b6e?w=500&q=60',
        'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=500&q=60',
        'https://images.unsplash.com/photo-1524492412937-b28074a5d7da?w=500&q=60',
        'https://images.unsplash.com/photo-1493558103817-58b2924bce98?w=500&q=60',
        'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?w=500&q=60',
        'https://images.unsplash.com/photo-1449824913935-59a10b8d2000?w=500&q=60',
        'https://images.unsplash.com/photo-1473448912268-2022ce9509d8?w=500&q=60',
        'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=500&q=60',
        'https://images.unsplash.com/photo-1502602898657-3e91760cbb34?w=500&q=60'
    ];
    function getFallbackImage(index) {
        return fallbackImages[index % fallbackImages.length];
    }

    function fetchListings() {
        const type = filterType.value;
        const location = filterLocation.value;
        const price = filterPrice.value;

        // Build query string
        const params = new URLSearchParams({
            type: type,
            location: location,
            price: price
        });

        exploreGrid.innerHTML = '<div style="text-align: center; grid-column: 1 / -1; padding: 40px;"><p>Loading...</p></div>';

        fetch(`api/filter_listings.php?${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    renderListings(data.data);
                } else {
                    console.error('Error fetching listings:', data.message);
                    exploreGrid.innerHTML = `<div style="text-align: center; grid-column: 1 / -1; padding: 40px; color: #ef4444;"><p>Failed to load listings.</p></div>`;
                }
            })
            .catch(err => {
                console.error('Fetch error:', err);
                exploreGrid.innerHTML = `<div style="text-align: center; grid-column: 1 / -1; padding: 40px; color: #ef4444;"><p>Connection error.</p></div>`;
            });
    }

    function renderListings(listings) {
        resultsCount.textContent = `${listings.length} Results`;
        exploreGrid.innerHTML = '';

        if (listings.length === 0) {
            exploreGrid.innerHTML = '<div style="text-align: center; grid-column: 1 / -1; padding: 40px; color: var(--text-muted);"><p>No listings found matching your criteria.</p></div>';
            return;
        }

        listings.forEach((item, index) => {
            const card = document.createElement('div');
            card.className = 'card fade-in';
            // Slight delay for stagger effect
            card.style.animationDelay = `${index * 0.05}s`;

            const typeLabel = item.type ? item.type.charAt(0).toUpperCase() + item.type.slice(1) : 'Tour';
            const imgUrl = item.image_url || getFallbackImage(index);
            
            card.innerHTML = `
                <div class="card-img" style="background-image: url('${imgUrl}');">
                    <span class="card-badge">${typeLabel}</span>
                </div>
                <div class="card-body">
                    <div class="card-meta">
                        <span><i class="fas fa-map-marker-alt"></i> ${item.location}</span>
                        <span>By SAFAR</span>
                    </div>
                    <h3 class="card-title">${item.title}</h3>
                    <p class="card-price">$${item.price_formatted}</p>
                    <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 20px; flex-grow: 1;">
                        ${item.description.substring(0, 100)}...
                    </p>
                    <div style="display: flex; gap: 10px;">
                        <a href="package-details.php?id=${item.id}" class="btn btn-outline" style="flex: 1;">View Details</a>
                        <a href="package-details.php?id=${item.id}" class="btn" style="flex: 1;">Book Now</a>
                    </div>
                </div>
            `;
            exploreGrid.appendChild(card);
        });

        // Trigger fade-in for newly added elements
        setTimeout(() => {
            document.querySelectorAll('#explore-grid .card.fade-in').forEach(el => el.classList.add('visible'));
        }, 50);
    }

    // Event Listeners
    filterType.addEventListener('change', fetchListings);
    
    // Debounce location search
    let timeout = null;
    filterLocation.addEventListener('input', () => {
        clearTimeout(timeout);
        timeout = setTimeout(fetchListings, 400);
    });

    filterPrice.addEventListener('input', (e) => {
        priceDisplay.textContent = `$${e.target.value}`;
    });

    filterPrice.addEventListener('change', fetchListings);

    // Near Hotels functionality
    const btnNearHotels = document.getElementById('btn-near-hotels');
    if (btnNearHotels) {
        btnNearHotels.addEventListener('click', () => {
            // For demo purposes, we automatically filter by type=hotel
            // and try to get the user's location, or mock it.
            filterType.value = 'hotel';
            if (navigator.geolocation) {
                // Mocking the location to "Sylhet" as requested, 
                // but in a real app we'd reverse geocode the coords.
                filterLocation.value = 'Sylhet'; 
            }
            fetchListings();
        });
    }

    // Initial load, handle potential URL params if passed from index search
    const urlParams = new URLSearchParams(window.location.search);
    if(urlParams.has('search')) {
        filterLocation.value = urlParams.get('search');
    }
    if(urlParams.has('type')) {
        filterType.value = urlParams.get('type');
    }
    if(urlParams.has('sort') && urlParams.get('sort') === 'near') {
        // Mocking user location for near hotels
        filterType.value = 'hotel';
        filterLocation.value = 'Sylhet';
    }

    fetchListings();
});
