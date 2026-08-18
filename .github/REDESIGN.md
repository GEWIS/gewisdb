# GEWISDB redesign — work order

Designs live in the Claude Design project `41894272-5b82-4680-adfe-810f19778d5b`, not in this repository. Re-read a
screen from there before implementing it; they may change. `CLAUDE-CODE-PROMPT.md` in that project is the brief.

The designs are intent, not code: read them for layout, hierarchy, states, wording and flow, then build with this
application's own view layer and GEWISWEB's conventions. No inline styles, no second styling system.

## Order

- [x] **Shell** — `layout-sidebar.html.twig`, sidebar macros and rows, page-header partial, sidebar SCSS and
      variables, top bar trimmed to what applies everywhere.
- [x] **Members, list** — search, status filters and pagination. Extracts the shared overview pattern (pills with
      counts, empty state, status badge) and proves the pagination base from GH-575 with its first real consumer.
- [x] **Members, detail** — the record table, the supremum control, organs, addresses, mailing lists and notes,
      arranged as a main column and a rail. Restored the organ view page, which was never ported and which the
      member's organ list links to.
- [x] **Prospective members** — list with state filters and counts; detail with the approval panel, the
      recommendation, the checkout history and the applicant's own data.
- [x] **Meetings** — list; meeting view as decision cards with their subdecision lines; the add-decision affordance.
- [x] **Organs and decision export** — organ list with search; export as a meeting picker with a running total.
- [x] **Decisions, create** — the kind picker is one flat list with a description per kind, replacing two levels of
      nested tabs. Each kind keeps its own form and its own post.
- [ ] **Decisions, the builder** — *needs backend work.* The designs show one decision assembled from several
      subdecisions of mixed kinds, with a panel stating the effect on the register before it is recorded and
      submission blocked while any subdecision is incomplete. The entity model supports it — a decision is already a
      container of subdecisions, and the installation form already records several — but `decision_decision_form`
      builds one decision from one form type, so mixing kinds needs a form type and a recording path that do not
      exist. Not faked.
- [ ] **Registration** — five server-rendered steps (Personal, Study, Address, Mailing lists, Review & pay) with
      GEWISWEB's `form-stepper` ported as progressive enhancement. Per-step server-side validation; student number
      exactly 7 digits; Pattern notice only for a data-science programme; announcements list locked rather than
      unchecked; review lists every value with per-section edit; checkout blocked until both agreements are accepted.
      One submission at the end, 14-day expiry unchanged.
- [ ] **Overview** — dashboard last, because its numbers come from queries the screens above build.

## Domains

The bounded contexts are the five the application always had: **Application**, **Checker**, **Database**,
**Report**, **User**.

There is no `Api` domain. The API is a delivery mechanism, not a context, and its parts belong to what they serve:
`ApiService` and `ApiController` read the projection, so they are Report's, next to the query console;
`ApiPrincipal` is an authentication principal, so it is User's; and `FrontPageService` was never API at all — it is
the dashboard, and sits under Application. Only `Security/` keeps an `Api` directory, because that layer is
partitioned by authentication mechanism (token versus form login and LDAP) rather than by domain.

`Database` is the ledger and `Report` is the projection of it. Member, Decision, Meeting, Organ, MailingList,
ProspectiveMember and the rest are entities *in* one of those two, never domains of their own. The test is which
context owns the record, not whether it has a counterpart on the other side: a prospective member is a row in the
ledger that becomes a member, so it is `Database`, and the fact that `Report` has no copy of it says nothing.

Which host serves a controller is a routing concern and not a domain: the sign-up pages answer on the join host and
still live under `Database`.

The query console is Report's, since its DQL runs against the report entity manager. `SavedQuery` and its repository
stay under `Database`, because that directory is what maps them to the default manager and the table lives there.

## Decisions already taken

- **Subdecisions are never reorderable.** `sequence` is part of the primary key and ReportDB copies it downstream.
  The prototype's drag handle is not built, in the builder or anywhere else.
- **Registration degrades without JavaScript** by being server-rendered steps rather than by falling back to the old
  single-page form. Server-side validation stays authoritative.
- **Our tokens, not the prototype's.** The designs use `#C8102E` and Hanken Grotesk; this application uses the GEWIS
  red `#D40000` and Raleway/Lato, both already shared with GEWISWEB. Screens will not pixel-match, deliberately.
- **`btn-gewis-primary`, not `btn-primary`**, following GEWISWEB: `-primary` stays Bootstrap's blue.

## Open, to raise rather than invent

- The fee appears as €20 on the registration design and €25,00 on the prospective-member design, and the real amount
  lives in Stripe as a price id. Nothing renders a hardcoded figure until this is settled.
- The query console's "read replica" copy is wrong: queries run against the report entity manager, a projection
  refreshed by cron, so results can be half an hour stale. Reword rather than repeat.
- Out of scope without backend work: the global search (no endpoint spans members, organs and decisions), CSV export
  of a member selection or of query results, and the full audit log page.
