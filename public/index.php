<?php

declare(strict_types=1);

use App\DatabaseHandler;
use App\ProcessRequestHandler;
use DI\Container;
use Laminas\Db\Adapter\Adapter;
use \Monolog\Handler\StreamHandler;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

const DATABASE_PATH = "data/database.sqlite";

$container = new Container();
AppFactory::setContainer($container);

$container->set(DatabaseHandler::class, function (): DatabaseHandler {
    return new DatabaseHandler(new Adapter([
        'driver'   => 'Pdo_Sqlite',
        'database' => DATABASE_PATH,
    ]));
});

$container->set(
    ProcessRequestHandler::class,
    function (Container $container): ProcessRequestHandler {
        return new ProcessRequestHandler(
            $container->get(DatabaseHandler::class),
            $container->get(\Psr\Log\LoggerInterface::class),
        );
    }
);

$container->set(
    \Psr\Log\LoggerInterface::class,
    function (): MonologWriter {
        return new MonologWriter(array(
            'handlers' => array(
                new StreamHandler('./logs/'.date('Y-m-d').'.log'),
            ),
        ));
    }
);

$app = AppFactory::create();

$app->get('/', $container->get(ProcessRequestHandler::class));

$app->run();