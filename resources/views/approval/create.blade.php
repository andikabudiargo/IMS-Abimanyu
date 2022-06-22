{{Form::open(array('route' => 'approval.store.level','method'=>'post'))}}
<div class="card-body p-0">
    <div class="form-row">
        <div class="form-group col-md-12 col-lg-12">
            {{ Form::label('module', __('Module')) }}
            {{ Form::select('module',$approvals,null, array('class' => 'form-control select2','required'=>'required')) }}
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-md-12 col-lg-12">
            {{ Form::label('username', __('Username')) }}
            {{ Form::select('username',$users,null, array('class' => 'form-control select2','required'=>'required')) }}
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-md-12 col-lg-12">
            {{ Form::label('approvalOrder', __('Approval Order')) }}
            {{ Form::select('approvalOrder',$orders,null, array('class' => 'form-control select2','required'=>'required')) }}
        </div>
    </div>
</div>
<div class="modal-footer pr-0">
    <button type="button" class="btn btn-success" data-dismiss="modal">{{__('Cancel')}}</button>
    {{Form::submit(__('Save'),array('class'=>'btn btn-primary'))}}
</div>
{{Form::close()}}