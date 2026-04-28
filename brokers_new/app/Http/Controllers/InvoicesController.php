<?php

namespace App\Http\Controllers;

use App\Company;
use App\Invoice;
use App\Mail\PaymentMail;
use App\Service;
use App\Services\OpenPayService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Mail;
use OpenpayApiAuthError;
use OpenpayApiConnectionError;
use OpenpayApiError;
use OpenpayApiRequestError;
use OpenpayApiTransactionError;
use Redirect;

class InvoicesController extends Controller
{
    private $openPayService;

    public function __construct(OpenPayService $openPayService)
    {
        $this->openPayService = $openPayService;
    }

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
        // TENANT LOCK — previene acceso cross-tenant
        $invoice = Invoice::where('id', $invoice)
            ->where('company_id', auth()->user()->company_id)
            ->firstOrFail();
        $company = Company::find($invoice->company_id);
        $services = $services = $invoice->services;
        try {
            $owner      = $company->owner_user;
            $chargeData = [
                'method'      => 'store',
                'amount'      => (float) $invoice->m_total,
                'description' => 'Cargo a tienda',
                'order_id'    => $invoice->id,
                'customer'    => [
                    'external_id' => $owner ? $owner->id : $company->id,
                    'name'        => $owner ? $owner->full_name : $company->name,
                    'last_name'   => $owner ? ($owner->last_name ?? '') : '',
                    'phone_number'=> $company->phone,
                    'email'       => $company->email,
                ],
            ];

            $charge = $this->openPayService->createSandboxCharge($chargeData);
            $url    = 'https://sandbox-dashboard.openpay.mx/paynet-pdf/' . env('OPENPAY_SANDBOX_ID') . '/' . $charge->payment_method->reference;

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

        $error_m = $this->getErrorMessage($error_code);
        if ('Error desconocido.2003' == $error_m) {
            $charges = $this->openPayService->getSandboxChargeList([
                'order_id' => $invoice->id,
                'limit'    => 1,
            ]);
            $url = 'https://sandbox-dashboard.openpay.mx/paynet-pdf/' . env('OPENPAY_SANDBOX_ID') . '/' . $charges[0]->payment_method->reference;
            return Redirect::intended($url);
        }
    }

    public function openPay_spei(Request $request, $invoice)
    {
        try {
            // TENANT LOCK — previene acceso cross-tenant
            $invoice = Invoice::where('id', $invoice)
                ->where('company_id', auth()->user()->company_id)
                ->firstOrFail();
            $company = Company::find($invoice->company_id);
            $services = $services = $invoice->services;
            $owner      = $company->owner_user;
            $chargeData = [
                'method'      => 'bank_account',
                'amount'      => (float) $invoice->m_total,
                'description' => 'Cargo con banco',
                'order_id'    => $invoice->id,
                'customer'    => [
                    'name'        => $owner ? $owner->full_name : $company->name,
                    'last_name'   => $owner ? ($owner->last_name ?? '') : '',
                    'phone_number'=> $company->phone,
                    'email'       => $company->email,
                ],
            ];

            $charge = $this->openPayService->createSandboxCharge($chargeData);
            $url    = 'https://sandbox-dashboard.openpay.mx/spei-pdf/' . env('OPENPAY_SANDBOX_ID') . '/' . $charge->id;

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

        $error_m = $this->getErrorMessage($error_code);
        if ('Error desconocido.1006' == $error_m) {
            $list = $this->openPayService->getSandboxChargeList([
                'order_id' => $invoice->id,
                'limit'    => 1,
            ]);
            $url = 'https://sandbox-dashboard.openpay.mx/spei-pdf/' . env('OPENPAY_SANDBOX_ID') . '/' . $list[0]->id;
            return Redirect::intended($url);
        }

    return redirect(route('invoices.view', ['invoice'=>$invoice->id]))->with(compact('invoice','company','services','error_code','error_m'));
    }

    public function openPay_payment(Request $request, $invoice)
    {
        $company = auth()->user()->company;
        $package = $company->service;
        $num_users = $company->users()->count() - $package->users_included;

        $invoice = new Invoice;
        $invoice->name        = 'Mensualidad -' . $package->service;
        $invoice->cost_package = $package->price;
        $invoice->cost_user   = $package->user_price;
        $invoice->users       = $num_users;
        $invoice->company_id  = $company->id;

        if ($company->is_active) {
            $last_invoice    = $company->invoices()->latest()->first();
            $invoice->due_date = $last_invoice->due_date->addMonth();
        } else {
            $invoice->due_date = Carbon::now()->addMonth();
        }

        $invoice->status = 2;
        $invoice->payday = Carbon::now();
        $invoice->save();

        try {
            // Obtiene (o crea y persiste) el customer de OpenPay vinculado a este tenant
            $openPayCustomer = $this->openPayService->getOrCreateCustomer($company);

            $chargeData = [
                'method'           => 'card',
                'use_3d_secure'    => true,
                'source_id'        => $request->token_id,
                'currency'         => 'MXN',
                'redirect_url'     => request()->getHttpHost() . '/payment',
                'amount'           => strval($invoice->total),
                'description'      => 'Mensualidad -' . $package->service,
                'order_id'         => $invoice->id,
                'device_session_id'=> $request->deviceIdHiddenFieldName,
            ];

            // Cargo asociado al customer (no anónimo)
            $charge = $openPayCustomer->charges->create($chargeData);

        } catch (\Exception $e) {
            $invoice->delete();
            return back()->with('error', 'Error Inesperado, Favor de intentar de nuevo');
        }

        return redirect($charge->payment_method->url);
    }

    public function payment(Request $request)
    {
         //Revisar el estado del cargo
         if($request->id)
         {

             $charge_id = $request->id;
             $charge    = $this->openPayService->getCharge($charge_id);

             if($charge)
             {
                if($charge->status == "failed")
                {
                    $error_message = "Error en la trasacción: " . Invoice::error_code_openpay($charge->serializableData["error_code"]);
                    return redirect(url("home/invoices"))->with('error', $error_message);
                }

                // ANTI-FRAUDE: solo procesar si el cargo está completado Y la factura existe pendiente.
                // Previene que un charge_id externo marque una factura ajena o ya pagada.
                if ($charge->status !== 'completed') {
                    return redirect(url("home/invoices"))->with('error', 'El pago no pudo confirmarse. Intenta de nuevo o contacta soporte.');
                }

                $invoice = Invoice::where('id', $charge->order_id)
                    ->where('status', 2)   // solo facturas pendientes de pago
                    ->first();

                if (!$invoice) {
                    return redirect(url("home/invoices"))->with('error', 'Factura no encontrada o ya procesada.');
                }

                $invoice->status    = 1;
                $invoice->charge_id = $charge_id;
                $invoice->save();

                try {
                    Mail::to([$invoice->company->email])->send(new PaymentMail($invoice));
                } catch (\Exception $e) { }

                return redirect(url("home/invoices"))->with('success', 'El pago ha sido procesado, gracias por continuar con nosotros');
             }

             abort(404);
         }
         else
         {
            abort(404);
         }
    }


    private function getErrorMessage($error_code)
    {
        $message = '';
        switch ($error_code) {
            case 3001:
                $message = 'La tarjeta fue declinada.';
                break;
            case 3002:
                $message = 'La tarjeta ha expirado.';
                break;
            case 3003:
                $message = 'La tarjeta fue declinada.';
                break;
            case 3004:
                $message = 'La tarjeta fue declinada.';
                break;
            case 3005:
                $message = 'La tarjeta ha sido rechazada por el sistema antifraudes.';
                break;
            case 3006:
                $message = 'La operación no está permitida para este cliente o esta transacción.';
                break;
            case 3007:
                $message = 'Deprecado. La tarjeta fue declinada.';
                break;
            case 3008:
                $message = 'La tarjeta no es soportada en transacciones en línea.';
                break;
            case 3009:
                $message = 'La tarjeta fue reportada como perdida.';
                break;
            case 3010:
                $message = 'El banco ha restringido la tarjeta.';
                break;
            case 3011:
                $message = 'El banco ha solicitado que la tarjeta sea retenida. Contacte al banco.';
                break;
            case 3012:
                $message = 'Se requiere solicitar al banco autorización para realizar este pago.';
                break;
            default:
                $message = 'Error desconocido.' . $error_code;
                break;
        }
        return $message;
    }
}
