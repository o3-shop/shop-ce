# Multi-Worktree Docker Design

**Date:** 2026-05-20  
**Status:** Approved

## Goal

Allow multiple Claude agents to work simultaneously in separate git worktrees, each with its own running shop instance, while sharing a single MariaDB container. No port conflicts, no manual configuration.

## Architecture

One shared Docker bridge network (`o3shop-shared`) lives on the host. The main repo owns and manages MariaDB; it joins `o3shop-shared` with alias `db` so any container on that network can reach it as hostname `db:3306`. Each worktree runs its own shop, mailpit, and adminer — no db container.

```
Host
├── o3shop-shared  (external Docker network, created once)
│   ├── main-repo: db container  ← alias "db" on shared network
│   ├── main-repo: shop
│   ├── worktree-A: shop         ← reaches MariaDB as db:3306
│   └── worktree-B: shop
│
├── main-repo default network (shop ↔ db ↔ mailpit ↔ adminer)
├── worktree-A default network (shop ↔ mailpit ↔ adminer)
└── worktree-B default network (shop ↔ mailpit ↔ adminer)
```

Main repo remains on default ports (8080 / 8081 / 8025 / 1025 / 3306). Worktrees get deterministic port blocks in 9000–9890.

## Port Assignment

Port blocks are derived from `cksum` of the worktree directory basename (e.g. `124-fix-foo`):

```bash
HASH=$(echo -n "$(basename "$MY_DIR")" | cksum | cut -d' ' -f1)
BLOCK=$(( HASH % 90 ))
O3SHOP_PORT_HTTP=$(( 9000 + BLOCK * 10 ))
O3SHOP_PORT_ADMINER=$(( O3SHOP_PORT_HTTP + 1 ))
O3SHOP_PORT_MAILPIT=$(( O3SHOP_PORT_HTTP + 2 ))
O3SHOP_PORT_SMTP=$(( O3SHOP_PORT_HTTP + 3 ))
```

- Range: 9000–9890, step 10 → supports 90 simultaneous worktrees
- Deterministic: same worktree name always maps to the same ports — no coordination needed between agents
- Collision probability: 1-in-90; manifests as a port-already-in-use error on `up` (obvious, not silent)

## Database Naming

- Main repo: `o3shop` (unchanged, backward-compatible)
- Worktrees: `o3shop_<http_port>` e.g. `o3shop_9040`

The worktree's DB is created automatically by `docker.sh` before `up`, using the shared MariaDB's root credentials:

```sql
CREATE DATABASE IF NOT EXISTS `o3shop_9040`;
GRANT ALL ON `o3shop_9040`.* TO 'o3shop'@'%';
```

## Changes: `docker/docker-compose.yml`

1. **db service** — add `profiles: [db]`; join `o3shop-shared` with alias `db`:
   ```yaml
   db:
     profiles: [db]
     networks:
       default:
       o3shop-shared:
         aliases:
           - db
   ```

2. **shop, mailpit, adminer** — add `o3shop-shared` to their networks list:
   ```yaml
   networks:
     - default
     - o3shop-shared
   ```

3. **Top-level networks** — declare `o3shop-shared` as external:
   ```yaml
   networks:
     default:
     o3shop-shared:
       external: true
       name: o3shop-shared
   ```

## Changes: `docker.sh`

### Worktree detection
```bash
IS_WORKTREE=false
[[ "$MY_DIR" == *".claude/worktrees/"* ]] && IS_WORKTREE=true
```

### Shared network (before every `up`)
```bash
docker network create o3shop-shared 2>/dev/null || true
```

### Port computation (worktrees only, written to `docker/.env`)
```bash
if $IS_WORKTREE; then
  HASH=$(echo -n "$(basename "$MY_DIR")" | cksum | cut -d' ' -f1)
  BLOCK=$(( HASH % 90 ))
  O3SHOP_PORT_HTTP=$(( 9000 + BLOCK * 10 ))
  O3SHOP_PORT_ADMINER=$(( O3SHOP_PORT_HTTP + 1 ))
  O3SHOP_PORT_MAILPIT=$(( O3SHOP_PORT_HTTP + 2 ))
  O3SHOP_PORT_SMTP=$(( O3SHOP_PORT_HTTP + 3 ))
  O3SHOP_CONF_DBNAME="o3shop_${O3SHOP_PORT_HTTP}"
fi
```

### Conditional profile
```bash
COMPOSE_PROFILES=""
$IS_WORKTREE || COMPOSE_PROFILES="--profile db"
docker compose $COMPOSE_PROFILES up -d ...
```

### DB auto-creation (worktrees only, before `up`)
```bash
if $IS_WORKTREE; then
  DB_CONTAINER=$(docker ps -q --filter "name=o3shop-shop-ce-db")
  if [ -z "$DB_CONTAINER" ]; then
    echo "ERROR: Shared MariaDB is not running. Start the main repo first: ./docker.sh start"
    exit 1
  fi
  docker exec "$DB_CONTAINER" mysql -uroot -psupersecret -e \
    "CREATE DATABASE IF NOT EXISTS \`${O3SHOP_CONF_DBNAME}\`;
     GRANT ALL ON \`${O3SHOP_CONF_DBNAME}\`.* TO 'o3shop'@'%';"
fi
```

### Teardown (`stop` in main repo)
When `./docker.sh stop` is called from the main repo:
1. Find all running containers from worktree compose projects (by `working_dir` label)
2. Stop each worktree stack via `docker compose -p <project> down`
3. Stop main repo stack (including db)

```bash
if ! $IS_WORKTREE; then
  WORKTREE_PROJECTS=$(docker ps \
    --format '{{index .Labels "com.docker.compose.project.working_dir"}}|{{index .Labels "com.docker.compose.project"}}' \
    | awk -F'|' '$1 ~ /\.claude\/worktrees\// {print $2}' \
    | sort -u)
  for project in $WORKTREE_PROJECTS; do
    echo "Stopping worktree: $project"
    docker compose -p "$project" down
  done
fi
```

Worktree `stop` only affects that worktree's containers — shared DB is not touched.

## Lifecycle Summary

| Action | Main repo | Worktree |
|--------|-----------|---------|
| `start` | Creates `o3shop-shared`, starts db+shop+mailpit+adminer | Creates `o3shop-shared` (no-op), creates per-worktree DB, starts shop+mailpit+adminer |
| `stop` | Stops all worktree stacks first, then main stack (including db) | Stops only this worktree's containers |
| `rebuild` | Full rebuild including db | Rebuilds only shop image for this worktree |

## Constraints

- Worktrees require the main repo's MariaDB to be running. Attempting to start a worktree without it produces a clear error message.
- The `o3shop-shared` network persists until explicitly removed with `docker network rm o3shop-shared`. It survives `docker compose down` on any individual stack.
- DB names use underscores (MySQL identifier rules), not hyphens.
