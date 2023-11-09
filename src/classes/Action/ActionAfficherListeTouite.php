<?php

namespace touiteur\Action;

use PDO;
use touiteur\Database\ConnectionFactory;

use touiteur\Database\ListeIdTouite;
use touiteur\Database\User;
use touiteur\Renderer\ListeRenderer;
use touiteur\Renderer\TouiteRenderer;

class ActionAfficherListeTouite extends Action
{
    /** @var string $TAG constante pour l'option d'affichage,
     * affiche tout les touites contenant un tag dans le tableau $_GET
     */
    public const TAG = "tag";

    /** @var string $UTILISATEUR constante pour l'option d'affichage
     * affiche tout les touites d'un utilisateur dans le tableau $_GET
     */

    public const UTILISATEUR = "utilisateur";

    /** @var string $DEFAULT constante pour l'option d'affichage,
     * affiche tout les touites par ordre chronologique
     */

    public const DEFAULT = "default";

    /** @var string $option option d'affichage de l'objet, change l'affichage des listes */
    private string $option;

    /**
     * @param string $option option d'affichage de l'objet, definis par les constante de classe
     */
    function __construct(string $option)
    {
        parent::__construct();
        $this->option = $option;

    }

    /**
     * @return string code html de la liste de touite correspondant a l'option d'affichage
     */
    function execute(): string
    {
        $retour = "";

        switch ($this->option) {    //en fonction de l'option choisie pendant la construction
            //les touites affichés seront differents
            case self::TAG:
                $retour .= $this->tag();
                break;
            case self::UTILISATEUR:

                break;

            default:
                $retour .= $this->default();
                break;
        }
        return ($retour);
    }

    /**
     * @return string code html d'une liste de touite du plus récent au plus vieux
     */
    private function default(): string
    {
            $retour = "";
            if(isset($_SESSION['user'])){
                // On fait une immense requête pour traiter chaque sous ensemble par date, puis encore tout l'ensemble
                $requeteGen = <<<SQL
SELECT idTouite 
FROM (
    SELECT TOUITE.idTouite as idTouite, TOUITE.date as dateTouite
    FROM SUIVREUSER, UTILISATEUR, TOUITE
    WHERE SUIVREUSER.idUser=UTILISATEUR.idUser 
        AND TOUITE.idUser=SUIVREUSER.idUserSuivi 
        AND UTILISATEUR.idUser=? 
    UNION
    SELECT DISTINCT TOUITE.idTouite as idTouite, TOUITE.date as dateTouite
    FROM SUIVRETAG, TAG2TOUITE, TOUITE, UTILISATEUR
    WHERE SUIVRETAG.idUser=UTILISATEUR.idUser 
        AND TAG2TOUITE.idTag=SUIVRETAG.idTag 
        AND TOUITE.idTouite=TAG2TOUITE.idTouite 
        AND UTILISATEUR.idUser=?
)as listeTouites 
ORDER BY dateTouite DESC;
SQL;
                $res = ConnectionFactory::$db->prepare($requeteGen);
                $idUserCourant = User::getIdSession();
                $res->bindParam(1, $idUserCourant);
                $res->bindParam(2, $idUserCourant);
                $res->execute();

                $resultat = [];
                while($row = $res->fetch(PDO::FETCH_ASSOC)){
                    $resultat[] = $row['idTouite'];
                }




        }else{
                //requete sql qui vas selectionner les idTouite par ordre decroissant sur la date
                $query = "SELECT idTouite FROM `TOUITE` order by date desc";

                $resultat = ListeIdTouite::listeTouite($query, []); //sous traite la requete a une autre classe


            }


        $retour .= ListeRenderer::render($resultat, TouiteRenderer::COURT); //on fait le rendu html de la liste
        return ($retour);

    }

    /**
     * @return string liste de touite correspondant au tag en GET
     */
    private function tag():string{
        $retour = "";


        //requete sql qui vas selectionner les idTouite par ordre decroissant sur la date
        $query = "SELECT TOUITE.idTouite FROM `TOUITE`,`TAG2TOUITE`,`TAG` 
                WHERE TOUITE.idTouite=TAG2TOUITE.idTouite 
                  and TAG.idTag=TAG2TOUITE.idTag and TAG.libelle=?";

        $tag="#".$_GET["tag"];

        $resultat = ListeIdTouite::listeTouite($query, [$tag]); //sous traite la requete a une autre classe

        $retour .= ListeRenderer::render($resultat, TouiteRenderer::COURT); //on fait le rendu html de la liste de touite correspondant au ids données


        return ($retour);
    }

}