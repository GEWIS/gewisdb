import { startStimulusApp } from '@symfony/stimulus-bundle';

// Application-wide, domain-agnostic controllers.
import CopyController from './controllers/application/copy_controller.ts';
import FormCollectionController from './controllers/application/form_collection_controller.ts';
import FormStepperController from './controllers/application/form_stepper_controller.ts';
import RegistrationReviewController from './controllers/join/registration_review_controller.ts';
import NavigateSelectController from './controllers/application/navigate_select_controller.ts';

// Decision-specific controllers.
import DecisionLookupController from './controllers/decision/decision_lookup_controller.ts';
import DecisionNumberController from './controllers/decision/decision_number_controller.ts';
import FoundationFormController from './controllers/decision/foundation_form_controller.ts';
import InstallEditorController from './controllers/decision/install_editor_controller.ts';
import MeetingLookupController from './controllers/decision/meeting_lookup_controller.ts';
import MemberLookupController from './controllers/decision/member_lookup_controller.ts';
import OrganLookupController from './controllers/decision/organ_lookup_controller.ts';
import OrganMembersController from './controllers/decision/organ_members_controller.ts';
import SubDecisionChoiceController from './controllers/decision/subdecision_choice_controller.ts';

// Join-specific controllers.
import InitialsController from './controllers/join/initials_controller.ts';
import StudyNoticeController from './controllers/join/study_notice_controller.ts';

// Query-specific controllers.
import QueryEditorController from './controllers/query/query_editor_controller.ts';

const app = startStimulusApp();

// Controllers live in domain subdirectories under controllers/, but are registered here with flat identifiers so a
// template writes `data-controller="member-lookup"`; the path-based autoload would namespace them instead
// (`decision--member-lookup`).
app.register('copy', CopyController);
app.register('decision-lookup', DecisionLookupController);
app.register('decision-number', DecisionNumberController);
app.register('form-collection', FormCollectionController);
app.register('form-stepper', FormStepperController);
app.register('registration-review', RegistrationReviewController);
app.register('foundation-form', FoundationFormController);
app.register('initials', InitialsController);
app.register('install-editor', InstallEditorController);
app.register('meeting-lookup', MeetingLookupController);
app.register('member-lookup', MemberLookupController);
app.register('navigate-select', NavigateSelectController);
app.register('organ-lookup', OrganLookupController);
app.register('organ-members', OrganMembersController);
app.register('query-editor', QueryEditorController);
app.register('study-notice', StudyNoticeController);
app.register('subdecision-choice', SubDecisionChoiceController);
