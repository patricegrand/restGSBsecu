<?php

require_once("rest.php");
require_once("pdogsbrapports.php");

/**
* @class RestGSB

* Cette classe permet de :
*    + identifier la ressource visée
*    + procéder à l'analyse de la représentation de la ressource
*    + procéder à la résolution de l'action demandée
*    + générer la réponse
*/

class RestGSB extends Rest {
    /**
     * Instance PDO qui représente la connexion à la base de données
     */
    private $pdo;

    /**
     * Représentation de la ressource demandée qui sera incluse dans la réponse
     */
    private $data;

    /**
     * Constructeur de la classe.
     *
     * Analyse la ressource demandée et détermine le point de terminanison (endpoint)
     * et l'identifiant éventuel de la ressource
     */
    public function __construct() {
        // Appel du constructeur de la classe mère
        parent :: __construct();
      
        /* Extrait, de la ressource demandée, le point de terminaison (endpoint) et
           l'identifiant éventuel de la ressource.
           La ressource doit être de type "endpoint" ou "endpoint/{id}".
           Ces informations sont stockées dans le tableau $request. */
        $tab = Array();
        $tab = explode('/', $this->request['ressource']);
        //error_log(print_r($this->request,true),3,"log.txt"); 
         /* La première partie correspond au endpoint  */
        if (array_key_exists(0, $tab))
            $this->request['endpoint'] = array_shift($tab);
        /* La seconde partie, si elle existe et est numérique,
           correspond à l'identifiant de la ressource (id) */
        if (array_key_exists(0, $tab) && is_numeric($tab[0]))
            $this->request['id'] = array_shift($tab);

        // Connexion à la base de données
       
        $this->pdo = $this->dbConnect();
     
    }

    /**
     * Connexion à la base de données.
     * Si la connexion ne peut pas être établie, une erreur 503 est retournée
     *
     * @return  instance pdo représentant la connexion à la base de données
     */
    private function dbConnect() {
       
        $pdo = null;

        try {
            $pdo =  PdoGsbRapports::getPdo();
           
        }
        catch (Exception $e) {
            $this->response('', 503);  //Service Unavailable
        }
        return $pdo;
    }

    /**
     * Méhode publique d'accès au web service REST.
     *
     * Cette méthode vérifie la cohérence des uri : cohérence entre endpoint, méthode,
     * identifiant ressource et paramètres attendus.
     * Si la demande n'est pas cohérente, une erreur 400 (Bad request) est retournée.
     * Si la demande est cohérente, appel de la méthode qui réalise le traitement demandé.
     *
     */
    public function process() {
        $this->codeRetour = 200;

        // Stocke les paramètres de la requête et l'identifiant de la ressource dans $args
        // $args contient les paramètres qui seront nécessaires à l'exécution des méthodes
        
        $args = array();
        foreach ($this->request as $k => $v) {
            if ($k != 'ressource' && $k != 'endpoint')
                $args[$k] = $v;
        }
      //  error_log(print_r($args,true),3,"log.txt");
       // error_log(print_r("methode : " . $this->method,true),3,"log.txt");
       // error_log(print_r("endpoint : " . $this->request['endpoint'],true),3,"log.txt");
     
        /*
        * Détermine le traitement à exécuter (méthode) selon l'uri (ressource) et l'action
        * (get, poste put, delete) demandées
        * Si l'uri n'est pas correcte, on retourne le status 'Bad request'
        */
        switch ($this->request['endpoint']) {
        // GET : ce service s'appellera à partir d'une URI de la forme :
        // .../restGSB/medecins?ticket=<valeur du ticket>&nom=tre
        // ou tre est le début du nom du médecin 
        // POST :  pour un update 
        // .../restGSB/medecins?ticket=<ticket>&id=<id>&tel=<tel>&specialite=<specialite>&adresse=<adresse>
            case "medecins" :
            
                switch ($this->method) {
                    case 'GET':
                   // error_log(print_r("passage dans les medecins",true),3,"log.txt");
                        $this->request['fonction'] = "getLesMedecins";
                        break;
                    case 'POST':
                    //    error_log(print_r("passage dans le PUT ",true),3,"log.txt");
                        $this->request['fonction'] = "ajouterMedecin";
                        break;
                }
            break;
      // ce service s'appellera à partir d'une URI de la forme .../restGSB/medecin/123 
      // où 123 est l'id du médecin      
            case "medecin" :
                if ( !isset($args['id']) ) {  // l'id de la ressource DOIT être renseigné
                    $this->response("", 400); // Bad Request
                }
                else { 
                    switch ($this->method) {
                        case 'GET':
                            $this->request['fonction'] = "getLeMedecin";
                        break;
                        case 'POST':
                            $this->request['fonction'] = "majMedecin";
                        break;
                    } 
                }
               
                break;
            case "familles" :
           // error_log(print_r("passage dans la famille",true),3,"log.txt");
                switch ($this->method) {
                    case 'GET':
                        $this->request['fonction'] = "getLesFamilles";
                    break;
                    case 'POST' :
                        $this->request['fonction'] = "ajouterFamille";
                    break;
                }
            break;
            case "famille" :
           // error_log(print_r("passage dans la famille",true),3,"log.txt");
                switch ($this->method) {
                    case 'GET':
                        $this->request['fonction'] = "getLaFamille";
                    break;
                    case 'POST' :
                        $this->request['fonction'] = "majFamille";
                    break;
                }
            break;
            case "medicaments" :
         //   error_log(print_r("passage dans les medocs",true),3,"log.txt");
                switch ($this->method) {
                    case 'GET':
                        $this->request['fonction'] = "getLesMedicaments";
                        break;
                    case 'POST':
                    //    error_log(print_r("passage dans le PUT ",true),3,"log.txt");
                        $this->request['fonction'] = "ajouterMedicament";
                    break;
                }
            break;
            case "medicament" :
                    switch ($this->method) {
                        case 'GET':
                            $this->request['fonction'] = "getLeMedicament";
                        break;
                        case 'POST':
                            $this->request['fonction'] = "majMedicament";
                        break;
                    } 
            break;
            
            case "visiteurs" :
         //   error_log(print_r("passage dans le cas visteurs ",true),3,"log.txt");
                switch ($this->method) {
                    case 'GET':
                        $this->request['fonction'] = "getLesVisiteurs";
                        break;
                    case 'POST':
                    //    error_log(print_r("passage dans le PUT ",true),3,"log.txt");
                        $this->request['fonction'] = "ajouterVisiteur";
                break;
                }
            break;
            case "visiteur" :
                    switch ($this->method) {
                        case 'GET':
                            $this->request['fonction'] = "getLeVisiteur";
                        break;
                        case 'POST':
                            $this->request['fonction'] = "majVisiteur";
                        break;
                    } 
            break;
            case "rapports" :
                switch ($this->method) {
                    case 'GET':
                        $this->request['fonction'] = "getLesRapports";
                        break;
                    case 'POST':
                        $this->request['fonction'] = "ajouterRapport";
                break;
                }
            break;
            case "rapport" :
                if ( !isset($args['id']) ) {  // l'id de la ressource DOIT être renseigné
                    $this->response("", 400); // Bad Request
                }
                else { 
                    switch ($this->method) {
                        case 'GET':
                            $this->request['fonction'] = "getLeRapport";
                        break;
                        case 'POST':
                            $this->request['fonction'] = "majRapport";
                        break;
                    }
                } 
            break;
            case 'login':
                if ( isset($args['id']) )   // l'id de la ressource NE DOIT PAS être renseigné
                    $this->response("", 400); // Bad Request
                else   // Seules la méthode GET est autorisée
                    if ($this->method == 'GET') 
                        $this->request['fonction'] = "getTicket";
                    else 
                        $this->response("", 400); // Bad Request
            break;        
            case 'connexion':
             
               if ( isset($args['id']) ) {  // l'id de la ressource NE DOIT PAS être renseigné
                    $this->response("", 400); // Bad Request
                }
                else {  // Seules la méthode GET est autorisée
                    if ($this->method == 'GET') {
                        $this->request['fonction'] = "connexion";
                    } 
                    else {
                        $this->response("", 400); // Bad Request
                    }
                }
                break;
           default:
         }

        // Exécute la méthode (fonction) correspondant à la ressource demandée
        // Si la méthode n'existe pas, une erreur 501 est retournée
        $func = $this->request['fonction'];
       // error_log(print_r("nom de la fonction" . $func,true),3,"log.txt");
        if ((int) method_exists($this, $func) > 0) {  // Vérifie si la méthode existe
            // Exécute la méthode correspondant au traitement demandé
            $this->$func($args);
            
        }
        else {
            $this->response("", 501);  
 
        }
    //    error_log(print_r("data:" . $this->data,true),3,"log.txt");
        $this->response($this->data, $this->codeRetour);
    }
    /*------------------------------------------------------------------------------------*/
    /*-----------------------------------------LES FONCTIONS------------------------------*/
    /*------------------------------------------------------------------------------------*/
    private function getTicket($args){
         $login = $args['login'];
         $ticket = $this->pdo->existeLogin($login);
         if($ticket != null){
               $this->data = json_encode($ticket);
         }
         else{
             $this->data="";
        }

    }
    
    private function connexion($args){
        $login = $args['login'];
        $mdp = $args['mdp'];
        $secretaire = $this->pdo->verifierSecretaire($login, $mdp);
        $ticket = $secretaire['ticket'];
        if($ticket != NULL){
                   $newTicket = $this->pdo->setTicket($ticket);
                   $secretaire['ticket'] = $newTicket;
                   $this->data = json_encode( $secretaire);
        }
         else{
             $this->data="erreur de connexion";
             $this->codeRetour=400;
         }
    }
    
     
    /*-----------------------------------------Gestion des medecins-----------------------------*/
    private function getLesMedecins($args){
        $ticket = $args['ticket'];
        $idSecretaire = $this->pdo->estValide($ticket);
        if($idSecretaire != ""){
            if(isset($args['nom'])){
                $nom = $args['nom'];
                $lesLignes = array();
                $lesMedecins = $this->pdo->getLesMedecins($nom);
                $lesLignes['medecins'] =  $lesMedecins;
                $newTicket = $this->pdo->setTicket($idSecretaire);
                $lesLignes['ticket'] = $newTicket;
                $this->data = json_encode( $lesLignes);
            }
            else{
                $this->data="Pas de ressource";
                $this->codeRetour=404;
            }
        }
        else{
            $this->data="pas connecté";
            $this->codeRetour=401;
       }
    }  
    private function getLeMedecin($args){
        $ticket = $args['ticket'];
        $nbArgs = count($args);
        $idSecretaire = $this->pdo->estValide($ticket);
         if($idSecretaire !=""){
            if(isset($args['id']) && $nbArgs == 2){
                $id = $args['id'];
                $laLigne = array();
                $laLigne['medecin'] = $this->pdo->getLeMedecin($id);
                $newTicket = $this->pdo->setTicket($idSecretaire);
                $laLigne['ticket'] = $newTicket;
                $this->data = json_encode( $laLigne);
            }
            else{
                $this->data="Pas de ressource";
                $this->codeRetour=404;
            }
        }
        else{
            $this->data="pas connecté";
            $this->codeRetour=401;
        }
         
    }   
    private function majMedecin($args){
            $ticket = $args['ticket'];
            $idSecretaire = $this->pdo->estValide($ticket);
            if($idSecretaire != "" ){
             // error_log(print_r("ticket valide dans majMedecin",true),3,"log.txt");
                $id = $args['id'];
                $adresse = $args['adresse'];
                $tel = $args['tel'];
                $specialite = $args['specialite'];
                $departement = $args['departement'];
                $retour = $this->pdo->majMedecin($id ,$adresse ,$tel ,$specialite, $departement);
                if($retour){
                    $newTicket = $this->pdo->setTicket($idSecretaire);
                    $this->data =  $newTicket;
                }
                else{
                    $this->data ="Maj non effectuée";
                    $this->codeRetour=500;
                } 
               
            }
            else{
                $this->data ="pas connecté";
                $this->codeRetour=200;

         }
    }
    private function ajouterMedecin($args){
        //error_log(print_r( $args,true),3,"log.txt");
        $ticket = $args['ticket'];
        $idSecretaire = $this->pdo->estValide($ticket);
        if($idSecretaire != ""){
            $nom = $args['nom'];
            $prenom = $args['prenom'];
            $adresse = $args['adresse'];
            $tel = $args['tel'];
            $departement = $args['departement'];
            $specialite = $args['specialite'];
            $retour = $this->pdo->ajouterMedecin($nom ,$prenom, $adresse ,$tel ,$specialite,$departement);
            if($retour){
                $newTicket = $this->pdo->setTicket($idSecretaire);
                $this->data =  $newTicket;
            }
            else{
                $this->data ="Ajout non effectué";
                $this->codeRetour=500;
            } 
        }
        else{
            $this->data ="pas connecté";
            $this->codeRetour=200;
        }
    } 
    /*---------------------------------------------Gestion des rapports----------------------*/
    private function getLeRapport($args){
        $ticket = $args['ticket'];
        $nbArgs = count($args);
        $idSecretaire = $this->pdo->estValide($ticket);
        if($idSecretaire != ""){
            if(isset($args['id']) &&  $nbArgs==2){
                $laLigne = array();
                $id = $args['id'];
                $laLigne['rapport'] = $this->pdo->getLeRapport($id);
                $newTicket = $this->pdo->setTicket($idSecretaire);
                $laLigne['ticket'] = $newTicket;
                $this->data = json_encode( $laLigne);
            }
            else{
                $this->data="Pas de ressource";
                $this->codeRetour=404;
            }
        }   
        else{
                $this->data="pas connecté";
                $this->codeRetour=401;
        }
    } 
    private function getLesRapports($args){
         $ticket = $args['ticket'];
         $idSecretaire = $this->pdo->estValide($ticket);
         if($idSecretaire != ""){
            //error_log(print_r("ticket valide dans rapports" ,true),3,"log.txt");
                $nbArgs = count($args);
                $lesLignes = array();
                $ok = false;
                switch($nbArgs){
                    case 2 :
                        if(isset($args['idMedecin'])){
                           
                            $idMedecin = $args['idMedecin'];
                            error_log(print_r("idMedecin dans le rest:".$idMedecin ,true),3,"log.txt");
                            $lesRapports = $this->pdo->getLesRapportsParMedecin($idMedecin);
                            $ok = true;
                        }
                        if(isset($args['idVisiteur'])){
                            $idVisiteur = $args['idVisiteur'];
                            $lesRapports = $this->pdo->getLesRapportsParVisiteur($idVisiteur);
                            $ok = true;
                        } 
                    break;
                    case 3:
                        if(isset($args['dateDebut']) && isset($args['dateDebut'])){    
                            $dateDebut = $args['dateDebut'];
                            $dateFin = $args['dateFin'];
                            $lesRapports = $this->pdo->getLesRapportsEntreDeuxDates( $dateDebut, $dateFin);
                            $ok = true;
                        }
                    break;
                    case 4:
                        if(isset($args['dateDebut']) && isset($args['dateDebut']) && isset($args['idVisiteur'])){
                            $dateDebut = $args['dateDebut'];
                            $dateFin = $args['dateFin'];
                            $idVisiteur = $args['idVisiteur'];
                            $lesRapports = $this->pdo->getLesRapportsEntreDeuxDatesUnVisiteur($idVisiteur, $dateDebut, $dateFin);
                            $ok = true;
                        }
                    break;
                }
                if($ok){
                    $newTicket = $this->pdo->setTicket($idSecretaire);
                    $lesLignes['rapports'] = $lesRapports;
                    $lesLignes['ticket'] = $newTicket;
                    $this->data = json_encode( $lesLignes);
                    }
                else{
                    $this->data ="pas de ressource";
                    $this->codeRetour=401;
                }
         }
         else{
                $this->data ="pas connecté";
                $this->codeRetour=401;

         }
    } 
    private function majRapport($args){
         $ticket = $args['ticket'];
         $idSecretaire = $this->pdo->estValide($ticket);
         if($idSecretaire != ""){
            $idRapport = $args['id'];
            $bilan = $args['bilan'];
            $motif = $args['motif'];
            $retour = $this->pdo->majRapport($idRapport,$motif,$bilan);
                if($retour){
                    $newTicket = $this->pdo->setTicket($idSecretaire);
                    $this->data =  $newTicket;
                }
                else{
                    $this->data ="Maj non effectuée";
                    $this->codeRetour=500;
                } 
            }
            else{
                $this->data ="";
                $this->codeRetour=400;
             }
    } 
    
    private function ajouterRapport($args){
        $ticket = $args['ticket'];
        $idSecretaire = $this->pdo->estValide($ticket);
        if($idSecretaire != ""){
         //   error_log(print_r("ajouter rapport" .$args ,true),3,"log.txt");
        //    $idVisiteur =  $this->pdo->getIdVisiteur($ticket);
            $idVisiteur = $args['idVisiteur'];
            $idMedecin =  $args['idMedecin'];
            $motif =  $args['motif'];
            $bilan =  $args['bilan'];
            $date =  $args['date'];
            $medicaments = $args['medicaments'];
            $retour = $this->pdo->ajouterRapport($idMedecin ,$idVisiteur ,$bilan ,$motif ,$date ,$medicaments);
            if($retour){
                $newTicket = $this->pdo->setTicket($idSecretaire);
                $this->data =  $newTicket;
            }
            else{
                $this->data ="Ajout non effectué";
                $this->codeRetour=500;
            } 
        }
        else{
            $this->data ="";
            $this->codeRetour=400;

        }
    }
    /*---------------------------------------------Gestion des médicaments--------------------*/
     private function getLesMedicaments($args){
          $ticket = $args['ticket']; // couple MD5(mdp + ticket )
          $idSecretaire = $this->pdo->estValide($ticket);
          if($idSecretaire != ""){
                $nbArgs = count($args);
                $lesLignes = array();
                $ok = false;
                switch( $nbArgs){
                    case 1:
                        $lesMedicaments = $this->pdo->getLesMedicaments();
                        $ok = true;
                    break;
                    case 2:
                        if(isset($args['idFamille'])){
                            $idFamille = $args['idFamille'];
                            $lesMedicaments = $this->pdo->getLesMedicamentsParFamille($idFamille);
                            $ok = true; 
                        }
                        if(isset($args['nom'])){
                            $nom = $args['nom'];
                            $lesMedicaments = $this->pdo->getLesMedicamentsParNom($nom);
                            $ok = true;
                        }
                    break;
                    case 3:
                        if(isset($args['dateDebut']) && isset($args['dateFin'])){
                            $dateDebut = $args['dateDebut'];
                            $dateFin = $args['dateFin'];
                            $lesMedicaments = $this->pdo->getLesMedicamentsEntreDates($dateDebut, $dateFin);
                            $ok = true;
                        }
                    break;
                }
                if($ok){
                    $lesLignes['medicaments'] =  $lesMedicaments;
                    $newTicket = $this->pdo->setTicket($idSecretaire);
                    $lesLignes['ticket'] = $newTicket;
                    $this->data =json_encode( $lesLignes);
                }
                else{
                    $this->data="Pas de ressource";
                    $this->codeRetour=404;
                }
        }
        else{
            $this->data="pas connecté";
             $this->codeRetour=401;
        }
    }
    private function ajouterMedicament($args){
        $ticket = $args['ticket'];
        $idSecretaire = $this->pdo->estValide($ticket);
       // error_log(print_r( $args,true),3,"log.txt");
        if($idSecretaire != ""){
            $id = $args['idMedicament'];
            $nom = $args['nomCommercial'];
            $effets = $args['effets'];
            $contreIndics = $args['contreIndications'];
            $compo = $args['composition'];
            $idFamille = $args['idFamille'];
            $retour  = $this->pdo->ajouterMedicament($id, $nom, $effets, $contreIndics, $compo, $idFamille);
            if($retour){
                $newTicket = $this->pdo->setTicket($idSecretaire);
                $this->data =  $newTicket;
            }
            else{
                $this->data ="Ajout non effectué";
                $this->codeRetour=500;
            }
        } 
        else{
            $this->data ="pas connecté";
            $this->codeRetour=401;
        }

    }
    private function getLeMedicament($args){
        $ticket = $args['ticket'];
        $idSecretaire = $this->pdo->estValide($ticket);
        $nbArgs = count($args);
        if($idSecretaire != ""){
            //error_log(print_r("ticket valide",true),3,"log.txt");
            if(isset($args['idMedicament'])&& $nbArgs == 2){
                $laLigne = array();
                $idMedicament = $args['idMedicament'];
                $laLigne['medicament'] = $this->pdo->getLeMedicament($idMedicament);
                $newTicket = $this->pdo->setTicket($idSecretaire);
                $laLigne['ticket'] = $newTicket;
                $this->data = json_encode( $laLigne);
            }
            else{
                $this->data="pas de ressource";
                $this->codeRetour=404;
            }
        }
        else{
            $this->data="pas connecté";
            $this->codeRetour=401;
        }
         
    }   
    private function majMedicament($args){
        //error_log(print_r($args,true),3,"log.txt");
        $ticket = $args['ticket'];
        $idSecretaire = $this->pdo->estValide($ticket);
        if($idSecretaire != ""){
         // error_log(print_r("ticket valide dans majMedecin",true),3,"log.txt");
            $id = $args['idMedicament'];
            $effets = $args['effets'];
            $contreIndics = $args['contreIndications'];
            $compo = $args['composition'];
            $retour = $this->pdo->majMedicament($id ,$effets, $contreIndics, $compo);
            if($retour){
                $newTicket = $this->pdo->setTicket($idSecretaire);
                $this->data =  $newTicket;
            }
            else{
                $this->data ="maj non effectuée";
                $this->codeRetour=500;
            } 
           
        }
        else{
            $this->data ="pas connecté";
            $this->codeRetour=401;

        }
    }
    /*-----------------------------------------Gestion des familles-----------------------------------------*/
    private function getLesFamilles($args){
        $ticket = $args['ticket'];
        $nbArgs = count($args);
        $idSecretaire = $this->pdo->estValide($ticket);
        if($idSecretaire != ""){
            if($nbArgs == 1){
                $lesLignes = array();
                $lesFamilles = $this->pdo->getLesFamilles();
                $lesLignes['familles'] =  $lesFamilles;
                $newTicket = $this->pdo->setTicket($idSecretaire);
                $lesLignes['ticket'] = $newTicket;
                $this->data = json_encode( $lesLignes);
            }
            else{
                $this->data="pas de resource";
                $this->codeRetour=404;
            }

        }
        else{
            $this->data="pas connecté";
             $this->codeRetour=401;
        }
    }
    private function getLafamille($args){
        $ticket = $args['ticket'];
        $nbArgs = count($args);
        $idSecretaire = $this->pdo->estValide($ticket);
        if($idSecretaire != ""){
           if(isset($args['idFamille'])&& $nbArgs == 2){
                $laLigne = array();
                $idFamille = $args['idFamille'];
                $laLigne['famille'] = $this->pdo->getLaFamille($idFamille);
                $newTicket = $this->pdo->setTicket($idSecretaire);
                $laLigne['ticket'] = $newTicket;
                $this->data = json_encode( $laLigne);
            }
            else{
                $this->data="pas de ressource";
                $this->codeRetour=404;
            }
        }
        else{
            $this->data="pas connecté";
            $this->codeRetour=401;
        }
         
    }   
    private function ajouterFamille($args){
        //error_log(print_r( "passage dans ajouter famille debut:" . $retour,true),3,"log.txt");
        $ticket = $args['ticket'];
        $idSecretaire = $this->pdo->estValide($ticket);
        if($idSecretaire != ""){
            $idFamille = $args['idFamille'];
            $libelle = $args['libelle'];
            $retour = $this->pdo->ajouterFamille($idFamille, $libelle);
          //  error_log(print_r( "passage dans ajouter famille:" . $retour,true),3,"log.txt");
            if($retour){
                $newTicket = $this->pdo->setTicket($idSecretaire);
                $this->data =  $newTicket;
            }
            else{
                $this->data ="Ajout non effectué";
                $this->codeRetour=500;
            }    
        }
        else{
          $this->data ="pas connecté";
          $this->codeRetour=401;
      }
    }
    private function majFamille($args){
        $ticket = $args['ticket'];
        $idSecretaire = $this->pdo->estValide($ticket);
        if($idSecretaire != ""){
            $idFamille = $args['idFamille'];
            $libelle = $args['libelle'];
            $retour = $this->pdo->majFamille($idFamille, $libelle);
            if($retour){
                $newTicket = $this->pdo->setTicket($idSecretaire);
                $this->data =  $newTicket;
              //  error_log(print_r("ticket:".$newTicket,true),3,"log.txt");
            }
            else{
                $this->data ="Ajout non effectué";
                $this->codeRetour=500;
            }    
        }
        else{
          $this->data ="pas connecté";
          $this->codeRetour=401;
      }
    }

    
/*-----------------------------------------Gestion des visiteurs----------------------------*/ 
private function getLesVisiteurs($args){
    $ticket = $args['ticket'];
    $idSecretaire = $this->pdo->estValide($ticket);
    if($idSecretaire != ""){
          $lesLignes = array();
          $nom ="";
          if(isset ($args['nom'])){
                $nom = $args['nom'];
          }
          if($nom == "" || isset ($args['nom'])  ){
              $lesVisiteurs = $this->pdo->getLesVisiteurs($nom);
              $lesLignes['visiteurs'] =  $lesVisiteurs;
              $newTicket = $this->pdo->setTicket($idSecretaire);
              $lesLignes['ticket'] = $newTicket;
              $this->data =json_encode( $lesLignes);
          }
          else{
            $this->data="pas de ressource";
            $this->codeRetour=404;
          }
 //  error_log(print_r( $lesLignes,true),3,"log.txt");
  }
  else{
      $this->data="pas connecté";
       $this->codeRetour=401;
      
  }
}
private function ajouterVisiteur($args){
    $ticket = $args['ticket'];
    $idSecretaire = $this->pdo->estValide($ticket);
    if($idSecretaire != ""){
        //error_log(print_r( "ticket valide",true),3,"log.txt");
        $id = $args['idVisiteur'];
        $nom = $args['nom'];
        $prenom = $args['prenom'];
        $ville = $args['ville'];
        $adresse = $args['adresse'];
        $cp = $args['cp'];
        $dateEmbauche = $args['dateEmbauche'];
        $retour = $this->pdo->ajouterVisiteur($id, $nom, $prenom, $adresse, $cp, $ville, $dateEmbauche);
        if($retour){
            $newTicket = $this->pdo->setTicket($idSecretaire);
            $this->data =  $newTicket;
        }
        else{
            $this->data ="Ajout non effectué";
            $this->codeRetour=500;
        }    
    }
    else{
      $this->data ="pas connecté";
      $this->codeRetour=401;
  }

}
private function getLeVisiteur($args){
    $ticket = $args['ticket'];
    $idSecretaire = $this->pdo->estValide($ticket);
    if($idSecretaire != ""){
       // error_log(print_r("ticket valide",true),3,"log.txt");
        $laLigne = array();
        $nbArgs = count($args);
        if(isset($args['idVisiteur']) && $nbArgs == 2){
            $idVisiteur = $args['idVisiteur'];
            $laLigne['visiteur'] = $this->pdo->getLeVisiteur($idVisiteur);
            $newTicket = $this->pdo->setTicket($idSecretaire);
            $laLigne['ticket'] = $newTicket;
            $this->data = json_encode( $laLigne);
        }
        else{
            $this->data="erreur de requête";
            $this->codeRetour=404;
        }
      }
      else{
        $this->data="pas connecté";
        $this->codeRetour=401;
      }
   
}   
private function majVisiteur($args){
    //error_log(print_r($args,true),3,"log.txt");
    $ticket = $args['ticket'];
    $idSecretaire = $this->pdo->estValide($ticket);
    if($idSecretaire != ""){
   // error_log(print_r("ticket valide dans majMedecin",true),3,"log.txt");
        $id = $args['idVisiteur'];
        $adresse = $args['adresse'];
        $cp = $args['cp'];
        $ville = $args['ville'];
        $retour = $this->pdo->majVisiteur($id ,$adresse ,$cp ,$ville);
        if($retour){
            $newTicket = $this->pdo->setTicket($idSecretaire);
            $this->data =  $newTicket;
        }
        else{
            $this->data ="maj non effectuée";
            $this->codeRetour=500;
        } 
    }
    else{
        $this->data="pas connecté";
        $this->codeRetour=200;
    }
}   
   
}
