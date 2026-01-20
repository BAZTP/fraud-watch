<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Fraud Watch - Transacciones</title>
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
      <h1 class="h4 mb-0">🧾 Transacciones (Simulador)</h1>
      <div class="muted small">Crea transacciones para luego correr el detector en Python</div>
    </div>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-light" href="index.php">Dashboard</a>
      <a class="btn btn-outline-light" href="alerts.php">Alertas</a>
    </div>
  </div>

  <div class="row g-3">
    <!-- Form -->
    <div class="col-12 col-lg-4">
      <div class="card rounded-4 shadow-sm">
        <div class="card-body">
          <h2 class="h5 mb-2">Crear transacción</h2>

          <form id="formTx" class="row g-2">
            <div class="col-12">
              <label class="form-label">Customer ID *</label>
              <input class="form-control" name="customer_id" required maxlength="40" placeholder="Ej: C001">
            </div>

            <div class="col-12">
              <label class="form-label">Nombre cliente *</label>
              <input class="form-control" name="customer_name" required maxlength="120" placeholder="Ej: Bryan">
            </div>

            <div class="col-12 col-md-6">
              <label class="form-label">Monto (USD) *</label>
              <input class="form-control" name="amount" required inputmode="decimal" placeholder="Ej: 120.50">
            </div>

            <div class="col-12 col-md-6">
              <label class="form-label">Canal *</label>
              <select class="form-select" name="channel" required>
                <option value="card" selected>Tarjeta</option>
                <option value="qr">QR</option>
                <option value="transfer">Transferencia</option>
                <option value="cash">Efectivo</option>
              </select>
            </div>

            <div class="col-12">
              <label class="form-label">Comercio (merchant) *</label>
              <input class="form-control" name="merchant" required maxlength="120" placeholder="Ej: LuxuryStore">
            </div>

            <div class="col-12 col-md-6">
              <label class="form-label">País (2 letras) *</label>
              <input class="form-control" name="country" value="EC" maxlength="2" required>
            </div>

            <div class="col-12 col-md-6">
              <label class="form-label">Ciudad</label>
              <input class="form-control" name="city" maxlength="80" placeholder="Ej: Quito">
            </div>

            <div class="col-12 col-md-6">
              <label class="form-label">Device ID</label>
              <input class="form-control" name="device_id" maxlength="80" placeholder="Ej: DEV-1">
            </div>

            <div class="col-12 col-md-6">
              <label class="form-label">IP</label>
              <input class="form-control" name="ip_addr" maxlength="45" placeholder="Ej: 181.10.10.1">
            </div>

            <input type="hidden" name="currency" value="USD">

            <div class="col-12 d-grid mt-2">
              <button class="btn btn-primary btn-lg">Crear</button>
            </div>

            <div class="muted small mt-2" id="msg"></div>
          </form>

          <div class="alert alert-secondary mt-3 mb-0">
            <b>Tip Fraude:</b> Crea 3 transacciones rápidas del mismo cliente (10 min) o cambia país (EC → US) para disparar reglas.
          </div>
        </div>
      </div>
    </div>

    <!-- List -->
    <div class="col-12 col-lg-8">
      <div class="card rounded-4 shadow-sm">
        <div class="card-body">
          <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-2">
            <div>
              <h2 class="h5 mb-0">Transacciones recientes</h2>
              <div class="muted small">Últimas 50</div>
            </div>
            <div class="d-flex gap-2">
              <input class="form-control form-control-sm" id="q" placeholder="Buscar tx_code/customer/merchant">
              <button class="btn btn-outline-light btn-sm" id="btnSearch">Buscar</button>
              <button class="btn btn-outline-light btn-sm" id="btnReload">Actualizar</button>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table table-dark table-hover align-middle mb-0">
              <thead>
                <tr>
                  <th>Código</th>
                  <th>Cliente</th>
                  <th>Monto</th>
                  <th>Canal</th>
                  <th>Merchant</th>
                  <th>País</th>
                  <th>Fecha</th>
                </tr>
              </thead>
              <tbody id="tb"></tbody>
            </table>
          </div>

          <div class="muted small mt-2" id="listMsg"></div>
        </div>
      </div>
    </div>

  </div>
</div>

<script src="assets/app.js"></script>
<script>
  async function loadTx(){
    const q = document.querySelector("#q").value.trim();
    const url = `../api/transactions_list.php?limit=50&q=${encodeURIComponent(q)}`;
    const d = await apiGet(url);

    const tb = document.querySelector("#tb");
    tb.innerHTML = "";

    const lm = document.querySelector("#listMsg");
    lm.textContent = d.ok ? "OK ✅" : (d.error || "Error");

    if(!d.ok){
      console.log(d.raw || d);
      return;
    }

    (d.transactions||[]).forEach(t=>{
      tb.insertAdjacentHTML("beforeend", `
        <tr>
          <td><b>${esc(t.tx_code)}</b></td>
          <td>${esc(t.customer_name)} <div class="text-secondary small">${esc(t.customer_id)}</div></td>
          <td>${money(t.amount_cents)} <span class="text-secondary small">${esc(t.currency)}</span></td>
          <td><span class="badge text-bg-info">${esc(t.channel)}</span></td>
          <td>${esc(t.merchant)}</td>
          <td>${esc(t.country)} <span class="text-secondary small">${esc(t.city||"")}</span></td>
          <td class="text-secondary">${esc(t.created_at)}</td>
        </tr>
      `);
    });

    if((d.transactions||[]).length === 0){
      tb.innerHTML = `<tr><td colspan="7" class="text-secondary">Sin transacciones.</td></tr>`;
    }
  }

  document.querySelector("#formTx").addEventListener("submit", async (e)=>{
    e.preventDefault();
    const msg = document.querySelector("#msg");
    msg.textContent = "Creando...";

    const fd = new FormData(e.target);
    const d = await apiPost("../api/transactions_create.php", fd);

    if(!d.ok){
      msg.textContent = d.error || "Error";
      console.log(d.raw || d);
      return;
    }

    msg.textContent = `✅ Creada: ${d.tx_code}`;
    e.target.reset();
    // valores por defecto
    e.target.country.value = "EC";
    e.target.currency.value = "USD";

    await loadTx();
  });

  document.querySelector("#btnReload").addEventListener("click", loadTx);
  document.querySelector("#btnSearch").addEventListener("click", loadTx);

  window.addEventListener("load", loadTx);
</script>
</body>
</html>
