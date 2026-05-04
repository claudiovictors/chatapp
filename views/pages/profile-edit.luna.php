@extends('layouts.main')

@section('title', 'Editar Perfil')

@section('content')

{{-- ── SIDEBAR (Lista de Conversas e Amigos) ── --}}
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
        {{-- Barra de Pesquisa --}}
        <div class="form-serach">
            <form onsubmit="return false">
                <input type="text" id="searchInput" placeholder="Procurar conversa..." autocomplete="off">
                <button type="button"><i class="bx bx-search"></i></button>
            </form>
        </div>

        {{-- Tabs de Navegação --}}
        <input type="radio" name="slider" id="messages" checked>
        <input type="radio" name="slider" id="friends">
        <div class="menu-tag">
            <label for="messages">Conversas</label>
            <label for="friends">Amigos</label>
            <div class="line"></div>
        </div>

        <article>
            {{-- Lista de Amigos/Conversas --}}
            <div class="content content-1">
                @forelse($friends as $friend)
                <div class="users-list-sms">
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

            {{-- Sugestões --}}
            <div class="content content-2">
                <p style="padding:2rem 1rem; color:var(--gray-500); text-align:center;">Sem sugestões de momento.</p>
            </div>
        </article>
    </div>
</div>

{{-- ── PAINEL PRINCIPAL (Formulário de Edição) ── --}}
<div class="chat-home edit-panel" id="chatHome">
    <div style="display:flex; flex:1; flex-direction:column; overflow:hidden;">

        <header class="header-chat">
            <div class="menu-left">
                <a href="{{ route('profile.show') }}" class="icon" style="text-decoration:none; color:var(--gray-600);">
                    <i class="bx bx-arrow-left" style="font-size:1.3rem;"></i>
                </a>
                <div class="chat-names" style="margin-left:6px;">
                    <h4 class="chat-name">Editar Perfil</h4>
                    <p class="chat-status" style="color:var(--gray-500);">Actualiza as tuas informações</p>
                </div>
            </div>
        </header>

        <div class="edit-scroll">

            {{-- Alerta de Sucesso --}}
            @if(session('success'))
            <div class="edit-alert edit-alert-success">
                <i class="bx bx-check-circle"></i>
                {{ session('success') }}
            </div>
            @endif

            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="edit-form">
                @csrf
                @method('PUT')

                {{-- Campos de Dados Pessoais --}}
                <div class="edit-fields">

                    <div class="edit-row">
                        <div class="edit-field">
                            <label class="edit-label">Primeiro Nome</label>
                            <div class="edit-input-wrap">
                                <i class="bx bx-user edit-input-icon"></i>
                                <input type="text" name="fname" class="edit-input" value="{{ old('fname', auth()->user()->fname) }}" placeholder="Primeiro nome" required>
                            </div>
                        </div>
                        <div class="edit-field">
                            <label class="edit-label">Último Nome</label>
                            <div class="edit-input-wrap">
                                <i class="bx bx-user edit-input-icon"></i>
                                <input type="text" name="lname" class="edit-input" value="{{ old('lname', auth()->user()->lname) }}" placeholder="Último nome" required>
                            </div>
                        </div>
                    </div>

                    <div class="edit-field">
                        <label class="edit-label">Nome de utilizador</label>
                        <div class="edit-input-wrap">
                            <span class="edit-prefix">@</span>
                            <input type="text" name="username" class="edit-input edit-input-prefixed" value="{{ old('username', auth()->user()->username) }}" placeholder="nome.utilizador">
                        </div>
                    </div>

                    <div class="edit-field">
                        <label class="edit-label">Bio</label>
                        <div class="edit-input-wrap">
                            <textarea name="bio" class="edit-textarea" placeholder="Conta um pouco sobre ti..." maxlength="160" rows="3">{{ old('bio', auth()->user()->bio) }}</textarea>
                        </div>
                        <span class="edit-char-count"><span id="bioCount">{{ strlen(auth()->user()->bio ?? '') }}</span>/160</span>
                    </div>

                    <div class="edit-field">
                        <label class="edit-label">Telefone</label>
                        <div class="edit-input-wrap">
                            <i class="bx bx-phone edit-input-icon"></i>
                            <input type="tel" name="phone" class="edit-input" value="{{ old('phone', auth()->user()->phone) }}" placeholder="+244 9XX XXX XXX">
                        </div>
                    </div>

                    <div class="edit-field">
                        <label class="edit-label">Localização</label>
                        <div class="edit-input-wrap">
                            <i class="bx bx-map-pin edit-input-icon"></i>
                            <input type="text" name="location" class="edit-input" value="{{ old('location', auth()->user()->location) }}" placeholder="Luanda, Angola">
                        </div>
                    </div>

                    <div class="edit-field">
                        <label class="edit-label">Email</label>
                        <div class="edit-input-wrap">
                            <i class="bx bx-envelope edit-input-icon"></i>
                            <input type="email" name="email" class="edit-input" value="{{ old('email', auth()->user()->email) }}" placeholder="email@exemplo.com" required>
                        </div>
                    </div>

                </div>

                {{-- Botões de Ação --}}
                <div class="edit-submit-row">
                    <a href="{{ route('profile.show') }}" class="edit-btn edit-btn-cancel">Cancelar</a>
                    <button type="submit" class="edit-btn edit-btn-save">
                        <i class="bx bx-check"></i> Guardar Alterações
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>

@endsection