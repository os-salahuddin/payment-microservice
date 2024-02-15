<!DOCTYPE html>
<html>
<head>
    <noscript>
        <style type="text/css">
            .noscript {display:none;}
        </style>
    </noscript>
    <style type="text/css">
        .typewriter {
            display: inline-block;
        }

        .typewriter-text {
            display: inline-block;
            overflow: hidden;
            animation: typing 5s steps(30, end), blink .75s step-end infinite;
            white-space: nowrap;
            font-size: 15px;
            font-weight: 700;
            border-right: 2px solid orange;
            box-sizing: border-box;
            line-height: 15px;
            color: #FAA61A;
        }

        @keyframes typing {
            from {
                width: 0%
            }
            to {
                width: 100%
            }
        }

        @keyframes blink {
            from, to {
                border-color: transparent
            }
            50% {
                border-color: #FAA61A;
            }
        }
    </style>
</head>
<body>
    <div style="text-align: center; margin: 0 auto">
    <h3>CYBERSOURCE</h3>
    <div class="noscript">
        <div class="typewriter">
            <div class="typewriter-text">
                YOU WILL REDIRECT TO PAYMENT PAGE AUTOMATICALLY. PLEASE WAIT...
            </div>
        </div>
    </div>
    </div>
    <form id="payment_form" action="<?= $paymentUrl ?>" method="post">
        <?php
        foreach($requests as $name => $value) {
            echo '<input type="hidden" name="' . $name . '" value="' . $value . '"/>';
        }
        ?>
    </form>
    <script type="text/javascript">
        window.onload = function() {
            document.forms['payment_form'].submit();
        }
    </script>
</body>
</html>