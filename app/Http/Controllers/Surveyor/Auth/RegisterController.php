<?php

namespace App\Http\Controllers\Surveyor\Auth;

use App\Models\GeneralSetting;
use App\Http\Controllers\Controller;
use App\Models\Surveyor;
use App\Models\SurveyorLogin;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/surveyor/dashboard';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('surveyor.guest');
        $this->middleware('surveyorRegStatus');
    }


    /**
     * Show the application registration form.
     *
     * @return \Illuminate\View\View
     */

    public function showRegistrationForm()
    {
        $page_title = "Sign Up";
        $info = json_decode(json_encode(getIpInfo()), true);
        $country_code = @implode(',', $info['code']);
        return view('surveyor.auth.register', compact('page_title','country_code'));
    }


    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        $validate = Validator::make($data, [
            'firstname' => 'sometimes|required|string|max:50',
            'lastname' => 'sometimes|required|string|max:50',
            'email' => 'required|string|email|max:90|unique:surveyors',
            'mobile' => 'required|string|max:50|unique:surveyors',
            'password' => 'required|string|min:6|max:255|confirmed',
            'username' => 'required|alpha_num|unique:surveyors|min:6|max:50',
            'captcha' => 'sometimes|required',
            'country_code' => 'required'
        ]);

        return $validate;
    }


    /**
     * Handle a registration request for the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */

    public function register(Request $request)
    {
        $this->validator($request->all())->validate();

        $exist = Surveyor::where('mobile',$request->country_code.$request->mobile)->first();
        if ($exist) {
            $notify[] = ['error', 'Mobile number already exist'];
            return back()->withNotify($notify)->withInput();
        }

        if (isset($request->captcha)) {
            if (!captchaVerify($request->captcha, $request->captcha_secret)) {
                $notify[] = ['error', "Invalid Captcha"];
                return back()->withNotify($notify)->withInput();
            }
        }

        event(new Registered($surveyor = $this->create($request->all())));

        $this->guard()->login($surveyor);

        return $this->registered($request, $surveyor)
            ?: redirect($this->redirectPath());

    }


    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array $data
     * @return \App\Surveyor
     */
    protected function create(array $data)
    {

        $gnl = GeneralSetting::first();


        $referBy = session()->get('reference');
        if ($referBy != null) {
            $referSurveyor = Surveyor::where('username', $referBy)->first();
        } else {
            $referSurveyor = null;
        }

        //User Create
        $surveyor = new Surveyor();
        $surveyor->firstname = isset($data['firstname']) ? $data['firstname'] : null;
        $surveyor->lastname = isset($data['lastname']) ? $data['lastname'] : null;
        $surveyor->email = strtolower(trim($data['email']));
        $surveyor->password = Hash::make($data['password']);
        $surveyor->username = trim($data['username']);
        $surveyor->ref_by = ($referSurveyor != null) ? $referSurveyor->id : null;
        $surveyor->mobile = $data['country_code'].$data['mobile'];
        $surveyor->address = [
            'address' => '',
            'state' => '',
            'zip' => '',
            'country' => isset($data['country']) ? $data['country'] : null,
            'city' => ''
        ];
        $surveyor->status = 1;
        $surveyor->ev = $gnl->ev ? 0 : 1;
        $surveyor->sv = $gnl->sv ? 0 : 1;
        $surveyor->ts = 0;
        $surveyor->tv = 1;
        $surveyor->save();



        //Login Log Create
        $ip = $_SERVER["REMOTE_ADDR"];
        $exist = SurveyorLogin::where('surveyor_ip',$ip)->first();
        $surveyorLogin = new SurveyorLogin();

        //Check exist or not
        if ($exist) {
            $surveyorLogin->longitude =  $exist->longitude;
            $surveyorLogin->latitude =  $exist->latitude;
            $surveyorLogin->location =  $exist->location;
            $surveyorLogin->country_code = $exist->country_code;
            $surveyorLogin->country =  $exist->country;
        }else{
            $info = json_decode(json_encode(getIpInfo()), true);
            $surveyorLogin->longitude =  @implode(',',$info['long']);
            $surveyorLogin->latitude =  @implode(',',$info['lat']);
            $surveyorLogin->location =  @implode(',',$info['city']) . (" - ". @implode(',',$info['area']) ."- ") . @implode(',',$info['country']) . (" - ". @implode(',',$info['code']) . " ");
            $surveyorLogin->country_code = @implode(',',$info['code']);
            $surveyorLogin->country =  @implode(',', $info['country']);
        }

        $surveyorAgent = osBrowser();
        $surveyorLogin->surveyor_id = $surveyor->id;
        $surveyorLogin->surveyor_ip =  $ip;

        $surveyorLogin->browser = @$surveyorAgent['browser'];
        $surveyorLogin->os = @$surveyorAgent['os_platform'];
        $surveyorLogin->save();


        return $surveyor;
    }

        /**
     * Get the guard to be used during registration.
     *
     * @return \Illuminate\Contracts\Auth\StatefulGuard
     */

    protected function guard()
    {
        return Auth::guard('surveyor');
    }

    /**
     * The user has been registered.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $surveyor
     * @return mixed
     */

    public function registered()
    {
        return redirect()->route('surveyor.dashboard');
    }

}
