# GEWISDB redesign — work order

Designs live in the Claude Design project `41894272-5b82-4680-adfe-810f19778d5b`, not in this repository. Re-read a
screen from there before implementing it; they may change. `CLAUDE-CODE-PROMPT.md` in that project is the brief.

The designs are intent, not code: read them for layout, hierarchy, states, wording and flow, then build with this
application's own view layer and GEWISWEB's conventions. No inline styles, no second styling system.

## Order

- [x] **Shell** — `layout-sidebar.html.twig`, sidebar macros and rows, page-header partial, sidebar SCSS and
      variables, top bar trimmed to what applies everywhere.
- [ ] **Members** — list with search, status filters and pagination; detail with the record table, organs, addresses,
      mailing lists and notes. Extracts the shared overview pattern (filter chips, table shell, empty state) and
      proves the pagination base from GH-575 with its first real consumer.
- [ ] **Prospective members** — list with state filters and stat cards; detail with the approval panel, membership
      type as radio cards with a recommendation, and the checkout panel.
- [ ] **Meetings** — list; meeting view as decision cards with their subdecision lines; the add-decision affordance.
- [ ] **Organs and decision export** — organ list with search; export as a meeting picker with a running total.
- [ ] **Decisions** — register with kind tabs and annulled styling; the create flow with the subdecision builder:
      per-kind fields, discharge selecting an existing installation, an effects panel stating what changes, and
      submission blocked while any subdecision is incomplete.
- [ ] **Registration** — five server-rendered steps (Personal, Study, Address, Mailing lists, Review & pay) with
      GEWISWEB's `form-stepper` ported as progressive enhancement. Per-step server-side validation; student number
      exactly 7 digits; Pattern notice only for a data-science programme; announcements list locked rather than
      unchecked; review lists every value with per-section edit; checkout blocked until both agreements are accepted.
      One submission at the end, 14-day expiry unchanged.
- [ ] **Overview** — dashboard last, because its numbers come from queries the screens above build.

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
