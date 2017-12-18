<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Styles -->
    <style>
        .pre-loader {position: fixed; height: 100%;width: 100%; background-color: #ffffff;z-index: 999;}
        .pre-loader-spinner {border: 4px solid transparent;border-top: 4px solid #1e70bf;border-radius: 50%;-moz-border-radius: 50%;-webkit-border-radius: 50%;width: 80px;height: 80px;animation: pre-loader-spin 1s linear infinite;-moz-animation: pre-loader-spin 1s linear infinite;-webkit-animation: pre-loader-spin 1s linear infinite;position: absolute;top: calc(50% - 40px);left: calc(50% - 40px);}
        @keyframes pre-loader-spin {0% { transform: rotate(0deg); -webkit-transform: rotate(0deg); -moz-transform: rotate(0deg);}100% { transform: rotate(360deg); -webkit-transform: rotate(360deg); -moz-transform: rotate(360deg);}}
    </style>
</head>
<body>
<div class="pre-loader"><span class="pre-loader-spinner"></span></div>
<div id="main-app">
    @yield('content')
</div>

<!-- Scripts -->
<link href="{{ asset('css/app.css') }}" rel="stylesheet">
<script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
