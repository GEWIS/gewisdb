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
- [x] **Query console** — the editor with numbered lines, Clear and Execute; the stored queries and the entity list
      in the rail, the latter filterable and clicking one starting a `SELECT` over it; results as their own panel
      with a row count and the CSV download, and an empty state for before anything has run.
- [x] **Everything the designs do not cover** — the pages the redesigned screens link into (member updates and
      approvals, bulk renewal, member and address forms, meeting and decision forms, mailing lists, users, API
      tokens, settings, the error pages) now carry the same header band, breadcrumbs, panels and empty states. No
      page is left with a bare `<h1>` in its body.
- [ ] **Decisions, the builder** — *needs backend work.* The designs show one decision assembled from several
      subdecisions of mixed kinds, with a panel stating the effect on the register before it is recorded and
      submission blocked while any subdecision is incomplete. The entity model supports it — a decision is already a
      container of subdecisions, and the installation form already records several — but `decision_decision_form`
      builds one decision from one form type, so mixing kinds needs a form type and a recording path that do not
      exist. Not faked.
- [x] **Registration** — five steps (personal, study, address, mailing lists, review and pay) over one form and one
      POST, using GEWISWEB's `form-stepper` controller and its styles. The review reads the fields already in the
      document, so nothing is stored half-finished and each row links back to the step it came from. Checkout stays
      shut until both agreements are ticked. With scripting off the whole form is visible at once and submits as it
      did before; a rejected submission opens the first step carrying an error.
- [x] **Overview** — four figures, what needs someone, the jump-to grid, and a rail with the membership breakdown,
      the integrations and the build. The attention list is the notification bell's set, read from the same view
      model, so the dashboard and the bell cannot state different things. The designs' trend pills ("+2 this month",
      "stable") are not rendered: nothing records a figure over time to compare against. The activity feed and its
      "full audit log" are not rendered either, for the same reason — see the out-of-scope list below.

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
- **Registration is one form paged into steps, not a step per request.** Every step is rendered by the server in one
  document and posts once, at the end; the stepper hides the steps you are not on. A step per request would mean
  storing a half-finished registration between them, and there is nowhere to put one that is not a prospective member
  record — which is the thing the payment is supposed to create. Without JavaScript all five steps are simply visible
  and the form submits as it always did. Server-side validation stays authoritative either way; a rejected submission
  opens the first step that carries an error.
- **Our tokens, not the prototype's.** The designs use `#C8102E` and Hanken Grotesk; this application uses the GEWIS
  red `#D40000` and Raleway/Lato, both already shared with GEWISWEB. Screens will not pixel-match, deliberately.
- **`btn-gewis-primary`, not `btn-primary`**, following GEWISWEB: `-primary` stays Bootstrap's blue.

## Open, to raise rather than invent

- ~~The fee, €20 or €25~~ — settled: it is **€20**. The registration says so in its own copy, which is where a
  prospective member needs it. Nothing else renders a figure: what a prospective member actually paid comes back from
  Stripe with the checkout, and printing €20 next to it would be a second, unrelated claim.
- ~~The query console's "read replica" copy~~ — the panel is gone. It was wrong twice over: ReportDB is neither a
  read replica nor a scheduled copy, `DatabaseUpdateListener` and `DatabaseDeletionListener` write it as the ledger
  is written. (The half-hourly job is GEWISWEB's `ImportGewisdbCommand` pulling *from* ReportDB, one hop further
  out, and says nothing about how fresh ReportDB itself is.) Nothing on the page claims either way now.
- Out of scope without backend work: the global search (no endpoint spans members, organs and decisions), CSV export
  of a member selection or of query results, and the full audit log page.
