<?php

namespace App\Http\Controllers;
use App\Contact;
use App\ContactNote;
use Illuminate\Http\Request;
//use \Spatie\Permission\Models\Role;
use App\Http\Requests\ContactRequest;
use App\ContactPhone;
use App\User;
use App\Property;
class ContactController extends Controller
{
    //
    public function home()
    {
        $contacts=Contact::where('company_id',auth()->user()->company_id)->orderBy('name')->paginate(10);
        
        return view("contact.list")->with(compact('contacts'));
    }
    function create(ContactRequest $request){
        //dd($request->all());
        $companyId = auth()->user()->company->id;
        $emailValidation = Contact::where('email',  $request->get('email'))
        ->where('company_id', $companyId); 
        if($emailValidation->count() > 0)
        {
            //Pude regresarlo al contacto registrado
            return back()->with('error', 'El email ya esta en uso');
        }
        $contact = new Contact();
        $contact->company_id = $companyId;
        $contact->name = $request->get('name');
        $contact->address = $request->get('address');
        $contact->surname = $request->get('surname');
        $contact->job = $request->get('job');
        $contact->status = $request->get('status');
        $contact->origin = $request->get('origin');
        $contact->type = $request->get('type');
        $contact->email = $request->get('email');
        $contact->otros = $request->get('otros');
        $contact->save();
        foreach($request->get('phone') as $a){
            if($a['phone'] != "")
            {
                 if(!is_numeric($a['phone'])) {
                    return back()->with('error', 'El telefono debe contener solo dígitos');
                }
                $phone = new ContactPhone();
                $phone->phone = $a['phone'];
                
                if( $a['type'] ==""){
                        $a['type'] = "Celular";
                }
                $phone->type = $a['type'];
                $phone->contact_id = $contact->id;
                $phone->save();
            }
        }
    
        return redirect()->action('ContactController@home')->with('success','Contacto ingresado con éxito');
    }

    public function showCreate()
    {
        return view("contact.addContact");
    }

    public function edit($id){

        $contact=auth()->user()->company->contacts()->find($id);

        if(!$contact)
        {
            abort(404);
        }

        $phones = ContactPhone::where('contact_id',$contact->id)->get();
        return view("contact.edit")->with(compact('contact', 'phones'));
    }

    public function update(ContactRequest $request, $id){
        $companyId = auth()->user()->company->id;
        $emailValidation = Contact::where('email',  $request->get('email'))
        ->where('company_id', $companyId)->get(); 
        if($emailValidation->count() > 0)
        {
            if($emailValidation[0]->email != $request->get('email')){
                return back()->with('error', 'El email ya existe');
            }
        }
        //Pude regresarlo al contacto registrado
           
        $contact = Contact::find($id);
        $contact->name = $request->get('name');
        $contact->address = $request->get('address');
        $contact->surname = $request->get('surname');
        $contact->job = $request->get('job');
        $contact->status = $request->get('status');
        $contact->origin = $request->get('origin');
        $contact->type = $request->get('type');
        $contact->email = $request->get('email');
        $contact->otros = $request->get('otros');
        $phoness=ContactPhone::where('contact_id',$contact->id)->delete();
        if($request->get('phone'))
        {
            foreach($request->get('phone') as $a){
                if($a['phone'] != "")
                {
                    if(!is_numeric($a['phone'])) {
                        return back()->with('error', 'El telefono debe contener solo dígitos');
                    }
                    $phone = new ContactPhone();
                    $phone->phone = $a['phone'];
                    if($a['type'] == "")
                    {
                        $a['type'] = "Celular";
                    }
                    $phone->type = $a['type'];
                    $phone->contact_id = $contact->id;
                    $phone->save();
                }
            }
        }
    
        $contact->save();  //UPDATE
        return redirect(url('home/contact/'.$contact->id))->with('success', 'Contacto actulizado con éxito');
        //return redirect()->action('ContactController@home');
    }

    public function showContact($id){
        $contact=auth()->user()->company->contacts()->find($id);

        if(!$contact)
        {
            abort(404);
        }

      
        if($contact == null)
        {
            return back();
        }
        $phones=ContactPhone::where('contact_id',$contact->id)->get();
        $agents = User::where("company_id", auth()->user()->company_id)->role("Agent")->get();        //$users=User::where('company_id',auth()->user()->company_id)->get();
       // dd($phones);
        return view("contact.view")->with(compact('contact','phones','agents'));
    }

    public function delete(Request $request)
    {
        $contact=auth()->user()->company->contacts()->find($request->user_id);

        if(!$contact)
        {
            abort(404);
        }
      
        $contact->delete();
        return response("Contacto borrado con éxito", 200);
    }
    public function updateAgent(Request $request)
    {

        //$contact=Contact::find($request->get('contact_id'));
        $contact = Contact::findOrFail($request->get('contact_id'));
        $ant = $contact->agent_id;

        $contact->agent_id = $request->get('agent_id');
        $contact->save();
        $agent = User::find($request->get('agent_id'));
      
        return response()->json([
            'agent_name' => $agent->full_name,
            'ant' => $ant
        ]);
    }


    public function contactForm(Request $request){

        $property = Property::find($request->property_id);
    
       $contact_exists = Contact::checkIfExists($request->email, $property->company_id);
       
       if (!$contact_exists) {

        
            $contact = new Contact;
            $contact->company_id = $property->company_id;
            $contact->name = $request->name;
            $contact->surname = $request->lastname;
            $contact->origin = 5;
            $contact->status = 1;
            $contact->type = 1;
            $contact->email = $request->email;
            $contact->save();


            $content = [
             //   'property_title' => $property->title,
                'property_id' => $property->id,
               // 'property_image' => $property->featured_img->thumbnail,
                'message' => $request->content
            ];

            $note = new ContactNote;
            $note->type = 2; //activity type
            $note->contact_id = $contact->id;
            $note ->content = json_encode($content);
            $note->save();
       } else {

        $contact = Contact::where('email', $request->email)->first();
        $content = [
               'property_id' => $property->id,
               'message' => $request->content
           ];

           $note = new ContactNote;
           $note->type = 2; //activity type
           $note->contact_id = $contact->id;
           $note ->content = json_encode($content);
           $note->save();
        

       }
       
       return back()->with('success', true);


    }
}
