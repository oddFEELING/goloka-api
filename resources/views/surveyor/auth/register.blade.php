@extends('surveyor.layouts.master')

@section('content')
<div class="page-wrapper default-version">
    <div class="form-area bg_img bg_fixed" data-background="{{asset('assets/surveyor/images/1.jpg')}}">
        <div class="container p-0">
            <div class="row justify-content-center no-gutters">
                <div class="col-lg-10">
                    <div class="form-wrapper w-100">
                        <h4 class="logo-text mb-15">@lang('Welcome to') <strong>{{$general->sitename}}</strong></h4>
                        <p>{{$page_title}}</p>
                        <form action="{{ route('surveyor.regStatus') }}" method="POST" class="cmn-form mt-30 form-row" onsubmit="return submitUserForm();">
                            @csrf
                            <div class="form-group col-md-6">
                                <label for="firstname">@lang('First Name')</label>
                                <input type="text" name="firstname" class="form-control b-radius--capsule" id="firstname" value="{{ old('firstname') }}" required>
                                <i class="las la-user input-icon"></i>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="lastname">@lang('Last Name')</label>
                                <input type="text" name="lastname" class="form-control b-radius--capsule" id="lastname" value="{{ old('lastname') }}" required>
                                <i class="las la-user input-icon"></i>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="username">@lang('Username')</label>
                                <input type="text" name="username" class="form-control b-radius--capsule" id="username" value="{{ old('username') }}" required>
                                <i class="las la-user input-icon"></i>
                            </div>

                            <div class="form-group col-md-6 country-code">
                                <label for="lastname">@lang('Your Phone Number')</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text p-0 border-0">
                                            <select name="country_code">
                                                @include('partials.country_code')
                                            </select>
                                        </span>
                                    </div>
                                    <input type="text" name="mobile" class="form-control b-radius--capsule" placeholder="@lang('Your Phone Number')">
                                </div>
                            </div>

                            <div class="form-group col-md-6">
                                <label for="country">@lang('Country')</label>
                                <input type="text" name="country" class="form-control b-radius--capsule" readonly>
                                <i class="lab la-font-awesome-flag input-icon"></i>
                            </div>

                            <div class="form-group col-md-6">
                                <label for="email">@lang('Email')</label>
                                <input type="text" name="email" class="form-control b-radius--capsule" id="email" required>
                                <i class="lar la-envelope input-icon"></i>
                            </div>

                            <div class="form-group col-md-6">
                                <label for="password">@lang('Password')</label>
                                <input type="password" name="password" class="form-control b-radius--capsule" id="password" placeholder="@lang('Enter your password')" required>
                                <i class="las la-lock input-icon"></i>
                            </div>

                            <div class="form-group col-md-6">
                                <label for="password-confirm">@lang('Confirm Password')</label>
                                <input type="password" name="password_confirmation" class="form-control b-radius--capsule" id="password-confirm"
                                    placeholder="@lang('Confirm your password')" required>
                                <i class="las la-lock input-icon"></i>
                            </div>

                            <div class="form-group col-md-12">

                                @php echo recaptcha() @endphp

                            </div>
                            <div class="form-group col-md-12">
                                @include($activeTemplate.'partials.custom-captcha')
                            </div>

                            <div class="form-group col-md-12">
                                <button type="submit" id="recaptcha" class="submit-btn mt-25 b-radius--capsule">@lang('Register') <i
                                        class="las la-sign-in-alt"></i></button>
                            </div>
                            <div class="form-group mb-0 col-md-12">
                                <a href="{{route('surveyor.login')}}" class="text-muted text--small"><i class="las la-lock"></i>@lang('Already Have an account ?')</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('style')
    <style>
        .input-group .form-control.b-radius--capsule {
            border-radius: 0 999px 999px 0 !important;
        }
        .input-group .input-group-text select {
            padding:   7px 10px;
        }
        .input-group .input-group-prepend .input-group-text,
        .input-group .input-group-prepend select {
            border-radius: 999px 0 0 999px;
        }
    </style>
@endpush

@push('script')
    <script>
        'use strict';

        (function ($) {
            @if($country_code)
                var t = $(`option[data-code={{ $country_code }}]`).attr('selected','');
            @endif
                $('select[name=country_code]').on('change',function(){
                    $('input[name=country]').val($('select[name=country_code] :selected').data('country'));
                }).change();
        })(jQuery);

        function submitUserForm() {
                var response = grecaptcha.getResponse();
                if (response.length == 0) {
                    document.getElementById('g-recaptcha-error').innerHTML = '<span style="color:red;">@lang("Captcha field is required.")</span>';
                    return false;
                }
                return true;
            }
            function verifyCaptcha() {
                document.getElementById('g-recaptcha-error').innerHTML = '';
            }
    </script>
@endpush
