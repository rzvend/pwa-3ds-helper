(function(){
  function log(msg){ try{ console.debug('[PWA3DS]', msg);}catch(e){} }

  function ensureBanner(){
    if (document.getElementById('pwa3ds-banner')) return;
    var el = document.createElement('div');
    el.id = 'pwa3ds-banner';
    el.textContent = 'Aguardando retorno do banco…';
    document.body.appendChild(el);
  }

  async function beacon(payload){
    try{
      await fetch(PWA3DS.rest.beacon, {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify(payload)
      });
    }catch(e){}
  }

  async function health(){
    try{
      const r = await fetch(PWA3DS.rest.health, {credentials:'same-origin'});
      return await r.json();
    }catch(e){ return null; }
  }

  function serializeCheckoutEmail(){
    var email = '';
    var el = document.querySelector('#billing_email, [name=billing_email]');
    if (el && el.value) email = el.value;
    return email;
  }

  function resubmitCheckout(){
    var f = document.querySelector('form.checkout');
    if (f){
      log('Resubmitting checkout form');
      f.dispatchEvent(new Event('submit', {bubbles:true, cancelable:true}));
    } else {
      location.reload();
    }
  }

  function sendPageState(){
    var email = serializeCheckoutEmail();
    var href = location.href;
    beacon({phase:'INFO', data:{event:'SAVE', data:{ billing_email: email }}, href: href});
  }

  document.addEventListener('visibilitychange', async function(){
    if (document.visibilityState === 'visible'){
      log('RETURN_VISIBLE');
      ensureBanner();
      beacon({phase:'INFO', data:{event:'RETURN_VISIBLE', href: location.href}, href: location.href});
      var h = await health();
      if (h && h.order_received_url){
        log('Redirecting to order-received');
        location.href = h.order_received_url;
        return;
      }
      if (h && h.should_resubmit){
        setTimeout(function(){ resubmitCheckout(); }, (PWA3DS.timeout||10)*1000);
      }
    }
  });

  window.addEventListener('pageshow', function(e){
    sendPageState();
  });

  if (document.readyState === 'complete' || document.readyState === 'interactive'){
    sendPageState();
  } else {
    document.addEventListener('DOMContentLoaded', sendPageState);
  }
})();