import { Controller } from '@hotwired/stimulus';

/**
 * Points a decision at an existing sub-decision -- the board installation being relieved or discharged, the key code
 * being withdrawn -- by copying the reference the chosen radio carries into the hidden fields the form reads back.
 *
 * The radios themselves are not form fields: which one is checked says nothing the server needs beyond the reference,
 * and leaving them out of the form keeps the reference the single source of truth.
 */
export default class extends Controller<HTMLElement> {
    static targets = ['meetingType', 'meetingNumber', 'decisionPoint', 'decisionNumber', 'sequence', 'until',
        'lidnr', 'submit'];

    declare readonly meetingTypeTarget: HTMLInputElement;
    declare readonly meetingNumberTarget: HTMLInputElement;
    declare readonly decisionPointTarget: HTMLInputElement;
    declare readonly decisionNumberTarget: HTMLInputElement;
    declare readonly sequenceTarget: HTMLInputElement;
    // Only a key code withdrawal carries the granting it takes back, so the grantee and the expiry are optional.
    declare readonly hasUntilTarget: boolean;
    declare readonly untilTarget: HTMLInputElement;
    declare readonly hasLidnrTarget: boolean;
    declare readonly lidnrTarget: HTMLInputElement;
    declare readonly submitTarget: HTMLButtonElement;

    select(event: Event): void {
        const chosen = (event.target as HTMLElement).dataset;

        this.meetingTypeTarget.value = chosen.meetingType ?? '';
        this.meetingNumberTarget.value = chosen.meetingNumber ?? '';
        this.decisionPointTarget.value = chosen.decisionPoint ?? '';
        this.decisionNumberTarget.value = chosen.decisionNumber ?? '';
        this.sequenceTarget.value = chosen.sequence ?? '';

        if (this.hasUntilTarget) {
            this.untilTarget.value = chosen.until ?? '';
        }

        if (this.hasLidnrTarget) {
            this.lidnrTarget.value = chosen.lidnr ?? '';
        }

        this.submitTarget.disabled = false;
    }
}
