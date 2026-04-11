<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

use App\Property;
use App\User;
use App\Company;
use App\Feature;
use App\PropertyType;
use App\PropertyUse;
use App\PropertyStatus;
use App\PropertyStock;


class StockController extends Controller
{
    public function ChangeView(Request $request){

        session(['view-stock' => $request->value]);

        return redirect(url()->previous());

    }

    public function index()
    {
        $service_adquired=auth()->user()->company->service;
      
        $properties_count=Property::bbcgeneral()->count();
        $properties=Property::bbcgeneral()->paginate(15);

        $statuses=PropertyStatus::select("*")->notinclude()->orderBy("name")->get();
        $types=PropertyType::select("*")->notinclude()->orderBy("name")->get();

        return view("stock.index", compact("properties", 'statuses', 'types','properties_count','service_adquired'));
    }
    
    function view(Request $request, $id)
    {
        $property = Property::withoutGlobalScope('published')->findOrFail($id);
        $url_full=request()->getSchemeAndHttpHost();

        $contact=$property->agent_assignt;
       
        if($request->contact_email)
        {
            $user=User::where("email", $request->contact_email)->first();
            if($user)
            {
                $contact=$user;
            }
        }
        

        $categories_data = collect();
        foreach ( Feature::parents() as $value) {
           
            $results=$property->features()->where("parent_id", $value->id);
            if($results->count() > 0)
            {
                $categories_data->put($value->name, $results->get());
            }
           
        }
       
        
      
        $property->with('FileProperties');
       
        return view('stock.view', compact('property','categories_data','url_full','contact'));
    }

    function viewCompany($id)
    {
        $service_adquired=auth()->user()->company->service;
        $company=Company::findOrFail($id);
        
        $properties_count=$company->properties()->bbcgeneral()->count();
        $properties=$company->properties()->bbcgeneral()->paginate(15);

        $statuses=PropertyStatus::select("*")->notinclude()->orderBy("name")->get();
        $types=PropertyType::select("*")->notinclude()->orderBy("name")->get();

        return view("stock.company-view", compact("properties", 'properties_count' , 'statuses', 'types', 'company','service_adquired'));
    }

    public function search(Request $request)
    {
       
        $myPropertiesOn=false;
        switch ($request->properties_show) {
            case 1:
                $query=Property::bbcgeneral();
                break;
            
            case 2:
                $query=Property::bbcaspi();
                break;
            
            case 3:
                $query=Property::bbcampi();
                break;

            case 4:
                $query=User::myPropertiesOnStock()->with("local.city");
                $myPropertiesOn=true;
                break;
            
            default:
                $query=Property::bbcgeneral();
                break;
        }

       
        
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

        // if($request->order){
            switch ($request->order) {
                case 1:
                    $query->orderBy('price','asc');
                    break;
                case 2:
                    $query->orderBy('price','desc');
                    break;
                case 3:
                    $query->orderBy('title','asc');
                    break;
                case 4:
                    $query->orderBy('title','desc');
                    break;
                case 5:
                    $query->orderBy('created_at','desc');
                    break;
                case 6:
                    $query->orderBy('created_at','asc');
                    break;
                
                default:
                $query->orderBy("created_at","desc");
                    break;
            }

        // }

        $properties_count=$query->count();
        $properties=$query->paginate(15);
        $statuses=PropertyStatus::select("*")->notinclude()->orderBy("name")->get();
        $types=PropertyType::select("*")->notinclude()->orderBy("name")->get();
        $service_adquired=auth()->user()->company->service;
        $old_inputs=$request->all();

      
        return view("stock.index", compact("properties", 'statuses', 'types', 'old_inputs','btn_more_display', 'properties_count', 'service_adquired', 'myPropertiesOn'));
    }


    public function searchCompany(Request $request, $id)
    {
        $company=Company::findOrFail($id);
        
        switch ($request->properties_show) {
            case 1:
                $query=$company->properties()->bbcgeneral();
              
                break;
            
            case 2:
                $query=$company->properties()->bbcaspi();
                break;
            
            case 3:
                $query=$company->properties()->bbcampi();
                break;
            case 4:
                return redirect(url('stock/index/search').'?properties_show=4');
                break;
            
            default:
                $query=Property::bbcgeneral();
                break;
        }
       
        $query=$query->withoutGlobalScope('published')->with("local.city");
  
        $btn_more_display=false;

        if($request->filled("search"))
        {
            $query=$query->where("title", "like", "%".$request->search."%");
            $btn_more_display=true;
        }

        if($request->filled("price_min"))
        {
            $query=$query->where("price", ">=", $request->price_min);
        }

        if($request->filled("price_max"))
        {
            $query=$query->where("price", "<=", $request->price_max);
        }

        if($request->filled("parking"))
        {
            $query=$query->where("parking_lots", "=", $request->parking);
            $btn_more_display=true;
        }

        if($request->filled("baths"))
        {
            $query=$query->where("baths", "=", $request->baths);
            $btn_more_display=true;
        }

        if($request->filled("rooms"))
        {
            $query=$query->where("bedrooms", "=", $request->rooms);
            $btn_more_display=true;
        }

        if($request->filled("city"))
        {
            $query=$query->whereHas('local.city', function($q) use ($request){

                $q->where('id',$request->city);

            });
        }

        if($request->filled("status"))
        {
            $query=$query->where("prop_status_id", "=", $request->status);
        }

        if($request->filled("type"))
        {
            $query=$query->whereIn("prop_type_id", $request->type);
        }

        // if($request->order){
            switch ($request->order) {
                case 1:
                    $query->orderBy('price','asc');
                    break;
                case 2:
                    $query->orderBy('price','desc');
                    break;
                case 3:
                    $query->orderBy('title','asc');
                    break;
                case 4:
                    $query->orderBy('title','desc');
                    break;
                case 5:
                    $query->orderBy('created_at','desc');
                    break;
                case 6:
                    $query->orderBy('created_at','asc');
                    break;
                
                default:
                $query->orderBy("created_at","desc");
                    break;
            }

        // }

        $properties_count=$query->count();
        $properties=$query->paginate(15);
        $statuses=PropertyStatus::select("*")->notinclude()->orderBy("name")->get();
        $types=PropertyType::select("*")->notinclude()->orderBy("name")->get();
        $service_adquired=auth()->user()->company->service;
        $old_inputs=$request->all();

       
        return view("stock.company-view", compact("properties", 'properties_count' , 'statuses', 'types', 'company', "old_inputs", "btn_more_display",'service_adquired'));
    }
}
