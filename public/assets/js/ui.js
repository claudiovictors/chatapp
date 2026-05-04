/**
 * @fileoverview YovChat — Interface Principal (ui.js)
 *
 * @module ui
 * @version 2.0.0
 */

'use strict';

// ============================================================
// CONFIGURAÇÕES GLOBAIS
// ============================================================

/** @type {number} ID do utilizador autenticado, lido do data-attribute do <body>. */
const MY_USER_ID = parseInt(document.body.dataset.userId || 0, 10);

/** @type {string} URL do servidor WebSocket. */
const WS_URL = 'ws://127.0.0.1:8081/ws/chat';

/** @type {string} Chave da API Giphy. */
const GIPHY_KEY = 'vKR2p1fYZGOdd2q1myZT5StWLFaIGTX7';

/** @type {string} GIFs em tendência. */
const GIPHY_TRENDING = `https://api.giphy.com/v1/gifs/trending?api_key=${GIPHY_KEY}&limit=12&rating=g`;

/**
 * URL de pesquisa do Giphy.
 * @param {string} q Termo de pesquisa.
 * @returns {string}
 */
const GIPHY_SEARCH = (q) =>
    `https://api.giphy.com/v1/gifs/search?api_key=${GIPHY_KEY}&q=${encodeURIComponent(q)}&limit=12&rating=g`;

/** @type {string} Chave do localStorage para a conversa ativa. */
const LS_ACTIVE_FRIEND = 'yov_active_friend';

/** @type {string} Prefixo do localStorage para histórico de mensagens. */
const LS_MESSAGES_PREFIX = 'yov_messages_';

/** @type {string} Prefixo do localStorage para contadores de não lidos. */
const LS_UNREAD_PREFIX = 'yov_unread_';

// ============================================================
// ESTADO GLOBAL
// ============================================================

/** @type {WebSocket|null} Instância ativa do WebSocket. */
let ws = null;

/** @type {boolean} Indica se a reconexão está agendada. */
let wsReconnecting = false;

/**
 * Amigo com quem a conversa está aberta.
 * @type {{ id: number, name: string, avatarHtml: string, initials: string }|null}
 */
let activeFriend = null;

/** @type {boolean} GIFs já carregados pelo menos uma vez. */
let gifLoaded = false;

/** @type {ReturnType<typeof setTimeout>|null} Debounce da pesquisa de GIFs. */
let gifDebounce = null;

/** @type {ReturnType<typeof setTimeout>|null} Debounce da pesquisa de conversas. */
let searchDebounce = null;

/** @type {'emoji'|'gif'} Tab ativo no picker. */
let activePickerTab = 'emoji';

/**
 * Mapa de contadores de mensagens não lidas por friend_id.
 * @type {Map<number, number>}
 */
const unreadCounts = new Map();

// ============================================================
// CATEGORIAS DE EMOJIS
// ============================================================

/** @type {Object.<string, string[]>} Categorias e emojis disponíveis. */
const EMOJI_CATEGORIES = {
    'Alegria':   ['😀','😃','😄','😁','😆','😅','🤣','😂','🙂','🙃','😊','😇','🥰','😍','🤩','😘','😗','😚','😋','😛','😜','🤪','🥳'],
    'Tristeza':  ['😢','😭','😞','🥺','😔','😟','😣','😖','😫','😩','🥱','😤','😡','😠','🤬','😰','😨','😱'],
    'Amor':      ['❤️','🧡','💛','💚','💙','💜','🤎','🖤','🤍','💖','💕','💞','💗','💘','💝','💟','💌','🫂'],
    'Reações':   ['👍','👎','👌','🤌','✌️','🤞','🤙','🤘','🤟','👏','🙌','👐','🙏','🤝','🔥','✨','💯','💢','💥','💫','👀','🧠','💣'],
    'Animais':   ['🐶','🐱','🐭','🐹','🐰','🦊','🐻','🐼','🐻‍❄️','🐨','🐯','🦁','🐮','🐷','🐸','🐵','🐧','🐦','🐤','🦆','🦅','🦉','🦇'],
    'Natureza':  ['🌵','🌲','🌳','🌴','🌱','🌿','☘️','🍀','🍃','🍂','🍁','🍄','🐚','🌎','🌍','🌏','🌕','🌑','☀️','🌤️','☁️','⛈️','❄️'],
    'Comida':    ['🍏','🍎','🍐','🍊','🍋','🍌','🍉','🍇','🍓','🫐','🍒','🍑','🥭','🍍','🥥','🥝','🍕','🍔','🍟','🌮','🌯','🥗','🍜','🍣','🍩','🍪','🎂','☕','🍷','🍺'],
    'Objetos':   ['💻','🖥️','⌨️','🖱️','📱','📸','📹','🎮','🕹️','💾','📡','🔋','🔌','💡','🔦','💸','💳','💎','🔑','🎁','🎈','🎉','📚','📝','✏️','🔍'],
    'Viagem':    ['🚗','🚕','🚌','🏎️','🚓','🚑','🚒','🛵','🏍️','🚲','⛵','🚢','✈️','🛩️','🚁','🚀','🛸','🧳','🌍','🗺️','🏖️','🏔️','🌋','🗽'],
};

// ============================================================
// WEBSOCKET — CONEXÃO E HANDLERS
// ============================================================

/**
 * Estabelece a conexão WebSocket, autentica e configura todos os handlers.
 * Reconecta automaticamente após 3 segundos em caso de desconexão.
 *
 * @returns {void}
 */
function connectWS() {
    if (wsReconnecting) return;

    ws = new WebSocket(WS_URL);

    ws.onopen = () => {
        console.info('[WS] Conectado.');
        wsReconnecting = false;
        ws.send(JSON.stringify({ type: 'auth', user_id: MY_USER_ID }));
    };

    ws.onmessage = (event) => {
        let data;
        try {
            data = JSON.parse(event.data);
        } catch {
            console.warn('[WS] Mensagem inválida recebida:', event.data);
            return;
        }
        handleWsMessage(data);
    };

    ws.onclose = () => {
        console.warn('[WS] Desconectado. Reconectando em 3 s...');
        ws = null;
        wsReconnecting = true;
        setTimeout(() => {
            wsReconnecting = false;
            connectWS();
        }, 3000);
    };

    ws.onerror = (err) => {
        console.error('[WS] Erro:', err);
    };
}

/**
 * Envia dados via WebSocket de forma segura, verificando o estado da ligação.
 *
 * @param {Object} payload Objeto a serializar e enviar.
 * @returns {boolean} `true` se enviado, `false` se a ligação não está ativa.
 */
function wsSend(payload) {
    if (!ws || ws.readyState !== WebSocket.OPEN) {
        console.warn('[WS] Tentativa de envio sem ligação ativa.');
        return false;
    }
    ws.send(JSON.stringify(payload));
    return true;
}

/**
 * Trata todas as mensagens recebidas pelo WebSocket.
 *
 * @param {Object} data Payload desserializado.
 * @returns {void}
 */
function handleWsMessage(data) {
    switch (data.type) {

        /**
         * Confirmação do servidor para o remetente.
         * Renderiza a mensagem enviada com estado "entregue" (✓).
         */
        case 'message_sent':
            appendMessage(data, 'me');
            updateLastMessage(data.receiver_id, data.body, data.msg_type, true);
            saveMessageToStorage(data.receiver_id, data);
            break;

        /**
         * Mensagem recebida de outro utilizador.
         * Se a conversa estiver aberta, renderiza imediatamente.
         * Caso contrário, incrementa o badge de não lidos.
         */
        case 'message':
            if (activeFriend && data.sender_id === activeFriend.id) {
                appendMessage(data, 'friend');
                // Marca como lido imediatamente (conversa aberta)
                wsSend({ type: 'message_read', sender_id: data.sender_id });
            } else {
                incrementUnread(data.sender_id);
            }
            updateLastMessage(data.sender_id, data.body, data.msg_type, false);
            saveMessageToStorage(data.sender_id, data);
            break;

        /**
         * Confirmação de que o destinatário leu as mensagens.
         * Atualiza os tiques de leitura nas mensagens enviadas.
         */
        case 'messages_read':
            markMessagesAsRead(data.friend_id);
            break;

        /**
         * Pedido de amizade recebido em tempo real.
         */
        case 'friend_request':
            addPendingRequest(data);
            break;

        /**
         * Pedido de amizade aceite — adiciona à lista de conversas.
         */
        case 'friend_accepted':
            addFriendToList(data);
            break;

        /**
         * Alteração de status online/offline.
         * Atualiza a bolinha de status e o texto em toda a interface.
         */
        case 'user_status_change':
            updateUserStatus(data.user_id, data.status);
            break;

        default:
            console.debug('[WS] Tipo de mensagem desconhecido:', data.type);
    }
}

// Expõe ws globalmente para compatibilidade com código legado
Object.defineProperty(window, 'ws', {
    get: () => ws,
    configurable: true,
});

connectWS();

// ============================================================
// LOCALSTORAGE — PERSISTÊNCIA
// ============================================================

/**
 * Guarda uma mensagem no localStorage para persistência entre recarregamentos.
 *
 * @param {number} friendId ID do amigo da conversa.
 * @param {Object} msgData  Dados da mensagem a guardar.
 * @returns {void}
 */
function saveMessageToStorage(friendId, msgData) {
    const key = `${LS_MESSAGES_PREFIX}${friendId}`;
    let history = [];

    try {
        const stored = localStorage.getItem(key);
        if (stored) history = JSON.parse(stored);
    } catch { history = []; }

    // Evita duplicados pelo mesmo id
    const exists = history.some(m => m.id && m.id === msgData.id);
    if (!exists) {
        history.push(msgData);
        // Mantém apenas as últimas 200 mensagens para não sobrecarregar o storage
        if (history.length > 200) history = history.slice(-200);
        try {
            localStorage.setItem(key, JSON.stringify(history));
        } catch {
            console.warn('[Storage] Impossível guardar mensagens (quota excedida?)');
        }
    }
}

/**
 * Carrega o histórico de mensagens do localStorage para um amigo.
 *
 * @param {number} friendId ID do amigo.
 * @returns {Object[]} Array de mensagens ou array vazio.
 */
function loadMessagesFromStorage(friendId) {
    try {
        const stored = localStorage.getItem(`${LS_MESSAGES_PREFIX}${friendId}`);
        return stored ? JSON.parse(stored) : [];
    } catch { return []; }
}

/**
 * Persiste o amigo ativo no localStorage.
 *
 * @param {Object|null} friend Objeto do amigo ativo ou null para limpar.
 * @returns {void}
 */
function saveActiveFriend(friend) {
    try {
        if (friend) {
            localStorage.setItem(LS_ACTIVE_FRIEND, JSON.stringify({
                id:       friend.id,
                name:     friend.name,
                initials: friend.initials,
                status:   friend.status || 'offline',
            }));
        } else {
            localStorage.removeItem(LS_ACTIVE_FRIEND);
        }
    } catch { /* ignorado */ }
}

/**
 * Recupera o último amigo ativo do localStorage.
 *
 * @returns {Object|null} Dados do amigo ou null.
 */
function loadActiveFriend() {
    try {
        const stored = localStorage.getItem(LS_ACTIVE_FRIEND);
        return stored ? JSON.parse(stored) : null;
    } catch { return null; }
}

/**
 * Persiste o contador de não lidos no localStorage.
 *
 * @param {number} friendId
 * @param {number} count
 * @returns {void}
 */
function saveUnreadCount(friendId, count) {
    try {
        localStorage.setItem(`${LS_UNREAD_PREFIX}${friendId}`, String(count));
    } catch { /* ignorado */ }
}

/**
 * Recupera o contador de não lidos do localStorage.
 *
 * @param {number} friendId
 * @returns {number}
 */
function loadUnreadCount(friendId) {
    try {
        return parseInt(localStorage.getItem(`${LS_UNREAD_PREFIX}${friendId}`) || '0', 10);
    } catch { return 0; }
}

// ============================================================
// ABRIR CONVERSA
// ============================================================

// Delegação de eventos para itens de conversa (inclui os adicionados dinamicamente)
document.getElementById('chatList').addEventListener('click', (e) => {
    const item = e.target.closest('.conversation-item');
    if (!item) return;

    openChat(
        parseInt(item.dataset.friendId, 10),
        item.dataset.friendName,
        item.querySelector('.av')?.innerHTML || '',
        item.dataset.friendStatus || 'offline',
        item.dataset.friendInitials || '??'
    );
});

/**
 * Abre o painel de chat para um amigo específico.
 * Carrega primeiro do localStorage e depois sincroniza com o servidor.
 *
 * @param {number} friendId     ID do amigo.
 * @param {string} friendName   Nome de exibição.
 * @param {string} avatarHtml   HTML interno do avatar.
 * @param {string} friendStatus Estado online/offline.
 * @param {string} [initials]   Iniciais de fallback.
 * @returns {void}
 */
function openChat(friendId, friendName, avatarHtml, friendStatus, initials = '??') {
    activeFriend = { id: friendId, name: friendName, avatarHtml, initials, status: friendStatus };
    saveActiveFriend(activeFriend);

    // Atualiza cabeçalho
    document.getElementById('chatName').textContent   = friendName;
    document.getElementById('chatAvatar').innerHTML   = avatarHtml;
    document.getElementById('chatStatus').textContent =
        friendStatus === 'online' ? '● Online' : '● Offline';
    document.getElementById('chatStatus').style.color =
        friendStatus === 'online' ? 'var(--green)' : 'var(--gray-400)';

    // Mostra painel de chat
    document.getElementById('chatEmpty').style.display   = 'none';
    document.getElementById('chatContent').style.display = 'flex';
    document.getElementById('appWrapper')?.classList.add('chat-open');

    // Limpa área e zera badge de não lidos
    document.getElementById('areaSms').innerHTML = '';
    clearUnread(friendId);

    // 1. Renderiza histórico do localStorage imediatamente (sem esperar o servidor)
    const cached = loadMessagesFromStorage(friendId);
    if (cached.length > 0) {
        cached.forEach(msg => {
            appendMessage(msg, msg.sender_id === MY_USER_ID ? 'me' : 'friend', false);
        });
        const area = document.getElementById('areaSms');
        if (area) area.scrollTop = area.scrollHeight;
    }

    // 2. Sincroniza com o servidor em background
    syncHistory(friendId);
}

// ============================================================
// HISTÓRICO DE MENSAGENS (REST + SYNC)
// ============================================================

/**
 * Sincroniza o histórico de mensagens com o servidor REST.
 * Mescla com o cache local, evitando duplicados por id.
 * Atualiza o localStorage com os dados mais recentes.
 *
 * @param {number} friendId ID do amigo.
 * @returns {Promise<void>}
 */
async function syncHistory(friendId) {
    try {
        const res = await fetch(`/messages/${friendId}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        if (!res.ok) throw new Error(`HTTP ${res.status}`);

        const raw  = await res.json();
        const msgs = normalizeMessages(raw);

        if (msgs.length === 0) return;

        // Guarda no localStorage
        try {
            localStorage.setItem(`${LS_MESSAGES_PREFIX}${friendId}`, JSON.stringify(msgs));
        } catch { /* quota */ }

        // Só re-renderiza se ainda estiver na mesma conversa
        if (!activeFriend || activeFriend.id !== friendId) return;

        const area = document.getElementById('areaSms');
        if (!area) return;

        area.innerHTML = '';
        msgs.forEach(msg => {
            appendMessage(msg, msg.sender_id === MY_USER_ID ? 'me' : 'friend', false);
        });
        area.scrollTop = area.scrollHeight;

    } catch (err) {
        console.error('[History] Erro ao sincronizar histórico:', err);
    }
}

/**
 * Normaliza a resposta do servidor para um array uniforme de mensagens.
 *
 * @param {*} raw Dados brutos da API.
 * @returns {Object[]}
 */
function normalizeMessages(raw) {
    if (Array.isArray(raw))               return raw;
    if (raw && Array.isArray(raw.data))   return raw.data;
    if (raw && typeof raw === 'object')   return Object.values(raw).filter(v => v && typeof v === 'object');
    return [];
}

// ============================================================
// RENDERIZAR MENSAGENS
// ============================================================

/**
 * Insere uma bolha de mensagem na área de chat.
 *
 * Tipos suportados:
 *  - `text`    → parágrafo escapado + hora + tiques de leitura (mensagens próprias).
 *  - `sticker` → emoji grande.
 *  - `gif`     → imagem animada (Giphy).
 *
 * @param {Object}        data          Dados da mensagem.
 * @param {'me'|'friend'} who           Quem enviou.
 * @param {boolean}       [scroll=true] Faz scroll até ao fim.
 * @returns {void}
 */
function appendMessage(data, who, scroll = true) {
    const area = document.getElementById('areaSms');
    if (!area) return;

    const msgType = data.msg_type || data.type || 'text';
    const isMedia = ['gif', 'sticker'].includes(msgType);
    const msgId   = data.id ? `data-msg-id="${data.id}"` : '';

    let content = '';

    if (msgType === 'gif') {
        content = `<img src="${escapeHtml(data.sticker_url || '')}" class="msg-sticker" alt="GIF" loading="lazy">`;
    } else if (msgType === 'sticker') {
        content = `<span class="msg-emoji-sticker">${data.body || ''}</span>`;
    } else {
        const ticks = who === 'me'
            ? `<span class="msg-ticks ${data.is_read ? 'read' : 'delivered'}" data-tick-id="${data.id || ''}">
                   ${data.is_read}
               </span>`
            : '';
        content = `
            <p class="text-sms">${escapeHtml(data.body ?? '')}</p>
            <div class="msg-meta">
                <span class="time">${data.time || formatTime()}</span>
            </div>
        `;
    }

    const bubbleStyle = isMedia
        ? 'style="background:transparent; box-shadow:none; padding:0;"'
        : '';

    if (who === 'me') {
        area.insertAdjacentHTML('beforeend', `
            <div class="user user-content" ${msgId}>${
                /* Sem avatar no próprio utilizador */''
            }
                <div class="messages" ${bubbleStyle}>${content}</div>
            </div>
        `);
    } else {
        area.insertAdjacentHTML('beforeend', `
            <div class="friend-content user" ${msgId}>
                <div class="av-msg">${activeFriend?.avatarHtml || activeFriend?.initials || '?'}</div>
                <div class="messages" ${bubbleStyle}>${content}</div>
            </div>
        `);
    }

    if (scroll) area.scrollTop = area.scrollHeight;
}

/**
 * Marca todas as mensagens enviadas como lidas (✓✓ azul).
 * Chamado quando o WebSocket recebe `messages_read`.
 *
 * @param {number} friendId ID do amigo que leu as mensagens.
 * @returns {void}
 */
function markMessagesAsRead(friendId) {
    if (!activeFriend || activeFriend.id !== friendId) return;

    document.querySelectorAll('.msg-ticks.delivered').forEach(el => {
        el.textContent = '✓✓';
        el.classList.replace('delivered', 'read');
    });
}

// ============================================================
// ÚLTIMA MENSAGEM NA SIDEBAR
// ============================================================

/**
 * Atualiza o preview da última mensagem e a hora no item da sidebar.
 *
 * @param {number}  friendId  ID do amigo.
 * @param {string}  body      Corpo da mensagem (pode ser null para GIFs).
 * @param {string}  msgType   Tipo: 'text' | 'sticker' | 'gif'.
 * @param {boolean} isFromMe  Se a mensagem foi enviada pelo utilizador autenticado.
 * @returns {void}
 */
function updateLastMessage(friendId, body, msgType, isFromMe) {
    const item = document.querySelector(`.conversation-item[data-friend-id="${friendId}"]`);
    if (!item) return;

    let preview = '';
    if (msgType === 'gif')     preview = 'GIF';
    else if (msgType === 'sticker') preview = body || '😊';
    else                       preview = body || '';

    const prefix = isFromMe ? 'Você: ' : '';

    // Atualiza ou cria o elemento de prévia
    let statusEl = item.querySelector('.last-msg-preview');
    if (!statusEl) {
        statusEl = document.createElement('p');
        statusEl.className = 'last-msg-preview status';
        item.querySelector('.info')?.appendChild(statusEl);
    }
    statusEl.textContent = `${prefix}${preview}`;

    // Move o item para o topo da lista
    const list = document.getElementById('chatList');
    if (list && item.parentElement === list) {
        list.prepend(item);
    }
}

// ============================================================
// BADGES DE MENSAGENS NÃO LIDAS
// ============================================================

/**
 * Incrementa o badge de não lidos de um amigo.
 *
 * @param {number} friendId ID do amigo remetente.
 * @returns {void}
 */
function incrementUnread(friendId) {
    const current = (unreadCounts.get(friendId) || loadUnreadCount(friendId)) + 1;
    unreadCounts.set(friendId, current);
    saveUnreadCount(friendId, current);
    renderUnreadBadge(friendId, current);
}

/**
 * Zera o badge de não lidos ao abrir a conversa.
 *
 * @param {number} friendId ID do amigo.
 * @returns {void}
 */
function clearUnread(friendId) {
    unreadCounts.set(friendId, 0);
    saveUnreadCount(friendId, 0);
    renderUnreadBadge(friendId, 0);
}

/**
 * Atualiza visualmente o badge de não lidos no item da sidebar.
 *
 * @param {number} friendId ID do amigo.
 * @param {number} count    Número de mensagens não lidas.
 * @returns {void}
 */
function renderUnreadBadge(friendId, count) {
    const item = document.querySelector(`.conversation-item[data-friend-id="${friendId}"]`);
    if (!item) return;

    let badge = item.querySelector('.unread-badge');

    if (count <= 0) {
        badge?.remove();
        return;
    }

    if (!badge) {
        badge = document.createElement('span');
        badge.className = 'unread-badge';
        // Insere antes do indicador de status (bolinha)
        const circle = item.querySelector('.bxs-circle');
        circle ? circle.before(badge) : item.querySelector('.users').appendChild(badge);
    }

    badge.textContent = count > 99 ? '99+' : String(count);
}

// ============================================================
// ENVIAR MENSAGENS
// ============================================================

/**
 * Envia mensagem de texto ao submeter o formulário.
 * @listens submit#formData
 */
document.getElementById('formData').addEventListener('submit', (e) => {
    e.preventDefault();
    const input = document.getElementById('messageInput');
    const body  = input.value.trim();

    if (!body || !activeFriend) return;

    if (!wsSend({
        type:        'message',
        msg_type:    'text',
        receiver_id: activeFriend.id,
        body,
    })) {
        console.error('[Chat] Mensagem não enviada — WebSocket desconectado.');
        return;
    }

    input.value = '';
});

/**
 * Envia um emoji como sticker grande.
 *
 * @param {string} emoji Caractere emoji.
 * @returns {void}
 */
function sendEmojiSticker(emoji) {
    if (!activeFriend) return;
    wsSend({
        type:        'message',
        msg_type:    'sticker',
        receiver_id: activeFriend.id,
        body:        emoji,
    });
    closePicker();
}

/**
 * Envia um GIF selecionado.
 * A renderização aguarda a confirmação `message_sent` do servidor
 * para evitar duplicados e garantir id/timestamp corretos.
 *
 * @param {string} url URL do GIF (Giphy fixed_height).
 * @returns {void}
 */
function sendGif(url) {
    if (!activeFriend) {
        alert('Selecione uma conversa primeiro!');
        return;
    }
    wsSend({
        type:        'message',
        msg_type:    'gif',
        receiver_id: activeFriend.id,
        body:        null,
        sticker_url: url,
    });
    closePicker();
}

// ============================================================
// STATUS ONLINE / OFFLINE
// ============================================================

/**
 * Atualiza a bolinha de status e o texto de um utilizador em toda a interface.
 * Chamado quando o WebSocket recebe `user_status_change`.
 *
 * @param {number} userId ID do utilizador.
 * @param {string} status 'online' | 'offline'.
 * @returns {void}
 */
function updateUserStatus(userId, status) {
    const item = document.querySelector(`.conversation-item[data-friend-id="${userId}"]`);
    if (item) {
        const circle     = item.querySelector('.bxs-circle');
        const statusText = item.querySelector('.status, .last-msg-preview');

        if (circle) {
            circle.style.color = status === 'online' ? 'var(--green)' : 'var(--gray-400)';
        }

        item.dataset.friendStatus = status;
    }

    // Se a conversa com este utilizador estiver aberta, atualiza o cabeçalho
    if (activeFriend && activeFriend.id === userId) {
        const chatStatus = document.getElementById('chatStatus');
        if (chatStatus) {
            chatStatus.textContent = status === 'online' ? '● Online' : '● Offline';
            chatStatus.style.color = status === 'online' ? 'var(--green)' : 'var(--gray-400)';
        }
        activeFriend.status = status;
        saveActiveFriend(activeFriend);
    }
}

// ============================================================
// PICKER UNIFICADO (EMOJI + GIF)
// ============================================================

document.getElementById('btnEmoji').addEventListener('click', (e) => {
    e.stopPropagation();
    togglePicker('emoji');
});

document.getElementById('btnEm')?.addEventListener('click', (e) => {
    e.stopPropagation();
    togglePicker('emoji');
});

document.getElementById('btnSticker').addEventListener('click', (e) => {
    e.stopPropagation();
    togglePicker('gif');
});

document.getElementById('btnGi')?.addEventListener('click', (e) => {
    e.stopPropagation();
    togglePicker('gif');
});

/**
 * Abre o picker no tab especificado, ou fecha se já estiver aberto no mesmo tab.
 *
 * @param {'emoji'|'gif'} tab
 * @returns {void}
 */
function togglePicker(tab) {
    const picker   = document.getElementById('mainPicker');
    const isOpen   = picker.classList.contains('active');
    const sameTab  = activePickerTab === tab;

    if (isOpen && sameTab) {
        closePicker();
        return;
    }

    picker.classList.add('active');
    switchPickerTab(tab);
}

/**
 * Muda o tab ativo no picker.
 *
 * @param {'emoji'|'gif'} tab
 * @returns {void}
 */
function switchPickerTab(tab) {
    activePickerTab = tab;

    document.querySelectorAll('.picker-tab').forEach(t =>
        t.classList.toggle('active', t.dataset.tab === tab)
    );

    document.getElementById('tabEmoji').style.display = tab === 'emoji' ? 'block' : 'none';
    document.getElementById('tabGif').style.display   = tab === 'gif'   ? 'block' : 'none';

    if (tab === 'gif' && !gifLoaded) {
        loadGifs('');
        gifLoaded = true;
    }
}

/** Fecha o picker de emojis/GIFs. */
function closePicker() {
    document.getElementById('mainPicker')?.classList.remove('active');
}

// Fecha ao clicar fora
document.addEventListener('click', (e) => {
    if (!document.getElementById('mainPicker')?.contains(e.target)) {
        closePicker();
    }
});

// Impede fecho ao clicar dentro do picker
document.getElementById('mainPicker')?.addEventListener('click', (e) => e.stopPropagation());

// ============================================================
// GIFs — GIPHY API
// ============================================================

document.getElementById('gifSearch').addEventListener('input', (e) => {
    clearTimeout(gifDebounce);
    gifDebounce = setTimeout(() => loadGifs(e.target.value.trim()), 400);
});

/**
 * Carrega GIFs da API Giphy e renderiza na grelha.
 *
 * @param {string} [query=''] Pesquisa (vazio = trending).
 * @returns {Promise<void>}
 */
async function loadGifs(query = '') {
    const grid = document.getElementById('gifGrid');
    if (!grid) return;
    grid.innerHTML = '<p class="sticker-loading">A carregar GIFs...</p>';

    const url = query ? GIPHY_SEARCH(query) : GIPHY_TRENDING;

    try {
        const res  = await fetch(url);
        const json = await res.json();

        grid.innerHTML = '';

        if (!json.data || json.data.length === 0) {
            grid.innerHTML = '<p class="sticker-loading">Nenhum resultado.</p>';
            return;
        }

        json.data.forEach(gif => {
            const img   = document.createElement('img');
            img.src     = gif.images.fixed_height_small.url;
            img.alt     = gif.title || 'GIF';
            img.className = 'gif-item';
            img.loading = 'lazy';
            img.onclick = () => sendGif(gif.images.fixed_height.url);
            grid.appendChild(img);
        });
    } catch (err) {
        grid.innerHTML = '<p class="sticker-loading" style="color:var(--red);">Erro ao carregar do Giphy.</p>';
        console.error('[Giphy] Erro:', err);
    }
}

// ============================================================
// EMOJIS
// ============================================================

/**
 * Constrói o tab de emojis dinamicamente a partir de EMOJI_CATEGORIES.
 * Os emojis são inseridos no cursor do input (não enviados diretamente como sticker).
 *
 * @returns {void}
 */
function buildEmojiTab() {
    const container = document.getElementById('emojiCategoryList');
    if (!container) return;
    container.innerHTML = '';

    Object.entries(EMOJI_CATEGORIES).forEach(([label, emojis]) => {
        const section = document.createElement('div');
        section.innerHTML = `<p class="emoji-category-label">${escapeHtml(label)}</p>`;

        const grid = document.createElement('div');
        grid.className = 'emoji-grid';

        emojis.forEach(emoji => {
            const btn     = document.createElement('button');
            btn.type      = 'button';
            btn.className = 'emoji-btn';
            btn.textContent = emoji;
            btn.onclick   = () => insertEmojiAtCursor(emoji);
            grid.appendChild(btn);
        });

        section.appendChild(grid);
        container.appendChild(section);
    });
}

/**
 * Insere um emoji na posição atual do cursor no input de texto.
 *
 * @param {string} emoji Emoji a inserir.
 * @returns {void}
 */
function insertEmojiAtCursor(emoji) {
    const input = document.getElementById('messageInput');
    if (!input) return;
    const start  = input.selectionStart ?? input.value.length;
    input.value  = input.value.slice(0, start) + emoji + input.value.slice(start);
    input.focus();
    input.setSelectionRange(start + emoji.length, start + emoji.length);
}

buildEmojiTab();

// ============================================================
// PESQUISA EM TEMPO REAL
// ============================================================

/**
 * Filtra a lista de conversas em tempo real à medida que o utilizador digita.
 * Faz a pesquisa por nome do amigo (case-insensitive).
 *
 * @listens input#searchInput
 */
document.getElementById('searchInput')?.addEventListener('input', (e) => {
    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(() => {
        filterConversations(e.target.value.trim().toLowerCase());
    }, 200);
});

/**
 * Mostra/oculta os itens de conversa com base no termo de pesquisa.
 *
 * @param {string} query Termo de pesquisa em minúsculas.
 * @returns {void}
 */
function filterConversations(query) {
    const items   = document.querySelectorAll('#chatList .conversation-item');
    let   visible = 0;

    items.forEach(item => {
        const name    = (item.dataset.friendName || '').toLowerCase();
        const matches = !query || name.includes(query);
        item.style.display = matches ? '' : 'none';
        if (matches) visible++;
    });

    // Feedback se não houver resultados
    let noResults = document.getElementById('searchNoResults');
    if (!noResults) {
        noResults = document.createElement('p');
        noResults.id        = 'searchNoResults';
        noResults.style.cssText = 'padding:1.5rem 1rem; color:var(--gray-400); text-align:center; display:none;';
        noResults.textContent   = 'Nenhuma conversa encontrada.';
        document.getElementById('chatList')?.appendChild(noResults);
    }
    noResults.style.display = visible === 0 && query ? '' : 'none';
}

// ============================================================
// MENU FLUTUANTE
// ============================================================

/**
 * Alterna a visibilidade do menu flutuante.
 * @listens click .btnMenuFloat
 */
document.querySelector('.btnMenuFloat')?.addEventListener('click', (e) => {
    e.stopPropagation();
    document.querySelector('.menu-float')?.classList.toggle('active');
});

/**
 * Fecha o menu flutuante ao clicar em qualquer lugar da página.
 * Usa delegação no document para capturar todos os cliques.
 *
 * @listens click document
 */
document.addEventListener('click', () => {
    document.querySelector('.menu-float')?.classList.remove('active');
});

// Impede que cliques dentro do menu o fechem
document.querySelector('.menu-float')?.addEventListener('click', (e) => e.stopPropagation());

// ============================================================
// GESTÃO DE AMIZADES
// ============================================================

/**
 * Obtém o token CSRF do formulário mais próximo de um elemento.
 * Suporta os nomes `_csrf_token` (Slenix) e `_token` (fallback).
 *
 * @param {HTMLElement} el Elemento de referência.
 * @returns {string|null} Token CSRF ou null se não encontrado.
 */
function getCsrfToken(el) {
    const form = el.closest('form');
    if (!form) return null;

    return (
        form.querySelector('input[name="_csrf_token"]')?.value ||
        form.querySelector('input[name="_token"]')?.value      ||
        document.querySelector('meta[name="csrf-token"]')?.content ||
        null
    );
}

// ── Enviar Pedido de Amizade ────────────────────────────────

/**
 * Delegação de evento para botões "Adicionar Amigo".
 * Processa o pedido via AJAX e notifica via WebSocket.
 *
 * @listens click(document) .btn-add-friend
 */
document.addEventListener('click', async (e) => {
    const btn = e.target.closest('.btn-add-friend');
    if (!btn || btn.disabled) return;

    e.preventDefault();
    e.stopPropagation();

    const form       = btn.closest('form');
    const receiverId = parseInt(btn.dataset.userId, 10);
    const csrfToken  = getCsrfToken(btn);

    if (!csrfToken) {
        console.error('[Friends] Token CSRF não encontrado.');
        return;
    }

    btn.disabled    = true;
    btn.textContent = '...';

    try {
        const url = form?.action || `/friends/${receiverId}/request`;
        const res = await fetch(url, {
            method:  'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN':     csrfToken,
            },
            body: new FormData(form),
        });

        const data = await res.json();

        if (data.type === 'friend_request_sent') {
            // Notifica o destinatário via WebSocket
            wsSend({ type: 'friend_request', receiver_id: receiverId });

            btn.textContent          = 'Solicitado ✓';
            btn.style.background     = 'var(--gray-400)';
            btn.style.cursor         = 'default';
            btn.setAttribute('aria-disabled', 'true');
        } else {
            btn.disabled    = false;
            btn.textContent = 'Adicionar';
            console.warn('[Friends] Erro:', data.message);
        }
    } catch (err) {
        btn.disabled    = false;
        btn.textContent = 'Adicionar';
        console.error('[Friends] Erro fatal no pedido:', err);
    }
});

// ── Aceitar Pedido de Amizade ───────────────────────────────

/**
 * Delegação de evento para botões "Aceitar Amizade".
 * Move o utilizador da lista de pendentes para a lista de conversas.
 *
 * @listens click(document) .btn-accept-friend
 */
document.addEventListener('click', async (e) => {
    const btn = e.target.closest('.btn-accept-friend');
    if (!btn || btn.disabled) return;

    e.preventDefault();
    e.stopPropagation();

    const form         = btn.closest('form');
    const friendshipId = btn.dataset.friendshipId;
    const csrfToken    = getCsrfToken(btn);
    const container    = btn.closest('.users-list-sms');

    btn.disabled    = true;
    btn.textContent = '...';

    try {
        const url = form?.action || `/friends/${friendshipId}/accept`;
        const res = await fetch(url, {
            method:  'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN':     csrfToken || '',
            },
            body: new FormData(form),
        });

        const data = await res.json();

        if (data.type === 'friend_accepted') {
            // Notifica o remetente original via WebSocket
            wsSend({ type: 'friend_accepted', friend_id: data.friend_id });

            // Recolhe dados do novo amigo a partir do DOM do pedido pendente
            const friendName     = container?.querySelector('.name')?.textContent?.trim() || 'Utilizador';
            const friendAvatarHtml = container?.querySelector('.av')?.innerHTML || '';
            const friendInitials = extractInitials(friendName);

            // Injeta na lista de conversas
            injectFriendToChat(data.friend_id, friendName, friendAvatarHtml, friendInitials);

            // Remove o pedido pendente com animação
            if (container) {
                container.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                container.style.opacity    = '0';
                container.style.transform  = 'translateX(-10px)';
                setTimeout(() => container.remove(), 300);
            }
        } else {
            btn.disabled    = false;
            btn.textContent = 'Aceitar';
            console.warn('[Friends] Erro ao aceitar:', data.message);
        }
    } catch (err) {
        btn.disabled    = false;
        btn.textContent = 'Aceitar';
        console.error('[Friends] Erro fatal ao aceitar:', err);
    }
});

/**
 * Injeta um novo amigo na lista de conversas (#chatList) com estrutura completa.
 * Adiciona o event listener de clique para abrir o chat.
 *
 * @param {number} friendId       ID do novo amigo.
 * @param {string} friendName     Nome completo.
 * @param {string} friendAvatarHtml HTML do avatar.
 * @param {string} initials       Iniciais de fallback.
 * @returns {void}
 */
function injectFriendToChat(friendId, friendName, friendAvatarHtml, initials) {
    const chatList = document.getElementById('chatList');
    if (!chatList) return;

    // Evita duplicados
    if (chatList.querySelector(`.conversation-item[data-friend-id="${friendId}"]`)) return;

    const div = document.createElement('div');
    div.className                  = 'users-list-sms conversation-item';
    div.dataset.friendId           = String(friendId);
    div.dataset.friendName         = friendName;
    div.dataset.friendStatus       = 'online';
    div.dataset.friendInitials     = initials;

    div.innerHTML = `
        <div class="users">
            <div class="sms-left">
                <div class="av" style="border:none;background:transparent;">
                    ${friendAvatarHtml}
                </div>
                <div class="info">
                    <h3 class="name">${escapeHtml(friendName)}</h3>
                    <p class="status">online</p>
                </div>
            </div>
            <i class="bx bxs-circle" style="font-size:.55rem;color:var(--green);flex-shrink:0;"></i>
        </div>
    `;

    // Animação de entrada
    div.style.opacity   = '0';
    div.style.transform = 'translateY(-8px)';
    chatList.prepend(div);

    requestAnimationFrame(() => {
        div.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
        div.style.opacity    = '1';
        div.style.transform  = 'translateY(0)';
    });
}

/**
 * Adiciona um pedido de amizade pendente à aba "Amigos" em tempo real.
 * Chamado quando o WebSocket recebe `friend_request`.
 *
 * @param {{ sender_id: number, name: string, initials?: string }} data
 * @returns {void}
 */
function addPendingRequest(data) {
    // Tenta encontrar a secção de pendentes na aba de Amigos
    const friendsTab = document.querySelector('.content-2');
    if (!friendsTab) return;

    let section = friendsTab.querySelector('[data-section="pending"]');
    if (!section) {
        // Cria a secção se não existir
        section = document.createElement('div');
        section.dataset.section = 'pending';
        section.innerHTML = '<h4>Solicitações Pendentes</h4>';
        friendsTab.prepend(section);
    }

    const initials = data.initials || extractInitials(data.name || 'U');
    const item     = document.createElement('div');
    item.className = 'users-list-sms';
    item.innerHTML = `
        <div class="users">
            <div class="sms-left">
                <div class="av" style="border:none;background:transparent;">
                    <div style="width:44px;height:44px;border-radius:50%;background:var(--primary);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:.9rem;">
                        ${escapeHtml(initials)}
                    </div>
                </div>
                <div class="info">
                    <h3 class="name">${escapeHtml(data.name || 'Utilizador')}</h3>
                    <p class="status">Enviou-te um pedido</p>
                </div>
            </div>
            <p style="font-size:.75rem;color:var(--gray-400);">Novo pedido</p>
        </div>
    `;

    section.appendChild(item);

    // Badge na aba de Amigos
    const friendsLabel = document.querySelector('label[for="friends"]');
    if (friendsLabel && !friendsLabel.querySelector('.tab-badge')) {
        const badge       = document.createElement('span');
        badge.className   = 'tab-badge';
        badge.textContent = '•';
        friendsLabel.appendChild(badge);
    }
}

/**
 * Adiciona um novo amigo à lista quando o WebSocket recebe `friend_accepted`.
 * Chamado do lado de quem enviou o pedido original.
 *
 * @param {{ user_id: number, name?: string, initials?: string, status?: string }} data
 * @returns {void}
 */
function addFriendToList(data) {
    injectFriendToChat(
        data.user_id || data.friend_id,
        data.name || 'Novo Amigo',
        '',
        data.initials || extractInitials(data.name || 'NA')
    );
}

// ============================================================
// SUPORTE MÓVEL
// ============================================================

/**
 * Volta para a lista de conversas (mobile).
 * @listens click#btnBack
 */
document.getElementById('btnBack')?.addEventListener('click', () => {
    document.getElementById('appWrapper')?.classList.remove('chat-open');
    activeFriend = null;
    saveActiveFriend(null);
});

// ============================================================
// RESTAURAR SESSÃO AO CARREGAR A PÁGINA
// ============================================================

/**
 * Ao carregar a página, verifica se havia uma conversa ativa no localStorage
 * e restaura automaticamente o chat (sem perder o contexto após F5).
 *
 * @returns {void}
 */
(function restoreSession() {
    const saved = loadActiveFriend();
    if (!saved) return;

    // Encontra o item correspondente na lista
    const item = document.querySelector(`.conversation-item[data-friend-id="${saved.id}"]`);
    if (!item) return;

    // Aguarda brevemente para o DOM estar completamente pronto
    setTimeout(() => {
        openChat(
            saved.id,
            saved.name,
            item.querySelector('.av')?.innerHTML || saved.initials || '??',
            saved.status || 'offline',
            saved.initials || '??'
        );
    }, 100);

    // Restaura badges de não lidos
    document.querySelectorAll('.conversation-item').forEach(convItem => {
        const fid   = parseInt(convItem.dataset.friendId, 10);
        const count = loadUnreadCount(fid);
        if (count > 0) {
            unreadCounts.set(fid, count);
            renderUnreadBadge(fid, count);
        }
    });
})();

// ============================================================
// UTILITÁRIOS
// ============================================================

/**
 * Escapa caracteres especiais HTML para prevenir XSS.
 *
 * @param {string} text Texto a escapar.
 * @returns {string}
 */
function escapeHtml(text) {
    if (!text) return '';
    return String(text)
        .replace(/&/g,  '&amp;')
        .replace(/</g,  '&lt;')
        .replace(/>/g,  '&gt;')
        .replace(/"/g,  '&quot;')
        .replace(/'/g,  '&#39;');
}

/**
 * Formata a hora atual no formato HH:MM.
 *
 * @returns {string} Hora formatada.
 */
function formatTime() {
    const now = new Date();
    return `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;
}

/**
 * Extrai as iniciais de um nome completo (até 2 caracteres).
 *
 * @param {string} name Nome completo.
 * @returns {string} Iniciais em maiúsculas.
 */
function extractInitials(name) {
    const parts = name.trim().split(/\s+/);
    if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
}