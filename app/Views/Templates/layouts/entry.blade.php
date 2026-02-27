<!DOCTYPE html>
<html dir="{{ __('language.direction') }}" lang="{{ __('language.code') }}">
<head>
    @include('global::sections.header')
    <style>
        .brandLogo { position: fixed; bottom: 10px; right: 10px; }
        .loginTopLogo {
            text-align: left;
            margin-bottom: 24px;
        }
        .loginTopLogo a,
        .loginTopLogo img {
            display: block;
        }
        .loginTopLogo img {
            max-width: 240px;
            height: auto;
        }
    </style>
    @stack('styles')
</head>

<body class="loginpage" style="height:100%;">

<div class="header hidden-gt-sm tw-p-[10px]" style="background:var(--header-gradient)">
    <a href="{!! BASE_URL !!}" target="_blank">
        <img src="{{ BASE_URL }}/assets/images/logo-login.png" class="tw-h-full "/>
    </a>
</div>

<div class="row" style="min-height:100vh; max-width: 98vw; height: auto;">
    <div class="col-md-4 hidden-phone regLeft">

        <div class="welcomeContent">
            @dispatchFilter('welcomeText', '<h1 class="mainWelcome">'.$language->__("headlines.welcome_back").'</h1>')
        </div>

    </div>
    <div class="col-md-8 col-sm-12 regRight">

        <div class="regpanel">
            <div class="regpanelinner">

                <?php $displayLogoPath = BASE_URL.'/assets/images/logo-login.png'; ?>
                <div class="loginTopLogo">
                    <a href="{!! BASE_URL !!}" target="_blank">
                        <img src="{{ $displayLogoPath }}" />
                    </a>
                </div>

                @isset($action, $module)
                    @include("$module::$action")
                @else
                    @yield('content')
                @endisset
            </div>
        </div>

    </div>
    <div class="brandLogo">
        <img style="height: 25px;" src="{!! BASE_URL !!}/assets/images/logo-login.png">
    </div>
</div>

@include('global::sections.pageBottom')
@stack('scripts')
</body>

</html>
