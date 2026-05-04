<!DOCTYPE html>
<html lang="{{ env('APP_LOCALE') }}">

<head>
    <!-- Configurações Básicas de Renderização -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <!-- SEO Estrutural: Yov - Chatweb -->
    <title>@yield('title') | Yov</title>
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
    <link rel="stylesheet" href="/assets/css/style.css">

    <!-- Pré-carregamento de Fontes (Opcional, mas melhora performance) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- Basic Icons -->
    <link href="https://cdn.boxicons.com/3.0.8/fonts/basic/boxicons.min.css" rel="stylesheet">
    <!-- Filled Icons -->
    <link href="https://cdn.boxicons.com/3.0.8/fonts/filled/boxicons-filled.min.css" rel="stylesheet">
    <!-- Brand Icons -->
    <link href="https://cdn.boxicons.com/3.0.8/fonts/brands/boxicons-brands.min.css" rel="stylesheet">
</head>

<body data-user-id="{{ auth()->id() }}">

    <!-- 
        ESTRUTURA PRINCIPAL (Yov Layout System):
        O .app-wrapper é o container flex/grid global. 
        Ele deve manter o contexto das colunas (Sidebar e Chat) para 
        preservar o estado da conexão e animações entre transições.
    -->
    <div class="app-wrapper" id="appWrapper">
        @yield('content')
    </div>

    <!-- 
        STACK DE SCRIPTS:
        Permite que views específicas (como o editor de código do blog ou o chat em tempo real)
        injetem lógica JS sem poluir o layout global.
    -->
    @stack('scripts')
</body>

</html>