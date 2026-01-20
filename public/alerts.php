<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Fraud Watch - Alertas</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body{ background:#0b1220; }
    .card{ background: rgba(255,255,255,.03); border:1px solid rgba(255,255,255,.08); }
    .muted{ color:#9ca3af; }
  </style>
</head>
<body class="text-light">
<div class="container py-4">

  <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
    <div>
      <h1 class="h4 mb-0">🚨 Alertas de Fraude</h1>
      <div class="muted small">Filtros por score y severidad</div>
    </div>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-light" href="index.php">Dashboard</a>
      <a class="btn btn-outline-light" href="transactions.php">Transacciones</a>
      <button class="btn btn-primary" id="btnRun">▶ Ejecutar detector</button>
    </div>
  </div>

  <div class="card rounded-4 shadow-sm mb-3">
    <div class="card-body">
      <form id="form" class="row g-2 align-items-end">
        <div class="col-12 col-md-3">
          <label class="form-label">Min score</label>
          <input type="number" class="form-control" id="min" value="40" min="0" max="100">
        </div>
        <div class="col-12 col-md-3">
          <label class="form-label">Severidad</label>
          <select class="form-select" id="sev">
            <option value="all" selected>Todas</option>
            <option value="low">Low</option>
            <option value="medium">Medium</option>
            <option value="high">High</option>
          </select>
        </div>
        <div class="col-12 col-md-4">
          <label class="form-label">Buscar</label>
          <input class="form-control" id="q" placeholder="cliente / tx_code / merchant / regla">
        </div>
        <div class="col-12 col-md-2 d-grid">
          <button class="btn btn-outline-light">Aplicar</button>
        </div>
      </form>
      <div class="muted small mt-2" id="msg">—</div>
    </div>
  </div>

  <div class="card rounded-4 shadow-sm">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-dark table-hover align-middle mb-0">
          <thead>
            <tr>
              <th>Score</th>
              <th>Sev</th>
              <th>Regla</th>
              <th>Razón</th>
              <th>Cliente</th>
              <th>Monto</th>
              <th>Tx</th>
              <th>Fecha</th>
            </tr>
          </thead>
          <tbody id="tb"></tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<script src="assets/app.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
  function sevBadge(sev){
    if(sev==="high") return "danger";
    if(sev==="medium") return "warning";
    return "info";
  }

  async function load(){
    const min = document.querySelector("#min").value;
    const sev = document.querySelector("#sev").value;
    const q = document.querySelector("#q").value.trim().toLowerCase();

    const d = await apiGet(`../api/alerts_list.php?min_score=${encodeURIComponent(min)}&severity=${encodeURIComponent(sev)}`);
    const tb = document.querySelector("#tb");
    tb.innerHTML = "";

    if(!d.ok){
      document.querySelector("#msg").textContent = d.error || "Error";
      if(d.error==="No JSON") debugNoJson(d);
      return;
    }

    // filtro local por búsqueda (rápido)
    let rows = d.alerts || [];
    if(q){
      rows = rows.filter(r=>{
        const s = [
          r.tx_code, r.customer_name, r.customer_id, r.merchant,
          r.rule_code, r.reason, r.country, r.city
        ].join(" ").toLowerCase();
        return s.includes(q);
      });
    }

    // orden: score desc, fecha desc
    rows.sort((a,b)=> (b.risk_score||0)-(a.risk_score||0));

    rows.forEach(r=>{
      tb.insertAdjacentHTML("beforeend", `
        <tr>
          <td><span class="badge text-bg-${sevBadge(r.severity)}">${esc(r.risk_score)}</span></td>
          <td><span class="badge text-bg-${sevBadge(r.severity)}">${esc(r.severity)}</span></td>
          <td><b>${esc(r.rule_code)}</b></td>
          <td class="text-secondary">${esc(r.reason)}</td>
          <td>${esc(r.customer_name)} <div class="text-secondary small">${esc(r.customer_id)}</div></td>
          <td>${money(r.amount_cents)}</td>
          <td class="text-secondary">${esc(r.tx_code)}</td>
          <td class="text-secondary">${esc(r.created_at)}</td>
        </tr>
      `);
    });

    document.querySelector("#msg").textContent = `OK ✅ (${rows.length} alertas)`;
    if(rows.length===0){
      tb.innerHTML = `<tr><td colspan="8" class="text-secondary">Sin resultados.</td></tr>`;
    }
  }

  async function runDetector(){
    const btn = document.querySelector("#btnRun");
    btn.disabled = true;
    btn.textContent = "Ejecutando...";
    const r = await apiPost("../api/run_detector.php", new FormData());

    if(!r.ok){
      if(r.error==="No JSON") debugNoJson(r);
      toast(r.error || "No se pudo ejecutar", "danger");
      btn.disabled = false;
      btn.textContent = "▶ Ejecutar detector";
      return;
    }

    toast("Detector ejecutado ✅", "success");
    console.log("Detector output:\n", r.output);
    btn.disabled = false;
    btn.textContent = "▶ Ejecutar detector";
    await load();
  }

  document.querySelector("#form").addEventListener("submit", (e)=>{ e.preventDefault(); load(); });
  document.querySelector("#btnRun").addEventListener("click", runDetector);
  window.addEventListener("load", load);
</script>
</body>
</html>
