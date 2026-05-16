# Kubernetes Secrets Rollout

## 0) Build and push FrankenPHP image

```bash
docker build -f container/frankenphp/Dockerfile -t faezsal/symfony-shop-frankenphp:3.2.0 .
docker push faezsal/symfony-shop-frankenphp:3.2.0
```

## 1) Set secret values in environment variables (PowerShell)

```powershell
$env:APP_SECRET = "replace"
$env:DATABASE_URL = "mysql://user:pass@service-symfony-shop-db:3306/dbname"
$env:DATABASE_REPLICA_URL = "mysql://user:pass@service-symfony-shop-db-read:3306/dbname"
$env:PAYPAL_CLIENT_ID = "replace"
$env:PAYPAL_CLIENT_SECRET = "replace"

$env:MYSQL_ROOT_PASSWORD = "replace"
$env:MYSQL_DATABASE = "replace"
$env:MYSQL_USER = "replace"
$env:MYSQL_PASSWORD = "replace"
$env:MYSQL_REPLICATION_USER = "replace"
$env:MYSQL_REPLICATION_PASSWORD = "replace"

$env:MYSQL_EXPORTER_DATA_SOURCE_NAME = "user:pass@(service-symfony-shop-db:3306)/"
```

## 2) Apply secrets first

```powershell
powershell -ExecutionPolicy Bypass -File scripts/k8s/apply-secrets.ps1 -Namespace default
```

## 3) Apply updated deployments

```bash
kubectl apply -f kubernetes/frankenphp-configmap.yml
kubectl apply -f kubernetes/appdeploy-frankenphp.yml
kubectl apply -f kubernetes/workerdeploy.yml
kubectl apply -f kubernetes/db.yml
```

## 4) Restart and verify rollout

```bash
kubectl rollout restart deploy/symfony-shop-deploy3
kubectl rollout restart deploy/symfony-shop-worker
kubectl rollout restart deploy/symfony-shop-db-primary
kubectl rollout restart deploy/symfony-shop-db-replica

kubectl rollout status deploy/symfony-shop-deploy3
kubectl rollout status deploy/symfony-shop-worker
kubectl rollout status deploy/symfony-shop-db-primary
kubectl rollout status deploy/symfony-shop-db-replica
```

## 5) Verify env injection quickly

```bash
POD=$(kubectl get pod -l app=symfony-shopapp-new3 -o jsonpath='{.items[0].metadata.name}')
kubectl exec -it "$POD" -c symfony-shopapp-new3 -- printenv | egrep "APP_SECRET|DATABASE_URL|PAYPAL_CLIENT_ID|PAYPAL_CLIENT_SECRET"
```
