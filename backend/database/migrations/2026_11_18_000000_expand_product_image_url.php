<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('image_url', 2048)->nullable()->change();
        });
    }

    public function down(): void
    {
        if (DB::table('products')->whereRaw('CHAR_LENGTH(image_url) > 255')->exists()) {
            throw new RuntimeException('Hay URLs de más de 255 caracteres. Revisa esas filas antes de revertir la migración.');
        }
        Schema::table('products', function (Blueprint $table) {
            $table->string('image_url', 255)->nullable()->change();
        });
    }
};
