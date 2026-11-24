@extends('layouts.viewer-layout')

@section('styles')
<link href="{{asset('metronic_assets/plugins/custom/datatables/datatables.bundle.css')}}" rel="stylesheet"
  type="text/css" />

<style>
  .fixTableHead {
    overflow-y: auto;
    height: 300px;
  }

  .fixTableHead thead th {
    position: sticky;
    top: 0;
  }

  .table-fixed {
    border-collapse: collapse;
    width: 100%;
  }
</style>
@endsection

@section('content')
<div class="docs-content d-flex flex-column flex-column-fluid" id="kt_docs_content">
  <div class="px-10" id="kt_docs_content_container">
    <!--begin::Back to portal access-->
    <div>
      <a href={{ url('/') }} class="btn btn-primary btn-sm mb-2" id="back-to-portal-access-btn">
        Back to portal access
      </a>
    </div>
    <!--end::Back to portal access-->

    <!--begin::Content 1-->
    <div class="row">
      <!--begin::Result List Content-->
      <div class="col-lg-6">
        <div class="card card-docs mb-2">
          <div class="card-body fs-6 py-15 px-5 py-lg-8 px-lg-8 text-gray-700">
            <div class="p-0">
              <!--begin::Heading-->
              <div class="d-flex justify-content-between">
                <h1 class="anchor fw-bolder mb-5">
                  Results List
                </h1>
              </div>
              <!--end::Heading-->

              <!--begin::Datatable-->
              <div class="py-5">
                <!--begin::Group Actions-->
                {{-- <div class="d-flex justify-content-between mb-5">
                  <!--begin::Date range picker-->
                  <div class="col-lg-4">
                    <input class="form-control form-control-solid form-control-sm" placeholder="Pick date range"
                      id="daterange-picker" />
                  </div>
                  <!--end::Date range picker-->

                  <!--begin::Select grop-->
                  <div class="col-lg-4 px-2">
                    <div class="">
                      {{ Form::select('group_id', [], null, ['class' => 'form-select form-select-sm form-select-solid
                      select-two select-group','required'=>true, 'data-control' => 'select2', 'data-placeholder' =>
                      'Select Group','id'=>'group_id']) }}
                    </div>
                  </div>
                  <!--end::Date range picker-->

                  <!--begin::Search-->
                  <div class="col-lg-4 px-2">
                    <div class="d-flex align-items-center position-relative my-1 mb-2 mb-md-0">
                      <span class="svg-icon svg-icon-1 position-absolute ms-6">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                          <rect opacity="0.5" x="17.0365" y="15.1223" width="8.15546" height="2" rx="1"
                            transform="rotate(45 17.0365 15.1223)" fill="black" />
                          <path
                            d="M11 19C6.55556 19 3 15.4444 3 11C3 6.55556 6.55556 3 11 3C15.4444 3 19 6.55556 19 11C19 15.4444 15.4444 19 11 19ZM11 5C7.53333 5 5 7.53333 5 11C5 14.4667 7.53333 17 11 17C14.4667 17 17 14.4667 17 11C17 7.53333 14.4667 5 11 5Z"
                            fill="black" />
                        </svg>
                      </span>
                      <input type="text" data-kt-docs-table-filter="search-result-list"
                        class="form-control form-control-sm form-control-solid w-250px ps-15"
                        placeholder="Search Result" />
                    </div>
                  </div>
                  <!--end::Search-->

                  <!--begin::Selected action-->
                  <div class="d-flex justify-content-end align-items-center d-none"
                    data-kt-docs-table-toolbar="selected">
                    <div class="fw-bolder me-5">
                      <span class="me-2" data-kt-docs-table-select="selected_count"></span>Selected
                    </div>
                    <button type="button" class="btn btn-danger" data-kt-docs-table-select="delete_selected">Selection
                      Action</button>
                  </div>
                  <!--end::Selected action-->
                </div> --}}
                <!--end::Group Actions-->

                <table class="table gy-1 align-middle table-striped px-0 result-list-datatable-ajax">
                  <thead>
                    <tr class="text-start text-gray-600 fw-bolder fs-7 text-uppercase gs-0">
                      <th class="text-start min-w-80px" width="10%">Date</th>
                      <th class="text-start" width="15%">Lab No</th>
                      <th class="text-start" width="15%">Medrec</th>
                      <th class="text-start" width="25%">Name</th>
                      <th class="text-start" width="25%">Room</th>
                      <th class="text-lowercase text-center" style="width:10%"><i class="bi bi-info-circle"></i></th>
                      {{-- <th class="text-center" style="width:5%"></th> --}}
                    </tr>
                  </thead>
                  <tbody class="fw-bold fs-8"></tbody>
                </table>
              </div>
              <!--end::Datatable-->
            </div>
          </div>
        </div>
      </div>
      <!--end::Result List Content-->

      <!--begin::Result Detail Content-->
      <div class="col-lg-6">
        <div class="card card-docs mb-2">
          <div class="card-body fs-6 py-15 py-lg-6 px-lg-6 text-gray-700">
            <!--begin::Heading-->
            <div class="d-flex justify-content-between mb-4">
              <h1>Result Details</h1>
            </div>
            <!--end::Heading-->

            <div class="separator mb-2" style="border: 1px solid grey;"></div>

            <!--begin::Detail patient-->
            <div class=" row">
              <!--begin::Column 1-->
              <div class="col-12 col-md-4 px-0">
                <div class="table-responsive">
                  <table class="table">
                    <!--Name-->
                    <tr>
                      <th class="py-1 w-10px" style="font-size: 13px;">Name</th>
                      <td class="name-detail py-1 text-gray-600 w-auto" style="font-size: 12px;">-</td>
                    </tr>

                    <!--NIK-->
                    <tr>
                      <th class="py-1" style="font-size: 13px;">NIK</th>
                      <td class="nik-detail py-1 text-gray-600" style="font-size: 12px;">-</td>
                    </tr>

                    <!--Gender-->
                    <tr>
                      <th class="py-1" style="font-size: 13px;">Gender</th>
                      <td class="gender-detail py-1 text-gray-600" style="font-size: 12px;">-</td>
                    </tr>

                    <!--First Printed-->
                    <tr>
                      <th class="py-1" style="font-size: 13px;">First Printed</th>
                      <td class="first-printed-detail py-1 text-gray-600" style="font-size: 12px;">-</td>
                    </tr>
                  </table>
                </div>
              </div>
              <!--end::Column 1-->

              <!--begin::Column 2-->
              <div class="col-12 col-md-4 px-0">
                <div class="table-responsive">
                  <table class="table">
                    <!--Medical Record-->
                    <tr>
                      <th class="py-1" style="font-size: 13px;">Medical Record</th>
                      <td class="medrec-detail py-1 text-gray-600" style="font-size: 12px;">-</td>
                    </tr>

                    <!--Patient Type-->
                    <tr>
                      <th class="py-1" style="font-size: 13px;">Patient type</th>
                      <td class="type-detail py-1 text-gray-600" style="font-size: 12px;">-</td>
                    </tr>

                    <!--Room-->
                    <tr>
                      <th class="py-1" style="font-size: 13px;">Room</th>
                      <td class="room-detail py-1 text-gray-600" style="font-size: 12px;">-</td>
                    </tr>

                    <!--Order Number-->
                    <tr>
                      <th class="py-1" style="font-size: 13px;">Nomor Order</th>
                      <td class="no-order-detail py-1 text-gray-600" style="font-size: 12px;">-</td>
                    </tr>

                    <!--New order number-->
                    <tr>
                      <th class="py-1" style="font-size: 13px;">New Nomor Order</th>
                      <td class="new-no-order-detail py-1 text-gray-600" style="font-size: 12px;">-</td>
                    </tr>
                  </table>
                </div>
              </div>
              <!--end::Column 2-->

              <!--begin::Column 3-->
              <div class="col-12 col-md-4 px-0">
                <div class="table-responsive">
                  <table class="table">
                    <!--Physician-->
                    <tr>
                      <th class="py-1" style="font-size: 13px;">Physician</th>
                      <td class="doctor-detail py-1 text-gray-600" style="font-size: 12px;">-</td>
                    </tr>

                    <!--Note-->
                    <tr>
                      <th class="py-1" style="font-size: 13px;">Note</th>
                      <td class="note-detail py-1 text-gray-600" style="font-size: 12px;">-</td>
                    </tr>
                  </table>
                </div>
              </div>
              <!--end::Column 3-->

              <!--begin::Memo-->
              <div class="col-12 col-md-4 px-0">
                <div class="table-responsive">
                  <table class="table">
                    <tr>
                      <th class="py-1">
                        <div class="row">
                          <div class="col-md-7" style="font-size: 13px;">Memo Result</div>
                          <div class="col-md-5 form-check form-check-sm form-check-custom form-check-solid">
                            <input id="is_memo_print" name="is_memo_print" value="1" class="form-check-input"
                              type="checkbox">
                          </div>
                        </div>
                      </th>
                      <td class="memo-result py-1 text-gray-600" style="font-size: 12px;">-</td>
                    </tr>
                  </table>
                </div>
              </div>
              <!--end::Memo-->
            </div>
            <!--end::Detail patient-->

            <div class="separator mb-2" style="border: 1px solid grey;"></div>

            <!--begin::Detail result-->
            <div class="row">
              <div class="col-12">
                <!--begin::Heading-->
                <div class="d-flex justify-content-between">
                  @php
                  $icon = '<i class="bi bi-pencil test-data-action d-none" style="cursor:pointer" data-transaction-id=""
                    data-text="" id="memo-result-btn"></i>';
                  @endphp
                  <p>Parameter Data &nbsp;&nbsp; {!! $icon !!}</p>

                  {{-- <div class="mb-1">
                    <div>
                      <!--begin::Print result-->
                      <button type="button" class="btn btn-light-primary btn-sm btnPrintHasil" type="button"
                        id="btnPrintHasil" data-transaction-id="">Print Hasil</button>
                      <!--end::Print result-->
                      <!--begin::Export PDF result-->
                      <a class="btn btn-primary btn-sm btnExportPdfHasil" type="button" id="btnExportPdfHasil"
                        data-transaction-id="">Export PDF</a>
                      <!--end::Export PDF result-->
                    </div>
                  </div> --}}
                </div>
                <!--end::Heading-->

                <!--begin::Table result-->
                <div class="fixTableHead">
                  <table class="table table-striped result-list-table" style="overflow-y:scroll; height: 220px;">
                    <thead>
                      <tr class="px-0 text-uppercase text-gray-600 fw-bolder fs-7">
                        <td class="px-0 text-center">Test</td>
                        <td class="px-0 text-center">Result</td>
                        <td class="px-0 text-center">Unit</td>
                        <td class="px-0 text-center">Norm</td>
                        <td class="px-0 text-center"><i class="bi bi-info-circle"></i></td>
                        {{-- <td class="px-0 text-center">Reported to</td>
                        <td class="px-0 text-center">Report by</td>
                        <td class="px-0 text-center">Print</td> --}}
                      </tr>
                    </thead>
                    <tbody id="result-list-table-body">
                    </tbody>
                  </table>
                </div>
                <!--end::Table result-->
              </div>
            </div>
            <!--end::Detail result-->
          </div>
        </div>
        <!--end::Result Detail Content-->
      </div>
    </div>
    <!--end::Content 1-->

    <br>

    <!--begin::Content 2-->
    <div class="row">
      <div class="col-lg-12">
        <!--begin::Card-->
        <div class="card card-docs mb-2">
          <!--begin::Card Body-->
          <div class="card-body fs-6 py-15 px-5 py-lg-8 px-lg-8 text-gray-700">
            <!--begin::Section-->
            <div class="p-0">
              <!--begin::Heading-->
              <div class="d-flex justify-content-between">
                <h1 class="anchor fw-bolder mb-5">
                  Timestamp Process
                </h1>
              </div>
              <!--end::Heading-->
              <!--begin::CRUD-->
              <div class="py-5">
                <table class="table table-striped process-time-table">
                  <thead>
                    <tr class="px-0 text-uppercase text-gray-600 fw-bolder fs-7">
                      <td class="px-0 text-center">Test</td>
                      <td class="px-0 text-center">CheckIn Time</td>
                      <td class="px-0 text-center">User</td>
                      <td class="px-0 text-center">Draw Time</td>
                      <td class="px-0 text-center">User</td>
                      <td class="px-0 text-center">Verify Time</td>
                      <td class="px-0 text-center">User</td>
                      <td class="px-0 text-center">Validate Time</td>
                      <td class="px-0 text-center">User</td>
                      <td class="px-0 text-center">Process Time</td>
                      <td class="px-0 text-center">User</td>
                    </tr>
                  </thead>
                </table>
              </div>
              <!--end::CRUD-->
            </div>
            <!--end::Section-->
          </div>
          <!--end::Card Body-->
        </div>
        <!--end::Card-->
      </div>
    </div>
    <!--end::Content 2-->
  </div>
</div>

{{-- @include('viewer.print-hasil-modal'); --}}
@endsection

@section('scripts')
<!-- Form validation -->
<script src="{{asset('limitless_assets/js/plugins/forms/validation/validate.min.js')}}"></script>
<!-- /Form validation -->

<script src="{{asset('metronic_assets/plugins/custom/datatables/datatables.bundle.js')}}"></script>
<script>
  document.write("<script type='text/javascript' src='{{asset('js/viewer/result.js?v')}}" + Date.now() + "'><\/script>");
</script>
<script src="{{asset('js/viewer/datatable.js')}}"></script>
@endsection