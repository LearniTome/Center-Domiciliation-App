import json
import time
from contextlib import contextmanager
from pathlib import Path
from typing import Any, Dict, Optional


class StartupProfiler:
    """Simple in-process startup profiler with span events and JSON export."""

    def __init__(self, enabled: bool = True):
        self.enabled = bool(enabled)
        self._t0 = time.perf_counter()
        self._events: list[Dict[str, Any]] = []

    def _now_ms(self) -> float:
        return (time.perf_counter() - self._t0) * 1000.0

    def mark(self, name: str, **meta: Any) -> None:
        if not self.enabled:
            return
        self._events.append({
            "type": "mark",
            "name": str(name),
            "at_ms": round(self._now_ms(), 3),
            "meta": meta or {},
        })

    def start_span(self, name: str, **meta: Any) -> Dict[str, Any]:
        if not self.enabled:
            return {}
        return {
            "name": str(name),
            "start_ms": self._now_ms(),
            "meta": meta or {},
        }

    def end_span(self, token: Optional[Dict[str, Any]], **meta: Any) -> None:
        if not self.enabled or not token:
            return
        end_ms = self._now_ms()
        start_ms = float(token.get("start_ms", end_ms))
        merged_meta = dict(token.get("meta", {}) or {})
        merged_meta.update(meta or {})
        self._events.append({
            "type": "span",
            "name": str(token.get("name", "unknown")),
            "start_ms": round(start_ms, 3),
            "end_ms": round(end_ms, 3),
            "duration_ms": round(max(0.0, end_ms - start_ms), 3),
            "meta": merged_meta,
        })

    @contextmanager
    def span(self, name: str, **meta: Any):
        token = self.start_span(name, **meta)
        try:
            yield
        finally:
            self.end_span(token)

    def events(self) -> list[Dict[str, Any]]:
        return list(self._events)

    def summary(self, top_n: int = 15) -> str:
        spans = [e for e in self._events if e.get("type") == "span"]
        spans_sorted = sorted(spans, key=lambda e: float(e.get("duration_ms", 0.0)), reverse=True)
        lines = ["Startup profile (top spans by duration):"]
        if not spans_sorted:
            lines.append("- no spans recorded")
            return "\n".join(lines)
        for idx, event in enumerate(spans_sorted[: max(1, int(top_n))], start=1):
            lines.append(
                f"{idx:02d}. {event.get('name')} -> {float(event.get('duration_ms', 0.0)):.2f} ms"
            )
        return "\n".join(lines)

    def dump_json(self, path: Path) -> Path:
        out = Path(path)
        out.parent.mkdir(parents=True, exist_ok=True)
        payload = {
            "generated_at_ms": round(self._now_ms(), 3),
            "events": self._events,
        }
        out.write_text(json.dumps(payload, ensure_ascii=False, indent=2), encoding="utf-8")
        return out
