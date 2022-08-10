/**
 * A validator for different types of fields used in the subscribe forms
 */
 class InputValidator {
    constructor(topEl) {
        this.topEl = topEl;
        this.addListeners("focusout", "validate-initials", InputValidator.initialValidator);
    }

    validateElement(event, validator) {
        event.target.disabled = true;
        let currentValue = event.target.value;
        event.target.value = validator(currentValue);
        event.target.disabled = false;
    }

    addListeners(eventName, className, validator, runOnce = false) {
        for (let elem of this.topEl.getElementsByClassName(className)) {
            elem.addEventListener(eventName, (event) => {this.validateElement(event, validator)}, {once : runOnce});
        }
    }

    // This validator updates initials. We only allow 1 and 2-letter initials (such as Th.)
    // If it is longer, we assume it is a name
    static initialValidator(inputValue) {
        inputValue = inputValue.replace(/\s/g, '.') + '.';
        inputValue = inputValue.replace(/([A-Za-z])[A-Za-z]{2,}/g, '$1');
        inputValue = inputValue.replace(/\.{2,}/g, '.');
        inputValue = InputValidator.titleCase(inputValue);
        if (inputValue == '.') inputValue = '';
        inputValue = inputValue.replace(/[^A-Za-z\.]/g, '');
        return inputValue;
    }

    static titleCase(str) {
        return str.replace(/\w*/g, function(txt){
            return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();
        });
    }
}
  