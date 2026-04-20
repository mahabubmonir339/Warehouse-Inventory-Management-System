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
        Schema::table('rfid_tag_assignments', function (Blueprint $table) {
            if (!Schema::hasColumn('rfid_tag_assignments', 'account_id')) {
                $table->foreignId('account_id')->nullable()->after('item_id')->constrained()->cascadeOnDelete();
            }
            
            if (!Schema::hasColumn('rfid_tag_assignments', 'assigned_by')) {
                $table->foreignId('assigned_by')->nullable()->after('account_id')->constrained('users')->nullOnDelete();
            }
            
            if (!Schema::hasColumn('rfid_tag_assignments', 'assigned_at')) {
                $table->timestamp('assigned_at')->useCurrent();
            }
            
            if (!Schema::hasColumn('rfid_tag_assignments', 'status')) {
                $table->enum('status', ['active', 'reassigned', 'damaged'])->default('active')->after('assigned_at');
            }

            $table->index(['account_id', 'status']);
            $table->index('item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rfid_tag_assignments', function (Blueprint $table) {
            $table->dropIndex(['account_id', 'status']);
            $table->dropIndex(['item_id']);
            
            $table->dropForeignKeyIfExists(['account_id']);
            $table->dropForeignKeyIfExists(['assigned_by']);
            $table->dropColumn(['account_id', 'assigned_by', 'assigned_at', 'status']);
        });
    }
};
