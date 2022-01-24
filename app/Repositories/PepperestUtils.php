<?php
namespace App\Repositories;
use App\Mail\TransactionMail;
use App\Mail\OTPMail;
use App\Mail\SignupWelcomeMail;
use App\Mail\PasswordResetMail;
use App\Mail\PasswordResetMail_token;
use App\Mail\NewOrderMail_Seller;
use App\Mail\NewOrderMail_Buyer;
use App\Mail\OrderStatusChangeMail;
use App\Mail\OrderStatusChangeMail_Seller;
use App\Mail\OrderDeliveryChangeMail;
use App\Mail\OrderDeliveryChangeConfirmMail;
use App\Mail\OrderDisputeMail;
use App\Mail\OrderDisputeMail_Seller;
use App\Mail\OrderSalesContractMail;
use App\Mail\OrderStatusChangeMailWithOTP;
use App\Mail\OrderStatusChangeMailWithOTP_Seller;
use App\Mail\OrderDisputeResolutionMail;
use App\Mail\OrderDisputeResolutionMail_Seller;
use App\Mail\OrderDisputeRejectionMail;
use App\Mail\OrderDisputeRejectionMail_Seller;
use App\Mail\OrderModificationMail;
use App\Mail\OrderModificationMail_Seller;
use App\Mail\OrderEndDateReminderMail;
use App\Mail\OrderModifiedReminderMail;
use App\Mail\AccountConfirmationMail;
use PDF;
use Mail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log as Logger;

class PepperestUtils 
{
    

    public function __construct()
    {
        
    }

    public function send_test()
    {
        try{
            
            //[$cur, $ActualAmount, $description, $TxnRef]
            $data['name'] = 'Emmanuel Obute';
            $email = 'emmanuel6.obute@gmail.com';
            $data['ActualAmount'] = 50000.45;
            $data['description'] = 'Payment for enso water pack';
            $data['TxnRef'] = '747748383';
            $data['cur'] = 'N';
            Mail::to($email)
            ->send(new TransactionMail($data));
                    
            return true;
        }

        //catch exception
        catch(Exception $e) {
          Logger::info('Email Error', [$e->getMessage()]); 
          return false;
        }
       
    }

    public function send_welcome_email($user)
    {
        try{
            
            //[$cur, $ActualAmount, $description, $TxnRef]
            $data['name'] = $user->name;
            $data['email'] = $user->email;
            $reference = $user->verification_ref;
            $data['link'] = cc('frontend_base_url')."account/confirm/token?reference=$reference";
            $email = $user->email;
            if (!is_null($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                 Mail::to($email)
                 ->send(new AccountConfirmationMail($data));
                
                return true;
            }
            
                    
            return false;
        }

        //catch exception
        catch(Exception $e) {
          Logger::info('Email Error', [$e->getMessage()]); 
          return false;
        }
       
    }


    public function send_welcome_email_with_password($user, $password)
    {
        try{
            
            //[$cur, $ActualAmount, $description, $TxnRef]
            $data['name'] = $user->name;
            $data['email'] = $user->email;
            $data['link'] = cc('frontend_base_url');
            $data['password'] = $password;
            $email = $user->email;
            if (!is_null($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                // Mail::to($email)
                // ->send(new SignupWelcomeMail($data));
                $pdf = PDF::loadView('mails.termsAndConditions', $data);
               
                $buyer_mail = Mail::send('mails.signupWelcomeWithPasword', $data, function($message) use($data, $pdf) {
                    $message->to($data['email'])
                            ->from(cc('mail_from'))
                            ->subject("Welcome to First Online Escrow")
                            ->attachData($pdf->output(), "TermsAndConditions.pdf");
                });

                //termsAndConditions
                return true;
            }
            
                    
            return false;
        }

        //catch exception
        catch(Exception $e) {
          Logger::info('Email Error', [$e->getMessage()]); 
          return false;
        }
       
    }


    public function send_user_otp($user)
    {
        try{
            
            //[$cur, $ActualAmount, $description, $TxnRef]
            $data['name'] = $user->name;
            $email = $user->email;
            $data['otp'] = $user->two_factor_code;
            $otp = $user->two_factor_code;
            if (!is_null($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                Mail::to($email)
                ->send(new OTPMail($data));
            }

            $to = $user->phoneNo;
            $msg = "You just made a signup attempt on First Escrow, here is your OTP: $otp"; 
            $this->sendSMS($to, $msg);
                    
            return true;
        }

        //catch exception
        catch(Exception $e) {
          Logger::info('Email Error', [$e->getMessage()]); 
          return false;
        }
       
    }

    
    public function send_trans_otp($phoneNo, $otp)
    {
        try{
            if (is_null($phoneNo)) {
                return false;
            }
            
            $msg = "You are about to make a transfer from your FBN account on First Escrow, here is your OTP: $otp"; 
            $phoneNo = formatPhoneNo($phoneNo);
            
            $this->sendSMS($phoneNo, $msg);
                    
            return true;
        }

        //catch exception
        catch(Exception $e) {
          Logger::info('Message Error', [$e->getMessage()]); 
          return false;
        }
       
    }

    
    public function send_user_otp_to_email($user)
    {
        try{
            
            //[$cur, $ActualAmount, $description, $TxnRef]
            $data['name'] = $user->name;
            $email = $user->email;
            $data['otp'] = $user->two_factor_code;
            $otp = $user->two_factor_code;
            if (!is_null($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                Mail::to($email)
                ->send(new OTPMail($data));
                return true;
            }
            
                    
            return false;
        }

        //catch exception
        catch(Exception $e) {
          Logger::info('Email Error', [$e->getMessage()]); 
          return false;
        }
       
    }


    public function send_password_reset($userObj, $token)
    {
      
        $data['name'] = $userObj->name;
        $email = $userObj->email;
        
        $data['link'] = cc('frontend_base_url') . "reset_password/token?token=$token&email=$email";
        
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
           if (Mail::to($email)->send(new PasswordResetMail($data))) {
                return true;
            }
        }
        
        return false;
       
    }

    public function send_password_reset_token($userObj, $token)
    {
      
        $data['name'] = $userObj->name;
        $email = $userObj->email;
        $data['token'] = $token;

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
           if (Mail::to($email)->send(new PasswordResetMail_token($data))) {
                return true;
            }
        }
        
        return false;
       
    }

    //sellerDetails
    //buyerDetails

    public function send_create_order_email($order, $buyer){
        
        $data['description'] = $order->description;
        $data['cost'] = number_format($order->cost,2);
        $data['currency'] = $order->currency;
        $data['orderRef'] = $order->orderRef;
        $data['seller_email'] = $order->seller_email;
        $data['buyer_email'] = $buyer->email;
        $data['order_link'] = cc('frontend_base_url') .'app/orders?id='. $order->id;
        $data['seller_name'] = $order->sellerDetails->name;
        $data['buyer_name'] = $order->buyerDetails->name;
        
        $buyer_mail = false;
        $seller_mail = false;
        if (filter_var($data['seller_email'], FILTER_VALIDATE_EMAIL)) {
          $seller_mail = Mail::to($data['seller_email'])->send(new NewOrderMail_Seller($data));
        }

        if (filter_var($data['buyer_email'], FILTER_VALIDATE_EMAIL)) {
          $buyer_mail = Mail::to($data['buyer_email'])->send(new NewOrderMail_Buyer($data));
        }
        

        if ($seller_mail && $buyer_mail) {
            return true;
        }


        return false;

    }

    public function send_order_modification_email($order, $order_mod, $initiator){
        $data['description'] = $order->description;
        $data['cost'] = number_format($order->cost,2);
        $data['currency'] = $order->currency;
        $data['orderRef'] = $order->orderRef;
        $data['initiated_name'] = $initiator->name;
        $data['initiated_email'] = $initiator->email;
        $data['start_date'] = Carbon::parse($order->start_date)->format('Y-m-d');
        $data['end_date'] = Carbon::parse($order->end_date)->format('Y-m-d');
        $data['seller_email'] = $order->seller_email;
        $data['buyer_email'] = $order->buyerDetails->email;
        $data['order_link'] = cc('frontend_base_url') .'app/orders?id='. $order->id;
        $data['modification'] = $order_mod;
        $data['seller_name'] = $order->sellerDetails->name;
        $data['buyer_name'] = $order->buyerDetails->name;
        
        $send_mail = false;
        //$send_mail = false;
        if (filter_var($data['seller_email'], FILTER_VALIDATE_EMAIL)) {
          $send_mail_seller = Mail::to($data['seller_email'])->send(new OrderModificationMail_Seller($data));
        }

        if (filter_var($data['buyer_email'], FILTER_VALIDATE_EMAIL)) {
          $send_mail = Mail::to($data['buyer_email'])->send(new OrderModificationMail($data));
        }
        

        if ($send_mail) {
            return true;
        }


        return false; 
    }


    public function send_order_modification_reminder_email($order, $order_mod){
        $data['description'] = $order->description;
        $data['cost'] = number_format($order->cost,2);
        $data['currency'] = $order->currency;
        $data['orderRef'] = $order->orderRef;
        $data['start_date'] = $order->start_date;
        $data['end_date'] = $order->end_date;
        $data['seller_email'] = $order->seller_email;
        $data['buyer_email'] = $order->buyerDetails->email;
        $data['order_link'] = cc('frontend_base_url') .'app/orders?id='. $order->id;
        $data['modification'] = $order_mod;
        $data['buyer_name'] = $order->buyerDetails->name;
        
        $send_mail = false;
        //$seller_mail = false;
        if (filter_var($data['buyer_email'], FILTER_VALIDATE_EMAIL)) {
          $send_mail = Mail::to([$data['buyer_email']])->send(new OrderModifiedReminderMail($data));
        }

        if ($send_mail) {
            return true;
        }


        return false; 
    }

    public function send_order_status_change_email($order, $initiator){
        
        $data['description'] = $order->description;
        $data['cost'] = number_format($order->cost,2);
        $data['currency'] = $order->currency;
        $data['orderRef'] = $order->orderRef;
        $data['seller_email'] = $order->seller_email;
        $data['buyer_email'] = $order->buyer->email;
        $tranxStatusArray = cc('transaction.status');
        $data['tranxStatus'] = isset($tranxStatusArray[$order->status]) ? $tranxStatusArray[$order->status] : 'Undefined';
        $data['initiator_name'] = $initiator->name;
        $data['initiator_email'] = $initiator->email;
        $data['order_link'] = cc('frontend_base_url') .'app/orders?id='. $order->id;
        $data['seller_name'] = $order->sellerDetails->name;
        $data['buyer_name'] = $order->buyerDetails->name;
        
        $buyer_mail = false;
        $seller_mail = false;
        if (filter_var($data['seller_email'], FILTER_VALIDATE_EMAIL)) {
          $send_mail_seller = Mail::to($data['seller_email'])->send(new OrderStatusChangeMail_Seller($data));
        }

        if (filter_var($data['buyer_email'], FILTER_VALIDATE_EMAIL)) {
          $buyer_mail = Mail::to($data['buyer_email'])->send(new OrderStatusChangeMail($data));
        }

        if ($seller_mail && $buyer_mail) {
            return true;
        }


        return false;

    }

    public function send_order_status_change_email_with_otp($order, $initiator){
        
        $data['description'] = $order->description;
        $data['cost'] = number_format($order->cost,2);
        $data['currency'] = $order->currency;
        $data['orderRef'] = $order->orderRef;
        $data['seller_email'] = $order->seller_email;
        $data['buyer_email'] = $order->buyer->email;
        $data['orderOTP'] = $order->delivery_confirmation_pin;
        $tranxStatusArray = cc('transaction.status');
        $data['tranxStatus'] = isset($tranxStatusArray[$order->status]) ? $tranxStatusArray[$order->status] : 'Undefined';
        $data['initiator_name'] = $initiator->name;
        $data['initiator_email'] = $initiator->email;
        $data['order_link'] = cc('frontend_base_url') .'app/orders?id='. $order->id;
        $data['seller_name'] = $order->sellerDetails->name;
        $data['buyer_name'] = $order->buyerDetails->name;
        
        $buyer_mail = false;
        $seller_mail = false;
        if (filter_var($data['seller_email'], FILTER_VALIDATE_EMAIL)) {
          $seller_mail = Mail::to($data['seller_email'])->send(new OrderStatusChangeMailWithOTP_Seller($data));
        }

        if (filter_var($data['buyer_email'], FILTER_VALIDATE_EMAIL)) {
          $buyer_mail = Mail::to($data['buyer_email'])->send(new OrderStatusChangeMailWithOTP($data));
        }

        if ($seller_mail && $buyer_mail) {
            return true;
        }


        return false;

    }

    public function send_placed_order_status_change_email($order, $initiator){
        
        $data['description'] = $order->orderRef;
        $data['cost'] = number_format($order->total,2);
        $data['currency'] = $order->currency;
        $data['orderRef'] = $order->orderRef;
        $data['seller_email'] = !is_null($order->seller) ? $order->seller->email : null;
        $data['buyer_email'] = !is_null($order->buyer) ? $order->buyer->email : null;
        $tranxStatusArray = cc('transaction.status');
        $data['tranxStatus'] = isset($tranxStatusArray[$order->status]) ? $tranxStatusArray[$order->status] : 'Undefined';
        $data['initiator_name'] = $initiator->name;
        $data['initiator_email'] = $initiator->email;
        $data['order_link'] = cc('frontend_base_url') .'app/placed_orders?id='. $order->id;
        $data['seller_name'] = !is_null($order->seller) ? $order->seller->name : null;
        $data['buyer_name'] = !is_null($order->buyer) ? $order->buyer->name : null;
        
        $buyer_mail = false;
        $seller_mail = false;
        if (filter_var($data['seller_email'], FILTER_VALIDATE_EMAIL)) {
          $send_mail_seller = Mail::to($data['seller_email'])->send(new OrderStatusChangeMail_Seller($data));
        }

        if (filter_var($data['buyer_email'], FILTER_VALIDATE_EMAIL)) {
          $buyer_mail = Mail::to($data['buyer_email'])->send(new OrderStatusChangeMail($data));
        }

        if ($seller_mail && $buyer_mail) {
            return true;
        }


        return false;

    }

    public function send_placed_order_status_change_email_with_otp($order, $initiator){
        
        $data['description'] = $order->orderRef;
        $data['cost'] = number_format($order->total,2);
        $data['currency'] = $order->currency;
        $data['orderRef'] = $order->orderRef;
        $data['seller_email'] = !is_null($order->seller) ? $order->seller->email : null;
        $data['buyer_email'] = !is_null($order->buyer) ? $order->buyer->email : null;
        $data['orderOTP'] = $order->delivery_confirmation_pin;
        $tranxStatusArray = cc('transaction.status');
        $data['tranxStatus'] = isset($tranxStatusArray[$order->status]) ? $tranxStatusArray[$order->status] : 'Undefined';
        $data['initiator_name'] = $initiator->name;
        $data['initiator_email'] = $initiator->email;
        $data['order_link'] = cc('frontend_base_url') .'app/placed_orders?id='. $order->id;
        $data['seller_name'] = !is_null($order->seller) ? $order->seller->name : null;
        $data['buyer_name'] = !is_null($order->buyer) ? $order->buyer->name : null;
        
        $buyer_mail = false;
        $seller_mail = false;
        if (filter_var($data['seller_email'], FILTER_VALIDATE_EMAIL)) {
          $seller_mail = Mail::to($data['seller_email'])->send(new OrderStatusChangeMailWithOTP_Seller($data));
        }

        if (filter_var($data['buyer_email'], FILTER_VALIDATE_EMAIL)) {
          $buyer_mail = Mail::to($data['buyer_email'])->send(new OrderStatusChangeMailWithOTP($data));
        }

        if ($seller_mail && $buyer_mail) {
            return true;
        }


        return false;

    }

    public function send_order_dispute_email($order, $dispute, $initiator){
        $data['description'] = $order->description;
        $data['cost'] = number_format($order->cost,2);
        $data['currency'] = $order->currency;
        $data['orderRef'] = $order->orderRef;
        $data['seller_email'] = $order->seller_email;
        $data['buyer_email'] = $order->buyer->email;
        $tranxStatusArray = cc('transaction.status');
        $data['tranxStatus'] = isset($tranxStatusArray[$order->status]) ? $tranxStatusArray[$order->status] : 'Undefined';
        $data['initiator_name'] = $initiator->name;
        $data['initiator_email'] = $initiator->email;
        $data['dispute_description'] = $dispute->dispute_description;
        $data['order_link'] = cc('frontend_base_url') .'app/orders?id='. $order->id;
        $data['seller_name'] = $order->sellerDetails->name;
        $data['buyer_name'] = $order->buyerDetails->name;
        
        $buyer_mail = false;
        $seller_mail = false;
        if (filter_var($data['seller_email'], FILTER_VALIDATE_EMAIL)) {
          $seller_mail = Mail::to($data['seller_email'])->send(new OrderDisputeMail_Seller($data));
        }

        if (filter_var($data['buyer_email'], FILTER_VALIDATE_EMAIL)) {
          $buyer_mail = Mail::to($data['buyer_email'])->send(new OrderDisputeMail($data));
        }

        if ($seller_mail) {
            return true;
        }


        return false; 
    }


    public function send_placed_order_dispute_email($order, $dispute, $initiator){
        $data['description'] = $order->orderRef;
        $data['cost'] = number_format($order->total,2);
        $data['currency'] = $order->currency;
        $data['orderRef'] = $order->orderRef;
        $data['seller_email'] = !is_null($order->seller) ? $order->seller->email : null;
        $data['buyer_email'] = !is_null($order->buyer) ? $order->buyer->email : null;
        $tranxStatusArray = cc('transaction.status');
        $data['tranxStatus'] = isset($tranxStatusArray[$order->status]) ? $tranxStatusArray[$order->status] : 'Undefined';
        $data['initiator_name'] = $initiator->name;
        $data['initiator_email'] = $initiator->email;
        $data['dispute_description'] = $dispute->dispute_description;
        $data['order_link'] = cc('frontend_base_url') .'app/placed_orders?id='. $order->id;
        $data['seller_name'] = !is_null($order->seller) ? $order->seller->name : null;
        $data['buyer_name'] = !is_null($order->buyer) ? $order->buyer->name : null;
        
        $buyer_mail = false;
        $seller_mail = false;
        if (filter_var($data['seller_email'], FILTER_VALIDATE_EMAIL)) {
          $seller_mail = Mail::to($data['seller_email'])->send(new OrderDisputeMail_Seller($data));
        }

        if (filter_var($data['buyer_email'], FILTER_VALIDATE_EMAIL)) {
          $buyer_mail = Mail::to($data['buyer_email'])->send(new OrderDisputeMail($data));
        }

        if ($seller_mail) {
            return true;
        }


        return false; 
    }

  
    public function send_order_dispute_resolution_email($order, $dispute, $initiator){
        $data['description'] = $order->description;
        $data['cost'] = number_format($order->cost,2);
        $data['currency'] = $order->currency;
        $data['orderRef'] = $order->orderRef;
        $data['seller_email'] = $order->seller_email;
        $data['buyer_email'] = $order->buyer->email;
        $tranxStatusArray = cc('transaction.status');
        $data['tranxStatus'] = isset($tranxStatusArray[$order->status]) ? $tranxStatusArray[$order->status] : 'Undefined';
        $data['initiator_name'] = $initiator->name;
        $data['initiator_email'] = $initiator->email;
        $data['dispute_description'] = $dispute->dispute_description;
        $data['resolution'] = $dispute->final_resolution;
        $data['order_link'] = cc('frontend_base_url') .'app/orders?id='. $order->id;
        $data['seller_name'] = $order->sellerDetails->name;
        $data['buyer_name'] = $order->buyerDetails->name;
        
        $buyer_mail = false;
        $seller_mail = false;
        if (filter_var($data['seller_email'], FILTER_VALIDATE_EMAIL)) {
          $seller_mail = Mail::to($data['seller_email'])->send(new OrderDisputeResolutionMail_Seller($data));
        }

        if (filter_var($data['buyer_email'], FILTER_VALIDATE_EMAIL)) {
          $buyer_mail = Mail::to($data['buyer_email'])->send(new OrderDisputeResolutionMail($data));
        }

        if ($seller_mail) {
            return true;
        }


        return false; 
    }


    public function send_placed_order_dispute_resolution_email($order, $dispute, $initiator){
        $data['description'] = $order->orderRef;
        $data['cost'] = number_format($order->total,2);
        $data['currency'] = $order->currency;
        $data['orderRef'] = $order->orderRef;
        $data['seller_email'] = !is_null($order->seller) ? $order->seller->email : null;
        $data['buyer_email'] = !is_null($order->buyer) ? $order->buyer->email : null;
        $tranxStatusArray = cc('transaction.status');
        $data['tranxStatus'] = isset($tranxStatusArray[$order->status]) ? $tranxStatusArray[$order->status] : 'Undefined';
        $data['initiator_name'] = $initiator->name;
        $data['initiator_email'] = $initiator->email;
        $data['dispute_description'] = $dispute->dispute_description;
        $data['resolution'] = $dispute->final_resolution;
        $data['order_link'] = cc('frontend_base_url') .'app/placed_orders?id='. $order->id;
        $data['seller_name'] = !is_null($order->seller) ? $order->seller->name : null;
        $data['buyer_name'] = !is_null($order->buyer) ? $order->buyer->name : null;
        
        $buyer_mail = false;
        $seller_mail = false;
        if (filter_var($data['seller_email'], FILTER_VALIDATE_EMAIL)) {
          $seller_mail = Mail::to($data['seller_email'])->send(new OrderDisputeResolutionMail_Seller($data));
        }

        if (filter_var($data['buyer_email'], FILTER_VALIDATE_EMAIL)) {
          $buyer_mail = Mail::to($data['buyer_email'])->send(new OrderDisputeResolutionMail($data));
        }

        if ($seller_mail) {
            return true;
        }


        return false; 
    }


    public function send_order_dispute_rejection_email($order, $dispute, $initiator){
        $data['description'] = $order->description;
        $data['cost'] = number_format($order->cost,2);
        $data['currency'] = $order->currency;
        $data['orderRef'] = $order->orderRef;
        $data['seller_email'] = $order->seller_email;
        $data['buyer_email'] = $order->buyer->email;
        $tranxStatusArray = cc('transaction.status');
        $data['tranxStatus'] = isset($tranxStatusArray[$order->status]) ? $tranxStatusArray[$order->status] : 'Undefined';
        $data['initiator_name'] = $initiator->name;
        $data['initiator_email'] = $initiator->email;
        $data['dispute_description'] = $dispute->dispute_description;
        $data['resolution'] = $dispute->final_resolution;
        $data['order_link'] = cc('frontend_base_url') .'app/orders?id='. $order->id;
        $data['seller_name'] = $order->sellerDetails->name;
        $data['buyer_name'] = $order->buyerDetails->name;
        
        $buyer_mail = false;
        $seller_mail = false;
        if (filter_var($data['seller_email'], FILTER_VALIDATE_EMAIL)) {
          $seller_mail = Mail::to($data['seller_email'])->send(new OrderDisputeRejectionMail_Seller($data));
        }

        if (filter_var($data['buyer_email'], FILTER_VALIDATE_EMAIL)) {
          $buyer_mail = Mail::to($data['buyer_email'])->send(new OrderDisputeRejectionMail($data));
        }

        if ($seller_mail) {
            return true;
        }


        return false; 
    }


    public function send_placed_order_dispute_rejection_email($order, $dispute, $initiator){
        $data['description'] = $order->orderRef;
        $data['cost'] = number_format($order->total,2);
        $data['currency'] = $order->currency;
        $data['orderRef'] = $order->orderRef;
        $data['seller_email'] = !is_null($order->seller) ? $order->seller->email : null;
        $data['buyer_email'] = !is_null($order->buyer) ? $order->buyer->email : null;
        $tranxStatusArray = cc('transaction.status');
        $data['tranxStatus'] = isset($tranxStatusArray[$order->status]) ? $tranxStatusArray[$order->status] : 'Undefined';
        $data['initiator_name'] = $initiator->name;
        $data['initiator_email'] = $initiator->email;
        $data['dispute_description'] = $dispute->dispute_description;
        $data['resolution'] = $dispute->final_resolution;
        $data['order_link'] = cc('frontend_base_url') .'app/placed_orders?id='. $order->id;
        $data['seller_name'] = !is_null($order->seller) ? $order->seller->name : null;
        $data['buyer_name'] = !is_null($order->buyer) ? $order->buyer->name : null;
        
        $buyer_mail = false;
        $seller_mail = false;
        if (filter_var($data['seller_email'], FILTER_VALIDATE_EMAIL)) {
          $seller_mail = Mail::to($data['seller_email'])->send(new OrderDisputeRejectionMail_Seller($data));
        }

        if (filter_var($data['buyer_email'], FILTER_VALIDATE_EMAIL)) {
          $buyer_mail = Mail::to($data['buyer_email'])->send(new OrderDisputeRejectionMail($data));
        }

        if ($seller_mail) {
            return true;
        }


        return false; 
    }

    public function send_order_sales_contract_email($order, $initiator){
       $data['description'] = $order->description;
        $data['cost'] = number_format($order->cost,2);
        $data['currency'] = $order->currency;
        $data['orderRef'] = $order->orderRef;
        $data['seller_email'] = $order->seller_email;
        $data['buyer_email'] = $order->buyer->email;
        $tranxStatusArray = cc('transaction.status');
        $data['tranxStatus'] = isset($tranxStatusArray[$order->status]) ? $tranxStatusArray[$order->status] : 'Undefined';
        $data['initiator_name'] = $initiator->name;
        $data['initiator_email'] = $initiator->email;
        $data['order'] = $order;
        $data['date_time'] = Carbon::now()->format('M d, Y');
        //$data['order_link'] = cc('frontend_base_url') .'/app/orders?id='. $order->id;
        
        $pdf = PDF::loadView('mails.salesContractPDF', $data);
        // return $pdf->download('invoice.pdf');
        $buyer_mail = false;
        if (filter_var($data['buyer_email'], FILTER_VALIDATE_EMAIL)) {
          $buyer_mail = Mail::send('mails.salesContract', $data, function($message) use($data, $pdf) {
            $message->to([$data['seller_email'],$data['buyer_email']])
                    ->from(cc('mail_from'))
                    ->subject("Order Sales Contract Online Escrow - ".$data['orderRef'])
                    ->attachData($pdf->output(), "Sales_Contract.pdf");
           });
        }
        
        // $seller_mail = Mail::to($data['seller_email'])->send(new OrderStatusChangeMail($data));
        // $buyer_mail = Mail::to($data['buyer_email'])->send(new OrderStatusChangeMail($data));

        if ($buyer_mail) {
            return true;
        }


        return false;
    }

   
    public function send_order_extend_delivery_email($order, $order_extend, $initiator){
        
        $data['description'] = $order->description;
        $data['cost'] = number_format($order->cost,2);
        $data['currency'] = $order->currency;
        $data['orderRef'] = $order->orderRef;
        $data['seller_email'] = $order->seller_email;
        $data['buyer_email'] = $order->buyer->email;
        $tranxStatusArray = cc('transaction.status');
        $data['tranxStatus'] = isset($tranxStatusArray[$order->status]) ? $tranxStatusArray[$order->status] : 'Undefined';
        $data['initiator_name'] = $initiator->name;
        $data['initiator_email'] = $initiator->email;
        //Carbon::parse($order->end_date)->format('Y-m-d')
        $data['end_date'] = Carbon::parse($order->end_date)->format('Y-m-d');
        $data['new_end_date'] = Carbon::parse($order_extend->new_end_date)->format('Y-m-d');
        $data['seller_reason'] = $order_extend->seller_reason;
        $data['order_link'] = cc('frontend_base_url') .'app/orders?id='. $order->id;
        //$data['order_link'] = cc('frontend_base_url') .'/app/extend/orders?id='. $order->id;
        $seller_mail = false;
        if (filter_var($data['seller_email'], FILTER_VALIDATE_EMAIL)) {
          $seller_mail = Mail::to([$data['seller_email'], $data['buyer_email']])->send(new OrderDeliveryChangeMail($data));
        }

        //$buyer_mail = Mail::to($data['buyer_email'])->send(new OrderDeliveryChangeMail($data));

        if ($seller_mail) {
            return true;
        }


        return false;

    }

    
    public function send_order_extend_confirm_email($order, $order_extend, $initiator){
        
        $data['description'] = $order->description;
        $data['cost'] = number_format($order->cost,2);
        $data['currency'] = $order->currency;
        $data['orderRef'] = $order->orderRef;
        $data['seller_email'] = $order->seller_email;
        $data['buyer_email'] = $order->buyer->email;
        $tranxStatusArray = cc('transaction.status');
        $data['tranxStatus'] = isset($tranxStatusArray[$order->status]) ? $tranxStatusArray[$order->status] : 'Undefined';
        $data['initiator_name'] = $initiator->name;
        $data['initiator_email'] = $initiator->email;
        $data['new_end_date'] = $order_extend->new_end_date;
        $data['buyer_action'] = $order_extend->buyer_action;
        $data['buyer_comment'] = $order_extend->buyer_comment;
        $data['order_link'] = cc('frontend_base_url') .'app/orders?id='. $order->id;
        //$data['order_link'] = cc('frontend_base_url') .'/app/extend/orders?id='. $order->id;
        
        $seller_mail = false;
        if (filter_var($data['seller_email'], FILTER_VALIDATE_EMAIL)) {
          $seller_mail = Mail::to([$data['seller_email'], $data['buyer_email']])->send(new OrderDeliveryChangeConfirmMail($data));
        }
        //$buyer_mail = Mail::to($data['buyer_email'])->send(new OrderDeliveryChangeConfirmMail($data));

        if ($seller_mail) {
            return true;
        }


        return false;

    }


    public function sendSMS($to,$msg){
        if (!is_null($to) && !is_null($msg)) {
            $from = env('SMS_SENDER_NAME');
            $username = env('SMS_SENDER_USERNAME');
            $pass = env('SMS_SENDER_PASSWORD');
            $url = env('SMS_SENDER_HOST');

            $curlPost = 'user='.$username.'&pass='.$pass.'&to='.$to.'&from='.$from.'&msg='.$msg;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            $response = curl_exec($ch);
            curl_close($ch);
            return $response;
            //if (stristr($response,$to) !== FALSE) return true; else return false;
        }
        return false;
        
    }

   

    public function SendBulkSMS($from,$to,$msg)
    {
        //$this->BulkSMSBalance();
        $url = env('SMS_SENDER_HOST');
        $username = env('SMS_SENDER_USERNAME');
        $password = env('SMS_SENDER_PASSWORD');

        if ($from && $to && $msg)
        {
            //Get Parameters
            $ret=$this->GetParameters();
            
            $url=''; $user=''; $pass='';
                        
            if (count($ret)>0)
            {
                foreach($ret as $row):
                    if($row->sms_url) $url=$row->sms_url;
                    if($row->sms_username) $user=$row->sms_username;
                    if($row->sms_password) $pass=$row->sms_password;
                endforeach;
                
#$file = fopen('aaa.txt',"w"); fwrite($file, count($ret)."\n".$from."\n".$to."\n".$msg); fclose($file);             
                if ($url && $user && $pass)
                {
                    #Process Recipient Phone Numbers - GSMPhoneNo($phone)
                    $arrPh=explode(',',$to); $p='';
                    
                    if (count($arrPh)>1)
                    {
                        foreach($arrPh as $v)
                        {
                            if ($v)
                            {
                                $pp=$this->CleanPhoneNo($v);
                                
                                if ($pp)
                                {
                                    if ($p=='') $p=$pp; else $p .= ','.$pp;
                                }
                            }
                        }
                        
                        $to=$pp;
                    }
                    
                    $curlPost = 'user='.$user.'&pass='.$pass.'&to='.$to.'&from='.$from.'&msg='.$msg;
            
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    
                    
                    /*$ch = curl_init();
                    curl_setopt($ch, CURLOPT_POST, TRUE);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_HEADER, FALSE);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
                    */
                    
                    // response of the POST request
                    $response = curl_exec($ch);
                    curl_close($ch);
                    
                    
                    if (stristr($response,$to) !== FALSE) return 'OK'; else return 'FAIL';
#http://cloud.nuobjects.com/api/credit/?user=demo&pass=demopass 

#msg -> must <= 905chars;
#to-> must be in international format and no prefix e.g. 234819...) Separate with comma when sending to multiple recipients e.g. 234805... , 234803...;
#from -> must <= 11chars for Alphanumerical or <= 16chars for Numerical) 
#type Message Format) -> 0 = Normal SMS, 1 = Flash SMS, 2 = Unicode SMS (Arabic, Chinese etc) 
                }else
                {
                    return 'FAIL';
                }
            }else
            {
                return 'FAIL';
            }
        }else
        {
            return 'FAIL';
        }
    }

    // public function BulkSMSBalance()
    // {
    //     #Get Settings
    //     $emails=''; $phones='';
        
    //     $sql="SELECT * FROM settings";
            
    //     $query = $this->db->query($sql);
                
    //     if ( $query->num_rows()> 0 )
    //     {
    //         $row = $query->row();   
                    
    //         if ($row->emergency_no) $phones = $row->emergency_no;
    //         if ($row->emergency_emails) $emails = $row->emergency_emails;           
    //     }
        
    //     //Get Parameters
    //     $ret=$this->GetParameters();
        
    //     $url='http://cloud.nuobjects.com/api/credit'; $user=''; $pass='';
        
    //     if (count($ret)>0)
    //     {
    //         foreach($ret as $row):
    //             if($row->sms_username) $user=$row->sms_username;
    //             if($row->sms_password) $pass=$row->sms_password;
    //         endforeach;
                
    //         if ($user && $pass)
    //         {
    //             $curlPost = 'user='.$user.'&pass='.$pass;
        
    //             $ch = curl_init();
    //             curl_setopt($ch, CURLOPT_URL, $url);
    //             curl_setopt($ch, CURLOPT_POST, 1);
    //             curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
    //             curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                
    //             // response of the POST request
    //             $response = curl_exec($ch);
    //             curl_close($ch);
                
    //             $ret=str_replace(',','',$response);
                
    //             if (floatval($ret)<100)#Send message
    //             {
    //                 $r=intval($ret);
    //                 $ret=number_format(floatval($ret),0);
                    
    //                 $t='<i><b>'.$ret.' ('.str_replace('naira','',strtolower(MoneyInWords($r))).')</b></i>';
    //                 $t=str_replace('.','',$t);
                    
                    
    //                 $ta=$ret.' ('.str_replace('naira','',strtolower(MoneyInWords($r))).')';
    //                 $ta=str_replace('.','',$ta);
                    
    //                 $img=base_url()."images/emaillogo.png";
                    
    //                 $message = '
    //                     <html>
    //                     <head>
    //                     <meta charset="utf-8">
    //                     <title>Pepperest | Bulk SMS Warning</title>
    //                     </head>
    //                     <body>
    //                             <p><img src="'.$img.'" width="100" alt="Pepperest" title="Pepperest" /></p>
                                
    //                             <p>Dear User,<br><br></p>
                                
    //                             <p>The portal\'s bulk sms total units is '.$t.'. You are advised to credit the account so that the portal\'s messaging module can function effectively.</p>
                                                                                                                                                                        
    //                             <p>Best Regards</p>
    //                             <p>
    //                                 Pepperest<br>
    //                             </p>
    //                     </body>
    //                     </html>';
                        
    //                 $altmessage = '
    //                     Hello User,
                                
    //                     The portal\'s bulk sms total units is '.$ta.'. You are advised to credit the account so that the portal\'s messaging module can function effectively.
                                                                                                                                                    
    //                     Best Regards
                        
    //                     Pepperest
    //                     ';  
                        
    //                 $this->SendEmail('pepperest.com',$emails,'Bulk SMS Units Warning','',$message,$altmessage,'Pepperest Advisor');
                    
    //                 $m='Portal\'s bulk sms total units is '.$ret.'. You\'re advised to credit the account so that the portal\'s messaging module can function effectively. Pepperest.';
                    
    //                 #Send SMS
    //                 $this->SendBulkSMS('Pepperest',$this->GSMPhoneNo($phones),$m);
    //             }
                
    //             return $ret;
    //         }else#No Username and Password
    //         {
    //             $img=base_url()."images/emaillogo.png";
                    
    //             $message = '
    //                 <html>
    //                 <head>
    //                 <meta charset="utf-8">
    //                 <title>Pepperest | Bulk SMS Warning</title>
    //                 </head>
    //                 <body>
    //                         <p><img src="'.$img.'" width="100" alt="Pepperest" title="Pepperest" /></p>
                            
    //                         <p>Dear User,<br><br></p>
                            
    //                         <p>The bulk sms account information (username and password) have not been set on the Pepperest portal. You are advised to ensure that the account information are set so that the portal\'s messaging module can function effectively.</p>
                            
    //                         <p>To set the account information, sign in to the Pepperest portal with a valid account details, Under <b><i>Settings/Users</i></b> menu (side menu), click on <b><i>Portal Settings</i></b> to open the settings screen, enter the correct buksms account information and click on <b><i>Update Settings</i></b> button. Please note that <i><b>YOU MUST HAVE THE REQUIRED PERMISSION TO BE ABLE TO DO THE ABOVE.</i></b></p>
                                                                                                                                                                    
    //                         <p>Best Regards</p>
    //                         <p>
    //                             Pepperest<br>
    //                         </p>
    //                 </body>
    //                 </html>';
                    
    //             $altmessage = '
    //                 Dear User,
                            
    //                 The bulk sms account information (username and password) have not been set on the Pepperest portal. You are advised to ensure that the account information are set so that the portal\'s messaging module can function effectively.
                    
    //                 To set the account information, sign in to the Pepperest portal with a valid account details, Under "Settings/Users" menu (side menu), click on "Portal Settings" to open the settings screen, enter the correct buksms account information and click on "Update Settings" button. Please note that "YOU MUST HAVE THE REQUIRED PERMISSION TO BE ABLE TO DO THE ABOVE".
                                                                                                                                                
    //                 Best Regards
                    
    //                 Pepperest
    //                 ';  
                    
    //             $this->SendEmail('pepperest.com',$emails,'Bulk SMS Account Warning','',$message,$altmessage,'Pepperest System');
                
    //             return '';
    //         }
    //     }else
    //     {
    //         $img=base_url()."images/emaillogo.png";
                    
    //         $message = '
    //             <html>
    //             <head>
    //             <meta charset="utf-8">
    //             <title>Pepperest | Bulk SMS Warning</title>
    //             </head>
    //             <body>
    //                     <p><img src="'.$img.'" width="100" alt="Pepperest" title="Pepperest" /></p>
                        
    //                     <p>Dear User,<br><br></p>
                        
    //                     <p>The bulk sms account information (username and password) have not been set on the Pepperest portal. You are advised to ensure that the account information are set so that the portal\'s messaging module can function effectively.</p>
                        
    //                     <p>To set the account information, sign in to the Pepperest portal with a valid account details, Click on <b><i>System Settings</i></b> menu item(side menu) to open the settings screen, enter the correct buksms account information and click on <b><i>Update Settings</i></b> button. Please note that <i><b>YOU MUST HAVE THE REQUIRED PERMISSION TO BE ABLE TO DO THE ABOVE.</i></b></p>
                                                                                                                                                                
    //                     <p>Best Regards</p>
    //                     <p>
    //                         Pepperest<br>
    //                     </p>
    //             </body>
    //             </html>';
                
    //         $altmessage = '
    //             Dear User,
                        
    //             The bulk sms account information (username and password) have not been set on the Pepperest portal. You are advised to ensure that the account information are set so that the portal\'s messaging module can function effectively.
                
    //             To set the account information, sign in to the Pepperest portal with a valid account details, Click on "System Settings  menu item to open the settings screen, enter the correct buksms account information and click on "Update Settings" button. Please note that "YOU MUST HAVE THE REQUIRED PERMISSION TO BE ABLE TO DO THE ABOVE".
                                                                                                                                            
    //             Best Regards
                
    //             Pepperest
    //             ';  
                
    //         $this->SendEmail('pepperest.com',$emails,'Bulk SMS Account Warning','',$message,$altmessage,'Pepperest System');
            
    //         return '';
    //     }
    // }

    // public function send_sanction($late_comer_obj)
    // {
    //     try{
    //         $user = HCIS_employee::where('EmployeeId', $late_comer_obj->employee_id)->first();
    //         if (!is_null($user)) {
    //             if ($user->Branch == 'Head Office') {
    //                 $data['name'] = $user->EmployeeName;
    //                 $email = $user->Email;
    //                 $supervisor = !is_null($user->userSupervisor) ? $user->userSupervisor->supervisor_email : null;
    //                 //$branch = $user->Branch;
    //                 $data['day'] = $late_comer_obj->day;
    //                 $data['date'] = str_replace(' 00:00:00.000', '', $late_comer_obj->date);
    //                 if (!is_null($late_comer_obj->signin_time)) {
    //                     $data['time'] = $late_comer_obj->signin_time;
    //                 }else{
    //                     $data['time'] = '--:-- (you did not clock-in)';  
    //                 }
    //                 try{
    //                     if(!is_null($supervisor)){
    //                        Mail::to($email)
    //                         ->cc(['ServiceQualityUnit@asoplc.com', 'PerformanceManagementUnit@asoplc.com', 'ifeanyi.akaelu@asoplc.com', 'iyedele.bolaji@asoplc.com', $supervisor])
    //                         //->replyTo('ServiceQualityUnit@asoplc.com')
    //                         ->send(new SanctionMail($data)); 
    //                     }else{
    //                       Mail::to($email)
    //                         ->cc(['ServiceQualityUnit@asoplc.com', 'PerformanceManagementUnit@asoplc.com', 'ifeanyi.akaelu@asoplc.com', 'iyedele.bolaji@asoplc.com'])
    //                         //->replyTo('ServiceQualityUnit@asoplc.com')
    //                         ->send(new SanctionMail($data));  
    //                     }
                        
    //                     return true;
    //                 }

    //                     //catch exception
    //                 catch(Exception $e) {
    //                   return true;
    //                 }
    //             }
    //            return false; 
    //         }
    //         return false;
    //     }

    //     //catch exception
    //     catch(Exception $e) {
    //       return false;
    //     }
       
    // }

    
    // public function send_query_clockout($defaulter_obj)
    // {
    //     try{
    //         $user = HCIS_employee::where('EmployeeId', $defaulter_obj->employee_id)->first();
    //         if (!is_null($user)) {
    //             if ($user->Branch == 'Head Office') {
    //                 $data['name'] = $user->EmployeeName;
    //                 $email = $user->Email;
    //                 $supervisor = !is_null($user->userSupervisor) ? $user->userSupervisor->supervisor_email : null;
    //                 //$branch = $user->Branch;
    //                 $data['day'] = $defaulter_obj->day;
    //                 $data['date'] = str_replace(' 00:00:00.000', '', $defaulter_obj->date);
    //                 if (!is_null($defaulter_obj->signout_time)) {
    //                     $data['time'] = $defaulter_obj->signout_time;
    //                 }else{
    //                     $data['time'] = '--:-- (you did not clock-out)';  
    //                 }
    //                 try{
    //                     if(!is_null($supervisor)){
    //                       Mail::to($email)
    //                         ->cc(['ServiceQualityUnit@asoplc.com', 'PerformanceManagementUnit@asoplc.com', 'ifeanyi.akaelu@asoplc.com', 'iyedele.bolaji@asoplc.com', $supervisor])
    //                         //->replyTo('ServiceQualityUnit@asoplc.com')
    //                         ->send(new ClockoutMail($data));  
    //                     }else{
    //                        Mail::to($email)
    //                         ->cc(['ServiceQualityUnit@asoplc.com', 'PerformanceManagementUnit@asoplc.com', 'ifeanyi.akaelu@asoplc.com', 'iyedele.bolaji@asoplc.com'])
    //                         //->replyTo('ServiceQualityUnit@asoplc.com')
    //                         ->send(new ClockoutMail($data)); 
    //                     }
                        
    //                     return true;
    //                 }

    //                     //catch exception
    //                 catch(Exception $e) {
    //                   return true;
    //                 }
    //             }
    //            return false; 
    //         }
    //         return false;
    //     }

    //     //catch exception
    //     catch(Exception $e) {
    //       return false;
    //     }
       
    // }

    //  public function notify_staff($defaulter_obj, $message)
    // {
    //     try{
    //         $user = HCIS_employee::where('EmployeeId', $defaulter_obj->employee_id)->first();
    //         if (!is_null($user)) {
    //             if ($user->Branch == 'Head Office') {
    //                 $data['name'] = $user->EmployeeName;
    //                 $email = $user->Email;
    //                 $supervisor = !is_null($user->userSupervisor) ? $user->userSupervisor->supervisor_email : null;
    //                 $data['reply_body'] = $message;
    //                 try{
    //                     if(!is_null($supervisor)){
    //                        Mail::to($email)
    //                         ->cc(['ServiceQualityUnit@asoplc.com', 'PerformanceManagementUnit@asoplc.com', 'ifeanyi.akaelu@asoplc.com', 'iyedele.bolaji@asoplc.com', $supervisor])
    //                         //->replyTo('ServiceQualityUnit@asoplc.com')
    //                         ->send(new NotifyStaffMail($data)); 
    //                     }else{
    //                       Mail::to($email)
    //                         ->cc(['ServiceQualityUnit@asoplc.com', 'PerformanceManagementUnit@asoplc.com', 'ifeanyi.akaelu@asoplc.com', 'iyedele.bolaji@asoplc.com'])
    //                         //->replyTo('ServiceQualityUnit@asoplc.com')
    //                         ->send(new NotifyStaffMail($data));  
    //                     }
                        
    //                     return true;
    //                 }

    //                     //catch exception
    //                 catch(Exception $e) {
    //                   return true;
    //                 }
    //             }
    //            return false; 
    //         }
    //         return false;
    //     }

    //     //catch exception
    //     catch(Exception $e) {
    //       return false;
    //     }
       
    // }

    public function audit_log($log_json, $initiator, $subject)
    {
        return ;
    }
}