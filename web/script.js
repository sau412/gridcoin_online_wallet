// Show only one block from the page
function show_block(block_name) {
        $("#main_block").load("./?ajax=1&block=" + encodeURI(block_name));
        return true;
}

// Download transactions in CSV
function download_transactions_csv(user_uid, token) {
        $.get("./?ajax=1&block=transactions_csv")
                .done(function(data) {
                        console.log(data);
                });
}