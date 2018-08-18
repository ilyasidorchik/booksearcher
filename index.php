<!-- Поиск книг в библиотеках Москвы. Бета 4 © Илья Сидорчик -->
<html lang="ru">
    <head>
        <meta charset="utf-8">
        <title>Поиск книг в библиотеках Москвы</title>
        <meta name="description" content="Сводный каталог библиотек Москвы. Помогает найти, в каких библиотеках есть нужная книга, забронировать и доставить">
        <meta name="keywords" content="как, где, найти, узнать, проверить, взять, на дом, есть, поиск, нужную, книга, книгу, книг, литература, электронный, единый, сводный, каталог, база, данных, в, библиотека, библиотеках, москва, московских">
        <!--<meta name="yandex-verification" content="835e608657377f1e" />-->
        <meta content="width=device-width, initial-scale=1" name="viewport">
        <link rel="shortcut icon" href="img/favicon.ico">
        <link rel="apple-touch-icon" sizes="180x180" href="img/apple-touch-icon-180x180.png">
        <link rel="apple-touch-icon-precomposed" href="img/apple-touch-icon-180x180-precomposed.png">
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css" integrity="sha384-WskhaSGFgHYWDcbwN70/dfYBj47jz9qbsMId/iRN3ewGhXQFZCSftd1LZCfmhktB" crossorigin="anonymous">
        <link href="css/styles.less" rel="stylesheet/less" type="text/css">
        <!--[if lt IE 9]>
            <script src="http://cdnjs.cloudflare.com/ajax/libs/html5shiv/r29/html5.min.js"></script>
            <script src="https://raw.githubusercontent.com/jonathantneal/flexibility/master/flexibility.js"></script>
        <![endif]-->
        <!-- Yandex.Metrika counter --> <script> (function (d, w, c) { (w[c] = w[c] || []).push(function() { try { w.yaCounter49510096 = new Ya.Metrika2({ id:49510096, clickmap:true, trackLinks:true, accurateTrackBounce:true, webvisor:true }); } catch(e) { } }); var n = d.getElementsByTagName("script")[0], s = d.createElement("script"), f = function () { n.parentNode.insertBefore(s, n); }; s.type = "text/javascript"; s.async = true; s.src = "https://mc.yandex.ru/metrika/tag.js"; if (w.opera == "[object Opera]") { d.addEventListener("DOMContentLoaded", f, false); } else { f(); } })(document, window, "yandex_metrika_callbacks2"); </script> <noscript><div><img src="https://mc.yandex.ru/watch/49510096" style="position:absolute; left:-9999px;" alt="" /></div></noscript> <!-- /Yandex.Metrika counter -->
    </head>
    <body>
        <?php
            require 'php/functions.php';

            $bookTitle = $_GET['title'];

            printInput($bookTitle);
        ?>
        <div id="results"></div>
        <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>
        <script src="http://cdnjs.cloudflare.com/ajax/libs/less.js/3.0.0/less.min.js"></script>
        <script src="js/script.js"></script>
    </body>
</html>