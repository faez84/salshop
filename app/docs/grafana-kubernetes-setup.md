# Grafana + Prometheus on Kubernetes

## 1) Prerequisites

- Kubernetes cluster running (minikube is fine)
- `helm` installed

Optional for minikube:

```bash
minikube addons enable metrics-server
minikube addons enable ingress
```

## 2) Install kube-prometheus-stack

```bash
helm repo add prometheus-community https://prometheus-community.github.io/helm-charts
helm repo update

kubectl create namespace monitoring
helm upgrade --install monitoring prometheus-community/kube-prometheus-stack -n monitoring --wait \
  --set prometheus.prometheusSpec.enableRemoteWriteReceiver=true
```

## 3) Deploy Redis exporter (if not already)

```bash
kubectl apply -f kubernetes/redis-exporter-deploy.yml
kubectl apply -f kubernetes/redis-exporter-service.yml
```

## 4) Wire exporter to Prometheus

```bash
kubectl apply -f kubernetes/redis-exporter-servicemonitor.yml
```

## 5) Open Grafana

Get admin password:

```bash
kubectl -n monitoring get secret monitoring-grafana -o jsonpath='{.data.admin-password}' | base64 --decode; echo
```

Port-forward:

```bash
kubectl -n monitoring port-forward svc/monitoring-grafana 3000:80
```

Login at `http://127.0.0.1:3000` with:

- username: `admin`
- password: command output above

## 6) Verify Redis metrics in Prometheus

Port-forward Prometheus:

```bash
kubectl -n monitoring port-forward svc/monitoring-kube-prometheus-prometheus 9090:9090
```

Open `http://127.0.0.1:9090` and run:

- `redis_up`
- `redis_memory_used_bytes`
- `rate(redis_commands_processed_total[1m])`

If `redis_up` returns `1`, scraping works.

## 7) Add k6 request dashboard

Apply the dashboard ConfigMap:

```bash
kubectl apply -f kubernetes/grafana-k6-request-dashboard-configmap.yml
```

The Grafana sidecar in `kube-prometheus-stack` auto-loads ConfigMaps labeled `grafana_dashboard: "1"`.
Open Grafana and search for:

- `Salshop k6 Kubernetes Request Metrics`

## 8) Run k6 and write metrics to Prometheus

Port-forward app and Prometheus:

```bash
kubectl port-forward svc/service-symfony-shopapp-new3 8080:80
kubectl -n monitoring port-forward svc/monitoring-kube-prometheus-prometheus 9090:9090
```

Run k6 from your host:

```powershell
docker run --rm -i `
  -e BASE_URL=http://host.docker.internal:8080 `
  -e K6_PROMETHEUS_RW_SERVER_URL=http://host.docker.internal:9090/api/v1/write `
  -e K6_PROMETHEUS_RW_TREND_STATS="p(95),p(99),avg" `
  -e K6_TAG_TESTID="k8s-baseline-$(Get-Date -Format yyyyMMdd-HHmm)" `
  -v "${PWD}\app\docs:/scripts" `
  grafana/k6 run -o experimental-prometheus-rw /scripts/k6-performance-baseline.js `
  --summary-export=/scripts/k8s-summary.json
```

The dashboard includes:

- Request rate
- Failed request rate
- `http_req_duration` `p95`, `p99`, `avg`
- Dropped iterations
- Request rate by endpoint
- Endpoint trend durations (`home`, `products_api`, `categories_api`, `product_page`, `health`)
- App pod CPU by container (`php-fpm` and `nginx`)
- App pod memory by container
- Deployment replica timeline
- HPA current/desired/min/max replicas

Alternative: run k6 entirely inside Kubernetes (no host port-forward needed for k6):

```bash
kubectl apply -f kubernetes/k6-loadtest-configmap.yml
kubectl delete job k6-loadtest --ignore-not-found
kubectl apply -f kubernetes/k6-loadtest-job.yml
kubectl logs -f job/k6-loadtest
```

## 9) Verify app infra metrics (CPU/Memory/HPA)

If the new app panels are empty, verify these queries in Prometheus:

- `sum by (container) (rate(container_cpu_usage_seconds_total{namespace=~"default|salshop",container=~"symfony-shopapp-new3|nginx",container!="POD"}[5m]))`
- `sum by (container) (container_memory_working_set_bytes{namespace=~"default|salshop",container=~"symfony-shopapp-new3|nginx",container!="POD"})`
- `kube_horizontalpodautoscaler_status_current_replicas{horizontalpodautoscaler="symfony-shop-deploy3-hpa"}`

## 10) Add Kubernetes pod monitoring dashboard

Apply the dashboard ConfigMap:

```bash
kubectl apply -f kubernetes/grafana-k8s-pod-dashboard-configmap.yml
```

Open Grafana and search for:

- `Salshop Kubernetes Pod Monitoring`

This dashboard includes:

- Running, pending, failed pod counts
- Ready pod ratio
- CPU and memory usage by pod
- Network RX/TX by pod
- Container restarts over 15m

If panels are empty, verify in Prometheus:

- `kube_pod_info`
- `container_cpu_usage_seconds_total`
- `container_memory_working_set_bytes`

## 10b) Add MySQL monitoring dashboard

Apply the dashboard ConfigMap:

```bash
kubectl apply -f kubernetes/grafana-mysql-dashboard-configmap.yml
```

Open Grafana and search for:

- `Salshop MySQL Kubernetes Monitoring`

If panels are empty, verify in Prometheus:

- `mysql_up`
- `mysql_global_status_threads_connected`
- `rate(mysql_global_status_queries[1m])`

## 11) Advanced autoscaling (queue-aware and request-aware)

Install KEDA (one-time):

```bash
helm repo add kedacore https://kedacore.github.io/charts
helm repo update
helm upgrade --install keda kedacore/keda -n keda --create-namespace
```

### Queue-aware worker scaling (recommended first)

This scales `symfony-shop-worker` by Messenger backlog in MySQL table `messenger_messages`.
Use one scaler controller per deployment. Remove the existing worker HPA first:

```bash
kubectl -n default delete hpa symfony-shop-worker-hpa --ignore-not-found
```

```bash
kubectl apply -f kubernetes/keda-worker-triggerauth.yml
kubectl apply -f kubernetes/keda-worker-scaledobject.yml
kubectl -n default get scaledobject
kubectl -n default get hpa | grep keda
```

### Request-aware app scaling (optional, after ingress metrics are available)

Use one scaler controller per deployment. Remove existing app HPA first:

```bash
kubectl -n default delete hpa symfony-shop-deploy3-hpa --ignore-not-found
```

```bash
kubectl apply -f kubernetes/keda-app-prometheus-scaledobject.yml
```

Verify Prometheus query first (must return data):

- `sum(rate(nginx_ingress_controller_requests{namespace="default",ingress="ingress-symfony-shopapp-new3"}[1m]))`

If query is empty, keep CPU/memory HPA for app and use worker queue-aware scaling first.
