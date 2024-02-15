<!-- HTML for static distribution bundle build -->
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Swagger UI</title>
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <link rel="stylesheet" type="text/css"
          href="https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/3.34.0/swagger-ui.css">
    <link rel="icon" type="image/png" href="./favicon-32x32.png" sizes="32x32"/>
    <link rel="icon" type="image/png" href="./favicon-16x16.png" sizes="16x16"/>
    <style>
        .swagger-ui .scheme-container,
        .swagger-ui .topbar {
            display: none !important;
        }
    </style>
    <style>
        html {
            box-sizing: border-box;
            overflow: -moz-scrollbars-vertical;
            overflow-y: scroll;
        }

        *,
        *:before,
        *:after {
            box-sizing: inherit;
        }

        body {
            margin: 0;
            background: #fafafa;
        }
    </style>
</head>

<body>
<div id="swagger-ui"></div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/3.34.0/swagger-ui-bundle.js" charset="UTF-8"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/3.34.0/swagger-ui-standalone-preset.js"
        charset="UTF-8"></script>
<script>
    window.onload = function () {
        // Begin Swagger UI call region
        const ui = SwaggerUIBundle({
            url: "<?= yii\helpers\Url::to('api/resource', getenv('PROTOCOL')) ?>",
            dom_id: '#swagger-ui',
            validatorUrl: null,
            deepLinking: true,
            supportedSubmitMethods: ['get', 'post', 'put', 'delete', 'patch'],
            presets: [
                SwaggerUIBundle.presets.apis,
                SwaggerUIStandalonePreset
            ],
            plugins: [
                SwaggerUIBundle.plugins.DownloadUrl
            ],
            layout: "StandaloneLayout"
        })
        // End Swagger UI call region

        window.ui = ui
    }
</script>
</body>

</html>