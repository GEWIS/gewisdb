import { Controller } from '@hotwired/stimulus';

/**
 * Opening the page that records a new decision. Nothing is submitted: the point and the decision number are part of
 * the decision's address, so they go into the URL. The meeting already knows which number is next for each of its
 * points, which saves looking it up in the list above.
 */
export default class extends Controller<HTMLFormElement> {
    static targets = ['point', 'number'];
    static values = {
        url: String,
        next: Object,
    };

    declare readonly pointTarget: HTMLInputElement;
    declare readonly numberTarget: HTMLInputElement;

    declare readonly urlValue: string;
    declare readonly nextValue: Record<string, number>;

    suggest(): void {
        const next = this.nextValue[this.pointTarget.value];

        this.numberTarget.value = undefined === next ? '' : String(next);
    }

    open(event: Event): void {
        event.preventDefault();

        if (!this.element.reportValidity()) {
            return;
        }

        // The point and the decision number are the last two segments of the route, which is why they can be
        // substituted positionally rather than by matching on a value that also occurs elsewhere in the path.
        const url = new URL(this.urlValue, window.location.origin);
        const segments = url.pathname.split('/');

        segments[segments.length - 2] = encodeURIComponent(this.pointTarget.value);
        segments[segments.length - 1] = encodeURIComponent(this.numberTarget.value);
        url.pathname = segments.join('/');

        window.location.assign(url.toString());
    }
}
