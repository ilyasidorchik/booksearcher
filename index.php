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
            require 'vendor/autoload.php';
            require 'php/functions.php';

            use GuzzleHttp\Client;

            $client = new Client();

            $bookTitle = $_GET['title'];

            printInput($bookTitle);

            if ($bookTitle) {
                // Инициализация экземпляра класса для работы с удалённым веб-ресурсом
                $client = new Client();

                $findNoFound_MGDB = true;
                /* Деловая библиотека отключена, пока не восстановится их каталог
                // Если книга одна в каталоге, совершается редирект на отдельную страницу книги
                // На этой странице есть biblionumber
                // Если его нет, не было редиректа
                $doc_MGDB = new DOMDocument();
                $url_MGDB = "http://catalog.mgdb.ru:49001/cgi-bin/koha/opac-search.pl?limit=branch:CGDB-AB&idx=kw&sort_by=pubdate_dsc&q=$bookTitle";
                @$doc_MGDB->loadHTMLFile($url_MGDB);
                $xpath_MGDB = new DOMXpath($doc_MGDB);

                $findNoFound_MGDB = $xpath_MGDB->query("//strong[text() = 'No Results Found!']")->length;

                // В МГДБ что-то есть
                if (!$findNoFound_MGDB) {
                    // Ищем количество серпов
                    $pages_MGDB = $xpath_MGDB->query('//*[@id="userresults"]/div[2]/a[@class="nav"]')->length * 20;

                    // Если серпов 0 — значит, одна страница
                    if ($pages_MGDB == 0)
                        $pages_MGDB = 1;

                    // $page увеличиваем на 20, потому что так меняется урл у следующих страниц
                    for ($page_MGDB = 0; $page_MGDB < $pages_MGDB; $page_MGDB += 20) {
                        // Если страница первая или только одна — продолжаем брать информацию с загруженной страницы
                        // Если страница не первая — грузим новую
                        if ($page_MGDB !== 0 || $pages_MGDB !== 1) {
                            @$doc_MGDB->loadHTMLFile("$url_MGDB&offset=$page_MGDB");
                            $xpath_MGDB = new DOMXpath($doc_MGDB);
                        }

                        $booksCount_MGDB = $xpath_MGDB->query('//*[@name="biblionumber"]')->length;

                        // Если книга одна в библиотеке
                        if ($booksCount_MGDB === 0)
                            $booksCount_MGDB += 1;

                        // Вывод карточек с информацией о книге и библиотеке
                        for ($bookI_MGDB = 0; $bookI_MGDB < $booksCount_MGDB; $bookI_MGDB++) {
                            // Определение $biblionumber
                            // Если книга не одна в библиотеке
                            if ($booksCount_MGDB > 1)
                                $biblionumber_MGDB = $xpath_MGDB->query('//*[@name="biblionumber"]/@value')[$bookI_MGDB]->nodeValue;
                            // Если книга одна
                            else
                                $biblionumber_MGDB = $xpath_MGDB->query('//*[@id="gbs-thumbnail-preview"]/@title')[0]->nodeValue;

                            // Вывод карточки
                            $bookInfo_MGDB = getBookInfo('ЦГДБ', $biblionumber_MGDB);
                            printBook($bookInfo_MGDB);

                            $libraryInfo_MGDB = getLibraryInfo('ЦГДБ', $biblionumber_MGDB, $bookInfo_MGDB);
                            printLibrary($libraryInfo_MGDB);

                            // Вывод библиотек, в которых есть эта книга или такая же, но с другим годом издания. Переменная-массив нужна, чтобы дальше не печатать ненужное
                            // Библиотеки СКБМ
                            $arrayOfWasteBookI_SKBM = printBooksAndLibs_SKBM($client, $bookTitle, $arrayOfWasteBookI_SKBM, $bookInfo_MGDB, 'checkOnSameWithBookMGDB');

                            printBookContainerEnd();
                        }
                    }
                }*/

                // Вывод оставшихся карточек
                // СКБМ
                $arrayOfWasteBookI_SKBM .= printBooksAndLibs_SKBM($client, $bookTitle, $arrayOfWasteBookI_SKBM, $bookInfo_MGDB, 'notCheckOnSameWithBookMGDB');


                if ($findNoFound_MGDB && empty($arrayOfWasteBookI_SKBM))
                    printMessageAboutNoFoundAndRequestForm($bookTitle);
            }
        ?>
        <footer<?php if (!$bookTitle) { echo ' class="index"'; } ?>>
            <div class="container">
                <div class="row">
                    <div class="col-sm-12 col-md-12 col-lg-10 offset-lg-1 col-xl-8 offset-xl-2">
                        <p>
                            <a href="http://sidorchik.ru/blog/all/booksearcher-beta1/" class="static">О проекте</a>&nbsp;&nbsp;·&nbsp;&nbsp;<a href="https://docs.google.com/document/d/1zHMqFfosGLYYG-jE6NskzIqpjWD23PU9avdPaAH0g3Q/edit?usp=sharing" class="static"><nobr>Библиотеки-участники</nobr></a>&nbsp;&nbsp;·&nbsp;&nbsp;<wbr>Поиск&nbsp;<a href="http://rgub.ru/searchopac/" class="static">по РГБМ</a> и <a href="https://libfl.ru/ru/item/catalogue" class="static">Рудомино</a>
                        </p>
                        <p>
                            Проект придумал и разработал <a href="http://sidorchik.ru/" class="static">Илья&nbsp;Сидорчик</a>. Второй разработчик: <a href="https://vk.com/romka023" class="static">Роман&nbsp;Мешков</a>.
                            <br>Если есть вопрос, замечание — пожалуйста, пишите: <a href="mailto:ilya@sidorchik.ru" class="static">ilya@sidorchik.ru</a> или&nbsp;<a href="https://t.me/ilyasidorchik" class="static">@ilyasidorchik</a>
                        </p>
                    </div>
                </div>
            </div>
        </footer>
        <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>
        <script src="http://cdnjs.cloudflare.com/ajax/libs/less.js/3.0.0/less.min.js"></script>
        <script src="js/script.js"></script>
    </body>
</html>