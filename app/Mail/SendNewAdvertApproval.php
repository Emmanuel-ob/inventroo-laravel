<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\User;
use App\Models\Advertisement;


class SendNewAdvertApproval extends Mailable
{
    use Queueable, SerializesModels;

    protected $user;
    protected $advert;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $user, Advertisement $advert)
    {
        $this->user = $user;
        $this->advert = $advert;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from(env('MAIL_FROM_ADDRESS', 'support@100bricks.ng'))
            ->to($this->user->email)
            ->subject("100Bricks - Advert Approval")
            ->view('mails.admin.advert.advert_approval', [
                "user" => $this->user,
                "advert" => $this->advert,
            ]);
    }
}
