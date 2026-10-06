<?php

namespace App\Http\Controllers\Concerns;

use Auth;
use DB;

/**
 * Partial payment untuk AR (invoice_hdr / debit_note_hdr) di Bank Masuk & Bank Keluar.
 * Terbayar = SUM(kas_det.credit) di voucher BM/KM/BK yang belum deleted.
 * Status: 6 PAID, 8 PARTIALLY PAID (7 sudah dipakai REVISED), 3 APPROVED (belum ada bayar).
 */
trait ArPartialPayment
{
    protected function arGrandTotal($reference)
    {
        $amount = DB::table('invoice_hdr')->where('invoice_number', $reference)->value('grand_total');
        if ($amount === null) {
            $amount = DB::table('debit_note_hdr')->where('dn_number', $reference)->value('grand_total');
        }
        return $amount === null ? null : (float) $amount;
    }

    protected function arPaid($reference, $excludeVcNumber = null)
    {
        if ($reference === null || $reference === '') return 0;

        return (float) DB::table('kas_det')
            ->join('kas_hdr', 'kas_hdr.voucher_number', '=', 'kas_det.voucher_number')
            ->where('kas_det.reference', $reference)
            ->whereIn('kas_hdr.voucher_type', ['BM', 'KM', 'BK'])
            ->where('kas_hdr.status', '<>', '5')
            ->when($excludeVcNumber, fn($q) => $q->where('kas_det.voucher_number', '<>', $excludeVcNumber))
            ->sum(DB::raw('coalesce(kas_det.credit, 0)'));
    }

    protected function syncArStatus($refs)
    {
        foreach (array_unique($refs) as $ref) {
            if ($ref === null || $ref === '') continue;
            $grandTotal = $this->arGrandTotal($ref);
            if ($grandTotal === null) continue;

            $paid = $this->arPaid($ref);
            $status = $paid >= $grandTotal - 0.01 ? '6' : ($paid > 0.01 ? '8' : '3');
            $upd = ['status' => $status, 'updated_by' => Auth::user()->username, 'updated_at' => date('Y-m-d H:i:s')];

            DB::table('invoice_hdr')->where('invoice_number', $ref)->whereIn('status', ['3', '6', '8'])->update($upd);
            DB::table('debit_note_hdr')->where('dn_number', $ref)->whereIn('status', ['3', '6', '8'])->update($upd);
        }
    }
}
