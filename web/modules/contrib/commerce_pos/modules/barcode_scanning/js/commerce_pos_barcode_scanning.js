(function ($, Drupal, drupalSettings, Quagga) {
    'use strict';

    var delay = 0;
    var throbber = 0;

    var open = false;
    if(drupalSettings.commerce_pos_barcode_scanning.statusOnLoad === "open") {
        open = true;
    }

    Drupal.behaviors.commercePosBarcodeScanning = {
        attach: function (context, settings) {
            $("#scanner-toggle").once("commerce_pos_barcode_scanning").click(function () {
                var scannerContainer = $("#scanner-container");

                if (scannerContainer.is(":visible") === false) {
                    Quagga.start();
                    scannerContainer.show(500);
                    open = true;
                }
                else {
                    scannerContainer.hide(500);
                    Quagga.pause();
                    open = false;
                }
            });

            $("#scanner-close").once("commerce_pos_barcode_scanning_close").click(function () {
                $("#scanner-container").hide(500);
                Quagga.pause();
                open = false;
            });

            if($("#scanner-container").length == 0) {
                $('.layout-region-pos-content').prepend('<div id="scanner-container"><div id="interactive" class="viewport"></div></div>');

                if(open) {
                    $("#scanner-container").show();
                }

                Quagga.init({
                    inputStream: {
                        name: "Live",
                        type: "LiveStream"
                    },
                    decoder: {
                        readers: ["code_128_reader", "upc_reader"]
                    }
                }, function (err) {
                    if (err) {
                        console.log(err);
                        return;
                    }
                    if (open) {
                        Quagga.start();
                    }
                });
            }
        }
    };

    Quagga.onProcessed(function (result) {
        if (delay < Date.now() - drupalSettings.commerce_pos_barcode_scanning.delay) {
            var drawingCtx = Quagga.canvas.ctx.overlay,
                drawingCanvas = Quagga.canvas.dom.overlay;

            var height = parseInt(drawingCanvas.getAttribute("height"));
            var width = parseInt(drawingCanvas.getAttribute("width"));

            drawingCtx.clearRect(0, 0, width, height);

            drawingCtx.fillStyle = "rgba(0, 0, 0, 0.5)";
            drawingCtx.fillRect(0, height - 45, width, height);

            if (result && result.codeResult && result.codeResult.code) {
                Quagga.ImageDebug.drawPath(result.box, {x: 0, y: 1}, drawingCtx, {color: "#00F", lineWidth: 2});
                Quagga.ImageDebug.drawPath(result.line, {x: 'x', y: 'y'}, drawingCtx, {color: 'red', lineWidth: 3});

                return;
            }

            // Scanning for barcode output.
            var dots = "";
            if (throbber < 30) {
                dots = ".";
            }
            else if (throbber < 60) {
                dots = "..";
            }
            else {
                dots = "...";

            }
            throbber++;

            if (throbber > 90) {
                throbber = 0;
            }

            var text = "Scanning for barcode" + dots;

            drawingCtx.font = "20px sans-serif";
            drawingCtx.fillStyle = "white";
            drawingCtx.fillText(text, 10, height - 15);
        }
    });

    Quagga.onDetected(function (result) {
        if (delay < Date.now() - drupalSettings.commerce_pos_barcode_scanning.delay) {
            delay = Date.now();

            var drawingCtx = Quagga.canvas.ctx.overlay,
                drawingCanvas = Quagga.canvas.dom.overlay;

            var height = parseInt(drawingCanvas.getAttribute("height"));
            var width = parseInt(drawingCanvas.getAttribute("width"));

            drawingCtx.clearRect(0, height - 45, width, height);

            drawingCtx.fillStyle = "rgba(0, 0, 0, 0.5)";
            drawingCtx.fillRect(0, height - 45, width, height);

            drawingCtx.fillStyle = "white";
            drawingCtx.fillText(result.codeResult.code, 10, height - 15);

            var input = $("[name='order_items[target_id][product_selector]']");

            input.val(result.codeResult.code);
            input.trigger("autocompleteclose");

            if(drupalSettings.commerce_pos_barcode_scanning.closeAfterScanning === "closed") {
                $("#scanner-container").hide(500);
                Quagga.pause();
                open = false;
            }
        }
    });


})(jQuery, Drupal, drupalSettings, Quagga);
