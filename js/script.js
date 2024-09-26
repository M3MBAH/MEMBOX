document.getElementById('messageForm').addEventListener('submit', function(e) {
    e.preventDefault();

    var messageInput = document.getElementById('messageInput');
    var message = messageInput.value;
    var baseUrl = "<?php echo $base_url; ?>";

    if (message.trim() === '') {
        return;
    }

    var xhr = new XMLHttpRequest();
    xhr.open('POST', baseUrl + '/shoutbox.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
        if (xhr.status === 200) {
            document.getElementById('messages').innerHTML = xhr.responseText;
            messageInput.value = '';
        }
    };
    xhr.send('message=' + encodeURIComponent(message));
});

function loadMessages() {
    var xhr = new XMLHttpRequest();
    var baseUrl = "<?php echo $base_url; ?>";
    xhr.open('GET', baseUrl + '/shoutbox.php', true);
    xhr.onload = function() {
        if (xhr.status === 200) {
            document.getElementById('messages').innerHTML = xhr.responseText;
        }
    };
    xhr.send();
}

setInterval(loadMessages, 5000);
