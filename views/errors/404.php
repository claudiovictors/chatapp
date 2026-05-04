<!DOCTYPE html>
<html lang="{{ env('APP_LOCALE') }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página não encontrada | Yov</title>
    
    <!-- SEO & Identidade -->
    <meta name="robots" content="noindex, follow">
    <link rel="shortcut icon" href="/assets/images/logo-transparente.png" type="image/x-icon">
    
    <!-- Assets -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;800&display=swap');

        :root {
            --bg-app: #eef0f6;
            --white: #ffffff;
            --blue-500: #1f9e89;
            --gray-100: #f1f3f5;
            --gray-300: #dee2e6;
            --gray-500: #adb5bd;
            --gray-600: #6c757d;
            --gray-900: #212529;
            --radius: 14px;
            --shadow: 0 10px 40px rgba(0, 0, 0, 0.05);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            min-height: 100dvh;
            background-color: var(--bg-app);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        /* Estrutura do Card (Estilo App-Wrapper) */
        .error-card {
            width: 100%;
            max-width: 800px;
            background: var(--white);
            border-radius: var(--radius);
            padding: 8rem 2rem;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        /* Visual do Erro */
        .visual-box {
            position: relative;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .icon-bg {
            width: 100px;
            height: 100px;
            background: var(--gray-100);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: var(--gray-300);
            z-index: 2;
        }

        .code-404 {
            position: absolute;
            font-size: 13rem;
            font-weight: 800;
            color: #f1f3f5; /* Quase invisível no fundo */
            z-index: 1;
            letter-spacing: -5px;
            user-select: none;
        }

        /* Textos */
        h1 {
            color: var(--gray-900);
            font-size: 2.3rem;
            font-weight: 600;
            padding-top: 2rem;
            margin-bottom: 0.8rem;
        }

        p {
            color: var(--gray-600);
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 2rem;
            max-width: 320px;
        }

        /* Botão PT-PT */
        .btn-home {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background-color: var(--blue-500);
            color: var(--white);
            padding: 0.9rem 1.8rem;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }

        .btn-home:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        /* Responsividade Total Mobile */
        @media (max-width: 480px) {
            body {
                background-color: var(--white);
                padding: 0;
            }

            .error-card {
                max-width: 100%;
                min-height: 100dvh;
                border-radius: 0;
                box-shadow: none;
                padding: 2rem;
            }

            .code-404 {
                font-size: 5.5rem;
            }
        }
    </style>
</head>
<body>

    <main class="error-card">
        <div class="visual-box">
            <span class="code-404">404</span>
            <div class="icon-bg">
                <i class='bx bx-search-alt'></i>
            </div>
        </div>

        <h1>Ups! Página não encontrada</h1>
        <p>Parece que o caminho que seguiu já não existe ou foi removido definitivamente.</p>

        <a href="/" class="btn-home">
            <i class='bx bx-home-alt'></i>
            Voltar ao Início
        </a>
    </main>

</body>
</html>