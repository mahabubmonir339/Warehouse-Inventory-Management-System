# RFID Module - Configuration Examples

## Environment Configuration (.env)

Add these optional settings to your `.env` file:

```env
# RFID Configuration
RFID_DUPLICATE_COOLDOWN=2
RFID_DEFAULT_WAREHOUSE=1
RFID_ENABLE_BULK_SCAN=true
RFID_LOG_RETENTION_DAYS=90
RFID_MAX_READERS_PER_WAREHOUSE=10
RFID_READER_TIMEOUT=30
```

---

## Service Configuration

### RfidScanProcessor Constants

```php
// In app/Services/Rfid/RfidScanProcessor.php

const DUPLICATE_COOLDOWN = 2;      // seconds - prevent rapid duplicate scans
const ACTIONS = ['IN', 'OUT', 'MOVE', 'TRANSFER'];
```

Modify these constants based on your requirements:

```php
// For slower scanners, increase cooldown
const DUPLICATE_COOLDOWN = 5;  // 5 seconds

// For faster scanners, decrease cooldown
const DUPLICATE_COOLDOWN = 1;  // 1 second
```

---

## Reader Configuration Examples

### Main Gate Reader

```json
{
  "name": "Main Gate - Entrance",
  "ip_address": "192.168.1.100",
  "warehouse_id": 1,
  "location": "main_gate_in",
  "status": "active",
  "read_range": 5,
  "frequency": "UHF"
}
```

### Exit/Checkout Gate Reader

```json
{
  "name": "Exit Gate - Checkout",
  "ip_address": "192.168.1.101",
  "warehouse_id": 1,
  "location": "exit_gate",
  "status": "active",
  "read_range": 3,
  "frequency": "UHF"
}
```

### Zone-Based Reader

```json
{
  "name": "Storage Zone A",
  "ip_address": "192.168.1.102",
  "warehouse_id": 1,
  "location": "zone_a",
  "status": "active",
  "read_range": 8,
  "frequency": "UHF"
}
```

---

## Permission Role Configuration

### RFID Manager Role

Assign these permissions:
- `view-rfid-tags`
- `create-rfid-tags`
- `edit-rfid-tags`
- `assign-rfid-tags`
- `view-rfid-readers`
- `create-rfid-readers`
- `edit-rfid-readers`
- `test-rfid-readers`
- `view-rfid-logs`
- `view-rfid-live`
- `view-rfid-dashboard`
- `view-rfid-locations`
- `manage-rfid-settings`

### RFID Viewer Role

Assign these permissions:
- `view-rfid-tags`
- `view-rfid-readers`
- `view-rfid-logs`
- `view-rfid-live`
- `view-rfid-dashboard`
- `view-rfid-locations`

### RFID Operator Role

Assign these permissions:
- `view-rfid-tags`
- `assign-rfid-tags`
- `view-rfid-readers`
- `test-rfid-readers`
- `view-rfid-logs`
- `view-rfid-live`
- `view-rfid-locations`

---

## Hardware Configuration

### RFID Reader API Endpoint

Configure your RFID reader hardware to send data to:

```
POST http://your-domain.com/api/rfid/scan
Content-Type: application/json

{
  "tag_uid": "READER_SCANNED_TAG_UID",
  "reader_id": READER_ID_FROM_SYSTEM,
  "signal_strength": OPTIONAL_SIGNAL_STRENGTH,
  "timestamp": OPTIONAL_UNIX_TIMESTAMP
}
```

### Example Reader Firmware Configuration

**Zebra FX7500 Reader**:
```
Connection: HTTP POST
URL: http://192.168.1.50/api/rfid/scan
Method: POST
Payload Format: JSON
Retry on Failure: Yes
Retry Count: 3
```

**Impinj Speedway Reader**:
```
HTTP Connection
Method: POST
URL: http://192.168.1.50/api/rfid/scan
Content-Type: application/json
Timeout: 30 seconds
```

---

## Database Optimization

### Recommended Indexes (Already Included)

The migrations include these indexes for performance:

```sql
-- rfid_readers
INDEX warehouse_id_status (warehouse_id, status)

-- rfid_scan_logs
INDEX warehouse_id_action (warehouse_id, action)
INDEX status (status)
INDEX created_at_warehouse (created_at, warehouse_id)

-- rfid_tags
INDEX account_id_is_active (account_id, is_active)
INDEX status (status)

-- rfid_tag_assignments
INDEX account_id_status (account_id, status)
INDEX item_id (item_id)

-- item_locations
UNIQUE item_id_warehouse (item_id, warehouse_id)
INDEX warehouse_id_zone (warehouse_id, zone)
INDEX last_seen_at (last_seen_at)
```

---

## Performance Tuning

### For High-Volume Scanning Environments

1. **Increase Cooldown** (to prevent duplicate processing)
```php
const DUPLICATE_COOLDOWN = 3; // 3 seconds
```

2. **Enable Bulk Scanning** (batch multiple scans)
```php
POST /api/rfid/bulk-scan  // More efficient than individual requests
```

3. **Archive Old Logs** (maintain database size)
```bash
# Delete scans older than 90 days
php artisan rfid:archive-logs --days=90
```

4. **Use Database Connection Pooling**
```php
// In config/database.php
'redis' => [
    'connection' => 'pool',
    'pool_size' => 10,
],
```

---

## API Rate Limiting

### Suggested Limits

For production, add rate limiting middleware:

```php
// In app/Http/Middleware/ApiRateLimit.php

Route::middleware(['api', 'throttle:1000,1'])->group(function () {
    Route::post('/rfid/scan', [RfidScanController::class, 'processScan']);
    Route::post('/rfid/bulk-scan', [RfidScanController::class, 'bulkScan']);
});

Route::middleware(['api:sanctum', 'throttle:300,1'])->group(function () {
    // Authenticated endpoints
});
```

---

## IP Whitelisting

### For Production Security

```php
// In routes/api.php

Route::middleware(['ip-whitelist:rfid'])->group(function () {
    Route::post('/rfid/scan', [RfidScanController::class, 'processScan']);
    Route::post('/rfid/bulk-scan', [RfidScanController::class, 'bulkScan']);
});
```

**Whitelist Configuration** (config/ip-whitelist.php):
```php
'rfid' => [
    '192.168.1.100',  // Main gate reader
    '192.168.1.101',  // Exit gate reader
    '192.168.1.102',  // Zone A reader
    '192.168.1.0/24', // Entire subnet
],
```

---

## Logging Configuration

### Application Logs

Create custom log channel for RFID:

```php
// In config/logging.php

'channels' => [
    'rfid' => [
        'driver' => 'single',
        'path' => storage_path('logs/rfid.log'),
        'level' => 'debug',
    ],
],
```

**Usage in Code**:
```php
Log::channel('rfid')->info('RFID scan processed', [
    'tag_uid' => $tagUid,
    'item_id' => $itemId,
    'action' => $action,
]);
```

---

## Monitoring & Alerts

### Key Metrics to Monitor

1. **Scan Success Rate**
```sql
SELECT 
    status,
    COUNT(*) as count,
    ROUND(COUNT(*) / (SELECT COUNT(*) FROM rfid_scan_logs WHERE DATE(created_at) = CURDATE()) * 100, 2) as percentage
FROM rfid_scan_logs
WHERE DATE(created_at) = CURDATE()
GROUP BY status;
```

2. **Reader Performance**
```sql
SELECT 
    reader_id,
    COUNT(*) as scans,
    COUNT(DISTINCT item_id) as unique_items,
    ROUND(COUNT(*) / (COUNT(*) + COUNT(CASE WHEN status = 'failed' THEN 1 END)) * 100, 2) as success_rate
FROM rfid_scan_logs
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
GROUP BY reader_id;
```

3. **Duplicate Rate**
```sql
SELECT 
    COUNT(*) as duplicates,
    ROUND(COUNT(*) / (SELECT COUNT(*) FROM rfid_scan_logs) * 100, 2) as duplicate_percentage
FROM rfid_scan_logs
WHERE status = 'duplicate';
```

---

## Cache Configuration

### Enable Redis for Performance

```php
// In .env
CACHE_DRIVER=redis
REDIS_CLIENT=phpredis

// In config/database.php
'redis' => [
    'client' => env('REDIS_CLIENT', 'phpredis'),
    'default' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD', null),
        'port' => env('REDIS_PORT', 6379),
        'database' => env('REDIS_CACHE_DB', 1),
    ],
],
```

### Cache Recent Scans

```php
// In RfidScanProcessor
$recentScans = Cache::remember('rfid.recent_scans.' . $warehouseId, 300, function () {
    return RfidScanLog::where('warehouse_id', $warehouseId)
        ->latest('created_at')
        ->limit(100)
        ->get();
});
```

---

## Queue Configuration

For high-volume environments, consider queuing long operations:

```php
// In app/Jobs/ProcessRfidScan.php

class ProcessRfidScan implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public function handle() {
        // Process scan asynchronously
    }
}
```

---

## Disaster Recovery

### Database Backup Strategy

```bash
# Daily backup
0 2 * * * /path/to/backup_rfid_db.sh

# Monthly archive
0 3 1 * * /path/to/archive_rfid_logs.sh
```

**Backup Script** (`backup_rfid_db.sh`):
```bash
#!/bin/bash
BACKUP_DIR="/backups/rfid"
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u user -p'password' warehouse_db | gzip > "$BACKUP_DIR/rfid_backup_$DATE.sql.gz"
```

---

This completes the configuration documentation! 🎉
