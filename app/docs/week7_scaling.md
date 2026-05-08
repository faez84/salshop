# Week 7 - Scaling Hardening

## Implemented

- Added checkout traffic throttling with Symfony RateLimiter:
  - `checkout_finalize`: 10 req/min per session/ip
  - `checkout_callback`: 30 req/min per session/ip
- Applied limiter checks in `PaymentController` for:
  - `/basket/payment`
  - `/basket/payment/paypal/success`
  - `/basket/payment/paypal/cancel`
- Updated Kubernetes deployments:
  - `appdeploy.yml`: explicit env for Redis/session/lock/messenger and health HTTP probes
  - `workerdeploy.yml`: consumes `checkout_async async`, env aligned with runtime config

## Why This Improves Scalability

- Rate limiting protects checkout from bursts and abusive traffic, preserving headroom for valid users.
- Dedicated health probes (`/health/live`, `/health/ready`) reduce bad-pod traffic during rollouts.
- Worker queue alignment ensures checkout-specific async workload scales predictably with worker replicas.

## Next High-Impact Step

- Move messenger transport from Doctrine queues to broker transport (e.g. RabbitMQ) for higher throughput and better queue isolation once transport package/runtime is finalized.
