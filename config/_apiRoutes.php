<?php

return [
    //@API V1
    'api/card-validation',
    'api/transaction',
    'api/gateway',
    'api/order-details',
    'api/token',
    'api/service',
    'api/currency',
    'api/conversion-rate',
    'api/banks',
    'api/bank-types',
    'api/get-order',
    'api/generate-order',

    // EBL CALLBACKS
    'callback/ebl-success',
    'callback/ebl-timeout',
    'callback/ebl-cancel',

    // CBL CALLBACKS
    'callback/city-bank-approve',
    'callback/city-bank-decline',
    'callback/city-bank-cancel',

    // NAGAD CALLBACKS
    'callback/nagad-callback',

    //Bkash CALLBACKS
    'bkash/index',
    'bkash/create-payment-request',
    'callback/bkash-cancel',
    'bkash/execute-payment',

    //Nexus CALLBACKS
    'callback/dbbl-success',
    'callback/dbbl-failed',

    //INVOICE
    'instant-invoice/pay'

    //Others
];
