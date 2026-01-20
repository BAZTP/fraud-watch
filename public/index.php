<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Fraud Watch - Dashboard</title>
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
      <h1 class="h4 mb-0">🕵️ Fraud Watch (Simulado)</h1>
      <div class="muted small">Dashboard de detección de fraude por reglas</div>
    </div>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-light" href="transactions.php">Transacciones</a>
      <a class="btn btn-outline-light" href="alerts.php">Alertas</a>
      <button class="btn btn-primary" id="btnRun">▶ Ejecutar detector</button>
    </div>
  </div>

  <!-- KPIs -->
  <div class="row g-3 mb-3">
    <div class="col-12 col-md-6 col-lg-3">
      <div class="card rounded-4 shadow-sm h-100">
        <div class="card-body">
          <div class="muted small">Transacciones</div>
          <div class="display-6 mb-0" id="k_tx">0</div>
        </div>
      </div>
    </div>

    <div class="col-12 col-md-6 col-lg-3">
      <div class="card rounded-4 shadow-sm h-100">
        <div class="card-body">
          <div class="muted small">Alertas</div>
          <div class="display-6 mb-0" id="k_alerts">0</div>
        </div>
      </div>
    </div>

    <div class="col-12 col-md-6 col-lg-3">
      <div class="card rounded-4 shadow-sm h-100">
        <div class="card-body">
          <div class="muted small">High (severidad)</div>
          <div class="display-6 mb-0" id="k_high">0</div>
        </div>
      </div>
    </div>

    <div class="col-12 col-md-6 col-lg-3">
      <div class="card rounded-4 shadow-sm h-100">
        <div class="card-body">
          <div class="muted small">Score promedio</div>
          <div class="display-6 mb-0" id="k_avg">0</div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3">
    <!-- Chart -->
    <div class="col-12 col-lg-6">
      <div class="card rounded-4 shadow-sm h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
              <h2 class="h5 mb-0">Alertas por día</h2>
              <div class="muted small">Últimos 14 días</div>
            </div>
            <button class="btn btn-outline-light btn-sm" id="btnRefresh">Actualizar</button>
          </div>
          <canvas id="chart" height="140"></canvas>
        </div>
      </div>
    </div>

    <!-- Recent Alerts -->
    <div class="col-12 col-lg-6">
      <div class="card rounded-4 shadow-sm h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
              <h2 class="h5 mb-0">Alertas recientes</h2>
              <div class="muted small">Top por score</div>
            </div>
            <a class="btn btn-outline-light btn-sm" href="alerts.php">Ver todas</a>
          </div>

          <div class="table-responsive">
            <table class="table table-dark table-hover align-middle mb-0">
              <thead>
                <tr>
                  <th>Score</th>
                  <th>Regla</th>
                  <th>Cliente</th>
                  <th>Monto</th>
                  <th>Tx</th>
                </tr>
              </thead>
              <tbody id="tb"></tbody>
            </table>
          </div>

          <div class="muted small mt-2" id="msg">—</div>
        </div>
      </div>
    </div>
  </div>

</div>

<script src="assets/app.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<script>
  let chart;

  function badge(sev){
    if(sev==="high") return "danger";
    if(sev==="medium") return "warning";
    return "info";
  }

  async function loadAll(){
    // KPIs + chart
    const m = await apiGet("../api/metrics.php");
    if(!m.ok){
      if(m.error==="No JSON") debugNoJson(m);
      toast(m.error || "Error cargando metrics", "danger");
      return;
    }

    const k = m.kpis || {};
    document.querySelector("#k_tx").textContent = k.tx_total ?? 0;
    document.querySelector("#k_alerts").textContent = k.alerts_total ?? 0;
    document.querySelector("#k_high").textContent = k.high_alerts ?? 0;
    document.querySelector("#k_avg").textContent = Math.round(k.avg_score ?? 0);

    // Chart
    const labels = (m.chart||[]).map(x=>x.d);
    const values = (m.chart||[]).map(x=>Number(x.c||0));

    const ctx = document.querySelector("#chart").getContext("2d");
    if(chart) chart.destroy();
    chart = new Chart(ctx, {
      type: "line",
      data: { labels, datasets: [{ label:"Alertas", data: values }] },
      options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
          x: { ticks: { color:"#9ca3af" } },
          y: { ticks: { color:"#9ca3af" } }
        }
      }
    });

    // Tabla alertas (min_score 40)
    const a = await apiGet("../api/alerts_list.php?min_score=40&severity=all");
    const tb = document.querySelector("#tb");
    tb.innerHTML = "";

    if(!a.ok){
      document.querySelector("#msg").textContent = a.error || "Error alertas";
      if(a.error==="No JSON") debugNoJson(a);
      return;
    }

    // ordena por score desc y toma top 10
    const rows = (a.alerts||[]).sort((x,y)=> (y.risk_score||0)-(x.risk_score||0)).slice(0,10);

    rows.forEach(r=>{
      tb.insertAdjacentHTML("beforeend", `
        <tr>
          <td><span class="badge text-bg-${badge(r.severity)}">${esc(r.risk_score)}</span></td>
          <td>
            <b>${esc(r.rule_code)}</b>
            <div class="text-secondary small">${esc(r.reason)}</div>
          </td>
          <td>${esc(r.customer_name)} <div class="text-secondary small">${esc(r.customer_id)}</div></td>
          <td>${money(r.amount_cents)}</td>
          <td class="text-secondary">${esc(r.tx_code)}</td>
        </tr>
      `);
    });

    document.querySelector("#msg").textContent = "OK ✅";
    if(rows.length===0){
      tb.innerHTML = `<tr><td colspan="5" class="text-secondary">Sin alertas aún. Crea transacciones y ejecuta el detector.</td></tr>`;
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
    await loadAll();
  }

  document.querySelector("#btnRefresh").addEventListener("click", loadAll);
  document.querySelector("#btnRun").addEventListener("click", runDetector);
  window.addEventListener("load", loadAll);
</script>
</body>
</html>
