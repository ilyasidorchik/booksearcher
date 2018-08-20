<?php
    if ($_SERVER['SERVER_NAME'] == 'dev.booksearcher.ru')
        $devMode = true;

    function printMessageAboutNoFoundAndRequestForm($bookTitle) {
        echo <<<HERE
                        <div class="container">
                            <div class="row mb-3">
                                <div class="d-none d-md-block col-lg-10 offset-lg-1 col-xl-8 offset-xl-2">
                                    <b>Книги нет <a href="https://docs.google.com/document/d/1zHMqFfosGLYYG-jE6NskzIqpjWD23PU9avdPaAH0g3Q/edit?usp=sharing" class="static"><nobr>в библиотеках-участниках</nobr></a></b>
                                    <p>Но она может появиться через <nobr>полгода-год</nobr>, <nobr>если вы попросите библиотеку Некрасова:</nobr></p>
                                </div>
                                <div class="d-block d-md-none">
                                    <b>Книги нет <a href="https://docs.google.com/document/d/1zHMqFfosGLYYG-jE6NskzIqpjWD23PU9avdPaAH0g3Q/edit?usp=sharing" class="static">в библиотеках-участниках</a></b>
                                    <p>Но она может появиться через <nobr>полгода-год</nobr>, если вы попросите библиотеку Некрасова:</p>
                                </div>
                            </div>  
                            <div class="row">
                                <div class="col-sm-12 col-md-7 col-lg-5 offset-lg-1 col-xl-4 offset-xl-2">     
                                    <form class="form">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="exampleModalCenterTitle">Запрос книги</h5>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="title" id='title' value='$bookTitle'>
                                            <div class="form-group">
                                                <label for="author">Автор</label>
                                                <input type="text" name="author" class="form-control" id="author" aria-describedby="authorHelp" required>
                                                <small id="authorHelp" class="form-text text-muted">Чтобы не подумали о другой книге</small>
                                            </div>
HERE;

        // Если в учётной записи нет почты — показываем поля почты и фамилии
        $encryption = $_COOKIE["encryption"];

        /* Подключение к базе данных */
        include 'db_connection.php';
        $link = mysqli_connect($host, $user, $password, $database) or die("Ошибка");

        $result = mysqli_query($link, "SELECT email FROM readers WHERE encryption = '$encryption'");
        $row = mysqli_fetch_assoc($result);
        if (!$row['email']) {
            echo <<<HERE
                                            <div class="form-group">
                                                <label for="email">Ваша эл. почта</label>
                                                <input type="email" name="email" class="form-control" id="email" aria-describedby="emailHelp" required>
                                                <small id="emailHelp" class="form-text text-muted">Библиотекарь напишет в случае чего</small>
                                            </div>
                                            <div class="form-group">
                                                <label for="surname">Ваша фамилия</label>
                                                <input type="text" name="surname" class="form-control" id="surname" aria-describedby="surnameHelp" required>
                                                <small id="surnameHelp" class="form-text text-muted">Для связи с библиотекарем</small>
                                            </div>
HERE;
        }
        else {
            $email = $row['email'];
            $surname = $row['surname'];

            echo "<input type='hidden' name='email' id='email' value='$email'>
                  <input type='hidden' name='surname' id='surname' value='$surname'>";
        }

        $title = typograf('«' . $bookTitle . '»');
        
        echo <<<HERE
                                        </div>
                                        <div class="modal-footer">
                                            <input type="button" class="btn btn-primary" id="toRequest" value="Запросить">
                                        </div>
                                    </form>
                                    <div class="formProof alert alert-success" role="alert" style="display: none;">
                                        <h4 class="alert-heading">
                                            Книга запрошена
                                        </h4>
                                        <p>В отдел формирования фонда библиотеки Некрасова отправлено письмо с просьбой приобрести книгу $title автора <span id="authorAdd"></span>.</p>
                                        <p>Если книгу одобрят, она появится не скоро: через <nobr>полгода-год</nobr>. С вами должны связаться по почте <span id="emailAdd">$email</span>.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
HERE;
    }

    function getBookID_SKBM($xpath_SKBM, $bookI_SKBM) {
        $bookID_SKBM = $xpath_SKBM->query('//div[@id="searchrezult"]/div[@class="searchrez"][' . $bookI_SKBM . ']/@id')[0]->nodeValue;
        $bookID_SKBM = str_replace('\\\\\\\\', '\\', $bookID_SKBM);
        return $bookID_SKBM;
    }

    function getHtmlWithBookDetails_SKBM($client, $xpath_SKBM, $bookI_SKBM) {
        $bookID_SKBM = getBookID_SKBM($xpath_SKBM, $bookI_SKBM);
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
                $callNumber = $xpathNormal->query("//*[@id='holdingst']/tbody/tr[1]/td[3]")[0]->nodeValue;
                $callNumber = str_replace(['(Browse Shelf)', ' '], '', $callNumber);

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
                $pages = preg_replace("/[^0-9]/", '', $pages) . '&nbsp;с.';

                break;

            case 'СКБМ':
                // ISBN
                preg_match('/<ISBN:>\s+([\d-]+)/', $source, $matches);
                $ISBNWithDashes = $matches[1];
                $ISBN = preg_replace("/[^0-9]/", '', $ISBNWithDashes);

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
                $year = preg_replace("/[^0-9]/", '', $matches[1]);
                $year = substr($year, 0, 4);

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
                $pages .= '&nbsp;с.';

                break;
        }

        $bookInfo = [
            "ISBN" => $ISBN,
            "ISBNWithDashes" => $ISBNWithDashes,
            "title" => $title,
            "titleTypografed" => $titleTypografed,
            "author" => $author,
            "publisher" => $publisher,
            "year" => $year,
            "pages" => $pages,
            "callNumber" => $callNumber
        ];

        return $bookInfo;
    }

    function getLibraryInfo($library, $source, $bookInfo_MGDB) {
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
                    $date = date('l');
                    $time = date('Hi');

                    if ($date == 'Friday' && $time >= 2000 || $date == 'Saturday' || $date == 'Sunday' || $date == 'Monday') {
                        // Следующий рабочий день — следующий вторник
                        $nextWorkingDay = date('j.m', strtotime("next Tuesday"));

                        // Определение последнего вторника в месяце — санитарного дня
                        $month = date('m');
                        $daysInMonth = date('t');
                        $year = date('Y');
                        for ($i = 1; $i <= $daysInMonth; $i++) {
                            if (date('w', strtotime("$i.$month.$year")) == 2)
                                $cleanupDay = $i;
                        }
                        $cleanupDay .= ".$month"; // для даты формата 31.01

                        // Если следующий рабочий день — санитарный,
                        // значит, действительный следующий рабочий день — после
                        if ($nextWorkingDay == $cleanupDay)
                            $nextWorkingDay = date('j.m', strtotime("next day $nextWorkingDay"));


                        $nextWorkingDay = explode('.', $nextWorkingDay);
                        switch ($nextWorkingDay[1]) {
                            case '01':
                                $nextWorkingDay[1] = 'января';
                                break;
                            case '02':
                                $nextWorkingDay[1] = 'февраля';
                                break;
                            case '03':
                                $nextWorkingDay[1] = 'марта';
                                break;
                            case '04':
                                $nextWorkingDay[1] = 'апреля';
                                break;
                            case '05':
                                $nextWorkingDay[1] = 'мая';
                                break;
                            case '06':
                                $nextWorkingDay[1] = 'июня';
                                break;
                            case '07':
                                $nextWorkingDay[1] = 'июля';
                                break;
                            case '08':
                                $nextWorkingDay[1] = 'августа';
                                break;
                            case '09':
                                $nextWorkingDay[1] = 'сентября';
                                break;
                            case '10':
                                $nextWorkingDay[1] = 'октября';
                                break;
                            case '11':
                                $nextWorkingDay[1] = 'ноября';
                                break;
                            case '12':
                                $nextWorkingDay[1] = 'декабря';
                                break;
                        }

                        $nextWorkingDay = "<br>на $nextWorkingDay[0] $nextWorkingDay[1]";


                        $hint = "<div class='hint'>
                                                    <div class='text'>
                                                        <b>Почему нельзя забронировать раньше</b>";

                        if ($date == 'Monday')
                            $hint .= "<p>По понедельникам библиотека закрыта для читателей.</p>";
                        else
                            $hint .= "<p>1. Секретарь, который получает запросы на бронь, не работает по выходным.</p>
                                      <p>2. По понедельникам библиотека закрыта для читателей.</p>";

                        $hint .= "</div>
                                </div>";

                    }

                    echo $bookingButton = "<div class='libraryBookingButton'>
                                            <button type='button' class='btn btn-outline-dark btn-sm' data-toggle='modal' data-target='#bookingForm'>Забронировать".$nextWorkingDay."…</button>
                                            $hint
                                        
                                            <div class='modal fade' id='bookingForm' tabindex='-1' role='dialog' aria-labelledby='bookingFormTitle' aria-hidden='true'>
                                                <div class='modal-dialog modal-dialog-centered' role='document'>
                                                    <div class='modal-contentformBooking'>
                                                          <div class='modal-header'>
                                                                <h5 class='modal-title' id='exampleModalCenterTitle'>Бронирование книги</h5>
                                                                <button type='button' class='close' data-dismiss='modal' aria-label='Close'>
                                                                  <span aria-hidden='true'>&times;</span>
                                                                </button>
                                                          </div>
                                                          <form action='book.php' method='POST' id='formBooking'>
                                                              <div class='modal-body'>
                                                                    <div class='form-group'>
                                                                        <label for='email'>Ваша эл. почта</label>
                                                                        <input type='email' class='form-control' id='email' name='email' aria-describedby='emailHelp' required>
                                                                        <small id='emailHelp' class='form-text text-muted'>Библиотекарь подтвердит бронь или напишет в случае чего</small>
                                                                    </div>
                                                                    <div class='form-group'>
                                                                        <label for='surname'>Ваша фамилия</label>
                                                                        <input type='text' class='form-control' id='surname' name='surname' aria-describedby='surnameHelp' required>
                                                                        <small id='surnameHelp' class='form-text text-muted'>Назовёте в библиотеке</small>
                                                                    </div>
                                                                    <input type='hidden' name='title' value='$bookInfo_MGDB[title]'>
                                                                    <input type='hidden' name='author' value='$bookInfo_MGDB[author]'>
                                                                    <input type='hidden' name='publisher' value='$bookInfo_MGDB[publisher]'> 
                                                                    <input type='hidden' name='year' value='$bookInfo_MGDB[year]'>
                                                                    <input type='hidden' name='pages' value='$bookInfo_MGDB[pages]'>
                                                                    <input type='hidden' name='callNumber' value='$bookInfo_MGDB[callNumber]'>
                                                                </div>
                                                              <div class='modal-footer'>
                                                                    <button name='toBook' class='btn btn-primary'>Забронировать$nextWorkingDay</button>
                                                              </div>
                                                          </form>
                                                    </div>
                                                    <div class='formProof alert alert-success' role='alert' style='display: none;'>
                                                        <h4 class='alert-heading'>Книга забронирована</h4>
                                                        <p>В Деловую библиотеку отправлено письмо с просьбой забронировать книгу на фамилию <span id='surnameAdd'>$surname</span>.</p>
                                                        <p>Почту регулярно проверяет секретарь, он передаст просьбу библиотекарю. Библиотекарь отложит книгу, но он может не написать вам&nbsp;об этом.</p>
                                                        <p><i>Как забрать книгу.</i> Входите в библиотеку, идёте направо, говорите библиотекарю о брони, называете свою&nbsp;фамилию.</p>
                                                    </div>
                                                </div>
                                            </div>
                                      </div>";

                    $availabilityInfo = $availability . ' книг';
                    switch ($availability) {
                        case 1:
                            $availabilityInfo .= 'а';
                            break;
                        case 2:case 3:case 4:
                            $availabilityInfo .= 'и';
                            break;
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


                if ($libraryBooking != '')
                    $libraryBooking = "<div class='libraryBooking'><div class='libraryBookingText'>$libraryBooking</div>$bookingButton</div>";

                return $library = [
                    "name" => "Деловая библиотека",
                    "address" => "м. ВДНХ, ул. Бориса Галушкина, 19к1",
                    "timetable" => "http://mgdb.mos.ru/",
                    "availability" => $libraryBooking
                ];
        }
    }

    function printBooksAndLibs_SKBM($client, $bookTitle, $arrayOfWasteBookI_SKBM, $bookInfo_MGDB, $isCheckOnSame) {
        $pages_SKBM = 1;

        for ($page_SKBM = 1; $page_SKBM <= $pages_SKBM; $page_SKBM++) {
            $pageForRequest_SKBM = ($page_SKBM - 1) * 15;

            // Запрос на страницу выдачи, ответ
            $response_SKBM = $client->request('POST', 'http://skbm.nekrasovka.ru/request', [
                'form_params' => [
                    '_service' => 'STORAGE:opacfindd:IndexView',
                    '_action' => 'php',
                    '_errorhtml' => 'error1',
                    '_handler' => 'search/search.php',
                    'querylist' => '<_service>STORAGE:opacfindd:FindView[separator]<_version>2.5.0[separator]<session>31926[separator]<_start>'.$pageForRequest_SKBM.'[separator]<start>'.$pageForRequest_SKBM.'[separator]<$length>15[separator]<length>15[separator]<iddb>1[separator]<_showstr><i>Везде</i> '.$bookTitle.'[separator]<_str>[bracket]AH '.$bookTitle.'[/bracket][separator]<$outform>SHORTFM[separator]<outformList[0]/outform>SHORTFM[separator]<outformList[1]/outform>LINEORD[separator]<outformList[2]/outform>SHORTFMS[separator]<outformList[3]/outform>SHORTFMSTR[separator]<$filterstr>[bracket][bracket]LFR [apos]печатная/рукописная[apos][/bracket][/bracket] AND [bracket][bracket]LRES [apos]ТЕКСТЫ[apos][/bracket][/bracket] AND [bracket]LPUB [apos]КНИГИ[apos][/bracket][separator]<$filtersids>fixed_1_0_1530871811453[END]filter_1_2_0[END]filter_1_3_0[separator]<$fshowstr><i>форма ресурса</i> печатная/рукописная И <i>вид документа</i> тексты И <i>вид издания</i> книги[separator]<query/body>(AH ' . $bookTitle . ') AND ((LFR печатная/рукописная)) AND ((LRES ТЕКСТЫ)) AND (LPUB КНИГИ)[separator]<_history>yes[separator]<userId>ADMIN[separator]<$linkstring>043[ID]Заказ документа[END]044[ID]Заказ копии документа[END][separator]<level[0]>Full[separator]<level[1]>Retro[separator]<level[2]>Unfinished[separator]<level[3]>Identify[separator]<$swfterm>[bracket]AH ' . $bookTitle . '[/bracket] AND [bracket][bracket]LFR [apos]печатная/рукописная[apos][/bracket][/bracket] AND [bracket][bracket]LRES [apos]ТЕКСТЫ[apos][/bracket][/bracket] AND [bracket]LPUB [apos]КНИГИ[apos][/bracket][separator]<_iddb>1[separator]<$addfilters>[NEXT]filter_1_1_else[IND]fixed_1_0_1530871811453[CLASS](LFR печатная/рукописная)[TEXT]печатная/рукописная[separator]<$typework>search[separator]<$basequant>2398048[separator]<$flag45>yes',
                    '_numsean' => '31926'
                ]
            ]);

            // Нахождение XPath из хтмла выдачи
            $html_SKBM = $response_SKBM->getBody();
            $doc_SKBM = new DOMDocument();
            @$doc_SKBM->loadHTML($html_SKBM);
            $xpath_SKBM = new DOMXpath($doc_SKBM);

            $pages_SKBM = $xpath_SKBM->query('//*[@id="infor"]/div[5]/p[1]/*')->length;

            // Чтобы была одна страница
            if ($pages_SKBM == 0)
                $pages_SKBM = 1;

            $booksCount_SKBM = $xpath_SKBM->query('//div[@id="searchrezult"]/div[@class="searchrez"]')->length;

            for ($bookI_SKBM = 2; $bookI_SKBM <= $booksCount_SKBM; $bookI_SKBM++) {
                // В массиве $sameISBNBookIArray_SKBM хранятся индексы книг, которые уже напечатаны, не имеют автора и которые есть в библиотеках, не подходящих по условиям проекта
                if ($arrayOfWasteBookI_SKBM) {
                    if (in_array($page_SKBM . '_' . $bookI_SKBM, $arrayOfWasteBookI_SKBM))
                        continue;
                }
                elseif (!is_array($arrayOfWasteBookI_SKBM))
                    $arrayOfWasteBookI_SKBM = array();

                if (!isLibraryFit($xpath_SKBM, $bookI_SKBM)) {
                    array_push($arrayOfWasteBookI_SKBM, $page_SKBM . '_' . $bookI_SKBM);
                    continue;
                }

                $htmlWithBookDetails_SKBM = getHtmlWithBookDetails_SKBM($client, $xpath_SKBM, $bookI_SKBM);
                $bookInfo_SKBM = getBookInfo('СКБМ', $htmlWithBookDetails_SKBM);

                // Если книга без издателя — она не подходит по условиям проекта
                if (!$bookInfo_SKBM[publisher]) {
                    array_push($arrayOfWasteBookI_SKBM, $page_SKBM . '_' . $bookI_SKBM);
                    continue;
                }

                if ($isCheckOnSame == 'checkOnSameWithBookMGDB')
                    $arrayOfWasteBookI_SKBM = printLibsWithSameBookMGDB_SKBM($bookInfo_MGDB, $bookInfo_SKBM, $pages_SKBM, $page_SKBM, $bookI_SKBM, $client, $xpath_SKBM, $booksCount_SKBM, $arrayOfWasteBookI_SKBM);
                else {
                    $arrayOfWasteBookI_SKBM = printBookAndLibsWithIt_SKBM($bookTitle, $bookInfo_SKBM, $bookI_SKBM, $client, $xpath_SKBM, $pages_SKBM, $page_SKBM, $booksCount_SKBM, $arrayOfWasteBookI_SKBM);
                    printBookContainerEnd();
                }
            }
        }

        return $arrayOfWasteBookI_SKBM;
    }

    function printLibsWithSameBookMGDB_SKBM($bookInfo_MGDB, $bookInfo_SKBM, $pages_SKBM, $page_SKBM, $bookI_SKBM, $client, $xpath_SKBM, $booksCount_SKBM, $arrayOfWasteBookI_SKBM) {
        if (areBooksSame($bookInfo_MGDB, $bookInfo_SKBM)) {
            array_push($arrayOfWasteBookI_SKBM, $page_SKBM . '_' . $bookI_SKBM);

            if ($bookInfo_MGDB[year] != $bookInfo_SKBM[year])
                printLibs($client, $xpath_SKBM, $bookI_SKBM, $bookInfo_SKBM[year], $bookInfo_SKBM);
            else
                printLibs($client, $xpath_SKBM, $bookI_SKBM, '', $bookInfo_SKBM);

            // Вывод библиотек, в которых есть книга с $bookI_SKBM, и запись их индексов в массив, чтобы не выводить ещё раз
            $pageStart_SKBM = $page_SKBM;
            for ($page_SKBM = $pageStart_SKBM; $page_SKBM <= $pages_SKBM; $page_SKBM++) {
                // Чтобы не делать ещё раз такой же запрос
                if ($page_SKBM > $pageStart_SKBM) {
                    $bookI_SKBM = 0;

                    $pageForRequest_SKBM = ($page_SKBM - 1) * 15;

                    // Запрос на страницу выдачи, ответ
                    $response_SKBM = $client->request('POST', 'http://skbm.nekrasovka.ru/request', [
                        'form_params' => [
                            '_service' => 'STORAGE:opacfindd:IndexView',
                            '_action' => 'php',
                            '_errorhtml' => 'error1',
                            '_handler' => 'search/search.php',
                            'querylist' => '<_service>STORAGE:opacfindd:FindView[separator]<_version>2.5.0[separator]<session>31926[separator]<_start>' . $pageForRequest_SKBM . '[separator]<start>' . $pageForRequest_SKBM . '[separator]<$length>15[separator]<length>15[separator]<iddb>1[separator]<_showstr><i>Везде</i> ' . $bookTitle . '[separator]<_str>[bracket]AH ' . $bookTitle . '[/bracket][separator]<$outform>SHORTFM[separator]<outformList[0]/outform>SHORTFM[separator]<outformList[1]/outform>LINEORD[separator]<outformList[2]/outform>SHORTFMS[separator]<outformList[3]/outform>SHORTFMSTR[separator]<$filterstr>[bracket][bracket]LFR [apos]печатная/рукописная[apos][/bracket][/bracket] AND [bracket][bracket]LRES [apos]ТЕКСТЫ[apos][/bracket][/bracket] AND [bracket]LPUB [apos]КНИГИ[apos][/bracket][separator]<$filtersids>fixed_1_0_1530871811453[END]filter_1_2_0[END]filter_1_3_0[separator]<$fshowstr><i>форма ресурса</i> печатная/рукописная И <i>вид документа</i> тексты И <i>вид издания</i> книги[separator]<query/body>(AH ' . $bookTitle . ') AND ((LFR печатная/рукописная)) AND ((LRES ТЕКСТЫ)) AND (LPUB КНИГИ)[separator]<_history>yes[separator]<userId>ADMIN[separator]<$linkstring>043[ID]Заказ документа[END]044[ID]Заказ копии документа[END][separator]<level[0]>Full[separator]<level[1]>Retro[separator]<level[2]>Unfinished[separator]<level[3]>Identify[separator]<$swfterm>[bracket]AH ' . $bookTitle . '[/bracket] AND [bracket][bracket]LFR [apos]печатная/рукописная[apos][/bracket][/bracket] AND [bracket][bracket]LRES [apos]ТЕКСТЫ[apos][/bracket][/bracket] AND [bracket]LPUB [apos]КНИГИ[apos][/bracket][separator]<_iddb>1[separator]<$addfilters>[NEXT]filter_1_1_else[IND]fixed_1_0_1530871811453[CLASS](LFR печатная/рукописная)[TEXT]печатная/рукописная[separator]<$typework>search[separator]<$basequant>2398048[separator]<$flag45>yes',
                            '_numsean' => '31926'
                        ]
                    ]);

                    // Нахождение XPath из хтмла выдачи
                    $html_SKBM = $response_SKBM->getBody();
                    $doc_SKBM = new DOMDocument();
                    @$doc_SKBM->loadHTML($html_SKBM);
                    $xpath_SKBM = new DOMXpath($doc_SKBM);

                    $pages_SKBM = $xpath_SKBM->query('//*[@id="infor"]/div[5]/p[1]/*')->length;

                    $booksCount_SKBM = $xpath_SKBM->query('//div[@id="searchrezult"]/div[@class="searchrez"]')->length;
                }

                $nextBookIAfterCurrent_SKBM = $bookI_SKBM + 1;
                for ($nextBookI_SKBM = $nextBookIAfterCurrent_SKBM; $nextBookI_SKBM <= $booksCount_SKBM; $nextBookI_SKBM++) {
                    if (!isLibraryFit($xpath_SKBM, $nextBookI_SKBM)) {
                        array_push($arrayOfWasteBookI_SKBM, $page_SKBM . '_' . $nextBookI_SKBM);
                        continue;
                    }

                    $htmlWithBookDetails_SKBM = getHtmlWithBookDetails_SKBM($client, $xpath_SKBM, $nextBookI_SKBM);
                    $bookNextInfo_SKBM = getBookInfo('СКБМ', $htmlWithBookDetails_SKBM);

                    // Если книга без издателя — она не подходит по условиям проекта
                    if (!$bookInfo_SKBM[publisher]) {
                        array_push($arrayOfWasteBookI_SKBM, $page_SKBM . '_' . $bookI_SKBM);
                        continue;
                    }

                    // Проверка на совпадение. Сравнение ISBN или названия и издательства
                    if (areBooksSame($bookInfo_SKBM, $bookNextInfo_SKBM)) {
                        array_push($arrayOfWasteBookI_SKBM, $page_SKBM . '_' . $nextBookI_SKBM);

                        if ($bookInfo_MGDB[year] != $bookNextInfo_SKBM[year] && $bookInfo_SKBM[year] != $bookNextInfo_SKBM[year])
                            printLibs($client, $xpath_SKBM, $nextBookI_SKBM, $bookNextInfo_SKBM[year], $bookInfo_SKBM);
                        else
                            printLibs($client, $xpath_SKBM, $nextBookI_SKBM, '', $bookInfo_SKBM);
                    }
                }
            }
        }

        return $arrayOfWasteBookI_SKBM;
    }

    function printBookAndLibsWithIt_SKBM($bookTitle, $bookInfo_SKBM, $bookI_SKBM, $client, $xpath_SKBM, $pages_SKBM, $page_SKBM, $booksCount_SKBM, $arrayOfWasteBookI_SKBM) {
        printBook($bookInfo_SKBM);

        array_push($arrayOfWasteBookI_SKBM, $page_SKBM . '_' . $bookI_SKBM);
        printLibs($client, $xpath_SKBM, $bookI_SKBM, '', $bookInfo_SKBM);

        // Вывод библиотек, в которых есть книга с $bookI_SKBM, и запись их индексов в массив, чтобы не выводить ещё раз
        $pageStart_SKBM = $page_SKBM;
        for ($page_SKBM = $pageStart_SKBM; $page_SKBM <= $pages_SKBM; $page_SKBM++) {
            // Чтобы не делать ещё раз такой же запрос
            if ($page_SKBM > $pageStart_SKBM) {
                $bookI_SKBM = 0;

                $pageForRequest_SKBM = ($page_SKBM - 1) * 15;

                // Запрос на страницу выдачи, ответ
                $response_SKBM = $client->request('POST', 'http://skbm.nekrasovka.ru/request', [
                    'form_params' => [
                        '_service' => 'STORAGE:opacfindd:IndexView',
                        '_action' => 'php',
                        '_errorhtml' => 'error1',
                        '_handler' => 'search/search.php',
                        'querylist' => '<_service>STORAGE:opacfindd:FindView[separator]<_version>2.5.0[separator]<session>31926[separator]<_start>' . $pageForRequest_SKBM . '[separator]<start>' . $pageForRequest_SKBM . '[separator]<$length>15[separator]<length>15[separator]<iddb>1[separator]<_showstr><i>Везде</i> ' . $bookTitle . '[separator]<_str>[bracket]AH ' . $bookTitle . '[/bracket][separator]<$outform>SHORTFM[separator]<outformList[0]/outform>SHORTFM[separator]<outformList[1]/outform>LINEORD[separator]<outformList[2]/outform>SHORTFMS[separator]<outformList[3]/outform>SHORTFMSTR[separator]<$filterstr>[bracket][bracket]LFR [apos]печатная/рукописная[apos][/bracket][/bracket] AND [bracket][bracket]LRES [apos]ТЕКСТЫ[apos][/bracket][/bracket] AND [bracket]LPUB [apos]КНИГИ[apos][/bracket][separator]<$filtersids>fixed_1_0_1530871811453[END]filter_1_2_0[END]filter_1_3_0[separator]<$fshowstr><i>форма ресурса</i> печатная/рукописная И <i>вид документа</i> тексты И <i>вид издания</i> книги[separator]<query/body>(AH ' . $bookTitle . ') AND ((LFR печатная/рукописная)) AND ((LRES ТЕКСТЫ)) AND (LPUB КНИГИ)[separator]<_history>yes[separator]<userId>ADMIN[separator]<$linkstring>043[ID]Заказ документа[END]044[ID]Заказ копии документа[END][separator]<level[0]>Full[separator]<level[1]>Retro[separator]<level[2]>Unfinished[separator]<level[3]>Identify[separator]<$swfterm>[bracket]AH ' . $bookTitle . '[/bracket] AND [bracket][bracket]LFR [apos]печатная/рукописная[apos][/bracket][/bracket] AND [bracket][bracket]LRES [apos]ТЕКСТЫ[apos][/bracket][/bracket] AND [bracket]LPUB [apos]КНИГИ[apos][/bracket][separator]<_iddb>1[separator]<$addfilters>[NEXT]filter_1_1_else[IND]fixed_1_0_1530871811453[CLASS](LFR печатная/рукописная)[TEXT]печатная/рукописная[separator]<$typework>search[separator]<$basequant>2398048[separator]<$flag45>yes',
                        '_numsean' => '31926'
                    ]
                ]);

                // Нахождение XPath из хтмла выдачи
                $html_SKBM = $response_SKBM->getBody();
                $doc_SKBM = new DOMDocument();
                @$doc_SKBM->loadHTML($html_SKBM);
                $xpath_SKBM = new DOMXpath($doc_SKBM);

                $pages_SKBM = $xpath_SKBM->query('//*[@id="infor"]/div[5]/p[1]/*')->length;

                $booksCount_SKBM = $xpath_SKBM->query('//div[@id="searchrezult"]/div[@class="searchrez"]')->length;
            }

            $nextBookIAfterCurrent_SKBM = $bookI_SKBM + 1;
            for ($nextBookI_SKBM = $nextBookIAfterCurrent_SKBM; $nextBookI_SKBM <= $booksCount_SKBM; $nextBookI_SKBM++) {
                if (!isLibraryFit($xpath_SKBM, $nextBookI_SKBM)) {
                    array_push($arrayOfWasteBookI_SKBM, $page_SKBM . '_' . $nextBookI_SKBM);
                    continue;
                }

                $htmlWithBookDetails_SKBM = getHtmlWithBookDetails_SKBM($client, $xpath_SKBM, $nextBookI_SKBM);
                $bookNextInfo_SKBM = getBookInfo('СКБМ', $htmlWithBookDetails_SKBM);

                // Если книга без издателя — она не подходит по условиям проекта
                if (!$bookInfo_SKBM[publisher]) {
                    array_push($arrayOfWasteBookI_SKBM, $page_SKBM . '_' . $bookI_SKBM);
                    continue;
                }

                // Проверка на совпадение. Сравнение ISBN или названия и издательства
                if (areBooksSame($bookInfo_SKBM, $bookNextInfo_SKBM)) {
                    array_push($arrayOfWasteBookI_SKBM, $page_SKBM . '_' . $nextBookI_SKBM);

                    if ($bookInfo_SKBM[year] != $bookNextInfo_SKBM[year])
                        printLibs($client, $xpath_SKBM, $nextBookI_SKBM, $bookNextInfo_SKBM[year], $bookNextInfo_SKBM);
                    else
                        printLibs($client, $xpath_SKBM, $nextBookI_SKBM, '', $bookInfo_SKBM);
                }
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

    function printLibs($client, $xpathSKBM, $bookI, $bookInfoYear, $bookInfo_SKBM) {
        // Библиотечные системы у книги
        $librarySystemCount = $xpathSKBM->query('//div[@id="searchrezult"]/div[@class="searchrez"]['.$bookI.']//div[@class="level"]')->length;
        for ($librarySystemI = 1; $librarySystemI <= $librarySystemCount; $librarySystemI += 2) {
            $libraryName = $xpathSKBM->query('//div[@id="searchrezult"]/div[@class="searchrez"]['.$bookI.']//div[@class="level"]['.$librarySystemI.']//p[1]')[0]->nodeValue;
            // Если библиотека не одна, то у неё два класса: row и ur,— $libraryFullAddress будет пустым
            $libraryFullAddress = $xpathSKBM->query('//div[@id="searchrezult"]/div[@class="searchrez"]['.$bookI.']//div[@class="level"]['.$librarySystemI.']/div[@class="row"][1]/div[@class="td w30 p5x"]/input[@class="addr"]/@value')[0]->nodeValue;

            if ($libraryFullAddress) {
                // Это библиотека-одиночка
                $libraryAuthID = $xpathSKBM->query('//div[@id="searchrezult"]/div[@class="searchrez"]['.$bookI.']//div[@class="level"]['.$librarySystemI.']/div[@class="row"][1]/div[@class="td w30 p5x"]/input[@class="authid"]/@value')[0]->nodeValue;
                printLib($libraryName, $libraryFullAddress, $libraryAuthID, $client, $bookInfoYear, $bookInfo_SKBM);
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
                    printLib($libraryName, $libraryFullAddress, $libraryAuthID, $client, $bookInfoYear, $bookInfo_SKBM);
                }
            }
        }
    }

    function printLib($libraryName, $libraryFullAddress, $libraryAuthID, $client, $bookInfoYear, $bookInfo_SKBM) {
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


        if ($libraryName == 'Библиотека Некрасова') {
            $libraryBooking = getBookAvailabilityInNekrasovka($client, $bookInfo_SKBM);
            $libraryBookingText = $libraryBooking[availabilityInfoText];
            $callNumber = $libraryBooking[callNumber];

            if (strpos($libraryBookingText,'для выдачи на дом') !== false) {
                // Если в учётной записи есть почта — бронирование книги в один клик
                $encryption = $_COOKIE["encryption"];
                // Подключение к базе данных
                include 'db_connection.php';
                $link = mysqli_connect($host, $user, $password, $database) or die("Ошибка");
                mysqli_set_charset($link, 'utf8');
                $result = mysqli_query($link, "SELECT * FROM readers WHERE encryption = '$encryption'");
                $row = mysqli_fetch_assoc($result);

                if ($row['email'])  {
                    $titleQuotedTypografed = typograf('«' . $bookInfo_SKBM[title] . '»');
                    $publisherQuotedTypografed = typograf('«' . $bookInfo_SKBM[publisher] . '»');

                    $libraryBookingButton = "<button class='btn btn-outline-dark' data-toggle='modal' data-target='#deliveryForm$bookInfo_SKBM[ISBN]'>Доставить…</button>
                                             <div class='modal fade' id='deliveryForm$bookInfo_SKBM[ISBN]' tabindex='-1' role='dialog' aria-labelledby='bookingFormTitle' aria-hidden='true'>
                                                <div class='modal-dialog modal-dialog-centered' role='document'>
                                                    <div class='modal-content'>
                                                          <div class='modal-header'>
                                                                <h5 class='modal-title' id='exampleModalCenterTitle'>Доставка книг из библиотеки Некрасова</h5>
                                                                <button type='button' class='close' data-dismiss='modal' aria-label='Close'>
                                                                  <span aria-hidden='true'>&times;</span>
                                                                </button>
                                                          </div>
                                                          <form>
                                                              <div class='modal-body' style='padding-bottom: 0;'>
                                                                    <div class='delivery'>
                                                                        <div class='deliveryOrder'>
                                                                            <p>
                                                                                <b>Для кого</b><br>
                                                                                Для жителей Москвы, у которых есть читательский билет Некрасовки.
                                                                            </p>
                                                                            <p>
                                                                                <b>Как заказать</b><br>
                                                                                Вы пишете мне в Телеграме <a href='https://t.me/ilyasidorchik' class='static'>@ilyasidorchik</a>: копируете нижеприведённый текст, дописываете время, место встречи и фотографируете штрихкод на задней стороне читательского.
                                                                            </p>
                                                                            <div class='deliveryOrderTemplate'>
                                                                                <p>
                                                                                    Илья, привет!
                                                                                </p>
                                                                                <p>
                                                                                    Привези книгу $titleQuotedTypografed: автор $bookInfo_SKBM[author], издательство $publisherQuotedTypografed, год выпуска $bookInfo_SKBM[year].
                                                                                </p>
                                                                                <p>
                                                                                    Давай встретимся сегодня в 20:00 по адресу: Декабристов, 35, подъезд 1, домофон #3501, этаж 7.
                                                                                </p>
                                                                                <p>
                                                                                    Мой читательский:
                                                                                </p>
                                                                            </div>
                                                                            <p>
                                                                                <b>Сколько стоит</b><br>
                                                                                69 ₽. Дешевле, чем дорога в библиотеку.
                                                                                <br>Успейте, пока цена не поднялась.
                                                                            </p>
                                                                            <p>
                                                                                <b>Когда и как оплатить</b><br>
                                                                                При получении книжки. Как вам удобно: наличными или переводом на карту (Сбербанка, Тинькоффа или Рокета).
                                                                            </p>
                                                                        </div>
                                                                        <div class='deliveryMan'>
                                                                            <div class='deliveryManPhoto'>
                                                                                <img src='/img/ilya-sidorchik.png'>
                                                                            </div>
                                                                            <div class='deliveryManText'>
                                                                                <p>
                                                                                    <b>Кто доставит</b><br>
                                                                                    Я, <a href='http://sidorchik.ru' class='static'>Илья&nbsp;Сидорчик</a>, создатель этого сайта.
                                                                                </p>
                                                                                <p>
                                                                                    <b>Сколько книг</b><br>
                                                                                    Можно заказать до 5 книг. Это<br>не влияет на цену.
                                                                                </p>
                                                                                <p>
                                                                                    <b>Можно вернуть</b><br>
                                                                                    Когда прочитаете<br>книгу, я&nbsp;верну её<br>в&nbsp;библиотеку.<br>Тоже за&nbsp;69&nbsp;₽.
                                                                                </p>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                              </div>
                                                          </form>
                                                    </div>
                                                </div>
                                             </div>";

                    $libraryBookingButton .= "<form class='formBooking'>
                                                <input type='hidden' name='email' value='$row[email]'>
                                                <input type='hidden' name='surname' value='$row[surname]'>
                                                <input type='hidden' name='titleBooking' value='$bookInfo_SKBM[title]'>
                                                <input type='hidden' name='author' value='$bookInfo_SKBM[author]'>
                                                <input type='hidden' name='publisher' value='$bookInfo_SKBM[publisher]'> 
                                                <input type='hidden' name='year' value='$bookInfo_SKBM[year]'>
                                                <input type='hidden' name='pages' value='$bookInfo_SKBM[pages]'>
                                                <input type='hidden' name='callNumber' value='$callNumber'>
                                                <input type='button' class='btn btn-outline-dark' value='Забронировать'>
                                            </form>
                                            <div class='formProof' style='display: none;'>
                                                <button class='btn btn-success' disabled>Забронировано</button>
                                            </div>";
                }
                else {
                    $titleQuotedTypografed = typograf('«' . $bookInfo_SKBM[title] . '»');
                    $publisherQuotedTypografed = typograf('«' . $bookInfo_SKBM[publisher] . '»');

                    $libraryBookingButton = "<button class='btn btn-outline-dark' data-toggle='modal' data-target='#deliveryForm$bookInfo_SKBM[ISBN]'>Доставить…</button>
                                             <div class='modal fade' id='deliveryForm$bookInfo_SKBM[ISBN]' tabindex='-1' role='dialog' aria-labelledby='bookingFormTitle' aria-hidden='true'>
                                                <div class='modal-dialog modal-dialog-centered' role='document'>
                                                    <div class='modal-content'>
                                                          <div class='modal-header'>
                                                                <h5 class='modal-title' id='exampleModalCenterTitle'>Доставка книг из библиотеки Некрасова</h5>
                                                                <button type='button' class='close' data-dismiss='modal' aria-label='Close'>
                                                                  <span aria-hidden='true'>&times;</span>
                                                                </button>
                                                          </div>
                                                          <form>
                                                              <div class='modal-body' style='padding-bottom: 0;'>
                                                                    <div class='delivery'>
                                                                        <div class='deliveryOrder'>
                                                                            <p>
                                                                                <b>Для кого</b><br>
                                                                                Для жителей Москвы, у которых есть читательский билет Некрасовки.
                                                                            </p>
                                                                            <p>
                                                                                <b>Как заказать</b><br>
                                                                                Вы пишете мне в Телеграме <a href='https://t.me/ilyasidorchik' class='static'>@ilyasidorchik</a>: копируете нижеприведённый текст, дописываете время, место встречи и фотографируете штрихкод на задней стороне читательского.
                                                                            </p>
                                                                            <div class='deliveryOrderTemplate'>
                                                                                <p>
                                                                                    Илья, привет!
                                                                                </p>
                                                                                <p>
                                                                                    Привези книгу $titleQuotedTypografed: автор $bookInfo_SKBM[author], издательство $publisherQuotedTypografed, год выпуска $bookInfo_SKBM[year].
                                                                                </p>
                                                                                <p>
                                                                                    Давай встретимся сегодня в 20:00 по адресу: Декабристов, 35, подъезд 1, домофон #3501, этаж 7.
                                                                                </p>
                                                                                <p>
                                                                                    Мой читательский:
                                                                                </p>
                                                                            </div>
                                                                            <p>
                                                                                <b>Сколько стоит</b><br>
                                                                                69 ₽. Дешевле, чем дорога в библиотеку.
                                                                                <br>Успейте, пока цена не поднялась.
                                                                            </p>
                                                                            <p>
                                                                                <b>Когда и как оплатить</b><br>
                                                                                При получении книжки. Как вам удобно: наличными или переводом на карту (Сбербанка, Тинькоффа или Рокета).
                                                                            </p>
                                                                        </div>
                                                                        <div class='deliveryMan'>
                                                                            <div class='deliveryManPhoto'>
                                                                                <img src='/img/ilya-sidorchik.png'>
                                                                            </div>
                                                                            <div class='deliveryManText'>
                                                                                <p>
                                                                                    <b>Кто доставит</b><br>
                                                                                    Я, <a href='http://sidorchik.ru' class='static'>Илья&nbsp;Сидорчик</a>, создатель этого сайта.
                                                                                </p>
                                                                                <p>
                                                                                    <b>Сколько книг</b><br>
                                                                                    Можно заказать до 5 книг. Это<br>не влияет на цену.
                                                                                </p>
                                                                                <p>
                                                                                    <b>Можно вернуть</b><br>
                                                                                    Когда прочитаете<br>книгу, я&nbsp;верну её<br>в&nbsp;библиотеку.<br>Тоже за&nbsp;69&nbsp;₽.
                                                                                </p>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                              </div>
                                                          </form>
                                                    </div>
                                                </div>
                                             </div>
                                             
                                             <button type='button' class='btn btn-outline-dark' data-toggle='modal' data-target='#bookingForm$bookInfo_SKBM[ISBN]'>Забронировать…</button>                             
                                             <div class='modal fade' id='bookingForm$bookInfo_SKBM[ISBN]' tabindex='-1' role='dialog' aria-labelledby='bookingFormTitle' aria-hidden='true'>
                                                <div class='modal-dialog modal-dialog-centered' role='document'>
                                                    <div class='modal-content formBooking'>
                                                          <div class='modal-header'>
                                                                <h5 class='modal-title' id='exampleModalCenterTitle'>Бронирование книги</h5>
                                                                <button type='button' class='close' data-dismiss='modal' aria-label='Close'>
                                                                  <span aria-hidden='true'>&times;</span>
                                                                </button>
                                                          </div>
                                                          <form>
                                                              <div class='modal-body'>
                                                                    <div class='form-group'>
                                                                        <label for='email$bookInfo_SKBM[ISBN]'>Ваша эл. почта</label>
                                                                        <input type='email' name='email' class='form-control' id='email$bookInfo_SKBM[ISBN]' aria-describedby='emailHelp$bookInfo_SKBM[ISBN]' required>
                                                                        <small id='emailHelp$bookInfo_SKBM[ISBN]' class='form-text text-muted'>Библиотекарь подтвердит бронь или напишет в случае чего</small>
                                                                    </div>
                                                                    <div class='form-group'>
                                                                        <label for='surname$bookInfo_SKBM[ISBN]'>Ваша фамилия</label>
                                                                        <input type='text' name='surname' class='form-control' id='surname$bookInfo_SKBM[ISBN]' aria-describedby='surnameHelp$bookInfo_SKBM[ISBN]' required>
                                                                        <small id='surnameHelp$bookInfo_SKBM[ISBN]' class='form-text text-muted'>Назовёте в библиотеке</small>
                                                                    </div>
                                                                    <input type='hidden' name='titleBooking' value='$bookInfo_SKBM[title]'>
                                                                    <input type='hidden' name='author' value='$bookInfo_SKBM[author]'>
                                                                    <input type='hidden' name='publisher' value='$bookInfo_SKBM[publisher]'>
                                                                    <input type='hidden' name='year' value='$bookInfo_SKBM[year]'>
                                                                    <input type='hidden' name='pages' value='$bookInfo_SKBM[pages]'>
                                                                    <input type='hidden' name='callNumber' value='$callNumber'>
                                                                </div>
                                                              <div class='modal-footer'>
                                                                    <input type='button' class='btn btn-primary' value='Забронировать'>
                                                              </div>
                                                          </form>
                                                    </div>
                                                    <div class='formProof alert alert-success' role='alert' style='display: none;'>
                                                        <h4 class='alert-heading'>Книга будет забронирована</h4>
                                                        <p>В библиотеку Некрасова отправлено письмо с просьбой забронировать книгу на фамилию <span class='surnameAdd'>$surname</span>.</p>
                                                        <p>Почту регулярно проверяют библиотекари, они отложат книгу и напишут&nbsp;вам.</p>
                                                    </div>
                                                </div>
                                             </div>";
                }


                $libraryBookingButton = "<div class='libraryBookingButton'>$libraryBookingButton</div>";
            }


        }

        if ($bookInfoYear)
            $bookInfoYear = "<div class='libraryBookOtherYear'>$bookInfoYear</div>";

        if ($libraryBookingText != '')
            $libraryBooking = "<div class='libraryBooking'>
                               $libraryBookingButton
                               <div class='libraryBookingText'>$libraryBookingText</div>
                               $bookInfoYear
                           </div>";

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

    function getBookAvailabilityInNekrasovka($client, $bookInfo_SKBM) {
        $ISBNWithDashed = $bookInfo_SKBM[ISBNWithDashes];

        $response = $client->request('POST', 'http://opac.nekrasovka.ru/request', [
            'form_params' => [
                '_action' => 'php',
                '_errorhtml' => 'error1',
                '_handler' => 'search/search.php',
                'querylist' => '<_service>STORAGE:opacfindd:FindView[separator]<_version>2.5.0[separator]<session>1450305[separator]<_start>0[separator]<start>0[separator]<$length>15[separator]<length>15[separator]<_showstr><i>Везде</i> ' . $ISBNWithDashed . '[separator]<_str>[bracket]AH ' . $ISBNWithDashed . '[/bracket][separator]<$outform>SHOTWEB[separator]<outformList[0]/outform>SHOTWEB[separator]<outformList[1]/outform>LINEORD[separator]<iddb>988[separator]<query/body>(AH ' . $ISBNWithDashed . ')[separator]<userId>IGUES[separator]<level[0]>Full[separator]<level[1]>Retro[separator]<_iddb>988[separator]<$typework>search',
                '_numsean' => '1450305'
            ]
        ]);
        $html = $response->getBody();

        $doc = new DOMDocument();
        @$doc->loadHTML($html);
        $xpath_SKBM = new DOMXpath($doc);

        $bookID_SKBM = getBookID_SKBM($xpath_SKBM, 1);
        if (stripos($bookID_SKBM, '-') !== false)
            $bookID_SKBM = getBookID_SKBM($xpath_SKBM, 2);
        $bookID_SKBM = str_replace('\\\\', '\\', $bookID_SKBM);

        $availabilityResponse = $client->request('POST', 'http://opac.nekrasovka.ru/request', [
            'form_params' => [
                '_action' => 'execute',
                '_html' => 'stat',
                '_errorhtml' => 'error',
                'querylist' => '<_service>STORAGE:opacholdd:MoveCopies[separator]<_version>1.1.0[separator]<session>1450305[separator]<iddb>5[separator]<idbr>' . $bookID_SKBM . '[separator]<copyform>SEE7BB[separator]<writeoff>false[separator]<userId>IGUES'
            ]
        ]);
        $availabilityResponse = $availabilityResponse->getBody();


        $booksCount = preg_match_all('/_permanentLocation: "(.*?)"/', $availabilityResponse, $matches);
        $booksForHome = 0;
        $availability = 0;
        preg_match_all('/_status: "(.*?)"/', $availabilityResponse, $matchesStatus);
        for ($i = 0; $i < $booksCount; $i++) {
            if (strpos($matches[1][$i], 'ЧЗ') === false && strpos($matches[1][$i], 'чз') === false && strpos($matches[1][$i], 'РФ') === false && strpos($matches[1][$i], 'рф') === false && strpos($matches[1][$i], 'НЕТ ДАННЫХ') === false) {
                $booksForHome++;
                if ($matchesStatus[1][$i] == 1)
                    $availability++;
            }
        }

        if ($availability) {
            preg_match('/Шифр: \[\/b\](.*?)\[br\]/', $availabilityResponse, $matchesCallNumber);
            $callNumber = $matchesCallNumber[1];

            $availabilityInfo = "$availability книг";

            switch ($availability) {
                case 1:
                    $availabilityInfo .= 'а';
                    break;
                case 2:case 3:case 4:
                    $availabilityInfo .= 'и';
                    break;
            }

            $availabilityInfo .= ' для выдачи на дом';

            if ($booksForHome > $availability) {
                $otherBooksForHome = $booksForHome - $availability;

                $availabilityInfo .= ",<br>на руках";

                $availabilityInfo .= " $otherBooksForHome книг";

                switch ($otherBooksForHome) {
                    case 1:
                        $availabilityInfo .= 'а';
                        break;
                    case 2:case 3:case 4:
                        $availabilityInfo .= 'и';
                        break;
                }

                $dates = array();
                $booksCount = preg_match_all('/_location: "(.*?)"/', $availabilityResponse, $matches);
                for ($i = 0; $i < $booksCount; $i++) {
                    if (stristr($matches[1][$i], 'В хранении') == false) {
                        $date = str_replace('На руках до', '', $matches[1][$i]);

                        if (stristr($matches[1][$i], ' - ') == false) {
                            array_push($dates, $date);
                        }
                    }
                }

                $availabilityInfo .= ' до ';
                foreach ($dates as $date) {
                    $availabilityInfo .= $date . ', ';
                }
                if (substr($availabilityInfo, -2) == ', ')
                    $availabilityInfo = substr($availabilityInfo, 0, -2);
            }
        }
        else {
            if ($booksForHome) {
                $availabilityInfo = "На руках";

                if ($booksForHome > 1) {
                    $availabilityInfo .= " $booksForHome книг";
                    switch ($booksForHome) {
                        case 2:case 3:case 4:
                            $availabilityInfo .= 'и';
                            break;
                    }
                }

                $availabilityInfo .= ' до ';

                $dates = array();
                $booksCount = preg_match_all('/_location: "(.*?)"/', $availabilityResponse, $matches);
                for ($i = 0; $i < $booksCount; $i++) {
                    $date = preg_replace("/[^.0-9]/", '', $matches[1][$i]);
                    array_push($dates, $date);
                }

                $dates = dsort($dates);

                foreach ($dates as $date) {
                    $availabilityInfo .= $date . ', ';
                }
                $availabilityInfo = substr($availabilityInfo, 0, -2);
                if (substr($availabilityInfo, -2) == ', ') {
                    $availabilityInfo = substr($availabilityInfo, 0, -2);
                }
            }
            else
                $availabilityInfo = 'Книги нет';
        }

        $availabilityInfo = [
            'availabilityInfoText' => $availabilityInfo,
            'callNumber' => $callNumber
        ];

        return $availabilityInfo;
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

    function dsort($dates){
        usort($dates,function($a,$b){if(date(strtotime($a))>date(strtotime($b))) return 1; else return 0;});
        return $dates;
    }

    function sendEmailForBooking($email, $surname, $title, $author, $publisher, $year, $callNumber) {
        $titleQuoted = '«' . $title . '»';
        global $devMode;
        if ($devMode)
            $to = 'ilya@sidorchik.ru';
        else
            $to = 'abonement@nekrasovka.ru';
        $title = "Бронирование книги $titleQuoted";
        $headers  = 'MIME-Version: 1.0' . "\r\n";
        $headers .= "Content-type: text/html; charset=utf-8 \r\n";
        $headers .= "From: $surname <'$email'>\r\n";

        $titleTypografed = typograf($titleQuoted);

        $mess = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 3.2 Final//EN">
			    <html>
				    <head>
						<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	                    <title>'. $title .'</title>
					</head>
					<body>
					    <p>Здравствуйте!</p>
							
						<p>В вашей библиотеке есть книга ' . $titleTypografed . ': автор ' . $author . ', издательство «' . $publisher . '», год выпуска ' . $year . ', шифр ' . $callNumber . '.</p>
						<p>Я проверил: книга выдаётся на дом, сейчас не на руках. Пожалуйста, отложите её для меня. Моя фамилия — ' . $surname . '.</p>
					</body>
				</html>';

        mail($to, $title, $mess, $headers);
    }

    function sendEmailForRequesting($title, $author, $surname, $email) {
        $titleQuoted = '«' . $title . '»';
        global $devMode;
        if ($devMode)
            $to = 'ilya@sidorchik.ru';
        else
            $to = 'off@nekrasovka.ru';
        $title = "Просьба заказать книгу $titleQuoted";
        $headers  = 'MIME-Version: 1.0' . "\r\n";
        $headers .= "Content-type: text/html; charset=utf-8 \r\n";
        $headers .= "From: $surname <'$email'>\r\n";

        $titleTypografed = typograf($titleQuoted);

        $mess = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 3.2 Final//EN">
			    <html>
				    <head>
						<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	                    <title>'. $title .'</title>
					</head>
					<body><p>Здравствуйте!</p>
							<p> Я хотел бы прочитать книгу ' . $titleTypografed . ' автора ' . $author . ', но этой книги нет ни в одной московской библиотеке для выдачи на дом. Пожалуйста, приобретите её.</p>
					</body>
				</html>';

        mail($to, $title, $mess, $headers);
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