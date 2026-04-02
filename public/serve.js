(function() {
  'use strict';

  var API_BASE = 'https://reklam.biz/api';

  // Find all reklam ad containers
  var containers = document.querySelectorAll('[id="reklam-ad"], [data-reklam]');

  containers.forEach(function(container) {
    var unitId = container.getAttribute('data-unit');
    var format = container.getAttribute('data-format') || '300x250';

    if (!unitId) return;

    // Fetch ad from API
    fetch(API_BASE + '/serve?unit=' + encodeURIComponent(unitId) + '&format=' + encodeURIComponent(format))
      .then(function(response) { return response.json(); })
      .then(function(data) {
        if (!data.ad) {
          container.style.display = 'none';
          return;
        }

        var ad = data.ad;

        // Build ad HTML based on format
        var html = '';
        if (ad.image_url) {
          html = '<a href="' + ad.click_url + '?unit=' + unitId + '" target="_blank" rel="noopener" style="display:inline-block;text-decoration:none;">'
            + '<img src="' + ad.image_url + '" alt="' + escapeHtml(ad.title) + '" style="max-width:100%;height:auto;border:0;" />'
            + '</a>';
        } else {
          // Text ad
          html = '<div style="padding:12px;border:1px solid #eee;border-radius:8px;font-family:sans-serif;">'
            + '<a href="' + ad.click_url + '?unit=' + unitId + '" target="_blank" rel="noopener" style="text-decoration:none;color:#333;">'
            + '<div style="font-weight:600;font-size:14px;margin-bottom:4px;">' + escapeHtml(ad.title) + '</div>'
            + (ad.description ? '<div style="font-size:12px;color:#666;">' + escapeHtml(ad.description) + '</div>' : '')
            + '</a>'
            + '<div style="font-size:10px;color:#999;margin-top:4px;">Ad by Reklam.biz</div>'
            + '</div>';
        }

        container.innerHTML = html;

        // Track impression
        fetch(API_BASE + '/track/impression', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ ad_id: ad.id, unit_id: unitId })
        }).catch(function() {});
      })
      .catch(function(err) {
        console.error('[Reklam.biz] Failed to load ad:', err);
      });
  });

  function escapeHtml(text) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(text));
    return div.innerHTML;
  }
})();
