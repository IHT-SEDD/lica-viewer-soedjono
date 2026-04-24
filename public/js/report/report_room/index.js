var baseUrl = function(url) {
    return base + url;
}

function getAgeFull(dateString) {
  var now = new Date();
  var today = new Date(now.getYear(), now.getMonth(), now.getDate());

  var yearNow = now.getYear();
  var monthNow = now.getMonth();
  var dateNow = now.getDate();
  var dob = new Date(dateString);

  var yearDob = dob.getYear();
  var monthDob = dob.getMonth();
  var dateDob = dob.getDate();
  var age = {};
  var ageString = "";
  var yearString = "";
  var monthString = "";
  var dayString = "";


  yearAge = yearNow - yearDob;

  if (monthNow >= monthDob)
    var monthAge = monthNow - monthDob;
  else {
    yearAge--;
    var monthAge = 12 + monthNow - monthDob;
  }

  if (dateNow >= dateDob)
    var dateAge = dateNow - dateDob;
  else {
    monthAge--;
    var dateAge = 31 + dateNow - dateDob;

    if (monthAge < 0) {
      monthAge = 11;
      yearAge--;
    }
  }

  age = {
    years: yearAge,
    months: monthAge,
    days: dateAge
  };

  if (age.years > 1) yearString = "Y";
  else yearString = "Y";
  if (age.months > 1) monthString = "M";
  else monthString = "M";
  if (age.days > 1) dayString = "D";
  else dayString = "D";


  if ((age.years > 0) && (age.months > 0) && (age.days > 0))
    ageString = age.years + yearString + "/" + age.months + monthString + "/" + age.days + dayString + "";
  else if ((age.years == 0) && (age.months == 0) && (age.days > 0))
    ageString = "/" + age.days + dayString + "";
  else if ((age.years > 0) && (age.months == 0) && (age.days == 0))
    ageString = age.years + yearString + "";
  else if ((age.years > 0) && (age.months > 0) && (age.days == 0))
    ageString = age.years + yearString + "/" + age.months + monthString + "";
  else if ((age.years == 0) && (age.months > 0) && (age.days > 0))
    ageString = age.months + monthString + "/" + age.days + dayString + "";
  else if ((age.years > 0) && (age.months == 0) && (age.days > 0))
    ageString = age.years + yearString + "/" + age.days + dayString + "";
  else if ((age.years == 0) && (age.months > 0) && (age.days == 0))
    ageString = age.months + monthString + "";
  else ageString = "Oops! Could not calculate age!";

  return ageString;
}
  
var DateRangePicker = () => {
  var start = moment();
  var end = moment();

  function cb(start, end) {
      $("#daterange-picker").html(start.format("YYYY-MM-DD") + "," + end.format("YYYY-MM-DD"));
      const startDate = $("#daterange-picker").data('daterangepicker').startDate.format('YYYY-MM-DD');
      const endDate = $("#daterange-picker").data('daterangepicker').endDate.format('YYYY-MM-DD');
  }

  $("#daterange-picker").daterangepicker({
      startDate: start,
      endDate: end,
      ranges: {
      "Today": [moment(), moment()],
      "Yesterday": [moment().subtract(1, "days"), moment().subtract(1, "days")],
      "Last 7 Days": [moment().subtract(6, "days"), moment()],
      "Last 30 Days": [moment().subtract(29, "days"), moment()],
      "This Month": [moment().startOf("month"), moment().endOf("month")],
      "Last Month": [moment().subtract(1, "month").startOf("month"), moment().subtract(1, "month").endOf("month")]
      },
      locale:{
          format: 'DD MMMM YYYY'
      },
      alwaysShowCalendars: true
  }, cb);

  cb(start, end);

  $("#daterange-picker").on('change', function () {
      const startDate = $(this).data('daterangepicker').startDate.format('YYYY-MM-DD');
      const endDate = $(this).data('daterangepicker').endDate.format('YYYY-MM-DD');

      let roomId = $("select[name='room_id']").val();
      let url = '';
      
      if (roomId && roomId !== '') {
        url = baseUrl(`report/room-datatable/${startDate}/${endDate}/${roomId}`);
      } else {
        url = baseUrl(`report/room-datatable/${startDate}/${endDate}`);
      }
      
      console.log("Room ID: ", roomId);
      console.log("URL: ", url);
      console.log("Date Filter: ", startDate, endDate);

      Datatable.refreshTableAjax(url);

      const printUrl = roomId && roomId !== ''
        ? baseUrl(`report/room-print/${startDate}/${endDate}/${roomId}`)
        : baseUrl(`report/room-print/${startDate}/${endDate}`);

      const excelUrl = roomId && roomId !== ''
        ? baseUrl(`report/room-excel/${startDate}/${endDate}/${roomId}`)
        : baseUrl(`report/room-excel/${startDate}/${endDate}`);

      $('#print-report').attr("href", printUrl);
      $('#export-report').attr("href", excelUrl);
  });

  $("#room_id").on('change', function () {
    $("#daterange-picker").trigger('change')
  });
}

var Select2ServerSideTest = function () {
    var _componentSelect2 = function () {
      // Initialize
      $('.select-type').select2({
        allowClear: true,
        ajax: {
          url: baseUrl('report/select-room-options'),
          dataType: 'json',
          delay: 250,
          data: function (params) {
            return {
              query: params.term
            };
          },
          processResults: function (data, params) {
            return {
              results: $.map(data, function (item) {
                var additionalText = ''
                var PrefixText = ''
                PrefixText = item.room_name
                // additionalText = " ["+item.general_code+"]"
                return {
                    // text: PrefixText,
                    text: PrefixText + additionalText,
                    id: item.room_id
                }
              })
            };
          },
          cache: true
        },
        escapeMarkup: function (markup) { return markup; }, // let our custom formatter work
        // minimumInputLength: 0,
        // tags: true, // for create new tags
        language: {
          inputTooShort: function () {
            return 'Input is too short';
          },
          errorLoading: function () {
            return `There's error on our side`;
          },
          noResults: function () {
            return 'There are no result based on your search';
          }
        }
  
      });
  
    }
  
    //
    // Return objects assigned to module
    //
  
    return {
      init: function () {
        _componentSelect2();
      }
    }
  }
  
document.addEventListener('DOMContentLoaded', function () {
  Datatable.init();
  DateRangePicker();
  Select2ServerSideTest().init();
  $('.btnPrint').printPage();
  $('body').tooltip({
    selector: '[data-toggle="tooltip"]',
    trigger: 'hover'
  });

});