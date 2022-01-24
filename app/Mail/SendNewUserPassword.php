<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\User;
use App\Models\Role;


class SendNewUserPassword extends Mailable
{
    use Queueable, SerializesModels;

    protected $user;
    protected $password;
    protected $role;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $user, string $password, Role $role)
    {
        $this->user = $user;
        $this->role = $role;
        $this->password = $password;
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
            ->subject("Welcome to 100Bricks: Account Created")
            ->view('mails.send_user_password', [
                "password" =>$this->password,
                "user" => $this->user,
                "role" => $this->role,
            ]);
    }
}
