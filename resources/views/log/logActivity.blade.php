@extends('layouts.app')
@section('title', 'Log Activity Lists')
@section('content')
@include('layouts.breadcrumb')
<section id="log-activities-index">
    <div class="row">
        <div class="col-md-12">
          <div class="card">
            <div class="card-header">  
              <div class="card-title">@yield('title')
              </div>
            </div>
            <div class="card-body">
                <form class="needs-validation" novalidate>
                    <div class="form-row">
                        <div class="form-group col-md-2"> 
                            <label class="form-label" for="searchSubject">Subject</label>
                            <select class="select2 form-control" id="searchSubject" name="searchSubject">
                                <option value="">All</option>
                                <option value="save">Save</option>
                                <option value="edit">Edit</option>
                                <option value="update">Update</option>
                                <option value="delete">Delete</option>
                                <option value="login">Login</option>
                                <option value="approve">Approve</option>
                            </select>
                        </div>
                        <div class="form-group col-md-3"> 
                            <label for="searchDesc">Description</label>
                            <input type="text" class="form-control" id="searchDesc" name="searchDesc" placeholder=""  />
                        </div>
                        <div class="col-md-3 form-group">
                            <label for="logDate">Date</label>
                            <input type="text" id="logDate" name="logDate" class="form-control flatpickr-range" placeholder="YYYY-MM-DD to YYYY-MM-DD" />
                        </div>
                        <div class="form-group col-md-2"> 
                            <label class="form-label" for="searchUser">Username</label>
                            <select class="select2 form-control" id="searchUser" name="searchUser">
                                <option value="">All</option>
                                @foreach($users as $val)
                                    <option value="{{ $val->username }}">{{ $val->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-12"> 
                            <button type="button" class="btn btn-primary" id ="btnSearch" name="btnSearch">Search</button>
                        </div>
                    </div>
                </form>
                {{-- <div class="row">
                    <div class="col-12">
                        <h5 class="card-header">Search Filter</h5>
                        <div class="d-flex justify-content-start align-items-center mx-50 row pt-0 pb-2">
                          <div class="col-md-4 subject"></div>
                          <div class="col-md-4 userName"></div>
                        </div>
                    </div>
                </div> --}}
                <div class="table-responsive">
                    <table id="detailedTable" class="table">
                    </table>
                </div>
            </div>
          </div>
        </div>
    </div>    
</section>
@endsection
@section('styles')
@endsection

@section('scripts')
<script type="text/javascript">
    let searchDesc = document.querySelector("#searchDesc");
    let searchSubject = document.querySelector("#searchSubject");
    let searchUserId = document.querySelector("#searchUser");
    let searchDate = document.querySelector("#logDate");
    let search = document.querySelector('#btnSearch');
    let rangePickr = document.querySelector('.flatpickr-range');

    initDatePicker(rangePickr,{
        minDate: "01/01/2020",
        maxDate: "31/12/2040",
        dateFormat: "d-m-Y",
        mode: "range"
    });

    $(document).ready(function() {
        // showList(searchDesc.value,searchSubject.value,searchUserId.value,searchDate.value);
    });

    search.addEventListener("click", function(){
        // let subject = searchSubject.val();
        // subject = jQuery.inArray('all',subject) == 0 ? null : subject;
        // console.log(subject);
        showList(searchDesc.value,searchSubject.value,searchUserId.value,searchDate.value);
    });

    const showList = (searchDesc,searchSubject,searchUserId,searchDate) => {
        showDataTables({
            tableId:"detailedTable",
            route:"{{ route('show.log.lists') }}",
            // style:"post",
            kolom:{!! $kolom !!},
                    arrColPrint:[0,1,2,3,4],
            dataSearch:  {
                searchDesc:searchDesc,
                searchSubject:searchSubject,
                searchUserId:searchUserId,
                searchDate:searchDate
            },
            orderColumn:[[ 4, 'desc' ]],  
            excelFileName:'log_activities'
        });
    }

    
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

</script>
@endsection
