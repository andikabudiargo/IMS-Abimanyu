<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\StockCountController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use DB;

class StoController extends Controller
{
    private $autoNumberLocations = ['005', '006', '042', '049'];
    private $allowedUserIds = [58, 75, 23, 163, 176, 52, 66, 152, 185, 187, 67, 53];

    private function web()
    {
        return app(StockCountController::class);
    }

    private function isAutoNumber($targetRef)
    {
        return in_array($targetRef, $this->autoNumberLocations);
    }

    private function statusLabel($status)
    {
        $map = [
            1 => 'SCHEDULED',
            2 => 'ONGOING',
            3 => 'COMPLETED',
            5 => 'CANCELED',
        ];
        return $map[$status] ?? (string) $status;
    }

    /**
     * List target Stock Count milik user.
     * Query param:
     *   scope = today (default) | all
     */
    public function countList(Request $request)
    {
        $userId = Auth::id();
        $isAcct = in_array($userId, $this->allowedUserIds);
        $today  = date('d-m-Y');
        $scope  = $request->get('scope', 'today');

        $query = DB::table('sto_config_mapping as m')
            ->join('sto_config as h', 'h.config_id', '=', 'm.config_id')
            ->leftJoin('stock_location_master as l', function ($j) {
                $j->on('l.location_code', '=', 'm.target_ref')
                  ->where('m.target_type', '=', 'LOCATION');
            })
            ->leftJoin('third_party as tp', function ($j) {
                $j->on('tp.kode', '=', 'm.target_ref')
                  ->whereIn('m.target_type', ['SUPPLIER', 'CUSTOMER']);
            })
            ->leftJoin('users as u1', 'u1.id', '=', 'm.counter1_user')
            ->leftJoin('users as u2', 'u2.id', '=', 'm.counter2_user')
            ->leftJoin('users as u3', 'u3.id', '=', 'm.counter3_user')
            ->where('h.status', '!=', 5)
            ->select([
                'm.mapping_id',
                'm.config_id',
                'm.target_type',
                'm.target_ref',
                'm.sto_date',
                'm.finish_time',
                'm.is_blind',
                'm.target_plan_loc',
                'm.target_act_loc',
                'm.notes',
                'm.counter1_user',
                'm.counter2_user',
                'm.counter3_user',
                'u1.name as counter1_name',
                'u2.name as counter2_name',
                'u3.name as counter3_name',
                'h.sto_code',
                'h.sto_type',
                'h.periode',
                'h.status as config_status',
                DB::raw("COALESCE(l.location_name, tp.nama, m.target_ref) as target_name"),
                DB::raw("(
                    SELECT COUNT(*) FROM sto_dtl d
                    JOIN sto_hdr sh ON sh.sto_id = d.sto_id
                    WHERE sh.mapping_id = m.mapping_id
                ) as total_lines"),
                DB::raw("(
                    SELECT COUNT(*) FROM sto_dtl d
                    JOIN sto_hdr sh ON sh.sto_id = d.sto_id
                    WHERE sh.mapping_id = m.mapping_id
                      AND d.count_status IN ('INCOMPLETE','NOT MATCH')
                ) as pending_lines"),
                DB::raw("(
                    SELECT COUNT(*) FROM sto_hdr sh
                    WHERE sh.mapping_id = m.mapping_id
                ) as total_sheets"),
            ]);

        if (!$isAcct) {
            $query->where(function ($q) use ($userId) {
                $q->where('m.counter1_user', $userId)
                  ->orWhere('m.counter2_user', $userId)
                  ->orWhere('m.counter3_user', $userId);
            });

            // Counter hanya boleh INPUT di tanggal STO-nya. scope=all cuma
            // buat lihat-lihat jadwal, tetap tidak bisa input di luar tanggal.
            if ($scope !== 'all') {
                $query->where('m.sto_date', $today);
            }
        } else {
            if ($scope !== 'all') {
                $query->where('m.sto_date', $today);
            }
        }

        $rows = $query
            ->orderByRaw("TO_DATE(m.sto_date,'DD-MM-YYYY') DESC")
            ->orderBy('target_name')
            ->get();

        $data = $rows->map(function ($r) use ($userId, $isAcct, $today) {
            // role user di target ini
            $role = null;
            if ($r->counter1_user == $userId) $role = 'counter1';
            elseif ($r->counter2_user == $userId) $role = 'counter2';
            elseif ($r->counter3_user == $userId) $role = 'counter3';
            elseif ($isAcct) $role = 'accounting';

            $isToday   = $r->sto_date === $today;
            $isFinish  = !empty($r->finish_time);
            $canInput  = $isAcct || (!$isFinish && $isToday && $role !== null);

            return [
                'mapping_id'      => $r->mapping_id,
                'enc_mapping_id'  => Crypt::encryptString((string) $r->mapping_id),
                'sto_code'        => $r->sto_code,
                'sto_type'        => $r->sto_type,
                'periode'         => $r->periode,
                'target_type'     => $r->target_type,
                'target_ref'      => $r->target_ref,
                'target_name'     => $r->target_name,
                'sto_date'        => $r->sto_date,
                'finish_time'     => $r->finish_time,
                'is_blind'        => in_array($r->is_blind, [true, 1, '1', 't', 'true'], true),
                'config_status'   => (int) $r->config_status,
                'status_label'    => $this->statusLabel((int) $r->config_status),
                'target_plan_loc' => (float) $r->target_plan_loc,
                'target_act_loc'  => (float) $r->target_act_loc,
                'total_lines'     => (int) $r->total_lines,
                'pending_lines'   => (int) $r->pending_lines,
                'total_sheets'    => (int) $r->total_sheets,
                'counter1_name'   => $r->counter1_name,
                'counter2_name'   => $r->counter2_name,
                'counter3_name'   => $r->counter3_name,
                'notes'           => $r->notes,
                'my_role'         => $role,
                'is_today'        => $isToday,
                'can_input'       => $canInput,
            ];
        });

        return response()->json([
            'status'    => 1,
            'message'   => 'OK',
            'is_acct'   => $isAcct,
            'today'     => $today,
            'scope'     => $scope,
            'total'     => $data->count(),
            'data'      => $data->values(),
        ]);
    }

     /**
     * Guard baca — mirror checkAccess() di StockCountController.
     * Return ['ok'=>bool, 'message'=>..., 'role'=>..., 'mapping'=>...]
     */
    private function access($mappingId)
    {
        $userId = Auth::id();
        $isAcct = in_array($userId, $this->allowedUserIds);
        $today  = date('d-m-Y');

        $m = DB::table('sto_config_mapping as m')
            ->join('sto_config as h', 'h.config_id', '=', 'm.config_id')
            ->where('m.mapping_id', $mappingId)
            ->select('m.*', 'h.status as config_status', 'h.sto_code', 'h.periode')
            ->first();

        if (!$m) return ['ok' => false, 'message' => 'Target STO tidak ditemukan.'];

        if ($m->config_status == 5) {
            return ['ok' => false, 'message' => 'STO ini sudah dibatalkan (CANCELED).'];
        }

        if ($isAcct) return ['ok' => true, 'role' => 'accounting', 'mapping' => $m];

        $role = null;
        if ($m->counter1_user == $userId) $role = 'counter1';
        elseif ($m->counter2_user == $userId) $role = 'counter2';
        elseif (($m->counter3_user ?? null) == $userId) $role = 'counter3';

        if (!$role) {
            return ['ok' => false, 'message' => 'Anda tidak terdaftar sebagai counter untuk target ini.'];
        }

        // sudah finish → boleh lihat, tanggal tidak dicek lagi
        if ($m->finish_time) return ['ok' => true, 'role' => $role, 'mapping' => $m];

        if ($m->sto_date !== $today) {
            return ['ok' => false, 'message' => "Hari ini bukan tanggal STO untuk target ini ($m->sto_date)."];
        }

        return ['ok' => true, 'role' => $role, 'mapping' => $m];
    }

    /**
     * GET /api/sto/count/detail?mapping_id=<encrypted>
     * Header + semua sheet + baris — setara create() versi web.
     */
    public function detail(Request $request)
    {
        try {
            $mappingId = Crypt::decryptString($request->mapping_id);
        } catch (\Exception $e) {
            return response()->json(['status' => 0, 'message' => 'Parameter tidak valid.'], 422);
        }

        $acc = $this->access($mappingId);
        if (!$acc['ok']) {
            return response()->json(['status' => 0, 'message' => $acc['message']], 403);
        }

        $m        = $acc['mapping'];
        $role     = $acc['role'];
        $userId   = Auth::id();
        $isPartner = in_array($m->target_type, ['SUPPLIER', 'CUSTOMER']);
        $isAuto    = $this->isAutoNumber($m->target_ref);

        $targetName = DB::table('sto_config_mapping as mm')
            ->leftJoin('stock_location_master as l', function ($j) {
                $j->on('l.location_code', '=', 'mm.target_ref')
                  ->where('mm.target_type', '=', 'LOCATION');
            })
            ->leftJoin('third_party as tp', function ($j) {
                $j->on('tp.kode', '=', 'mm.target_ref')
                  ->whereIn('mm.target_type', ['SUPPLIER', 'CUSTOMER']);
            })
            ->where('mm.mapping_id', $mappingId)
            ->selectRaw("COALESCE(l.location_name, tp.nama, mm.target_ref) as target_name")
            ->value('target_name');

        // SCHEDULED → ONGOING saat counter pertama buka
        if ($m->config_status == 1 && $role !== 'accounting') {
            DB::table('sto_config')->where('config_id', $m->config_id)
                ->where('status', 1)
                ->update(['status' => 2, 'updated_at' => date('Y-m-d H:i:s')]);
        }

        $allHdrs = DB::table('sto_hdr')
            ->where('mapping_id', $mappingId)
            ->orderBy('sto_id')
            ->get();

        $sheets = [];
        foreach ($allHdrs as $hdr) {
            $lines = DB::table('sto_dtl as d')
                ->leftJoin('stock_location_master as l', 'l.location_code', '=', 'd.location_number')
                ->where('d.sto_id', $hdr->sto_id)
                ->orderBy('d.dtl_id')
                ->select('d.*', 'l.location_name')
                ->get()
                ->map(function ($l) use ($role, $userId) {
                    $myQty = null;
                    if ($role === 'accounting')                    $myQty = $l->qty_counter1;
                    elseif ($l->counter1_user == $userId)          $myQty = $l->qty_counter1;
                    elseif ($l->counter2_user == $userId)          $myQty = $l->qty_counter2;
                    elseif (($l->counter3_user ?? null) == $userId) $myQty = $l->qty_counter3;

                    return [
                        'dtl_id'          => $l->dtl_id,
                        'sto_id'          => $l->sto_id,
                        'article_code'    => $l->article_code,
                        'article_desc'    => $l->article_desc,
                        'is_manual'       => (bool) $l->is_manual,
                        'uom'             => $l->uom,
                        'min_package'     => $l->min_package,
                        'my_qty'          => $myQty !== null ? (float) $myQty : null,
                        'qty_counter1'    => $l->qty_counter1 !== null ? (float) $l->qty_counter1 : null,
                        'qty_counter2'    => $l->qty_counter2 !== null ? (float) $l->qty_counter2 : null,
                        'qty_counter3'    => $l->qty_counter3 !== null ? (float) $l->qty_counter3 : null,
                        'count_status'    => $l->count_status,
                        'note'            => $l->note,
                        'location_number' => $l->location_number,
                        'location_name'   => $l->location_name,
                    ];
                })->values();

            $sheets[] = [
                'sto_id'     => $hdr->sto_id,
                'sto_number' => $hdr->sto_number,
                'status'     => (int) $hdr->status,
                'lines'      => $lines,
            ];
        }

        $locations = [];
        if ($isPartner) {
            $locations = DB::table('stock_location_master')
                ->select('location_code as code', 'location_name as name')
                ->orderBy('location_name')->get();
        }

        return response()->json([
            'status'  => 1,
            'message' => 'OK',
            'mapping' => [
                'mapping_id'      => (int) $m->mapping_id,
                'config_id'       => (int) $m->config_id,
                'sto_code'        => $m->sto_code,
                'periode'         => $m->periode,
                'target_type'     => $m->target_type,
                'target_ref'      => $m->target_ref,
                'target_name'     => $targetName,
                'sto_date'        => $m->sto_date,
                'finish_time'     => $m->finish_time,
                'is_blind'        => in_array($m->is_blind, [true, 1, '1', 't', 'true'], true),
                'target_plan_loc' => (float) $m->target_plan_loc,
                'target_act_loc'  => (float) $m->target_act_loc,
                'config_status'   => (int) $m->config_status,
            ],
            'access_role' => $role,
            'is_auto'     => $isAuto,
            'is_partner'  => $isPartner,
            'locations'   => $locations,
            'sheets'      => $sheets,
        ]);
    }

    // ── delegasi ke controller web (semuanya sudah return JSON) ──

    public function articles(Request $request)
    {
        return $this->web()->getArticles($request);
    }

    public function availableNumbers(Request $request)
    {
        return $this->web()->getAvailableNumbers($request);
    }

    public function storeLine(Request $request)
    {
        return $this->web()->storeLine($request);
    }

    public function storeSheet(Request $request)
    {
        return $this->web()->storeSheet($request);
    }

    public function updateLine(Request $request, $dtlId)
    {
        return $this->web()->updateLine($request, $dtlId);
    }

    public function deleteLine(Request $request, $dtlId)
    {
        return $this->web()->deleteLine($request, $dtlId);
    }

    public function finish(Request $request)
    {
        return $this->web()->finish($request);
    }
}