/**
 * Created by sajib on 6/20/2015.
 */

function selectModule(source) {
    let checkboxes = document.getElementsByClassName('checkBox_' + source.value);
    for (let i = 0, n = checkboxes.length; i < n; i++) {
        checkboxes[i].checked = source.checked;
    }

    let id = source.value;
    if (source.checked) {
        $("#" + id).attr('checked', true);
        $(".checkBox_" + id).attr('checked', true);
    } else {
        $("#" + id).attr('checked', false);
        $(".checkBox_" + id).attr('checked', false);
    }
}

function checkMePreSet(info) {
    let inputId = $(info).attr('dataName');
    let inputValue = info.value;

    if ($('.checkValue_' + inputValue).is(':checked')) {
        $(".checkValue_" + inputValue).attr('checked', true);
    } else {
        $(".checkValue_" + inputValue).attr('checked', false);
    }

    let totalCheckBox = document.getElementsByClassName('checkBox_' + inputId).length;
    let totalChecked = $('.checkBox_' + inputId).filter(':checked').length;
    let totalUnChecked = $('.checkBox_' + inputId).filter(':not(":checked")').length;

    if (totalUnChecked === totalCheckBox) {
        $("#" + inputId).prop('checked', false);
    } else if (totalChecked > 0) {
        $("#" + inputId).prop('checked', true);
    }

}