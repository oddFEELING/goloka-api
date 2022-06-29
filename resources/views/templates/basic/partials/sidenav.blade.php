<div class="sidebar-menu section--bg bg-overlay-black bg_img" data-background="{{asset($activeTemplateTrue.'images/banner.svg')}}">
    <div class="sidebar-menu-inner">
        <div class="logo-env">
            <div class="logo">
                <a href="{{route('home')}}">
                    <img src="{{getImage(imagePath()['logoIcon']['path'] .'/logo.png')}}" width="120" alt="@lang('logo')">
                </a>
            </div>
            <div class="sidebar-collapse">
                <a href="#" class="sidebar-collapse-icon">
                    <i class="las la-bars"></i>
                </a>
            </div>
            <div class="sidebar-mobile-menu">
                <a href="#" class="with-animation">
                    <i class="las la-bars"></i>
                </a>
            </div>
        </div>
        <ul id="sidebar-main-menu" class="sidebar-main-menu">
            <li class="sidebar-single-menu nav-item {{sidenavActive('user.home',2)}}">
                <a href="{{route('user.home')}}">
                    <i class="lab la-buffer"></i>
                    <span class="title">@lang('Dashboard')</span>
                </a>
            </li>
            <li class="sidebar-single-menu nav-item {{sidenavActive('user.survey*',2)}}">
                <a href="{{route('user.survey')}}">
                    <i class="lar la-question-circle"></i>
                    <span class="title">@lang('Start Survey')</span>
                </a>
            </li>

            <li class="sidebar-single-menu nav-item {{sidenavActive('user.withdraw',2)}}">
                <a href="{{route('user.withdraw')}}">
                    <i class="la la-bank"></i>
                    <span class="title">@lang('Withdraw Now')</span>
                </a>
            </li>

            <li class="sidebar-single-menu nav-item {{sidenavActive('user.withdraw.history',2)}}">
                <a href="{{route('user.withdraw.history')}}">
                    <i class="las la-history"></i>
                    <span class="title">@lang('Withdraw History')</span>
                </a>
            </li>

            <li class="sidebar-single-menu nav-item {{sidenavActive('user.transaction*',2)}}">
                <a href="{{route('user.transaction')}}">
                    <i class="las la-exchange-alt"></i>
                    <span class="title">@lang('Transaction')</span>
                </a>
            </li>

            <li class="sidebar-single-menu has-sub nav-item {{sidenavActive('ticket*',1)}}">
                <a href="#" class="open-icon-link">
                    <i class="las la-ticket-alt"></i><span class="title"> @lang('Support Ticket')</span>
                </a>
                <ul class="sidebar-submenu">
                    <li class="nav-item {{sidenavActive('ticket.open',2)}}">
                        <a href="{{route('ticket.open')}}">
                            <i class="las la-dot-circle"></i><span class="title"> @lang('Create New')</span>
                        </a>
                    </li>
                    <li class="nav-item {{sidenavActive('ticket',2)}}">
                        <a href="{{route('ticket')}}">
                            <i class="las la-dot-circle"></i><span class="title"> @lang('My Tickets')</span>
                        </a>
                    </li>
                </ul>
            </li>
            <li class="sidebar-single-menu nav-item {{sidenavActive('user.twofactor',2)}}">
                <a href="{{ route('user.twofactor') }}">
                    <i class="las la-shield-alt"></i>
                    <span class="title">@lang('2FA Security')</span>
                </a>
            </li>
            <li class="sidebar-single-menu nav-item {{sidenavActive('user.profile-setting',2)}}">
                <a href="{{route('user.profile-setting')}}">
                    <i class="las la-user-circle"></i>
                    <span class="title">@lang('Profile Setting')</span>
                </a>
            </li>
            <li class="sidebar-single-menu nav-item {{sidenavActive('user.change-password',2)}}">
                <a href="{{ route('user.change-password') }}">
                    <i class="las la-lock"></i>
                    <span class="title">@lang('Change Password')</span>
                </a>
            </li>
            <li class="sidebar-single-menu nav-item}">
                <a href="{{ route('user.logout') }}">
                    <i class="las la-power-off"></i>
                    <span class="title">@lang('Logout')</span>
                </a>
            </li>
        </ul>
    </div>
</div>
