# RFID Automation Module - Complete Implementation Guide

## Overview

This document provides comprehensive implementation details for the fully integrated RFID automation module integrated into your Laravel-based Warehouse Inventory Management System (WIMS).

---

## ✅ What's Included

### 1. **Database Migrations**

Located in `database/migrations/`:

- `2026_04_20_000001_create_rfid_readers_table.php` - RFID reader devices
- `2026_04_20_000002_create_item_locations_table.php` - Real-time item location tracking
- `2026_04_20_000003_enhance_rfid_scan_logs_table.php` - Enhanced scan logs with full metadata
- `2026_04_20_000004_enhance_rfid_tags_table.php` - Enhanced tag management
- `2026_04_20_000005_enhance_rfid_tag_assignments_table.php` - Enhanced tag assignments

**Run migrations:**
```bash
php artisan migrate
```

---

### 2. **Models**

All models include relationships and scopes:

#### Core RFID Models

- **`RfidTag`** (`app/Models/RfidTag.php`)
  - Relations: `assignment()`, `item()`, `scans()`
  - Scopes: `active()`, `ofAccount()`, `unassigned()`

- **`RfidReader`** (`app/Models/RfidReader.php`)
  - Relations: `warehouse()`, `scans()`, `itemLocations()`
  - Scopes: `active()`, `ofWarehouse()`, `ofAccount()`

- **`RfidScanLog`** (`app/Models/RfidScanLog.php`)
  - Relations: `item()`, `reader()`, `warehouse()`, `tag()`
  - Scopes: `ofWarehouse()`, `ofAccount()`, `byAction()`, `processed()`, `pending()`, `recent()`

- **`RfidTagAssignment`** (`app/Models/RfidTagAssignment.php`)
  - Relations: `item()`, `tag()`, `assignedBy()`, `scans()`
  - Scopes: `active()`, `ofAccount()`

- **`ItemLocation`** (`app/Models/ItemLocation.php`)
  - Relations: `item()`, `warehouse()`, `lastReader()`
  - Scopes: `ofItem()`, `ofWarehouse()`, `ofAccount()`, `recentlyUpdated()`

#### Extended Models

- **`Item`** - Added relationships: `rfidAssignment()`, `locations()`, `currentLocation()`, `locationInWarehouse()`

---

### 3. **Services** (Core Business Logic)

Located in `app/Services/Rfid/`:

#### **RfidScanProcessor** (`RfidScanProcessor.php`)
Main service for processing RFID scans:

```php
// Process a scan
$processor = new RfidScanProcessor();
$result = $processor->processScan($tagUid, $readerId, $metadata);

// Get recent scans
$scans = $processor->getRecentScans($warehouseId, 60);

// Get summary
$summary = $processor->getScanSummary($warehouseId, 60);
```

**Features:**
- Duplicate scan prevention (cooldown logic)
- Automatic action detection
- Inventory system integration
- Real-time location tracking
- Comprehensive error handling

#### **RfidActionResolver** (`RfidActionResolver.php`)
Intelligent action detection:

- **IN** - Item entering warehouse for first time
- **OUT** - Item leaving (checkout/exit gate)
- **MOVE** - Item moved within same warehouse
- **TRANSFER** - Item moved to different warehouse

```php
$resolver = new RfidActionResolver();
$action = $resolver->detectAction($item, $reader, $metadata);
```

#### **RfidSyncService** (`RfidSyncService.php`)
Synchronization with existing inventory system:

- Creates `Checkin` records for IN actions
- Creates `Checkout` records for OUT actions
- Creates `Transfer` records for TRANSFER actions
- Logs MOVE actions
- Updates stock automatically
- Supports scan rollback/cancellation

```php
$syncService = new RfidSyncService();
$result = $syncService->syncWithInventorySystem($item, $reader, $action, $scanLog);
```

---

### 4. **API Controllers**

Located in `app/Http/Controllers/Api/`:

#### **RfidScanController**
```php
// Public endpoint - no auth required
POST /api/rfid/scan
POST /api/rfid/bulk-scan

// Authenticated endpoints
GET /api/rfid/recent-scans
GET /api/rfid/scan-summary
GET /api/rfid/scan-logs
DELETE /api/rfid/scan-logs/{id}
```

#### **RfidTagController**
```php
// Tag CRUD
GET    /api/rfid/tags
POST   /api/rfid/tags
GET    /api/rfid/tags/{tag}
PUT    /api/rfid/tags/{tag}
DELETE /api/rfid/tags/{tag}

// Tag Assignment
POST /api/rfid/tags/{tag}/assign
POST /api/rfid/tags/{tag}/unassign
POST /api/rfid/tags/bulk-create
```

#### **RfidReaderController**
```php
// Reader CRUD
GET    /api/rfid/readers
POST   /api/rfid/readers
GET    /api/rfid/readers/{reader}
PUT    /api/rfid/readers/{reader}
DELETE /api/rfid/readers/{reader}

// Reader Operations
POST /api/rfid/readers/{reader}/health
GET  /api/rfid/readers/{reader}/scans
PUT  /api/rfid/readers/{reader}/status
```

---

### 5. **Web Controller**

**RfidController** (`app/Http/Controllers/RfidController.php`)

Handles Inertia/Vue page rendering:

```php
// Pages/Routes
GET  /rfid/live           -> Live scan monitor
GET  /rfid/assign         -> Tag assignment interface
POST /rfid/assign         -> Store tag assignment
GET  /rfid/logs           -> Scan logs viewer
GET  /rfid/readers        -> Readers management
GET  /rfid/locations      -> Item location tracking
GET  /rfid/settings       -> RFID settings
```

---

### 6. **API Routes**

Located in `routes/api.php`:

```php
// Public endpoint (no auth required for hardware)
POST /api/rfid/scan
POST /api/rfid/bulk-scan

// Authenticated REST API
GET    /api/rfid/recent-scans
GET    /api/rfid/scan-summary
GET    /api/rfid/scan-logs
DELETE /api/rfid/scan-logs/{id}

// Tags
GET    /api/rfid/tags
POST   /api/rfid/tags
GET    /api/rfid/tags/{tag}
PUT    /api/rfid/tags/{tag}
DELETE /api/rfid/tags/{tag}
POST   /api/rfid/tags/{tag}/assign
POST   /api/rfid/tags/{tag}/unassign
POST   /api/rfid/tags/bulk-create

// Readers
GET    /api/rfid/readers
POST   /api/rfid/readers
GET    /api/rfid/readers/{reader}
PUT    /api/rfid/readers/{reader}
DELETE /api/rfid/readers/{reader}
POST   /api/rfid/readers/{reader}/health
GET    /api/rfid/readers/{reader}/scans
PUT    /api/rfid/readers/{reader}/status
```

---

### 7. **Vue Components**

Located in `resources/js/Pages/Rfid/`:

- **LiveMonitor.vue** - Real-time scan dashboard with statistics
- **TagAssignmentPanel.vue** - Assign tags to items
- **ScanLogs.vue** - Browse and filter scan logs
- **ReadersManagement.vue** - Manage RFID reader devices

---

### 8. **Permissions**

Located in `database/seeders/RfidPermissionsSeeder.php`:

Run seeder:
```bash
php artisan db:seed --class=RfidPermissionsSeeder
```

**Permissions included:**
- `view-rfid-tags` - View tags
- `create-rfid-tags` - Create tags
- `edit-rfid-tags` - Edit tags
- `delete-rfid-tags` - Delete tags
- `assign-rfid-tags` - Assign to items
- `view-rfid-readers` - View readers
- `create-rfid-readers` - Add readers
- `edit-rfid-readers` - Edit readers
- `delete-rfid-readers` - Delete readers
- `test-rfid-readers` - Test connectivity
- `view-rfid-logs` - View scan logs
- `export-rfid-logs` - Export logs
- `delete-rfid-logs` - Delete logs
- `view-rfid-live` - Live monitor
- `view-rfid-dashboard` - Dashboard access
- `view-rfid-locations` - Location tracking
- `manage-rfid-settings` - Settings access

---

## 🚀 Usage Guide

### 1. **Initial Setup**

```bash
# Run all migrations
php artisan migrate

# Seed permissions
php artisan db:seed --class=RfidPermissionsSeeder
```

### 2. **Create RFID Readers**

```bash
curl -X POST http://localhost/api/rfid/readers \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "name": "Main Gate",
    "ip_address": "192.168.1.100",
    "warehouse_id": 1,
    "location": "main_gate",
    "read_range": 5,
    "frequency": "UHF"
  }'
```

### 3. **Create RFID Tags**

```bash
curl -X POST http://localhost/api/rfid/tags \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "tag_code": "E200001234",
    "tag_type": "UHF"
  }'
```

### 4. **Assign Tag to Item**

```bash
curl -X POST http://localhost/api/rfid/tags/1/assign \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "item_id": 5
  }'
```

### 5. **Process RFID Scan** (from hardware)

```bash
# Public endpoint - no auth required
curl -X POST http://localhost/api/rfid/scan \
  -H "Content-Type: application/json" \
  -d '{
    "tag_uid": "E200001234",
    "reader_id": 1,
    "signal_strength": 85
  }'
```

### 6. **Bulk Scans**

```bash
curl -X POST http://localhost/api/rfid/bulk-scan \
  -H "Content-Type: application/json" \
  -d '{
    "scans": [
      {"tag_uid": "E200001234", "reader_id": 1},
      {"tag_uid": "E200001235", "reader_id": 1},
      {"tag_uid": "E200001236", "reader_id": 2}
    ]
  }'
```

---

## 📊 Scan Processing Flow

```
1. RFID Hardware → /api/rfid/scan (tag_uid + reader_id)
                        ↓
2. RfidScanProcessor validates tag & reader
                        ↓
3. RfidActionResolver detects action (IN/OUT/MOVE/TRANSFER)
                        ↓
4. Create RfidScanLog entry (pending status)
                        ↓
5. RfidSyncService syncs with inventory:
   - IN  → Create Checkin + CheckinItem, increase stock
   - OUT → Create Checkout + CheckoutItem, decrease stock
   - MOVE → Log movement in item_locations
   - TRANSFER → Create Transfer + TransferItem, update stock in both warehouses
                        ↓
6. Update ItemLocation with real-time location
                        ↓
7. Mark scan as processed
                        ↓
8. Return success response
```

---

## 🔐 Security Considerations

1. **Public Scan Endpoint** - No authentication required (for hardware)
   - Implement IP whitelisting in production
   - Rate limiting on API endpoint
   - CSRF protection disabled for hardware endpoint

2. **Authenticated Endpoints** - Require API token
   - All management operations require authentication
   - Permission checks on all routes

3. **Data Validation**
   - Tag UID, Reader ID, and Warehouse validation
   - Duplicate scan prevention (cooldown logic)
   - Stock consistency checks

---

## 🛠️ Advanced Features

### Duplicate Scan Prevention

```php
// Automatically prevents scans within 2 seconds
const DUPLICATE_COOLDOWN = 2;

// Returns 409 status code for duplicates
{
  "status": "duplicate",
  "message": "Duplicate scan detected (cooldown)"
}
```

### Real-Time Location Tracking

```php
// Get item's current location in warehouse
$location = $item->locationInWarehouse($warehouseId);
// Returns: warehouse_id, zone, last_seen_at, last_reader_id
```

### Scan Rollback

```php
// Rollback a scan and reverse inventory transaction
$syncService->rollbackScan($scanLog);
```

### Summary Statistics

```php
$summary = $processor->getScanSummary($warehouseId, $minutes);
// Returns: total_scans, by_action, by_status, unique_items, success_rate
```

---

## 📝 Database Schema

### rfid_readers
```
id, name, ip_address, warehouse_id, location, status, read_range, 
frequency, account_id, created_at, updated_at, deleted_at
```

### rfid_tags (enhanced)
```
id, tag_code, tag_type, is_active, status, account_id, created_at, updated_at
```

### rfid_tag_assignments (enhanced)
```
id, rfid_tag_id, item_id, account_id, assigned_by, assigned_at, 
status, created_at, updated_at
```

### rfid_scan_logs (enhanced)
```
id, tag_code, item_id, reader_id, warehouse_id, location, action, 
related_model, related_id, status, scanned_at, account_id, 
created_at, updated_at
```

### item_locations
```
id, item_id, warehouse_id, zone, last_seen_at, last_reader_id, 
account_id, created_at, updated_at
```

---

## 🐛 Troubleshooting

### Scans not being processed
1. Verify reader status is `active`
2. Check tag exists and is assigned to an item
3. Verify account_id matches
4. Check scan logs for error status

### Stock not updating
1. Verify item has stock entry for warehouse
2. Check RfidSyncService executing successfully
3. Review transaction logs in rfid_scan_logs

### Duplicate scans
1. Ensure reader cooldown is appropriate
2. Check hardware scan rate
3. Review rfid_scan_logs with status='duplicate'

---

## 📞 Support

For issues or questions, refer to:
- API response messages (detailed error descriptions)
- rfid_scan_logs table (comprehensive scan history)
- Laravel logs (app/storage/logs/)

---

## ✨ Key Integration Points

1. **Existing Checkin/Checkout System** - Fully integrated
2. **Stock Management** - Automatic updates
3. **Transfer System** - Automatic transfer creation
4. **User Accounts** - Account-based data isolation
5. **Permissions System** - Full RBAC support
6. **Activity Logging** - All operations tracked

---

**Implementation Complete!** 🎉

Your WIMS now has a production-ready, fully automated RFID system integrated seamlessly with existing inventory operations.
