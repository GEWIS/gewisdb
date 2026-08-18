import { Controller } from '@hotwired/stimulus';

/**
 * Copies a decision to the clipboard in the form the decision list expects, so it can be pasted straight into the
 * LaTeX document. The button says so briefly afterwards, because the clipboard gives no other sign that it worked.
 */
export default class extends Controller<HTMLElement> {
    static values = {
        text: String,
    };

    declare readonly textValue: string;

    async copy(event: Event): Promise<void> {
        const button = event.currentTarget as HTMLButtonElement;

        try {
            await navigator.clipboard.writeText(this.textValue.trim());
        } catch {
            return;
        }

        const copied = button.dataset.copyCopiedLabel;

        if (undefined === copied) {
            return;
        }

        const original = button.textContent;
        button.textContent = copied;
        window.setTimeout(() => {
            button.textContent = original;
        }, 2000);
    }
}
