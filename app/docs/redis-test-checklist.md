# Redis Test Checklist

## 1) Connectivity

- Pass: `PING` returns `PONG`
- Fail: timeout / connection refused

## 2) Read/Write correctness

- Pass: `SET demo:key hello` then `GET demo:key` returns `hello`
- Fail: nil or wrong value

## 3) TTL behavior

- Pass: `EXPIRE demo:key 15` then `TTL demo:key` returns positive value and decreases
- Fail: TTL remains `-1` when expiry is expected

## 4) Counter atomicity

- Pass: two `INCR demo:counter` increase value by exactly `2`
- Fail: skipped or incorrect increments

## 5) Memory safety

- Pass: `used_memory` grows gradually under load
- Fail: sudden/uncontrolled spike near max memory

## 6) Cache efficiency

- Monitor `keyspace_hits` and `keyspace_misses` from `INFO stats`
- Pass: hit ratio improves after warmup (rule of thumb: > 70-80%)
- Hit ratio formula:
  - `hits / (hits + misses)`

## 7) Evictions

- Pass: `evicted_keys = 0` under normal load
- Warning/Fail: `evicted_keys` increases continuously

## 8) Slow operations

- Pass: `SLOWLOG LEN` stays low/steady
- Fail: recurring slow commands in `SLOWLOG GET`

## 9) Availability under load

- Pass: Redis pod remains `Running` and `Ready`; app remains healthy
- Fail: probe failures, restarts, or Redis timeout errors

## 10) Exporter / Grafana sanity

- Pass:
  - `redis_up = 1`
  - memory and commands/sec panels update continuously
- Fail:
  - missing data / scrape gaps
  - exporter pod not ready
