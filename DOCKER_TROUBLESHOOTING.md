# 🔧 Troubleshooting Docker - Erro "read-only file system"

## Problema

Erro ao construir a imagem Docker:
```
ERROR: read-only file system
```

## Soluções (tente nesta ordem)

### 1. Reiniciar Docker Desktop

1. Feche completamente o Docker Desktop
2. Abra o Gerenciador de Tarefas (Ctrl+Shift+Esc)
3. Finalize todos os processos relacionados ao Docker
4. Reinicie o Docker Desktop
5. Aguarde até que o Docker esteja totalmente iniciado (ícone verde)

### 2. Limpar Cache do Docker

No PowerShell, execute:

```powershell
# Parar todos os containers
docker stop $(docker ps -aq)

# Limpar sistema (pode demorar)
docker system prune -a --volumes

# Se o comando acima falhar, tente:
docker builder prune -a -f
```

### 3. Verificar Espaço em Disco

O Docker Desktop precisa de espaço livre. Verifique se há pelo menos 10GB livres.

### 4. Resetar Docker Desktop

1. Abra Docker Desktop
2. Vá em Settings (Configurações)
3. Troubleshoot (Solução de Problemas)
4. Clique em "Clean / Purge data"
5. Reinicie o Docker Desktop

### 5. Reinstalar Docker Desktop (último recurso)

1. Desinstale o Docker Desktop
2. Baixe a versão mais recente
3. Reinstale
4. Reinicie o computador

## Solução Alternativa: Usar WSL2

Se o problema persistir, considere usar WSL2:

1. Instale WSL2 no Windows
2. Configure o Docker Desktop para usar WSL2 backend
3. Execute os comandos dentro do WSL2

## Após Resolver

Depois de resolver o problema, tente novamente:

```bash
docker-compose down -v
docker-compose up -d --build
```

