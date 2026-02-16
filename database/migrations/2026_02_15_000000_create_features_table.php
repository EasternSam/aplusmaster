<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Solo crear la tabla 'features' si no existe
        if (!Schema::hasTable('features')) {
            Schema::create('features', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique(); // 'academic', 'finance'
                $table->string('label');          // 'Gestión Académica'
                $table->string('description')->nullable();
                $table->string('icon')->default('📦');
                $table->string('category')->default('general'); // 'academic', 'system', etc.
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // Si el error mencionaba 'packages', asegúrate de que esta migración NO esté intentando crear 'packages'
        // Si necesitas crear 'packages' aquí también, usa este bloque:
        /*
        if (!Schema::hasTable('packages')) {
            Schema::create('packages', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->json('features')->nullable();
                $table->timestamps();
            });
        }
        */
    }

    public function down(): void
    {
        Schema::dropIfExists('features');
        // Schema::dropIfExists('packages'); // Solo si la creaste aquí
    }
};