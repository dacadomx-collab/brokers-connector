<?php

namespace App\Services;

use App\Company;
use Openpay;

class OpenPayService
{
    private $openpay;

    public function __construct()
    {
        $this->openpay = Openpay::getInstance(
            env('OPENPAY_ID'),
            env('OPENPAY_KEY_SECRET')
        );
        Openpay::setProductionMode(env('OPENPAY_PRODUCTION', false));
    }

    /**
     * Recupera el customer de OpenPay vinculado a la compañía, o lo crea si no existe.
     * Guarda el openpay_customer_id en companies para usos futuros (tarjetas guardadas, recurrentes).
     */
    public function getOrCreateCustomer(Company $company)
    {
        if ($company->openpay_customer_id) {
            return $this->openpay->customers->get($company->openpay_customer_id);
        }

        $owner = $company->owner_user;

        $customerData = [
            'name'         => $owner ? $owner->full_name : $company->name,
            'last_name'    => $owner ? ($owner->last_name ?? '') : '',
            'email'        => $company->email,
            'phone_number' => $company->phone,
            'external_id'  => (string) $company->id,
        ];

        $customer = $this->openpay->customers->add($customerData);

        $company->openpay_customer_id = $customer->id;
        $company->save();

        return $customer;
    }

    /**
     * Crea una suscripción recurrente para el customer vinculado a la compañía.
     *
     * @param  Company $company   Tenant cuya suscripción se gestiona.
     * @param  string  $planId    ID del Plan tal como existe en el dashboard de OpenPay.
     * @param  string  $tokenId   Token de tarjeta generado por OpenPay.js en el frontend.
     * @param  string  $deviceId  Device session ID generado por openpay-data.js.
     * @return object             Objeto suscripción retornado por el SDK de OpenPay.
     */
    public function createSubscription(Company $company, string $planId, string $tokenId, string $deviceId)
    {
        $customer = $this->getOrCreateCustomer($company);

        $subscriptionData = [
            'plan_id'           => $planId,
            'token_id'          => $tokenId,
            'device_session_id' => $deviceId,
        ];

        return $customer->subscriptions->add($subscriptionData);
    }

    /**
     * Recupera un cargo de producción por su charge_id.
     * Usado por el callback de retorno 3D-Secure.
     */
    public function getCharge(string $chargeId)
    {
        return $this->openpay->charges->get($chargeId);
    }

    /**
     * Crea un cargo en el entorno sandbox (paynet / SPEI — métodos legacy).
     */
    public function createSandboxCharge(array $data)
    {
        return $this->sandboxOpenpay()->charges->create($data);
    }

    /**
     * Recupera lista de cargos del entorno sandbox (recuperación de errores en paynet/SPEI).
     */
    public function getSandboxChargeList(array $params)
    {
        return $this->sandboxOpenpay()->charges->getList($params);
    }

    private function sandboxOpenpay()
    {
        return Openpay::getInstance(
            env('OPENPAY_SANDBOX_ID'),
            env('OPENPAY_SANDBOX_KEY')
        );
    }
}
