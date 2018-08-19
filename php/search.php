<?php
    require '../vendor/autoload.php';
    require 'functions.php';
    use GuzzleHttp\Client;

    if (isset($_POST['bookTitle'])) {
        sleep(10);

        $bookTitle = $_POST['bookTitle'];

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