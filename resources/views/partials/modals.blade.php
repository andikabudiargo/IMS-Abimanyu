<div class="modal fade text-left bisa-geser" id="commonModal" tabindex="-1" role="dialog" aria-labelledby="commonModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="commonModalLabel"></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
            </div>
        </div>
    </div>
</div>

<style>
    .modal-body{
        max-height: calc(100vh - 200px);
        overflow-y: auto;
    }
</style>

<script type="text/javascript">      
    $('.modal').on('shown.bs.modal', function () {
        $('.select2').select2();
    })
</script>
