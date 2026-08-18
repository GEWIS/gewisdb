import { startStimulusApp } from '@symfony/stimulus-bundle';

const app = startStimulusApp();

// Controllers live in domain subdirectories under controllers/, but are registered here with flat identifiers so a
// template writes `data-controller="member-search"`; the path-based autoload would namespace them instead
// (`member--member-search`).
