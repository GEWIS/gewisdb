import { LookupController } from './lookup.ts';

interface DecisionMatch {
    meeting_type: string;
    meeting_number: number;
    decision_point: number;
    decision_number: number;
    content: string;
}

/**
 * Picks the decision an annulment takes back. The decision being recorded travels with the query so the endpoint can
 * leave out the decisions this one cannot annul, and the chosen decision is shown in full before it is annulled.
 */
export default class extends LookupController<DecisionMatch> {
    static targets = ['meetingType', 'meetingNumber', 'point', 'number', 'preview', 'previewNumber',
        'previewContent', 'submit'];
    static values = {
        context: Object,
    };

    declare readonly meetingTypeTarget: HTMLInputElement;
    declare readonly meetingNumberTarget: HTMLInputElement;
    declare readonly pointTarget: HTMLInputElement;
    declare readonly numberTarget: HTMLInputElement;
    declare readonly previewTarget: HTMLElement;
    declare readonly previewNumberTarget: HTMLElement;
    declare readonly previewContentTarget: HTMLElement;
    declare readonly submitTarget: HTMLButtonElement;

    declare readonly contextValue: Record<string, string>;

    protected label(match: DecisionMatch): string {
        const content = 100 < match.content.length ? `${match.content.substring(0, 100)}...` : match.content;

        return `${this.number(match)} ${content}`;
    }

    protected chosenLabel(match: DecisionMatch): string {
        return this.number(match);
    }

    protected parameters(): Record<string, string> {
        return this.contextValue;
    }

    protected choose(match: DecisionMatch): void {
        this.meetingTypeTarget.value = match.meeting_type;
        this.meetingNumberTarget.value = String(match.meeting_number);
        this.pointTarget.value = String(match.decision_point);
        this.numberTarget.value = String(match.decision_number);

        this.previewNumberTarget.textContent = this.number(match);
        this.previewContentTarget.textContent = match.content;
        this.previewTarget.classList.remove('d-none');

        this.submitTarget.disabled = false;
    }

    private number(match: DecisionMatch): string {
        return `${match.meeting_type} ${match.meeting_number}.${match.decision_point}.${match.decision_number}`;
    }
}
