<?php
use app\components\Utils;
use app\models\Transaction;
use Codeception\Test\Unit;

class TripCoinMultiplyTest extends Unit
{
    //To run, use the command below:
    //sudo php vendor/bin/codecept run unit TripCoinMultiplyTest

    public function testIsEqual()
    {
        $orderId = 'STPBCF652378DB0D6F0';
        $existingData = Transaction::getOrderDetailsWithOrder($orderId);
        $tripCoinMultiply = Utils::getTripCoinMultiply($existingData['tripCoinMultiply']);
        $this->assertEquals(1, $tripCoinMultiply);
    }
}