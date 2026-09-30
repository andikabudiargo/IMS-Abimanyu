<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MasterScheduleProjectController extends Controller
{
    /**
     * The 13 standard project stages. Fixed by requirement — only pic/document
     * report text per line item change from project to project, never this list.
     */
    const ITEMS = [
        1 => ['stage' => 'Preparation Stage', 'name' => 'Meeting New Project + Schedule Project'],
        2 => ['stage' => 'Preparation Stage', 'name' => 'Determination Project'],
        3 => ['stage' => 'Preparation Stage', 'name' => 'Line Capacity Check'],
        4 => ['stage' => 'Confirmation Stage', 'name' => 'Customer Visit (Genba)'],
        5 => ['stage' => 'Confirmation Stage', 'name' => 'Document Preparation (PPAP)'],
        6 => ['stage' => 'Confirmation Stage', 'name' => 'Mapping Man Power'],
        7 => ['stage' => 'Confirmation Stage', 'name' => 'Line Preparation'],
        8 => ['stage' => 'Mass Production Stage', 'name' => 'Safety Trial'],
        9 => ['stage' => 'Mass Production Stage', 'name' => 'Report Trial'],
        10 => ['stage' => 'Mass Production Stage', 'name' => 'Limit Sample'],
        11 => ['stage' => 'Mass Production Stage', 'name' => 'Training'],
        12 => ['stage' => 'Mass Production Stage', 'name' => 'Serah Terima Masspro'],
        13 => ['stage' => 'Mass Production Stage', 'name' => 'Mass Production'],
    ];

    private $title = 'Master Schedule Project';

    public function index(Request $request)
    {
        $q = $request->input('q');

        $projects = DB::table('msp_hdr')
            ->when($q, function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('msp_no', 'like', "%$q%")
                      ->orWhere('part_name', 'like', "%$q%")
                      ->orWhere('customer', 'like', "%$q%")
                      ->orWhere('model', 'like', "%$q%");
                });
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('masterScheduleProject.index', [
            'title' => $this->title,
            'projects' => $projects,
            'q' => $q,
        ]);
    }

    public function create()
    {
        return view('masterScheduleProject.form', [
            'title' => $this->title,
            'hdr' => null,
            'items' => self::ITEMS,
            'dtlByItem' => [],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'part_name' => 'required|string|max:255',
        ]);

        $username = Auth::user()->username ?? Auth::user()->name;

        DB::beginTransaction();
        try {
            $hdrId = DB::table('msp_hdr')->insertGetId([
                'msp_no' => $this->generateMspNo(),
                'customer' => $request->customer,
                'part_name' => $request->part_name,
                'model' => $request->model,
                'part_number' => $request->part_number,
                'issue_date' => $request->issue_date ?: now()->toDateString(),
                'revision_no' => 0,
                'status' => 'ongoing',
                'created_by' => $username,
                'updated_by' => $username,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->saveDtlRows($hdrId, $request, 0, $username, false);

            DB::commit();
            return redirect()->route('msp.edit', $hdrId)->with('success', 'Master Schedule Project berhasil dibuat.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function edit($id)
    {
        $hdr = DB::table('msp_hdr')->where('id', $id)->first();
        abort_if(!$hdr, 404);

        $dtl = DB::table('msp_dtl')->where('hdr_id', $id)->orderBy('item_no')->orderBy('sort_order')->get();
        $dtlByItem = $dtl->groupBy('item_no');

        return view('masterScheduleProject.form', [
            'title' => $this->title,
            'hdr' => $hdr,
            'items' => self::ITEMS,
            'dtlByItem' => $dtlByItem,
        ]);
    }

    public function update(Request $request, $id)
    {
        $hdr = DB::table('msp_hdr')->where('id', $id)->first();
        abort_if(!$hdr, 404);

        $request->validate([
            'part_name' => 'required|string|max:255',
        ]);

        $username = Auth::user()->username ?? Auth::user()->name;
        $nextRevisionNo = $hdr->revision_no + 1;

        DB::beginTransaction();
        try {
            DB::table('msp_hdr')->where('id', $id)->update([
                'customer' => $request->customer,
                'part_name' => $request->part_name,
                'model' => $request->model,
                'part_number' => $request->part_number,
                'issue_date' => $request->issue_date,
                'status' => $request->status ?: $hdr->status,
                'updated_by' => $username,
                'updated_at' => now(),
            ]);

            $anyChanged = $this->saveDtlRows($id, $request, $nextRevisionNo, $username, true);

            if ($anyChanged) {
                DB::table('msp_hdr')->where('id', $id)->update(['revision_no' => $nextRevisionNo]);
            }

            DB::commit();
            return redirect()->route('msp.edit', $id)->with('success', 'Schedule berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function history($id)
    {
        $hdr = DB::table('msp_hdr')->where('id', $id)->first();
        abort_if(!$hdr, 404);

        $history = DB::table('msp_dtl_history')
            ->where('hdr_id', $id)
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($row) {
                $row->old_data = json_decode($row->old_data, true);
                $row->new_data = json_decode($row->new_data, true);
                return $row;
            });

        return view('masterScheduleProject.history', [
            'title' => $this->title,
            'hdr' => $hdr,
            'history' => $history,
        ]);
    }

    /**
     * Insert new lines / update existing ones for a header, snapshotting a
     * before/after diff into msp_dtl_history whenever a tracked field actually
     * changes. Returns whether anything changed (used to bump hdr revision_no).
     */
    private function saveDtlRows($hdrId, Request $request, int $revisionNo, string $username, bool $withHistory): bool
    {
        $ids = $request->input('dtl_id', []);
        $itemNos = $request->input('item_no', []);
        $itemNames = $request->input('item_name', []);
        $stageGroups = $request->input('stage_group', []);
        $documents = $request->input('document_report', []);
        $pics = $request->input('pic', []);
        $planStarts = $request->input('plan_start', []);
        $planEnds = $request->input('plan_end', []);
        $actualStarts = $request->input('actual_start', []);
        $actualEnds = $request->input('actual_end', []);
        $progresses = $request->input('progress', []);
        $notes = $request->input('notes', []);
        $reason = $request->input('revision_reason');

        $anyChanged = false;

        foreach ($itemNos as $i => $itemNo) {
            // skip fully blank added-but-unused rows
            $isBlank = !$documents[$i] && !$pics[$i] && !$planStarts[$i] && !$planEnds[$i]
                && !$actualStarts[$i] && !$actualEnds[$i];
            $dtlId = $ids[$i] ?? null;
            if ($isBlank && !$dtlId) {
                continue;
            }

            $newData = [
                'document_report' => $documents[$i] ?: null,
                'pic' => $pics[$i] ?: null,
                'plan_start_date' => $planStarts[$i] ?: null,
                'plan_end_date' => $planEnds[$i] ?: null,
                'actual_start_date' => $actualStarts[$i] ?: null,
                'actual_end_date' => $actualEnds[$i] ?: null,
                'progress' => (int) ($progresses[$i] ?? 0),
                'notes' => $notes[$i] ?: null,
            ];

            if ($dtlId) {
                $old = DB::table('msp_dtl')->where('id', $dtlId)->first();
                if (!$old) {
                    continue;
                }

                $oldData = [
                    'document_report' => $old->document_report,
                    'pic' => $old->pic,
                    'plan_start_date' => $old->plan_start_date,
                    'plan_end_date' => $old->plan_end_date,
                    'actual_start_date' => $old->actual_start_date,
                    'actual_end_date' => $old->actual_end_date,
                    'progress' => $old->progress,
                    'notes' => $old->notes,
                ];

                $changed = $oldData != $newData;

                if ($changed) {
                    $anyChanged = true;

                    if ($withHistory) {
                        DB::table('msp_dtl_history')->insert([
                            'hdr_id' => $hdrId,
                            'dtl_id' => $dtlId,
                            'revision_no' => $revisionNo,
                            'item_no' => $itemNo,
                            'item_name' => $itemNames[$i] ?? $old->item_name,
                            'document_report' => $newData['document_report'],
                            'old_data' => json_encode($oldData),
                            'new_data' => json_encode($newData),
                            'reason' => $reason,
                            'changed_by' => $username,
                            'created_at' => now(),
                        ]);
                    }

                    DB::table('msp_dtl')->where('id', $dtlId)->update($newData + ['updated_at' => now()]);
                }
            } else {
                $anyChanged = true;
                DB::table('msp_dtl')->insert(array_merge($newData, [
                    'hdr_id' => $hdrId,
                    'item_no' => $itemNo,
                    'item_name' => $itemNames[$i] ?? (self::ITEMS[$itemNo]['name'] ?? ''),
                    'stage_group' => $stageGroups[$i] ?? (self::ITEMS[$itemNo]['stage'] ?? ''),
                    'sort_order' => $i,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }

        return $anyChanged;
    }

    private function generateMspNo(): string
    {
        $year = now()->format('Y');
        $count = DB::table('msp_hdr')->whereYear('created_at', $year)->count() + 1;
        return sprintf('MSP-%s-%03d', $year, $count);
    }
}
