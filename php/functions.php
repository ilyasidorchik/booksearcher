<?php
    function printInput($bookTitle) {
        if (!$bookTitle)
            $autofocus = 'autofocus';

        echo <<<HERE
            <main class="mt-4">
                <div class="container">
                    <div class="row">
                         <div class="col-sm-12 col-md-12 col-lg-10 offset-lg-1 col-xl-8 offset-xl-2">
                             <div class="searchСontainer">
                                 <label for="search_inp"><h4>Поиск книг в библиотеках Москвы</h4></label>
                                 <form action="" method="GET" class="form-inline search">
                                     <input type="search" name="title" id="search_inp" class="form-control" placeholder="Название книги, автор или ISBN — что знаете" value='$bookTitle' $autofocus>
                                     <button class="btn btn-primary ml-2">Найти</button>
                                 </form>
                             </div>
                         </div>
                    </div>
HERE;
    }

    function getXPath_SKBM($client, $bookTitle) {
        // Запрос на страницу выдачи, ответ
        $response_SKBM = $client->request('POST', 'http://skbm.nekrasovka.ru/request', [
            'form_params' => [
                '_service' => 'STORAGE:opacfindd:IndexView',
                '_action' => 'php',
                '_errorhtml' => 'error1',
                '_handler' => 'search/search.php',
                'querylist' => '<_service>STORAGE:opacfindd:FindView[separator]<_version>2.5.0[separator]<session>31926[separator]<_start>0[separator]<start>0[separator]<$length>15[separator]<length>15[separator]<iddb>1[separator]<_showstr><i>Везде</i> ' . $bookTitle . '[separator]<_str>[bracket]AH ' . $bookTitle . '[/bracket][separator]<$outform>SHORTFM[separator]<outformList[0]/outform>SHORTFM[separator]<outformList[1]/outform>LINEORD[separator]<outformList[2]/outform>SHORTFMS[separator]<outformList[3]/outform>SHORTFMSTR[separator]<$filterstr>[bracket][bracket]LFR [apos]печатная/рукописная[apos][/bracket][/bracket] AND [bracket][bracket]LRES [apos]ТЕКСТЫ[apos][/bracket][/bracket] AND [bracket]LPUB [apos]КНИГИ[apos][/bracket][separator]<$filtersids>fixed_1_0_1530871811453[END]filter_1_2_0[END]filter_1_3_0[separator]<$fshowstr><i>форма ресурса</i> печатная/рукописная И <i>вид документа</i> тексты И <i>вид издания</i> книги[separator]<query/body>(AH ' . $bookTitle . ') AND ((LFR печатная/рукописная)) AND ((LRES ТЕКСТЫ)) AND (LPUB КНИГИ)[separator]<_history>yes[separator]<userId>ADMIN[separator]<$linkstring>043[ID]Заказ документа[END]044[ID]Заказ копии документа[END][separator]<level[0]>Full[separator]<level[1]>Retro[separator]<level[2]>Unfinished[separator]<level[3]>Identify[separator]<$swfterm>[bracket]AH ' . $bookTitle . '[/bracket] AND [bracket][bracket]LFR [apos]печатная/рукописная[apos][/bracket][/bracket] AND [bracket][bracket]LRES [apos]ТЕКСТЫ[apos][/bracket][/bracket] AND [bracket]LPUB [apos]КНИГИ[apos][/bracket][separator]<_iddb>1[separator]<$addfilters>[NEXT]filter_1_1_else[IND]fixed_1_0_1530871811453[CLASS](LFR печатная/рукописная)[TEXT]печатная/рукописная[separator]<$typework>search[separator]<$basequant>2398048[separator]<$flag45>yes',
                '_numsean' => '26026'
            ]
        ]);

        // Нахождение XPath из хтмла выдачи
        $html_SKBM = $response_SKBM->getBody();
        $doc_SKBM = new DOMDocument();
        @$doc_SKBM->loadHTML($html_SKBM);
        $xpath_SKBM = new DOMXpath($doc_SKBM);

        return $xpath_SKBM;
    }

    function getHtmlWithBookDetails_SKBM($client, $xpath_SKBM, $bookI_SKBM) {
        $bookID_SKBM = $xpath_SKBM->query('//div[@id="searchrezult"]/div[@class="searchrez"][' . $bookI_SKBM . ']/@id')[0]->nodeValue;
        $bookID_SKBM = str_replace('\\\\\\\\', '\\', $bookID_SKBM);
        $responseWithBookDetails_SKBM = $client->request('POST', 'http://skbm.nekrasovka.ru/request', [
            'form_params' => [
                '_action' => 'execute',
                '_html' => 'stat',
                '_errorhtml' => 'error',
                'querylist' => '<_service>STORAGE:opacfindd:FindView[separator]<_version>2.3.0[separator]<session>26210[separator]<iddbIds[0]/id>' . $bookID_SKBM . '[separator]<iddbIds[0]/iddb>1[separator]<outform>FULLFORM[separator]<_history>yes[separator]<$iddb>1[separator]<userId>ADMIN[separator]<$basequant>2391872[separator]<$flag45>yes'
            ]
        ]);
        return $responseWithBookDetails_SKBM->getBody();
    }

    function getBookInfo($catalog, $source) {
        switch ($catalog) {
            case 'ЦГДБ':
                $docNormal = new DOMDocument();
                @$docNormal->loadHTMLFile('http://catalog.mgdb.ru:49001/cgi-bin/koha/opac-detail.pl?biblionumber='.$source);
                $xpathNormal = new DOMXpath($docNormal);

                // Получение информации о книги с первой страницы
                $ISBN = $xpathNormal->query("//span[@class='results_summary'][span='ISBN: ']/text()")[0]->nodeValue;
                $ISBN = preg_replace("/[^0-9]/", '', $ISBN);

                // Получение информации о книги со страницы Марк-вью
                $docMarc = new DOMDocument();
                @$docMarc->loadHTMLFile('http://catalog.mgdb.ru:49001/cgi-bin/koha/opac-MARCdetail.pl?biblionumber='.$source);
                $xpathMarc = new DOMXpath($docMarc);

                $title = $xpathMarc->query("//tr[td='Основное заглавие']/td[2]")[0]->nodeValue;
                $titleTypografed = typograf($title);

                $author1Dirty = $xpathMarc->query("//tr[td='Часть имени, кроме начального элемента ввода']/td[2]")[0]->nodeValue;
                $author2Dirty = $xpathMarc->query("//tr[td='Начальный элемент ввода']/td[2]")[0]->nodeValue;
                $author1Dirty1 = str_replace(' ', '', $author1Dirty);
                $author = str_replace('.', '. ', $author1Dirty1) . $author2Dirty;

                $publisher = $xpathMarc->query("//tr[td='Издательство']/td[2]")[0]->nodeValue;

                $year = $xpathMarc->query("//tr[td='Дата издания, распространения и т.д.']/td[2]")[0]->nodeValue;

                $pages = $xpathMarc->query("//tr[td='Объем и специфическое обозначение материала']/td[2]")[0]->nodeValue;
                $pages = preg_replace("/[^0-9]/", '', $pages) . ' стр.';

                break;

            case 'СКБМ':
                // ISBN
                preg_match('/<ISBN:>\s+([\d-]+)/', $source, $matches);
                $ISBN = preg_replace("/[^0-9]/", '', $matches[1]);

                // Название
                preg_match('/к заглавию:> (.*)",\n"/', $source, $matches);
                $title2 = $matches[1];
                if ($title2) {
                    preg_match('/<Основное заглавие:> (.*?)",\n"/', $source, $matches);
                    $title1 = $matches[1];

                    if (strpos($title2, '[') === 0)
                        $title2 = null;
                    else
                        $title2 = '. ' . makeFirstLetterCapital($title2);
                }
                else {
                    preg_match('/заглавие:> (.*)"/', $source, $matches);
                    $title1 = $matches[1];
                    $title1 = str_replace('\\', '', $title1);
                }
                $title = $title1 . $title2;
                $title = str_replace('\\', '', $title);
                $titleTypografed = typograf($title);

                // Автор
                preg_match('/<Ответственность:> (.*?)"/', $source, $matches);
                if($matches[1]) {
                    $author = $matches[1];
                    // Если начинается с квадратной скобки или содержит переводчика — назначаем автора из Автора
                    if (strpos($author, '[') === 0 || strpos($author, 'пер.') === 0) {
                        preg_match('/<Автор:> ?(.*?)"/', $source, $matches);
                        $author  = str_replace(['[i class=RP]', '[/i]'], '', $matches[1]);
                    }
                }
                else {
                    preg_match('/<Автор:> ?(.*?)"/', $source, $matches);
                    $author  = str_replace(['[i class=RP]', '[/i]'], '', $matches[1]);
                }
                if (substr_count($author, ' ') <= 2) {
                    // Вместо «Сидорчик, Илья» — «Илья Сидорчик»
                    $author = explode(', ', $author);
                    $author = $author[1] . ' ' . $author[0];
                }

                // Издательство
                preg_match('/<Издательство:> (.*?)\[\/i]"/', $source, $matches);
                $publisher = $matches[1];
                $publisher = str_replace(['[i class=PU]', '[/i]'], '', $publisher);
                $publisher = str_replace('\\"', '', $publisher);
                $publisher = str_replace('ООО ', '', $publisher);
                if ($publisher == 'Э' || $publisher == 'ЭКСМО')
                    $publisher = 'Эксмо';
                if ($publisher == 'Альпина Паблишерз')
                    $publisher = 'Альпина Паблишер';

                // Год издания
                preg_match('/<Дата издания:> (.*?)"/', $source, $matches);
                $year = $matches[1];

                // Объём
                preg_match('/<Объем:> (.*?)"/', $source, $matches);
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

                break;
        }

        $bookInfo = [
            "ISBN" => $ISBN,
            "title" => $title,
            "titleTypografed" => $titleTypografed,
            "author" => $author,
            "publisher" => $publisher,
            "year" => $year,
            "pages" => $pages
        ];

        return $bookInfo;
    }

    function getLibraryInfo($library, $source) {
        switch ($library) {
            case 'ЦГДБ':
                $docNormal = new DOMDocument();
                @$docNormal->loadHTMLFile('http://catalog.mgdb.ru:49001/cgi-bin/koha/opac-detail.pl?biblionumber='.$source);
                $xpathNormal = new DOMXpath($docNormal);

                $availability = $xpathNormal->query("//table[@id='holdingst']/tbody/tr/td[2][starts-with(., 'Абонемент')]/../td[4][contains(text(),' Available ')]")->length;
                $availabilityOnHands = $xpathNormal->query("//table[@id='holdingst']/tbody/tr/td[2][starts-with(., 'Абонемент')]/../td[4][contains(text(),'Checked out')]")->length;

                // Если есть книги на руках, определение даты возврата
                if ($availabilityOnHands > 0) {
                    if ($availabilityOnHands === 1)
                        $availabilityOnHandsDate = $xpathNormal->query("//table[@id='holdingst']/tbody/tr/td[5]/text()")[0]->nodeValue;
                    else {
                        $i = 0;
                        while ($i < $availabilityOnHands) {
                            $availabilityOnHandsDate .= $xpathNormal->query("//table[@id='holdingst']/tbody/tr/td[5]/text()")[$i]->nodeValue;
                            if ($i + 1 != $availabilityOnHands)
                                $availabilityOnHandsDate .=  ',<br>';
                            $i++;
                        }
                    }

                    $availabilityOnHandsDate = str_replace('/', '.', $availabilityOnHandsDate);
                }


                // Формирование дива .libraryBooking о доступности
                if ($availability > 0)  {
                    $availabilityInfo = $availability . ' книг';
                    switch ($availability) {
                        case 1:
                            $availabilityInfo .= 'а';
                            break;
                        case 2:case 3:case 4:
                        $availabilityInfo .= 'и';
                    }

                    $availabilityInfo .= " для выдачи на дом";

                    $libraryBooking = '<div class="availabilityAtHome';

                    if ($availabilityOnHands)
                        $libraryBooking .= ' comma';

                    $libraryBooking .= '">'.$availabilityInfo.'</div>';

                    if ($availabilityOnHands)
                        $availabilityOnHandsInfo = "$availabilityOnHands на руках до $availabilityOnHandsDate";
                }
                else {
                    if ($availabilityOnHands) {
                        if ($availabilityOnHands === 1)
                            $availabilityOnHandsInfo = "На руках до $availabilityOnHandsDate";
                        else {
                            $availabilityOnHandsInfo = "На руках $availabilityOnHands книг";
                            if ($availabilityOnHands < 5)
                                $availabilityOnHandsInfo .= "и";
                            $availabilityOnHandsInfo .= " до $availabilityOnHandsDate";
                        }
                    }
                }

                if ($availabilityOnHands)
                    $libraryBooking .= "<div class='availabilityOnHands'>$availabilityOnHandsInfo</div>";

                $libraryBooking = "<div class='libraryBooking'>$libraryBooking</div>";

                return $library = [
                    "name" => "Деловая библиотека",
                    "address" => "м. ВДНХ, ул. Бориса Галушкина, 19к1",
                    "timetable" => "http://mgdb.mos.ru/",
                    "availability" => $libraryBooking
                ];
        }
    }

    function printBooksAndLibs_SKBM($booksCount_SKBM, $xpath_SKBM, $client, $arrayOfWasteBookI_SKBM, $bookInfo_MGDB, $isCheckOnSame) {
        for ($bookI_SKBM = 2; $bookI_SKBM <= $booksCount_SKBM; $bookI_SKBM++) {
            // В массиве $sameISBNBookIArray_SKBM хранятся индексы книг, которые уже напечатаны, не имеют автора и которые есть в библиотеках, не подходящих по условиям проекта
            if ($arrayOfWasteBookI_SKBM) {
                if (in_array($bookI_SKBM, $arrayOfWasteBookI_SKBM))
                    continue;
            }
            elseif (!is_array($arrayOfWasteBookI_SKBM))
                $arrayOfWasteBookI_SKBM = array();

            if (!isLibraryFit($xpath_SKBM, $bookI_SKBM)) {
                array_push($arrayOfWasteBookI_SKBM, $bookI_SKBM);
                continue;
            }

            $htmlWithBookDetails_SKBM = getHtmlWithBookDetails_SKBM($client, $xpath_SKBM, $bookI_SKBM);
            $bookInfo_SKBM = getBookInfo('СКБМ', $htmlWithBookDetails_SKBM);

            // Если книга без издателя — она не подходит по условиям проекта
            if (!$bookInfo_SKBM[publisher]) {
                array_push($arrayOfWasteBookI_SKBM, $bookI_SKBM);
                continue;
            }

            if ($isCheckOnSame == 'checkOnSameWithBookMGDB')
                $arrayOfWasteBookI_SKBM = printAllLibsWithSameBook_SKBM($bookInfo_MGDB, $bookInfo_SKBM, $bookI_SKBM, $client, $xpath_SKBM, $booksCount_SKBM, $arrayOfWasteBookI_SKBM);
            else {
                $arrayOfWasteBookI_SKBM = printAllLibs_SKBM($bookInfo_SKBM, $bookI_SKBM, $client, $xpath_SKBM, $booksCount_SKBM, $arrayOfWasteBookI_SKBM);
                printBookContainerEnd();
            }
        }

        return $arrayOfWasteBookI_SKBM;
    }

    function printAllLibsWithSameBook_SKBM($bookInfo_MGDB, $bookInfo_SKBM, $bookI_SKBM, $client, $xpath_SKBM, $booksCount_SKBM, $arrayOfWasteBookI_SKBM) {
        if (areBooksSame($bookInfo_MGDB, $bookInfo_SKBM)) {
            array_push($arrayOfWasteBookI_SKBM, $bookI_SKBM);

            if ($bookInfo_MGDB[year] != $bookInfo_SKBM[year])
                printLibs($client, $xpath_SKBM, $bookI_SKBM, $bookInfo_SKBM[year]);
            else
                printLibs($client, $xpath_SKBM, $bookI_SKBM, '');

            // Вывод библиотек, в которых есть книга с $bookI_SKBM, и запись их индексов в массив, чтобы не выводить ещё раз
            $nextBookIAfterCurrent_SKBM = $bookI_SKBM + 1;
            for ($nextBookI_SKBM = $nextBookIAfterCurrent_SKBM; $nextBookI_SKBM <= $booksCount_SKBM; $nextBookI_SKBM++) {
                if (!isLibraryFit($xpath_SKBM, $nextBookI_SKBM)) {
                    array_push($arrayOfWasteBookI_SKBM, $nextBookI_SKBM);
                    continue;
                }

                $htmlWithBookDetails_SKBM = getHtmlWithBookDetails_SKBM($client, $xpath_SKBM, $nextBookI_SKBM);
                $bookNextInfo_SKBM = getBookInfo('СКБМ', $htmlWithBookDetails_SKBM);

                // Если книга без издателя — она не подходит по условиям проекта
                if (!$bookInfo_SKBM[publisher]) {
                    array_push($arrayOfWasteBookI_SKBM, $bookI_SKBM);
                    continue;
                }

                // Проверка на совпадение. Сравнение ISBN или названия и издательства
                if (areBooksSame($bookInfo_SKBM, $bookNextInfo_SKBM)) {
                    array_push($arrayOfWasteBookI_SKBM, $nextBookI_SKBM);

                    if ($bookInfo_MGDB[year] != $bookNextInfo_SKBM[year] && $bookInfo_SKBM[year] != $bookNextInfo_SKBM[year])
                        printLibs($client, $xpath_SKBM, $nextBookI_SKBM, $bookNextInfo_SKBM[year]);
                    else
                        printLibs($client, $xpath_SKBM, $nextBookI_SKBM, '');
                }
            }
        }

        return $arrayOfWasteBookI_SKBM;
    }

    function printAllLibs_SKBM($bookInfo_SKBM, $bookI_SKBM, $client, $xpath_SKBM, $booksCount_SKBM, $arrayOfWasteBookI_SKBM) {
        printBook($bookInfo_SKBM);

        array_push($arrayOfWasteBookI_SKBM, $bookI_SKBM);
        printLibs($client, $xpath_SKBM, $bookI_SKBM, '');

        // Вывод библиотек, в которых есть книга с $bookI_SKBM, и запись их индексов в массив, чтобы не выводить ещё раз
        $nextBookIAfterCurrent_SKBM = $bookI_SKBM + 1;
        for ($nextBookI_SKBM = $nextBookIAfterCurrent_SKBM; $nextBookI_SKBM <= $booksCount_SKBM; $nextBookI_SKBM++) {
            if (!isLibraryFit($xpath_SKBM, $nextBookI_SKBM)) {
                array_push($arrayOfWasteBookI_SKBM, $nextBookI_SKBM);
                continue;
            }

            $htmlWithBookDetails_SKBM = getHtmlWithBookDetails_SKBM($client, $xpath_SKBM, $nextBookI_SKBM);
            $bookNextInfo_SKBM = getBookInfo('СКБМ', $htmlWithBookDetails_SKBM);

            // Если книга без издателя — она не подходит по условиям проекта
            if (!$bookInfo_SKBM[publisher]) {
                array_push($arrayOfWasteBookI_SKBM, $bookI_SKBM);
                continue;
            }

            // Проверка на совпадение. Сравнение ISBN или названия и издательства
            if (areBooksSame($bookInfo_SKBM, $bookNextInfo_SKBM)) {
                array_push($arrayOfWasteBookI_SKBM, $nextBookI_SKBM);

                if ($bookInfo_SKBM[year] != $bookNextInfo_SKBM[year])
                    printLibs($client, $xpath_SKBM, $nextBookI_SKBM, $bookNextInfo_SKBM[year]);
                else
                    printLibs($client, $xpath_SKBM, $nextBookI_SKBM, '');
            }
        }

        return $arrayOfWasteBookI_SKBM;
    }

    function areBooksSame($bookInfo1, $bookInfo2) {
        // Приведение названий в нижний регистр и очистка всех символов, кроме текста и запятой. Это нужно, например, чтобы считать одинаковыми книги «Бизнес: власть» и «Бизнес. Власть»
        $bookInfo1[title] = mb_strtolower(preg_replace("/[^\p{L},]/u", '', $bookInfo1[title]));
        $bookInfo2[title] = mb_strtolower(preg_replace("/[^\p{L},]/u", '', $bookInfo2[title]));

        if (($bookInfo1[ISBN] == $bookInfo2[ISBN]) || ($bookInfo1[title] == $bookInfo2[title] && mb_strtolower($bookInfo1[publisher]) == mb_strtolower($bookInfo2[publisher])))
            return true;
        else
            return false;
    }

    function printBook($bookInfo) {
        echo <<<HERE
            <div class="bookContainer">
                <div class="row">
                    <div class="col-sm-12 col-md-12 col-lg-10 offset-lg-1 col-xl-8 offset-xl-2">
                        <div class="book">
                            <div class="bookDesc">
                                 <h2>$bookInfo[titleTypografed]</h2>
                                 <div class="details lead">
                                    <span class="author">$bookInfo[author]</span>
                                    <span class="publisher">$bookInfo[publisher], $bookInfo[year]</span>
                                    <span class="pages">$bookInfo[pages]</span>
                                 </div>
                            </div>
                        </div>
                    </div>
                </div>
HERE;
    }

    function printBookContainerEnd() {
        echo '</div>';
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
                if ($libraryCount == 1)
                    $libraryName = $xpathSKBM->query('//div[@id="searchrezult"]/div[@class="searchrez"]['.$bookI.']//div[@class="level"]['.$librarySystemContentI.']/div[@class="row"][1]/div[@class="td loc"][1]//b')[0]->nodeValue;
            }
        }
        if (strpos($libraryName, 'Детская') !== false || strpos($libraryName, 'детская') !== false || strpos($libraryName, 'читальня') !== false)
            return false;
        else
            return true;
    }

    function printLibs($client, $xpathSKBM, $bookI, $bookInfoYear) {
        // Библиотечные системы у книги
        $librarySystemCount = $xpathSKBM->query('//div[@id="searchrezult"]/div[@class="searchrez"]['.$bookI.']//div[@class="level"]')->length;
        for ($librarySystemI = 1; $librarySystemI <= $librarySystemCount; $librarySystemI += 2) {
            $libraryName = $xpathSKBM->query('//div[@id="searchrezult"]/div[@class="searchrez"]['.$bookI.']//div[@class="level"]['.$librarySystemI.']//p[1]')[0]->nodeValue;
            // Если библиотека не одна, то у неё два класса: row и ur,— $libraryFullAddress будет пустым
            $libraryFullAddress = $xpathSKBM->query('//div[@id="searchrezult"]/div[@class="searchrez"]['.$bookI.']//div[@class="level"]['.$librarySystemI.']/div[@class="row"][1]/div[@class="td w30 p5x"]/input[@class="addr"]/@value')[0]->nodeValue;

            if ($libraryFullAddress) {
                // Это библиотека-одиночка
                $libraryAuthID = $xpathSKBM->query('//div[@id="searchrezult"]/div[@class="searchrez"]['.$bookI.']//div[@class="level"]['.$librarySystemI.']/div[@class="row"][1]/div[@class="td w30 p5x"]/input[@class="authid"]/@value')[0]->nodeValue;
                printLib($libraryName, $libraryFullAddress, $libraryAuthID, $client, $bookInfoYear);
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
                    printLib($libraryName, $libraryFullAddress, $libraryAuthID, $client, $bookInfoYear);
                }
            }
        }
    }

    function printLib($libraryName, $libraryFullAddress, $libraryAuthID, $client, $bookInfoYear) {
        // Отказ в печати библиотек, которые не выдают книги на дом взрослым
        if (strpos($libraryName, 'Детская') !== false || strpos($libraryName, 'детская') !== false || strpos($libraryName, 'читальня') !== false)
            return false;

        // Сокращение названия библиотеки
        switch($libraryName) {
            case 'Центральная универсальная научная библиотека имени Н.А. Некрасова':
                $libraryName = 'Некрасова';
                break;
            case 'ГБУК города Москвы "Библиотека искусств им. А. П. Боголюбова"':
                $libraryName = 'Боголюбова';
                break;
            case 'Библиотека им. Ф.В. Гладкова № 224':
                $libraryName = 'Гладкова';
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

        $libraryNameTypografed = typograf($libraryName);

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
        $libraryTimetable = $responseLibraryInfo->getBody();
        preg_match('/text: "Интернет-сайт\[END\](.*?)"/', $libraryTimetable, $matches);
        $libraryTimetable = $matches[1];

        if (!$libraryAddress)
            $libraryAddress = '&nbsp;';

        if ($bookInfoYear)
            $libraryBooking = "<div class='libraryBooking'>Книга издана в&nbsp;$bookInfoYear</div>";

        $libraryInfo = [
            "name" => $libraryNameTypografed,
            "address" => $libraryAddress,
            "timetable" => $libraryTimetable,
            "availability" => $libraryBooking
        ];

        printLibrary($libraryInfo);
    }

    function printLibrary($libraryInfo) {
        echo <<<HERE
            <div class="row">
                <div class="col-sm-12 col-md-12 col-lg-10 offset-lg-1 col-xl-8 offset-xl-2">
                    <div class="library">
                        <div class="libraryDesc">
                            <div class="name"><a href='$libraryInfo[timetable]' class='static'>$libraryInfo[name]</a></div>
                            <div class="details">
                                <div class="address">$libraryInfo[address]</div>
                            </div>
                        </div>
                        $libraryInfo[availability]
                    </div>
                </div>
            </div>
HERE;
    }

    function findAddressWithMetro($libraryFullAddress) {
        // Для библиотек-одиночек нет адреса
        if ($libraryFullAddress == 'Россия, Москва')
            return;

        // Если адрес уже с метро — оставляем
        if (strpos($libraryFullAddress, 'м. ') === 0)
            return $libraryFullAddress;

        // Стирание страны, индекса, города в адресе
        if(strpos($libraryFullAddress, 'Москва')) {
            $libraryFullAddress = str_replace('Москва ', 'Москва, ', $libraryFullAddress);
            $address = explode('Москва, ', $libraryFullAddress);
            $address = $address[1];
        }
        else
            $address = substr($libraryFullAddress, 21);

        // Ставим недостающие пробелы: вместо «ул.Пушкина, дом.1» — «ул. Пушкина, д. 1»
        if(strpos($libraryFullAddress, '. ') == false)
            $address = str_replace('.', '. ', $address);

        $metro = findMetro($address);

        $address = str_replace('д. ', '', $address);
        $address = str_replace(', к. ', 'к', $address);
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
        require 'db_connection.php';
        $link = mysqli_connect($host, $user, $password, $database) or die("Не удалось подключиться к базе данных");
        mysqli_set_charset($link, "utf8");

        // Определение ближайшего метро путём перебора
        $min = 10000000;
        for($metroRowI = 1; $metroRowI <= 250; $metroRowI++) {
            // Запрос на выборку для определения координат и названия станции метро
            $result = mysqli_query($link, "SELECT metroName, metroLatitude, metroLongitude FROM metro WHERE metroID = $metroRowI");
            $row = mysqli_fetch_assoc($result);

            // Вычисление расстояния между библиотекой и станцией метро по формуле Хаверсина
            $latFrom = deg2rad($latitudeFrom);
            $lonFrom = deg2rad($longitudeFrom);
            $latTo = deg2rad($row[metroLatitude]);
            $lonTo = deg2rad($row[metroLongitude]);
            $latDelta = $latTo - $latFrom;
            $lonDelta = $lonTo - $lonFrom;
            $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) + cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
            $distance = $angle * 6371000;

            // Если расстояние до станции метро меньше, чем минимальное найденное,— назначаем его минимальным, запоминаем название
            if($distance < $min) {
                $min = $distance;
                $closestMetro = $row[metroName];
            }
        }
        return 'м. ' . $closestMetro . ', ';
    }

    function makeFirstLetterCapital($str, $encoding = 'UTF-8') {
        $str = mb_ereg_replace('^[\ ]+', '', $str);
        $str = mb_strtoupper(mb_substr($str, 0, 1, $encoding), $encoding) . mb_substr($str, 1, mb_strlen($str), $encoding);
        return $str;
    }

    include 'remotetypograf.php';
    function typograf($str) {
        $remoteTypograf = new RemoteTypograf('UTF-8');
        $strTypografed = $remoteTypograf->processText($str);
        $strTypografed = strip_tags($strTypografed);
        $strTypografed = substr($strTypografed, 0, -1);
        return $strTypografed;
    }

    function sendEmailForBooking($client, $email, $surname, $title, $author, $publisher, $year) {
        $response = $client->request('POST', 'http://sidorchik.ru/mail-for-book-booking/', [
            'form_params' => [
                'email' => $email,
                'surname' => $surname,
                'title' => $title,
                'author' => $author,
                'publisher' => $publisher,
                'year' => $year
            ]
        ]);
    }
    function sendEmailForRequesting($client, $email, $surname, $title, $author) {
        $response = $client->request('POST', 'http://sidorchik.ru/mail-for-book-requesting/', [
            'form_params' => [
                'email' => $email,
                'surname' => $surname,
                'title' => $title,
                'author' => $author
            ]
        ]);
    }

    function getReaderID($link, $encryption) {
        $result = mysqli_query($link, "SELECT readerID FROM readers WHERE encryption = '$encryption'");
        $row = mysqli_fetch_assoc($result);
        return $row['readerID'];
    }

    function addToBooked($link, $readerID, $title, $author, $publisher, $year, $pages) {
        mysqli_query($link, "INSERT INTO `booked` (`id`, `readerID`, `bookTitle`, `bookAuthor`, `bookPublisher`, `bookYear`, `bookPages`, `libraryName`, `libraryAddress`, `libraryTimetable`) VALUES (NULL, '$readerID', '$title', '$author', '$publisher', '$year', '$pages', 'Деловая библиотека', 'м. ВДНХ, ул. Бориса Галушкина, 19к1', 'http://mgdb.mos.ru/contacts/info/')");
    }

    function addToWishlist($link, $readerID, $title, $author, $publisher, $year, $pages) {
        mysqli_query($link, "INSERT INTO `wishlist` (`id`, `readerID`, `bookTitle`, `bookAuthor`, `bookPublisher`, `bookYear`, `bookPages`) VALUES (NULL, '$readerID', '$title', '$author', '$publisher', '$year', '$pages')");
    }

    function addToRequested($link, $readerID, $title, $author) {
        mysqli_query($link, "INSERT INTO `requested` (`id`, `readerID`, `bookTitle`, `bookAuthor`) VALUES (NULL, '$readerID', '$title', '$author')");
    }