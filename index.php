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
                $doc_MGDB = new DOMDocument();
                $url_MGDB = "http://catalog.mgdb.ru:49001/cgi-bin/koha/opac-search.pl?limit=branch:CGDB-AB&idx=kw&q=$bookTitle";
                @$doc_MGDB->loadHTMLFile($url_MGDB);
                $xpath_MGDB = new DOMXpath($doc_MGDB);

                $findNoFound_MGDB = $xpath_MGDB->query("//strong[text() = 'No Results Found!']")->length;

                // Что-то найдено
                if (!$findNoFound_MGDB) {
                    // Ищем количество серпов
                    $pages_MGDB = $xpath_MGDB->query('//*[@id="userresults"]/div[2]/a[@class="nav"]')->length * 20;

                    // Если серпов 0 — значит одна страница
                    if (!$pages_MGDB)
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

                            $libraryInfo_MGDB = getLibraryInfo('ЦГДБ', $biblionumber_MGDB);
                            printLibrary($libraryInfo_MGDB);



                            // СКБМ
                            // Делаем запрос на страницу выдачи, получаем ответ
                            $response_SKBM = $client->request('POST', 'http://skbm.nekrasovka.ru/request', [
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
                            $html_SKBM = $response_SKBM->getBody();
                            $doc_SKBM = new DOMDocument();
                            @$doc_SKBM->loadHTML($html_SKBM);
                            $xpath_SKBM = new DOMXpath($doc_SKBM);
                            $booksCount_SKBM = $xpath_SKBM->query('//div[@id="searchrezult"]/div[@class="searchrez"]')->length;

                            if (!$booksCount_SKBM)
                                printBookContainerEnd();
                            else {
                                for ($bookI_SKBM = 2; $bookI_SKBM <= $booksCount_SKBM; $bookI_SKBM++) {
                                    // В массиве $sameISBNBookIArray_SKBM хранятся индексы книг, которые уже напечатаны и которые есть в библиотеке, единственной и не подходящей по условиям проекта
                                    if ($sameISBNBookIArray_SKBM) {
                                        $wasPrinted = false;
                                        foreach ($sameISBNBookIArray_SKBM as $bookIndex) {
                                            if ($bookI_SKBM == $bookIndex)
                                                $wasPrinted = true;
                                        }
                                        if ($wasPrinted)
                                            continue;
                                    }
                                    else {
                                        if (!isLibraryFit($xpath_SKBM, $bookI_SKBM))
                                            continue;
                                    }

                                    // Стягивание подробной информации о книге
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
                                    $htmlWithBookDetails_SKBM = $responseWithBookDetails_SKBM->getBody();

                                    $bookInfo_SKBM = getBookInfo('СКБМ', $htmlWithBookDetails_SKBM);

                                    /* Если книга без издателя — она не подходит по условиям проекта */
                                    if (!$bookInfo_SKBM[publisher])
                                        array_push($sameISBNBookIArray_SKBM, $bookI_SKBM);



                                    printLibs($client, $xpath_SKBM, $bookI_SKBM);

                                    // Вывод библиотек, в которых есть книга с $bookI_SKBM, и запись их индексов в массив, чтобы не выводить ещё раз
                                    $nextBookIAfterCurrent_SKBM = $bookI_SKBM + 1;
                                    if (!$sameISBNBookIArray_SKBM)
                                        $sameISBNBookIArray_SKBM = array();
                                    for ($nextBookI_SKBM = $nextBookIAfterCurrent_SKBM; $nextBookI_SKBM <= $booksCount_SKBM; $nextBookI_SKBM++) {
                                        if (!isLibraryFit($xpath_SKBM, $nextBookI_SKBM)) {
                                            array_push($sameISBNBookIArray_SKBM, $nextBookI_SKBM);
                                            continue;
                                        }

                                        // Стягивание подробной информации о книге
                                        $bookID_SKBM = $xpath_SKBM->query('//div[@id="searchrezult"]/div[@class="searchrez"][' . $nextBookI_SKBM . ']/@id')[0]->nodeValue;
                                        $bookID_SKBM = str_replace('\\\\\\\\', '\\', $bookID_SKBM);
                                        $responseWithBookDetails_SKBM = $client->request('POST', 'http://skbm.nekrasovka.ru/request', [
                                            'form_params' => [
                                                '_action' => 'execute',
                                                '_html' => 'stat',
                                                '_errorhtml' => 'error',
                                                'querylist' => '<_service>STORAGE:opacfindd:FindView[separator]<_version>2.3.0[separator]<session>26210[separator]<iddbIds[0]/id>' . $bookID_SKBM . '[separator]<iddbIds[0]/iddb>1[separator]<outform>FULLFORM[separator]<_history>yes[separator]<$iddb>1[separator]<userId>ADMIN[separator]<$basequant>2391872[separator]<$flag45>yes'
                                            ]
                                        ]);
                                        $htmlWithBookDetails_SKBM = $responseWithBookDetails_SKBM->getBody();

                                        // Проверка на совпадение
                                        // Сравнение ISBN
                                        $bookNextInfo_SKBM = getBookInfo('СКБМ', $htmlWithBookDetails_SKBM);
                                        if ($bookInfo_SKBM[ISBN] == $bookNextInfo_SKBM[ISBN]) {
                                            printLibs($client, $xpath_SKBM, $nextBookI_SKBM);
                                            array_push($sameISBNBookIArray_SKBM, $nextBookI_SKBM);
                                        }
                                        else {
                                            // Сравнение названия и издательства

                                            /* Если книга без издателя — она не подходит по условиям проекта */
                                            if (!$bookNextInfo_SKBM[publisher])
                                                continue;

                                            if ($bookInfo_SKBM[title] == $bookNextInfo_SKBM[title] && mb_strtolower($bookInfo_SKBM[publisher]) == mb_strtolower($bookNextInfo_SKBM[publisher])) {
                                                printLibs($client, $xpath_SKBM, $nextBookI_SKBM);
                                                array_push($sameISBNBookIArray_SKBM, $nextBookI_SKBM);
                                            }
                                        }
                                    }

                                    printBookContainerEnd();
                                }
                            }
                        }
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