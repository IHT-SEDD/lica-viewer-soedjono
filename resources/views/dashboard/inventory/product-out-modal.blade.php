<!-- Horizontal form modal -->
<div id="product-out-modal" class="modal fade" data-backdrop="static">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Form Product Out</h5>
                <!--begin::Close-->
                <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                    X
                </div>
                <!--end::Close-->
            </div>
              <!-- open modal body -->
            <div class="modal-body">
                <form action="{{ url('inventory/product-out') }}" id="form-product-out" method="post">
                    @csrf
                <div class="edit-patient-details-form">
                         <div class="mb-4">
                                <!--begin::Repeater-->
                                <div id="product-outs">
                                  <!--begin::Form group-->
                                  <div class="form-group">
                                      <div data-repeater-list="product_outs">
                                          <div data-repeater-item>
                                              <div class="form-group row mb-2">
                                                  <div class="col-md-5">
                                                      <label class="form-label required">Product</label>
                                                      {{ Form::select('product_id', [], null, ['class' => 'form-select form-select-sm form-select-solid select-two select-product-out','required'=>true, 'data-control' => 'select2', 'data-placeholder' => 'Select product']) }}
                                                  </div>
                                                  <div class="col-md-5">
                                                      <label class="form-label required">Qty</label>
                                                      {{ Form::text('qty', null, ['class' => 'form-control form-control-solid form-control-sm','required'=>true]) }}
                                                  </div>
                                                  <div class="col-md-2">
                                                      <a href="javascript:;" data-repeater-delete class="btn btn-sm btn-light-danger mt-3 mt-md-8">
                                                          Delete
                                                      </a>
                                                  </div>
                                              </div>
                                          </div>
                                      </div>
                                  </div>
                                  <!--end::Form group-->
  
                                  <!--begin::Form group-->
                                  <div class="form-group mt-5">
                                      <a href="javascript:;" data-repeater-create class="btn btn-sm btn-light-primary">
                                          <i class="la la-plus"></i>Add
                                      </a>
                                  </div>
                                  <!--end::Form group-->
                                </div>
                                <!--end::Repeater-->
                            </div>
                    </div>
                <div class="mb-2 mt-8">
                    <button type="submit" class="form-control btn btn-light-primary" type="button" id="btn-submit" data-transaction-id="" data-auto-nolab="false">Submit</button>
                </div>
                </form>
            </div>
            <!-- close modal body -->
        </div>
    </div>
</div>
<!-- /horizontal form modal -->