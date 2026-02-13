<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->string('client_name'); // Ej: "Colegio San Pedro"
            $table->string('license_key')->unique(); // Ej: "SGA-SP-12345-ABCDE"
            $table->string('registered_domain')->nullable(); // Ej: "sga.colegiosanpedro.com"
            $table->boolean('is_active')->default(true); // Control de encendido/apagado (Corte por falta de pago)
            $table->date('expires_at')->nullable(); // Para licencias de pago anual
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licenses');
    }
};
