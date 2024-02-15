
//ListBox Multi-select @Author:Rajon
$(document).ready(function() {

    $('#search').multiselect({
        search: {
            left: '<input type="text" name="q" class="form-control" placeholder="Search..." />',
            right: '<input type="text" name="q" class="form-control" placeholder="Search..." />',
        },
        fireSearch: function(value) {
            return value.length > 2;
        }
    });

    $("#search_rightAll, #search_rightSelected, #search_leftSelected, #search_leftAll").click(function(){

        let quizQuestionsDropdown = $("#search_to").children('option').length; //get all selected questions

        $("#questionSelectedCount").text('Selected ' + (parseInt(quizQuestionsDropdown))); //append selection count
    });  //select count Calculation and append event end

    // for changing select count @Author: Rajon //Campaign Insertion
    $("#search").hover(function(){

        let quizQuestionsDropdown = $("#search_to").children('option').length; //get all selected questions

        $("#questionSelectedCount").text('Selected ' + (parseInt(quizQuestionsDropdown))); //append selection count
    });
});

function loadTransactionsData(id) {
    $('#TransactionsLoading').html("Loading...");
    $.ajax({
        url: '/transaction/view',
        type: 'POST',
        data: {
            'id': id
        },
        success: function (res) {
            $('#TransactionsLoading').hide();
            $('#TransactionsModalResponse').html(res);
        }
    });
}

function loadRefundedTransactionsData(id) {
    $('#RefundedTransactionsLoading').html("Loading...");
    $.ajax({
        url: '/refund-transaction/view',
        type: 'POST',
        data: {
            'id': id
        },
        success: function (res) {
            $('#RefundedTransactionsLoading').hide();
            $('#RefundedTransactionsModalResponse').html(res);
        }
    });
}

function loadDashboard() {

    $.ajax({
        url: '/site/dashboard',
        type: 'GET',
        success: function (res) {
            $('#dashboardLoading').hide();
            $('#dashboard').html(res);
        },
        complete: function () {
            $('#dashboardLoading').hide();
            setTimeout(loadDashboard, 120000); //2 minutes
        }
    });
}