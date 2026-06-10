<?php

use Illuminate\Support\Facades\Mail;

try {
    Mail::raw('¡Hola Jeison! Este es un correo de prueba automático desde tu plataforma Fuego Vivo. Si estás leyendo esto, significa que el sistema SMTP de Mailjet está funcionando perfectamente y el servidor está listo para enviar alertas a los líderes.', function ($message) {
        $message->to('jeisonmonto22@gmail.com')
                ->subject('Test SMTP - Plataforma Fuego Vivo');
    });

    echo "✅ ¡Correo enviado exitosamente a jeisonmonto22@gmail.com!\n";
} catch (\Exception $e) {
    echo "❌ Error al enviar el correo:\n";
    echo $e->getMessage() . "\n";
}
