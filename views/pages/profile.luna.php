@extends('layouts.main')

@section('title', 'O meu Perfil')

@section('content')

{{--
================================================================
LIST HOME (SIDEBAR) — mesma estrutura da página de mensagens
================================================================
--}}
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
            <div class="content content-1" id="chatList">
                @forelse($friends as $friend)
                <div class="users-list-sms conversation-item"
                    data-friend-id="{{ $friend->id }}"
                    data-friend-name="{{ $friend->fname }} {{ $friend->lname }}"
                    data-friend-initials="@php echo strtoupper(substr($friend->fname,0,1).substr($friend->lname,0,1)); @endphp "
                    data-friend-status="{{ $friend->status }}">
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
                        <i class="bx bxs-circle"
                            style="font-size:.55rem; color: @php echo $friend->status === 'online' ? 'var(--green)' : 'var(--gray-400)' @endphp ; flex-shrink:0;"></i>
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

{{--
================================================================
MAIN PANEL — PERFIL
================================================================
--}}
<div class="chat-home profile-panel" id="chatHome">
    <div style="display:flex; flex:1; flex-direction:column; overflow:hidden;">

        {{-- Header --}}
        <header class="header-chat">
            <div class="menu-left">
                <a class="icon" id="btnBack" href="{{ route('home.show') }}"><i class="bx bx-arrow-left"></i></a>
                <div class="chat-names">
                    <h4 class="chat-name">O meu Perfil</h4>
                    <p class="chat-status" style="color:var(--gray-500);">Informações pessoais</p>
                </div>
            </div>
            <div class="menu-right">
                <a href="{{ route('profile.edit') }}" style="display:flex; align-items:center; gap:6px; font-size:.8rem; font-weight:600; color:var(--blue-500); text-decoration:none; padding:6px 14px; border-radius:20px; border:1.5px solid var(--blue-400); transition:all .2s;">
                    <i class="bx bx-edit-alt"></i> Editar
                </a>
            </div>
        </header>

        {{-- Scrollable body --}}
        <div class="profile-scroll">

            {{-- Cover + Avatar --}}
            <div class="profile-cover">
                <div class="profile-cover-bg"></div>
                <div class="profile-avatar-wrap">
                    <div class="profile-avatar-ring">
                        @if(auth()->user()->avatar)
                            <img src="{{ auth()->user()->avatar }}" alt="Avatar" class="profile-avatar-img">
                        @else
                            <div class="profile-avatar-initials">
                                {{ strtoupper(substr(auth()->user()->fname,0,1).substr(auth()->user()->lname,0,1)) }}
                            </div>
                        @endif
                    </div>
                    <span class="profile-online-dot"></span>
                </div>
            </div>

            {{-- Name & meta --}}
            <div class="profile-identity">
                <h2 class="profile-fullname">{{ auth()->user()->fname }} {{ auth()->user()->lname }}</h2>
                <p class="profile-handle">@{{ auth()->user()->username ?? strtolower(auth()->user()->fname) }}</p>
                <div class="profile-badges">
                    <span class="badge badge-online"><i class="bx bxs-circle" style="font-size:.55rem;"></i> Online</span>
                    <span class="badge badge-member">Membro desde {{ auth()->user()->created_at->format('M Y') }}</span>
                </div>
            </div>

            {{-- Stats bar --}}
            <div class="profile-stats">
                <div class="stat-item">
                    <span class="stat-num">{{ $friendsCount ?? 0 }}</span>
                    <span class="stat-label">Amigos</span>
                </div>
                <div class="stat-divider"></div>
                <div class="stat-item">
                    <span class="stat-num">{{ $messagesCount ?? 0 }}</span>
                    <span class="stat-label">Mensagens</span>
                </div>
                <div class="stat-divider"></div>
                <div class="stat-item">
                    <span class="stat-num">{{ $groupsCount ?? 0 }}</span>
                    <span class="stat-label">Grupos</span>
                </div>
            </div>

            {{-- Bio --}}
            @if(auth()->user()->bio)
            <div class="profile-section">
                <h5 class="section-label"><i class="bx bx-quote-alt-left"></i> Bio</h5>
                <p class="profile-bio">{{ auth()->user()->bio }}</p>
            </div>
            @endif

            {{-- Info rows --}}
            <div class="profile-section">
                <h5 class="section-label"><i class="bx bx-info-circle"></i> Informações</h5>
                <div class="info-list">
                    <div class="info-row">
                        <span class="info-icon"><i class="bx bx-envelope"></i></span>
                        <div class="info-text">
                            <span class="info-key">Email</span>
                            <span class="info-val">{{ auth()->user()->email }}</span>
                        </div>
                    </div>
                    @if(auth()->user()->phone)
                    <div class="info-row">
                        <span class="info-icon"><i class="bx bx-phone"></i></span>
                        <div class="info-text">
                            <span class="info-key">Telefone</span>
                            <span class="info-val">{{ auth()->user()->phone }}</span>
                        </div>
                    </div>
                    @endif
                    @if(auth()->user()->location)
                    <div class="info-row">
                        <span class="info-icon"><i class="bx bx-map-pin"></i></span>
                        <div class="info-text">
                            <span class="info-key">Localização</span>
                            <span class="info-val">{{ auth()->user()->location }}</span>
                        </div>
                    </div>
                    @endif
                    <div class="info-row">
                        <span class="info-icon"><i class="bx bx-calendar"></i></span>
                        <div class="info-text">
                            <span class="info-key">Registado em</span>
                            <span class="info-val">{{ auth()->user()->created_at->format('d \d\e F \d\e Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="profile-section profile-actions-row">
                <a href="{{ route('profile.edit') }}" class="pf-btn pf-btn-primary">
                    <i class="bx bx-edit-alt"></i> Editar Perfil
                </a>
                <a href="{{ route('settings.show') }}" class="pf-btn pf-btn-secondary">
                    <i class="bx bx-cog"></i> Definições
                </a>
            </div>

        </div>{{-- /profile-scroll --}}
    </div>
</div>

<style>
/* ── PROFILE PANEL ── */
.profile-panel {
    background: var(--bg-chat);
}

.profile-scroll {
    flex: 1;
    overflow-y: auto;
    padding-bottom: 2rem;
    scrollbar-width: thin;
    scrollbar-color: var(--gray-300) transparent;
}

/* Cover */
.profile-cover {
    position: relative;
    height: 110px;
    flex-shrink: 0;
}

.profile-cover-bg {
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, var(--blue-500) 0%, #046452 100%);
    position: relative;
    overflow: hidden;
}

.profile-cover-bg::after {
    content: '';
    position: absolute;
    inset: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
}

.profile-avatar-wrap {
    position: absolute;
    bottom: -26px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 2;
}

.profile-avatar-ring {
    width: 72px;
    height: 72px;
    border-radius: 50%;
    border: 3px solid var(--white);
    background: var(--gray-200);
    box-shadow: 0 4px 20px rgba(0,0,0,.15);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.profile-avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.profile-avatar-initials {
    font-size: 1.5rem;
    font-weight: 800;
    color: var(--blue-500);
    background: var(--gray-100);
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.profile-online-dot {
    position: absolute;
    bottom: 2px;
    right: 2px;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    background: var(--green);
    border: 2px solid var(--white);
}

/* Identity */
.profile-identity {
    padding: 36px 20px 16px;
    text-align: center;
}

.profile-fullname {
    font-size: 1.2rem;
    font-weight: 800;
    color: var(--gray-900);
    letter-spacing: -.5px;
}

.profile-handle {
    font-size: .82rem;
    color: var(--gray-500);
    margin-top: 2px;
}

.profile-badges {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-top: 10px;
    flex-wrap: wrap;
}

.badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: .72rem;
    font-weight: 600;
}

.badge-online {
    background: rgba(52,168,83,.1);
    color: var(--green);
}

.badge-member {
    background: var(--gray-100);
    color: var(--gray-600);
}

/* Stats */
.profile-stats {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0;
    margin: 0 20px 4px;
    background: var(--white);
    border-radius: var(--radius);
    padding: 14px 0;
    box-shadow: 0 1px 6px rgba(0,0,0,.05);
}

.stat-item {
    flex: 1;
    text-align: center;
}

.stat-num {
    display: block;
    font-size: 1.2rem;
    font-weight: 800;
    color: var(--gray-900);
    line-height: 1;
}

.stat-label {
    font-size: .7rem;
    color: var(--gray-500);
    font-weight: 500;
    margin-top: 3px;
    display: block;
}

.stat-divider {
    width: 1px;
    height: 32px;
    background: var(--gray-200);
}

/* Sections */
.profile-section {
    margin: 12px 20px 0;
    background: var(--white);
    border-radius: var(--radius);
    padding: 14px 16px;
    box-shadow: 0 1px 6px rgba(0,0,0,.05);
}

.section-label {
    font-size: .72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: var(--gray-500);
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 5px;
}

.section-label i {
    font-size: .9rem;
    color: var(--blue-400);
}

.profile-bio {
    font-size: .86rem;
    color: var(--gray-800);
    line-height: 1.6;
}

/* Info list */
.info-list {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.info-row {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px 0;
    border-bottom: 1px solid var(--gray-100);
}

.info-row:last-child {
    border-bottom: none;
}

.info-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: rgba(46,196,169,.1);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--blue-500);
    font-size: 1rem;
    flex-shrink: 0;
}

.info-text {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.info-key {
    font-size: .7rem;
    font-weight: 600;
    color: var(--gray-500);
    text-transform: uppercase;
    letter-spacing: .04em;
}

.info-val {
    font-size: .86rem;
    color: var(--gray-900);
    font-weight: 500;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Actions */
.profile-actions-row {
    display: flex;
    gap: 10px;
}

.pf-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 10px 0;
    border-radius: 10px;
    font-size: .84rem;
    font-weight: 700;
    text-decoration: none;
    transition: all .2s;
}

.pf-btn-primary {
    background: var(--blue-500);
    color: #fff;
}

.pf-btn-primary:hover {
    background: #046452;
}

.pf-btn-secondary {
    background: var(--gray-100);
    color: var(--gray-700);
    border: 1.5px solid var(--gray-200);
}

.pf-btn-secondary:hover {
    background: var(--gray-200);
}
</style>

@endsection