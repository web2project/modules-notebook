
// Opens the note's URL in a new window. An address typed without a scheme
//   gets http://; only http(s) and ftp addresses are opened.
function testURL() {
    var url = document.editFrm.note_doc_url.value.trim();
    if (url.length < 4) {
        return;
    }
    if (!/^[a-z][a-z0-9+.-]*:/i.test(url)) {
        url = 'http://' + url;
    }
    if (/^(https?|ftp):/i.test(url)) {
        window.open(url, 'newwin', 'noopener');
    }
}
