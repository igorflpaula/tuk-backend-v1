# Setup Local - Tuk Backend

Guia rápido para rodar o projeto localmente sem Docker.

## ✅ Pré-requisitos

- PHP 8.2+
- Composer
- MySQL/MariaDB
- Extensões PHP: pdo_mysql, mbstring, curl, zip

## 🚀 Passos para rodar

### 1. Instalar dependências (se ainda não instalou)

```bash
composer install
```

### 2. Configurar o .env

Certifique-se de que o `.env` está configurado com:

```env
APP_NAME=Tuk
APP_ENV=local
APP_KEY=base64:... (já foi gerado)
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

### 3. Criar o banco de dados

```bash
mysql -u seu_usuario -p -e "CREATE DATABASE IF NOT EXISTS tuk CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 4. Executar migrations

```bash
php artisan migrate
```

### 5. Iniciar o servidor

```bash
php artisan serve
```

O servidor estará rodando em `http://localhost:8000`

### 6. Configurar o webhook do Telegram

Você precisará de uma URL pública para o webhook. Opções:

#### Opção A: Usar ngrok (para desenvolvimento)

```bash
# Instale o ngrok: https://ngrok.com/
ngrok http 8000
```

Depois use a URL do ngrok para configurar o webhook:

```bash
curl -X POST "https://api.telegram.org/bot<SEU_TOKEN>/setWebhook?url=https://sua-url-ngrok.ngrok.io/webhook/telegram"
```

#### Opção B: Usar um servidor com domínio público

Configure o webhook apontando para sua URL pública:

```bash
curl -X POST "https://api.telegram.org/bot<SEU_TOKEN>/setWebhook?url=https://seu-dominio.com/webhook/telegram"
```

### 7. Configurar o scheduler (opcional)

Para que os lembretes sejam enviados automaticamente, você precisa executar o scheduler do Laravel. Adicione ao crontab:

```bash
crontab -e
```

Adicione esta linha:

```
* * * * * cd /caminho/para/tuk-backend-v1 && php artisan schedule:run >> /dev/null 2>&1
```

Ou execute manualmente para testar:

```bash
php artisan tuk:send-reminders
```

## 🧪 Testar

### Testar o webhook localmente

Você pode testar o webhook usando curl:

```bash
curl -X POST http://localhost:8000/webhook/telegram \
  -H "Content-Type: application/json" \
  -d '{
    "message": {
      "message_id": 1,
      "from": {
        "id": 123456789,
        "first_name": "Teste",
        "username": "teste"
      },
      "chat": {
        "id": 123456789
      },
      "text": "Tuk, gostaria de ler 30 minutos de livro por dia"
    }
  }'
```

### Verificar logs

```bash
tail -f storage/logs/laravel.log
```

## 📝 Comandos úteis

```bash
# Ver rotas
php artisan route:list

# Limpar cache
php artisan cache:clear
php artisan config:clear

# Ver status das migrations
php artisan migrate:status

# Executar o scheduler manualmente
php artisan schedule:run

# Executar apenas o comando de lembretes
php artisan tuk:send-reminders
```

## 🔧 Troubleshooting

### Erro de conexão com banco
- Verifique se o MySQL está rodando: `sudo systemctl status mysql`
- Verifique as credenciais no `.env`
- Teste a conexão: `mysql -u seu_usuario -p tuk`

### Erro ao processar mensagens do Telegram
- Verifique se `TELEGRAM_BOT_TOKEN` está correto no `.env`
- Verifique os logs: `tail -f storage/logs/laravel.log`

### Erro ao chamar OpenAI
- Verifique se `OPENAI_API_KEY` está correto no `.env`
- Verifique se há créditos na conta OpenAI
- Verifique os logs para mais detalhes
