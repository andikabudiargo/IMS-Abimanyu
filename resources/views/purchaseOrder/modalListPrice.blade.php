<div class="modal fade text-left bisa-geser" id="modalListPrice" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4>List price</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <h5><span class="semi-bold" id='modalArticle'></span></h5>
                <div class="table-responsive main-table">
                    <table class="table table-bordered w-100" id="modalTableData" >
                        <thead class="thead-dark">
                            <tr>
                                <th style="width:5%">No</th>
                                <th>PO Number</th>
                                <th>Date</th>
                                <th class="text-right">Price</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    #modalTableData td {
        padding-top: 0.1rem;
        padding-right: 1rem;
        padding-bottom: 0.1rem;
        padding-left: 1rem;
    }

    #modalTableData > .btn {
        padding-top: 0.4rem;
        padding-right: 1rem;
        padding-bottom: 0.4rem;
        padding-left: 1.1rem;
    }
</style>