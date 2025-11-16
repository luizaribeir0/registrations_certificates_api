<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Sobre a API

API de Inscrições e Certificados - Sistema para gerenciamento de inscrições em eventos e geração de certificados.

### Porta do Servidor

Esta API roda na **porta 8001** para evitar conflitos com outras APIs:
- `eventos-api`: porta 8000
- `inscricoes-certificados-api`: porta 8001

### Como iniciar o servidor

```bash
# Usando o script composer (recomendado - sempre usa porta 8001)
composer run serve

# Ou para desenvolvimento completo (com queue, logs e vite)
composer run dev

# Ou manualmente (não recomendado - pode usar porta errada)
php artisan serve --port=8001
```

A API estará disponível em: `http://localhost:8001`

Documentação Swagger: `http://localhost:8001/api/documentation`

### Logs de Acesso

A API registra automaticamente todos os acessos às rotas em arquivos de log separados:

- **Logs da API**: `storage/logs/api-YYYY-MM-DD.log`
  - Registra todas as requisições HTTP
  - Inclui: método, URL, IP, User-Agent, token (mascarado), status code, tempo de resposta
  - Logs são rotacionados diariamente
  - Mantidos por 30 dias (configurável via `LOG_DAILY_DAYS`)

**Informações registradas:**
- Método HTTP (GET, POST, etc.)
- URL completa
- IP do cliente
- User-Agent
- Token de autorização (mascarado para segurança)
- Dados do body (quando aplicável)
- Status code da resposta
- Tempo de resposta em milissegundos
- Timestamp

**Níveis de log:**
- `INFO`: Requisições bem-sucedidas (2xx, 3xx)
- `WARNING`: Erros do cliente (4xx)
- `ERROR`: Erros do servidor (5xx)

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
