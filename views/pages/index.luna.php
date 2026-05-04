@extends('layouts.main')

@section('title', 'Yov - Mensagens')

@push('scripts')
    <script src="/assets/js/ui.js"></script>
@endpush

@section('content')

{{--
================================================================
LIST HOME (SIDEBAR)
================================================================
--}}
<div class="list-home">

    {{--
    ================================================================
    YOV BRAND HEADER
    ================================================================
    --}}
    <div class="brand-header">
        <div class="brand-content">
            {{-- Lado Esquerdo: Logo e Nome --}}
            <div class="brand-left">
                <div class="logo-circle">
                    <img src="/assets/images/logo.png" alt="Yov Logo">
                </div>
                <span class="brand-name">YovChat</span>
            </div>

            {{-- Lado Direito: Menu de Opções --}}
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

    {{-- BODY NAVIGATION & CONTENT --}}
    <div class="body-list">

        {{-- SEARCH COMPONENT --}}
        <div class="form-serach">
            <form onsubmit="return false">
                <input type="text" id="searchInput" placeholder="Procurar conversa..." autocomplete="off">
                <button type="button"><i class="bx bx-search"></i></button>
            </form>
        </div>

        {{-- NAVIGATION TABS --}}
        <input type="radio" name="slider" id="messages" checked>
        <input type="radio" name="slider" id="friends">

        <div class="menu-tag">
            <label for="messages">Conversas</label>
            <label for="friends">Amigos</label>
            <div class="line"></div>
        </div>

        {{-- SCROLLABLE CONTENT AREA --}}
        <article>

            {{-- TAB: CHAT MESSAGES --}}
            <div class="content content-1" id="chatList">
                @forelse($friends as $friend)
                <div class="users-list-sms conversation-item" data-friend-id="{{ $friend->id }}"
                    data-friend-name="{{ $friend->fname }} {{ $friend->lname }}"
                    data-friend-initials="@php echo strtoupper(substr($friend->fname,0,1).substr($friend->lname,0,1)); @endphp "
                    data-friend-status="{{ $friend->status }}">

                    <div class="users">

                        <div class="sms-left">

                            {{-- Avatar com cor dinâmica via Helper --}}
                            <div class="av" style="border: none; background: transparent;">
                                {!! avatar($friend->fname . ' ' . $friend->lname, 42) !!}
                            </div>

                            <div class="info">

                                <h3 class="name">
                                    {{ $friend->fname }} {{ $friend->lname }}
                                </h3>

                                <p class="status">

                                    @if($friend->last_message)

                                    @if($friend->last_message->sender_id === auth()->id())
                                    <span style="opacity:.7;">Você: </span>
                                    @endif

                                    {{ \Slenix\Supports\Libraries\Str::limit($friend->last_message->message ?? '', 35)
                                    }}

                                    @else

                                    <span style="opacity:.6;">Diga um "Oi" ao seu novo amigo.</span>

                                    @endif

                                </p>

                            </div>

                        </div>

                        <i class="bx bxs-circle"
                            style="font-size:.55rem; color: {{ $friend->status === 'online' ? 'var(--green)' : 'var(--gray-400)' }}; flex-shrink:0;"></i>

                    </div>

                </div>
                @empty
                <p style="padding:2rem 1rem; color:var(--gray-400); text-align:center;">
                    Nenhuma conversa ativa.
                </p>
                @endforelse
            </div>

            {{-- TAB: FRIENDS & REQUESTS --}}
            <div class="content content-2">

                {{-- PENDING REQUESTS --}}
                @if($pending->count() > 0)
                <h4>Solicitações Pendentes</h4>
                @foreach($pending as $requester)
                <div class="users-list-sms">
                    <div class="users">
                        <div class="sms-left">
                            <div class="av" style="border: none; background: transparent;">
                                {!! avatar($requester->fname . ' ' . $requester->lname, 44) !!}
                            </div>
                            <div class="info">
                                <h3 class="name">{{ $requester->fname . ' ' . $requester->lname }}</h3>
                                <p class="status">Enviou-te um pedido</p>
                            </div>
                        </div>
                        <form method="POST"
                            action="{{ route('friends.accept', ['id' => (string)$requester->pivot_id ?? $requester->id]) }}">
                            @csrf
                            <button type="submit" class="btn-accept btn-accept-friend"
                                data-friendship-id="{{ $requester->pivot_id ?? $requester->id }}">
                                Aceitar
                            </button>
                        </form>
                    </div>
                </div>
                @endforeach
                @endif

                {{-- FRIEND SUGGESTIONS --}}
                @if($suggestions->count() > 0)
                <h4>Sugestões para ti</h4>
                @foreach($suggestions as $suggestion)
                <div class="users-list-sms">
                    <div class="users">
                        <div class="sms-left">
                            <div class="av" style="border: none; background: transparent;">
                                {!! avatar($suggestion->fname . ' ' . $suggestion->lname, 44) !!}
                            </div>
                            <div class="info">
                                <h3 class="name">{{ $suggestion->fname . ' ' . $suggestion->lname }}</h3>
                                <p class="status">Sugestão do Yov</p>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('friends.request', ['id' => (string)$suggestion->id]) }}">
                            @csrf
                            <button type="submit" class="btn-add btn-add-friend" data-user-id="{{ $suggestion->id }}">
                                Adicionar
                            </button>
                        </form>
                    </div>
                </div>
                @endforeach
                @endif

                @if($pending->count() === 0 && $suggestions->count() === 0)
                <p style="padding:2rem 1rem; color:var(--gray-500); text-align:center;">Não há novas sugestões de
                    momento.</p>
                @endif
            </div>
        </article>
    </div>
</div>

{{--
================================================================
CHAT INTERFACE (MAIN PANEL)
================================================================
--}}
<div class="chat-home" id="chatHome">

    {{-- EMPTY STATE --}}
    <div class="chat-empty" id="chatEmpty">
        <div class="empty-icon"><i class="bx bx-message-circle-notification"></i></div>
        <p>Selecione uma conversa para começar a interagir.</p>
    </div>

    {{-- ACTIVE CONVERSATION CONTENT --}}
    <div id="chatContent" style="display:none; flex:1; flex-direction:column; overflow:hidden;">

        {{-- ACTIVE CHAT HEADER --}}
        <header class="header-chat">
            <div class="menu-left">
                <div class="image-arrow">
                    <span class="icon" id="btnBack"><i class="bx bx-arrow-left"></i></span>
                    <div class="av-chat" id="chatAvatar">--</div>
                </div>
                <div class="chat-names">
                    <h4 class="chat-name" id="chatName">Nome do Amigo</h4>
                    <p class="chat-status" id="chatStatus">● Online</p>
                </div>
            </div>
            <div class="menu-right">
                <i class="bx bx-dots-vertical-rounded"></i>
            </div>
        </header>

        {{-- MESSAGES AREA --}}
        <aside class="area-sms" id="areaSms">
            {{-- Mensagens injetadas via JS --}}
        </aside>

        {{-- MESSAGE COMPOSER --}}
        <footer class="form-send">
            {{-- Picker de Emojis/GIF --}}
            <div class="main-picker" id="mainPicker">
                <div class="picker-tabs">
                    <button type="button" class="picker-tab active" data-tab="emoji" id="btnEm">😊 Emojis</button>
                    <button type="button" class="picker-tab" data-tab="gif" id="btnGi">GIF</button>
                </div>
                <div id="tabEmoji" style="display:block;">
                    <div class="emoji-category-list" id="emojiCategoryList"></div>
                </div>
                <div id="tabGif" style="display:none;">
                    <div class="gif-search">
                        <input type="text" id="gifSearch" placeholder="Procurar no Tenor...">
                    </div>
                    <div class="sticker-grid" id="gifGrid"></div>
                </div>
            </div>

            <form id="formData" onsubmit="return false">
                <div class="input-actions">
                    <button type="button" class="action-btn" id="btnEmoji"><i class="bx bx-smile"></i></button>
                    <button type="button" class="action-btn" id="btnSticker"><i
                            class="bx bx-play-circle-alt"></i></button>
                </div>
                <input type="text" id="messageInput" placeholder="Escreve uma mensagem..." autocomplete="off">
                <button type="submit" id="btnSend"><i class="bx bx-paper-plane"></i></button>
            </form>
        </footer>
    </div>
</div>

@endsection