<?php

declare(strict_types=1);

use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$container = new Container();
AppFactory::setContainer($container);

$container->set(\App\ProcessRequestHandler::class, function () {
    return new \App\ProcessRequestHandler();
});


$app = AppFactory::create();

$app->get('/', new \App\ProcessRequestHandler());

$app->run();