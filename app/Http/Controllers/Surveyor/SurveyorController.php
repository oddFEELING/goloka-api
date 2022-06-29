<?php

namespace App\Http\Controllers\Surveyor;

use App\Models\Answer;
use App\Models\Category;
use App\Models\Deposit;
use App\Models\GeneralSetting;
use App\Lib\GoogleAuthenticator;
use App\Http\Controllers\Controller;
use App\Models\Option;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Rules\FileTypeValidate;
use App\Models\Survey;
use App\Models\Transaction;
use Illuminate\Support\Facades\Hash;
use PDF;

class SurveyorController extends Controller
{
    public function dashboard()
    {
        $page_title = 'Dashboard';
        $surveyor = auth()->guard('surveyor')->user();
        $totalDeposit = Deposit::where('surveyor_id',$surveyor->id)->where('status',1)->sum('amount');
        $totalTransaction = Transaction::where('surveyor_id',$surveyor->id)->count();
        $approvedSurvey = $surveyor->surveys->where('status',0)->count();
        $pendingSurvey = $surveyor->surveys->where('status',1)->count();
        $rejectedSurvey = $surveyor->surveys->where('status',3)->count();

        $survey_chart = Answer::where('surveyor_id',$surveyor->id)->groupBy('survey_id')->orderBy('created_at')->get()->groupBy(function ($d) {
            return $d->created_at->format('F');
        });

        $survey_all = [];
        $month_survey = [];
        foreach ($survey_chart as $key => $value) {
            $survey_all[] = count($value);
            $month_survey[] = $key;
        }
        return   view('surveyor.dashboard',compact('page_title','totalDeposit','surveyor','totalTransaction','approvedSurvey','pendingSurvey','rejectedSurvey','survey_all','month_survey'));
    }

    public function profile()
    {
        $page_title = 'Profile';
        $surveyor = Auth::guard('surveyor')->user();
        return view('surveyor.profile', compact('page_title', 'surveyor'));
    }

    public function profileUpdate(Request $request)
    {
        $this->validate($request, [
            'image' => [new FileTypeValidate(['jpeg', 'jpg', 'png'])],
            'firstname' => 'required|string|max:50',
            'lastname' => 'required|string|max:50',
            'address' => "sometimes|required|max:191",
            'state' => 'sometimes|required|max:80',
            'zip' => 'sometimes|required|max:40',
            'city' => 'sometimes|required|max:50',
        ],[
            'firstname.required'=>'First Name Field is required',
            'lastname.required'=>'Last Name Field is required'
        ]);

        $surveyor = Auth::guard('surveyor')->user();


        $in['firstname'] = $request->firstname;
        $in['lastname'] = $request->lastname;

        $in['address'] = [
            'address' => $request->address,
            'state' => $request->state,
            'zip' => $request->zip,
            'country' => $surveyor->address->country,
            'city' => $request->city,
        ];


        $surveyor_image = $surveyor->image;
        if($request->hasFile('image')) {
            try{

                $location = imagePath()['profile']['surveyor']['path'];
                $size = imagePath()['profile']['surveyor']['size'];
                $old = $surveyor->image;
                $surveyor_image = uploadImage($request->image, $location , $size, $old);

            }catch(\Exception $exp) {
                return back()->withNotify(['error', 'Could not upload the image.']);
            }
        }

        $in['image'] = $surveyor_image;
        $surveyor->fill($in)->save();

        $notify[] = ['success', 'Your profile has been updated.'];
        return redirect()->route('surveyor.profile')->withNotify($notify);
    }

    public function password()
    {
        $page_title = 'Password Setting';
        $surveyor = Auth::guard('surveyor')->user();
        return view('surveyor.password', compact('page_title', 'surveyor'));
    }

    public function passwordUpdate(Request $request)
    {
        $this->validate($request, [
            'old_password' => 'required',
            'password' => 'required|min:6|confirmed',
        ]);

        $surveyor = Auth::guard('surveyor')->user();
        if (!Hash::check($request->old_password, $surveyor->password)) {
            $notify[] = ['error', 'Password Do not match !!'];
            return back()->withErrors(['Invalid old password.']);
        }

        $surveyor->update([
            'password' => Hash::make($request->password),
        ]);

        $notify[] = ['success', 'Password Changed Successfully.'];
        return redirect()->route('surveyor.password')->withNotify($notify);
    }

    public function show2faForm()
    {
        $gnl = GeneralSetting::first();
        $ga = new GoogleAuthenticator();
        $surveyor = Auth::guard('surveyor')->user();
        $secret = $ga->createSecret();
        $qrCodeUrl = $ga->getQRCodeGoogleUrl($surveyor->username . '@' . $gnl->sitename, $secret);
        $prevcode = $surveyor->tsc;
        $prevqr = $ga->getQRCodeGoogleUrl($surveyor->username . '@' . $gnl->sitename, $prevcode);
        $page_title = 'Two Factor';
        return view('surveyor.twofactor', compact('page_title', 'secret', 'qrCodeUrl', 'prevcode', 'prevqr'));
    }

    public function create2fa(Request $request)
    {
        $surveyor = Auth::guard('surveyor')->user();
        $this->validate($request, [
            'key' => 'required',
            'code' => 'required',
        ]);

        $ga = new GoogleAuthenticator();
        $secret = $request->key;
        $oneCode = $ga->getCode($secret);

        if ($oneCode === $request->code) {
            $surveyor->tsc = $request->key;
            $surveyor->ts = 1;
            $surveyor->tv = 1;
            $surveyor->save();


            $surveyorAgent = getIpInfo();
            $osBrowser = osBrowser();
            notify($surveyor, '2FA_ENABLE', [
                'operating_system' => @$osBrowser['os_platform'],
                'browser' => @$osBrowser['browser'],
                'ip' => @$surveyorAgent['ip'],
                'time' => @$surveyorAgent['time']
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

        $surveyor = Auth::guard('surveyor')->user();
        $ga = new GoogleAuthenticator();

        $secret = $surveyor->tsc;
        $oneCode = $ga->getCode($secret);
        $surveyorCode = $request->code;

        if ($oneCode == $surveyorCode) {

            $surveyor->tsc = null;
            $surveyor->ts = 0;
            $surveyor->tv = 1;
            $surveyor->save();


            $surveyorAgent = getIpInfo();
            $osBrowser = osBrowser();
            notify($surveyor, '2FA_DISABLE', [
                'operating_system' => @$osBrowser['os_platform'],
                'browser' => @$osBrowser['browser'],
                'ip' => @$surveyorAgent['ip'],
                'time' => @$surveyorAgent['time']
            ]);


            $notify[] = ['success', 'Two Factor Authenticator Disable Successfully'];
            return back()->withNotify($notify);
        } else {
            $notify[] = ['error', 'Wrong Verification Code'];
            return back()->withNotify($notify);
        }
    }

    public function depositHistory()
    {
        $page_title = 'Deposit History';
        $empty_message = 'No history found.';
        $logs = auth()->guard('surveyor')->user()->deposits()->with(['gateway'])->latest()->paginate(getPaginate());
        return view('surveyor.deposit_history', compact('page_title', 'empty_message', 'logs'));
    }

    public function transactionHistory()
    {
        $page_title = 'Successfull Transactions';
        $transactions = auth()->guard('surveyor')->user()->transactions()->with('surveyor')->latest()->paginate(getPaginate());
        $empty_message = 'No transactions';
        return view('surveyor.transactions', compact('page_title', 'transactions', 'empty_message'));
    }

    public function transactionSearch(Request $request)
    {
        $request->validate(['search' => 'required']);
        $search = $request->search;
        $page_title = 'Transactions Search - ' . $search;
        $empty_message = 'No transactions.';

        $transactions = Transaction::where('surveyor_id',auth()->guard('surveyor')->user()->id)->where('trx', $search)->orderBy('id','desc')->paginate(getPaginate());

        return view('surveyor.transactions', compact('page_title', 'transactions', 'empty_message'));
    }

    public function surveyAll()
    {
        $page_title = 'New Survey';
        $surveys = Survey::where('surveyor_id',auth()->guard('surveyor')->user()->id)->latest()->paginate(getPaginate());
        $empty_message = 'No data found';
        return view('surveyor.survey.index', compact('page_title','surveys','empty_message'));
    }

    public function surveyNew()
    {
        $page_title = 'New Survey';
        $categories = Category::where('status','1')->latest()->get();
        return view('surveyor.survey.new', compact('page_title','categories'));
    }

    public function surveyStore(Request $request)
    {
        $this->validate($request, [
            'image' => ['required',new FileTypeValidate(['jpeg', 'jpg', 'png'])],
            'category_id' => 'required|numeric|gt:0',
            'name' => 'required|string|max:191',
            'age_limit' => 'required|min:0|max:1',
            'country_limit' => 'required|min:0|max:1',
            'start_age' => 'sometimes|required|numeric|min:1|max:200',
            'end_age' => "sometimes|required|numeric|min:1|max:200|gt:$request->start_age",
            'country' => 'required_if:country_limit,1|array|min:1',
            'country.*' => 'required_with:country|string',
        ],[
            'country.required_if'=>'Country field is required when country limit is yes'
        ]);


        $general = GeneralSetting::first();

        $survey_image = '';
        if($request->hasFile('image')) {
            try{
                $location = imagePath()['survey']['path'];
                $size = imagePath()['survey']['size'];

                $survey_image = uploadImage($request->image, $location , $size);

            }catch(\Exception $exp) {
                return back()->withNotify(['error', 'Could not upload the image.']);
            }
        }

        $survey = new Survey();
        $survey->image = $survey_image;
        $survey->name = $request->name;
        $survey->age_limit = $request->age_limit;
        $survey->country_limit = $request->country_limit;
        $survey->start_age = $request->start_age;
        $survey->end_age = $request->end_age;
        $survey->country = $request->country;
        $survey->category_id = $request->category_id;
        $survey->surveyor_id = auth()->guard('surveyor')->user()->id;
        $survey->status = $general->survey_approval;
        $survey->save();

        $notify[] = ['success', 'Survey has been added'];
        return redirect()->route('surveyor.survey.question.new',$survey->id)->withNotify($notify);
    }

    public function surveyEdit($id)
    {
        $survey = Survey::findOrFail($id);
       

        if ($survey->surveyor_id != auth()->guard('surveyor')->user()->id) {
            $notify[] = ['success', 'You are not authorized to edit this survey'];
            return back()->withNotify($notify);
        }

        if ($survey->status == 3) {
            $notify[] = ['error', 'Do no try to cheat us'];
            return back()->withNotify($notify);
        }

        $page_title = 'Edit Survey';
        $categories = Category::where('status','1')->latest()->get();
        return view('surveyor.survey.edit',compact('page_title','survey','categories'));
    }

    public function surveyUpdate(Request $request, $id)
    {
        $this->validate($request, [
            'image' => [new FileTypeValidate(['jpeg', 'jpg', 'png'])],
            'category_id' => 'required|numeric|gt:0',
            'name' => 'required|string|max:191',
            'age_limit' => 'required|min:0|max:1',
            'country_limit' => 'required|min:0|max:1',
            'start_age' => 'sometimes|required|numeric|min:1|max:200',
            'end_age' => "sometimes|required|numeric|min:1|max:200|gt:$request->start_age",
            'country' => 'required_if:country_limit,1|array|min:1',
            'country.*' => 'required_with:country|string',
        ],[
            'country.required_if'=>'Country field is required when country limit is yes'
        ]);

        $survey = Survey::findOrFail($id);

        if ($survey->surveyor_id != auth()->guard('surveyor')->user()->id) {
            $notify[] = ['success', 'You are not authorized to edit this survey'];
            return back()->withNotify($notify);
        }

        $general = GeneralSetting::first();

        $survey_image = $survey->image;
        if($request->hasFile('image')) {
            try{
                $location = imagePath()['survey']['path'];
                $size = imagePath()['survey']['size'];
                $old = $survey_image;
                $survey_image = uploadImage($request->image, $location , $size, $old);

            }catch(\Exception $exp) {
                return back()->withNotify(['error', 'Could not upload the image.']);
            }
        }

        $survey->image = $survey_image;
        $survey->name = $request->name;
        $survey->age_limit = $request->age_limit;
        $survey->country_limit = $request->country_limit;
        $survey->start_age = $request->start_age;
        $survey->end_age = $request->end_age;
        $survey->country = $request->country;
        $survey->category_id = $request->category_id;
        $survey->status = $general->survey_approval;
        $survey->save();

        $notify[] = ['success', 'Survey has been updated'];
        return back()->withNotify($notify);
    }

    public function questionAll($id)
    {
        $page_title = 'All Questions';
        $survey = Survey::findOrFail($id);

        if ($survey->surveyor_id != auth()->guard('surveyor')->user()->id) {
            $notify[] = ['success', 'You are not authorized to see this survey'];
            return back()->withNotify($notify);
        }

        $questions = $survey->questions()->paginate(getPaginate());
        $empty_message = 'No question found';
        return view('surveyor.question.index', compact('page_title','survey','empty_message','questions'));
    }

    public function questionNew($id)
    {
        $survey = Survey::findOrFail($id);
        $page_title = 'New Question';
        return view('surveyor.question.new', compact('page_title','survey'));
    }

    public function questionStore(Request $request)
    {
        $this->validate($request, [
            'survey_id' => 'required|gt:0',
            'type' => 'required|min:1|max:2',
            'custom_input' => 'required|min:0|max:1',
            'custom_input_type' => 'sometimes|required|min:0|max:1',
            'custom_question' => 'sometimes|required|max:255',
            'question' => 'required|max:255',
            'options.*' => 'required|max:191',
        ],[
            'options.*.required' => 'Please add all options',
            'options.*.max' => 'Total options should not be more than 191 charecters'
        ]);

        $survey = Survey::findOrFail($request->survey_id);

        $question = new Question();
        $question->survey_id = $survey->id;
        $question->question = $request->question;
        $question->type = $request->type;
        $question->custom_input = $request->custom_input;
        $question->custom_input_type = $request->custom_input_type??0;
        $question->custom_question = $request->custom_question;
        $question->options = array_values($request->options);
        $question->save();

        $notify[] = ['success', 'Question has been added'];
        return back()->withNotify($notify);
    }

    public function questionEdit($q_id,$s_id)
    {
        $question = Question::findOrFail($q_id);

        if ($question->survey_id != $s_id) {
            $notify[] = ['error', 'You are not authorized to edit this question'];
            return back()->withNotify($notify);
        }

        $page_title = 'Edit Question';
        return view('surveyor.question.edit',compact('page_title','question','s_id'));
    }

    public function questionUpdate(Request $request,$id)
    {
        $this->validate($request, [
            'survey_id' => 'required|gt:0',
            'type' => 'required|min:1|max:2',
            'custom_input' => 'required|min:0|max:1',
            'custom_input_type' => 'sometimes|required|min:0|max:1',
            'custom_question' => 'sometimes|required|max:255',
            'question' => 'required|max:255',
            'options.*' => 'required|max:191',
        ],[
            'options.*.required' => 'Please add all options',
            'options.*.max' => 'Option should not be more than 191 charecters'
        ]);


        $survey = Survey::findOrFail($request->survey_id);
        $general = GeneralSetting::first();
        $survey->status = $general->survey_approval;
        $survey->save();

        $question = Question::findOrFail($id);
        if ($question->survey_id != $survey->id) {
            $notify[] = ['error', 'You are not authorized to update this question'];
            return back()->withNotify($notify);
        }


        if(!$request->options){

            $options = $question->options;
        }
        if($request->options){

            $options = array_merge( $question->options,$request->options);
        }

        $question->question = $request->question;
        $question->type = $request->type;
        $question->custom_input = $request->custom_input;
        $question->custom_input_type = $request->custom_input_type??0;
        $question->custom_question = $request->custom_question;
        $question->options = $options;
        $question->save();
        $notify[] = ['success', 'Question has been updated'];
        return back()->withNotify($notify);
    }

    public function questionView($q_id,$s_id)
    {
        $question = Question::findOrFail($q_id);

        if ($question->survey_id != $s_id) {
            $notify[] = ['error', 'You are not authorized to view this question'];
            return back()->withNotify($notify);
        }

        $page_title = 'View Question';
        return view('surveyor.question.view',compact('page_title','question','s_id'));
    }

    public function report()
    {
        $page_title = 'Survey Report';
        $surveys = Survey::where('surveyor_id',auth()->guard('surveyor')->user()->id)->where('status',0)->latest()->paginate(getPaginate());
        $empty_message = 'No survey found';
        return view('surveyor.report.index', compact('page_title','surveys','empty_message'));
    }

    public function reportQuestion($id)
    {
        $page_title = 'Survey Report';
        $survey = Survey::where('surveyor_id',auth()->guard('surveyor')->user()->id)->findOrFail($id);

        if (count($survey->answers) <= 0) {
            $notify[] = ['error', 'Not report ready yet'];
            return back()->withNotify($notify);
        }
        return view('surveyor.report.question', compact('page_title','survey'));
    }

    public function reportDownload($id)
    {
        $survey = Survey::where('surveyor_id',auth()->guard('surveyor')->user()->id)->findOrFail($id);

        if(count($survey->questions) <= 0) {
            $notify[] = ['error', 'No report available'];
            return back()->withNotify($notify);
        }

        $page_title = 'Report Download';
        $filename = strtolower(str_replace(' ','_',$survey->name));
        return view('surveyor.report.report',compact('survey','page_title','filename'));
    }
}

