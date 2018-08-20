<?php
    if ($_SERVER['SERVER_NAME'] == 'dev.booksearcher.ru')
        $devMode = true;
?>
<!-- Поиск книг в библиотеках Москвы. Бета 5 © Илья Сидорчик -->
<html lang="ru">
    <head>
        <meta charset="utf-8">
        <title>Поиск книг в библиотеках Москвы</title>
        <meta name="description" content="Более объёмный и удобный сводный каталог. Помогает найти книгу в библиотеках Москвы, забронировать и доставить из Некрасовки">
        <meta name="keywords" content="как, где, найти, узнать, проверить, взять, на дом, есть, поиск, нужную, книга, книгу, книг, литература, электронный, единый, сводный, каталог, база, данных, в, библиотека, библиотеках, москва, московских">
        <meta content="width=device-width, initial-scale=1" name="viewport">
        <link rel="shortcut icon" href="/img/favicon.ico">
        <link rel="apple-touch-icon" sizes="180x180" href="/img/apple-touch-icon-180x180.png">
        <link rel="apple-touch-icon-precomposed" href="/img/apple-touch-icon-180x180-precomposed.png">
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css" integrity="sha384-WskhaSGFgHYWDcbwN70/dfYBj47jz9qbsMId/iRN3ewGhXQFZCSftd1LZCfmhktB" crossorigin="anonymous">
        <?php
            if ($devMode)
                echo '<link href="/css/styles.less" rel="stylesheet/less" type="text/css">';
            else
                echo '<meta name="yandex-verification" content="835e608657377f1e"/><link href="/css/style.css" rel="stylesheet"><!-- Yandex.Metrika counter --> <script> (function (d, w, c) { (w[c] = w[c] || []).push(function() { try { w.yaCounter49510096 = new Ya.Metrika2({ id:49510096, clickmap:true, trackLinks:true, accurateTrackBounce:true, webvisor:true }); } catch(e) { } }); var n = d.getElementsByTagName("script")[0], s = d.createElement("script"), f = function () { n.parentNode.insertBefore(s, n); }; s.type = "text/javascript"; s.async = true; s.src = "https://mc.yandex.ru/metrika/tag.js"; if (w.opera == "[object Opera]") { d.addEventListener("DOMContentLoaded", f, false); } else { f(); } })(document, window, "yandex_metrika_callbacks2"); </script> <noscript><div><img src="https://mc.yandex.ru/watch/49510096" style="position:absolute; left:-9999px;" alt="" /></div></noscript> <!-- /Yandex.Metrika counter -->';
        ?>
        <!--[if lt IE 9]>
            <script src="http://cdnjs.cloudflare.com/ajax/libs/html5shiv/r29/html5.min.js"></script>
            <script src="https://raw.githubusercontent.com/jonathantneal/flexibility/master/flexibility.js"></script>
        <![endif]-->
    </head>
    <body>
        <main class="mt-4">
            <div class="container">
                <div class="searchСontainer">
                    <div class="row">
                        <div class="col-sm-12 col-md-12 col-lg-10 offset-lg-1 col-xl-8 offset-xl-2">
                            <label for="searchInput"><h4>Поиск книг в библиотеках Москвы <small class="text-muted">Бета</small></h4></label>
                            <form class="form-inline search">
                                <input type="search" name="title" id="searchInput" class="form-control" placeholder="Название книги, автор или ISBN — что знаете" autofocus required>
                                <button type="button" class="btn btn-primary ml-2" id="searchBtn">Найти</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div id="results"></div>
            </div>
        </main>
        <footer class="index">
            <div class="container">
                <div class="row">
                    <div class="col-sm-12 col-md-12 col-lg-10 offset-lg-1 col-xl-8 offset-xl-2">
                        <p>
                            <a href="http://sidorchik.ru/blog/all/booksearcher-beta1/" class="static">О проекте</a>&nbsp;&nbsp;·&nbsp;&nbsp;<a href="https://docs.google.com/document/d/1zHMqFfosGLYYG-jE6NskzIqpjWD23PU9avdPaAH0g3Q/edit?usp=sharing" class="static"><nobr>Библиотеки-участники</nobr></a>&nbsp;&nbsp;·&nbsp;&nbsp;<wbr>Поиск&nbsp;<a href="http://rgub.ru/searchopac/" class="static">по РГБМ</a> и&nbsp;<a href="https://libfl.ru/ru/item/catalogue" class="static">Рудомино</a>
                        </p>
                        <p>
                            Разработчики: <a href="http://sidorchik.ru/" class="static">Илья&nbsp;Сидорчик</a>, <a href="https://vk.com/romka023" class="static">Роман&nbsp;Мешков</a>. Тайный советник: <a href="http://blinov.design" class="static">Роберт&nbsp;Блинов</a><br>
                            Если есть вопрос, замечание — пожалуйста, пишите: <a href="mailto:ilya@sidorchik.ru" class="static">ilya@sidorchik.ru</a> или&nbsp;<a href="https://t.me/ilyasidorchik" class="static">@ilyasidorchik</a>
                        </p>
                    </div>
                </div>
            </div>
        </footer>
        <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>
        <?php
            if ($devMode)
                echo '<script src="http://cdnjs.cloudflare.com/ajax/libs/less.js/3.0.0/less.min.js"></script>';
        ?>
        <script src="/js/script.js"></script>
    </body>
</html>