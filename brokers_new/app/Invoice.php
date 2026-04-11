<?php

namespace App;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{

    protected $dates=[
        "due_date", "payday"
    ];

    public function services(){
        return $this->belongsToMany('App\Service','invoices_services')->withPivot('price');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function getMPackageAttribute()
    {
        /*
        Mutador: m_package
        Nombre del campo: .
        Descripción: Mutador para traer el paquete de una factura si no tiene paquete le asignamos el 3 por default.
        Creador: Betún.
        */
        $services = $this->services;
        $package = 3;
        foreach ($services as $service)
        {
            if($service->id != '4')
            {
                $package = $service->id;
            }
        }
       return $package;
    }
    public function getMInvoiceDateAttribute()
    {
        /*
            Mutador: m_invoice_date
            Nombre del campo: invoice_date
            Descripción: le quita la hora a la fecha
            Creador: Betún.
        */
        $date = Carbon::parse($this->invoice_date)->format('d-m-Y');
        return $date;

    }
    public function getMDueDateAttribute()
    {
        /*
            Mutador: m_due_date
            Nombre del campo: invoice_date
            Descripción: le quita la hora a la fecha
            Creador: Betún.
        */
        $date = Carbon::parse($this->due_date)->format('d-m-Y');
        return $date;
    }

    public function getSubtotalAttribute(Type $var = null)
    {
        return  ($this->cost_package + ($this->cost_user * $this->users));
    }

    public function getTotalAttribute()
    {
        $total = ($this->cost_package + ($this->cost_user * $this->users)) * 1.16;
        $total = round($total,2);
        return $total;
    }

    public function getMIvaAttribute() //FUNCIONES CULERAS DEL BETO CAPITULO 3 VERSICULO 2 ---- REMOVER!!!
    {
       $iva = ($this->subtotal * .16);
       return round($iva, 2);
    }

    static function error_code_openpay($error_code)
    {

        switch ($error_code) {
            case 3001:
                $message = 'La tarjeta fue declinada.';
                break;
            case 3002:
                $message = "La tarjeta ha expirado.";
                break;
            case 3003:
                $message = "La tarjeta no tiene fondos suficientes.";
                break;
            case 3004:
                $message = "La tarjeta ha sido identificada como una tarjeta robada.";
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

            case 1000:
                $message="Ocurrió un error interno en el servidor de Openpay";
            break;

            case 1001:
                $message="El formato de la petición no es JSON, los campos no tienen el formato correcto, o la petición no tiene campos que son requeridos.";
            break;

            case 1002:
                $message="La llamada no esta autenticada o la autenticación es incorrecta.";
            break;

            case 1003:
                $message="La operación no se pudo completar por que el valor de uno o más de los parámetros no es correcto.";
            break;

            case 1004:
                $message="Un servicio necesario para el procesamiento de la transacción no se encuentra disponible.";
            break;

            case 1005:
                $message="Uno de los recursos requeridos no existe.";
            break;

            case 1006:
                $message="Ya existe una transacción con el mismo ID de orden.";
            break;

            case 1007:
                $message="La transferencia de fondos entre una cuenta de banco o tarjeta y la cuenta de Openpay no fue aceptada.";
            break;

            case 1008:
                $message="Una de las cuentas requeridas en la petición se encuentra desactivada.";
            break;

            case 1009:
                $message="El cuerpo de la petición es demasiado grande.";
            break;

            case 1010:
                $message="Se esta utilizando la llave pública para hacer una llamada que requiere la llave privada, o bien, se esta usando la llave privada desde JavaScript.";
            break;

            case 1011:
                $message="Se solicita un recurso que esta marcado como eliminado.";
            break;

            case 1012:
                $message="El monto transacción esta fuera de los limites permitidos.";
            break;

            case 1013:
                $message="La operación no esta permitida para el recurso.";
            break;

            case 1014:
                $message="La cuenta esta inactiva.";
            break;

            case 1015:
                $message="No se ha obtenido respuesta de la solicitud realizada al servicio.";
            break;

            case 1016:
                $message="El mail del comercio ya ha sido procesada.";
            break;

            case 1017:
                $message="El gateway no se encuentra disponible en ese momento.";
            break;

            case 1018:
                $message="El número de intentos de cargo es mayor al permitido.";
            break;

            case 1020:
                $message="El número de dígitos decimales es inválido para esta moneda";
            break;

            case 2001:
                $message="La cuenta de banco con esta CLABE ya se encuentra registrada en el cliente.";
            break;

            case 2002:
                $message="La tarjeta con este número ya se encuentra registrada en el cliente.";
            break;

            case 2003:
                $message="El cliente con este identificador externo (External ID) ya existe.";
            break;

            case 2004:
                $message="El dígito verificador del número de tarjeta es inválido de acuerdo al algoritmo Luhn.";
            break;

            case 2005:
                $message="La fecha de expiración de la tarjeta es anterior a la fecha actual.";
            break;

            case 2006:
                $message="El código de seguridad de la tarjeta (CVV2) no fue proporcionado.";
            break;

            case 2007:
                $message="El número de tarjeta es de prueba, solamente puede usarse en Sandbox.";
            break;

            case 2008:
                $message="La tarjeta no es válida para puntos Santander.";
            break;

            case 2009:
                $message="El código de seguridad de la tarjeta (CVV2) es inválido.";
            break;

            case 2010:
                $message="Autenticación 3D Secure fallida.";
            break;

            case 2011:
                $message="Tipo de tarjeta no soportada";
            break;

            case 4001:
                $message="La cuenta de Openpay no tiene fondos suficientes.";
            break;

            case 4002:
                $message="La operación no puede ser completada hasta que sean pagadas las comisiones pendientes.";
            break;

            case 5001:
                $message="La orden con este identificador externo ya existe.";
            break;

            default:
                $message = "Error desconocido.". $error_code;
            break;
        }


        return $message;
    }

}
