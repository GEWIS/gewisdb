var clearButton = document.getElementById("signature-canvas-clear");
var submitButton = document.getElementById("subscription-submit");
var inputSignature = document.getElementById("signature-canvas-data");
var ibanInput = document.getElementById("iban-input");
var ibanCheckbox = document.getElementById("iban-agreement");
var form = document.getElementById("form-subscribe");
var signatureFormGroup = document.getElementById("signature-form-group");
var canvas = document.getElementById("signature-canvas");
var signaturePad = new SignaturePad(canvas, {
    'throttle': 32,
    'penColor': '#D40000',
    'onEnd': function() {
        inputSignature.value = signaturePad.toDataURL();
    }
});

function resizeCanvas() {
    var ratio =  Math.max(window.devicePixelRatio || 1, 1);

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

form.addEventListener("submit", function (event) {
    if (!(ibanInput.value === null || ibanInput.value === "")) {
        if (!ibanCheckbox.checked || signaturePad.isEmpty()) {
            event.preventDefault();
            alert('You have filled in an IBAN, however, you have not yet signed and/or agreed to the use of a SEPA Direct Debit. Please do so before continuing.');
        }
    } else {
        inputSignature.value = "";
    }
});