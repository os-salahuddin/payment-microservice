if [ "$DEPLOYMENT_GROUP_NAME" == "st-payment-gateway-stg" ]
then
    sudo systemctl stop apache2
fi

if [ "$DEPLOYMENT_GROUP_NAME" == "st-payment-gateway-prod" ]
then
    #sudo systemctl stop apache2
    ls -lah
fi
