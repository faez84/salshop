# Scenario 1: Read Traffic Spike Incident Drill

Goal: simulate a campaign spike on:
- `/category/{id}/products`
- `/product/{id}`

Symptoms to reproduce:
- Response time jumps from ~120ms toward ~1.8s
- Error rate increases toward ~5%
- Users report slow pages

## 1) Initial configuration (before load test)

Use stable prod-like settings before testing:

1. App runtime
- `APP_ENV=prod`
- `APP_DEBUG=0`
- OPcache enabled (already configured in image)

2. PHP-FPM
- Start from known safe pool values (example):
  - `pm.max_children=5` or `7` (not `1` for baseline)
  - `pm.max_requests=1000`

3. Deployment state
- Pods healthy (`READY 2/2`)
- No crash loops/restart storms

4. Cache and data
- Warm cache before measured run
- Keep same dataset between before/after runs

Quick checks:

```bash
kubectl -n default get pods -l app=symfony-shopapp-new3
kubectl -n default top pods -l app=symfony-shopapp-new3 --containers
kubectl -n default get hpa symfony-shop-deploy3-hpa
```

## 2) Run spike scenario (local k6 container hitting k8s service via port-forward)

Port-forward app:

```bash
kubectl -n default port-forward svc/service-symfony-shopapp-new3 8080:80
```

In another terminal run spike:

```powershell
docker run --rm -i `
  -e BASE_URL=http://host.docker.internal:8080 `
  -e START_RATE=30 `
  -e WARM_TARGET=60 `
  -e SPIKE_TARGET=220 `
  -e STEADY_TARGET=140 `
  -e CATEGORY_IDS=1,2,3,4,5 `
  -e PRODUCT_IDS=1,2,3,4,5,6,7,8,9,10 `
  -v "${PWD}\app\docs:/scripts" `
  grafana/k6 run /scripts/k6-read-spike-scenario.js `
  --summary-export=/scripts/scenario1-read-spike-summary.json
```

## 3) What to monitor in Grafana during run

App behavior:
- `http_req_duration` p95/p99
- `http_req_failed` rate
- Request rate
- Dropped iterations

Pod behavior:
- CPU by pod
- Memory by pod
- Pod restarts/readiness failures
- HPA current vs desired replicas

Data layer:
- MySQL latency / active connections
- Redis CPU/memory/evictions (if cache-heavy)

## 4) Triage guide (if latency spikes)

1. CPU high, memory stable
- Scale app replicas
- Increase FPM workers carefully

2. Memory near limits, restarts rising
- Reduce `pm.max_children`
- Increase pod memory
- Check for large payload/object hydration

3. DB saturation (slow queries/locks)
- Add indexes for hot read paths
- Move expensive joins from critical request path
- Add response/data caching for category/product pages

4. Redis pressure (evictions rising)
- Split cache/session/lock Redis paths
- Increase memory or reduce TTL churn

## 5) Success criteria

- `http_req_failed < 1%`
- p95 back under target (example `< 700-900ms`, define your SLO)
- No sustained pod restarts
- HPA scales up and down cleanly

## 6) Repeatable before/after method

For every config/code change:
1. Warm-up 2-5 minutes
2. Run 10 minutes measured window
3. Repeat 3-5 times
4. Compare median p95/p99/error rate
5. Keep only changes with clear improvement
