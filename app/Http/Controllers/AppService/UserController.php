<?php

namespace App\Http\Controllers\AppService;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\SocialProvider;
use Session;
use Socialite;
Use \Carbon\Carbon;
use Exception;
use DB;
use Illuminate\Support\Str;
//use JWTAuth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Facades\JWTFactory;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Tymon\JWTAuth\PayloadFactory;
use Tymon\JWTAuth\JWTManager as JWT;
use App\Models\Organization;
use App\Models\UserLoginAttempt;
use App\Http\Resources\UserResource;
use App\Http\Resources\OrganizationResource;
use GuzzleHttp\Client;

use App\Repositories\InventrooMailUtils;
use App\Repositories\TransactionLogUtils;


class UserController extends Controller
{   

  //The constructor function
    public function __construct(InventrooMailUtils $invUtil, TransactionLogUtils $transLogUtil){
        $this->invUtil = $invUtil;
        $this->transLogUtil = $transLogUtil;
        $this->perPage = 10;
    }

    public function testSMS(){
      $to = '2348039688395';
      $msg = "You just made a login attempt on Pepperest, here is your OTP: 123456"; 
      $result = $this->peppUtil->sendSMS($to, $msg);
      dd($result);
    }

    protected function decode_pass($str_encoded){
      if (base64_decode($str_encoded, true)) {
        return base64_decode($str_encoded);
      } else {
        return $str_encoded;
      }
    }


    public function createAccount(Request $request)
    {
        //return response()->json($request->all(), 200);
        if (!$request->accepts(['application/json'])) {
            return response()->json([ "ResponseStatus" => "Unsuccessful", 'Detail' => "The request format is not allowed","ResponseCode" => 401, "ResponseMessage" => "The request format is not allowed"], 401);
        }

        $validator = Validator::make($request->all() , [
          'business_name' => 'required|string|max:50',
          'email' => 'required|string|email|max:255|unique:users',
          'password' => 'required|confirmed|string|min:6', 
          'password_confirmation' => 'required|string|min:6', 
            //'password' => 'required|min:6|regex:/^.*(?=.{3,})(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[\d\x])(?=.*[!$#%]).*$/', 
        ]);

        if($validator->fails()){
          //$this->transLogUtil->logRequestError($request);
          return response()->json([ "ResponseStatus" => "Unsuccessful", 'Detail' => $validator->errors(), "ResponseCode" => 401, "ResponseMessage" => implode(', ',$validator->messages()->all())], 401);
        }

        //$this->transLogUtil->logRequest($request);

        try{

          $name = $request->input('business_name');
          $password = Hash::make($this->decode_pass($request->input('password')));
          $name_arr = explode (" ", $name);
          //$pass = 'passthru'.substr($request->input('phone'), -4);
          
          $user = User::create([
            'name' => $name,
            'first_name' =>  array_key_exists(0, $name_arr) ? $name_arr[0] : null,
            'last_name' =>  array_key_exists(1, $name_arr) ? $name_arr[1] : null,
            'email' => $request->input('email'),
            'password' => $password,
            'account_type' => 'admin',
            'unique_id' => generateUniqueCode(),
            'verification_ref' => generateUniqueID($request->input('email'))
          ]);
          
          //$user->generateTwoFactorCode();
          //send OTP
          if (!is_null($user)) {
            $org =  Organization::create([
                  'clientName' => $name,
                  'business_email' => $request->input('email'),
                  'admin_user_id' => $user->id,
                  //'account_type' => $request->input('account_type'),
                  'sub_user_count' => 1,
                 
                ]);

            if (!is_null($org)) {
               $user->update(['organization_id' => $org->id]);
            }

            $this->invUtil->send_account_confirmation($user);
          }else{
            return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => 'Registration not successful', "ResponseMessage" => 'Registration not successful'],500);
          }
          
          //log Audit Trail
          $this->transLogUtil->logAuditTrail($user->id, $request->ip(), 'User Creation', '', $user);
          //$this->logPasswordHistory($user->id, $password);
          
          $userInfo = new UserResource($user);

          return response()->json(compact('userInfo'),201);
        } catch (Exception $e) {
          return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong'],500);
        }
    }

    public function updateOrgAccount(Request $request)
    {
        $validator = Validator::make($request->all() , [
            'company_name' => 'required|string|max:50',
            'business_type' => 'required|string|max:50',
            'business_category' => 'required|string|max:50',
            'reg_no' => 'nullable|string|max:50',
            'vat_id' => 'nullable|string|max:50',
            'business_email' => 'required|email|max:50',
            'business_phone_no' => 'required|string|max:50',
            'website_link' => 'nullable|string|max:50',
            'business_address' => 'required|string',
            'city' => 'required|string|max:50',
            'state' => 'required|string|max:50',
            'country' => 'required|string|max:50',
            'acct_no' => 'nullable|numeric',
            'acct_name' => 'nullable|string',
            'acct_bank' => 'nullable|string|max:50',
            'fiscal_year_from' => 'nullable|date', 
            'fiscal_year_to' => 'nullable|date',
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'phone_no' => 'nullable|string|max:50',
            'home_address' => 'required|string',
            'home_city' => 'required|string|max:50',
            'home_state' => 'required|string|max:50',
            'home_country' => 'required|string|max:50',
            
        ]);

        if($validator->fails()){
          //$this->transLogUtil->logRequestError($request);
          return response()->json([ "ResponseStatus" => "Unsuccessful", 'Detail' => $validator->errors(), "ResponseCode" => 401, "ResponseMessage" => implode(', ',$validator->messages()->all())], 401);
          //implode(', ',$validator->messages()->all())
        }
        
        //$this->transLogUtil->logRequest($request);

        try{
          $user = $this->getAuthUser($request);
          if (!$user) {
             return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'User not found.', "ResponseMessage" => "User not found.", "ResponseCode" => 401], 401);
          }
          //$request->filled('productname') ? $request->input('productname') : $product->productname,
          $name = $request->input('first_name'). ' '. $request->input('last_name');
          $user->update(['name' => $name, 
            'first_name' => $request->input('first_name'), 
            'last_name' => $request->input('last_name'), 
            'phone' => $request->filled('phone_no') ? $request->input('phone_no') : $user->phone, 
            'street' => $request->filled('home_address') ? $request->input('home_address') : $user->street, 
            'city' => $request->filled('home_city') ? $request->input('home_city') : $user->city, 
            'state' => $request->filled('home_state') ? $request->input('home_state') : $user->state, 
            'country' => $request->filled('home_country') ? $request->input('home_country') : $user->country
          ]); 

          $org = Organization::find($user->organization_id);
          if (!is_null($org)) {
            $org->update(['clientName' => $request->filled('company_name') ? $request->input('company_name') : $org->clientName, 
              'address' => $request->filled('business_address') ? $request->input('business_address') : $org->address, 
              'state' => $request->filled('state') ? $request->input('state') : $org->state, 
              'city' => $request->filled('city') ? $request->input('city') : $org->city, 
              'country' => $request->filled('country') ? $request->input('country') : $org->country, 
              'business_email' => $request->filled('business_email') ? $request->input('business_email') : $org->business_email,
              'business_phone_no' => $request->filled('business_phone_no') ? $request->input('business_phone_no') : $org->business_phone_no, 
              'account_type' => $request->filled('business_type') ? $request->input('business_type') : $org->account_type, 
              'admin_user_id' => is_null($org->admin_user_id) ? $user->id : $org->admin_user_id, 
              'reg_no' => $request->filled('reg_no') ? $request->input('reg_no') : $org->reg_no, 
              'vat_id' => $request->filled('vat_id') ? $request->input('vat_id') : $org->vat_id, 
              'business_category' => $request->filled('business_category') ? $request->input('business_category') : $org->stbusiness_categoryreet, 
              'website_link' => $request->filled('website_link') ? $request->input('website_link') : $org->website_link, 
              'fiscal_year_from' => $request->filled('fiscal_year_from') ? $request->input('fiscal_year_from') : $org->fiscal_year_from, 
              'fiscal_year_to' => $request->filled('fiscal_year_to') ? $request->input('fiscal_year_to') : $org->fiscal_year_to, 
              'biz_acct_no' => $request->filled('acct_no') ? $request->input('acct_no') : $org->biz_acct_no, 
              'biz_acct_name' => $request->filled('acct_name') ? $request->input('acct_name') : $org->biz_acct_name, 
              'biz_acct_bank' => $request->filled('acct_bank') ? $request->input('acct_bank') : $org->biz_acct_bank, 
              'biz_acct_country' => $request->filled('country') ? $request->input('country') : $org->biz_acct_country
            ]);

            $userInfo = new UserResource($user);
            $companyInfo = new OrganizationResource($org);
             
            return response()->json(compact('userInfo', 'companyInfo'),201);
          }
          return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'Company not found', "ResponseCode" => 401],401);
        } catch (Exception $e) {
          return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
        }
    }

    
    public function login(Request $request)
    {

        $validator = Validator::make($request->all() , [
            'email' => 'required|string|email|max:60',
            'password' => 'required|string|min:6', 
        ]);

        if($validator->fails()){
          $this->transLogUtil->logRequestError($request);
          return response()->json([ "ResponseStatus" => "Unsuccessful", 'Detail' => $validator->errors(), "ResponseCode" => 401, "ResponseMessage" => implode(', ',$validator->messages()->all())], 401);
        }
        //$this->transLogUtil->logRequest($request);
        $credentials = ['email' => $request->input('email'), 'password' => $this->decode_pass($request->input('password'))];
        $user = User::where('email', $request->input('email'))->first();

        if (!is_null($user) && $user->status != 1) {
          //$user = new UserResource($user);

          //log Audit Trail
          $this->transLogUtil->logAuditTrail($user->id, $request->ip(), 'User Login', '', $user);

          //$this->logLoginAttempt($user->id, $request->ip(), 1);

          //return $this->addHeader(response()->json(compact('userInfo'),201));
          return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 401, "ResponseMessage" => 'Your account is inactive, pls contact an admin.'],401);
        }
        //for social auth
          
        try {
          if (!$token = JWTAuth::attempt($credentials)) {
            
            return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 401, "ResponseMessage" => 'Invalid credentials'],401);
          }
        } catch (JWTException $e) {
            //return response()->json(['error' => 'could_not_create_token'], 500);
            return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, "ResponseMessage" => $e->getMessage()],500);
        }

        try{
         
          $token = $this->respondWithToken($token);
          $userInfo = new   UserResource($user);
          
          //log Audit Trail
          $this->transLogUtil->logAuditTrail($user->id, $request->ip(), 'User Login', '', $user);

          return response()->json(compact('token', 'userInfo'),201);
        } catch (Exception $e) {
          return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong'],500);
        }
    }



    public function confirmAccount(Request $request)
    {
      $validator = Validator::make($request->all() , [
          'reference' => 'required|string|max:70',
          'source' => 'nullable|string', 
      ]);

      if($validator->fails()){
          return response()->json([ "ResponseStatus" => "Unsuccessful", 'Detail' => "Input error", "ResponseCode" => 401, "ResponseMessage" => $validator->errors()], 401);
      }
      $reference = $request->input('reference');
      if (!is_null($reference)) {
        $user = User::where('verification_ref', $reference)->first();
        if (!is_null($user) && $user->email_verified !=1) {
          $user->update(['email_verified' => 1, 'email_verified_at' => Carbon::now(), 'status' => 1]);
          //return response()->json(["ResponseStatus" => "Successful", 'Detail' => 'Your account has been confirmed.', "ResponseCode" => 201], 201);
          $token = $this->respondWithToken(JWTAuth::fromUser($user));
          $userInfo = new UserResource($user);
          return response()->json(compact('token', 'userInfo'),201);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'Invalid input or account is already confirmed', "ResponseCode" => 401], 401);
      }
      return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'Invalid Request', "ResponseCode" => 401], 401);
    }



    public function resendVerificationMail(Request $request)
    {
        $validator = Validator::make($request->all() , [
            'email' => 'required|string|email|max:255', 
            'source' => 'nullable|string', 
        ]);

        if($validator->fails()){
            return response()->json([ "ResponseStatus" => "Unsuccessful", 'Detail' => "Input error", "ResponseCode" => 400, "ResponseMessage" => $validator->errors()], 400);
        }

        $user = User::where('email', $request->input('email'))->first();
        if (!is_null($user)) {
          if ($request->input('source') == 'mobile') {
            $verificationRef = mt_rand(100000, 999999);
          }else{
            $verificationRef = generateUniqueID($request->input('email'));
          }

          $user->update(['verification_ref' => $verificationRef]);

          if ($request->input('source') == 'mobile') {
            $this->peppUtil->send_account_confirmation_token($user);
          }else{
            $this->peppUtil->send_welcome_email($user);
          }
          

          return response()->json(["ResponseStatus" => "Successful", 'Detail' => 'Check your mail to confirm account.', "ResponseCode" => 201], 201);
        }

        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'User was not found', "ResponseCode" => 400], 400);
    }


    public function verifyOTP(Request $request)
    {

      $validator = Validator::make($request->all() , [
          'userID' => 'required|integer',
          'otp' => 'required|numeric|min:6', 
      ]);

      if($validator->fails()){
        $this->transLogUtil->logRequestError($request);
        return response()->json([ "ResponseStatus" => "Unsuccessful", 'Detail' => $validator->errors(), "ResponseCode" => 401, "ResponseMessage" => implode(', ',$validator->messages()->all())], 401);
      }
      $this->transLogUtil->logRequest($request);

      try{

        $user = User::find($request->input('userID'));
        if (!is_null($user)) {
          if ($user->two_factor_code == $request->input('otp')) {

            if(Carbon::parse($user->two_factor_expires_at)->lt(Carbon::now()))
            {
              $user->resetTwoFactorCode();
              return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'OTP has expired', "ResponseMessage" => "OTP has expired, check again, another code has been sent.", "ResponseCode" => 401],401);
            }
            //return $user;
            $token = $this->respondWithToken(JWTAuth::fromUser($user));
            $customer = Customer::where('email', $user->email)->first();
            //$customer = $user->userInfo();
            $userInfo = new CustomerResource($customer);
            $user->resetTwoFactorCode();
            $user->update(['status' => 1]);
            //log Audit Trail
            $this->transLogUtil->logAuditTrail($user->id, $request->ip(), 'User OTP Verification', 'otp: '. $request->input('otp'), $customer);
            //Send welcome email for new customer
            if ($user->sent_welcome_msg != 1) {
              $this->peppUtil->send_welcome_email($user);
              $user->update(['sent_welcome_msg' => 1]);
            }
            
            return response()->json(compact('token', 'userInfo'));
          }

          return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'OTP is invalid', "ResponseCode" => 401],401);
        }

        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'User not found.', "ResponseCode" => 401], 401);
      } catch (Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong'],500);
      }
        
    }


    public function resendOTP(Request $request)
    {

      $validator = Validator::make($request->all() , [
          'userID' => 'required|integer',
      ]);

      if($validator->fails()){
        $this->transLogUtil->logRequestError($request);
        return response()->json([ "ResponseStatus" => "Unsuccessful", 'Detail' => $validator->errors(), "ResponseCode" => 401, "ResponseMessage" => implode(', ',$validator->messages()->all())], 401);
      }
      $this->transLogUtil->logRequest($request);

      try{

        $user = User::find($request->input('userID'));
        if (!is_null($user)) {
          $user->generateTwoFactorCode();
          //send OTP
          $this->peppUtil->send_user_otp($user);
          return response()->json(["ResponseStatus" => "Successful", 'Detail' => 'OTP has been sent', "ResponseCode" => 201],201);
        }

        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'User not found.', "ResponseCode" => 401], 401);
      } catch (Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong'],500);
      }
        
    }
    

    protected function respondWithToken($token)
    {

      return [
        'access_token' => $token,
        'token_type' => 'bearer',
        'expires_in' => auth('api')->factory()->getTTL() * 60
      ];
    }


    protected function getAuthUser(Request $request)
    {  
      try {
          if (! $user = JWTAuth::parseToken()->authenticate()) {
            return false;
            return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'User not found', "ResponseCode" => 400],400);
          }
           
          return $user;
         
      } catch (Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
          return false;
          return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'Expired Token', "ResponseCode" => $e->getStatusCode()],$e->getStatusCode());
      } catch (Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
          return false;
          return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'Invalid token', "ResponseCode" => $e->getStatusCode()],$e->getStatusCode());
      } catch (Tymon\JWTAuth\Exceptions\JWTException $e) {
          return false;
          return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'Token not provided', "ResponseCode" => $e->getStatusCode()],$e->getStatusCode());
      }
      return $user;
    }

    

    public function getAuthenticatedUser(Request $request)
    {
        //requires below to be passed in header
         //Authorization: Bearer {yourtokenhere}
        try {
            if (! $user = JWTAuth::parseToken()->authenticate()) {
                return response()->json(['user_not_found'], 404);
            }
        } catch (Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json(['token_expired'], $e->getStatusCode());
        } catch (Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['token_invalid'], $e->getStatusCode());
        } catch (Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['token_absent'], $e->getStatusCode());
        }
        return response()->json(compact('user'));
    }

    public function resetPasswordByEmail(Request $request)
    {
      $validator = Validator::make($request->all() , [
          'email' => 'required|string|email|max:255',
          'source' => 'nullable|string', 
      ]);

      if($validator->fails()){
        $this->transLogUtil->logRequestError($request);
        return response()->json([ "ResponseStatus" => "Unsuccessful", 'Detail' => $validator->errors(), "ResponseCode" => 401, "ResponseMessage" => implode(', ',$validator->messages()->all())], 401);
      }

      $user = User::where('email', $request['email'])->first();
      if (!is_null($user)) {
          if ($request->input('source') == 'mobile') {
            $token = mt_rand(100000,999999);
          }else{
            $token = Str::random(60);
          }
          //Create Password Reset Token
          DB::table('password_resets')->insert([
              'email' =>$request['email'],
              'token' => $token,
              'created_at' => Carbon::now()
          ]);
          
          try {
            
            if ($request->input('source') == 'mobile') {
              $this->peppUtil->send_password_reset_token($user, $token);
            }else{
              $this->peppUtil->send_password_reset($user, $token);
            }

            //log Audit Trail
            $this->transLogUtil->logAuditTrail($user->id, $request->ip(), 'Reset Passord Request', '', $user);
            return response()->json(["ResponseStatus" => "Successful", 'Detail' => 'A reset link has been sent to your email address.', "ResponseCode" => 201], 201);
          } catch (Exception $e) {
            //return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'An Error occurred. Please try again.', "ResponseCode" => 401, 'error' => $e->getMessage()], 401);
            return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong'],500);
          }
          
      }  
      return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'user not found', "ResponseCode" => 400], 400);
      
    }


    public function resetPasswordConfirm(Request $request)
    {
        //Validate input
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'password' => 'required|min:6|string', 
            //'password' => 'required|min:6|regex:/^.*(?=.{3,})(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[\d\x])(?=.*[!$#%]).*$/', 
            'token' => 'required|string',
            'source' => 'nullable|string', 
        ]);

        //check if payload is valid before moving on
        if ($validator->fails()) {
          $this->transLogUtil->logRequestError($request);
          return response()->json([ "ResponseStatus" => "Unsuccessful", 'Detail' => $validator->errors(), "ResponseCode" => 401, "ResponseMessage" => implode(', ',$validator->messages()->all())], 401);
        }
        $this->transLogUtil->logRequest($request);
        $password = $this->decode_pass($request->input('password'));
      // Validate the token
        $tokenData = DB::table('password_resets')
        ->where('token', $request->input('token'))->first();
      // Redirect the user back to the password reset request form if the token is invalid
        if (!$tokenData) {
          return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'Token not found', "ResponseMessage" => 'Token not found', "ResponseCode" => 401], 401);
        }
        $user = User::where('email', $tokenData->email)->first();
      // Redirect the user back if the email is invalid
        if (!$user) {
          return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'Email not found', "ResponseMessage" => 'Email not found', "ResponseCode" => 401], 401);
        }
        
        $password_hash = \Hash::make($password);

        $check_pass = $this->logPasswordHistory($user->id, $password_hash);
        if (!$check_pass) {
          return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'Password has been used in recent past.', "ResponseMessage" => 'Password has been used in recent past.', "ResponseCode" => 401], 401);
        }
      //Hash and update the new password
        $user->password = $password_hash;
        $user->update(); //or $user->save();

        //Delete the token
        DB::table('password_resets')->where('email', $user->email)
        ->delete();

        //login the user immediately they change password successfully
        $token = $this->respondWithToken(JWTAuth::fromUser($user));
        $customer = Customer::where('email', $user->email)->first();
        if (!is_null($customer)) {
          $userInfo = new CustomerResource($customer);
        }else{
          $userInfo = new UserResource($user);
        }
        
        //log Audit Trail
        $this->transLogUtil->logAuditTrail($user->id, $request->ip(), 'Reset Password Confirmation', 'token: '. $request->input('token'), $customer);
        
        return response()->json(compact('token', 'userInfo'),201);

    }

    public function resetUserPassword(Request $request)
    {
      $validator = Validator::make($request->all() , [
          'userID' => 'required|integer',
          'password' => 'required|min:6|string', 
          //'password' => 'required|min:6|regex:/^.*(?=.{3,})(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[\d\x])(?=.*[!$#%]).*$/', 
          
      ]);

      if($validator->fails()){
        $this->transLogUtil->logRequestError($request);
        return response()->json([ "ResponseStatus" => "Unsuccessful", 'Detail' => $validator->errors(), "ResponseCode" => 401, "ResponseMessage" => implode(', ',$validator->messages()->all())], 401);
      }
      $this->transLogUtil->logRequest($request);
      $user = User::find($request->input('userID'));
      if (!is_null($user)) {
          //check if password comply with password history policy
          $password_hash = \Hash::make($request->input('password'));
          $check_pass = $this->logPasswordHistory($user->id, $password_hash);
          if (!$check_pass) {
            return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'Password has been used in recent past.', "ResponseMessage" => 'Password has been used in recent past.', "ResponseCode" => 401], 401);
          }
          $user->update(['password' => $password_hash ]);
          $token = $this->respondWithToken(JWTAuth::fromUser($user));
          $customer = Customer::where('email', $user->email)->first();
          $userInfo = new CustomerResource($customer);
          //$userInfo = new UserResource($user);
          return response()->json(compact('token', 'userInfo'),201);
      }  
      return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'user not found', "ResponseCode" => 401], 401);
    }

    public function resetPassword(Request $request)
    {
      $validator = Validator::make($request->all() , [
          'merchantID' => 'required|integer',
          'password' => 'required|confirmed|string|min:6', 
          'password_confirmation' => 'required|string|min:6', 
          //'new_password' => 'required|string|min:6', 
      ]);

      if($validator->fails()){
        $this->transLogUtil->logRequestError($request);
        return response()->json([ "ResponseStatus" => "Unsuccessful", 'Detail' => "Input error", "ResponseCode" => 401, "ResponseMessage" => implode(', ',$validator->messages()->all())], 401);
      }
      $this->transLogUtil->logRequest($request);
      $merchant = Customer::find($request['merchantID']);
      $user = $merchant->user;
      if (!is_null($user)) {
          $user->update(['password' => Hash::make($request['password'])]);
          $token = $this->respondWithToken(JWTAuth::fromUser($user));
          //$token = $this->respondWithToken($token);
          return response()->json(compact('token'));
      }  
      return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'user not found', "ResponseCode" => 401], 401);
    }


    public function changePasswordNew(Request $request)
    {
      $validator = Validator::make($request->all() , [
        'password' => 'required',
        'new_password' => 'required|string|min:6|different:password', 
        //'password_confirmation' => 'required|string|min:6',
      ]);

      if($validator->fails()){
        //$this->transLogUtil->logRequestError($request);
        return response()->json([ "ResponseStatus" => "Unsuccessful", 'Detail' => $validator->errors(), "ResponseCode" => 401, "ResponseMessage" => implode(', ',$validator->messages()->all())], 401);
      }
      $this->transLogUtil->logRequest($request);
      try{
        $user = $this->getAuthUser($request);
        if (!is_null($user)) {
          if (Hash::check($this->decode_pass($request->input('password')), $user->password)) { 
            //check if password comply with password history policy
            $password_hash = \Hash::make($this->decode_pass($request->input('new_password')));
            $check_pass = $this->logPasswordHistory($user->id, $password_hash);
            if (!$check_pass) {
              return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'Password has been used in recent past.', "ResponseMessage" => 'Password has been used in recent past.', "ResponseCode" => 401], 401);
            }
            $user->update(['password' => $password_hash]);
            $token = $this->respondWithToken(JWTAuth::fromUser($user));
            return response()->json(compact('token'));

          }
          return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'Current Password is not valid', "ResponseMessage" => "Current Password is not valid", "ResponseCode" => 401], 401);
        }  
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'user not found', "ResponseMessage" => "user not found", "ResponseCode" => 401], 401);
      } catch (Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => $e->getMessage(), "ResponseCode" => 500, "ResponseMessage" => $e->getMessage()],500);
      }
    }

    /**
     * Log out
     * Invalidate the token, so user cannot use it anymore
     * They have to relogin to get a new token
     *
     * @param Request $request
     */
    public function logout(Request $request) {
      $validator = Validator::make($request->all(), ['token' => 'required']);
      
      if($validator->fails()){
           return response()->json([ 'message' => $validator->errors()], 400);
      }
      
      try {
          JWTAuth::invalidate($request->input('token'));
          return response()->json([
          'status' => 'success',
          'mmessage' => 'You have successfully logged out.'
      ], 201);
      } catch (JWTException $e) {
          // something went wrong whilst attempting to encode the token
          return response()->json([
              'status' => 'error',
              'message' => 'Failed to logout, please try again.'
          ], 400);
      }
    }

    public function refresh()
    {
        return response([
            'status' => 'success'
        ]);
    }

    public function refreshToken(Request $request){
      $token = JWTAuth::getToken();
      if(!$token){
          //throw new BadRequestHtttpException('Token not provided');
          return response()->json(['Token not provided'], 400);
      }
      try{
          $token = JWTAuth::refresh($token);
      }catch(Tymon\JWTAuth\Exceptions\TokenInvalidException $e){
          //throw new AccessDeniedHttpException('The token is invalid');
          return response()->json(['The token is invalid'], $e->getStatusCode());
      }
      $token = $this->respondWithToken($token);
      return response()->json(compact('token'));
    }

    //This returns user mgt page
    public function addUser(Request $request){
      try{

        $this->validate($request, [
            'email' => 'email|required|unique:users',
            'password'  => 'string|required|min:4',
            'lname'  => 'string|required',
            'fname'  => 'string|required',
            'job_title'  => 'string|required',
            'phone_no'  => 'numeric|nullable',
            'is_super_admin'  => 'integer|nullable',
            'org_id'  => 'integer|required'
        ]);
        $user = Auth::user();
        //dd($request->all());
        if(!is_null($user)){
          
          User::create(["email" => $request->input('email'),
                        "password" => Hash::make($request->input('password')),
                        "firstName" => $request->input('fname'),
                        "lastName" => $request->input('lname'),
                        "job_title" => $request->input('job_title'),
                        "phone_no" => $request->input('phone_no'),
                        "super_admin" => $request->input('is_super_admin'),
                        "organization_id" => $request->input('org_id')
                    ]);
           return redirect()->route('users')->with('message', 'User account was created sucessfully.');
        }
        return redirect()->route('login')->with('message', 'You have to login before accessing the resource.');
      }
        //catch exception
        catch(Exception $e) {
        return redirect()->back()->with('message', ' - Something went wrong.');
      }
    }
    
    //This returns user mgt page
    public function getUsers(){
      try{
        $user = Auth::user();
        //dd($user->orgAccounts);
        if(!is_null($user)){
          $user_ids[] = 0;
          //$user_ids[] = Auth::user()->id;
          $data['users'] = User::whereNotIn('id', $user_ids)->orderBy('firstName', 'asc')->paginate(30);
          $data['orgs'] = Organization::all();
          //dd($data['users'][0]->myOrganization);
          // $data['new_users'] = HCIS_employee::where('isActive', 0)->orderBy('EmployeeName', 'asc')->get(['EmployeeName', 'Email']);
          $data['user_roles'] = Role::all();
          //dd($data['new_users']);
          $data['userActive'] = 'active-menu';
          return view('backend.user_mgt', $data);
        }
        return redirect()->route('login')->with('message', 'You have to login before accessing the resource.');
      }
        //catch exception
        catch(Exception $e) {
        return redirect()->back()->with('message', ' - Something went wrong.');
      }
    }

   //This returns find user page
    public function findUser(Request $request){
       
        if (!is_null($email = $request['email'])) {
            $data['user'] = User::where('email', $email)->first();
            $data['user_roles'] = Role::all();
            $data['userActive'] = 'active-menu';
            return view('backend.userSearch', $data);
        }
        
       return redirect()->back()->with('message', 'User not found.');
    }

    //This function a user's account information
    public function editUser(Request $request){

      try{

        $this->validate($request, [
            'email' => 'email|required',
            'password'  => 'string|nullable',
            'lname'  => 'string|required',
            'fname'  => 'string|required',
            'job_title'  => 'string|required',
            'phone_no'  => 'numeric|nullable',
            'is_super_admin'  => 'integer|nullable',
            'userID'  => 'integer|required',
            'org_id'  => 'integer|required'
        ]);
        $user = Auth::user();
        //dd($request->all());
        if(!is_null($user)){
          $user_update = User::find($request->input('userID'));
          if (!is_null($user)) {
            $user_update->update(["email" => $request->input('email'),
                        "firstName" => $request->input('fname'),
                        "lastName" => $request->input('lname'),
                        "job_title" => $request->input('job_title'),
                        "phone_no" => $request->input('phone_no'),
                        "super_admin" => $request->input('is_super_admin'),
                        "organization_id" => $request->input('org_id')
                    ]);
            if ($request->filled('password')) {
              $user_update->update(["password" => Hash::make($request->input('password'))]);
            }
          }

        }
        return redirect()->route('users')->with('message', 'User account was updated sucessfully.');
        
      }
        //catch exception
        catch(Exception $e) {
        return redirect()->back()->with('message', ' - Something went wrong.');
      }
    }
    
    //This function deletes a user's account
    public function deleteUser(Request $request){
        if($user = User::find($request['user_id'])){
            $id = $user->id;
            $user->delete();
            RoleUser::where('user_id', $id)->delete();
            return redirect()->route('users')->with('message', 'User was deleted successfully');
        }
        return redirect()->back()->with('message', 'Your action was unsuccessful');
    }

 //This function blocks and unblock a user
    public function blockUser(Request $request){
        if($user = User::find($request['userID'])){
            if($user->status == 1){
              $user->update(['status' => 0]);
              return redirect()->route('users')->with('message', 'User was blocked successfully'); 
           }else{
              $user->update(['status' => 1]);
              return redirect()->route('users')->with('message', 'User was unblocked successfully');
           }
           
        }
        return redirect()->back();
    }


    //This returns user mgt page
    public function addRole(Request $request){
      try{

        $this->validate($request, [
            'email' => 'email|required|unique:users',
            'password'  => 'string|required|min:4',
            'lname'  => 'string|required',
            'fname'  => 'string|required',
            'job_title'  => 'string|required',
            'phone_no'  => 'numeric|nullable',
            'is_super_admin'  => 'integer|nullable',
            'org_id'  => 'integer|required'
        ]);
        $user = Auth::user();
        //dd($request->all());
        if(!is_null($user)){
          
          User::create(["email" => $request->input('email'),
                        "password" => Hash::make($request->input('password')),
                        "firstName" => $request->input('fname'),
                        "lastName" => $request->input('lname'),
                        "job_title" => $request->input('job_title'),
                        "phone_no" => $request->input('phone_no'),
                        "super_admin" => $request->input('is_super_admin'),
                        "organization_id" => $request->input('org_id')
                    ]);
           return redirect()->route('users')->with('message', 'User account was created sucessfully.');
        }
        return redirect()->route('login')->with('message', 'You have to login before accessing the resource.');
      }
        //catch exception
        catch(Exception $e) {
        return redirect()->back()->with('message', ' - Something went wrong.');
      }
    }
    
    //This returns user mgt page
    public function getRoles(){
      try{
        $user = Auth::user();
        //dd($user->orgAccounts);
        if(!is_null($user)){
          $user_ids[] = 0;
          //$user_ids[] = Auth::user()->id;
          $data['users'] = User::whereNotIn('id', $user_ids)->orderBy('firstName', 'asc')->paginate(30);
          $data['orgs'] = Organization::all();
          //dd($data['users'][0]->myOrganization);
          // $data['new_users'] = HCIS_employee::where('isActive', 0)->orderBy('EmployeeName', 'asc')->get(['EmployeeName', 'Email']);
          $data['user_roles'] = Role::all();
          //dd($data['new_users']);
          $data['userActive'] = 'active-menu';
          return view('backend.user_mgt', $data);
        }
        return redirect()->route('login')->with('message', 'You have to login before accessing the resource.');
      }
        //catch exception
        catch(Exception $e) {
        return redirect()->back()->with('message', ' - Something went wrong.');
      }
    }

   //This returns find user page
    public function findRole(Request $request){
       
        if (!is_null($email = $request['email'])) {
            $data['user'] = User::where('email', $email)->first();
            $data['user_roles'] = Role::all();
            $data['userActive'] = 'active-menu';
            return view('backend.userSearch', $data);
        }
        
       return redirect()->back()->with('message', 'User not found.');
    }

    //This function a user's account information
    public function editRole(Request $request){

      try{

        $this->validate($request, [
            'email' => 'email|required',
            'password'  => 'string|nullable',
            'lname'  => 'string|required',
            'fname'  => 'string|required',
            'job_title'  => 'string|required',
            'phone_no'  => 'numeric|nullable',
            'is_super_admin'  => 'integer|nullable',
            'userID'  => 'integer|required',
            'org_id'  => 'integer|required'
        ]);
        $user = Auth::user();
        //dd($request->all());
        if(!is_null($user)){
          $user_update = User::find($request->input('userID'));
          if (!is_null($user)) {
            $user_update->update(["email" => $request->input('email'),
                        "firstName" => $request->input('fname'),
                        "lastName" => $request->input('lname'),
                        "job_title" => $request->input('job_title'),
                        "phone_no" => $request->input('phone_no'),
                        "super_admin" => $request->input('is_super_admin'),
                        "organization_id" => $request->input('org_id')
                    ]);
            if ($request->filled('password')) {
              $user_update->update(["password" => Hash::make($request->input('password'))]);
            }
          }

        }
        return redirect()->route('users')->with('message', 'User account was updated sucessfully.');
        
      }
        //catch exception
        catch(Exception $e) {
        return redirect()->back()->with('message', ' - Something went wrong.');
      }
    }
    
    //This function deletes a user's account
    public function deleteRole(Request $request){
        if($user = User::find($request['user_id'])){
            $id = $user->id;
            $user->delete();
            RoleUser::where('user_id', $id)->delete();
            return redirect()->route('users')->with('message', 'User was deleted successfully');
        }
        return redirect()->back()->with('message', 'Your action was unsuccessful');
    }

 //This function blocks and unblock a user
    public function blockRole(Request $request){
        if($user = User::find($request['userID'])){
            if($user->status == 1){
              $user->update(['status' => 0]);
              return redirect()->route('users')->with('message', 'User was blocked successfully'); 
           }else{
              $user->update(['status' => 1]);
              return redirect()->route('users')->with('message', 'User was unblocked successfully');
           }
           
        }
        return redirect()->back();
    }
    

    public function addClient(Request $request)
    {

      $validator = Validator::make($request->all() , [
          'clientName' => 'required|string|max:60',
          'business_email' => 'required|string|email|max:255|unique:organizations',
          'description' => 'nullable|string|max:255',
          'country' => 'required|string|max:60',
          'biz_address' => 'required|string|max:255',
          'biz_state' => 'required|string|max:60',
          'biz_city' => 'required|string|max:60',
          'business_phone_no' => 'required|numeric',
          'account_type' => 'string|max:255',
          'sub_user_count' => 'required|integer',
          'firstName' => 'string|max:255',
          'lastName' => 'string|max:255',
          'email' => 'required|string|email|max:255|unique:users',
          'accessTypeID' => 'required|integer',
          'phoneNo' => 'required|numeric',
          //'organizationID' => 'required|numeric',
          'gender' => 'required|string|max:20',
          'userStatus' => 'required|integer',
          'jobTitle' => 'nullable|string|max:50', 
          'city' => 'nullable|string|max:50', 
          'state' => 'nullable|string|max:50', 
      ]);

      if($validator->fails()){
          return response()->json([ "ResponseStatus" => "Unsuccessful", 'Detail' => "Input error", "ResponseCode" => 400, "ResponseMessage" => $validator->errors()], 400);
      }

      try{

        $org =  Organization::create([
                  'clientName' => $request->input('clientName'),
                  'business_email' => $request->input('business_email'),
                  'description' => $request->input('description'),
                  'address' => $request->input('biz_address'),
                  'country' => $request->input('country'),
                  'state' => $request->input('biz_state'),
                  'city' => $request->input('biz_city'),
                  'business_phone_no' => $request->input('business_phone_no'),
                  'account_type' => $request->input('account_type'),
                  'sub_user_count' => $request->input('sub_user_count'),
                 
                ]);

        if (!is_null($org)) {
          $user = User::create([
              'firstName' => $request->input('firstName'),
              'lastName' => $request->input('lastName'),
              'email' => $request->input('email'),
              'accessTypeID' => $request->input('accessTypeID'),
              'job_title' => $request->input('jobTitle'),
              'state' => $request->input('state'),
              'city' => $request->input('city'),
              'gender' => $request->input('gender'),
              'status' => $request->input('userStatus'),
              'phone_no' => $request->input('phoneNo'),
              'organization_id' => $org->id,
             
          ]);

           if (is_null($user)) {
              //$token = $this->respondWithToken(JWTAuth::fromUser($user));
               return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'User was not created successfully, try again', "ResponseCode" => 400], 400);
               
            }else{

              $org->update(['admin_user_id' => $user->id]);

              //log trail
              $s_user = $this->getAuthUser($request);
              $this->logAuditTrail($s_user->id, $request->ip(), 'Client creation', '', $org);
              
              $clientInfo = new ClientResource($org);

               return response()->json(compact('clientInfo'),201);

            } 

          
        }else{

          return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'Client was not created successfully, try again', "ResponseCode" => 400], 400);

        }

      } catch (Exception $e) {
         return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 
                                     "ResponseMessage" => $e], 500);
      }

    
    }

    public function removeClient(Request $request)
    {
      $validator = Validator::make($request->all() , [
          'requesterID' => 'required|integer',
          'organizationID' => 'required|integer',
      ]);

      if($validator->fails()){
          return response()->json([ "ResponseStatus" => "Unsuccessful", 'Detail' => "Input error", "ResponseCode" => 400, "ResponseMessage" => $validator->errors()], 400);
      }
      try{
        $requester = User::find($request->input('requesterID'));
        $org = Organization::find($request->input('organizationID'));

        if(!is_null($requester) && !is_null($org)){
          if ($requester->super_admin == 1 || $requester->organization_id == $org->id) {
            //log trail
            $this->logAuditTrail($requester->id, $request->ip(), 'Client deletion', $org, '');
            $org->delete();
            return response()->json(["ResponseStatus" => "Successful", 'Detail' => 'Delete operation was successful', "ResponseCode" => 201], 201);
          }else{
            return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'Requester is not allowed to perform this operation', "ResponseCode" => 400], 400);
          }
          
        }

         return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'User or client was not found, check and try again', "ResponseCode" => 400], 400);
      } catch (Exception $e) {
         return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 
                                     "ResponseMessage" => $e], 500);
      }

    }


    public function modifyClient(Request $request)
    {
      $validator = Validator::make($request->all() , [
          //'clientName' => 'required|string|max:60',
          'userID' => 'required|integer',
          'orgID' => 'required|integer',
          //'business_email' => 'required|string|email|max:255|unique:organizations',
          'description' => 'nullable|string|max:255',
          'country' => 'required|string|max:60',
          'biz_address' => 'required|string|max:255',
          'biz_state' => 'required|string|max:60',
          'biz_city' => 'required|string|max:60',
          'admin_user_id' => 'required|integer',
          'business_phone_no' => 'required|numeric',
          'account_type' => 'string|max:255',
          'sub_user_count' => 'required|integer',
          
      ]);

      if($validator->fails()){
          return response()->json([ "ResponseStatus" => "Unsuccessful", 'Detail' => "Input error", "ResponseCode" => 400, "ResponseMessage" => $validator->errors()], 400);
      }

      try{
        $user = User::find($request->input('userID'));

        if(!is_null($user)){

          $org =  Organization::find($request->input('orgID'));
          if (!is_null($org)) {
            $before = $org;
            $org->update([ 
                    'description' => $request->input('description'),
                    'address' => $request->input('biz_address'),
                    'country' => $request->input('biz_country'),
                    'state' => $request->input('biz_state'),
                    'city' => $request->input('biz_city'),
                    'admin_user_id' => $request->input('admin_user_id'),
                    'business_phone_no' => $request->input('business_phone_no'),
                    'account_type' => $request->input('account_type'),
                    'sub_user_count' => $request->input('sub_user_count'),
                   
                  ]);
            
            //log trail
            $this->logAuditTrail($user->id, $request->ip(), 'Client Modification', $before, $org);

            $clientInfo = new ClientResource($org);

            return response()->json(compact('clientInfo'),201);
          }else{
            return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'Client was not found, check and try again', "ResponseCode" => 400], 400);
          }
        }else{
          return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'User was not found, check and try again', "ResponseCode" => 400], 400);
        }         
      } catch (Exception $e) {
         return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 
                                     "ResponseMessage" => $e], 500);
      }

    }


    /**
     * Redirect the user to the social media for authentication page.
     *
     * @return Response
     */
    public function redirectToProvider($provider)
    { 
        //dd('am here! '. $provider); ->scopes($scopes)->instagram_basic
        $scopes = ['pages_show_list', 'manage_pages', 'pages_read_engagement'];
        //return Socialite::driver($provider)->scopes($scopes)->redirect();
        return Socialite::driver($provider)->redirect();
        // https://www.facebook.com/v3.1/dialog/oauth?client_id=2253727468182840&state=4977ca1668e14138f63940d979040a51&response_type=code&sdk=php-sdk-5.7.0&redirect_uri=https%3A%2F%2Fpepperest.com%2Findex.php%2FLogin%2Ffacebooklogin&scope=email%2Cmanage_pages%2Cinstagram_basic
    }


    /**
     * Obtain the user information from the Provider.
     *
     * @return Response
     */
    public function handleProviderCallback(Request $request, $provider)
    {
        
        //try
         //{
            if($provider == 'twitter'){
              //$socialUser = Socialite::driver($provider)->user();
              $oauth_token = env('ACCESS_TOKEN');
              $oauth_token_secret = env('ACCESS_TOKEN_SECRET');

              $socialUser = Socialite::driver($provider)->userFromTokenAndSecret($oauth_token, $oauth_token_secret);
              
            }else{
              //dd('we are here');
              $socialUser = Socialite::driver($provider)->stateless()->user();
              //$user = Socialite::driver('github')->userFromToken($token);
            //dd($socialUser);
              }
        //  }
        // catch(\Exception $e)
        // {
        //     //dd($e);
        //     return response()->json(['message' => $e, 'error' => 'could not login user'], 400);
        // }

          // "https://graph.facebook.com/{user-id}/accounts
          // ?access_token={user-access-token}"

        $access_token = $socialUser->token;
        $user_id = $socialUser->id;

        $client = new Client();
        $response = $client->request('GET', "https://graph.facebook.com/{$user_id}/accounts?fields=name,access_token,id,instagram_business_account&access_token={$access_token}");
        ///me/accounts?fields=name,access_token,id,instagram_business_account', $accessToken
        // if ($response->getStatusCode() != 200) {
        //     return redirect()->route('home')->with('error', 'Unauthorized login to Instagram.');
        // }

        $content = $response->getBody()->getContents();
        $content = json_decode($content);
        dd($content);
        $page = $content->data[0];
        $page_access_token = $page->access_token;
        $page_id = $page->id;
        $page_name = $page->name;
        $insta_id = $page->instagram_business_account->id;
        
        //$page_response = $client->request('GET', "https://graph.facebook.com/{$page_id}/photos?access_token={$page_access_token}");
        $page_response = $client->request('GET', "https://graph.facebook.com/{$insta_id}/media?access_token={$page_access_token}");
        //GET /{ig-user-id}/media
        //GET graph.facebook.com/17841405822304914/media


        // if ($response->getStatusCode() != 200) {
        //     return redirect()->route('home')->with('error', 'Unauthorized login to Instagram.');
        // }

        $page_content = $page_response->getBody()->getContents();
        $page_content = json_decode($page_content);
        //dd($page_content);
        $item = $page_content->data[0];
        $item_id = $item->id;
        //$item_response = $client->request('GET', "https://graph.facebook.com/{$item_id}?fields=id,images&access_token={$page_access_token}");
        $item_response = $client->request('GET', "https://graph.facebook.com/{$item_id}?fields=id,media_type,media_url,permalink,owner&access_token={$page_access_token}");

        //https://graph.facebook.com/v7.0/17895695668004550?fields=id,media_type,media_url,owner,timestamp

        // if ($response->getStatusCode() != 200) {
        //     return redirect()->route('home')->with('error', 'Unauthorized login to Instagram.');
        // }

        $item_content = $item_response->getBody()->getContents();
        $item_content = json_decode($item_content);
        //bigest image 
        //$item_content->images[0]->source;
        dd($item_content);

       // User {#275 ▼
       //    +token: "EAAgBwNEpXTgBABT0YErqTPNlONYmnTrZAhr1spAqZCfawSghzZCeaZCwgYN9Taawqo0RgWYzjYytwaZBe9ats8BMKIp8UHuMfj0giCZABSupDyJmsOPk5anNrgvN4AXVYbXDuWgr9yNOZBkbsdXEj3BkvIDuBfg ▶"
       //    +refreshToken: null
       //    +expiresIn: 5183998
       //    +id: "2691575360947039"
       //    +nickname: null
       //    +name: "Emmanuel Obute"
       //    +email: null
       //    +avatar: "https://graph.facebook.com/v3.0/2691575360947039/picture?type=normal"
       //    +user: array:2 [▼
       //      "name" => "Emmanuel Obute"
       //      "id" => "2691575360947039"
       //    ]
       //    +"avatar_original": "https://graph.facebook.com/v3.0/2691575360947039/picture?width=1920"
       //    +"profileUrl": null
       //  }
        //check if we have logged provider
        
        $socialProvider = SocialProvider::where('provider_id',$socialUser->getId())->first();
        if(!$socialProvider)
        {
            //create a new user and provider
            $user = User::firstOrCreate(
                ['email' => !is_null($socialUser->getEmail()) ? $socialUser->getEmail() : $socialUser->getId().'@gmail.com' ],
                ['name' => $socialUser->getName()]
                //['phone_no' => $socialUser->getId()]
   
            );

            $user->socialProviders()->create(
                ['provider_id' => $socialUser->getId(), 'provider' => $provider, 'nickname' => $socialUser->getNickname(), 'avatar' => $socialUser->getAvatar(), 'access_token' => $socialUser->token]
            );

            $customer = Customer::create([
                  'email' => $user->email,
                  'businessname' => $user->name, 
                  'usertype' => 'Buyer',
            ]);

            if (!is_null($customer)) {
              $user->update(['customerID' => $customer->id]);
            }

        }
        else{
          $user = $socialProvider->user;
        }


        $token = $this->respondWithToken(JWTAuth::fromUser($user));
        
        $userInfo = new CustomerResource($customer);

        return response()->json(compact('userInfo','token'),201);

    }


  public function loginWithCode(Request $request)
  {
    $validator = Validator::make($request->all() , [
      'provider' => 'required|string',
      'email' => 'nullable|string',
      'name' => 'required|string',
      'phoneNo' => 'nullable|numeric',
      'userProviderID' => 'required|string',
      //'code' => 'required|string',
      'accountType' => 'nullable|string|max:15', 
    ]);


    if($validator->fails()){
      $this->transLogUtil->logRequestError($request);
      return response()->json([ "ResponseStatus" => "Unsuccessful", 'Detail' => $validator->errors(), "ResponseCode" => 401, "ResponseMessage" => implode(', ',$validator->messages()->all())], 401);
    }
    $this->transLogUtil->logRequest($request);

         //$client = new Client();
    $name = $request->input('name');
    $userProviderID = $request->input('userProviderID');
    $provider = $request->input('provider');
    $email = $request->input('email');
    $accountType = $request->input('accountType');
    $phoneNo = $request->input('phoneNo');
    $name_arr = explode (" ", $name);

      if ($provider == 'facebook') {
        
        $socialProvider = SocialProvider::where('provider_id',$userProviderID)->first();
        if(!$socialProvider)
        {

            //$name_arr = explode (" ", $name); 
            $user = User::where('email', $email)->first();
            if (is_null($user)) {
              //create a new user and provider
              $user = User::create([
                  'email' => !is_null($email) ? $email : $userProviderID,
                  'name' => $name,
                  'firstName' =>  array_key_exists(0, $name_arr) ? $name_arr[0] : null,
                  'lastName' =>  array_key_exists(1, $name_arr) ? $name_arr[1] : null,
                  'phoneNo' => $phoneNo,
              ]);

              // $uniqueCode = strtoupper(substr($user->firstName, 0, 2)). $user->id .strtolower(substr($user->lastName, 0, 1))  .time();
              // $user->update(['uniqueCode' => $uniqueCode]);
            }
            //create a new user and provider
            // $user = User::firstOrCreate(
            //     ['email' => !is_null($email) ? $email : $id ],
            //     ['name' => $name]
            //     //['phone_no' => $socialUser->getId()]
   
            // );

            $user->socialProviders()->create(
                ['provider_id' => $userProviderID, 'provider' => $provider]
            );

            $customer = Customer::create([
                  'email' => $user->email,
                  'businessname' => $user->name, 
                  'name' => $user->name, 
                  //'usertype' => 'Buyer',
                  'usertype' => $accountType,
            ]);

            if (!is_null($customer)) {
              $user->update(['customerID' => $customer->id]);
            }

          }
          else{
            $user = $socialProvider->user;
          }
          $customer = $user->userInfo;
           //$customer = Customer::where('email', $request->input('email'))->first();
          if (is_null($customer->merchantid)) {
            $customer->update(['merchantid' => generateUniqueID($email)]);
          }

          // try{
            
          //   $user->generateTwoFactorCode();
          //   //send OTP
          //   $this->peppUtil->send_user_otp($user);
          //   $user = new UserResource($user);
          //   return response()->json(compact('user'),201);
          // } catch (Exception $e) {
          //   return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, "ResponseMessage" => $e->getMessage()],500);
          // }

          $token = $this->respondWithToken(JWTAuth::fromUser($user));
          
          $userInfo = new CustomerResource($customer);

          return response()->json(compact('userInfo','token'),201);
        }elseif($provider == 'google'){

          $socialProvider = SocialProvider::where('provider_id', $userProviderID)->first();
          if(!$socialProvider)
          {
              //create a new user and provider
              //$name_arr = explode (" ", $name); 
              $user = User::where('email', $email)->first();
              if (is_null($user)) {
                //create a new user and provider
                $user = User::create([
                    'email' => !is_null($email) ? $email : $userProviderID,
                    'name' => $name,
                    'firstName' =>  array_key_exists(0, $name_arr) ? $name_arr[0] : null,
                    'lastName' =>  array_key_exists(1, $name_arr) ? $name_arr[1] : null,
                    'phoneNo' => $phoneNo,
                ]);

                // $uniqueCode = strtoupper(substr($user->firstName, 0, 2)). $user->id .strtolower(substr($user->lastName, 0, 1))  .time();
                // $user->update(['uniqueCode' => $uniqueCode]);
              }

              $user->socialProviders()->create(
                  ['provider_id' => $userProviderID, 'provider' => $provider,]
              );
              //'access_token' => $token

              $customer = Customer::create([
                    'email' => $user->email,
                    'businessname' => $user->name, 
                    'name' => $user->name, 
                    //'usertype' => 'Buyer',
                    'usertype' => $accountType,
              ]);

              if (!is_null($customer)) {
                $user->update(['customerID' => $customer->id]);
              }

          }
          else{
            $user = $socialProvider->user;
          }

          $customer = $user->userInfo;
           //$customer = Customer::where('email', $request->input('email'))->first();
          if (is_null($customer->merchantid)) {
            $customer->update(['merchantid' => generateUniqueID($request->input('email'))]);
          }


          $token = $this->respondWithToken(JWTAuth::fromUser($user));
          
          $userInfo = new CustomerResource($customer);

          return response()->json(compact('userInfo','token'),201);
        }else{
          return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'Unknown provider', "ResponseCode" => 400]);
        }

       return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'Invalid request', "ResponseCode" => 400]);

  }


}

