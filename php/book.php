<?php
    require 'functions.php';

    if ($_POST['title'] && $_POST['author']) {
        // Получение данных о книге
        $title = $_POST['title'];
        $author = $_POST['author'];
        $publisher = $_POST['publisher'];
        $year = $_POST['year'];
        $pages = $_POST['pages'];
        $callNumber = $_POST['callNumber'];
        $library = $_POST['library'];

        // Подключение к базе данных
        $ini = parse_ini_file('../app.ini', true);
        $link = mysqli_connect($ini[database][host], $ini[database][user], $ini[database][password], $ini[database][name]) or die('Ошибка');
        mysqli_set_charset($link, 'utf8');

        // Проверка: знаем ли мы этого пользователя
        // Если в учётной записи есть почта — бронирование книги в один клик
        $encryption = $_COOKIE["encryption"];
        $result = mysqli_query($link, "SELECT email FROM readers WHERE encryption = '$encryption'");
        $row = mysqli_fetch_assoc($result);

        if ($row['email']) {
            // Пользователь узнан, почта есть
            $encryption = $_COOKIE["encryption"];
            $result = mysqli_query($link, "SELECT readerID, email, surname FROM readers WHERE encryption = '$encryption'");
            $row = mysqli_fetch_assoc($result);
            $readerID = $row['readerID'];
            $email = $row['email'];
            $surname = $row['surname'];
        }
        else {
            // Почты нет

            /* Остановка работы, если пользователь не заполнил поля почты и фамилии */
            if (!$_POST['email'] && !$_POST['surname'])
                exit;

            $email = $_POST['email'];
            $surname = $_POST['surname'];

            if ($_COOKIE["encryption"]) {
                /* Пользователь узнан */
                /* Добавление почты и фамилии читателю */
                $encryption = $_COOKIE["encryption"];
                mysqli_query($link, "UPDATE readers SET `email` = '$email', `surname` = '$surname' WHERE encryption = '$encryption'");
            }
            else {
                /* Пользователь не узнан */
                /* Генерация шифра, добавление нового читателя и запоминание */
                $encryption = base_convert(sha1(uniqid(mt_rand(), true)), 16, 36);
                setcookie("encryption", $encryption, time() + 3600 * 24 * 365 * 10, '/');
                mysqli_query($link, "INSERT INTO `readers` (`readerID`, `encryption`, `email`, `surname`) VALUES (NULL, '$encryption', '$email', '$surname')");
            }

            $readerID = getReaderID($link, $encryption);
        }

        sendEmailForBooking($email, $surname, $title, $author, $publisher, $year, $callNumber, $library);
        addToBooked($link, $readerID, $title, $author, $publisher, $year, $pages, $library);
    }