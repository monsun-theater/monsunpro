#!/usr/bin/env bash
set -euo pipefail

KEY_PATH="${HOME}/.ssh/id_ed25519_statamic"
SSH_CONFIG="${HOME}/.ssh/config"
KNOWN_HOSTS="${HOME}/.ssh/known_hosts"
GITHUB_SSH_REPO="git@github.com:${GIT_REPOSITORY}.git"

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

# Init repo and reset worktree if it is missing
if [ ! -d .git ]; then
  git init
  git remote add origin "$GITHUB_SSH_REPO"
  echo "Updated from git"
fi
git fetch origin "${GIT_BRANCH}"
git reset --hard "origin/${GIT_BRANCH}"


echo "Statamic Git SSH setup: OK"

