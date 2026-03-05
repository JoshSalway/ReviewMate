/**
 * ReviewMate Embeddable Review Widget
 * Usage: <script src="https://reviewmate.app/widget.js" data-business="your-business-slug"></script>
 */
(function () {
    'use strict';

    var script = document.currentScript || (function () {
        var scripts = document.getElementsByTagName('script');
        return scripts[scripts.length - 1];
    })();

    var slug = script.getAttribute('data-business');
    if (!slug) {
        console.warn('[ReviewMate Widget] No data-business attribute found on script tag.');
        return;
    }

    var apiBase = script.src.replace(/\/widget\.js.*$/, '');
    var apiUrl = apiBase + '/api/widget/' + encodeURIComponent(slug);

    function stars(rating) {
        var filled = '';
        var empty = '';
        for (var i = 0; i < 5; i++) {
            if (i < rating) filled += '<span style="color:#f59e0b">&#9733;</span>';
            else empty += '<span style="color:#d1d5db">&#9733;</span>';
        }
        return filled + empty;
    }

    function buildWidget(data, theme) {
        var isDark = theme === 'dark';
        var bg = isDark ? '#1f2937' : '#ffffff';
        var cardBg = isDark ? '#111827' : '#f9fafb';
        var textPrimary = isDark ? '#f9fafb' : '#111827';
        var textSecondary = isDark ? '#9ca3af' : '#6b7280';
        var border = isDark ? '#374151' : '#e5e7eb';

        var reviewsHtml = data.reviews.map(function (r) {
            return '<div style="background:' + cardBg + ';border:1px solid ' + border + ';border-radius:8px;padding:16px;margin-bottom:12px;">' +
                '<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">' +
                '<span style="font-weight:600;color:' + textPrimary + ';font-size:14px;">' + escHtml(r.reviewer_name) + '</span>' +
                '<span style="font-size:12px;color:' + textSecondary + ';">' + escHtml(r.reviewed_at) + '</span>' +
                '</div>' +
                '<div style="margin-bottom:8px;">' + stars(r.rating) + '</div>' +
                '<p style="color:' + textSecondary + ';font-size:14px;line-height:1.5;margin:0;">' + escHtml(r.body) + '</p>' +
                '</div>';
        }).join('');

        var avgRating = data.business.rating ? data.business.rating.toFixed(1) : '—';
        var reviewCount = data.business.review_count || 0;

        return '<div style="font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif;background:' + bg + ';border:1px solid ' + border + ';border-radius:12px;padding:24px;max-width:680px;">' +
            '<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">' +
            '<div>' +
            '<h3 style="margin:0;font-size:18px;font-weight:700;color:' + textPrimary + ';">' + escHtml(data.business.name) + '</h3>' +
            '<div style="display:flex;align-items:center;gap:6px;margin-top:4px;">' +
            stars(Math.round(data.business.rating || 0)) +
            '<span style="font-size:14px;font-weight:600;color:' + textPrimary + ';">' + avgRating + '</span>' +
            '<span style="font-size:13px;color:' + textSecondary + ';">(' + reviewCount + ' reviews)</span>' +
            '</div>' +
            '</div>' +
            '</div>' +
            reviewsHtml +
            '<div style="text-align:center;margin-top:16px;">' +
            '<a href="' + escAttr(data.powered_by_url) + '" target="_blank" rel="noopener noreferrer" ' +
            'style="font-size:11px;color:' + textSecondary + ';text-decoration:none;">Powered by ReviewMate</a>' +
            '</div>' +
            '</div>';
    }

    function escHtml(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function escAttr(str) {
        return String(str || '').replace(/"/g, '&quot;');
    }

    var container = document.createElement('div');
    container.setAttribute('id', 'reviewmate-widget-' + slug);
    script.parentNode.insertBefore(container, script.nextSibling);

    var xhr = new XMLHttpRequest();
    xhr.open('GET', apiUrl, true);
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.onload = function () {
        if (xhr.status >= 200 && xhr.status < 300) {
            try {
                var data = JSON.parse(xhr.responseText);
                var theme = script.getAttribute('data-theme') || 'light';
                container.innerHTML = buildWidget(data, theme);
            } catch (e) {
                console.warn('[ReviewMate Widget] Failed to parse response.');
            }
        } else {
            console.warn('[ReviewMate Widget] Failed to load reviews (status ' + xhr.status + ').');
        }
    };
    xhr.onerror = function () {
        console.warn('[ReviewMate Widget] Network error loading reviews.');
    };
    xhr.send();
})();
