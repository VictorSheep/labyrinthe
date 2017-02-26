<?php namespace Controllers; // nom du dossier dans lequel se trouve la classe

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

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

		$constraint = new Assert\Collection([
			'largeur' => [
				new Assert\NotBlank(
					['message' => 'ce champ ne doit pas être vide']
				), new Assert\Type([
					'value' => 'numeric',
					'message' => 'nombre svp'
				]), new Assert\Regex([
					'message' => 'le nombre de ligne doit être positif',
					'pattern' => '/^[1-9][0-9]*$/'
				])],
			'hauteur' => [
				new Assert\NotBlank(
					['message' => 'ce champ ne doit pas être vide']
				), new Assert\Type([
					'value' => 'numeric',
					'message' => 'nombre svp'
				]), new Assert\Regex([
					'message' => 'Le nombre de colonne doit être positif',
					'pattern' => '/^[1-9][0-9]*$/'
				])],
			'couleur' => [
				new Assert\NotBlank(
					['message' => 'ce champ ne doit pas être vide']
				)]
		]);

		$errors = $this->app['validator']->validate($datas, $constraint);

		if (count($errors) > 0) {
            $this->app['session']->getFlashBag()->add('errors', $errors);
            return $this->app->redirect('/labyrinthe')
        ;}

		$prepare = $this->app['pdo']->prepare("DELETE FROM param");
		$prepare->execute();

		$prepare = $this->app['pdo']->prepare("INSERT INTO param (largeur,hauteur,couleur) VALUES (?,?,?);");
		$prepare->bindValue(1,$datas['largeur'],\PDO::PARAM_STR);
		$prepare->bindValue(2,$datas['hauteur'],\PDO::PARAM_STR);
		$prepare->bindValue(3,$datas['couleur'],\PDO::PARAM_STR);
		$prepare->execute();
		return $this->app->redirect('/labyrinthe');
	}

		// Methode pour récupérer toutes les cases d'une même salle
	private function getRoomCells($table, $x, $y){
		$val = $table[$x][$y];
		$result = [];
		for ($i=0; $i < count($table); $i++) {
			for ($j=0; $j < count($table[$i]); $j++) { 
				if($table[$i][$j] == $val)
					$result[] = ['x'=>$i, 'y'=>$j];
			}
		}
		return $result;
	}
	
	// Methode pour check si le labyrinthe ne comporte qu'une seul "salle"
	private function oneRoom($table){
		$val = $table[0][0];
		for ($x=0; $x < count($table); $x++) { 
			for ($y=0; $y < count($table[$x]); $y++) { 
				if($table[$x][$y]!=$val) return(false);
			}
		}
		return(true);
	}

	public function generate(){
		$prepare = $this->app['pdo']->prepare("SELECT * FROM param");
		$prepare->execute();
		$data = $prepare->fetch();

		$mapSize = ($data->hauteur>$data->largeur)? $data->hauteur : $data->largeur;
		$celSize = round(720/$mapSize);

    // Génération des mur verticaux
		for ($i=0; $i < $data->hauteur; $i++) {
			for ($j=0; $j < $data->largeur+1; $j++) {
				$ver[$i][$j] = 1;
			}
		}
    // Génération des murs horizontaux
		for ($i=0; $i < $data->hauteur+1; $i++) {
			for ($j=0; $j < $data->largeur; $j++) { 
				$hor[$i][$j] = 1;
			}
		}
		
		// Indice différent sur chaque cellule
		for ($i=0; $i < $data->hauteur; $i++) {
			for ($j=0; $j < $data->largeur; $j++) { 
				$map[$i][$j] = $j+($i*$data->largeur);
			}
		}

		// Génération du labyrinthe
		while (1) { 			
			begin:

			$u = mt_rand(0,$data->hauteur-1);
			$v = mt_rand(0,$data->largeur-1);
			$val = $map[$u][$v];
			$u2= $u;
			$v2= $v;
			$val2= NULL;

			// agirat-on sur $hor ou $ver ?
			$axis = mt_rand(0,1);
      
			// incrémentation ou décrémentation ?
			$sens = mt_rand(0,1);

			if ($axis==0) {
				$u += $sens;/**/
				$u2 += ($sens)? 1 : -1;

				// si la case adjacente n'existe pas on recommence le for
				if (!isset($map[$u2][$v2])) goto begin;
				$val2 = $map[$u2][$v2];

				if ($val != $val2) {
					if (isset($map[$u][$v]) && $u!=0) {
						$hor[$u][$v]=0;
						
					}
				}
			}
			if ($axis==1) {
				$v += $sens;
				$v2 += ($sens)? 1 : -1;

				if (!isset($map[$u2][$v2])) goto begin;
				$val2 = $map[$u2][$v2];

				if ($val != $val2) {
					if (isset($map[$u][$v]) && $v!=0) {
						$ver[$u][$v]=0;
					}
				}
			}
			// on récupère toutes les cellules de la pièce dont on "ouvre la porte" Pour leur attribuer une nouvelle valeur
			$roomCells = $this->getRoomCells($map,$u2,$v2);
			for ($i=0; $i < count($roomCells); $i++) { 
				$x = $roomCells[$i]['x'];
				$y = $roomCells[$i]['y'];
				$map[$x][$y] = $val;
			}
      
      // On vérifie si le labyrinthe est terminé
			if ($this->oneRoom($map)) break;
		}		

		$tables=['ver'=>$ver, 'hor'=>$hor, 'celSize'=>$celSize];

		$this->app['session']->getFlashBag()->add('message', 'Super cool'); return $this->app['twig']->render('labyrinthe.twig', ['data' => $data, 'tables' => $tables]);

	}
}