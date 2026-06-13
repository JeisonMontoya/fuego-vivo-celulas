# Changelog

Todas las notas sobre los cambios notables en este proyecto se documentarán en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/),
y este proyecto se adhiere a [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2026-06-13
### Añadido
- **Estado Global del Sistema:** Nuevo indicador visual (rojo, naranja, verde) en la barra de navegación para todos los usuarios.
- **Panel de Control de Estado:** Interfaz en el panel de Administrador para modificar el estado global y mensaje utilizando caché persistente para alto rendimiento.
- **Acceso Directo al Directorio:** Nuevo botón "Directorio" en el menú principal y móvil para navegación rápida a la lista de células.
- **Despliegue Continuo (CI/CD):** Automatización completa del proceso de despliegue usando GitHub Actions, sincronización segura, y compilación automática de assets (Vite) antes de la subida.

### Corregido
- Solucionado error donde `dashboard.blade.php` arrojaba error "Attempt to read property 'meeting_time' on null" cuando el usuario administrador no tenía una célula asignada.
- Arreglado problema de persistencia en la compilación de estilos de TailwindCSS debido a configuraciones de `.gitignore`.
- Resuelta la vulnerabilidad de conflictos de Git en el servidor de producción usando `x-access-token` y `git reset --hard`.
