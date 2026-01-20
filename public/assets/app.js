async function apiGet(url){
  const r = await fetch(url, { credentials:"same-origin", cache:"no-store" });
  const t = await r.text();
  try { return JSON.parse(t); } catch { return { ok:false, error:"No JSON", raw:t, status:r.status }; }
}
async function apiPost(url, fd){
  const r = await fetch(url, { method:"POST", body:fd, credentials:"same-origin", cache:"no-store" });
  const t = await r.text();
  try { return JSON.parse(t); } catch { return { ok:false, error:"No JSON", raw:t, status:r.status }; }
}
function esc(s){
  return (s??"").toString().replace(/[&<>"']/g, m => ({
    "&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#039;"
  }[m]));
}
function money(cents){ return "$" + (Number(cents||0)/100).toFixed(2); }
