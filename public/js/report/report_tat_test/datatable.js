var baseUrl = function(url) {
    return base + url;
}
  
var columnsDataTable = [
    {
      data: "id",
        render: function (data, type, row, meta) {
            return meta.row + meta.settings._iDisplayStart + 1;
        }
    },
    { data: 'created_time',
        render: function (data, type, row, meta) {
            var date = moment(row.created_time).format("DD/MM/YYYY");
            return date;
        }
    },
    { data: 'no_lab' },
    { data: 'test_name' },
    { data: 'checkin_time', 
        render: function (data, type, row, meta) {
            var checkin_time = moment(row.checkin_time).format("HH:mm:ss");
            var date = moment(row.created_time).format("DD/MM/YYYY");
            return date +' '+checkin_time;
        }
    },
    { data: 'analytic_time',
        render: function (data, type, row, meta) {
            var checkin_time = moment(row.checkin_time).format("DD/MM/YYYY HH:mm:ss");
            var analytic = moment(row.analytic_time).format("HH:mm:ss");
            var analytic_time = moment.utc(moment(analytic, "HH:mm:ss").diff(moment(checkin_time, "HH:mm:ss"))).format("HH:mm:ss");

            return  checkin_time;
        }
    },
    { data: 'verify_time',
        render: function (data, type, row, meta) {
            var checkin_time = moment(row.checkin_time).format("HH:mm:ss");
            var verify = moment(row.verify_time).format("DD/MM/YYYY HH:mm:ss");
            var verify_time = moment.utc(moment(verify, "HH:mm:ss").diff(moment(checkin_time, "HH:mm:ss"))).format("HH:mm:ss");

            return verify;
        }
    },
    { data: 'validate_time', name: 'finish_transaction_tests.validate_time',
        render: function (data, type, row, meta) {
            var checkin_time = moment(row.checkin_time).format("HH:mm:ss");
            var validate = moment(row.validate_time).format("DD/MM/YYYY HH:mm:ss");
            var validate_time = moment.utc(moment(validate, "HH:mm:ss").diff(moment(checkin_time, "HH:mm:ss"))).format("HH:mm:ss");

            return validate;
        }
    },
    {
        data: 'post_time',
        render: function (data, type, row, meta) {
            var checkin_time = moment(row.checkin_time);
            var post_time = moment(row.post_time);
    
            if (!checkin_time.isValid() || !post_time.isValid()) {
                return '-';
            }
    
            checkin_time = checkin_time.format("HH:mm:ss");
            post_time = post_time.format("DD/MM/YYYY HH:mm:ss");
            
            var diff_time = moment.utc(moment(post_time, "HH:mm:ss").diff(moment(checkin_time, "HH:mm:ss"))).format("HH:mm:ss");
            
            return post_time;
        }
    },
    
    { data: 'id', 
        render: function (data, type, row, meta) {
            var checkin_time = moment(row.checkin_time, "YYYY-MM-DD HH:mm:ss");
            var post_time = moment(row.post_time, "YYYY-MM-DD HH:mm:ss");
            
            // ubah ke timestamp
            var checkin_ts = checkin_time.valueOf();
            var post_ts = post_time.valueOf();
            
            // hitung selisih (positif)
            var diffMs = post_ts - checkin_ts; // hasil milidetik
            if (diffMs < 0) diffMs = Math.abs(diffMs); // jaga-jaga kalau terbalik
            
            // konversi ke detik
            var diffSeconds = Math.floor(diffMs / 1000);
            
            // ubah ke jam, menit, detik
            var hours = Math.floor(diffSeconds / 3600);
            var minutes = Math.floor((diffSeconds % 3600) / 60);
            var seconds = diffSeconds % 60;
            
            // format 2 digit
            var formatted = 
              String(hours).padStart(2, '0') + ':' +
              String(minutes).padStart(2, '0') + ':' +
              String(seconds).padStart(2, '0');
            
            console.log("Durasi:", formatted);

            return formatted;
        }
    }
  ];
  
  // Datatable Component
  var selectedTransactionId;
  var Datatable = function () {
    // Shared variables
    var dt;
    var selectorName = '.datatable-ajax';
    // Private functions
    var initDatatable = function () {
        dt = $(selectorName).DataTable({
            paging: false,
            scrollY: '400px',
            scrollX: '100%',
            select: {
              style: 'single'
            },
            order: [[0, 'desc']],
            responsive: true,
            searchDelay: 500,
            processing: true,
            serverSide: true,
            stateSave: false,
            ajax: {
                url: baseUrl('report/tat-test-datatable/')
            },
            columns: columnsDataTable,
            // columnDefs: [{
            //   "defaultContent": "-",
            //   "targets": "_all"
            // }]
        });
    }
  
  
    // Search Datatable --- official docs reference: https://datatables.net/reference/api/search()
    var handleSearchDatatable = function () {
        const filterSearch = document.querySelector('[data-kt-docs-table-filter="search-datatable-tat"]');
        filterSearch.addEventListener('keyup', function (e) {
            dt.search(e.target.value).draw();
        });
    }
  
    // Init toggle toolbar
    var initToggleToolbar = function () {
        // Toggle selected action toolbar
        // Select all checkboxes
        const container = document.querySelector(selectorName);
        const checkboxes = container.querySelectorAll('[type="checkbox"]');
        
        // Toggle delete selected toolbar
        checkboxes.forEach(c => {
            // Checkbox on click event
            c.addEventListener('click', function () {
                setTimeout(function () {
                    // toggleToolbars();
                }, 50);
            });
        });
    }
    
  
    // Toggle toolbars
    var toggleToolbars = function () {
        // Define variables
        const container = document.querySelector(selectorName);
        const toolbarSelected = document.querySelector('[data-kt-docs-table-toolbar="selected"]');
        const selectedCount = document.querySelector('[data-kt-docs-table-select="selected_count"]');
  
        // Select refreshed checkbox DOM elements
        const allCheckboxes = container.querySelectorAll('tbody [type="checkbox"]');
  
        // Detect checkboxes state & count
        let checkedState = false;
        let count = 0;
  
        // Count checked boxes
        allCheckboxes.forEach(c => {
            if (c.checked) {
                checkedState = true;
                count++;
            }
        });
  
        // Toggle toolbars
        if (checkedState) {
            selectedCount.innerHTML = count;
            toolbarSelected.classList.remove('d-none');
        } else {
            toolbarSelected.classList.add('d-none');
        }
    }
  
    // Public methods
    return {
        init: function () {
            initDatatable();
            handleSearchDatatable();
            // initToggleToolbar();
            // handleFilterDatatable();
            // handleDeleteRows();
            // handleResetForm();
        },
        refreshTable: function() {
            dt.ajax.reload();
        },
        refreshTableAjax: function(url) {
            dt.ajax.url(url).load();
        }
    }
  }();