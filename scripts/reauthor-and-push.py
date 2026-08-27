#!/usr/bin/env python3
"""Rewrite every commit's author/committer, then force-push to another remote.

Requires Git and Python 3.8+. Works on macOS and Windows (cmd, PowerShell, Terminal).

  Mac:     python3 scripts/reauthor-and-push.py --name "Name" --email "you@example.com" --remote https://github.com/user/repo.git
  Windows: py scripts\\reauthor-and-push.py --name "Name" --email "you@example.com" --remote https://github.com/user/repo.git

This rewrites history (new SHAs) and force-pushes. Only run it on a repo you
own, with permission to overwrite the target remote. GPG/SSH commit signatures
are stripped because they no longer match.
"""

from __future__ import annotations

import argparse
import os
import re
import shutil
import subprocess
import sys
import tempfile
from pathlib import Path
from typing import List, Optional, Sequence

IDENTITY_LINE = re.compile(
    rb"^(author|committer|tagger) .+ <[^>]+> (\d+ [+-]\d+)$",
    re.MULTILINE,
)


class GitError(RuntimeError):
    pass


def git_exe() -> str:
    found = shutil.which("git")
    if not found:
        raise GitError(
            "Git was not found on PATH. Install Git and open a new terminal.\n"
            "  Mac:     https://git-scm.com  or  xcode-select --install\n"
            "  Windows: https://git-scm.com/download/win"
        )
    return found


def run_git(
    git: str,
    args: Sequence[str],
    *,
    cwd: Path,
    check: bool = True,
    capture: bool = True,
    input_bytes: Optional[bytes] = None,
) -> subprocess.CompletedProcess:
    result = subprocess.run(
        [git, *args],
        cwd=str(cwd),
        input=input_bytes,
        stdout=subprocess.PIPE if capture else None,
        stderr=subprocess.PIPE if capture else None,
        check=False,
    )
    if check and result.returncode != 0:
        err = (result.stderr or b"").decode("utf-8", "replace").strip()
        raise GitError(f"git {' '.join(args)} failed:\n{err or '(no stderr)'}")
    return result


def require_repo(git: str, repo: Path) -> None:
    result = run_git(git, ["rev-parse", "--is-inside-work-tree"], cwd=repo, check=False)
    if result.returncode != 0 or (result.stdout or b"").strip() != b"true":
        raise GitError(f"Not a Git working tree: {repo}")


def working_tree_dirty(git: str, repo: Path) -> bool:
    result = run_git(git, ["status", "--porcelain"], cwd=repo)
    return bool((result.stdout or b"").strip())


def rewrite_identities(export_data: bytes, name: str, email: str) -> bytes:
    name_b = name.encode("utf-8")
    email_b = email.encode("utf-8")

    def repl(match: re.Match[bytes]) -> bytes:
        return match.group(1) + b" " + name_b + b" <" + email_b + b"> " + match.group(2)

    rewritten, count = IDENTITY_LINE.subn(repl, export_data)
    if count == 0:
        raise GitError(
            "No author/committer/tagger lines were rewritten. "
            "The repo may have no commits."
        )
    print(f"Rewrote {count} author/committer/tagger line(s).")
    return rewritten


def rewrite_history(git: str, repo: Path, name: str, email: str) -> None:
    print("Exporting history (signatures on tags/commits will be stripped)...")
    exported = run_git(
        git,
        ["fast-export", "--all", "--signed-tags=strip"],
        cwd=repo,
    ).stdout
    if not exported:
        raise GitError("git fast-export produced no output. Does this repo have commits?")

    rewritten = rewrite_identities(exported, name, email)

    with tempfile.NamedTemporaryFile(prefix="git-export-", suffix=".txt", delete=False) as handle:
        export_path = Path(handle.name)
        handle.write(rewritten)

    try:
        print("Importing rewritten history...")
        with export_path.open("rb") as handle:
            payload = handle.read()
        run_git(
            git,
            ["fast-import", "--force", "--quiet"],
            cwd=repo,
            input_bytes=payload,
        )
    finally:
        try:
            export_path.unlink()
        except OSError:
            pass

    print("Expiring reflogs and pruning old objects...")
    run_git(git, ["reflog", "expire", "--expire=now", "--all"], cwd=repo)
    run_git(git, ["gc", "--prune=now"], cwd=repo)


def ensure_remote(git: str, repo: Path, remote_name: str, remote_url: str) -> None:
    result = run_git(git, ["remote"], cwd=repo)
    remotes = (result.stdout or b"").decode("utf-8", "replace").split()
    if remote_name in remotes:
        run_git(git, ["remote", "set-url", remote_name, remote_url], cwd=repo)
        print(f"Updated remote '{remote_name}' -> {remote_url}")
    else:
        run_git(git, ["remote", "add", remote_name, remote_url], cwd=repo)
        print(f"Added remote '{remote_name}' -> {remote_url}")


def push_all(git: str, repo: Path, remote_name: str) -> None:
    print(f"Force-pushing all branches to '{remote_name}'...")
    run_git(git, ["push", "--force", remote_name, "--all"], cwd=repo, capture=False)
    print(f"Force-pushing tags to '{remote_name}'...")
    run_git(git, ["push", "--force", remote_name, "--tags"], cwd=repo, capture=False)


def confirm(prompt: str) -> bool:
    try:
        answer = input(prompt).strip()
    except EOFError:
        return False
    return answer == "REWRITE"


def parse_args(argv: List[str]) -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Re-author every Git commit and force-push to another repository.",
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog=(
            "Example:\n"
            "  python3 scripts/reauthor-and-push.py \\\n"
            '    --name "Alex Rivera" \\\n'
            '    --email "alex@example.com" \\\n'
            "    --remote https://github.com/alex/project.git\n"
        ),
    )
    parser.add_argument("--name", required=True, help="New author and committer name")
    parser.add_argument("--email", required=True, help="New author and committer email")
    parser.add_argument(
        "--remote",
        required=True,
        help="Target repo URL (HTTPS or SSH) to force-push into",
    )
    parser.add_argument(
        "--remote-name",
        default="target",
        help="Local remote name to create or update (default: target)",
    )
    parser.add_argument(
        "--repo",
        default=".",
        help="Path to the Git working tree (default: current directory)",
    )
    parser.add_argument(
        "--allow-dirty",
        action="store_true",
        help="Continue even if the working tree has uncommitted changes",
    )
    parser.add_argument(
        "--rewrite-only",
        action="store_true",
        help="Rewrite local history but do not push",
    )
    parser.add_argument(
        "-y",
        "--yes",
        action="store_true",
        help="Skip the interactive confirmation prompt",
    )
    return parser.parse_args(argv)


def main(argv: List[str]) -> int:
    args = parse_args(argv)
    repo = Path(args.repo).expanduser().resolve()

    if "<" in args.name or ">" in args.name or "<" in args.email or ">" in args.email:
        print("Name and email must not contain < or >.", file=sys.stderr)
        return 2

    try:
        git = git_exe()
        require_repo(git, repo)

        if working_tree_dirty(git, repo) and not args.allow_dirty:
            raise GitError(
                "Working tree has uncommitted changes. Commit/stash them, "
                "or pass --allow-dirty."
            )

        print("This will:")
        print(f"  1. Rewrite ALL commits in {repo}")
        print(f"     author/committer -> {args.name} <{args.email}>")
        if args.rewrite_only:
            print("  2. Leave remotes unchanged (no push)")
        else:
            print(f"  2. Force-push every branch and tag to {args.remote}")
            print("     That overwrites history on the target remote.")
        print()

        if not args.yes and not confirm('Type REWRITE to continue (anything else aborts): '):
            print("Aborted.")
            return 1

        os.chdir(repo)
        rewrite_history(git, repo, args.name, args.email)

        if not args.rewrite_only:
            ensure_remote(git, repo, args.remote_name, args.remote)
            push_all(git, repo, args.remote_name)

        print("Done.")
        print("Create a fresh clone of the target repo to verify the new authors.")
        return 0
    except GitError as exc:
        print(str(exc), file=sys.stderr)
        return 1


if __name__ == "__main__":
    sys.exit(main(sys.argv[1:]))
