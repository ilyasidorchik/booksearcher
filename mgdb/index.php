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
                @$doc->loadHTMLFile('http://catalog.mgdb.ru:49001/cgi-bin/koha/opac-search.pl?idx=kw&q='.$bookTitle);
                $xpath = new DOMXpath($doc);
                $biblionumber = $xpath->query('//*[@id="gbs-thumbnail-preview"]/@title')[0]->nodeValue;

                if ($biblionumber) {
                    // Определение ISBN, количества всех книг, количества книг на руках
                    $ISBNMGDB = $xpath->query("//span[@class='results_summary'][span='ISBN: ']/text()")[0]->nodeValue;
                    $ISBNMGDB = preg_replace("/[^0-9]/", '', $ISBNMGDB);

                    $bookStatus = $xpath->query("//table[@id='holdingst']/tbody/tr/td[2][starts-with(., 'Абонемент')]/../td[4][contains(text(),'Available')]")->length;
                    $bookStatusOnHands = $xpath->query("//table[@id='holdingst']/tbody/tr/td[2][starts-with(., 'Абонемент')]/../td[4][contains(text(),'Checked out')]")->length;

                    // Если есть книги на руках, определение даты возврата
                    if ($bookStatusOnHands > 0) {
                        if ($bookStatusOnHands == 1) {
                            $bookStatusOnHandsDate = $xpath->query("//table[@id='holdingst']/tbody/tr/td[5]/text()")[0]->nodeValue;
                        }
                        else {
                            $i = 0;
                            while ($i < $bookStatusOnHands) {
                                $bookStatusOnHandsDate .= $xpath->query("//table[@id='holdingst']/tbody/tr/td[5]/text()")[$i]->nodeValue;
                                if ($i + 1 != $bookStatusOnHands) {
                                    $bookStatusOnHandsDate .=  ', ';
                                }
                                $i++;
                            }
                        }

                        $bookStatusOnHandsDate = str_replace('/', '.', $bookStatusOnHandsDate);
                    }

                    // Определение библиографических сведений
                    $docMarc = new DOMDocument();
                    @$docMarc->loadHTMLFile('http://catalog.mgdb.ru:49001/cgi-bin/koha/opac-MARCdetail.pl?biblionumber='.$biblionumber);
                    $xpath = new DOMXpath($docMarc);

                    $titleMGDB = $xpath->query("//tr[td='Основное заглавие']/td[2]")[0]->nodeValue;

                    $author1Dirty = $xpath->query("//tr[td='Часть имени, кроме начального элемента ввода']/td[2]")[0]->nodeValue;
                    $author2Dirty = $xpath->query("//tr[td='Начальный элемент ввода']/td[2]")[0]->nodeValue;
                    $author1Dirty1 = str_replace(' ', '', $author1Dirty);
                    $authorMGDB = str_replace('.', '. ', $author1Dirty1) . $author2Dirty;

                    $publisherMGDB = $xpath->query("//tr[td='Издательство']/td[2]")[0]->nodeValue;

                    $yearMGDB = $xpath->query("//tr[td='Дата издания, распространения и т.д.']/td[2]")[0]->nodeValue;

                    $pages = $xpath->query("//tr[td='Объем и специфическое обозначение материала']/td[2]")[0]->nodeValue;
                    $pagesMGDB = preg_replace("/[^0-9]/", '', $pages) . ' стр.';

                    $titleTypografed = typograf($titleMGDB);

                    echo <<<HERE
                        <div class="bookContainer">
                            <div class="row">
                                <div class="col-sm-12 col-md-12 col-lg-12 col-xl-8">
                                    <div class="book">
                                        <div class="bookDesc">
                                            <h2>$titleTypografed</h2>
                                            <div class="details lead">
                                                <span class="author">$authorMGDB</span>
                                                <span class="publisher">$publisherMGDB, $yearMGDB</span>
                                                <span class="pages">$pagesMGDB</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
HERE;

                    $libraryMGDB = [
                        "name" => "Деловая библиотека",
                        "address" => "м. ВДНХ, ул. Бориса Галушкина, 19к1",
                        "timetable" => "http://mgdb.mos.ru/contacts/info/"
                    ];

                    echo <<<HERE
                        <div class="row">
                            <div class="col-sm-12 col-md-12 col-lg-12 col-xl-8">
                                <div class="library">
                                    <div class="libraryDesc">
                                        <div class="name"><b>$libraryMGDB[name]</b></div>
                                        <div class="details">
                                            <div class="address">$libraryMGDB[address]</div>
                                            <div class="timetable">
                                                <!--<span class="timetable-item today">Сегодня до 22</span>-->
                                                <a href="$libraryMGDB[timetable]" class="timetable-item link">Режим работы</a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="libraryBooking">
HERE;

                    if ($bookStatusOnHands > 0)
                        $bookStatusOnHands = '<br>На руках: ' . $bookStatusOnHands . ' шт.' . ' до ' . $bookStatusOnHandsDate;
                    elseif ($bookStatusOnHands == 0)
                        $bookStatusOnHands = '';

                    echo <<<HERE
                                        <div class="status small">Доступно: $bookStatus шт$bookStatusOnHands</div>
                                    </div>
                                </div>
                            </div>
                        </div>
HERE;
                }
                else {
                    $docList = new DOMDocument();
                    @$docList->loadHTMLFile('http://catalog.mgdb.ru:49001/cgi-bin/koha/opac-search.pl?idx=kw&q='.$bookTitle);
                    $xpathList = new DOMXpath($docList);
                    $findNoFound = $xpathList->query("//strong[text() = 'No Results Found!']")->length;
                    if ($findNoFound) {
                        $findNoFoundStatus = 'Такой книги нет в каталоге ЦГДБ';
                    }
                    else {
                        $findNoFoundStatus = 'В каталогах есть несколько книг с названием, похожим на ваше. Но для такого сценария программист ещё не написал код :-(';
                    }
                }
            }
        ?>
    </body>
</html>