<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <!-- meta tags -->
    @include('includes.meta')

    <title>@yield('title', 'Index') | {{ config('app.name', 'Laraflow') }}</title>

    <!-- favicon -->
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset(config('app.favicon')) }}">

    <!-- vendor css -->
    @include('includes.plugin-css')

<!-- app css -->
    <link rel="stylesheet" href="{{ asset('/assets/css/app.min.css') }}">

    <!-- custom css -->
    @include('includes.page-css')

</head>
<body>
<!-- header -->
@include('partials.header')
<!--/. header -->

<!-- content -->
<div class="content content-fixed content-auth-alt">
    <div class="container ht-100p tx-center">
        <div class="ht-100p d-flex flex-column align-items-center justify-content-center">
            <div class="wd-70p wd-sm-250 wd-lg-450 mg-b-15">
                <img src="@yield('image','https://via.placeholder.com/1260x840')" class="img-fluid" alt="">
            </div>
            <h1 class="tx-color-01 tx-24 tx-sm-32 tx-lg-36 mg-xl-b-5">@yield('code') @yield('title')</h1>
            <h5 class="tx-16 tx-sm-18 tx-lg-20 tx-normal mg-b-20">@yield('message')</h5>
            <p class="tx-color-03 mg-b-30">We've been automatically alerted of the issue and will work to fix it asap.</p>
            <div class="mg-b-40"><button class="btn btn-white bd-2 pd-x-30">Back to Home</button></div>
            {{--@include('includes.error-search')--}}
        </div>
    </div><!-- container -->
</div>
<!--/. content -->

<!-- footer -->
@include('partials.footer')
<!--/. footer -->


<script src="{{ asset('/plugins/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('/plugins/feather-icons/feather.min.js') }}"></script>
<script src="{{ asset('/plugins/perfect-scrollbar/perfect-scrollbar.min.js') }}"></script>

<script src="{{ asset('/assets/js/app.js') }}"></script>

<!-- append theme customizer -->
<script src="{{ asset('/plugins/js-cookie/js.cookie.js') }}"></script>
<script src="{{ asset('/assets/js/app.settings.js') }}"></script>
<script>
    $(function () {
        'use script'

        window.darkMode = function () {
            $('.btn-white').addClass('btn-dark').removeClass('btn-white');
        }

        window.lightMode = function () {
            $('.btn-dark').addClass('btn-white').removeClass('btn-dark');
        }

        var hasMode = Cookies.get('df-mode');
        if (hasMode === 'dark') {
            darkMode();
        } else {
            lightMode();
        }
    });
</script>
</body>
</html>


{{--<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito&display=swap" rel="stylesheet">

    <!-- Styles -->
    <style>
        html, body {
            background-color: #fff;
            color: #636b6f;
            font-family: 'Nunito', sans-serif;
            font-weight: 100;
            height: 100vh;
            margin: 0;
        }

        .full-height {
            height: 100vh;
        }

        .flex-center {
            align-items: center;
            display: flex;
            justify-content: center;
        }

        .position-ref {
            position: relative;
        }

        .content {
            text-align: center;
        }

        .title {
            font-size: 36px;
            padding: 20px;
        }
    </style>
</head>
<body>
<div class="flex-center position-ref full-height">
    <div class="content">
        <div class="title">
            @yield('message')
        </div>
    </div>
</div>
</body>
</html>--}}
