<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('contactabilidads', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->timestamps();
        });

        DB::table('contactabilidads')->insert([
            ['nombre' => 'No Contactado'],
            ['nombre' => 'Llamada'],
            ['nombre' => 'WhatsApp'],
            ['nombre' => 'Correo'],
            ['nombre' => 'Visita'],
        ]);

        Schema::table('comentarios', function (Blueprint $table) {
            $table->foreignId('contactabilidad_id')->after('etiqueta_id')->nullable()->constrained('contactabilidads');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contactabilidads');
        Schema::table('comentarios', function (Blueprint $table) {
            $table->dropForeign(['contactabilidad_id']);
            $table->dropIndex(['contactabilidad_id']);
            $table->dropColumn('contactabilidad_id');
        });
    }
};
