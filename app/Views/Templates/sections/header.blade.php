<title>Al Mudheer</title>

<meta name="requestId" content="{{ \Illuminate\Support\Str::random(4) }}">
<meta name="description" content="{{ $sitename }}">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-touch-fullscreen" content="yes">
<meta name="theme-color" content="{{ $primaryColor }}">
<meta name="color-scheme" content="{{ $themeColorMode }}">
<meta name="theme" content="{{ $theme }}">
<meta name="identifier-URL" content="{!! BASE_URL !!}">
<meta name="product-version" content="{{ $version }}">

@dispatchEvent('afterMetaTags')

<link rel="shortcut icon" href="{!! BASE_URL !!}/assets/images/favicon.png"/>
<link rel="apple-touch-icon" href="{!! BASE_URL !!}/assets/images/apple-touch-icon.png">

<link rel="stylesheet" href="{!! BASE_URL !!}/dist/css/main.{!! $assetVersion !!}.min.css"/>
<link rel="stylesheet" href="{!! BASE_URL !!}/dist/css/app.{!! $assetVersion !!}.min.css"/>
@if($tpl->needsComponent('tiptap'))
<link rel="stylesheet" href="{!! BASE_URL !!}/dist/css/tiptap-editor.{!! $assetVersion !!}.min.css"/>
<link rel="stylesheet" href="{!! BASE_URL !!}/dist/css/katex.min.css"/>
@endif

@dispatchEvent('afterLinkTags')

<script src="{!! BASE_URL !!}/api/i18n?v={!! $version !!}"></script>

<script src="{!! BASE_URL !!}/dist/js/compiled-htmx.{!! $assetVersion !!}.min.js"></script>
<script src="{!! BASE_URL !!}/dist/js/compiled-htmx-extensions.{!! $assetVersion !!}.min.js"></script>

<!-- libs -->
<script src="{!! BASE_URL !!}/dist/js/compiled-frameworks.{!! $assetVersion !!}.min.js"></script>
<script src="{!! BASE_URL !!}/dist/js/compiled-framework-plugins.{!! $assetVersion !!}.min.js"></script>
<script src="{!! BASE_URL !!}/dist/js/compiled-global-component.{!! $assetVersion !!}.min.js"></script>
@if($tpl->needsComponent('calendar'))
<script src="{!! BASE_URL !!}/dist/js/compiled-calendar-component.{!! $assetVersion !!}.min.js"></script>
@endif
@if($tpl->needsComponent('table'))
<script src="{!! BASE_URL !!}/dist/js/compiled-table-component.{!! $assetVersion !!}.min.js"></script>
@endif
@if($tpl->needsComponent('tiptap'))
<script src="{!! BASE_URL !!}/dist/js/compiled-tiptap-toolbar.{!! $assetVersion !!}.min.js"></script>
<script src="{!! BASE_URL !!}/dist/js/compiled-tiptap-editor.{!! $assetVersion !!}.min.js"></script>
@endif
@if($tpl->needsComponent('gantt'))
<script src="{!! BASE_URL !!}/dist/js/compiled-gantt-component.{!! $assetVersion !!}.min.js"></script>
@endif
@if($tpl->needsComponent('chart'))
<script src="{!! BASE_URL !!}/dist/js/compiled-chart-component.{!! $assetVersion !!}.min.js"></script>
@endif

@dispatchEvent('afterScriptLibTags')

<!-- app -->
<script src="{!! BASE_URL !!}/dist/js/compiled-app.{!! $assetVersion !!}.min.js"></script>
@dispatchEvent('afterMainScriptTag')

<!--
//For future file based ref js loading
<script src="{!! BASE_URL !!}/dist/js/{{ ucwords(\Leantime\Core\Controller\Frontcontroller::getModuleName()) }}/Js/{{ \Leantime\Core\Controller\Frontcontroller::getModuleName() }}Controller.js"></script>
-->

<!-- theme & custom -->
@foreach ($themeScripts as $script)
    <script src="{!! $script !!}"></script>
@endforeach

@foreach ($themeStyles as $style)
    <link rel="stylesheet" @isset($style['id']) id="{{{ $style['id'] }}}" @endisset href="{!! $style['url'] !!}"/>
@endforeach

@dispatchEvent('afterScriptsAndStyles')

<!-- Replace main theme colors -->
<style id="colorSchemeSetter">
    @foreach ($accents as $accent)
        @if($accent !== false)
            :root {
                --accent{{ $loop->iteration }}: {{{ $accent }}};
            }
        @endif
    @endforeach
</style>

<style id="fontStyleSetter">
    :root {
        --primary-font-family: '{{{ $themeFont }}}', 'Helvetica Neue', Helvetica, sans-serif;
    }
</style>


<style id="backgroundImageSetter">
    @if(!empty($themeBg))
            .rightpanel {
                background-image: url({!! filter_var($themeBg, FILTER_SANITIZE_URL) !!});
                opacity: {{ $themeOpacity }};
                mix-blend-mode: {{ $themeType == 'image' ? 'normal' : 'multiply' }};
                background-size: var(--background-size, cover);
                background-position: center;
                background-attachment: fixed;
            }

    @if($themeType === 'image')
        .rightpanel:before {
            background: none;
        }
    @endif
    @endif
</style>


@dispatchEvent('afterThemeColors')


<script>
    window.leantime.currentProject = '{{ session("currentProject") }}';
</script>
