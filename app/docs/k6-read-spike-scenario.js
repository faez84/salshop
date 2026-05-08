import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate, Trend } from 'k6/metrics';

const BASE_URL = (__ENV.BASE_URL || 'http://localhost:8000').replace(/\/$/, '');
const CATEGORY_PATH_TEMPLATE = __ENV.CATEGORY_PATH_TEMPLATE || '/category/{id}/products';
const PRODUCT_PATH_TEMPLATE = __ENV.PRODUCT_PATH_TEMPLATE || '/product/{id}';
const CATEGORY_IDS = (__ENV.CATEGORY_IDS || '1,2,3,4,5').split(',').map((v) => v.trim()).filter(Boolean);
const PRODUCT_IDS = (__ENV.PRODUCT_IDS || '1,2,3,4,5,6,7,8,9,10').split(',').map((v) => v.trim()).filter(Boolean);

const categoryProductsDuration = new Trend('category_products_duration', true);
const productDetailDuration = new Trend('product_detail_duration', true);
const applicationErrors = new Rate('application_errors');

function asInt(name, fallback) {
  const value = Number(__ENV[name]);

  if (Number.isNaN(value) || value <= 0) {
    return fallback;
  }

  return Math.floor(value);
}

function asDuration(name, fallback) {
  return __ENV[name] || fallback;
}

function pickRandom(values, fallback) {
  if (!values.length) {
    return fallback;
  }

  const idx = Math.floor(Math.random() * values.length);
  return values[idx];
}

function renderPath(template, id) {
  return template.replace('{id}', String(id));
}

export const options = {
  discardResponseBodies: true,
  scenarios: {
    read_spike: {
      executor: 'ramping-arrival-rate',
      startRate: asInt('START_RATE', 30),
      timeUnit: '1s',
      preAllocatedVUs: asInt('PRE_ALLOCATED_VUS', 60),
      maxVUs: asInt('MAX_VUS', 300),
      stages: [
        { target: asInt('WARM_TARGET', 60), duration: asDuration('WARM_DURATION', '2m') },
        { target: asInt('SPIKE_TARGET', 220), duration: asDuration('SPIKE_DURATION', '4m') },
        { target: asInt('STEADY_TARGET', 140), duration: asDuration('STEADY_DURATION', '4m') },
      ],
    },
  },
  thresholds: {
    http_req_failed: ['rate<0.05'],
    http_req_duration: ['p(95)<1800', 'p(99)<2500'],
    application_errors: ['rate<0.05'],
    category_products_duration: ['p(95)<1800'],
    product_detail_duration: ['p(95)<1800'],
  },
};

function request(path, trend, endpointName, expectedStatuses) {
  const res = http.get(`${BASE_URL}${path}`, {
    tags: { endpoint: endpointName },
  });

  trend.add(res.timings.duration);

  const ok = check(res, {
    [`${endpointName} status`]: (r) => expectedStatuses.includes(r.status),
  });

  if (!ok || res.status >= 500) {
    applicationErrors.add(1);
    return;
  }

  applicationErrors.add(0);
}

function readSpikeStep() {
  const p = Math.random();

  if (p < 0.7) {
    const id = pickRandom(CATEGORY_IDS, '1');
    const path = renderPath(CATEGORY_PATH_TEMPLATE, id);
    request(path, categoryProductsDuration, 'category_products', [200, 301, 302, 304]);
    return;
  }

  const id = pickRandom(PRODUCT_IDS, '1');
  const path = renderPath(PRODUCT_PATH_TEMPLATE, id);
  request(path, productDetailDuration, 'product_detail', [200, 301, 302, 304, 404]);
}

export default function () {
  readSpikeStep();
  sleep(asInt('SLEEP_MS', 0) / 1000);
}
