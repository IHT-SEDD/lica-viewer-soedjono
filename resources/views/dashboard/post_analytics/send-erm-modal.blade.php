<!-- Horizontal form modal -->
<div id="send-erm-modal" class="modal fade" tabindex="-1">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New No Order</h5>
                <!--begin::Close-->
                <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                    X
                </div>
                <!--end::Close-->
            </div>
            <div class="modal-body">

                <div class="edit-patient-details-form">

                    <div class="fv-row row mb-4">
                        <div class="col-md-3"><label class="form-label fs-7">New No Order</label></div>
                        <div class="col-md-9">
                            <input type="text" id="new-no-order" name="new_no_order" data-transaction-id="" class="form-control form-control-solid form-control-sm" value="">
                        </div>
                    </div>
                    <!-- End Input -->
                </div>
                <div class="mb-2 mt-8">
                    <button type="button" class="form-control btn btn-light-primary btnSubmitNoOrder" type="button" id="btnSubmitNoOrder" data-transaction-id="">Submit</button>
                    <!-- <button class="btn btn-light-primary btn-sm mb-1 test-data-action btnPrintHasil" href="{{url('/printHasilTest/1')}}" type="button" id="btnPrintHasil" data-transaction-id="">Print Hasil Test</button> -->
                </div>

            </div>
        </div>
    </div>
</div>
<!-- /horizontal form modal -->