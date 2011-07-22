<?php
$cache = App::$settings->CACHES['default'];

if(!isset($cache))
    throw new AppException("No caches defined");

list($module, $class) = module_dot_class($cache['driver']);

import($module);

return new $class;



