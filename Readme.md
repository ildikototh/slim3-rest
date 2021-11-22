Lepesek 
*slim [https://www.slimframework.com/docs/v3/]
composer require slim/slim:3.*
src/public konyvtar letrehozas
index.php file:

<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require '../vendor/autoload.php';

$app = new \Slim\App;
$app->get('/hello/{name}', function (Request $request, Response $response, array $args) {
    $name = $args['name'];
    $response->getBody()->write("Hello, $name");

    return $response;
});
$app->run();

elso route tesztelese:
php -S localhost:8080

routing az autoloaderhez
db table letrehozas
CREATE TABLE IF NOT EXISTS `library` ( `book_id` int(11) NOT NULL AUTO_INCREMENT, `book_name` varchar(100) NOT NULL, `book_isbn` varchar(100) NOT NULL, `book_category` varchar(100) NOT NULL, PRIMARY KEY (book_id) ) DEFAULT CHARSET=utf8;

INSERT INTO `library` (`book_id`, `book_name`, `book_isbn`, `book_category`) VALUES (1, 'PHP', 'bk001', 'Server Side'), (3, 'javascript', 'bk002', 'Client Side'), (4, 'Python', 'bk003', 'Data Analysis');

db csatlakozas hozzaadasa index.phpben
Get route hozzaadasa
Post route hozzaadasa
delete route
Put route
patch route

