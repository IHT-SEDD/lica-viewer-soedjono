@extends('layouts.viewer-layout')

@section('styles')
@endsection

@section('content')
<div
 class="d-flex flex-column flex-column-fluid bgi-position-y-bottom position-x-center bgi-no-repeat bgi-size-contain bgi-attachment-fixed">
 <div class="d-flex flex-center flex-column flex-column-fluid p-10 pb-lg-20">
  <!--begin::Logo-->
  <div class="d-flex flex-center p-10; w-lg-400px">
   <img src="{{asset('images/logo-diponegoro.png')}}" style="height: 100px; width: 100px;">
  </div>
  <!--begin::Logo-->

  <br>

  <div class="w-lg-500px bg-body rounded shadow-sm p-10 p-lg-15 mx-auto">
   <!--begin::Form-->
   {!! Form::open(['class' => 'access-form form-validate-jquery', 'id' => 'form-access']) !!}

   <!--begin::Heading-->
   <div class="text-center mb-10">
    <!--begin::Title-->
    <h1 class="text-dark mb-3">Access Lica Viewer</h1>
    <!--end::Title-->
    <!--begin::Subtitle-->
    <div class="text-gray-400 fw-bold fs-5">Masukkan nomor rekam medis dan tanggal lahir untuk mengakses data.</div>
    <!--end::Subtitle-->
   </div>
   <!--begin::Heading-->

   <!--begin::Medrec Input-->
   <div class="fv-row mb-5">
    <!--begin::Label-->
    <label class="form-label fs-6 fw-bolder text-dark">Nomor Rekam Medis</label>
    <!--end::Label-->
    <!--begin::Input-->
    {{ Form::text('medrec', null, ['class' => 'form-control form-control-md form-control-solid', 'id' =>
    'medrec-input']) }}
    <!--end::Input-->
   </div>
   <!--end::Medrec Input-->

   <!--begin::Date Birth-->
   <div class="fv-row mb-5">
    <!--begin::Label-->
    <label class="form-label fs-6 fw-bolder text-dark">Tanggal Lahir</label>
    <!--end::Label-->
    <!--begin::Input-->
    <div>
     {{ Form::text('birthdate', null, ['class' => 'form-control form-control-md form-control-solid birthdate req-input'
     , 'id' =>
     'birthdate-input']) }}
    </div>
    <!--end::Input-->
   </div>
   <!--end::Date Birth-->

   <!--begin::Actions-->
   <div class="text-center">
    <!--begin::Submit button-->
    <button type="submit" class="btn btn-md btn-primary w-100 mb-5 submit-btn" id="submit_btn">
     <span class="indicator-label">Access</span>
     <span class="indicator-progress">Please wait...
      <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
     </span>
    </button>
    <!--end::Submit button-->
   </div>
   <!--end::Actions-->

   {!! Form::close() !!}
   <!--end::Form-->
  </div>
 </div>
</div>
@endsection

@section('scripts')
<!-- Form validation -->
<script src="{{asset('limitless_assets/js/plugins/forms/validation/validate.min.js')}}"></script>
<!-- /Form validation -->
<script>
 document.write("<script type='text/javascript' src='{{asset('js/viewer/access.js?v')}}" + Date.now() + "'><\/script>");
</script>
@endsection