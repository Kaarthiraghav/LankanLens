# Database Migrations

This folder contains database migration scripts for incremental schema changes.

## Usage

- Name migrations with timestamp prefix: `YYYYMMDD_HHMMSS_description.sql`
- Example: `20260126_143000_add_vendor_rating_column.sql`
- Execute migrations in chronological order
- Always test migrations on development database first

## Migration Template

```sql
-- Migration: [Description]
-- Date: [YYYY-MM-DD]
-- Author: [Your Name]

-- Up Migration (Apply Changes)
ALTER TABLE table_name ADD COLUMN new_column VARCHAR(255);

-- Down Migration (Rollback Changes)
-- ALTER TABLE table_name DROP COLUMN new_column;
```

## Tracking

Keep a log of executed migrations in this README or create a `migrations_log` table.
