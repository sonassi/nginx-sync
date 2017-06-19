@foreach ($rules as $rule)
allow {{ $rule['ip'] }}; # Added on {{ $rule['timestamp'] }} for {{ $rule['name'] }} at {{ $rule['location'] }}
@endforeach

deny all;

rewrite /robots.txt /robotad.txt;

location ~* \.(php)$ {
  fastcgi_param PHP_VALUE "newrelic.enabled=false";
  include fastcgi_params;
}
