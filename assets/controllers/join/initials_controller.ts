import { Controller } from '@hotwired/stimulus';

/**
 * Normalises the initials a prospective member types into dot-separated capitals. Anything of three letters or more
 * is taken to be a name rather than an initial and is reduced to its first letter, so "Thomas Jan" becomes "T.J.".
 */
export default class extends Controller<HTMLInputElement> {
    normalise(): void {
        this.element.value = normalise(this.element.value);
    }
}

function normalise(value: string): string {
    let initials = `${value.replace(/\s/g, '.')}.`;

    initials = initials.replace(/([A-Za-z])[A-Za-z]{2,}/g, '$1');
    initials = initials.replace(/\.{2,}/g, '.');
    initials = titleCase(initials);

    if ('.' === initials) {
        return '';
    }

    return initials.replace(/[^A-Za-z.]/g, '');
}

function titleCase(value: string): string {
    return value.replace(/\w*/g, (word) => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase());
}
