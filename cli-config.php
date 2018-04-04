<?php
use Doctrine\ORM\Tools\Console\ConsoleRunner;

require_once __DIR__."/vendor/autoload.php";

$app = ImHere\App::App();
$entityManager = $app['orm.em'];

return ConsoleRunner::createHelperSet($entityManager);

