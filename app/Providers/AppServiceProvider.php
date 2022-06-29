<?php

namespace App\Providers;

use App\Models\Deposit;
use App\Models\GeneralSetting;
use App\Models\Language;
use App\Models\Page;
use App\Models\Extension;
use App\Models\User;
use App\Models\Frontend;
use App\Models\SupportTicket;
use App\Models\Survey;
use App\Models\Surveyor;
use App\Models\Withdrawal;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        paginateMacro();
        $activeTemplate = activeTemplate();

        $viewShare['general'] = GeneralSetting::first();
        $viewShare['activeTemplate'] = $activeTemplate;
        $viewShare['activeTemplateTrue'] = activeTemplate(true);
        $viewShare['language'] = Language::all();
        $viewShare['pages'] = Page::where('tempname',$activeTemplate)->where('slug','!=','home')->get();
        view()->share($viewShare);


        view()->composer('admin.partials.sidenav', function ($view) {
            $view->with([
                'banned_users_count'           => User::banned()->count(),
                'email_unverified_users_count' => User::emailUnverified()->count(),
                'sms_unverified_users_count'   => User::smsUnverified()->count(),
                'pending_ticket_count'         => SupportTicket::whereIN('status', [0,2])->count(),
                'pending_deposits_count'    => Deposit::pending()->count(),
                'pending_withdraw_count'    => Withdrawal::pending()->count(),

                'banned_surveyors_count'           => Surveyor::banned()->count(),
                'email_unverified_surveyors_count' => Surveyor::emailUnverified()->count(),
                'sms_unverified_surveyors_count'   => Surveyor::smsUnverified()->count(),

                'pending_survey_count' => Survey::where('status',1)->whereHas('surveyor', function($q){
                    $q->where('status',1);
                })->count()
            ]);
        });

        view()->composer('partials.seo', function ($view) {
            $seo = Frontend::where('data_keys', 'seo.data')->first();
            $view->with([
                'seo' => $seo ? $seo->data_values : $seo,
            ]);
        });

        view()->composer([$activeTemplate.'partials.footer',$activeTemplate.'layouts.master','surveyor.report.report'], function ($view) {
            $footer_content = Frontend::where('data_keys', 'footer.content')->first();
            $view->with([
                'footer_content' => $footer_content,
            ]);
        });

    }
}
