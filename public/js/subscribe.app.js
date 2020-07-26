var clearButton = document.getElementById("signature-canvas-clear");
var submitButton = document.getElementById("subscription-submit");
var inputSignature = document.getElementById("signature-canvas-data");
var ibanInput = document.getElementById("iban-input");
var ibanCheckbox = document.getElementById("iban-agreement");
var form = document.getElementById("form-subscribe");
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

  canvas.width = canvas.offsetWidth * ratio;
  canvas.height = canvas.offsetHeight * ratio;
  canvas.getContext("2d").scale(ratio, ratio);

  signaturePad.clear();
}

window.onresize = resizeCanvas;
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