<?php

namespace unit;

use app\components\Utils;
use app\models\Transaction;
use Codeception\Test\Unit;

class BinRestrictionTest extends Unit
{
    //To run, use the command below:
    //sudo php vendor/bin/codecept run unit BinRestrictionTest

    public function testIsEqual()
    {
        $orderId = 'STPBCF652242ACDF7C0';
        $existingData = Transaction::getOrderDetailsWithOrder($orderId);
        $isPanVerified = Utils::isPanVerified($existingData['binRestriction'], $existingData['pan'], $existingData['bankCode']);
        $this->assertEquals(true, $isPanVerified);
    }
}