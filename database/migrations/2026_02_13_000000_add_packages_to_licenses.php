<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Crear tabla de Paquetes (Planes)
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Ej: "Plan Básico"
            $table->json('features')->nullable(); // Ej: ["academic", "students_view"]
            $table->timestamps();
        });

        // 2. Modificar Licencias para vincularlas a un paquete y permitir excepciones
        Schema::table('licenses', function (Blueprint $table) {
            $table->foreignId('package_id')->nullable()->constrained('packages')->nullOnDelete();
            // Aquí guardaremos funciones activadas/desactivadas manualmente para este cliente específico
            $table->json('custom_features')->nullable(); 
        });
    }

    public function down(): void
    {
        Schema::table('licenses', function (Blueprint $table) {
            $table->dropForeign(['package_id']);
            $table->dropColumn(['package_id', 'custom_features']);
        });
        Schema::dropIfExists('packages');
    }
};