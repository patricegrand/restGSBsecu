<?php
/** 
 * Classe d'accès aux données. 
 
 * Utilise les services de la classe PDO
 * pour l'application Gsb Rapport 
 * Les attributs sont tous statiques,
 * les 4 premiers pour la connexion
 * $monPdo de type PDO 
 * $monPdoGsbRapports qui contiendra l'unique instance de la classe
 * @package default
 * @author Cheri Bibi
 * @version    1.0
 * @link       http://www.php.net/manual/fr/book.pdo.php
 */

class PdoGsbRapports{   		
      	 /*--------------------Version locale---------------------------------------- */
    
      private static $serveur='mysql:host=localhost';
      private static $bdd='dbname=gsbrapports';   		
      private static $user='root' ;    		
      private static $mdp='' ;
      private static $monPdo;
      private static $monPdoGsbRapports = null;
/**
 * Constructeur privé, crée l'instance de PDO qui sera sollicitée
 * pour toutes les méthodes de la classe
 */				
	private function __construct(){
            self::$monPdo = new PDO(self::$serveur.';'.self::$bdd, self::$user, self::$mdp); 
            self::$monPdo->query("SET CHARACTER SET utf8");
	}
        
	public function _destruct(){
            self::$monPdo = null;
	}
/**
 * Fonction statique qui crée l'unique instance de la classe
 
 * Appel : $instancePdoGsbRapports = PdoGsbRapports::getPdo();
 
 * @return l'unique objet de la classe PdoGsbRapports
 */
	public  static function getPdo(){
		if(self::$monPdoGsbRapports == null){
			self::$monPdoGsbRapports = new PdoGsbRapports();
		}
		return self::$monPdoGsbRapports;  
	}
        public function existeLogin($login){
                $req = "select id from visiteur where login = :login";
                $stm = self::$monPdo->prepare($req);
                $stm->bindParam(':login', $login);
                $stm->execute();
        	$laLigne = $stm->fetch();
                if($laLigne!=null){
                   $ticket = $this->setTicket($laLigne['id']);
                   return $ticket;
                }
                return NULL;
        
        }

/**
 * Retourne les informations du visiteur
 * @param $login 
 * @param $mdp
 * @return le tableau associatif ou NULL
*/

	public function verifierVisiteur($login, $mdpHache){
     		$req = "select * from visiteur where login = :login";
                $stm = self::$monPdo->prepare($req);
                $stm->bindParam(':login', $login);
                $stm->execute();
        	$laLigne = $stm->fetch();
                if(count($laLigne)>1){
                        $mdp = $laLigne['mdp'];
                        $ticket = $laLigne['ticket'];
                        $dernierTimespan = $laLigne['timespan'];
                        $timespanAutorise = time() - 600;
                        $verif = sha1($ticket . $mdp); 
                        if($mdpHache == $verif &&  $dernierTimespan > $timespanAutorise){
                                return $ticket;
                        }
                }
                return NULL;
	}
        public function getIdVisiteur($ticket){
                $req = "select id from visiteur where ticket = :ticket";
                $stm = self::$monPdo->prepare($req);
                $stm->bindParam(':ticket', $ticket);
                $stm->execute();
        	$laLigne = $stm->fetch();
                return $laLigne['id'];
        }
        public function getLesRapportsUneDate($ticket, $date){
                $req = "select rapport.id as idRapport, medecin.nom as nomMedecin, medecin.prenom as prenomMedecin, ";
                $req .= "rapport.motif as motif, rapport.bilan as bilan ";
                $req .= " from visiteur, rapport, medecin where visiteur.ticket = :ticket";
                $req.= " and rapport.idVisiteur = visiteur.id ";
                $req .=" and rapport.idMedecin = medecin.id and rapport.date = :date ";
                $stm = self::$monPdo->prepare($req);
                $stm->bindParam(':ticket', $ticket);
                $stm->bindParam(':date', $date);
                $stm->execute();
                $lesLignes = $stm->fetchall();
                return $lesLignes;
         }
         public function getLeRapport($idRapport){
                $req = "select * from rapport where id = :idRapport" ; 
                $stm = self::$monPdo->prepare($req);
                $stm->bindParam(':idRapport', $idRapport);
                $stm->execute();
                $laLigne = $stm->fetch();
                return $laLigne;
         }
        public function majRapport($idRapport,$motif,$bilan){
                 $req = "update rapport set bilan = :bilan ,motif = :motif where id = :idRapport";
                  $stm = self::$monPdo->prepare($req);
                  $stm->bindParam(':idRapport', $idRapport);
                  $stm->bindParam(':motif', $motif); 
                  $stm->bindParam(':bilan', $bilan); 
                  return $stm->execute();
                 
        } 
        public function getLesMedecins($nom){
            
                $req = "select  * from medecin where nom like '" . $nom ."%' order by nom, prenom";
                $rs = self::$monPdo->query($req);
                $lesLignes = $rs->fetchAll();
                return $lesLignes;
        }
        
        public function getLeMedecin($idMedecin){
                $req = "select * from medecin where id = :idMedecin";
                $stm = self::$monPdo->prepare($req);
                $stm->bindParam(':idMedecin', $idMedecin); 
		$stm->execute();
                $laLigne = $stm->fetch();
                return $laLigne;
                
            
        }
        
        public function majMedecin($id ,$adresse ,$tel ,$specialite){
             $req = "update medecin set tel = :tel ,adresse = :adresse, ";
              $req .= "specialitecomplementaire = :specialite where id = :idMedecin";
                  $stm = self::$monPdo->prepare($req);
                  $stm->bindParam(':idMedecin', $id);
                  $stm->bindParam(':specialite', $specialite);
                  $stm->bindParam(':tel', $tel); 
                  $stm->bindParam(':adresse', $adresse); 
                  return $stm->execute();
        
        }
        public function getLesRapports($idMedecin){
            $req = "select rapport.date as date, rapport.motif as motif, ";
            $req .= " rapport.bilan as bilan, visiteur.nom as nom from rapport, visiteur ";
            $req .= " where rapport.idVisiteur = visiteur.id and rapport.idMedecin = :idMedecin order by date";
            $stm = self::$monPdo->prepare($req);
            $stm->bindParam(':idMedecin', $idMedecin); 
            $stm->execute();
            $lesLignes = $stm->fetchall();
            return $lesLignes;
        }
        public function getLesMedicaments($nom){
            
                $req = "select id, nomCommercial from medicament where nomCommercial like '" . $nom ."%' order by nomCommercial";
                $rs = self::$monPdo->query($req);
		$lesLignes = $rs->fetchAll();
                return $lesLignes;
            
        }
        public function ajouterRapport($idMedecin ,$idVisiteur ,$bilan ,$motif ,$date ,$medicaments){
                  $req = "insert into rapport(idMedecin ,idVisiteur ,bilan ,date, motif) " ;
                  $req .= " values (:idMedecin ,:idVisiteur ,:bilan , :date,  :motif )";
                  $stm = self::$monPdo->prepare($req);
                  $stm->bindParam(':idMedecin', $idMedecin);
                  $stm->bindParam(':idVisiteur', $idVisiteur);
                  $stm->bindParam(':motif', $motif); 
                  $stm->bindParam(':bilan', $bilan); 
                  $stm->bindParam(':date', $date); 
                  $retour = $stm->execute();;
                  $idRapport =  self::$monPdo->lastInsertId();   // récupère l'id créé
                  if($medicaments !=0){
                          foreach ($medicaments as $idMedicament =>$qte){
                              $req = "insert into offrir(idRapport, idMedicament, quantite) ";
                              $req .= "values( :idRapport, :idMedicament, :qte)  ";
                              $stm = self::$monPdo->prepare($req);
                              $stm->bindParam(':idRapport', $idRapport);
                              $stm->bindParam(':idMedicament', $idMedicament);
                              $stm->bindParam(':qte', $qte);
                              $ret = $stm->execute();
                              if($ret!=1)
                                  $retour = 0;
                          }
                   }
                   return $retour;
                   
        }
        public function setTicket($visiteur){
                $time = time();
                $val="azertyuiopqsdfghjklmwxcvbn1234567890AZERTYUIOPQSDFGHJKLMWXCVBN";
                $ticket ="";
                for($i=0;$i<20;$i++)
                        $ticket .= $val[rand(0,strlen($val)-1)];
                $req = "update visiteur set timespan = ".$time. ", ticket ='".$ticket."' where ";
                $req.= " id ='".$visiteur."' or ticket = '". $visiteur ."'";
                $rs = self::$monPdo->query($req);
                return $ticket;
        }
        public function estTicketValide($ticket){
                $req = "select * from visiteur where ticket = :ticket";
                $stm = self::$monPdo->prepare($req);
                $stm->bindParam(':ticket', $ticket); 
		$stm->execute();
                $laLigne = $stm->fetch();
                $timespanAutorise = time() - 600;
                $dernierTimespan = $laLigne['timespan'];
                $ret = 0;
                if(count($laLigne)>1 && $dernierTimespan > $timespanAutorise) 
                        $ret = 1;
                return $ret;
        }
}   // fin classe
?>


