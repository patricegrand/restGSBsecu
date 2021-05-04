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
      	
    
      private static $serveur='mysql:host=localhost:3308'; // pour mysql
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
 /**
   * Retourne le ticket du secretaire
   * @param $login 
   * @return le ticket ou null si login non trouve
 */
        public function existeLogin($login){
                $req = "select id from secretaire where login = :login ";
                $stm = self::$monPdo->prepare($req);
                $stm->bindParam(':login', $login);
                $stm->execute();
                $laLigne = $stm->fetch();
                if($laLigne != null){
                   $ticket = $this->setTicket($laLigne['id']);
                   return $ticket;
                }
                return NULL;
        }
        public function setTicket($secretaire){
                $time = time();
                $val="azertyuiopqsdfghjklmwxcvbn1234567890ABCDEFGHLMNP";
                $ticket ="";
                $nb = rand(40,50);
                $longueur = strlen($val);
                for($i=0;$i<$nb;$i++)
                        $ticket .= $val[rand(0,$longueur-1)];
                $req = "update secretaire set timespan = ".$time. ", ticket ='".$ticket."' where ";
                $req.= " id = :secretaire or ticket = :secretaire ";
                $stm = self::$monPdo->prepare($req);
                $stm->bindParam(':secretaire', $secretaire);
                $retour = $stm->execute();
                //error_log(print_r($ticket.'\n',true),3,"log.txt");
                return $ticket;
        }
        public function estValide($ticketHash){
                $req = "select  * from secretaire where SHA1(concat(ticket,mdp)) = :ticketHash ";
                $stm = self::$monPdo->prepare($req);
                $stm->bindParam(':ticketHash', $ticketHash);
                $retour = $stm->execute();
                $laLigne = $stm->fetch();
                $timespanAutorise = time() - 600;
                $dernierTimespan = $laLigne['timespan'];
                $ret = "";
                if(count($laLigne)>0 && $dernierTimespan > $timespanAutorise) {
                        $ret = $laLigne['id'];
                }
                return $ret;
        }

/**
 * Retourne les informations du secretaire
 * @param $mdpHache
 * @param $mdp
 * @return le tableau associatif ou NULL
*/

	public function verifierSecretaire($login, $mdpHache){
     		$req = "select * from secretaire where login = :login";
                $stm = self::$monPdo->prepare($req);
                $stm->bindParam(':login', $login);
                $stm->execute();
        	$laLigne = $stm->fetch();
                if(count($laLigne)>1){
                        $mdp = $laLigne['mdp'];
                        $nom = $laLigne['nom'];
                        $prenom = $laLigne['prenom'];
                        $ticket = $laLigne['ticket'];
                       // error_log(print_r("ticket" .$ticket ,true),3,"log.txt");
                        $dernierTimespan = $laLigne['timespan'];
                        $timespanAutorise = time() - 600;
                        $verif = sha1($ticket . $mdp); 
                       // error_log(print_r("verif hash" .$verif ,true),3,"log.txt");
                        if($mdpHache == $verif &&  $dernierTimespan > $timespanAutorise){
                                return array(
                                      "nom" =>  $nom,
                                      "prenom" => $prenom,
                                      "ticket" => $ticket
                                );
                        }
                }
                return NULL;
        }
        /*----------------------------------------Gestion des médecins-----------------------------*/
        public function getLesMedecins($nom){
                
                $req = "select  * from medecin where upper(nom) like upper('" . $nom ."%') order by nom, prenom";
                $rs = self::$monPdo->query($req);
                $lesLignes = $rs->fetchAll();
                return $lesLignes;
        }
        public function ajouterMedecin($nom, $prenom, $adresse, $tel, $specialite, $departement){
                 $req ="insert into medecin(nom, prenom, adresse, tel, departement, specialiteComplementaire)
                        values ( :nom, :prenom, :adresse, :tel, :departement,:specialite )"; 
        //        error_log(print_r($req,true),3,"log.txt");
                $stm = self::$monPdo->prepare($req);
                $stm->bindParam(':nom', $nom);
                $stm->bindParam(':prenom', $prenom);
                $stm->bindParam(':adresse', $adresse); 
                $stm->bindParam(':tel', $tel); 
                $stm->bindParam(':specialite', $specialite);
                $stm->bindParam(':departement', $departement);
                $retour = $stm->execute();
                return $retour; 
        }
        public function getLeMedecin($idMedecin){
                $req = "select * from medecin where id = :idMedecin";
                $stm = self::$monPdo->prepare($req);
                $stm->bindParam(':idMedecin', $idMedecin); 
		$stm->execute();
                $laLigne = $stm->fetch();
                return $laLigne;
        }
        
        public function majMedecin($id ,$adresse ,$tel ,$specialite, $departement){
                $req = "update medecin set tel = :tel ,adresse = :adresse, ";
                $req .= "specialitecomplementaire = :specialite, departement = :departement where id = :id";
                $stm = self::$monPdo->prepare($req);
                $stm->bindParam(':id', $id);
                $stm->bindParam(':specialite', $specialite);
                $stm->bindParam(':tel', $tel); 
                $stm->bindParam(':adresse', $adresse);
                $stm->bindParam(':departement', $departement);  
                return $stm->execute();
        }
            
       
        /*--------------------------------Gestion des rapports------------------------ */
        public function getLesRapportsEntreDeuxDates( $dateDebut, $dateFin){ /* à tester*/
                $req = " select distinct medecin.nom as nomMedecin, medecin.prenom as prenomMedecin,rapport.date as date, ";
                $req .= "visiteur.nom as nomVisiteur, visiteur.prenom as prenomVisiteur,rapport.motif as motif, rapport.bilan as bilan ";
                $req .= " from visiteur, rapport, medecin where ";
                $req.= " rapport.idVisiteur = visiteur.id ";
                $req .=" and rapport.idMedecin = medecin.id and rapport.date > :dateDebut and rapport.date < :dateFin order by date";
         //       error_log(print_r($req ,true),3,"log.txt");
                $stm = self::$monPdo->prepare($req);
                $stm->bindParam(':dateDebut', $dateDebut);
                $stm->bindParam(':dateFin', $dateFin);
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
        
        public function getLesRapportsParMedecin($idMedecin){
              //  error_log(print_r("dans le pdo" .$idMedecin ,true),3,"log.txt");
            $req = "select rapport.id as id, rapport.date as date, rapport.motif as motif,medecin.nom as nomMedecin, medecin.prenom as prenomMedecin, ";
            $req .= " rapport.bilan as bilan, visiteur.nom as nomVisiteur, visiteur.prenom as prenomVisiteur from rapport, visiteur, medecin  ";
            $req .= " where rapport.idVisiteur = visiteur.id and rapport.idMedecin = Medecin.id and rapport.idMedecin = :idMedecin order by date ";
            error_log(print_r("dans le pdo" .$req ,true),3,"log.txt");
            $stm = self::$monPdo->prepare($req);
            $stm->bindParam(':idMedecin', $idMedecin); 
            $stm->execute();
            $lesLignes = $stm->fetchall();
            return $lesLignes;
        }
        public function getLesRapportsParVisiteur($idVisiteur){
                //error_log(print_r("passe par visiteur" .$idVisiteur ,true),3,"log.txt");
                $req = "select distinct rapport.date as date, rapport.motif as motif, ";
                $req .= " rapport.bilan as bilan, medecin.nom as nomMedecin, medecin.prenom as prenomMedecin from rapport, visiteur, medecin ";
                $req .= " where rapport.idMedecin = medecin.id and rapport.idVisiteur = :idVisiteur ";
                $req .= " and rapport.idVisiteur = visiteur.id order by date ";
                //error_log(print_r($req ,true),3,"log.txt");
                $stm = self::$monPdo->prepare($req);
                $stm->bindParam(':idVisiteur', $idVisiteur); 
                $stm->execute();
                $lesLignes = $stm->fetchall();
                return $lesLignes;
            }
        public function getLesRapportsEntreDeuxDatesUnVisiteur($idVisiteur, $dateDebut, $dateFin){
                $req = " select distinct medecin.nom as nomMedecin, medecin.prenom as prenomMedecin,rapport.date as date, ";
                $req .= "visiteur.nom as nomVisiteur, visiteur.prenom as prenomVisiteur,rapport.motif as motif, rapport.bilan as bilan ";
                $req .= " from visiteur, rapport, medecin where rapport.idVisiteur = :idVisiteur and ";
                $req.= " rapport.idVisiteur = visiteur.id and rapport.idMedecin = medecin.id ";
                $req .="  and rapport.date > :dateDebut and rapport.date < :dateFin order by date";  
                $stm = self::$monPdo->prepare($req);
                $stm->bindParam(':dateDebut', $dateDebut);
                $stm->bindParam(':dateFin', $dateFin);
                $stm->bindParam(':idVisiteur', $idVisiteur);
                $stm->execute();
                $lesLignes = $stm->fetchall();
                return $lesLignes;
        }
        public function ajouterRapport($idMedecin ,$idVisiteur ,$bilan ,$motif ,$date ,$medicaments){
               // error_log(print_r($idMedecin ,true),3,"log.txt");
                error_log(print_r($medicaments ,true),3,"log.txt");
                //error_log(print_r($idVisiteur ,true),3,"log.txt");
                //error_log(print_r($bilan ,true),3,"log.txt");
               // error_log(print_r($motif . $date ,true),3,"log.txt");
                  $req = "insert into rapport(idMedecin ,idVisiteur ,bilan ,date, motif) " ;
                  $req .= " values (:idMedecin ,:idVisiteur ,:bilan , :date,  :motif )";
                  $stm = self::$monPdo->prepare($req);
                  $stm->bindParam(':idMedecin', $idMedecin);
                  $stm->bindParam(':idVisiteur', $idVisiteur);
                  $stm->bindParam(':motif', $motif); 
                  $stm->bindParam(':bilan', $bilan); 
                  $stm->bindParam(':date', $date); 
                  $retour = $stm->execute();
                  $idRapport =  self::$monPdo->lastInsertId();   // récupère l'id créé
                  error_log(print_r(count($medicaments) ,true),3,"log.txt");
                 if(count($medicaments) !=0){
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
         /*---------------------------------------Gestion des médicaments------------------- */          
         public function getLesMedicaments(){
                $req = "select * from medicament order by nomCommercial";
                //error_log(print_r($req,true),3,"log.txt");
                $rs = self::$monPdo->query($req);
		$lesLignes = $rs->fetchAll();
                return $lesLignes;            
        }
        public function getLesMedicamentsparNom($nom){
                $req = "select * from medicament where upper(nomCommercial) like upper('" . $nom ."%') order by nomCommercial";
                $rs = self::$monPdo->query($req);
                $lesLignes = $rs->fetchAll();
                return $lesLignes;
        }
        public function getLesMedicamentsParFamille($idFamille){
                $req = "select * from medicament where idFamille =:idFamille order by nomCommercial";
                $stm = self::$monPdo->prepare($req);
                $stm->bindParam(':idFamille', $idFamille);
                $stm->execute();
                $lesLignes = $stm->fetchAll();
                return $lesLignes;
        }
        public function getLesMedicamentsEntreDates($dateDebut, $dateFin){
                $req= "select distinct medicament.* from medicament, rapport, offrir where offrir.idMedicament = medicament.id ";
                $req.= " and rapport.id = offrir.idRapport and rapport.date > :dateDebut and rapport.date < :dateFin order by nomCommercial";
                $stm = self::$monPdo->prepare($req);
                $stm->bindParam(':dateDebut', $dateDebut);
                $stm->bindParam(':dateFin', $dateFin);
                $stm->execute();
                $lesLignes = $stm->fetchAll();
                return $lesLignes;

        }
        public function getLeMedicament($idMedicament){
                $req = "select * from medicament where id = :idMedicament";
                $stm = self::$monPdo->prepare($req);
                $stm->bindParam(':idMedicament', $idMedicament); 
		$stm->execute();
                $laLigne = $stm->fetch();
                return $laLigne;
        }
        public function majMedicament($id ,$effets, $contreIndics, $compo){
                $req = "update medicament set effets = :effets ,contreIndications = :contreIndics, ";
                $req .= "composition = :compo where id = :idMedicament";
             //   error_log(print_r($req,true),3,"log.txt");
                $stm = self::$monPdo->prepare($req);
                $stm->bindParam(':idMedicament', $id);
                $stm->bindParam(':effets', $effets);
                $stm->bindParam(':compo', $compo);
                $stm->bindParam(':contreIndics', $contreIndics); 
                return $stm->execute();
        }
        public function ajouterMedicament($idMedicament, $nomCommercial,$effets, $contreIndications, $composition, $idFamille){
                $req = "insert into medicament (id, nomCommercial, effets, composition, contreIndications, idFamille) ";
                $req .= " values(:id, :nomCommercial, :effets,  :composition, :contreIndications, :idFamille)";
              //  error_log(print_r($req,true),3,"log.txt");
                $stm = self::$monPdo->prepare($req);
                $stm->bindParam(':id', $idMedicament);
                $stm->bindParam(':nomCommercial', $nomCommercial);
                $stm->bindParam(':effets', $effets);
                $stm->bindParam(':composition', $composition);
                $stm->bindParam(':idFamille', $idFamille);
                $stm->bindParam(':contreIndications', $contreIndications); 
                return $stm->execute();
        }
        /*----------------------------------------Gestion des familles--------------------------------*/
        public function getLesFamilles(){
                $req = "select * from famille";
                $rs = self::$monPdo->query($req);
		$lesLignes = $rs->fetchAll();
                return $lesLignes;
        }
        public function getLaFamille($id){
                $req = "select * from famille where id = :id ";
                $stm = self::$monPdo->prepare($req);
                $stm->bindParam(':id', $id);
                $stm->execute();
        	$laLigne = $stm->fetch();
                return $laLigne;
        }
        public function ajouterFamille($id, $libelle){
                $req = "insert into famille(id, libelle) values( :id, :libelle)";
               
                $stm = self::$monPdo->prepare($req);
                $stm->bindParam(':id', $id);
                $stm->bindParam(':libelle', $libelle);
              
                return $stm->execute();
        }
        public function majFamille($id ,$libelle){
                $req = "update famille set libelle = :libelle where id = :id";
        //          error_log(print_r($req,true),3,"log.txt");
                $stm = self::$monPdo->prepare($req);
                $stm->bindParam(':id', $id);
                $stm->bindParam(':libelle', $libelle);
                return $stm->execute();
                
        
        }

        /*-----------------------------Gestion des visiteurs---------------------------------*/
        public function getIdVisiteur($ticket){
                $req = "select id from visiteur where ticket = :ticket";
                $stm = self::$monPdo->prepare($req);
                $stm->bindParam(':ticket', $ticket);
                $stm->execute();
        	$laLigne = $stm->fetch();
                return $laLigne['id'];
        }
        public function getLesVisiteurs($nom){
                if($nom == "")
                     $req = "select * from visiteur order by nom, prenom";
                else
                      $req = "select  * from visiteur where upper(nom) like upper('" . $nom ."%') order by nom, prenom";
                $rs = self::$monPdo->query($req);
                $lesLignes = $rs->fetchAll();
                return $lesLignes;
        }
        public function ajouterVisiteur($id, $nom, $prenom, $adresse, $cp, $ville, $dateEmbauche){
               // error_log(print_r($id . $nom . $prenom . $adresse . $cp . $ville .$dateEmbauche,true),3,"log.txt");
                $req ="insert into visiteur(id, nom, prenom, adresse, cp, ville, dateEmbauche)
                        values ( :id, :nom, :prenom, :adresse, :cp, :ville,:dateEmbauche )"; 
               
                $stm = self::$monPdo->prepare($req);
                $stm->bindParam(':id', $id);
                $stm->bindParam(':nom', $nom);
                $stm->bindParam(':prenom', $prenom);
                $stm->bindParam(':adresse', $adresse); 
                $stm->bindParam(':cp', $cp); 
                $stm->bindParam(':ville', $ville);
                $stm->bindParam(':dateEmbauche', $dateEmbauche);
                $retour = $stm->execute();
                return $retour; 
        }
        public function getLeVisiteur($idVisiteur){
                $req = "select * from visiteur where id = :idVisiteur";
                $stm = self::$monPdo->prepare($req);
                $stm->bindParam(':idVisiteur', $idVisiteur); 
		$stm->execute();
                $laLigne = $stm->fetch();
                return $laLigne;
        }
        
        public function majVisiteur($id ,$adresse ,$cp ,$ville){
                $req = "update visiteur set adresse = :adresse ,cp = :cp, ";
                $req .= "ville = :ville where id = :idVisiteur";
                $stm = self::$monPdo->prepare($req);
                $stm->bindParam(':idVisiteur', $id);
                $stm->bindParam(':ville', $ville);
                $stm->bindParam(':cp', $cp); 
                $stm->bindParam(':adresse', $adresse); 
                return $stm->execute();
                
        
        }

}   // fin classe
?>


