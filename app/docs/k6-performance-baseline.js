import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate, Trend } from 'k6/metrics';

const BASE_URL = (__ENV.BASE_URL || 'http://localhost:8000').replace(/\/$/, '');
const PATH_HOME = __ENV.PATH_HOME || '/';
const PATH_HEALTH = __ENV.PATH_HEALTH || '/health/live';
const PATH_CATEGORIES_API = __ENV.PATH_CATEGORIES_API || '/api/categories';
const PATH_PRODUCTS_API = __ENV.PATH_PRODUCTS_API || '/api/products';
const PATH_PRODUCT_PAGE = __ENV.PATH_PRODUCT_PAGE || '/product/1';

const homeDuration = new Trend('home_duration', true);
const healthDuration = new Trend('health_duration', true);
const categoriesApiDuration = new Trend('categories_api_duration', true);
const productsApiDuration = new Trend('products_api_duration', true);
const productPageDuration = new Trend('product_page_duration', true);
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

export const options = {
  discardResponseBodies: true,
  scenarios: {
    mixed_reads: {
      executor: 'ramping-arrival-rate',
      startRate: asInt('START_RATE', 20),
      timeUnit: '1s',
      preAllocatedVUs: asInt('PRE_ALLOCATED_VUS', 40),
      maxVUs: asInt('MAX_VUS', 200),
      stages: [
        { target: asInt('STAGE1_TARGET', 50), duration: asDuration('STAGE1_DURATION', '2m') },
        { target: asInt('STAGE2_TARGET', 100), duration: asDuration('STAGE2_DURATION', '5m') },
        { target: asInt('STAGE3_TARGET', 120), duration: asDuration('STAGE3_DURATION', '3m') },
      ],
    },
  },
  thresholds: {
    http_req_failed: ['rate<0.01'],
    http_req_duration: ['p(95)<700', 'p(99)<1200'],
    application_errors: ['rate<0.01'],
    home_duration: ['p(95)<600'],
    products_api_duration: ['p(95)<600'],
    categories_api_duration: ['p(95)<600'],
    product_page_duration: ['p(95)<700'],
    health_duration: ['p(95)<250'],
  },
};

function request(path, trend, name, expectedStatuses) {
  const res = http.get(`${BASE_URL}${path}`, {
    tags: { endpoint: name },
  });

  trend.add(res.timings.duration);

  const ok = check(res, {
    [`${name} status`]: (r) => expectedStatuses.includes(r.status),
  });

  if (!ok || res.status >= 500) {
    applicationErrors.add(1);
    return;
  }

  applicationErrors.add(0);
}

function mixedReadStep() {
  const p = Math.random();

  if (p < 0.35) {
    request(PATH_HOME, homeDuration, 'home', [200, 301, 302, 304]);
    return;
  }

  if (p < 0.60) {
    request(PATH_PRODUCTS_API, productsApiDuration, 'products_api', [200, 301, 302, 304]);
    return;
  }

  if (p < 0.80) {
    request(PATH_CATEGORIES_API, categoriesApiDuration, 'categories_api', [200, 301, 302, 304]);
    return;
  }

  if (p < 0.95) {
    request(PATH_PRODUCT_PAGE, productPageDuration, 'product_page', [200, 301, 302, 304, 404]);
    return;
  }

  request(PATH_HEALTH, healthDuration, 'health', [200]);
}

export default function () {
  mixedReadStep();
  sleep(asInt('SLEEP_MS', 0) / 1000);
}
