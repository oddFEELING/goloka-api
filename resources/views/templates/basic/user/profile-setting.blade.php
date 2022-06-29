@extends($activeTemplate.'layouts.master')
@section('content')

    <div class="user-profile-area mt-30">
        <form action="" method="post" enctype="multipart/form-data">
            @csrf
            <div class="row justify-content-center mb-30-none">
                <div class="col-xl-5 col-md-12 col-sm-12 mb-30">
                    <div class="panel panel-default">
                        <div class="panel-heading d-flex flex-wrap align-items-center justify-content-between">
                            <div class="panel-title"><i class="las la-user"></i> @lang('User Details')</div>
                            <div class="panel-options">
                                <a href="#" data-rel="collapse"><i class="las la-chevron-circle-down"></i></a>
                            </div>
                        </div>
                        <div class="panel-body">
                            <div class="panel-body-inner">
                                <div class="profile-thumb-area text-center">
                                    <div class="profile-thumb">
                                        <div class="image-preview bg_img" data-background="{{ getImage(imagePath()['profile']['user']['path'].'/'. $user->image,imagePath()['profile']['user']['size']) }}"></div>
                                    </div>
                                    <div class="profile-edit">
                                        <input type="file" name="image" id="imageUpload" class="upload" accept=".png, .jpg, .jpeg">
                                        <div class="rank-label">
                                            <label for="imageUpload" class="imgUp bg--primary">
                                                @lang('Upload Image')
                                            </label>
                                        </div>
                                    </div>
                                    <div class="profile-content-area text-center mt-20">
                                        <h3 class="name">{{__($user->fullname)}}</h3>
                                        <h5 class="email">@lang('E-Mail') : {{__($user->email)}}</h5>
                                        <h5 class="phone">@lang('Phone') : {{$user->mobile}}</h5>
                                        <h5 class="address">@lang('Country') : {{$user->address->country}}</h5>
                                        <h5 class="reference">@lang('Balance') : <span class="badge badge--primary text-white">{{getAmount($user->balance)}}</span></h5>
                                        <h5 class="reference">@lang('Status') : <span class="badge badge--primary text-white">@lang('Active')</span></h5>

                                        <a href="#0" class="btn btn--success text-white btn-rounded btn-block btn-icon icon-left mt-20"
                                            data-clipboard-text="bbakaHwKsaMc">
                                            <i class="las la-clipboard-check"></i> @lang('Status : Active')
                                        </a>

                                        <div class="profile-footer-btn mt-10">
                                            <div class="row mb-10-none">
                                                <div class="col-md-6 col-sm-12 mb-10">
                                                    <a href="{{ route('user.change-password') }}"
                                                        class="btn btn--primary  text-white btn-rounded btn-block btn-icon icon-left"><i
                                                            class="las la-lock"></i> @lang('Change Password')</a>
                                                </div>
                                                <div class="col-md-6 col-sm-12 mb-10">
                                                    <a href="{{route('user.survey')}}" class="btn btn--primary text-white btn-rounded btn-block btn-icon icon-left"><i class="lar la-question-circle"></i> @lang('Start Survey')</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-7 col-md-12 col-sm-12 mb-30">
                    <div class="panel panel-default">
                        <div class="panel-heading d-flex flex-wrap align-items-center justify-content-between">
                            <div class="panel-title"><i class="las la-user"></i> @lang('User Form')</div>
                            <div class="panel-options-form">
                                <a href="#" data-rel="collapse"><i class="las la-chevron-circle-down"></i></a>
                            </div>
                        </div>
                        <div class="panel-form-area">
                            <div class="row justify-content-center">
                                <div class="col-lg-6 form-group">
                                    <label>@lang('First Name') <span class="text-danger">*</span></label>
                                    <input type="text" name="firstname" class="form-control" value="{{$user->firstname}}" required>
                                </div>
                                <div class="col-lg-6 form-group">
                                    <label>@lang('Last Name') <span class="text-danger">*</span></label>
                                    <input type="text" name="lastname" class="form-control" value="{{$user->lastname}}" required>
                                </div>
                                <div class="col-lg-6 form-group">
                                    <label>@lang('Email Address')</label>
                                    <input type="email" class="form-control" value="{{$user->email}}" readonly>
                                </div>
                                <div class="col-lg-6 form-group">
                                    <label>@lang('Mobile Number')</label>
                                    <input type="hidden" id="track" name="country_code">
                                    <input type="tel" class="form-control" value="{{$user->mobile}}" placeholder="@lang('Your Contact Number')" readonly>
                                </div>

                                <div class="col-lg-6 form-group">
                                    <label>@lang('Age')</label>
                                    <input type="text" class="form-control" value="{{$user->age}}" disabled>
                                </div>

                                <div class="col-lg-6 form-group">
                                    <label>@lang('Profession')</label>
                                    <input type="text" class="form-control" value="{{$user->profession}}" disabled>
                                </div>

                                <div class="col-lg-6 form-group">
                                    <label>@lang('Address') <span class="text-danger">*</span></label>
                                    <input type="text" name="address" class="form-control" placeholder="@lang('Address')" value="{{@$user->address->address}}" required>
                                </div>
                                <div class="col-lg-6 form-group">
                                    <label>@lang('State') <span class="text-danger">*</span></label>
                                    <input type="text" name="state" class="form-control" placeholder="@lang('state')" value="{{@$user->address->state}}" required>
                                </div>
                                <div class="col-lg-6 form-group">
                                    <label>@lang('Zip Code') <span class="text-danger">*</span></label>
                                    <input type="text" name="zip" class="form-control" placeholder="@lang('Zip Code')" value="{{@$user->address->zip}}" required>
                                </div>
                                <div class="col-lg-6 form-group">
                                    <label>@lang('City') <span class="text-danger">*</span></label>
                                    <input type="text" name="city" class="form-control" placeholder="@lang('city')" value="{{@$user->address->city}}" required>
                                </div>
                                <div class="col-lg-12 form-group text-center">
                                    <button type="submit" class="submit-btn">@lang('Update')</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

