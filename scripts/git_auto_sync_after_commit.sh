#!/usr/bin/env bash
set -euo pipefail

repo_root="$(git rev-parse --show-toplevel)"
cd "$repo_root"

if ! git rev-parse --verify HEAD >/dev/null 2>&1; then
  exit 0
fi

branch="$(git rev-parse --abbrev-ref HEAD)"
if [[ "$branch" != "main" ]]; then
  echo "[auto-sync] Skipping: current branch is '$branch' (only 'main' is synced)."
  exit 0
fi

changed_files="$(git diff-tree --no-commit-id --name-only -r HEAD || true)"

echo "[auto-sync] Pushing main to netbil-live..."
git push netbil-live main

if echo "$changed_files" | rg -q '^app/Modules/Inventory/'; then
  echo "[auto-sync] Syncing Inventory module..."
  inv_sha="$(git subtree split --prefix=app/Modules/Inventory HEAD)"
  git push inventory "${inv_sha}:main"
fi

if echo "$changed_files" | rg -q '^app/Modules/PettyCash/'; then
  echo "[auto-sync] Syncing PettyCash module..."
  petty_sha="$(git subtree split --prefix=app/Modules/PettyCash HEAD)"
  git push pettycash "${petty_sha}:main"
fi

echo "[auto-sync] Done."
