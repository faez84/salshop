# Performance Before/After Template

Date:
Environment:
Tester:
Branch/Commit:
Change Scope:

## 1. Test Goal
- Main objective:
- SLO target (example: p95 < 250ms, error rate < 0.5%):

## 2. Fixed Test Conditions
- Runtime mode: `APP_ENV=prod`, `APP_DEBUG=0`
- Dataset size:
- Deployment shape (pods/replicas):
- Pod resources (request/limit):
- DB and Redis instance types:
- Background workers count:
- Traffic source host:

## 3. Load Profile
- Tool: `k6`
- Script: `app/docs/k6-performance-baseline.js`
- Base URL:
- Stages:
- Preallocated VUs / max VUs:

## 4. Commands Used
Before run command:
```bash
docker run --rm -i -v "${PWD}/app/docs:/scripts" grafana/k6 run /scripts/k6-performance-baseline.js --summary-export=/scripts/before-summary.json
```

After run command:
```bash
docker run --rm -i -v "${PWD}/app/docs:/scripts" grafana/k6 run /scripts/k6-performance-baseline.js --summary-export=/scripts/after-summary.json
```

## 5. Raw Results Per Run
| Run | Version | RPS | p50 (ms) | p95 (ms) | p99 (ms) | Error % | Timeouts | App CPU % | App Mem (MiB) | DB CPU % |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 1 | Before |  |  |  |  |  |  |  |  |  |
| 2 | Before |  |  |  |  |  |  |  |  |  |
| 3 | Before |  |  |  |  |  |  |  |  |  |
| 4 | Before |  |  |  |  |  |  |  |  |  |
| 5 | Before |  |  |  |  |  |  |  |  |  |
| 1 | After |  |  |  |  |  |  |  |  |  |
| 2 | After |  |  |  |  |  |  |  |  |  |
| 3 | After |  |  |  |  |  |  |  |  |  |
| 4 | After |  |  |  |  |  |  |  |  |  |
| 5 | After |  |  |  |  |  |  |  |  |  |

## 6. Median Comparison
| Metric | Before (median) | After (median) | Change |
| --- | --- | --- | --- |
| RPS |  |  | `((after/before)-1)*100` |
| p95 latency |  |  | `(1-(after/before))*100` |
| p99 latency |  |  | `(1-(after/before))*100` |
| Error rate |  |  | `(before-after)` |
| RPS per vCPU |  |  | `((after/before)-1)*100` |

## 7. Endpoint-Level Observations
- `/`:
- `/api/products`:
- `/api/categories`:
- `/product/{id}`:
- `/health/live`:

## 8. Conclusion
- Outcome:
- Did we meet SLO:
- Regression risks:
- Next experiment:
