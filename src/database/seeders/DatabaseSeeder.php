<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * アプリケーションのデータベースにシードデータを投入する。
     */
    public function run(): void
    {
        $this->call([
            CareActionSeeder::class,
            TitleSeeder::class,
        ]);
    }
}
