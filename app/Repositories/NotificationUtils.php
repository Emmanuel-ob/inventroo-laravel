<?php
namespace App\Repositories;

use Illuminate\Support\Facades\Log as Logger;
use Notification;
//use Illuminate\Notifications\Notification;
use App\Notifications\OrderNotification;

class NotificationUtils 
{
    
    public function __construct()
    {
        
    }

    public function orderNotification($user, $order, $subject){
      
      $details = [
              'orderRef' => $order->orderRef,
              'orderID' => $order->id,
              'subject' => $subject,
              'orderDesc' =>  $order->description,
              'ref' => $order->orderRef,
              'url' => '/order?' . $order->orderRef,
      ];

      //Notification::send($users, new OrderNotification($details));

      $user->notify(new OrderNotification($details));
    }

    public function orderNotificationToUsers($users, $order, $subject){
      
      $details = [
              'orderRef' => $order->orderRef,
              'orderID' => $order->id,
              'subject' => $subject,
              'orderDesc' =>  $order->description,
              'ref' => $order->orderRef,
              'url' => 'orderS?' . $order->orderRef,
      ];

      Notification::send($users, new OrderNotification($details));

      // $user->notify(new \App\Notifications\TaskComplete($details));
    }

}