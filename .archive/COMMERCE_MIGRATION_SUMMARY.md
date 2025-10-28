# Commerce Content Migration Summary

## Overview
Replaced all migrations, schemas, and seeds with content from sprinkle-commerce repository to support c6test pages for two master-detail use cases.

## Source Repository
- **Repository**: https://github.com/ssnukala/sprinkle-commerce
- **Branch**: main
- **Reference**: [Implementation Summary](https://github.com/ssnukala/sprinkle-commerce/blob/main/IMPLEMENTATION_SUMMARY.md)

## Changes Made

### Deleted Files (Old Content)

**Migrations (5 files deleted):**
- CreateCategoriesTable.php
- CreateOrdersTable.php
- CreateProductsTable.php
- CreateOrderDetailsTable.php
- CreateProductCategoriesTable.php

**Schemas (7 files deleted):**
- categories.json
- orders.json
- products.json
- order_details.json
- product_categories.json
- groups.json
- users.json

**Seeds (5 files deleted):**
- CategoriesSeeder.php
- OrdersSeeder.php
- ProductsSeeder.php
- OrderDetailsSeeder.php
- ProductCategoriesSeeder.php

### Added Files (New Content)

**Migrations (9 files in v600 directory):**
1. **CategoryTable.php** - Base category table
2. **CatalogTable.php** - Base catalog table
3. **ProductTable.php** - Base product table
4. **ProductCatalogTable.php** - Product-catalog relationship (pivot table)
5. **SalesOrderTable.php** - Sales order header table
6. **SalesOrderLinesTable.php** - Sales order line items
7. **PurchaseOrderTable.php** - Purchase order header table
8. **PurchaseOrderLinesTable.php** - Purchase order line items
9. **CommerceRolesTable.php** - Commerce-specific roles

**Schemas (8 files):**
1. **catalog.json** - Catalog schema with detail_editable for products
2. **category.json** - Category schema
3. **product.json** - Product schema with detail_editable for catalogs
4. **product_catalog.json** - Product-catalog relationship schema
5. **purchase_order.json** - Purchase order header schema
6. **purchase_order_lines.json** - Purchase order lines schema
7. **sales_order.json** - Sales order header schema with detail_editable
8. **sales_order_lines.json** - Sales order lines schema

**Seeds (9 new + 2 retained):**
1. **CommerceSeeder.php** - Main commerce data seeder
2. **CategorySeeder.php** - Category sample data
3. **CatalogSeeder.php** - Catalog sample data
4. **ProductSeeder.php** - Product sample data
5. **ProductCatalogSeeder.php** - Product-catalog relationships
6. **SalesOrderSeeder.php** - Sales order sample data
7. **SalesOrderLinesSeeder.php** - Sales order line items
8. **PurchaseOrderSeeder.php** - Purchase order sample data
9. **PurchaseOrderLinesSeeder.php** - Purchase order line items
10. **DefaultRoles.php** - ✓ Retained from original
11. **DefaultPermissions.php** - ✓ Retained from original

### Updated Files

**CRUD6.php:**
- Updated imports to include all new migration and seed classes
- Updated `getMigrations()` to return 9 migration classes
- Updated `getSeeds()` to return 11 seed classes (9 new + 2 original)

**CRUD6Test.php:**
- Updated migration class imports to v600 namespace
- Updated expected migrations list in test assertions

## Namespace Updates

All copied files had namespaces updated from:
- `UserFrosting\Sprinkle\Commerce` → `UserFrosting\Sprinkle\CRUD6`
- Copyright headers updated to CRUD6 Sprinkle
- Repository links updated to sprinkle-crud6

## Use Cases Supported

### Use Case 1: Sales Order with Line Items (One-to-Many)

**Master Table:** `or_sales_order`
- Schema: `sales_order.json`
- Has `detail_editable` configuration pointing to `sales_order_lines`

**Detail Table:** `or_sales_order_lines`
- Schema: `sales_order_lines.json`
- Foreign key: `order_id` references `or_sales_order.id`
- Fields: line_no, description, product_catalog_id, unit_price, quantity, net_amount, tax, discount, gross_amount

**Features:**
- Create sales orders with multiple line items in single transaction
- Inline editing of line items
- Add, edit, delete line items
- Automatic foreign key population

**Routes (from commerce sprinkle):**
- List: `/commerce/sales-orders`
- Create: `/commerce/sales-orders/create`
- Edit: `/commerce/sales-orders/:id`

### Use Case 2: Product and Catalog Management (Many-to-Many)

**Table 1:** `pr_product`
- Schema: `product.json`
- Has `detail_editable` configuration pointing to `product_catalog`

**Table 2:** `pr_catalog`
- Schema: `catalog.json`
- Has `detail_editable` configuration pointing to `product_catalog`

**Pivot Table:** `pr_product_catalog`
- Schema: `product_catalog.json`
- Foreign keys: 
  - `product_id` references `pr_product.id`
  - `catalog_id` references `pr_catalog.id`
- Additional fields: name, description, unit_price, tax, active_date, status

**Features:**
- Bidirectional relationship management
- Manage products and assign to catalogs
- Manage catalogs and assign products
- Custom pricing per product-catalog assignment

**Routes (from commerce sprinkle):**
- Products List: `/commerce/products`
- Products Create: `/commerce/products/create`
- Products Edit: `/commerce/products/:id`
- Catalogs List: `/commerce/catalogs`
- Catalogs Create: `/commerce/catalogs/create`
- Catalogs Edit: `/commerce/catalogs/:id`

## Database Table Structure

### Sales Order Use Case Tables

```sql
-- Master table
or_sales_order
├── id
├── order_no
├── customer_id
├── order_date
├── total_amount
├── tax
├── discount
├── gross_amount
└── ... (more fields)

-- Detail table (one-to-many)
or_sales_order_lines
├── id
├── order_id (FK → or_sales_order.id)
├── line_no
├── product_catalog_id
├── description
├── unit_price
├── quantity
├── net_amount
├── tax
├── discount
├── gross_amount
└── ... (more fields)
```

### Product-Catalog Use Case Tables

```sql
-- Table 1
pr_product
├── id
├── name
├── description
├── sku
└── ... (more fields)

-- Table 2
pr_catalog
├── id
├── name
├── description
└── ... (more fields)

-- Pivot table (many-to-many)
pr_product_catalog
├── id
├── product_id (FK → pr_product.id)
├── catalog_id (FK → pr_catalog.id)
├── name (custom name for this relationship)
├── description
├── unit_price (custom pricing)
├── tax
├── active_date
├── status
└── ... (more fields)
```

## Migration Order in CRUD6.php

```php
public function getMigrations(): array
{
    return [
        // Base tables
        CategoryTable::class,
        CatalogTable::class,
        ProductTable::class,
        
        // Relationship tables
        ProductCatalogTable::class,
        
        // Order tables
        SalesOrderTable::class,
        SalesOrderLinesTable::class,
        PurchaseOrderTable::class,
        PurchaseOrderLinesTable::class,
        
        // Commerce roles
        CommerceRolesTable::class,
    ];
}
```

## Seed Order in CRUD6.php

```php
public function getSeeds(): array
{
    return [
        DefaultRoles::class,
        DefaultPermissions::class,
        CommerceSeeder::class,
        CategorySeeder::class,
        CatalogSeeder::class,
        ProductSeeder::class,
        ProductCatalogSeeder::class,
        SalesOrderSeeder::class,
        SalesOrderLinesSeeder::class,
        PurchaseOrderSeeder::class,
        PurchaseOrderLinesSeeder::class,
    ];
}
```

## Validation

✅ All PHP files pass syntax validation
✅ All migration namespaces updated to CRUD6
✅ All seed namespaces updated to CRUD6
✅ CRUD6.php updated with new migrations and seeds
✅ CRUD6Test.php updated with new migration assertions
✅ All changes committed and pushed

## Next Steps

After merging this PR, users should:

1. **Run migrations:**
   ```bash
   php bakery migrate
   ```

2. **Run seeds:**
   ```bash
   php bakery seed
   ```

3. **Test Use Case 1 - Sales Orders:**
   - Navigate to `/commerce/sales-orders/create`
   - Create a sales order with line items
   - Verify master-detail functionality

4. **Test Use Case 2 - Products & Catalogs:**
   - Navigate to `/commerce/products/create`
   - Create a product with catalog assignments
   - Navigate to `/commerce/catalogs/create`
   - Create a catalog with product assignments

## References

- [sprinkle-commerce Repository](https://github.com/ssnukala/sprinkle-commerce)
- [Implementation Summary](https://github.com/ssnukala/sprinkle-commerce/blob/main/IMPLEMENTATION_SUMMARY.md)
- [Master-Detail Pages Documentation](https://github.com/ssnukala/sprinkle-commerce/blob/main/docs/MASTER_DETAIL_PAGES.md)
- [sprinkle-crud6 PR #130](https://github.com/ssnukala/sprinkle-crud6/pull/130) - Original master-detail implementation

## Files Changed Summary

- **Deleted**: 17 files (5 migrations, 7 schemas, 5 seeds)
- **Added**: 26 files (9 migrations, 8 schemas, 9 seeds)
- **Modified**: 2 files (CRUD6.php, CRUD6Test.php)
- **Total Changes**: +3241 lines, -1494 lines
- **Net Change**: +1747 lines
