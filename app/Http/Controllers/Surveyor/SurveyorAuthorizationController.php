<?php

namespace App\Http\Controllers\Surveyor;

use App\Lib\GoogleAuthenticator;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class SurveyorAuthorizationController extends Controller
{
    public function __construct()
    {
        return $this->activeTemplate = activeTemplate();
    }
    public function checkValidCode($surveyor, $code, $add_min = 10000)
    {
        if (!$code) return false;
        if (!$surveyor->ver_code_send_at) return false;
        if ($surveyor->ver_code_send_at->addMinutes($add_min) < Carbon::now()) return false;
        if ($surveyor->ver_code !== $code) return false;
        return true;
    }


    public function authorizeForm()
    {

        if (auth()->guard('surveyor')->check()) {
            $surveyor = Auth::guard('surveyor')->user();
            if (!$surveyor->status) {
                auth()->guard('surveyor')->logout();
            }elseif (!$surveyor->ev) {
                if (!$this->checkValidCode($surveyor, $surveyor->ver_code)) {
                    $surveyor->ver_code = verificationCode(6);
                    $surveyor->ver_code_send_at = Carbon::now();
                    $surveyor->save();
                    send_email($surveyor, 'EVER_CODE', [
                        'code' => $surveyor->ver_code
                    ]);
                }
                $page_title = 'Email verification form';
                return view('surveyor.auth.authorization.email', compact('surveyor', 'page_title'));
            }elseif (!$surveyor->sv) {
                if (!$this->checkValidCode($surveyor, $surveyor->ver_code)) {
                    $surveyor->ver_code = verificationCode(6);
                    $surveyor->ver_code_send_at = Carbon::now();
                    $surveyor->save();
                    send_sms($surveyor, 'SVER_CODE', [
                        'code' => $surveyor->ver_code
                    ]);
                }
                $page_title = 'SMS verification form';
                return view('surveyor.auth.authorization.sms', compact('surveyor', 'page_title'));
            }elseif (!$surveyor->tv) {
                $page_title = 'Google Authenticator';
                return view('surveyor.auth.authorization.2fa', compact('surveyor', 'page_title'));
            }else{
                return redirect()->route('surveyor.dashboard');
            }

        }

        return redirect()->route('surveyor.login');
    }

    public function sendVerifyCode(Request $request)
    {
        $surveyor = Auth::guard('surveyor')->user();


        if ($this->checkValidCode($surveyor, $surveyor->ver_code, 2)) {
            $target_time = $surveyor->ver_code_send_at->addMinutes(2)->timestamp;
            $delay = $target_time - time();
            throw ValidationException::withMessages(['resend' => 'Please Try after ' . $delay . ' Seconds']);
        }
        if (!$this->checkValidCode($surveyor, $surveyor->ver_code)) {
            $surveyor->ver_code = verificationCode(6);
            $surveyor->ver_code_send_at = Carbon::now();
            $surveyor->save();
        } else {
            $surveyor->ver_code = $surveyor->ver_code;
            $surveyor->ver_code_send_at = Carbon::now();
            $surveyor->save();
        }



        if ($request->type === 'email') {
            send_email($surveyor, 'EVER_CODE',[
                'code' => $surveyor->ver_code
            ]);

            $notify[] = ['success', 'Email verification code sent successfully'];
            return back()->withNotify($notify);
        } elseif ($request->type === 'phone') {
            send_sms($surveyor, 'SVER_CODE', [
                'code' => $surveyor->ver_code
            ]);
            $notify[] = ['success', 'SMS verification code sent successfully'];
            return back()->withNotify($notify);
        } else {
            throw ValidationException::withMessages(['resend' => 'Sending Failed']);
        }
    }

    public function emailVerification(Request $request)
    {
        $rules = [
            'email_verified_code.*' => 'required',
        ];
        $msg = [
            'email_verified_code.*.required' => 'Email verification code is required',
        ];
        $validate = $request->validate($rules, $msg);


        $email_verified_code =  str_replace(',','',implode(',',$request->email_verified_code));

        $surveyor = Auth::guard('surveyor')->user();

        if ($this->checkValidCode($surveyor, $email_verified_code)) {
            $surveyor->ev = 1;
            $surveyor->ver_code = null;
            $surveyor->ver_code_send_at = null;
            $surveyor->save();
            return redirect()->intended(route('surveyor.dashboard'));
        }
        throw ValidationException::withMessages(['email_verified_code' => 'Verification code didn\'t match!']);
    }

    public function smsVerification(Request $request)
    {
        $request->validate([
            'sms_verified_code.*' => 'required',
        ], [
            'sms_verified_code.*.required' => 'SMS verification code is required',
        ]);


        $sms_verified_code =  str_replace(',','',implode(',',$request->sms_verified_code));

        $surveyor = Auth::guard('surveyor')->user();
        if ($this->checkValidCode($surveyor, $sms_verified_code)) {
            $surveyor->sv = 1;
            $surveyor->ver_code = null;
            $surveyor->ver_code_send_at = null;
            $surveyor->save();
            return redirect()->intended(route('surveyor.dashboard'));
        }
        throw ValidationException::withMessages(['sms_verified_code' => 'Verification code didn\'t match!']);
    }
    public function g2faVerification(Request $request)
    {
        $surveyor = Auth::guard('surveyor')->user();

        $this->validate(
            $request, [
            'code.*' => 'required',
        ], [
            'code.*.required' => 'Code is required',
        ]);

        $ga = new GoogleAuthenticator();


        $code =  str_replace(',','',implode(',',$request->code));

        $secret = $surveyor->tsc;
        $oneCode = $ga->getCode($secret);
        $surveyorCode = $code;
        if ($oneCode == $surveyorCode) {
            $surveyor->tv = 1;
            $surveyor->save();
            return redirect()->route('surveyor.dashboard');
        } else {
            $notify[] = ['error', 'Wrong Verification Code'];
            return back()->withNotify($notify);
        }
    }

}
