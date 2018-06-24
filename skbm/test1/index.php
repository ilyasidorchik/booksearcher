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
    </head>
    <body>
        <?php
            require '../vendor/autoload.php';

            use GuzzleHttp\Client;

            $bookTitle = 'Код Дурова';

            // Инициализируем класс для работы с удалёнными веб-ресурсами
            $client = new Client();

            // Делаем запрос на страницу выдачи, получаем ответ
            $response = $client->request('POST', 'http://skbm.nekrasovka.ru/request', [
                'form_params' => [
                    '_service' =>'STORAGE:opacfindd:IndexView',
                    '_action' => 'php',
                    '_errorhtml' => 'error1',
                    '_handler' => 'search/search.php',
                    'querylist' => '<_service>STORAGE:opacfindd:FindView[separator]<_version>2.5.0[separator]<session>26026[separator]<_start>0[separator]<start>0[separator]<$length>15[separator]<length>15[separator]<iddb>1[separator]<_showstr><i>Заглавие</i> '.$bookTitle.'[separator]<_str>[bracket]TITL '.$bookTitle.'[/bracket][separator]<$outform>SHORTFM[separator]<outformList[0]/outform>SHORTFM[separator]<outformList[1]/outform>LINEORD[separator]<outformList[2]/outform>SHORTFMS[separator]<outformList[3]/outform>SHORTFMSTR[separator]<$filterstr>[bracket][bracket]LRES [apos]ТЕКСТЫ[apos][/bracket][/bracket] AND [bracket]LPUB [apos]КНИГИ[apos][/bracket][separator]<$filtersids>filter_1_2_0[END]filter_1_3_0[separator]<$fshowstr><i>вид документа</i> тексты И <i>вид издания</i> книги[separator]<query/body>(TITL '.$bookTitle.') AND ((LRES \'ТЕКСТЫ\')) AND (LPUB \'КНИГИ\')[separator]<_history>yes[separator]<userId>ADMIN[separator]<$linkstring>043[ID]Заказ документа[END]044[ID]Заказ копии документа[END][separator]<level[0]>Full[separator]<level[1]>Retro[separator]<level[2]>Unfinished[separator]<level[3]>Identify[separator]<$swfterm>[bracket]TITL '.$bookTitle.'[/bracket] AND [bracket][bracket]LRES [apos]ТЕКСТЫ[apos][/bracket][/bracket] AND [bracket]LPUB [apos]КНИГИ[apos][/bracket][separator]<_iddb>1[separator]<$addfilters>[NEXT]filter_1_1_else[IND]fixed_1_0_1525854941893[CLASS](LFR \'печатная/рукописная\')[TEXT]печатная/рукописная[separator]<$typework>search[separator]<$basequant>2391872[separator]<$flag45>yes',
                    '_numsean' => '26026'
                ]
            ]);

            // Находим нужное из хтмла выдачи
            $htmlSKBM = $response->getBody();
            $docSKBM = new DOMDocument();
            @$docSKBM->loadHTML($htmlSKBM);
            $xpathSKBM = new DOMXpath($docSKBM);
            //echo $xpathSKBM->query('//div[@id="searchrezult"]/div[@class="searchrez"]')->length;
            //echo $xpathSKBM->query('//div[@id="searchrezult"]/div[@class="searchrez"][4]//div[@class="output"]/div')->length;
            //echo $xpathSKBM->query('//div[@class="tabdivs"]/div[@class="adddiv"][2]//p/text()')[0]->nodeValue;
            $rowID = $xpathSKBM->query('//div[@id="searchrezult"]/div[@class="searchrez"][2]/@id')[0]->nodeValue;
            $rowID = str_replace('\\\\\\\\', '\\', $rowID);

            // Делаем запрос на страницу с подробной информацией о книге, получаем ответ
            $responseDetails = $client->request('POST', 'http://skbm.nekrasovka.ru/request', [
                'form_params' => [
                    '_action' => 'execute',
                    '_html' => 'stat',
                    '_errorhtml' => 'error',
                    'querylist' => '<_service>STORAGE:opacfindd:FindView[separator]<_version>2.3.0[separator]<session>26210[separator]<iddbIds[0]/id>'.$rowID.'[separator]<iddbIds[0]/iddb>1[separator]<outform>FULLFORM[separator]<_history>yes[separator]<$iddb>1[separator]<userId>ADMIN[separator]<$basequant>2391872[separator]<$flag45>yes'
                ]
            ]);

            // Выводим содержание ответа (HTTP-клиент Guzzle)
            $htmlDetails = $responseDetails->getBody();
            $htmlDetails = str_replace('\\"', '', $htmlDetails);
            echo $htmlDetails; exit;
            
            //$htmlDetails = 'var response=[];//ютф8 var _iddb="1"; var _basequant="2391872"; var _flag45="yes"; response[0]= { _size: "1", _result_0: { _isn: "1606361", _id: "SKBM-SZAO-RU/CBS_SZAO/IBIS/117439", _level: "Full", _iddb: "1", _sourceIddb: "1", _archive: "false", _controlType: "UNDEFINE", _status: "NEW", _FULLFORM_0: [ "<Автор:>[i class=RP]Кононов, Николай[/i]", "<Основное заглавие:> Код Дурова", "<Сведения, относящ. к заглавию:> реальная история \"ВКонтакте\" и ее создателя", "<Ответственность:> Н. Кононов", "<Место издания:> Москва", "<Издательство:> [i class=PU]Манн, Иванов и Фербер[/i]", "<Дата издания:> 2013", "<Объем:> 188, [19] с.", "<Аннотация:>Павел Дуров почти не общается с журналистами, но автору этой книги удалось проникнуть внутрь \"ВКонтакте\". Получилось не просто журналистское расследование, а авантюрная история, исследующая феномен сетевого предпринимателя-харизматика и его детища, а заодно и вполне материальное выражение популярности бизнеса соцсети.", " 978-5-91657-546-0", "" ] }, _iddb_0: { _number: "1", _title: "Сводный каталог" } }; var test=1;//ютф8';

            // Выводим Сведения, относящ. к заглавию: реальная история \"ВКонтакте\" и ее создателя
            preg_match('/к заглавию:> (.*)", "<Ответственность/', $htmlDetails, $matches);
            echo $matches[1];

            //$htmlDetails = 'var response=[];//ютф8 var _iddb="1"; var _basequant="2391872"; var _flag45="yes"; response[0]= { _size: "1", _result_0: { _isn: "1606361", _id: "SKBM-SZAO-RU/CBS_SZAO/IBIS/117439", _level: "Full", _iddb: "1", _sourceIddb: "1", _archive: "false", _controlType: "UNDEFINE", _status: "NEW", _FULLFORM_0: [ "<Автор:>[i class=RP]Кононов, Николай[/i]", "<Основное заглавие:> Код Дурова", "<Сведения, относящ. к заглавию:> реальная история \"ВКонтакте\" и ее создателя", "<Ответственность:> Н. Кононов", "<Место издания:> Москва", "<Издательство:> [i class=PU]Манн, Иванов и Фербер[/i]", "<Дата издания:> 2013", "<Объем:> 188, [19] с.", "<Аннотация:>Павел Дуров почти не общается с журналистами, но автору этой книги удалось проникнуть внутрь \"ВКонтакте\". Получилось не просто журналистское расследование, а авантюрная история, исследующая феномен сетевого предпринимателя-харизматика и его детища, а заодно и вполне материальное выражение популярности бизнеса соцсети.", " 978-5-91657-546-0", "" ] }, _iddb_0: { _number: "1", _title: "Сводный каталог" } }; var test=1;//ютф8';


            exit;

            // Выводим ответ
            //$htmlDetails = str_replace('\\"', '', $htmlDetails);

            //preg_match('/<Сведения, относящ. к заглавию:> (.*?)"/', $htmlDetails, $matches);

            //$htmlDetails = str_replace('\\', '', $htmlDetails);

            /**
             * проверяем, что функция mb_ucfirst не объявлена
             * и включено расширение mbstring (Multibyte String Functions)
             */
            if (!function_exists('mb_ucfirst') && extension_loaded('mbstring'))
            {
                /**
                 * mb_ucfirst - преобразует первый символ в верхний регистр
                 * @param string $str - строка
                 * @param string $encoding - кодировка, по-умолчанию UTF-8
                 * @return string
                 */
                function mb_ucfirst($str, $encoding='UTF-8')
                {
                    $str = mb_ereg_replace('^[\ ]+', '', $str);
                    $str = mb_strtoupper(mb_substr($str, 0, 1, $encoding), $encoding).
                        mb_substr($str, 1, mb_strlen($str), $encoding);
                    return $str;
                }
            }
            //preg_match('/<Основное заглавие:> (.*?)"/', $htmlDetails, $matches);
            //$title1 = $matches[1];
            //preg_match('/<Сведения, относящ. к заглавию:> (.*?)"/', $htmlDetails, $matches);
            /*preg_match('/к заглавию:> (.*)", "<Ответственность/', $htmlDetails, $matches);
            print_r($matches);*/

            //if ($matches[1]) {

            //    $title2 ='. ' . mb_ucfirst($title2);
            //}

            preg_match('/<Издательство:> (.*?)"/', $htmlDetails, $matches);
            $publisher  = str_replace(['[i class=PU]', '[/i]'], '', $matches[1]);

            preg_match('/<Дата издания:> (.*?)"/', $htmlDetails, $matches);
            $year = $matches[1];

            preg_match('/<Объем:> (.*?)"/', $htmlDetails, $matches);
            $mystring = $matches[1];
            $findme   = ',';
            $pos = strpos($mystring, $findme);
            if($pos) {
                for ($i = 0; $i < $pos; $i++) {
                    $pages .= $mystring[$i];
                }
            }
            else {
                $pages = preg_replace("/[^0-9]/", '', $mystring);
            }
            $pages .= ' стр.';

            preg_match('/<Ответственность:> (.*?)"/', $htmlDetails, $matches);
            if($matches[1]) {
                $author = $matches[1];
                if (preg_match('/,/', $author, $matches)) {
                    $author = str_replace(' ', '', $author);
                    $author = str_replace('.', '. ', $author);
                }
            }
            else {
                preg_match('/<Автор:> ?(.*?)"/', $htmlDetails, $matches);
                $author  = str_replace(['[i class=RP]', '[/i]'], '', $matches[1]);
            }

            echo $publisher;


            exit;
        ?>
        <main>
            <div class="container">
                <div class="row">
                    <div class="col-sm-12 col-md-12 col-lg-12 col-xl-12">
                        <div class="search-container">
                            <label for="search_inp"><h4>Поиск книг в библиотеках Москвы</h4></label>
                            <form action="" method="GET" class="form-inline search">
                                <input type="search" name="title" id="search_inp" class="form-control" placeholder="Название книги" value='<?php echo $title; ?>'>
                                <button class="btn btn-primary ml-2">Найти</button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php
                    if(empty($_GET['title']) || $_GET['title'] == '') {
                        exit;
                    }
                ?>
                <div class="row">
                    <div class="col-sm-12 col-md-12 col-lg-12 col-xl-12">
                        <div class="bookDesc">
                            <h2><?php echo $title; ?></h2>
                            <div class="details lead">
                                <span class="author"><?php echo $author; ?></span>
                                <span class="publisher"><?php echo $publisher . ', ' . $year; ?></span>
                                <span class="pages"><?php echo $pages; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-12 col-md-12 col-lg-12 col-xl-12">
                        <div class="library">
                            <div class="libraryDesc">
                                <div class="name"><b><?php echo $libraryMGDB['name']; ?></b></div>
                                <div class="details">
                                    <div class="address"><?php echo $libraryMGDB['address']; ?></div>
                                    <div class="timetable">
                                        <span class="timetable-item today">Сегодня до 22</span>
                                        <a href="<?php echo $libraryMGDB['timetable']; ?>" class="timetable-item link">Режим работы</a>
                                    </div>
                                </div>
                            </div>
                            <div class="libraryBooking">
                                <input type="submit" name="to-book" class="btn btn-outline-dark btn-sm" value="Забронировать">
                                <div class="status small">
                                    <?php
                                        echo $bookStatus . ' шт.';
                                        if ($bookStatusOnHands > 0) {
                                            echo '<br>На руках ' . $bookStatusOnHands . ' шт.' . ' до ' . $bookStatusOnHandsDate;
                                        }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </body>
</html>