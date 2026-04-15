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
        Schema::create('rfid_tag_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rfid_tag_id')->constrained()->cascadeOnDelete();

            // polymorphic relation (item, location)
            $table->unsignedBigInteger('assignable_id');
            $table->string('assignable_type');

            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('unassigned_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfid_tag_assignments');
    }
};
