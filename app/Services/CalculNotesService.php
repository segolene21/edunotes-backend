<?php namespace App\Services;

use Illuminate\Support\Facades\DB;

class CalculNotesService
{

    public function getMoyenneSemestrielle($matricule, $idperiode)
    {
        $matieres = DB::select("
            SELECT m.coefficient, AVG(n.valeur) as moyenne_matiere
            FROM matiere m
            JOIN note n ON n.idmatiere = m.idmatiere
            WHERE n.matricule = ?
            AND n.idperiode = ?
            GROUP BY m.idmatiere, m.coefficient
        ", [$matricule, $idperiode]);

        if (empty($matieres)) return 0;

        $somme_ponderee = 0;
        $somme_coefficients = 0;

        foreach ($matieres as $m) {
            $somme_ponderee += $m->moyenne_matiere * $m->coefficient;
            $somme_coefficients += $m->coefficient;
        }

        return $somme_coefficients > 0
            ? round($somme_ponderee / $somme_coefficients, 2)
            : 0;
    }

    public function getMention($moyenne)
    {
        if ($moyenne >= 16) return "Tres Bien";
        elseif ($moyenne >= 14) return "Bien";
        elseif ($moyenne >= 12) return "Assez Bien";
        elseif ($moyenne >= 10) return "Passable";
        else return "Insuffisant";
    }

    public function getResultat($moyenne, $seuil = 10)
    {
        return $moyenne >= $seuil ? "Admis" : "Recale";
    }

    public function getRang($matricule, $idsalle, $idperiode)
    {
        $classement = DB::select("
            SELECT e.matricule,
                   SUM(n.valeur * m.coefficient) / SUM(m.coefficient) as moyenne
            FROM etudiant e
            JOIN note n ON n.matricule = e.matricule
            JOIN matiere m ON m.idmatiere = n.idmatiere
            WHERE e.idsalle = ?
            AND n.idperiode = ?
            GROUP BY e.matricule
            ORDER BY moyenne DESC
        ", [$idsalle, $idperiode]);

        foreach ($classement as $rang => $etudiant) {
            if ($etudiant->matricule == $matricule) {
                return $rang + 1;
            }
        }
        return null;
    }

    public function getBulletin($matricule, $idsalle, $idperiode)
    {
        $moyenne = $this->getMoyenneSemestrielle($matricule, $idperiode);
        $mention = $this->getMention($moyenne);
        $resultat = $this->getResultat($moyenne);
        $rang = $this->getRang($matricule, $idsalle, $idperiode);

        $notes = DB::select("
            SELECT m.intitule as matiere, m.coefficient,
                   AVG(n.valeur) as moyenne_matiere
            FROM matiere m
            JOIN note n ON n.idmatiere = m.idmatiere
            WHERE n.matricule = ?
            AND n.idperiode = ?
            GROUP BY m.idmatiere, m.intitule, m.coefficient
            ORDER BY m.intitule ASC
        ", [$matricule, $idperiode]);

        return [
            'moyenne_generale' => $moyenne,
            'mention' => $mention,
            'resultat' => $resultat,
            'rang' => $rang,
            'notes_par_matiere' => $notes,
            ];
    }
    public function sauvegarderDeliberation($matricule,$idperiode,$moyenne,$mention,$resultat){
        DB::table('deliberation')->updateOrInsert(
            ['matricule'=>$matricule,'idperiode'=>$idperiode],
            ['moyenne'=>$moyenne,'decision'=>$resultat]
        );
    }

    public function traiterEtudiant($matricule, $idsalle, $idperiode)
    {
        $bulletin = $this->getBulletin($matricule, $idsalle, $idperiode);

        $this->sauvegarderDeliberation(
            $matricule,
            $idperiode,
            $bulletin['moyenne_generale'],
            $bulletin['mention'],
            $bulletin['resultat']
        );

        return $bulletin;
    }
}