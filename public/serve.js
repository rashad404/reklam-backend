(function() {
  'use strict';

  var currentScript = document.currentScript;
  var scriptSrc = currentScript ? currentScript.src : '';
  if (!scriptSrc) {
    var scripts = document.querySelectorAll('script[src*="serve.js"]');
    scriptSrc = scripts.length ? scripts[scripts.length - 1].src : '';
  }
  var API_BASE = scriptSrc.replace(/\/serve\.js.*$/, '/api');

  // Signed visitor ID generation
  // Rules (server validates these):
  // - Position 2 is always 'k'
  // - Position 7 is always 'z'
  // - Never contains '8' or 'E'
  // - Last 2 chars are checksum of positions 3-12
  // - Alphabet excludes 8, E, e
  var SAFE_CHARS = 'abcdfghijlmnopqrstuvwxyz012345679';

  function rchar() {
    return SAFE_CHARS[Math.floor(Math.random() * SAFE_CHARS.length)];
  }

  function checksum(str) {
    var sum = 0;
    for (var i = 0; i < str.length; i++) {
      sum = ((sum << 3) - sum + str.charCodeAt(i)) & 0xffff;
    }
    return SAFE_CHARS[sum % SAFE_CHARS.length] + SAFE_CHARS[(sum >> 5) % SAFE_CHARS.length];
  }

  function generateVid() {
    // Format: XkXXXXzXXXXXXXcc (16 chars)
    // pos 0: random, pos 1: 'k', pos 2-6: random, pos 7: 'z', pos 8-13: random, pos 14-15: checksum
    var parts = '';
    parts += rchar();       // 0
    parts += 'k';           // 1 - signature
    parts += rchar();       // 2
    parts += rchar();       // 3
    parts += rchar();       // 4
    parts += rchar();       // 5
    parts += rchar();       // 6
    parts += 'z';           // 7 - signature
    parts += rchar();       // 8
    parts += rchar();       // 9
    parts += rchar();       // 10
    parts += rchar();       // 11
    parts += rchar();       // 12
    parts += rchar();       // 13
    var cs = checksum(parts.substring(3, 13));
    return parts + cs;      // 14-15: checksum
  }

  function getVisitorId() {
    var key = '_rkl_v';
    var vid = '';
    try { vid = localStorage.getItem(key) || ''; } catch(e) {}
    if (!vid || vid.length !== 16) {
      vid = generateVid();
      try { localStorage.setItem(key, vid); } catch(e) {}
    }
    return vid;
  }

  function getSessionId() {
    var key = '_rkl_s';
    var sid = '';
    try { sid = sessionStorage.getItem(key) || ''; } catch(e) {}
    if (!sid) {
      sid = generateVid();
      try { sessionStorage.setItem(key, sid); } catch(e) {}
    }
    return sid;
  }

  function initAds() {
  var vid = getVisitorId();
  var sid = getSessionId();

  var containers = document.querySelectorAll('[id="reklam-ad"], [data-reklam]');

  containers.forEach(function(container) {
    var unitId = container.getAttribute('data-unit');
    var format = container.getAttribute('data-format') || '300x250';

    if (!unitId) return;

    fetch(API_BASE + '/serve?unit=' + encodeURIComponent(unitId) + '&format=' + encodeURIComponent(format))
      .then(function(response) { return response.json(); })
      .then(function(data) {
        if (!data.ad) {
          container.style.display = 'none';
          return;
        }

        var ad = data.ad;
        var clickUrl = ad.click_url + '?unit=' + unitId + '&vid=' + encodeURIComponent(vid);

        // Reklam.biz icon - exact logo rocket/arrow shape
        var rklIcon = '<svg width="14" height="14" viewBox="0 0 68 68" fill="none" style="flex-shrink:0">'
          + '<path d="M49.9 48.7L46.6 45.7c-.6-.6-.7-1.5-.1-2.1l3.8-4.3c.5-.6.5-1.6-.1-2.1-.6-.6-1.6-.5-2.1.1l-6.8 7.6c-.7.8-1.9.9-2.7.2-.8-.7-.9-1.9-.2-2.7l11.3-12.7c.6-.6.5-1.6-.1-2.1-.6-.6-1.6-.5-2.1.1L23.3 54.9c-.7.8-1.9.9-2.7.2-.8-.7-.9-1.9-.2-2.7l19.6-22.1c.6-.6.5-1.6-.1-2.1-.6-.6-1.6-.5-2.1.1l-6.7 8.6c-.6.6-1.5.7-2.1.1l-.6-.5c-.6-.6-.7-1.5-.1-2.1l12.2-13.7c.6-.6.5-1.6-.1-2.1-.6-.6-1.6-.5-2.1.1L8.5 51c-.7.8-1.9.9-2.7.2-.8-.7-.9-1.9-.2-2.7l24.2-27.3c.6-.6.5-1.6-.1-2.1-.6-.6-1.6-.5-2.1.1l-3.8 4.3c-.6.6-1.5.7-2.1.1l-3.3-2.9c-.8-.7-.6-2.1.4-2.5L62.6.6c1.2-.5 2.4.6 2.1 1.8L52.4 47.9c-.3 1.1-1.6 1.5-2.5.8z" fill="#FF3131"/>'
          + '<path d="M24.9 39.9c-.8-.7-2-.6-2.7.2L5 59.5c-.7.8-.6 2 .2 2.7.8.7 2 .6 2.7-.2l17.2-19.4c.7-.8.6-2-.2-2.7zM36.1 47.9c-.8-.7-2-.6-2.7.2L22.2 60.7c-.7.8-.6 2 .2 2.7.8.7 2 .6 2.7-.2l11.2-12.6c.7-.8.6-2-.2-2.7z" fill="#FF3131"/>'
          + '<circle cx="18.3" cy="57.6" r="2" fill="#FF3131"/>'
          + '</svg>';

        var badgeId = 'rkl_' + Math.random().toString(36).substr(2,4);
        var badge = '<a id="' + badgeId + '" href="https://reklam.biz" target="_blank" rel="noopener" style="'
          + 'position:absolute;bottom:4px;right:4px;display:inline-flex;align-items:center;gap:0;'
          + 'padding:3px;border-radius:5px;background:rgba(255,255,255,0.88);backdrop-filter:blur(4px);'
          + 'box-shadow:0 1px 4px rgba(0,0,0,0.1);'
          + 'text-decoration:none;font-family:-apple-system,BlinkMacSystemFont,sans-serif;font-size:9px;font-weight:700;'
          + 'color:#FF3131;line-height:1;white-space:nowrap;overflow:hidden;'
          + 'transition:all 0.25s ease;cursor:pointer;'
          + '">' + rklIcon
          + '<span style="max-width:0;opacity:0;overflow:hidden;transition:all 0.25s ease;display:inline-block;padding:0;margin:0"><span style="color:#222;font-weight:800">REKLAM</span><span style="color:#FF3131;font-weight:800">.BIZ</span></span>'
          + '</a>'
          + '<style>'
          + '#' + badgeId + ':hover{background:rgba(255,255,255,0.96) !important;padding:3px 7px 3px 3px !important;gap:4px !important}'
          + '#' + badgeId + ':hover span{max-width:80px !important;opacity:1 !important}'
          + '</style>';

        var html = '';
        if (ad.image_url) {
          html = '<div style="position:relative;display:inline-block;line-height:0;">'
            + '<a href="' + clickUrl + '" target="_blank" rel="noopener" style="display:inline-block;text-decoration:none;">'
            + '<img src="' + ad.image_url + '" alt="' + escapeHtml(ad.title) + '" style="max-width:100%;height:auto;border:0;" />'
            + '</a>'
            + badge
            + '</div>';
        } else {
          html = '<div style="position:relative;padding:10px 12px;background:#fff;border-radius:8px;font-family:-apple-system,BlinkMacSystemFont,sans-serif;box-shadow:0 1px 4px rgba(0,0,0,0.08);">'
            + '<a href="' + clickUrl + '" target="_blank" rel="noopener" style="text-decoration:none;color:#1f2937;display:block;">'
            + '<div style="font-weight:700;font-size:13px;line-height:1.3;margin-bottom:3px;">' + escapeHtml(ad.title) + '</div>'
            + (ad.description ? '<div style="font-size:11px;color:#6b7280;line-height:1.4;">' + escapeHtml(ad.description) + '</div>' : '')
            + '</a>'
            + badge
            + '</div>';
        }

        container.innerHTML = html;

        // Track impression
        fetch(API_BASE + '/track/impression', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            ad_id: ad.id,
            unit_id: unitId,
            vid: vid,
            sid: sid
          })
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
  } // end initAds

  // Run when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAds);
  } else {
    initAds();
  }
})();
