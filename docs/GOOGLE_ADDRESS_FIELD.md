# Google Address Field Type

## Overview

The `address` field type integrates Google Places Autocomplete API to provide intelligent address capture with automatic geocoding. Users can search for addresses and have multiple related fields automatically populated.

## Features

- ✅ **Google Places Autocomplete** - Real-time address suggestions as user types
- ✅ **Automatic Geocoding** - Breaks down full address into components
- ✅ **Multiple Field Population** - Auto-fills addr_line1, addr_line2, city, state, zip
- ✅ **Latitude/Longitude** - Optional coordinate capture
- ✅ **Fallback Support** - Works as regular text field if API key not configured
- ✅ **UIKit Styled** - Matches application design language

## Schema Configuration

### Basic Address Field

```json
{
  "full_address": {
    "type": "address",
    "label": "Address",
    "required": true,
    "address_fields": {
      "addr_line1": "address_line_1",
      "addr_line2": "address_line_2",
      "city": "city",
      "state": "state",
      "zip": "postal_code"
    }
  }
}
```

### With Coordinates

```json
{
  "location": {
    "type": "address",
    "label": "Location",
    "required": true,
    "address_fields": {
      "addr_line1": "street_address",
      "city": "city",
      "state": "state",
      "zip": "zip_code",
      "latitude": "lat",
      "longitude": "lng"
    }
  }
}
```

### With Country

```json
{
  "shipping_address": {
    "type": "address",
    "label": "Shipping Address",
    "required": true,
    "address_fields": {
      "addr_line1": "ship_address_1",
      "addr_line2": "ship_address_2",
      "city": "ship_city",
      "state": "ship_state",
      "zip": "ship_zip",
      "country": "ship_country"
    }
  }
}
```

## Field Mapping

### Address Fields Configuration

The `address_fields` object maps Google Places address components to your model fields:

| Property | Description | Example Google Data | Required |
|----------|-------------|---------------------|----------|
| `addr_line1` | Street address (number + street) | "123 Main St" | Yes |
| `addr_line2` | Apartment/Suite (usually empty) | "Apt 4B" | No |
| `city` | City/Locality | "San Francisco" | Yes |
| `state` | State/Province (short code) | "CA" | Yes |
| `zip` | Postal code | "94102" or "94102-1234" | Yes |
| `country` | Country (short code) | "US" | No |
| `latitude` | Latitude coordinate | 37.7749 | No |
| `longitude` | Longitude coordinate | -122.4194 | No |

### Database Schema Example

For the address field to work, your database table should have the corresponding fields:

```sql
CREATE TABLE contacts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    full_address VARCHAR(500),      -- The main address field
    address_line_1 VARCHAR(200),    -- Populated from addr_line1
    address_line_2 VARCHAR(200),    -- Populated from addr_line2
    city VARCHAR(100),              -- Populated from city
    state VARCHAR(10),              -- Populated from state
    postal_code VARCHAR(20),        -- Populated from zip
    country VARCHAR(10),            -- Populated from country (optional)
    lat DECIMAL(10, 8),            -- Populated from latitude (optional)
    lng DECIMAL(11, 8)             -- Populated from longitude (optional)
);
```

## Setup

### 1. Get Google API Key

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select existing
3. Enable **Google Maps JavaScript API** and **Places API**
4. Create API Key with restrictions:
   - HTTP referrers (websites)
   - Add your domain(s)

### 2. Configure API Key

**Option A: Environment Variable (Recommended)**

Create `.env` file in your project root:
```env
VITE_GOOGLE_MAPS_API_KEY=your_api_key_here
```

**Option B: Backend Configuration**

Add to UserFrosting config file (e.g., `app/config/default.php`):
```php
return [
    'crud6' => [
        'google_maps_api_key' => 'your_api_key_here',
    ],
];
```

Then expose to frontend in your layout template:
```html
<script>
    window.CRUD6Config = {
        googleMapsApiKey: '{{ config.crud6.google_maps_api_key }}'
    };
</script>
```

### 3. Add to Schema

```json
{
  "model": "locations",
  "fields": {
    "id": {
      "type": "integer",
      "auto_increment": true
    },
    "name": {
      "type": "string",
      "label": "Location Name",
      "required": true
    },
    "full_address": {
      "type": "address",
      "label": "Address",
      "placeholder": "Start typing to search...",
      "required": true,
      "listable": true,
      "address_fields": {
        "addr_line1": "address_line_1",
        "addr_line2": "address_line_2",
        "city": "city",
        "state": "state",
        "zip": "postal_code"
      }
    },
    "address_line_1": {
      "type": "string",
      "label": "Street Address",
      "listable": false,
      "editable": true
    },
    "address_line_2": {
      "type": "string",
      "label": "Apt/Suite",
      "required": false,
      "listable": false,
      "editable": true
    },
    "city": {
      "type": "string",
      "label": "City",
      "listable": true,
      "editable": true
    },
    "state": {
      "type": "string",
      "label": "State",
      "listable": true,
      "editable": true
    },
    "postal_code": {
      "type": "string",
      "label": "ZIP Code",
      "listable": false,
      "editable": true
    }
  }
}
```

## How It Works

### 1. User Types Address

User begins typing in the address field:
```
"123 Main"
```

### 2. Google Suggests Addresses

Dropdown shows autocomplete suggestions:
```
📍 123 Main St, San Francisco, CA, USA
📍 123 Main Ave, Los Angeles, CA, USA
📍 123 Main Rd, Oakland, CA, USA
```

### 3. User Selects Address

User clicks/selects: "123 Main St, San Francisco, CA 94102, USA"

### 4. Automatic Field Population

The component automatically populates:
```javascript
{
  full_address: "123 Main St, San Francisco, CA 94102, USA",
  address_line_1: "123 Main St",
  address_line_2: "",
  city: "San Francisco",
  state: "CA",
  postal_code: "94102"
}
```

### 5. Form Submission

All fields are submitted together to the backend.

## Complete Example

### Schema: `examples/schema/stores.json`

```json
{
  "model": "stores",
  "title": "Store Locations",
  "table": "stores",
  "primary_key": "id",
  "timestamps": true,
  "fields": {
    "id": {
      "type": "integer",
      "auto_increment": true,
      "listable": true
    },
    "store_name": {
      "type": "string",
      "label": "Store Name",
      "required": true,
      "listable": true,
      "validation": {
        "required": true,
        "length": { "max": 100 }
      }
    },
    "full_address": {
      "type": "address",
      "label": "Store Address",
      "placeholder": "Search for store location...",
      "required": true,
      "listable": true,
      "address_fields": {
        "addr_line1": "address_line_1",
        "addr_line2": "address_line_2",
        "city": "city",
        "state": "state",
        "zip": "postal_code",
        "country": "country",
        "latitude": "lat",
        "longitude": "lng"
      },
      "validation": {
        "required": true
      }
    },
    "address_line_1": {
      "type": "string",
      "label": "Street Address",
      "listable": false,
      "viewable": true,
      "editable": true
    },
    "address_line_2": {
      "type": "string",
      "label": "Suite/Unit",
      "required": false,
      "listable": false,
      "viewable": true,
      "editable": true
    },
    "city": {
      "type": "string",
      "label": "City",
      "listable": true,
      "viewable": true,
      "editable": true
    },
    "state": {
      "type": "string",
      "label": "State",
      "listable": true,
      "viewable": true,
      "editable": true
    },
    "postal_code": {
      "type": "zip",
      "label": "ZIP Code",
      "listable": false,
      "viewable": true,
      "editable": true
    },
    "country": {
      "type": "string",
      "label": "Country",
      "default": "US",
      "listable": false,
      "viewable": true,
      "editable": true
    },
    "lat": {
      "type": "decimal",
      "label": "Latitude",
      "listable": false,
      "viewable": true,
      "editable": false
    },
    "lng": {
      "type": "decimal",
      "label": "Longitude",
      "listable": false,
      "viewable": true,
      "editable": false
    },
    "phone": {
      "type": "phone",
      "label": "Store Phone",
      "listable": true
    },
    "is_active": {
      "type": "boolean",
      "label": "Active",
      "default": true,
      "listable": true
    }
  }
}
```

### Migration

```php
Schema::create('stores', function (Blueprint $table) {
    $table->id();
    $table->string('store_name', 100);
    $table->string('full_address', 500);
    $table->string('address_line_1', 200);
    $table->string('address_line_2', 200)->nullable();
    $table->string('city', 100);
    $table->string('state', 10);
    $table->string('postal_code', 20);
    $table->string('country', 10)->default('US');
    $table->decimal('lat', 10, 8)->nullable();
    $table->decimal('lng', 11, 8)->nullable();
    $table->string('phone', 20)->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

## Backend Handling

The address field is stored as a regular string in the `full_address` field. The backend doesn't need special handling since all component fields are submitted separately.

```php
// In Base.php controller
protected function transformFieldValue(array $fieldConfig, mixed $value): mixed
{
    $type = $fieldConfig['type'] ?? 'string';
    
    switch ($type) {
        // ... other types ...
        case 'address':
            // Address is stored as string
            return (string) $value;
        default:
            return (string) $value;
    }
}
```

## API Key Security

### Best Practices

1. **Restrict API Key**
   - Add HTTP referrer restrictions
   - Limit to specific domains
   - Enable only required APIs

2. **Don't Commit Keys**
   - Use `.env` file (add to `.gitignore`)
   - Use environment variables in production

3. **Monitor Usage**
   - Set up budget alerts in Google Cloud
   - Monitor API usage regularly

4. **Rate Limiting**
   - Google provides generous free tier
   - 1000 requests/day free
   - Additional costs after that

## Fallback Behavior

If Google API key is not configured:
- Field works as regular text input
- Shows warning message
- User can manually enter address
- Address component fields can still be edited manually

## Styling

The component automatically matches UIKit styling:

```css
/* Input field */
.uk-input {
    height: 40px;
    padding: 0 10px;
    border: 1px solid #e5e5e5;
    border-radius: 4px;
}

/* Autocomplete dropdown */
.pac-container {
    border: 1px solid #e5e5e5;
    border-radius: 4px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
}

/* Selected item */
.pac-item-selected {
    background-color: #1e87f0;
    color: white;
}
```

## Troubleshooting

### API Key Not Working

**Problem:** "Google Maps API key not configured" warning

**Solutions:**
1. Check `.env` file has `VITE_GOOGLE_MAPS_API_KEY`
2. Restart dev server after adding env variable
3. Verify API key is enabled in Google Cloud Console
4. Check HTTP referrer restrictions

### Autocomplete Not Showing

**Problem:** No dropdown appears when typing

**Solutions:**
1. Check browser console for API errors
2. Verify Places API is enabled
3. Check API key restrictions
4. Ensure script loaded: Look for `google.maps.places` in console

### Wrong Address Components

**Problem:** City/state not populating correctly

**Solutions:**
1. Check `address_fields` mapping in schema
2. Verify field names match database columns
3. Test with different addresses

### Coordinates Not Capturing

**Problem:** lat/lng not populated

**Solutions:**
1. Add `latitude` and `longitude` to `address_fields`
2. Ensure fields exist in schema and database
3. Check field type is `decimal` for coordinates

## See Also

- [FIELD_TYPES_REFERENCE.md](FIELD_TYPES_REFERENCE.md) - All field types
- [FIELD_TYPES_UTILITY.md](FIELD_TYPES_UTILITY.md) - Field type utilities
- [Google Places Autocomplete Documentation](https://developers.google.com/maps/documentation/javascript/place-autocomplete)
- [Google Maps Platform](https://developers.google.com/maps)
