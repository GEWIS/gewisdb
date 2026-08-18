import { Controller } from '@hotwired/stimulus';

/**
 * The type-ahead behaviour every lookup on the decision forms shares: a text field that queries a JSON endpoint while
 * typing, and a dropdown of matches to pick from. Subclasses say how a match reads and what picking one does -- which
 * is always to fill in hidden identifiers, because the text field itself is never what the form reads back.
 *
 * A monotonic token guards against a slow response replacing a newer one, and options are built through DOM APIs
 * rather than markup because the labels contain names people typed.
 *
 * This class is not registered with Stimulus; only its subclasses are.
 */
export abstract class LookupController<T> extends Controller<HTMLElement> {
    static targets = ['input', 'results'];
    static values = {
        url: String,
        minLength: { type: Number, default: 2 },
    };

    declare readonly inputTarget: HTMLInputElement;
    declare readonly resultsTarget: HTMLElement;

    declare readonly urlValue: string;
    declare readonly minLengthValue: number;

    private token = 0;
    private timer: number | null = null;
    private matches: T[] = [];
    private highlighted = -1;

    connect(): void {
        document.addEventListener('click', this.onDocumentClick);
    }

    disconnect(): void {
        document.removeEventListener('click', this.onDocumentClick);
        this.stopTimer();
    }

    search(): void {
        this.stopTimer();
        this.timer = window.setTimeout(() => void this.fetchMatches(), 150);
    }

    navigate(event: KeyboardEvent): void {
        if (!this.isOpen()) {
            return;
        }

        switch (event.key) {
            case 'ArrowDown':
                event.preventDefault();
                this.highlight(this.highlighted + 1);
                break;
            case 'ArrowUp':
                event.preventDefault();
                this.highlight(this.highlighted - 1);
                break;
            case 'Enter':
                // Only swallow the key when it actually picks something; otherwise the form submits as usual.
                if (-1 !== this.highlighted) {
                    event.preventDefault();
                    this.pick(this.matches[this.highlighted]);
                }

                break;
            case 'Escape':
                this.close();
                break;
        }
    }

    close(): void {
        this.resultsTarget.replaceChildren();
        this.resultsTarget.classList.remove('show');
        this.matches = [];
        this.highlighted = -1;
    }

    /**
     * How a match reads in the dropdown.
     */
    protected abstract label(match: T): string;

    /**
     * What picking a match does. Implementations fill in the hidden fields the form reads back.
     */
    protected abstract choose(match: T): void;

    /**
     * What the text field is left showing once a match is picked; the label by default.
     */
    protected chosenLabel(match: T): string {
        return this.label(match);
    }

    /**
     * Extra query parameters the endpoint needs beyond the search term.
     */
    protected parameters(): Record<string, string> {
        return {};
    }

    private async fetchMatches(): Promise<void> {
        const term = this.inputTarget.value.trim();
        const token = ++this.token;

        if (term.length < this.minLengthValue) {
            this.close();

            return;
        }

        const query = new URLSearchParams({ q: term, ...this.parameters() });
        const response = await fetch(`${this.urlValue}?${query.toString()}`, {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok || token !== this.token) {
            return;
        }

        const matches = (await response.json()) as T[];

        if (token !== this.token) {
            return;
        }

        this.render(matches);
    }

    private render(matches: T[]): void {
        this.matches = matches;
        this.highlighted = -1;

        if (0 === matches.length) {
            this.close();

            return;
        }

        this.resultsTarget.replaceChildren(...matches.map((match, index) => {
            const option = document.createElement('button');
            option.type = 'button';
            option.className = 'dropdown-item text-wrap';
            option.setAttribute('role', 'option');
            option.textContent = this.label(match);
            // Picking on mousedown, before the input loses focus, so the click is not lost to the field blurring.
            option.addEventListener('mousedown', (event) => {
                event.preventDefault();
                this.pick(match);
            });
            option.addEventListener('mouseenter', () => this.highlight(index));

            return option;
        }));

        this.resultsTarget.classList.add('show');
    }

    private pick(match: T): void {
        this.inputTarget.value = this.chosenLabel(match);
        this.close();
        this.choose(match);
    }

    private highlight(index: number): void {
        const options = Array.from(this.resultsTarget.children);

        if (0 === options.length) {
            return;
        }

        this.highlighted = (index + options.length) % options.length;
        options.forEach((option, position) => option.classList.toggle('active', position === this.highlighted));
    }

    private isOpen(): boolean {
        return this.resultsTarget.classList.contains('show');
    }

    private stopTimer(): void {
        if (null === this.timer) {
            return;
        }

        clearTimeout(this.timer);
        this.timer = null;
    }

    private readonly onDocumentClick = (event: MouseEvent): void => {
        if (this.element.contains(event.target as Node)) {
            return;
        }

        this.close();
    };
}
