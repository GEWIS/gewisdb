import { Controller } from '@hotwired/stimulus';

/**
 * A select whose option values are URLs, navigating on change. Used by the page-size selector so it works without
 * wrapping the surrounding table in a form.
 */
export default class extends Controller<HTMLSelectElement> {
    navigate(): void {
        const url = this.element.value;

        if ('' !== url) {
            window.location.assign(url);
        }
    }
}
