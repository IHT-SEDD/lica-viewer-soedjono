@extends('dashboard.qms.display.qms_layout')

@section('styles')
<link href="{{asset('metronic_assets/plugins/custom/datatables/datatables.bundle.css')}}" rel="stylesheet" type="text/css" />
@endsection
@section('content')
<!--begin::Content-->
<div class="docs-content d-flex flex-column flex-column-fluid" id="kt_docs_content">
    <!--begin::Container-->
    <div class="px-5 mx-5 mt-5" id="kt_docs_content_container">
        <div class="row">
            <!--PRE ANALYTICS-->
            <div class="col-md-12 col-lg-12 col-xl-12 pt-15">
                <!--begin::Card-->
                <div class="card">
                    <!-- begin::Content -->
                    <div class="card-content table-responsive table-full-width" style="height: auto; overflow-y:auto; overflow-x:hidden" id="table_pre">
                        <div class="py-2 d-flex flex-lg-column">
                            <marquee style="margin: 0;">
                                <dive style="color: #000000; font-size: 50px; font-weight: 600;">Mohon Perhatian. <br> 
                                    Bpk/Ibu Setelah dilakukan sampling dapat segera kembali ke poliklinik masing-masing. Hasil Laboratorium akan dikirimkan ke Poliklinik melalui eRM. Terimakasih
                                </dive>
                            </marquee>
                        </div>
                    </div>
                    <!-- end::Content -->
                </div>
            </div>
            <!--END PRE ANALYTICS-->
        </div>
        
        <div class="row">
            <!--PRE ANALYTICS-->
            <div class="col-md-4 col-lg-12 col-xl-4">
                <!--begin::Col header-->
                <div class="mb-9">
                    <div class="d-flex flex-stack">
                        <div class="fw-bolder fs-1 pt-2">
                            DALAM ANTRIAN
                        </div>
                    </div>
                    <div class="h-3px w-100 bg-warning"></div>
                </div>
                <!--end::Col header-->
                <!--begin::Card-->
                <div class="card mb-6 mb-xl-9">
                    <!-- begin::Content -->
                    <table class="table table-striped align-middle table-row-dashed fs-6 gy-5" cellpadding="0" width="100%" style="width:100%; text-align: left;">
                        <thead style="text-transform: uppercase; font-size: 20px; font-weight: bold;">
                            <th width="4%" style="font-size: 18px; font-weight: bold; text-align:left;">No</th>
                            <th width="62%" style="font-size: 18px; font-weight: bold; text-align:left;">Nama</th>
                            <th width="19%" style="font-size: 18px; font-weight: bold; text-align:left;">No. Lab</th>
                        </thead>
                    </table>

                    <div class="card-content table-responsive table-full-width" style="height:55vh; overflow-y:auto; overflow-x:hidden" id="table_pre">
                        <table id="table_1" class="table table-striped align-middle table-row-dashed fs-6 gy-5" cellspacing="0" width="100%" style="width:100%; font-weight: bold;">
                            <tbody id="data_1">
                            </tbody>
                        </table>
                    </div>
                    <!-- end::Content -->
                </div>
            </div>
            <!--END PRE ANALYTICS-->

            <!--ANALYTICS-->
            <div class="col-md-4 col-lg-12 col-xl-4">
                <!--begin::Col header-->
                <div class="mb-9">
                    <div class="d-flex flex-stack">
                        <div class="fw-bolder fs-1 pt-2">
                            SEDANG PROSES PEMERIKSAAN
                        </div>
                    </div>
                    <div class="h-3px w-100 bg-primary"></div>
                </div>
                <!--end::Col header-->
                <!--begin::Card-->
                <div class="card mb-6 mb-xl-9">
                    <!-- begin::Content -->
                    <table class="table table-striped align-middle table-row-dashed fs-6 gy-5" cellpadding="0" style="width: 100%; table-layout: fixed;">
                        <thead style="text-transform: uppercase;">
                            <th width="12%" style="font-size: 18px; font-weight: bold; text-align:left;">No</th>
                            <th width="48%" style="font-size: 18px; font-weight: bold; text-align:left;">Nama</th>
                            <th width="21%" style="font-size: 18px; font-weight: bold; text-align:left;">No. Lab</th>
                            <th width="20%" style="font-size: 18px; font-weight: bold; text-align:left;">Status</th>
                        </thead>
                    </table>
                    <div class="card-content table-responsive table-full-width" style="height:55vh; overflow-y:auto; overflow-x:hidden" id="table_proses">
                        <table id="table_2" class="table table-striped align-middle table-row-dashed fs-6 gy-5" cellspacing="0" width="100%" style="width:100%; font-weight: bold;">
                            <tbody id="data_2">
                            </tbody>
                        </table>
                    </div>
                    <!-- end::Content -->
                </div>
            </div>
            <!--END ANALYTICS-->

            <!--POST ANALYTICS-->
            <div class="col-md-4 col-lg-12 col-xl-4">
                <!--begin::Col header-->
                <div class="mb-9">
                    <div class="d-flex flex-stack">
                        <div class="fw-bolder fs-1 pt-2">
                            PEMERIKSAAN SELESAI
                        </div>
                    </div>
                    <div class="h-3px w-100 bg-success"></div>
                </div>
                <!--end::Col header-->
                <!--begin::Card-->
                <div class="card mb-6 mb-xl-9">
                    <!-- begin::Content -->
                    <table class="table table-striped align-middle table-row-dashed fs-6 gy-5" cellpadding="0" style="width: 100%; table-layout: fixed;">
                        <thead style="text-transform: uppercase;">
                            <th width="15%" style="font-size: 18px; font-weight: bold; text-align:left;">No.</th>
                            <th width="60%" style="font-size: 18px; font-weight: bold; text-align:left;">Nama</th>
                            <th width="25%" style="font-size: 18px; font-weight: bold; text-align:left;">No. Lab</th>
                        </thead>
                    </table>
                    <div class="card-content table-responsive table-full-width" style="height:55vh; overflow-y:auto; overflow-x:hidden" id="table_selesai">
                        <table id="table_2" class="table table-striped align-middle table-row-dashed fs-6 gy-5" cellspacing="0" width="100%" style="width:100%; font-weight: bold;">
                            <tbody id="data_3">
                            </tbody>
                        </table>
                    </div>
                    <!-- end::Content -->
                </div>
            </div>
            <!--END POST ANALYTICS-->
        </div>
    </div>
    <!--end::Container-->
</div>
<!--end::Content-->

@endsection

@section('scripts')

<!-- Form validation -->
<script src="{{asset('limitless_assets/js/plugins/forms/validation/validate.min.js')}}"></script>
<!-- /Form validation -->

<script src="{{asset('metronic_assets/plugins/custom/datatables/datatables.bundle.js')}}"></script>
<script src="{{asset('js/qms/index_display.js')}}"></script>
@endsection