-- ═══════════════════════════════════════════════════════════════
-- CRUD6 Integration Test Seed Data
-- Generated from JSON schemas
-- ═══════════════════════════════════════════════════════════════
--
-- ⚠️ THIS FILE IS GENERATED - DO NOT EDIT MANUALLY
-- 
-- This seed data is automatically generated from JSON schema files in
-- examples/schema/ directory. To update this file, regenerate it using:
--   php scripts/generate-test-data.php
-- 
-- The seed data is schema-driven, meaning:
-- - Data structure comes from schema field definitions
-- - Relationships come from schema relationship definitions
-- - All models are treated generically based on their schemas
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
-- Generated: 2025-12-12T03:29:28.182Z
-- Source: Schema files in examples/schema/
-- ═══════════════════════════════════════════════════════════════

-- Disable foreign key checks for seeding
SET FOREIGN_KEY_CHECKS=0;

-- ------------------------------------------------------------
-- Model: activities
-- ------------------------------------------------------------

-- Seed data for activities
-- Generated from schema: activities.json

INSERT INTO `activities` (`ip_address`, `user_id`, `type`, `occurred_at`, `description`)
VALUES ('192.168.2.102', 2, 'type_c', '2024-01-02 12:00:00', 'Test description for description - Record 2') AS new_values
ON DUPLICATE KEY UPDATE `ip_address` = new_values.`ip_address`, `user_id` = new_values.`user_id`, `type` = new_values.`type`, `occurred_at` = new_values.`occurred_at`, `description` = new_values.`description`;

INSERT INTO `activities` (`ip_address`, `user_id`, `type`, `occurred_at`, `description`)
VALUES ('192.168.3.103', 3, 'type_a', '2024-01-03 12:00:00', 'Test description for description - Record 3') AS new_values
ON DUPLICATE KEY UPDATE `ip_address` = new_values.`ip_address`, `user_id` = new_values.`user_id`, `type` = new_values.`type`, `occurred_at` = new_values.`occurred_at`, `description` = new_values.`description`;

INSERT INTO `activities` (`ip_address`, `user_id`, `type`, `occurred_at`, `description`)
VALUES ('192.168.4.104', 4, 'type_b', '2024-01-04 12:00:00', 'Test description for description - Record 4') AS new_values
ON DUPLICATE KEY UPDATE `ip_address` = new_values.`ip_address`, `user_id` = new_values.`user_id`, `type` = new_values.`type`, `occurred_at` = new_values.`occurred_at`, `description` = new_values.`description`;


-- ------------------------------------------------------------
-- Model: categories
-- ------------------------------------------------------------

-- Seed data for categories
-- Generated from schema: categories.json

INSERT INTO `categories` (`name`, `slug`, `description`, `icon`, `is_active`)
VALUES ('Name2', 'test-slug-2', 'Test description for description - Record 2', 'fas fa-cog', true) AS new_values
ON DUPLICATE KEY UPDATE `name` = new_values.`name`, `slug` = new_values.`slug`, `description` = new_values.`description`, `icon` = new_values.`icon`, `is_active` = new_values.`is_active`;

INSERT INTO `categories` (`name`, `slug`, `description`, `icon`, `is_active`)
VALUES ('Name3', 'test-slug-3', 'Test description for description - Record 3', 'fas fa-star', true) AS new_values
ON DUPLICATE KEY UPDATE `name` = new_values.`name`, `slug` = new_values.`slug`, `description` = new_values.`description`, `icon` = new_values.`icon`, `is_active` = new_values.`is_active`;

INSERT INTO `categories` (`name`, `slug`, `description`, `icon`, `is_active`)
VALUES ('Name4', 'test-slug-4', 'Test description for description - Record 4', 'fas fa-heart', true) AS new_values
ON DUPLICATE KEY UPDATE `name` = new_values.`name`, `slug` = new_values.`slug`, `description` = new_values.`description`, `icon` = new_values.`icon`, `is_active` = new_values.`is_active`;


-- ------------------------------------------------------------
-- Model: contacts
-- ------------------------------------------------------------

-- Seed data for contacts
-- Generated from schema: contacts.json

INSERT INTO `contacts` (`first_name`, `last_name`, `email`, `phone`, `mobile`, `website`, `address`, `city`, `state`, `zip`, `notes`, `bio`, `company`, `position`, `is_active`, `newsletter_opt_in`)
VALUES ('Name2', 'Name2', 'test2@example.com', '555-000-002', '555-000-002', 'https://example2.com', '102 Main St', 'Chicago', 'TX', '10002', 'Test description for notes - Record 2', 'Test description for bio - Record 2', 'Company 2', 'Position 2', true, false) AS new_values
ON DUPLICATE KEY UPDATE `first_name` = new_values.`first_name`, `last_name` = new_values.`last_name`, `email` = new_values.`email`, `phone` = new_values.`phone`, `mobile` = new_values.`mobile`, `website` = new_values.`website`, `address` = new_values.`address`, `city` = new_values.`city`, `state` = new_values.`state`, `zip` = new_values.`zip`, `notes` = new_values.`notes`, `bio` = new_values.`bio`, `company` = new_values.`company`, `position` = new_values.`position`, `is_active` = new_values.`is_active`, `newsletter_opt_in` = new_values.`newsletter_opt_in`;

INSERT INTO `contacts` (`first_name`, `last_name`, `email`, `phone`, `mobile`, `website`, `address`, `city`, `state`, `zip`, `notes`, `bio`, `company`, `position`, `is_active`, `newsletter_opt_in`)
VALUES ('Name3', 'Name3', 'test3@example.com', '555-000-003', '555-000-003', 'https://example3.com', '103 Main St', 'Houston', 'FL', '10003', 'Test description for notes - Record 3', 'Test description for bio - Record 3', 'Company 3', 'Position 3', true, false) AS new_values
ON DUPLICATE KEY UPDATE `first_name` = new_values.`first_name`, `last_name` = new_values.`last_name`, `email` = new_values.`email`, `phone` = new_values.`phone`, `mobile` = new_values.`mobile`, `website` = new_values.`website`, `address` = new_values.`address`, `city` = new_values.`city`, `state` = new_values.`state`, `zip` = new_values.`zip`, `notes` = new_values.`notes`, `bio` = new_values.`bio`, `company` = new_values.`company`, `position` = new_values.`position`, `is_active` = new_values.`is_active`, `newsletter_opt_in` = new_values.`newsletter_opt_in`;

INSERT INTO `contacts` (`first_name`, `last_name`, `email`, `phone`, `mobile`, `website`, `address`, `city`, `state`, `zip`, `notes`, `bio`, `company`, `position`, `is_active`, `newsletter_opt_in`)
VALUES ('Name4', 'Name4', 'test4@example.com', '555-000-004', '555-000-004', 'https://example4.com', '104 Main St', 'Phoenix', 'IL', '10004', 'Test description for notes - Record 4', 'Test description for bio - Record 4', 'Company 4', 'Position 4', true, false) AS new_values
ON DUPLICATE KEY UPDATE `first_name` = new_values.`first_name`, `last_name` = new_values.`last_name`, `email` = new_values.`email`, `phone` = new_values.`phone`, `mobile` = new_values.`mobile`, `website` = new_values.`website`, `address` = new_values.`address`, `city` = new_values.`city`, `state` = new_values.`state`, `zip` = new_values.`zip`, `notes` = new_values.`notes`, `bio` = new_values.`bio`, `company` = new_values.`company`, `position` = new_values.`position`, `is_active` = new_values.`is_active`, `newsletter_opt_in` = new_values.`newsletter_opt_in`;


-- ------------------------------------------------------------
-- Model: tasks
-- ------------------------------------------------------------

-- Seed data for tasks
-- Generated from schema: tasks.json

INSERT INTO `tasks` (`title`, `description`, `status`, `priority`, `assigned_to`, `due_date`)
VALUES ('Title 2', 'Test description for description - Record 2', 'pending', 'medium', 'Value2', '2024-01-02') AS new_values
ON DUPLICATE KEY UPDATE `title` = new_values.`title`, `description` = new_values.`description`, `status` = new_values.`status`, `priority` = new_values.`priority`, `assigned_to` = new_values.`assigned_to`, `due_date` = new_values.`due_date`;

INSERT INTO `tasks` (`title`, `description`, `status`, `priority`, `assigned_to`, `due_date`)
VALUES ('Title 3', 'Test description for description - Record 3', 'pending', 'medium', 'Value3', '2024-01-03') AS new_values
ON DUPLICATE KEY UPDATE `title` = new_values.`title`, `description` = new_values.`description`, `status` = new_values.`status`, `priority` = new_values.`priority`, `assigned_to` = new_values.`assigned_to`, `due_date` = new_values.`due_date`;

INSERT INTO `tasks` (`title`, `description`, `status`, `priority`, `assigned_to`, `due_date`)
VALUES ('Title 4', 'Test description for description - Record 4', 'pending', 'medium', 'Value4', '2024-01-04') AS new_values
ON DUPLICATE KEY UPDATE `title` = new_values.`title`, `description` = new_values.`description`, `status` = new_values.`status`, `priority` = new_values.`priority`, `assigned_to` = new_values.`assigned_to`, `due_date` = new_values.`due_date`;


-- ------------------------------------------------------------
-- Model: groups
-- ------------------------------------------------------------

-- Seed data for groups
-- Generated from schema: groups.json

INSERT INTO `groups` (`slug`, `name`, `description`, `icon`)
VALUES ('test_slug_2', 'Name2', 'Test description for description - Record 2', 'fas fa-user') AS new_values
ON DUPLICATE KEY UPDATE `slug` = new_values.`slug`, `name` = new_values.`name`, `description` = new_values.`description`, `icon` = new_values.`icon`;

INSERT INTO `groups` (`slug`, `name`, `description`, `icon`)
VALUES ('test_slug_3', 'Name3', 'Test description for description - Record 3', 'fas fa-user') AS new_values
ON DUPLICATE KEY UPDATE `slug` = new_values.`slug`, `name` = new_values.`name`, `description` = new_values.`description`, `icon` = new_values.`icon`;

INSERT INTO `groups` (`slug`, `name`, `description`, `icon`)
VALUES ('test_slug_4', 'Name4', 'Test description for description - Record 4', 'fas fa-user') AS new_values
ON DUPLICATE KEY UPDATE `slug` = new_values.`slug`, `name` = new_values.`name`, `description` = new_values.`description`, `icon` = new_values.`icon`;


-- ------------------------------------------------------------
-- Model: order_details
-- ------------------------------------------------------------

-- Seed data for order_details
-- Generated from schema: order_details.json

INSERT INTO `order_details` (`order_id`, `line_number`, `sku`, `product_name`, `quantity`, `unit_price`, `line_total`, `notes`)
VALUES (2, 2, 'Value2', 'Name2', 2, 21.00, 21.00, 'Test description for notes - Record 2') AS new_values
ON DUPLICATE KEY UPDATE `order_id` = new_values.`order_id`, `line_number` = new_values.`line_number`, `sku` = new_values.`sku`, `product_name` = new_values.`product_name`, `quantity` = new_values.`quantity`, `unit_price` = new_values.`unit_price`, `line_total` = new_values.`line_total`, `notes` = new_values.`notes`;

INSERT INTO `order_details` (`order_id`, `line_number`, `sku`, `product_name`, `quantity`, `unit_price`, `line_total`, `notes`)
VALUES (3, 3, 'Value3', 'Name3', 3, 31.50, 31.50, 'Test description for notes - Record 3') AS new_values
ON DUPLICATE KEY UPDATE `order_id` = new_values.`order_id`, `line_number` = new_values.`line_number`, `sku` = new_values.`sku`, `product_name` = new_values.`product_name`, `quantity` = new_values.`quantity`, `unit_price` = new_values.`unit_price`, `line_total` = new_values.`line_total`, `notes` = new_values.`notes`;

INSERT INTO `order_details` (`order_id`, `line_number`, `sku`, `product_name`, `quantity`, `unit_price`, `line_total`, `notes`)
VALUES (4, 4, 'Value4', 'Name4', 4, 42.00, 42.00, 'Test description for notes - Record 4') AS new_values
ON DUPLICATE KEY UPDATE `order_id` = new_values.`order_id`, `line_number` = new_values.`line_number`, `sku` = new_values.`sku`, `product_name` = new_values.`product_name`, `quantity` = new_values.`quantity`, `unit_price` = new_values.`unit_price`, `line_total` = new_values.`line_total`, `notes` = new_values.`notes`;


-- ------------------------------------------------------------
-- Model: orders
-- ------------------------------------------------------------

-- Seed data for orders
-- Generated from schema: orders.json

INSERT INTO `orders` (`order_number`, `customer_name`, `customer_email`, `total_amount`, `payment_status`, `order_date`, `notes`)
VALUES ('test_order_number_2', 'Name2', 'test2@example.com', 21.00, 'pending', '2024-01-02', 'Test description for notes - Record 2') AS new_values
ON DUPLICATE KEY UPDATE `order_number` = new_values.`order_number`, `customer_name` = new_values.`customer_name`, `customer_email` = new_values.`customer_email`, `total_amount` = new_values.`total_amount`, `payment_status` = new_values.`payment_status`, `order_date` = new_values.`order_date`, `notes` = new_values.`notes`;

INSERT INTO `orders` (`order_number`, `customer_name`, `customer_email`, `total_amount`, `payment_status`, `order_date`, `notes`)
VALUES ('test_order_number_3', 'Name3', 'test3@example.com', 31.50, 'pending', '2024-01-03', 'Test description for notes - Record 3') AS new_values
ON DUPLICATE KEY UPDATE `order_number` = new_values.`order_number`, `customer_name` = new_values.`customer_name`, `customer_email` = new_values.`customer_email`, `total_amount` = new_values.`total_amount`, `payment_status` = new_values.`payment_status`, `order_date` = new_values.`order_date`, `notes` = new_values.`notes`;

INSERT INTO `orders` (`order_number`, `customer_name`, `customer_email`, `total_amount`, `payment_status`, `order_date`, `notes`)
VALUES ('test_order_number_4', 'Name4', 'test4@example.com', 42.00, 'pending', '2024-01-04', 'Test description for notes - Record 4') AS new_values
ON DUPLICATE KEY UPDATE `order_number` = new_values.`order_number`, `customer_name` = new_values.`customer_name`, `customer_email` = new_values.`customer_email`, `total_amount` = new_values.`total_amount`, `payment_status` = new_values.`payment_status`, `order_date` = new_values.`order_date`, `notes` = new_values.`notes`;


-- ------------------------------------------------------------
-- Model: permissions
-- ------------------------------------------------------------

-- Seed data for permissions
-- Generated from schema: permissions.json

INSERT INTO `permissions` (`slug`, `name`, `conditions`, `description`)
VALUES ('test_slug_2', 'Name2', '', 'Test description for description - Record 2') AS new_values
ON DUPLICATE KEY UPDATE `slug` = new_values.`slug`, `name` = new_values.`name`, `conditions` = new_values.`conditions`, `description` = new_values.`description`;

INSERT INTO `permissions` (`slug`, `name`, `conditions`, `description`)
VALUES ('test_slug_3', 'Name3', '', 'Test description for description - Record 3') AS new_values
ON DUPLICATE KEY UPDATE `slug` = new_values.`slug`, `name` = new_values.`name`, `conditions` = new_values.`conditions`, `description` = new_values.`description`;

INSERT INTO `permissions` (`slug`, `name`, `conditions`, `description`)
VALUES ('test_slug_4', 'Name4', '', 'Test description for description - Record 4') AS new_values
ON DUPLICATE KEY UPDATE `slug` = new_values.`slug`, `name` = new_values.`name`, `conditions` = new_values.`conditions`, `description` = new_values.`description`;


-- Relationship: permissions -> roles
-- Pivot table: permission_roles

INSERT INTO `permission_roles` (`permission_id`, `role_id`)
VALUES (2, 2) AS new_rel
ON DUPLICATE KEY UPDATE `permission_id` = new_rel.`permission_id`;

INSERT INTO `permission_roles` (`permission_id`, `role_id`)
VALUES (3, 2) AS new_rel
ON DUPLICATE KEY UPDATE `permission_id` = new_rel.`permission_id`;

INSERT INTO `permission_roles` (`permission_id`, `role_id`)
VALUES (3, 3) AS new_rel
ON DUPLICATE KEY UPDATE `permission_id` = new_rel.`permission_id`;


-- ------------------------------------------------------------
-- Model: product_categories
-- ------------------------------------------------------------

-- Seed data for product_categories
-- Generated from schema: product_categories.json

INSERT INTO `product_categories` (`product_id`, `category_id`)
VALUES (2, 2) AS new_values
ON DUPLICATE KEY UPDATE `product_id` = new_values.`product_id`, `category_id` = new_values.`category_id`;

INSERT INTO `product_categories` (`product_id`, `category_id`)
VALUES (3, 3) AS new_values
ON DUPLICATE KEY UPDATE `product_id` = new_values.`product_id`, `category_id` = new_values.`category_id`;

INSERT INTO `product_categories` (`product_id`, `category_id`)
VALUES (4, 4) AS new_values
ON DUPLICATE KEY UPDATE `product_id` = new_values.`product_id`, `category_id` = new_values.`category_id`;


-- ------------------------------------------------------------
-- Model: products
-- ------------------------------------------------------------

-- Seed data for products
-- Generated from schema: products.json

INSERT INTO `products` (`name`, `sku`, `price`, `description`, `category_id`, `tags`, `is_active`, `launch_date`, `metadata`)
VALUES ('Name2', 'test_sku_2', 21.00, 'Test description for description - Record 2', 2, 'Value2', true, '2024-01-02', '{}') AS new_values
ON DUPLICATE KEY UPDATE `name` = new_values.`name`, `sku` = new_values.`sku`, `price` = new_values.`price`, `description` = new_values.`description`, `category_id` = new_values.`category_id`, `tags` = new_values.`tags`, `is_active` = new_values.`is_active`, `launch_date` = new_values.`launch_date`, `metadata` = new_values.`metadata`;

INSERT INTO `products` (`name`, `sku`, `price`, `description`, `category_id`, `tags`, `is_active`, `launch_date`, `metadata`)
VALUES ('Name3', 'test_sku_3', 31.50, 'Test description for description - Record 3', 3, 'Value3', true, '2024-01-03', '{}') AS new_values
ON DUPLICATE KEY UPDATE `name` = new_values.`name`, `sku` = new_values.`sku`, `price` = new_values.`price`, `description` = new_values.`description`, `category_id` = new_values.`category_id`, `tags` = new_values.`tags`, `is_active` = new_values.`is_active`, `launch_date` = new_values.`launch_date`, `metadata` = new_values.`metadata`;

INSERT INTO `products` (`name`, `sku`, `price`, `description`, `category_id`, `tags`, `is_active`, `launch_date`, `metadata`)
VALUES ('Name4', 'test_sku_4', 42.00, 'Test description for description - Record 4', 4, 'Value4', true, '2024-01-04', '{}') AS new_values
ON DUPLICATE KEY UPDATE `name` = new_values.`name`, `sku` = new_values.`sku`, `price` = new_values.`price`, `description` = new_values.`description`, `category_id` = new_values.`category_id`, `tags` = new_values.`tags`, `is_active` = new_values.`is_active`, `launch_date` = new_values.`launch_date`, `metadata` = new_values.`metadata`;


-- ------------------------------------------------------------
-- Model: products
-- ------------------------------------------------------------

-- Seed data for products
-- Generated from schema: products.json

INSERT INTO `products` (`name`, `sku`, `price`, `description`, `category_id`, `tags`, `is_active`, `launch_date`, `metadata`)
VALUES ('Name2', 'test_sku_2', 21.00, 'Test description for description - Record 2', 2, 'Value2', true, '2024-01-02', '{}') AS new_values
ON DUPLICATE KEY UPDATE `name` = new_values.`name`, `sku` = new_values.`sku`, `price` = new_values.`price`, `description` = new_values.`description`, `category_id` = new_values.`category_id`, `tags` = new_values.`tags`, `is_active` = new_values.`is_active`, `launch_date` = new_values.`launch_date`, `metadata` = new_values.`metadata`;

INSERT INTO `products` (`name`, `sku`, `price`, `description`, `category_id`, `tags`, `is_active`, `launch_date`, `metadata`)
VALUES ('Name3', 'test_sku_3', 31.50, 'Test description for description - Record 3', 3, 'Value3', true, '2024-01-03', '{}') AS new_values
ON DUPLICATE KEY UPDATE `name` = new_values.`name`, `sku` = new_values.`sku`, `price` = new_values.`price`, `description` = new_values.`description`, `category_id` = new_values.`category_id`, `tags` = new_values.`tags`, `is_active` = new_values.`is_active`, `launch_date` = new_values.`launch_date`, `metadata` = new_values.`metadata`;

INSERT INTO `products` (`name`, `sku`, `price`, `description`, `category_id`, `tags`, `is_active`, `launch_date`, `metadata`)
VALUES ('Name4', 'test_sku_4', 42.00, 'Test description for description - Record 4', 4, 'Value4', true, '2024-01-04', '{}') AS new_values
ON DUPLICATE KEY UPDATE `name` = new_values.`name`, `sku` = new_values.`sku`, `price` = new_values.`price`, `description` = new_values.`description`, `category_id` = new_values.`category_id`, `tags` = new_values.`tags`, `is_active` = new_values.`is_active`, `launch_date` = new_values.`launch_date`, `metadata` = new_values.`metadata`;


-- ------------------------------------------------------------
-- Model: products
-- ------------------------------------------------------------

-- Seed data for products
-- Generated from schema: products.json

INSERT INTO `products` (`name`, `sku`, `price`, `description`, `category_id`, `tags`, `is_active`, `launch_date`, `metadata`)
VALUES ('Name2', 'test_sku_2', 21.00, 'Test description for description - Record 2', 2, 'Value2', true, '2024-01-02', '{}') AS new_values
ON DUPLICATE KEY UPDATE `name` = new_values.`name`, `sku` = new_values.`sku`, `price` = new_values.`price`, `description` = new_values.`description`, `category_id` = new_values.`category_id`, `tags` = new_values.`tags`, `is_active` = new_values.`is_active`, `launch_date` = new_values.`launch_date`, `metadata` = new_values.`metadata`;

INSERT INTO `products` (`name`, `sku`, `price`, `description`, `category_id`, `tags`, `is_active`, `launch_date`, `metadata`)
VALUES ('Name3', 'test_sku_3', 31.50, 'Test description for description - Record 3', 3, 'Value3', true, '2024-01-03', '{}') AS new_values
ON DUPLICATE KEY UPDATE `name` = new_values.`name`, `sku` = new_values.`sku`, `price` = new_values.`price`, `description` = new_values.`description`, `category_id` = new_values.`category_id`, `tags` = new_values.`tags`, `is_active` = new_values.`is_active`, `launch_date` = new_values.`launch_date`, `metadata` = new_values.`metadata`;

INSERT INTO `products` (`name`, `sku`, `price`, `description`, `category_id`, `tags`, `is_active`, `launch_date`, `metadata`)
VALUES ('Name4', 'test_sku_4', 42.00, 'Test description for description - Record 4', 4, 'Value4', true, '2024-01-04', '{}') AS new_values
ON DUPLICATE KEY UPDATE `name` = new_values.`name`, `sku` = new_values.`sku`, `price` = new_values.`price`, `description` = new_values.`description`, `category_id` = new_values.`category_id`, `tags` = new_values.`tags`, `is_active` = new_values.`is_active`, `launch_date` = new_values.`launch_date`, `metadata` = new_values.`metadata`;


-- ------------------------------------------------------------
-- Model: products_optimized
-- ------------------------------------------------------------

-- Seed data for products
-- Generated from schema: products_optimized.json

INSERT INTO `products` (`name`, `sku`, `category_id`, `price`, `description`, `tags`, `is_active`, `launch_date`, `metadata`)
VALUES ('Name2', 'test_sku_2', 2, 21.00, 'Test description for description - Record 2', 'Value2', true, '2024-01-02', '{}') AS new_values
ON DUPLICATE KEY UPDATE `name` = new_values.`name`, `sku` = new_values.`sku`, `category_id` = new_values.`category_id`, `price` = new_values.`price`, `description` = new_values.`description`, `tags` = new_values.`tags`, `is_active` = new_values.`is_active`, `launch_date` = new_values.`launch_date`, `metadata` = new_values.`metadata`;

INSERT INTO `products` (`name`, `sku`, `category_id`, `price`, `description`, `tags`, `is_active`, `launch_date`, `metadata`)
VALUES ('Name3', 'test_sku_3', 3, 31.50, 'Test description for description - Record 3', 'Value3', true, '2024-01-03', '{}') AS new_values
ON DUPLICATE KEY UPDATE `name` = new_values.`name`, `sku` = new_values.`sku`, `category_id` = new_values.`category_id`, `price` = new_values.`price`, `description` = new_values.`description`, `tags` = new_values.`tags`, `is_active` = new_values.`is_active`, `launch_date` = new_values.`launch_date`, `metadata` = new_values.`metadata`;

INSERT INTO `products` (`name`, `sku`, `category_id`, `price`, `description`, `tags`, `is_active`, `launch_date`, `metadata`)
VALUES ('Name4', 'test_sku_4', 4, 42.00, 'Test description for description - Record 4', 'Value4', true, '2024-01-04', '{}') AS new_values
ON DUPLICATE KEY UPDATE `name` = new_values.`name`, `sku` = new_values.`sku`, `category_id` = new_values.`category_id`, `price` = new_values.`price`, `description` = new_values.`description`, `tags` = new_values.`tags`, `is_active` = new_values.`is_active`, `launch_date` = new_values.`launch_date`, `metadata` = new_values.`metadata`;


-- ------------------------------------------------------------
-- Model: products_with_template_file
-- ------------------------------------------------------------

-- Seed data for products
-- Generated from schema: products_with_template_file.json

INSERT INTO `products` (`name`, `sku`, `price`, `description`, `category_id`, `tags`, `is_active`, `launch_date`, `metadata`)
VALUES ('Name2', 'test_sku_2', 21.00, 'Test description for description - Record 2', 2, 'Value2', true, '2024-01-02', '{}') AS new_values
ON DUPLICATE KEY UPDATE `name` = new_values.`name`, `sku` = new_values.`sku`, `price` = new_values.`price`, `description` = new_values.`description`, `category_id` = new_values.`category_id`, `tags` = new_values.`tags`, `is_active` = new_values.`is_active`, `launch_date` = new_values.`launch_date`, `metadata` = new_values.`metadata`;

INSERT INTO `products` (`name`, `sku`, `price`, `description`, `category_id`, `tags`, `is_active`, `launch_date`, `metadata`)
VALUES ('Name3', 'test_sku_3', 31.50, 'Test description for description - Record 3', 3, 'Value3', true, '2024-01-03', '{}') AS new_values
ON DUPLICATE KEY UPDATE `name` = new_values.`name`, `sku` = new_values.`sku`, `price` = new_values.`price`, `description` = new_values.`description`, `category_id` = new_values.`category_id`, `tags` = new_values.`tags`, `is_active` = new_values.`is_active`, `launch_date` = new_values.`launch_date`, `metadata` = new_values.`metadata`;

INSERT INTO `products` (`name`, `sku`, `price`, `description`, `category_id`, `tags`, `is_active`, `launch_date`, `metadata`)
VALUES ('Name4', 'test_sku_4', 42.00, 'Test description for description - Record 4', 4, 'Value4', true, '2024-01-04', '{}') AS new_values
ON DUPLICATE KEY UPDATE `name` = new_values.`name`, `sku` = new_values.`sku`, `price` = new_values.`price`, `description` = new_values.`description`, `category_id` = new_values.`category_id`, `tags` = new_values.`tags`, `is_active` = new_values.`is_active`, `launch_date` = new_values.`launch_date`, `metadata` = new_values.`metadata`;


-- ------------------------------------------------------------
-- Model: products
-- ------------------------------------------------------------

-- Seed data for products
-- Generated from schema: products.json

INSERT INTO `products` (`name`, `sku`, `price`, `description`, `category_id`, `tags`, `is_active`, `launch_date`, `metadata`)
VALUES ('Name2', 'test_sku_2', 21.00, 'Test description for description - Record 2', 2, 'Value2', true, '2024-01-02', '{}') AS new_values
ON DUPLICATE KEY UPDATE `name` = new_values.`name`, `sku` = new_values.`sku`, `price` = new_values.`price`, `description` = new_values.`description`, `category_id` = new_values.`category_id`, `tags` = new_values.`tags`, `is_active` = new_values.`is_active`, `launch_date` = new_values.`launch_date`, `metadata` = new_values.`metadata`;

INSERT INTO `products` (`name`, `sku`, `price`, `description`, `category_id`, `tags`, `is_active`, `launch_date`, `metadata`)
VALUES ('Name3', 'test_sku_3', 31.50, 'Test description for description - Record 3', 3, 'Value3', true, '2024-01-03', '{}') AS new_values
ON DUPLICATE KEY UPDATE `name` = new_values.`name`, `sku` = new_values.`sku`, `price` = new_values.`price`, `description` = new_values.`description`, `category_id` = new_values.`category_id`, `tags` = new_values.`tags`, `is_active` = new_values.`is_active`, `launch_date` = new_values.`launch_date`, `metadata` = new_values.`metadata`;

INSERT INTO `products` (`name`, `sku`, `price`, `description`, `category_id`, `tags`, `is_active`, `launch_date`, `metadata`)
VALUES ('Name4', 'test_sku_4', 42.00, 'Test description for description - Record 4', 4, 'Value4', true, '2024-01-04', '{}') AS new_values
ON DUPLICATE KEY UPDATE `name` = new_values.`name`, `sku` = new_values.`sku`, `price` = new_values.`price`, `description` = new_values.`description`, `category_id` = new_values.`category_id`, `tags` = new_values.`tags`, `is_active` = new_values.`is_active`, `launch_date` = new_values.`launch_date`, `metadata` = new_values.`metadata`;


-- ------------------------------------------------------------
-- Model: products_vue_template
-- ------------------------------------------------------------

-- Seed data for products
-- Generated from schema: products_vue_template.json

INSERT INTO `products` (`name`, `sku`, `price`, `description`, `category_id`, `tags`, `is_active`, `launch_date`, `metadata`)
VALUES ('Name2', 'test_sku_2', 21.00, 'Test description for description - Record 2', 2, 'Value2', true, '2024-01-02', '{}') AS new_values
ON DUPLICATE KEY UPDATE `name` = new_values.`name`, `sku` = new_values.`sku`, `price` = new_values.`price`, `description` = new_values.`description`, `category_id` = new_values.`category_id`, `tags` = new_values.`tags`, `is_active` = new_values.`is_active`, `launch_date` = new_values.`launch_date`, `metadata` = new_values.`metadata`;

INSERT INTO `products` (`name`, `sku`, `price`, `description`, `category_id`, `tags`, `is_active`, `launch_date`, `metadata`)
VALUES ('Name3', 'test_sku_3', 31.50, 'Test description for description - Record 3', 3, 'Value3', true, '2024-01-03', '{}') AS new_values
ON DUPLICATE KEY UPDATE `name` = new_values.`name`, `sku` = new_values.`sku`, `price` = new_values.`price`, `description` = new_values.`description`, `category_id` = new_values.`category_id`, `tags` = new_values.`tags`, `is_active` = new_values.`is_active`, `launch_date` = new_values.`launch_date`, `metadata` = new_values.`metadata`;

INSERT INTO `products` (`name`, `sku`, `price`, `description`, `category_id`, `tags`, `is_active`, `launch_date`, `metadata`)
VALUES ('Name4', 'test_sku_4', 42.00, 'Test description for description - Record 4', 4, 'Value4', true, '2024-01-04', '{}') AS new_values
ON DUPLICATE KEY UPDATE `name` = new_values.`name`, `sku` = new_values.`sku`, `price` = new_values.`price`, `description` = new_values.`description`, `category_id` = new_values.`category_id`, `tags` = new_values.`tags`, `is_active` = new_values.`is_active`, `launch_date` = new_values.`launch_date`, `metadata` = new_values.`metadata`;


-- ------------------------------------------------------------
-- Model: products
-- ------------------------------------------------------------

-- Seed data for products
-- Generated from schema: products.json

INSERT INTO `products` (`name`, `sku`, `price`, `description`, `category_id`, `tags`, `is_active`, `launch_date`, `metadata`)
VALUES ('Name2', 'test_sku_2', 21.00, 'Test description for description - Record 2', 2, 'Value2', true, '2024-01-02', '{}') AS new_values
ON DUPLICATE KEY UPDATE `name` = new_values.`name`, `sku` = new_values.`sku`, `price` = new_values.`price`, `description` = new_values.`description`, `category_id` = new_values.`category_id`, `tags` = new_values.`tags`, `is_active` = new_values.`is_active`, `launch_date` = new_values.`launch_date`, `metadata` = new_values.`metadata`;

INSERT INTO `products` (`name`, `sku`, `price`, `description`, `category_id`, `tags`, `is_active`, `launch_date`, `metadata`)
VALUES ('Name3', 'test_sku_3', 31.50, 'Test description for description - Record 3', 3, 'Value3', true, '2024-01-03', '{}') AS new_values
ON DUPLICATE KEY UPDATE `name` = new_values.`name`, `sku` = new_values.`sku`, `price` = new_values.`price`, `description` = new_values.`description`, `category_id` = new_values.`category_id`, `tags` = new_values.`tags`, `is_active` = new_values.`is_active`, `launch_date` = new_values.`launch_date`, `metadata` = new_values.`metadata`;

INSERT INTO `products` (`name`, `sku`, `price`, `description`, `category_id`, `tags`, `is_active`, `launch_date`, `metadata`)
VALUES ('Name4', 'test_sku_4', 42.00, 'Test description for description - Record 4', 4, 'Value4', true, '2024-01-04', '{}') AS new_values
ON DUPLICATE KEY UPDATE `name` = new_values.`name`, `sku` = new_values.`sku`, `price` = new_values.`price`, `description` = new_values.`description`, `category_id` = new_values.`category_id`, `tags` = new_values.`tags`, `is_active` = new_values.`is_active`, `launch_date` = new_values.`launch_date`, `metadata` = new_values.`metadata`;


-- ------------------------------------------------------------
-- Model: roles
-- ------------------------------------------------------------

-- Seed data for roles
-- Generated from schema: roles.json

INSERT INTO `roles` (`slug`, `name`, `description`)
VALUES ('test_slug_2', 'Name2', 'Test description for description - Record 2') AS new_values
ON DUPLICATE KEY UPDATE `slug` = new_values.`slug`, `name` = new_values.`name`, `description` = new_values.`description`;

INSERT INTO `roles` (`slug`, `name`, `description`)
VALUES ('test_slug_3', 'Name3', 'Test description for description - Record 3') AS new_values
ON DUPLICATE KEY UPDATE `slug` = new_values.`slug`, `name` = new_values.`name`, `description` = new_values.`description`;

INSERT INTO `roles` (`slug`, `name`, `description`)
VALUES ('test_slug_4', 'Name4', 'Test description for description - Record 4') AS new_values
ON DUPLICATE KEY UPDATE `slug` = new_values.`slug`, `name` = new_values.`name`, `description` = new_values.`description`;


-- Relationship: roles -> permissions
-- Pivot table: permission_roles

INSERT INTO `permission_roles` (`role_id`, `permission_id`)
VALUES (2, 2) AS new_rel
ON DUPLICATE KEY UPDATE `role_id` = new_rel.`role_id`;

INSERT INTO `permission_roles` (`role_id`, `permission_id`)
VALUES (3, 2) AS new_rel
ON DUPLICATE KEY UPDATE `role_id` = new_rel.`role_id`;

INSERT INTO `permission_roles` (`role_id`, `permission_id`)
VALUES (3, 3) AS new_rel
ON DUPLICATE KEY UPDATE `role_id` = new_rel.`role_id`;

-- Relationship: roles -> users
-- Pivot table: role_users

INSERT INTO `role_users` (`role_id`, `user_id`)
VALUES (2, 2) AS new_rel
ON DUPLICATE KEY UPDATE `role_id` = new_rel.`role_id`;

INSERT INTO `role_users` (`role_id`, `user_id`)
VALUES (3, 2) AS new_rel
ON DUPLICATE KEY UPDATE `role_id` = new_rel.`role_id`;

INSERT INTO `role_users` (`role_id`, `user_id`)
VALUES (3, 3) AS new_rel
ON DUPLICATE KEY UPDATE `role_id` = new_rel.`role_id`;


-- ------------------------------------------------------------
-- Model: order
-- ------------------------------------------------------------

-- Seed data for orders
-- Generated from schema: order.json

INSERT INTO `orders` (`customer_id`, `order_number`, `order_date`, `status`, `total_amount`, `notes`, `deleted_at`)
VALUES (2, 'Value2', '2024-01-02', 'pending', 21.00, 'Test description for notes - Record 2', '2024-01-02 12:00:00') AS new_values
ON DUPLICATE KEY UPDATE `customer_id` = new_values.`customer_id`, `order_number` = new_values.`order_number`, `order_date` = new_values.`order_date`, `status` = new_values.`status`, `total_amount` = new_values.`total_amount`, `notes` = new_values.`notes`, `deleted_at` = new_values.`deleted_at`;

INSERT INTO `orders` (`customer_id`, `order_number`, `order_date`, `status`, `total_amount`, `notes`, `deleted_at`)
VALUES (3, 'Value3', '2024-01-03', 'pending', 31.50, 'Test description for notes - Record 3', '2024-01-03 12:00:00') AS new_values
ON DUPLICATE KEY UPDATE `customer_id` = new_values.`customer_id`, `order_number` = new_values.`order_number`, `order_date` = new_values.`order_date`, `status` = new_values.`status`, `total_amount` = new_values.`total_amount`, `notes` = new_values.`notes`, `deleted_at` = new_values.`deleted_at`;

INSERT INTO `orders` (`customer_id`, `order_number`, `order_date`, `status`, `total_amount`, `notes`, `deleted_at`)
VALUES (4, 'Value4', '2024-01-04', 'pending', 42.00, 'Test description for notes - Record 4', '2024-01-04 12:00:00') AS new_values
ON DUPLICATE KEY UPDATE `customer_id` = new_values.`customer_id`, `order_number` = new_values.`order_number`, `order_date` = new_values.`order_date`, `status` = new_values.`status`, `total_amount` = new_values.`total_amount`, `notes` = new_values.`notes`, `deleted_at` = new_values.`deleted_at`;


-- ------------------------------------------------------------
-- Model: order_legacy
-- ------------------------------------------------------------

-- Seed data for orders
-- Generated from schema: order_legacy.json

INSERT INTO `orders` (`customer_id`, `order_number`)
VALUES (2, 'Value2') AS new_values
ON DUPLICATE KEY UPDATE `customer_id` = new_values.`customer_id`, `order_number` = new_values.`order_number`;

INSERT INTO `orders` (`customer_id`, `order_number`)
VALUES (3, 'Value3') AS new_values
ON DUPLICATE KEY UPDATE `customer_id` = new_values.`customer_id`, `order_number` = new_values.`order_number`;

INSERT INTO `orders` (`customer_id`, `order_number`)
VALUES (4, 'Value4') AS new_values
ON DUPLICATE KEY UPDATE `customer_id` = new_values.`customer_id`, `order_number` = new_values.`order_number`;


-- ------------------------------------------------------------
-- Model: users
-- ------------------------------------------------------------

-- Seed data for users
-- Generated from schema: users.json

INSERT INTO `users` (`user_name`, `first_name`, `last_name`, `email`, `locale`, `group_id`, `flag_verified`, `flag_enabled`, `password`)
VALUES ('test_user_name_2', 'Name2', 'Name2', 'test2@example.com', 'en_US', 1, true, true, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi') AS new_values
ON DUPLICATE KEY UPDATE `user_name` = new_values.`user_name`, `first_name` = new_values.`first_name`, `last_name` = new_values.`last_name`, `email` = new_values.`email`, `locale` = new_values.`locale`, `group_id` = new_values.`group_id`, `flag_verified` = new_values.`flag_verified`, `flag_enabled` = new_values.`flag_enabled`, `password` = new_values.`password`;

INSERT INTO `users` (`user_name`, `first_name`, `last_name`, `email`, `locale`, `group_id`, `flag_verified`, `flag_enabled`, `password`)
VALUES ('test_user_name_3', 'Name3', 'Name3', 'test3@example.com', 'en_US', 1, true, true, '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm') AS new_values
ON DUPLICATE KEY UPDATE `user_name` = new_values.`user_name`, `first_name` = new_values.`first_name`, `last_name` = new_values.`last_name`, `email` = new_values.`email`, `locale` = new_values.`locale`, `group_id` = new_values.`group_id`, `flag_verified` = new_values.`flag_verified`, `flag_enabled` = new_values.`flag_enabled`, `password` = new_values.`password`;

INSERT INTO `users` (`user_name`, `first_name`, `last_name`, `email`, `locale`, `group_id`, `flag_verified`, `flag_enabled`, `password`)
VALUES ('test_user_name_4', 'Name4', 'Name4', 'test4@example.com', 'en_US', 1, true, true, '$2y$10$lSqpQGHmQVHSrWPvWSbqsuJQs9lDlwHUMQgW8XcPjcC8QVgQC5B0u') AS new_values
ON DUPLICATE KEY UPDATE `user_name` = new_values.`user_name`, `first_name` = new_values.`first_name`, `last_name` = new_values.`last_name`, `email` = new_values.`email`, `locale` = new_values.`locale`, `group_id` = new_values.`group_id`, `flag_verified` = new_values.`flag_verified`, `flag_enabled` = new_values.`flag_enabled`, `password` = new_values.`password`;


-- Relationship: users -> roles
-- Pivot table: role_users

INSERT INTO `role_users` (`user_id`, `role_id`)
VALUES (2, 2) AS new_rel
ON DUPLICATE KEY UPDATE `user_id` = new_rel.`user_id`;

INSERT INTO `role_users` (`user_id`, `role_id`)
VALUES (3, 2) AS new_rel
ON DUPLICATE KEY UPDATE `user_id` = new_rel.`user_id`;

INSERT INTO `role_users` (`user_id`, `role_id`)
VALUES (3, 3) AS new_rel
ON DUPLICATE KEY UPDATE `user_id` = new_rel.`user_id`;


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