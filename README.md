<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Acceso al panel administrativo

El panel solo permite iniciar sesión con cuentas creadas por personal autorizado. El registro público y la recuperación de contraseña por correo están deshabilitados; los sitios públicos de la pastelería no forman parte de este cambio.

Solo hay una cuenta administrativa del sistema: `admin`, con acceso a los módulos administrativos, incluida Caja y Pedidos. `RolePermissionSeeder` prepara el rol y sus permisos; no crea ni modifica cuentas.

Después de ejecutar las migraciones, configura `ADMIN_EMAIL` y `ADMIN_PASSWORD` en el `.env` privado y ejecuta `php artisan app:provision-admin-accounts`. La contraseña debe tener al menos 12 caracteres, con mayúsculas, minúsculas, números y símbolos. El comando crea la cuenta solo si no existe, nunca imprime la contraseña y no modifica credenciales existentes al repetirse. En producción requiere `--force`. No compartas ni agregues `.env` al repositorio.

El administrador puede cambiar su contraseña desde Configuración y restablecer las contraseñas de los empleados desde Gestión de personal.

Los roles de empleados no se precargan: crea cada rol desde el módulo Roles y asígnale los permisos necesarios. Solo los roles activos aparecen al crear o editar empleados. Para sembrar un empleado de prueba en un entorno `local` o `testing`, define `DEMO_EMPLOYEE_ROLE` con el nombre de un rol ya existente y activo y ejecuta `php artisan db:seed --class=EmployeeSeeder`. Puedes personalizar su correo y contraseña con `DEMO_EMPLOYEE_EMAIL` y `DEMO_EMPLOYEE_PASSWORD`; si omites la contraseña, se genera una aleatoria. Este seeder no se ejecuta en producción ni se invoca automáticamente.

El registro de empleados solicita nombres, primer apellido, segundo apellido opcional, correo único, celular y carnet de identidad único. Correos se guardan en minúsculas y el carnet en mayúsculas sin espacios para evitar duplicados por diferencias de formato.

## Pedidos, calendario y ventas

Los pedidos creados desde el módulo Pedidos o desde el calendario se guardan en la misma tabla y usan las mismas validaciones, permisos y flujo de cancelación. Al seleccionar una fecha no pasada en el calendario se abre el formulario de pedido; sus cambios se reflejan en ambas vistas. El calendario muestra también eventos externos de Google Calendar, pero esos eventos todavía no se importan automáticamente como pedidos. La creación y edición desde la aplicación se sincroniza hacia Google cuando la integración está configurada.

El módulo de Ventas presenta ventas recientes con métricas de ventas completadas y filtros por fechas, producto y responsable. Pedidos conserva disponibles las entregas atrasadas pendientes y permite consultar el historial completado o cancelado.

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
