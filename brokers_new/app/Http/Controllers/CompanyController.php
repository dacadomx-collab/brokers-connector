<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Http\Requests\StoreCompanyRequest;
use Illuminate\Validation\Rule;
use App\Company;
use App\Invoice;
use App\Service;
use File;
use Validator;


class CompanyController extends Controller
{
    public function plans()
    {
        $company; 
        if(auth()->user()->company!=null)  //ASK que pasa en el else?
        {
            $company = auth()->user()->company;
        }
        $services = Service::all();
        return view("website.plans")->with(compact('services','company'));
    }

    public function editPlan(Request $request)
    {   
        $company; 
        if(auth()->user()->company!=null)
        {
            $company=auth()->user()->company;
        }
        //$company->package = $request->package;
        //$company->save();
        //ENVIAR EMAIL CON EL ID DE LA COPAÑIA
        $email = new \SendGrid\Mail\Mail(); 
        $email->setFrom("correos@brokersconnector.com", "CAMBIO DE PAQUETE");
        //$email->setReplyto();
        $email->setSubject('CAMBIO DE PAQUETE');
       // $email->addTo('albertt.villanueva@gmail.com', 'Betun');
      //  $email->addTo('bernado.perez@acadep.com', 'Berni');
        $email->addTo('daniel@acadep.com', 'Daniel');
        $email->addTo('sistemas@acadep.com', 'Malcom');
        $email->addContent(
            "text/html", 'La compañia '. $company->id . ' quiere cambiar del paquete de'. $company->m_package->service.'-'.$company->m_package->id .' a el paquete'. $request->package
        );
        $sendgrid = new \SendGrid('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');
        try {
            $response = $sendgrid->send($email);
        } catch (Exception $e) {
            return 'Caught exception: '. $e->getMessage() ."\n";
        }
        //Termina email
        $services = Service::all();
        //$success = 'El plan cambio';
        return back()->with('success', 'En revisión.');
    }


    public function update(StoreCompanyRequest $request)
    {
        $user = auth()->user();
        $company = $user->company;
        $data =$request->validated();
        if($request->has('file'))
        {
            $request->validate(
            [ 'file'=>'image|mimes:jpeg,png,jpg,gif,svg|max:2048'],
            [ 'file.image'=>"El logo debe de ser una imagen y pesar menos de 2MB" ]);
            if($company->logo!="")
            {
                File::delete(public_path($company->logo));
            }
            $data["logo"] = "/".$request->file->store("companies/".$user->id);
        }
        $company->update($data);    
        return back()->with("success", "Información actualizada");
    }


    public function store(StoreCompanyRequest $request){

        $regex = '/^[a-zA-Z0-9][a-zA-Z0-9-]+[a-zA-Z0-9]$/';  //el dominio debe cumplir con este formato, el servidor solo acepta subdominios en este formato

        if (!preg_match($regex, $request->dominio)) {  //si no cumple se envia hacia atras con error
           return back()->withErrors('Por favor escriba un nombre de dominio valido')->withInput($request->all());
        }

         //formateamos el subdominio
        //quitamos espacios
        $domain = str_replace(' ', '', $request["dominio"]);
        //convertimos en minusculas y concatenamos el dominio principal
        $domain = strtolower($domain).".brokersconnector.com";
        $domainExists = Company::where('dominio', $domain)->exists();

        if ($domainExists) {
            return back()->withErrors('El dominio que intenta registrar ya existe, por favor elija otro')->withInput($request->all()); //si el dominio existe enviamos warning para que lo cambie, los subdominios son unicos y detergentes
        }

        $request["dominio"] = $domain;

        $user = auth()->user();
        $company = new Company;
        $request['owner'] = $user->id;

        if($request->has('file'))
        {
            $request->validate(
            [ 'file'=>'image|mimes:jpeg,png,jpg,gif,svg|max:2048'],
            [ 'file.image'=>"El logo debe de ser una imagen y pesar menos de 2MB" ]);
            if($company->logo!="")
            {
                File::delete(public_path($company->logo));
            }
            $request["logo"]="/".$request->file->store("companies/".$user->id);
        }

        $request['active'] = 1;

        $company_created = $company->create($request->all()); //creación de compañia

        $user->company_id = $company_created->id;
        $user->save();


            $invoice = new Invoice;
            $invoice->status = '1';
            $invoice->name = 'Periodo de prueba';
            $invoice->cost_package = 0;
            $invoice->cost_user = 0;
            $invoice->company_id = $company_created->id;
         //   $invoice->invoice_date = Carbon::today();
            $invoice->payday = Carbon::today();
            $invoice->due_date = Carbon::today()->addDays(7);
            $invoice->save();
           // $invoice->services()->attach($company_created->m_package->id,['price'=> 0 ]);


            return back()->with("success", "Te has registrado correctamente");
        

    }


    public function updateCompany(StoreCompanyRequest $request){

        $company = auth()->user()->company;
        //dd($request);
        if($request->hasFile('file'))
        {
            $request->validate(
            [ 'file'=>'image|mimes:jpeg,png,jpg,gif,svg|max:2048'],
            [ 'file.image'=>"El logo debe de ser una imagen y pesar menos de 2MB" ]);

            if($company->logo!="")
            {
                File::delete(public_path($company->logo));
            }

            $logo ="/".$request->file->store("companies/".auth()->user()->id);
            $company->logo = $logo;
        }


        $company->name = $request->name;
        $company->email = $request->email;
        $company->rfc = $request->rfc;
        $company->address = $request->address;
        $company->colony = $request->colony;
        $company->phone = $request->phone;
        $company->zipcode = $request->zipcode;
      


        $company->save();

        return back()->withSuccess('Información actualizada correctamente');
    }


}
