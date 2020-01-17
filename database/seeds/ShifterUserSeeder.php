<?php

use Illuminate\Database\Seeder;

class ShifterUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $items = [            
            //Warehouse
            ['Name' => 'Admin', 'UserName' => "Admin", 'Password' => 'admin123']
        ];
        foreach ($items as $item) {
            App\ShifterUser::updateOrCreate($item);
        }
    }
}
