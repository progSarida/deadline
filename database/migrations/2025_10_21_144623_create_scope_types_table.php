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
        Schema::create('scope_types', function (Blueprint $table) {                                  // tabella ambiti scadenze
            $table->id();
            $table->string('name');                                                             // nome ambito
            $table->string('description');                                                      // descrizione ambito
            $table->integer('position');                                                        // posizione nella selezione
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('scope_types');
        Schema::enableForeignKeyConstraints();
    }
};
