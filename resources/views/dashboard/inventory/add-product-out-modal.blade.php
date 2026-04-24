<!-- Horizontal form modal -->
<div id="add-product-out-modal" class="modal fade" tabindex="-1">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Product Out</h5>
                <!--begin::Close-->
                <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                    X
                </div>
                <!--end::Close-->
            </div>
            <div class="modal-body">
                {!! Form::open(['class'=>'form form-horizontal form-validate-jquery', 'id' => 'form-add-product-out']) !!}
                {{ Form::hidden('id') }}
                    <div class="fv-row row mb-4">
                        <div class="col-md-3"><label class="form-label fs-7">Name</label></div>
                        <div class="col-md-9">
                        {{ Form::text('name', null, ['class' => 'form-control form-control-solid form-control-sm', 'readonly']) }}
                        </div>
                    </div>
                    <div class="message-error">
                    </div>
                        <!-- End Input -->


                    <div class="fv-row row mb-4">
                        <div class="col-md-3"><label class="form-label fs-7">Unit</label></div>
                        <div class="col-md-9">
                        {{ Form::text('unit', null, ['class' => 'form-control form-control-solid form-control-sm', 'readonly']) }}
                        </div>
                    </div>
                    <div class="message-error">
                    </div>
                        <!-- End Input -->
            

                    <div class="fv-row row mb-4">
                        <div class="col-md-3"><label class="form-label fs-7 required">Analyzer</label></div>
                        <div class="col-md-9">
                        {{ Form::select('analyzer_id', [], null, ['class' => 'form-select form-select-sm form-select-solid select-two select-analyzer', 'data-control' => 'select2', 'data-placeholder' => 'Select analyzer']) }}
                        </div>
                    </div>
                        <!-- End Input -->

                        <div class="fv-row row mb-4">
                        <div class="col-md-3"><label class="form-label fs-7 required">Qty</label></div>
                        <div class="col-md-9">
                        {{ Form::text('qty', null, ['class' => 'form-control form-control-solid form-control-sm']) }}
                        </div>
                    </div>
                    <div class="message-error">
                    </div>
                        <!-- End Input -->
    
                    <div class="mb-2 mt-8">
                        <button type="submit" class="form-control btn btn-light-success btn-submit-product">Add Product Out</button>
                        <!-- <button class="btn btn-light-primary btn-sm mb-1 test-data-action btnPrintHasil" href="{{url('/printHasilTest/1')}}" type="button" id="btnPrintHasil" data-transaction-id="">Print Hasil Test</button> -->
                    </div>
                {!! Form::close() !!}
                </div>
            </div>
        </div>
    </div>
<!-- /horizontal form modal -->