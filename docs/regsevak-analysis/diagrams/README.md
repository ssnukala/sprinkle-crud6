# RegSevak Documentation Diagrams

This folder contains visual diagrams and flowcharts to supplement the documentation.

## Available Diagrams

### Documentation Structure Diagram

```
RegSevak Analysis Documentation
├── README.md ─────────────────► Overview & Methodology
├── INDEX.md ──────────────────► Quick Navigation Guide
│
├── Architecture & Design
│   └── 01-overview.md ────────► Application Overview
│       ├── RegSevak Purpose
│       ├── Technology Stack
│       ├── Directory Structure
│       └── Data Models
│
├── User Interface
│   ├── 02-rsdashboard-flow.md ► Main Dashboard Flow
│   │   ├── Route Definition
│   │   ├── Controller Logic
│   │   ├── Template Structure
│   │   └── JavaScript Init
│   │
│   ├── 05-user-flows.md ──────► User Workflows
│   │   ├── Registration Submission
│   │   ├── Status Checking
│   │   ├── Document Upload
│   │   └── Profile Management
│   │
│   └── 06-admin-flows.md ─────► Admin Workflows
│       ├── Review & Approval
│       ├── Batch Operations
│       ├── Search & Filter
│       └── Reports & Analytics
│
├── Backend & Data
│   ├── 03-datatables-integration.md ► DataTables & Sprunje
│   │   ├── ufTable Implementation
│   │   ├── Sprunje Processing
│   │   ├── Custom Columns
│   │   └── Export Features
│   │
│   └── 04-crud-operations.md ─► CRUD Implementation
│       ├── Create Operation
│       ├── Read Operation
│       ├── Update Operation
│       ├── Delete Operation
│       └── Batch Operations
│
├── Analysis
│   └── 07-key-features.md ────► Feature Analysis
│       ├── Registration Features
│       ├── DataTables Features
│       ├── User Management
│       ├── Workflow Features
│       └── Integration Features
│
└── Migration
    └── 08-migration-guide.md ─► UF 4.6.7 → UF 6
        ├── Breaking Changes
        ├── Migration Strategy
        ├── Schema Conversion
        ├── Testing Strategy
        └── Deployment Plan
```

## Application Flow Diagrams

### User Registration Flow

```
┌─────────────┐
│ New User    │
│ Visits Site │
└──────┬──────┘
       │
       ▼
┌─────────────┐
│ Register    │
│ Account     │
└──────┬──────┘
       │
       ▼
┌─────────────┐
│ Verify      │
│ Email       │
└──────┬──────┘
       │
       ▼
┌─────────────┐
│ Login to    │
│ /rsdashboard│
└──────┬──────┘
       │
       ▼
┌─────────────┐
│ Submit      │
│ Registration│
└──────┬──────┘
       │
       ▼
┌─────────────┐
│ Upload      │
│ Documents   │
└──────┬──────┘
       │
       ▼
┌─────────────┐
│ Monitor     │
│ Status      │
└─────────────┘
```

### Admin Review Flow

```
┌─────────────┐
│ Admin       │
│ Login       │
└──────┬──────┘
       │
       ▼
┌─────────────┐
│ Dashboard   │
│ Overview    │
└──────┬──────┘
       │
       ▼
┌─────────────┐
│ View Pending│
│ Registrations│
└──────┬──────┘
       │
       ▼
┌─────────────┐
│ Review      │
│ Details     │
└──────┬──────┘
       │
       ├─────────────┬─────────────┬─────────────┐
       │             │             │             │
       ▼             ▼             ▼             ▼
┌─────────┐   ┌─────────┐   ┌─────────┐   ┌─────────┐
│ Approve │   │ Reject  │   │Request  │   │ On Hold │
│         │   │         │   │ Info    │   │         │
└────┬────┘   └────┬────┘   └────┬────┘   └────┬────┘
     │             │             │             │
     └─────────────┴─────────────┴─────────────┘
                   │
                   ▼
            ┌─────────────┐
            │ Notify User │
            └─────────────┘
```

### Data Flow Diagram

```
┌─────────────────────────────────────────────────────────┐
│                    User Interface                        │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐              │
│  │Dashboard │  │ Forms    │  │ Tables   │              │
│  └────┬─────┘  └────┬─────┘  └────┬─────┘              │
└───────┼─────────────┼─────────────┼────────────────────┘
        │             │             │
        ▼             ▼             ▼
┌─────────────────────────────────────────────────────────┐
│                  API Layer (REST)                        │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐              │
│  │  GET     │  │  POST    │  │ PUT/DEL  │              │
│  └────┬─────┘  └────┬─────┘  └────┬─────┘              │
└───────┼─────────────┼─────────────┼────────────────────┘
        │             │             │
        ▼             ▼             ▼
┌─────────────────────────────────────────────────────────┐
│               Controllers / Actions                      │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐              │
│  │ List     │  │ Create   │  │ Update   │              │
│  └────┬─────┘  └────┬─────┘  └────┬─────┘              │
└───────┼─────────────┼─────────────┼────────────────────┘
        │             │             │
        ▼             ▼             ▼
┌─────────────────────────────────────────────────────────┐
│            Business Logic / Services                     │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐              │
│  │Validation│  │Workflow  │  │Notification│            │
│  └────┬─────┘  └────┬─────┘  └────┬─────┘              │
└───────┼─────────────┼─────────────┼────────────────────┘
        │             │             │
        ▼             ▼             ▼
┌─────────────────────────────────────────────────────────┐
│                Data Access Layer                         │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐              │
│  │ Models   │  │ Sprunje  │  │Eloquent  │              │
│  └────┬─────┘  └────┬─────┘  └────┬─────┘              │
└───────┼─────────────┼─────────────┼────────────────────┘
        │             │             │
        ▼             ▼             ▼
┌─────────────────────────────────────────────────────────┐
│                    Database                              │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐              │
│  │  users   │  │registrations││documents│              │
│  └──────────┘  └──────────┘  └──────────┘              │
└─────────────────────────────────────────────────────────┘
```

### Registration Status State Machine

```
     ┌─────────┐
     │  Draft  │ (User editing)
     └────┬────┘
          │
          │ Submit
          ▼
     ┌─────────┐
     │ Pending │ (Awaiting review)
     └────┬────┘
          │
          │ Admin review
          ▼
     ┌──────────┐
     │In Review │
     └─────┬────┘
           │
    ┌──────┼──────┬──────────┐
    │      │      │          │
    ▼      ▼      ▼          ▼
┌────────┐ │ ┌────────┐ ┌────────┐
│Approved│ │ │Rejected│ │On Hold │
└────────┘ │ └────────┘ └───┬────┘
           │                 │
           │ Request Info    │ Resume
           ▼                 │
    ┌──────────────┐         │
    │Info Requested│◄────────┘
    └──────┬───────┘
           │
           │ User provides info
           ▼
    ┌──────────┐
    │ Pending  │
    └──────────┘
```

## Technology Stack Diagram

```
┌─────────────────────────────────────────────────────────┐
│                   Frontend Layer                         │
├─────────────────────────────────────────────────────────┤
│  Browser                                                 │
│  ├── HTML5                                              │
│  ├── CSS3 / Bootstrap                                   │
│  ├── JavaScript                                         │
│  │   ├── jQuery                                         │
│  │   ├── DataTables                                     │
│  │   └── Custom Scripts                                 │
│  └── Twig Templates                                     │
└─────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────┐
│                Application Layer                         │
├─────────────────────────────────────────────────────────┤
│  UserFrosting 4.6.7                                     │
│  ├── Slim Framework 3.x                                 │
│  ├── Sprinkle Architecture                              │
│  │   └── RegSevak Sprinkle                             │
│  ├── Controllers                                        │
│  ├── Middleware                                         │
│  ├── Services                                           │
│  └── Sprunje (DataTables processing)                   │
└─────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────┐
│                  Data Layer                              │
├─────────────────────────────────────────────────────────┤
│  Eloquent ORM                                           │
│  ├── Models                                             │
│  ├── Relationships                                      │
│  ├── Query Builder                                      │
│  └── Migrations                                         │
└─────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────┐
│                  Database                                │
├─────────────────────────────────────────────────────────┤
│  MySQL / PostgreSQL                                     │
│  ├── users                                              │
│  ├── registrations                                      │
│  ├── documents                                          │
│  ├── roles                                              │
│  ├── permissions                                        │
│  └── ...                                                │
└─────────────────────────────────────────────────────────┘
```

## Migration Path Diagram

```
┌────────────────────────────────────────────────────────┐
│         UserFrosting 4.6.7 (RegSevak)                  │
├────────────────────────────────────────────────────────┤
│  PHP 7.x                                               │
│  Slim 3.x                                              │
│  Custom Controllers                                     │
│  jQuery + DataTables                                   │
│  Twig 1.x                                              │
└────────────────┬───────────────────────────────────────┘
                 │
                 │ Migration Process
                 │
        ┌────────┴────────┐
        │                 │
        ▼                 ▼
┌──────────────┐  ┌──────────────┐
│ Code         │  │ Data         │
│ Migration    │  │ Migration    │
├──────────────┤  ├──────────────┤
│- Update PHP  │  │- Export DB   │
│- Controllers │  │- Transform   │
│- Routes      │  │- Import      │
│- Templates   │  │- Verify      │
└──────┬───────┘  └──────┬───────┘
       │                 │
       └────────┬────────┘
                │
                ▼
┌────────────────────────────────────────────────────────┐
│         UserFrosting 6 (with CRUD6)                    │
├────────────────────────────────────────────────────────┤
│  PHP 8.1+                                              │
│  Slim 4.x                                              │
│  Action Controllers                                     │
│  Vue.js 3 + TypeScript                                 │
│  Twig 3.x                                              │
│  JSON Schema Models                                     │
│  sprinkle-crud6                                        │
└────────────────────────────────────────────────────────┘
```

## Permission Hierarchy

```
                    ┌──────────────┐
                    │ Super Admin  │
                    └──────┬───────┘
                           │
            ┌──────────────┼──────────────┐
            │              │              │
            ▼              ▼              ▼
    ┌──────────┐   ┌──────────┐   ┌──────────┐
    │  Admin   │   │Registration│  │ Support  │
    │          │   │  Manager   │  │  Staff   │
    └────┬─────┘   └────┬───────┘  └────┬─────┘
         │              │               │
         └──────────────┼───────────────┘
                        │
                        ▼
                  ┌──────────┐
                  │   User   │
                  └──────────┘

Permissions:
├── Super Admin
│   ├── All admin permissions
│   ├── Manage users
│   ├── Manage system settings
│   └── Access all data
│
├── Admin
│   ├── View all registrations
│   ├── Approve/reject registrations
│   ├── Generate reports
│   └── Manage regular users
│
├── Registration Manager
│   ├── View all registrations
│   ├── Approve/reject registrations
│   └── Update registration fields
│
├── Support Staff
│   ├── View registrations
│   └── Request information
│
└── User
    ├── Create registrations
    ├── View own registrations
    ├── Edit pending registrations
    └── Upload documents
```

## Creating Custom Diagrams

To add your own diagrams:

1. Create ASCII art diagrams in markdown files
2. Use tools like:
   - [Draw.io](https://www.draw.io/) for flowcharts
   - [PlantUML](https://plantuml.com/) for UML diagrams
   - [Mermaid](https://mermaid-js.github.io/) for markdown diagrams
3. Export as PNG or SVG and place in this folder
4. Reference in documentation with relative paths

Example:
```markdown
![User Flow Diagram](diagrams/user-flow.png)
```

## Diagram Best Practices

1. **Keep it Simple** - Focus on one concept per diagram
2. **Use Consistent Symbols** - Maintain visual language
3. **Label Clearly** - Every element should be labeled
4. **Show Direction** - Use arrows to indicate flow
5. **Include Legend** - If using custom symbols

## Related Documentation

- [README.md](../README.md) - Documentation overview
- [INDEX.md](../INDEX.md) - Navigation guide
- All analysis documents reference these diagrams
