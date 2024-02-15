<?php

namespace app\controllers;

use app\components\AllCurrencyConversion;
use app\components\CacheUtil;
use app\components\Checkout;
use app\components\CyberSource;
use app\components\EmailInvoice;
use app\components\EmailPublisher;
use app\components\RabbitMQ;
use app\components\TokenizationUtil;
use app\components\TransactionLogDetailsUtil;
use app\models\EmiOption;
use Exception;
use GuzzleHttp\Client as HttpClient;
use Swagger\Annotations\Swagger;
use Yii;
use app\components\BKash;
use app\components\BracBank\iPayPipe;
use app\components\BracBank\payUtils;
use app\components\CityBank;
use app\components\EBLSkyPay;
use app\components\EBLTokenization;
use app\components\GatewayUtil;
use app\components\NagadPay;
use app\components\TAPay;
use app\components\UPay;
use app\components\Utils;
use app\models\Bank;
use app\models\BankType;
use app\models\Client;
use app\models\Currency;
use app\models\Gateway;
use app\models\InstantInvoice;
use app\models\SavedCard;
use app\models\Service;
use app\models\Transaction;
use app\models\TransactionLog;
use function GuzzleHttp\Promise\all;
use function Swagger\scan;
use yii\helpers\Json;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\Response;
use app\components\CurrencyConversion as CurrencyConversionComponent;

/**
 * @swg\Info(
 *   title="Payment Manager APIs",
 *   version="1.0.0",
 *   contact={
 *     "name": "Support",
 *     "email": "tech@sharetrip.net"
 *   }
 * )
 */
class ApiController extends Controller
{
    /**
     * @throws BadRequestHttpException
     */
    public function beforeAction($action): bool
    {
        $this->enableCsrfValidation = false;
        return parent::beforeAction($action);
    }

    public function actionDocs(): string
    {
        $this->layout = false;
        return $this->render('docs');
    }

    public function actionResource(): Swagger
    {
        return scan([Yii::getAlias('@app/controllers')]);
    }


    /**
     * @swg\Post(
     *     path="/api/transaction",
     *     summary="Transaction",
     *     tags={"Transaction"},
     *     @swg\Parameter(
     *         type="string",
     *         name="accessToken",
     *         in="header",
     *         description="Client Access Token",
     *         required=true,
     *         @swg\Definition(
     *           definition="cardValidator",
     *           type="object",
     *           required={"accessToken"},
     *           @swg\Property(property="accessToken", type="string")
     *        )
     *     ),
     *     @swg\Parameter(
     *         name="body",
     *         in="body",
     *         description="Required data",
     *         required=true,
     *         @swg\Definition(
     *           definition="transactionValidator3",
     *           type="object",
     *           required={"bankCode", "bookingId", "serviceType", "amount"},
     *           @SWG\Property(property="bankCode", type="string", description="gateway or bankCode"),
     *           @SWG\Property(property="bookingId", type="string" , description="booking_id or bookingId"),
     *           @SWG\Property(property="serviceType", type="string", description="service or serviceType"),
     *           @SWG\Property(property="amount", type="string"),
     *           @SWG\Property(property="emiOption", type="string"),
     *           @SWG\Property(property="description", type="string"),
     *           @SWG\Property(property="cardSeries", type="string", description="cardSeries or card_series"),
     *           @SWG\Property(property="successUrl", type="string"),
     *           @SWG\Property(property="cancelUrl", type="string"),
     *           @SWG\Property(property="declineUrl", type="string"),
     *           @SWG\Property(property="customerId", type="string"),
     *           @SWG\Property(property="customerName", type="string"),
     *        )
     *     ),
     *     @swg\Response(
     *         response=200,
     *         description="Transaction Success Response",
     *         @SWG\Schema(
     *              @SWG\Property(property="success", type="boolean", example=true),
     *              @SWG\Property(property="code", type="integer", example=200),
     *              @SWG\Property(property="url", type="string"),
     *              @SWG\Property(property="message", type="string", example="success"),
     *         )
     *     ),
     *     @swg\Response(
     *         response=405,
     *         description="Method Not Allowed Response",
     *          @SWG\Schema(
     *              @SWG\Property(property="success", type="boolean", example=false),
     *              @SWG\Property(property="code", type="integer", example=405),
     *              @SWG\Property(property="message", type="string", example="Invalid Request Type"),
     *              @SWG\Property(property="url", type="string"),
     *          ),
     *     ),
     *     @swg\Response(
     *         response=401,
     *         description="Unauthorized Response",
     *          @SWG\Schema(
     *              @SWG\Property(property="success", type="boolean", example=false),
     *              @SWG\Property(property="code", type="integer", example=401),
     *              @SWG\Property(property="message", type="string", example="Unauthorized"),
     *              @SWG\Property(property="url", type="string"),
     *          ),
     *     ),
     *     @swg\Response(
     *         response=500,
     *         description="Server Error Response",
     *          @SWG\Schema(
     *              @SWG\Property(property="success", type="boolean", example=false),
     *              @SWG\Property(property="code", type="integer", example=500),
     *              @SWG\Property(property="message", type="string", example="Something Went Wrong!"),
     *              @SWG\Property(property="url", type="string"),
     *          ),
     *     ),
     *     @swg\Response(
     *         response=422,
     *         description="Unprocessable Entity Response",
     *          @SWG\Schema(
     *              @SWG\Property(property="success", type="boolean", example=false),
     *              @SWG\Property(property="code", type="integer", example=422),
     *              @SWG\Property(property="message", type="string", example="One or more parameter is missing!"),
     *              @SWG\Property(property="url", type="string"),
     *          ),
     *     ),
     *  )
     */
    public function actionTransaction(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        if(!Yii::$app->request->isPost) return [];
            $body = json_decode(Yii::$app->request->getRawBody(), false);
            $amount = $body->amount;
            $gatewayCode = $body->gatewayCode;
            $successUrl = (!empty($body->successUrl)) ? $body->successUrl : '';
            $cancelUrl = (!empty($body->cancelUrl)) ? $body->cancelUrl : '';
            $declineUrl = (!empty($body->declineUrl)) ? $body->declineUrl : '';

            return GatewayUtil::createOrder($gatewayCode, $amount, $successUrl, $cancelUrl, $declineUrl);
    }
}
