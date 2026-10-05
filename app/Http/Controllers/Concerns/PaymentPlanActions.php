<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use DB;

/*
    Dipakai bersama oleh ApPaymentPlanningController & ArPaymentPlanningController
    -- satu-satunya beda AP/AR di sini cuma kode module ('AP'/'AR'), jadi logic
    checklist-nya di-share lewat trait daripada di-duplikat di 2 controller.
*/
trait PaymentPlanActions
{
    protected function markPlanned($module, Request $request)
    {
        $refs   = (array) $request->refs;
        $action = $request->action;

        if (empty($refs) || !in_array($action, ['hold', 'to_be_paid', 'pending'], true)) {
            return response()->json(['status' => 0, 'message' => 'Data tidak lengkap.']);
        }
        if ($action === 'hold' && trim((string) $request->hold_reason) === '') {
            return response()->json(['status' => 0, 'message' => 'Hold Reason wajib diisi.']);
        }

        $user = Auth::user()->username ?? Auth::user()->name ?? null;

        foreach ($refs as $ref) {
            if ($action === 'pending') {
                DB::table('payment_plan')->where('module', $module)->where('ref_number', $ref)->delete();
                continue;
            }

            DB::table('payment_plan')->updateOrInsert(
                ['module' => $module, 'ref_number' => $ref],
                [
                    'status'             => $action,
                    'hold_reason'        => $action === 'hold' ? $request->hold_reason : null,
                    'biaya_administrasi' => $action === 'to_be_paid' ? (float) ($request->biaya_administrasi ?? 0) : 0,
                    'updated_by'         => $user,
                    'updated_at'         => now(),
                ]
            );
        }

        return response()->json(['status' => 1]);
    }

    protected function updateFeePlanned($module, Request $request)
    {
        $ref = $request->ref;
        $fee = (float) ($request->biaya_administrasi ?? 0);

        $updated = DB::table('payment_plan')
            ->where('module', $module)->where('ref_number', $ref)
            ->update(['biaya_administrasi' => $fee, 'updated_at' => now()]);

        if (!$updated) {
            return response()->json(['status' => 0, 'message' => 'Tandai sebagai Hold / To Be Paid dahulu.']);
        }

        return response()->json(['status' => 1]);
    }

    // Parse "voucher_type::kas_hdr.id::voucher_number,..." (lihat subquery di
    // ApPaymentPlanningController/ArPaymentPlanningController) jadi link ke
    // halaman show voucher yang bener sesuai tipenya -- satu invoice bisa
    // dibayar lewat beberapa voucher sekaligus (partial payment).
    protected function buildVoucherLinks($raw)
    {
        if (!$raw) {
            return [];
        }

        $routeMap = [
            'KK' => 'kasKeluar.show',
            'BK' => 'bankKeluar.show',
            'KM' => 'kasPenerimaan.show',
            'BM' => 'bankPenerimaan.show',
            'GJ' => 'jurnalUmum.show',
        ];

        $out = [];
        foreach (explode(',', $raw) as $part) {
            [$type, $id, $number] = array_pad(explode('::', $part, 3), 3, null);
            if ($number === null) {
                continue;
            }
            $out[] = [
                'number' => $number,
                'link'   => isset($routeMap[$type]) ? route($routeMap[$type], ['id' => Crypt::encryptString($id)]) : null,
            ];
        }

        return $out;
    }
}
