<<<<<<< HEAD
git# External Python Simulation Engine
=======
# External Python Simulation Engine
>>>>>>> 62b96af (docs: update documentation)

This document outlines a language-agnostic way to run large-scale simulations using Python while keeping a single source of truth for balance in `config/game_balance.php`.

## Why Python?

- Rich ecosystem (NumPy, Pandas, SciPy) for scalable modeling and analytics
- Easy plotting/reporting (Matplotlib, Seaborn, Jupyter)
- Fast iteration for data-heavy Monte Carlo and agent-based sims

## Why Keep PHP?

- Game runs in PHP; balance lives in one place
- Avoids drift between app and sim by exporting config
- PHP-based smoke tests stay co-located with the game

## Hybrid Architecture

1. **Single Source of Truth**: PHP file `config/game_balance.php`
2. **Export for Sims**: `scripts/export_game_balance_json.php` produces `outputs/game_balance.json`
3. **Python Engine**: Reads JSON, runs simulations, writes metrics/artifacts
4. **CI/CD**: Optional scheduled runs + artifacts uploaded

## Minimal Python Repo Skeleton

```
python-sim/
├── pyproject.toml
├── README.md
├── src/
│   ├── loader.py           # loads JSON config
│   ├── archetypes.py       # player behavior definitions
│   ├── engine.py           # turn loop, income, combat, snapshots
│   ├── metrics.py          # gini, power gaps, replenishment
│   └── report.py           # tabular + plots
└── scripts/
    └── run_sim.py          # CLI entry
```

### Example: loader.py
```python
import json
from pathlib import Path

def load_config(path: str | Path) -> dict:
    with open(path, 'r') as f:
        return json.load(f)
```

### Example: run_sim.py
```python
import argparse
from pathlib import Path
from src.loader import load_config
from src.engine import run_simulation
from src.report import generate_report

if __name__ == '__main__':
    ap = argparse.ArgumentParser()
    ap.add_argument('--config', default='../outputs/game_balance.json')
    ap.add_argument('--days', type=int, default=90)
    args = ap.parse_args()

    cfg = load_config(Path(args.config))
    result = run_simulation(days=args.days, config=cfg)
    print(generate_report(result))
```

## Workflow

1. Export PHP config:
```bash
php scripts/export_game_balance_json.php
```

2. Run Python sim:
```bash
python scripts/run_sim.py --config ../outputs/game_balance.json --days 90
```

3. Produce artifacts:
- KPIs (JSON/CSV)
- Plots (PNG/SVG)
- Snapshot tables

## CI/CD (Optional)

- GitHub Actions job that:
  - Checks out both repos
  - Exports config JSON from PHP repo
  - Runs Python sim on a matrix of scenarios
  - Publishes artifacts

## Guardrails

- Do not duplicate balance constants in Python; always read JSON
- Version JSON file and include a hash in reports
- Keep scenario definitions separate from core engine
- Treat DB-backed E2E sims as a separate tier (slower, validate integrations)

## Recommendation

- Use **PHP** for smoke tests and fast sanity checks inside this repo
- Use **Python** in a dedicated repo for heavy simulations, analytics, and dashboards
- Share config via exported JSON to prevent drift
