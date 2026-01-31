# Tuk Backend - Assistente de Tarefas

Backend Laravel para o Tuk, um assistente de tarefas inteligente que funciona através de API REST e Telegram usando IA (OpenAI) para entender comandos em linguagem natural.

## 🚀 Início Rápido

### 1. Instalar dependências

```bash
composer install
```

### 2. Configurar .env

Copie o `.env.example` para `.env` e configure:

```env
APP_NAME=Tuk
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tuk
DB_USERNAME=seu_usuario
DB_PASSWORD=sua_senha

TELEGRAM_BOT_TOKEN=seu_token_telegram
OPENAI_API_KEY=sua_chave_openai
```

### 3. Gerar chave da aplicação

```bash
php artisan key:generate
```

### 4. Executar migrations

```bash
php artisan migrate
```

### 5. Iniciar servidor

```bash
php artisan serve
```

A API estará disponível em: `http://localhost:8000/api`

## 📋 Funcionalidades

- **API REST**: Endpoints para autenticação, tarefas e chat com IA
- **Telegram Bot**: Webhook para receber mensagens do Telegram
- **IA com Function Calling**: OpenAI GPT-4o mini para entender intenções
- **Agendamento**: Sistema de lembretes automáticos
- **Multi-usuário**: Suporta usuários web e Telegram

## 📚 Documentação

- **API**: Veja `API_DOCUMENTATION.md` para documentação completa da API
- **Consultas SQL**: Veja `queries_simples.sql` para consultas úteis

## 🔧 Comandos Úteis

```bash
# Executar migrations
php artisan migrate

# Ver rotas
php artisan route:list

# Executar scheduler manualmente
php artisan tuk:send-reminders

# Limpar cache
php artisan config:clear
php artisan cache:clear
```

## 🌐 Endpoints Principais

- `POST /api/register` - Registrar usuário
- `POST /api/login` - Login
- `GET /api/tasks` - Listar tarefas
- `POST /api/tasks` - Criar tarefa
- `POST /api/chat` - Processar mensagem com IA
- `POST /webhook/telegram` - Webhook do Telegram

## 📝 Licença

MIT
