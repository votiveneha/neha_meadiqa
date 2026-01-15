<?php

namespace App\Http\Controllers\agencies;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Services\User\AuthServices;
use App\Repository\Eloquent\SpecialityRepository;
use App\Models\SpecialityModel;
use App\Models\PractitionerTypeModel;
use App\Models\WorkPreferModel;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Mail;
use Validator;
use DB;
use URL;
use Session;

class HomeController extends Controller
{
 protected $authServices;
 protected $specialityRepository;
      public function __construct(AuthServices $authServices, SpecialityRepository $specialityRepository){
        $this->authServices = $authServices;
        $this->specialityRepository = $specialityRepository;
    }
    public function index($message = '')
    {
         if (!Auth::guard('nurse_middle')->check()) {
            $title = "Login";
            $practitioner_data = SpecialityModel::where("status",'1')->get();
            //print_r($practitioner_data);die;
            $speciality_data = PractitionerTypeModel::where("status",'1')->get();
            $work_preferences_data = WorkPreferModel::get();
            return view('nurse.home', compact( 'message','practitioner_data','speciality_data','work_preferences_data'));
        } else {
            

            return redirect()->route('nurse.dashboard');
        }
        
    }
    public function index_main($message = '')
    {
         if (!Auth::guard('nurse_middle')->check()) {
            $title = "Login";
            $practitioner_data = SpecialityModel::where("status",'1')->get();
            //print_r($practitioner_data);die;
            $speciality_data = PractitionerTypeModel::where("status",'1')->get();
            $work_preferences_data = WorkPreferModel::get();
           return view('nurse.agencies', compact( 'message','practitioner_data','speciality_data','work_preferences_data'));
        } else {
            

            return redirect()->route('nurse.dashboard');
        }
        
    }
    public function registraion($message = '')
    {
         if (!Auth::guard('nurse_middle')->check()) {
            $practitioner_data = SpecialityModel::where("status",'1')->get();
            //print_r($practitioner_data);die;
            $speciality_data = PractitionerTypeModel::where("status",'1')->get();
            $work_preferences_data = WorkPreferModel::get();
            $title = "Login";
           return view('agencies.agencies-registraion', compact( 'message','practitioner_data','speciality_data','work_preferences_data'));
        } else {
            return redirect()->route('nurse.dashboard');
        }
        
    }

    public function login($message = '')
    {

        $practitioner_data = SpecialityModel::where("parent",0)->get();
        $speciality_data = PractitionerTypeModel::where("parent",0)->get();
        $work_preferences_data = WorkPreferModel::where("sub_env_id",0)->where("sub_envp_id",0)->get();
        $title = "Login";
        return view('nurse.login', compact('title', 'message','practitioner_data','speciality_data','work_preferences_data'));
        
    }

    public function userloginAction(Request $request)
    {
        
        if (Auth::guard('agencies')->attempt(['email' => $request->email, 'password' => $request->password, 'role' => 'agency'])) {
            if (isset($request->remember_me) && !empty($request->remember_me)) {
                setcookie("email", $request->email, time() + 3600);
                setcookie("password", $request->password, time() + 3600);
            } else {
                setcookie("email", "");
                setcookie("password", "");
            }
            return redirect('/agencies/my-profile')->with('success', 'You are Logged in sucessfully.');
        } else {
            return back()->with('error', 'Invalid login details.');
        }
    }

    public function agenciesRegistration(Request $request)
    {
        
        $fullname = $request->fullname;
        $emailaddress = $request->emailaddress;
        
        $password = $request->password;
        
        $user_data = User::where("email",$emailaddress)->first(); 

        if(empty($user_data)){
            $user = new User();
            $user->name = $fullname;
            $user->email = $emailaddress;
            $user->role = "agency";
            $user->password = Hash::make($password);
            $run = $user->save();
            Auth::guard('agencies')->login($user);
            Auth::login($user);
        }else{
            $run = 0;
        }

        if ($run) {
            Session::put('user_id', $user->id);
            $json['status'] = 1;
            $json['message'] = 'Congratulations! Your registration was successful. Please check your email; we have sent you a verification email to your registered address!';
        }else{
            $json['status'] = 0;
            $json['message'] = 'Email is already registered.!';
        }
        echo json_encode($json);
    }

    public function logout(Request $request)
    {
        Auth::guard('agencies')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('agencies');
    }

    public function emailVerificationPending()
    {

        if (Auth::guard('agencies')->user()) {

            if (Auth::guard('agencies')->user()->emailVerified == 1 &&  Auth::guard('agencies')->user()->user_stage == 1 && Auth::guard('agencies')->user()->type == 1) {

                return redirect()->route('agencies.profile-under-reviewed');
            } elseif (Auth::guard('agencies')->user()->emailVerified == 1 &&  Auth::guard('agencies')->user()->type == 0) {
                return redirect()->route('agencies.dashboard');
            } else {
                $title = "";
                $message = "";

                return view('auth.email-verification-pending', compact('title', 'message'));
            }
        } else if (Session::get('user_id')) {
            $user_id = Session::get('user_id');

            $title = 'sa';
            $message = "";
            $r = User::where("id", $user_id)->first();
            Auth::guard('agencies')->attempt(['email' => $r->email, 'password' => $r->ps]);
            // return redirect('/nurse/my-profile?page=my_profile');
            return redirect('/nurse/dashboard');
            return view('auth.email-verification-pending', compact('title', 'message'));
        } else {
            $title = "s";
            return redirect()->route('agencies.login');
        }
    }

    public function manage_profile(){
        return view('agencies.profile');
    }

    public function profileUnderReviewed()
    {
        // die();

        if (Auth::guard('healthcare_facilities')->user()) {
            if (Auth::guard('healthcare_facilities')->user()->user_stage == 2) {

                return redirect()->route('nurse.dashboard');
            } else {
                $title = "";
                $message = "";
                return view('auth.profile-under-reviewed', compact('title', 'message'));
            }
        } else {

            return redirect()->route('medical-facilities.login');
        }
    }
    
}