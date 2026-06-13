<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;
use Livewire\Attributes\On;

new class extends Component
{
    public int $unreadCount = 0;

    #[On('echo-presence:general-chat,MessageSent')]
    public function incrementUnread()
    {
        if (!request()->routeIs('chat.index') && !request()->routeIs('admin.chat')) {
            $this->unreadCount++;
        }
    }

    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" wire:navigate>
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('directory')" :active="request()->routeIs('directory')" wire:navigate>
                        {{ __('Directorio') }}
                    </x-nav-link>
                    @if(auth()->user()->role === 'admin')
                        <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')" wire:navigate>
                            {{ __('Panel Admin') }}
                        </x-nav-link>
                        <x-nav-link :href="route('admin.reports.index')" :active="request()->routeIs('admin.reports.*')" wire:navigate>
                            {{ __('Gestión de Reportes') }}
                        </x-nav-link>
                    @else
                        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" wire:navigate>
                            {{ __('Mi Panel (Líder)') }}
                        </x-nav-link>
                        <x-nav-link :href="route('reports.create')" :active="request()->routeIs('reports.create')" wire:navigate>
                            {{ __('Reportar') }}
                        </x-nav-link>
                        <x-nav-link :href="route('members.index')" :active="request()->routeIs('members.index')" wire:navigate>
                            {{ __('Mi Célula') }}
                        </x-nav-link>
                    @endif
                    <x-nav-link :href="route('chat.index')" :active="request()->routeIs('chat.index')" wire:navigate>
                        <div class="relative flex items-center">
                            {{ __('Chat Global') }}
                            @if($unreadCount > 0)
                                <span class="absolute -top-2 -right-5 bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full shadow-sm animate-pulse">{{ $unreadCount }}</span>
                            @endif
                        </div>
                    </x-nav-link>
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6 gap-4">
                <livewire:system-status />

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150 gap-2">
                            @if(auth()->user()->photo_path)
                                <img class="h-8 w-8 object-cover rounded-full border border-gray-200" src="{{ asset('storage/' . auth()->user()->photo_path) }}" alt="{{ auth()->user()->name }}">
                            @else
                                <div class="h-8 w-8 rounded-full bg-gradient-to-r from-indigo-500 to-purple-500 flex items-center justify-center text-white text-sm font-bold">
                                    {{ substr(auth()->user()->name, 0, 1) }}
                                </div>
                            @endif

                            <div x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile')" wire:navigate>
                            {{ __('Mi Perfil') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <button wire:click="logout" class="w-full text-start">
                            <x-dropdown-link>
                                {{ __('Cerrar Sesión') }}
                            </x-dropdown-link>
                        </button>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('directory')" :active="request()->routeIs('directory')" wire:navigate>
                {{ __('Directorio') }}
            </x-responsive-nav-link>
            @if(auth()->user()->role === 'admin')
                <x-responsive-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')" wire:navigate>
                    {{ __('Panel Admin') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.reports.index')" :active="request()->routeIs('admin.reports.*')" wire:navigate>
                    {{ __('Gestión de Reportes') }}
                </x-responsive-nav-link>
            @else
                <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" wire:navigate>
                    {{ __('Mi Panel (Líder)') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('reports.create')" :active="request()->routeIs('reports.create')" wire:navigate>
                    {{ __('Reportar') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('members.index')" :active="request()->routeIs('members.index')" wire:navigate>
                    {{ __('Mi Célula') }}
                </x-responsive-nav-link>
            @endif
            <x-responsive-nav-link :href="route('chat.index')" :active="request()->routeIs('chat.index')" wire:navigate>
                <div class="relative inline-flex items-center">
                    {{ __('Chat Global') }}
                    @if($unreadCount > 0)
                        <span class="ml-2 bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full shadow-sm animate-pulse">{{ $unreadCount }}</span>
                    @endif
                </div>
            </x-responsive-nav-link>
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4 mb-3">
                <livewire:system-status />
            </div>
            <div class="px-4 flex items-center gap-3 border-t border-gray-100 pt-3">
                @if(auth()->user()->photo_path)
                    <img class="h-10 w-10 object-cover rounded-full border border-gray-200" src="{{ asset('storage/' . auth()->user()->photo_path) }}" alt="{{ auth()->user()->name }}">
                @else
                    <div class="h-10 w-10 rounded-full bg-gradient-to-r from-indigo-500 to-purple-500 flex items-center justify-center text-white text-lg font-bold">
                        {{ substr(auth()->user()->name, 0, 1) }}
                    </div>
                @endif
                <div>
                    <div class="font-medium text-base text-gray-800" x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>
                    <div class="font-medium text-sm text-gray-500">{{ auth()->user()->email }}</div>
                </div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile')" wire:navigate>
                    {{ __('Mi Perfil') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <button wire:click="logout" class="w-full text-start">
                    <x-responsive-nav-link>
                        {{ __('Cerrar Sesión') }}
                    </x-responsive-nav-link>
                </button>
            </div>
        </div>
    </div>
</nav>
