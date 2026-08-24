<div class="form-row">
    <div class="form-group col-md-2">
        <label>Return Number</label>
        <input type="text" id="searchDn" class="form-control" placeholder="Search return number">
    </div>
    <div class="form-group col-md-2">
        <label>Status</label>
        <select id="searchStatus" class="form-control">
            <option value="">All</option>
            @foreach($status as $key=>$val)
                <option value="{{$key}}">{{$val}}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group col-md-3">
        <label>Supplier</label>
        <select id="searchSupplier" class="select2 form-control">
            <option value="">All</option>
            @foreach($suppliers as $val)
                <option value="{{$val->kode}}">{{$val->kode}} - {{$val->nama}}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group col-md-2">
        <label>Location</label>
        <select id="searchLocation" class="select2 form-control">
            <option value="">All</option>
            @foreach($locations as $val)
                <option value="{{$val->location_number}}">{{$val->location_number}} - {{$val->location_name}}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group col-md-3">
        <label>Return Date</label>
        <input type="text" id="returnDate" class="form-control" placeholder="Date range">
    </div>
</div>