# RFID Module - Postman API Collection

This document provides a complete Postman collection for testing all RFID endpoints.

## Base URL
```
http://localhost/api
```

## Headers (for authenticated endpoints)
```
Authorization: Bearer YOUR_API_TOKEN
Content-Type: application/json
```

---

## Public Endpoints (No Auth Required)

### 1. Process Single RFID Scan

**POST** `/rfid/scan`

Request:
```json
{
  "tag_uid": "E200001234",
  "reader_id": 1,
  "signal_strength": 85
}
```

Response (Success):
```json
{
  "status": "success",
  "message": "Item 'Widget A' IN in Main Warehouse",
  "item_id": 5,
  "action": "IN",
  "scan_log_id": 123,
  "warehouse_id": 1
}
```

---

### 2. Bulk Process Scans

**POST** `/rfid/bulk-scan`

Request:
```json
{
  "scans": [
    {
      "tag_uid": "E200001234",
      "reader_id": 1,
      "signal_strength": 85
    },
    {
      "tag_uid": "E200001235",
      "reader_id": 1,
      "signal_strength": 80
    },
    {
      "tag_uid": "E200001236",
      "reader_id": 2,
      "signal_strength": 75
    }
  ]
}
```

Response:
```json
{
  "status": "completed",
  "total": 3,
  "successful": 3,
  "scans": [...]
}
```

---

## Authenticated Endpoints

### Recent Scans

**GET** `/rfid/recent-scans?warehouse_id=1&minutes=60`

Response:
```json
{
  "status": "success",
  "count": 45,
  "data": [...]
}
```

---

### Scan Summary

**GET** `/rfid/scan-summary?warehouse_id=1&minutes=60`

Response:
```json
{
  "status": "success",
  "data": {
    "total_scans": 150,
    "by_action": {
      "IN": 50,
      "OUT": 40,
      "MOVE": 35,
      "TRANSFER": 25
    },
    "by_status": {
      "processed": 145,
      "pending": 3,
      "failed": 2,
      "duplicate": 0
    },
    "unique_items": 89,
    "success_rate": 96.67
  }
}
```

---

### List Scan Logs

**GET** `/rfid/scan-logs?warehouse_id=1&action=IN&status=processed&days=7&page=1&per_page=50`

Response:
```json
{
  "status": "success",
  "data": {
    "data": [
      {
        "id": 1,
        "tag_code": "E200001234",
        "item": {
          "id": 5,
          "name": "Widget A",
          "code": "W001"
        },
        "reader": {
          "id": 1,
          "name": "Main Gate"
        },
        "warehouse": {
          "id": 1,
          "name": "Main Warehouse"
        },
        "action": "IN",
        "status": "processed",
        "related_model": "Checkin",
        "related_id": 42,
        "created_at": "2026-04-20T10:30:00Z"
      }
    ],
    "current_page": 1,
    "total": 250
  }
}
```

---

### Delete Scan Log

**DELETE** `/rfid/scan-logs/123`

Response:
```json
{
  "status": "success",
  "message": "Scan rolled back successfully"
}
```

---

## RFID Tags Endpoints

### List Tags

**GET** `/rfid/tags?status=active&unassigned=false&page=1&per_page=50`

Response:
```json
{
  "status": "success",
  "data": {
    "data": [
      {
        "id": 1,
        "tag_code": "E200001234",
        "tag_type": "UHF",
        "is_active": true,
        "status": "active",
        "assignment": {
          "id": 1,
          "item": {
            "id": 5,
            "name": "Widget A"
          },
          "assigned_at": "2026-04-15T08:00:00Z"
        }
      }
    ]
  }
}
```

---

### Create Tag

**POST** `/rfid/tags`

Request:
```json
{
  "tag_code": "E200001234",
  "tag_type": "UHF"
}
```

Response:
```json
{
  "status": "success",
  "message": "RFID tag created",
  "data": {
    "id": 1,
    "tag_code": "E200001234",
    "tag_type": "UHF",
    "is_active": true,
    "status": "active"
  }
}
```

---

### Assign Tag to Item

**POST** `/rfid/tags/1/assign`

Request:
```json
{
  "item_id": 5
}
```

Response:
```json
{
  "status": "success",
  "message": "Tag assigned to item",
  "data": {
    "id": 1,
    "rfid_tag_id": 1,
    "item_id": 5,
    "assigned_at": "2026-04-20T10:00:00Z",
    "status": "active"
  }
}
```

---

### Unassign Tag

**POST** `/rfid/tags/1/unassign`

Response:
```json
{
  "status": "success",
  "message": "Tag unassigned"
}
```

---

### Bulk Create Tags

**POST** `/rfid/tags/bulk-create`

Request:
```json
{
  "tags": [
    {"tag_code": "E200001234", "tag_type": "UHF"},
    {"tag_code": "E200001235", "tag_type": "UHF"},
    {"tag_code": "E200001236", "tag_type": "HF"}
  ]
}
```

Response:
```json
{
  "status": "success",
  "message": "Tags created",
  "count": 3,
  "data": [...]
}
```

---

## RFID Readers Endpoints

### List Readers

**GET** `/rfid/readers?warehouse_id=1&status=active&page=1&per_page=50`

Response:
```json
{
  "status": "success",
  "data": {
    "data": [
      {
        "id": 1,
        "name": "Main Gate",
        "ip_address": "192.168.1.100",
        "location": "main_gate",
        "status": "active",
        "read_range": 5,
        "warehouse": {
          "id": 1,
          "name": "Main Warehouse"
        }
      }
    ]
  }
}
```

---

### Create Reader

**POST** `/rfid/readers`

Request:
```json
{
  "name": "Main Gate",
  "ip_address": "192.168.1.100",
  "warehouse_id": 1,
  "location": "main_gate",
  "status": "active",
  "read_range": 5,
  "frequency": "UHF"
}
```

Response:
```json
{
  "status": "success",
  "message": "RFID reader created",
  "data": {
    "id": 1,
    "name": "Main Gate",
    "ip_address": "192.168.1.100",
    ...
  }
}
```

---

### Update Reader

**PUT** `/rfid/readers/1`

Request:
```json
{
  "name": "Main Gate",
  "read_range": 10,
  "status": "maintenance"
}
```

Response:
```json
{
  "status": "success",
  "message": "RFID reader updated",
  "data": {...}
}
```

---

### Check Reader Health

**POST** `/rfid/readers/1/health`

Response:
```json
{
  "status": "success",
  "data": {
    "reader_id": 1,
    "name": "Main Gate",
    "ip_address": "192.168.1.100",
    "is_online": true,
    "last_scan": "2026-04-20T10:45:30Z",
    "signal_strength": 95
  }
}
```

---

### Get Reader Scans

**GET** `/rfid/readers/1/scans?limit=100&days=7`

Response:
```json
{
  "status": "success",
  "count": 87,
  "data": [...]
}
```

---

### Update Reader Status

**PUT** `/rfid/readers/1/status`

Request:
```json
{
  "status": "active"
}
```

Response:
```json
{
  "status": "success",
  "message": "Reader status updated",
  "data": {...}
}
```

---

### Delete Reader

**DELETE** `/rfid/readers/1`

Response:
```json
{
  "status": "success",
  "message": "RFID reader deleted"
}
```

---

## Error Responses

### 404 - Not Found
```json
{
  "status": "error",
  "message": "Tag not found or inactive"
}
```

### 422 - Validation Error
```json
{
  "status": "error",
  "message": "Tag already assigned"
}
```

### 409 - Duplicate Scan
```json
{
  "status": "duplicate",
  "message": "Duplicate scan detected (cooldown)",
  "scan_log_id": 123
}
```

---

## Testing Steps

1. Create reader
2. Create 5 tags
3. Assign tags to items
4. Send scan requests
5. Check logs and summary
6. Update reader status
7. Delete reader

All endpoints are fully functional and production-ready!
