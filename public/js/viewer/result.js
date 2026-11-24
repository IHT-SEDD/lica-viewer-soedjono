let onSelectTransaction, refreshPatientDatatables, refreshProcessTimeDatatables;

var baseUrl = function (url) {
    return base + url;
};

$.ajaxSetup({
    headers: {
        "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
    },
});

var getAge = function (dateString) {
    var today = new Date();
    var birthDate = new Date(dateString);
    var age = today.getFullYear() - birthDate.getFullYear();
    var m = today.getMonth() - birthDate.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
        age--;
    }

    return age;
};

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

    if (monthNow >= monthDob) var monthAge = monthNow - monthDob;
    else {
        yearAge--;
        var monthAge = 12 + monthNow - monthDob;
    }

    if (dateNow >= dateDob) var dateAge = dateNow - dateDob;
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
        days: dateAge,
    };

    if (age.years > 1) yearString = "Y";
    else yearString = "Y";
    if (age.months > 1) monthString = "M";
    else monthString = "M";
    if (age.days > 1) dayString = "D";
    else dayString = "D";

    if (age.years > 0 && age.months > 0 && age.days > 0)
        ageString =
            age.years +
            yearString +
            "/" +
            age.months +
            monthString +
            "/" +
            age.days +
            dayString +
            "";
    else if (age.years == 0 && age.months == 0 && age.days > 0)
        ageString = "/" + age.days + dayString + "";
    else if (age.years > 0 && age.months == 0 && age.days == 0)
        ageString = age.years + yearString + "";
    else if (age.years > 0 && age.months > 0 && age.days == 0)
        ageString =
            age.years + yearString + "/" + age.months + monthString + "";
    else if (age.years == 0 && age.months > 0 && age.days > 0)
        ageString = age.months + monthString + "/" + age.days + dayString + "";
    else if (age.years > 0 && age.months == 0 && age.days > 0)
        ageString = age.years + yearString + "/" + age.days + dayString + "";
    else if (age.years == 0 && age.months > 0 && age.days == 0)
        ageString = age.months + monthString + "";
    else ageString = "Oops! Could not calculate age!";

    return ageString;
}


var theFullMinute = function (date) {
    let d = new Date(date);
    let hours = d.getHours();
    let minutes = d.getMinutes();
    let seconds = d.getSeconds();
    let theMinute = hours + ":" + minutes + ":" + seconds;

    return theMinute;
};

var getFirstPrint = (transactionId) => {
    $.ajax({
        url: baseUrl("viewer/getFirstPrint/" + transactionId),
        type: "get",
        success: function (data) {
            if (data.data) {
                $(".first-printed-detail").text(data.data);
            } else {
                $(".first-printed-detail").text("-");
            }
        },
    });
};

var Select2ServerSideTest = function (theData, searchKey = "name") {
    var _componentSelect2 = function () {
        // Initialize
        $(".select-" + theData).select2({
            allowClear: true,
            ajax: {
                url: baseUrl(
                    "viewer/select-options/" + theData + "/" + searchKey,
                ),
                dataType: "json",
                delay: 250,
                data: function (params) {
                    return {
                        query: params.term, // search term
                    };
                },
                processResults: function (data, params) {
                    // parse the results into the format expected by Select2
                    // since we are using custom formatting functions we do not need to
                    // alter the remote JSON data, except to indicate that infinite
                    // scrolling can be used
                    // params.page = params.page || 1;

                    return {
                        results: $.map(data, function (item) {
                            var additionalText = "";
                            var PrefixText = "";
                            PrefixText = item.id + " - ";
                            // additionalText = " ["+item.general_code+"]"

                            return {
                                text: PrefixText + item.name + additionalText,
                                id: item.id,
                            };
                        }),
                    };
                },
                cache: true,
            },
            escapeMarkup: function (markup) {
                return markup;
            }, // let our custom formatter work
            // minimumInputLength: 0,
            // tags: true, // for create new tags
            language: {
                inputTooShort: function () {
                    return "Input is too short";
                },
                errorLoading: function () {
                    return `There's error on our side`;
                },
                noResults: function () {
                    return "There are no result based on your search";
                },
            },
        });
    };

    //
    // Return objects assigned to module
    //

    return {
        init: function () {
            _componentSelect2();
        },
    };
};

var printOptions = function () {
    $("#print-hasil-btn").on("click", function () {
        const transactionId = $(this).data("transaction-id");

        $.ajax({
            url: baseUrl("viewer/get-verificator-name/" + transactionId),
            type: "get",
            success: function (data) {
                $("#verificator-hasil-pemeriksaan").val(data.data);

                $("#print-hasil-modal").modal("show");
            },
        });
    });
};

var btnSendResult = function () {
    $("#btn-send-result").on("click", function () {
        const no_order = $(this).data("no-order");

        $.ajax({
            url: baseUrl("api/send_result/" + no_order),
            type: "get",
            success: function (data) {
                if (data.message == "integration_success") {
                    alert("Send data to ERM successfully");
                    // $('#btn-send-result').prop('disabled', true);
                } else {
                    alert("Send data to ERM failed");
                    // $('#btn-send-result').prop('disabled', false);
                }
                PostAnalyticDatatable.refreshTable();
            },
        });
    });
};

var updateNewNoOrder = () => {
    $("#btnSubmitNoOrder").on("click", function () {
        const transactionId = $(this).data("transaction-id");
        let newNoOrder = $("#new-no-order").val();
        $.ajax({
            url: baseUrl("post-analytics/new-no-order/" + transactionId),
            type: "PUT",
            data: {
                newNoOrder: newNoOrder,
            },
            success: function (res) {
                toastr.success(res.message, "Successfully");
                PostAnalyticDatatable.refreshTable();
                $("#send-erm-modal").modal("hide");
            },
            error: function (request, status, error) {
                toastr.error(request.responseJSON.message);
            },
        });
    });
};

onSelectTransaction = (selectedData) => {
    console.log(selectedData);

    const transactionId = selectedData.transaction_id;
    const finishTransactionId = selectedData.id;
    console.log(finishTransactionId, transactionId);

    switch (selectedData.type) {
        case "rawat_jalan":
            patientType = "Rawat Jalan";
            break;
        case "rawat_inap":
            patientType = "Rawat Inap";
            break;
        case "igd":
            patientType = "IGD";
            break;
        case "rujukan":
            patientType = "Rujukan";
            break;
        default:
            patientType = "-";
    }

    // Column 1
    $(".name-detail").html(selectedData.patient_name);
    $(".nik-detail").html(selectedData.patient_nik);
    $(".gender-detail").html(
        selectedData.patient_gender == "M" ? "Laki-laki" : "Perempuan",
    );
    getFirstPrint(finishTransactionId);

    // Column 2
    $(".medrec-detail").html(selectedData.patient_medrec);
    $(".type-detail").html(patientType);
    $(".room-detail").html(selectedData.room_name);
    $(".no-order-detail").html(selectedData.no_order);
    $(".new-no-order-detail").html(selectedData.new_no_order);

    // Column 3
    $(".doctor-detail").html(selectedData.doctor_name);
    $(".note-detail").html(selectedData.note);

    // Memo
    $(".memo-result").html(selectedData.memo_result);
    if (selectedData.is_print_memo == 1) {
        $("#is_memo_print").prop("checked", true);
    } else {
        $("#is_memo_print").prop("checked", false);
    }

    $("#is_memo_print").attr(
        "onChange",
        "printMemoChange(" + finishTransactionId + ",event)",
    );

    // $("#btnPrintHasil").attr(
    //     "href",
    //     baseUrl("viewer/printHasilTest/" + finishTransactionId),
    // );

    $("#btnExportPdfHasil").attr(
        "href",
        baseUrl("viewer/exportPdfHasil/" + finishTransactionId),
    );

    $("#btnPrintHasil").on("click", function () {
        $("#btnPrintHasil").attr(
            "href",
            baseUrl("viewer/printHasilTest/" + finishTransactionId),
        );
    });
    refreshPatientDatatables(finishTransactionId);
    refreshProcessTimeDatatables(finishTransactionId);
};

refreshPatientDatatables = (transactionId) => {
    $.ajax({
        url: baseUrl("viewer/datatable-test/" + transactionId),
        type: "get",
        success: function (data) {
            $("#result-list-table-body").html(data.html);
            data.data.forEach(function (item) {
                $("#print-checkbox-id-" + item.id).attr(
                    "onChange",
                    "printChange(" + item.id + ",event)",
                );
            });
        },
    });
};

refreshProcessTimeDatatables = (transactionId) => {
    if (!$.fn.DataTable.isDataTable(".process-time-table")) {
        transactionProcessTimeTable = $(".process-time-table").DataTable({
            paging: false,
            scrollY: "230px",
            responsive: true,
            searchDelay: 500,
            processing: true,
            serverSide: true,
            // order: [],
            sort: false,
            stateSave: false,
            ajax: {
                url: baseUrl("viewer/datatable-process-time/" + transactionId),
            },
            columns: [
                {
                    data: "id",
                    render: function (data, type, row) {
                        if (row.package_id) {
                            return row.package_name;
                        } else {
                            return row.test_name;
                        }
                    },
                    defaultContent: "",
                },
                {
                    data: "checkin_time",
                    render: function (data, type, row) {
                        return theFullMinute(data);
                    },
                },
                {
                    data: "checkin_by_name",
                    render: function (data, type, row) {
                        if (row.checkin_by_name == null) {
                            return "Auto";
                        } else {
                            return row.checkin_by_name;
                        }
                    },
                    defaultContent: "",
                },
                {
                    data: "draw_time",
                    render: function (data, type, row) {
                        return theFullMinute(data);
                    },
                },
                {
                    data: "draw_by_name",
                    render: function (data, type, row) {
                        if (row.draw_by_name == null) {
                            return "Auto";
                        } else {
                            return row.draw_by_name;
                        }
                    },
                    defaultContent: "",
                },
                {
                    data: "verify_time",
                    render: function (data, type, row) {
                        return theFullMinute(data);
                    },
                },
                { data: "verify_by_name" },
                {
                    data: "validate_time",
                    render: function (data, type, row) {
                        return theFullMinute(data);
                    },
                },
                { data: "validate_by_name" },
                {
                    data: "analytic_time",
                    render: function (data, type, row) {
                        return theFullMinute(data);
                    },
                },
                { data: "draw_by_name" },
            ],
        });
    } else {
        transactionProcessTimeTable.ajax
            .url(baseUrl("viewer/datatable-process-time/" + transactionId))
            .load();
    }
};

function printChange(transactionTestId, e) {
    const component = e.target;
    const value = e.target.value;
    $.ajax({
        url: baseUrl("viewer/update-is-print/" + transactionTestId),
        type: "put",
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
        data: { result: value },
        success: function (res) {
            toastr.success("Success update print status");
        },
        error: function (request, status, error) {
            toastr.error(request.responseJSON.message);
            component.focus();
        },
    });
}

function printMemoChange(transactionId, e) {
    const component = e.target;
    const value = e.target.value;
    $.ajax({
        url: baseUrl("viewer/update-is-print-memo/" + transactionId),
        type: "put",
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
        data: { result: value },
        success: function (res) {
            toastr.success("Success update print status");
        },
        error: function (request, status, error) {
            toastr.error(request.responseJSON.message);
            component.focus();
        },
    });
}

document.addEventListener("DOMContentLoaded", function () {
    ResultDatatable.init();
    printOptions();
    btnSendResult();
    updateNewNoOrder();
    Select2ServerSideTest("group").init();
    $(".btnPrintHasil").printPage();
    $("body").tooltip({
        selector: '[data-toggle="tooltip"]',
        trigger: "hover",
    });
});
