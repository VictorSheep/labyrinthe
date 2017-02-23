<?php namespace Controllers; // nom du dossier dans lequel se trouve la classe

use Symfony\Component\HttpFoundation\Request;


class FrontController{

	public function __construct($app){
		$this->app = $app;
	}

	public function index(){
		return $this->app['twig']->render('home.twig');
	}

	public function create(Request $request){
		$datas = [
			'largeur'=>$request->get('largeur'),
			'hauteur'=>$request->get('hauteur'),
			'couleur'=>$request->get('couleur')
		];

		$prepare = $this->app['pdo']->prepare("DELETE FROM param");
		$prepare->execute();

		$prepare = $this->app['pdo']->prepare("INSERT INTO param (largeur,hauteur,couleur) VALUES (?,?,?);");
		$prepare->bindValue(1,$datas['largeur'],\PDO::PARAM_STR);
		$prepare->bindValue(2,$datas['hauteur'],\PDO::PARAM_STR);
		$prepare->bindValue(3,$datas['couleur'],\PDO::PARAM_STR);
		$prepare->execute();
		return $this->app->redirect('/labyrinthe');
	}

	public function generate(){
		$prepare = $this->app['pdo']->prepare("SELECT * FROM param");
		$prepare->execute();
		$data = $prepare->fetch();


		for ($i=0; $i < $data->largeur+1; $i++) {
			for ($j=0; $j < $data->hauteur; $j++) { 
				$ver[$i][$j] = ($j==0 || $j==count($data->largeur)+1)? 1 : mt_rand(0,1);
			}
		}
		for ($i=0; $i < $data->largeur; $i++) {
			for ($j=0; $j < $data->hauteur+1; $j++) { 
				$hor[$i][$j] = ($j==0 || $j==count($data->hauteur)+2)? 1 : mt_rand(0,1);
			}
		}
		$table=['ver'=>$ver, 'hor'=>$hor];

		/*echo'<pre>';
			print_r($table);
		echo'</pre>';*/

		return $this->app['twig']->render('labyrinthe.twig', ['data' => $data, 'table' => $table]);

	}
}