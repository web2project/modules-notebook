
function testURL( x ) {
    var test = document.editFrm.note_doc_url.value;
    if (test.length > 6) {
        newwin = window.open( 'http://' + test, 'newwin', '' );
    }
}