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
        Schema::create('scope_types', function (Blueprint $table) {                                 // tabella ambiti scadenze
            $table->id();
            $table->string('name');                                                                 // nome ambito
            $table->string('description');                                                          // descrizione ambito
            $table->integer('position');                                                            // posizione nella selezione
            $table->timestamps();
        });

        Schema::create('user_scope_type', function (Blueprint $table) {                             // tabella permessi utente
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');                // id utente
            $table->foreignId('scope_type_id')->constrained('scope_types')->onDelete('cascade');    // id tipo ambito
            $table->enum('permission', ['read', 'write', 'delete']);                                // tipo di permesso (enum Permission)
            $table->unique(['user_id', 'scope_type_id']);                                           // evito duplicati
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('user_scope_type');
        Schema::dropIfExists('scope_types');
        Schema::enableForeignKeyConstraints();
    }
};
