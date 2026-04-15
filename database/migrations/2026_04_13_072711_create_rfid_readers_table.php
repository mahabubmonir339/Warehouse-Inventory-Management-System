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
            $table->string('device_code')->unique();
            $table->string('ip_address')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfid_readers');
    }
};
