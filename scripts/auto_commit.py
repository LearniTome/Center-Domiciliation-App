"""
Simple file watcher that stages, commits and pushes changes automatically.

Usage:
  - Install dependencies in your virtualenv: pip install watchdog
  - Run: python scripts/auto_commit.py --message "autosave" --branch chore/auto

Notes:
  - Be careful: this will commit all changes in the repository by default.
  - Do NOT run on repositories with secrets or on production branches unless you know what you are doing.
"""
import argparse
import subprocess
import sys
import time
from pathlib import Path
from threading import Event, Timer

try:
    from watchdog.observers import Observer
    from watchdog.events import FileSystemEventHandler
except Exception:
    print("Please install watchdog: pip install watchdog")
    sys.exit(1)

ROOT = Path(__file__).resolve().parent.parent
DEBOUNCE = 1.0  # seconds

class CommitHandler(FileSystemEventHandler):
    def __init__(self, do_commit, exclusions):
        super().__init__()
        self.do_commit = do_commit
        self.exclusions = [str((ROOT / p).resolve()) for p in exclusions]
        self._timer = None
        self._event = Event()

    def _schedule(self):
        if self._timer:
            self._timer.cancel()
        self._timer = Timer(DEBOUNCE, self._run)
        self._timer.start()

    def _run(self):
        try:
            self.do_commit()
        except Exception as e:
            print('Auto-commit failed:', e)

    def _is_excluded(self, path):
        p = str(Path(path).resolve())
        for ex in self.exclusions:
            if p.startswith(ex):
                return True
        return False

    def on_modified(self, event):
        if event.is_directory:
            return
        if self._is_excluded(event.src_path):
            return
        self._schedule()

    def on_created(self, event):
        if event.is_directory:
            return
        if self._is_excluded(event.src_path):
            return
        self._schedule()


def run_git(commands, cwd=ROOT):
    p = subprocess.run(commands, cwd=str(cwd), capture_output=True, text=True)
    if p.returncode != 0:
        raise RuntimeError(p.stdout + p.stderr)
    return p.stdout


def default_commit(message, branch):
    # stage all
    run_git(['git', 'add', '--all'])
    # commit
    ts = time.strftime('%Y-%m-%d %H:%M:%S')
    commit_msg = f"{message} - {ts}"
    run_git(['git', 'commit', '-m', commit_msg])
    # push
    run_git(['git', 'push', 'origin', branch])


def main(argv=None):
    parser = argparse.ArgumentParser(description='Auto git add/commit/push on file changes')
    parser.add_argument('--message', default='auto-save', help='Commit message prefix')
    parser.add_argument('--branch', default='chore/auto', help='Target branch to push to')
    parser.add_argument('--exclude', action='append', default=['venv', '.git', 'tmp_out'], help='Paths relative to repo root to exclude')
    parser.add_argument('--dry-run', action='store_true', help='Do not run git commands; only print what would be done')
    parser.add_argument('--paths', action='append', default=[], help='Restrict commits to paths (relative to repo root)')
    args = parser.parse_args(argv)

    print('Watching', ROOT)

    def do_it():
        try:
            print('Detected changes — preparing commit:')
            if args.dry_run:
                # Show git status porcelain to indicate changed files
                out = run_git(['git', 'status', '--porcelain'])
                if not out.strip():
                    print('No changes to commit')
                    return
                print(out)
                # Show the commands that would be run
                ts = time.strftime('%Y-%m-%d %H:%M:%S')
                commit_msg = f"{args.message} - {ts}"
                if args.paths:
                    print('Would run: git add -- <paths>')
                    for p in args.paths:
                        print('  ', p)
                else:
                    print('Would run: git add --all')
                print(f"Would run: git commit -m \"{commit_msg}\"")
                print(f"Would run: git push origin {args.branch}")
                return

            print('Staging and committing changes...')
            if args.paths:
                # stage only provided paths
                for p in args.paths:
                    run_git(['git', 'add', '--', str(p)])
            else:
                run_git(['git', 'add', '--all'])

            # commit
            ts = time.strftime('%Y-%m-%d %H:%M:%S')
            commit_msg = f"{args.message} - {ts}"
            run_git(['git', 'commit', '-m', commit_msg])
            # push
            run_git(['git', 'push', 'origin', args.branch])
            print('Pushed to origin/', args.branch)
        except Exception as e:
            print('Commit/push failed:', e)

    event_handler = CommitHandler(do_it, exclusions=args.exclude)
    observer = Observer()
    observer.schedule(event_handler, str(ROOT), recursive=True)
    observer.start()
    print('Started auto-commit. Ctrl-C to stop.')
    try:
        while True:
            time.sleep(1)
    except KeyboardInterrupt:
        observer.stop()
    observer.join()

if __name__ == '__main__':
    main()
