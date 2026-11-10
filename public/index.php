<?php
declare(strict_types=1);

define('BASE_URL', rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'));

require dirname(__DIR__) . '/app/bootstrap.php';

$rutas = require BASE_PATH . '/config/routes.php';

(new App\Core\Router($rutas))->despachar();
