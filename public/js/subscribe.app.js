let clearButton = document.getElementById("signature-canvas-clear");
let inputSignature = document.getElementById("signature-canvas-data");
let form = document.getElementById("form-subscribe");
let signatureFormGroup = document.getElementById("signature-form-group");
let canvas = document.getElementById("signature-canvas");
let signaturePad = new SignaturePad(canvas, {
    'throttle': 32,
    'penColor': '#D40000',
    'onEnd': function () {
        inputSignature.value = signaturePad.toDataURL();
    }
});

function resizeCanvas() {
    let ratio = Math.max(window.devicePixelRatio || 1, 1);

    canvas.width = Math.min(signatureFormGroup.offsetWidth * ratio, 300);
    canvas.height = Math.min(signatureFormGroup.offsetHeight * ratio, 150);

    inputSignature.value = "";
    signaturePad.clear();
}

window.onorientationchange = resizeCanvas;
resizeCanvas();

clearButton.addEventListener("click", function (event) {
    signaturePad.clear();
});
