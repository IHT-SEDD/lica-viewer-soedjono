<!-- Horizontal form modal -->
<div id="insert-patient-modal" class="modal fade" tabindex="-1">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Insert Patient</h5>
                <!--begin::Close-->
                <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                    X
                </div>
                <!--end::Close-->
            </div>
            <div class="modal-body">

                <div class="edit-patient-details-form">
                <div class="fv-row row mb-4">
                    <div class="col-md-3"><label class="form-label fs-7">Physican/Doctor</label></div>
                    <div class="col-md-9">
                      {{ Form::select('doctor_id', [], null, ['class' => 'form-select form-select-sm form-select-solid select-two select-doctor', 'data-control' => 'select2', 'data-placeholder' => 'Select doctor']) }}
                    </div>
                  </div>

                  <div class="message-error">

                  </div>

                    <!-- End Input -->
                </div>
                <div class="mb-2 mt-8">
                    <button type="button" class="form-control btn btn-light-success btnInsertPatient" type="button" id="btnInsertPatient" data-no-order="" data-id="">Insert Patient</button>
                    <!-- <button class="btn btn-light-primary btn-sm mb-1 test-data-action btnPrintHasil" href="{{url('/printHasilTest/1')}}" type="button" id="btnPrintHasil" data-transaction-id="">Print Hasil Test</button> -->
                </div>

            </div>
        </div>
    </div>
</div>
<!-- /horizontal form modal -->