# <p align="center">Yov Chat</p>

<p align="center">
  <img src="public/assets/images/logo.png" width="170" alt="YovChat Logo">
</p>

<p align="center">
  <a href="#"><img src="https://img.shields.io/badge/PHP-8.1%2B-blue?style=for-the-badge" alt="PHP Version"></a>
  <a href="#"><img src="https://img.shields.io/badge/Slenix-v2.6-6f42c1?style=for-the-badge" alt="Framework"></a>
  <a href="#"><img src="https://img.shields.io/badge/License-MIT-orange?style=for-the-badge" alt="License"></a>
</p>

<p align="center">
  Plataforma de mensagens moderna e responsiva construída com o ecossistema Slenix.
</p>

<p align="center">
  Se este projeto te ajudou ou se valorizas o meu trabalho no desenvolvimento do ecossistema Slenix, considera apoiar com um café.
</p>

<p align="center">
  <a href="https://buymeacoffee.com/claudio.dev">
    <img src="https://cdn.buymeacoffee.com/buttons/v2/default-yellow.png" alt="Buy Me A Coffee" style="height: 40px !important;width: 145px !important;" >
  </a>
</p>

---

## Demonstração

Abaixo podes conferir o funcionamento da interface, desde a navegação lateral até ao painel de edição de perfil.

<p align="center">
  <!-- Para adicionar o vídeo: No editor do GitHub, arraste o ficheiro .mp4 para esta linha -->
  <video src="public/assets/videos/demo.webm" width="100%" controls>
    O seu navegador não suporta a reprodução de vídeos.
  </video>
</p>

---

## Funcionalidades

- Mensagens em Tempo Real: Experiência de chat fluida.
- Gestão de Perfil: Atualização de dados pessoais e avatar.
- Design Moderno: Interface limpa e otimizada para dispositivos móveis.
- Segurança: Proteção de rotas e autenticação via middleware.
- Sistema de Amizades: Gestão de pedidos e lista de amigos.

## Tecnologias Utilizadas

- Backend: [PHP 8.1+](https://www.php.net/)
- Framework: [Slenix v2.6](https://slenix.vercel.app/slenix)
- Motor de Template: [Luna](https://slenix.vercel.app/docs/luna)
- Base de Dados: [MySQL](https://www.mysql.com/)
- Ícones: [Boxicons](https://boxicons.com/)

## Instalação e Configuração

1. Clone o repositório:
   ```bash
   git clone [https://github.com/claudiovictors/chatweb.git](https://github.com/claudiovictors/chatweb.git)
   ```

 ## Start do Projeto

Para iniciar o servidor da aplicação com WebSocket:

```bash
php celestial serve --ws