# PHP-FPM Saturation Scenario (`pm.max_children`)

This scenario demonstrates a real bottleneck:
- with `pm.max_children=1`, app cannot keep up
- with `pm.max_children=5`, app handles more load

Use the same load profile in both runs so results are comparable.

## 1) Pre-checks

```powershell
kubectl -n default get pods -l app=symfony-shopapp-new3
kubectl -n default get svc service-symfony-shopapp-new3
kubectl -n monitoring get pods
```

## 2) Disable autoscaling noise (optional but recommended)

```powershell
kubectl -n default delete hpa symfony-shop-deploy3-hpa --ignore-not-found
kubectl -n default scale deploy symfony-shop-deploy3 --replicas=2
```

## 3) Baseline run with `pm.max_children=1`

Patch deployment image config (persistent way):

1. Set `pm.max_children = 1` in:
`container/symfony/php-fpm-pool-performance.conf`
2. Build/push image.
3. Update `kubernetes/appdeploy.yml` image tag and apply.

Then verify inside a pod:

```powershell
kubectl -n default exec deploy/symfony-shop-deploy3 -c symfony-shopapp-new3 -- sh -lc "php-fpm -tt 2>&1 | grep -E 'pm.max_children|pm.start_servers|pm.min_spare_servers|pm.max_spare_servers'"
```

Run the bottleneck test job:

```powershell
kubectl -n default delete job k6-loadtest-fpm-bottleneck --ignore-not-found
kubectl -n default apply -f kubernetes/k6-loadtest-job-fpm-bottleneck.yml
kubectl -n default logs -f job/k6-loadtest-fpm-bottleneck
```

Save key numbers:
- `http_req_failed`
- `http_req_duration p(95), p(99), avg`
- `dropped_iterations`
- `http_reqs` (effective RPS)

## 4) Improvement run with `pm.max_children=5`

Repeat the same steps, but set:

```ini
pm.max_children = 5
pm.start_servers = 2
pm.min_spare_servers = 1
pm.max_spare_servers = 3
```

Redeploy, verify with `php-fpm -tt`, then run the same job again:

```powershell
kubectl -n default delete job k6-loadtest-fpm-bottleneck --ignore-not-found
kubectl -n default apply -f kubernetes/k6-loadtest-job-fpm-bottleneck.yml
kubectl -n default logs -f job/k6-loadtest-fpm-bottleneck
```

## 5) What outcome should you see

With `pm.max_children=1`:
- higher latency (`p95/p99`)
- more `dropped_iterations`
- lower effective `http_reqs` rate
- possible spikes in `http_req_failed`

With `pm.max_children=5`:
- lower latency
- fewer dropped iterations
- higher stable throughput

## 6) Extra observability during test

```powershell
kubectl -n default top pods -l app=symfony-shopapp-new3 --containers
kubectl -n default get pods -l app=symfony-shopapp-new3 -w
kubectl -n default logs deploy/symfony-shop-deploy3 -c symfony-shopapp-new3 --tail=200
```

In Grafana, watch:
- Request rate by endpoint
- `http_req_duration` (`avg`, `p95`, `p99`)
- Failed requests
- Dropped iterations
- Pod CPU / memory
