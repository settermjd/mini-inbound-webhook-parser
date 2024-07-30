<?php

declare(strict_types=1);

use App\DatabaseHandler;
use App\GetNoteMessageBodyHandler;
use App\ProcessRequestHandler;
use App\TwilioHandler;
use DI\Container;
use Laminas\Db\Adapter\Adapter;
use mikehaertl\shellcommand\Command;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Slim\Factory\AppFactory;
use Twilio\Rest\Client;

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . "/../");
$dotenv->load();
$dotenv->required([
    'TWILIO_ACCOUNT_SID',
    'TWILIO_AUTH_TOKEN',
    'TWILIO_PHONE_NUMBER',
])->notEmpty();

const DATABASE_PATH = __DIR__ . "/../database";

const LOG_PATH = __DIR__ . "/../logs";

$container = new Container();
AppFactory::setContainer($container);

// Set up the database
$databaseFile = sprintf("%s/database.sqlite", DATABASE_PATH);
if (! file_exists($databaseFile)) {
    $options = sprintf(
        'sqlite3 -init %1$s/dump.sql %1$s/database.sqlite .quit',
        DATABASE_PATH,
    );
    $command = new Command($options);
    echo ($command->execute())
        ? $command->getOutput()
        : $command->getError();
}

$container->set(
    DatabaseHandler::class,
    fn () => new DatabaseHandler(new Adapter([
        'driver'   => 'Pdo_Sqlite',
        'database' => $databaseFile,
    ]))
);

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
            $container->get(TwilioHandler::class),
            $container->get(LoggerInterface::class),
        );
    }
);

$container->set(
    GetNoteMessageBodyHandler::class,
    function (Container $container): GetNoteMessageBodyHandler {
        return new GetNoteMessageBodyHandler($container->get(DatabaseHandler::class));
    }
);

$container->set(
    LoggerInterface::class,
    fn (): LoggerInterface => (new Logger('name'))->pushHandler(
        new StreamHandler(sprintf('%s/app.log', LOG_PATH), Level::Debug)
    )
);

$app = AppFactory::create();

$app->get('/', $container->get(ProcessRequestHandler::class));

$app->run();