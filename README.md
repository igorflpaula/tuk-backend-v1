# Tuk Backend - Assistente de Tarefas no Telegram

Backend Laravel para o Tuk, um assistente de tarefas inteligente que funciona através do Telegram usando IA (OpenAI) para entender comandos em linguagem natural.

## 🚀 Funcionalidades

- **Chat no Telegram**: Interação natural com o usuário via Telegram Bot
- **IA com Function Calling**: Utiliza OpenAI GPT-4o mini para entender intenções e extrair dados
- **Agendamento de Tarefas**: Sistema de lembretes automáticos baseado em frequência e horário
- **Modelo de Comportamento**: Sistema extensível com modelos de comportamento (inicialmente "simple")

## 📋 Requisitos

- Docker e Docker Compose
- PHP 8.2+
- MySQL 8.0+
- Redis (opcional, para cache)

## 🛠️ Instalação

### 1. Clone o repositório

```bash
git clone <repository-url>
cd tuk-backend-v1
```

### 2. Configure as variáveis de ambiente

Copie o arquivo `.env.example` para `.env` e configure:

```bash
cp .env.example .env
```

Edite o `.env` e configure:

```env
APP_NAME=Tuk
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=tuk
DB_USERNAME=tuk
DB_PASSWORD=root

TELEGRAM_BOT_TOKEN=seu_token_do_bot_telegram
OPENAI_API_KEY=sua_chave_openai

REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### 3. Obtenha as credenciais

#### Telegram Bot Token
1. Fale com [@BotFather](https://t.me/botfather) no Telegram
2. Use `/newbot` para criar um novo bot
3. Copie o token fornecido

#### OpenAI API Key
1. Acesse [OpenAI Platform](https://platform.openai.com/)
2. Crie uma conta e obtenha sua API key
3. Adicione créditos à sua conta

### 4. Construa e inicie os containers

```bash
docker-compose up -d --build
```

### 5. Instale as dependências e execute as migrations

```bash
docker-compose exec app composer install
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate
```

### 6. Configure o webhook do Telegram

Substitua `SEU_DOMINIO` pela URL pública do seu servidor:

```bash
docker-compose exec app php artisan tinker
```

No tinker:
```php
app(\App\Services\TelegramService::class)->setWebhook('https://SEU_DOMINIO/webhook/telegram');
```

Ou use curl:
```bash
curl -X POST "https://api.telegram.org/bot<SEU_TOKEN>/setWebhook?url=https://SEU_DOMINIO/webhook/telegram"
```

### 7. Configure o scheduler

O Laravel precisa executar o scheduler. Adicione ao crontab do servidor:

```bash
* * * * * cd /caminho/do/projeto && docker-compose exec -T app php artisan schedule:run >> /dev/null 2>&1
```

Ou se estiver usando Docker, você pode usar um container separado para o scheduler.

## 📱 Como Usar

### Exemplos de Comandos

1. **Criar uma tarefa diária:**
   ```
   Tuk, gostaria de ler 30 minutos de livro por dia
   ```

2. **Definir horário:**
   ```
   Qual horário eu devo te lembrar?
   Todo dia às 21:00
   ```

3. **Criar tarefa completa:**
   ```
   Tuk, me lembre de fazer exercícios todos os dias às 7:00 da manhã por 1 hora
   ```

## 🏗️ Estrutura do Projeto

```
tuk-backend-v1/
├── app/
│   ├── Console/
│   │   └── Commands/
│   │       └── SendTaskReminders.php    # Comando do scheduler
│   ├── Http/
│   │   └── Controllers/
│   │       └── TelegramWebhookController.php
│   ├── Models/
│   │   ├── Task.php
│   │   └── TelegramUser.php
│   └── Services/
│       ├── OpenAIService.php            # Integração com OpenAI
│       ├── TaskService.php               # Lógica de negócio de tarefas
│       └── TelegramService.php           # Integração com Telegram
├── database/
│   └── migrations/
│       ├── create_telegram_users_table.php
│       └── create_tasks_table.php
├── docker/
│   ├── nginx/
│   └── php/
├── docker-compose.yml
└── Dockerfile
```

## 🔧 Comandos Úteis

### Executar migrations
```bash
docker-compose exec app php artisan migrate
```

### Executar o scheduler manualmente
```bash
docker-compose exec app php artisan tuk:send-reminders
```

### Ver logs
```bash
docker-compose logs -f app
```

### Acessar o container
```bash
docker-compose exec app bash
```

## 📊 Banco de Dados

### Tabelas

- **telegram_users**: Armazena informações dos usuários do Telegram
- **tasks**: Armazena as tarefas criadas pelos usuários

## 🤖 Como Funciona

1. **Recepção de Mensagem**: O webhook do Telegram recebe mensagens dos usuários
2. **Processamento com IA**: A mensagem é enviada para a OpenAI com Function Calling
3. **Extração de Dados**: A IA identifica a intenção e extrai dados estruturados (nome, frequência, horário, duração)
4. **Criação de Tarefa**: O sistema cria a tarefa no banco de dados
5. **Agendamento**: O scheduler verifica a cada minuto tarefas que precisam de lembrete
6. **Envio de Lembrete**: Quando chega o horário, o sistema envia uma mensagem no Telegram

## 🔐 Segurança

- O webhook do Telegram deve ser configurado com HTTPS
- Mantenha suas chaves de API seguras no arquivo `.env`
- Não commite o arquivo `.env` no repositório

## 📝 Licença

Este projeto é open-source e está disponível sob a licença MIT.

## 🤝 Contribuindo

Contribuições são bem-vindas! Sinta-se à vontade para abrir issues ou pull requests.
