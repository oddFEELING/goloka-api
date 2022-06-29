@extends('surveyor.layouts.app')
@section('panel')

    <div class="row">
        <div class="col-lg-12">
            <div class="card b-radius--10 ">
                <div class="card-body p-0">
                    <div class="table-responsive--md  table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                                <tr>
                                    <th scope="col">@lang('Sl')</th>
                                    <th scope="col">@lang('Category')</th>
                                    <th scope="col">@lang('Image')</th>
                                    <th scope="col">@lang('Name')</th>
                                    <th scope="col">@lang('Status')</th>
                                    <th scope="col">@lang('Total Questions')</th>
                                    <th scope="col">@lang('Posted At')</th>
                                    <th scope="col">@lang('Social Share')</th>
                                    <th scope="col">@lang('Set Question')</th>
                                </tr>
                            </thead>
                            <tbody>

                                @forelse($surveys as $item)
                                    <tr>
                                        <td data-label="@lang('SL')">{{$loop->index+1}}</td>
                                        <td data-label="@lang('Category')">{{__($item->category->name)}}</td>
                                        <td data-label="@lang('User')">
                                            <div class="user">
                                                <div class="thumb">
                                                    <img src="{{ getImage(imagePath()['survey']['path'].'/'.$item->image,imagePath()['survey']['size'])}}" alt="@lang('image')">
                                                </div>
                                            </div>
                                        </td>
                                        <td data-label="@lang('Name')">{{__($item->name)}}</td>
                                        <td data-label="@lang('Status')">
                                            @if ($item->status == 0)
                                                <span class="text--small badge font-weight-normal badge--success">@lang('Approved')</span>
                                            @elseif($item->status == 1)
                                                <span class="text--small badge font-weight-normal badge--warning">@lang('Pending')</span>
                                            @elseif($item->status == 3)
                                                <span class="text--small badge font-weight-normal badge--danger">@lang('Rejected')</span>
                                            @endif
                                        </td>
                                        <td data-label="@lang('Total Questions')">{{ $item->questions->count() }}</td>
                                        <td data-label="@lang('Posted At')">{{ showDateTime($item->created_at) }}</td>
                                        <td data-label="@lang('Social Share')">
                                            @if ($item->status == 0)
                                                <a href="http://www.facebook.com/sharer.php?u={{route('survey.share',$item->id)}}" class="icon-btn mr-1" data-toggle="tooltip" title="@lang('Facebook')" data-original-title="@lang('Questions')">
                                                    <i class="fab fa-facebook text--shadow"></i>
                                                </a>
                                                <a href="http://twitter.com/share?url=http://{{route('survey.share',$item->id)}}&text=Simple Share Buttons&hashtags=simplesharebuttons" class="icon-btn mr-1" data-toggle="tooltip" title="@lang('Twitter')" data-original-title="@lang('Questions')">
                                                    <i class="fab fa-twitter text--shadow"></i>
                                                </a>
                                                <a href="http://www.digg.com/submit?url={{route('survey.share',$item->id)}}" class="icon-btn mr-1" data-toggle="tooltip" title="@lang('Digg ')" data-original-title="@lang('Questions')">
                                                    <i class="fab fa-digg text--shadow"></i>
                                                </a>
                                                <a href="http://reddit.com/submit?url=http://{{route('survey.share',$item->id)}}&title={{$item->name}}" class="icon-btn mr-1" data-toggle="tooltip" title="@lang('Reddit ')" data-original-title="@lang('Questions')">
                                                    <i class="fab fa-reddit text--shadow"></i>
                                                </a>
                                                <a href="http://www.linkedin.com/shareArticle?mini=true&url=http://{{route('survey.share',$item->id)}}" class="icon-btn mr-1" data-toggle="tooltip" title="@lang('Linkedin')" data-original-title="@lang('Questions')">
                                                    <i class="fab fa-linkedin text--shadow"></i>
                                                </a>
                                                <a href="http://pinterest.com/pin/create/button/?url={{route('survey.share',$item->id)}}&description={{str_slug($item->name)}}" class="icon-btn mr-1" data-toggle="tooltip" title="@lang('Pinterest')" data-original-title="@lang('Questions')">
                                                    <i class="fab fa-pinterest text--shadow"></i>
                                                </a>
                                                <a href="http://www.stumbleupon.com/submit?url={{route('survey.share',$item->id)}}&title={{$item->name}}" class="icon-btn mr-1" data-toggle="tooltip" title="@lang('Stumbleupon')" data-original-title="@lang('Questions')">
                                                    <i class="fab fa-stumbleupon-circle text--shadow"></i>
                                                </a>
                                                <a href="mailto:?Subject={{$item->name}}&Body=I%20saw%20this%20and%20thought%20of%20you!%20 {{route('survey.share',$item->id)}}" class="icon-btn mr-1" data-toggle="tooltip" title="@lang('Email')" data-original-title="@lang('Questions')">
                                                    <i class="fas fa-envelope text--shadow"></i>
                                                </a>
                                            @endif
                                        </td>
                                        <td data-label="@lang('Action')">
                                            @if ($item->status != 3)
                                                <a href="{{route('surveyor.survey.edit',$item->id)}}" class="icon-btn mr-1" data-toggle="tooltip" title="@lang('Update')" data-original-title="@lang('Update')">
                                                    <i class="la la-pencil-alt text--shadow"></i>
                                                </a>
                                            @endif
                                            <a href="{{route('surveyor.survey.question.all',$item->id)}}" class="icon-btn" data-toggle="tooltip" title="@lang('Questions')" data-original-title="@lang('Questions')">
                                                <i class="fas fa-question-circle text--shadow"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-muted text-center" colspan="100%">{{ __($empty_message) }}</td>
                                    </tr>
                                @endforelse

                            </tbody>
                        </table><!-- table end -->
                    </div>
                </div>
                <div class="card-footer py-4">
                    {{ $surveys->links('admin.partials.paginate') }}
                </div>
            </div><!-- card end -->
        </div>
    </div>
@endsection

@push('breadcrumb-plugins')
    <a href="{{route('surveyor.survey.new')}}" class="btn btn-sm btn--primary box--shadow1 text--small"><i class="fa fa-fw fa-plus"></i>@lang('Add New')</a>
@endpush
