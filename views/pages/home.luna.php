@extends('layouts.main')

@section('title')
{{ env('APP_NAME') }}
@endsection

@section('content')
<div class="list-home">
    <div class="header-list">
    <div class="list-left">
        <a href="{{ $userLogued->id }}">
            <img src="/assets/images/{{ $userLogued->image }}" alt="{{ $userLogued->fname }}">
        </a>

        <div class="info">
            <h3 class="name">{{ $userLogued->fname }} {{ $userLogued->lname }}</h3>
            <p class="status">{{ $userLogued->status }}</p>
        </div>
    </div>

    <a href="{{ route('logout') }}" class="btn-logout">Logout</a>
</div>
<div class="body-list">
    <div class="form-serach">
        <form action="#" method="get">
            <input type="text" name="search" id="search" placeholder=" Procurar amigo...">
            <button type="submit"><i class="bx bx-search"></i></button>
        </form>
    </div>


    <!-- IMPORTANT: os inputs precisam estar antes do article e com o mesmo pai -->
    <input type="radio" name="slider" id="messages" checked>
    <input type="radio" name="slider" id="friends">

    <div class="menu-tag">
        <label for="messages">Mensagens</label>
        <label for="friends">Amigos</label>
        <div class="line"></div>
    </div>

    <article>
        
        <!-- LISTA DE MENSAGENS -->
        <div class="content content-1">
            <div class="users-list-sms">
                @if (empty($aceitos))
                 <div class="text-sms">Nenhum amigo.</div>
                @else
                    @foreach ($aceitos as $aceito)
                        <a href="{{ route('chat.page') }}/{{ $aceito->id }}" class="users">
                            <div class="sms-left">
                                <img src="/assets/images/{{ $aceito->image }}" />

                                <div class="info">
                                    <h3 class="name">{{ $aceito->fname }} {{ $aceito->lname }}</h3>
                                    <p class="status">Você: Olá, Anderson!</p>
                                </div>
                            </div>

                            @if ($aceito->status == 'Online')
                                <i class="bx bxs-circle"></i>
                            @else 
                                <i class="bx bxs-circle" style="color: #989898"></i>
                            @endif
                        </a>
                    @endforeach
                @endif
            </div>
        </div>

        <!-- LISTA DE AMIGOS -->
        <div class="content content-2">
            @if (!empty($pendentes))
                <h4>Pendentes</h4>
                <div class="users-list-sms">
                    @foreach ($pendentes as $pendente)
                        <div class="users">
                            <div class="sms-left">
                                <img src="/assets/images/{{ $pendente->image }}">

                                <div class="info">
                                    <h3 class="name">{{ $pendente->fname }} {{ $pendente->lname }}</h3>
                                    <p class="status">Novo</p>
                                </div>
                            </div>

                            <form action="{{ route('accept.friend') }}" method="post">
                                <input type="hidden" name="id" value="{{ $pendente->friendship_id }}">
                                <button type="submit">Aceitar</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif

            <h4>Procurar amigos</h4>
            <div class="users-list-sms">
                @if (empty($nofriends))
                    <div class="text-sms">Nenhuma amigo</div>
                @else 

                    @foreach ($nofriends as $nofriend)
                        <div class="users">
                            <div class="sms-left">
                                <img src="/assets/images/{{ $nofriend->image }}">

                                <div class="info">
                                    <h3 class="name">{{ $nofriend->fname }} {{ $nofriend->lname }}</h3>
                                    <p class="status">Novo</p>
                                </div>
                            </div>

                            <form action="{{ route('add.friend') }}" method="post">
                                @csrf
                                <input type="hidden" name="friend_id" id="friend_id" value="{{ $nofriend->id }}">
                                <button>Adicionar</button>
                            </form>
                        </div>
                    @endforeach
                @endif
                
            </div>
        </div>

    </article>
</div>
</div>
@endsection