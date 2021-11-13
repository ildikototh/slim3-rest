<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require '../../vendor/autoload.php';

$config['displayErrorDetails'] = true;
$config['addContentLengthHeader'] = false;

$config['db']['host']   = 'localhost';
$config['db']['user']   = 'user';
$config['db']['pass']   = 'password';
$config['db']['dbname'] = 'test';

$app = new \Slim\App(['settings' => $config]);
$container = $app->getContainer();

$container['logger'] = function($c) {
    $logger = new \Monolog\Logger('my_logger');
    $file_handler = new \Monolog\Handler\StreamHandler('../logs/app.log');
    $logger->pushHandler($file_handler);
    return $logger;
};

$container['db'] = function ($c) {
    $db = $c['settings']['db'];
    $pdo = new PDO('mysql:host=' . $db['host'] . ';dbname=' . $db['dbname'],
        $db['user'], $db['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
};

$app->get('/books', function() {
    $sth = $this->db->prepare("select * from library order by book_id");
    $sth->execute();
    $result = $sth->fetchAll(PDO::FETCH_OBJ);
     var_dump($result);
     while( $row = $result->fetch(PDO::FETCH_ASSOC) ) {
        $books[] = $row; // appends each row to the array
      }
      return $books;
   });

$app->get('/tickets', function (Request $request, Response $response) {
  
    $tickets = array('name' => 'Bob', 'age' => 40);
    $newResponse = $response->withJson($tickets);
    // $response->getBody()->write(var_export($tickets, true));
    return $newResponse;
});


$app->get('/hello/{name}', function (Request $request, Response $response, array $args) {
    $name = $args['name'];
    $response->getBody()->write("Hello, $name");

    return $response;
});

$app->run();

