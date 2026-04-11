<?php

namespace App\Http\Middleware;

use Closure;
use App\Invoice;
class CompanyPayment
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
    
        if(!auth()->user()->company){
            return $next($request);
        }else{
            if(auth()->user()->company->is_active)
            {
    
                return $next($request);
                          
            }else{
                return redirect('/suspended');
            }
        }

        


        // if (auth()->user()->company) {
        //     if(auth()->user()->company->is_active)
        //     {
        //         return $next($request);
                        
        //     }else{
        //         return redirect('/suspended');
        //     }
        // } else {
        //     return redirect('/complete-register');
        // }

      
        
        
       
    }
}
