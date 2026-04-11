<?php

use Illuminate\Database\Seeder;

class states extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('states')->insert([
            ['id' => '2901','name' => 'Aguascalientes'],
            ['id' => '2902','name' => 'Baja California'],
            ['id' => '2903','name' => 'Baja California Sur'],
            ['id' => '2904','name' => 'Campeche'],
            ['id' => '2905','name' => 'Chiapas'],
            ['id' => '2906','name' => 'Chihuahua'],
            ['id' => '2907','name' => 'Coahuila'],
            ['id' => '2908','name' => 'Colima'],
            ['id' => '2909','name' => 'Ciudad de México'],
            ['id' => '2910','name' => 'Durango'],
            ['id' => '2911','name' => 'Guanajuato'],
            ['id' => '2912','name' => 'Guerrero'],
            ['id' => '2913','name' => 'Hidalgo'],
            ['id' => '2914','name' => 'Jalisco'],
            ['id' => '2915','name' => 'Estado De México'],
            ['id' => '2916','name' => 'Michoacán'],
            ['id' => '2917','name' => 'Morelos'],
            ['id' => '2918','name' => 'Nayarit'],
            ['id' => '2919','name' => 'Nuevo León'],
            ['id' => '2920','name' => 'Oaxaca'],
            ['id' => '2921','name' => 'Puebla'],
            ['id' => '2922','name' => 'Querétaro'],
            ['id' => '2923','name' => 'Quintana Roo'],
            ['id' => '2924','name' => 'San Luis Potosí'],
            ['id' => '2925','name' => 'Sinaloa'],
            ['id' => '2926','name' => 'Sonora'],
            ['id' => '2927','name' => 'Tabasco'],
            ['id' => '2928','name' => 'Tamaulipas'],
            ['id' => '2929','name' => 'Tlaxcala'],
            ['id' => '2930','name' => 'Veracruz'],
            ['id' => '2931','name' => 'Yucatán'],
            ['id' => '2932','name' => 'Zacatecas']
          ]
          );
    }
}
