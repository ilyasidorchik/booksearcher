let footer = document.getElementsByTagName('footer')[0];
let searchInput = document.getElementById('searchInput');
document.addEventListener('DOMContentLoaded', start); // когда HTML будет подготовлен и загружен, вызвать функцию start

function start() {
    let bookTitle;

    if (searchInput.value == '') {
        let url = window.location.pathname;
        if (url.indexOf('/found/') != -1) {
            url = url.replace('/found/', '');
            if (url != '') {
                bookTitle = decodeURI(url);
                searchInput.value = bookTitle;

                searchBook(bookTitle);

                console.log('Я тут');
            }
            else {
                window.location.replace('/');
            }
        }
    }

    document.getElementById('searchBtn').addEventListener('click', searchBook);
    searchInput.addEventListener('keypress',()=>{if(event.key==='Enter'){event.preventDefault();searchBook()}}); // поиск по Энтеру
}

function searchBook(bookTitle) {
    if ((bookTitle == '[object MouseEvent]') || (bookTitle == undefined)) {
        bookTitle = searchInput.value;
    }
    if (bookTitle != '') {
        footer.classList.remove('index');
        searchInput.removeAttribute('autofocus');
        let xhr = new XMLHttpRequest();
        let params = 'bookTitle='+bookTitle,
            template = '<div class="row"><div class="col-sm-12 col-md-12 col-lg-10 offset-lg-1 col-xl-8 offset-xl-2"><div class="book"><div class="bookDesc"><h2>&nbsp;</h2><div class="details lead"> <span class="author">&nbsp;</span> <span class="publisher">&nbsp;</span> <span class="pages">&nbsp;</span></div></div></div></div></div><div class="row"><div class="col-sm-12 col-md-12 col-lg-10 offset-lg-1 col-xl-8 offset-xl-2"><div class="library"><div class="libraryDesc" style="width:20%"><div style="padding:0 40%" class="name">&nbsp;</div><div class="details"><div style="padding:0 50%" class="address">&nbsp;</div></div></div></div></div></div>';
        xhr.abort(); // отменяем предыдущий запрос
        document.getElementById('results').innerHTML=''; // очищаем контейнер для результатов
        for (let i=0;i<3;i++){ // цикл вставки шаблона в контейнер для результатов на время загрузки
            let elem = document.createElement('div');
            elem.classList.add('bookContainer','template');
            elem.innerHTML=template;
            document.getElementById('results').append(elem);
        }
        history.pushState(null, null, '/found/' + bookTitle); // добавление запроса в URL
        document.title = '«' + bookTitle + '» в библиотеках Москвы';
        xhr.open('POST', '../php/search.php');
        xhr.onreadystatechange=()=>{
            if(xhr.readyState === 4) {
                if(xhr.status === 200) {
                    document.getElementById('results').innerHTML = xhr.responseText;

                    // Обработка кнопок для запроса и бронирования книги
                    let requestButton = document.getElementById('toRequest');
                    if (requestButton != null)
                        requestButton.addEventListener('click', toRequest);

                    let bookingButtons = document.querySelectorAll('input[value="Забронировать"]');
                    if (bookingButtons.length > 0) {
                        for (var i = 0; i < bookingButtons.length; i++) {
                            bookingButtons[i].addEventListener('click', {handleEvent: toBook, number: i});
                            let surname = document.getElementsByName('surname')[i];
                            surname.addEventListener('blur', {handleEvent: printSurnameInFormProof, number: i, surname: surname});
                        }
                    }

                    // Добавление автора и почты в подтверждение запроса книги
                    var inputAuthor = document.getElementById('author');
                    if (inputAuthor != null) {
                        inputAuthor.onblur = printAuthor;
                        function printAuthor() {
                            var textAuthor = document.getElementById('authorAdd');
                            textAuthor.innerHTML = this.value;
                        }
                    }
                    var inputEmail = document.getElementById('email');
                    if (inputEmail != null) {
                        inputEmail.onblur = printEmail;
                        function printEmail() {
                            var textEmail = document.getElementById('emailAdd');
                            textEmail.innerHTML = this.value;
                        }
                    }
                }
                else console.log('Ошибка: ' + xhr.status);
            }
        };
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.send(params);
    }
    else {
        searchInput.focus();
    }
}

function toRequest() {
    let email = document.getElementById('email').value;
    let surname = document.getElementById('surname').value;
    let title = document.getElementById('title').value;
    let author = document.getElementById('author').value;
    let params = 'email=' + email + '&surname=' + surname + '&title=' + title + '&author=' + author;

    let xhr = new XMLHttpRequest();
    xhr.open('POST', '../php/request.php'); // определяем тип запроса и ссылку на обработчик запроса
    xhr.timeout = 5000; // таймаут запроса в мс
    xhr.ontimeout=()=>{alert('Превышено время ожидания ответа от сервера!')};
    xhr.onreadystatechange=()=>{ // когда меняется статус запроса, вызываем функцию
        if (xhr.readyState === 4){ // если статус 4 (завершено)
            if (xhr.status === 200) { // если код ответа сервера 200, получить ответ
                document.querySelector('.form').style.display = 'none';
                document.querySelector('.formProof').style.display = 'block';
            }
            else alert('Ошибка: ' + xhr.status);
        }
    };
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded'); // устанавливаем HTTP-заголовок
    xhr.send(params); // отправляем запрос
}

function toBook(e) {
    let email = document.getElementsByName('email')[this.number];
    let surname = document.getElementsByName('surname')[this.number];

    if ((email.value == '') || (email.value.match(/.+@.+\..+/i) == null)) {
        email.focus();
        email.classList.add("invalid");
    }
    else if (surname.value == '') {
        email.classList.remove("invalid");
        surname.focus();
        surname.classList.add("invalid");
    }
    else {
        surname.classList.remove("invalid");

        email = email.value;
        surname = surname.value;
        let title = document.getElementsByName('titleBooking')[this.number].value;
        let author = document.getElementsByName('author')[this.number].value;
        let publisher = document.getElementsByName('publisher')[this.number].value;
        let year = document.getElementsByName('year')[this.number].value;
        let pages = document.getElementsByName('pages')[this.number].value;
        let callNumber = document.getElementsByName('callNumber')[this.number].value;
        let params = 'email=' + email + '&surname=' + surname + '&title=' + title + '&author=' + author + '&publisher=' + publisher + '&year=' + year + '&pages=' + pages + '&callNumber=' + callNumber;

        let xhr = new XMLHttpRequest();
        xhr.open('POST', '../php/book.php'); // определяем тип запроса и ссылку на обработчик запроса
        xhr.timeout = 5000; // таймаут запроса в мс
        xhr.ontimeout=()=>{alert('Превышено время ожидания ответа от сервера!')};
        xhr.onreadystatechange=()=>{ // когда меняется статус запроса, вызываем функцию
            if (xhr.readyState === 4){ // если статус 4 (завершено)
                if (xhr.status === 200) { // если код ответа сервера 200, получить ответ
                    document.querySelectorAll('.formBooking')[this.number].style.display = 'none';
                    document.querySelectorAll('.formProof')[this.number].style.display = 'block';
                }
                else alert('Ошибка: ' + xhr.status);
            }
        };
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded'); // устанавливаем HTTP-заголовок
        xhr.send(params); // отправляем запрос
    }
}

function printSurnameInFormProof(e) {
    let textSurname = document.querySelectorAll('.surnameAdd')[this.number];
    textSurname.innerHTML = this.surname.value;
}