<table class="footable-details table table-striped table-hover toggle-circle">
    <tbody>
        <tr>
            <th>{{__('Code')}}</th>
            <td style="display: table-cell;">{{ $item->item_code }}</td>
        </tr>
        <tr>
            <th>{{__('Name')}}</th>
            <td style="display: table-cell;">{{ $item->item_name }}</td>
        </tr>
        <tr>
            <th>{{__('Uom')}}</th>
            <td style="display: table-cell;">{{ $item->item_uom }}</td>
        </tr>
        <tr>
            <th>{{__('Type')}}</th>
            <td style="display: table-cell;">{{ $item->tname }}</td>
        </tr>
        <tr>
            <th>{{__('Business Type')}}</th>
            <td style="display: table-cell;">{{ $item->btname }}</td>
        </tr>
        <tr>
            <th>{{__('Active')}}</th>
            @if ($item->is_active == 1)
            <td style="display: table-cell;"><div class="badge badge-pill badge-success">Yes</div></td>                
            @else
            <td style="display: table-cell;"><div class="badge badge-pill badge-danger">No</div></td>
            @endif
        </tr>
        <tr>
            <th>{{__('Created By')}}</th>
            <td style="display: table-cell;">{{ $item->created_by }}</td>
        </tr>
        <tr>
            <th>{{__('Created At')}}</th>
            <td style="display: table-cell;">{{ $item->created_at }}</td>
        </tr>
    </tbody>
</table>
{{-- <div class="modal-footer pr-0">
    <button type="button" class="btn btn-outline-dark" data-dismiss="modal">{{__('Close')}}</button>
</div> --}}
