@extends($activeTemplate.'layouts.master')

@php
    $notice_content = getContent('notice.content',true);
@endphp

@section('content')
<div class="survey-area mt-30">
    <div class="panel-card-header section--bg text-white">
        <div class="panel-card-title"><i class="las la-exclamation-circle"></i> @lang('Notice Board')</div>
    </div>
    <div class="panel-card-body">
        <div class="row justify-content-center mb-30-none">
                <div class="col-xl-12 col-md-12 col-sm-12 mb-30">
                    <p>{{__(@$notice_content->data_values->description)}}</p>
                </div>
        </div>
    </div>
</div>
<div class="survey-area mt-30">
    <div class="panel-card-header section--bg text-white">
        <div class="panel-card-title"><i class="lar la-question-circle"></i> @lang('Survey List')</div>
    </div>
    <div class="panel-card-body">
        <div class="row justify-content-center mb-30-none">
            @forelse ($surveys as $item)
                <div class="col-xl-3 col-md-6 col-sm-8 mb-30">
                    <div class="survey-list-item">
                        <div class="survey-list-body">
                            <div class="survey-list-thumb">
                                <img src="{{ getImage(imagePath()['survey']['path'].'/'. $item->image,imagePath()['survey']['size']) }}" alt="survey">
                            </div>
                            <div class="survey-list-content">
                                <div class="survey-list-header d-flex flex-wrap justify-content-between">
                                    <h3 class="title">{{__($item->category->name)}}</h3>
                                    <div class="survey-price">@lang('Reward'): {{$item->questions->count() * $general->paid_amount}} {{__($general->cur_text)}}</div>
                                </div>
                                <p>{{__($item->name)}}</p>
                            </div>
                        </div>
                        <div class="survey-list-footer bg--primary text-center">
                            <div class="survey-btn">
                                <a href="{{route('user.survey.questions',$item->id)}}" class="text-white text-center">@lang('Start Survey')</a>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <p class="mb-4">{{__($empty_message)}}</p>
            @endforelse
        </div>
    </div>
    <div class="panel-card-footer-area d-flex flex-wrap align-items-center justify-content-center">
        <div class="panel-card-footer-right">
            <nav>
                {{$surveys->links()}}
            </nav>
        </div>
    </div>
</div>
@endsection
