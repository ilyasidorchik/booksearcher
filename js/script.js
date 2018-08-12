document.addEventListener('DOMContentLoaded', start); // когда HTML будет подготовлен и загружен, вызвать функцию start

function start() {
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
}

function toRequest() {
    let email = document.getElementById('email').value;
    let surname = document.getElementById('surname').value;
    let title = document.getElementById('title').value;
    let author = document.getElementById('author').value;
    let params = 'email=' + email + '&surname=' + surname + '&title=' + title + '&author=' + author;

    let xhr = new XMLHttpRequest();
    xhr.open('POST', 'php/request.php'); // определяем тип запроса и ссылку на обработчик запроса
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
    let email = document.getElementsByName('email')[this.number].value;
    let surname = document.getElementsByName('surname')[this.number].value;
    let title = document.getElementsByName('titleBooking')[this.number].value;
    let author = document.getElementsByName('author')[this.number].value;
    let publisher = document.getElementsByName('publisher')[this.number].value;
    let year = document.getElementsByName('year')[this.number].value;
    let pages = document.getElementsByName('pages')[this.number].value;
    let callNumber = document.getElementsByName('callNumber')[this.number].value;
    let params = 'email=' + email + '&surname=' + surname + '&title=' + title + '&author=' + author + '&publisher=' + publisher + '&year=' + year + '&pages=' + pages + '&callNumber=' + callNumber;

    let xhr = new XMLHttpRequest();
    xhr.open('POST', 'php/book.php'); // определяем тип запроса и ссылку на обработчик запроса
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

function printSurnameInFormProof(e) {
    let textSurname = document.querySelectorAll('.surnameAdd')[this.number];
    textSurname.innerHTML = this.surname.value;
}


var inputAuthor = document.getElementById('author');
inputAuthor.onblur = printAuthor;
function printAuthor() {
    var textAuthor = document.getElementById('authorAdd');
    textAuthor.innerHTML = this.value;
}

var inputEmail = document.getElementById('email');
inputEmail.onblur = printEmail;
function printEmail() {
    var textEmail = document.getElementById('emailAdd');
    textEmail.innerHTML = this.value;
}