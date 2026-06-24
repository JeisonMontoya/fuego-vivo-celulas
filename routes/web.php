<?php

// Archivo principal de rutas de la aplicación

use Livewire\Volt\Volt;

Volt::route('/', 'pages.directory')->name('directory');

Route::get('dashboard', function () {
    if (auth()->user()->role === 'admin' || auth()->user()->role === 'supervisor') {
        return redirect()->route('admin.dashboard');
    }
    return view('dashboard');
})->middleware(['auth', 'active'])->name('dashboard');

Route::post('notifications/read', function () {
    auth()->user()->unreadNotifications->markAsRead();

    return back();
})->middleware(['auth'])->name('notifications.read');

Volt::route('admin/dashboard', 'admin.dashboard')
    ->middleware(['auth', 'admin'])
    ->name('admin.dashboard');

Volt::route('admin/reportes', 'admin.reports.index')
    ->middleware(['auth', 'admin'])
    ->name('admin.reports.index');

Volt::route('admin/reportes/{report}', 'admin.reports.show')
    ->middleware(['auth', 'admin'])
    ->name('admin.reports.show');

Volt::route('reportes/nuevo', 'reports.create')
    ->middleware(['auth', 'active'])
    ->name('reports.create');

Volt::route('miembros', 'members.index')
    ->middleware(['auth', 'active'])
    ->name('members.index');

Volt::route('chat', 'chat.index')
    ->middleware(['auth'])
    ->name('chat.index');

Volt::route('biblioteca', 'biblioteca')
    ->middleware(['auth'])
    ->name('biblioteca');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Volt::route('activation/pending', 'activation.pending')
    ->middleware(['auth'])
    ->name('activation.pending');

Volt::route('activation/inactive', 'activation.inactive')
    ->middleware(['auth'])
    ->name('activation.inactive');

require __DIR__.'/auth.php';
