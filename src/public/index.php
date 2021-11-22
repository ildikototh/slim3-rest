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

$container['db'] = function ($c) {
    $db = $c['settings']['db'];
    $pdo = new PDO('mysql:host=' . $db['host'] . ';dbname=' . $db['dbname'],
        $db['user'], $db['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
};

//sql query: SELECT * FROM library Order By book_id
//Az adatbazisban levo konyvek lekerdezese
//get 
$app->get('/api/books', function (Request $request, Response $response) {
    $sql_query = "SELECT * FROM library Order By book_id";
    $query = $this->db->prepare($sql_query);
    $query->execute();
    $books = $query->fetchAll();
    return $response->withJson(['data'=>$books], 200);
});

$app->get('/api/book/{id}', function (Request $request, Response $response, $args) {
    $sql_query = "SELECT * FROM library where book_id = :book_id";
    $query= $this->db->prepare($sql_query);
    $query->bindParam("book_id", $args['id']);
    $query->execute();
    $books = $query->fetchAll();
    //if (!$books) $message = "Nincs az adott idval konyv";
    return $response->withJson(['data'=>$books], 200);
});

// Konyv keresese megadott parameterek alapjan
$app->get('/api/book/search/[{query}]', function ($request, $response, $args) {
    $sth = $this->db->prepare("SELECT * FROM library WHERE UPPER(book_name) LIKE :query ORDER BY book_name");
    $query = "%".$args['query']."%";
    $sth->bindParam("query", $query);
    $sth->execute();
    $books = $sth->fetchAll();
    return $this->response->withJson($books, 200);
});

 
// Uj konyv hozzadasa
$app->post('/api/book', function ($request, $response) {
    $input = $request->getParsedBody();
    if(!empty($input['name'] && !empty($input['isbn']))){
        $sql = "INSERT INTO library (book_name, book_isbn, book_category) VALUES (:name, :isbn, :category)";
        $sth = $this->db->prepare($sql);
        $sth->bindValue(':name',$input['name']);
        $sth->bindValue(':isbn', $input['isbn']);
        $sth->bindValue(':category', $input['category']);
        $sth->execute();
        $input['id'] = $this->db->lastInsertId();
        $rsp['message'] =  $input['name'] . " konyv bekerult az adatbazisba, id ".  $input['id']  ;
    } else {
        $rsp["error"] = false;
        $rsp['message'] = "Hianyzo adatok, konyv cime es isbn kell" ;
    }
    return $response
        ->withStatus(201)
        ->withJson($rsp);
});
     
//konyv torlese id alapjan
//methodus: DELETE
//parameter: id 
$app->delete('/api/book/delete/[{id}]', function ($request, $response, $args) {
    $query = "DELETE FROM library WHERE book_id=:id";
    try {
        $sth = $this->db->prepare($query);
        $sth->bindParam("id", $args['id']);
        $sth->execute();
        $rsp["error"] = false;
        $rsp["message"] = 'A ' .$args['id'] . ' idju konyv sikeresen torolve';
    } catch (PDOException $e) {
      // a konyvet nem sikerult torolni
      $rsp["error"] = true;
      $rsp["message"] = 'Hiba a '. $args['id']. ' idju konyv torlese soran!';
    }

    return $response
        ->withStatus(200)
        ->withJson($rsp); 
});
    
// ID alapjan konyv cimenek updatelese -> a teljes adatsor updatelodik
//methodus: put
$app->put('/api/book/[{id}]', function ($request, $response, $args) {
    $input = $request->getParsedBody();
    $sql = "UPDATE library SET book_name=:name, book_isbn=:isbn, book_category=:category WHERE book_id=:id";
    try {
        $sth = $this->db->prepare($sql);
        $sth->bindParam("id", $args['id']);
        $sth->bindValue(':name',$input['name']);
        $sth->bindValue(':isbn',$input['isbn']);
        $sth->bindValue(':category',$input['category']);
        $sth->execute(); 
        $rsp["error"] = false;
        $rsp["message"] = 'A ' .$args['id'] . ' idju konyv sikeresen frissitve';
    } catch (Exception $e) {
        $rsp["error"] = true;
        $rsp["message"] = $e.getMessage();
    }
    return $response
        ->withStatus(200)
        ->withJson($rsp); 
});

// ID alapjan konyv cimenek updatelese -> reszleges frissites
//methodus: patch
$app->patch('/api/book/[{id}]', function ($request, $response, $args) {
    $input = $request->getParsedBody();
    $sql = "UPDATE library SET book_name=:name WHERE book_id=:id";
    try {
        $sth = $this->db->prepare($sql);
        $sth->bindParam("id", $args['id']);
        $sth->bindValue(':name',$input['name']);
        $sth->execute(); 
        $rsp["error"] = false;
        $rsp["message"] = 'A ' .$args['id'] . ' idju konyv sikeresen frissitve';
    } catch (Exception $e) {
        $rsp["error"] = true;
        $rsp["message"] = $e;
    }
    return $response
        ->withStatus(200)
        ->withJson($rsp); 
});

$app->get('/hello/{name}', function (Request $request, Response $response, array $args) {
    $name = $args['name'];
    $response->getBody()->write("Hello, $name");
    return $response;
});
$app->run();