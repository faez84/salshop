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

    set req.http.Cookie = regsuball(req.http.Cookie, "(^|;\s*)(__[a-z]+|has_js)=[^;]*", "");

    if (req.url ~ "\.(css|js|png|gif|jp(e)?g|swf|ico)") {
        unset req.http.cookie;
    }
    if (req.url ~ "\.*") {
        unset req.http.cookie;
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

    set beresp.grace = 1h;

    set beresp.ttl = 1620s;

    if (bereq.url ~ "\.*") {
        unset beresp.http.Set-Cookie;
        unset beresp.http.Cache-Control;
    }
    if (bereq.method == "POST") {
       return(abandon);
   }
    if (beresp.status == 404) {
       return(abandon);
    }
    return (deliver);
}