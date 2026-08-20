import { LookupController } from './lookup.ts';

interface MeetingMatch {
    meeting_type: string;
    meeting_number: number;
}

/**
 * Picks the meeting a set of minutes belongs to, which is never the meeting the decision itself is taken in.
 */
export default class extends LookupController<MeetingMatch> {
    static targets = ['meetingType', 'meetingNumber'];

    declare readonly meetingTypeTarget: HTMLInputElement;
    declare readonly meetingNumberTarget: HTMLInputElement;

    protected label(match: MeetingMatch): string {
        return `${match.meeting_type} ${match.meeting_number}`;
    }

    protected choose(match: MeetingMatch): void {
        this.meetingTypeTarget.value = match.meeting_type;
        this.meetingNumberTarget.value = String(match.meeting_number);
    }
}
