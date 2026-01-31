-- ============================================
-- CONSULTAS SIMPLES - VISUALIZAÇÃO DE DADOS
-- ============================================

-- ============================================
-- USUÁRIOS WEB
-- ============================================
-- Ver todos os usuários
SELECT * FROM users;

-- Ver usuários com contagem de tarefas
SELECT 
    u.id,
    u.name,
    u.email,
    COUNT(t.id) as total_tarefas
FROM users u
LEFT JOIN tasks t ON t.user_id = u.id
GROUP BY u.id, u.name, u.email;

-- ============================================
-- USUÁRIOS TELEGRAM
-- ============================================
-- Ver todos os usuários do Telegram
SELECT * FROM telegram_users;

-- Ver usuários do Telegram com contagem de tarefas
SELECT 
    tu.id,
    tu.telegram_id,
    tu.first_name,
    tu.username,
    COUNT(t.id) as total_tarefas
FROM telegram_users tu
LEFT JOIN tasks t ON t.telegram_user_id = tu.id
GROUP BY tu.id, tu.telegram_id, tu.first_name, tu.username;

-- ============================================
-- TAREFAS
-- ============================================
-- Ver todas as tarefas
SELECT * FROM tasks;

-- Ver tarefas com informações do usuário (web)
SELECT 
    t.id,
    t.name,
    t.frequency,
    t.reminder_time,
    t.duration,
    t.is_active,
    t.next_reminder_at,
    u.name as usuario_nome,
    u.email as usuario_email
FROM tasks t
LEFT JOIN users u ON t.user_id = u.id
WHERE t.user_id IS NOT NULL;

-- Ver tarefas com informações do usuário (Telegram)
SELECT 
    t.id,
    t.name,
    t.frequency,
    t.reminder_time,
    t.duration,
    t.is_active,
    t.next_reminder_at,
    tu.telegram_id,
    tu.first_name as usuario_nome,
    tu.username
FROM tasks t
LEFT JOIN telegram_users tu ON t.telegram_user_id = tu.id
WHERE t.telegram_user_id IS NOT NULL;

-- Ver todas as tarefas (web + Telegram) com tipo de usuário
SELECT 
    t.id,
    t.name,
    t.frequency,
    t.reminder_time,
    t.duration,
    t.is_active,
    t.next_reminder_at,
    CASE 
        WHEN t.user_id IS NOT NULL THEN 'Web'
        WHEN t.telegram_user_id IS NOT NULL THEN 'Telegram'
        ELSE 'Sem usuário'
    END as tipo_usuario,
    COALESCE(u.name, tu.first_name, 'N/A') as usuario_nome
FROM tasks t
LEFT JOIN users u ON t.user_id = u.id
LEFT JOIN telegram_users tu ON t.telegram_user_id = tu.id;

-- Tarefas ativas
SELECT * FROM tasks WHERE is_active = 1;

-- Tarefas que precisam de lembrete (próximas 24h)
SELECT 
    t.id,
    t.name,
    t.reminder_time,
    t.next_reminder_at,
    TIMESTAMPDIFF(MINUTE, NOW(), t.next_reminder_at) as minutos_ate_lembrete
FROM tasks t
WHERE t.is_active = 1
  AND t.next_reminder_at IS NOT NULL
  AND t.next_reminder_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 24 HOUR)
ORDER BY t.next_reminder_at;

-- Contagem de tarefas por frequência
SELECT 
    frequency,
    COUNT(*) as total
FROM tasks
GROUP BY frequency;

-- Contagem de tarefas por status
SELECT 
    is_active,
    COUNT(*) as total
FROM tasks
GROUP BY is_active;

-- ============================================
-- TOKENS DA API (SANCTUM)
-- ============================================
-- Ver todos os tokens
SELECT * FROM personal_access_tokens;

-- Ver tokens ativos (não expirados)
SELECT 
    id,
    tokenable_type,
    tokenable_id,
    name,
    last_used_at,
    expires_at,
    created_at
FROM personal_access_tokens
WHERE expires_at IS NULL OR expires_at > NOW();

-- ============================================
-- JOBS E FILAS
-- ============================================
-- Ver jobs pendentes
SELECT * FROM jobs WHERE reserved_at IS NULL ORDER BY created_at;

-- Ver jobs em processamento
SELECT * FROM jobs WHERE reserved_at IS NOT NULL;

-- Ver jobs que falharam
SELECT * FROM failed_jobs ORDER BY failed_at DESC LIMIT 10;

-- ============================================
-- ESTATÍSTICAS GERAIS
-- ============================================
-- Resumo geral
SELECT 
    (SELECT COUNT(*) FROM users) as total_usuarios_web,
    (SELECT COUNT(*) FROM telegram_users) as total_usuarios_telegram,
    (SELECT COUNT(*) FROM tasks) as total_tarefas,
    (SELECT COUNT(*) FROM tasks WHERE is_active = 1) as tarefas_ativas,
    (SELECT COUNT(*) FROM tasks WHERE user_id IS NOT NULL) as tarefas_web,
    (SELECT COUNT(*) FROM tasks WHERE telegram_user_id IS NOT NULL) as tarefas_telegram,
    (SELECT COUNT(*) FROM personal_access_tokens) as total_tokens_api;

-- ============================================
-- CONSULTAS ÚTEIS PARA DEBUG
-- ============================================
-- Ver últimas tarefas criadas
SELECT * FROM tasks ORDER BY created_at DESC LIMIT 10;

-- Ver tarefas com lembretes hoje
SELECT 
    t.id,
    t.name,
    t.reminder_time,
    t.next_reminder_at,
    DATE(t.next_reminder_at) as data_lembrete,
    TIME(t.next_reminder_at) as hora_lembrete
FROM tasks t
WHERE t.is_active = 1
  AND DATE(t.next_reminder_at) = CURDATE()
ORDER BY t.next_reminder_at;

-- Ver contexto de conversas (primeiros caracteres)
SELECT 
    id,
    name,
    email,
    LEFT(context, 100) as contexto_preview
FROM users
WHERE context IS NOT NULL;

SELECT 
    id,
    telegram_id,
    first_name,
    LEFT(context, 100) as contexto_preview
FROM telegram_users
WHERE context IS NOT NULL;
