#!/usr/bin/env bash
set -euo pipefail

# --- Config you set once (or pass via env) ---
: "${GITHUB_SSH_REPO:?Set GITHUB_SSH_REPO, e.g. [email protected]:owner/repo.git}"

KEY_PATH="${HOME}/.ssh/id_ed25519_statamic"
SSH_CONFIG="${HOME}/.ssh/config"
KNOWN_HOSTS="${HOME}/.ssh/known_hosts"

mkdir -p "${HOME}/.ssh"
chmod 700 "${HOME}/.ssh"

# Generate once if missing (stateful host)
if [ ! -f "${KEY_PATH}" ]; then
ssh-keygen -t ed25519 -C "statamic-bot" -f "${KEY_PATH}" -N ""
echo "Public key for GitHub Deploy Key (add with write access):"
cat "${KEY_PATH}.pub"
fi
chmod 600 "${KEY_PATH}"

# 2) Known hosts (avoid interactive prompts) — idempotent
if ! ssh-keygen -F github.com >/dev/null 2>&1; then
  # Collect multiple key types; ssh-keygen -F tests presence later runs
  { ssh-keyscan -t rsa,ecdsa,ed25519 github.com 2>/dev/null || true; } >> "${KNOWN_HOSTS}"
  chmod 600 "${KNOWN_HOSTS}"
fi

# 3) SSH config: force our key for github.com — idempotent append
if ! grep -q "IdentityFile ${KEY_PATH}" "${SSH_CONFIG}" 2>/dev/null; then
  {
    echo "Host github.com"
    echo "  HostName github.com"
    echo "  User git"
    echo "  IdentityFile ${KEY_PATH}"
    echo "  IdentitiesOnly yes"
  } >> "${SSH_CONFIG}"
  chmod 600 "${SSH_CONFIG}"
fi

# 5) Ensure remote uses SSH (convert from https if needed) — idempotent
if git remote get-url origin >/dev/null 2>&1; then
  CURRENT="$(git remote get-url origin)"
  if [ "${CURRENT}" != "${GITHUB_SSH_REPO}" ]; then
    git remote set-url origin "${GITHUB_SSH_REPO}"
  fi
else
  git remote add origin "${GITHUB_SSH_REPO}"
fi

# 6) Ensure upstream tracking if missing (no-op if already set)
default_branch="$(git symbolic-ref --quiet --short refs/remotes/origin/HEAD 2>/dev/null || true)"
default_branch="${default_branch#origin/}"
: "${default_branch:=main}"
if ! git rev-parse --abbrev-ref --symbolic-full-name @{u} >/dev/null 2>&1; then
  # Only set if local branch exists
  if git show-ref --verify --quiet "refs/heads/${default_branch}"; then
    git branch --set-upstream-to "origin/${default_branch}" "${default_branch}" || true
  fi
fi

# 7) Quick connectivity check (non-fatal)
ssh -o BatchMode=yes -o StrictHostKeyChecking=yes -T [email protected] >/dev/null 2>&1 || true
git ls-remote origin >/dev/null 2>&1 || true

# 8) Statamic .env toggles (idempotent line-replace/add)
if [ -f ".env" ]; then
  awk -v k="STATAMIC_GIT_ENABLED"   -v v="true" 'BEGIN{FS=OFS="="} $1==k{$2=v;f=1}1; END{if(!f)print k"="v}' .env > .env.tmp && mv .env.tmp .env
  awk -v k="STATAMIC_GIT_PUSH"      -v v="true" 'BEGIN{FS=OFS="="} $1==k{$2=v;f=1}1; END{if(!f)print k"="v}' .env > .env.tmp && mv .env.tmp .env
  awk -v k="STATAMIC_GIT_USER_NAME" -v v="${STATAMIC_GIT_USER_NAME}" 'BEGIN{FS=OFS="="} $1==k{$2=v;f=1}1; END{if(!f)print k"="v}' .env > .env.tmp && mv .env.tmp .env
  awk -v k="STATAMIC_GIT_USER_EMAIL"-v v="${STATAMIC_GIT_USER_EMAIL}" 'BEGIN{FS=OFS="="} $1==k{$2=v;f=1}1; END{if(!f)print k"="v}' .env > .env.tmp && mv .env.tmp .env
fi

echo "Statamic Git SSH setup: OK (idempotent)."
