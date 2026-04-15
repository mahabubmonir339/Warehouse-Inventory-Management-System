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
        Schema::create('rfid_scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rfid_tag_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rfid_reader_id')->nullable()->constrained()->nullOnDelete();

            $table->timestamp('scanned_at')->useCurrent();
            $table->string('signal_strength')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfid_scans');
    }
};
