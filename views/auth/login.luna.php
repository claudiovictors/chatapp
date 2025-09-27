<!DOCTYPE html>
<html lang="pt-AO">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/css/register.css">
    <link rel="stylesheet" href="/assets/css/boxicons.min.css">
    <title>{{ env('APP_NAME') }}</title>
</head>

<body>
    <section class="container">
        <h2 class="title">Login</h2>

        @if(Session::hasFlash('error'))
            <div class="message-error">{{ Session::getFlash('error') }}</div>
        @endif

        <form action="/login" method="post" autocomplete="off">
            @csrf

            <div class="field-line">
                <label for="email">E-mail</label>
                <input type="text" name="email" id="email" value="@old('email')" placeholder=" Digite o seu email">
            </div>

            <div class="field-line">
                <label for="password">Senha</label>
                <i class="bx bx-show"></i>
                <input type="password" name="password" id="password" value="@old('password')" placeholder=" Digite a sua senha">
            </div>

            <div class="field-line">
                <button type="submit">Registrar</button>
            </div>
        </form>

        <div class="line">Ainda não possuí uma conta <a href="{{ route('register') }}">Registrar?</a></div>
    </section>

    <script src="/assets/js/signup.js"></script>
</body>

</html>