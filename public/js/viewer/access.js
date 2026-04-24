let accessFormValidation, accessData, datepicker, birthDateInput;

var baseUrl = function (url) {
    return base + url;
};

$.ajaxSetup({
    headers: {
        "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
    },
});

birthDateInput = () => {
    const thisYear = new Date().getFullYear(); // returns the current year
    datepicker = $("#birthdate-input").flatpickr({
        altInput: true,
        altFormat: "j F Y",
        dateFormat: "Y-m-d",
        static: true,
        onChange: function () {
            areAllFilled();
        },
    });
    datepicker.jumpToDate(new Date(thisYear - 20, 0, 1));
};

var areAllFilled = function () {
    var filled = true;

    $("#form-access input").each(function () {
        if ($(this).val() == "" || $(this).val() == null) {
            filled = false;
        }
    });

    $(".submit-btn").prop("disabled", !filled);
};

var accessFormRulesValidation = {
    medrec: {
        required: true,
    },
    birthdate: {
        required: true,
    },
};

accessFormValidation = () => {
    if (!$().validate) {
        console.warn("Warning - validate.min.js is not loaded.");
        return;
    }

    $("#form-access").validate({
        ignore: "input[type=hidden], .select2-search__field, .ignore-this",
        errorClass: "fv-plugins-message-container invalid-feedback",
        successClass: "validation-valid-label",
        validClass: "validation-valid-label",
        highlight: function (element, errorClass) {
            $(element).removeClass(errorClass);
        },
        unhighlight: function (element, errorClass) {
            $(element).removeClass(errorClass);
        },
        errorPlacement: function (error, element) {
            // Unstyled checkboxes, radios
            if (element.parents().hasClass("form-check")) {
                error.appendTo(element.parents(".form-check").parent());
            }

            // Input with icons and Select2
            else if (
                element.parents().hasClass("form-group-feedback") ||
                element.hasClass("select2-hidden-accessible")
            ) {
                error.appendTo(element.parent());
            }

            // Input group, styled file input
            else if (
                element.parent().is(".uniform-uploader, .uniform-select") ||
                element.parents().hasClass("input-group")
            ) {
                error.appendTo(element.parent().parent());
            }

            // Other elements
            else {
                error.insertAfter(element);
            }
        },
        rules: accessFormRulesValidation,
        messages: {
            custom: {
                required: "This is a custom error message",
            },
        },
    });

    $("#form-access").on("submit", function (e) {
        e.preventDefault();
        if (typeof ckeditor != "undefined") {
            ckeditor.updateSourceElement();
        }
        if ($(this).valid()) {
            accessData();
        }
    });
};

accessData = () => {
    $("#submit_btn").prop("disabled", true);

    let formData = $("#form-access").serialize();
    let theForm = $("#form-access");

    $.ajax({
        url: baseUrl("viewer/access"),
        method: "POST",
        data: formData,
        success: function (res) {
            toastr.success(res.message, "Access Success!");

            $("#submit_btn").prop("disabled", false); // re-enable submit button
            theForm.trigger("reset"); // reset form after successfully create data
            $(".select-two").val(null).trigger("change"); // unselect all the select form
            $("#form-create input:visible:enabled:first").trigger("focus"); // set focus to first element of input

            setTimeout(function () {
                window.location.href = res.redirect_url;
            }, 1500);
        },
        error: function (request, status, error) {
            toastr.error(request.responseJSON.message);
            $("#submit_btn").prop("disabled", false); // re-enable button
            setTimeout(function () {
                window.location.href = res.redirect_url;
            }, 1500);
        },
    });
};

document.addEventListener("DOMContentLoaded", function () {
    birthDateInput();
    accessFormValidation();
    $(".submit-btn").prop("disabled", true);
    $(".access-form").on("change", function (e) {
        areAllFilled();
    });
});
