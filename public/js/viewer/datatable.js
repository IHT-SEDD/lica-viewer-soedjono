var baseUrl = function (url) {
    return base + url;
};

let segments = window.location.pathname.split("/");
let medrec = segments[segments.length - 1];
let params = new URLSearchParams(window.location.search);
let granted = params.get("granted");

var theFullDate = function (date) {
    const monthNames = [
        "January",
        "February",
        "March",
        "April",
        "May",
        "June",
        "July",
        "August",
        "September",
        "October",
        "November",
        "December",
    ];
    let d = new Date(date);
    let day = d.getDate();
    let month = d.getMonth() + 1;
    let year = d.getFullYear();
    let theDate = day + "/" + ("0" + month).slice(-2) + "/" + year;

    return theDate;
};

var newFormId = "#new-pre-analytics";

var buttonActionIndex = 6;
var columnsDataTable = [
    {
        data: "created_time",
        render: function (data, type, row) {
            return theFullDate(data);
        },
    },
    { data: "no_lab", orderable: true },
    { data: "patient_medrec" },
    { data: "patient_name" },
    { data: "room_name" },
    {
        data: "cito",
        render: function (data, type, row) {
            let cito = "";
            if (row.cito != null && row.cito != 0) {
                cito =
                    '<i class="bi bi-exclamation-triangle-fill text-warning" data-toggle="tooltip" data-placement="top" title="CITO"></i>';
            }

            let igd = "";
            if (row.is_igd == 1) {
                igd = `<span style="color:red" data-toggle="tooltip" data-placement="top" title="Ruang IGD"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-heart-pulse" viewBox="0 0 16 16">
              <path fill-rule="evenodd" d="m8 2.748-.717-.737C5.6.281 2.514.878 1.4 3.053.918 3.995.78 5.323 1.508 7H.43c-2.128-5.697 4.165-8.83 7.394-5.857.06.055.119.112.176.171a3.12 3.12 0 0 1 .176-.17c3.23-2.974 9.522.159 7.394 5.856h-1.078c.728-1.677.59-3.005.108-3.947C13.486.878 10.4.28 8.717 2.01L8 2.748ZM2.212 10h1.315C4.593 11.183 6.05 12.458 8 13.795c1.949-1.337 3.407-2.612 4.473-3.795h1.315c-1.265 1.566-3.14 3.25-5.788 5-2.648-1.75-4.523-3.434-5.788-5Zm8.252-6.686a.5.5 0 0 0-.945.049L7.921 8.956 6.464 5.314a.5.5 0 0 0-.88-.091L3.732 8H.5a.5.5 0 0 0 0 1H4a.5.5 0 0 0 .416-.223l1.473-2.209 1.647 4.118a.5.5 0 0 0 .945-.049l1.598-5.593 1.457 3.642A.5.5 0 0 0 12 9h3.5a.5.5 0 0 0 0-1h-3.162l-1.874-4.686Z"/>
            </svg> </span>`;
            }

            let print = "";
            if (row.print > 0) {
                print =
                    '<i class="ms-2 bi bi-check-square-fill text-info" data-toggle="tooltip" data-placement="top" title="printed"></i>';
            }

            return "<div>" + cito + " " + igd + " " + print + "</div>";
        },
        defaultContent: "",
        responsivePriority: 1,
    },
];

// Datatable Component
var selectedTransactionId;
var ResultDatatable = (function () {
    // Shared variables
    var table;
    var dt;
    var filterPayment;
    var selectorName = ".result-list-datatable-ajax";
    // Private functions
    var initDatatable = function () {
        dt = $(selectorName).DataTable({
            paging: false,
            scrollY: "355px",
            scrollX: "100%",
            select: {
                style: "single",
            },
            order: [[0, "desc"]],
            responsive: false,
            searchDelay: 500,
            processing: true,
            serverSide: true,
            stateSave: false,
            ajax: {
                url: baseUrl("viewer/datatable/"),
                data: function (d) {
                    d.medrec = medrec;
                    d.granted = granted;
                },
            },
            columns: columnsDataTable,
        });

        table = dt.$;

        // Re-init functions on every table re-draw -- more info: https://datatables.net/reference/event/draw
        dt.on("draw", function () {
            // initToggleToolbar();
            // toggleToolbars();
            KTMenu.createInstances();
        });

        dt.on("select", function (e, data, type, indexes) {
            const selectedData = data.rows().data()[indexes];
            selectedTransactionId = selectedData.id;
            onSelectTransaction(selectedData);
        });
    };

    // Search Datatable --- official docs reference: https://datatables.net/reference/api/search()
    // var handleSearchDatatable = function () {
    //     const filterSearch = document.querySelector(
    //         '[data-kt-docs-table-filter="search-result-list"]',
    //     );
    //     filterSearch.addEventListener("keyup", function (e) {
    //         dt.search(e.target.value).draw();
    //     });
    // };

    // Init toggle toolbar
    // var initToggleToolbar = function () {
    //     // Toggle selected action toolbar
    //     // Select all checkboxes
    //     const container = document.querySelector(selectorName);
    //     const checkboxes = container.querySelectorAll('[type="checkbox"]');

    //     // Toggle delete selected toolbar
    //     checkboxes.forEach((c) => {
    //         // Checkbox on click event
    //         c.addEventListener("click", function () {
    //             setTimeout(function () {
    //                 // toggleToolbars();
    //             }, 50);
    //         });
    //     });
    // };

    // Toggle toolbars
    // var toggleToolbars = function () {
    //     // Define variables
    //     const container = document.querySelector(selectorName);
    //     const toolbarSelected = document.querySelector(
    //         '[data-kt-docs-table-toolbar="selected"]',
    //     );
    //     const selectedCount = document.querySelector(
    //         '[data-kt-docs-table-select="selected_count"]',
    //     );

    //     // Select refreshed checkbox DOM elements
    //     const allCheckboxes = container.querySelectorAll(
    //         'tbody [type="checkbox"]',
    //     );

    //     // Detect checkboxes state & count
    //     let checkedState = false;
    //     let count = 0;

    //     // Count checked boxes
    //     allCheckboxes.forEach((c) => {
    //         if (c.checked) {
    //             checkedState = true;
    //             count++;
    //         }
    //     });

    //     // Toggle toolbars
    //     if (checkedState) {
    //         selectedCount.innerHTML = count;
    //         toolbarSelected.classList.remove("d-none");
    //     } else {
    //         toolbarSelected.classList.add("d-none");
    //     }
    // };

    // Public methods
    return {
        init: function () {
            initDatatable();
            // handleSearchDatatable();
            // initToggleToolbar();
            // handleFilterDatatable();
            // handleDeleteRows();
            // handleResetForm();
        },
        refreshTable: function () {
            dt.ajax.reload();
        },
        refreshTableAjax: function (url) {
            dt.ajax.url(url).load();
        },
    };
})();
