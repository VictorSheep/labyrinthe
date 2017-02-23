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

		for ($i=0; $i < $data->hauteur; $i++) {
			for ($j=0; $j < $data->largeur+1; $j++) {
				//$ver[$i][$j] = ($j==0 || $j==$data->largeur)? 1 : mt_rand(0,1);
				$ver[$i][$j] = 1;
			}
		}
		for ($i=0; $i < $data->hauteur+1; $i++) {
			for ($j=0; $j < $data->largeur; $j++) { 
				//$hor[$i][$j] = ($i==0 || $i==$data->hauteur)? 1 : mt_rand(0,1);
				$hor[$i][$j] = 1;
			}
		}
		
		for ($i=0; $i < $data->hauteur; $i++) {
			for ($j=0; $j < $data->largeur; $j++) { 
				$map[$i][$j] = $j+($i*$data->largeur);
			}
		}

		// Génération du labyrinthe
		for ($k=0; $k < 10; $k++) { 
			begin:
			$u = mt_rand(0,$data->hauteur-1);
			$v = mt_rand(0,$data->largeur-1);
			$u2= $u;
			$v2= $v;
			$val = $map[$u][$v];
			$val2= NULL;
			// echo("u = ".$u."<br/>");
			// echo("v = ".$v."<br/>");
			// echo("val = ".$map[$u][$v]."<br/>");

			// agirat-on sur $hor ou $ver ?
			$axis = mt_rand(0,1);
			// var_dump($axis);

			// incrémentation ou décrémentation ?
			$sens = mt_rand(0,1);
			// var_dump($sens);

			if ($axis==0) {
				$u += $sens;
				$u2 += ($sens)? 1 : -1;

				if (!isset($map[$u2][$v2])) goto begin;
				$val2 = $map[$u2][$v2];

				// echo "<br/>".$u2;
				if ($val != $val2) {
					if (isset($map[$u][$v]) && $u!=0) {
						$hor[$u][$v]=0;
						if ($sens) $map[$u][$v]=$val;
						else $map[$u-1][$v]=$val;
					}
				}
			}
			if ($axis==1) {
				$v += $sens;
				$v2 += ($sens)? 1 : -1;

				if (!isset($map[$u2][$v2])) goto begin;
				$val2 = $map[$u2][$v2];

				// echo "<br/>".$v2;
				if ($val != $val2) {
					if (isset($map[$u][$v]) && $v!=0) {
						$ver[$u][$v]=0;
						if ($sens) $map[$u][$v]=$val;
						else $map[$u][$v-1]=$val;
					}
				}
			}
			echo "<hr/>";
		}

		/*echo'<pre>';
			print_r($map);
		echo'</pre>';*/

		$tables=['ver'=>$ver, 'hor'=>$hor];

		/*echo'<pre>';
			print_r($map);
		echo'</pre>';*/

		return $this->app['twig']->render('labyrinthe.twig', ['data' => $data, 'tables' => $tables]);

	}
}