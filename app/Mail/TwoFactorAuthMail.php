<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TwoFactorAuthMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public $token;
    public $email;


    public function __construct($token, $email)
    {
        $this->token = $token;
        $this->email = $email;
    }
    public function build()
    {
        // return $this->view('admin.mails.2fa')->subject('Your Authentication Token');
        return $this->from(env('MAIL_FROM_ADDRESS', 'support@100bricks.ng'))
            ->to($this->email)
            ->subject("Your Authentication Token")
            ->view('mails.admin.2fa', [
                "token" =>$this->token,
                "email" => $this->email, 
            ]);
    }
}
