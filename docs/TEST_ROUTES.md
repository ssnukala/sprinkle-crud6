# Master-Detail Test Routes

This document describes the test routes created to demonstrate the master-detail functionality.

## Available Routes

### Use Case 1: Order Entry (One-to-Many)

**Base Route:** `/testc6/orders`

This demonstrates creating/editing an order with multiple order line items in a single form.

**Available Pages:**

1. **Order List** - `/testc6/orders`
   - Shows list of all orders
   - Click "Create" to add a new order
   - Click on an order to edit it

2. **Create Order** - `/testc6/orders/create`
   - Create a new order with line items
   - Master form shows order fields (order number, customer, date, etc.)
   - Detail grid allows adding multiple line items (SKU, product, quantity, price)
   - Single save operation for entire order

3. **Edit Order** - `/testc6/orders/:id`
   - Edit existing order and its line items
   - Add/edit/delete line items inline
   - Updates saved in single transaction

**Schema Files Required:**
- `app/schema/crud6/orders.json` - Master schema with `detail_editable` config
- `app/schema/crud6/order_details.json` - Detail schema
- Example schemas available in `examples/orders.json` and `examples/order_details.json`

**Component Used:**
- `UFCRUD6MasterDetailForm` - Handles master record + detail grid

### Use Case 2: Product Categories (Many-to-Many)

**Base Route:** `/testc6/products`

This demonstrates managing many-to-many relationships between products and categories.

**Available Pages:**

1. **Product List** - `/testc6/products`
   - Shows list of all products
   - Click on a product to manage its categories

2. **Manage Categories** - `/testc6/products/:id/categories`
   - Select/deselect categories for a product
   - Checkbox-based selection interface
   - Uses pivot table (`product_categories`) for associations
   - Save updates multiple relationships at once

**Schema Files Required:**
- `app/schema/crud6/products.json` - Product schema
- `app/schema/crud6/categories.json` - Category schema
- `app/schema/crud6/product_categories.json` - Pivot table schema
- Example schemas available in `examples/products.json`, `examples/categories.json`, `examples/product_categories.json`

**Composable Used:**
- `useCRUD6Relationships` - Handles attach/detach operations for many-to-many

## How to Test

### Setting Up Test Data

1. **Create database tables** using the SQL schemas from `examples/master-detail-integration.md`

2. **Copy schema files** from examples to your schema directory:
```bash
cp examples/orders.json app/schema/crud6/
cp examples/order_details.json app/schema/crud6/
cp examples/products.json app/schema/crud6/
cp examples/categories.json app/schema/crud6/
cp examples/product_categories.json app/schema/crud6/
```

3. **Navigate to test routes** in your browser:
   - Order entry: `http://yoursite.com/testc6/orders`
   - Product categories: `http://yoursite.com/testc6/products`

### Testing Order Entry

1. Go to `/testc6/orders/create`
2. Fill in order information (order number, customer name, date)
3. Click "Add Row" in the order items grid
4. Fill in line item details (SKU, product name, quantity, price)
5. Add multiple line items as needed
6. Click "Create Order" to save everything in one transaction
7. Verify the order and all line items are saved

### Testing Product Categories

1. Go to `/testc6/products` and select a product
2. Click to manage categories (or go to `/testc6/products/:id/categories`)
3. Check/uncheck categories to assign/remove
4. Click "Save Categories"
5. Verify the relationships are saved in the pivot table

## Route Configuration

Routes are defined in `app/assets/routes/TestRoutes.ts` and automatically included in the route configuration.

The routes use:
- **PageList.vue** - For list views (reuses existing CRUD6 list component)
- **TestOrderEntry.vue** - For order entry with master-detail form
- **TestProductCategory.vue** - For category assignment interface

## Permissions

All test routes use the `uri_crud6` permission slug. Users need this permission to access the test pages.

## Customization

You can customize the test routes by:
- Modifying the schema files to change fields and validation
- Updating the `detailConfig` in TestOrderEntry.vue to show different fields
- Changing the category selection UI in TestProductCategory.vue
- Adding more test routes for other use cases

## See Also

- `examples/master-detail-usage.md` - Detailed usage guide
- `examples/master-detail-integration.md` - Complete integration examples with SQL
- `.archive/MASTER_DETAIL_IMPLEMENTATION_SUMMARY.md` - Technical implementation details
