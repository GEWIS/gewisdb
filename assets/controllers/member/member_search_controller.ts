import { Controller } from '@hotwired/stimulus';

interface MemberResult {
    lidnr: number;
    fullName: string;
    email: string | null;
    generation: number;
    expiration: string;
    deleted: boolean;
    url: string;
}

/**
 * The member overview: fetches matches while typing and rebuilds the result table. An empty term is a valid search
 * that yields the first page of members, which is why the controller also searches once on connect.
 *
 * A monotonic token guards against a slow response overwriting a newer one, and rows are assembled through DOM APIs
 * because names and e-mail addresses are member data.
 */
export default class extends Controller {
    static targets = ['input', 'results', 'capped'];
    static values = {
        url: String,
        maxResults: { type: Number, default: 32 },
        unknown: String,
    };

    declare readonly inputTarget: HTMLInputElement;
    declare readonly resultsTarget: HTMLElement;
    declare readonly cappedTarget: HTMLElement;

    declare readonly urlValue: string;
    declare readonly maxResultsValue: number;
    declare readonly unknownValue: string;

    private token = 0;
    private debounce: number | null = null;

    connect(): void {
        this.cappedTarget.classList.add('d-none');
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
        const token = ++this.token;

        const response = await fetch(`${this.urlValue}?q=${encodeURIComponent(query)}`, {
            headers: { Accept: 'application/json' },
        });
        if (!response.ok || token !== this.token) {
            return;
        }

        const results = (await response.json()) as MemberResult[];
        if (token !== this.token) {
            return;
        }

        this.render(results);
    }

    private render(results: MemberResult[]): void {
        this.resultsTarget.replaceChildren(...results.map((result) => this.row(result)));
        this.cappedTarget.classList.toggle('d-none', results.length < this.maxResultsValue);
    }

    private row(result: MemberResult): HTMLTableRowElement {
        const row = document.createElement('tr');
        row.style.cursor = 'pointer';
        row.addEventListener('click', () => window.location.assign(result.url));

        // Deleted members keep their row so decisions mentioning them stay traceable, but they are not live data.
        if (result.deleted) {
            row.classList.add('table-danger');
        }

        row.append(
            this.linkCell(String(result.lidnr), result.url),
            this.linkCell(result.fullName, result.url),
            this.linkCell(result.email ?? this.unknownValue, result.url),
            this.cell(String(result.generation)),
            this.cell(result.expiration),
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

    private cell(text: string): HTMLTableCellElement {
        const cell = document.createElement('td');
        cell.textContent = text;

        return cell;
    }
}
