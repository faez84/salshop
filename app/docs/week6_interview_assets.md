# Week 6 - Senior Interview Assets

## 60-Second Architecture Pitch

"Checkout is split into synchronous request handling and asynchronous order finalization.  
We protect critical flows with idempotency keys, distributed locks, and callback verification.  
For observability, every request carries a correlation id, checkout logs go to a dedicated channel, and we expose live/ready health endpoints for orchestration."

## Deep-Dive Talking Points

1. Reliability in Checkout
- Idempotency key prevents duplicate order creation.
- Callback lock prevents concurrent PayPal callback races.
- Async + sync fallback in message dispatch reduces order-loss risk.

2. Observability Strategy
- Correlation id in response header and logs (`request_id`).
- Structured logs with route, method, and path context.
- Dedicated `checkout` channel for focused production triage.

3. Scalability Strategy
- Session state externalized to Redis (`SESSION_HANDLER_DSN`).
- Distributed locking externalized to Redis (`LOCK_DSN`).
- Background workers consume checkout finalization from dedicated transport.

## STAR Story (Behavioral)

Situation: Checkout failures were hard to debug because logs lacked correlation and were mixed with generic app logs.  
Task: Improve production triage speed without rewriting the checkout stack.  
Action: Added request correlation ids, contextual log processors, dedicated checkout channel, and readiness checks tied to DB/lock/transport dependencies.  
Result: Faster incident debugging, clearer ownership of checkout failures, and better rollout safety through health probes.

## Common Interview Q&A

Q: Why Redis for sessions and locks?  
A: It is shared, fast, and suitable for stateless horizontal scaling; local file sessions/locks break across multiple instances.

Q: What is the tradeoff of DB-backed messenger vs RabbitMQ/Kafka?  
A: DB transport is simple to operate and good early-stage; broker-based transports scale better for throughput and isolation.

Q: How do you prevent double-charge scenarios?  
A: Idempotency keys, provider request ids, callback signature verification, callback locking, and state-machine guarded transitions.

Q: What would you monitor first in checkout?  
A: Conversion funnel, payment failure ratio by method, callback mismatch rate, queue lag, and p95/p99 latency for finalize/callback flows.
