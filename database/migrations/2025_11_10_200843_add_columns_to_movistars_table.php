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
        Schema::table('movistars', function (Blueprint $table) {
            $table->integer('score')->default(0)->after('ejecutivo_salesforce');
            $table->integer('cantidad_trabajador')->default(0)->after('score');
            $table->integer('cantidad_sucursal')->default(0)->after('cantidad_trabajador');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('movistars', function (Blueprint $table) {
            $table->dropColumn(['score', 'cantidad_trabajador', 'cantidad_sucursal']);
        });
    }
};
