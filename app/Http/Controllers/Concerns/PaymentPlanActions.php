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

        // Biaya administrasi default: kalau user tidak isi manual, turunkan dari
        // bank supplier (AP only) -- BCA transfer gratis (0), bank lain kena 2.900.
        // Satu transfer bank = satu kali biaya admin, jadi kalau beberapa invoice
        // punya supplier (= rekening tujuan) yang sama dan ditandai bareng dalam
        // satu aksi ini, cuma invoice PERTAMA dari supplier itu yang kena biaya,
        // sisanya 0 -- supaya tidak dobel-hitung dalam satu transaksi transfer.
        $bankTypeBySupplier = [];
        $supplierByRef      = [];
        if ($module === 'AP' && $action === 'to_be_paid' && !$request->filled('biaya_administrasi')) {
            $info = DB::table('ap_invoice')
                ->join('third_party', 'third_party.kode', '=', 'ap_invoice.supplier_id')
                ->whereIn('ap_invoice.ap_number', $refs)
                ->get(['ap_invoice.ap_number', 'ap_invoice.supplier_id', 'third_party.bank_type']);
            foreach ($info as $row) {
                $supplierByRef[$row->ap_number]           = $row->supplier_id;
                $bankTypeBySupplier[$row->supplier_id]     = $row->bank_type;
            }
        }

        $feeChargedForSupplier = [];

        foreach ($refs as $ref) {
            if ($action === 'pending') {
                DB::table('payment_plan')->where('module', $module)->where('ref_number', $ref)->delete();
                continue;
            }

            $fee = 0;
            if ($action === 'to_be_paid') {
                if ($request->filled('biaya_administrasi')) {
                    $fee = (float) $request->biaya_administrasi;
                } elseif (isset($supplierByRef[$ref])) {
                    $supplierId = $supplierByRef[$ref];
                    $baseFee    = strtoupper((string) ($bankTypeBySupplier[$supplierId] ?? '')) === 'BCA' ? 0 : 2900;
                    if ($baseFee > 0 && empty($feeChargedForSupplier[$supplierId])) {
                        $fee = $baseFee;
                        $feeChargedForSupplier[$supplierId] = true;
                    }
                }
            }

            DB::table('payment_plan')->updateOrInsert(
                ['module' => $module, 'ref_number' => $ref],
                [
                    'status'             => $action,
                    'hold_reason'        => $action === 'hold' ? $request->hold_reason : null,
                    'biaya_administrasi' => $fee,
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
