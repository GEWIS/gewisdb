import { LookupController } from './lookup.ts';

interface MemberMatch {
    lidnr: number;
    fullName: string;
}

/**
 * Picks a member for one of the decision forms. The field the form reads back is the hidden membership number next to
 * the search box; where a page has no such field (the install editor's "add member" box) it listens for the event
 * instead.
 */
export default class extends LookupController<MemberMatch> {
    static targets = ['lidnr'];

    declare readonly hasLidnrTarget: boolean;
    declare readonly lidnrTarget: HTMLInputElement;

    protected label(match: MemberMatch): string {
        return `${match.fullName} (${match.lidnr})`;
    }

    protected chosenLabel(match: MemberMatch): string {
        return match.fullName;
    }

    protected choose(match: MemberMatch): void {
        if (this.hasLidnrTarget) {
            this.lidnrTarget.value = String(match.lidnr);
        }

        this.dispatch('selected', { detail: match });
    }
}
