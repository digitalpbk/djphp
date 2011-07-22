<?php
import("djphp.core.Url");

return array(
    "djphp.contrib.static_serve.views.StaticServeController",
    '^(?P<path>.*?)$' => new Url('serve','static_serve'),
);

