<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }} - Directorio</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased bg-gray-50">
        <div class="min-h-screen">
            <!-- Navbar pública simple -->
            <nav class="bg-white shadow-sm">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex">
                            <div class="shrink-0 flex items-center">
                                <a href="/" wire:navigate class="flex items-center gap-2">
                                    <x-application-logo class="block h-9 w-auto fill-current text-orange-600" />
                                    <span class="font-bold text-xl text-gray-800">Fuego Vivo</span>
                                </a>
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            @auth
                                <a href="{{ route('dashboard') }}" class="text-sm text-gray-700 hover:text-orange-600 font-medium">Dashboard</a>
                            @else
                                <a href="{{ route('login') }}" class="text-sm font-medium text-gray-600 hover:text-orange-600 transition-colors">Iniciar Sesión</a>
                                <a href="{{ route('register') }}" class="text-sm font-medium text-white bg-orange-600 hover:bg-orange-700 px-4 py-2 rounded-lg transition-colors shadow-sm">Registro</a>
                            @endauth
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Contenido Principal -->
            <main class="py-10">
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
