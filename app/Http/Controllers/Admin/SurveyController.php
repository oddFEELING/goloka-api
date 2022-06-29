<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\Survey;
use Illuminate\Http\Request;

class SurveyController extends Controller
{
    public function pending(){
        $page_title = 'Pending Survey';
        $empty_message = 'No pending survey';

        $surveys = Survey::where('status',1)->whereHas('surveyor', function($q){
            $q->where('status',1);
        })->paginate(getPaginate());

        return view('admin.survey.index',compact('page_title','empty_message','surveys'));
    }

    public function approved(){
        $page_title = 'Approved Survey';
        $empty_message = 'No approved survey';

        $surveys = Survey::where('status',0)->whereHas('surveyor', function($q){
            $q->where('status',1);
        })->paginate(getPaginate());

        return view('admin.survey.index',compact('page_title','empty_message','surveys'));
    }

    public function rejected(){
        $page_title = 'Rejected Survey';
        $empty_message = 'No rejected survey';

        $surveys = Survey::where('status',3)->whereHas('surveyor', function($q){
            $q->where('status',1);
        })->paginate(getPaginate());

        return view('admin.survey.index',compact('page_title','empty_message','surveys'));
    }

    public function approve(Request $request, $id){
        $survey = Survey::findOrFail($id);
        $survey->status = 0;
        $survey->save();

        $notify[] = ['success', 'Approved Successfully'];
        return back()->withNotify($notify);
    }

    public function reject(Request $request, $id){
        $survey = Survey::findOrFail($id);
        $survey->status = 3;
        $survey->save();

        $notify[] = ['success', 'Rejected Successfully'];
        return back()->withNotify($notify);
    }

    public function search(Request $request, $scope)
    {
        $search = $request->search;
        $surveys = Survey::whereHas('surveyor', function($q){
            $q->where('status',1);
        })->where(function($query) use($search){
            $query->whereHas('category', function($d) use($search) {
                $d->where('name', 'like', "%$search%");
            })->orWhere('name', 'like', "%$search%");
        });


        $page_title = '';

        switch ($scope) {
            case 'pending':
                $page_title .= 'Pending ';
                $surveys = $surveys->where('status', 1);
                break;
            case 'approved':
                $page_title .= 'Approved ';
                $surveys = $surveys->where('status', 0);
                break;
            case 'rejected':
                $page_title .= 'Rejected ';
                $surveys = $surveys->where('status', 3);
                break;
        }
        $surveys = $surveys->paginate(getPaginate());
        $page_title .= 'Survey Search - ' . $search;
        $empty_message = 'No search result found';
        return view('admin.survey.index', compact('page_title', 'search', 'scope', 'empty_message', 'surveys'));
    }

    public function questionAll($id)
    {
        $page_title = 'All Questions';
        $survey = Survey::findOrFail($id);
        $questions = $survey->questions()->paginate(getPaginate());
        $empty_message = 'No question found';
        return view('admin.survey.question', compact('page_title','survey','empty_message','questions'));
    }

    public function questionView($q_id,$s_id)
    {
        $question = Question::findOrFail($q_id);

        if ($question->survey_id != $s_id) {
            $notify[] = ['error', 'Something went Wrong  '];
            return back()->withNotify($notify);
        }

        $page_title = 'View Question';
        return view('admin.survey.view',compact('page_title','question'));
    }
}
