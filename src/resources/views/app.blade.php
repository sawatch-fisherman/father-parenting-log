<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'TotoOps') }}</title>

        {{--
            Noto Sans JP を Bunny Fonts 経由で読み込む（DESIGN.md 6.1）。
            Google Fonts と同一のフォント資産を、IPアドレスを記録しないプライバシー配慮型CDNで配信する
            サービスで、Laravelの公式スターターキットも同じ理由で標準採用している（docs/privacy.md 準拠）。
        --}}
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=noto-sans-jp:400,600,700" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.ts'])
        @inertiaHead
    </head>
    <body class="bg-background text-text-primary font-sans antialiased">
        @inertia
    </body>
</html>
