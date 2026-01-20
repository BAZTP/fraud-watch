CREATE DATABASE IF NOT EXISTS fraud_watch CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE fraud_watch;

-- Transacciones simuladas
CREATE TABLE transactions (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  tx_code VARCHAR(20) NOT NULL UNIQUE,
  customer_id VARCHAR(40) NOT NULL,
  customer_name VARCHAR(120) NOT NULL,
  amount_cents INT NOT NULL,
  currency CHAR(3) NOT NULL DEFAULT 'USD',
  channel ENUM('qr','card','transfer','cash') NOT NULL DEFAULT 'card',
  merchant VARCHAR(120) NOT NULL,
  country CHAR(2) NOT NULL DEFAULT 'EC',
  city VARCHAR(80) NULL,
  device_id VARCHAR(80) NULL,
  ip_addr VARCHAR(45) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_customer_time (customer_id, created_at),
  INDEX idx_amount (amount_cents),
  INDEX idx_created (created_at)
);

-- Alertas / flags de fraude
CREATE TABLE fraud_alerts (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  transaction_id BIGINT NOT NULL,
  rule_code VARCHAR(50) NOT NULL,
  severity ENUM('low','medium','high') NOT NULL DEFAULT 'medium',
  risk_score INT NOT NULL DEFAULT 0,          -- 0..100
  reason VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_tx_rule (transaction_id, rule_code),
  INDEX idx_score (risk_score),
  INDEX idx_created (created_at),
  CONSTRAINT fk_alert_tx FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE
);

-- Vista útil: transacciones con score máximo
CREATE VIEW v_transactions_risk AS
SELECT
  t.*,
  COALESCE(MAX(a.risk_score), 0) AS max_risk_score,
  COALESCE(SUM(CASE WHEN a.severity='high' THEN 1 ELSE 0 END), 0) AS high_flags
FROM transactions t
LEFT JOIN fraud_alerts a ON a.transaction_id=t.id
GROUP BY t.id;

-- Datos demo
INSERT INTO transactions(tx_code, customer_id, customer_name, amount_cents, channel, merchant, country, city, device_id, ip_addr, created_at) VALUES
('TX0001','C001','Bryan',  5000,'card','SuperMarket','EC','Quito','DEV-1','181.10.10.1', NOW() - INTERVAL 2 DAY),
('TX0002','C001','Bryan', 35000,'card','ElectroShop','EC','Quito','DEV-1','181.10.10.1', NOW() - INTERVAL 1 DAY),
('TX0003','C002','Ana',  120000,'transfer','LuxuryStore','US','Miami','DEV-9','45.1.1.2', NOW() - INTERVAL 6 HOUR),
('TX0004','C002','Ana',   90000,'transfer','LuxuryStore','EC','Quito','DEV-9','45.1.1.2', NOW() - INTERVAL 5 HOUR),
('TX0005','C003','Luis',   2000,'qr','Cafe','EC','Quito','DEV-3','190.2.3.4', NOW() - INTERVAL 10 MINUTE);
