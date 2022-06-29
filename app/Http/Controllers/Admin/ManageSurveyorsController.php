<?php

namespace App\Http\Controllers\Admin;

use App\Models\Deposit;
use App\Models\Gateway;
use App\Models\GeneralSetting;
use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\Surveyor;
use App\Models\Transaction;
use App\Models\UserLogin;
use App\Models\WithdrawMethod;
use App\Models\Withdrawal;
use Illuminate\Http\Request;

class ManageSurveyorsController extends Controller
{
    public function allSurveyors()
    {
        $page_title = 'Manage Surveyors';
        $empty_message = 'No surveyors found';
        $surveyors = Surveyor::latest()->paginate(getPaginate());
        return view('admin.surveyors.list', compact('page_title', 'empty_message', 'surveyors'));
    }

    public function activeSurveyors()
    {
        $page_title = 'Manage Active Surveyors';
        $empty_message = 'No active surveyor found';
        $surveyors = Surveyor::active()->latest()->paginate(getPaginate());
        return view('admin.surveyors.list', compact('page_title', 'empty_message', 'surveyors'));
    }

    public function bannedSurveyors()
    {
        $page_title = 'Banned surveyors';
        $empty_message = 'No banned surveyor found';
        $surveyors = Surveyor::banned()->latest()->paginate(getPaginate());
        return view('admin.surveyors.list', compact('page_title', 'empty_message', 'surveyors'));
    }

    public function emailUnverifiedSurveyors()
    {
        $page_title = 'Email Unverified Surveyors';
        $empty_message = 'No email unverified surveyor found';
        $surveyors = Surveyor::emailUnverified()->latest()->paginate(getPaginate());
        return view('admin.surveyors.list', compact('page_title', 'empty_message', 'surveyors'));
    }
    public function emailVerifiedSurveyors()
    {
        $page_title = 'Email Verified Surveyors';
        $empty_message = 'No email verified surveyor found';
        $surveyors = Surveyor::emailVerified()->latest()->paginate(getPaginate());
        return view('admin.surveyors.list', compact('page_title', 'empty_message', 'surveyors'));
    }


    public function smsUnverifiedSurveyors()
    {
        $page_title = 'SMS Unverified Surveyors';
        $empty_message = 'No sms unverified surveyors found';
        $surveyors = Surveyor::smsUnverified()->latest()->paginate(getPaginate());
        return view('admin.surveyors.list', compact('page_title', 'empty_message', 'surveyors'));
    }
    public function smsVerifiedSurveyors()
    {
        $page_title = 'SMS Verified Surveyors';
        $empty_message = 'No sms verified surveyor found';
        $surveyors = Surveyor::smsVerified()->latest()->paginate(getPaginate());
        return view('admin.surveyors.list', compact('page_title', 'empty_message', 'surveyors'));
    }



    public function search(Request $request, $scope)
    {
        $search = $request->search;
        $surveyors = Surveyor::where(function ($surveyor) use ($search) {
            $surveyor->where('username', 'like', "%$search%")
                ->orWhere('email', 'like', "%$search%")
                ->orWhere('mobile', 'like', "%$search%")
                ->orWhere('firstname', 'like', "%$search%")
                ->orWhere('lastname', 'like', "%$search%");
        });
        $page_title = '';
        switch ($scope) {
            case 'active':
                $page_title .= 'Active ';
                $surveyors = $surveyors->where('status', 1);
                break;
            case 'banned':
                $page_title .= 'Banned ';
                $surveyors = $surveyors->where('status', 0);
                break;
            case 'emailUnverified ':
                $page_title .= 'Email Unerified ';
                $surveyors = $surveyors->where('ev', 0);
                break;
            case 'smsUnverified ':
                $page_title .= 'SMS Unverified ';
                $surveyors = $surveyors->where('sv', 0);
                break;
        }
        $surveyors = $surveyors->paginate(getPaginate());
        $page_title .= 'Surveyor Search - ' . $search;
        $empty_message = 'No search result found';
        return view('admin.surveyors.list', compact('page_title', 'search', 'scope', 'empty_message', 'surveyors'));
    }


    public function detail($id)
    {
        $page_title = 'Surveyor Detail';
        $surveyor = Surveyor::findOrFail($id);
        $totalDeposit = Deposit::where('surveyor_id',$surveyor->id)->where('status',1)->sum('amount');
        $totalTransaction = Transaction::where('surveyor_id',$surveyor->id)->count();
        return view('admin.surveyors.detail', compact('page_title', 'surveyor','totalDeposit','totalTransaction'));
    }


    public function update(Request $request, $id)
    {
        $surveyor = Surveyor::findOrFail($id);
        $request->validate([
            'firstname' => 'required|max:60',
            'lastname' => 'required|max:60',
            'email' => 'required|email|max:160|unique:surveyors,email,' . $surveyor->id,
        ]);

        if ($request->email != $surveyor->email && Surveyor::whereEmail($request->email)->whereId('!=', $surveyor->id)->count() > 0) {
            $notify[] = ['error', 'Email already exists.'];
            return back()->withNotify($notify);
        }
        if ($request->mobile != $surveyor->mobile && Surveyor::where('mobile', $request->mobile)->whereId('!=', $surveyor->id)->count() > 0) {
            $notify[] = ['error', 'Phone number already exists.'];
            return back()->withNotify($notify);
        }

        $surveyor->mobile = $request->mobile;
        $surveyor->firstname = $request->firstname;
        $surveyor->lastname = $request->lastname;
        $surveyor->lastname = $request->lastname;
        $surveyor->email = $request->email;
        $surveyor->address = [
                            'address' => $request->address,
                            'city' => $request->city,
                            'state' => $request->state,
                            'zip' => $request->zip,
                            'country' => $request->country,
                        ];
        $surveyor->status = $request->status ? 1 : 0;
        $surveyor->ev = $request->ev ? 1 : 0;
        $surveyor->sv = $request->sv ? 1 : 0;
        $surveyor->ts = $request->ts ? 1 : 0;
        $surveyor->tv = $request->tv ? 1 : 0;
        $surveyor->save();

        $notify[] = ['success', 'Surveyor detail has been updated'];
        return redirect()->back()->withNotify($notify);
    }

    public function addSubBalance(Request $request, $id)
    {
        $request->validate(['amount' => 'required|numeric|gt:0']);

        $surveyor = Surveyor::findOrFail($id);
        $amount = getAmount($request->amount);
        $general = GeneralSetting::first(['cur_text','cur_sym']);
        $trx = getTrx();

        if ($request->act) {
            $surveyor->balance += $amount;
            $surveyor->save();
            $notify[] = ['success', $general->cur_sym . $amount . ' has been added to ' . $surveyor->username . ' balance'];


            $transaction = new Transaction();
            $transaction->surveyor_id = $surveyor->id;
            $transaction->amount = $amount;
            $transaction->post_balance = getAmount($surveyor->balance);
            $transaction->charge = 0;
            $transaction->trx_type = '+';
            $transaction->details = 'Added Balance Via Admin';
            $transaction->trx =  $trx;
            $transaction->save();


            notify($surveyor, 'BAL_ADD', [
                'trx' => $trx,
                'amount' => $amount,
                'currency' => $general->cur_text,
                'post_balance' => getAmount($surveyor->balance),
            ]);

        } else {
            if ($amount > $surveyor->balance) {
                $notify[] = ['error', $surveyor->username . ' has insufficient balance.'];
                return back()->withNotify($notify);
            }
            $surveyor->balance -= $amount;
            $surveyor->save();



            $transaction = new Transaction();
            $transaction->surveyor_id = $surveyor->id;
            $transaction->amount = $amount;
            $transaction->post_balance = getAmount($surveyor->balance);
            $transaction->charge = 0;
            $transaction->trx_type = '-';
            $transaction->details = 'Subtract Balance Via Admin';
            $transaction->trx =  $trx;
            $transaction->save();


            notify($surveyor, 'BAL_SUB', [
                'trx' => $trx,
                'amount' => $amount,
                'currency' => $general->cur_text,
                'post_balance' => getAmount($surveyor->balance)
            ]);
            $notify[] = ['success', $general->cur_sym . $amount . ' has been subtracted from ' . $surveyor->username . ' balance'];
        }
        return back()->withNotify($notify);
    }


    public function surveyorLoginHistory($id)
    {
        $surveyor = Surveyor::findOrFail($id);
        $page_title = 'Surveyor Login History - ' . $surveyor->username;
        $empty_message = 'No surveyors login found.';
        $login_logs = $surveyor->login_logs()->latest()->paginate(getPaginate());
        return view('admin.surveyors.logins', compact('page_title', 'empty_message', 'login_logs'));
    }



    public function showEmailSingleForm($id)
    {
        $surveyor = Surveyor::findOrFail($id);
        $page_title = 'Send Email To: ' . $surveyor->username;
        return view('admin.surveyors.email_single', compact('page_title', 'surveyor'));
    }

    public function sendEmailSingle(Request $request, $id)
    {
        $request->validate([
            'message' => 'required|string|max:65000',
            'subject' => 'required|string|max:190',
        ]);

        $surveyor = Surveyor::findOrFail($id);
        send_general_email($surveyor->email, $request->subject, $request->message, $surveyor->username);
        $notify[] = ['success', $surveyor->username . ' will receive an email shortly.'];
        return back()->withNotify($notify);
    }

    public function transactions(Request $request, $id)
    {
        $surveyor = Surveyor::findOrFail($id);
        if ($request->search) {
            $search = $request->search;
            $page_title = 'Search Surveyor Transactions : ' . $surveyor->username;
            $transactions = $surveyor->transactions()->where('trx', $search)->with('surveyor')->latest()->paginate(getPaginate());
            $empty_message = 'No transactions';
            return view('admin.reports.transactions', compact('page_title', 'search', 'surveyor', 'transactions', 'empty_message'));
        }
        $page_title = 'Surveyor Transactions : ' . $surveyor->username;
        $transactions = $surveyor->transactions()->with('surveyor')->latest()->paginate(getPaginate());
        $empty_message = 'No transactions';
        return view('admin.reports.transactions', compact('page_title', 'surveyor', 'transactions', 'empty_message'));
    }

    public function deposits(Request $request, $id)
    {
        $surveyor = Surveyor::findOrFail($id);
        $surveyorId = $surveyor->id;
        if ($request->search) {
            $search = $request->search;
            $page_title = 'Search Surveyor Deposits : ' . $surveyor->username;
            $deposits = $surveyor->deposits()->where('trx', $search)->latest()->paginate(getPaginate());
            $empty_message = 'No deposits';
            return view('admin.deposit.log', compact('page_title', 'search', 'surveyor', 'deposits', 'empty_message','surveyorId'));
        }

        $page_title = 'Surveyor Deposit : ' . $surveyor->username;
        $deposits = $surveyor->deposits()->latest()->paginate(getPaginate());
        $empty_message = 'No deposits';
        $scope = 'all';
        return view('admin.deposit.log', compact('page_title', 'surveyor', 'deposits', 'empty_message','surveyorId','scope'));
    }


    public function depViaMethod($method,$type = null,$surveyorId){
        $method = Gateway::where('alias',$method)->firstOrFail();
        $surveyor = Surveyor::findOrFail($surveyorId);
        if ($type == 'approved') {
            $page_title = 'Approved Payment Via '.$method->name;
            $deposits = Deposit::where('method_code','>=',1000)->where('surveyor_id',$surveyor->id)->where('method_code',$method->code)->where('status', 1)->latest()->with(['surveyor', 'gateway'])->paginate(getPaginate());
        }elseif($type == 'rejected'){
            $page_title = 'Rejected Payment Via '.$method->name;
            $deposits = Deposit::where('method_code','>=',1000)->where('surveyor_id',$surveyor->id)->where('method_code',$method->code)->where('status', 3)->latest()->with(['surveyor', 'gateway'])->paginate(getPaginate());
        }elseif($type == 'successful'){
            $page_title = 'Successful Payment Via '.$method->name;
            $deposits = Deposit::where('status', 1)->where('surveyor_id',$surveyor->id)->where('method_code',$method->code)->latest()->with(['surveyor', 'gateway'])->paginate(getPaginate());
        }elseif($type == 'pending'){
            $page_title = 'Pending Payment Via '.$method->name;
            $deposits = Deposit::where('method_code','>=',1000)->where('surveyor_id',$surveyor->id)->where('method_code',$method->code)->where('status', 2)->latest()->with(['surveyor', 'gateway'])->paginate(getPaginate());
        }else{
            $page_title = 'Payment Via '.$method->name;
            $deposits = Deposit::where('status','!=',0)->where('surveyor_id',$surveyor->id)->where('method_code',$method->code)->latest()->with(['surveyor', 'gateway'])->paginate(getPaginate());
        }
        $page_title = 'Deposit History: '.$surveyor->username.' Via '.$method->name;
        $methodAlias = $method->alias;
        $empty_message = 'Deposit Log';
        return view('admin.deposit.log', compact('page_title', 'empty_message', 'deposits','methodAlias','surveyorId'));
    }

    public function showEmailAllForm()
    {
        $page_title = 'Send Email To All Surveyors';
        return view('admin.surveyors.email_all', compact('page_title'));
    }

    public function sendEmailAll(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:65000',
            'subject' => 'required|string|max:190',
        ]);

        foreach (Surveyor::where('status', 1)->cursor() as $surveyor) {
            send_general_email($surveyor->email, $request->subject, $request->message, $surveyor->username);
        }

        $notify[] = ['success', 'All surveyors will receive an email shortly.'];
        return back()->withNotify($notify);
    }

    public function surveyAll($id)
    {
        $surveyor = Surveyor::with('surveys')->findOrFail($id);
        $page_title = $surveyor->getFullnameAttribute().' All Surveys';
        $surveys = $surveyor->surveys->paginate(getPaginate());
        $empty_message = 'No data found';
        return view('admin.survey.index',compact('page_title','empty_message','surveys'));
    }
}
