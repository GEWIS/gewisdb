import { Controller } from '@hotwired/stimulus';

interface ProspectiveMemberResult {
    lidnr: number;
    fullName: string;
    email: string | null;
    url: string;
}

/**
 * The prospective member overview, which shows the same term against three checkout states side by side. Each state
 * is its own `table` outlet, so one keystroke fans out into one request per state.
 *
 * A monotonic token per state guards against a slow response overwriting a newer one, and rows are assembled through
 * DOM APIs because names and e-mail addresses are entered by whoever signed up.
 */
export default class extends Controller {
    static targets = ['input', 'table'];
    static values = {
        url: String,
        maxResults: { type: Number, default: 128 },
        unknown: String,
    };

    declare readonly inputTarget: HTMLInputElement;
    declare readonly tableTargets: HTMLElement[];

    declare readonly urlValue: string;
    declare readonly maxResultsValue: number;
    declare readonly unknownValue: string;

    private tokens = new Map<string, number>();
    private debounce: number | null = null;

    connect(): void {
        void this.fetchResults();
    }

    disconnect(): void {
        if (null === this.debounce) {
            return;
        }

        clearTimeout(this.debounce);
    }

    search(): void {
        if (null !== this.debounce) {
            clearTimeout(this.debounce);
        }

        this.debounce = window.setTimeout(() => void this.fetchResults(), 200);
    }

    private async fetchResults(): Promise<void> {
        const query = this.inputTarget.value.trim();

        await Promise.all(this.tableTargets.map((table) => this.fetchState(table, query)));
    }

    private async fetchState(table: HTMLElement, query: string): Promise<void> {
        const type = table.dataset.state ?? '';
        const token = (this.tokens.get(type) ?? 0) + 1;
        this.tokens.set(type, token);

        const response = await fetch(
            `${this.urlValue}?q=${encodeURIComponent(query)}&type=${encodeURIComponent(type)}`,
            { headers: { Accept: 'application/json' } },
        );
        if (!response.ok || token !== this.tokens.get(type)) {
            return;
        }

        const results = (await response.json()) as ProspectiveMemberResult[];
        if (token !== this.tokens.get(type)) {
            return;
        }

        this.render(table, results);
    }

    private render(table: HTMLElement, results: ProspectiveMemberResult[]): void {
        const body = table.querySelector('tbody');
        const capped = table.querySelector('tfoot');

        body?.replaceChildren(...results.map((result) => this.row(result)));
        capped?.classList.toggle('d-none', results.length < this.maxResultsValue);
    }

    private row(result: ProspectiveMemberResult): HTMLTableRowElement {
        const row = document.createElement('tr');
        row.style.cursor = 'pointer';
        row.addEventListener('click', () => window.location.assign(result.url));

        row.append(
            this.linkCell(result.fullName, result.url),
            this.linkCell(result.email ?? this.unknownValue, result.url),
        );

        return row;
    }

    private linkCell(text: string, url: string): HTMLTableCellElement {
        const cell = document.createElement('td');
        const link = document.createElement('a');
        link.href = url;
        link.className = 'text-body text-decoration-none';
        link.textContent = text;
        cell.append(link);

        return cell;
    }
}
