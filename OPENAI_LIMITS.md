# Limites da OpenAI - Contas Gratuitas

## ⚠️ Limites de Rate Limit

Contas gratuitas da OpenAI têm limites muito mais restritivos:

### Limites Típicos (podem variar)
- **Requisições por minuto**: ~3-5 requisições/minuto
- **Requisições por dia**: Limitado
- **Tokens por minuto**: ~40.000 tokens/minuto (gpt-4o-mini)

### Soluções Implementadas

1. **Retry Automático**: O sistema tenta novamente automaticamente em caso de rate limit
2. **Backoff Exponencial**: Aguarda 2s, 4s, 8s entre tentativas
3. **Mensagens Claras**: Informa ao usuário sobre o limite

### Recomendações

1. **Aguarde entre requisições**: Não faça muitas requisições seguidas
2. **Use com moderação**: Contas gratuitas são para testes
3. **Considere upgrade**: Para produção, considere uma conta paga

### Para Produção

Se você planeja usar em produção:
- Upgrade para conta paga da OpenAI
- Implemente cache de respostas
- Use filas para processar requisições
- Implemente rate limiting no seu backend

### Verificar Limites

Você pode verificar seus limites em:
- https://platform.openai.com/account/limits
- Dashboard da OpenAI
