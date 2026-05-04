<!DOCTYPE html>
<html lang="{{ env('APP_LOCALE') }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- SEO Meta Tags -->
    <title>Criar Conta | Yov - Chat</title>
    <!-- SEO Estrutural: Yov - Chatweb -->
    <meta name="description" content="Yov: Conecte-se de forma simples, rápida e minimalista. O chatweb focado em performance e privacidade.">
    <meta name="keywords" content="chat, messenger, web app, real-time, Yov, comunicação">
    <meta name="robots" content="index, follow">
    <meta name="author" content="Cláudio Victor"> <!-- -->

    <!-- Open Graph (Otimização para Redes Sociais: WhatsApp, X, Facebook) -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="@yield('title') | Yov">
    <meta property="og:description" content="Conecte-se instantaneamente com o Yov. Minimalismo e velocidade em cada mensagem.">
    <meta property="og:image" content="/assets/images/og-image.png"> <!-- Recomenda-se usar a nova logo figura aqui -->
    <meta property="og:url" content="{{ config('app.url') }}">
    <meta property="og:site_name" content="Yov Chat">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('title') | Yov">
    <meta name="twitter:description" content="O novo padrão de chatweb minimalista.">

    <!-- Identidade Visual e Favicon -->
    <meta name="theme-color" content="#1f9e89">
    <link rel="shortcut icon" href="/assets/images/logo.png" type="image/x-icon">
    <link rel="apple-touch-icon" href="/assets/images/logo.png">

    <!-- Assets e Bibliotecas -->
    <link rel="stylesheet" href="/assets/css/auth.css">

    <!-- Basic Icons -->
    <link href="https://cdn.boxicons.com/3.0.8/fonts/basic/boxicons.min.css" rel="stylesheet">
    <!-- Filled Icons -->
    <link href="https://cdn.boxicons.com/3.0.8/fonts/filled/boxicons-filled.min.css" rel="stylesheet">
    <!-- Brand Icons -->
    <link href="https://cdn.boxicons.com/3.0.8/fonts/brands/boxicons-brands.min.css" rel="stylesheet">
</head>
<body>
    <main class="container">
        <header class="logo-box">
            <!-- A logo figura sem fundo que você aprovou -->
            <img src="/assets/images/logo.png" alt="Yov Logo">
        </header>

        <h1 class="title">Criar conta</h1>
        <p class="subtitle">Comece sua jornada no <strong>Yov</strong> hoje mesmo.</p>

        @if(flash()->has('error'))
            <div class="message-error" role="alert">{{ flash()->get('error') }}</div>
        @endif

        <form action="{{ route('register') }}" method="post" enctype="multipart/form-data"  autocomplete="off">
            @csrf
            <div class="field-group">
                <div class="field-line">
                    <label for="fname">Nome</label>
                    <input type="text" name="fname" id="fname" value="@old('fname')" placeholder="Ex: Cláudio" />
                </div>
                <div class="field-line">
                    <label for="lname">Sobrenome</label>
                    <input type="text" name="lname" id="lname" value="@old('lname')" placeholder="Ex: Victor" />
                </div>
            </div>

            <div class="field-line">
                <label for="email">E-mail</label>
                <input type="text" name="email" id="email" value="@old('email')" placeholder="seu@email.com" />
            </div>

            <div class="field-line">
                <label for="password">Senha</label>
                <input type="password" name="password" id="password" placeholder="Crie uma senha forte" />
                <i class="bx bx-eye-alt" id="togglePass"></i>
            </div>

            <button type="submit">Finalizar Cadastro</button>
        </form>

        <footer class="footer-line">
            Já possui uma conta? <a href="{{ route('login.show') }}">Fazer Login</a>
        </footer>
    </main>

    <script type="module">
        const inputPassword = document.querySelector("#password")
        const btnEye = document.querySelector(".bx-eye-alt")

        btnEye.addEventListener("click", function(){
            if(inputPassword.type === "password"){
                inputPassword.type = "text"
                btnEye.classList.replace('bx-eye-alt', 'bx-eye-closed')
            }else {
                inputPassword.type = "password"
                btnEye.classList.replace('bx-eye-closed','bx-eye-alt')
            }
        })
    </script>
</body>
</html>