import { Controller } from '@hotwired/stimulus';

/**
 * Copies a decision to the clipboard in the form the decision list expects, so it can be pasted straight into the
 * LaTeX document. The button says so briefly afterwards, because the clipboard gives no other sign that it worked.
 *
 * Only the label is swapped, never the button itself: assigning to the button's `textContent` would replace its
 * children with a single text node and take the icon with it, for good.
 */
export default class extends Controller<HTMLElement> {
    static targets = ['label'];
    static values = {
        text: String,
    };

    declare readonly hasLabelTarget: boolean;
    declare readonly labelTarget: HTMLElement;

    declare readonly textValue: string;

    private originalLabel = '';
    private restore: number | null = null;

    connect(): void {
        if (!this.hasLabelTarget) {
            return;
        }

        // Read once, so that clicking again while the button still says "Copied" cannot make that the label it
        // returns to.
        this.originalLabel = this.labelTarget.textContent ?? '';
    }

    disconnect(): void {
        if (null === this.restore) {
            return;
        }

        clearTimeout(this.restore);
    }

    async copy(event: Event): Promise<void> {
        const button = event.currentTarget as HTMLButtonElement;

        try {
            await navigator.clipboard.writeText(this.textValue.trim());
        } catch {
            return;
        }

        const copied = button.dataset.copyCopiedLabel;

        if (undefined === copied || !this.hasLabelTarget) {
            return;
        }

        if (null !== this.restore) {
            clearTimeout(this.restore);
        }

        this.labelTarget.textContent = copied;
        this.restore = window.setTimeout(() => {
            this.labelTarget.textContent = this.originalLabel;
            this.restore = null;
        }, 2000);
    }
}
