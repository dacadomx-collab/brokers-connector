<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\ProfileRequest;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Response;

use Illuminate\Support\Str;
use App\Company;
use File;
use App\User;
use App\Contact;
use App\Property;
use App\Service;
use App\Invoice;
use Carbon\Carbon;
use PDF;
use Session;

use App\Http\Resources\Portales\Propiedades as PropertyResource;


class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {

        $this->middleware('auth');
    }


    public function createPage($id){
        $property = Property::findOrFail($id);

        

        return view('properties.technical-datasheet.create', compact('property'));
    }

    public function preview(Request $request){

       
        // if($request->style){
            $show = [];
            $style = $request->style;
        // }else{
            // $style = 1;
        // }
        if ($request->show) {
            $show = $request->show;
        }
        
        $property = Property::findorFail($request->id_property);

            $property->title = $request->title;
            $property->description = $request->description;

        if($request->agent){
            $user = User::find($request->agent);
        }else{

            $user = null;
        }


       $images = $property->imagesPDF();

       for ($i=0; $i < 10; $i++) { 
        array_push($images, '/images/noimage.png');
       }


        // dd(auth()->user()->company->properties()->where('id', $property->id)->first());

        // if(!auth()->user()->company->properties()->where('id', $property->id)->first()){
            
        // }else{
            $pdf = PDF::loadView('properties.pdf-templates.1', compact('property', 'user', 'style', 'show', 'images'));

             return $pdf->stream($property->title.'.pdf');
        // }

    
        //return view('properties.pdf-templates.1', compact('property', 'user'));
       
    }


    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        if(!auth()->user()->verified) //Validar si su correo fue confirmado
        {
            
            //Si no ha sido confirmado regresarlo al login con un mensaje
            auth()->logout();
            // Session::flash('message-error-confirm', 'Confirmar correo para iniciar sesión');
            return redirect(url("login"))->with("message-error-confirm", "Confirmar correo para iniciar sesión");
        }

        $company = auth()->user()->company;
        
        $allMyProperties=User::allMyProperties();
      
        if($allMyProperties->count())
        {
            $allMyProperties=User::allMyProperties()->get();
        }


        $properties = collect([]);
        $number_properties = 0;

        if($company)
        {
            $company_properties=$company->properties();
            $properties=$company_properties->with('status')->orderBy('created_at','desc')->limit(10)->get();
            $number_properties=$company_properties->with('status')->where('published',1)->count();
        }

        $services = Service::all();

        return view('home')->with(compact('properties','company','number_properties','services','allMyProperties'));
    }

    public function profile()
    {
        $user=auth()->user();
        return view('profile.index',compact('user'));
    }

    public function updateprofile(ProfileRequest $request)
    {
      
        $user=auth()->user();

        

        if($request->has('file'))
        {
            $request->validate(
            [
                'file'=>'image|mimes:jpeg,png,jpg,gif,svg|max:2048'
            ]);

            if($user->avatar!="")
            {
                File::delete(public_path($user->avatar));
            }

            $request["avatar"]="/".$request->file->store("avatars/".$user->id);
        }

        
        $user->update($request->all());

        return back()->with("success", "La información del perfil ha sido actualizada");
    }

    public function account()
    {

        $company = auth()->user()->company;
        if ($company) {
            return view('account.index', compact('company'));
        } else {
           return redirect(url('/'));
        }
        
        
    }

    public function suspended(){

       if (auth()->user()->company->is_active) {
           return redirect(url('home'));
       }

        return view('suspended');
    }

    public function completeRegister(){

       if (auth()->user()->company) {
           return redirect(url('home'));
       }

        return view('complete-register');
    }

    public function processCompleteRegister(Request $request)
    {
        // Aquí puedes guardar datos si agregas inputs al formulario
        // Por ahora, solo redireccionamos al Home para que puedas entrar
        return redirect()->route('home');
    }

}
