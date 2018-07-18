<?php
    require 'php/functions.php';
    require 'vendor/autoload.php';
    use GuzzleHttp\Client;

    if (isset($_POST['toRequest'])) {
        // Получение данных о книге
        $title = $_POST['title'];
        $author = $_POST['author'];

        // Подключение к базе данных
        include 'php/db_connection.php';
        $link = mysqli_connect($host, $user, $password, $database) or die("Ошибка");
        mysqli_set_charset($link, 'utf8');

        // Инициализия класса для работы с удалённым веб-ресурсом
        $client = new Client();

        // Проверка: знаем ли мы этого пользователя
        // Если в учётной записи есть почта — бронирование книги в один клик
        $encryption = $_COOKIE["encryption"];
        $result = mysqli_query($link, "SELECT email FROM readers WHERE encryption = '$encryption'");
        $row = mysqli_fetch_assoc($result);

        if ($row['email'])  {
            // Пользователь узнан, почта есть — отправка письма и добавление книги в его список заказанных

            $encryption = $_COOKIE["encryption"];
            $result = mysqli_query($link, "SELECT readerID, email, surname FROM readers WHERE encryption = '$encryption'");
            $row = mysqli_fetch_assoc($result);
            $readerID = $row['readerID'];
            $email = $row['email'];
            $surname = $row['surname'];

            sendEmailForRequesting($client, $email, $surname, $title, $author);

            addToRequested($link, $readerID, $title, $author);
        }
        else {
            // Почты нет — отправка письма, (запоминание,) добавление книги в его список заказанных
            // Получение данных из формы
            $email = $_POST['email'];
            $surname = $_POST['surname'];
            sendEmailForRequesting($client, $email, $surname, $title, $author);

            if (!isset($_COOKIE["encryption"])) {
                // Пользователь не узнан
                // Генерация шифра, добавление нового читателя и запоминание
                $encryption = base_convert(sha1(uniqid(mt_rand(), true)), 16, 36);
                setcookie("encryption", $encryption, time() + 3600 * 24 * 365 * 10);
                mysqli_query($link, "INSERT INTO `readers` (`readerID`, `encryption`, `email`, `surname`) VALUES (NULL, '$encryption', '$email', '$surname')");
            }
            else {
                // Пользователь узнан
                $encryption = $_COOKIE["encryption"];
                mysqli_query($link, "UPDATE readers SET `email` = '$email', `surname` = '$surname' WHERE encryption = '$encryption'");
            }

            $readerID = getReaderID($link, $encryption);
            addToRequested($link, $readerID, $title, $author);
        }
    }
?>
<html lang="ru">
    <head>
        <meta charset="utf-8">
        <title>Поиск книг в библиотеках Москвы</title>
        <meta name="description" content="Узнайте, в каких библиотеках есть нужная вам книга">
        <meta name="keywords" content="как, где, найти, узнать, проверить, взять, на дом, есть, поиск, нужную, книга, книгу, книг, литература, электронный, единый, сводный, каталог, база, данных, в, библиотека, библиотеках, москва, московских">
        <!--<meta name="yandex-verification" content="835e608657377f1e" />-->
        <meta content="width=device-width, initial-scale=1" name="viewport">
        <link rel="shortcut icon" href="img/favicon.ico">
        <link rel="apple-touch-icon" sizes="180x180" href="img/apple-touch-icon-180x180.png">
        <link rel="apple-touch-icon-precomposed" href="img/apple-touch-icon-180x180-precomposed.png">
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css" integrity="sha384-WskhaSGFgHYWDcbwN70/dfYBj47jz9qbsMId/iRN3ewGhXQFZCSftd1LZCfmhktB" crossorigin="anonymous">
        <link href="css/styles.less" rel="stylesheet/less" type="text/css">
        <!--[if lt IE 9]>
        <script src="http://cdnjs.cloudflare.com/ajax/libs/html5shiv/r29/html5.min.js"></script>
        <script src="https://raw.githubusercontent.com/jonathantneal/flexibility/master/flexibility.js"></script>
        <![endif]-->
        <!-- Yandex.Metrika counter <script> (function (d, w, c) { (w[c] = w[c] || []).push(function() { try { w.yaCounter49510096 = new Ya.Metrika2({ id:49510096, clickmap:true, trackLinks:true, accurateTrackBounce:true, webvisor:true }); } catch(e) { } }); var n = d.getElementsByTagName("script")[0], s = d.createElement("script"), f = function () { n.parentNode.insertBefore(s, n); }; s.type = "text/javascript"; s.async = true; s.src = "https://mc.yandex.ru/metrika/tag.js"; if (w.opera == "[object Opera]") { d.addEventListener("DOMContentLoaded", f, false); } else { f(); } })(document, window, "yandex_metrika_callbacks2"); </script> <noscript><div><img src="https://mc.yandex.ru/watch/49510096" style="position:absolute; left:-9999px;" alt="" /></div></noscript> <!-- /Yandex.Metrika counter -->
    </head>
    <body>
        <main>
            <div class="container">
                <div class="row">
                    <div class="col-sm-12 col-md-12 col-lg-12 col-xl-8">
                        <?php
                            if (isset($_POST['toRequest'])) {
                                echo <<<HERE
                                    <div class="alert alert-success" role="alert">
                                        <h4 class="alert-heading">
                                            Книга запрошена
                                        </h4>
                                        <p>Отправлено письмо с просьбой приобрести книгу в библиотеку Некрасова в отдел формирования фонда.</p>
                                        <p>Если книгу одобрят, она появится не скоро: через полгода-год. С вами должны связаться по почте.</p>
                                    </div>
HERE;
                            }
                        ?>
                    </div>
                </div>
            </div>
        </main>
    </body>
</html>