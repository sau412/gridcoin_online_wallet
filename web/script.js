// Show only one block from the page
function show_block(block_name) {
        $("#main_block").load("./?ajax=1&block=" + encodeURI(block_name));
        return true;
}

// Download transactions in CSV
function download_transactions_csv(user_uid, token) {
        $.get("./?ajax=1&block=transactions_csv")
                .done(function(data) {
                        // https://stackoverflow.com/questions/17564103/using-javascript-to-download-file-as-a-csv-file
                        let downloadLink = document.createElement("a");
                        let blob = new Blob(["\ufeff", data]);
                        let url = URL.createObjectURL(blob);
                        downloadLink.href = url;
                        downloadLink.download = "data.csv";

                        document.body.appendChild(downloadLink);
                        downloadLink.click();
                        document.body.removeChild(downloadLink);
                });
}