<!DOCTYPE html>
<html lang="ru">
    <head>
        <meta charset="utf-8">
        <meta content="width=device-width, initial-scale=1" name="viewport">
        <title>Поиск книг в библиотеках Москвы</title>
        <link rel="apple-touch-icon" href="img/apple-touch-icon.png">
        <link rel="shortcut icon" href="img/favicon.ico" type="image/x-icon">
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css" integrity="sha384-WskhaSGFgHYWDcbwN70/dfYBj47jz9qbsMId/iRN3ewGhXQFZCSftd1LZCfmhktB" crossorigin="anonymous">
        <link href="css/styles.less" rel="stylesheet/less" type="text/css">
    </head>
    <body>
        <?php
            require 'vendor/autoload.php';
            require 'php/functions.php';

            use GuzzleHttp\Client;
            use Psr\Http\Message\RequestInterface;
            use Psr\Http\Message\ResponseInterface;
            use Psr\Http\Message\UriInterface;

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

                // Определение адреса после редиректа с помощью анонимной функции
                $redirUrl = '';
                $onRedirect = function(RequestInterface $request, ResponseInterface $response, UriInterface $uri) use (&$redirUrl) {
                    $redirUrl = $uri;
                };

                // Запрос по исходному адресу, получение ответа
                $response = $client->request('GET', 'http://catalog.mgdb.ru:49001/cgi-bin/koha/opac-search.pl?idx=ti&q='.$bookTitle, [
                    'allow_redirects' => [
                        'strict'          => true,      // use "strict" RFC compliant redirects.
                        'referer'         => true,      // add a Referer header
                        'on_redirect'     => $onRedirect,
                        'track_redirects' => true
                    ]
                ]);

                // Проверка на наличие редиректа
                if ($redirUrl) {
                    // Получение из адреса после редиректа значение параметра biblionumber
                    $query = [];
                    parse_str(parse_url($redirUrl, PHP_URL_QUERY), $query);

                    // Определение ISBN, количества всех книг, количества книг на руках
                    $docNormal = new DOMDocument();
                    @$docNormal->loadHTMLFile('http://catalog.mgdb.ru:49001/cgi-bin/koha/opac-detail.pl?biblionumber='.$query['biblionumber']);
                    $xpathNormal = new DOMXpath($docNormal);

                    $ISBNMGDB = $xpathNormal->query("//span[@class='results_summary'][span='ISBN: ']/text()")[0]->nodeValue;
                    $ISBNMGDB = preg_replace("/[^0-9]/", '', $ISBNMGDB);

                    $bookStatus = $xpathNormal->query("//table[@id='holdingst']/tbody/tr/td[2][starts-with(., 'Абонемент')]/../td[4][contains(text(),'Available')]")->length;
                    $bookStatusOnHands = $xpathNormal->query("//table[@id='holdingst']/tbody/tr/td[2][starts-with(., 'Абонемент')]/../td[4][contains(text(),'Checked out')]")->length;

                    // Если есть книги на руках, определение даты возврата
                    if ($bookStatusOnHands > 0) {
                        if ($bookStatusOnHands == 1) {
                            $bookStatusOnHandsDate = $xpathNormal->query("//table[@id='holdingst']/tbody/tr/td[5]/text()")[0]->nodeValue;
                        }
                        else {
                            $i = 0;
                            while ($i < $bookStatusOnHands) {
                                $bookStatusOnHandsDate .= $xpathNormal->query("//table[@id='holdingst']/tbody/tr/td[5]/text()")[$i]->nodeValue;
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
                    @$docMarc->loadHTMLFile('http://catalog.mgdb.ru:49001/cgi-bin/koha/opac-MARCdetail.pl?biblionumber='.$query['biblionumber']);
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
                                        <div class="bookFunctions bookAddingToWishlist">
                                            <form action="wishlist.php" method="POST">
                                                <input type="hidden" name="title" value='$titleMGDB'>
                                                <input type="hidden" name="author" value='$authorMGDB'>
                                                <input type="hidden" name="publisher" value='$publisherMGDB'> 
                                                <input type="hidden" name="year" value='$yearMGDB'>
                                                <input type="hidden" name="pages" value='$pagesMGDB'>
                                                <button name="toWishlist">
                                                    <svg viewbox="0 0 99.57 94.7" xmlns="http://www.w3.org/2000/svg">
                                                        <polygon points="49.78 5.65 63.51 33.46 94.2 37.92 71.99 59.56 77.23 90.13 49.78 75.69 22.34 90.13 27.58 59.56 5.37 37.92 36.06 33.46 49.78 5.65"></polygon>
                                                    </svg>
                                                    <u>Добавить в вишлист</u>
                                                </button>
                                            </form>
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

                    // Если в учётной записи есть почта — бронирование книги в один клик
                    $encryption = $_COOKIE["encryption"];
                    // Подключение к базе данных
                    include 'php/db_connection.php';
                    $link = mysqli_connect($host, $user, $password, $database) or die("Ошибка");
                    $result = mysqli_query($link, "SELECT email FROM readers WHERE encryption = '$encryption'");
                    $row = mysqli_fetch_assoc($result);

                    if ($row['email'])  {
                        echo <<<HERE
                            <form action="booked.php" method="POST">
                                <input type="hidden" name="title" value='$titleMGDB'>
                                <input type="hidden" name="author" value='$authorMGDB'>
                                <input type="hidden" name="publisher" value='$publisherMGDB'> 
                                <input type="hidden" name="year" value='$yearMGDB'>
                                <input type="hidden" name="pages" value='$pagesMGDB'>
                                <input type="submit" name="toBook" value="Забронировать" class="btn btn-outline-dark btn-sm">
                            </form>
HERE;
                    }
                    else {
                        echo <<<HERE
                                        <button type="button" class="btn btn-outline-dark btn-sm" data-toggle="modal" data-target="#bookingForm">Забронировать…</button>
                                        
                                        <div class="modal fade" id="bookingForm" tabindex="-1" role="dialog" aria-labelledby="bookingFormTitle" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered" role="document">
                                                <div class="modal-content">
                                                      <div class="modal-header">
                                                            <h5 class="modal-title" id="exampleModalCenterTitle">Бронирование книги</h5>
                                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                              <span aria-hidden="true">&times;</span>
                                                            </button>
                                                      </div>
                                                      <form action="booked.php" method="POST">
                                                          <div class="modal-body">
                                                                <div class="form-group">
                                                                    <label for="email">Ваша эл. почта</label>
                                                                    <input type="email" class="form-control" id="email" name="email" aria-describedby="emailHelp" required>
                                                                    <small id="emailHelp" class="form-text text-muted">Библиотекарь подтвердит бронь или напишет в случае чего</small>
                                                                </div>
                                                                <div class="form-group">
                                                                    <label for="surname">Ваша фамилия</label>
                                                                    <input type="text" class="form-control" id="surname" name="surname" aria-describedby="surnameHelp" required>
                                                                    <small id="surnameHelp" class="form-text text-muted">Назовёте в библиотеке</small>
                                                                </div>
                                                                <input type="hidden" name="title" value='$titleMGDB'>
                                                                <input type="hidden" name="author" value='$authorMGDB'>
                                                                <input type="hidden" name="publisher" value='$publisherMGDB'> 
                                                                <input type="hidden" name="year" value='$yearMGDB'>
                                                                <input type="hidden" name="pages" value='$pagesMGDB'>
                                                            </div>
                                                          <div class="modal-footer">
                                                                <button name="toBook" class="btn btn-primary">Забронировать</button>
                                                          </div>
                                                      </form>
                                                </div>
                                            </div>
                                        </div>
HERE;
                    }

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
                    // СКБМ
                    // Делаем запрос на страницу выдачи, получаем ответ
                    $response = $client->request('POST', 'http://skbm.nekrasovka.ru/request', [
                        'form_params' => [
                            '_service' => 'STORAGE:opacfindd:IndexView',
                            '_action' => 'php',
                            '_errorhtml' => 'error1',
                            '_handler' => 'search/search.php',
                            'querylist' => '<_service>STORAGE:opacfindd:FindView[separator]<_version>2.5.0[separator]<session>26026[separator]<_start>0[separator]<start>0[separator]<$length>15[separator]<length>15[separator]<iddb>1[separator]<_showstr><i>Заглавие</i> ' . $bookTitle . '[separator]<_str>[bracket]TITL ' . $bookTitle . '[/bracket][separator]<$outform>SHORTFM[separator]<outformList[0]/outform>SHORTFM[separator]<outformList[1]/outform>LINEORD[separator]<outformList[2]/outform>SHORTFMS[separator]<outformList[3]/outform>SHORTFMSTR[separator]<$filterstr>[bracket][bracket]LRES [apos]ТЕКСТЫ[apos][/bracket][/bracket] AND [bracket]LPUB [apos]КНИГИ[apos][/bracket][separator]<$filtersids>filter_1_2_0[END]filter_1_3_0[separator]<$fshowstr><i>вид документа</i> тексты И <i>вид издания</i> книги[separator]<query/body>(TITL ' . $bookTitle . ') AND ((LRES \'ТЕКСТЫ\')) AND (LPUB \'КНИГИ\')[separator]<_history>yes[separator]<userId>ADMIN[separator]<$linkstring>043[ID]Заказ документа[END]044[ID]Заказ копии документа[END][separator]<level[0]>Full[separator]<level[1]>Retro[separator]<level[2]>Unfinished[separator]<level[3]>Identify[separator]<$swfterm>[bracket]TITL ' . $bookTitle . '[/bracket] AND [bracket][bracket]LRES [apos]ТЕКСТЫ[apos][/bracket][/bracket] AND [bracket]LPUB [apos]КНИГИ[apos][/bracket][separator]<_iddb>1[separator]<$addfilters>[NEXT]filter_1_1_else[IND]fixed_1_0_1525854941893[CLASS](LFR \'печатная/рукописная\')[TEXT]печатная/рукописная[separator]<$typework>search[separator]<$basequant>2391872[separator]<$flag45>yes',
                            '_numsean' => '26026'
                        ]
                    ]);

                    // Находим нужное из хтмла выдачи
                    $htmlSKBM = $response->getBody();
                    $docSKBM = new DOMDocument();
                    @$docSKBM->loadHTML($htmlSKBM);
                    $xpathSKBM = new DOMXpath($docSKBM);
                    $bookCount = $xpathSKBM->query('//div[@id="searchrezult"]/div[@class="searchrez"]')->length;

                    if ($bookCount) {
                        for ($bookI = 2; $bookI <= $bookCount; $bookI++) {
                            // В массиве $sameISBNBookIArray хранятся индексы книг, которые уже напечатаны и которые есть в библиотеке, единственной и не подходящей по условиям проекта
                            if ($sameISBNBookIArray) {
                                $wasPrinted = false;
                                foreach ($sameISBNBookIArray as $bookIndex) {
                                    if ($bookI == $bookIndex) {
                                        $wasPrinted = true;
                                    }
                                }
                                if ($wasPrinted)
                                    continue;
                            } else {
                                if (!isLibraryFit($xpathSKBM, $bookI)) {
                                    continue;
                                }
                            }

                            // Стягивание подробной информации о книге
                            $bookID = $xpathSKBM->query('//div[@id="searchrezult"]/div[@class="searchrez"][' . $bookI . ']/@id')[0]->nodeValue;
                            $bookID = str_replace('\\\\\\\\', '\\', $bookID);
                            $responseDetails = $client->request('POST', 'http://skbm.nekrasovka.ru/request', [
                                'form_params' => [
                                    '_action' => 'execute',
                                    '_html' => 'stat',
                                    '_errorhtml' => 'error',
                                    'querylist' => '<_service>STORAGE:opacfindd:FindView[separator]<_version>2.3.0[separator]<session>26210[separator]<iddbIds[0]/id>' . $bookID . '[separator]<iddbIds[0]/iddb>1[separator]<outform>FULLFORM[separator]<_history>yes[separator]<$iddb>1[separator]<userId>ADMIN[separator]<$basequant>2391872[separator]<$flag45>yes'
                                ]
                            ]);
                            $htmlDetails = $responseDetails->getBody();

                            // Получение каждого свойства книги
                            $ISBN = getBookInfo($htmlDetails, 'ISBN');

                            $title = getBookInfo($htmlDetails, 'title');
                            
                            $publisher = getBookInfo($htmlDetails, 'publisher');
                            /* Если книга без издателя — она не подходит по условиям проекта */
                            if (!$publisher)
                                continue;

                            $year = getBookInfo($htmlDetails, 'year');

                            $pages = getBookInfo($htmlDetails, 'pages');

                            $author = getBookInfo($htmlDetails, 'author');

                            if ($doNotShowFirstSKMBBook) {
                                // Вывод описания книги
                                echo <<<HERE
                                        <div class="bookContainer">
                                            <div class="row">
                                                <div class="col-sm-12 col-md-12 col-lg-12 col-xl-8">
                                                    <div class="book">
                                                        <div class="bookDesc">
                                                            <h2>$titleTypografed</h2>
                                                            <div class="details lead">
                                                                <span class="author">$author</span>
                                                                <span class="publisher">$publisher, $year</span>
                                                                <span class="pages">$pages</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
HERE;
                            }
                            $doNotShowFirstSKMBBook = 1;

                            printLibs($client, $xpathSKBM, $bookI);

                            // Вывод библиотек, в которых есть книга с $bookI, и запись их индексов в массив, чтобы не выводить ещё раз
                            $nextCurrentBookI = $bookI + 1;
                            if (!$sameISBNBookIArray)
                                $sameISBNBookIArray = array();
                            for ($nextBookI = $nextCurrentBookI; $nextBookI <= $bookCount; $nextBookI++) {
                                if (!isLibraryFit($xpathSKBM, $nextBookI)) {
                                    array_push($sameISBNBookIArray, $nextBookI);
                                    continue;
                                }

                                // Стягивание подробной информации о книге
                                $bookID = $xpathSKBM->query('//div[@id="searchrezult"]/div[@class="searchrez"][' . $nextBookI . ']/@id')[0]->nodeValue;
                                $bookID = str_replace('\\\\\\\\', '\\', $bookID);
                                $responseDetails = $client->request('POST', 'http://skbm.nekrasovka.ru/request', [
                                    'form_params' => [
                                        '_action' => 'execute',
                                        '_html' => 'stat',
                                        '_errorhtml' => 'error',
                                        'querylist' => '<_service>STORAGE:opacfindd:FindView[separator]<_version>2.3.0[separator]<session>26210[separator]<iddbIds[0]/id>' . $bookID . '[separator]<iddbIds[0]/iddb>1[separator]<outform>FULLFORM[separator]<_history>yes[separator]<$iddb>1[separator]<userId>ADMIN[separator]<$basequant>2391872[separator]<$flag45>yes'
                                    ]
                                ]);
                                $htmlDetails = $responseDetails->getBody();


                                // Вытаскивание ISBN
                                $ISBNNext = getBookInfo($htmlDetails, 'ISBN');
                                if ($ISBN == $ISBNNext) {
                                    printLibs($client, $xpathSKBM, $nextBookI);
                                    array_push($sameISBNBookIArray, $nextBookI);
                                } else {
                                    // Вытаскивание названия и издательства
                                    $titleNext = getBookInfo($htmlDetails, 'title');

                                    $publisherNext = getBookInfo($htmlDetails, 'publisher');
                                    /* Если книга без издателя — она не подходит по условиям проекта */
                                    if (!$publisherNext)
                                        continue;

                                    if ($title == $titleNext && mb_strtolower($publisher) == mb_strtolower($publisherNext)) {
                                        printLibs($client, $xpathSKBM, $nextBookI);
                                        array_push($sameISBNBookIArray, $nextBookI);
                                    }
                                }
                            }

                            echo '</div>';
                        }
                    }
                    else {
                        echo '</div>';
                    }
                }
                else {
                    /*$docList = new DOMDocument();
                    @$docList->loadHTMLFile('http://catalog.mgdb.ru:49001/cgi-bin/koha/opac-search.pl?idx=ti&q='.$bookTitle);
                    $xpathList = new DOMXpath($docList);
                    $findNoFound = $xpathList->query("//strong[text() = 'No Results Found!']")->length;
                    if ($findNoFound) {
                        $findNoFoundStatus = 'Такой книги нет в каталоге ЦГДБ';
                    }
                    else {
                        $findNoFoundStatus = 'В каталогах есть несколько книг с названием, похожим на ваше. Но для такого сценария программист ещё не написал код :-(';
                    }*/

                    // СКБМ
                    // Делаем запрос на страницу выдачи, получаем ответ
                    $response = $client->request('POST', 'http://skbm.nekrasovka.ru/request', [
                        'form_params' => [
                            '_service' => 'STORAGE:opacfindd:IndexView',
                            '_action' => 'php',
                            '_errorhtml' => 'error1',
                            '_handler' => 'search/search.php',
                            'querylist' => '<_service>STORAGE:opacfindd:FindView[separator]<_version>2.5.0[separator]<session>26026[separator]<_start>0[separator]<start>0[separator]<$length>15[separator]<length>15[separator]<iddb>1[separator]<_showstr><i>Заглавие</i> ' . $bookTitle . '[separator]<_str>[bracket]TITL ' . $bookTitle . '[/bracket][separator]<$outform>SHORTFM[separator]<outformList[0]/outform>SHORTFM[separator]<outformList[1]/outform>LINEORD[separator]<outformList[2]/outform>SHORTFMS[separator]<outformList[3]/outform>SHORTFMSTR[separator]<$filterstr>[bracket][bracket]LRES [apos]ТЕКСТЫ[apos][/bracket][/bracket] AND [bracket]LPUB [apos]КНИГИ[apos][/bracket][separator]<$filtersids>filter_1_2_0[END]filter_1_3_0[separator]<$fshowstr><i>вид документа</i> тексты И <i>вид издания</i> книги[separator]<query/body>(TITL ' . $bookTitle . ') AND ((LRES \'ТЕКСТЫ\')) AND (LPUB \'КНИГИ\')[separator]<_history>yes[separator]<userId>ADMIN[separator]<$linkstring>043[ID]Заказ документа[END]044[ID]Заказ копии документа[END][separator]<level[0]>Full[separator]<level[1]>Retro[separator]<level[2]>Unfinished[separator]<level[3]>Identify[separator]<$swfterm>[bracket]TITL ' . $bookTitle . '[/bracket] AND [bracket][bracket]LRES [apos]ТЕКСТЫ[apos][/bracket][/bracket] AND [bracket]LPUB [apos]КНИГИ[apos][/bracket][separator]<_iddb>1[separator]<$addfilters>[NEXT]filter_1_1_else[IND]fixed_1_0_1525854941893[CLASS](LFR \'печатная/рукописная\')[TEXT]печатная/рукописная[separator]<$typework>search[separator]<$basequant>2391872[separator]<$flag45>yes',
                            '_numsean' => '26026'
                        ]
                    ]);

                    // Находим нужное из хтмла выдачи
                    $htmlSKBM = $response->getBody();
                    $docSKBM = new DOMDocument();
                    @$docSKBM->loadHTML($htmlSKBM);
                    $xpathSKBM = new DOMXpath($docSKBM);
                    $bookCount = $xpathSKBM->query('//div[@id="searchrezult"]/div[@class="searchrez"]')->length;

                    if (!$bookCount) {
                        echo <<<HERE
                            <div class="row">
                                <div class="col-sm-12 col-md-12 col-lg-12 col-xl-8">
                                    <p class="mb-5">
                                        <b>Книги нет</b><br>
                                        Она может появиться, если вы попросите библиотеку Некрасова:
                                    </p>
                                </div>
                            </div>    
                            <div class="row">
                                <div class="col-sm-12 col-md-12 col-lg-12 col-xl-4">        
                                    <form action="requested.php" method="POST" style="background: #f0f0f0;">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="exampleModalCenterTitle">Заказ книги</h5>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="title" value='$bookTitle'>
                                            <div class="form-group">
                                                <label for="author">Автор</label>
                                                <input type="text" class="form-control" id="author" name="author" aria-describedby="authorHelp" required>
                                                <small id="authorHelp" class="form-text text-muted">Чтобы не подумали о другой книге</small>
                                            </div>
HERE;

                        // Если в учётной записи нет почты — показываем поля почты и фамилии
                        $encryption = $_COOKIE["encryption"];
                        // Подключение к базе данных
                        include 'php/db_connection.php';
                        $link = mysqli_connect($host, $user, $password, $database) or die("Ошибка");
                        $result = mysqli_query($link, "SELECT email FROM readers WHERE encryption = '$encryption'");
                        $row = mysqli_fetch_assoc($result);

                        if (!$row['email']) {
                            echo <<<HERE
                                            <div class="form-group">
                                                <label for="email">Ваша эл. почта</label>
                                                <input type="email" class="form-control" id="email" name="email" aria-describedby="emailHelp" required>
                                                <small id="emailHelp" class="form-text text-muted">Библиотекарь напишет в случае чего</small>
                                            </div>
                                            <div class="form-group">
                                                <label for="surname">Ваша фамилия</label>
                                                <input type="text" class="form-control" id="surname" name="surname" aria-describedby="surnameHelp" required>
                                                <small id="surnameHelp" class="form-text text-muted">Для связи с библиотекарем</small>
                                            </div>
HERE;
                        }

                        echo <<<HERE
                                        </div>
                                        <div class="modal-footer">
                                            <button name="toRequest" class="btn btn-primary">Забронировать</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
HERE;

                    }

                    for ($bookI = 2; $bookI <= $bookCount; $bookI++) {
                        // В массиве $sameISBNBookIArray хранятся индексы книг, которые уже напечатаны и которые есть в библиотеке, единственной и не подходящей по условиям проекта
                        if ($sameISBNBookIArray) {
                            $wasPrinted = false;
                            foreach ($sameISBNBookIArray as $bookIndex) {
                                if ($bookI == $bookIndex) {
                                    $wasPrinted = true;
                                }
                            }
                            if ($wasPrinted)
                                continue;
                        } else {
                            if (!isLibraryFit($xpathSKBM, $bookI)) {
                                continue;
                            }
                        }

                        // Стягивание подробной информации о книге
                        $bookID = $xpathSKBM->query('//div[@id="searchrezult"]/div[@class="searchrez"][' . $bookI . ']/@id')[0]->nodeValue;
                        $bookID = str_replace('\\\\\\\\', '\\', $bookID);
                        $responseDetails = $client->request('POST', 'http://skbm.nekrasovka.ru/request', [
                            'form_params' => [
                                '_action' => 'execute',
                                '_html' => 'stat',
                                '_errorhtml' => 'error',
                                'querylist' => '<_service>STORAGE:opacfindd:FindView[separator]<_version>2.3.0[separator]<session>26210[separator]<iddbIds[0]/id>' . $bookID . '[separator]<iddbIds[0]/iddb>1[separator]<outform>FULLFORM[separator]<_history>yes[separator]<$iddb>1[separator]<userId>ADMIN[separator]<$basequant>2391872[separator]<$flag45>yes'
                            ]
                        ]);
                        $htmlDetails = $responseDetails->getBody();

                        // Получение каждого свойства книги
                        $ISBN = getBookInfo($htmlDetails, 'ISBN');

                        $title = getBookInfo($htmlDetails, 'title');
                        $titleTypografed = typograf($title);

                        $publisher = getBookInfo($htmlDetails, 'publisher');
                        /* Если книга без издателя — она не подходит по условиям проекта */
                        if (!$publisher)
                            continue;

                        $year = getBookInfo($htmlDetails, 'year');

                        $pages = getBookInfo($htmlDetails, 'pages');

                        $author = getBookInfo($htmlDetails, 'author');

                        // Вывод описания книги
                        echo <<<HERE
                        <div class="bookContainer">
                            <div class="row">
                                <div class="col-sm-12 col-md-12 col-lg-12 col-xl-8">
                                    <div class="book">
                                        <div class="bookDesc">
                                            <h2>$titleTypografed</h2>
                                            <div class="details lead">
                                                <span class="author">$author</span>
                                                <span class="publisher">$publisher, $year</span>
                                                <span class="pages">$pages</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
HERE;

                        printLibs($client, $xpathSKBM, $bookI);

                        // Вывод библиотек, в которых есть книга с $bookI, и запись их индексов в массив, чтобы не выводить ещё раз
                        $nextCurrentBookI = $bookI + 1;
                        if (!$sameISBNBookIArray)
                            $sameISBNBookIArray = array();
                        for ($nextBookI = $nextCurrentBookI; $nextBookI <= $bookCount; $nextBookI++) {
                            if (!isLibraryFit($xpathSKBM, $nextBookI)) {
                                array_push($sameISBNBookIArray, $nextBookI);
                                continue;
                            }

                            // Стягивание подробной информации о книге
                            $bookID = $xpathSKBM->query('//div[@id="searchrezult"]/div[@class="searchrez"][' . $nextBookI . ']/@id')[0]->nodeValue;
                            $bookID = str_replace('\\\\\\\\', '\\', $bookID);
                            $responseDetails = $client->request('POST', 'http://skbm.nekrasovka.ru/request', [
                                'form_params' => [
                                    '_action' => 'execute',
                                    '_html' => 'stat',
                                    '_errorhtml' => 'error',
                                    'querylist' => '<_service>STORAGE:opacfindd:FindView[separator]<_version>2.3.0[separator]<session>26210[separator]<iddbIds[0]/id>' . $bookID . '[separator]<iddbIds[0]/iddb>1[separator]<outform>FULLFORM[separator]<_history>yes[separator]<$iddb>1[separator]<userId>ADMIN[separator]<$basequant>2391872[separator]<$flag45>yes'
                                ]
                            ]);
                            $htmlDetails = $responseDetails->getBody();


                            // Вытаскивание ISBN
                            $ISBNNext = getBookInfo($htmlDetails, 'ISBN');
                            if ($ISBN == $ISBNNext) {
                                printLibs($client, $xpathSKBM, $nextBookI);
                                array_push($sameISBNBookIArray, $nextBookI);
                            }
                            else {
                                // Вытаскивание названия и издательства
                                $titleNext = getBookInfo($htmlDetails, 'title');

                                $publisherNext = getBookInfo($htmlDetails, 'publisher');
                                /* Если книга без издателя — она не подходит по условиям проекта */
                                if (!$publisherNext)
                                    continue;

                                if ($title == $titleNext && mb_strtolower($publisher) == mb_strtolower($publisherNext)) {
                                    printLibs($client, $xpathSKBM, $nextBookI);
                                    array_push($sameISBNBookIArray, $nextBookI);
                                }
                            }
                        }

                        echo '</div>';
                    }
                }
            }
        ?>
            </div>
        </main>
        <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
        <script src="http://cdnjs.cloudflare.com/ajax/libs/less.js/3.0.0/less.min.js"></script>
    </body>
</html>