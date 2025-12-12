-- ═══════════════════════════════════════════════════════════════
-- CRUD6 Integration Test Seed Data
-- Generated from JSON schemas
-- ═══════════════════════════════════════════════════════════════
--
-- EXECUTION ORDER IN INTEGRATION TESTS:
-- 1. Migrations run (php bakery migrate)
-- 2. Admin user created (php bakery create:admin-user) → user_id = 1, group_id = 1
-- 3. THIS SQL RUNS → Creates test data starting from ID 2
-- 4. Unauthenticated path testing begins
-- 5. Authenticated path testing begins
--
-- CRITICAL CONSTRAINTS:
-- - User ID 1 and Group ID 1 are RESERVED for system/admin
-- - Test data ALWAYS starts from ID 2 or higher
-- - DELETE/DISABLE tests MUST NOT use ID 1 (system account protection)
-- - Uses INSERT...ON DUPLICATE KEY UPDATE for safe re-seeding
--
-- Generated: 2025-12-12T02:11:25.641Z
-- Source: Schema files in examples/schema/
-- ═══════════════════════════════════════════════════════════════

-- Disable foreign key checks for seeding
SET FOREIGN_KEY_CHECKS=0;

-- ------------------------------------------------------------
-- Model: activities
-- ------------------------------------------------------------

-- Seed data for activities
-- Generated from schema: activities.json

INSERT INTO activities (ip_address, user_id, type, occurred_at, description)
VALUES ('Test ip_address', 3, 'Test type', '2024-01-02 12:00:00', 'Test description for description - Record 2')
ON DUPLICATE KEY UPDATE ip_address = VALUES(ip_address), user_id = VALUES(user_id), type = VALUES(type), occurred_at = VALUES(occurred_at), description = VALUES(description);

INSERT INTO activities (ip_address, user_id, type, occurred_at, description)
VALUES ('Test ip_address', 4, 'Test type', '2024-01-03 12:00:00', 'Test description for description - Record 3')
ON DUPLICATE KEY UPDATE ip_address = VALUES(ip_address), user_id = VALUES(user_id), type = VALUES(type), occurred_at = VALUES(occurred_at), description = VALUES(description);

INSERT INTO activities (ip_address, user_id, type, occurred_at, description)
VALUES ('Test ip_address', 5, 'Test type', '2024-01-04 12:00:00', 'Test description for description - Record 4')
ON DUPLICATE KEY UPDATE ip_address = VALUES(ip_address), user_id = VALUES(user_id), type = VALUES(type), occurred_at = VALUES(occurred_at), description = VALUES(description);


-- ------------------------------------------------------------
-- Model: categories
-- ------------------------------------------------------------

-- Seed data for categories
-- Generated from schema: categories.json

INSERT INTO categories (name, slug, description, icon, is_active)
VALUES ('Test name 2', 'test-slug-2', 'Test description for description - Record 2', 'Test icon', true)
ON DUPLICATE KEY UPDATE name = VALUES(name), slug = VALUES(slug), description = VALUES(description), icon = VALUES(icon), is_active = VALUES(is_active);

INSERT INTO categories (name, slug, description, icon, is_active)
VALUES ('Test name 3', 'test-slug-3', 'Test description for description - Record 3', 'Test icon', true)
ON DUPLICATE KEY UPDATE name = VALUES(name), slug = VALUES(slug), description = VALUES(description), icon = VALUES(icon), is_active = VALUES(is_active);

INSERT INTO categories (name, slug, description, icon, is_active)
VALUES ('Test name 4', 'test-slug-4', 'Test description for description - Record 4', 'Test icon', true)
ON DUPLICATE KEY UPDATE name = VALUES(name), slug = VALUES(slug), description = VALUES(description), icon = VALUES(icon), is_active = VALUES(is_active);


-- ------------------------------------------------------------
-- Model: contacts
-- ------------------------------------------------------------

-- Seed data for contacts
-- Generated from schema: contacts.json

INSERT INTO contacts (first_name, last_name, email, phone, mobile, website, address, city, state, zip, notes, bio, company, position, is_active, newsletter_opt_in)
VALUES ('Test first_name 2', 'Test last_name 2', 'test_email', NULL, NULL, NULL, 'Test address', 'Test city', 'Test state', NULL, NULL, NULL, 'Test company', 'Test position', true, false)
ON DUPLICATE KEY UPDATE first_name = VALUES(first_name), last_name = VALUES(last_name), email = VALUES(email), phone = VALUES(phone), mobile = VALUES(mobile), website = VALUES(website), address = VALUES(address), city = VALUES(city), state = VALUES(state), zip = VALUES(zip), notes = VALUES(notes), bio = VALUES(bio), company = VALUES(company), position = VALUES(position), is_active = VALUES(is_active), newsletter_opt_in = VALUES(newsletter_opt_in);

INSERT INTO contacts (first_name, last_name, email, phone, mobile, website, address, city, state, zip, notes, bio, company, position, is_active, newsletter_opt_in)
VALUES ('Test first_name 3', 'Test last_name 3', 'test_email', NULL, NULL, NULL, 'Test address', 'Test city', 'Test state', NULL, NULL, NULL, 'Test company', 'Test position', true, false)
ON DUPLICATE KEY UPDATE first_name = VALUES(first_name), last_name = VALUES(last_name), email = VALUES(email), phone = VALUES(phone), mobile = VALUES(mobile), website = VALUES(website), address = VALUES(address), city = VALUES(city), state = VALUES(state), zip = VALUES(zip), notes = VALUES(notes), bio = VALUES(bio), company = VALUES(company), position = VALUES(position), is_active = VALUES(is_active), newsletter_opt_in = VALUES(newsletter_opt_in);

INSERT INTO contacts (first_name, last_name, email, phone, mobile, website, address, city, state, zip, notes, bio, company, position, is_active, newsletter_opt_in)
VALUES ('Test first_name 4', 'Test last_name 4', 'test_email', NULL, NULL, NULL, 'Test address', 'Test city', 'Test state', NULL, NULL, NULL, 'Test company', 'Test position', true, false)
ON DUPLICATE KEY UPDATE first_name = VALUES(first_name), last_name = VALUES(last_name), email = VALUES(email), phone = VALUES(phone), mobile = VALUES(mobile), website = VALUES(website), address = VALUES(address), city = VALUES(city), state = VALUES(state), zip = VALUES(zip), notes = VALUES(notes), bio = VALUES(bio), company = VALUES(company), position = VALUES(position), is_active = VALUES(is_active), newsletter_opt_in = VALUES(newsletter_opt_in);


-- ------------------------------------------------------------
-- Model: tasks
-- ------------------------------------------------------------

-- Seed data for tasks
-- Generated from schema: tasks.json

INSERT INTO tasks (title, description, status, priority, assigned_to, due_date)
VALUES ('Test title', 'Test description for description - Record 2', 'pending', 'medium', 'Test assigned_to', '2024-01-02')
ON DUPLICATE KEY UPDATE title = VALUES(title), description = VALUES(description), status = VALUES(status), priority = VALUES(priority), assigned_to = VALUES(assigned_to), due_date = VALUES(due_date);

INSERT INTO tasks (title, description, status, priority, assigned_to, due_date)
VALUES ('Test title', 'Test description for description - Record 3', 'pending', 'medium', 'Test assigned_to', '2024-01-03')
ON DUPLICATE KEY UPDATE title = VALUES(title), description = VALUES(description), status = VALUES(status), priority = VALUES(priority), assigned_to = VALUES(assigned_to), due_date = VALUES(due_date);

INSERT INTO tasks (title, description, status, priority, assigned_to, due_date)
VALUES ('Test title', 'Test description for description - Record 4', 'pending', 'medium', 'Test assigned_to', '2024-01-04')
ON DUPLICATE KEY UPDATE title = VALUES(title), description = VALUES(description), status = VALUES(status), priority = VALUES(priority), assigned_to = VALUES(assigned_to), due_date = VALUES(due_date);


-- ------------------------------------------------------------
-- Model: groups
-- ------------------------------------------------------------

-- Seed data for groups
-- Generated from schema: groups.json

INSERT INTO groups (slug, name, description, icon)
VALUES ('test_slug_2', 'Test name 2', 'Test description for description - Record 2', 'fas fa-user')
ON DUPLICATE KEY UPDATE slug = VALUES(slug), name = VALUES(name), description = VALUES(description), icon = VALUES(icon);

INSERT INTO groups (slug, name, description, icon)
VALUES ('test_slug_3', 'Test name 3', 'Test description for description - Record 3', 'fas fa-user')
ON DUPLICATE KEY UPDATE slug = VALUES(slug), name = VALUES(name), description = VALUES(description), icon = VALUES(icon);

INSERT INTO groups (slug, name, description, icon)
VALUES ('test_slug_4', 'Test name 4', 'Test description for description - Record 4', 'fas fa-user')
ON DUPLICATE KEY UPDATE slug = VALUES(slug), name = VALUES(name), description = VALUES(description), icon = VALUES(icon);


-- ------------------------------------------------------------
-- Model: order_details
-- ------------------------------------------------------------

-- Seed data for order_details
-- Generated from schema: order_details.json

INSERT INTO order_details (order_id, line_number, sku, product_name, quantity, unit_price, line_total, notes)
VALUES (3, 3, 'Test sku', 'Test product_name 2', 3, 21.00, 21.00, 'Test description for notes - Record 2')
ON DUPLICATE KEY UPDATE order_id = VALUES(order_id), line_number = VALUES(line_number), sku = VALUES(sku), product_name = VALUES(product_name), quantity = VALUES(quantity), unit_price = VALUES(unit_price), line_total = VALUES(line_total), notes = VALUES(notes);

INSERT INTO order_details (order_id, line_number, sku, product_name, quantity, unit_price, line_total, notes)
VALUES (4, 4, 'Test sku', 'Test product_name 3', 4, 31.50, 31.50, 'Test description for notes - Record 3')
ON DUPLICATE KEY UPDATE order_id = VALUES(order_id), line_number = VALUES(line_number), sku = VALUES(sku), product_name = VALUES(product_name), quantity = VALUES(quantity), unit_price = VALUES(unit_price), line_total = VALUES(line_total), notes = VALUES(notes);

INSERT INTO order_details (order_id, line_number, sku, product_name, quantity, unit_price, line_total, notes)
VALUES (5, 5, 'Test sku', 'Test product_name 4', 5, 42.00, 42.00, 'Test description for notes - Record 4')
ON DUPLICATE KEY UPDATE order_id = VALUES(order_id), line_number = VALUES(line_number), sku = VALUES(sku), product_name = VALUES(product_name), quantity = VALUES(quantity), unit_price = VALUES(unit_price), line_total = VALUES(line_total), notes = VALUES(notes);


-- ------------------------------------------------------------
-- Model: orders
-- ------------------------------------------------------------

-- Seed data for orders
-- Generated from schema: orders.json

INSERT INTO orders (order_number, customer_name, customer_email, total_amount, payment_status, order_date, notes)
VALUES ('test_order_number_2', 'Test customer_name 2', 'test2@example.com', 21.00, 'pending', '2024-01-02', 'Test description for notes - Record 2')
ON DUPLICATE KEY UPDATE order_number = VALUES(order_number), customer_name = VALUES(customer_name), customer_email = VALUES(customer_email), total_amount = VALUES(total_amount), payment_status = VALUES(payment_status), order_date = VALUES(order_date), notes = VALUES(notes);

INSERT INTO orders (order_number, customer_name, customer_email, total_amount, payment_status, order_date, notes)
VALUES ('test_order_number_3', 'Test customer_name 3', 'test3@example.com', 31.50, 'pending', '2024-01-03', 'Test description for notes - Record 3')
ON DUPLICATE KEY UPDATE order_number = VALUES(order_number), customer_name = VALUES(customer_name), customer_email = VALUES(customer_email), total_amount = VALUES(total_amount), payment_status = VALUES(payment_status), order_date = VALUES(order_date), notes = VALUES(notes);

INSERT INTO orders (order_number, customer_name, customer_email, total_amount, payment_status, order_date, notes)
VALUES ('test_order_number_4', 'Test customer_name 4', 'test4@example.com', 42.00, 'pending', '2024-01-04', 'Test description for notes - Record 4')
ON DUPLICATE KEY UPDATE order_number = VALUES(order_number), customer_name = VALUES(customer_name), customer_email = VALUES(customer_email), total_amount = VALUES(total_amount), payment_status = VALUES(payment_status), order_date = VALUES(order_date), notes = VALUES(notes);


-- ------------------------------------------------------------
-- Model: permissions
-- ------------------------------------------------------------

-- Seed data for permissions
-- Generated from schema: permissions.json

INSERT INTO permissions (slug, name, conditions, description)
VALUES ('test_slug_2', 'Test name 2', '', 'Test description for description - Record 2')
ON DUPLICATE KEY UPDATE slug = VALUES(slug), name = VALUES(name), conditions = VALUES(conditions), description = VALUES(description);

INSERT INTO permissions (slug, name, conditions, description)
VALUES ('test_slug_3', 'Test name 3', '', 'Test description for description - Record 3')
ON DUPLICATE KEY UPDATE slug = VALUES(slug), name = VALUES(name), conditions = VALUES(conditions), description = VALUES(description);

INSERT INTO permissions (slug, name, conditions, description)
VALUES ('test_slug_4', 'Test name 4', '', 'Test description for description - Record 4')
ON DUPLICATE KEY UPDATE slug = VALUES(slug), name = VALUES(name), conditions = VALUES(conditions), description = VALUES(description);


-- Relationship: permissions -> roles
-- Pivot table: permission_roles

INSERT INTO permission_roles (permission_id, role_id)
VALUES (2, 2)
ON DUPLICATE KEY UPDATE permission_id = VALUES(permission_id);

INSERT INTO permission_roles (permission_id, role_id)
VALUES (3, 2)
ON DUPLICATE KEY UPDATE permission_id = VALUES(permission_id);

INSERT INTO permission_roles (permission_id, role_id)
VALUES (3, 3)
ON DUPLICATE KEY UPDATE permission_id = VALUES(permission_id);


-- ------------------------------------------------------------
-- Model: product_categories
-- ------------------------------------------------------------

-- Seed data for product_categories
-- Generated from schema: product_categories.json

INSERT INTO product_categories (product_id, category_id)
VALUES (3, 3)
ON DUPLICATE KEY UPDATE product_id = VALUES(product_id), category_id = VALUES(category_id);

INSERT INTO product_categories (product_id, category_id)
VALUES (4, 4)
ON DUPLICATE KEY UPDATE product_id = VALUES(product_id), category_id = VALUES(category_id);

INSERT INTO product_categories (product_id, category_id)
VALUES (5, 5)
ON DUPLICATE KEY UPDATE product_id = VALUES(product_id), category_id = VALUES(category_id);


-- ------------------------------------------------------------
-- Model: products
-- ------------------------------------------------------------

-- Seed data for products
-- Generated from schema: products.json

INSERT INTO products (name, sku, price, description, is_active)
VALUES ('Test name 2', 'test_sku_2', 21.00, 'Test description for description - Record 2', true)
ON DUPLICATE KEY UPDATE name = VALUES(name), sku = VALUES(sku), price = VALUES(price), description = VALUES(description), is_active = VALUES(is_active);

INSERT INTO products (name, sku, price, description, is_active)
VALUES ('Test name 3', 'test_sku_3', 31.50, 'Test description for description - Record 3', true)
ON DUPLICATE KEY UPDATE name = VALUES(name), sku = VALUES(sku), price = VALUES(price), description = VALUES(description), is_active = VALUES(is_active);

INSERT INTO products (name, sku, price, description, is_active)
VALUES ('Test name 4', 'test_sku_4', 42.00, 'Test description for description - Record 4', true)
ON DUPLICATE KEY UPDATE name = VALUES(name), sku = VALUES(sku), price = VALUES(price), description = VALUES(description), is_active = VALUES(is_active);


-- ------------------------------------------------------------
-- Model: products
-- ------------------------------------------------------------

-- Seed data for products
-- Generated from schema: products.json

INSERT INTO products (name, sku, price, category_id, tags, description, is_active)
VALUES ('Test name 2', 'test_sku_2', 21.00, 3, 'Test tags', 'Test description for description - Record 2', true)
ON DUPLICATE KEY UPDATE name = VALUES(name), sku = VALUES(sku), price = VALUES(price), category_id = VALUES(category_id), tags = VALUES(tags), description = VALUES(description), is_active = VALUES(is_active);

INSERT INTO products (name, sku, price, category_id, tags, description, is_active)
VALUES ('Test name 3', 'test_sku_3', 31.50, 4, 'Test tags', 'Test description for description - Record 3', true)
ON DUPLICATE KEY UPDATE name = VALUES(name), sku = VALUES(sku), price = VALUES(price), category_id = VALUES(category_id), tags = VALUES(tags), description = VALUES(description), is_active = VALUES(is_active);

INSERT INTO products (name, sku, price, category_id, tags, description, is_active)
VALUES ('Test name 4', 'test_sku_4', 42.00, 5, 'Test tags', 'Test description for description - Record 4', true)
ON DUPLICATE KEY UPDATE name = VALUES(name), sku = VALUES(sku), price = VALUES(price), category_id = VALUES(category_id), tags = VALUES(tags), description = VALUES(description), is_active = VALUES(is_active);


-- ------------------------------------------------------------
-- Model: products
-- ------------------------------------------------------------

-- Seed data for products
-- Generated from schema: products.json

INSERT INTO products (name, sku, price, category_id, tags, launch_date, is_active, stock_quantity, description)
VALUES ('Test name 2', 'test_sku_2', 21.00, 3, 'Test tags', '2024-01-02', true, 3, 'Test description for description - Record 2')
ON DUPLICATE KEY UPDATE name = VALUES(name), sku = VALUES(sku), price = VALUES(price), category_id = VALUES(category_id), tags = VALUES(tags), launch_date = VALUES(launch_date), is_active = VALUES(is_active), stock_quantity = VALUES(stock_quantity), description = VALUES(description);

INSERT INTO products (name, sku, price, category_id, tags, launch_date, is_active, stock_quantity, description)
VALUES ('Test name 3', 'test_sku_3', 31.50, 4, 'Test tags', '2024-01-03', true, 4, 'Test description for description - Record 3')
ON DUPLICATE KEY UPDATE name = VALUES(name), sku = VALUES(sku), price = VALUES(price), category_id = VALUES(category_id), tags = VALUES(tags), launch_date = VALUES(launch_date), is_active = VALUES(is_active), stock_quantity = VALUES(stock_quantity), description = VALUES(description);

INSERT INTO products (name, sku, price, category_id, tags, launch_date, is_active, stock_quantity, description)
VALUES ('Test name 4', 'test_sku_4', 42.00, 5, 'Test tags', '2024-01-04', true, 5, 'Test description for description - Record 4')
ON DUPLICATE KEY UPDATE name = VALUES(name), sku = VALUES(sku), price = VALUES(price), category_id = VALUES(category_id), tags = VALUES(tags), launch_date = VALUES(launch_date), is_active = VALUES(is_active), stock_quantity = VALUES(stock_quantity), description = VALUES(description);


-- ------------------------------------------------------------
-- Model: products_optimized
-- ------------------------------------------------------------

-- Seed data for products
-- Generated from schema: products_optimized.json

INSERT INTO products (name, sku, category_id, price, description, is_active, is_featured, stock_status, launch_date, metadata)
VALUES ('Test name 2', 'test_sku_2', 'test_category_id', 21.00, 'Test description for description - Record 2', true, false, true, '2024-01-02', '{}')
ON DUPLICATE KEY UPDATE name = VALUES(name), sku = VALUES(sku), category_id = VALUES(category_id), price = VALUES(price), description = VALUES(description), is_active = VALUES(is_active), is_featured = VALUES(is_featured), stock_status = VALUES(stock_status), launch_date = VALUES(launch_date), metadata = VALUES(metadata);

INSERT INTO products (name, sku, category_id, price, description, is_active, is_featured, stock_status, launch_date, metadata)
VALUES ('Test name 3', 'test_sku_3', 'test_category_id', 31.50, 'Test description for description - Record 3', true, false, true, '2024-01-03', '{}')
ON DUPLICATE KEY UPDATE name = VALUES(name), sku = VALUES(sku), category_id = VALUES(category_id), price = VALUES(price), description = VALUES(description), is_active = VALUES(is_active), is_featured = VALUES(is_featured), stock_status = VALUES(stock_status), launch_date = VALUES(launch_date), metadata = VALUES(metadata);

INSERT INTO products (name, sku, category_id, price, description, is_active, is_featured, stock_status, launch_date, metadata)
VALUES ('Test name 4', 'test_sku_4', 'test_category_id', 42.00, 'Test description for description - Record 4', true, false, true, '2024-01-04', '{}')
ON DUPLICATE KEY UPDATE name = VALUES(name), sku = VALUES(sku), category_id = VALUES(category_id), price = VALUES(price), description = VALUES(description), is_active = VALUES(is_active), is_featured = VALUES(is_featured), stock_status = VALUES(stock_status), launch_date = VALUES(launch_date), metadata = VALUES(metadata);


-- ------------------------------------------------------------
-- Model: products_with_template_file
-- ------------------------------------------------------------

-- Seed data for products
-- Generated from schema: products_with_template_file.json

INSERT INTO products (name, sku, price, description, category_id, is_active)
VALUES ('Test name 2', 'test_sku_2', 21.00, 'Test description for description - Record 2', 3, true)
ON DUPLICATE KEY UPDATE name = VALUES(name), sku = VALUES(sku), price = VALUES(price), description = VALUES(description), category_id = VALUES(category_id), is_active = VALUES(is_active);

INSERT INTO products (name, sku, price, description, category_id, is_active)
VALUES ('Test name 3', 'test_sku_3', 31.50, 'Test description for description - Record 3', 4, true)
ON DUPLICATE KEY UPDATE name = VALUES(name), sku = VALUES(sku), price = VALUES(price), description = VALUES(description), category_id = VALUES(category_id), is_active = VALUES(is_active);

INSERT INTO products (name, sku, price, description, category_id, is_active)
VALUES ('Test name 4', 'test_sku_4', 42.00, 'Test description for description - Record 4', 5, true)
ON DUPLICATE KEY UPDATE name = VALUES(name), sku = VALUES(sku), price = VALUES(price), description = VALUES(description), category_id = VALUES(category_id), is_active = VALUES(is_active);


-- ------------------------------------------------------------
-- Model: products
-- ------------------------------------------------------------

-- Seed data for products
-- Generated from schema: products.json

INSERT INTO products (name, sku, description, price, cost, stock_quantity, category_id, status, featured)
VALUES ('Test name 2', 'test_sku_2', 'Test description for description - Record 2', 21.00, 21.00, 3, 'test_category_id', 'draft', false)
ON DUPLICATE KEY UPDATE name = VALUES(name), sku = VALUES(sku), description = VALUES(description), price = VALUES(price), cost = VALUES(cost), stock_quantity = VALUES(stock_quantity), category_id = VALUES(category_id), status = VALUES(status), featured = VALUES(featured);

INSERT INTO products (name, sku, description, price, cost, stock_quantity, category_id, status, featured)
VALUES ('Test name 3', 'test_sku_3', 'Test description for description - Record 3', 31.50, 31.50, 4, 'test_category_id', 'draft', false)
ON DUPLICATE KEY UPDATE name = VALUES(name), sku = VALUES(sku), description = VALUES(description), price = VALUES(price), cost = VALUES(cost), stock_quantity = VALUES(stock_quantity), category_id = VALUES(category_id), status = VALUES(status), featured = VALUES(featured);

INSERT INTO products (name, sku, description, price, cost, stock_quantity, category_id, status, featured)
VALUES ('Test name 4', 'test_sku_4', 'Test description for description - Record 4', 42.00, 42.00, 5, 'test_category_id', 'draft', false)
ON DUPLICATE KEY UPDATE name = VALUES(name), sku = VALUES(sku), description = VALUES(description), price = VALUES(price), cost = VALUES(cost), stock_quantity = VALUES(stock_quantity), category_id = VALUES(category_id), status = VALUES(status), featured = VALUES(featured);


-- ------------------------------------------------------------
-- Model: products_vue_template
-- ------------------------------------------------------------

-- Seed data for products
-- Generated from schema: products_vue_template.json

INSERT INTO products (name, sku, price, description, category_id, is_active)
VALUES ('Test name 2', 'test_sku_2', 21.00, 'Test description for description - Record 2', 3, true)
ON DUPLICATE KEY UPDATE name = VALUES(name), sku = VALUES(sku), price = VALUES(price), description = VALUES(description), category_id = VALUES(category_id), is_active = VALUES(is_active);

INSERT INTO products (name, sku, price, description, category_id, is_active)
VALUES ('Test name 3', 'test_sku_3', 31.50, 'Test description for description - Record 3', 4, true)
ON DUPLICATE KEY UPDATE name = VALUES(name), sku = VALUES(sku), price = VALUES(price), description = VALUES(description), category_id = VALUES(category_id), is_active = VALUES(is_active);

INSERT INTO products (name, sku, price, description, category_id, is_active)
VALUES ('Test name 4', 'test_sku_4', 42.00, 'Test description for description - Record 4', 5, true)
ON DUPLICATE KEY UPDATE name = VALUES(name), sku = VALUES(sku), price = VALUES(price), description = VALUES(description), category_id = VALUES(category_id), is_active = VALUES(is_active);


-- ------------------------------------------------------------
-- Model: products
-- ------------------------------------------------------------

-- Seed data for products
-- Generated from schema: products.json

INSERT INTO products (name, sku, price, description, category_id, tags, is_active, launch_date, metadata)
VALUES ('Test name 2', 'test_sku_2', 21.00, 'Test description for description - Record 2', 3, 'Test tags', true, '2024-01-02', '{}')
ON DUPLICATE KEY UPDATE name = VALUES(name), sku = VALUES(sku), price = VALUES(price), description = VALUES(description), category_id = VALUES(category_id), tags = VALUES(tags), is_active = VALUES(is_active), launch_date = VALUES(launch_date), metadata = VALUES(metadata);

INSERT INTO products (name, sku, price, description, category_id, tags, is_active, launch_date, metadata)
VALUES ('Test name 3', 'test_sku_3', 31.50, 'Test description for description - Record 3', 4, 'Test tags', true, '2024-01-03', '{}')
ON DUPLICATE KEY UPDATE name = VALUES(name), sku = VALUES(sku), price = VALUES(price), description = VALUES(description), category_id = VALUES(category_id), tags = VALUES(tags), is_active = VALUES(is_active), launch_date = VALUES(launch_date), metadata = VALUES(metadata);

INSERT INTO products (name, sku, price, description, category_id, tags, is_active, launch_date, metadata)
VALUES ('Test name 4', 'test_sku_4', 42.00, 'Test description for description - Record 4', 5, 'Test tags', true, '2024-01-04', '{}')
ON DUPLICATE KEY UPDATE name = VALUES(name), sku = VALUES(sku), price = VALUES(price), description = VALUES(description), category_id = VALUES(category_id), tags = VALUES(tags), is_active = VALUES(is_active), launch_date = VALUES(launch_date), metadata = VALUES(metadata);


-- ------------------------------------------------------------
-- Model: roles
-- ------------------------------------------------------------

-- Seed data for roles
-- Generated from schema: roles.json

INSERT INTO roles (slug, name, description, permission_ids)
VALUES ('test_slug_2', 'Test name 2', 'Test description for description - Record 2', NULL)
ON DUPLICATE KEY UPDATE slug = VALUES(slug), name = VALUES(name), description = VALUES(description), permission_ids = VALUES(permission_ids);

INSERT INTO roles (slug, name, description, permission_ids)
VALUES ('test_slug_3', 'Test name 3', 'Test description for description - Record 3', NULL)
ON DUPLICATE KEY UPDATE slug = VALUES(slug), name = VALUES(name), description = VALUES(description), permission_ids = VALUES(permission_ids);

INSERT INTO roles (slug, name, description, permission_ids)
VALUES ('test_slug_4', 'Test name 4', 'Test description for description - Record 4', NULL)
ON DUPLICATE KEY UPDATE slug = VALUES(slug), name = VALUES(name), description = VALUES(description), permission_ids = VALUES(permission_ids);


-- Relationship: roles -> permissions
-- Pivot table: permission_roles

INSERT INTO permission_roles (role_id, permission_id)
VALUES (2, 2)
ON DUPLICATE KEY UPDATE role_id = VALUES(role_id);

INSERT INTO permission_roles (role_id, permission_id)
VALUES (3, 2)
ON DUPLICATE KEY UPDATE role_id = VALUES(role_id);

INSERT INTO permission_roles (role_id, permission_id)
VALUES (3, 3)
ON DUPLICATE KEY UPDATE role_id = VALUES(role_id);

-- Relationship: roles -> users
-- Pivot table: role_users

INSERT INTO role_users (role_id, user_id)
VALUES (2, 2)
ON DUPLICATE KEY UPDATE role_id = VALUES(role_id);

INSERT INTO role_users (role_id, user_id)
VALUES (3, 2)
ON DUPLICATE KEY UPDATE role_id = VALUES(role_id);

INSERT INTO role_users (role_id, user_id)
VALUES (3, 3)
ON DUPLICATE KEY UPDATE role_id = VALUES(role_id);


-- ------------------------------------------------------------
-- Model: order
-- ------------------------------------------------------------

-- Seed data for orders
-- Generated from schema: order.json

INSERT INTO orders (customer_id, order_number, order_date, status, total_amount, notes, deleted_at)
VALUES ('test_customer_id', 'Test order_number', '2024-01-02', 'pending', 21.00, 'Test description for notes - Record 2', '2024-01-02 12:00:00')
ON DUPLICATE KEY UPDATE customer_id = VALUES(customer_id), order_number = VALUES(order_number), order_date = VALUES(order_date), status = VALUES(status), total_amount = VALUES(total_amount), notes = VALUES(notes), deleted_at = VALUES(deleted_at);

INSERT INTO orders (customer_id, order_number, order_date, status, total_amount, notes, deleted_at)
VALUES ('test_customer_id', 'Test order_number', '2024-01-03', 'pending', 31.50, 'Test description for notes - Record 3', '2024-01-03 12:00:00')
ON DUPLICATE KEY UPDATE customer_id = VALUES(customer_id), order_number = VALUES(order_number), order_date = VALUES(order_date), status = VALUES(status), total_amount = VALUES(total_amount), notes = VALUES(notes), deleted_at = VALUES(deleted_at);

INSERT INTO orders (customer_id, order_number, order_date, status, total_amount, notes, deleted_at)
VALUES ('test_customer_id', 'Test order_number', '2024-01-04', 'pending', 42.00, 'Test description for notes - Record 4', '2024-01-04 12:00:00')
ON DUPLICATE KEY UPDATE customer_id = VALUES(customer_id), order_number = VALUES(order_number), order_date = VALUES(order_date), status = VALUES(status), total_amount = VALUES(total_amount), notes = VALUES(notes), deleted_at = VALUES(deleted_at);


-- ------------------------------------------------------------
-- Model: order_legacy
-- ------------------------------------------------------------

-- Seed data for orders
-- Generated from schema: order_legacy.json

INSERT INTO orders (customer_id, order_number)
VALUES ('test_customer_id', 'Test order_number')
ON DUPLICATE KEY UPDATE customer_id = VALUES(customer_id), order_number = VALUES(order_number);

INSERT INTO orders (customer_id, order_number)
VALUES ('test_customer_id', 'Test order_number')
ON DUPLICATE KEY UPDATE customer_id = VALUES(customer_id), order_number = VALUES(order_number);

INSERT INTO orders (customer_id, order_number)
VALUES ('test_customer_id', 'Test order_number')
ON DUPLICATE KEY UPDATE customer_id = VALUES(customer_id), order_number = VALUES(order_number);


-- ------------------------------------------------------------
-- Model: users
-- ------------------------------------------------------------

-- Seed data for users
-- Generated from schema: users.json

INSERT INTO users (user_name, first_name, last_name, email, locale, group_id, flag_verified, flag_enabled, password)
VALUES ('test_user_name_2', 'Test first_name 2', 'Test last_name 2', 'test_email', 'en_US', 1, true, true, NULL)
ON DUPLICATE KEY UPDATE user_name = VALUES(user_name), first_name = VALUES(first_name), last_name = VALUES(last_name), email = VALUES(email), locale = VALUES(locale), group_id = VALUES(group_id), flag_verified = VALUES(flag_verified), flag_enabled = VALUES(flag_enabled), password = VALUES(password);

INSERT INTO users (user_name, first_name, last_name, email, locale, group_id, flag_verified, flag_enabled, password)
VALUES ('test_user_name_3', 'Test first_name 3', 'Test last_name 3', 'test_email', 'en_US', 1, true, true, NULL)
ON DUPLICATE KEY UPDATE user_name = VALUES(user_name), first_name = VALUES(first_name), last_name = VALUES(last_name), email = VALUES(email), locale = VALUES(locale), group_id = VALUES(group_id), flag_verified = VALUES(flag_verified), flag_enabled = VALUES(flag_enabled), password = VALUES(password);

INSERT INTO users (user_name, first_name, last_name, email, locale, group_id, flag_verified, flag_enabled, password)
VALUES ('test_user_name_4', 'Test first_name 4', 'Test last_name 4', 'test_email', 'en_US', 1, true, true, NULL)
ON DUPLICATE KEY UPDATE user_name = VALUES(user_name), first_name = VALUES(first_name), last_name = VALUES(last_name), email = VALUES(email), locale = VALUES(locale), group_id = VALUES(group_id), flag_verified = VALUES(flag_verified), flag_enabled = VALUES(flag_enabled), password = VALUES(password);


-- Relationship: users -> roles
-- Pivot table: role_users

INSERT INTO role_users (user_id, role_id)
VALUES (2, 2)
ON DUPLICATE KEY UPDATE user_id = VALUES(user_id);

INSERT INTO role_users (user_id, role_id)
VALUES (3, 2)
ON DUPLICATE KEY UPDATE user_id = VALUES(user_id);

INSERT INTO role_users (user_id, role_id)
VALUES (3, 3)
ON DUPLICATE KEY UPDATE user_id = VALUES(user_id);


-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS=1;

-- ═══════════════════════════════════════════════════════════════
-- Successfully generated 21 model seed data sets
-- ═══════════════════════════════════════════════════════════════
-- REMINDER: This seed data is designed to run AFTER admin user creation
--           and BEFORE unauthenticated path testing.
--
-- Protected Records:
--   - User ID 1 (admin user)
--   - Group ID 1 (admin group)
--
-- Test Data Range: ID >= 2 (safe for DELETE/DISABLE operations)
-- ═══════════════════════════════════════════════════════════════