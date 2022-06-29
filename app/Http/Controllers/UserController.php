<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\GeneralSetting;
use App\Lib\GoogleAuthenticator;
use App\Models\Question;
use App\Models\Survey;
use App\Models\Surveyor;
use App\Models\Transaction;
use App\Models\WithdrawMethod;
use App\Models\Withdrawal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Image;
use Validator;

class UserController extends Controller
{
    public function __construct()
    {
        $this->activeTemplate = activeTemplate();
    }

    public function home()
    {
        $page_title = 'Dashboard';
        $user = auth()->user();
        $totalWithdraw = Withdrawal::where('user_id',$user->id)->where('status',1)->sum('amount');
        $totalTransaction = Transaction::where('user_id',$user->id)->count();

        $withdraw['month'] = collect([]);
        $withdraw['amount'] = collect([]);

        $withdraw_chart = Withdrawal::where('user_id',$user->id)->where('status',1)->whereYear('created_at', '=', date('Y'))->orderBy('created_at')->groupBy(DB::Raw("MONTH(created_at)"))->get();

        $withdraw_chart_data = $withdraw_chart->map(function ($query) use ($withdraw) {
            $withdraw['month'] = $query->created_at->format('F');
            $withdraw['amount'] = $query->where('status',1)->whereMonth('created_at',$query->created_at)->sum('amount');
            return $withdraw;
        });

        return view($this->activeTemplate . 'user.dashboard', compact('page_title','user','totalWithdraw','totalTransaction','withdraw_chart_data'));
    }

    public function profile()
    {
        $data['page_title'] = "Profile Setting";
        $data['user'] = Auth::user();
        return view($this->activeTemplate. 'user.profile-setting', $data);
    }

    public function submitProfile(Request $request)
    {
        $request->validate([
            'firstname' => 'required|string|max:50',
            'lastname' => 'required|string|max:50',
            'address' => "sometimes|required|max:80",
            'state' => 'sometimes|required|max:80',
            'zip' => 'sometimes|required|max:40',
            'city' => 'sometimes|required|max:50',
            'image' => 'mimes:png,jpg,jpeg'
        ],[
            'firstname.required'=>'First Name Field is required',
            'lastname.required'=>'Last Name Field is required'
        ]);

        $user = Auth::user();


        $in['firstname'] = $request->firstname;
        $in['lastname'] = $request->lastname;

        $in['address'] = [
            'address' => $request->address,
            'state' => $request->state,
            'zip' => $request->zip,
            'country' => $user->address->country,
            'city' => $request->city,
        ];


        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = time() . '_' . $user->username . '.jpg';
            $location = 'assets/images/user/profile/' . $filename;
            $in['image'] = $filename;

            $path = './assets/images/user/profile/';
            $link = $path . $user->image;
            if (file_exists($link)) {
                @unlink($link);
            }
            $size = imagePath()['profile']['user']['size'];
            $image = Image::make($image);
            $size = explode('x', strtolower($size));
            $image->resize($size[0], $size[1]);
            $image->save($location);
        }

        $user->fill($in)->save();
        $notify[] = ['success', 'Profile Updated successfully.'];
        return back()->withNotify($notify);
    }

    public function changePassword()
    {
        $data['page_title'] = "CHANGE PASSWORD";
        return view($this->activeTemplate . 'user.password', $data);
    }

    public function submitPassword(Request $request)
    {

        $this->validate($request, [
            'current_password' => 'required',
            'password' => 'required|min:5|confirmed'
        ]);
        try {
            $user = auth()->user();
            if (Hash::check($request->current_password, $user->password)) {
                $password = Hash::make($request->password);
                $user->password = $password;
                $user->save();
                $notify[] = ['success', 'Password Changes successfully.'];
                return back()->withNotify($notify);
            } else {
                $notify[] = ['error', 'Current password not match.'];
                return back()->withNotify($notify);
            }
        } catch (\PDOException $e) {
            $notify[] = ['error', $e->getMessage()];
            return back()->withNotify($notify);
        }
    }

    /*
     * Deposit History
     */
    public function depositHistory()
    {
        $page_title = 'Deposit History';
        $empty_message = 'No history found.';
        $logs = auth()->user()->deposits()->with(['gateway'])->latest()->paginate(getPaginate());
        return view($this->activeTemplate . 'user.deposit_history', compact('page_title', 'empty_message', 'logs'));
    }

    /*
     * Withdraw Operation
     */

    public function withdrawMoney()
    {
        $data['withdrawMethod'] = WithdrawMethod::whereStatus(1)->get();
        $data['page_title'] = "Withdraw Money";
        return view(activeTemplate() . 'user.withdraw.methods', $data);
    }

    public function withdrawStore(Request $request)
    {
        $this->validate($request, [
            'method_code' => 'required',
            'amount' => 'required|numeric'
        ]);
        $method = WithdrawMethod::where('id', $request->method_code)->where('status', 1)->firstOrFail();
        $user = auth()->user();
        if ($request->amount < $method->min_limit) {
            $notify[] = ['error', 'Your Requested Amount is Smaller Than Minimum Amount.'];
            return back()->withNotify($notify);
        }
        if ($request->amount > $method->max_limit) {
            $notify[] = ['error', 'Your Requested Amount is Larger Than Maximum Amount.'];
            return back()->withNotify($notify);
        }

        if ($request->amount > $user->balance) {
            $notify[] = ['error', 'Your do not have Sufficient Balance For Withdraw.'];
            return back()->withNotify($notify);
        }


        $charge = $method->fixed_charge + ($request->amount * $method->percent_charge / 100);
        $afterCharge = $request->amount - $charge;
        $finalAmount = getAmount($afterCharge * $method->rate);

        $withdraw = new Withdrawal();
        $withdraw->method_id = $method->id; // wallet method ID
        $withdraw->user_id = $user->id;
        $withdraw->amount = getAmount($request->amount);
        $withdraw->currency = $method->currency;
        $withdraw->rate = $method->rate;
        $withdraw->charge = $charge;
        $withdraw->final_amount = $finalAmount;
        $withdraw->after_charge = $afterCharge;
        $withdraw->trx = getTrx();
        $withdraw->save();
        session()->put('wtrx', $withdraw->trx);
        return redirect()->route('user.withdraw.preview');
    }

    public function withdrawPreview()
    {
        $data['withdraw'] = Withdrawal::with('method','user')->where('trx', session()->get('wtrx'))->where('status', 0)->latest()->firstOrFail();
        $data['page_title'] = "Withdraw Preview";
        return view($this->activeTemplate . 'user.withdraw.preview', $data);
    }


    public function withdrawSubmit(Request $request)
    {
        $general = GeneralSetting::first();
        $withdraw = Withdrawal::with('method','user')->where('trx', session()->get('wtrx'))->where('status', 0)->latest()->firstOrFail();

        $rules = [];
        $inputField = [];
        if ($withdraw->method->user_data != null) {
            foreach ($withdraw->method->user_data as $key => $cus) {
                $rules[$key] = [$cus->validation];
                if ($cus->type == 'file') {
                    array_push($rules[$key], 'image');
                    array_push($rules[$key], 'mimes:jpeg,jpg,png');
                    array_push($rules[$key], 'max:2048');
                }
                if ($cus->type == 'text') {
                    array_push($rules[$key], 'max:191');
                }
                if ($cus->type == 'textarea') {
                    array_push($rules[$key], 'max:300');
                }
                $inputField[] = $key;
            }
        }
        $this->validate($request, $rules);
        $user = auth()->user();

        if (getAmount($withdraw->amount) > $user->balance) {
            $notify[] = ['error', 'Your Request Amount is Larger Then Your Current Balance.'];
            return back()->withNotify($notify);
        }

        $directory = date("Y")."/".date("m")."/".date("d");
        $path = imagePath()['verify']['withdraw']['path'].'/'.$directory;
        $collection = collect($request);
        $reqField = [];
        if ($withdraw->method->user_data != null) {
            foreach ($collection as $k => $v) {
                foreach ($withdraw->method->user_data as $inKey => $inVal) {
                    if ($k != $inKey) {
                        continue;
                    } else {
                        if ($inVal->type == 'file') {
                            if ($request->hasFile($inKey)) {
                                try {
                                    $reqField[$inKey] = [
                                        'field_name' => $directory.'/'.uploadImage($request[$inKey], $path),
                                        'type' => $inVal->type,
                                    ];
                                } catch (\Exception $exp) {
                                    $notify[] = ['error', 'Could not upload your ' . $request[$inKey]];
                                    return back()->withNotify($notify)->withInput();
                                }
                            }
                        } else {
                            $reqField[$inKey] = $v;
                            $reqField[$inKey] = [
                                'field_name' => $v,
                                'type' => $inVal->type,
                            ];
                        }
                    }
                }
            }
            $withdraw['withdraw_information'] = $reqField;
        } else {
            $withdraw['withdraw_information'] = null;
        }


        $withdraw->status = 2;
        $withdraw->save();
        $user->balance  -=  $withdraw->amount;
        $user->save();

        $transaction = new Transaction();
        $transaction->user_id = $withdraw->user_id;
        $transaction->amount = getAmount($withdraw->amount);
        $transaction->post_balance = getAmount($user->balance);
        $transaction->charge = getAmount($withdraw->charge);
        $transaction->trx_type = '-';
        $transaction->details = getAmount($withdraw->final_amount) . ' ' . $withdraw->currency . ' Withdraw Via ' . $withdraw->method->name;
        $transaction->trx =  $withdraw->trx;
        $transaction->save();

        notify($user, 'WITHDRAW_REQUEST', [
            'method_name' => $withdraw->method->name,
            'method_currency' => $withdraw->currency,
            'method_amount' => getAmount($withdraw->final_amount),
            'amount' => getAmount($withdraw->amount),
            'charge' => getAmount($withdraw->charge),
            'currency' => $general->cur_text,
            'rate' => getAmount($withdraw->rate),
            'trx' => $withdraw->trx,
            'post_balance' => getAmount($user->balance),
            'delay' => $withdraw->method->delay
        ]);

        $notify[] = ['success', 'Withdraw Request Successfully Send'];
        return redirect()->route('user.withdraw.history')->withNotify($notify);
    }

    public function withdrawLog()
    {
        $data['page_title'] = "Withdraw Log";
        $data['withdraws'] = Withdrawal::where('user_id', Auth::id())->where('status', '!=', 0)->with('method')->latest()->paginate(getPaginate());
        $data['empty_message'] = "No Data Found!";
        return view($this->activeTemplate.'user.withdraw.log', $data);
    }

    public function transaction()
    {
        $page_title = 'Successful Transaction Logs';
        $transactions = Transaction::where('user_id',Auth::id())->orderBy('id','desc')->paginate(getPaginate());
        $empty_message = 'No transactions.';
        return view($this->activeTemplate.'user.transaction', compact('page_title', 'transactions', 'empty_message'));
    }


    public function show2faForm()
    {
        $gnl = GeneralSetting::first();
        $ga = new GoogleAuthenticator();
        $user = auth()->user();
        $secret = $ga->createSecret();
        $qrCodeUrl = $ga->getQRCodeGoogleUrl($user->username . '@' . $gnl->sitename, $secret);
        $prevcode = $user->tsc;
        $prevqr = $ga->getQRCodeGoogleUrl($user->username . '@' . $gnl->sitename, $prevcode);
        $page_title = 'Two Factor';
        return view($this->activeTemplate.'user.twofactor', compact('page_title', 'secret', 'qrCodeUrl', 'prevcode', 'prevqr'));
    }

    public function create2fa(Request $request)
    {
        $user = auth()->user();
        $this->validate($request, [
            'key' => 'required',
            'code' => 'required',
        ]);

        $ga = new GoogleAuthenticator();
        $secret = $request->key;
        $oneCode = $ga->getCode($secret);

        if ($oneCode === $request->code) {
            $user->tsc = $request->key;
            $user->ts = 1;
            $user->tv = 1;
            $user->save();


            $userAgent = getIpInfo();
            $osBrowser = osBrowser();
            notify($user, '2FA_ENABLE', [
                'operating_system' => @$osBrowser['os_platform'],
                'browser' => @$osBrowser['browser'],
                'ip' => @$userAgent['ip'],
                'time' => @$userAgent['time']
            ]);


            $notify[] = ['success', 'Google Authenticator Enabled Successfully'];
            return back()->withNotify($notify);
        } else {
            $notify[] = ['error', 'Wrong Verification Code'];
            return back()->withNotify($notify);
        }
    }


    public function disable2fa(Request $request)
    {
        $this->validate($request, [
            'code' => 'required',
        ]);

        $user = auth()->user();
        $ga = new GoogleAuthenticator();

        $secret = $user->tsc;
        $oneCode = $ga->getCode($secret);
        $userCode = $request->code;

        if ($oneCode == $userCode) {

            $user->tsc = null;
            $user->ts = 0;
            $user->tv = 1;
            $user->save();


            $userAgent = getIpInfo();
            $osBrowser = osBrowser();
            notify($user, '2FA_DISABLE', [
                'operating_system' => @$osBrowser['os_platform'],
                'browser' => @$osBrowser['browser'],
                'ip' => @$userAgent['ip'],
                'time' => @$userAgent['time']
            ]);

            $notify[] = ['success', 'Two Factor Authenticator Disable Successfully'];
            return back()->withNotify($notify);

        } else {
            $notify[] = ['error', 'Wrong Verification Code'];
            return back()->withNotify($notify);
        }
    }

    public function surveyAvailable()
    {
        $page_title = 'Surveys';
        $all_surveys = Survey::where('status',0)->with('surveyor')->whereHas('questions')->latest()->get();
        $general = GeneralSetting::first();

        $surveys = collect([]);

        foreach ($all_surveys as  $item) {

            $question_balance = $general->get_amount * count($item->questions);

            if ($item->surveyor->balance >= $question_balance) {

                if ($item->users) {
                    if(!in_array(auth()->user()->id,$item->users)){
                        $surveys->push($item);
                    }

                    if(in_array(auth()->user()->id,$item->users)){
                    }

                }else{
                    $surveys->push($item);
                }
            }
        }

        $surveys =  $surveys->paginate(getPaginate());

        $empty_message = 'No data found';
        return view($this->activeTemplate.'user.survey.index', compact('page_title', 'surveys', 'empty_message'));
    }

    public function surveyQuestions($id)
    {
        $page_title = 'Surveys Questions';
        $empty_message = 'No data found';
        $survey = Survey::findOrFail($id);
        $user = auth()->user();


        if (count($survey->questions) <= 0) {
            $notify[] = ['error', 'No question is available for this survey'];
            return back()->withNotify($notify);
        }

        if ($survey->users) {

            if(in_array($user->id,$survey->users)){

                $notify[] = ['error', 'You already participated on this'];
                return redirect()->route('user.survey')->withNotify($notify);
            }

        }

        if ($survey->age_limit == 1 && $survey->start_age && $survey->end_age) {
            if($user->age < $survey->start_age || $user->age > $survey->end_age){
                $notify[] = ['error', 'This survey has age limit from ' .$survey->start_age. ' to ' .$survey->end_age];
                return redirect()->route('user.survey')->withNotify($notify);
            }
        }

        if ($survey->country_limit == 1 && $survey->country) {
            if(!in_array($user->address->country,$survey->country)){
                $notify[] = ['error', 'This survey is not available for your country'];
                return redirect()->route('user.survey')->withNotify($notify);
            }
        }

        return view($this->activeTemplate.'user.survey.question', compact('page_title', 'survey', 'empty_message'));
    }

    public function surveyQuestionsAnswers(Request $request, $id){

        $request->validate([
            "answer" => "required|array|min:1",
            "answer.*" => "required_with:answer",
        ]);

        $survey = Survey::where('id',$id)->with('questions')->first();

        $user = auth()->user();

        if ($survey->users) {

            if(in_array($user->id,$survey->users)){
                $notify[] = ['error', 'You already participated on this'];
                return back()->withNotify($notify);
            }

            if(!in_array($user->id,$survey->users)){
                $survey_users = $survey->users;
                array_push($survey_users,$user->id);
                 $survey->users = $survey_users;
            }

        }

        if(!$survey->users){
            $survey->users = [$user->id];
        }


        $answers = $request['answer'];

        foreach ($survey->questions as $item) {
            $surveyAns = @$answers[$item->id];

            if (!$surveyAns) {
                $notify[] = ['error','Please answer all the questions'];
                return back()->withNotify($notify);
            }

            //Custom input validation
            if ($item->custom_input == 1 && $item->custom_input_type == 1) {
                $cusInp = $surveyAns['c'];
                if (!$cusInp) {
                    $notify[] = ['error','You missed input type answer'];
                    return back()->withNotify($notify);
                }
            }

            //radio type validation
            if ($item->type == 1) {
                $radioAns = array_shift($surveyAns);
                if(!$radioAns){
                    $notify[] = ['error','You missed radio type answer'];
                    return back()->withNotify($notify);
                }
                if(!in_array($radioAns,$item->options)){
                    $notify[] = ['error','Do not try to cheat us'];
                    return back()->withNotify($notify);
                }

            }

            //checkbox validation
            if ($item->type == 2) {
                $checkBoxValue = $surveyAns;
                unset($checkBoxValue['c']);
                if(@count($checkBoxValue) == 0 || !$checkBoxValue){
                    $notify[] = ['error','You missed checkBox type answer'];
                    return back()->withNotify($notify);
                }
                $diffAns = array_diff($checkBoxValue,$item->options);
                if(count($diffAns) > 0){
                    $notify[] = ['error','Do not try to cheat us'];
                    return back()->withNotify($notify);
                }
            }

        }

        $surveyor = Surveyor::where('id',$survey->surveyor_id)->first();
        if (!$surveyor) {
            $notify[] = ['error', 'You are not authorized to answer this'];
            return back()->withNotify($notify);
        }

        $general = GeneralSetting::first();

        $answer_balance = $general->get_amount * count($answers);

        if ($surveyor->balance < $answer_balance) {
            $notify[] = ['error', 'surveyor does not  have enough balance to pay your reward. Try another one'];
            return back()->withNotify($notify);
        }

        if (!$user) {
            $notify[] = ['error', 'You are not authorized to answer this'];
            return back()->withNotify($notify);
        }

        $survey->save();

        foreach ($answers as $key => $item) {
            $custom_ans = $item['c']??null;

            if($custom_ans){
                unset($item['c']);
            }

            $create_ans = new Answer();
            $create_ans->surveyor_id = $survey->surveyor->id;
            $create_ans->survey_id = $survey->id;
            $create_ans->user_id = $user->id;
            $create_ans->question_id = $key;
            $create_ans->answer = array_values($item);
            $create_ans->custom_answer = $custom_ans;
            $create_ans->save();
        }

        $surveyor->balance -= $general->get_amount * count($answers);
        $surveyor->save();

        $user->balance += $general->paid_amount * count($answers);
        $user->completed_survey += 1;
        $user->save();

        $transaction = new Transaction();
        $transaction->user_id = $user->id;
        $transaction->amount = getAmount($general->paid_amount * count($answers));
        $transaction->post_balance = getAmount($user->balance);
        $transaction->trx_type = '+';
        $transaction->details = 'For Completing ' . $survey->name;
        $transaction->trx =  getTrx();
        $transaction->save();

        $transaction = new Transaction();
        $transaction->surveyor_id = $surveyor->id;
        $transaction->amount = getAmount($general->get_amount * count($answers));
        $transaction->post_balance = getAmount($surveyor->balance);
        $transaction->trx_type = '-';
        $transaction->details = 'For Get Answerd ' . $survey->name;
        $transaction->trx =  getTrx();
        $transaction->save();



        notify($user, 'SURVEY_COMPLETED', [
            'survey_name' => $survey->name,
            'amount' => getAmount($general->paid_amount * count($answers)),
            'currency' => $general->cur_text,
            'post_balance' => getAmount($user->balance)
        ]);

        notify($surveyor, 'SURVEY_ANSWERD', [
            'survey_name' => $survey->name,
            'total_question' => count($answers),
            'charge' => getAmount($general->get_amount),
            'amount' => getAmount($general->get_amount * count($answers)),
            'currency' => $general->cur_text,
            'post_balance' => getAmount($surveyor->balance)
        ]);

        $notify[] = ['success', 'You have done this survey successfully'];
        return redirect()->route('user.home')->withNotify($notify);
    }
}
