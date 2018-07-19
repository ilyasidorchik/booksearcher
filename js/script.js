$(function() {
    $('#form').on('submit', function(e) {
        e.preventDefault();
        $('#submit').attr('disabled', 'disabled');
        var fd = new FormData(this);
        $.ajax({
            url: 'php/request.php',
            type: 'POST',
            contentType: false,
            processData: false,
            data: fd,
            success: function() {
                $('.form').css('display', 'none');
                $('.formProof').css('display', 'block');
            },
        });
    });
});


// Для формы запроса книги
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