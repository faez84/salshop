vcl 4.0;
import directors;

backend default {
    .host = "nginx";
    .port = "80";
}

sub vcl_init {
    new bar = directors.round_robin();
    bar.add_backend(default);
}

sub vcl_recv {
    set req.backend_hint = bar.backend();

    if (req.method != "GET" && req.method != "HEAD") {
        return (pass);
    }

    if (req.url ~ "^/(admin|cp|basket|basketkk|order|login|logout|register|api|_profiler|_wdt)") {
        return (pass);
    }

    if (req.http.Authorization) {
        return (pass);
    }

    if (req.http.Cookie) {
        set req.http.Cookie = regsuball(req.http.Cookie, "(^|; )(__utm[^=]*|_ga[^=]*|_gid|_fbp|has_js)=[^;]*", "");
        set req.http.Cookie = regsuball(req.http.Cookie, "^;\\s*|\\s*;\\s*$", "");

        if (req.http.Cookie != "") {
            return (pass);
        }

        unset req.http.Cookie;
    }

    return (hash);
}

sub vcl_backend_response {
    set beresp.grace = 60s;

    if (beresp.http.Cache-Control ~ "(?i)private|no-cache|no-store") {
        set beresp.uncacheable = true;
        return (deliver);
    }

    if (beresp.http.Set-Cookie) {
        set beresp.uncacheable = true;
        return (deliver);
    }

    if (beresp.status >= 500) {
        set beresp.ttl = 5s;
        return (deliver);
    }

    if (beresp.ttl <= 0s && beresp.status == 200) {
        set beresp.ttl = 120s;
    }

    return (deliver);
}

sub vcl_deliver {
    if (obj.hits > 0) {
        set resp.http.X-Cache = "HIT";
    } else {
        set resp.http.X-Cache = "MISS";
    }

    set resp.http.X-Cache-Hits = obj.hits;
}
