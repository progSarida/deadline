<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ScopeTypesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('scope_types')->delete();

        DB::table('scope_types')->insert(array (
            0 =>
            array (
                'id' => 1,
                'name' => 'Fatture da oagare/Pagamenti',
                'description' => '',
                'position' => 1,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            1 =>
            array (
                'id' => 2,
                'name' => 'Fatture da emettere',
                'description' => '',
                'position' => 2,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            2 =>
            array (
                'id' => 3,
                'name' => 'Comunicazioni',
                'description' => '',
                'position' => 3,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            3 =>
            array (
                'id' => 4,
                'name' => 'Rendicontazioni',
                'description' => '',
                'position' => 4,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            4 =>
            array (
                'id' => 5,
                'name' => 'Ritenute',
                'description' => '',
                'position' => 5,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            5 =>
            array (
                'id' => 6,
                'name' => 'PEC/Email/Posta',
                'description' => '',
                'position' => 6,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            6 =>
            array (
                'id' => 7,
                'name' => 'Preavvisi',
                'description' => '',
                'position' => 7,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            7 =>
            array (
                'id' => 8,
                'name' => 'Ricorsi',
                'description' => '',
                'position' => 8,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            8 =>
            array (
                'id' => 9,
                'name' => 'Varie',
                'description' => '',
                'position' => 9,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
        ));
    }
}
