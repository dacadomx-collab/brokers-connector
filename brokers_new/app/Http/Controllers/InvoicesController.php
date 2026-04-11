<?php

namespace App\Http\Controllers;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Mail\PaymentMail;
use App\Invoice;
use App\Service;
use App\Company;
use Openpay;
use Exception;
use OpenpayApiError;
use OpenpayApiAuthError;
use OpenpayApiRequestError;
use OpenpayApiConnectionError;
use OpenpayApiTransactionError;
use Redirect;
use Illuminate\Http\JsonResponse;
use Mail;



//require '../vendor/autoload.php';
class InvoicesController extends Controller
{
    //Controlador para la facturación

    public function invoices()
    {

        $invoices = auth()->user()->company->invoices()->orderBy('id','desc')->paginate(10);
        //ordenar facturas por fecha de vencimiento
        return view("invoices.index")->with(compact('invoices'));
    }


    public function invoice($invoice_id)
    {
        $invoice = Invoice::findOrFail($invoice_id);
        $company = auth()->user()->company;
        if($invoice->company_id != $company->id)
        {
            return  abort(404, 'Page not found');
        }
        $services = $invoice->services;
        $name_services = Service::all();
        return view("invoices.view")->with(compact('invoice','company','services','name_services'));
    }


    public function openPay_paynet($invoice)
    {

       // dd('here');
        $invoice = Invoice::find($invoice);
        $company = Company::find($invoice->company_id);
        $services = $services = $invoice->services;
        try {

        $openpay = Openpay::getInstance('myad9doynwfpb6iyoyci',
        'sk_a616a096b2b447f2b8f43281c1110143');


            $customer = array(
                'external_id' => $company->owner_user->id,
                'name' => $company->owner_user->full_name,
                'last_name' => $company->owner_user->last_name,
                'phone_number' => $company->phone,
                'email' => $company->email);
                //dd( $request->deviceIdHiddenFieldName);
            $chargeData = array(
                'method' => 'store',
                'amount' => (float)$invoice->m_total,
                'description' => 'Cargo a tienda',
                'order_id' => $invoice->id,

                'customer' => $customer);
        $charge = $openpay->charges->create($chargeData);

        $url = "https://sandbox-dashboard.openpay.mx/paynet-pdf/myad9doynwfpb6iyoyci/".$charge->payment_method->reference;

        return Redirect::intended($url);
    } catch (OpenpayApiTransactionError $e) {
        $error_code = $e->getErrorCode();
    } catch (OpenpayApiRequestError $e) {
        $error_code = $e->getErrorCode();
    } catch (OpenpayApiConnectionError $e) {
        $error_code = $e->getErrorCode();
    } catch (OpenpayApiAuthError $e) {
        $error_code = $e->getErrorCode();
    } catch (OpenpayApiError $e) {
        $error_code = $e->getErrorCode();
    } catch (Exception $e) {
        $error_code = $e->getErrorCode();
    }

    $error_m = getErrorMessage($error_code);
        if("Error desconocido.2003" == $error_m)
        {
            $openpay = Openpay::getInstance('myad9doynwfpb6iyoyci',
            'sk_a616a096b2b447f2b8f43281c1110143');

            $searchParams = array(
                'order_id' => $invoice->id,
                'limit' => 1
            );
            $charge = $openpay->charges->getList($searchParams);

            //dd($charge[0]->payment_method->reference);
            $url = "https://sandbox-dashboard.openpay.mx/paynet-pdf/myad9doynwfpb6iyoyci/".$charge[0]->payment_method->reference;
            return Redirect::intended($url);
        }

    }

    public function openPay_spei(Request $request, $invoice)
    {

        try {

            $invoice = Invoice::find($invoice);
            $company = Company::find($invoice->company_id);
            $services = $services = $invoice->services;
            $openpay = Openpay::getInstance('myad9doynwfpb6iyoyci',
            'sk_a616a096b2b447f2b8f43281c1110143');
            $customer = array(

                'name' => $company->owner_user->full_name,
                'last_name' => $company->owner_user->last_name,
                'phone_number' => $company->phone,
                'email' => $company->email);

            $chargeData = array(
            'method' => 'bank_account',
            'amount' => (float)$invoice->m_total,
            'description' => 'Cargo con banco',
            'order_id' =>  $invoice->id,
            'customer' => $customer);

      $charge = $openpay->charges->create($chargeData);
      //dd($charge);
      $url = "https://sandbox-dashboard.openpay.mx/spei-pdf/myad9doynwfpb6iyoyci/".$charge->id;

      return Redirect::intended($url);
    } catch (OpenpayApiTransactionError $e) {
        $error_code = $e->getErrorCode();
    } catch (OpenpayApiRequestError $e) {
        $error_code = $e->getErrorCode();
    } catch (OpenpayApiConnectionError $e) {
        $error_code = $e->getErrorCode();
    } catch (OpenpayApiAuthError $e) {
        $error_code = $e->getErrorCode();
    } catch (OpenpayApiError $e) {
        $error_code = $e->getErrorCode();
    } catch (Exception $e) {
        $error_code = $e->getErrorCode();
    }
    $error_m = getErrorMessage($error_code);
    if("Error desconocido.1006" == $error_m)
    {
        $openpay = Openpay::getInstance('myad9doynwfpb6iyoyci',
        'sk_a616a096b2b447f2b8f43281c1110143');

        $searchParams = array(
            'order_id' => $invoice->id,
            'limit' => 1
        );
        $list = $openpay->charges->getList($searchParams);

       //dd($list);
        $url = "https://sandbox-dashboard.openpay.mx/spei-pdf/myad9doynwfpb6iyoyci/".$list[0]->id;
        return Redirect::intended($url);
    }

    return redirect(route('invoices.view', ['invoice'=>$invoice->id]))->with(compact('invoice','company','services','error_code','error_m'));
    }

    public function openPay_payment(Request $request, $invoice)
    {
   
            $openpay = Openpay::getInstance(env('OPENPAY_ID'), env('OPENPAY_KEY_SECRET'));
            Openpay::setProductionMode(env('OPENPAY_PRODUCTION'));

            $company = auth()->user()->company;
            //Plan contratado
            $package = $company->service;
            //Contar usurios no incluidos en el plan
            $num_users = $company->users()->count() - $package->users_included;

            $invoice = new Invoice;
            $invoice->name = 'Mensualidad -'.$package->service;
            $invoice->cost_package = $package->price;
            $invoice->cost_user = $package->user_price;
            $invoice->users = $num_users;
            $invoice->company_id = $company->id;

            if ($company->is_active) {
                $last_invoice = $company->invoices()->latest()->first();
                $invoice->due_date = $last_invoice->due_date->addMonth();
            } else {
                $invoice->due_date = Carbon::now()->addMonth();
            }

            $invoice->status = 2;
            $invoice->payday = Carbon::now();
            $invoice->save();
            try {

                $customer = array(
                    'name' => $company->owner_user->full_name,
                    'last_name' => $company->owner_user->last_name,
                    'phone_number' => $company->phone,
                    'email' => $company->email);

                $chargeData = array(
                    'method' => 'card',
                    'use_3d_secure'     =>  true,
                    'source_id' => $request->token_id,
                    'currency' => 'MXN',
                    "redirect_url"      =>  request()->getHttpHost()."/payment",
                    'amount' => strval($invoice->total),
                    'description' => 'Mensualidad -'.$package->service,
                    'order_id'          => $invoice->id,
                    'device_session_id' => $request->deviceIdHiddenFieldName,
                    'customer' => $customer
                );

                $charge = $openpay->charges->create($chargeData);
            } catch (\Exception $e) {
                $invoice->delete();
                return back()->with('error', "Error Inesperado, Favor de intentar de nuevo");
            }

            return redirect($charge->payment_method->url);
    }

    public function payment(Request $request)
    {
         //Revisar el estado del cargo
         if($request->id)
         {

             $charge_id=$request->id;

            $openpay = Openpay::getInstance(env('OPENPAY_ID'), env('OPENPAY_KEY_SECRET'));
            Openpay::setProductionMode(env('OPENPAY_PRODUCTION'));

             $charge = $openpay->charges->get($charge_id);

             if($charge)
             {
                if($charge->status=="failed") //Regresar mensaje de error
                {
                    $error_message="Error en la trasacción: ".Invoice::error_code_openpay($charge->serializableData["error_code"]);

                    return redirect(url("home/invoices"))->with('error', $error_message);
                }

                /** Pago completado **/
                $invoice = Invoice::find($charge->order_id);
                try {
                    Mail::to([$invoice->company->email])->send(new PaymentMail($invoice));
                } catch (\Exception $e) { }
                $invoice->status=1;
                $invoice->charge_id=$charge_id;
                $invoice->save();

                return redirect(url("home/invoices"))->with('success', 'El pago ha sido procesado, gracias por continuar con nosotros');
             }

             abort(404);
         }
         else
         {
            abort(404);
         }
    }


}


function  getErrorMessage($error_code){
    $message = '';
    switch ($error_code) {
        case 3001:
            $message = 'La tarjeta fue declinada.';
            break;
        case 3002:
            $message = "La tarjeta ha expirado.";
            break;
        case 3003:
            $message = "La tarjeta fue declinada.";
            break;
        case 3004:
            $message = "La tarjeta fue declinada.";
            break;
        case 3005:
            $message = "La tarjeta ha sido rechazada por el sistema antifraudes.";
            break;
        case 3006:
            $message = "La operación no esta permitida para este cliente o esta transacción.";
            break;
        case 3007:
            $message = "Deprecado. La tarjeta fue declinada.";
            break;
        case 3008:
            $message = "La tarjeta no es soportada en transacciones en línea.";
            break;
        case 3009:
            $message = "La tarjeta fue reportada como perdida.";
            break;
        case 3010:
            $message = "El banco ha restringido la tarjeta.";
            break;
        case 3011:
            $message = "El banco ha solicitado que la tarjeta sea retenida. Contacte al banco.";
            break;
        case 3012:
            $message = "Se requiere solicitar al banco autorización para realizar este pago.";
            break;
        default:
            $message = "Error desconocido.". $error_code;
        break;
    }
    return $message;
}
