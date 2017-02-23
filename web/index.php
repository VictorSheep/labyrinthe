<?php
require_once __DIR__.'/../vendor/autoload.php';

use Silex\Application;
use Silex\Provider\TwigServiceProvider as Twig;

$app = new Application();
$app['debug'] = true;
// On ajoute notre controller
$app['front.controller'] = function () use ($app) {
	return new \Controllers\FrontController($app);
};
$app['pdo'] = function($app){
	$options = $app['database.config'];
	return new \PDO($options['dsn'],$options['username'],$options['password'],$options['options']);
};

const DB_HOST = 'localhost';
const DB_DATABASE = 'labyrinthe';
const DB_USERNAME = 'root';
const DB_PASSWORD = '';

$app['database.config'] = [
    'dsn'      => 'mysql:host=' . DB_HOST . ';dbname=' . DB_DATABASE,
    'username' => 'root',
    'password' => '',
    'options'  => [
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8", // flux en utf8
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // mysql erreurs remontées sous forme d'exception
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ, // tous les fetch en objets
    ]
];

$app->register(new Silex\Provider\ServiceControllerServiceProvider());
$app->register(new Silex\Provider\SessionServiceProvider());

// loader de twig
$app->register(new Twig(), [
        'twig.path' => __DIR__ . '/../views',
]);

$app->post('/create', 'front.controller:create');
$app->get('/', 'front.controller:index');
$app->get('/labyrinthe', 'front.controller:generate');


$app->run();