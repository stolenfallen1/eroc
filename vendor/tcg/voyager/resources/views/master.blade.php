<!DOCTYPE html>
<html lang="{{ config('app.locale') }}" dir="{{ __('voyager::generic.is_rtl') == 'true' ? 'rtl' : 'ltr' }}">

<head>
    <title>@yield('page_title', setting('admin.title') . ' - ' . setting('admin.description'))</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <meta name="assets-path" content="{{ route('voyager.voyager_assets') }}" />

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,700" rel="stylesheet">
    <!-- Favicon -->
    <?php $admin_favicon = Voyager::setting('admin.icon_image', ''); ?>
    @if ($admin_favicon == '')
        <link rel="shortcut icon" href="{{ voyager_asset('images/logo-icon.png') }}" type="image/png">
    @else
        <link rel="shortcut icon" href="{{ Voyager::image($admin_favicon) }}" type="image/png">
    @endif


    <!-- App CSS -->
    <link rel="stylesheet" href="{{ voyager_asset('css/app.css') }}">

    @yield('css')
    @if (__('voyager::generic.is_rtl') == 'true')
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-rtl/3.4.0/css/bootstrap-rtl.css">
        <link rel="stylesheet" href="{{ voyager_asset('css/rtl.css') }}">
    @endif

    <!-- Few Dynamic Styles -->
    <style type="text/css">
        .voyager .side-menu .navbar-header {
            background: {{ config('voyager.primary_color', '#22A7F0') }};
            border-color: {{ config('voyager.primary_color', '#22A7F0') }};
        }

        .widget .btn-primary {
            border-color: {{ config('voyager.primary_color', '#22A7F0') }};
        }

        .widget .btn-primary:focus,
        .widget .btn-primary:hover,
        .widget .btn-primary:active,
        .widget .btn-primary.active,
        .widget .btn-primary:active:focus {
            background: {{ config('voyager.primary_color', '#22A7F0') }};
        }

        .voyager .breadcrumb a {
            color: {{ config('voyager.primary_color', '#22A7F0') }};
        }

        .voyager .side-menu.sidebar-inverse .navbar li>a {
            color: #ffffff !important;
        }

        .app-container .content-container .side-menu .navbar-nav li.dropdown li.dropdown div>ul>li>a {
            padding: 0 4.0em;
        }

        .app-container .content-container .side-menu .navbar-nav li.dropdown div>ul>li>a {
            padding: 0 2.0em;
        }

        .app-container .content-container .side-menu .navbar-nav li a .icon {
            font-size: 15px !important;
        }

        .app-container .content-container .side-menu .navbar-nav li.dropdown ul li a {
            height: 40px !important;
            line-height: 44px !important;
            padding: 0 1em;
            vertical-align: middle;
        }

        .app-container .content-container .side-menu .navbar-nav li a .title {
            padding-left: 0px !important;
        }

        .navbar-fixed-top {
            background: #ffffff !important;
        }

        .app-container .content-container .side-menu .navbar-nav li a {
            display: block;
            font-family: Open Sans, sans-serif;
            font-size: 13px !important;
        }

        .navbar-nav li a .icon {
            display: inline-block;
            font-size: 1.1em;
            margin-left: 0;
            text-align: center;
            width: 25px !important;
        }

        .roles .panel-title {
            display: block;
            font-size: 15px;
            padding: 10px 10px 10px 10px;
            text-align: left;
        }

        .roles .panel {
            background-color: #fff;
            border: 1px solid transparent;
            border-radius: 4px;
            box-shadow: 0 2px 10px rgb(0 0 0 / 5%);
            margin-bottom: 5px !important;
        }

        .checkbox label,
        .radio label {

            padding-left: 0px;
        }

        .roles .table>tbody>tr>td,
        .table>tbody>tr>th,
        .table>tfoot>tr>td,
        .table>tfoot>tr>th,
        .table>thead>tr>td {
            border-top: 1px solid #ddd;
            line-height: 1.428571429;
            padding: 8px 0px 10px 30px;
            vertical-align: top;
        }

        .voyager .table>thead>tr>th {
            background: #549dc5;
            border-color: #d4d1d1;
        }

        .table>tfoot>tr>th,
        .table>thead>tr>th {
            color: #f7f7f8;
            font-weight: 400;
        }

        .table th a {
            color: #e8eef4;
        }

        .panel-bordered>.panel-body {
            overflow: hidden;
            padding: 30px 10px 10px;
        }

        .tab-content>div {
            padding: 5px;
        }

        .voyager .nav-tabs,
        .voyager .nav-tabs>li>a:hover {
            background-color: #ffffff;
        }

        .nav-tabs>li.active>a,
        .nav-tabs>li.active>a:focus,
        .nav-tabs>li.active>a:hover {
            background-color: #ffffff;
            border-color: transparent transparent #62a8ea;
            color: #191818;
            font-weight: 900;
        }

        .usermanager .row>[class*=col-] {
            margin-bottom: 2px;
        }

        .usermanager .panel-bordered>.panel-body {
            overflow: hidden;
            padding: 5px 5px 5px 5px;
        }

        .usermanager .table>tbody>tr>td,
        .table>tbody>tr>th,
        .table>tfoot>tr>td,
        .table>tfoot>tr>th,
        .table>thead>tr>td {
            border-top: none;
            line-height: 1.428571429;
            padding: 2px 0px 0px 0px;
            vertical-align: middle;
        }

        .usermanager .table>tbody>tr>th label {
            margin-top: 5px;
            vertical-align: middle;
            font-weight: 500;
            width: 100%;
        }

        .usermanager .table>tbody>tr>th {
            width: 30%;
        }

        .usermanager .table>tbody>tr>td {
            width: 70%;
        }

        .dnone {
            display: none !important;
        }

        .form-control {
            background-color: #fff;
            background-image: none;
            border: 1px solid #b5b8b9;
            color: #1c1e1f;
        }

        .panel-body .select2-selection {
            border: 1px solid #b5b8b9;
        }

        hr {
            border: 0;
            border-top: 1px solid #eee;
            margin-bottom: 10px;
            margin-top: 10px;
        }
    </style>

    @if (!empty(config('voyager.additional_css')))
        <!-- Additional CSS -->

        @foreach (config('voyager.additional_css') as $css)
            <link rel="stylesheet" type="text/css" href="{{ asset($css) }}">
        @endforeach
    @endif

    @yield('head')
</head>

<body class="voyager @if (isset($dataType) && isset($dataType->slug)) {{ $dataType->slug }} @endif">

    <div id="voyager-loader">
        <?php $admin_loader_img = Voyager::setting('admin.loader', ''); ?>
        @if ($admin_loader_img == '')
            <img src="{{ voyager_asset('images/logo-icon.png') }}" alt="Voyager Loader">
        @else
            <img src="{{ Voyager::image($admin_loader_img) }}" alt="Voyager Loader">
        @endif
    </div>

    <?php
    if (\Illuminate\Support\Str::startsWith(Auth::user()->avatar, 'http://') || \Illuminate\Support\Str::startsWith(Auth::user()->avatar, 'https://')) {
        $user_avatar = Auth::user()->avatar;
    } else {
        $user_avatar = Voyager::image(Auth::user()->avatar);
    }
    ?>

    <div class="app-container">
        <div class="fadetoblack visible-xs"></div>
        <div class="row content-container">

            @include('voyager::dashboard.navbar')
            @include('voyager::dashboard.sidebar')

            <script>
                (function() {
                    var appContainer = document.querySelector('.app-container'),
                        sidebar = appContainer.querySelector('.side-menu'),
                        navbar = appContainer.querySelector('nav.navbar.navbar-top'),
                        loader = document.getElementById('voyager-loader'),
                        hamburgerMenu = document.querySelector('.hamburger'),
                        sidebarTransition = sidebar.style.transition,
                        navbarTransition = navbar.style.transition,
                        containerTransition = appContainer.style.transition;

                    sidebar.style.WebkitTransition = sidebar.style.MozTransition = sidebar.style.transition =
                        appContainer.style.WebkitTransition = appContainer.style.MozTransition = appContainer.style.transition =
                        navbar.style.WebkitTransition = navbar.style.MozTransition = navbar.style.transition = 'none';

                    if (window.innerWidth > 768 && window.localStorage && window.localStorage['voyager.stickySidebar'] ==
                        'true') {
                        appContainer.className += ' expanded no-animation';
                        loader.style.left = (sidebar.clientWidth / 2) + 'px';
                        hamburgerMenu.className += ' is-active no-animation';
                    }

                    navbar.style.WebkitTransition = navbar.style.MozTransition = navbar.style.transition = navbarTransition;
                    sidebar.style.WebkitTransition = sidebar.style.MozTransition = sidebar.style.transition = sidebarTransition;
                    appContainer.style.WebkitTransition = appContainer.style.MozTransition = appContainer.style.transition =
                        containerTransition;
                })();
            </script>
            <!-- Main Content -->
            <div class="container-fluid">
                <div class="side-body padding-top">
                    @yield('page_header')
                    <div id="voyager-notifications"></div>
                    @yield('content')
                </div>
            </div>


        </div>
    </div>
    @include('voyager::partials.app-footer')

    <!-- Javascript Libs -->


    <script type="text/javascript" src="{{ voyager_asset('js/app.js') }}"></script>

    <script>
        @if (Session::has('alerts'))
            let alerts = {!! json_encode(Session::get('alerts')) !!};
            helpers.displayAlerts(alerts, toastr);
        @endif

        @if (Session::has('message'))

            // TODO: change Controllers to use AlertsMessages trait... then remove this
            var alertType = {!! json_encode(Session::get('alert-type', 'info')) !!};
            var alertMessage = {!! json_encode(Session::get('message')) !!};
            var alerter = toastr[alertType];

            if (alerter) {
                alerter(alertMessage);
            } else {
                toastr.error("toastr alert-type " + alertType + " is unknown");
            }
        @endif

        $('.panel-collapse').on('hide.bs.collapse', function(e) {
            if ($(event.target).parent().hasClass('collapsed')) {
                e.stopPropagation();
                e.preventDefault();
            }
        });
    </script>
    @include('voyager::media.manager')
    @yield('javascript')
    @stack('javascript')
    @if (!empty(config('voyager.additional_js')))
        <!-- Additional Javascript -->
        @foreach (config('voyager.additional_js') as $js)
            <script type="text/javascript" src="{{ asset($js) }}"></script>
        @endforeach
    @endif

</body>

</html>
