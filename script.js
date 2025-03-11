function uploadText() {
    let text = document.getElementById('textContent').value;
    let formData = new FormData();
    formData.append('text_content', text);

    fetch('upload.php', { method: 'POST', body: formData })
    .then(response => response.json())
    .then(data => {
        document.getElementById('response').innerHTML = `Shared: <a href="${data.url}" target="_blank">${data.url}</a>`;
    });
}

function uploadFile() {
    let file = document.getElementById('fileInput').files[0];
    let formData = new FormData();
    formData.append('file', file);

    fetch('upload.php', { method: 'POST', body: formData })
    .then(response => response.json())
    .then(data => {
        document.getElementById('response').innerHTML = `Shared: <a href="${data.url}" target="_blank">${data.url}</a>`;
    });
}
