@extends('admin.layouts.app')
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
                                    <th scope="col">@lang('Survey Name')</th>
                                    <th scope="col">@lang('Surveyor')</th>
                                    <th scope="col">@lang('Status')</th>
                                    <th scope="col">@lang('Total Questions')</th>
                                    <th scope="col">@lang('Posted At')</th>
                                    <th scope="col">@lang('Set Question')</th>
                                </tr>
                            </thead>
                            <tbody>

                                @forelse($surveys as $item)
                                    <tr>
                                        <td data-label="@lang('SL')">{{$loop->index+1}}</td>
                                        <td data-label="@lang('Category')">{{__($item->category->name)}}</td>
                                        <td data-label="@lang('Image')">
                                            <div class="user">
                                                <div class="thumb">
                                                    <img src="{{ getImage(imagePath()['survey']['path'].'/'.$item->image,imagePath()['survey']['size'])}}" alt="@lang('image')">
                                                </div>
                                            </div>
                                        </td>
                                        <td data-label="@lang('Survey Name')">{{__($item->name)}}</td>
                                        <td data-label="@lang('Surveyor')">
                                            <a href="{{ route('admin.surveyors.detail', $item->surveyor->id) }}">{{ $item->surveyor->username }}</a>
                                        </td>
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
                                        <td data-label="@lang('Action')">

                                            <a href="{{route('admin.manage.survey.question.all',$item->id)}}" class="icon-btn mr-1" data-toggle="tooltip" title="@lang('Questions')" data-original-title="@lang('Questions')">
                                                <i class="fas fa-question-circle text--shadow"></i>
                                            </a>

                                            @if ($item->status == 1)
                                                <a href="javascript:void(0)" class="icon-btn btn--success mr-1 approveBtn" data-toggle="tooltip" title="@lang('Questions')" data-original-title="@lang('Approve')" data-url="{{route('admin.manage.survey.approve',$item->id)}}">
                                                    <i class="fas fa-check-circle text--shadow"></i>
                                                </a>

                                                <a href="javascript:void(0)" class="icon-btn btn--danger rejectBtn" data-toggle="tooltip" title="@lang('Reject')" data-original-title="@lang('Reject')" data-url="{{route('admin.manage.survey.reject',$item->id)}}">
                                                    <i class="las la-ban text--shadow"></i>
                                                </a>
                                            @endif
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

    {{-- Approve MODAL --}}
    <div class="modal fade" id="approveModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="myModalLabel">@lang('Approve Survey')</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                </div>
                <form method="post" action="">
                    @csrf
                    <div class="modal-body">
                        <p class="text-muted">@lang('Are you sure you want to Approve?')</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn--dark" data-dismiss="modal">@lang('Close')</button>
                        <button type="submit" class="btn btn--success approveButton">@lang('Approve')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- DELETE MODAL --}}
    <div class="modal fade" id="rejectModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="myModalLabel">@lang('Remove Survey')</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                </div>
                <form method="post" action="">
                    @csrf
                    <div class="modal-body">
                        <p class="text-muted">@lang('Are you sure you want to Reject?')</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn--dark" data-dismiss="modal">@lang('Close')</button>
                        <button type="submit" class="btn btn--danger deleteButton">@lang('Reject')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('breadcrumb-plugins')
    <form action="{{ route('admin.manage.survey.search', $scope ?? str_replace('admin.manage.survey.', '', request()->route()->getName())) }}" method="GET" class="form-inline float-sm-right bg--white">
        <div class="input-group has_append">
            <input type="text" name="search" class="form-control" placeholder="@lang('Survey category name')" value="{{ $search ?? '' }}">
            <div class="input-group-append">
                <button class="btn btn--primary" type="submit"><i class="fa fa-search"></i></button>
            </div>
        </div>
    </form>
@endpush

@push('script')
    <script>
        (function($){
            "use strict";

            $('.rejectBtn').on('click', function () {
                var modal = $('#rejectModal');
                var url = $(this).data('url');

                modal.find('form').attr('action', url);
                modal.modal('show');
            });

            $('.approveBtn').on('click', function () {
                var modal = $('#approveModal');
                var url = $(this).data('url');

                modal.find('form').attr('action', url);
                modal.modal('show');
            });
        })(jQuery);
    </script>
@endpush
