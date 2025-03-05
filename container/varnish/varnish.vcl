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

    if (req.http.Cookie == "") {
        unset req.http.Cookie;
    }
}

sub vcl_deliver {

    # A bit of debugging info.
    if (obj.hits > 0) {
        set resp.http.X-Cache = "HIT";
    }
    else {
        set resp.http.X-Cache = "MISS";
    }
}

sub vcl_backend_response {

    set beresp.grace = 15s;

    set beresp.ttl = 30s;


    return (deliver);
}