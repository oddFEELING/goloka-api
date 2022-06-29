@php
	$about_content = getContent('about.content',true);
@endphp
<section class="about-section ptb-80">
    <div class="container">
        <div class="row justify-content-center mb-30-none">
            <div class="col-xl-5 col-md-6 mb-30">
                <div class="about-thumb">
                    <img src="{{ getImage('assets/images/frontend/about/'. @$about_content->data_values->image,'700x700') }}" alt="@lang('about-image')">
                </div>
            </div>
            <div class="col-xl-6 offset-xl-1 col-md-6 mb-30">
                <div class="about-area">
                    <h2 class="section-title mb-20">{{__(@$about_content->data_values->heading)}}</h2>
                    <p>{{__(@$about_content->data_values->description)}}</p>
                    <div class="about-btn mt-40">
                        <a href="{{@$about_content->data_values->button_url}}" class="btn--base">{{__(@$about_content->data_values->button_name)}}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
