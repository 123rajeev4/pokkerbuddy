<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\API\APIBaseController as APIBaseController;
use App\User;
use App\Token;
use Validator;
use Image;
use Mail;  
use Intervention\Image\ImageServiceProvider;
use DB;
use App\City;
use App\Doctor;
use App\Otp;
use App\Dtoken;
use App\Post;
use App\Specialty;
use App\Like;
use App\Savepost;
use Carbon\Carbon;
use App\Comment;
use Illuminate\Support\Facades\Crypt;
use App\CreateGame;
use App\JoinGame;    
use App\JoinedGames;
use App\HostRate;
use App\ScreenAdd;
use App\ReportUser;

class UserAPIController extends APIBaseController
{
    
    public function index()
    {
        
    }

 private function randomNumber()   
    {
        $alphabet    = "0123456789abcdefghijklmnopqrstwxyz";       
        $pass        = array();
        $alphaLength = strlen($alphabet) - 1;

        for ($i = 0; $i <= 3; $i++) {   
            $n      = rand(0, $alphaLength);
            $pass[] = $alphabet[$n];
        }
        return implode($number);   
    } 

private function tokenInsert($id = "", $devicetype = "", $devicetoken = "")
    {
        if (!empty($id) && !empty($devicetype) && !empty($devicetoken)) {
            $tokens              = new Token;
            $tokens->token       = Text::uuid();
            $tokens->user_id     = $id;
            $tokens->devicetype  = $devicetype;
            $tokens->devicetoken = $devicetoken;           
            $tokens->save($datatoken);
            return $datatoken;
        } else {
            return false;
        }
    }       
    private function checkToken($token)  
    { 

          
        $user_detail = Token::where('token',$token)->first();
        //echo "<pre>";print_r($token);die;
        if (!empty($user_detail)) {  
            return $user_detail->user_id;
        } else {
            $response["status"]  = "Failure";
            $response["message"] = "Invalid User.";
            echo json_encode($response);
            exit;
        }
    }

    public function signup(Request $request)    
    {
         $show_data='1';
         $input = $request->all();  
         $val_arr = [
            'name' => 'required|unique:users,name',
            'first_name' => 'required',
            'last_name' => 'required',
            'home_zip_code' => 'required',
            'country' => 'required',
            'dob' => 'required',
            'gender' => 'required',
            'email'=>'required|email|unique:users,email',
            'password' => 'required|min:6',
            'confirm_password' => 'required|same:password',  
            'deviceToken' => 'required',
            'deviceType' => 'required',
            'show_data' => '',   
        ];
        
        $validator = Validator::make($input, $val_arr);
        if($validator->fails()){   
            return $this->sendError($request->path(),$validator->errors()->first());       
        }
          
        $check_username = User::where('name',$input['name'])->first();  
        if (!empty($check_username)) {
            return $this->sendError($request->path(),'Nick Name already exists'); 
        }

        $check_email = User::where('email',$input['email'])->first();  

        if (!empty($check_email)) {
            return $this->sendError($request->path(),'Email id already exists'); 
        }
        
        $input['show_data']=$show_data;
       
        $post = User::create($input);

        $user   = new User;

        $token_s = str_random(25);                  

        $tokens_array = array('user_id'=>$post->id,'token'=>$token_s,'deviceType'=>$input['deviceType'],'deviceToken'=>$input['deviceToken']);
       $token_saver = Token::create($tokens_array);  
       $response['id']=$post->id;
       $response['name']=!empty($post->name)?$post->name:"";
       $response['first_name']=!empty($post->first_name)?$post->first_name:"";
       $response['last_name']=!empty($post->last_name)?$post->last_name:"";
       $response['home_zip_code']=!empty($post->home_zip_code)?$post->home_zip_code:"";
        
       $response['country']=$post->country;
       $response['dob']=$post->dob;
       $response['gender']=$post->gender;
       $response['email']=$post->email;
       $response['password']=$post->password;
       $response['confirm_password']=$post->confirm_password; 
       $response['deviceType']=$token_saver->deviceType;
       $response['deviceToken']=$token_saver->deviceToken;
       $response['token']=$token_s;         
      return $this->sendResponse($response,'User created successfully.',$request->path());

    }    

    public function login(Request $request)   
    {
       $input = $request->all();

       $validator = Validator::make($input, [   
            'name'=>'required',  
            'password' => 'required',
            'deviceToken'=>'required',
            'deviceType'=>'required|in:android,ios',
        ]);

        if($validator->fails()){
            return $this->sendError($request->path(),$validator->errors()->first());       
        }

          $details = DB::table('users')->where(['name'=>$input['name'],'confirm_password'=>$input['password']])->first();        
         
        if (empty($details)) {

            return $this->sendError($request->path(),"Password or nick name is incorrect");
        }

        $token_s = str_random(25);  
 
        $token_saver = Token::where('user_id',$details->id)->update(['token'=>$token_s,'deviceToken'=>$input['deviceToken'],'deviceType'=>$input['deviceType']]);       

        $response['token'] = $token_s;
        $response['name'] = $details->name;
        $response['email'] = $details->email;
        $response['password'] = $details->password;
        $response['confirm_password'] = $details->confirm_password;
        $response['deviceType'] = $input['deviceType'];
        $response['deviceToken'] = $input['deviceToken'];    
        return $this->sendResponse($response, 'User login successfully.',$request->path());  
    
    }

    public function CreateGame(Request $request)         
    {   
        $status=1;
        $message="New Event create";
        $input = $request->all();
        $val_arr = [
            'token' => 'required',
            'event_date' => 'required',
            'event_time' => 'required',
            'seats'=>'required',
            'zip_code'=>'required',
            'event_description' => 'required',
            'street_number' => 'required',
            'home_number' => 'required',
            'show_data' => '',
        ];
       
        $validator = Validator::make($input, $val_arr);
        if($validator->fails()){
            return $this->sendError($request->path(),$validator->errors()->first());       
        }


       $user_id=$this->checkToken($input['token']);

       $input['user_id']=$user_id;
       $input['show_data']=$status;
       

       // $note=$this->android_push($user_id,$message,$input['show_data'],$input['event_time'],$input['event_time']);
   
       // print_r($note);
       // die();
      
       $createGame= CreateGame::create($input);                
       return $this->sendResponse($createGame,'Game created successfully.',$request->path());
    

    }

    public function forgotPassword(Request $request)  
    {

            $input = $request->all();
            $validator = Validator::make($input, [
                'email'=>'required',
            ]);

            if($validator->fails()){
                return $this->sendError($request->path(),$validator->errors()->first());       
            }

            $details = User::whereEmail($input['email'])->first();

            //echo "<pre>";print_r($details);die;    

           if (empty($details)) {
                return $this->sendError($request->path(),"Email id does not exist with us!");
            }else{

              $otp = substr(str_shuffle(str_repeat("0123456789abcdefghijklmnopqrstuvwxyz", 6)), 0, 6);      

             $details->password=md5($otp);
             $details->confirm_password=$otp;                                        
             $details->save();       

             $email   = $input['email'];
             $subject = "Forgot Password";                                 
                 
                $postData ="";                          
                 try{  
                     Mail::send('emails.forgotPassword', ['otp' =>$otp], function($message) use ($postData,$email)
                                {  
                                  $message->from('support@mobulous.co.in', 'PokkerBuddy');
                                  $message->to($email, 'PokkerBuddy')->subject('Forgot Password');
                                });  

                     return $this->sendResponse(array('otp'=>$otp),'Password send your  email id ',$request->path());    

                 }
                 catch(Exception $e){
                                    return $this->sendResponse($otp,'User mail sent successfully',$request->path());

            }      
                   
            }     

    }



 public function Template(Request $request)  
    {
       
            $input = $request->all();
            $validator = Validator::make($input, [
                'email'=>'required', 
                'name'=>'required',
            ]);

            if($validator->fails()){
                return $this->sendError($request->path(),$validator->errors()->first());       
            }

            $details12 = User::whereName($input['name'])->first();

            if (!empty($details12)) {  
                return $this->sendError($request->path(),"Nick name already exists with us.!");
            }

            $details = User::whereEmail($input['email'])->first();

            //echo "<pre>";print_r($details);die;    

            if (!empty($details)) {
                return $this->sendError($request->path(),"Email id already exists with us!");
            }else{

            
                 $otp=''; 
                 $email   = $input['email'];
                 $subject = "Verification Mail";
                
                $datavalue=array('email'=>$email,'subject'=>$subject);
                 
                $postData ="";                            
                 try{     
                     Mail::send('emails.emailtemplate', ['otp' =>$otp], function($message) use ($postData,$email)
                                {  
                                  $message->from('support@mobulous.co.in', 'PokkerBuddy');
                                  $message->to($email, 'PokkerBuddy')->subject('Verification Mail');
                                });    


                       
                     return $this->sendResponse($datavalue,'Mail send successfully ',$request->path());    

                 }
                 catch(Exception $e){   
                                    return $this->sendResponse($datavalue,'Mail sent successfully',$request->path());

            }  
                    
            }          
               
            } 






    public function sendCode(Request $request)  
    {

            $input = $request->all();
            $validator = Validator::make($input, [
                'email'=>'required', 
                'name'=>'required',
            ]);

            if($validator->fails()){
                return $this->sendError($request->path(),$validator->errors()->first());       
            }

            $details12 = User::whereName($input['name'])->first();

            if (!empty($details12)) {  
                return $this->sendError($request->path(),"Nick name already exists with us.!");
            }

            $details = User::whereEmail($input['email'])->first();

            if (!empty($details)) {
                return $this->sendError($request->path(),"Email id already exists with us!");
            }else{

             $otp = rand(1000,9999);                          
                        
                 $email   = $input['email'];
                 $subject = "Verification otp";                                 
                 
                $postData ="";                            
                 try{     
                     Mail::send('emails.sendCode', ['otp' =>$otp], function($message) use ($postData,$email)
                                {  
                                  $message->from('support@mobulous.co.in', 'PokkerBuddy');
                                  $message->to($email, 'PokkerBuddy')->subject('Verification otp');
                                });    

                     return $this->sendResponse(array('otp'=>(String)$otp),'Mail send successfully ',$request->path());    

                 }
                 catch(Exception $e){   
                                    return $this->sendResponse((String)$otp,'Mail sent successfully',$request->path());

                            }        
               
            }     

    }


    public function updatePassword(Request $request)  
    {

         $input = $request->all();

            $validator = Validator::make($input, [
                'email'=>'required',
                'new_password'=>'required',
                'confirm_password'=>'required',
            ]);


            if($validator->fails()){
                return $this->sendError($request->path(),$validator->errors()->first());       
            }

            $details = User::whereEmail($input['email'])->first();

            if (empty($details)) {
                return $this->sendError($request->path(),"Email id does not exist with us!");
            }else{

            
                $details->password=md5($input['new_password']);
                $details->confirm_password=$input['confirm_password'];                   
                $details->save(); 
                $return_array = $details->toArray();        
                
                return $this->sendResponse($return_array, 'password save successfully.',$request->path());   
               
            }      

    }

  public function home(Request $request)           
    {

        $temp=array();
        $input = $request->all();
        $val_arr = [
            'token' => 'required',
        ];

        $validator = Validator::make($input, $val_arr);

        if($validator->fails()){
            return $this->sendError($request->path(),$validator->errors()->first());       
        }
        
       $user_id=$this->checkToken($input['token']);
        //print_r($user_id);die;  

         date_default_timezone_set('Asia/Kolkata');
         $date = date('Y-m-d');
                 // print_r($date);
                 // die();

       $game = DB::table('create_games')->where('user_id','!=',$user_id)->where('event_date','>=',$date)->where('show_data','1')->orderBy('id', 'desc')->get();  
       //echo "<pre>";print_r($game->user_id);die; 


       foreach($game as $games){         
        $join_status=JoinGame::where('user_id',$user_id)->where('game_id',$games->id)->first();  
         $game_count=JoinGame::where('game_id',$games->id)->count();

         // print_r($users);die;  
         $user_data = User::where('id',$games->user_id)->first();   
         //print_r($user_data);die;

         if(empty($user_data['image']))
         {
            $response['image'] = "https://mobulous.app/fametales/public/img/user_signup.png";
         } else {
            $response['image'] = url('/public/').'/'.$user_data->image;      
         }
         $address1 = $games->home_number."+".$games->street_number."+".$games->zip_code; 
         $address =  str_replace(' ','+',$address1); 
        
         $geocode = file_get_contents('https://maps.google.com/maps/api/geocode/json?key=AIzaSyABmNRYOXc8tN8qyJ1XnxhBDj6Q_IdTdRg&address='.$address.'&sensor=false');
        $output = json_decode($geocode);

       //  print_r($output->results[0]->geometry->location->lng); exit;

        if(!empty($output->results)){
               $lat1 = $output->results[0]->geometry->location->lat;
               $lat = number_format($lat1, 4, '.', '');
               $long1 =$output->results[0]->geometry->location->lng;
               $long = number_format($long1, 4, '.', ''); 
              
        }
     
        $response['id']=$games->id;    
        $response['name']=$user_data->name;
        $response['user_id']=$user_data->id;   
        $response['seats']=$games->seats;
        $response['avible_seats']=$games->seats-$game_count;
        $response['home_number']= !empty($games->home_number)?$games->home_number:"";
        $response['street_number']=!empty($games->street_number)?$games->street_number:"";
        $response['event_date']=$games->event_date;
        $response['event_time']=date('h:i A', strtotime($games->event_time));        
        $response['zip_code']=$games->zip_code;  
        $response['event_description']=$games->event_description;
        $response['lat']= !empty($lat)?$lat:""; 
        $response['log']= !empty($long)?$long:"";
        $response['event_date']=$games->event_date;
        $response['join_status']=!empty($join_status['status'])?$join_status['status']:"";     
        $temp['home_listing'][]=$response;      

       }        
       return $this->sendResponse($temp, 'Home Listing.',$request->path());    
    }

/***

find top rating host list
***/

   public function Maxhostrating(Request $request){
         $i=1;
         $temp=array();
         $input = $request->all();
         $val_arr = [
            'token' => 'required',
            'host_id'=>'required',
            'name'=>'required',
            'rate'=>'required',   
            'user_id'=>'required',
           
                   ];

         $validator = Validator::make($input, $val_arr);  
         $Maxhost = DB::table('host_rates')
        ->groupBy('host_rates.host_id')
        ->orderBy('host_rates.host_id', 'desc')
        ->join('users', 'users.id', '=', 'host_rates.host_id')
        ->select(DB::raw('avg(host_rates.rate)as rate'), 'users.name','users.image','host_rates.host_id','host_rates.user_id')
        ->get();
           
        foreach($Maxhost as $topRating){
        $response['increment_id']=$i;
        $response['name']=$topRating->name;
        $response['image']=$topRating->image;    
        $response['rate']=$topRating->rate;
        $response['user_id']=$topRating->user_id;
        $response['host_id']=$topRating->host_id;
        $temp['home_listing1'][]=$response;
        }
       return $this->sendResponse($temp, 'Home Listing.',$request->path()); 
       }

   
    public function gameListing(Request $request)           
    {
        $input = $request->all();
        $val_arr = [
            'token' => 'required',
        ];

        $validator = Validator::make($input, $val_arr);

        if($validator->fails()){
            return $this->sendError($request->path(),$validator->errors()->first());       
        }
        
       $user_id=$this->checkToken($input['token']);
       //print_r($user_id);die;  

       $game = DB::table('create_games')->where('user_id', $user_id)->orderBy('id', 'desc')->get();  
       //echo "<pre>";print_r($game->user_id);die;    
       foreach($game as $games){

       $join_status=JoinGame::where('user_id',$user_id)->where('game_id',$games->id)->first();         

        //$tol=count(DB::table('join_games')->groupBy('game_id')->get()); 
        
        //echo $games->id  ;  die;

        $game_count=JoinGame::where('game_id',$games->id)->count();

         
        
      // print_r($users);die;  
         $user_data = User::where('id',$games->user_id)->first();   
         //print_r($user_data);die;

         if(empty($user_data['image']))
         {
            $response['image'] = "https://mobulous.app/fametales/public/img/user_signup.png";
         } else {
            $response['image'] = url('/public/').'/'.$user_data->image;      
         } 
        $response['id']=$games->id;    
        $response['name']=$user_data->name;
        $response['user_id']=$user_data->id;   
        $response['seats']=$games->seats;
        $response['avible_seats']=$games->seats-$game_count;
        $response['event_date']=$games->event_date;
        $response['event_time']=date('h:i A', strtotime($games->event_time));      
        $response['zip_code']=$games->zip_code;
        $response['geo_location']=$games->home_number." ".$games->street_number;
        $response['event_description']=$games->event_description;
        $response['event_date']=$games->event_date;
        $response['createdon']=$games->created_at;
        $response['join_status']=!empty($join_status['status'])?$join_status['status']:"";     
        $temp['game_listing'][]=$response;      

       }        
        if(!empty($temp)){
          return $this->sendResponse($temp, 'Game Listing.',$request->path());
          }
          if(empty($temp)){
          return $this->sendResponse(array(), 'Game Listing.',$request->path());
          }    
        
    
    }



    public function editUserProfile(Request $request)    
    {

        $input = $request->all();
        $val_arr = [
            'token'=>'required',
            'home_zip_code'=>'required',
            'name'=>'required',  

        ];

        $validator = Validator::make($input, $val_arr);
        $user_id=$this->checkToken($input['token']);    
       
        if($validator->fails()){
            return $this->sendError($request->path(),$validator->errors()->first());       
        }

        $check_name = User::where('name',$input['name'])->where('id','<>',$user_id)->first();  
        if (!empty($check_name)) {  
            return $this->sendError($request->path(),'Nick name already exists'); 
        }

        $userdetails = User::find($user_id);
        if (is_null($userdetails)) {
                return $this->sendError($request->path(),'User not found.');
            }

        if ($request->hasFile('image')) 
        {

        $image = $request->file('image');
        $name = md5($user_id.time()).rand(1000,9999).'.'.$image->getClientOriginalExtension();
            $destinationPath = public_path('/userimage');
            $imagePath = $destinationPath. "/".  $name;
            $image->move($destinationPath, $name);
            $userdetails->image = 'userimage/'.$name;  
        }

        $userdetails->home_zip_code=$input['home_zip_code'];
        $userdetails->name=$input['name'];     
        $userdetails->save(); 

        $return_array = $userdetails->toArray();
        $rating_avg1 = HostRate::where('host_id',$user_id)->avg('rate');
        $rating_avg=number_format($rating_avg1,1);
    
    $hosted_event_count=CreateGame::where('user_id',$user_id)->count();
    $return_array['hosted_event']=!empty($hosted_event_count)?(String)$hosted_event_count:"0";  
    $return_array['join_event']="0";              

         if(empty($return_array['image']))
         {
            $return_array['image'] = "https://mobulous.app/fametales/public/img/user_signup.png";
         } else {
            $return_array['image'] = url('/public/').'/'.$userdetails->toArray()['image'];  
         }  

        $return_array['host_rank'] = !empty($rating_avg)?$rating_avg:"0";
        //dd($return_array['host_rank']);
        return $this->sendResponse($return_array, 'User Profile successfully updated',$request->path());
    }

    public function viewUserProfile(Request $request)    
    {

        $input = $request->all(); 
        $val_arr = [
            'token'=>'required',
                   ];

        $validator = Validator::make($input, $val_arr);
        $user_id=$this->checkToken($input['token']);  
       
        if($validator->fails()){
            return $this->sendError($request->path(),$validator->errors()->first());       
        }

          
          $userdetails = User::find($user_id);
          $return_array = $userdetails->toArray();
          $rating_avg1 = HostRate::where('host_id',$user_id)->avg('rate');
          $rating_avg=number_format($rating_avg1,1);



      $return_array['zip_code']=!empty($return_array['zip_code'])?$return_array['zip_code']:"";
      $hosted_event_count=CreateGame::where('user_id',$user_id)->count();

      $join_game=JoinGame::where(['user_id'=>$user_id,'status'=>'Approved'])->count();
      $return_array['hosted_event']=!empty($hosted_event_count)?(String)$hosted_event_count:"0"; 
        $return_array['home_zip_code']=!empty($return_array['home_zip_code'])?(String)$return_array['home_zip_code']:"";
        $return_array['first_name']=!empty($return_array['first_name'])?(String)$return_array['first_name']:"";
        $return_array['last_name']=!empty($return_array['last_name'])?(String)$return_array['last_name']:"";
        $return_array['join_event']=(String)$join_game;
        $return_array['host_rank'] = !empty($rating_avg)?$rating_avg:"0";
        $return_array['user_rank'] = !empty($return_array['user_rank'])?$return_array['user_rank']:"0";        


         if(empty($return_array['image'])){
           $return_array['image'] = "https://mobulous.app/fametales/public/img/user_signup.png";
         } else {
            $return_array['image'] = url('/public/').'/'.$userdetails->toArray()['image'];  
         }  

        return $this->sendResponse($return_array, 'User Profile successfully updated',$request->path());

    } 

     public function JoinGames(Request $request){        
        //echo "hello";die;  
        $input = $request->all();   

        $val_arr = [
            'token'=>'required',
            'game_id'=>'required',
            'game_host_id'=>'required',
        ];  

        $user_id=$this->checkToken($input['token']);    
        $input['user_id']=$user_id;
        $input['status']="Pending";   
        //print_r($input);die;
        $joinGame= JoinGame::create($input);
        return $this->sendResponse($joinGame, 'Game Join successfully.',$request->path());    
    
    } 

    public function userotp(Request $request)
    {
        $input = $request->all();
        $check_username = User::where('email',$input['email'])->first();

        if (!empty($check_username)) {
            return $this->sendError($request->path(),'Email already exist as user'); 
        }

        $check_phone = User::where('phone',$input['phone'])->first();
        //dd($check_phone);
        if (!empty($check_phone)) {
            return $this->sendError($request->path(),'phone number already exist as user'); 
        }

        $check_username1 = Doctor::where('email',$input['email'])->first();

        if (!empty($check_username1)) {
            return $this->sendError($request->path(),'email already exist as doctor'); 
        }
        
        $check_phone1 = Doctor::where('phone',$input['phone'])->first();
        
        //dd($check_phone);

        if (!empty($check_phone1)) {
            return $this->sendError($request->path(),'phone number already exist as doctor'); 
        }


        $check_phone_in_otp = DB::table('otps')->wherePhone($input['phone'])->first();

        if(!empty($check_phone_in_otp))
        {
            $check_phone_in_otp111 = Otp::find($check_phone_in_otp->id);
            $check_phone_in_otp111->otp = (string)rand(1000,9999);
            $check_phone_in_otp111->save();

             $return_array = $check_phone_in_otp111->toArray();
            unset($return_array['id']);

             return $this->sendResponse($return_array, 'Otp send successfully.',$request->path());
        } else {
            $insert_array = array('otp'=>(string)rand(1000,9999),'phone'=>$input['phone']);
            $check_phone_in_otp1 = Otp::create($insert_array);

            $return_array = $check_phone_in_otp1->toArray();
            unset($return_array['id']);
             return $this->sendResponse($return_array, 'Otp send successfully.',$request->path());
        }

    }

    public function newsfeedlist(Request $request)
    {
        $input = $request->all();

        $val_arr = [
            'user_id' => 'required',   
        ];

        $validator = Validator::make($input, $val_arr);


        if($validator->fails()){
            return $this->sendError($request->path(),$validator->errors()->first());       
        }

        $get_news_feedlist = DB::select("select * from(SELECT `posts`.`title`,`posts`.`url`,`posts`.`description`,`posts`.`created_at`,`doctors`.`fullname`,`doctors`.`image`,`posts`.`id` as post_id, `specialties`.`name` as speciality_name,`posts`.`image` as post_image,'doctor' as usertype FROM `posts` inner join `doctors` on (`posts`.`user_id`=`doctors`.`id`) inner join `specialties` on (`specialties`.`id`=`doctors`.`speciality_id`)   where `posts`.`usertype`='doctor' 
union all
SELECT `posts`.`title`,`posts`.`url`,`posts`.`description`,`posts`.`created_at`,`users`.`fullname`,`users`.`image` as user_image,`posts`.`id` as post_id, '' as speciality_name,`posts`.`image` as post_image,'user' as usertype FROM `posts` inner join `users` on (`posts`.`user_id`=`users`.`id`) where `posts`.`usertype`='user') as x order by x.post_id desc");

        

        foreach ($get_news_feedlist as $value) {

            $value->shareurl = url('/')."/postpage/".Crypt::encryptString($value->post_id);

             if(empty($value->image)){
                $value->image = "https://mobulous.app/fametales/public/img/user_signup.png";
            } else {
                 $value->image = url('/public/').'/'.$value->image;
            }

            if(empty($value->post_image)){
                $value->post_image = "";
            } else {
                $value->post_image = url('/public/').'/'.$value->post_image;
            }

            $like_recorde = DB::table('likes')->where(['user_id'=>$input['user_id'],'post_id'=>$value->post_id,'usertype'=>$input['usertype']])->first();

            if(empty($like_recorde))
            {
                $value->likeflag = "0";
            } else {
                $value->likeflag = "1";
            }

            $saveposts_recorde = DB::table('saveposts')->where(['user_id'=>$input['user_id'],'post_id'=>$value->post_id,'usertype'=>$input['usertype']])->first();

            if(empty($saveposts_recorde))
            {
                $value->saveflag = "0";
            } else {
                $value->saveflag = "1";
            }
        }

        $return_array = $get_news_feedlist;
       
       return $this->sendResponse($return_array, 'Post list retrieve successfully Submited',$request->path());
    }

    public function postnews(Request $request)
    {
        $input = $request->all();

         $val_arr = [
            'title' => 'required|max:25',
            'description' => 'required|max:500',
            'user_id'=>'required',
            'usertype'=>'required',
            'token'=>'required',
        ];

        $validator = Validator::make($input, $val_arr);


        if($validator->fails()){
            return $this->sendError($request->path(),$validator->errors()->first());       
        }

        if(empty($input['url'])){
             $input['url'] = "";
        }

       

        if($input['usertype'] == 'user')
        {
             $post = User::find($input['user_id']);


        if (is_null($post)) {
            return $this->sendError($request->path(),'User not found.');
        }

         $check_token = Token::where(['user_id'=>$input['user_id'],'token'=>$input['token']])->first();

        if (empty($check_token)) {
            return $this->sendError($request->path(),'Token Expire');
        }


        if ($request->hasFile('image')) 
        {
            $image = $request->file('image');
            $name = md5($input['user_id'].time()).rand(1000,9999).'.'.$image->getClientOriginalExtension();
            $destinationPath = public_path('/postimage');
            $imagePath = $destinationPath. "/".  $name;
            $image->move($destinationPath, $name);
            $input['image'] = 'postimage/'.$name;
        }

        $newpost = Post::create($input);
        $return_array = $newpost->toArray();

        unset($return_array['id']);
        $return_array['post_id'] = (string)$newpost->toArray()['id'];

        if(empty($return_array['image'])){
           $return_array['image'] = ""; 
        } else {
           
           $return_array['image'] = url('/public/').'/'.$newpost->toArray()['image'];
        }
        
        
        return $this->sendResponse($return_array, 'Post successfully Submited.',$request->path());


        } else if($input['usertype'] == 'doctor')
        {
             $post = Doctor::find($input['user_id']);


        if (is_null($post)) {
            return $this->sendError($request->path(),'Doctor not found.');
        }

         $check_token = Dtoken::where(['user_id'=>$input['user_id'],'token'=>$input['token']])->first();

        if (empty($check_token)) {
            return $this->sendError($request->path(),'Token Expire');
        }

        if ($request->hasFile('image')) 
        {
            $image = $request->file('image');
            $name = md5($input['user_id'].time()).rand(1000,9999).'.'.$image->getClientOriginalExtension();
            $destinationPath = public_path('/postimage');
            $imagePath = $destinationPath. "/".  $name;
            $image->move($destinationPath, $name);
            $input['image'] = 'postimage/'.$name;
        }

        

        $newpost = Post::create($input);

        $return_array = $newpost->toArray();

        unset($return_array['id']);
        //unset($return_array['image']);
        $return_array['post_id'] = (string)$newpost->toArray()['id'];
        $return_array['image'] = url('/public/').'/'.$newpost->toArray()['image'];
        return $this->sendResponse($return_array, 'Post successfully Submited',$request->path());
        }

    }

    public function changePassword(Request $request)
    {
        $input = $request->all();

        $val_arr = [
            'oldpassword'=>'required',
            'password' => 'required|min:6',
            'token'=>'required',

        ];  

        $validator = Validator::make($input, $val_arr);   

        $user_id=$this->checkToken($input['token']);  

        if($validator->fails()){
            return $this->sendError($request->path(),$validator->errors()->first());       
        }

           $post = User::where('id',$user_id)->first();     
            //echo "<pre>";print_r($user_data);die;      

            if (is_null($post)) {
                return $this->sendError($request->path(),'User not found.');
            }

            if($post->confirm_password != $input['oldpassword']) 
            {
                return $this->sendError($request->path(),'Old Password is wrong');
            } else {  
                $post->password = md5($input['password']);
                $post->confirm_password = $input['password'];
                $result=$post->save();

                return $this->sendResponse(array('status'=>'success'), 'Password Changed successfully',$request->path());
            }
        
    }   

    public function postlistbyuserid(Request $request,$id)
    {
        $input = $request->all();

        if($input['usertype'] == 'user')
        {
            $get_news_feedlist = DB::select("select * from(SELECT `posts`.`title`,`posts`.`url`,`posts`.`description`,`posts`.`created_at`,`users`.`fullname`,`users`.`image`,`posts`.`id` as post_id, '' as speciality_name,`posts`.image as post_image FROM `posts` inner join `users` on (`posts`.`user_id`=`users`.`id`) where `posts`.`usertype`='user' and `posts`.`user_id`='".$id."') as x order by x.post_id desc");
        } else if($input['usertype'] == 'doctor')
        {
            $get_news_feedlist = DB::select("select * from(SELECT `posts`.`title`,`posts`.`url`,`posts`.`description`,`posts`.`created_at`,`doctors`.`fullname`,`doctors`.`image`,`posts`.`id` as post_id, `specialties`.`name` as speciality_name,`posts`.image as post_image FROM `posts` inner join `doctors` on (`posts`.`user_id`=`doctors`.`id`) inner join `specialties` on (`specialties`.`id`=`doctors`.`speciality_id`)   where `posts`.`usertype`='doctor' and `posts`.`user_id`='".$id."') as x order by x.post_id desc");
        }

        foreach ($get_news_feedlist as $value) {

            $value->shareurl = url('/')."/postpage/".Crypt::encryptString($value->post_id);

            if(empty($value->image)){
                $value->image = "https://mobulous.app/fametales/public/img/user_signup.png";
            } else {
                 $value->image = url('/public/').'/'.$value->image;
            }

            if(empty($value->post_image)){
                $value->post_image = "";
            } else {
                $value->post_image = url('/public/').'/'.$value->post_image;
            }

            $like_recorde = DB::table('likes')->where(['user_id'=>$id,'post_id'=>$value->post_id,'usertype'=>$input['usertype']])->first();

            if(empty($like_recorde))
            {
                $value->likeflag = "0";
            } else {
                $value->likeflag = "1";
            }

            $saveposts_recorde = DB::table('saveposts')->where(['user_id'=>$id,'post_id'=>$value->post_id,'usertype'=>$input['usertype']])->first();

            if(empty($saveposts_recorde))
            {
                $value->saveflag = "0";
            } else {
                $value->saveflag = "1";
            }

           
           
        }

        



        return $this->sendResponse($get_news_feedlist, 'Post list retrieve successfully',$request->path());


    }

    public function likepost(Request $request)
    {
        $input = $request->all();

        $val_arr = [
            'token'=>'required',
            'usertype'=>'required',
            'user_id'=>'required',
            'post_id'=>'required',
        ];

        $validator = Validator::make($input, $val_arr);


        if($validator->fails()){
            return $this->sendError($request->path(),$validator->errors()->first());       
        }

        $like_recorde = DB::table('likes')->where(['user_id'=>$input['user_id'],'post_id'=>$input['post_id'],'usertype'=>$input['usertype']])->first();

        if(empty($like_recorde))
        {
                if($input['usertype'] == 'user')
        {
            $post = User::find($input['user_id']);


            if (is_null($post)) {
                return $this->sendError($request->path(),'User not found.');
            }

             $check_token = Token::where(['user_id'=>$input['user_id'],'token'=>$input['token']])->first();

            if (empty($check_token)) {
                return $this->sendError($request->path(),'Token Expire');
            }

            $like_colmn = Like::create($input);

            return $this->sendResponse(array('status'=>'success','likeflag'=>'1'), 'Post liked successfully',$request->path());


        } else if($input['usertype'] == 'doctor')
        {
           $post = Doctor::find($input['user_id']);


            if (is_null($post)) {
                return $this->sendError($request->path(),'Doctor not found.');
            }

             $check_token = Dtoken::where(['user_id'=>$input['user_id'],'token'=>$input['token']])->first();

            if (empty($check_token)) {
                return $this->sendError($request->path(),'Token Expire');
            } 

            $like_colmn = Like::create($input);  

            return $this->sendResponse(array('status'=>'success','likeflag'=>'1'), 'Post liked successfully',$request->path());
        }
        } else {
            
            DB::table('likes')->where(['user_id'=>$input['user_id'],'post_id'=>$input['post_id'],'usertype'=>$input['usertype']])->delete();

            return $this->sendResponse(array('status'=>'success','likeflag'=>'0'), 'Post unliked successfully',$request->path());
        }

        


    }

    public function savepost(Request $request)
    {
        $input = $request->all();

        $val_arr = [
            'token'=>'required',
            'usertype'=>'required',
            'user_id'=>'required',
            'post_id'=>'required',
        ];

        $validator = Validator::make($input, $val_arr);


        if($validator->fails()){
            return $this->sendError($request->path(),$validator->errors()->first());       
        }

        $saveposts_recorde = DB::table('saveposts')->where(['user_id'=>$input['user_id'],'post_id'=>$input['post_id'],'usertype'=>$input['usertype']])->first();

        if(empty($saveposts_recorde))
        {
            if($input['usertype'] == 'user')
            {
                $post = User::find($input['user_id']);


                if (is_null($post)) {
                    return $this->sendError($request->path(),'User not found.');
                }

                 $check_token = Token::where(['user_id'=>$input['user_id'],'token'=>$input['token']])->first();

                if (empty($check_token)) {
                    return $this->sendError($request->path(),'Token Expire');
                }

                $Savepost_colmn = Savepost::create($input);

                return $this->sendResponse(array('status'=>'success','saveflag'=>'1'), 'Post save successfully',$request->path());


            } else if($input['usertype'] == 'doctor')
            {
               $post = Doctor::find($input['user_id']);


                if (is_null($post)) {
                    return $this->sendError($request->path(),'Doctor not found.');
                }

                 $check_token = Dtoken::where(['user_id'=>$input['user_id'],'token'=>$input['token']])->first();

                if (empty($check_token)) {
                    return $this->sendError($request->path(),'Token Expire');
                } 

                $Savepost_colmn = Savepost::create($input);  

                return $this->sendResponse(array('status'=>'success','saveflag'=>'1'), 'Post save successfully',$request->path());
            }
        } else {

            DB::table('saveposts')->where(['user_id'=>$input['user_id'],'post_id'=>$input['post_id'],'usertype'=>$input['usertype']])->delete();

            return $this->sendResponse(array('status'=>'success','saveflag'=>'0'), 'Post unsave successfully',$request->path());
        }

        


    }

     public function commentOnPost(Request $request)
    {
        $input = $request->all();

        $validator = Validator::make($input, [
              'post_id' => 'required',
              'message' => 'required',
              'user_id' =>'required',
              'usertype'=>'required',
              'token'=>'required',
          ]);


          if($validator->fails()){
              return $this->sendError($request->path(),$validator->errors()->first());       
          }


          if($input['usertype'] == 'user')
          {
                $post = User::find($input['user_id']);


                if (is_null($post)) {
                    return $this->sendError($request->path(),'User not found.');
                }

                 $check_token = Token::where(['user_id'=>$input['user_id'],'token'=>$input['token']])->first();

                if (empty($check_token)) {
                    return $this->sendError($request->path(),'Token Expire');
                }

                Comment::create($input);

          } else if($input['usertype'] == 'doctor') {

               $post = Doctor::find($input['user_id']);


                if (is_null($post)) {
                    return $this->sendError($request->path(),'Doctor not found.');
                }

                 $check_token = Dtoken::where(['user_id'=>$input['user_id'],'token'=>$input['token']])->first();

                if (empty($check_token)) {
                    return $this->sendError($request->path(),'Token Expire');
                } 

                Comment::create($input);
          }


        

        

        $return_array = DB::select("select * from(SELECT comments.id as comment_id,users.image,users.fullname,users.email,comments.message,comments.created_at FROM `comments` inner join `users` on (users.id = comments.user_id) where comments.`usertype` = 'user' and comments.post_id ='".$input['post_id']."'
union ALL
SELECT comments.id as comment_id,doctors.image,doctors.fullname,doctors.email,comments.message,comments.created_at FROM `comments` inner join `doctors` on (doctors.id = comments.user_id) where comments.`usertype` = 'doctor' and comments.post_id ='".$input['post_id']."') as x order by x.comment_id DESC");

        foreach ($return_array as $value) {
             if(empty($value->image)){
                $value->image = "https://mobulous.app/fametales/public/img/user_signup.png";
            } else {
                 $value->image = url('/public/').'/'.$value->image;
            }

             $value->created_at = Carbon::parse($value->created_at)->diffForHumans();
        }


          return $this->sendResponse($return_array, 'Post Comment list retrieved successfully.',$request->path());




    }

     public function Commentlist(Request $request,$id)
    {
         $return_array = DB::select("select * from(SELECT comments.id as comment_id,users.image,users.fullname,users.email,comments.message,comments.created_at FROM `comments` inner join `users` on (users.id = comments.user_id) where comments.`usertype` = 'user' and comments.post_id ='".$id."'
union ALL
SELECT comments.id as comment_id,doctors.image,doctors.fullname,doctors.email,comments.message,comments.created_at FROM `comments` inner join `doctors` on (doctors.id = comments.user_id) where comments.`usertype` = 'doctor' and comments.post_id ='".$id."') as x order by x.comment_id DESC");


         foreach ($return_array as $value) {
             if(empty($value->image)){
                $value->image = "https://mobulous.app/fametales/public/img/user_signup.png";
            } else {
                 $value->image = url('/public/').'/'.$value->image;
            }

             $value->created_at = Carbon::parse($value->created_at)->diffForHumans();
        }




          return $this->sendResponse($return_array, 'Post Comment list retrieved successfully.',$request->path());
    }

    


    public function eviewUserProfile(Request $request,$id)
    {


          $userdetails = User::find($id);

        if (is_null($userdetails)) {
                return $this->sendError($request->path(),'User not found.');
            }  

            $return_array = $userdetails->toArray();

        unset($return_array['id']);
        unset($return_array['confirmpassword']);

         $return_array['user_id'] = (string)$userdetails->toArray()['id']; 

         if(empty($return_array['image']))
         {
            $return_array['image'] = "https://mobulous.app/fametales/public/img/user_signup.png";
         } else {
            $return_array['image'] = url('/public/').'/'.$userdetails->toArray()['image'];
         }


            return $this->sendResponse($return_array, 'User Profile details',$request->path());
    }

     public function savepostlist(Request $request,$id)
    {
        $input = $request->all();

        $get_news_feedlist = DB::select("select posts.usertype as ignuser,posts.user_id as ignuserid, posts.id as post_id,posts.title,posts.url,posts.description,posts.created_at,posts.image as post_image from saveposts inner join posts on (posts.id = saveposts.post_id) where saveposts.user_id ='".$id."' and saveposts.usertype='".$input['usertype']."' order by post_id desc");


         foreach ($get_news_feedlist as $value) {

            $value->shareurl = url('/')."/postpage/".Crypt::encryptString($value->post_id);

            
            $userlist = DB::table($value->ignuser."s")->where('id',$value->ignuserid)->first();
           
            $value->fullname = $userlist->fullname;
            if(empty($userlist->image)){
                $value->image = "https://mobulous.app/fametales/public/img/user_signup.png";
            } else {
                 $value->image = url('/public/').'/'.$userlist->image;
            }

            if(empty($value->post_image)){
                $value->post_image = "";
            } else {
                $value->post_image = url('/public/').'/'.$value->post_image;
            }

            if($value->ignuser == 'user')
            {
               $value->speciality_name = ""; 
            } else {
                $spl_name_by_id = DB::table('specialties')->where('id',$userlist->speciality_id)->first();

                $value->speciality_name = $spl_name_by_id->name;

            }

        }

        //dd($return_array);

        



        return $this->sendResponse($get_news_feedlist, 'Saved Post list retrieve successfully',$request->path());




    }


    public function deletepost(Request $request)
    {
        $input = $request->all();

        $val_arr = [
            'token'=>'required',
            'user_id'=>'required',
            'post_id'=>'required',
            'usertype'=>'required',
        ];

        $validator = Validator::make($input, $val_arr);


        if($validator->fails()){
            return $this->sendError($request->path(),$validator->errors()->first());       
        }

        if($input['usertype'] == 'user'){

                $post = User::find($input['user_id']);


                if (is_null($post)) {
                    return $this->sendError($request->path(),'User not found.');
                }

                 $check_token = Token::where(['user_id'=>$input['user_id'],'token'=>$input['token']])->first();

                if (empty($check_token)) {
                    return $this->sendError($request->path(),'Token Expire');
                }

                DB::table('posts')->where(['user_id'=>$input['user_id'],'id'=>$input['post_id'],'usertype'=>$input['usertype']])->delete();


        } else if($input['usertype'] == 'doctor')
        {


               $post = Doctor::find($input['user_id']);


                if (is_null($post)) {
                    return $this->sendError($request->path(),'Doctor not found.');
                }

                 $check_token = Dtoken::where(['user_id'=>$input['user_id'],'token'=>$input['token']])->first();

                if (empty($check_token)) {
                    return $this->sendError($request->path(),'Token Expire');
                } 

               DB::table('posts')->where(['user_id'=>$input['user_id'],'id'=>$input['post_id'],'usertype'=>$input['usertype']])->delete();
        }

        return $this->sendResponse(array('status'=>'success'), 'post deleted successfully',$request->path());


    }

    public function viewdoctorprofile(Request $request,$id)
    {
            $doctor_details = Doctor::find($id);


        if (is_null($doctor_details)) {
                return $this->sendError($request->path(),'User not found.');
            }  

            $return_array = $doctor_details->toArray();

             unset($return_array['id']);
             unset($return_array['confirmpassword']);

         $return_array['user_id'] = (string)$doctor_details->toArray()['id']; 

            if(empty($return_array['image']))
         {
            $return_array['image'] = "https://mobulous.app/fametales/public/img/user_signup.png";
         } else {
            $return_array['image'] = url('/public/').'/'.$doctor_details->toArray()['image'];
         }

         if(empty($return_array['insurance_accept']))
         {
            $return_array['insurance_accept'] = "";
         }

         $specialties_name = DB::table('specialties')->where('id',$return_array['speciality_id'])->first();

         $city_name = DB::table('cities')->where('id',$return_array['city_id'])->first();

         $return_array['city_name'] = $city_name->name;

          $return_array['specialties_name'] = $specialties_name->name;

          $return_array['avilability'] = $this->long_to_week($return_array['avilability']);

         return $this->sendResponse($return_array, 'Doctor Profile details',$request->path());


    }

     public function editdoctorprofile(Request $request,$id)
    {
                $input = $request->all();

                $val_arr = [
                    'token'=>'required',
                ];

                $validator = Validator::make($input, $val_arr);


                if($validator->fails()){
                    return $this->sendError($request->path(),$validator->errors()->first());       
                }


                $userdetails = Doctor::find($id);

                if (is_null($userdetails)) {
                        return $this->sendError($request->path(),'User not found.');
                    }

                $check_token = Dtoken::where(['user_id'=>$id,'token'=>$input['token']])->first();

                    if (empty($check_token)) {
                        return $this->sendError($request->path(),'Token Expire');
                    }    


                $name = "";

                if ($request->hasFile('image')) 
                {
                    $image = $request->file('image');
                    $name = md5($id.time()).rand(1000,9999).'.'.$image->getClientOriginalExtension();
                    $destinationPath = public_path('/userimage');
                    $imagePath = $destinationPath. "/".  $name;
                    $image->move($destinationPath, $name);
                    $userdetails->image = 'userimage/'.$name;
                }

                if(!empty($input['city_id'])){
                    $userdetails->city_id = $input['city_id'];
                }

                $userdetails->qualification = $input['qualification'];
                $userdetails->fee = $input['fees'];
                $userdetails->start_time = $input['start_time'];
                $userdetails->end_time = $input['end_time'];
                $userdetails->clinic = $input['clinic'];
               
                $userdetails->avilability = $this->short_to_week($input['avilability']);

                $userdetails->save();


                $return_array = $userdetails->toArray();

             unset($return_array['id']);
             unset($return_array['confirmpassword']);

             if(empty($return_array['insurance_accept']))
         {
            $return_array['insurance_accept'] = "";
         }

         $return_array['avilability'] = $this->long_to_week($return_array['avilability']);

         $return_array['user_id'] = (string)$userdetails->toArray()['id']; 

            if(empty($return_array['image']))
         {
            $return_array['image'] = "https://mobulous.app/fametales/public/img/user_signup.png";
         } else {
            $return_array['image'] = url('/public/').'/'.$userdetails->toArray()['image'];
         }

         if(empty($return_array['insurance_accept']))
         {
            $return_array['insurance_accept'] = "";
         }

                return $this->sendResponse($return_array, 'Doctor Profile updated successfully',$request->path());

    }

    

    


    public function all_city(Request $request)
    {
        $all_name_cities = DB::table('cities')->select('id','name')->get();

        return $this->sendResponse($all_name_cities, 'City list retrieve successfully',$request->path());
    }



    public function fulldoctordetails(Request $request,$id)
    {
        $recorde_detail = DB::select("SELECT doctors.fullname,doctors.id as user_id,doctors.clinic,doctors.avilability,doctors.image,specialties.name as speciality_name,cities.name as city_name,doctors.expertise_area,doctors.insurance_accept,doctors.qualification FROM `doctors` inner join cities on (cities.id=doctors.city_id) inner JOIN specialties on (specialties.id=doctors.speciality_id) where doctors.id='".$id."'");

           
            if(empty($recorde_detail[0]->image)){
                $recorde_detail[0]->image = "https://mobulous.app/fametales/public/img/user_signup.png";
            } else {
               $recorde_detail[0]->image = url('/').'/public/'.$value->image;
            }


            $recorde_detail[0]->rating = "100";

            if(empty($recorde_detail[0]->insurance_accept)){
                $recorde_detail[0]->insurance_accept = "";
            }

            $review_details = DB::select("SELECT users.image,doctorratings.rating*20 as rating,doctorratings.review,users.fullname FROM `doctorratings` INNER JOIN users on (users.id=doctorratings.user_id) where doctorratings.doctor_id='".$id."'");

            foreach ($review_details as $value) {
                  if(empty($value->image)){
                        $value->image = "https://mobulous.app/fametales/public/img/user_signup.png";
                    } else {
                       $value->image = url('/').'/public/'.$value->image;
                    }
            }

            $recorde_detail[0]->num_of_review = sizeof($review_details);

            $recorde_detail[0]->review_list = $review_details;

            return $this->sendResponse($recorde_detail[0], 'Doctors details retrieve successfully',$request->path());


    }




    public function gameRequestList(Request $request)           
    {
        $input = $request->all();

        $val_arr = [
            'token' => 'required',
              
        ];

        $validator = Validator::make($input, $val_arr);

        if($validator->fails()){
            return $this->sendError($request->path(),$validator->errors()->first());       
        }
       //print_r($input); exit;
       $user_id = $this->checkToken($input['token']);
        
       $game_req = DB::table('join_games')->where(['status'=>'Pending','game_host_id'=>$user_id])->get();
       //dd($game);
       //echo "<pre>"; print_r($game);die;    
       foreach($game_req as $games){
 
        $user_data = User::where('id',$games->user_id)->first();
        $game_data = CreateGame::where('id',$games->game_id)->first();   
         //print_r($user_data);die;

       if(empty($user_data['image']))
        {
         $response['image'] = "https://mobulous.app/fametales/public/img/user_signup.png";
        } else {
         $response['image'] = url('/public/').'/'.$user_data->image;      
        } 
        $response['id']=$games->id;    
        $response['name']=$user_data->name;
        $response['user_id']=$user_data->id;
        $response['event_date']=!empty($game_data->event_date)?$game_data->event_date:"";
        $response['event_time']=date('h:i A', strtotime($game_data->event_time));    
        $response['join_status']=!empty($games->status)?$games->status:"";
        $response['createdon']=$games->created_at;      
        $temp['game_request'][]=$response;      

       }

        $game_friend_res = DB::table('join_games')->where(['status'=>'Approved','game_host_id'=>$user_id])->get();
       foreach($game_friend_res as $games_friend){
 
        $user_data = User::where('id',$games_friend->user_id)->first();   
         //print_r($user_data);die;

       if(empty($user_data['image']))
        {
         $response1['image'] = "https://mobulous.app/fametales/public/img/user_signup.png";
        } else {
         $response1['image'] = url('/public/').'/'.$user_data->image;      
        } 
        $response1['id']=$games_friend->id;    
        $response1['name']=$user_data->name;
        $response1['user_id']=$user_data->id;
        $response1['join_status']=!empty($games_friend->status)?$games_friend->status:"";
        $response1['createdon']=$games_friend->created_at;      
        $temp1['game_friend'][]=$response1;      

       } 
      if(empty($temp)){
            $temp['game_request'] = array();
        }               
       if(empty($temp1)){
            $temp1['game_friend'] = array();
        }
        $request_data = array_merge($temp,$temp1);

       return $this->sendResponse($request_data, 'Game Request.',$request->path());    
        
    
    }


  public function RequestAcceptReject(Request $request,$id=null)
    {

                $input = $request->all();
                $val_arr = [
                    'token'=>'required',
                    'id'=>'required',
                     'status'=>'required',
                ];
                 
                $validator = Validator::make($input, $val_arr);
                if($validator->fails()){
                  return $this->sendError($request->path(),$validator->errors()->first());       
                  }

     
                 $user_id     = $this->checkToken($input['token']);   
                 $id          = $input['id'];
                 $status      = $input['status'];

                // echo $user_id;
                // die();

                 $request_data = JoinGame::find($id);
                 $request_data->status = $input['status'];
                 $request_data->save();
                 $return_array = $request_data->toArray();
                

           //retrive id 
                 $user = DB::table('join_games')->where('id',$id)->first();
                $user1 = DB::table('create_games')->where('id',$user->game_id)->first();
            
            //echo $user->user_id;
           // echo $status;
                   
            $show_value=0;
            /// GAME SEATS DECRESS///
            if($status=='Approved'){

               $update_seates= $user1->seats-1;
                if($update_seates >0)
                 {

                 DB::table('create_games')
                ->where('id', $user->game_id)
                ->update(['seats' => $update_seates]);

                 }
                 }

                 if($status=='Decline')
                 {

                 $data1= DB::table('users')
                ->where('id',$user->user_id)
                ->update(['show_data' => $status]);

                 $data1= DB::table('create_games')
                ->where('id',$user->game_id)
                ->update(['show_data'=>$show_value]);

                 }
                

                 ///--END///

                //  $return_array['data'] = "message";
                // return response()->json($return_array, 200);


                return $this->sendResponse($return_array, 'Request Updated successfully',$request->path());

    }



     
 public function JoinedGames(Request $request)           
    {
        $input = $request->all();

        $val_arr = [
            'token' => 'required',
              
        ];

        $validator = Validator::make($input, $val_arr);

        if($validator->fails()){
            return $this->sendError($request->path(),$validator->errors()->first());       
        }
       //print_r($input); exit;
        $user_id = $this->checkToken($input['token']);  
        
       $game_req = DB::table('join_games')->where(['user_id'=>$user_id,'status'=>'Approved'])->get();
        //dd($game_req);
       //echo "<pre>"; print_r($game);die;    
       foreach($game_req as $games){
       //echo $games->game_id; exit;
 
        $user_data = User::where('id',$games->game_host_id)->first();
        $game_data = CreateGame::where('id',$games->game_id)->first();   
        //print_r($game_data);die;
         //dd($game_data); exit;
       if(empty($user_data['image']))
        {
         $response['image'] = "https://mobulous.app/fametales/public/img/user_signup.png";
        } else {
         $response['image'] = url('/public/').'/'.$user_data->image;      
        } 
        $response['id']=$games->id;    
        $response['name']=$user_data->name; 
        $response['game_id']=!empty($game_data->id)?$game_data->id:""; 
        $response['event_date']=!empty($game_data->event_date)?$game_data->event_date:"";
        $response['event_time']=date('h:i A', strtotime($game_data->event_time));
        $response['seats']=!empty($game_data->seats)?$game_data->seats:"";
        $response['home_number']=!empty($game_data->home_number)?$game_data->home_number:"";
        $response['street_number']=!empty($game_data->street_number)?$game_data->street_number:"";
        $response['zip_code']=!empty($game_data->zip_code)?$game_data->zip_code:"";
        $response['event_description']=!empty($game_data->event_description)?$game_data->event_description:"";
        $response['host_id']=$user_data->id;
        $response['user_id']=$games->user_id;    
        $response['join_status']=!empty($games->status)?$games->status:"";
        $response['createdon']=$games->created_at;      
        $temp['joined_games'][]=$response;      

       }

       
      if(empty($temp)){
            $temp['joined_games'] = array();
        }               
       

       return $this->sendResponse($temp, 'Joined Games.',$request->path());    
        
    
    }

  public function JoinedGamesParticipant(Request $request)           
    {
     
        $input = $request->all();
        $temp=array();
        $val_arr = [
            'token' => 'required',
            'game_id' => 'required',
              
        ];

 
       $validator = Validator::make($input, $val_arr);
       if($validator->fails()){
            return $this->sendError($request->path(),$validator->errors()->first());       
        }
       //print_r($input); exit;
        $user_id = $this->checkToken($input['token']);  
        
        $game_req = DB::select("SELECT * FROM join_games WHERE status='Approved' AND game_id= '$input[game_id]'");
          
      foreach($game_req as $games){
        $user_data = User::where('id',$games->user_id)->first();
        $game_data = CreateGame::where('id',$games->game_id)->first();  

        // dd($user_data);
        
      if(empty($user_data['image']))
        {
         $response['image'] = "https://mobulous.app/fametales/public/img/user_signup.png";
        } 
        else{
         $response['image'] = url('/public/').'/'.$user_data->image;      
        } 
        $response['id']=$games->id;
        $response['user_id']=$games->user_id;
        $response['host_id']=$games->game_host_id;    
        $response['name']=$user_data->name;
        $response['player_type']="player";
        $response['createdon']=$games->created_at;     
        $temp['JoinedGamesParticipant'][]=$response;

       }
        
        $host_data = User::where('id',$temp['JoinedGamesParticipant'][0]['host_id'])->first();
        $response1['image'] = url('/public/').'/'.$host_data->image;
        $response1['id']=(string)$host_data->id;
        $response1['user_id']=(string)$host_data->id;
        $response1['host_id']=(string)$host_data->id;    
        $response1['name']=$host_data->name;
        $response1['player_type']="host";
        $response1['createdon']=date("Y-m-d h:i:s",strtotime($host_data->created_at));
        $temp['JoinedGamesParticipant'][] = $response1;
        return $this->sendResponse($temp, 'JoinedGamesParticipant Games.',$request->path());    
        

    }




 public function HostRating(Request $request,$id=null)
    {
                $input = $request->all();
                $val_arr = [
                    'token'=>'required',
                    'host_id'=>'required',
                    'rate'=>'required',
                    'review_msg'=>'required',   
                ];

                $validator = Validator::make($input, $val_arr);
                if($validator->fails()){
                  return $this->sendError($request->path(),$validator->errors()->first());       
                  }

                 $user_id            = $this->checkToken($input['token']);
                 $host_id            = $input['host_id'];
                 $rate               = $input['rate'];
                 
                 $request_data = new HostRate;
                 $request_data->user_id = $user_id;
                 $request_data->host_id = $input['host_id'];
                 $request_data->rate    = $input['rate'];
                 $request_data->review_msg    = $input['review_msg'];
                 $request_data->save();
                 $return_array = $request_data->toArray();

                 // print_r($rate);
                 // die();
 

                return $this->sendResponse($return_array, 'Host Ratedd successfully',$request->path());

    }


    

 public function ScreenAdds(Request $request)           
    {
        $input = $request->all();

        $val_arr = [
            'token' => 'required',
              
        ];

        $validator = Validator::make($input, $val_arr);

       if($validator->fails()){
            return $this->sendError($request->path(),$validator->errors()->first());       
        }
       //print_r($input); exit;
       $user_id = $this->checkToken($input['token']);
        
       $games = DB::table('screen_adds')->get();
      if(empty($games[0]->image))
        {
         $response['image'] = "https://mobulous.app/fametales/public/img/user_signup.png";
        } else {
         $response['image'] = url('/public/').'/adds/'.$games[0]->image;      
        } 
        $response['id']=$games[0]->id;    
        $response['created_at']=$games[0]->created_at;      
        $temp=$response;      
            
      
       return $this->sendResponse($temp, 'Screen Adds.',$request->path());    
        
    
    }

 public function ReportUserdetail(Request $request,$id=null)
    {
                $input = $request->all();

                $val_arr = [
                    'token'=>'required',
                    'id'=>'required',
                    
                ];
                
               $validator = Validator::make($input, $val_arr);
                if($validator->fails()){
                  return $this->sendError($request->path(),$validator->errors()->first());      
                  }

                 $user_id      = $this->checkToken($input['token']);
                 $id      = $input['id'];
                 $user_data = User::where('id',$id)->first();
                 
                 //$request_data = HostRate::find($id);
              if(empty($user_data['image']))
                {
                 $response['image'] = "https://mobulous.app/fametales/public/img/user_signup.png";
                } else {
                 $response['image'] = url('/public/').'/'.$user_data['image'];      
                } 
                 $response['id'] = $user_data['id'];
                 $response['name'] = $user_data['name'];
                 $response['host_rank'] = !empty($user_data['host_rank'])?$user_data['host_rank']:"0";
                 $response['user_rank'] = !empty($user_data['user_rank'])?$user_data['user_rank']:"0";
                 $response['zip_code'] = $user_data['zip_code'];
                 $response['created_at'] = date("Y-m-d h:i:s",strtotime($user_data['created_at']));
                 $temp = $response; 
                return $this->sendResponse($temp, 'Report User Detail',$request->path());


    }



 public function ReportUser(Request $request,$id=null)
    {
                $input = $request->all();

                $val_arr = [
                    'token'=>'required',
                    'reported_to'=>'required',
                    'reason'=>'required',
                    
                ];
                
                $validator = Validator::make($input, $val_arr);
                if($validator->fails()){
                  return $this->sendError($request->path(),$validator->errors()->first());       
                  }

                 $user_id      = $this->checkToken($input['token']);
                 $reported_to    = $input['reported_to'];
                 $reason       = $input['reason'];
                 

                 $request_data = new ReportUser;
                 $request_data->user_id   = $user_id;
                 $request_data->reported_to = $input['reported_to'];
                 $request_data->reason    = $input['reason']; 
                 $request_data->save(); 

                 $return_array = $request_data->toArray();
                return $this->sendResponse($return_array, 'User Report successfully',$request->path());

    }



   
}



