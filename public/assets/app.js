// public/assets/app.js
// Helpers básicos + fetch JSON robusto + toasts Bootstrap

async function apiGet(url){
  const r = await fetch(url, { credentials:"same-origin", cache:"no-store" });
  const t = await r.text();
  try { return JSON.parse(t); }
  catch { return { ok:false, error:"No JSON", raw:t, status:r.status }; }
}

async function apiPost(url, data){
  const body = (data instanceof FormData) ? data : (()=> {
    const fd = new FormData();
    Object.entries(data||{}).forEach(([k,v])=> fd.append(k, v));
    return fd;
  })();

  const r = await fetch(url, { method:"POST", body, credentials:"same-origin", cache:"no-store" });
  const t = await r.text();
  try { return JSON.parse(t); }
  catch { return { ok:false, error:"No JSON", raw:t, status:r.status }; }
}

function esc(s){
  return (s??"").toString().replace(/[&<>"']/g, m => ({
    "&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#039;"
  }[m]));
}
function money(cents){ return "$" + (Number(cents||0)/100).toFixed(2); }

function toast(message, type="info"){
  // type: info|success|warning|danger
  if(!window.bootstrap?.Toast){
    alert(message);
    return;
  }
  let host = document.querySelector("#toastHost");
  if(!host){
    host = document.createElement("div");
    host.id = "toastHost";
    host.className = "toast-container position-fixed bottom-0 end-0 p-3";
    document.body.appendChild(host);
  }
  const el = document.createElement("div");
  el.className = `toast text-bg-${type} border-0`;
  el.setAttribute("role","alert");
  el.setAttribute("aria-live","assertive");
  el.setAttribute("aria-atomic","true");
  el.innerHTML = `
    <div class="d-flex">
      <div class="toast-body">${esc(message)}</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  `;
  host.appendChild(el);
  const t = new bootstrap.Toast(el, { delay: 2800 });
  t.show();
  el.addEventListener("hidden.bs.toast", ()=> el.remove());
}

function debugNoJson(resp){
  console.warn("No JSON:", resp);
  if(resp?.raw) console.log(resp.raw.slice(0, 1200));
}
