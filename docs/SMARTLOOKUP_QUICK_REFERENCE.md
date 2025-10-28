# Quick Reference: SmartLookup Field Type

## Basic Schema Configuration

```json
{
  "field_name": {
    "type": "smartlookup",
    "label": "Field Label",
    "lookup_model": "target_model",
    "lookup_id": "id",
    "lookup_desc": "name",
    "required": true,
    "placeholder": "Search..."
  }
}
```

## Parameters

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `type` | string | Yes | - | Must be "smartlookup" |
| `lookup_model` or `model` | string | Yes | - | CRUD6 model to search |
| `lookup_id` or `id` | string | No | "id" | Field to use as value |
| `lookup_desc` or `desc` | string | No | "name" | Field to display |
| `label` | string | No | - | Field label |
| `placeholder` | string | No | - | Input placeholder |
| `required` | boolean | No | false | Required field |
| `readonly` | boolean | No | false | Read-only field |
| `description` | string | No | - | Help text |
| `default` | any | No | null | Default value |

## Common Examples

### Customer Lookup
```json
{
  "customer_id": {
    "type": "smartlookup",
    "lookup_model": "customers",
    "lookup_id": "id",
    "lookup_desc": "name"
  }
}
```

### Product Lookup with Multiple Display Fields
```json
{
  "product_id": {
    "type": "smartlookup",
    "lookup_model": "products",
    "lookup_id": "id",
    "lookup_desc": "name",
    "display_fields": ["sku", "name", "price"]
  }
}
```

### Category Lookup
```json
{
  "category_id": {
    "type": "smartlookup",
    "lookup_model": "categories",
    "lookup_id": "id",
    "lookup_desc": "name",
    "required": true
  }
}
```

## API Endpoint

SmartLookup uses the standard CRUD6 Sprunje endpoint:

```
GET /api/crud6/{lookup_model}?search={query}&size=20
```

## Where It Works

- ✅ PageRow (edit mode)
- ✅ PageMasterDetail (master form, detail grid)
- ✅ Form component
- ✅ DetailGrid component (inline editing)

## See Full Documentation

- [SMARTLOOKUP_FIELD_TYPE.md](../docs/SMARTLOOKUP_FIELD_TYPE.md) - Complete field type guide
- [PAGE_MASTER_DETAIL.md](../docs/PAGE_MASTER_DETAIL.md) - PageMasterDetail component guide
- [smartlookup-example.json](../examples/smartlookup-example.json) - Example schema
