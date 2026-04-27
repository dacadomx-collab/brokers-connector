<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Property;
use App\User;
use App\PropertyStatus;
use App\PropertyType;
use App\PropertyStock;
use App\PropertyUse;
use App\Feature;
use App\FeatureProperty;
use App\Contact;
use App\Company;

use App\State;
use App\Http\Requests\CreatePropertyRequest;
use Carbon\Carbon;
use Mail;
use Illuminate\Validation\Rule;
use DB;
use File;
use Barryvdh\DomPDF\Facade as PDF;

class PropertyController extends Controller
{

    

    public function ChangeView(Request $request){

        session(['view' => $request->value]);

        return redirect(url()->previous());

    }
    
    function index(){

        $properties = User::allMyProperties()->with("local.city")->orderBy('id','desc')->paginate(20,['*'], 'page');
       
        $statuses=PropertyStatus::notinclude()->orderBy("name")->get();
        $types=PropertyType::notinclude()->orderBy("name")->get();

        return view('properties.index', compact('properties', 'statuses', 'types'));
    }

    public function getCityByFilter(Request $request)
    {
        $res=DB::table('cities')->selectRaw("id, name as text")
        ->where("state_id", $request->state)
        ->where("name", 'like', '%'.$request->q.'%')->limit(5)->get()->toArray();

        return response(["results"=>$res], 200);
    }

    public function getStateByFilter(Request $request)
    {
        $res=DB::table('states')->selectRaw("id, name as text")->where("name", 'like', '%'.$request->q.'%')->limit(5)->get()->toArray();

        return response(["results"=>$res], 200);
    }

    function search(Request $request)
    {
       
        $query=User::allMyProperties()->withoutGlobalScope('published')->with("local.city");
        $btn_more_display=false;
        
        if($request->search)
        {
            $query=$query->where("title", "like", "%".$request->search."%");
            $btn_more_display=true;
        }
        
       
        
        if($request->price_min)
        {
            $query=$query->where("price", ">=", $request->price_min);
        }
        

        if($request->price_max)
        {
            $query=$query->where("price", "<=", $request->price_max);
        }
        

        if($request->parking)
        {
            $query=$query->where("parking_lots", "=", $request->parking);
            $btn_more_display=true;
        }
        

        if($request->baths)
        {
            $query=$query->where("baths", "=", $request->baths);
            $btn_more_display=true;
        }

        if($request->rooms)
        {
            $query=$query->where("bedrooms", "=", $request->rooms);
            $btn_more_display=true;
        }
       
        if($request->city)
        {
            $query=$query->whereHas('local.city', function($q) use ($request){

                $q->where('id',$request->city);

            });
        }

        if($request->state)
        {
            $query=$query->whereHas('local.city.state', function($q) use ($request){

                $q->where('id',$request->state);

            });
        }

        if($request->status)
        {
            $query=$query->where("prop_status_id", "=", $request->status);
        }

        if($request->type)
        {
            $query=$query->whereIn("prop_type_id", $request->type);
        }

      

        if($request->order){
            switch ($request->order) {
                case '1':
                    $query->orderBy('price','asc');
                    break;
                case '2':
                    $query->orderBy('price','desc');
                    break;
                case '3':
                    $query->orderBy('title','asc');
                    break;
                case '4':
                    $query->orderBy('title','desc');
                    break;
                case '5':
                    $query->orderBy('featured','desc');
                    break;
                
                default:
                $query->orderBy('id','desc');
                    break;
            }

        }


        $properties=$query->paginate(20,['*'], 'page');
        $statuses=PropertyStatus::notinclude()->orderBy("name")->get();
        $types=PropertyType::notinclude()->orderBy("name")->get();
        $old_inputs=$request->all();
       
        
        return view('properties.index', compact('properties', 'statuses', 'types', 'old_inputs', 'btn_more_display'));
    }

    function view($id)
    {
        $property = auth()->user()->allMyProperties()->find($id);
        if(!$property)
        {
            abort(404);
        }
        $property->with('FileProperties');
        $agents = User::where("company_id", auth()->user()->company_id)->role(["Agent","Admin"])->get();
        // $images = 
        return view('properties.view', compact('property','agents'));
    }

    function create()
    {
        
        $features = Feature::parents()->sortBy(function($features)
        {
            return $features->children()->count();
        });
        
        $stocks=config("app.images_array");
        
        $statuses=PropertyStatus::notinclude()->orderBy("name")->get();
        $types=PropertyType::notinclude()->orderBy("name")->get();
        $uses= PropertyUse::notinclude()->orderBy("name")->get();
        
        $agents = User::where("company_id", auth()->user()->company_id)->role(["Agent","Admin"])->get();
        $property= new Property;
        $features_id=[];
        $year_now=Carbon::now()->year;
        $states=State::orderBy("name")->get();

        
        return view('properties.create', compact('types', 'statuses', 'features','uses' ,'agents', 'property','features_id','year_now','states','stocks'));
    }

    function edit($id)
    {
       
        $property = auth()->user()->allMyProperties()->find($id);
        if(!$property)
        {
            abort(404);
        }
        
        $property->with('FileProperties');
        
        $features_id=[];
        
        foreach($property->features->pluck('id') as $id)
        {
            array_push($features_id, $id);
        }

        $year_now=Carbon::now()->year;
       
        $features = Feature::parents()->sortBy(function($features)
        {
            return $features->children()->count();
        });

        $stocks=config("app.images_array");


        
        $statuses=PropertyStatus::notinclude()->orderBy("name")->get();
        $types=PropertyType::notinclude()->orderBy("name")->get();
        $uses= PropertyUse::notinclude()->orderBy("name")->get();
        


        $states=State::orderBy("name")->get();
        $agents = User::where("company_id", auth()->user()->company_id)->role(["Agent","Admin"])->get();
        
        return view('properties.edit', compact('property','types','uses' ,'statuses', 'features','features_id','agents','year_now','states','stocks'));
    }

    function update(CreatePropertyRequest $request){
        
        if($request->filled("key"))
        {
            $request->validate([
                'key' => [Rule::unique('properties')->where(function ($query) use ($request) {
                    return $query->where('company_id', $request->company_id)
                                ->where("id","!=",$request->property_id)
                                ->where("deleted_at", null);
                })],
            ],
            [
                "key.unique"=>"El indentificador ya ha sido ingresado para este empresa, ingrese una diferente"
            ]);
        }
        
        
        $request["antiquity"]=0;
        
        if($request->year=='y_3')
        {
            $request->validate([
                "antique_year"=>"required|numeric"
            ],
            [
                "antique_year.required"=>"Ingresar el año de antiguiedad de la propiedad",
                "antique_year.numeric"=>"Ingresar el año de antiguiedad con dígitos"
            ]);

            $request["antiquity"]=$request->antique_year;
        }
        else if($request->year=='y_2')
        {
            $request["antiquity"]=Carbon::now()->year;
        }
        
        

        if($request["commission"] <= 0 || $request["commission"]=="")
        {
            $request["type_commission"]=0;
        }
        
       
        $request["created_by"]=auth()->user()->id;
    
        $request["company_id"]=auth()->user()->company->id;

        $property = Property::withoutGlobalScope('published')->findOrFail($request->property_id);

        //desactivar opciones de bolsas
        $property->offBbcOptions($request);
        
        $property->update($request->all());

        FeatureProperty::syncFeatures($request->features,$property->id);

        

        //Notificar de nuevas propiedades
        if(in_array(auth()->user()->company_id, config('app.notification_companies')))
        {
            $data="La propiedad llamada ". $property->title." con ID ".$property->id." a sido actualizda por: ".auth()->user()->f_name;
            $viewa = strval(view('email.notification-properties')->with(compact('data')));
            $email = new \SendGrid\Mail\Mail(); 
            $email->setFrom("propiedades@brokersconnector.com", "Brokers Connector");
            $email->setSubject("PROPIEDAD ACTUALIZADA");
            $email->addTo(config('app.notification_email'), "Contacto");
           
            $email->addContent(
                "text/html", $viewa
            );
            $sendgrid = new \SendGrid(env('SENDGRID_API_KEY'));
            try {
                $response = $sendgrid->send($email);
           
                //return "al cien pariente";
            } catch (Exception $e) {
                
                //return 'Caught exception: '. $e->getMessage() ."\n";
                
            }
        }
        
        return redirect(url('properties/index'))->with("success", "La propiedad ".$property->title." ha sido actualizada");
    }
    
    function store(CreatePropertyRequest $request){
        
      
        if($request->filled("key"))
        {
            $request->validate([
                'key' => [Rule::unique('properties')->where(function ($query) use ($request) {
                    return $query->where('company_id', $request->company_id)
                                ->where("deleted_at", null);
                })],
                
            ],
            [
                "key.unique"=>"El indentificador ya ha sido ingresado para este empresa, ingrese una diferente"
            ]);
        }


        if($request->year=='y_3')
        {
            $request->validate([
                "antique_year"=>"required|numeric"
            ],
            [
                "antique_year.required"=>"Ingresar el año de antiguiedad de la propiedad",
                "antique_year.numeric"=>"Ingresar el año de antiguiedad con dígitos"
            ]);

            $request["antiquity"]=$request->antique_year;
        }
        else if($request->year=='y_2')
        {
            $request["antiquity"]=Carbon::now()->year;
        }
      

        
        if($request["commission"] <= 0 || $request["commission"]=="")
        {
            $request["type_commission"]=0;
        }

        $request["created_by"]=auth()->user()->id;
    
        $request["company_id"]=auth()->user()->company->id;
        // $request['exterior'] = $request->exterior;
        // $request['i nterior'] = $request->interior;
        // $request['interior'] = $request->interior;
       
        // dd($request->all());

        $property = Property::create($request->all());

        if($request->filled('features'))
        {
            foreach ($request->features as $id_feature) {
                $feature = new FeatureProperty;
                $feature->feature_id = $id_feature;
                $feature->property_id = $property->id;
                $feature->save();
            }

        }

        

        //Notificar de nuevas propiedades
        if(in_array(auth()->user()->company_id, config('app.notification_companies')))
        {
            $data="Nueva propiedad llamada ". $property->title." con ID ".$property->id." a sido creada por: ".auth()->user()->f_name;
            $viewa = strval(view('email.notification-properties')->with(compact('data')));
            $email = new \SendGrid\Mail\Mail(); 
            $email->setFrom("propiedades@brokersconnector.com", "Brokers Connector");
            $email->setSubject("NUEVA PROPIEDAD");
            $email->addTo(config('app.notification_email'), "Contacto");
           
            $email->addContent(
                "text/html", $viewa
            );
            $sendgrid = new \SendGrid(env('SENDGRID_API_KEY'));
            try {
                $response = $sendgrid->send($email);
           
                //return "al cien pariente";
            } catch (Exception $e) {
                
                //return 'Caught exception: '. $e->getMessage() ."\n";
                
            }
        }
        
        return redirect(url('properties/add-images/'.$property->id));
        
    }

    public function delete(Request $request)
    {
        //Buscar la propiedad
        $property=Property::withoutGlobalScope('published')->findOrFail($request->id);
        
        //Eliminar sus caracteristicas
        $property->features();

        //Eliminar sus imágenes
        // foreach ($property->fileProperties()->get() as $file) 
        // {
        //     File::delete(public_path($file->src));
        //     File::delete(public_path($file->thumbnail));
            
        //     $file->delete();
        // }

        //Eliminar la propieadad
        $property->delete();

        if($request->ajax())
        {
            return response("La propiedad ".$property->title." ha sido eliminada");
        }else{
            return redirect(url('properties/index'))->with('success' , "La propiedad".$property->title." ha sido eliminada");
        }

        

        
    }

    public function showInfo(Request $request, $id){

        
        $property=Property::findOrFail($id);
        if($request->company)
        {
            $contact=Company::findOrFail($request->company)->owner_user;
        }
        else
        {
            $contact=$property->agent_assignt;
        }
        $url_full=request()->getSchemeAndHttpHost();
        

       
        return view("properties.shared.index" , compact("property", 'url_full', 'contact'));
    }

    public function addImages($id){

        $property = auth()->user()->allMyProperties()->findOrFail($id);

        return view('properties.add-images', compact('property'));
    }

    public function properties(){

        return Property::latest()->take(6)->get();
    }

    public function agent(Request $request){
        //Asignar agente a la propiedad
        $id_agent = $request->get('agent_id');
        $id_property = $request->get('property_id');
      
        $property = Property::findOrFail($id_property);
        $ant = $property->agent_id;
        $property->agent_id = $id_agent;
        $property->save();
        $agent = User::find($id_agent);
        $agent_img ='/img/profile/sin-avatar.png';
        if( $agent->avatar != '')
        {
            $agent_img = $agent->avatar ;
        }
        

        return response()->json([
            'agent_name' => $agent->name,
            'agent_img'=> $agent_img,
            'ant' => $ant
        ]);
    }


    public function state(Request $request){

        $id_property = $request->get('property_id');
        $published = $request->get('published');
        $property = Property::findOrFail($id_property);
        $ant = $property->published;

        if($published == 1)
        {
            $published = 0;
        }else{
            $published = 1;
        }
        $property->published =$published ;
        $property->save();
        return response()->json([
            'ant' => $ant,
            'published' => $property->published
        ]);
    }

    public function deletesession()
    {
        if(session()->has("email-properties")){
            session()->forget("email-properties");
        }
       

        return response("Session eliminda", 200);
    }

    public function removeProperty(Request $request)
    {
        $msg="No hay propiedades seleccionadas";
        $checkbox_property=[];
        if(session()->has("email-properties"))
        {

            $checkbox_property=session()->get("email-properties");
            
            $key = array_search($request->id, $checkbox_property);
            unset($checkbox_property[$key]);
    
            $i=0;
            $get_parameters="";
            foreach($checkbox_property as $item)
            {
                $get_parameters.="checkbox_property[".$i."]=".$item."&";
                $i++;
            }
            session(['email-properties' => $checkbox_property]);
            $msg="Propiedad removida";
            
        }

        return response(["msg"=>$msg, "url"=>"/properties/email?".$get_parameters ], 200);

        
        
    }

    public function sendEmail(Request $request){
       // dd($request);
        if($request->color == "")
        {
            $request->color = "#05526a";
        }else
        {
            $pos = strpos($request->color, "rgb");
            if($pos === false)
            {
            $request->color = "#".$request->color;
            }
        }
        if($request->colorLetra == "")
        {
            $request->colorLetra = "#ffffff";
        }else
        {
            $pos = strpos($request->colorLetra, "rgb");
            if($pos === false)
            {
            $request->colorLetra = "#".$request->colorLetra;
            }
            
        }
        if(!$request->checkbox_property)
        {
            return back()->with("fail", "Elegir propiedades a enviar");
            
        }
        //Validar maximo de 10  propieades
        if(count($request->checkbox_property)>10)
        {
            return back()->with("fail", "No se permiten enviar más de 10 propieades");
            
        }
        $user = auth()->user();
        foreach($request->email as $key_email)
        {

                $contact = Contact::findOrFail($key_email);
        
                $properties=Property::whereIn("id", $request->checkbox_property)->get();
               
                // dd("https://maps.googleapis.com/maps/api/staticmap?center=".$properties[0]->lat.",".$properties[0]->lng."&zoom=15&size=765x300&markers=color:red%7Clabel:•%7C".$properties[0]->lat.",".$properties[0]->lng."&key=AIzaSyCd-nS2-__7zMOypXiB_EJC53l-8s1cg84");
               
              
                
                
                $view = 0;
                $url = config('app.server');
                $message = $request->message;
                $viewa = strval(view('email.new-email-property')->with(compact('properties')));
                // return $viewa;
                $email = new \SendGrid\Mail\Mail(); 
                $email->setFrom("propiedades@brokersconnector.com");
                $email->setSubject($request->asunto);
                $email->addTo($contact->email, "Contacto");
                //$email->addContent("text/plain", $message);

                $email->addContent(
                    "text/html", $viewa
                );
                $sendgrid = new \SendGrid(env('SENDGRID_API_KEY'));
                try {
                   $response = $sendgrid->send($email);
            
                    //return "al cien pariente";
                } catch (Exception $e) {
                    return redirect()->route('show.all.properties')->with('error','Disculpe intente de nuevo porfavor');
                    //return 'Caught exception: '. $e->getMessage() ."\n";
                    
                }
            
        }
     
       /*
        Mail::send('email.email_properti', compact('property','user','request'), function($msj) use($request) {
            $msj->to($request->destinatario);
            $msj->replyTo('daniel@acadep.com', 'Reply Guy');
            $msj->subject($request->asunto);
            }); 

        */
        $msj_success="Correo enviado con éxito a: ";
        foreach($request->email as $key_email)
        {
            $contact = Contact::findOrFail($key_email);
            $msj_success.=$contact->name.", ";
            
        }
        
        if(session()->has("email-properties")){
            session()->forget("email-properties");
        }
        
        if($request->page == null)
        {
            return redirect()->route('show.all.properties')->with('success',$msj_success);
        }else
        {
            return redirect()->route('show.all.stock')->with('success',$msj_success);
        }
        
    }

    public function emailView(Request $request ){

       
        session(['email-properties' => $request->checkbox_property]);
        

        //Vista index enviar correo seleccion de correos electronicos
        $properties = Property::whereIn(
            'id', session()->get("email-properties") ? session()->get("email-properties") : []
        )->get();
        
        $user = auth()->user();
        $contacts= $user->company->contacts;
        
        return view('email.property_email')->with(compact('user', 'properties','contacts','request'));
    }

    public function emailpdf(Request $request, $id){
        if($request->color == "")
        {
            $request->color = "#05526a";
        }else
        {
            $pos = strpos($request->color, "rgb");
            if($pos === false)
            {
            $request->color = "#".$request->color;
            }
            
        }
        if($request->colorLetra == "")
        {
            $request->colorLetra = "#ffffff";
        }else
        {
            $pos = strpos($request->colorLetra, "rgb");
            if($pos === false)
            {
            $request->colorLetra = "#".$request->colorLetra;
            }
        }
        $property = Property::findOrFail($id);
        $images = $property->images()->take(4);
        $view = 1;
        $message = $request->message;
        $url = config('app.server');

        $user = auth()->user();
        $pdf = PDF::loadView('email.email_properti', compact('property','user','request','images','view', 'url', 'message'));
        return $pdf->stream('test.pdf');
    }

    public function preview(Request $request, $id){
        
        if($request->color == "")
        {
            $request->color = "#05526a";
        }else
        {
            $pos = strpos($request->color, "rgb");
            if($pos === false)
            {
            $request->color = "#".$request->color;
            }
            
        }
        if($request->colorLetra == "")
        {
            $request->colorLetra = "#ffffff";
        }else
        {
            $pos = strpos($request->colorLetra, "rgb");
            if($pos === false)
            {
            $request->colorLetra = "#".$request->colorLetra;
            }
        }
        $property = Property::findOrFail($id);
        $images = $property->images()->take(4);
        $view = 1;
        $message = $request->message;
        $url = config('app.server');

        $user = auth()->user();
        if($request->style == "1")
        {
            return view('email.emailBee')->with(compact('property','user','request','images','view', 'url', 'message'));
        }
 
    }
    
    public function add_featured(Request $request)
    {
        //Cambiar de estado el featured
        $property = Property::findOrFail($request->id_property);
        if($property->featured)
        {
            $property->featured = false;
        }else{
            $property->featured = true;
        }
        $property->save();
        return response()->json(["featured"=> $property->featured,"msg"=>"La propiedad cambio de estado"],200);
    }

    public function print($id){

        $property = Property::findorFail($id);

        if ($property->agent_id) {
           $user = $property->agent;
        } else {
           $user = auth()->user();
        }
        

        // dd(auth()->user()->company->properties()->where('id', $property->id)->first());

        if(!auth()->user()->company->properties()->where('id', $property->id)->first()){
            
        }else{
            $pdf = PDF::loadView('properties.pdf-templates.1', compact('property', 'user'));

            return $pdf->stream($property->title.'.pdf');
        }

    
// return view('properties.pdf-templates.1', compact('property'));
       
    }


    public function orderImages(Request $request){
        $property = Property::find($request->property_id);
        $oldIndex = $request->old;
        $newIndex = $request->new;

       

        $images = $property->images();

        // return $images;

        $date_temp = $images[$oldIndex]->index_order; // guardamos la fecha de la primera imagen para cambiarla sin perder el dato

        $images[$oldIndex]->index_order = $images[$newIndex]->index_order;  //cambiamos la fecha de la image por la de la imagen 2
        $images[$oldIndex]->save();  //guardamos

        $images[$newIndex]->index_order = $date_temp;  //hacemos el proceso a la inversa con el valor que guardamos
        $images[$newIndex]->save();  //guardamos


        return response()->json(['success' => 'success'], 200);

    }
}

