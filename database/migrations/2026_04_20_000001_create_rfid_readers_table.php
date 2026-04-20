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
        Schema::create('rfid_readers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('ip_address')->unique()->nullable();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->string('location')->comment('gate/zone'); // e.g., 'main_gate', 'zone_a', 'checkout'
            $table->enum('status', ['active', 'inactive', 'maintenance'])->default('active');
            $table->integer('read_range')->default(5)->comment('read range in meters');
            $table->string('frequency')->nullable()->comment('RFID frequency band');
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['warehouse_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rfid_readers');
    }
};
