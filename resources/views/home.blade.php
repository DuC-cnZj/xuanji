<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{config('app.name')}}</title>
        <link rel="stylesheet" href="{{ mix("css/app.css") }}">
        <style>
            body {
                height: 100vh;
            }
        </style>
    </head>
    <body>
        <div id="app">
            <nav-bar app-name="{{config("app.name")}}" user-name="{{auth()->user()->user_name}}" csrf="{{csrf_token()}}"></nav-bar>
            <div class="apps">
                <app-cards/>
            </div>
        </div>
    </body>
    <script>
        window.MIX_SHELL_SOCKET_URL = @json(env("MIX_SHELL_SOCKET_URL"));
        window.MIX_NS_PREFIX = @json(env("MIX_NS_PREFIX"));
    </script>
    <script src="{{ mix('js/app.js') }}"></script>
</html>
