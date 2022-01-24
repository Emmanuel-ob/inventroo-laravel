<?php
namespace App\Repositories;
use App\Models\Transaction;
use App\Models\PendingTransfer;
use App\Models\OrderTransaction;
use App\Models\TransactionHistory;
use App\Models\AppAccount;
use App\Models\PaymentTransaction;
use App\Models\PendingPayment;
use App\Models\Invoice;
use App\Models\AuditTrail;
use App\Models\RequestLog;
use Exception;
Use \Carbon\Carbon;
use Illuminate\Support\Facades\Log as Logger;

class TransactionLogUtils 
{
    
    public function __construct()
    {
        
    }

    
    public function logAuditTrail($user_id, $user_ip, $event, $before, $after)
    {
      try{
        //$user_ip  = $this->getIp();
        if (!is_null($user_ip)) {
          $details = json_decode(file_get_contents("http://ipinfo.io/$user_ip/json"));
          $city=$details->city;
          $country=$details->country;
          $org=$details->org;
          $location="$city, $country. $org";
        }else{
          $location = null;
        }
        //$mac_addr = shell_exec('getmac');
        //$mac_addr1 = exec('getmac');

        // $cmd = "arp -a " . $user_ip;
        // $status = 0;
        // $return = [];
        // exec($cmd, $return, $status);
        // $mac = $status . '-'. $return;
        // $mac = (string)$mac;
        $mac=exec("arp -a ".escapeshellarg($user_ip));
        AuditTrail::create([
              'user_id' => $user_id,
              'user_ip' => $user_ip, 
              'mac_address' => $mac, 
              //'mac_address' => exec('getmac'), 
              'event' => $event, 
              'before' => $before, 
              'after' => $after, 
              'location' => $location
          ]);
      } catch (Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => $e->getMessage(), "ResponseCode" => 500, "ResponseMessage" => $e->getMessage()],500);
      }
    }
    // $this->logAuditTrail($s_user->id, $request->ip(), 'User creation', '', $user);

    public function logRequest($request){
      try{
        //$header = $_SERVER;
         $req_header = (object)$_SERVER;
         $header = (object)apache_request_headers();
         
         RequestLog::create([
                     'uri' => $req_header->REDIRECT_URL,
                     'method' => $req_header->REQUEST_METHOD,
                     'params' => json_encode($request->all()),
                     'api_key' => 01,
                     'ip_address' => $req_header->REMOTE_ADDR,
                     'time' => $req_header->REQUEST_TIME,
                     'request_date' => Carbon::now(),
                     'authorized' => 1,
                     'response_code' => 200
                    ]);
         
        return 'Ok';
      } catch (Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => $e->getMessage(), "ResponseCode" => 500, "ResponseMessage" => $e->getMessage()],500);
      }
    }

    public function logRequestError($request){
      try{
        //$header = $_SERVER;
         $req_header = (object)$_SERVER;
         $header = (object)apache_request_headers();
         
         RequestLog::create([
                     'uri' => $req_header->REDIRECT_URL,
                     'method' => $req_header->REQUEST_METHOD,
                     'params' => json_encode($header),
                     'api_key' => 01,
                     'ip_address' => $req_header->REMOTE_ADDR,
                     'time' => $req_header->REQUEST_TIME,
                     'authorized' => 0,
                     'response_code' => 400
                    ]);
         
        return 'Ok';
      } catch (Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => $e->getMessage(), "ResponseCode" => 500, "ResponseMessage" => $e->getMessage()],500);
      }
    }

    
    

}