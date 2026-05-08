# Redis Monitoring (Kubernetes)

## Deploy exporter

```bash
kubectl apply -f kubernetes/redis-exporter-deploy.yml
kubectl apply -f kubernetes/redis-exporter-service.yml
```

## Verify exporter is healthy

```bash
kubectl get pods -l app=redis-exporter
kubectl get svc redis-exporter
```

## Inspect metrics quickly (without Prometheus)

```bash
kubectl port-forward svc/redis-exporter 9121:9121
curl -s http://127.0.0.1:9121/metrics | head -n 40
```

## Useful metrics to watch

- `redis_up`
- `redis_connected_clients`
- `redis_memory_used_bytes`
- `redis_memory_max_bytes`
- `redis_keyspace_hits_total`
- `redis_keyspace_misses_total`
- `redis_evicted_keys_total`
- `redis_expired_keys_total`
- `redis_commands_processed_total`

## Inspect cached keys/values directly

```bash
REDIS_POD=$(kubectl get pod -l app=redis -o jsonpath='{.items[0].metadata.name}')
kubectl exec -it "$REDIS_POD" -- redis-cli --scan | head -n 100
kubectl exec -it "$REDIS_POD" -- redis-cli INFO keyspace
kubectl exec -it "$REDIS_POD" -- redis-cli INFO stats
```
