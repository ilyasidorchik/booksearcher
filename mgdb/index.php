<?php

?>
<!DOCTYPE html>
<html lang="ru">
    <head>
        <meta charset="utf-8">
        <meta content="width=device-width, initial-scale=1" name="viewport">
        <title>Поиск книг в библиотеках Москвы</title>
        <link rel="apple-touch-icon" href="../img/apple-touch-icon.png">
        <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon">
        <link href="http://hometask.std-221.ist.mospolytech.ru/term1/web/bootstrap/css/bootstrap.min.css" rel="stylesheet">
        <link href="../css/styles.less" rel="stylesheet/less" type="text/css">
        <script src="http://cdnjs.cloudflare.com/ajax/libs/less.js/3.0.0/less.min.js"></script>
    </head>
    <body>
        <?php
            require '../vendor/autoload.php';
            require '../php/functions.php';

            use GuzzleHttp\Client;

            $bookTitle = $_GET['title'];

            if ($bookTitle)
                $linkToHome = ' href="/"';
            else {
                $linkToHome = 'class="active"';
                $autofocus = 'autofocus';
            }

            echo <<<HERE
                        <main class="mt-5">
                            <div class="container">
                                <div class="row">
                                    <div class="col-sm-12 col-md-12 col-lg-12 col-xl-12">
                                        <div class="search-container">
                                            <label for="search_inp"><h4>Поиск книг в библиотеках Москвы</h4></label>
                                            <form action="" method="GET" class="form-inline search">
                                                <input type="search" name="title" id="search_inp" class="form-control" placeholder="Название книги" value='$bookTitle' $autofocus>
                                                <button class="btn btn-primary ml-2">Найти</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
HERE;

            if ($bookTitle) {
                // Инициализация экземпляра класса для работы с удалённым веб-ресурсом
                $client = new Client();

                // Если книга одна в каталоге, совершается редирект на отдельную страницу книги
                // На этой странице есть biblionumber
                // Если его нет, не было редиректа
                $doc = new DOMDocument();
                $url = "http://catalog.mgdb.ru:49001/cgi-bin/koha/opac-search.pl?limit=branch:CGDB-AB&idx=kw&q=$bookTitle";
                @$doc->loadHTMLFile($url);
                $xpath = new DOMXpath($doc);

                $findNoFound = $xpath->query("//strong[text() = 'No Results Found!']")->length;

                // Что-то найдено
                if (!$findNoFound) {
                    // Ищем количество серпов
                    $pages = $xpath->query('//*[@id="userresults"]/div[2]/a[@class="nav"]')->length * 20;

                    // Если серпов 0 — значит одна страница
                    if (!$pages)
                        $pages = 1;

                    // $page увеличиваем на 20, потому что так меняется урл у следующих страниц
                    for ($page = 0; $page < $pages; $page += 20) {
                        // Если страница первая или только одна — продолжаем брать информацию с загруженной страницы
                        // Если страница не первая — грузим новую
                        if ($page !== 0 || $pages !== 1) {
                            @$doc->loadHTMLFile("$url&offset=$page");
                            $xpath = new DOMXpath($doc);
                        }

                        $booksCount = $xpath->query('//*[@name="biblionumber"]')->length;

                        // Если книга одна в библиотеке
                        if ($booksCount === 0)
                            $booksCount += 1;

                        // Вывод карточек с информацией о книге и библиотеке
                        for ($bookI = 0; $bookI < $booksCount; $bookI++) {
                            // Определение $biblionumber
                            // Если книга не одна в библиотеке
                            if ($booksCount > 1)
                                $biblionumber = $xpath->query('//*[@name="biblionumber"]/@value')[$bookI]->nodeValue;
                            // Если книга одна
                            else
                                $biblionumber = $xpath->query('//*[@id="gbs-thumbnail-preview"]/@title')[0]->nodeValue;

                            // Вывод карточки
                            $bookInfo = getBookInfo('Деловая библиотека', $biblionumber);
                            printBook($bookInfo);

                            $libraryInfo = getLibraryInfo('Деловая библиотека', $biblionumber);
                            printLibrary($libraryInfo);

                            printBookContainerEnd();
                        }
                    }
                }
                // Ничего не найдено
                else
                    echo 'Такой книги нет в Деловой библиотеке';
            }
        ?>
    </body>
</html>