@extends($activeTemplate.'layouts.master')
@section('content')

    <div class="dashboard-area mt-30">
        <div class="panel-card-header section--bg text-white">
            <div class="panel-card-title"><i class="las la-cloud-upload-alt"></i> @lang('User Activity')</div>
        </div>
        <div class="panel-card-body">
            <div class="row justify-content-center mb-30-none">
                <div class="col-xl-3 col-md-6 col-sm-8 mb-30">
                    <div class="dashboard-item bg--danger">
                        <div class="dashboard-content">
                            <div class="dashboard-icon">
                                <i class="las la-wallet"></i>
                            </div>
                            <div class="num text-white" data-start="0" data-end="0" data-postfix=""
                                data-duration="1500" data-delay="0">{{getAmount($user->balance)}} {{$general->cur_text}}</div>
                            <h3 class="title text-white">@lang('Total Balance')</h3>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 col-sm-8 mb-30">
                    <div class="dashboard-item bg--success">
                        <div class="dashboard-content">
                            <div class="dashboard-icon">
                                <i class="las la-ticket-alt"></i>
                            </div>
                            <div class="num text-white" data-start="0" data-end="0" data-postfix=""
                                data-duration="1500" data-delay="0">{{getAmount($user->completed_survey)}}</div>
                            <h3 class="title text-white">@lang('Completed Survey')</h3>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 col-sm-8 mb-30">
                    <div class="dashboard-item bg--warning">
                        <div class="dashboard-content">
                            <div class="dashboard-icon">
                                <i class="las la-address-card"></i>
                            </div>
                            <div class="num text-white" data-start="0" data-end="0" data-postfix=""
                                data-duration="1500" data-delay="0">{{getAmount($totalWithdraw)}} {{$general->cur_text}}</div>
                            <h3 class="title text-white">@lang('Total Withdarw')</h3>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 col-sm-8 mb-30">
                    <div class="dashboard-item bg--primary">
                        <div class="dashboard-content">
                            <div class="dashboard-icon">
                                <i class="las la-th-list"></i>
                            </div>
                            <div class="num text-white" data-start="0" data-end="0" data-postfix=""
                                data-duration="1500" data-delay="0">{{$totalTransaction}}</div>
                            <h3 class="title text-white">@lang('Total Transaction')</h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row justify-content-center mb-30-none mt-30">
                <div class="col-xl-12 col-md-12 col-sm-12 mb-30">
                    <div class="chart-area">
                        <div class="chart-scroll">
                            <div class="chart-wrapper m-0">
                                <canvas id="myChart" width="400" height="150"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection

@push('script')
<!--chart js-->
@php
    $months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    $itr = 0;
@endphp
<script src="{{asset($activeTemplateTrue.'js/chart.js')}}"></script>
<script>
   var config = {
			type: 'line',
			data: {
				labels: @php echo json_encode($months) @endphp,
				datasets: [{
					label: '@lang('Amount')',
					backgroundColor: '#{{$general->base_color}}',
					borderColor: '#{{$general->base_color}}',
					data: [
                        @foreach($months as $k => $month)
                            @if(@$withdraw_chart_data[$itr]['month'] == $month)
                                {{ @$withdraw_chart_data[$itr]['amount'] }},
                                @php $itr++; @endphp
                            @else
                                0,
                            @endif
                        @endforeach
                    ],
					fill: false,
				}]
			},
			options: {
				responsive: true,
				title: {
					display: true,
					text: '@lang('Withdraw Data Monthly')'
				},
				scales: {
					yAxes: [{
						ticks: {
							// the data minimum used for determining the ticks is Math.min(dataMin, suggestedMin)
							suggestedMin: 10,

							// the data maximum used for determining the ticks is Math.max(dataMax, suggestedMax)
							suggestedMax: 50
						}
					}]
				}
			}
		};

		window.onload = function() {
			var ctx = document.getElementById('myChart').getContext('2d');
			window.myLine = new Chart(ctx, config);
		};
</script>
@endpush

