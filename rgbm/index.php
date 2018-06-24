<?php

?>
<!DOCTYPE html>
<html lang="ru">
    <head>
        <meta charset="utf">
        <meta content="width=device-width, initial-scale=1" name="viewport">
        <title>Поиск книг в библиотеках Москвы</title>
        <link rel="apple-touch-icon" href="../img/apple-touch-icon.png">
        <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon">
        <link href="http://hometask.std-221.ist.mospolytech.ru/term1/web/bootstrap/css/bootstrap.min.css" rel="stylesheet">
        <link href="../less/styles.less" rel="stylesheet/less" type="text/css">
        <link rel="stylesheet" href="js/script.js">
        <script src="http://cdnjs.cloudflare.com/ajax/libs/less.js/3.0.0/less.min.js"></script>
    </head>
    <body>
        <?php
            require '../vendor/autoload.php';

            // Подключаем класс Client
            use GuzzleHttp\Client;

            // Инициализируем класс для работы с удаленными веб-ресурсами
            $client = new Client();

            // Делаем запрос, получаем ответ
            $response = $client->request("POST", "http://opac.rgub.ru/cgiopac/opacg/opac.exe", [
                "form_params" => [
                    "arg0" => "GUEST",
                    "arg1" => "GUESTE",
                    "TypeAccess" => "FreeAccess",
                    "_errorXsl" => "/opacg/html/common/xsl/error.xsl",
                    "_wait" => "6M",
                    "_xsl" => "/opacg/html/search/xsl/search_results.xsl",
                    "_version" => "2.0.0",
                    "_service" => "STORAGE:opacfindd:FindView",
                    "outform" => "SHOTFORM",
                    "length" => "15",
                    "query/body" => "%28TI+%CE%E1%EB%EE%EC%EE%E2%29",
                    "query/open" => "{NC:<span class='red_text'>}",
                    "query/close" => "{NC:</span>}",
                    "userId" => "GUEST",
                    "session" => "495180",
                    "iddb" => "5",
                    "level[0]" => "Full",
                    "level[1]" => "Retro"
                ]
            ]);

            // Находим нужное из хтмла выдачи
            $htmlRGBM = $response->getBody();
            $docRGBM = new DOMDocument();
            @$docRGBM->loadHTML($htmlRGBM);

            $xpathRGBM = new DOMXpath($docRGBM);
            echo $xpathRGBM->query('//entry[1]/text()')[0]->nodeValue;


        ?>
    </body>
</html>