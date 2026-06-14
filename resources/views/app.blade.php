<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class([\App\Support\WorkspaceAppearance::htmlColorModeClass(\App\Support\WorkspaceAppearance::fromWorkspace(session('central_workspace')))])>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Complaint Desk') }}</title>
    @php($appearance = \App\Support\WorkspaceAppearance::fromWorkspace(session('central_workspace')))
    <script>
        (function () {
            var mode = @json($appearance['color_mode'] ?? 'light');
            if (mode === 'dark' || (mode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
    <style>{!! \App\Support\WorkspaceAppearance::cssVariablesBlock($appearance) !!}</style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-shell min-h-screen antialiased">
    <div id="app"></div>
</body>
</html>
