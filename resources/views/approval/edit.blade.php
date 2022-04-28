{{-- {{Form::model($approvalLevel,array('url' => route('approval.update.level', ['id'=>$approvalLevel->id]), 'method' => 'POST')) }} --}}
{{Form::model($approvalLevel,array('route' => array('approval.update.level','id'=> $approvalLevel->id), 'method' => 'POST')) }}
<div class="card-body p-0">
    <div class="form-row">
        <div class="form-group col-md-12 col-lg-12">
            {{ Form::label('module', __('Module')) }}
            {{ Form::select('module',$approvals,$approvalLevel->module_code, array('class' => 'form-control select2','required'=>'required')) }}
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-md-12 col-lg-12">
            {{ Form::label('username', __('Username')) }}
            {{ Form::select('username',$users,$approvalLevel->username, array('class' => 'form-control select2','required'=>'required')) }}
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-md-12 col-lg-12">
            {{ Form::label('approvalOrder', __('Approval Order')) }}
            {{ Form::select('approvalOrder',$orders,$approvalLevel->approval_order, array('class' => 'form-control select2','required'=>'required')) }}
        </div>
    </div>
</div>
<div class="modal-footer pr-0">
    <button type="button" class="btn btn-success" data-dismiss="modal">{{__('Cancel')}}</button>
    {{Form::submit(__('Update'),array('class'=>'btn btn-primary'))}}
</div>
{{Form::close()}}