<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Service;
class PaymentMail extends Mailable
{
    use Queueable, SerializesModels;
    protected $invoice;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($invoice)
    {
        $this->invoice=$invoice;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $name_services = Service::all();
        
        return $this->subject('Pago Completado')
        ->view('invoices.invoice')
        ->with('company', $this->invoice->company)
        ->with('invoice', $this->invoice)
        ->with('services', $this->invoice->services)
        ->with('name_services', $name_services);

    }
}
