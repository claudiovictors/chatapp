@extends('layouts.main')

@section('title')
{{ env('APP_NAME') }}
@endsection

@push('scripts')
    <script src="/assets/js/sendMessage.js"></script>
@endpush

@section('content')
<div class="chat-home">
    <header class="header-chat">
        <div class="menu-left">
            <div class="image-arrow">
                <a href="{{ route('home.page') }}" class="icon"><i class="bx bx-left-arrow-alt"></i></a>
                <img src="/assets/images/{{ $id_user->image }}" alt="{{ $id_user->fname }} {{ $id_user->lname }}">
            </div>
            <div class="chat-names">
                <h4 class="chat-name">{{ $id_user->fname }} {{ $id_user->lname}}</h4>
                <p class="chat-status">{{ $id_user->status }}</p>
            </div>
        </div>

        <div class="menu-right">
            <i class='bx bx-dots-vertical'></i>
        </div>
    </header>
    
    <aside class="area-sms">
        @if(empty($allMessages))
            <div class="no-messages">
                <p>Nenhuma mensagem ainda.</p>
            </div>
        @else
            @foreach($allMessages as $index => $message)
                @if($message->user_id == $user_id)
                    <!-- Mensagem do usuário logado -->
                    <div class="user-content {{ ($index > 0 && $allMessages[$index-1]->user_id == $user_id) ? 'consecutive' : '' }}">
                        <div class="messages">
                            <p class="text-sms">{{ $message->message ?? '' }}</p>
                            <span class="time">
                                @if($message->created_at)
                                    {{ date('H:i', strtotime($message->created_at)) }}
                                @endif
                            </span>
                        </div>
                    </div>
                @else
                    <!-- Mensagem do amigo -->
                    <div class="friend-content {{ ($index > 0 && $allMessages[$index-1]->user_id == $message->user_id) ? 'consecutive' : '' }}">
                        <img src="/assets/images/{{ $id_user->image ?? 'default.png' }}" width="35" alt="{{ $id_user->fname ?? 'User' }} {{ $id_user->lname ?? '' }}">
                        <div class="messages">
                            <p class="text-sms">{{ $message->message ?? '' }}</p>
                            <span class="time">
                                @if($message->created_at)
                                    {{ date('H:i', strtotime($message->created_at)) }}
                                @endif
                            </span>
                        </div>
                    </div>
                @endif
            @endforeach
        @endif
    </aside>
    
    <footer class="form-send">
        <form action="/send" method="post" id="formData">
            <input type="hidden" name="user_id" value="{{ $user_id }}">
            <input type="hidden" name="friend_id" value="{{ $friend_id }}">
            <input type="text" name="message" id="message" placeholder="Mensagem..." required autocomplete="off">
            <button type="submit" id="btnSubmit"><i class="bx bx-send"></i></button>
        </form>
    </footer>
</div>
@endsection