if [ "$DEPLOYMENT_GROUP_NAME" == "st-payment-gateway-stg" ]
then
    sudo rsync -av /home/ubuntu/st-payment-gateway/ /var/www/html/stg
    cd /var/www/html/stg || exit
    sudo chmod -R 777 web/uploads web/assets runtime
    sudo systemctl restart apache2
fi

if [ "$DEPLOYMENT_GROUP_NAME" == "st-payment-gateway-prod" ]
then
    sudo rsync -av /home/ubuntu/st-payment-gateway/ /var/www/html/prod
    cd /var/www/html/prod || exit
    sudo chmod -R 777 web/uploads web/assets runtime
    #sudo systemctl restart apache2
fi
