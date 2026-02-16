<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('features', function (Blueprint $table) {
            // Ruta donde se guarda el ZIP del módulo (ej: addons/hr_v1.zip)
            $table->string('file_path')->nullable()->after('icon');
            // Versión del módulo para gestionar actualizaciones futuras
            $table->string('version')->default('1.0.0')->after('file_path');
        });
    }

    public function down(): void
    {
        Schema::table('features', function (Blueprint $table) {
            $table->dropColumn(['file_path', 'version']);
        });
    }
};