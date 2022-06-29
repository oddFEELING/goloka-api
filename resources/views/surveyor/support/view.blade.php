@extends('surveyor.layouts.app')

@section('panel')

    <div class="row">
        <div class="col-lg-12">

            <div class="card">
                <div class="card-body ">

                    <h6 class="card-title  mb-4">
                        <div class="row">
                            <div class="col-sm-8 col-md-6">
                                @if($my_ticket->status  == 0)
                                    <span class="badge badge--success py-1 px-2">@lang('Open')</span>
                                @elseif($my_ticket->status  == 1)
                                    <span class="badge badge--primary py-1 px-2">@lang('Answered')</span>
                                @elseif($my_ticket->status  == 2)
                                    <span class="badge badge--warning py-1 px-2">@lang('Your Replied')</span>
                                @elseif($my_ticket->status  == 3)
                                    <span class="badge badge--dark py-1 px-2">@lang('Closed')</span>
                                @endif
                                [@lang('Ticket')#{{ $my_ticket->ticket }}] {{ $my_ticket->subject }}
                            </div>
                            <div class="col-sm-4  col-md-6 text-sm-right mt-sm-0 mt-3">

                                <button class="btn btn-danger close-button" type="button" title="@lang('Close Ticket')" data-toggle="modal" data-target="#DelModal"><i class="fa fa-lg fa-times-circle"></i>
                                </button>
                            </div>
                        </div>
                    </h6>


                    @if($my_ticket->status != 4)
                        <form action="{{ route('ticket.reply', $my_ticket->id) }}" enctype="multipart/form-data" method="post" class="form-horizontal">
                            @csrf

                            <div class="row ">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <textarea class="form-control" name="message" rows="3" id="inputMessage" placeholder="@lang('Your Reply')"></textarea>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="row form-group">
                                        <div class="col-md-12">
                                            <label for="inputAttachments">@lang('Attachments')</label>
                                        </div>
                                        <div class="col-9">

                                            <div class="file-upload-wrapper" data-text="@lang('Select your file!')">
                                                <input type="file" name="attachments[]" id="inputAttachments"
                                                class="file-upload-field"/>
                                            </div>
                                            <div id="fileUploadsContainer"></div>
                                        </div>
                                        <div class="col-3">
                                            <button type="button" class="btn btn--primary" onclick="extraTicketAttachment()"><i class="fa fa-plus"></i></button>
                                        </div>
                                        <div class="col-md-12 ticket-attachments-message text-muted mt-3">
                                            @lang('Allowed File Extensions'): .@lang('jpg'), .@lang('jpeg'), .@lang('png'), .@lang('pdf'), .@lang('doc'), .@lang('docx')
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 offset-md-3">
                                    <button class="btn btn--primary btn-block mt-4" type="submit" name="replayTicket"
                                            value="1"><i class="la la-fw la-lg la-reply"></i> @lang('Reply')
                                    </button>
                                </div>
                            </div>
                        </form>
                    @endif


                    @foreach($messages as $message)
                        @if($message->admin_id == 0)

                            <div class="row border border-primary border-radius-3 py-3 mx-2 my-3">

                                <div class="col-md-3 border-right text-right">
                                    <h5 class="my-3">{{ $message->ticket->name }}</h5>
                                </div>

                                <div class="col-md-9">
                                    <p class="text-muted font-weight-bold my-3">
                                        @lang('Posted on') {{ $message->created_at->format('l, dS F Y @ H:i') }}</p>
                                    <p>{{ $message->message }}</p>
                                    @if($message->attachments()->count() > 0)
                                        <div class="my-3">
                                            @foreach($message->attachments as $k=> $image)
                                                <a href="{{route('surveyor.ticket.download',encrypt($image->id))}}" class="mr-3"><i class="fa fa-file"></i>@lang('Attachment') {{++$k}}</a>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="row border border-warning border-radius-3 my-3 py-3 mx-2 admin-bg-reply">

                                <div class="col-md-3 border-right text-right">
                                    <h5 class="my-3">{{ $message->admin->name }}</h5>
                                    <p class="lead text-muted">@lang('Staff')</p>
                                </div>

                                <div class="col-md-9">
                                    <p class="text-muted font-weight-bold my-3">
                                        @lang('Posted on') {{showDateTime($message->created_at,'l, dS F Y @ H:i') }}</p>
                                    <p>{{ $message->message }}</p>
                                    @if($message->attachments()->count() > 0)
                                        <div class="my-3">
                                            @foreach($message->attachments as $k=> $image)
                                                <a href="{{route('surveyor.ticket.download',encrypt($image->id))}}" class="mr-3"><i class="fa fa-file"></i>  @lang('Attachment') {{++$k}} </a>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>

                            </div>

                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </div>




    <div class="modal fade" id="DelModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"> @lang('Close Support Ticket!')</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                      <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <strong>@lang('Are you  want to Close This Support Ticket?')</strong>
                </div>
                <div class="modal-footer">
                    <form method="post" action="{{ route('ticket.reply', $my_ticket->id) }}">
                        @csrf
                        <button type="button" class="btn btn--secondary" data-dismiss="modal">@lang('No') </button>
                        <button type="submit" class="btn btn--primary" name="replayTicket" value="2"> @lang('Close Ticket') </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        "use strict";

        function extraTicketAttachment() {
            $("#fileUploadsContainer").append(`
            <div class="file-upload-wrapper" data-text="Select your file!"><input type="file" name="attachments[]" id="inputAttachments" class="file-upload-field"/></div>`)
        }
    </script>
@endpush
