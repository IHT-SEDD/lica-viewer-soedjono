<!DOCTYPE html>
<!--
Author: Keenthemes
Product Name: Metronic - Bootstrap 5 HTML, VueJS, React, Angular & Laravel Admin Dashboard Theme
Purchase: https://1.envato.market/EA4JP
Website: http://www.keenthemes.com
Contact: support@keenthemes.com
Follow: www.twitter.com/keenthemes
Dribbble: www.dribbble.com/keenthemes
Like: www.facebook.com/keenthemes
License: For each use you must have a valid license purchased only from above link in order to legally use the theme for your project.
-->
<html lang="en">

<head>
 <base href="{{ url('/') }}">
 <title>LICA Viewer - {{ $title }}</title>
 <meta charset="utf-8" />
 <meta name="description"
  content="The most advanced Bootstrap Admin Theme on Themeforest trusted by 94,000 beginners and professionals. Multi-demo, Dark Mode, RTL support and complete React, Angular, Vue &amp; Laravel versions. Grab your copy now and get life-time updates for free." />
 <meta name="keywords"
  content="Metronic, bootstrap, bootstrap 5, Angular, VueJs, React, Laravel, admin themes, web design, figma, web development, free templates, free admin themes, bootstrap theme, bootstrap template, bootstrap dashboard, bootstrap dak mode, bootstrap button, bootstrap datepicker, bootstrap timepicker, fullcalendar, datatables, flaticon" />
 <meta name="viewport" content="width=device-width, initial-scale=1" />
 <meta property="og:locale" content="en_US" />
 <meta property="og:type" content="article" />
 <meta property="og:title"
  content="Metronic - Bootstrap 5 HTML, VueJS, React, Angular &amp; Laravel Admin Dashboard Theme" />
 <meta property="og:url" content="https://keenthemes.com/metronic" />
 <meta property="og:site_name" content="Keenthemes | Metronic" />
 <link rel="canonical" href="https://preview.keenthemes.com/metronic8" />
 <link rel="shortcut icon" href="{{asset('metronic_assets/media/logos/favicon-lica2.png')}}" />

 <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Montserrat:300,400,500,600,700" />

 <!--begin::Page Vendor Stylesheets(used by this page)-->
 @yield('styles')
 <!--end::Page Vendor Stylesheets-->

 <!--begin::Global Stylesheets Bundle(used by all pages)-->
 <link href="{{ asset('metronic_assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
 <link href="{{ asset('metronic_assets/css/style.bundle.css')}}" rel="stylesheet" type="text/css" />
 <link href="{{ asset('css/app-custom.css?ver=1.0.2')}}" rel="stylesheet" type="text/css" />
 <!--end::Global Stylesheets Bundle-->

 <style>
  #cover-spin {
   position: fixed;
   width: 100%;
   left: 0;
   right: 0;
   top: 0;
   bottom: 0;
   background-color: rgba(255, 255, 255, 0.7);
   z-index: 9999;
   display: none;
  }

  @-webkit-keyframes spin {
   from {
    -webkit-transform: rotate(0deg);
   }

   to {
    -webkit-transform: rotate(360deg);
   }
  }

  @keyframes spin {
   from {
    transform: rotate(0deg);
   }

   to {
    transform: rotate(360deg);
   }
  }

  #cover-spin::after {
   content: '';
   display: block;
   position: absolute;
   left: 48%;
   top: 40%;
   width: 40px;
   height: 40px;
   border-style: solid;
   border-color: #ffb582;
   border-top-color: transparent;
   border-width: 4px;
   border-radius: 50%;
   -webkit-animation: spin .8s linear infinite;
   animation: spin .8s linear infinite;
  }
 </style>
</head>

<div id="cover-spin"></div>

<body id="kt_body" class="header-fixed header-tablet-and-mobile-fixed toolbar-enabled"
 style="--kt-toolbar-height:55px;--kt-toolbar-height-tablet-and-mobile:55px">

 <!--begin::Root-->
 <div class="d-flex flex-column flex-root">
  <!--begin::Page Wrapper-->
  <div class="page d-flex flex-row flex-column-fluid">
   <!--begin::Wrapper-->
   <div class="wrapper d-flex flex-column flex-row-fluid" id="kt_wrapper">
    <!--begin::Content-->
    <div class="content d-flex flex-column flex-column-fluid" id="kt_content">
     @yield('content')
    </div>
    <!--end::Content-->

    <!--begin::Footer-->
    <div class="footer py-4 d-flex flex-lg-column" id="kt_footer">
     <!--begin::Container-->
     <div class="container-fluid d-flex flex-column flex-md-row align-items-center justify-content-between">
      <!--begin::Copyright-->
      <div class="text-dark order-2 order-md-1">
       <span class="text-muted fw-bold me-1">2022©</span>
       <a href="https://keenthemes.com" target="_blank" class="text-gray-800 text-hover-primary">LICA</a>
      </div>
      <!--end::Copyright-->
     </div>
     <!--end::Container-->
    </div>
    <!--end::Footer-->
   </div>
   <!--end::Wrapper-->
  </div>
  <!--end::Page Wrapper-->
 </div>
 <!--end::Root-->

 <!--begin::Javascript-->
 <script>
  var hostUrl = "{{ url('metronic_assets') }}";
 </script>

 <!--begin::Global Javascript Bundle(used by all pages)-->
 <script src="{{asset('metronic_assets/plugins/global/plugins.bundle.js')}}"></script>
 <script src="{{asset('metronic_assets/js/scripts.bundle.js')}}"></script>
 <script src="{{ asset('js/jquery.printPage.js') }}"></script>
 <!--end::Global Javascript Bundle-->

 <!--begin::Page Vendors Javascript(used by this page)-->
 <script src="{{asset('metronic_assets/plugins/custom/datatables/datatables.bundle.js')}}"></script>
 <script>
  var base = "{{ url('/') }}/";
 </script>

 @yield('scripts')
 <!--end::Page Vendors Javascript-->

 <!--end::Javascript-->

</body>

</html>