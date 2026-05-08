# k6 Benchmark Quickstart

This guide gives you a repeatable before/after benchmark flow for this project.

## 1. What We Measure
- Throughput (`RPS`)
- Latency (`p50`, `p95`, `p99`)
- HTTP error rate
- Application-level errors (from k6 checks)
- App/DB CPU and memory from your platform metrics

Use script:
- `app/docs/k6-performance-baseline.js`
- `app/docs/k6-read-spike-scenario.js` (campaign spike drill for category/product pages)

Use report template:
- `app/docs/performance-before-after-template.md`
- `app/docs/scenario1-read-spike-playbook.md`

## 2. Pre-checks
- Run in production mode: `APP_ENV=prod`, `APP_DEBUG=0`
- Keep same infra/resources for both before and after
- Keep dataset and background load similar
- Warm up caches before timed runs

## 3. Local (Docker Compose) Run
```powershell
docker compose up -d
docker run --rm -i `
  -e BASE_URL=http://host.docker.internal:8000 `
  -e START_RATE=20 `
  -e STAGE1_TARGET=50 `
  -e STAGE2_TARGET=100 `
  -e STAGE3_TARGET=120 `
  -v "${PWD}\app\docs:/scripts" `
  grafana/k6 run /scripts/k6-performance-baseline.js --summary-export=/scripts/before-summary.json
```

Repeat for after-change:
```powershell
docker run --rm -i `
  -e BASE_URL=http://host.docker.internal:8000 `
  -e START_RATE=20 `
  -e STAGE1_TARGET=50 `
  -e STAGE2_TARGET=100 `
  -e STAGE3_TARGET=120 `
  -v "${PWD}\app\docs:/scripts" `
  grafana/k6 run /scripts/k6-performance-baseline.js --summary-export=/scripts/after-summary.json
```

## 4. Kubernetes Run
Kubernetes-native load test (runs `k6` inside the cluster):

```bash
kubectl apply -f kubernetes/k6-loadtest-configmap.yml
kubectl delete job k6-loadtest --ignore-not-found
kubectl apply -f kubernetes/k6-loadtest-job.yml
kubectl logs -f job/k6-loadtest
```

Get only the summary JSON from logs:

```bash
kubectl logs job/k6-loadtest | sed -n '/SUMMARY_JSON_START/,/SUMMARY_JSON_END/p'
```

Notes:

- The job targets `http://service-symfony-shopapp-new3` from inside cluster.
- It remote-writes metrics to Prometheus at:
  `http://monitoring-kube-prometheus-prometheus.monitoring.svc:9090/api/v1/write`
- Dashboard filter `Test ID` uses `K6_TAG_TESTID` (default in manifest: `k8s-baseline`).

If you want a different run tag:

1. Edit `K6_TAG_TESTID` in `kubernetes/k6-loadtest-job.yml`.
2. Re-run `kubectl delete job ...` then `kubectl apply -f kubernetes/k6-loadtest-job.yml`.

## 5. A/B Method You Should Follow
- Do 2-5 minutes warm-up
- Run 10 minutes measured window
- Repeat each version 5 times
- Compare median values, not single runs
- Treat regression if p95 or error rate worsens materially

## 6. Suggested Acceptance Gates
- Error rate `< 1%`
- Global p95 `< 700ms`
- Global p99 `< 1200ms`
- Endpoint p95 thresholds from the script must pass

Tune these gates to your actual SLOs once you collect enough baseline data.
