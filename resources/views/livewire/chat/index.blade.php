<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\On;
use Livewire\Attributes\Layout;
use App\Models\Message;
use App\Events\MessageSent;
use App\Events\MessageDeleted;
use Illuminate\Support\Facades\Storage;

new #[Layout('layouts.app')] class extends Component {
    use WithFileUploads;

    public $chatMessages = [];
    public $body = '';
    public $attachment;
    public $replyToMessage = null;

    public function mount()
    {
        $this->loadMessages();
    }

    public function loadMessages()
    {
        // Usamos Eloquent collections para la UI
        $this->chatMessages = Message::with(['user', 'replyTo.user'])
            ->latest()
            ->take(100)
            ->get()
            ->reverse()
            ->values();
    }

    public function sendMessage()
    {
        $this->validate([
            'body' => 'required_without:attachment|max:2000',
            'attachment' => 'nullable|file|max:5120', // 5MB = 5120KB
        ]);

        $path = null;
        $name = null;
        $type = null;

        if ($this->attachment) {
            $name = $this->attachment->getClientOriginalName();
            $mime = $this->attachment->getMimeType();
            $path = $this->attachment->store('chat', 'public');
            
            if (str_starts_with($mime, 'image/')) {
                $type = 'image';
            } elseif (str_starts_with($mime, 'video/')) {
                $type = 'video';
            } else {
                $type = 'document';
            }
        }

        $message = Message::create([
            'user_id' => auth()->id(),
            'body' => $this->body,
            'attachment_path' => $path,
            'attachment_name' => $name,
            'attachment_type' => $type,
            'reply_to_id' => $this->replyToMessage ? $this->replyToMessage['id'] : null,
        ]);

        $message->load(['user', 'replyTo.user']);

        broadcast(new MessageSent($message))->toOthers();

        $this->chatMessages->push($message);
        
        $this->reset('body', 'attachment', 'replyToMessage');
        
        $this->dispatch('message-sent');
    }

    public function setReply($id)
    {
        $this->replyToMessage = Message::with('user')->find($id)->toArray();
    }

    public function cancelReply()
    {
        $this->replyToMessage = null;
    }

    public function deleteMessage($id)
    {
        $message = Message::find($id);
        
        if ($message && ($message->user_id === auth()->id() || auth()->user()->role === 'admin')) {
            $message->update(['is_deleted' => true]);
            
            // Delete attachment if exists
            if ($message->attachment_path) {
                Storage::disk('public')->delete($message->attachment_path);
                $message->update(['attachment_path' => null]);
            }
            
            broadcast(new MessageDeleted($id))->toOthers();
            
            $this->loadMessages();
        }
    }

    #[On('echo-presence:general-chat,MessageSent')]
    public function onMessageSent($event)
    {
        $this->loadMessages();
        $this->dispatch('message-received');
    }

    // Prevents 500 error when Alpine/Browser extensions try to stringify $wire
    public function toJSON()
    {
        return [];
    }

    #[On('echo-presence:general-chat,MessageDeleted')]
    public function onMessageDeleted($event)
    {
        $this->loadMessages();
    }
}; ?>

<div class="flex-1 flex flex-col bg-gray-50 h-full w-full" x-data="{
    typingUsers: [],
    onlineUsers: [],
    me: {{ auth()->id() }},
    initEcho() {
        let checkEcho = setInterval(() => {
            if (window.Echo) {
                clearInterval(checkEcho);
                let channel = window.Echo.join('general-chat');
                
                channel.here((users) => {
                    let usersArray = Array.isArray(users) ? users : Object.values(users);
                    // Asegurarnos de que nosotros estamos en la lista siempre
                    if (!usersArray.find(u => u.id === this.me)) {
                        usersArray.push({
                            id: this.me,
                            name: '{{ auth()->user()->name }}',
                            photo_path: '{{ auth()->user()->photo_path }}'
                        });
                    }
                    this.onlineUsers = usersArray;
                })
                .joining((user) => {
                    if (!this.onlineUsers.find(u => u.id === user.id)) {
                        this.onlineUsers.push(user);
                    }
                })
                .leaving((user) => {
                    this.onlineUsers = this.onlineUsers.filter(u => u.id !== user.id);
                })
                .listenForWhisper('typing', (e) => {
                    if (e.id === this.me) return;
                    
                    let userIndex = this.typingUsers.findIndex(u => u.id === e.id);
                    if (userIndex === -1) {
                        this.typingUsers.push({ id: e.id, name: e.name, timer: null });
                    }
                    
                    let userRef = this.typingUsers.find(u => u.id === e.id);
                    if (userRef) {
                        if (userRef.timer) clearTimeout(userRef.timer);
                        userRef.timer = setTimeout(() => {
                            this.typingUsers = this.typingUsers.filter(u => u.id !== e.id);
                        }, 2000);
                    }
                });
            }
        }, 200);
    },
    sendTypingEvent() {
        if (window.Echo) {
            window.Echo.join('general-chat').whisper('typing', {
                id: this.me,
                name: '{{ auth()->user()->name }}'
            });
        }
    },
    scrollToBottom() {
        setTimeout(() => {
            let container = $refs.messagesContainer;
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        }, 100);
    },
    insertEmoji(emoji) {
        let current = $wire.get('body') || '';
        $wire.set('body', current + emoji);
    },
    parseYouTube(text) {
        if (!text) return '';
        const regex = /(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/;
        const match = text.match(regex);
        if (match) {
            return '<iframe class=\'w-full aspect-video rounded-lg mt-2\' src=\'https://www.youtube.com/embed/' + match[1] + '\' frameborder=\'0\' allowfullscreen></iframe>';
        }
        return '';
    },
    formatText(text) {
        if (!text) return '';
        let div = document.createElement('div');
        div.innerText = text;
        let escaped = div.innerHTML;
        const urlRegex = /(https?:\/\/[^\s]+)/g;
        return escaped.replace(urlRegex, function(url) {
            return `<a href='${url}' target='_blank' class='text-indigo-500 hover:underline'>${url}</a>`;
        });
    }
}" x-init="scrollToBottom(); initEcho();"
@message-sent.window="scrollToBottom()"
@message-received.window="scrollToBottom()"
>
    <script type="module" src="https://cdn.jsdelivr.net/npm/emoji-picker-element@^1/index.js"></script>
    <style>
        /* Bloquear el scroll global y convertir el layout en una app de pantalla completa (Flexbox) */
        body, html {
            overflow: hidden !important;
            height: 100% !important;
            height: 100dvh !important;
        }
        @supports (-webkit-touch-callout: none) {
            body, html {
                height: -webkit-fill-available !important;
            }
        }
        /* El div principal de layouts.app */
        .min-h-screen {
            height: 100% !important;
            height: 100dvh !important;
            min-height: 100% !important;
            display: flex !important;
            flex-direction: column !important;
            overflow: hidden !important;
        }
        @supports (-webkit-touch-callout: none) {
            .min-h-screen {
                height: -webkit-fill-available !important;
            }
        }
        /* El contenedor main de layouts.app */
        main {
            flex: 1 1 0% !important;
            display: flex !important;
            flex-direction: column !important;
            overflow: hidden !important;
            padding: 0 !important;
            margin: 0 !important;
        }
    </style>
    <!-- Chat Header -->
    <div class="bg-white px-4 py-4 sm:px-6 shadow-sm border-b border-gray-200 z-10 flex-shrink-0">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center gap-2">
                <svg class="w-6 h-6 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"></path></svg>
                Chat Global de Líderes
            </h2>
            <div class="flex items-center gap-2 text-sm text-gray-500">
                <template x-if="onlineUsers.length > 0">
                    <div class="flex items-center gap-2">
                        <span class="relative flex h-3 w-3">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                        </span>
                        <span class="font-medium text-green-600" x-text="onlineUsers.length + ' en línea'"></span>
                        
                        <!-- Miniaturas de usuarios (max 3) -->
                        <div class="hidden sm:flex -space-x-2 ml-2 pr-1">
                            <template x-for="(ou, index) in onlineUsers.slice(0, 3)" :key="ou.id">
                                <div class="inline-block h-6 w-6 rounded-full ring-2 ring-white bg-indigo-100 flex items-center justify-center text-[10px] font-bold text-indigo-700 overflow-hidden" :title="ou.name">
                                    <template x-if="ou.photo_path">
                                        <img :src="'/storage/' + ou.photo_path" class="h-full w-full object-cover">
                                    </template>
                                    <template x-if="!ou.photo_path">
                                        <span x-text="ou.name.substring(0, 1)"></span>
                                    </template>
                                </div>
                            </template>
                            <template x-if="onlineUsers.length > 3">
                                <div class="inline-block h-6 w-6 rounded-full ring-2 ring-white bg-gray-100 flex items-center justify-center text-[10px] font-bold text-gray-600">
                                    <span x-text="'+' + (onlineUsers.length - 3)"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
                <template x-if="onlineUsers.length === 0">
                    <span class="text-gray-400 italic">Conectando...</span>
                </template>
            </div>
        </div>
    </div>

    <!-- Messages Area -->
    <div class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-6 flex flex-col" x-ref="messagesContainer">
        @if(count($chatMessages) === 0)
            <div class="flex-1 flex flex-col items-center justify-center text-gray-400 opacity-70">
                <svg class="w-16 h-16 mb-4 text-indigo-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"></path></svg>
                <p class="text-lg font-medium text-gray-500">Aún no hay mensajes</p>
                <p class="text-sm">¡Sé el primero en saludar a la comunidad!</p>
            </div>
        @endif
        @foreach($chatMessages as $msg)
            @php
                $isMine = $msg->user_id === auth()->id();
            @endphp
            <div class="flex {{ $isMine ? 'justify-end' : 'justify-start' }} group" wire:key="msg-{{ $msg->id }}">
                
                @if(!$isMine)
                    <!-- Avatar -->
                    <div class="flex-shrink-0 mr-3 mt-1">
                        @if($msg->user->photo_path)
                            <img src="{{ asset('storage/'.$msg->user->photo_path) }}" class="h-8 w-8 rounded-full object-cover border border-gray-200">
                        @else
                            <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold text-xs">
                                {{ substr($msg->user->name, 0, 1) }}
                            </div>
                        @endif
                    </div>
                @endif

                <div class="max-w-[85%] sm:max-w-[70%] flex flex-col {{ $isMine ? 'items-end' : 'items-start' }}">
                    <!-- Header -->
                    <div class="flex items-baseline gap-2 mb-1">
                        @if(!$isMine)
                            <span class="text-xs font-bold text-gray-700">{{ $msg->user->name }}</span>
                        @endif
                        <span class="text-[10px] text-gray-400">{{ $msg->created_at->format('H:i') }}</span>
                    </div>

                    <!-- Bubble -->
                    <div class="relative group">
                        @if($msg->is_deleted)
                            <div class="px-4 py-2 rounded-2xl bg-gray-100 border border-gray-200 text-gray-400 italic text-sm flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                                Este mensaje ha sido eliminado.
                            </div>
                        @else
                            <!-- Actions menu -->
                            <div class="absolute {{ $isMine ? 'right-full mr-2' : 'left-full ml-2' }} top-1/2 -translate-y-1/2 opacity-100 sm:opacity-0 sm:group-hover:opacity-100 transition-opacity flex items-center gap-1">
                                <button wire:click="setReply({{ $msg->id }})" class="p-1.5 bg-white rounded-full shadow text-gray-500 hover:text-indigo-600 transition" title="Responder">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path></svg>
                                </button>
                                @if($isMine || auth()->user()->role === 'admin')
                                    <button wire:click="deleteMessage({{ $msg->id }})" wire:confirm="¿Seguro que deseas borrar este mensaje?" class="p-1.5 bg-white rounded-full shadow text-gray-500 hover:text-red-600 transition" title="Eliminar">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                @endif
                            </div>

                            <div class="px-4 py-3 rounded-2xl shadow-sm {{ $isMine ? 'bg-indigo-600 text-white rounded-tr-sm' : 'bg-white border border-gray-100 text-gray-800 rounded-tl-sm' }}">
                                
                                <!-- Reply Reference -->
                                @if($msg->replyTo)
                                    <div class="mb-2 p-2 rounded bg-black/10 {{ $isMine ? 'border-l-4 border-white/50' : 'border-l-4 border-indigo-400' }} text-sm opacity-90 cursor-pointer" @click="document.getElementById('msg-{{ $msg->replyTo->id }}')?.scrollIntoView({behavior:'smooth'})">
                                        <div class="font-bold text-xs {{ $isMine ? 'text-indigo-100' : 'text-indigo-600' }}">{{ $msg->replyTo->user->name }}</div>
                                        <div class="truncate text-xs">{{ $msg->replyTo->is_deleted ? 'Mensaje eliminado' : ($msg->replyTo->body ?: 'Archivo adjunto') }}</div>
                                    </div>
                                @endif

                                <!-- Attachment -->
                                @if($msg->attachment_path)
                                    @if($msg->attachment_type === 'image')
                                        <a href="{{ asset('storage/'.$msg->attachment_path) }}" target="_blank">
                                            <img src="{{ asset('storage/'.$msg->attachment_path) }}" class="max-w-full rounded-lg mb-2 cursor-pointer hover:opacity-90 transition">
                                        </a>
                                    @elseif($msg->attachment_type === 'video')
                                        <video src="{{ asset('storage/'.$msg->attachment_path) }}" controls class="max-w-full rounded-lg mb-2"></video>
                                    @else
                                        <a href="{{ asset('storage/'.$msg->attachment_path) }}" target="_blank" class="flex items-center gap-3 p-3 rounded-lg mb-2 {{ $isMine ? 'bg-indigo-700 hover:bg-indigo-800' : 'bg-gray-50 hover:bg-gray-100' }} transition">
                                            <svg class="w-8 h-8 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                            <div class="overflow-hidden">
                                                <p class="font-bold text-sm truncate">{{ $msg->attachment_name }}</p>
                                                <p class="text-xs opacity-70">Documento</p>
                                            </div>
                                        </a>
                                    @endif
                                @endif

                                <!-- Body Text -->
                                @if($msg->body)
                                    <div class="text-sm whitespace-pre-wrap break-words leading-relaxed" x-html="formatText('{{ addslashes($msg->body) }}')"></div>
                                    
                                    <!-- Embedded YouTube (if detected) -->
                                    <div x-html="parseYouTube('{{ addslashes($msg->body) }}')"></div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Typing Indicator -->
    <div x-show="typingUsers.length > 0" x-transition style="display: none;" class="px-6 py-2 text-sm font-medium text-orange-600 italic bg-orange-50 flex items-center gap-2 border-t border-orange-100 shadow-inner">
        <span class="flex gap-1">
            <span class="w-1.5 h-1.5 bg-orange-500 rounded-full animate-bounce"></span>
            <span class="w-1.5 h-1.5 bg-orange-500 rounded-full animate-bounce" style="animation-delay: 0.2s"></span>
            <span class="w-1.5 h-1.5 bg-orange-500 rounded-full animate-bounce" style="animation-delay: 0.4s"></span>
        </span>
        <span x-text="typingUsers.map(u => u.name.split(' ')[0]).join(', ') + (typingUsers.length > 1 ? ' están escribiendo...' : ' está escribiendo...')"></span>
    </div>

    <!-- Input Area -->
    <div class="bg-white border-t border-gray-200 px-4 pt-4 pb-4 sm:px-6" style="padding-bottom: calc(1rem + env(safe-area-inset-bottom));">
        
        <!-- Reply Context -->
        @if($replyToMessage)
            <div class="flex items-center justify-between mb-3 p-2 bg-indigo-50 border-l-4 border-indigo-500 rounded-r-lg text-sm">
                <div>
                    <span class="font-bold text-indigo-700">Respondiendo a {{ $replyToMessage['user']['name'] }}</span>
                    <p class="text-gray-600 truncate max-w-md">{{ $replyToMessage['body'] ?: 'Archivo adjunto' }}</p>
                </div>
                <button wire:click="cancelReply" class="text-gray-400 hover:text-gray-600 p-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
        @endif

        <!-- File Upload Preview -->
        @if($attachment)
            <div class="mb-3 p-2 bg-gray-50 border rounded-lg flex items-center justify-between">
                <div class="flex items-center gap-2 truncate text-sm text-gray-700">
                    <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                    <span class="truncate font-medium">{{ $attachment->getClientOriginalName() }}</span>
                    <span class="text-gray-400 text-xs">({{ round($attachment->getSize() / 1024) }} KB)</span>
                </div>
                <button wire:click="$set('attachment', null)" class="text-red-400 hover:text-red-600 p-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            @error('attachment') <span class="text-red-500 text-xs block mb-2">{{ $message }}</span> @enderror
        @endif

        <form wire:submit="sendMessage" class="flex items-end gap-2">
            
            <!-- Contenedor Principal (Píldora) -->
            <div class="flex-1 flex items-end bg-gray-100 rounded-3xl border border-transparent focus-within:border-indigo-300 focus-within:bg-white transition-colors duration-200 shadow-sm relative min-h-[44px]">
                
                <!-- Emoji Picker -->
                <div class="relative flex-shrink-0" x-data="{ emojiOpen: false }">
                    <button type="button" @click="emojiOpen = !emojiOpen" class="px-3 py-2 text-gray-500 hover:text-indigo-600 transition h-full flex items-center mt-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </button>
                    
                    <!-- Contenedor del Emoji Picker Profesional -->
                    <div x-show="emojiOpen" x-transition style="display: none; bottom: 100%; margin-bottom: 10px;" @click.outside="emojiOpen = false" class="absolute sm:left-0 -left-2 z-50 shadow-2xl rounded-2xl overflow-hidden border border-gray-200">
                        <emoji-picker class="light" style="--num-columns: 8; --emoji-size: 1.5rem;" @emoji-click="insertEmoji($event.detail.unicode)"></emoji-picker>
                    </div>
                </div>

                <!-- Input Text -->
                <div class="flex-1 relative flex items-center">
                    <textarea 
                        wire:model="body" 
                        x-on:keydown.enter.prevent="if(!$event.shiftKey) $wire.sendMessage()"
                        placeholder="Mensaje" 
                        class="w-full border-none focus:ring-0 bg-transparent resize-none max-h-32 py-2.5 px-0 text-[15px] leading-relaxed" 
                        rows="1"
                        style="overflow-y: hidden;"
                        x-data="{ resize() { 
                            $el.style.height = 'auto'; 
                            $el.style.height = ($el.scrollHeight) + 'px';
                            $el.style.overflowY = $el.scrollHeight > 100 ? 'auto' : 'hidden';
                        } }"
                        x-init="resize()"
                        @input="resize(); sendTypingEvent();"
                    ></textarea>
                </div>

                <!-- File Button -->
                <label class="cursor-pointer px-3 py-2 text-gray-500 hover:text-indigo-600 transition flex-shrink-0 flex items-center mt-1" title="Adjuntar archivo">
                    <svg class="w-5 h-5 transform -rotate-45" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                    <input type="file" wire:model="attachment" class="hidden">
                </label>
            </div>

            <!-- Send Button -->
            <button type="submit" class="bg-indigo-600 text-white hover:bg-indigo-700 rounded-full transition flex-shrink-0 shadow-md transform active:scale-95 flex items-center justify-center w-11 h-11 self-end mb-0.5" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="attachment" class="flex items-center justify-center pr-0.5">
                    <svg class="w-5 h-5 transform rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                </span>
                <span wire:loading wire:target="attachment">
                    <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
        </form>
    </div>
</div>
