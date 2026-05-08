# Scale Playground (Kubernetes)

This runbook helps you "play around" with scaling safely.

## 1) Check prerequisites

```bash
kubectl get deploy symfony-shop-deploy3
kubectl get deploy symfony-shop-worker
kubectl get hpa
kubectl top nodes
```

If `kubectl top` fails, install metrics server first:

```bash
minikube addons enable metrics-server
```

## 2) Manual scaling test

Scale app up/down manually:

```bash
kubectl scale deploy/symfony-shop-deploy3 --replicas=4
kubectl get pods -l app=symfony-shopapp-new3 -w

kubectl scale deploy/symfony-shop-deploy3 --replicas=2
```

Scale worker up/down manually:

```bash
kubectl scale deploy/symfony-shop-worker --replicas=4
kubectl get pods -l app=symfony-shop-worker -w

kubectl scale deploy/symfony-shop-worker --replicas=2
```

## 3) HPA scaling test (app)

Apply HPA and start load generator:

```bash
kubectl apply -f kubernetes/app-hpa.yml
kubectl apply -f kubernetes/app-loadgen.yml
```

Watch autoscaling:

```bash
kubectl get hpa symfony-shop-deploy3-hpa -w
kubectl top pods -l app=symfony-shopapp-new3
```

Inspect scaling decisions:

```bash
kubectl describe hpa symfony-shop-deploy3-hpa
```

## 4) Stop test load and return to baseline

```bash
kubectl delete -f kubernetes/app-loadgen.yml
kubectl scale deploy/symfony-shop-deploy3 --replicas=2
```

## 5) Optional worker HPA watch

```bash
kubectl apply -f kubernetes/worker-hpa.yml
kubectl get hpa symfony-shop-worker-hpa -w
kubectl top pods -l app=symfony-shop-worker
```
