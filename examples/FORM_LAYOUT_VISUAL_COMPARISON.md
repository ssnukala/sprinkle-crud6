# Form Layout Visual Comparison

This document provides a visual comparison of the different form layout options available in CRUD6.

## Layout Options Overview

### 1-Column Layout (`"form_layout": "1-column"`)

**Responsive Behavior:**
- Mobile: 1 column
- Tablet: 1 column  
- Desktop: 1 column

**Visual Structure:**
```
┌────────────────────────────────────────────────┐
│ ┌──────────────────────────────────────────┐   │
│ │ Product Name                             │   │
│ └──────────────────────────────────────────┘   │
│ ┌──────────────────────────────────────────┐   │
│ │ SKU                                      │   │
│ └──────────────────────────────────────────┘   │
│ ┌──────────────────────────────────────────┐   │
│ │ Price                                    │   │
│ └──────────────────────────────────────────┘   │
│ ┌──────────────────────────────────────────┐   │
│ │ Category                                 │   │
│ └──────────────────────────────────────────┘   │
│ ┌──────────────────────────────────────────┐   │
│ │ Description                              │   │
│ │                                          │   │
│ └──────────────────────────────────────────┘   │
│ ┌──────────────────────────────────────────┐   │
│ │ □ Active                                 │   │
│ └──────────────────────────────────────────┘   │
└────────────────────────────────────────────────┘
```

**Best For:**
- Forms with few fields (1-5 fields)
- Complex field types requiring full width
- Rich text editors, file uploads
- Maximum field width needed

---

### 2-Column Layout (`"form_layout": "2-column"`) - **DEFAULT**

**Responsive Behavior:**
- Mobile: 1 column
- Tablet: 2 columns
- Desktop: 2 columns

**Visual Structure (Desktop/Tablet):**
```
┌────────────────────────────────────────────────┐
│ ┌────────────────────┐ ┌──────────────────┐   │
│ │ Product Name       │ │ SKU              │   │
│ └────────────────────┘ └──────────────────┘   │
│ ┌────────────────────┐ ┌──────────────────┐   │
│ │ Price              │ │ Category         │   │
│ └────────────────────┘ └──────────────────┘   │
│ ┌────────────────────┐ ┌──────────────────┐   │
│ │ Tags               │ │ □ Active         │   │
│ └────────────────────┘ └──────────────────┘   │
│ ┌──────────────────────────────────────────┐   │
│ │ Description                              │   │
│ │                                          │   │
│ └──────────────────────────────────────────┘   │
└────────────────────────────────────────────────┘
```

**Visual Structure (Mobile):**
```
┌────────────────────────┐
│ ┌──────────────────┐   │
│ │ Product Name     │   │
│ └──────────────────┘   │
│ ┌──────────────────┐   │
│ │ SKU              │   │
│ └──────────────────┘   │
│ ┌──────────────────┐   │
│ │ Price            │   │
│ └──────────────────┘   │
│ ┌──────────────────┐   │
│ │ Category         │   │
│ └──────────────────┘   │
│ ┌──────────────────┐   │
│ │ Tags             │   │
│ └──────────────────┘   │
│ ┌──────────────────┐   │
│ │ □ Active         │   │
│ └──────────────────┘   │
│ ┌──────────────────┐   │
│ │ Description      │   │
│ │                  │   │
│ └──────────────────┘   │
└────────────────────────┘
```

**Best For:**
- Most general use cases (4-12 fields)
- Mix of short and long fields
- Good balance of space and readability
- **Recommended default for most forms**

---

### 3-Column Layout (`"form_layout": "3-column"`)

**Responsive Behavior:**
- Mobile: 1 column
- Tablet: 2 columns
- Desktop: 3 columns

**Visual Structure (Desktop):**
```
┌────────────────────────────────────────────────┐
│ ┌────────────┐ ┌────────────┐ ┌────────────┐  │
│ │ First Name │ │ Last Name  │ │ Email      │  │
│ └────────────┘ └────────────┘ └────────────┘  │
│ ┌────────────┐ ┌────────────┐ ┌────────────┐  │
│ │ Phone      │ │ Company    │ │ Position   │  │
│ └────────────┘ └────────────┘ └────────────┘  │
│ ┌────────────┐ ┌────────────┐ ┌────────────┐  │
│ │ City       │ │ State      │ │ ZIP        │  │
│ └────────────┘ └────────────┘ └────────────┘  │
│ ┌──────────────────────────────────────────┐  │
│ │ Notes                                    │  │
│ │                                          │  │
│ └──────────────────────────────────────────┘  │
└────────────────────────────────────────────────┘
```

**Visual Structure (Tablet):**
```
┌────────────────────────────────┐
│ ┌────────────┐ ┌────────────┐  │
│ │ First Name │ │ Last Name  │  │
│ └────────────┘ └────────────┘  │
│ ┌────────────┐ ┌────────────┐  │
│ │ Email      │ │ Phone      │  │
│ └────────────┘ └────────────┘  │
│ ┌────────────┐ ┌────────────┐  │
│ │ Company    │ │ Position   │  │
│ └────────────┘ └────────────┘  │
│ ┌────────────┐ ┌────────────┐  │
│ │ City       │ │ State      │  │
│ └────────────┘ └────────────┘  │
│ ┌────────────┐                 │
│ │ ZIP        │                 │
│ └────────────┘                 │
│ ┌──────────────────────────┐   │
│ │ Notes                    │   │
│ │                          │   │
│ └──────────────────────────┘   │
└────────────────────────────────┘
```

**Visual Structure (Mobile):**
```
┌────────────────────┐
│ ┌──────────────┐   │
│ │ First Name   │   │
│ └──────────────┘   │
│ ┌──────────────┐   │
│ │ Last Name    │   │
│ └──────────────┘   │
│ ┌──────────────┐   │
│ │ Email        │   │
│ └──────────────┘   │
│ ┌──────────────┐   │
│ │ Phone        │   │
│ └──────────────┘   │
│ ┌──────────────┐   │
│ │ Company      │   │
│ └──────────────┘   │
│     (etc...)       │
└────────────────────┘
```

**Best For:**
- Forms with many simple fields (10+ fields)
- Short input fields (names, numbers, dates)
- Minimize vertical scrolling on desktop
- Data-heavy forms (contacts, detailed records)

---

## Responsive Breakpoints

CRUD6 uses UIKit's responsive breakpoints:

| Breakpoint | Screen Width | Layout Behavior |
|------------|-------------|-----------------|
| Default    | < 640px     | All layouts display as 1 column |
| `@s` (Small) | ≥ 640px   | 2-column and 3-column activate |
| `@m` (Medium) | ≥ 960px  | 3-column displays full 3 columns |

## Implementation Details

The grid system uses UIKit classes:

```vue
<!-- 1-Column -->
<div class="uk-grid-small uk-child-width-1-1" uk-grid>

<!-- 2-Column (Default) -->
<div class="uk-grid-small uk-child-width-1-1 uk-child-width-1-2@s" uk-grid>

<!-- 3-Column -->
<div class="uk-grid-small uk-child-width-1-1 uk-child-width-1-2@s uk-child-width-1-3@m" uk-grid>
```

## Field Flow

Fields flow in the following order:

**2-Column Layout:**
```
Field 1  | Field 2
Field 3  | Field 4
Field 5  | Field 6
```

**3-Column Layout:**
```
Field 1  | Field 2  | Field 3
Field 4  | Field 5  | Field 6
Field 7  | Field 8  | Field 9
```

## Recommendations

### Choose 1-Column When:
- ✓ You have 1-5 simple fields
- ✓ Fields need maximum width (rich text, long descriptions)
- ✓ Complex field types (file uploads, custom components)
- ✗ NOT recommended for forms with many fields

### Choose 2-Column When (DEFAULT):
- ✓ You have 4-12 fields
- ✓ Mix of field types and lengths
- ✓ Good balance between space and readability
- ✓ Most common use case
- ✓ **This is the recommended default**

### Choose 3-Column When:
- ✓ You have 10+ simple fields
- ✓ Most fields are short (text, numbers, dates, selects)
- ✓ Want to minimize scrolling on large screens
- ✓ Data-heavy forms
- ✗ NOT recommended for complex fields (rich text, large textareas)
- ✗ NOT recommended if many fields are wide

## Example Use Cases

### 1-Column: Blog Post Form
- Title (wide)
- Content (rich text editor - needs full width)
- Author
- Published Date

### 2-Column: Product Form (DEFAULT)
- Product Name | SKU
- Price | Category
- Tags | Active Status
- Description (full width)

### 3-Column: Contact Form
- First Name | Last Name | Email
- Phone | Company | Position
- Address | City | State
- ZIP | Country | Department
- Notes (full width)
