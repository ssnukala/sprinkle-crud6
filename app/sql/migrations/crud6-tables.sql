-- ═══════════════════════════════════════════════════════════════
-- CRUD6 DDL - CREATE TABLE Statements
-- Generated from JSON schemas
-- ═══════════════════════════════════════════════════════════════
--
-- This file creates all tables needed for CRUD6 test schemas.
-- Run this BEFORE seeding data with INSERT statements.
--
-- EXECUTION ORDER:
-- 1. Run UserFrosting migrations (php bakery migrate)
-- 2. Run this DDL file (CREATE TABLE statements)
-- 3. Create admin user (php bakery create:admin-user)
-- 4. Run seed data (INSERT statements)
--
-- Generated: 2025-12-12T00:24:27.928Z
-- Source: Schema files in examples/schema/
-- ═══════════════════════════════════════════════════════════════

-- Disable foreign key checks during table creation
SET FOREIGN_KEY_CHECKS=0;

-- ------------------------------------------------------------
-- Table: activities
-- Schema: activities.json
-- ------------------------------------------------------------

CREATE TABLE IF NOT EXISTS activities (
  id INT AUTO_INCREMENT NOT NULL,
  ip_address VARCHAR(45) NULL,
  user_id INT NOT NULL,
  type VARCHAR(255) NOT NULL,
  occurred_at TIMESTAMP NOT NULL,
  description TEXT NULL,
  PRIMARY KEY (id),
  KEY occurred_at_idx (occurred_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: categories
-- Schema: categories.json
-- ------------------------------------------------------------

CREATE TABLE IF NOT EXISTS categories (
  id INT AUTO_INCREMENT NOT NULL,
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(255) NOT NULL,
  description TEXT NULL,
  icon VARCHAR(255) NULL,
  is_active TINYINT(1) NULL DEFAULT 1,
  PRIMARY KEY (id),
  KEY name_idx (name),
  KEY slug_idx (slug),
  KEY is_active_idx (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: contacts
-- Schema: contacts.json
-- ------------------------------------------------------------

CREATE TABLE IF NOT EXISTS contacts (
  id INT AUTO_INCREMENT NOT NULL,
  first_name VARCHAR(50) NOT NULL,
  last_name VARCHAR(50) NOT NULL,
  email VARCHAR(255) NOT NULL,
  phone VARCHAR(20) NULL,
  mobile VARCHAR(20) NULL,
  website VARCHAR(2048) NULL,
  address VARCHAR(200) NULL,
  city VARCHAR(100) NULL,
  state VARCHAR(2) NULL,
  zip VARCHAR(10) NULL,
  notes TEXT NULL,
  bio TEXT NULL,
  company VARCHAR(100) NULL,
  position VARCHAR(100) NULL,
  is_active TINYINT(1) NULL DEFAULT 1,
  newsletter_opt_in TINYINT(1) NULL DEFAULT 0,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY email_unique (email),
  KEY first_name_idx (first_name),
  KEY last_name_idx (last_name),
  KEY phone_idx (phone),
  KEY address_idx (address)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: tasks
-- Schema: field-template-example.json
-- ------------------------------------------------------------

CREATE TABLE IF NOT EXISTS tasks (
  id INT AUTO_INCREMENT NOT NULL,
  title VARCHAR(255) NOT NULL,
  description TEXT NULL,
  status VARCHAR(255) NULL DEFAULT 'pending',
  priority VARCHAR(255) NULL DEFAULT 'medium',
  assigned_to VARCHAR(255) NULL,
  due_date DATE NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY title_idx (title),
  KEY status_idx (status),
  KEY priority_idx (priority),
  KEY assigned_to_idx (assigned_to),
  KEY due_date_idx (due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: groups
-- Schema: groups.json
-- ------------------------------------------------------------

CREATE TABLE IF NOT EXISTS groups (
  id INT AUTO_INCREMENT NOT NULL,
  slug VARCHAR(255) NOT NULL,
  name VARCHAR(255) NOT NULL,
  description TEXT NULL,
  icon VARCHAR(100) NULL DEFAULT 'fas fa-user',
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY slug_unique (slug),
  KEY name_idx (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: order_details
-- Schema: order_details.json
-- ------------------------------------------------------------

CREATE TABLE IF NOT EXISTS order_details (
  id INT AUTO_INCREMENT NOT NULL,
  order_id INT NOT NULL,
  line_number INT NOT NULL,
  sku VARCHAR(255) NOT NULL,
  product_name VARCHAR(255) NOT NULL,
  quantity INT NOT NULL,
  unit_price DECIMAL(10,2) NOT NULL,
  line_total DECIMAL(10,2) NULL,
  notes TEXT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY order_id_idx (order_id),
  KEY line_number_idx (line_number),
  KEY sku_idx (sku),
  KEY product_name_idx (product_name),
  KEY quantity_idx (quantity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: orders
-- Schema: orders.json
-- ------------------------------------------------------------

CREATE TABLE IF NOT EXISTS orders (
  id INT AUTO_INCREMENT NOT NULL,
  order_number VARCHAR(255) NOT NULL,
  customer_name VARCHAR(255) NOT NULL,
  customer_email VARCHAR(255) NULL,
  total_amount DECIMAL(10,2) NOT NULL,
  payment_status VARCHAR(255) NOT NULL DEFAULT 'pending',
  order_date DATE NOT NULL,
  notes TEXT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY order_number_unique (order_number),
  KEY customer_name_idx (customer_name),
  KEY customer_email_idx (customer_email),
  KEY total_amount_idx (total_amount),
  KEY payment_status_idx (payment_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: permissions
-- Schema: permissions.json
-- ------------------------------------------------------------

CREATE TABLE IF NOT EXISTS permissions (
  id INT AUTO_INCREMENT NOT NULL,
  slug VARCHAR(255) NOT NULL,
  name VARCHAR(255) NOT NULL,
  conditions TEXT NULL DEFAULT '',
  description TEXT NULL,
  role_ids TEXT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY slug_unique (slug),
  KEY name_idx (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pivot table for permissions <-> roles relationship
CREATE TABLE IF NOT EXISTS permission_roles (
  permission_id INT NOT NULL,
  role_id INT NOT NULL,
  PRIMARY KEY (permission_id, role_id),
  KEY permission_id_idx (permission_id),
  KEY role_id_idx (role_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: product_categories
-- Schema: product_categories.json
-- ------------------------------------------------------------

CREATE TABLE IF NOT EXISTS product_categories (
  id INT AUTO_INCREMENT NOT NULL,
  product_id INT NOT NULL,
  category_id INT NOT NULL,
  created_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY product_id_idx (product_id),
  KEY category_id_idx (category_id),
  KEY created_at_idx (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: products
-- Schema: products-1column.json
-- ------------------------------------------------------------

CREATE TABLE IF NOT EXISTS products (
  id INT AUTO_INCREMENT NOT NULL,
  name VARCHAR(255) NOT NULL,
  sku VARCHAR(255) NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  description TEXT NULL,
  is_active TINYINT(1) NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY sku_unique (sku),
  KEY name_idx (name),
  KEY price_idx (price),
  KEY is_active_idx (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: roles
-- Schema: roles.json
-- ------------------------------------------------------------

CREATE TABLE IF NOT EXISTS roles (
  id INT AUTO_INCREMENT NOT NULL,
  slug VARCHAR(255) NOT NULL,
  name VARCHAR(255) NOT NULL,
  description TEXT NULL,
  permission_ids TEXT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY slug_unique (slug),
  KEY name_idx (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pivot table for roles <-> users relationship
CREATE TABLE IF NOT EXISTS role_users (
  role_id INT NOT NULL,
  user_id INT NOT NULL,
  PRIMARY KEY (role_id, user_id),
  KEY role_id_idx (role_id),
  KEY user_id_idx (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: users
-- Schema: users.json
-- ------------------------------------------------------------

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT NOT NULL,
  user_name VARCHAR(50) NOT NULL,
  first_name VARCHAR(20) NOT NULL,
  last_name VARCHAR(30) NOT NULL,
  email VARCHAR(255) NOT NULL,
  locale VARCHAR(10) NULL DEFAULT 'en_US',
  group_id INT NULL DEFAULT 1,
  flag_verified TINYINT(1) NULL DEFAULT 1,
  flag_enabled TINYINT(1) NULL DEFAULT 1,
  role_ids TEXT NULL,
  password VARCHAR(255) NULL,
  deleted_at TIMESTAMP NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY user_name_unique (user_name),
  UNIQUE KEY email_unique (email),
  KEY first_name_idx (first_name),
  KEY last_name_idx (last_name),
  KEY flag_verified_idx (flag_verified)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS=1;

-- ═══════════════════════════════════════════════════════════════
-- Successfully generated 12 table definitions
-- and 2 pivot tables
-- from 12 schema files
-- ═══════════════════════════════════════════════════════════════