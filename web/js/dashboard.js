am4core.ready(function () {

    const bullet = ['triangle', 'rectangle', 'Circle'];

    function transaction() {

        var isSetCol = false;
        // Themes begin
        //am4core.useTheme(am4themes_material);
        am4core.useTheme(am4themes_animated);

        // Create chart instance
        var transactionChart = am4core.create("transaction", am4charts.XYChart);
        transactionChart.colors.step = 2;
        transactionChart.synchronizeGrid = false;

        // Add data
        transactionChart.dataSource.reloadFrequency = reloadFrequency;
        transactionChart.dataSource.url = `${basePath}/site/month-transaction`;
        transactionChart.dataSource.events.on("done", function (ev) {
            const data = ev.target.data;
            const col = data.col;
            const series = data.series;
            //transactionChart.data = [];

            let index = 0;

            if (!isSetCol) {
                for (const [field, name] of Object.entries(col)) {
                    createSeries(field, name, bullet[index]);
                    if (index++ === 2) {
                        index = 0;
                    }
                }
            }

            isSetCol = true;
            transactionChart.data = series;
        });


// Create axes
        var categoryAxis = transactionChart.xAxes.push(new am4charts.DateAxis());
        categoryAxis.renderer.grid.template.location = 0;
        categoryAxis.renderer.minGridDistance = 50;

        var valueAxis = transactionChart.yAxes.push(new am4charts.ValueAxis());

        function createSeries(field, name, point) {
            var series = transactionChart.series.push(new am4charts.LineSeries());
            series.dataFields.valueY = field;
            series.dataFields.dateX = "date";
            series.name = name;
            series.tooltipText = "{name}: [bold]{valueY}[/]";
            series.smoothing = "monotoneX";
            series.strokeWidth = 1.5;
            series.tensionX = 0.75;
            series.showOnInit = true;

            //var bullet = series.bullets.push(new am4charts.CircleBullet());


            var interfaceColors = new am4core.InterfaceColorSet();

            switch (point) {
                case "triangle":
                    var bullet = series.bullets.push(new am4charts.Bullet());
                    bullet.width = 10;
                    bullet.height = 10;
                    bullet.horizontalCenter = "middle";
                    bullet.verticalCenter = "middle";

                    var triangle = bullet.createChild(am4core.Triangle);
                    triangle.stroke = interfaceColors.getFor("background");
                    triangle.strokeWidth = 1;
                    triangle.direction = "top";
                    triangle.width = 10;
                    triangle.height = 10;
                    break;
                case "rectangle":
                    var bullet = series.bullets.push(new am4charts.Bullet());
                    bullet.width = 10;
                    bullet.height = 10;
                    bullet.horizontalCenter = "middle";
                    bullet.verticalCenter = "middle";

                    var rectangle = bullet.createChild(am4core.Rectangle);
                    rectangle.stroke = interfaceColors.getFor("background");
                    rectangle.strokeWidth = 1;
                    rectangle.width = 10;
                    rectangle.height = 10;
                    break;
                default:
                    var bullet = series.bullets.push(new am4charts.CircleBullet());
                    bullet.circle.stroke = interfaceColors.getFor("background");
                    bullet.circle.strokeWidth = 1;
                    break;
            }

            bullet.events.on("hit", function (ev) {
                alert("Clicked on " + ev.target.dataItem.dateX + ": " + ev.target.dataItem.valueY);
            });
        }

        transactionChart.legend = new am4charts.Legend();

        transactionChart.cursor = new am4charts.XYCursor();

        transactionChart.scrollbarX = new am4core.Scrollbar();

    }

    function client() {

        var isSetCol = false;
        // Themes begin
        //am4core.useTheme(am4themes_material);
        am4core.useTheme(am4themes_animated);

        // Create chart instance
        var clientChart = am4core.create("clientTransaction", am4charts.XYChart);
        clientChart.colors.step = 2;
        clientChart.synchronizeGrid = false;

        // Add data
        clientChart.dataSource.reloadFrequency = reloadFrequency;
        clientChart.dataSource.url = `${basePath}/site/client-transaction`;
        clientChart.dataSource.events.on("done", function (ev) {
            const data = ev.target.data;
            const col = data.col;
            const series = data.series;
            //transactionChart.data = [];
            let index = 0;

            if (!isSetCol) {
                for (const [field, name] of Object.entries(col)) {
                    createSeries(field, name, bullet[index]);
                    if (index++ === 2) {
                        index = 0;
                    }
                }
            }
            isSetCol = true;
            clientChart.data = series;
        });


        // Create axes
        var categoryAxis = clientChart.xAxes.push(new am4charts.DateAxis());
        categoryAxis.renderer.grid.template.location = 0;
        categoryAxis.renderer.minGridDistance = 50;

        var valueAxis = clientChart.yAxes.push(new am4charts.ValueAxis());

        function createSeries(field, name, point) {
            var series = clientChart.series.push(new am4charts.LineSeries());
            series.dataFields.valueY = field;
            series.dataFields.dateX = "date";
            series.name = name;
            series.tooltipText = "{name}: [bold]{valueY}[/]";
            series.smoothing = "monotoneX";
            series.strokeWidth = 1.5;
            series.tensionX = 0.75;
            series.showOnInit = true;

            //var bullet = series.bullets.push(new am4charts.CircleBullet());


            var interfaceColors = new am4core.InterfaceColorSet();

            switch (point) {
                case "triangle":
                    var bullet = series.bullets.push(new am4charts.Bullet());
                    bullet.width = 10;
                    bullet.height = 10;
                    bullet.horizontalCenter = "middle";
                    bullet.verticalCenter = "middle";

                    var triangle = bullet.createChild(am4core.Triangle);
                    triangle.stroke = interfaceColors.getFor("background");
                    triangle.strokeWidth = 1;
                    triangle.direction = "top";
                    triangle.width = 10;
                    triangle.height = 10;
                    break;
                case "rectangle":
                    var bullet = series.bullets.push(new am4charts.Bullet());
                    bullet.width = 10;
                    bullet.height = 10;
                    bullet.horizontalCenter = "middle";
                    bullet.verticalCenter = "middle";

                    var rectangle = bullet.createChild(am4core.Rectangle);
                    rectangle.stroke = interfaceColors.getFor("background");
                    rectangle.strokeWidth = 1;
                    rectangle.width = 10;
                    rectangle.height = 10;
                    break;
                default:
                    var bullet = series.bullets.push(new am4charts.CircleBullet());
                    bullet.circle.stroke = interfaceColors.getFor("background");
                    bullet.circle.strokeWidth = 1;
                    break;
            }

            bullet.events.on("hit", function (ev) {
                alert("Clicked on " + ev.target.dataItem.dateX + ": " + ev.target.dataItem.valueY);
            });
        }

        clientChart.legend = new am4charts.Legend();

        clientChart.cursor = new am4charts.XYCursor();

        clientChart.scrollbarX = new am4core.Scrollbar();

    }

    function gateway()
    {
        //am4core.useTheme(am4themes_material);
        am4core.useTheme(am4themes_animated);
        var gatewayPie = am4core.create("gatewayPie", am4charts.PieChart3D);
        gatewayPie.hiddenState.properties.opacity = 0; // this creates initial fade-in

        gatewayPie.dataSource.reloadFrequency = reloadFrequency;
        gatewayPie.dataSource.url = `${basePath}/site/daily-gateway`;

        gatewayPie.legend = new am4charts.Legend();
        gatewayPie.innerRadius = 100;

        var series = gatewayPie.series.push(new am4charts.PieSeries3D());
        series.dataFields.value = "gatewayTotal";
        series.dataFields.category = "gatewayName";

    }

    function clinetPie()
    {
        am4core.useTheme(am4themes_animated);
        var clientPie = am4core.create("clientPie", am4charts.PieChart3D);
        clientPie.hiddenState.properties.opacity = 0; // this creates initial fade-in

        clientPie.dataSource.reloadFrequency = reloadFrequency;
        clientPie.dataSource.url = `${basePath}/site/daily-client`;

        clientPie.legend = new am4charts.Legend();
        clientPie.innerRadius = 100;

        var series = clientPie.series.push(new am4charts.PieSeries3D());
        series.dataFields.value = "clientTotal";
        series.dataFields.category = "clientName";
    }

    service = document.querySelectorAll('[id ^= "servicePie"]');
    Array.prototype.forEach.call(service, serviceCallback);

    function serviceCallback(element) {
        var clientName = element.id;
        am4core.useTheme(am4themes_animated);

        var serviceChart = am4core.create(clientName, am4charts.PieChart3D);
        serviceChart.hiddenState.properties.opacity = 0;

        serviceChart.legend = new am4charts.Legend();

        serviceChart.dataSource.reloadFrequency = reloadFrequency;
        serviceChart.dataSource.url = `${basePath}/site/service?clientName=`+clientName;

        var series = serviceChart.series.push(new am4charts.PieSeries3D());
        series.dataFields.value = "amount";
        series.dataFields.category = "serviceType";
    }



    transaction();
    client();
    gateway();
    clinetPie();

});

function loadDashboardCount() {
    $.ajax({
        url: `${basePath}/site/total-count`,
        type: 'GET',
        success: function (res) {
            $.each(res, function(index,value) {
                $("#"+index).html(value);
            });
        },
        complete: function () {
            setTimeout(loadDashboardCount, reloadFrequency);
        }
    });
}

loadDashboardCount();