<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \Spatie\Permission\Models\Role;
use App\Http\Requests\ValidateUser;
use App\User;
use File;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function confirmEmail($token) {
        $userConfirm=User::whereToken($token);
        if($userConfirm->count() <= 0)
        {
            return redirect(url('login'))->with("message-error-confirm", 
            'El correo que intenta confirmar no existe o ya ha sido confirmado');
        }

        $userConfirm->firstOrFail()->confirmEmail();
        return redirect(url('login'))->with("message-success-confirm", "Su correo ha sido confirmado. Favor de iniciar sesión");

    }

    public function index()
    {
      
        $users=User::where('company_id',auth()->user()->company_id)->get();
        return view("users.list", compact('users'));
    }

    public function showCreate()
    {
        $user= new User;
        $roles=Role::where("name","!=","Admin")->get();
        return view("users.index", compact('roles','user'));
    }

    public function showEdit($id)
    {
        
        $user=auth()->user()->company->users()->find($id);

        if(!$user)
        {
            abort(404);
        }
       
        $roles=Role::where("name","!=","Admin")->get();
       
        return view("users.edit", compact('user','roles'));
    }

    public function create(ValidateUser $request)
    {    
        if(auth()->user()->company->package == 1)
        {
            return back()->with('error', 'El plan contratado no permite más de 1 usuario.');
        }
        $request->validate(
        [
            'password'=>'required|confirmed|min:8'
        ],
        [
            'password.required'=>'Ingresar la contraseña',
            'password.confirmed'=>'Las contraseñas no coinciden',
            'password.min'=>'La contraseña debe tener al menos 8 caracteres',
        ]);

        $company_id = auth()->user()->company_id;
    
        if($request->file)
        {
            
            $request->validate(
            [
                'file'=>'image|mimes:jpeg,png,jpg,gif,svg|max:2048'
            ]);
                    
            $path = 'companies/'.$company_id.'/avatars//';
            $store = $request->file->store($path);
            
            if($store){
                $request["avatar"]= "/".$store;
            }
        }

        $request['password']=bcrypt($request->password);
        $request['company_id']= $company_id;
        $user=User::create($request->all());
        $user->assignRole($request->user_a);
        return redirect(url('home/users'))->with('success','Usuario ingresado con éxito');
    }

    public function update(ValidateUser $request)
    {

        
        $user=auth()->user()->company->users()->find($request->id);

        if(!$user)
        {
            abort(404);
        }

        if($request->has('file'))
        {
            $request->validate(
            [
                'file'=>'image|mimes:jpeg,png,jpg,gif,svg|max:2048'
            ]);
                    
            $last_id=User::orderBy("id", 'desc')->first()->id;
            
            $company_id = auth()->user()->company_id;
            $path = 'companies/'.$company_id.'/avatars//';
            $request["avatar"]="/".$request->file->store($path);
        }
        
        $request['company_id']=auth()->user()->company_id;

        $request_all;
        if($request->filled('password'))
        {
            $request->validate(
            [
                'password'=>'required|confirmed|min:8'
            ],
            [
                'password.required'=>'Ingresar la contraseña',
                'password.confirmed'=>'Las contraseñas no coinciden',
                'password.min'=>'La contraseña debe tener al menos 8 caracteres',
            ]);
            
            $request['password']=bcrypt($request->password);

            $request_all=$request->all();
        }
        else
        {
            $request_all=$request->except('password');
        }

        $user->update($request_all);
        
        $user->assignRole($request->user_a);

        return back()->with('success','Usuario actualizado con éxito');
    }

    public function  upload_avatar(Request $request)
    {
        
    }

    public function showProfile($id)
    {
       
        $user=auth()->user()->company->users()->find($id);

        if(!$user)
        {
            abort(404);
        }
        
        $properties=$user->PropertiesAsignt()->get();
        $company=$user->company;



        return view('users.profile', compact('user','properties', 'company'));
    }

    public function delete(Request $request)
    {
        //Buscar al usuario
        $user=auth()->user()->company->users()->find($request->user_id);

        if(!$user)
        {
            abort(404);
        }
        
        //Verificar si el usuario tiene el rol de adiminstrador
        if(!$user->hasRole("Admin"))
        {
            //Quitar las asignaciones del ususario con propieades
            $user->deallocate();

            //Borrar su avatar
            if($user->avatar)
            {
                File::delete(public_path($user->avatar));
            }

            //Eliminar al usario
            $user->delete();
        }
        
        return response("Usuario ".$user->f_name." ha sido borrado con éxito", 200);
    }
    
}
