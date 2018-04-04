<?php
require_once __DIR__."/vendor/autoload.php";

// require_once ROOT."src/
// use Symfony\Component\HttpFoundation\Request;
// use Symfony\Component\HttpFoundation\Response;

// $app = new Silex\Application();

// $app->get("/", function () use ($app) {
//     $data = json_decode(file_get_contents('composer.json'), $assoc=true);
//     return $app->json([
//         "name" => $data["name"],
//         "version" => $data["version"]
//     ]);
// });

$app = ImHere\App::App();

// $app->mount("/import", new SM\ImportControllerProvider());
// $app->mount("/export", new SM\ExportControllerProvider());
// $app->mount("/images", new SM\ImagesControllerProvider());
// $app->mount("/profile", new SM\ProfileControllerProvider());
// $app->mount("/auth", new SM\AuthControllerProvider());
// $app->mount("/search", new SM\SearchControllerProvider());

$app->run();
