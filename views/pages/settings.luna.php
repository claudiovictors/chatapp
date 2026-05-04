@extends('layouts.main')

@section('title', 'Definições')

@push('scripts')
    <script src="/assets/js/ui.js"></script>
@endpush

@section('content')

{{-- ── SIDEBAR ── --}}
<div class="list-home">

    <div class="brand-header">
        <div class="brand-content">
            <div class="brand-left">
                <div class="logo-circle">
                    <img src="/assets/images/logo.png" alt="Yov Logo">
                </div>
                <span class="brand-name">YovChat</span>
            </div>
            <div class="display">
                <div class="brand-actions">
                    <button type="button" class="btn-action btnMenuFloat" title="Menu">
                        <i class="bx bx-dots-vertical-rounded"></i>
                    </button>
                </div>
                <nav class="menu-float">
                    <li><a href="{{ route('profile.show') }}"><i class="bx bx-user"></i> <span class="text-fl">O meu Perfil</span></a></li>
                    <li><a href="{{ route('settings.show') }}"><i class="bx bx-cog"></i> <span class="text-fl">Definições</span></a></li>
                    <li>
                        <a href="{{ route('logout.show') }}" class="logout">
                            <i class="bx bx-arrow-out-right-stroke-circle-half"></i>
                            <span class="text-fl">Terminar Sessão</span>
                        </a>
                    </li>
                </nav>
            </div>
        </div>
    </div>

    <div class="body-list">
        <div class="form-serach">
            <form onsubmit="return false">
                <input type="text" id="searchInput" placeholder="Procurar conversa..." autocomplete="off">
                <button type="button"><i class="bx bx-search"></i></button>
            </form>
        </div>
        <input type="radio" name="slider" id="messages" checked>
        <input type="radio" name="slider" id="friends">
        <div class="menu-tag">
            <label for="messages">Conversas</label>
            <label for="friends">Amigos</label>
            <div class="line"></div>
        </div>
        <article>
            <div class="content content-1">
                @forelse($friends as $friend)
                <div class="users-list-sms conversation-item"
                    data-friend-id="{{ $friend->id }}"
                    data-friend-name="{{ $friend->fname }} {{ $friend->lname }}">
                    <div class="users">
                        <div class="sms-left">
                            <div class="av" style="border:none; background:transparent;">
                                {!! avatar($friend->fname . ' ' . $friend->lname, 42) !!}
                            </div>
                            <div class="info">
                                <h3 class="name">{{ $friend->fname }} {{ $friend->lname }}</h3>
                                <p class="status">{{ $friend->status }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <p style="padding:2rem 1rem; color:var(--gray-400); text-align:center;">Nenhuma conversa ativa.</p>
                @endforelse
            </div>
            <div class="content content-2">
                <p style="padding:2rem 1rem; color:var(--gray-500); text-align:center;">Sem sugestões de momento.</p>
            </div>
        </article>
    </div>
</div>

{{-- ── MAIN PANEL — DEFINIÇÕES ── --}}
<div class="chat-home settings-panel" id="chatHome">
    <div style="display:flex; flex:1; flex-direction:column; overflow:hidden;">

        <header class="header-chat">
            <div class="menu-left">
                <a class="icon" id="btnBack" href="{{ route('home.show') }}"><i class="bx bx-arrow-left"></i></a>
                <div class="chat-names">
                    <h4 class="chat-name">Definições</h4>
                    <p class="chat-status" style="color:var(--gray-500);">Personaliza a tua experiência</p>
                </div>
            </div>
        </header>

        <div class="settings-scroll">

            @if(session('success'))
            <div class="settings-alert settings-alert-success">
                <i class="bx bx-check-circle"></i>
                {{ session('success') }}
            </div>
            @endif

            @if(session('error'))
            <div class="settings-alert settings-alert-error">
                <i class="bx bx-error-circle"></i>
                {{ session('error') }}
            </div>
            @endif

            {{-- ── CONTA ── --}}
            <div class="settings-group">
                <h5 class="settings-group-label"><i class="bx bx-user-circle"></i> Conta</h5>

                <div class="settings-item" onclick="window.location='<?=  route('profile.edit') ?>'">
                    <span class="settings-icon" style="background:rgba(46,196,169,.12); color:var(--blue-500);">
                        <i class="bx bx-edit-alt"></i>
                    </span>
                    <div class="settings-text">
                        <span class="settings-title">Editar Perfil</span>
                        <span class="settings-desc">Nome, foto, bio e localização</span>
                    </div>
                    <i class="bx bx-chevron-right settings-arrow"></i>
                </div>

                <div class="settings-item" onclick="window.location='<?=  route('settings.password') ?? '#' ?>'">
                    <span class="settings-icon" style="background:rgba(66,133,244,.1); color:#4285f4;">
                        <i class="bx bx-lock"></i>
                    </span>
                    <div class="settings-text">
                        <span class="settings-title">Alterar Palavra-passe</span>
                        <span class="settings-desc">Actualiza as tuas credenciais</span>
                    </div>
                    <i class="bx bx-chevron-right settings-arrow"></i>
                </div>

                <div class="settings-item" onclick="window.location='<?= route('settings.email') ?? '#' ?>'">
                    <span class="settings-icon" style="background:rgba(251,188,5,.1); color:#f9ab00;">
                        <i class="bx bx-envelope"></i>
                    </span>
                    <div class="settings-text">
                        <span class="settings-title">Endereço de Email</span>
                        <span class="settings-desc">{{ auth()->user()->email }}</span>
                    </div>
                    <i class="bx bx-chevron-right settings-arrow"></i>
                </div>
            </div>

            {{-- ── PRIVACIDADE ── --}}
            <div class="settings-group">
                <h5 class="settings-group-label"><i class="bx bx-shield-quarter"></i> Privacidade</h5>

                <div class="settings-item">
                    <span class="settings-icon" style="background:rgba(52,168,83,.1); color:var(--green);">
                        <i class="bx bx-radio-circle-marked" style="font-size:.9rem;"></i>
                    </span>
                    <div class="settings-text">
                        <span class="settings-title">Estado Online</span>
                        <span class="settings-desc">Mostrar quando estás activo</span>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" checked id="toggleOnline">
                        <span class="toggle-track"></span>
                    </label>
                </div>
            </div>

            {{-- ── NOTIFICAÇÕES ── --}}
            <div class="settings-group">
                <h5 class="settings-group-label"><i class="bx bx-bell"></i> Notificações</h5>

                <div class="settings-item">
                    <span class="settings-icon" style="background:rgba(251,188,5,.1); color:#f9ab00;">
                        <i class="bx bx-bell-ring"></i>
                    </span>
                    <div class="settings-text">
                        <span class="settings-title">Notificações Push</span>
                        <span class="settings-desc">Alertas de novas mensagens</span>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" checked id="togglePush">
                        <span class="toggle-track"></span>
                    </label>
                </div>
            </div>

            {{-- ── APARÊNCIA ── --}}
            <div class="settings-group">
                <h5 class="settings-group-label"><i class="bx bx-palette"></i> Aparência</h5>

                <div class="settings-item">
                    <span class="settings-icon" style="background:rgba(46,196,169,.12); color:var(--blue-500);">
                        <i class="bx bx-moon"></i>
                    </span>
                    <div class="settings-text">
                        <span class="settings-title">Tema Escuro</span>
                        <span class="settings-desc">Interface em modo escuro</span>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" id="toggleDark">
                        <span class="toggle-track"></span>
                    </label>
                </div>
            </div>

            {{-- ── SESSÃO / CONTA ── --}}
            <div class="settings-group settings-group-danger">
                <h5 class="settings-group-label" style="color:#ea4335;"><i class="bx bx-error-circle"></i> Zona Perigosa</h5>

                <div class="settings-item">
                    <span class="settings-icon" style="background:rgba(234,67,53,.1); color:#ea4335;">
                        <i class="bx bx-arrow-out-right-stroke-circle-half"></i>
                    </span>
                    <div class="settings-text">
                        <span class="settings-title">Terminar Sessão</span>
                        <span class="settings-desc">Sair da conta neste dispositivo</span>
                    </div>
                    <a href="{{ route('logout.show') }}" class="btn-danger-sm">Sair</a>
                </div>

                <div class="settings-item">
                    <span class="settings-icon" style="background:rgba(234,67,53,.1); color:#ea4335;">
                        <i class="bx bx-trash"></i>
                    </span>
                    <div class="settings-text">
                        <span class="settings-title">Eliminar Conta</span>
                        <span class="settings-desc">Esta acção é irreversível</span>
                    </div>
                    <button class="btn-danger-sm" onclick="confirmDelete()">Eliminar</button>
                </div>
            </div>

        </div>{{-- /settings-scroll --}}
    </div>
</div>
@endsection