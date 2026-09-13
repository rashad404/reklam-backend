(function () {
  'use strict';
  var script=document.currentScript;
  if(!script)return;
  var origin=new URL(script.src).origin;
  var api=origin+'/api';
  if(window.ReklamAds){window.ReklamAds.init();return;}
  var ready;
  function renderer(){
    if(window.ReklamRenderer)return Promise.resolve();
    if(!ready)ready=new Promise(function(resolve,reject){var s=document.createElement('script');s.src=origin+'/ad-renderer.js';s.onload=resolve;s.onerror=reject;document.head.appendChild(s);});
    return ready;
  }
  function send(kind,token){return fetch(api+'/track/'+kind,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({token:token}),keepalive:true,credentials:'omit'}).catch(function(){});}
  function observe(frame,token){
    if(!window.IntersectionObserver)return;
    var timer=null,visible=false,done=false;
    function update(){clearTimeout(timer);if(visible&&!document.hidden&&!done)timer=setTimeout(function(){done=true;send('viewable',token);observer.disconnect();document.removeEventListener('visibilitychange',update);},1000);}
    var observer=new IntersectionObserver(function(entries){visible=entries[0].intersectionRatio>=.5;update();},{threshold:[0,.5,1]});observer.observe(frame);document.addEventListener('visibilitychange',update);
  }
  function init(){
    document.querySelectorAll('[data-reklam],[id="reklam-ad"]').forEach(function(container){
      if(container.dataset.reklamLoaded||!container.dataset.unit)return;
      container.dataset.reklamLoaded='true';
      var format=container.dataset.format||'300x250';
      var sizes={'300x250':'300 / 250','728x90':'728 / 90','320x50':'320 / 50'};
      if(sizes[format]){container.style.aspectRatio=sizes[format];container.style.maxWidth=format.split('x')[0]+'px';}
      var controller=new AbortController();var timeout=setTimeout(function(){controller.abort();},8000);
      Promise.all([renderer(),fetch(api+'/serve?unit='+encodeURIComponent(container.dataset.unit),{signal:controller.signal,credentials:'omit'}).then(function(r){if(!r.ok)throw Error('delivery');return r.json();})])
      .then(function(values){var data=values[1];if(!data.ad){container.setAttribute('data-reklam-status','no-fill');return;}
        var frame;
        frame=window.ReklamRenderer.render(container,data.ad,{label:document.documentElement.lang==='az'?'Reklam':document.documentElement.lang==='ru'?'Реклама':'Advertisement',onError:function(){container.replaceChildren();container.setAttribute('data-reklam-status','image-error');},onReady:function(){queueMicrotask(function(){container.setAttribute('data-reklam-status','rendered');send('impression',data.token).then(function(){observe(frame,data.token);});});}});
      }).catch(function(){container.setAttribute('data-reklam-status','unavailable');}).finally(function(){clearTimeout(timeout);});
    });
  }
  window.ReklamAds={init:init};
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init,{once:true});else init();
})();
