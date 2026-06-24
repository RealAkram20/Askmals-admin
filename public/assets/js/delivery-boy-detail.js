'use strict';

document.addEventListener('DOMContentLoaded', function () {
    initMap();
    initStarRatings();
    initTabDatatableAdjust();
});

function initMap() {
    var config = window.__DB_DETAIL_CONFIG__;
    if (!config || !config.hasLocation) {
        var mapEl = document.getElementById('delivery-boy-map');
        if (mapEl) {
            mapEl.innerHTML = '<div class="d-flex align-items-center justify-content-center h-100 text-muted"><i class="ti ti-map-pin-off me-2"></i>No location data available</div>';
            mapEl.style.background = '#f8f9fa';
        }
        return;
    }

    var lat = config.latitude;
    var lng = config.longitude;

    var map = L.map('delivery-boy-map').setView([lat, lng], 15);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(map);

    L.marker([lat, lng])
        .addTo(map)
        .bindPopup('<strong>' + escapeHtml(config.name) + '</strong><br>Last known location')
        .openPopup();

    // Fix tile rendering when map is inside a hidden tab
    document.querySelectorAll('[data-bs-toggle="tab"]').forEach(function (tab) {
        tab.addEventListener('shown.bs.tab', function () {
            map.invalidateSize();
        });
    });
}

function initStarRatings() {
    document.querySelectorAll('.rating-stars').forEach(function (element) {
        if (typeof StarRating === 'undefined') return;
        new StarRating(element, {
            tooltip: false,
            clearable: false,
            readOnly: true,
            initialRating: element.dataset.rating,
            stars: function (el) {
                el.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87L18.18 22 12 18.27 5.82 22 7 14.14l-5-4.87 6.91-1.01L12 2z"/></svg>';
            },
        });
    });
}

/**
 * DataTables inside hidden tabs need a columns.adjust() call once the tab becomes visible.
 */
function initTabDatatableAdjust() {
    document.querySelectorAll('[data-bs-toggle="tab"]').forEach(function (tab) {
        tab.addEventListener('shown.bs.tab', function () {
            if (typeof $ !== 'undefined' && $.fn.DataTable) {
                $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust().responsive.recalc();
            }
        });
    });
}

function escapeHtml(text) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(text));
    return div.innerHTML;
}
