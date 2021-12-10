@extends('layouts.main')
@section('title', 'Jadwal')
@section('content')
<div class="card card-default">
  <div class="card-header ">
      <div class="card-title"><h5 data-toggle="tooltip" data-placement="top" data-original-title="Klik untuk isi jadwal" data-container="body" class="font-montserrat text-uppercase text-black" >@yield('title')</h5></div>
  </div>
  <div class="card-block">
    <div class="row">
      <div class="table-wrap">
        <div class="col-freeze-wrapper">
          <table  class="table table-bordered" id="tabelFreeze"> 
            <thead>
              <tr>
                <th class="judul col-sticky-freeze col-pertama" >PIN</th>
                <th class="judul col-sticky-freeze col-kedua" >Nama</th>
                <th class="judul col-sticky-freeze col-ketiga" >Absent</th>
                <th class="judul col-sticky-freeze col-keempat" >NIK</th>
                <th>21</th>
                <th>22</th>
                <th>23</th>
                <th>24</th>
                <th>25</th>
                <th>26</th>
                <th>27</th>
                <th>28</th>
                <th>29</th>
                <th>30</th>
                <th>01</th>
                <th>02</th>
                <th>03</th>
                <th>04</th>
                <th>05</th>
                <th>06</th>
                <th>07</th>
                <th>08</th>
                <th>09</th>
                <th>10</th>
                <th>11</th>
                <th>12</th>
                <th>13</th>
                <th>14</th>
                <th>15</th>
                <th>16</th>
                <th>17</th>
                <th>18</th>
                <th>19</th>
                <th>12</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td class="hijau col-sticky-freeze col-pertama" >1</td>
                <td class="hijau col-sticky-freeze col-kedua" >Oki Hartanto</td>
                <td class="hijau col-sticky-freeze col-ketiga" >ST 001</td>
                <td class="hijau col-sticky-freeze col-keempat" >02090051</td>
             <td></td>
             <td></td>
             <td></td>
             <td></td>
             <td></td>
             <td></td>
             <td></td>
             <td></td>
             <td></td>
             <td></td>
             <td></td>
             <td></td>
             <td></td>
             <td></td>
             <td></td>
             <td></td>
             <td></td>
             <td></td>
             <td></td>
             <td></td>
             <td></td>
             <td></td>
             <td></td>
             <td></td>
             <td></td>
             <td></td>
             <td></td>
             <td></td>
             <td></td>
             <td></td>
              </tr>
              <tr>
                <td class="hijau col-sticky-freeze col-pertama" >1</td>
                <td class="hijau col-sticky-freeze col-kedua" >Oki Hartanto</td>
                <td class="hijau col-sticky-freeze col-ketiga" >ST 001</td>
                <td class="hijau col-sticky-freeze col-keempat" >02090051</td>
             <td></td>
             <td></td>
             <td></td>
             <td></td>
             <td></td>
             <td></td>
             <td></td>
             <td></td>
             <td></td>
             <td></td>
             <td></td>
             <td></td>
             <td></td>
             <td></td>
             <td></td>
             <td></td>
             <td></td>
             <td></td>
             <td></td>
             <td></td>
             <td></td>
             <td></td>
             <td></td>
             <td></td>
             <td></td>
             <td></td>
             <td></td>
             <td></td>
             <td></td>
             <td></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <div class="card-block">
    <div class="row">
      <div class="table-wrap-2">
        <div class="col-freeze-wrapper">
          <table  class="table table-bordered" id="tabelFreeze"> 
            <thead>
              <tr>
                <th class="judul col-sticky-freeze col-pertama" >PIN</th>
                <th class="judul col-sticky-freeze col-kedua" >Nama</th>
                <th class="judul col-sticky-freeze col-ketiga" >Absent</th>
                <th class="judul col-sticky-freeze col-keempat" >NIK</th>
                <th>OFF</th>
                <th>P</th>
                <th>CT</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td class="hijau col-sticky-freeze col-pertama" >1</td>
                <td class="hijau col-sticky-freeze col-kedua" >Oki Hartanto</td>
                <td class="hijau col-sticky-freeze col-ketiga" >ST 001</td>
                <td class="hijau col-sticky-freeze col-keempat" >02090051</td>
                <td></td>
                <td></td>
                <td></td>             
              </tr>
              <tr>
                <td class="hijau col-sticky-freeze col-pertama" >1</td>
                <td class="hijau col-sticky-freeze col-kedua" >Oki Hartanto</td>
                <td class="hijau col-sticky-freeze col-ketiga" >ST 001</td>
                <td class="hijau col-sticky-freeze col-keempat" >02090051</td>
                <td></td>
                <td></td>
                <td></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
@section('style')
  <style>

    .table-wrap {
        left:0;
        width: 100%;
    }

    .table-wrap-2 {
        left:0;
        width: 50%;
    }

    .col-freeze-wrapper {
        position: relative;
        overflow: auto;
        white-space: nowrap;
    }

    .col-sticky-freeze {
        position: -webkit-sticky;
        position: sticky;
        background-color: white;
    }

    .col-pertama {
        width: 40px;
        min-width: 40px;
        max-width: 40px;
        left: 0px;
    }

    .col-kedua {
        width: 100px;
        min-width: 100px;
        max-width: 100px;
        left: 40px;
    }

    .col-ketiga {
        width: 100px;
        min-width: 100px;
        max-width: 100px;
        left: 140px;
    }

    .col-keempat {
        width: 100px;
        min-width: 100px;
        max-width: 100px;
        left: 240px;
    }

    td, th {
        margin: 0;
        border: 1px solid grey;
        white-space: nowrap;
        border-top-width: 0px;
    }

    .judul{
        background-color:#1f2323 !important;
        color:white !important;
        text-align: left !important;
    }

    .hijau {
        background-color:#aeebf2 !important;
        color:black !important;
    }

    .biru {
        background-color:black !important;
        color:black !important;
    }

    
  </style>
@endsection
@section('scripts')
@endsection