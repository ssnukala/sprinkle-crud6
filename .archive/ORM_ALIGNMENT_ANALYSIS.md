# ORM Framework Model Definition Analysis

**Date**: 2025-11-12  
**Purpose**: Analyze model definitions across popular ORM frameworks to ensure CRUD6 JSON schemas follow familiar patterns

## Framework Analysis

### 1. Laravel Eloquent (PHP)

**Model Definition**:
```php
class Product extends Model
{
    protected $table = 'products';
    protected $primaryKey = 'id';
    protected $fillable = ['name', 'sku', 'price', 'category_id'];
    protected $guarded = ['id', 'created_at', 'updated_at'];
    protected $hidden = ['password', 'api_token'];
    protected $visible = ['id', 'name', 'sku'];
    protected $casts = [
        'is_active' => 'boolean',
        'price' => 'decimal:2',
        'metadata' => 'json',
        'launch_date' => 'date',
        'created_at' => 'datetime'
    ];
    protected $dates = ['launch_date', 'created_at', 'updated_at'];
    protected $appends = ['full_name'];
    
    public $timestamps = true;
    
    // Relationships
    public function category() {
        return $this->belongsTo(Category::class);
    }
    
    public function tags() {
        return $this->belongsToMany(Tag::class);
    }
}
```

**Key Concepts**:
- `fillable` - Mass assignable fields (editable)
- `guarded` - Protected from mass assignment (readonly)
- `hidden` - Hidden from arrays/JSON (not visible in API)
- `visible` - Only these shown in arrays/JSON (alternative to hidden)
- `casts` - Type casting for attributes
- `dates` - Date attributes
- `timestamps` - Auto-manage created_at/updated_at

### 2. Sequelize (Node.js)

**Model Definition**:
```javascript
const Product = sequelize.define('Product', {
  id: {
    type: DataTypes.INTEGER,
    primaryKey: true,
    autoIncrement: true
  },
  name: {
    type: DataTypes.STRING,
    allowNull: false,
    validate: {
      notEmpty: true,
      len: [2, 255]
    }
  },
  sku: {
    type: DataTypes.STRING,
    unique: true,
    allowNull: false
  },
  price: {
    type: DataTypes.DECIMAL(10, 2),
    allowNull: false,
    validate: {
      min: 0
    }
  },
  isActive: {
    type: DataTypes.BOOLEAN,
    defaultValue: true,
    field: 'is_active'
  },
  metadata: {
    type: DataTypes.JSON
  }
}, {
  tableName: 'products',
  timestamps: true,
  underscored: true
});

// Associations
Product.belongsTo(Category, { foreignKey: 'category_id' });
Product.belongsToMany(Tag, { through: 'product_tags' });
```

**Key Concepts**:
- Field-level configuration with type, validation, defaults
- `allowNull` - Required/optional
- `validate` - Validation rules object
- `defaultValue` - Default values
- `field` - Database column name mapping
- Association methods for relationships

### 3. TypeORM (TypeScript)

**Model Definition**:
```typescript
@Entity('products')
export class Product {
  @PrimaryGeneratedColumn()
  id: number;

  @Column({ length: 255, nullable: false })
  name: string;

  @Column({ unique: true, nullable: false })
  sku: string;

  @Column('decimal', { precision: 10, scale: 2 })
  price: number;

  @Column({ default: true })
  isActive: boolean;

  @Column('json', { nullable: true })
  metadata: object;

  @ManyToOne(() => Category)
  @JoinColumn({ name: 'category_id' })
  category: Category;

  @ManyToMany(() => Tag)
  @JoinTable()
  tags: Tag[];

  @CreateDateColumn()
  createdAt: Date;

  @UpdateDateColumn()
  updatedAt: Date;
}
```

**Key Concepts**:
- Decorators for configuration
- Column-level options: nullable, default, unique, length
- Type specifications: string, number, boolean, Date
- Relationship decorators with clear semantics

### 4. Django ORM (Python)

**Model Definition**:
```python
class Product(models.Model):
    name = models.CharField(max_length=255, null=False, blank=False)
    sku = models.CharField(max_length=100, unique=True)
    price = models.DecimalField(max_digits=10, decimal_places=2, validators=[MinValueValidator(0)])
    is_active = models.BooleanField(default=True)
    description = models.TextField(blank=True)
    category = models.ForeignKey(Category, on_delete=models.CASCADE)
    tags = models.ManyToManyField(Tag)
    metadata = models.JSONField(null=True, blank=True)
    launch_date = models.DateField(null=True, blank=True)
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)
    
    class Meta:
        db_table = 'products'
        ordering = ['name']
```

**Key Concepts**:
- Field types as classes (CharField, BooleanField, etc.)
- `null` - Database allows NULL (vs `blank` for validation)
- `default` - Default values
- `validators` - Validation list
- Meta class for model-level configuration

### 5. Prisma (Modern ORM)

**Model Definition**:
```prisma
model Product {
  id          Int       @id @default(autoincrement())
  name        String    @db.VarChar(255)
  sku         String    @unique
  price       Decimal   @db.Decimal(10, 2)
  isActive    Boolean   @default(true) @map("is_active")
  description String?   @db.Text
  categoryId  Int       @map("category_id")
  category    Category  @relation(fields: [categoryId], references: [id])
  tags        Tag[]
  metadata    Json?
  launchDate  DateTime? @map("launch_date")
  createdAt   DateTime  @default(now()) @map("created_at")
  updatedAt   DateTime  @updatedAt @map("updated_at")
  
  @@map("products")
  @@index([sku])
}
```

**Key Concepts**:
- Clean, declarative syntax
- `?` for optional/nullable
- `@default()` for defaults
- `@map()` for column mapping
- Explicit relationships with references

## Common Patterns Across ORMs

### Universal Concepts

1. **Field Definition First**: All ORMs start with field/column definitions
2. **Type System**: Strong typing (string, integer, boolean, date, etc.)
3. **Nullable/Required**: Clear distinction between required and optional
4. **Defaults**: Default values specified at field level
5. **Validation**: Field-level validation rules
6. **Relationships**: Explicit relationship definitions
7. **Timestamps**: Common pattern for created_at/updated_at
8. **Table Mapping**: Separate model name from table name
9. **Primary Keys**: Explicit primary key definition

### Naming Conventions

**Laravel/PHP**: snake_case for DB, camelCase for accessors
**Sequelize**: camelCase with mapping to snake_case
**TypeORM**: camelCase with automatic/manual mapping
**Django**: snake_case throughout
**Prisma**: camelCase with @map for DB names

## Recommendations for CRUD6 JSON Schema

### 1. Align Field Structure with ORM Patterns

**Current CRUD6**:
```json
"name": {
    "type": "string",
    "label": "Product Name",
    "required": true,
    "sortable": true,
    "filterable": true,
    "listable": true,
    "validation": {
        "required": true,
        "length": { "min": 2, "max": 255 }
    }
}
```

**Proposed (ORM-aligned)**:
```json
"name": {
    "type": "string",
    "nullable": false,          // Laravel/Sequelize/TypeORM pattern
    "default": null,
    "length": 255,              // Sequelize/Django pattern
    "validation": {
        "min": 2,
        "max": 255
    },
    "ui": {                     // Separate UI concerns from data model
        "label": "Product Name",
        "sortable": true,
        "filterable": true,
        "show_in": ["list", "form", "detail"]
    }
}
```

### 2. Separate Data Model from UI Configuration

**Schema-Level Separation**:
```json
{
    "model": "products",
    "table": "products",
    "primaryKey": "id",         // Sequelize/Prisma pattern
    "timestamps": true,         // Laravel/Sequelize pattern
    "softDelete": false,
    
    "columns": {                // Data model definition (like ORM)
        "id": {
            "type": "integer",
            "autoIncrement": true,
            "primaryKey": true,
            "nullable": false
        },
        "name": {
            "type": "string",
            "length": 255,
            "nullable": false,
            "unique": false
        }
    },
    
    "ui": {                     // UI-specific configuration
        "title": "Products",
        "fields": {
            "id": {
                "label": "ID",
                "show_in": ["detail"]
            },
            "name": {
                "label": "Product Name",
                "show_in": ["list", "form", "detail"],
                "sortable": true,
                "filterable": true
            }
        }
    },
    
    "relations": {              // Laravel/Sequelize pattern
        "category": {
            "type": "belongsTo",
            "model": "categories",
            "foreignKey": "category_id"
        },
        "tags": {
            "type": "belongsToMany",
            "model": "tags",
            "through": "product_tags"
        }
    }
}
```

### 3. Simplified Hybrid Approach (Recommended)

Keep CRUD6's simplicity while borrowing ORM patterns:

```json
{
    "model": "products",
    "table": "products",
    "timestamps": true,
    "primaryKey": "id",
    
    "fields": {
        "id": {
            "type": "integer",
            "autoIncrement": true,
            "nullable": false,
            "ui": {
                "label": "ID",
                "show_in": ["detail"]
            }
        },
        "name": {
            "type": "string",
            "length": 255,
            "nullable": false,
            "validate": {
                "min": 2,
                "max": 255
            },
            "ui": {
                "label": "Product Name",
                "show_in": ["list", "form", "detail"],
                "sortable": true,
                "filterable": true
            }
        },
        "category_id": {
            "type": "integer",
            "nullable": false,
            "references": {                    // Prisma/TypeORM pattern
                "model": "categories",
                "key": "id",
                "display": "name"
            },
            "ui": {
                "label": "Category",
                "type": "lookup",              // UI type vs data type
                "show_in": ["list", "form", "detail"]
            }
        },
        "is_active": {
            "type": "boolean",
            "default": true,
            "ui": {
                "label": "Active",
                "widget": "toggle",            // UI widget selection
                "show_in": ["list", "form", "detail"]
            }
        }
    },
    
    "relations": {
        "category": {
            "type": "belongsTo",
            "foreignKey": "category_id",
            "references": "categories.id"
        }
    }
}
```

## Key Changes for ORM Alignment

### 1. Use `nullable` instead of `required`
```json
"nullable": false   // Matches Laravel, Sequelize, TypeORM, Django
```

### 2. Use `autoIncrement` for IDs
```json
"autoIncrement": true   // Matches Sequelize, TypeORM
```

### 3. Separate `validate` from `validation`
```json
"validate": {           // Sequelize pattern
    "min": 2,
    "max": 255,
    "isEmail": true
}
```

### 4. Add `references` for foreign keys
```json
"references": {         // Prisma/TypeORM pattern
    "model": "categories",
    "key": "id"
}
```

### 5. Use `ui` object for presentation
```json
"ui": {
    "label": "Product Name",
    "show_in": ["list", "form", "detail"],
    "sortable": true,
    "filterable": true,
    "widget": "text|toggle|select|lookup"
}
```

### 6. Use `relations` for relationships
```json
"relations": {
    "category": {
        "type": "belongsTo",      // Laravel/Sequelize
        "foreignKey": "category_id",
        "references": "categories.id"
    },
    "tags": {
        "type": "belongsToMany",  // Laravel/Sequelize
        "through": "product_tags"
    }
}
```

## Migration Strategy

### Phase 1: Add ORM-aligned attributes (maintain backward compatibility)
- Support both `nullable` and `required`
- Support both `autoIncrement` and `auto_increment`
- Add `references` as alternative to `lookup`
- Add `ui` object while keeping flat attributes

### Phase 2: Deprecation warnings
- Log warnings for old patterns
- Document migration path

### Phase 3: Default to ORM patterns
- New schemas use ORM-aligned patterns
- Legacy support remains

## Benefits of ORM Alignment

1. **Familiarity**: Developers already know these patterns
2. **Transferable Knowledge**: Laravel → CRUD6 → Sequelize
3. **Clear Separation**: Data model vs UI concerns
4. **Better Tooling**: IDE autocomplete, schema validation
5. **Documentation**: Can reference ORM docs for concepts
6. **Reduced Learning Curve**: Less CRUD6-specific knowledge needed

## Conclusion

By aligning with established ORM patterns, CRUD6 becomes:
- **More intuitive** for developers familiar with any major framework
- **Easier to learn** due to transferable knowledge
- **Better organized** with clear separation of concerns
- **More professional** following industry standards
