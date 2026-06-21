<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menjalankan migrasi untuk membangun skema tabel.
     */
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            // Mendefinisikan foreign key ke tabel users
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            
            // Mendefinisikan kolom untuk mencatat jenis aksi dan rinciannya
            $table->string('action');
            $table->text('description');
            
            $table->timestamps();
        });
    }

    /**
     * Membatalkan migrasi.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};