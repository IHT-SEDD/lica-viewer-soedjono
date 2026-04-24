var baseUrl = function(url) {
    return base + url;
  }
  
  var theFullDate = function(date) {
    const monthNames = [
        "January", "February", "March", "April", "May", "June",
        "July", "August", "September", "October", "November", "December"
    ];
    let d = new Date(date);
    let day = d.getDate();
    let month = d.getMonth() + 1;
    let year = d.getFullYear();
    let theDate = day + '/' + ('0'+month).slice(-2)  + '/' + year;
  
    return theDate;
  }

  var theFullMinute = function(date) {
    
    let d = new Date(date);
    let hours = d.getHours();
    let minutes = d.getMinutes();
    let seconds = d.getSeconds();
    let theMinute = hours + ':' + minutes  + ':' + seconds;
  
    return theMinute;
  }


   // ===============================
  // Column Inventory
  // ===============================
  var columnsDataTableInventoryData = [
    { data: 'product.name', name : 'product.name' },
    { data: 'product.unit', name : 'product.unit' },
    { data: 'stock', name: 'stock', orderable: false, searchable: false,
    render: function (data, type, row, meta) {
        let badgeClass = '';

        if (row.status == 1) {
            badgeClass = 'bg-warning';
        } else if (row.status == 2) {
            badgeClass = 'bg-danger'; 
        } 

        return `<span class="badge ${badgeClass} text-dark">${data ?? '-'}</span>`;     
    }
    }
  ];


  var DatatablesInventoryServerSide = function () {
    // Shared variables
    var table;
    var dt;
    var filterPayment;
    var buttonActionIndex = 5;
    var selectorName = '.inventory-datatable-ajax';
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
                url: baseUrl('inventory/datatable-inventory')
            },
            columns: columnsDataTableInventoryData,
            // columnDefs: [
            //     {
            //         targets: 3, // kolom stock
            //         render: function (data, type, row) {
            //             return `
            //                 <input type="text" 
            //                     class="form-control form-control-sm stock-input" 
            //                     data-id="${row.id}"
            //                     name="stock[${row.id}]" 
            //                     value="${data ?? ''}" 
            //                     style="width: 80px;">
            //             `;
            //         }
            //     },
            //     {
            //         targets: 4, // kolom test
            //         render: function (data, type, row) {
            //             return `
            //                 <input type="text" 
            //                     class="form-control form-control-sm test-input test-${row.id}" 
            //                     data-id="${row.id}"
            //                     name="test[${row.id}]" 
            //                     value="${data ?? ''}" 
            //                     style="width: 100px;" readonly>
            //             `;
            //         }
            //     },
            //     {
            //         responsivePriority: 1,
            //         targets: buttonActionIndex,
            //         data: null,
            //         orderable: false,
            //         searchable: false,
            //         render: function (data, type, row) {
            //             return `
            //                 <button class="btn btn-info btn-sm px-2"
            //                     onClick='insertProductOut(${JSON.stringify(row)})'>
            //                     <i class="bi bi-card-text"></i>
            //                 </button>
            //             `;
            //         },
            //     }
            // ],            
        });
  
        table = dt.$;
  
    }
  
    // Search Datatable --- official docs reference: https://datatables.net/reference/api/search()
    var handleSearchDatatableInventory = function () {
        const filterSearch = document.querySelector('[data-kt-docs-table-filter="search-inventory"]');
        filterSearch.addEventListener('keyup', function (e) {
            dt.search(e.target.value).draw();
        });
    }

    const inputValue = function () {
        dt.on('draw', function () {
            let debounceTimer;
            // stock input
            $('.stock-input').off('keyup').on('keyup', function (e) {
                let val = e.target.value;
                clearTimeout(debounceTimer);
                // validasi hanya angka
                // if (!/^\d*$/.test(val)) {
                //     e.target.value = val.replace(/\D/g, ''); 
                //     return;
                // }
        
                let id = $(this).data('id');
                console.log( $(this).data());

                debounceTimer = setTimeout(() => {
                    updateInventoryValue(id, 'stock', e.target.value);
                }, 1500);
            });
        
        });        
    }

    const selectRow = function () {
        $('.inventory-datatable-ajax tbody').off('click', 'tr').on('click', 'tr', function () {
            const data = dt.row(this).data();
            refreshProcessTimeDatatables(data.id);
            // bisa juga: tambahkan efek selected
            $('.inventory-datatable-ajax tbody tr').removeClass('selected');
            $(this).addClass('selected');
        });
    }
  
    // Public methods
    return {
        init: function () {
            initDatatable();
            handleSearchDatatableInventory();
            selectRow();
        },
        refreshTable: function() {
            dt.ajax.reload();
        },
        refreshTableAjax: function(url) {
            dt.ajax.url(url).load();
        }
    }
  }();

  function updateInventoryValue(id, field, value) {

    $.ajax({
        url: baseUrl('inventory/update-field'),
        type: 'PUT',
        data: {
            id: id,
            field: field,
            value: value,
            _token: $('meta[name="csrf-token"]').attr('content'),
        },
        success: function (res) {
             toastr.success(res.message);
             $('.test-'+res.id).val(res.test);
        },
        error: function (xhr) {
             toastr.error(xhr.responseText);
            console.error('Update failed', xhr.responseText);
        }
    });
}

    function insertProductOut (row){

        $('#add-product-out-modal input[name="id"]').val(row.id);
        $('#add-product-out-modal input[name="name"]').val(row.product.name);
        $('#add-product-out-modal input[name="unit"]').val(row.product.unit);

        $('#add-product-out-modal').modal('show');
    }

    let productIds = [];
    var Select2ServerSideModal = function (theData, modalId) {
        var _componentSelect2 = function() {
            // Initialize
             $('.select-' + theData).select2({
                allowClear: true,
                ajax: {
                    url: baseUrl('inventory/select-options-product/'),
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            query: params.term || '',
                            except_ids: productIds // kirim array ID ke server
                        };
                    },
                    processResults: function (data, params) {
      
                        // parse the results into the format expected by Select2
                        // since we are using custom formatting functions we do not need to
                        // alter the remote JSON data, except to indicate that infinite
                        // scrolling can be used
                        // params.page = params.page || 1;
      
                      return {
                          results: $.map(data, function(item){
                              var additionalText = '';
                              var PrefixText = '';
                              if (theData == 'room') {
                                  additionalText = `<i> Class `+item.class+`</i>`;
                              }
                              if ((theData == 'patient')) {
                                  let medrec = item.medrec;
                                  let pad = "";
                                  // for (var i = 0; i < 20-medrec.length; i++) {
                                  //     pad += "0";
                                  // }
                                  PrefixText = medrec+pad+" - "
                              }  
                              return {
                                  text:PrefixText + item.name + additionalText,
                                  id: item.id
                              }
                          })
                      };
                    },
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
                },
                dropdownParent: $(`${modalId} .modal-body`)
            });
      
        }
      
        //
        // Return objects assigned to module
        //
      
        return {
            init: function() {
                _componentSelect2();
            }
        }
      }


      var DateRangePickerProductOut = () => {
        var start = moment();
        var end = moment();
      
        function cb(start, end) {
            $("#daterange-picker-product-out").html(start.format("YYYY-MM-DD") + "," + end.format("YYYY-MM-DD"));
            const startDate = $("#daterange-picker-product-out").data('daterangepicker').startDate.format('YYYY-MM-DD');
            const endDate = $("#daterange-picker-product-out").data('daterangepicker').endDate.format('YYYY-MM-DD');
        }
      
        $("#daterange-picker-product-out").daterangepicker({
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
      
        $("#daterange-picker-product-out").on('change', function () {
          const startDate = $(this).data('daterangepicker').startDate.format('YYYY-MM-DD');
          const endDate = $(this).data('daterangepicker').endDate.format('YYYY-MM-DD');
          const url = baseUrl('inventory/datatable-product-out/'+startDate+'/'+endDate);
          DatatablesProductOutServerSide.refreshTableAjax(url);
        });
      }

 // ===============================
  // Column Product Out
  // ===============================
  var columnsDataTableProductOutData = [
    { data: 'created_at',
    render: function (data, type, row, meta) {
        var date = moment(row.created_at).format("YYYY/MM/DD HH:mm");
        return date;
    }
    },
    { data: 'product.name', name : 'product.name' },
    { data: 'product.unit', name : 'product.unit' },
    { data: 'user.name', name : 'user.name' },
    { data: 'stock', name: 'stock', orderable: false, searchable: false,
    render: function (data, type, row, meta) {
        let badgeClass = '';

        if (row.status == 1) {
            badgeClass = 'bg-warning';
        } else if (row.status == 2) {
            badgeClass = 'bg-danger'; 
        } 

        return `<span class="badge ${badgeClass} text-dark">${data ?? '-'}</span>`;     
    }
     },
    { data: 'qty', name: 'qty', orderable: false, searchable: false },
  ];


  var DatatablesProductOutServerSide = function () {
    // Shared variables
    var table;
    var dtProductOut;
    var filterPayment;
    // var buttonActionIndex = 4;
    var selectorName = '.product-out-datatable-ajax';
    // Private functions
    var initDatatable = function () {
        dtProductOut = $(selectorName).DataTable({
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
                url: baseUrl('inventory/datatable-product-out/')
            },
            columns: columnsDataTableProductOutData,         
        });
  
        table = dtProductOut.$;
  
    }
  
    // Search Datatable --- official docs reference: https://datatables.net/reference/api/search()
    var handleSearchDatatableProductOut = function () {
        const filterSearch = document.querySelector('[data-kt-docs-table-filter="search-product-out"]');
        filterSearch.addEventListener('keyup', function (e) {
            dtProductOut.search(e.target.value).draw();
        });
    }
  
    // Public methods
    return {
        init: function () {
            initDatatable();
            handleSearchDatatableProductOut();
        },
        refreshTable: function() {
            dtProductOut.ajax.reload();
        },
        refreshTableAjax: function(url) {
            dtProductOut.ajax.url(url).load();
        }
    }
  }();

  const formAddProductOut = () => {
    $('#form-add-product-out').on('submit', function (e) { 
        e.preventDefault();
        // $(".btn-submit-product").prop('disabled', true);
        let formData = $(this).serialize();
        let form = $(this);
        $.ajax({
            url: baseUrl('inventory/create-product-out'),
            method: 'POST',
            data: formData,
            success: function(res) {
                toastr.success(res.message, "Create Success!");
                form.trigger('reset');
                $('.select-analyzer').val(null).trigger('change');
                $(".btn-submit-product").prop('disabled', false);
                DatatablesInventoryServerSide.refreshTable();
                $('#add-product-out-modal').modal('hide');
            },
            error: function (request, status, error) {
                toastr.error(request.responseJSON.message);
                $(".btn-submit-product").prop('disabled', false); // re-enable button
            }
        })

      });
  }

  const createStock = () => {

    $('#form-stock').on('submit', function (e) {
        e.preventDefault();
        // $(".btn-submit-product").prop('disabled', true);
        let formData = $(this).serialize();
        let form = $(this);
        $.ajax({
            url: baseUrl('inventory/create-add-stock'),
            method: 'POST',
            data: formData,
            success: function(res) {
                toastr.success(res.message, "Create Success!");
                form.trigger('reset');
                $('.select-product').val(null).trigger('change');
                // $(".btn-submit-product").prop('disabled', false);
                DatatablesInventoryServerSide.refreshTable();
                productIds = [];
                $('#add-stock-modal').modal('hide');
            },
            error: function (request, status, error) {
                toastr.error(request.responseJSON.message);
                $(".btn-submit-product").prop('disabled', false); // re-enable button
            }
        })
        
    });
  }

  const createProductOut = () => {

    $('#form-product-out').on('submit', function (e) {
        e.preventDefault();
        // $(".btn-submit-product").prop('disabled', true);
        let formData = $(this).serialize();
        let form = $(this);
        $.ajax({
            url: baseUrl('inventory/product-out'),
            method: 'POST',
            data: formData,
            success: function(res) {
                toastr.success(res.message, "Create Success!");
                form.trigger('reset');
                $('.select-product').val(null).trigger('change');
                // $(".btn-submit-product").prop('disabled', false);
                DatatablesInventoryServerSide.refreshTable();
                DatatablesProductOutServerSide.refreshTable();
                productIds = [];
                $('#product-out-modal').modal('hide');
            },
            error: function (request, status, error) {
                toastr.error(request.responseJSON.message);
                $(".btn-submit-product").prop('disabled', false); // re-enable button
            }
        })
        
    });
  }

  // ===============================
  // Column Log Product In
  // ===============================
  var columnsDataTableLogInStockData = [
    { data: 'product.name', name : 'product.name' },
    { data: 'product.unit', name : 'product.unit' },
    { data: 'user.name', name : 'user.name' },
    { data: 'note', name: 'stock', orderable: false, searchable: false },
    { data: 'created_at', name: 'created_at', orderable: false, searchable: false,
        render: function (data, type, row, meta) {
        var date = moment(data).format("YYYY/MM/DD HH:mm");
        return date;
    }
     },
  ];

  let DatatablesLogInStockServerSide;

  const refreshProcessTimeDatatables = (inventoryId) => {
    if (!$.fn.DataTable.isDataTable('.log-in-stock-datatable-ajax')) {
      DatatablesLogInStockServerSide = $('.log-in-stock-datatable-ajax').DataTable({
        paging: false,
        scrollY: '230px',
        responsive: true,
        searchDelay: 500,
        processing: true,
        serverSide: true,
        sort: false,
        stateSave: false,
        ajax: {
          url: baseUrl('inventory/datatable-log-in-stock/' + inventoryId)
        },
        columns: columnsDataTableLogInStockData
      });
    } else {
      DatatablesLogInStockServerSide.ajax
        .url(baseUrl('inventory/datatable-log-in-stock/' + inventoryId))
        .load();
    }
  };
  

    // On document ready
    document.addEventListener('DOMContentLoaded', function () {
        DatatablesInventoryServerSide.init();
        DatatablesProductOutServerSide.init();
        Select2ServerSideModal('product', '#add-stock-modal').init();
        Select2ServerSideModal('product-out', '#product-out-modal').init();
        DateRangePickerProductOut();
        // formAddProductOut();
        createStock();
        createProductOut();

        $(document).on('change', '.select-product, .select-product-out', function () {
            const selectedId = $(this).val();
        
            const oldId = $(this).data('old-id');
            if (oldId) {
                productIds = productIds.filter(id => id !== oldId);
            }

            if (selectedId) {
                productIds.push(selectedId);
            }

            $(this).data('old-id', selectedId);
        });

        $('#products').repeater({
            initEmpty: false,
        
            defaultValues: {
                'text-input': 'foo'
            },
        
            show: function () {
                Select2ServerSideModal('product', '#add-stock-modal').init();
                $(this).slideDown();
                
                $(".select2-container:nth-child(4)").remove('');
                
            },
        
            hide: function (deleteElement) {
                const productId = $(this).find('.select-product').val();
                if (productId) {
                    productIds = productIds.filter(id => id !== productId);
                }
                $(this).slideUp(deleteElement);
                if ($('#test-code div[data-repeater-item]').length < 1) {
                  $("a[data-repeater-create]").parents('.form-group').addClass('d-none');
                } else {
                  $("a[data-repeater-create]").parents('.form-group').removeClass('d-none');
                }
            }
          });

          $('#product-outs').repeater({
            initEmpty: false,
        
            defaultValues: {
                'text-input': 'foo'
            },
        
            show: function () {
                Select2ServerSideModal('product-out', '#product-out-modal').init();
                $(this).slideDown();
                
                $(".select2-container:nth-child(4)").remove('');
                
            },
        
            hide: function (deleteElement) {
                const productId = $(this).find('.select-product').val();
                if (productId) {
                    productIds = productIds.filter(id => id !== productId);
                }
                $(this).slideUp(deleteElement);
                if ($('#test-code div[data-repeater-item]').length < 1) {
                  $("a[data-repeater-create]").parents('.form-group').addClass('d-none');
                } else {
                  $("a[data-repeater-create]").parents('.form-group').removeClass('d-none');
                }
            }
          });
    });