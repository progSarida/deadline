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
        Schema::create('deadlines', function (Blueprint $table) {                                                                       // tabella scadenze
            $table->id();
            $table->date('registration_date')->nullable();                                                                              // data registrazione scadenza
            $table->foreignId('registration_user_id')->nullable() ->constrained('users')->onUpdate('cascade')->onDelete('set null');    // id utente che ha registrato scadenza
            $table->date('modify_date')->nullable();                                                                                    // data modifica scadenza
            $table->foreignId('modify_user_id')->nullable() ->constrained('users')->onUpdate('cascade')->onDelete('set null');          // id utente che ha modificato scadenza
            $table->text('description')->nullable();                                                                                    // descrizione
            $table->text('note')->nullable();                                                                                           // note
            $table->foreignId('scope_type_id')->nullable()->constrained('scope_types')->onDelete('set null');                           // id tipo ambito
            $table->boolean('recurrent')->nullable();                                                                                   // flag per scadenza periodica
            $table->date('deadline_date')->nullable();                                                                                  // data scadenza
            $table->integer('quantity')->nullable();                                                                                    // numero archi di tempo
            $table->string('timespan')->nullable();                                                                                     // tipo cliente (enum Timespan)
            $table->boolean('met')->nullable();                                                                                         // flag per scadenza rispettata
            $table->date('met_date')->nullable();                                                                                       // data rispetto scadenza
            $table->foreignId('met_user_id')->nullable() ->constrained('users')->onUpdate('cascade')->onDelete('set null');             // id utente che ha registrato rispetto
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('deadlines');
        Schema::enableForeignKeyConstraints();
    }
};
