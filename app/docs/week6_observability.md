# Week 6 - Observability Baseline

## What We Added

- Request correlation id propagation (`X-Request-Id`) via `RequestContextSubscriber`.
- Structured request context enrichment for logs via `RequestContextProcessor`.
- Dedicated `checkout` Monolog channel and handlers for `dev`, `test`, and `prod`.
- Health endpoints:
  - `GET /health/live` for liveness
  - `GET /health/ready` for readiness checks (database, lock store, checkout transport config)

## Why This Matters

- Correlation id lets us trace one user request across controller, service, and async logs.
- Checkout channel isolates business-critical payment/order logs from noisy application logs.
- Readiness endpoint helps orchestration (Kubernetes/Docker) avoid routing traffic to unhealthy pods.

## Operational Usage

- Include `X-Request-Id` when calling APIs from gateways or clients.
- In production, `checkout` logs are emitted as JSON to `stderr` for centralized log ingestion.
- Wire probes:
  - Liveness probe -> `/health/live`
  - Readiness probe -> `/health/ready`

## Follow-Up Improvements

- Add queue depth and worker lag metrics.
- Add OpenTelemetry tracing for HTTP + Messenger.
- Add alerting on checkout failure ratio and PayPal callback mismatch spikes.
