@extends('layouts.user')
@section('title', __('User Dashboard'))
@php
$has_sidebar = false;
$base_currency = base_currency();
$image = (gws('welcome_img_hide', 0)==0) ? 'welcome.png' : '';
@endphp

@section('content')
<div class="content-area user-account-dashboard">
    @include('layouts.messages')
    <div class="row">
        <div class="col-lg-4">
            {!! UserPanel::user_balance_card($contribution, ['vers' => 'side', 'class'=> 'card-full-height']) !!}
        </div>
        <div class="col-lg-4 col-md-6">
            {!! UserPanel::user_token_block('', ['vers' => 'buy']) !!}
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="account-info card card-full-height">
                <div class="card-innr">
                    {!! UserPanel::user_account_status() !!}
                    <div class="gaps-2x"></div>
                    {!! UserPanel::user_account_wallet() !!}
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="token-price-graph card card-full-height">
                <div class="card-innr">
                    <div class="card-head has-aside">
                        <div>
                            <h4 class="card-title card-title-sm">Token Price Graph</h4>
                            <div class="price-chart-price">
                                <div class="price-chart-label-container">
                                    <p class="price" id="price">${{token_calc(1, 'price')->$base_currency}}</p><span class="currency">USD</span>
                                </div>
                                <span class="change positive" id="price-change">$0.00 (+0.00%)</span>
                            </div>
                        </div>
                    </div>
                    <div class="chart-tokensale chart-tokensale-long">
                        <canvas id="tknPrice"></canvas>
                    </div>
                    <div class="price-chart-buttons">
                        <div class="chart-buttons">
                            <a href="{{ url()->current() }}?chart=1440">1 Day</a>
                            <a href="{{ url()->current() }}?chart=10080">1 Week</a>
                            <a class="active" href="{{ url()->current() }}?chart=43200">1 Month</a>
                            <a href="{{ url()->current() }}?chart=-1">All Time</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @if(get_page('home_top', 'status') == 'active')
        @if(gws('user_sales_progress', 1)==1)
        <div class="col-12 col-lg-5">
            {!! UserPanel::token_sales_progress('',  ['class' => 'card-full-height']) !!}
        </div>
        @endif
        <div class="col-12{{ (gws('user_sales_progress', 1)==1) ? ' col-lg-7' : '' }}">
            {!! UserPanel::content_block('welcome', ['image' => $image, 'class' => 'card-full-height']) !!}
        </div>
        @endif

    </div>
</div>
@push('footer')
<script>
    var minDecimalPlaces = 2
    var maxDecimalPrecision = 4

    function removeTrailingZeros(numStr, minDecimals = minDecimalPlaces) {
        numStr = numStr.toString()
        var numSplit = numStr.split(".")
        if (numSplit.length >= 2) return numStr
        var integerStr = numSplit[0]
        var trailingZerosMatch = integerStr.match(/0*$/)
        var trailingZerosStr = trailingZerosMatch[0]
        var precisionDecimalStr = integerStr.substring(0, trailingZerosMatch.index)
        trailingZerosStr = trailingZerosStr.substring(0, minDecimals - (integerStr.length - trailingZerosStr.length))
        return `${precisionDecimalStr}${trailingZerosStr}`
    }

    function formatNumber(num, minDecimals = minDecimalPlaces, maxPrecision = maxDecimalPrecision) {
        var numStr = Number.parseFloat(num).toString()
        var decimals = 0
        if (numStr.includes(".")) {
            decimals = numStr.split(".")[1].length
        }
        if (decimals < minDecimals) {
            if (decimals === 0) numStr = numStr + "."
            for (var i = decimals; i < minDecimals; i++) {
                numStr = numStr + "0"
            }
        }
        if (decimals > 0) {
            var numSplit = numStr.split(".")
            var integerStr = numSplit[0]
            var decimalStr = numSplit[1]
            var nonZeroDecimals = decimalStr.match(/[1-9][0-9]*/)[0]
            var nonZeroDecimalCount = nonZeroDecimals.length
            var zeroDecimals = decimalStr.match(/0*/)[0]
            var decimals = zeroDecimals + nonZeroDecimals
            if (integerStr !== "0" && decimals > maxPrecision) {
                decimals = decimals.substring(0, maxPrecision)
                numStr = `${integerStr}.${removeTrailingZeros(decimals, minDecimals)}`
            } else if (nonZeroDecimalCount > maxPrecision) {
                nonZeroDecimals = nonZeroDecimals.substring(0, maxPrecision)
                numStr = `${integerStr}.${zeroDecimals}${removeTrailingZeros(nonZeroDecimals, minDecimals)}`
            }
        }

        return numStr
    }

    var tnx_labels = {!! json_encode($transactions['chart']['time_alt']) !!};
    var tnx_data = {!! json_encode($transactions['chart']['price_alt']) !!};
    var theme_color = {
        base:"<?=theme_color('base')?>",
        text: "<?=theme_color('text')?>",
        heading: "<?=theme_color('heading')?>"
    };

    if ($("#tknPrice")) {
        document.getElementById("price").innerHTML = `$${formatNumber({{token_calc(1, 'price')->$base_currency}})}`

        var canvas = document.getElementById("tknPrice")
        var chart_element = canvas.getContext("2d");
        var priceChange = document.getElementById("price-change")

        function updateChangeText(prices) {
            var first = Number.parseFloat(prices[0])
            var last = Number.parseFloat(prices[prices.length - 1])
            var changeAbsolute = last - first
            var changeRelative = changeAbsolute * 100 / first
            var sign = changeAbsolute >= 0 ? "+" : "-"
            priceChange.innerHTML = `$${formatNumber(Math.abs(changeAbsolute))} (${sign}${formatNumber(Math.abs(changeRelative), 2, 2)}%)`
            priceChange.classList.remove("negative")
            priceChange.classList.remove("positive")
            if (changeAbsolute >= 0) {
                priceChange.classList.add("positive")
            } else {
                priceChange.classList.add("negative")
            }
        }

        var transparency = "44"
        function getBackgroundGradient() {
            var height = canvas.getBoundingClientRect().height
            var backgroundGradient = chart_element.createLinearGradient(0, 0, 0, height - 30)
            backgroundGradient.addColorStop(0, `${theme_color.base}${transparency}`)
            backgroundGradient.addColorStop(1, `transparent`)
            return backgroundGradient
        }
        updateChangeText(tnx_data)
        var chart = new Chart(chart_element, {
            type: "line",
            data: {
                labels: tnx_labels.map((date) => new Date(date).toLocaleDateString()),
                datasets: [
                    {
                        label: "",
                        tension: 0,
                        backgroundColor: getBackgroundGradient(),
					    borderWidth: 3,
                        borderColor: theme_color.base,
                        pointRadius: 0,
                        pointHitRadius: 10,
                        data: tnx_data,
                    },
                ],
            },
            options: {
                legend: { display: false },
                maintainAspectRatio: false,
                tooltips: {
                    callbacks: {
                        title: function (a, b) {
                            return "Date : " + b.labels[a[0].index];
                        },
                        label: function (a, b) {
							return `$${formatNumber( b.datasets[0].data[a.index])}`
						}
                    },
                    backgroundColor: "#f2f4f7",
                    titleFontSize: 13,
                    titleFontColor: theme_color.heading,
                    titleMarginBottom: 10,
                    bodyFontColor: theme_color.text,
                    bodyFontSize: 14,
                    bodySpacing: 4,
                    yPadding: 15,
                    xPadding: 15,
                    footerMarginTop: 5,
                    displayColors: false,
                },
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: true,
                            fontSize: 12,
                            fontColor: theme_color.text,
                            callback: (value, i, ticks) => {
                                return [0, ticks.length - 1].includes(i) ? `$${formatNumber(value)}` : undefined
                            }
                        },
                        gridLines: {
						    drawOnChartArea: false,
                            color: "#e9edf3",
                            tickMarkLength: 0,
                            zeroLineColor: "#e9edf3"
                        }
                    }],
                    xAxes: [{
                        ticks: {
                            display: false,
                            autoSkip: true,
                            maxRotation: 0,
                            minRotation: 0,
                            fontSize: 12,
                            fontColor: theme_color.text,
                            source: "auto",
                            callback: (value, i, ticks, data) => {
                                return [0, ticks.length - 1].includes(i) ? value : undefined
                            }
                        },
                        gridLines: {
						    drawOnChartArea: false,
                            color: "transparent",
                            tickMarkLength: 20,
                            zeroLineColor: "#e9edf3"
                        }
                    }],
                }
            }
        });

        function resizeChart() {
            chart.data.datasets[0].backgroundColor = getBackgroundGradient()
            chart.update()
        }

        window.addEventListener("resize", resizeChart)
        setTimeout(resizeChart, 500)

        $(".token-price-graph li a, .chart-buttons a").on("click", function (event) {
            $(".chart-buttons a").removeClass("active")
            $(this).addClass("active")

            event.preventDefault();
            var d = $(this),
                e = $(this).attr("href");
            $.get(e).done((a) => {
                chart.data.labels = Object.values(a.chart.time_alt);
                chart.data.datasets[0].data = Object.values(a.chart.price_alt);
                chart.update();
                d.parents(".token-price-graph").find("a.toggle-tigger").text(d.text());
                d.closest(".toggle-class").toggleClass("active");
                updateChangeText(a.chart.price_alt)
            });
        });
    }
</script>
@endpush

@endsection
