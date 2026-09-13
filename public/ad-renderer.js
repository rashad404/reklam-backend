(function (global) {
  'use strict';
  function safeUrl(value) { try { var url = new URL(value, global.location.origin); return /^(https?:)$/.test(url.protocol) ? url.href : ''; } catch { return ''; } }
  function render(container, ad, options) {
    options = options || {};
    var document = container.ownerDocument;
    var format = ad.format || ad.ad_format || 'text';
    var sizes = {banner_300x250:[300,250],banner_728x90:[728,90],banner_320x50:[320,50]};
    var size = sizes[format];
    var frame = document.createElement('div');
    frame.style.cssText='position:relative;box-sizing:border-box;overflow:hidden;background:#fff;color:#17212c;border:1px solid #dce1e7;font-family:Arial,Helvetica,sans-serif;width:100%;max-width:'+(size?size[0]:480)+'px;margin:0 auto;';
    if(size) frame.style.aspectRatio=size[0]+'/'+size[1];
    else frame.style.minHeight='130px';
    var link=document.createElement(options.preview?'div':'a');
    link.style.cssText='display:flex;flex-direction:column;position:relative;height:100%;box-sizing:border-box;text-decoration:none;color:inherit;';
    if(!options.preview) { link.href=safeUrl(ad.click_url); link.target='_blank'; link.rel='sponsored noopener noreferrer'; }
    if(size && ad.image_url) {
      var img=document.createElement('img');img.src=safeUrl(ad.image_url);img.alt=ad.title||options.label||'Advertisement';img.width=size[0];img.height=size[1];
      img.style.cssText='display:block;width:100%;height:100%;object-fit:contain;';
      img.addEventListener('error',function(){if(options.onError) options.onError();});
      img.addEventListener('load',function(){if(options.onReady) options.onReady();});link.appendChild(img);
    } else {
      var text=document.createElement('div');text.style.cssText='padding:18px 18px 30px;overflow-wrap:anywhere;';
      var title=document.createElement('strong');title.textContent=ad.title||'';title.style.cssText='display:block;font-size:17px;line-height:1.25;margin-bottom:7px;';text.appendChild(title);
      var description=document.createElement('div');description.textContent=ad.description||'';description.style.cssText='font-size:13px;line-height:1.5;color:#52606e';text.appendChild(description);link.appendChild(text);
    }
    frame.appendChild(link);
    var label=document.createElement('a');label.href='https://reklam.biz/help';label.target='_blank';label.rel='noopener noreferrer';label.textContent=(options.label||'Advertisement')+' - Reklam.biz';
    label.style.cssText='position:absolute;bottom:0;right:0;padding:3px 6px;background:#fff;color:#485563;font-size:10px;line-height:1.25;text-decoration:none;';frame.appendChild(label);
    container.replaceChildren(frame);
    if(!(size&&ad.image_url)&&options.onReady) options.onReady();
    return frame;
  }
  global.ReklamRenderer={render:render};
})(window);
