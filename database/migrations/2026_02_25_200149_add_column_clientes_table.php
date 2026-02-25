<?php

use App\Models\Etiqueta;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('etiquetas')->insert([
            'nombre' => 'Base de la Empresa',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('etiquetas')->insert([
            'nombre' => 'Equipo de venta',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('tipobase')->after('contactabilidad')->default('Equipo de Ventas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('etiquetas')->whereIn('nombre', ['Base de la Empresa', 'Equipo de venta'])->delete();
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn('tipobase');
        });
    }
};
