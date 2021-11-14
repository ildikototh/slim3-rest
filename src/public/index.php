<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require '../../vendor/autoload.php';

$config['displayErrorDetails'] = true;
$config['addContentLengthHeader'] = false;

$config['db']['host']   = '127.0.0.1';
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

// az adatbazisban levo konyvek lekerdezese
// get method hasznalataval
$app->get('/books', function(Request $request, Response $response) {
    $query = $this->db->prepare("select * from library order by book_id");
    $query->execute();
    while( $row = $query->fetch(PDO::FETCH_ASSOC) ) {
        $books[] = $row; // appends each row to the array
    }
    return $response->withJson(['data' => $books ], 200); 
});

// Adott konyv Id alapjan keresese
$app->get('/book/[{id}]', function ($request, $response, $args) {
    $sth = $this->db->prepare("SELECT * FROM library WHERE book_id=:book_id");
    $sth->bindParam("book_id", $args['id']);
    $sth->execute();
    $books = $sth->fetchObject();
    return $this->response->withJson(['data' => $book ? $book : [] ], 200);
});

// Konyv keresese megadott parameterek alapjan
$app->get('/book/search/[{query}]', function ($request, $response, $args) {
    $sth = $this->db->prepare("SELECT * FROM library WHERE UPPER(name) LIKE :query ORDER BY book_name");
    $query = "%".$args['query']."%";
    $sth->bindParam("query", $query);
    $sth->execute();
    $boks = $sth->fetchAll();
        return $this->response->withJson($books);
});
        
// Uj konyv hozzadasa
$app->post('/book', function ($request, $response) {
    $input = $request->getParsedBody();
    if(!empty($input['name'] && !empty($input['isbn']))){
        $sql = "INSERT INTO library (book_name, book_isbn) VALUES (:name, :isbn)";
        $sth = $this->db->prepare($sql);
        $sth->bindValue(':name',$input['name']);
        $sth->bindValue(':isbn', $input['isbn']);
        $sth->execute();
        $input['id'] = $this->db->lastInsertId();
        $rsp['message'] =  $input['name'] . "konyv bekerult az adatbazisba, id ".  $input['id']  ;
    } else {
        $rsp["error"] = false;
        $rsp['message'] = "Hianyzo adatok, konyv cime es isbn kell" ;
    }
    return $response
        ->withStatus(201)
        ->withJson($rsp);
});
     

// konyv torlese id alapjan
//methodus: DELETE
//parameter: id 
$app->delete('/book/[{id}]', function ($request, $response, $args) {
    $sth = $this->db->prepare("DELETE FROM library WHERE book_id=:id");
    $sth->bindParam("id", $args['id']);
    $sth->execute();
    return $response->withJson(['message' => 'Book with id='. $args['id']. ' deleted' ], 200); 
});
    
// ID alapjan konyv cimenek updatelese -> a teljes adatsor updatelodik
//methodus: put
$app->put('/book/[{id}]', function ($request, $response, $args) {
    $input = $request->getParsedBody();
    $sql = "UPDATE library SET book_name=:name WHERE book_id=:id";
    $sth = $this->db->prepare($sql);
    $sth->bindParam("id", $args['id']);
    $sth->bindParam("name", $input['name']);
    $sth->execute();
    $input['id'] = $args['id'];
    return $this->response->withJson($input);
});

// Reszleges update
$app->patch('/book/:id', function ($id) {
    $input = $request->getParsedBody();
    $sql = "UPDATE library SET book_category=:category WHERE book_id=:id";
    $sth = $this->db->prepare($sql);
    $sth->bindParam("id", $args['id']);
    $sth->bindParam("category", $input['category']);
    $sth->execute();
    $input['id'] = $args['id'];
    return $this->response->withJson($input);
});

$app->get('/hello/{name}', function (Request $request, Response $response, array $args) {
    $name = $args['name'];
    $response->getBody()->write("Hello, $name");
    return $response;
});

$app->run();

