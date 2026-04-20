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
        Schema::table('rfid_tags', function (Blueprint $table) {
            if (!Schema::hasColumn('rfid_tags', 'account_id')) {
                $table->foreignId('account_id')->nullable()->after('is_active')->constrained()->cascadeOnDelete();
            }
            
            if (!Schema::hasColumn('rfid_tags', 'status')) {
                $table->enum('status', ['active', 'inactive', 'lost', 'damaged'])->default('active')->after('is_active');
            }
            
            if (!Schema::hasColumn('rfid_tags', 'tag_type')) {
                $table->string('tag_type')->default('UHF')->after('tag_code')->comment('e.g., UHF, HF, NFC');
            }

            $table->index(['account_id', 'is_active']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rfid_tags', function (Blueprint $table) {
            $table->dropIndex(['account_id', 'is_active']);
            $table->dropIndex(['status']);
            
            $table->dropForeignKeyIfExists(['account_id']);
            $table->dropColumn(['account_id', 'status', 'tag_type']);
        });
    }
};
