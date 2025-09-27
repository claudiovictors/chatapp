<!DOCTYPE html>
<html lang="pt-AO">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/boxicons.min.css">
    <link rel="shortcut icon" href="logo.svg" type="image/x-icon">
    <title>@yield('title')</title>
</head>
<body>
    <section class="container">
        @yield('content')
    </section>

    @stack('scripts')
</body>
</html>