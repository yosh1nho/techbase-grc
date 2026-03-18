<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RiskController extends Controller
{
    // SELECT — inclui dados do último assessment (probability, impact, score)
    public function index()
    {
        // Subquery para obter o id do assessment mais recente de cada risco
        $latestAssessment = DB::raw('(
            SELECT DISTINCT ON (id_risk)
                id_risk, probability, impact, score, assessedat
            FROM riskassessmenthistory
            ORDER BY id_risk, assessedat DESC
        ) AS latest_assessment');

        $risks = DB::table('risk')
            ->leftJoin('asset', 'risk.id_asset', '=', 'asset.id_asset')
            ->leftJoin($latestAssessment, 'risk.id_risk', '=', 'latest_assessment.id_risk')
            ->select(
                'risk.id_risk',
                'risk.id_asset',
                'risk.title',
                'risk.description',
                'risk.status',
                'risk.origin',
                'risk.threat',
                'risk.vulnerability',
                'risk.risk_owner',
                'risk.actions',
                'risk.due',
                'risk.createdat',
                'asset.hostname',
                'latest_assessment.probability',
                'latest_assessment.impact',
                'latest_assessment.score'
            )
            ->get();

        return response()->json($risks);
    }

    // SHOW — detalhes de um risco individual
    public function show($id)
    {
        $latestAssessment = DB::raw('(
            SELECT DISTINCT ON (id_risk)
                id_risk, probability, impact, score, assessedat
            FROM riskassessmenthistory
            ORDER BY id_risk, assessedat DESC
        ) AS latest_assessment');

        $risk = DB::table('risk')
            ->leftJoin('asset', 'risk.id_asset', '=', 'asset.id_asset')
            ->leftJoin($latestAssessment, 'risk.id_risk', '=', 'latest_assessment.id_risk')
            ->where('risk.id_risk', $id)
            ->select(
                'risk.id_risk',
                'risk.id_asset',
                'risk.title',
                'risk.description',
                'risk.threat',
                'risk.vulnerability',
                'risk.risk_owner',
                'risk.actions',
                'risk.due',
                'risk.status',
                'risk.origin',
                'risk.createdat',
                'asset.hostname',
                'latest_assessment.probability',
                'latest_assessment.impact',
                'latest_assessment.score'
            )
            ->first();

        if (!$risk) {
            return response()->json(['error' => 'Risk not found'], 404);
        }

        return response()->json($risk);
    }

    // CREATE
    public function store(Request $request)
    {
        $data = $request->all();

        $asset = DB::table('asset')
            ->where('hostname', $data['asset'])
            ->first();

        if (!$asset) {
            return response()->json(['error' => 'Asset not found'], 404);
        }

        DB::beginTransaction();

        try {

            $riskId = DB::table('risk')->insertGetId([
                'id_asset' => $asset->id_asset,
                'title' => $data['title'],
                'description' => $data['description'],
                'threat' => $data['threat'] ?? null,
                'vulnerability' => $data['vulnerability'] ?? null,
                'risk_owner' => $data['risk_owner'] ?? null,
                'actions' => $data['actions'] ?? null,
                'due' => $data['due'] ?? null,
                'status' => $data['status'] ?? 'Aberto',
                'origin' => $data['origin'] ?? 'manual',
                'createdby' => 1
            ], 'id_risk');

            $probability = $data['probability'] ?? 3;
            $impact = $data['impact'] ?? 3;
            $score = $probability * $impact;

            DB::table('riskassessmenthistory')->insert([
                'id_risk' => $riskId,
                'probability' => $probability,
                'impact' => $impact,
                'score' => $score,
                'assessed_by' => 1,
                'assessedat' => now()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'id_risk' => $riskId
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // UPDATE
    public function update(Request $request, $id)
    {
        $data = $request->all();

        // Separar campos do risk dos campos do assessment
        $riskFields = [];
        $assessmentFields = [];

        foreach ([
                'title',
                'description',
                'threat',
                'vulnerability',
                'risk_owner',
                'actions',
                'due',
                'status',
                'origin'
                ] as $field) {
            if (isset($data[$field])) {
                $riskFields[$field] = $data[$field];
            }
        }

        if (isset($data['probability']) || isset($data['impact'])) {
            $probability = $data['probability'] ?? 3;
            $impact = $data['impact'] ?? 3;
            $assessmentFields = [
                'id_risk' => $id,
                'probability' => $probability,
                'impact' => $impact,
                'score' => $probability * $impact,
                'assessed_by' => 1,
                'assessedat' => now()
            ];
        }

        DB::beginTransaction();
        try {
            if (!empty($riskFields)) {
                DB::table('risk')
                    ->where('id_risk', $id)
                    ->update($riskFields);
            }

            if (!empty($assessmentFields)) {
                DB::table('riskassessmenthistory')->insert($assessmentFields);
            }

            DB::commit();

            return response()->json([
                'message' => 'Risk updated'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // DELETE
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            // Apagar histórico de assessments primeiro (FK constraint)
            DB::table('riskassessmenthistory')
                ->where('id_risk', $id)
                ->delete();

            DB::table('risk')
                ->where('id_risk', $id)
                ->delete();

            DB::commit();

            return response()->json([
                'message' => 'Risk deleted'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // CREATE FROM ALERT

    public function createFromAlert(Request $request)
    {
        $data = $request->all();

        DB::beginTransaction();

        try {

            // 1️⃣ verificar asset
            $asset = DB::table('asset')
                ->where('hostname', $data['hostname'])
                ->first();

            // 2️⃣ se não existir cria
            if (!$asset) {

                $assetId = DB::table('asset')->insertGetId([
                    'source' => 'acronis',
                    'hostname' => $data['hostname'],
                    'display_name' => $data['hostname'],
                    'created_at' => now(),
                    'updatedat' => now()
                ], 'id_asset');

            } else {

                $assetId = $asset->id_asset;
            }

            // 3️⃣ criar risco
            $riskId = DB::table('risk')->insertGetId([
                'id_asset' => $assetId,
                'title' => $data['title'],
                'description' => $data['description'],
                'threat' => $data['threat'] ?? null,
                'vulnerability' => $data['vulnerability'] ?? null,
                'risk_owner' => $data['risk_owner'] ?? null,
                'actions' => $data['actions'] ?? null,
                'due' => $data['due'] ?? null,
                'status' => 'Aberto',
                'origin' => 'acronis',
                'createdby' => 1
            ], 'id_risk');

            // 4️⃣ criar assessment inicial
            $prob = $data['probability'] ?? 3;
            $impact = $data['impact'] ?? 3;

            DB::table('riskassessmenthistory')->insert([
                'id_risk' => $riskId,
                'probability' => $prob,
                'impact' => $impact,
                'score' => $prob * $impact,
                'assessed_by' => 1,
                'assessedat' => now()
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Risk created from alert',
                'id_risk' => $riskId
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
}


