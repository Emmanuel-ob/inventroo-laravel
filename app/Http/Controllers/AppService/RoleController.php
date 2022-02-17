<?php

namespace App\Http\Controllers\AppService;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
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
use App\Models\Role;
use App\Models\RoleUser;
use App\Models\ModuleRole;
use App\Models\Module;
use App\Models\UserLoginAttempt;
use App\Http\Resources\UserResource;
use App\Http\Resources\OrganizationResource;
use App\Http\Resources\RoleResource;
use GuzzleHttp\Client;

use App\Repositories\InventrooMailUtils;
use App\Repositories\TransactionLogUtils;


class RoleController extends Controller
{   

  //The constructor function
    public function __construct(InventrooMailUtils $invUtil, TransactionLogUtils $transLogUtil){
        $this->invUtil = $invUtil;
        $this->transLogUtil = $transLogUtil;
        $this->perPage = 10;
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

    

    
    //This returns user mgt page
    public function addUser(Request $request){
      try{

        $validator = Validator::make($request->all() , [
            'email' => 'email|required|unique:users',
            'name'  => 'string|required|min:4',
            'roleID'  => 'integer|required',
            //'job_title'  => 'string|nullable',
            'phone'  => 'numeric|nullable'
        ]);
      
        if($validator->fails()){
          //$this->transLogUtil->logRequestError($request);
          return response()->json([ "ResponseStatus" => "Unsuccessful", 'Detail' => $validator->errors(), "ResponseCode" => 401, "ResponseMessage" => implode(', ',$validator->messages()->all())], 401);
          //implode(', ',$validator->messages()->all())
        }
        
        //$this->transLogUtil->logRequest($request);
        $user = $this->getAuthUser($request);
        if (!$user) {
           return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'User not found.', "ResponseMessage" => "User not found.", "ResponseCode" => 401], 401);
        }
        if ($user->account_type == 'admin') {
          $name = $request->input('name');
          $password = generateUniqueCode();
          $name_arr = explode (" ", $name);
          
          $new_user = User::create(["email" => $request->input('email'),
                "password" => Hash::make($password),
                'name' => $name,
                'first_name' =>  array_key_exists(0, $name_arr) ? $name_arr[0] : null,
                'last_name' =>  array_key_exists(1, $name_arr) ? $name_arr[1] : null,
                //"job_title" => $request->input('job_title'),
                "phone" => $request->input('phone'),
                'account_type' => 'user',
                'unique_id' => generateUniqueCode(),
                'verification_ref' => generateUniqueID($request->input('email')),
                "organization_id" => $user->organization_id
              ]);

          if (!is_null($new_user)) {
            RoleUser::create(['user_id' => $new_user->id, 'role_id' => $request->input('roleID')]);

            $this->transLogUtil->logAuditTrail($user->id, $request->ip(), 'User Creation', '', $new_user);
            //$this->invUtil->send_account_confirmation_w_password($user, $password);
          }
         $new_user = new UserResource($new_user);

          return response()->json(compact('new_user'),201);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'You are not authorized to perform this function.', "ResponseMessage" => 'You are not authorized to perform this function.', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
    }


     //This function a user's account information
    public function editUser(Request $request){

      try{

        $validator = Validator::make($request->all() , [
            'email' => 'email|required',
            'name'  => 'string|required',
            'roleID'  => 'integer|required',
            //'job_title'  => 'string|nullable',
            'phone'  => 'numeric|nullable',
            'userID'  => 'integer|required',
        ]);
        
        if($validator->fails()){
          //$this->transLogUtil->logRequestError($request);
          return response()->json([ "ResponseStatus" => "Unsuccessful", 'Detail' => $validator->errors(), "ResponseCode" => 401, "ResponseMessage" => implode(', ',$validator->messages()->all())], 401);
          //implode(', ',$validator->messages()->all())
        }
        $user = $this->getAuthUser($request);
        if (!$user) {
           return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'User not found.', "ResponseMessage" => "User not found.", "ResponseCode" => 401], 401);
        }
        if ($user->account_type == 'admin') {
          $user_update = User::find($request->input('userID'));
          if (!is_null($user_update)) {
            $name = $request->input('name');
            $name_arr = explode (" ", $name);
            $user_bk = $user_update;
            $user_update->update(["email" => $request->input('email'),
                        'name' => $name,
                        'first_name' =>  array_key_exists(0, $name_arr) ? $name_arr[0] : null,
                        'last_name' =>  array_key_exists(1, $name_arr) ? $name_arr[1] : null,
                        //"job_title" => $request->filled('job_title') ? $request->input('job_title') : $user_update->job_title,
                        "phone" => $request->filled('phone') ? $request->input('phone') : $user_update->phone,
                    ]);
            if (!is_null($user_update)) {
              RoleUser::where('user_id', $user_update->id)->delete();
              RoleUser::create(['user_id' => $user_update->id, 'role_id' => $request->input('roleID')]);

              $this->transLogUtil->logAuditTrail($user->id, $request->ip(), 'User Modification', $user_bk, $user_update);
            }
          }
          $user_update = new UserResource($user_update);

          return response()->json(compact('user_update'),201);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'You are not authorized to perform this function.', "ResponseMessage" => 'You are not authorized to perform this function.', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
    }
    
    //This returns user mgt page
    public function getUsers(Request $request){
      try{
        $user = $this->getAuthUser($request);
        if (!$user) {
           return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'User not found.', "ResponseMessage" => "User not found.", "ResponseCode" => 401], 401);
        }
        if ($user->account_type == 'admin') {
          $user_ids[] = 0;
          //$user_ids[] = Auth::user()->id;
          $users = User::where('organization_id', $user->organization_id)->orderBy('name', 'asc')->paginate(30);
          
          $roles = Role::where('organization_id', $user->organization_id)->get();
          $users = UserResource::collection($users);
          $roles = RoleResource::collection($roles);

          return response()->json(compact('users', 'roles'),201);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'You are not authorized to perform this function.', "ResponseMessage" => 'You are not authorized to perform this function.', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
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

   
    
    //This function deletes a user's account
    public function deleteUser(Request $request){
      try{
        $validator = Validator::make($request->all() , [
            'userID'  => 'integer|required',
        ]);
        if($validator->fails()){
          //$this->transLogUtil->logRequestError($request);
          return response()->json([ "ResponseStatus" => "Unsuccessful", 'Detail' => $validator->errors(), "ResponseCode" => 401, "ResponseMessage" => implode(', ',$validator->messages()->all())], 401);
          //implode(', ',$validator->messages()->all())
        }
        $user = $this->getAuthUser($request);
        if (!$user) {
           return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'User not found.', "ResponseMessage" => "User not found.", "ResponseCode" => 401], 401);
        }
        if ($user->account_type == 'admin') {
          $del_user = User::find($request->input('userID'));
          if (!is_null($del_user)) {
            $del_user->delete();
            return response()->json(["ResponseStatus" => "Successful", 'Detail' => 'User deleted successfully', "ResponseMessage" => 'User deleted successfully', "ResponseCode" => 201],201);
          }
          return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'User not found', "ResponseMessage" => 'User not found', "ResponseCode" => 401],401);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'You are not authorized to perform this function.', "ResponseMessage" => 'You are not authorized to perform this function.', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
    }

 //This function blocks and unblock a user
    public function blockUser(Request $request){
      try{
        $validator = Validator::make($request->all() , [
            'userID'  => 'integer|required',
        ]);
        if($validator->fails()){
          //$this->transLogUtil->logRequestError($request);
          return response()->json([ "ResponseStatus" => "Unsuccessful", 'Detail' => $validator->errors(), "ResponseCode" => 401, "ResponseMessage" => implode(', ',$validator->messages()->all())], 401);
          //implode(', ',$validator->messages()->all())
        }
        $user = $this->getAuthUser($request);
        if (!$user) {
           return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'User not found.', "ResponseMessage" => "User not found.", "ResponseCode" => 401], 401);
        }
        if ($user->account_type == 'admin') {
          if($bl_user = User::find($request['userID'])){
              if($bl_user->status == 1){
                $bl_user->update(['status' => 0]);
                $message = 'Deactivated'; 
              }else{
                $bl_user->update(['status' => 1]);
                $message = 'Activated'; 
              }
             
           return response()->json(["ResponseStatus" => "Successful", 'Detail' => 'User deleted successfully', "ResponseMessage" => "User successfully $message", "ResponseCode" => 201],201);
          }
          return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'User not found', "ResponseMessage" => 'User not found', "ResponseCode" => 401],401);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'You are not authorized to perform this function.', "ResponseMessage" => 'You are not authorized to perform this function.', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
    }


    //This Adds a Role for priviledge access
    public function addRole(Request $request){
      try{

        $validator = Validator::make($request->all() , [
            'name'  => 'string|required',
            'description'  => 'string|nullable',
            'modules' => 'required|array',
            'modules.*' => 'integer',
        ]);
        if($validator->fails()){
          //$this->transLogUtil->logRequestError($request);
          return response()->json([ "ResponseStatus" => "Unsuccessful", 'Detail' => $validator->errors(), "ResponseCode" => 401, "ResponseMessage" => implode(', ',$validator->messages()->all())], 401);
          //implode(', ',$validator->messages()->all())
        }
        $user = $this->getAuthUser($request);
        if (!$user) {
           return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'User not found.', "ResponseMessage" => "User not found.", "ResponseCode" => 401], 401);
        }
        if ($user->account_type == 'admin') {
          
          $role = Role::create(["name" => $request->input('name'),
                        "description" => $request->input('description'),
                        "organization_id" => $user->organization_id
                      ]);
          if (!is_null($role)) {
            $modules = $request->input('modules');
            foreach ($modules as $module) {
              ModuleRole::create(['role_id' => $role->id, 'module_id' => $module]);
            }
          }
          $role = new RoleResource($role);
             
          return response()->json(compact('role'),201);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'You are not authorized to perform this function.', "ResponseMessage" => 'You are not authorized to perform this function.', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
    }


    //This function a user's account information
    public function editRole(Request $request){

      try{

        $validator = Validator::make($request->all() , [
            'roleID'  => 'integer|required',
            'name'  => 'string|required',
            'description'  => 'string|nullable',
            'modules' => 'required|array',
            'modules.*' => 'integer',
        ]);
        if($validator->fails()){
          //$this->transLogUtil->logRequestError($request);
          return response()->json([ "ResponseStatus" => "Unsuccessful", 'Detail' => $validator->errors(), "ResponseCode" => 401, "ResponseMessage" => implode(', ',$validator->messages()->all())], 401);
          //implode(', ',$validator->messages()->all())
        }
        $user = $this->getAuthUser($request);
        if (!$user) {
           return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'User not found.', "ResponseMessage" => "User not found.", "ResponseCode" => 401], 401);
        }
        if ($user->account_type == 'admin') {
          $role = Role::find($request->input('roleID'));
          if (!is_null($role)) {
            $role->update([
                    "name" => $request->filled('name') ? $request->input('name') : $role->name,
                    "description" => $request->filled('description') ? $request->input('description') : $role->description,
                 ]);
            if ($request->filled('modules')) {
              ModuleRole::where('role_id', $role->id)->delete();
              $modules = $request->input('modules');
              foreach ($modules as $module) {
                ModuleRole::create(['role_id' => $role->id, 'module_id' => $module]);
              }
            }
            $role = new RoleResource($role);
             
            return response()->json(compact('role'),201);
          }
          return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'Role not found', "ResponseMessage" => 'Role not found', "ResponseCode" => 401],401);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'You are not authorized to perform this function.', "ResponseMessage" => 'You are not authorized to perform this function.', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
    }
    
    //This returns all roles
    public function getRoles(Request $request){
      try{
        $user = $this->getAuthUser($request);
        if (!$user) {
           return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'User not found.', "ResponseMessage" => "User not found.", "ResponseCode" => 401], 401);
        }
        
        $roles = Role::where("organization_id", $user->organization_id)->get();
        if (!is_null($roles)) {
          $modules = Module::all();
          $roles = RoleResource::collection($roles);
             
          return response()->json(compact('roles', 'modules'),201);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'Roles not found', "ResponseMessage" => 'Roles not found', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
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

    
    
    //This function deletes a user's account
    public function deleteRole(Request $request){
        try{

        $validator = Validator::make($request->all() , [
            'roleID'  => 'integer|required',
        ]);
        if($validator->fails()){
          //$this->transLogUtil->logRequestError($request);
          return response()->json([ "ResponseStatus" => "Unsuccessful", 'Detail' => $validator->errors(), "ResponseCode" => 401, "ResponseMessage" => implode(', ',$validator->messages()->all())], 401);
          //implode(', ',$validator->messages()->all())
        }
        $user = $this->getAuthUser($request);
        if (!$user) {
           return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'User not found.', "ResponseMessage" => "User not found.", "ResponseCode" => 401], 401);
        }
        if ($user->account_type == 'admin') {
          $role = Role::find($request->input('roleID'));
          if (!is_null($role)) {
            $role->delete();
            return response()->json(["ResponseStatus" => "Successful", 'Detail' => 'Role deleted successfully', "ResponseMessage" => 'Role deleted successfully', "ResponseCode" => 201],201);
          }
          return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'Role not found', "ResponseMessage" => 'Role not found', "ResponseCode" => 401],401);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'You are not authorized to perform this function.', "ResponseMessage" => 'You are not authorized to perform this function.', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
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
    

   


}

