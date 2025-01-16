<?php

require_once dirname(__DIR__).'/vendor/autoload.php';

use Beam\Beam;
use Beam\Routing;

(new Beam(
    (new Routing)
        ->get('/', function(){return;})
        ->get('/{category}/{slug}', function(){return;})
        ->post('/{category}/{slug}/result', function(){return;})
))->run();
