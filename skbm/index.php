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
        <link href="/less/styles.less" rel="stylesheet/less" type="text/css">
        <script src="http://cdnjs.cloudflare.com/ajax/libs/less.js/3.0.0/less.min.js"></script>
    </head>
    <body>
        <?php
            require '../vendor/autoload.php';
            include '../remotetypograf.php';

            use GuzzleHttp\Client;

            $bookTitle = $_GET['title'];

            echo <<<HERE
                    <main>
                        <div class="container">
                            <div class="row">
                                <div class="col-sm-12 col-md-12 col-lg-12 col-xl-12">
                                    <div class="search-container">
                                        <label for="search_inp"><h4>Поиск книг в библиотеках Москвы</h4></label>
                                        <form action="" method="GET" class="form-inline search">
                                            <input type="search" name="title" id="search_inp" class="form-control" placeholder="Название книги" value='$bookTitle'>
                                            <button class="btn btn-primary ml-2">Найти</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
HERE;

        if($bookTitle) {

                // Инициализируем класс для работы с удалёнными веб-ресурсами
                $client = new Client();

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
                /*for ($bookI = 2; $bookI <= $bookCount; $bookI++) {*/
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

                    $remoteTypograf = new RemoteTypograf('UTF-8');
                    $titleTypografed = $remoteTypograf->processText($title);
                    $titleTypografed = strip_tags($titleTypografed);

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

            function getBookInfo($html, $infoType) {
                switch ($infoType) {
                    case 'ISBN':
                        preg_match('/<ISBN:>\s+([\d-]+)/', $html, $matches);
                        $ISBN = preg_replace("/[^0-9]/", '', $matches[1]);
                        return $ISBN;
                    case 'title':
                        preg_match('/к заглавию:> (.*)",\n"<Ответственность/', $html, $matches);
                        $title2 = $matches[1];
                        if ($title2) {
                            preg_match('/<Основное заглавие:> (.*?)"/', $html, $matches);
                            $title1 = $matches[1];
                            if (strpos($title2, '[') === 0)
                                $title2 = null;
                            else {
                                $title2 = str_replace('\\', '', $title2);
                                $title2 = '. ' . makeFirstLetterCapital($title2);
                            }
                        }
                        else {
                            preg_match('/заглавие:> (.*)"/', $html, $matches);
                            $title1 = $matches[1];
                            $title1 = str_replace('\\', '', $title1);
                        }
                        $title = $title1 . $title2;
                        $title = str_replace('!.', '!', $title);
                        return $title;
                    case 'publisher':
                        preg_match('/<Издательство:> (.*?)\[\/i]"/', $html, $matches);
                        $publisher = $matches[1];
                        $publisher = str_replace(['[i class=PU]', '[/i]'], '', $publisher);
                        $publisher = str_replace('\\"', '', $publisher);
                        $publisher = str_replace('ООО ', '', $publisher);
                        if ($publisher == 'Э' || $publisher == 'ЭКСМО')
                            $publisher = 'Эксмо';
                        if ($publisher == 'Альпина Паблишерз')
                            $publisher = 'Альпина Паблишер';
                        return $publisher;
                    case 'year':
                        preg_match('/<Дата издания:> (.*?)"/', $html, $matches);
                        return $matches[1];
                    case 'pages':
                        preg_match('/<Объем:> (.*?)"/', $html, $matches);
                        $pagesOriginally = $matches[1];
                        $pos = strpos($pagesOriginally, ',');
                        if($pos) {
                            $pages = '';
                            for ($i = 0; $i < $pos; $i++) {
                                $pages .= $pagesOriginally[$i];
                            }
                            $pages = preg_replace("/[^0-9]/", '', $pages);
                        }
                        else {
                            $pages = preg_replace("/[^0-9]/", '', $pagesOriginally);
                        }
                        $pages .= ' стр.';
                        return $pages;
                    case 'author':
                        preg_match('/<Ответственность:> (.*?)"/', $html, $matches);
                        if($matches[1]) {
                            $author = $matches[1];
                            // Если начинается с квадратной скобки или содержит переводчика — назначаем автора из Автора
                            if (strpos($author, '[') === 0 || strpos($author, 'пер.') === 0) {
                                preg_match('/<Автор:> ?(.*?)"/', $html, $matches);
                                $author  = str_replace(['[i class=RP]', '[/i]'], '', $matches[1]);
                            }
                        }
                        else {
                            preg_match('/<Автор:> ?(.*?)"/', $html, $matches);
                            $author  = str_replace(['[i class=RP]', '[/i]'], '', $matches[1]);
                        }
                        if (substr_count($author, ' ') <= 2) {
                            // Вместо «Сидорчик, Илья» — «Илья Сидорчик»
                            $author = explode(', ', $author);
                            $author = $author[1] . ' ' . $author[0];
                        }
                        return $author;
                }
            }

            function isLibraryFit($xpathSKBM, $bookI) {
                $isEbook = $xpathSKBM->query('//div[@id="searchrezult"]/div[@class="searchrez"]['.$bookI.']//div[@class="output"][1]/span')->length;
                if ($isEbook)
                    return false;

                // Библиотечные системы у книги
                $librarySystemCount = $xpathSKBM->query('//div[@id="searchrezult"]/div[@class="searchrez"]['.$bookI.']//div[@class="level"]')->length;
                for ($librarySystemI = 1; $librarySystemI <= $librarySystemCount; $librarySystemI += 2) {
                    $libraryName = $xpathSKBM->query('//div[@id="searchrezult"]/div[@class="searchrez"]['.$bookI.']//div[@class="level"]['.$librarySystemI.']//p[1]')[0]->nodeValue;
                    // Если библиотека не одна, то у неё два класса: row и ur,— $libraryFullAddress будет пустым
                    $libraryFullAddress = $xpathSKBM->query('//div[@id="searchrezult"]/div[@class="searchrez"]['.$bookI.']//div[@class="level"]['.$librarySystemI.']/div[@class="row"][1]/div[@class="td w30 p5x"]/input[@class="addr"]/@value')[0]->nodeValue;

                    if (!$libraryFullAddress) {
                        // Это библиотека-система
                        // Счётчик количества входящих библиотек
                        $librarySystemContentI = $librarySystemI + 1;
                        $libraryCount = $xpathSKBM->query('//div[@id="searchrezult"]/div[@class="searchrez"]['.$bookI.']//div[@class="level"]['.$librarySystemContentI.']/div[@class="row"]')->length;
                        if ($libraryCount == 1) {
                            $libraryName = $xpathSKBM->query('//div[@id="searchrezult"]/div[@class="searchrez"]['.$bookI.']//div[@class="level"]['.$librarySystemContentI.']/div[@class="row"][1]/div[@class="td loc"][1]//b')[0]->nodeValue;
                        }
                    }
                }
                if (strpos($libraryName, 'Детская') !== false || strpos($libraryName, 'детская') !== false || strpos($libraryName, 'читальня') !== false) {
                    return false;
                }
                else
                    return true;
            }

            function printLibs($client, $xpathSKBM, $bookI) {
                // Библиотечные системы у книги
                $librarySystemCount = $xpathSKBM->query('//div[@id="searchrezult"]/div[@class="searchrez"]['.$bookI.']//div[@class="level"]')->length;
                for ($librarySystemI = 1; $librarySystemI <= $librarySystemCount; $librarySystemI += 2) {

                    $libraryName = $xpathSKBM->query('//div[@id="searchrezult"]/div[@class="searchrez"]['.$bookI.']//div[@class="level"]['.$librarySystemI.']//p[1]')[0]->nodeValue;
                    // Если библиотека не одна, то у неё два класса: row и ur,— $libraryFullAddress будет пустым
                    $libraryFullAddress = $xpathSKBM->query('//div[@id="searchrezult"]/div[@class="searchrez"]['.$bookI.']//div[@class="level"]['.$librarySystemI.']/div[@class="row"][1]/div[@class="td w30 p5x"]/input[@class="addr"]/@value')[0]->nodeValue;

                    if ($libraryFullAddress) {
                        // Это библиотека-одиночка
                        $libraryAuthID = $xpathSKBM->query('//div[@id="searchrezult"]/div[@class="searchrez"]['.$bookI.']//div[@class="level"]['.$librarySystemI.']/div[@class="row"][1]/div[@class="td w30 p5x"]/input[@class="authid"]/@value')[0]->nodeValue;
                        printLib($libraryName, $libraryFullAddress, $libraryAuthID, $client);
                    }
                    else {
                        // Это библиотека-система
                        // Счётчик количества входящих библиотек
                        $librarySystemContentI = $librarySystemI + 1;
                        $libraryCount = $xpathSKBM->query('//div[@id="searchrezult"]/div[@class="searchrez"]['.$bookI.']//div[@class="level"]['.$librarySystemContentI.']/div[@class="row"]')->length;
                        for ($i = 1; $i <= $libraryCount; $i++) {
                            // Стягивание названия, адреса и AuthID у библиотеки
                            $libraryName = $xpathSKBM->query('//div[@id="searchrezult"]/div[@class="searchrez"]['.$bookI.']//div[@class="level"]['.$librarySystemContentI.']/div[@class="row"]['.$i.']/div[@class="td loc"][1]//b')[0]->nodeValue;
                            $libraryFullAddress = $xpathSKBM->query('///div[@id="searchrezult"]/div[@class="searchrez"]['.$bookI.']//div[@class="level"]['.$librarySystemContentI.']/div[@class="row"]['.$i.']/div[@class="td loc"][1]/p[2]')[0]->nodeValue;
                            $libraryAuthID = $xpathSKBM->query('//div[@id="searchrezult"]/div[@class="searchrez"]['.$bookI.']//div[@class="level"]['.$librarySystemContentI.']/div[@class="row"]['.$i.']/div[@class="td w30 p5x"]/input[@class="authid"]/@value')[0]->nodeValue;
                            printLib($libraryName, $libraryFullAddress, $libraryAuthID, $client);
                        }
                    }
                }
            }

            function printLib($libraryName, $libraryFullAddress, $libraryAuthID, $client) {
                // Отказ в печати библиотек, которые не выдают книги на дом взрослым
                if (strpos($libraryName, 'Детская') !== false || strpos($libraryName, 'детская') !== false || strpos($libraryName, 'читальня') !== false) {
                    return false;
                }

                // Сокращение названия библиотеки
                switch($libraryName) {
                    case 'Центральная универсальная научная библиотека имени Н.А. Некрасова':
                        $libraryName = 'Некрасова';
                        break;
                    case 'ГБУК города Москвы "Библиотека искусств им. А. П. Боголюбова"':
                        $libraryName = 'Боголюбова';
                        break;
                }

                if (strpos($libraryName, 'Библиотека №') !== false || strpos($libraryName, 'библиотека №')) {
                    // Вместо «Библиотека № 1 имени В. В. Пупкина» — «Пупкина»
                    if (strpos($libraryName, 'им.') !== false) {
                        $libraryName = explode('им.', $libraryName);
                        if(strpos($libraryName[1], '. ') !== false)
                            $libraryName = explode('. ', $libraryName[1]);
                        $libraryName = $libraryName[1];
                    }
                    // Вместо «Библиотека № 1…» — «№ 1…»
                    else {
                        $libraryName = str_replace('Библиотека №', '', $libraryName);
                        if($libraryName[0] == ' ') {
                            mb_internal_encoding("UTF-8");
                            $libraryName = mb_substr($libraryName, 1);
                        }
                        $libraryName = '№&nbsp;' . $libraryName;
                    }
                }

                $libraryName = 'Библиотека ' . $libraryName;

                // Если система, то удаляется слово «Централизованная»
                if (strpos($libraryName, 'Централизованная') !== false) {
                    $libraryName = explode('Централизованная ', $libraryName);
                    $libraryName = makeFirstLetterCapital($libraryName[1]);
                }

                $remoteTypograf = new RemoteTypograf('UTF-8');
                $libraryNameTypografed = $remoteTypograf->processText($libraryName);
                $libraryNameTypografed = strip_tags($libraryNameTypografed);

                $libraryAddress = findAddressWithMetro($libraryFullAddress);

                // Определение сайта библиотеки
                $responseLibraryInfo = $client->request('POST', 'http://skbm.nekrasovka.ru/request', [
                    'form_params' => [
                        '_action' => 'execute',
                        '_html' => 'stat',
                        '_errorhtml' => 'error',
                        'querylist' => '<_service>STORAGE:opacafd:View[separator]<_version>1.3.0[separator]<session>27510[separator]<iddb>100[separator]<id>'.$libraryAuthID.'[separator]<length>15[separator]<$length>15[separator]<$start>1[separator]<mode>OUTRECORD[separator]<outforms[0]>BLK856[separator]<outforms[1]>TITLE[separator]<outforms[2]>ADDRESS[separator]<outforms[3]>BLK305[separator]<outforms[4]>BLK300[separator]<outforms[5]>BLOCK310[separator]<outforms[6]>BLOCK320[separator]<outforms[7]>BLOCK330[separator]<outforms[8]>BLOCK340[separator]<outforms[9]>BLOCK4[separator]<outforms[10]>BLOCK5[separator]<outforms[11]>BLOCK7[separator]<userId>ADMIN[separator]<$basequant>2392771[separator]<$flag45>yes'
                    ]
                ]);
                $libraryInfo = $responseLibraryInfo->getBody();
                preg_match('/text: "Интернет-сайт\[END\](.*?)"/', $libraryInfo, $matches);
                $libraryTimetable = $matches[1];


                $library = [
                    "name" => "<a href='$libraryTimetable' class='static'>$libraryNameTypografed</a>",
                    "address" => $libraryAddress
                ];

                echo <<<HERE
                                            <div class="row">
                                                <div class="col-sm-12 col-md-12 col-lg-12 col-xl-8">
                                                    <div class="library">
                                                        <div class="libraryDesc">
                                                            <div class="name"><b>$library[name]</b></div>
                                                            <div class="details">
                                                                <div class="address">$library[address]</div>
                                                                <!--
                                                                <div class="timetable">
                                                                    span class="timetable-item today">Сегодня до 22</span>
                                                                    <a href="" class="timetable-item link">Режим работы</a>
                                                                </div>
                                                                -->
                                                            </div>
                                                        </div>
                                                        <div class="libraryBooking">
                                                            <input type="submit" name="to-book" class="btn btn-outline-dark btn-sm" value="Забронировать">
                                                            <div class="status small">
                                                                
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
HERE;
            }

            function findAddressWithMetro($libraryFullAddress) {
                // Для библиотек-одиночек нет адреса
                if ($libraryFullAddress == 'Россия, Москва') {
                    return;
                }
                // Если адрес уже с метро — оставляем
                if (strpos($libraryFullAddress, 'м. ') === 0) {
                    return $libraryFullAddress;
                }

                // Стирание страны, индекса, города в адресе
                if(strpos($libraryFullAddress, 'Москва')) {
                    $libraryFullAddress = str_replace('Москва ', 'Москва, ', $libraryFullAddress);
                    $address = explode('Москва, ', $libraryFullAddress);
                    $address = $address[1];
                }
                else {
                    $address = substr($libraryFullAddress, 21);
                }

                // Ставим недостающие пробелы: вместо «ул.Пушкина, дом.1» — «ул. Пушкина, д. 1»
                if(strpos($libraryFullAddress, '. ') == false) {
                    $address = str_replace('.', '. ', $address);
                }

                $metro = findMetro($address);

                $address = str_replace('д. ', '', $address);
                $address = str_replace(', корп. ', 'к', $address);

                return $metro . $address;
            }

            function findMetro($address) {
                // Определение координат библиотеки
                $address = str_replace(' ', '%20', $address);
                $contentGeocoder = file_get_contents("https://maps.googleapis.com/maps/api/geocode/json?key=AIzaSyBKEnemFnTUjnn3eMbWe_uJrOptTguXocU&address=Москва,%20$address");
                $jsonGeocoder = json_decode($contentGeocoder, true);

                // Проверка на перевыполнение трафика АПИ Геокодера
                $isExceeded = $jsonGeocoder["error_message"][0];
                if ($isExceeded) {
                    // Попытка подключиться к АПИ Геокодера без ключа, чтобы насобирать какие-то станции метро
                    $contentGeocoder = file_get_contents("https://maps.googleapis.com/maps/api/geocode/json?address=Москва,%20$address");
                    $jsonGeocoder = json_decode($contentGeocoder, true);

                    // Проверка на перевыполнение трафика АПИ Геокодера
                    $isExceeded = $jsonGeocoder["error_message"][0];
                    if ($isExceeded)
                        return;
                }

                $latitudeFrom = $jsonGeocoder["results"][0]["geometry"]["location"]["lat"];
                $longitudeFrom = $jsonGeocoder["results"][0]["geometry"]["location"]["lng"];

                // Подключение к базе данных с таблицей метро
                $ini = parse_ini_file('../app.ini', true);
                $link = mysqli_connect($ini[database][host], $ini[database][user], $ini[database][password], $ini[database][name]) or die('Ошибка');

                // Определение ближайшего метро путём перебора
                $min = 10000000;
                for($metroRowI = 1; $metroRowI <= 250; $metroRowI++) {
                    // Запрос на выборку для определения координат и названия станции метро
                    $result = mysqli_query($link, "SELECT name, latitude, longitude FROM metro WHERE id = $metroRowI");
                    $row = mysqli_fetch_assoc($result);

                    // Вычисление расстояния между библиотекой и станцией метро по формуле Хаверсина
                    $latFrom = deg2rad($latitudeFrom);
                    $lonFrom = deg2rad($longitudeFrom);
                    $latTo = deg2rad($row[latitude]);
                    $lonTo = deg2rad($row[longitude]);
                    $latDelta = $latTo - $latFrom;
                    $lonDelta = $lonTo - $lonFrom;
                    $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) + cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
                    $distance = $angle * 6371000;

                    // Если расстояние до станции метро меньше, чем минимальное найденное,— назначаем его минимальным, запоминаем название
                    if($distance < $min) {
                        $min = $distance;
                        $closestMetro = $row[name];
                    }
                }
                return 'м. ' . $closestMetro . ', ';
            }

            // Преобразование первого символа в верхний регистр
            function makeFirstLetterCapital($str, $encoding = 'UTF-8') {
                $str = mb_ereg_replace('^[\ ]+', '', $str);
                $str = mb_strtoupper(mb_substr($str, 0, 1, $encoding), $encoding) . mb_substr($str, 1, mb_strlen($str), $encoding);
                return $str;
            }
        ?>
            </div>
        </main>
    </body>
</html>