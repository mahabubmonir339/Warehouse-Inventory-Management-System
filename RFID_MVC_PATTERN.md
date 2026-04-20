# RFID MVC Pattern - Implementation Guide

## Overview

The RFID module now follows a **proper MVC (Model-View-Controller) pattern** with complete CRUD (Create, Read, Update, Delete) operations and **Form Request validation**.

---

## Architecture

### MVC Pattern Structure

```
├── Models (M)
│   ├── RfidTag
│   ├── RfidReader
│   ├── RfidScanLog
│   ├── RfidTagAssignment
│   └── ...
│
├── Controllers (C)
│   ├── RfidController (Web UI - Inertia/Vue)
│   ├── Api/RfidTagController (REST API)
│   ├── Api/RfidReaderController (REST API)
│   └── Api/RfidScanController (REST API)
│
├── Requests (Validation Layer)
│   ├── StoreRfidTagRequest
│   ├── UpdateRfidTagRequest
│   ├── StoreRfidReaderRequest
│   ├── UpdateRfidReaderRequest
│   └── StoreRfidTagAssignmentRequest
│
└── Views (V)
    └── resources/js/Pages/Rfid/
        ├── Live.vue
        ├── TagAssignmentPanel.vue
        ├── Logs.vue
        ├── Tags.vue
        └── Readers.vue
```

---

## 1. Models (M)

**Location:** `app/Models/`

### Core Models

#### `RfidTag.php`
- Represents physical RFID tags
- Relations: `assignment()`, `scans()`, `account()`
- Status: active, inactive, lost, damaged

#### `RfidReader.php`
- Represents RFID reader devices
- Relations: `warehouse()`, `scans()`, `account()`
- Status: active, inactive, maintenance

#### `RfidTagAssignment.php`
- Represents tag-to-item mappings
- Relations: `tag()`, `item()`, `assignedBy()`
- Status: active, reassigned, inactive

#### `RfidScanLog.php`
- Audit trail of RFID scans
- Relations: `tag()`, `reader()`, `warehouse()`, `item()`

---

## 2. Controllers (C)

### Web Controller - `RfidController.php`

**Location:** `app/Http/Controllers/RfidController.php`

**Purpose:** Renders Inertia/Vue pages for admin interface

#### Display Methods (READ)

```php
public function live(Request $request)        // Live scan dashboard
public function assign(Request $request)      // Tag assignment page
public function logs(Request $request)        // Scan logs viewer
public function readers(Request $request)     // Readers management
public function tags(Request $request)        // Tags management
```

#### Create Methods (CREATE)

```php
public function storeTag(StoreRfidTagRequest $request)
    // POST /rfid/tags
    // Creates new RFID tag
    
public function storeReader(StoreRfidReaderRequest $request)
    // POST /rfid/readers
    // Creates new RFID reader
    
public function storeAssign(StoreRfidTagAssignmentRequest $request)
    // POST /rfid/assign
    // Assigns tag to item
```

#### Update Methods (UPDATE)

```php
public function updateTag(UpdateRfidTagRequest $request, RfidTag $tag)
    // PUT /rfid/tags/{tag}
    // Updates RFID tag properties
    
public function updateReader(UpdateRfidReaderRequest $request, RfidReader $reader)
    // PUT /rfid/readers/{reader}
    // Updates RFID reader configuration
```

#### Delete Methods (DELETE)

```php
public function destroyTag(RfidTag $tag)
    // DELETE /rfid/tags/{tag}
    // Deletes RFID tag (if not assigned)
    
public function destroyReader(RfidReader $reader)
    // DELETE /rfid/readers/{reader}
    // Deletes RFID reader

public function unassignTag(RfidTag $tag)
    // POST /rfid/tags/{tag}/unassign
    // Unassigns tag from item
```

### API Controllers

#### `Api/RfidTagController.php`
REST endpoint for tag CRUD operations
- `GET /api/rfid/tags` - List tags
- `POST /api/rfid/tags` - Create tag
- `GET /api/rfid/tags/{tag}` - Show tag
- `PUT /api/rfid/tags/{tag}` - Update tag
- `DELETE /api/rfid/tags/{tag}` - Delete tag
- `POST /api/rfid/tags/{tag}/assign` - Assign tag
- `POST /api/rfid/tags/{tag}/unassign` - Unassign tag

#### `Api/RfidReaderController.php`
REST endpoint for reader CRUD operations
- `GET /api/rfid/readers` - List readers
- `POST /api/rfid/readers` - Create reader
- `GET /api/rfid/readers/{reader}` - Show reader
- `PUT /api/rfid/readers/{reader}` - Update reader
- `DELETE /api/rfid/readers/{reader}` - Delete reader

#### `Api/RfidScanController.php`
REST endpoint for scan operations
- Handles real-time RFID scan data ingestion

---

## 3. Form Requests (Validation Layer)

**Location:** `app/Http/Requests/`

### StoreRfidTagRequest

```php
Rules:
- tag_code: required, string, unique
- tag_type: UHF, HF, LF, NFC (default: UHF)
- status: active, inactive (default: active)
- frequency: optional string
- epc: optional string
```

### UpdateRfidTagRequest

```php
Rules:
- tag_code: unique (excluding current tag)
- status: active, inactive, lost, damaged
- frequency: optional
- epc: optional
```

### StoreRfidReaderRequest

```php
Rules:
- name: required, string, max 255
- ip_address: required, valid IPv4, unique
- warehouse_id: required, exists in warehouses
- location: required, string, max 255
- status: active, inactive, maintenance (default: active)
- read_range: integer 1-100 (default: 5)
- frequency: optional string
- protocol: TCP/UDP (default: TCP)
- port: 1-65535 (default: 9096)
```

### UpdateRfidReaderRequest

```php
Rules: Same as Store but all fields optional
- Validates unique constraints excluding current record
```

### StoreRfidTagAssignmentRequest

```php
Rules:
- rfid_tag_id: required, exists in rfid_tags
- item_id: required, exists in items
- warehouse_id: optional
- notes: optional, max 500
```

---

## 4. Routes

**Location:** `routes/web.php`

### Web Routes (Inertia Pages)

```php
Route::prefix('rfid')->group(function () {
    // Display pages
    Route::get('/live', [RfidController::class, 'live'])->name('rfid.live');
    Route::get('/assign', [RfidController::class, 'assign'])->name('rfid.assign');
    Route::get('/logs', [RfidController::class, 'logs'])->name('rfid.logs');
    Route::get('/readers', [RfidController::class, 'readers'])->name('rfid.readers');
    Route::get('/tags', [RfidController::class, 'tags'])->name('rfid.tags');
    
    // CRUD operations - Tags
    Route::post('/tags', [RfidController::class, 'storeTag'])->name('rfid.tags.store');
    Route::put('/tags/{tag}', [RfidController::class, 'updateTag'])->name('rfid.tags.update');
    Route::delete('/tags/{tag}', [RfidController::class, 'destroyTag'])->name('rfid.tags.destroy');
    Route::post('/tags/{tag}/unassign', [RfidController::class, 'unassignTag'])->name('rfid.tags.unassign');
    
    // CRUD operations - Assignment
    Route::post('/assign', [RfidController::class, 'storeAssign'])->name('rfid.assign.store');
    
    // CRUD operations - Readers
    Route::post('/readers', [RfidController::class, 'storeReader'])->name('rfid.readers.store');
    Route::put('/readers/{reader}', [RfidController::class, 'updateReader'])->name('rfid.readers.update');
    Route::delete('/readers/{reader}', [RfidController::class, 'destroyReader'])->name('rfid.readers.destroy');
});
```

### API Routes

**Location:** `routes/api.php`

```php
Route::apiResource('rfid/tags', RfidTagController::class);
Route::apiResource('rfid/readers', RfidReaderController::class);
Route::apiResource('rfid/scans', RfidScanController::class);

Route::post('rfid/tags/{tag}/assign', [RfidTagController::class, 'assignToItem']);
Route::post('rfid/tags/{tag}/unassign', [RfidTagController::class, 'unassignFromItem']);
```

---

## 5. Data Flow

### Create RFID Tag Flow

```
Vue Component (TagForm.vue)
         ↓
POST /rfid/tags (with form data)
         ↓
StoreRfidTagRequest (validation)
         ↓
RfidController::storeTag()
         ↓
RfidTag::create() (Model)
         ↓
Database
         ↓
Response with success message
```

### Update RFID Reader Flow

```
Vue Component (ReaderEdit.vue)
         ↓
PUT /rfid/readers/{reader} (with updated data)
         ↓
UpdateRfidReaderRequest (validation)
         ↓
RfidController::updateReader()
         ↓
$reader->update() (Model)
         ↓
Database
         ↓
Response with success message
```

### Delete RFID Tag Flow

```
Vue Component (Delete button)
         ↓
DELETE /rfid/tags/{tag}
         ↓
RfidController::destroyTag()
         ↓
Authorization check
         ↓
Relationship validation (no assignment)
         ↓
$tag->delete() (Model)
         ↓
Database
         ↓
Response with success message
```

---

## 6. Permission Integration

### Permission-Based Authorization

All CRUD methods check permissions:

```php
public function authorize()
{
    return $this->user()->can('create-rfid-tags');  // In Form Request
}

public function destroyTag(RfidTag $tag)
{
    $this->authorize('delete', $tag);  // In Controller
    // ...
}
```

### Required Permissions

| Operation | Permission | Form Request |
|-----------|-----------|---------------|
| View Tags | view-rfid-tags | N/A (display only) |
| Create Tag | create-rfid-tags | StoreRfidTagRequest |
| Update Tag | edit-rfid-tags | UpdateRfidTagRequest |
| Delete Tag | delete-rfid-tags | RfidController method |
| Create Reader | create-rfid-readers | StoreRfidReaderRequest |
| Update Reader | edit-rfid-readers | UpdateRfidReaderRequest |
| Delete Reader | delete-rfid-readers | RfidController method |
| Assign Tag | assign-rfid-tags | StoreRfidTagAssignmentRequest |

---

## 7. Validation Examples

### Creating RFID Tag (Vue)

```vue
const form = reactive({
  tag_code: '',
  tag_type: 'UHF',
  status: 'active'
});

async function createTag() {
  try {
    const response = await axios.post('/rfid/tags', form);
    // Success handling
  } catch (error) {
    // Error handling with validation messages
  }
}
```

### Backend Validation (Form Request)

```php
// Request automatically validates and returns errors
// If validation fails, Laravel redirects back with errors
// If validation passes, validated data is available

public function storeTag(StoreRfidTagRequest $request)
{
    $validated = $request->validated(); // Safe, pre-validated data
    
    RfidTag::create([
        'tag_code' => $validated['tag_code'],
        'tag_type' => $validated['tag_type'] ?? 'UHF',
        // ...
    ]);
}
```

---

## 8. Error Handling

### Model Validation
```php
// In Form Request
'tag_code' => 'required|string|unique:rfid_tags'

// Error: "This tag code already exists"
```

### Business Logic Validation
```php
if ($tag->assignment) {
    return back()->withErrors(['tag' => 'Tag already assigned']);
}
```

### Authorization
```php
$this->authorize('delete', $tag);
// Throws 403 if user lacks permission
```

---

## 9. Testing the MVC Pattern

### Create Tag API Test
```bash
POST /api/rfid/tags
{
  "tag_code": "TAG001",
  "tag_type": "UHF",
  "status": "active"
}
```

### Update Reader API Test
```bash
PUT /api/rfid/readers/1
{
  "name": "Main Entrance Reader",
  "status": "active",
  "read_range": 8
}
```

### Delete Tag API Test
```bash
DELETE /rfid/tags/5
```

---

## 10. File Structure Summary

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── RfidController.php
│   │   └── Api/
│   │       ├── RfidTagController.php
│   │       ├── RfidReaderController.php
│   │       └── RfidScanController.php
│   ├── Requests/
│   │   ├── StoreRfidTagRequest.php
│   │   ├── UpdateRfidTagRequest.php
│   │   ├── StoreRfidReaderRequest.php
│   │   ├── UpdateRfidReaderRequest.php
│   │   └── StoreRfidTagAssignmentRequest.php
│   └── Resources/
│       └── RfidTagResource.php (optional)
├── Models/
│   ├── RfidTag.php
│   ├── RfidReader.php
│   ├── RfidTagAssignment.php
│   └── RfidScanLog.php
└── Services/
    └── Rfid/
        ├── RfidScanProcessor.php
        ├── RfidActionResolver.php
        └── RfidSyncService.php

routes/
├── web.php (Web controller routes)
└── api.php (API controller routes)

resources/js/Pages/Rfid/
├── Live.vue
├── TagAssignmentPanel.vue
├── Logs.vue
├── Readers.vue
└── Tags.vue
```

---

## 11. Best Practices Applied

✅ **Separation of Concerns**
- Models handle data logic
- Controllers handle business flow
- Form Requests handle validation
- Views handle presentation

✅ **DRY Principle (Don't Repeat Yourself)**
- Validation rules defined once in Form Requests
- Used by both Web and API controllers

✅ **Permission-Based Access**
- Authorization checks in Form Requests and Controllers
- Follows principle of least privilege

✅ **Proper HTTP Methods**
- GET for retrieval
- POST for creation
- PUT for updates
- DELETE for removal

✅ **Error Handling**
- Validation errors returned with context
- Business logic errors with clear messages
- Authorization failures return 403

✅ **Code Reusability**
- API and Web controllers share Form Requests
- Services handle complex business logic
- Models define relationships and scopes

---

## 12. Next Steps

1. **Create Vue Components** for tag/reader management if not already done
2. **Add API Resource Classes** for JSON formatting (optional but recommended)
3. **Create Unit Tests** for Controllers and Form Requests
4. **Add Event Listeners** for RFID audit trail
5. **Implement Webhooks** for external system integration
6. **Add Rate Limiting** to API endpoints

---

## Summary

The RFID module now implements a **clean MVC architecture** with:
- ✅ Proper separation of Models, Views, and Controllers
- ✅ Form Request validation layer
- ✅ Complete CRUD operations
- ✅ Both Web UI and REST API
- ✅ Permission-based authorization
- ✅ Consistent error handling
- ✅ Ready for production use

All RFID operations now follow Laravel best practices and are maintainable and scalable!
