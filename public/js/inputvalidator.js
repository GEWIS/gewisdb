/**
 * A validator for different types of fields used in the subscribe forms
 */
 class InputValidator {
    constructor(topEl) {
        this.topEl = topEl;
        this.addListeners("focusout", "validate-name", InputValidator.nameValidator);
        this.addListeners("focusout", "validate-initials", InputValidator.initialValidator);
        this.addListeners("focusout", "validate-middlename", InputValidator.nameValidator, true);
        this.addListeners("focusout", "validate-email", InputValidator.emailValidator);
        this.addListeners("focusout", "validate-iban", InputValidator.ibanValidator);
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

    static nameValidator(inputValue) {
        // Capitalize each lastname
        inputValue = InputValidator.titleCase(inputValue);
        // Dutch or German prefixes are not capitalized
        inputValue = inputValue.replace(/(\'T|Auf|Aus|Aan|De|Den|Der|Een|Het|In|Im|La|Le|L\'|Onder|Op|Ter|Ten|Uit|Unter|Van|Von|Voor)(\s|$)/g, function(txt){
            return txt.toLowerCase();
        });
        // Sometimes we need two capitals
        inputValue = inputValue.replace(/Ij/g,'IJ')
        // We don't want spaces around dashes for consistency
        inputValue = inputValue.replace(' -','-').replace('- ','-');
        return inputValue;
    }

    static emailValidator(inputValue) {
        return inputValue.toLowerCase();
    }

    static ibanValidator(inputValue) {
        return inputValue.replace(/\s/g, '').toUpperCase();
    }

    static titleCase(str) {
        return str.replace(/\w*/g, function(txt){
            return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();
        });
    }
}
  