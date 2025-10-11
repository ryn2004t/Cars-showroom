INSERT INTO users (email, password_hash, role) VALUES
  ('demo@example.com', '$2y$10$uQQUIeoPn1qeq3qEyz8fFecGJrJ1qG8PhAbSDv4x0oUqdU08eTQwC', 'owner');
-- password for above hash is DemoPass123!

INSERT INTO customers (name, phone) VALUES
  ('Alex Johnson', '+1 555-0100'),
  ('Maria Garcia', '+1 555-0111');

INSERT INTO jobs (title, scheduled_at, status, customer_id) VALUES
  ('Oil Change', '2025-10-11 09:00:00', 'confirmed', 1),
  ('Brake Inspection', '2025-10-12 14:30:00', 'requested', 2);

INSERT INTO invoices (invoice_number, job_id, total_amount, status) VALUES
  ('INV-1001', 1, 89.99, 'sent'),
  ('INV-1002', 2, 149.50, 'draft');

INSERT INTO promotions (name, description, discount_percent, active_from, active_to) VALUES
  ('Fall Tune-Up', '10% off tune-ups', 10, '2025-09-01 00:00:00', '2025-11-30 23:59:59');

INSERT INTO reviews (rating, comment, customer_id) VALUES
  (5, 'Great service!', 1),
  (4, 'Quick and professional.', 2);

INSERT INTO settings (`key`, `value`) VALUES
  ('business_name', 'Demo Auto Shop'),
  ('operating_hours', 'Mon-Fri 9am-6pm');
