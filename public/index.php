<?php

declare(strict_types=1);

use App\DatabaseHandler;
use App\ProcessRequestHandler;
use App\TwilioHandler;
use DI\Container;
use Flynsarmy\SlimMonolog\Log\MonologWriter;
use Laminas\Db\Adapter\Adapter;
use \Monolog\Handler\StreamHandler;
use Slim\Factory\AppFactory;
use Twilio\Rest\Client;

require __DIR__ . '/../vendor/autoload.php';

const DATABASE_PATH = "data/database.sqlite";
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . "/../");
$dotenv->load();
$dotenv->required([
    'TWILIO_ACCOUNT_SID',
    'TWILIO_AUTH_TOKEN',
    'TWILIO_PHONE_NUMBER',
])->notEmpty();

$container = new Container();
AppFactory::setContainer($container);

$container->set(DatabaseHandler::class, function (): DatabaseHandler {
    return new DatabaseHandler(new Adapter([
        'driver'   => 'Pdo_Sqlite',
        'database' => DATABASE_PATH,
    ]));
});

$container->set(
    TwilioHandler::class,
    fn (): TwilioHandler => new TwilioHandler(
        new Client(
            $_SERVER['TWILIO_ACCOUNT_SID'],
            $_SERVER['TWILIO_AUTH_TOKEN'],
        ),
        $_SERVER['TWILIO_PHONE_NUMBER'],
        $_SERVER['APP_BASE_URL'],
    )
);

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