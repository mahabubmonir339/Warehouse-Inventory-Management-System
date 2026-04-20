<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('rfid_scan_logs', function (Blueprint $table) {
            // Check if columns don't already exist
            if (!Schema::hasColumn('rfid_scan_logs', 'reader_id')) {
                $table->foreignId('reader_id')->nullable()->after('item_id')->constrained('rfid_readers')->nullOnDelete();
            }
            
            if (!Schema::hasColumn('rfid_scan_logs', 'warehouse_id')) {
                $table->foreignId('warehouse_id')->nullable()->after('reader_id')->constrained()->nullOnDelete();
            }
            
            if (!Schema::hasColumn('rfid_scan_logs', 'action')) {
                $table->enum('action', ['IN', 'OUT', 'MOVE', 'TRANSFER'])->nullable()->after('warehouse_id')->comment('Type of action triggered');
            }
            
            if (!Schema::hasColumn('rfid_scan_logs', 'related_model')) {
                $table->string('related_model')->nullable()->after('action')->comment('Checkin/Checkout/Transfer');
            }
            
            if (!Schema::hasColumn('rfid_scan_logs', 'related_id')) {
                $table->unsignedBigInteger('related_id')->nullable()->after('related_model')->comment('ID of Checkin/Checkout/Transfer');
            }
            
            if (!Schema::hasColumn('rfid_scan_logs', 'status')) {
                $table->enum('status', ['pending', 'processed', 'failed', 'duplicate'])->default('pending')->after('related_id');
            }
            
            if (!Schema::hasColumn('rfid_scan_logs', 'account_id')) {
                $table->foreignId('account_id')->nullable()->after('status')->constrained()->cascadeOnDelete();
            }

            $table->index(['warehouse_id', 'action']);
            $table->index('status');
            $table->index(['created_at', 'warehouse_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rfid_scan_logs', function (Blueprint $table) {
            $table->dropIndex(['warehouse_id', 'action']);
            $table->dropIndex(['status']);
            $table->dropIndex(['created_at', 'warehouse_id']);
            
            $table->dropForeignKeyIfExists(['reader_id']);
            $table->dropForeignKeyIfExists(['warehouse_id']);
            $table->dropForeignKeyIfExists(['account_id']);
            
            $table->dropColumn(['reader_id', 'warehouse_id', 'action', 'related_model', 'related_id', 'status', 'account_id']);
        });
    }
};
