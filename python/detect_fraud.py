import json
import mysql.connector
from datetime import datetime, timedelta

DB = dict(host="localhost", user="root", password="", database="fraud_watch")

def load_rules(path="rules.json"):
    with open(path, "r", encoding="utf-8") as f:
        return json.load(f)

def usd(amount_cents: int) -> float:
    return amount_cents / 100.0

def main():
    rules = load_rules("rules.json")
    cn = mysql.connector.connect(**DB)
    cur = cn.cursor(dictionary=True)

    # Analizamos las últimas 7 días (puedes ajustar)
    since = datetime.now() - timedelta(days=7)

    cur.execute("""
      SELECT * FROM transactions
      WHERE created_at >= %s
      ORDER BY created_at ASC
    """, (since,))
    txs = cur.fetchall()

    # Cache por cliente para reglas de comportamiento
    by_customer = {}
    for t in txs:
        by_customer.setdefault(t["customer_id"], []).append(t)

    alerts_to_insert = []

    # Helper: inserta alerta si no existe
    def add_alert(tx_id, rule_code, severity, score, reason):
        alerts_to_insert.append((tx_id, rule_code, severity, score, reason))

    # Regla 1: Monto alto / medio
    for t in txs:
        amount_usd = usd(t["amount_cents"])
        if amount_usd >= rules["amount_high"]["threshold_usd"]:
            add_alert(t["id"], "amount_high", rules["amount_high"]["severity"], rules["amount_high"]["score"],
                      f"Monto alto: ${amount_usd:.2f} >= ${rules['amount_high']['threshold_usd']}")
        elif amount_usd >= rules["amount_medium"]["threshold_usd"]:
            add_alert(t["id"], "amount_medium", rules["amount_medium"]["severity"], rules["amount_medium"]["score"],
                      f"Monto medio: ${amount_usd:.2f} >= ${rules['amount_medium']['threshold_usd']}")

    # Regla 2: muchas transacciones en pocos minutos (rapid_fire)
    rf_count = rules["rapid_fire"]["count"]
    rf_window = timedelta(minutes=rules["rapid_fire"]["minutes"])
    for cust, arr in by_customer.items():
        for i in range(len(arr)):
            start = arr[i]["created_at"]
            end = start + rf_window
            window = [x for x in arr if start <= x["created_at"] <= end]
            if len(window) >= rf_count:
                # marca todas en esa ventana como sospechosas
                for x in window:
                    add_alert(x["id"], "rapid_fire", rules["rapid_fire"]["severity"], rules["rapid_fire"]["score"],
                              f"{len(window)} transacciones en {rules['rapid_fire']['minutes']} min (cliente {cust})")

    # Regla 3: cambio de país en 24h (country_change_24h)
    window_24 = timedelta(hours=24)
    for cust, arr in by_customer.items():
        for i in range(len(arr)):
            t = arr[i]
            start = t["created_at"] - window_24
            recent = [x for x in arr if start <= x["created_at"] <= t["created_at"]]
            countries = set(x["country"] for x in recent)
            if len(countries) >= 2:
                add_alert(t["id"], "country_change_24h", rules["country_change_24h"]["severity"], rules["country_change_24h"]["score"],
                          f"Cambio de país en 24h: {', '.join(sorted(countries))}")

    # Regla 4: horario nocturno (night_hours)
    h_from = rules["night_hours"]["from"]
    h_to = rules["night_hours"]["to"]
    for t in txs:
        h = t["created_at"].hour
        if h_from <= h <= h_to:
            add_alert(t["id"], "night_hours", rules["night_hours"]["severity"], rules["night_hours"]["score"],
                      f"Transacción en horario nocturno ({h:02d}:00)")

    # Insertar evitando duplicados por (transaction_id, rule_code)
    cur2 = cn.cursor()
    cur2.executemany("""
      INSERT INTO fraud_alerts(transaction_id, rule_code, severity, risk_score, reason)
      VALUES(%s,%s,%s,%s,%s)
      ON DUPLICATE KEY UPDATE
        severity=VALUES(severity),
        risk_score=VALUES(risk_score),
        reason=VALUES(reason)
    """, alerts_to_insert)

    cn.commit()
    print(f"OK: {len(alerts_to_insert)} alertas procesadas.")
    cur.close()
    cur2.close()
    cn.close()

if __name__ == "__main__":
    main()
