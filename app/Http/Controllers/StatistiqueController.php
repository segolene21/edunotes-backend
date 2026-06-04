<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class StatistiqueController extends Controller
{
    public function getStats()
    {
        $stats = DB::select("
            SELECT s.nom_salle as classe,
                   COUNT(DISTINCT e.matricule) as total_eleves,
                   AVG(n.valeur) as moyenne,
                   SUM(CASE WHEN n.valeur >= 10 THEN 1 ELSE 0 END) * 100.0 / COUNT(n.idnote) as taux_reussite
            FROM salle s
            JOIN etudiant e ON e.idsalle = s.idsalle
            JOIN note n ON n.matricule = e.matricule
            GROUP BY s.idsalle, s.nom_salle
            ORDER BY s.nom_salle
        ");

        return response()->json($stats);
    }
}