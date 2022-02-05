<?php
namespace App\Repositories;
use App\Mail\AccountConfirmationMail;
use App\Mail\AccountConfirmationMail_password;
use App\Mail\AccountConfirmationMail_token;
use App\Mail\PasswordResetMail;
use App\Mail\PasswordResetMail_token;
use App\Mail\SharePropertyMail;
use App\Mail\SendMessageToProviderMail;
use App\Mail\PublicationMail;
use Mail;


class InventrooMailUtils 
{
    

    public function __construct()
    {
        
    }

    public function send_account_confirmation($userObj)
    {
      
        $data['name'] = $userObj->name;
        $email = $userObj->email;
        $reference = $userObj->verification_ref;
        //$email = 'emmanuel.obute@asoplc.com';
        //$data['link'] = route('account.confirm', ['reference' => $userObj->verificationRef]);
        $data['link'] = cc('frontend_base_url')."account/confirm?reference=$reference";
    
        if (Mail::to($email)->send(new AccountConfirmationMail($data))) {
            return true;
        }
        return false;
       
    }

    public function send_account_confirmation_w_password($userObj, $password)
    {
      
        $data['name'] = $userObj->name;
        $data['password'] = $password;
        $email = $userObj->email;
        $reference = $userObj->verification_ref;
        //$email = 'emmanuel.obute@asoplc.com';
        //$data['link'] = route('account.confirm', ['reference' => $userObj->verificationRef]);
        $data['link'] = cc('frontend_base_url')."account/confirm?reference=$reference";
    
        if (Mail::to($email)->send(new AccountConfirmationMail_password($data))) {
            return true;
        }
        return false;
       
    }

    public function send_account_confirmation_token($userObj)
    {
      
        $data['name'] = $userObj->name;
        $email = $userObj->email;
        $reference = $userObj->verificationRef;
        
        $data['reference'] = $reference;
    
        if (Mail::to($email)->send(new AccountConfirmationMail_token($data))) {
            return true;
        }
        return false;
       
    }

    
    public function send_password_reset($userObj, $token)
    {
      
        $data['name'] = $userObj->name;
        $email = $userObj->email;
        //$email = 'emmanuel.obute@asoplc.com';
        //$data['link'] = route('account.password_reset', ['token' => $token, 'email' => $email]);
        $data['link'] = "https://100bricks.ng/reset_password/token?token=$token&email=$email";
    
        if (Mail::to($email)->send(new PasswordResetMail($data))) {
            return true;
        }
        return false;
       
    }

    public function send_password_reset_token($userObj, $token)
    {
      
        $data['name'] = $userObj->name;
        $email = $userObj->email;
        $data['token'] = $token;
    
        if (Mail::to($email)->send(new PasswordResetMail_token($data))) {
            return true;
        }
        return false;
       
    }


    public function shared_property_send($user, $receiver_email, $propertyLink, $imageLink)
    {
      
        $data['sender'] = $user->name;
        $data['sender_email'] = $user->email;
        $data['propertyLink'] = $propertyLink;
        $data['imageLink'] = $imageLink;
        
        if (Mail::to($receiver_email)->send(new SharePropertyMail($data))) {
            return true;
        }
        return false;
       
    }

    public function send_message_to_provider($provider, $sender_email, $sender_name, $message)
    {
        $receiver_email = $provider->email;
        $data['sender'] = $sender_name;
        $data['sender_email'] = $sender_email;
        $data['msg'] = $message;
       
        if (Mail::to($receiver_email)->send(new SendMessageToProviderMail($data))) {
            return true;
        }
        return false;
    }

    public function send_reset_password($title, $link)
    {
      
        $data['name'] = 'Emmanuel Obute';
        $email = 'emmanuel.obute@asoplc.com';
        $data['title'] = $title;
        $data['link'] = $link;

        if (Mail::to($email)->send(new PublicationMail($data))) {
            return true;
        }
        return false;
       
    }


    public function send_notification($title, $link)
    {
      
        $data['name'] = 'Emmanuel Obute';
        $email = 'emmanuel.obute@asoplc.com';
        $data['title'] = $title;
        $data['link'] = $link;

        if (Mail::to($email)->send(new PublicationMail($data))) {
            return true;
        }
        return false;
       
    }

    public function send_publication($user_obj, $roles)
    {
      
        //$user = HCIS_employee::where('EmployeeId', $user_obj->employee_id)->first();
        if (!is_null($user_obj)) {
       
            $data['name'] = $user_obj->name;
            $email = $user_obj->email;
            $data['roles'] = Role::whereIn('id', $roles)->orderBy('display_name', 'asc')->get();
            
            Mail::to($email)
            ->send(new RoleAssignmentMail($data))
            ->cc(['itrm@asoplc.com'])
            ->replyTo('itrm@asoplc.com');
            return true;
           
        }
        return false;
       
    }

    public function audit_log($log_json, $initiator, $subject)
    {
        return ;
    }
}