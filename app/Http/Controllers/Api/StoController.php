<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use DB;

class StoController extends Controller
{
    private $allowedUserIds = [58, 75, 23, 163, 176, 52, 66, 152, 185, 187, 67, 53];

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
}