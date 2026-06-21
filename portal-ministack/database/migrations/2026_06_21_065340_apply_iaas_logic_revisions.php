<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Revisi Tabel Payments (Menambahkan kolom status dan relasi kontrak)
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('subscription_id')->after('id')->constrained('user_subscriptions')->cascadeOnDelete();
            $table->string('metode_bayar')->after('subscription_id')->nullable();
            $table->enum('status_bayar', ['Pending', 'Lunas'])->default('Pending')->after('metode_bayar');
        });

        // 2. Pembuatan Tabel Resources (Mencatat fasilitas penyimpanan setelah lunas)
        Schema::create('resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('user_subscriptions')->cascadeOnDelete();
            $table->integer('kapasitas_storage')->comment('Kapasitas storage dalam satuan Megabyte/Gigabyte');
            $table->timestamps();
        });

        // 3. Revisi Tabel Buckets (Memutus relasi dari User, menyambungkan ke Resource)
        Schema::table('buckets', function (Blueprint $table) {
            // Menghapus kunci tamu (foreign key) lama
            $table->dropForeign('buckets_user_id_foreign');
            $table->dropColumn('user_id');
            
            // Menambahkan kunci tamu baru yang mengarah ke resources
            $table->foreignId('resource_id')->after('id')->constrained('resources')->cascadeOnDelete();
        });

        // 4. Pembuatan Tabel Objects (Mencatat file yang diunggah ke dalam bucket)
        Schema::create('objects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bucket_id')->constrained('buckets')->cascadeOnDelete();
            $table->string('nama_file');
            $table->string('tipe_file', 50);
            $table->integer('ukuran_file')->comment('Ukuran file dalam satuan Bytes');
            $table->timestamps();
        });

        // 5. Revisi Tabel Credentials (Memutus relasi dari User, menyambungkan ke Subscriptions & menambah kontrol)
        Schema::table('credentials', function (Blueprint $table) {
            // Menghapus kunci tamu (foreign key) lama
            $table->dropForeign('credentials_user_id_foreign');
            $table->dropColumn('user_id');
            
            // Menambahkan relasi baru ke kontrak sewa dan kontrol status kunci
            $table->foreignId('subscription_id')->after('id')->constrained('user_subscriptions')->cascadeOnDelete();
            $table->enum('status_kunci', ['Aktif', 'Dicabut'])->default('Aktif')->after('secret_access_key');
        });
    }

    public function down(): void
    {
        // Method ini berguna jika Anda ingin melakukan rollback (membatalkan revisi)
        Schema::table('credentials', function (Blueprint $table) {
            $table->dropColumn(['status_kunci']);
            $table->dropForeign(['subscription_id']);
            $table->dropColumn('subscription_id');
            $table->foreignId('user_id')->after('id')->constrained('users')->cascadeOnDelete();
        });

        Schema::dropIfExists('objects');

        Schema::table('buckets', function (Blueprint $table) {
            $table->dropForeign(['resource_id']);
            $table->dropColumn('resource_id');
            $table->foreignId('user_id')->after('id')->constrained('users')->cascadeOnDelete();
        });

        Schema::dropIfExists('resources');

        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['subscription_id']);
            $table->dropColumn(['subscription_id', 'metode_bayar', 'status_bayar']);
        });
    }
};