# RFID Module - Quick Start Guide

## 5-Minute Setup

### Step 1: Run Migrations

```bash
php artisan migrate
```

This creates all necessary RFID tables:
- `rfid_tags` - RFID tag storage
- `rfid_readers` - Reader device management
- `rfid_scan_logs` - Scan history and logs
- `rfid_tag_assignments` - Tag-to-item mappings
- `item_locations` - Real-time item locations

### Step 2: Seed Permissions

```bash
php artisan db:seed --class=RfidPermissionsSeeder
```

Creates 16 RFID-related permissions for role management.

### Step 3: Register Service Provider

Add to `config/app.php` providers array:

```php
'providers' => [
    // ...
    App\Providers\RfidServiceProvider::class,
],
```

### Step 4: Create Your First RFID Reader

```bash
curl -X POST http://localhost/api/rfid/readers \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -d '{
    "name": "Main Entrance",
    "ip_address": "192.168.1.100",
    "warehouse_id": 1,
    "location": "main_gate",
    "read_range": 5,
    "status": "active"
  }'
```

### Step 5: Create RFID Tags

```bash
curl -X POST http://localhost/api/rfid/tags \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -d '{
    "tag_code": "E200001234",
    "tag_type": "UHF"
  }'
```

### Step 6: Assign Tag to Item

```bash
curl -X POST http://localhost/api/rfid/tags/1/assign \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -d '{
    "item_id": 5
  }'
```

### Step 7: Test Scanner

Configure your RFID reader hardware to send scans to:

```
POST http://your-domain.com/api/rfid/scan
```

With JSON payload:
```json
{
  "tag_uid": "E200001234",
  "reader_id": 1
}
```

**That's it!** Your RFID system is now operational! ✅

---

## Common Operations

### Check Recent Scans

```bash
curl "http://localhost/api/rfid/recent-scans?warehouse_id=1&minutes=60" \
  -H "Authorization: Bearer YOUR_API_TOKEN"
```

### Get Scan Statistics

```bash
curl "http://localhost/api/rfid/scan-summary?warehouse_id=1" \
  -H "Authorization: Bearer YOUR_API_TOKEN"
```

### View All Logs

```bash
curl "http://localhost/api/rfid/scan-logs?warehouse_id=1&days=7" \
  -H "Authorization: Bearer YOUR_API_TOKEN"
```

### Check Reader Health

```bash
curl -X POST "http://localhost/api/rfid/readers/1/health" \
  -H "Authorization: Bearer YOUR_API_TOKEN"
```

### List All Active Readers

```bash
curl "http://localhost/api/rfid/readers?status=active" \
  -H "Authorization: Bearer YOUR_API_TOKEN"
```

---

## Testing Manually

### Using Postman

1. Create a new POST request
2. URL: `http://localhost/api/rfid/scan`
3. Body (raw JSON):
```json
{
  "tag_uid": "E200001234",
  "reader_id": 1,
  "signal_strength": 85
}
```
4. Click Send

You should get a 200 response with item details and action taken.

---

## Troubleshooting

### "Tag not found or inactive"
- Ensure tag exists in database
- Check tag status is 'active'
- Verify tag_code matches exactly

### "Item not assigned to this tag"
- Go to `/rfid/assign` page
- Select the tag and item
- Click "Assign Tag"

### "Reader not found or inactive"
- Check reader IP and status in `/rfid/readers`
- Update reader status to 'active'
- Test reader connectivity with `/api/rfid/readers/{id}/health`

### Scans not showing in logs
- Check database has migrated: `php artisan migrate --refresh`
- Verify account_id is set correctly
- Check Laravel logs in `storage/logs/`

---

## Next Steps

1. **Configure Role Permissions**
   - Go to Settings → Roles
   - Assign RFID permissions to appropriate roles

2. **Monitor Live Scans**
   - Go to RFID → Live Monitor
   - Watch real-time scan activity

3. **Review Scan History**
   - Go to RFID → Scan Logs
   - Filter by warehouse, action, date range

4. **Manage Item Locations**
   - Go to RFID → Item Locations
   - See where each item was last scanned

---

## API Endpoints Summary

### Public (No Auth)
- `POST /api/rfid/scan` - Process single scan
- `POST /api/rfid/bulk-scan` - Process multiple scans

### Tags (Authenticated)
- `GET /api/rfid/tags` - List tags
- `POST /api/rfid/tags` - Create tag
- `POST /api/rfid/tags/{id}/assign` - Assign to item
- `DELETE /api/rfid/tags/{id}` - Delete tag

### Readers (Authenticated)
- `GET /api/rfid/readers` - List readers
- `POST /api/rfid/readers` - Add reader
- `PUT /api/rfid/readers/{id}` - Update reader
- `POST /api/rfid/readers/{id}/health` - Check status

### Logs (Authenticated)
- `GET /api/rfid/scan-logs` - View logs
- `GET /api/rfid/recent-scans` - Recent scans
- `GET /api/rfid/scan-summary` - Statistics

---

**Ready to go!** Start scanning! 🚀
