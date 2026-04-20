<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RfidPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // RFID Tag Management
            'view-rfid-tags' => 'View RFID Tags',
            'create-rfid-tags' => 'Create RFID Tags',
            'edit-rfid-tags' => 'Edit RFID Tags',
            'delete-rfid-tags' => 'Delete RFID Tags',
            'assign-rfid-tags' => 'Assign Tags to Items',

            // RFID Reader Management
            'view-rfid-readers' => 'View RFID Readers',
            'create-rfid-readers' => 'Create RFID Readers',
            'edit-rfid-readers' => 'Edit RFID Readers',
            'delete-rfid-readers' => 'Delete RFID Readers',
            'test-rfid-readers' => 'Test RFID Reader Connectivity',

            // RFID Scan Logs
            'view-rfid-logs' => 'View RFID Scan Logs',
            'export-rfid-logs' => 'Export RFID Logs',
            'delete-rfid-logs' => 'Delete RFID Logs',

            // RFID Dashboard & Monitoring
            'view-rfid-live' => 'View Live RFID Monitor',
            'view-rfid-dashboard' => 'View RFID Dashboard',

            // Item Location Tracking
            'view-rfid-locations' => 'View Item Locations',

            // RFID Settings
            'manage-rfid-settings' => 'Manage RFID Settings',
        ];

        foreach ($permissions as $name => $description) {
            Permission::firstOrCreate(
                ['name' => $name],
                ['description' => $description]
            );
        }

        // Create RFID Manager permission group
        $managerPermissions = [
            'view-rfid-tags',
            'create-rfid-tags',
            'edit-rfid-tags',
            'delete-rfid-tags',
            'assign-rfid-tags',
            'view-rfid-readers',
            'create-rfid-readers',
            'edit-rfid-readers',
            'delete-rfid-readers',
            'test-rfid-readers',
            'view-rfid-logs',
            'export-rfid-logs',
            'view-rfid-live',
            'view-rfid-dashboard',
            'view-rfid-locations',
            'manage-rfid-settings',
        ];

        // If you have a Role model, you can assign these permissions
        // Example: Role::where('name', 'RFID Manager')->first()?->syncPermissions($managerPermissions);
    }
}
