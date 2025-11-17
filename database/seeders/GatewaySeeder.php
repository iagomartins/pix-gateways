<?php

namespace Database\Seeders;

use App\Models\Gateway;
use Illuminate\Database\Seeder;

class GatewaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Gateway::updateOrCreate(
            ['type' => 'subadq_a'],
            [
                'name' => 'Subadquirente A',
                'base_url' => env('SUBADQ_A_BASE_URL', 'https://0acdeaee-1729-4d55-80eb-d54a125e5e18.mock.pstmn.io'),
                'type' => 'subadq_a',
                'config' => [
                    'timeout' => 30,
                    'retry_attempts' => 3,
                ],
                'active' => true,
            ]
        );

        Gateway::updateOrCreate(
            ['type' => 'subadq_b'],
            [
                'name' => 'Subadquirente B',
                'base_url' => env('SUBADQ_B_BASE_URL', 'https://ef8513c8-fd99-4081-8963-573cd135e133.mock.pstmn.io'),
                'type' => 'subadq_b',
                'config' => [
                    'timeout' => 30,
                    'retry_attempts' => 3,
                ],
                'active' => true,
            ]
        );
    }
}

