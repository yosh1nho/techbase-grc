<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TreatmentPlanController extends Controller
{
    public function store(Request $req)
    {
        $id = DB::table('risktreatmentplan')->insertGetId([
            'id_risk' => $req->risk_id,
            'strategy' => $req->strategy,
            'owner_id' => $req->owner,
            'due_date' => $req->due,
            'status' => 'To do',
            'created_at' => now()
        ], 'id_plan');

        return response()->json([
            'success' => true,
            'id' => $id
        ]);
    }
}
