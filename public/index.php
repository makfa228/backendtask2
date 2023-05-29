<?php

use DI\Container;
use Slim\Views\Twig;
use Slim\Factory\AppFactory;
use Slim\Views\TwigMiddleware;
use Slim\Middleware\MethodOverrideMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require __DIR__ . '/../vendor/autoload.php';

$container = new Container();
AppFactory::setContainer($container);

$container->set('db', function () {
    $db = new \PDO("sqlite:" . __DIR__ . '/../database/database.sqlite');
    $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(\PDO::ATTR_TIMEOUT, 5000);
    $db->exec("PRAGMA journal_mode = WAL");
    return $db;
});


$app = AppFactory::create();

$app->addBodyParsingMiddleware();

$twig = Twig::create(__DIR__ . '/../twig', ['cache' => false]);

$app->get('/', function (Request $request, Response $response, $args) {
    $response->getBody()->write("Hello world!");
    return $response;
});
//создать GET ендпоинт /products и вывести товары в шаблон
$app->get('/products', function (Request $request, Response $response, $args) {
     $query_params = $request->getQueryParams();//получение параметров запроса

    $db = $this->get('db');//подключение к бд
    $sth = $db->prepare("SELECT * FROM products");//подготовка запрос 
    $sth->execute();//запускает выполнение подготовленного запроса
    $products = $sth->fetchAll(\PDO::FETCH_OBJ);//сохранение значений в массиве

    $view = Twig::fromRequest($request);//создание объекта из объекта запроса
    //возврашение рендеринга шаблона и передача массива в качестве шаблона
    return $view->render($response, 'products.html', [
        'products' => $products
    ]);
});
//создать POST ендпоинты /add-cart
$app->post('/add-cart', function (Request $request, Response $response, $args) {
    if(isset($_POST['product_id'])) {
        // Получаем id товара, который нужно добавить в корзину
        $product_id = $_POST['product_id'];
    
        // Получаем текущую корзину из куков (если она есть)
        $cart = isset($_COOKIE['cart']) ? json_decode($_COOKIE['cart'], true) : array();
    
        // Добавляем товар в корзину
        if(isset($cart[$product_id])) {
            // Если товар уже есть в корзине, увеличиваем его количество
            $cart[$product_id]['quantity']++;
        } else {
            // Если товара нет в корзине, добавляем его в корзину с количеством 1
            $cart[$product_id] = array('product_id' => $product_id, 'quantity' => 1);
        }
    
        // Сохраняем обновленную корзину в куки
        //установка значения корзины в печеньках с временем жизнi
        setcookie('cart', json_encode($cart), time() + 60, '/');
    
    }
});
//Создать GET ендпоинт /cart корзина добавленных товаров
$app->get('/cart', function (Request $request, Response $response, $args) {
    // получение содержания из текущей корзины из печенек и его сохранение в переменную
    $cart = isset($_COOKIE['cart']) ? json_decode($_COOKIE['cart'], true) : array();

    // Создаем массив товаров для вывода на страницу корзины
    $cart_products = array();
    $db = $this->get('db');
    foreach ($cart as $product_id => $cart_item) {
        $sth = $db->prepare("SELECT * FROM products WHERE id = ?");
        $sth->execute([$product_id]);
        $product = $sth->fetch(\PDO::FETCH_OBJ);
        if ($product) {
            $cart_products[] = array(
                'product' => $product,
                'id' => $product_id,
                'name' => $product->name,
                'price' => $product->price,
                'image' => $product->image,
                'quantity' => $cart_item['quantity']
            );
        }
    }

    //возврашение рендеринга шаблона и передаем массива в качестве шаблона
    $view = Twig::fromRequest($request);
    return $view->render($response, 'cart.html', [
        'cart_products' => $cart_products
    ]);
});
//создать POST ендпоинты /remove-cart
$app->post('/remove-cart', function (Request $request, Response $response, $args) {
    
    if(isset($_POST['product_id'])) {
        // Получаем id товара, который нужно убрать из корзины
        $product_id = $_POST['product_id'];
    
        // Получаем текущую корзину из куков (если она есть)
        $cart = isset($_COOKIE['cart']) ? json_decode($_COOKIE['cart'], true) : array();
    
        // Убираем товар из корзины
        if(isset($cart[$product_id])) {
            if($cart[$product_id]['quantity'] > 1) {
                // Если количество товара больше 1, то убираем 1 товар из корзины
                $cart[$product_id]['quantity']--;
            } else {
                // Если количество товара равно 1, то удаляем товар из корзины
                unset($cart[$product_id]);
            }
        }
        setcookie('cart', json_encode($cart), time() + 60, '/');
    
    

    
        //После обнавления печенек отправляется заголовок с адресом страницы
        header('Location: /cart');
        exit();
    }
});
//получение информации о количестве товара в корзине
$app->get('/cart-data', function(Request $request, Response $response, $args) {
    $cart = array();
    if (isset($_COOKIE['cart'])) {
        $cart = json_decode($_COOKIE['cart'], true);
    }
    $count = 0;
    foreach ($cart as $item) {
      $count += $item['quantity'];
    }
    $data = ['count' => $count];

    $json = json_encode($data);
    $response->getBody()->write($json);
        return $response
                ->withHeader('Content-Type', 'application/json');
});
 

$methodOverrideMiddleware = new MethodOverrideMiddleware();
$app->add($methodOverrideMiddleware);

$app->add(TwigMiddleware::create($app, $twig));
$app->run();
