@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="msp-history">
  <div class="card">
    <div class="card-header">
      <h4 class="card-title">History - {{ $hdr->msp_no }} ({{ $hdr->part_name }})</h4>
      <a href="{{ route('msp.edit', $hdr->id) }}" class="btn btn-sm btn-outline-primary">Back to Schedule</a>
    </div>
    <div class="card-body">
      <table class="table table-bordered table-sm">
        <thead>
          <tr>
            <th>Date</th>
            <th>Rev</th>
            <th>Item</th>
            <th>Document</th>
            <th>Field</th>
            <th>Before</th>
            <th>After</th>
            <th>Reason</th>
            <th>By</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($history as $h)
            @php
              $fieldLabels = [
                'document_report' => 'Document/Report',
                'pic' => 'PIC',
                'plan_start_date' => 'Plan Start',
                'plan_end_date' => 'Plan End',
                'actual_start_date' => 'Actual Start',
                'actual_end_date' => 'Actual End',
                'progress' => 'Progress %',
                'notes' => 'Notes',
                'plan_draft_date' => 'Plan Draft (D)',
                'plan_ai_date' => 'Plan Approval Internal (AI)',
                'plan_ae_date' => 'Plan Approval External (AE)',
                'actual_draft_date' => 'Actual Draft (D)',
                'actual_ai_date' => 'Actual Approval Internal (AI)',
                'actual_ae_date' => 'Actual Approval External (AE)',
                'resch_draft_date' => 'Reschedule Draft (RD)',
                'resch_ai_date' => 'Reschedule Approval Internal (RAI)',
                'resch_ae_date' => 'Reschedule Approval External (RAE)',
              ];
              $changedFields = array_filter($fieldLabels, function ($key) use ($h) {
                return ($h->old_data[$key] ?? null) != ($h->new_data[$key] ?? null);
              }, ARRAY_FILTER_USE_KEY);
            @endphp
            @forelse ($changedFields as $field => $label)
            <tr>
              <td class="text-nowrap">{{ $h->created_at }}</td>
              <td class="text-center">{{ $h->revision_no }}</td>
              <td>{{ $h->item_no }}. {{ $h->item_name }}</td>
              <td>{{ $h->document_report }}</td>
              <td>{{ $label }}</td>
              <td>{{ $h->old_data[$field] ?? '-' }}</td>
              <td>{{ $h->new_data[$field] ?? '-' }}</td>
              <td>{{ $h->reason }}</td>
              <td>{{ $h->changed_by }}</td>
            </tr>
            @empty
            @endforelse
          @empty
          <tr><td colspan="9" class="text-center">Belum ada revisi.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</section>
@endsection
