<?php
    if (isset($_GET['i'])) {
        require_once "imgdao.php";
        $img = new ImageObject();
        list($filepath, $err) = $img->queryDatabase($_GET['i']);
        if (is_null($err)) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            header('Content-Type: ' . $finfo->file($filepath));
            $handle = fopen($filepath, "rb");
            $contents = fread($handle, filesize($filepath));
            fclose($handle);
            echo $contents;
        } else {
            echo json_encode(array("error" => true, "msg" => $err, "i" => $_GET['i']));
        }
    } else {
?>
<!DOCTYPE html>
<html lang="en-us">
<head>
    <title>MintGreen</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" media="all" href="style.css">
</head>
<body>
    <form action="/upload.php" method="post" enctype="multipart/form-data">
        <h1><strong>MintGreen</strong> Image Storage</h1>
        <div class="form-group file-area">
            <label for="images">Images <span>Accepts JPEG, PNG, GIF, WebP image file with size < 4MB </span></label>
            <input type="file" name="images" id="images" required="required" />
            <div class="file-dummy">
                <div class="success">Image selected! Click button to upload!</div>
                <div class="default">Please select some image files</div>
            </div>
        </div>
        <div class="form-group">
            <button type="button">Upload images</button>
        </div>
        <progress class="form-group" style="display: none"></progress>
    </form>
    <div id="mint-modal" class="modal" style="display: none">
        <div class="modal-content">
            <span id="close" class="close">&times;</span>
            <p id="mint-modal-content-title"></p>
            <p id="mint-modal-content"></p>
        </div>
    </div>
    <link href='https://fonts.googleapis.com/css?family=Lato:100,200,300,400,500,600,700' rel='stylesheet' type='text/css'>
    <script type="application/javascript" src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script type="application/javascript">
        function show_modal(title, msg) {
            $('#mint-modal-content-title')[0].innerText = title;
            $('#mint-modal-content')[0].innerText = msg;
            $('#mint-modal').css('display', 'block');
        }
        $('#close').click(() => {
            $('#mint-modal').css('display', 'none');
        });
        window.onclick = (e) => {
            if (e.target === $('#mint-modal')[0]) {
                $('#mint-modal').css('display', 'none');
            }
        };
        $(':file').on('change', function () {
            var file = this.files[0];
            if (file && file.size > 4 * 1024 * 1024) {
                alert('max upload size is 4MB');
            }
        });
        $(':button').on('click', function () {
            var formData = new FormData();
            formData.append('images', $('#images')[0].files[0]);
            $.ajax({
                url: 'upload.php',
                type: 'POST',
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                xhr: function () {
                    var myXhr = $.ajaxSettings.xhr();
                    if (myXhr.upload) {
                        $('progress').css("display", "block");
                        myXhr.upload.addEventListener('progress', function (e) {
                            if (e.lengthComputable) {
                                $('progress').attr({
                                    value: e.loaded,
                                    max: e.total,
                                });
                            }
                        }, false);
                    }
                    return myXhr;
                },
                success: function (resp) {
                    try {
                        var r = JSON.parse(resp);
                        if (r['error'] === false) {
                            show_modal("Success", "https://img.rippyyyyy.com/?i="+r['file']);
                        } else {
                            show_modal("Error", "some error occurred:"+r['msg']);
                        }
                    } catch (e) {
                        show_modal("Error", "Cannot parse server response: "+resp);
                    }
                    $('progress').css("display", "none");
                },
                error: function () {
                    show_modal("Error", "some error occurred");
                }
            });
        });
    </script>
</body>
</html>
<?php
    }
?>
