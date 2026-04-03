#!/usr/bin/env node

const puppeteer = require('puppeteer');
const path = require('path');
const fs = require('fs');
const crypto = require('crypto');

const args = JSON.parse(process.argv[2] || '{}');
const {
  title = 'Ad Title',
  description = '',
  color = '#FF3131',
  size = '300x250',
  domain = '',
  template = 'default',
  output = '',
} = args;

const [width, height] = size.split('x').map(Number);

const templates = {
  news: {
    bg: `linear-gradient(135deg, #0F172A 0%, #1E3A5F 50%, #0F172A 100%)`,
    extra: `
      .accent-line { position:absolute; top:0; left:0; right:0; height:4px; background:linear-gradient(90deg, #EF4444, #F97316, #EAB308); }
      .icon { position:absolute; top:${size==='300x250'?'18px':'12px'}; right:${size==='300x250'?'22px':'16px'}; font-size:${size==='300x250'?'32px':'22px'}; }
      .title { color:#fff; }
      .desc { color:rgba(255,255,255,0.7); }
      .domain-badge { background:rgba(239,68,68,0.9); color:#fff; padding:6px 16px; border-radius:4px; font-weight:700; }
    `,
    extraHtml: `<div class="accent-line"></div>`,
  },
  sports: {
    bg: `linear-gradient(135deg, #064E3B 0%, #059669 60%, #10B981 100%)`,
    extra: `
      .field-circle { position:absolute; top:50%; left:50%; width:100px; height:100px; border:2px solid rgba(255,255,255,0.08); border-radius:50%; transform:translate(-50%,-50%); }
      .icon { position:absolute; top:${size==='300x250'?'18px':'12px'}; right:${size==='300x250'?'22px':'16px'}; font-size:${size==='300x250'?'32px':'22px'}; }
      .title { color:#fff; text-shadow:0 2px 8px rgba(0,0,0,0.2); }
      .desc { color:rgba(255,255,255,0.8); }
      .domain-badge { background:rgba(255,255,255,0.2); border:1px solid rgba(255,255,255,0.3); color:#fff; padding:6px 18px; border-radius:20px; font-weight:700; }
    `,
    extraHtml: `<div class="field-circle"></div><div class="icon">⚽</div>`,
  },
  webdev: {
    bg: `linear-gradient(145deg, #2E1065 0%, #7C3AED 50%, #4C1D95 100%)`,
    extra: `
      .code-bg { position:absolute; bottom:8px; right:12px; font-family:'Courier New',monospace; font-size:9px; color:rgba(255,255,255,0.06); line-height:1.3; white-space:pre; }
      .icon { position:absolute; top:${size==='300x250'?'18px':'12px'}; right:${size==='300x250'?'22px':'16px'}; font-size:${size==='300x250'?'32px':'22px'}; }
      .title { color:#fff; }
      .desc { color:rgba(255,255,255,0.75); }
      .domain-badge { background:linear-gradient(90deg,#A855F7,#7C3AED); color:#fff; padding:6px 18px; border-radius:6px; font-weight:700; box-shadow:0 2px 8px rgba(124,58,237,0.4); }
    `,
    extraHtml: `<div class="code-bg">&lt;div class="site"&gt;\n  &lt;header/&gt;\n  &lt;main/&gt;\n  &lt;footer/&gt;\n&lt;/div&gt;</div>`,
  },
  ai: {
    bg: `linear-gradient(145deg, #0C4A6E 0%, #0891B2 50%, #06B6D4 100%)`,
    extra: `
      .glow { position:absolute; top:25%; left:50%; width:160px; height:160px; background:radial-gradient(circle,rgba(6,182,212,0.25),transparent 70%); transform:translate(-50%,-50%); border-radius:50%; }
      .icon { position:absolute; top:${size==='300x250'?'18px':'12px'}; right:${size==='300x250'?'22px':'16px'}; font-size:${size==='300x250'?'32px':'22px'}; }
      .title { color:#fff; }
      .desc { color:rgba(255,255,255,0.8); }
      .domain-badge { background:rgba(255,255,255,0.15); backdrop-filter:blur(4px); border:1px solid rgba(255,255,255,0.25); color:#fff; padding:6px 18px; border-radius:20px; font-weight:700; }
    `,
    extraHtml: `<div class="glow"></div><div class="icon">✨</div>`,
  },
  marketplace: {
    bg: `linear-gradient(145deg, #7F1D1D 0%, #DC2626 50%, #991B1B 100%)`,
    extra: `
      .tag { position:absolute; top:0; right:30px; background:#FDE047; color:#7F1D1D; font-size:11px; font-weight:800; padding:5px 12px; border-radius:0 0 6px 6px; letter-spacing:0.5px; }
      .icon { position:absolute; top:${size==='300x250'?'18px':'10px'}; left:${size==='300x250'?'22px':'14px'}; font-size:${size==='300x250'?'32px':'22px'}; }
      .title { color:#fff; text-shadow:0 2px 4px rgba(0,0,0,0.3); }
      .desc { color:rgba(255,255,255,0.85); }
      .domain-badge { background:#FDE047; color:#7F1D1D; padding:6px 20px; border-radius:6px; font-weight:800; }
    `,
    extraHtml: `<div class="tag">AL-SAT</div>`,
  },
  finance: {
    bg: `linear-gradient(145deg, #1C1917 0%, #44403C 40%, #292524 100%)`,
    extra: `
      .gold-line { position:absolute; top:0; left:0; right:0; height:3px; background:linear-gradient(90deg,#B45309,#F59E0B,#B45309); }
      .icon { position:absolute; top:${size==='300x250'?'18px':'12px'}; right:${size==='300x250'?'22px':'16px'}; font-size:${size==='300x250'?'32px':'22px'}; }
      .title { color:#F59E0B; }
      .desc { color:rgba(255,255,255,0.65); }
      .domain-badge { background:linear-gradient(90deg,#B45309,#F59E0B); color:#1C1917; padding:6px 20px; border-radius:4px; font-weight:800; }
    `,
    extraHtml: `<div class="gold-line"></div><div class="icon">💰</div>`,
  },
  default: {
    bg: `linear-gradient(145deg, ${color} 0%, ${darken(color,0.4)} 100%)`,
    extra: `.title{color:#fff} .desc{color:rgba(255,255,255,0.75)} .domain-badge{background:rgba(255,255,255,0.2);border:1px solid rgba(255,255,255,0.3);color:#fff;padding:6px 16px;border-radius:20px;font-weight:700}`,
    extraHtml: '',
  },
};

const tpl = templates[template] || templates.default;
const isWide = size === '728x90';
const isMobile = size === '320x50';
const isRect = size === '300x250';

const html = `<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
  @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap');

  *{margin:0;padding:0;box-sizing:border-box}

  body {
    width:${width}px;
    height:${height}px;
    overflow:hidden;
    font-family:'Inter',-apple-system,BlinkMacSystemFont,sans-serif;
    -webkit-font-smoothing:antialiased;
    -moz-osx-font-smoothing:grayscale;
  }

  .banner {
    width:${width}px;
    height:${height}px;
    background:${tpl.bg};
    position:relative;
    overflow:hidden;
  }

  .banner::before {
    content:'';
    position:absolute;
    top:-25%;right:-8%;
    width:${Math.max(width,height)*0.4}px;
    height:${Math.max(width,height)*0.4}px;
    border-radius:50%;
    background:rgba(255,255,255,0.03);
  }

  .content {
    position:relative; z-index:2;
    width:100%; height:100%;
    display:flex;
    ${isRect ? 'flex-direction:column; justify-content:center; align-items:center; padding:28px 28px 22px;' : ''}
    ${isWide ? 'flex-direction:row; align-items:center; padding:0 30px; gap:24px;' : ''}
    ${isMobile ? 'flex-direction:row; align-items:center; padding:0 16px; gap:12px;' : ''}
  }

  .text-group {
    ${isRect ? 'text-align:center; margin-bottom:14px;' : ''}
    ${isWide ? 'flex:1;' : ''}
    ${isMobile ? 'flex:1; overflow:hidden;' : ''}
  }

  .title {
    font-weight:900;
    line-height:1.15;
    letter-spacing:-0.5px;
    font-size:${isRect ? '32px' : isWide ? '26px' : '16px'};
    ${isMobile ? 'white-space:nowrap; overflow:hidden; text-overflow:ellipsis;' : ''}
  }

  .desc {
    font-weight:400;
    line-height:1.4;
    margin-top:${isRect ? '10px' : '5px'};
    font-size:${isRect ? '16px' : isWide ? '15px' : '11px'};
    ${isMobile ? 'display:none;' : ''}
    ${isRect ? 'max-width:92%;' : ''}
  }

  .domain-badge {
    font-size:${isRect ? '14px' : isWide ? '14px' : '11px'};
    letter-spacing:0.3px;
    white-space:nowrap;
    flex-shrink:0;
    ${isRect ? 'margin-top:4px;' : ''}
  }

  .icon { z-index:3; }

  ${tpl.extra}
</style>
</head>
<body>
<div class="banner">
  ${tpl.extraHtml}
  <div class="content">
    <div class="text-group">
      <div class="title">${esc(title)}</div>
      ${description ? `<div class="desc">${esc(description)}</div>` : ''}
    </div>
    ${domain ? `<div class="domain-badge">${esc(domain)}</div>` : ''}
  </div>
</div>
</body>
</html>`;

(async () => {
  const browser = await puppeteer.launch({
    headless: 'new',
    args: ['--no-sandbox','--disable-setuid-sandbox'],
    executablePath: process.platform === 'darwin'
      ? '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome'
      : undefined,
  });

  const page = await browser.newPage();
  await page.setViewport({ width, height, deviceScaleFactor: 2 });
  await page.setContent(html, { waitUntil: 'networkidle0' });
  await page.evaluate(() => document.fonts.ready);

  const hash = crypto.createHash('md5').update(title+size+template+Date.now()).digest('hex');
  const outputPath = output || path.join(__dirname,'..','storage','app','public','banners',`${hash}.png`);

  const dir = path.dirname(outputPath);
  if (!fs.existsSync(dir)) fs.mkdirSync(dir, { recursive: true });

  await page.screenshot({ path: outputPath, type: 'png', clip: { x:0, y:0, width, height } });
  await browser.close();

  console.log(JSON.stringify({ path: outputPath, filename: `banners/${hash}.png` }));
})();

function darken(hex, factor) {
  hex = hex.replace('#','');
  const r = Math.max(0, Math.round(parseInt(hex.substr(0,2),16)*(1-factor)));
  const g = Math.max(0, Math.round(parseInt(hex.substr(2,2),16)*(1-factor)));
  const b = Math.max(0, Math.round(parseInt(hex.substr(4,2),16)*(1-factor)));
  return `rgb(${r},${g},${b})`;
}

function esc(t) {
  return t.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
