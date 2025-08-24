#!/usr/bin/env bash
set -euo pipefail

KEY_PATH="${HOME}/.ssh/id_ed25519_statamic"
SSH_CONFIG="${HOME}/.ssh/config"
KNOWN_HOSTS="${HOME}/.ssh/known_hosts"
GITHUB_SSH_REPO="git@github.com:tom300z/monsunpro.git"

mkdir -p "${HOME}/.ssh"
chmod 700 "${HOME}/.ssh"

# Generate once if missing (stateful host)
if [ ! -f "${KEY_PATH}" ]; then
ssh-keygen -t ed25519 -C "statamic-bot" -f "${KEY_PATH}" -N ""
echo "Public key for GitHub Deploy Key (add with write access):"
cat "${KEY_PATH}.pub"
fi
chmod 600 ${HOME}/.ssh/*

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

# Init repo and reset worktree if it is missing
if [ ! -d .git ]; then
  git init
  git remote add origin "git@github.com:${GIT_REPOSITORY}.git"
  git fetch origin "${GIT_BRANCH}"
  git reset --hard "origin/${GIT_BRANCH}"
  echo "Updated from git"
fi

echo "Statamic Git SSH setup: OK"

