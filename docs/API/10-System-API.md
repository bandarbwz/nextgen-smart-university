# System API

## Purpose

This document defines the System REST APIs for the NextGen Smart University Platform.

The System API manages system configuration, audit logs, backups, application settings, health monitoring, maintenance mode, and system administration.

---

# Base URL

```
/api/v1/system
```

---

# Authentication

All endpoints require:

```
Authorization: Bearer <JWT_TOKEN>
```

Administrator privileges are required unless otherwise stated.

---

# Content Type

```
Content-Type: application/json
```

---

# System Configuration APIs

---

## Get System Settings

```
GET /api/v1/system/settings
```

---

## Update System Settings

```
PUT /api/v1/system/settings
```

Permissions

- Administrator

---

## Reset System Settings

```
POST /api/v1/system/settings/reset
```

Permissions

- Administrator

---

# Audit Log APIs

---

## Get Audit Logs

```
GET /api/v1/system/logs
```

---

## Get Audit Log

```
GET /api/v1/system/logs/{id}
```

---

## Export Audit Logs

```
GET /api/v1/system/logs/export
```

---

# Backup APIs

---

## Create Backup

```
POST /api/v1/system/backups
```

Permissions

- Administrator

---

## Get Backups

```
GET /api/v1/system/backups
```

---

## Restore Backup

```
POST /api/v1/system/backups/{id}/restore
```

Permissions

- Administrator

---

## Delete Backup

```
DELETE /api/v1/system/backups/{id}
```

---

# Maintenance APIs

---

## Enable Maintenance Mode

```
POST /api/v1/system/maintenance/enable
```

---

## Disable Maintenance Mode

```
POST /api/v1/system/maintenance/disable
```

---

## Get Maintenance Status

```
GET /api/v1/system/maintenance
```

---

# Health APIs

---

## System Health

```
GET /api/v1/system/health
```

---

## Database Status

```
GET /api/v1/system/database
```

---

## Storage Status

```
GET /api/v1/system/storage
```

---

## Queue Status

```
GET /api/v1/system/queues
```

---

# Validation Rules

System Settings

- Configuration key required.
- Configuration value required.

Backup

- Backup name must be unique.
- Backup location must be available.

Restore

- Backup file must exist.
- Backup integrity must be verified.

---

# Security

- JWT Authentication
- Role-Based Access Control
- Audit Logging
- Encrypted Backup Storage
- Secure Configuration Management

---

# HTTP Status Codes

| Code | Description |
|------|-------------|
|200|OK|
|201|Created|
|400|Bad Request|
|401|Unauthorized|
|403|Forbidden|
|404|Not Found|
|409|Conflict|
|422|Validation Error|
|500|Internal Server Error|

---

# Permissions

Administrator

- Manage System Settings
- Manage Backups
- Restore Database
- View Audit Logs
- Enable Maintenance Mode

---

# Business Rules

- Only administrators may modify system settings.
- Every configuration change is recorded in the audit log.
- Backup restoration is available only during maintenance mode.
- Audit logs cannot be modified or deleted.
- Health monitoring is updated automatically.
- Backups follow the configured retention policy.

---

# Dependencies

This API depends on:

- Authentication API

Related APIs

- Reports API
- Download Center API
- Notification API

---

# Notes

The System API provides centralized administration for the NextGen Smart University Platform, including configuration management, audit logging, backup and recovery, maintenance operations, and system health monitoring.

---

# As built

Updated 2026-08-05, when the module was implemented. The endpoints that exist
are these:

| Method | Path | Notes |
|--------|------|-------|
| GET | `/api/v1/settings` | The signed in user's own preferences |
| PUT | `/api/v1/settings` | Language, theme, time zone |
| GET | `/api/v1/system/settings` | Grouped by category |
| PUT | `/api/v1/system/settings` | Validated against each setting's declared type |
| GET | `/api/v1/system/health` | Database, storage, AI service, email |
| GET | `/api/v1/system/logs` | Filterable by severity and module |
| PUT | `/api/v1/system/maintenance` | One endpoint, `enabled` true or false |
| POST | `/api/v1/system/backup` | Returns 501, see below |

Four differences from the specification above, each deliberate:

**Maintenance is one endpoint, not three.** `/maintenance/enable` and
`/maintenance/disable` became a single `PUT` carrying `enabled`, which is what
the toggle in the interface actually sends. The status the third endpoint would
have returned is already in the health payload as `maintenance_mode`.

**`/database`, `/storage` and `/queues` are folded into `/health`.** The first
two are checks inside the one health response, so an administrator sees the
whole picture in a single request instead of three. There is no queue system in
this platform, so an endpoint reporting on one would report on nothing.

**The backup endpoints are not implemented.** `POST /system/backup` returns 501
and says backups are run with `mysqldump` outside the application. Restoring a
database from inside the application it is restoring is not safe, and a
`BackupHistory` table filled in by an application that never ran a backup would
be a list of events that did not happen. Listing, restoring and deleting
backups follow from a backup that was never taken, so none of them exist.

**Settings are not reset from the API.** `POST /system/settings/reset` would
overwrite live configuration in one call with no undo. The seed values are in
`database/schema/17-settings-system.sql` if they are ever needed.

The audit log the specification describes as `/system/logs/{id}` and
`/system/logs/export` is served by the list endpoint and by the Reports module,
which already exports. `SystemLog` records configuration changes and maintenance
events; authorization changes stay in `AuthorizationLog` where the Role module
writes them.