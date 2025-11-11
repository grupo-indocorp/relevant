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
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('estado')->nullable()->after('razon_social');
            $table->string('condicion')->nullable()->after('estado');
            $table->string('actividad_economica')->nullable()->after('condicion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn(['estado', 'condicion', 'actividad_economica']);
        });
    }
};
