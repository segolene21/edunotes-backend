<?php
namespace App\Http\Controllers;

use App\Services\CalculNotesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BulletinController extends Controller
{
    protected $calcul;

    public function __construct(CalculNotesService $calcul)
    {
        $this->calcul = $calcul;
    }

    public function getBulletin($matricule, $niveau, $idperiode)
    {
        $etudiant=DB::table('etudiant')
                    ->where('matricule',$matricule)
                    ->first();
        if (!$etudiant){
            return response()->json(['erreur' =>"L'ecolier(e)/eleve n'est probablement pas dans cette classe,veuillez bien vous renseignez sur votre niveau/celui de votre enfant.THANK YOU!"],404);
        }
        $salle =DB::table('salle')
                  ->where('niveau',$niveau )
                  ->first();
        if (!$salle) {
            return response()->json(['erreur'=>'Classe non trouvee'],404);
            }   
        if ($etudiant->idsalle !=$salle->idsalle)   {
            return response()->json(['erreur'=>"Cet etudiant n'appartient pas a la classe $niveau!"],403);
        }

        $bulletin = $this->calcul->traiterEtudiant($matricule, $etudiant->idsalle, $idperiode);
        $bulletin['nom']=$etudiant->nom;
        $bulletin['prenom']=$etudiant->prenom;
        return response()->json($bulletin);
    }
    public function statistiques(){
        $classes =DB::table('etudiant')->join('note','etudiant.matricule','=','note.matricule')->select('etudiant.classe', 
        DB::raw('ROUND(AVG(note.valeur),2) as taux'))
                ->groupBy('etudiant.classe')
                ->get();
            return response()->json($classes);
    }

}