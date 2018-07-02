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

            $bookTitle = $_GET['title'];

            printInput($bookTitle);

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
                        for ($bookMGDBI = 0; $bookMGDBI < $booksCount; $bookMGDBI++) {
                            // Определение $biblionumber
                            // Если книга не одна в библиотеке
                            if ($booksCount > 1)
                                $biblionumber = $xpath->query('//*[@name="biblionumber"]/@value')[$bookMGDBI]->nodeValue;
                            // Если книга одна
                            else
                                $biblionumber = $xpath->query('//*[@id="gbs-thumbnail-preview"]/@title')[0]->nodeValue;

                            // Вывод карточки
                            $bookInfo = getBookInfo('ЦГДБ', $biblionumber);
                            printBook($bookInfo);

                            $libraryInfo = getLibraryInfo('ЦГДБ', $biblionumber);
                            printLibrary($libraryInfo);



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
                                            if ($bookI == $bookIndex)
                                                $wasPrinted = true;
                                        }
                                        if ($wasPrinted)
                                            continue;
                                    }
                                    else {
                                        if (!isLibraryFit($xpathSKBM, $bookI))
                                            continue;
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

                                    $bookInfo = getBookInfo('СКБМ', $htmlDetails);

                                    /* Если книга без издателя — она не подходит по условиям проекта */
                                    if (!$bookInfo[publisher])
                                        continue;

                                    if ($doNotShowFirstSKMBBook)
                                        printBook($bookInfo);

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


                                        // Сравнение ISBN
                                        $bookNextInfo = getBookInfo('СКБМ', $htmlDetails);
                                        if ($bookInfo[ISBN] == $bookNextInfo[ISBN]) {
                                            printLibs($client, $xpathSKBM, $nextBookI);
                                            array_push($sameISBNBookIArray, $nextBookI);
                                        }
                                        else {
                                            // Сравнение названия и издательства

                                            /* Если книга без издателя — она не подходит по условиям проекта */
                                            if (!$bookNextInfo[publisher])
                                                continue;

                                            if ($bookInfo[title] == $bookNextInfo[title] && mb_strtolower($bookInfo[publisher]) == mb_strtolower($bookNextInfo[publisher])) {
                                                printLibs($client, $xpathSKBM, $nextBookI);
                                                array_push($sameISBNBookIArray, $nextBookI);
                                            }
                                        }
                                    }

                                    printBookContainerEnd();
                                }
                            }
                            else
                                printBookContainerEnd();
                        }
                    }
                }
                else {
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
                                if ($bookI == $bookIndex)
                                    $wasPrinted = true;
                            }
                            if ($wasPrinted)
                                continue;
                        }
                        else {
                            if (!isLibraryFit($xpathSKBM, $bookI))
                                continue;
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
                        $bookInfo = getBookInfo('СКБМ', $htmlDetails);

                        /* Если книга без издателя — она не подходит по условиям проекта */
                        if (!$bookInfo[publisher])
                            continue;

                        printBook($bookInfo);

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

                            // Сравнение ISBN
                            $bookNextInfo = getBookInfo('СКБМ', $htmlDetails);
                            if ($bookInfo[ISBN] == $bookNextInfo[ISBN]) {
                                printLibs($client, $xpathSKBM, $nextBookI);
                                array_push($sameISBNBookIArray, $nextBookI);
                            }
                            else {
                                // Сравнение названия и издательства

                                /* Если книга без издателя — она не подходит по условиям проекта */
                                if (!$bookNextInfo[publisher])
                                    continue;

                                if ($bookInfo[title] == $bookNextInfo[title] && mb_strtolower($bookInfo[publisher]) == mb_strtolower($bookNextInfo[publisher])) {
                                    printLibs($client, $xpathSKBM, $nextBookI);
                                    array_push($sameISBNBookIArray, $nextBookI);
                                }
                            }
                        }

                        printBookContainerEnd();
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