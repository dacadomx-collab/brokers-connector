<?php
use Illuminate\Database\Seeder;
use Faker\Generator as Faker;
use App\Feature;
use App\User;
use App\Company;

// use App\PropertyStatus;
// use App\PropertyType;
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        DB::table('roles')->insert([[
            'name' => 'Admin',
            'display_name'=> 'Propietario',
            'guard_name' => 'web'
        ],[
            'name' => 'Agent',
            'display_name'=> 'Agente',
            'guard_name' => 'web'
        ]]);
       
        
   
            DB::table('services')->insert([
             [
                'service' => 'Single ASPI y AMPI',
                'price' => 600,
                'user_price' => 0,
                'days_trial' => 7,
                'users_included' => 0
            ],[
                'service' => 'Corporation ASPI y AMPI',
                'price' => 780,
                'user_price' => 50,
                'days_trial' => 7,
                'users_included' => 2
            ],[
                'service' => 'General',
                'price' => 1200,
                'user_price' => 100,
                'days_trial' => 7,
                'users_included' => 2
            ],]
            );
     
        
            
    
            //ubication seed

            $this->call([
                states::class,
                cities::class,
                districts::class,
            ]);
            
            
            
        


        
        //fin

        $status = array( 
        ["name"=>'Renta', "luly"=>0, "propiedades"=>"RENTA", "GI"=>"alquiler", "lamudi"=>"Renta"],
        ["name"=>'Venta', "luly"=>0, "propiedades"=>"VENTA", "GI"=>"venta", "lamudi"=>"Venta"],
        ["name"=>'Renta o Venta', "luly"=>1, "propiedades"=>"", "GI"=>"", "lamudi"=>""],
        ["name"=>'Tiempo compartido', "luly"=>1, "propiedades"=>"", "GI"=>"", "lamudi"=>""],
        ["name"=>'Aportación (Inversión)', "luly"=>1, "propiedades"=>"", "GI"=>"", "lamudi"=>""],
        ["name"=>'Permuta total', "luly"=>1, "propiedades"=>"", "GI"=>"", "lamudi"=>""],
        ["name"=>'Permuta parcial', "luly"=>1, "propiedades"=>"", "GI"=>"", "lamudi"=>""],
        ["name"=>'Traspaso', "luly"=>0, "propiedades"=>"VENTA", "GI"=>"Venta", "lamudi"=>"Venta"],
        ["name"=>'Atraque (Marinas,Angares y bodegas)', "luly"=>1, "propiedades"=>"", "GI"=>"", "lamudi"=>""],
        ["name"=>'Otro ingreso', "luly"=>1, "propiedades"=>"", "GI"=>"", "lamudi"=>""],
        ["name"=>'rentada', "luly"=>0, "propiedades"=>"", "GI"=>"", "lamudi"=>""],
        ["name"=>'vendida', "luly"=>0, "propiedades"=>"", "GI"=>"", "lamudi"=>""],
);
        
        $types = array( 
        ["name"=>'departamento', "luly"=>0, "propiedades"=>"DEPARTAMENTO", "GI"=>"departamento", "lamudi"=>"Departamento"],
        ["name"=>'oficina', "luly"=>0, "propiedades"=>"OFICINA", "GI"=>"oficina", "lamudi"=>"Oficina"],
        ["name"=>'residencial', "luly"=>1, "propiedades"=>"", "GI"=>"", "lamudi"=>""],
        ["name"=>'condominio', "luly"=>0, "propiedades"=>"CONDOMINIO", "GI"=>"quinta", "lamudi"=>"Departamento"],
        ["name"=>'comercial', "luly"=>1, "propiedades"=>"", "GI"=>"", "lamudi"=>""],
        ["name"=>'vacaciones', "luly"=>1, "propiedades"=>"", "GI"=>"", "lamudi"=>""],
        ["name"=>'terreno rustico', "luly"=>1, "propiedades"=>"", "GI"=>"", "lamudi"=>""],
        ["name"=>'terreno ciudad', "luly"=>1, "propiedades"=>"", "GI"=>"", "lamudi"=>""],
        ["name"=>'bodega/almacen', "luly"=>0, "propiedades"=>"BODEGA/ALMACEN", "GI"=>"negocio", "lamudi"=>"Comercio"],
        ["name"=>'bodega industrial', "luly"=>1, "propiedades"=>"", "GI"=>"", "lamudi"=>""],
        ["name"=>'duplex', "luly"=>0, "propiedades"=>"CASA", "GI"=>"duplex", "lamudi"=>"Casa"],
        ["name"=>'departamento en condominio', "luly"=>1, "propiedades"=>"", "GI"=>"", "lamudi"=>""],
        ["name"=>'escuela', "luly"=>1, "propiedades"=>"", "GI"=>"", "lamudi"=>""],
        ["name"=>'bodega en condominio', "luly"=>1, "propiedades"=>"", "GI"=>"", "lamudi"=>""],
        ["name"=>'inmuebles productivos urbanos', "luly"=>1, "propiedades"=>"", "GI"=>"", "lamudi"=>""],
        ["name"=>'oficinas en condominios', "luly"=>1, "propiedades"=>"", "GI"=>"", "lamudi"=>""],
        ["name"=>'huerta', "luly"=>1, "propiedades"=>"", "GI"=>"", "lamudi"=>""],
        ["name"=>'local', "luly"=>0, "propiedades"=>"LOCAL_COMERCIAL", "GI"=>"local", "lamudi"=>"Comercio"],
        ["name"=>'fabricas', "luly"=>1, "propiedades"=>"", "GI"=>"", "lamudi"=>""],
        ["name"=>'nave', "luly"=>0, "propiedades"=>"NAVE_COMERCIAL", "GI"=>"edificio", "lamudi"=>"Comercio"],
        ["name"=>'penthouse', "luly"=>1, "propiedades"=>"", "GI"=>"", "lamudi"=>""],
        ["name"=>'quinta', "luly"=>1, "propiedades"=>"", "GI"=>"", "lamudi"=>""],
        ["name"=>'rancho', "luly"=>0, "propiedades"=>"RANCHO", "GI"=>"campo", "lamudi"=>"Terreno"],
        ["name"=>'suite', "luly"=>1, "propiedades"=>"", "GI"=>"", "lamudi"=>""],
        ["name"=>'villa', "luly"=>1, "propiedades"=>"", "GI"=>"", "lamudi"=>""],
        ["name"=>'nave industrial', "luly"=>1, "propiedades"=>"", "GI"=>"", "lamudi"=>""],
        ["name"=>'casa', "luly"=>0, "propiedades"=>"CASA", "GI"=>"casa", "lamudi"=>"Casa"],
        ["name"=>'terreno', "luly"=>0, "propiedades"=>"TERRENO_PLANO", "GI"=>"lote", "lamudi"=>"Terreno"],
        ["name"=>'recamara', "luly"=>0, "propiedades"=>"RECAMARA", "GI"=>"departamento", "lamudi"=>"Casa"],
        ["name"=>'edificio', "luly"=>0, "propiedades"=>"EDIFICIO", "GI"=>"edificio", "lamudi"=>"Comercio"],
 
        );

        $uses=array( 'Agricola','Comercial','Ganadero','Hotelero','Industrial','Campestre','Monasterio','Ninguno','Tiempo compartido','Productos','Cementerios','Residencial','Turistico','Agricola','Comercial-Industrial','Comercial-Residencial','Comercial-Residencial-Industrial');
        
        $categories=array("Exteriores", 'Accesos', "Acabados", "Extras casa", "Senal de Tv", 
        "Inclusiones dentro de la casa", "Puntos cercanos", "Servicios basicos", "Vistas alrededor de la casa","area de albercas");

        $features = array( 
                    array('cesped', $categories[0]), 
                    array('Acabados de lujo',$categories[2]),
                    array('Acesso a la 3ra edad',$categories[1]),
                    array('Acceso por camino de tierra',$categories[1]),
                    array('Aire acondicionado',$categories[5]),
                    array('Amueblado',$categories[5]),
                    array('árboles',$categories[0]),
                    array('Área de eventos',$categories[3]),
                    array('Área de lavado',$categories[3]),
                    array('Cable/Sky',$categories[4]),
                    array('Calentador de agua',$categories[7]),
                    array('Balcón',$categories[5]),
                    array('centros comerciales cercanos',$categories[6]),
                    array('ciclovía',$categories[6]),
                    array('cisterna',$categories[7]),
                    array('cocina equipada',$categories[5]),
                    array('comedor',$categories[5]),
                    array('cortinas/persianas',$categories[5]),
                    array('Cristalería',$categories[2]),
                    array('Doble lavamanos',$categories[5]),
                    array('Escuelas cercanas',$categories[6]),
                    array('estudio privado',$categories[5]),
                    array('gas',$categories[7]),
                    array('iluminación',$categories[7]),
                    array('internet/Wifi',$categories[7]),
                    array('jacuzzi',$categories[9]),
                    array('Jardín',$categories[0]),
                    array('Linea telefónica',$categories[7]),
                    array('Mascotas',$categories[3]),
                    array('Mercado dentro',$categories[6]),
                    array('Parques cercanos',$categories[6]),
                    array('parrilla',$categories[5]),
                    array('patio',$categories[2]),
                    array('Alberca no techada',$categories[9]),
                    array('Pisos de mármol',$categories[2]),
                    array('seguridad',$categories[3]),
                    array('teatro en casa',$categories[5]),
                    array('terraza',$categories[5]),
                    array('TV',$categories[5]),
                    array('Utilidades (agua / electricidad)',$categories[7]),
                    array('Vestidor',$categories[5]),
                    array('vista al mar',$categories[8]),
                    array('vista al parque',$categories[8]),
                    array('vista de la ciudad',$categories[8]),
                    array('vista panorámica',$categories[8]),
                    array('Casa Adjudicada',$categories[3]),
                    array('oficinas',$categories[3]),
                    array('corriente trifasica',$categories[7]),
                    array('Piso de loseta',$categories[2]),
                    array('Sala de conferencias',$categories[5]),
                    array('Seguridad contra incendios',$categories[5]),
                    array('Instalación eléctrica',$categories[7]),
                    array('Divisiones',$categories[3]),
                    array('Caseta de vigilancia',$categories[3]),
                    array('Cuarto de maquillaje',$categories[5]),
                    array('Alberca techada',$categories[9]),
                    array('Ático',$categories[3]),
                    array('Cuarto de servicios',$categories[3]),
                    array('Baño de servicios',$categories[3]),
                    array('Portón eléctrico',$categories[3]),
                    array('Cochera eléctrico',$categories[3]),
                    array('Acceso asfaltada',$categories[1]),
                    array('Orilla de calle asfaltada',$categories[1]),
                    array('Tinaco',$categories[7]),
                    array('Teja',$categories[2]),
                    array('Cocina Integral',$categories[5]),
                    array('Electricidad Subterranea',$categories[7]),
                    array('Concreto Hidráulico',$categories[1]),
                    array('Sala',$categories[5]),
                    array('Pavimentación en el área',$categories[1]),
                    array('Clóset',$categories[5]),
                    array('Acabados Vitropiso',$categories[2]),
                    array('Elevador',$categories[1]),
                    array('Calefaccion',$categories[7])
                );
          
        foreach ($status as $item) {
            DB::table('property_statuses')->insert([
                'name' => $item["name"],
                'luly' => $item["luly"],
                'propiedades' => $item["propiedades"],
                'gran_inmobiliaria' => $item["GI"],
                'lamudi'=> $item["lamudi"],
            ]);
        }

        $data_id=array();
        foreach ($features as $item) {
            $feature=new Feature;
            $feature->name=$item[0];
            $feature->save();
            array_push($data_id,array($feature->id, $item[1]));
        }


        foreach ($categories as $item) {

            DB::table('features')->insert([
                'name' => $item
            ]);
        }

        //Agrupar las caracteristicas
    foreach ($data_id as $item) {
            $feature_item=Feature::find($item[0]);
            if($feature_item)
            {
                $category=Feature::where("name",$item[1])->first();
                if($category)
                {
                    $feature_item->parent_id=$category->id;
                    $feature_item->save();
                }
            }
        }

       foreach ($uses as $item) {
            DB::table('property_uses')->insert([
                'name' => $item
            ]);
        }
        foreach ($types as $item) {
            DB::table('property_types')->insert([
                'name' => $item["name"],
                'luly' => $item["luly"],
                'propiedades' => $item["propiedades"],
                'gran_inmobiliaria' => $item["GI"],
                'lamudi'=> $item["lamudi"],
            ]);
        }

          //Usuario test
          $user=new User;
       
        
          $user->full_name= "Luis Bernardo";
          $user->last_name= "Perez Torres";
          $user->email="sistemas@acadep.com";
        //    $user->email="sistemas@acadep.com";
          $user->password=bcrypt("acadep01");
          $user->save();
  
          $user->assignRole("Admin");
  
          $company = new Company;
          $company->name = 'acadep';
          $company->phone = '6121117686';
          $company->address = 'ignacio allende';
          $company->rfc = 'LASGDC';
          $company->colony = 'Centro';
          $company->zipcode = '23030';
          $company->email = 'sistemas@acadep.com';
          $company->package = 2;
          $company->active = 1;
          $company->owner = 1;
  
          $company->save();
  
          $user->company_id = 1;
          $user->save();

        

        //$users = factory(App\Property::class, 500)->create();
    }
}