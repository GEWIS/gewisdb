## [v4.2.0](https://github.com/GEIWS/gewisdb/tree/v4.2.0) (2025-06-29)

* Added foundation decision for GMM voting committees
* Added new Community Development Officer board function
* Fix body regulation using wrong field for abbreviation
* Fix member count on homepage
* Fix only pausing sync if a decision was saved

## [v4.1.1](https://github.com/GEIWS/gewisdb/tree/v4.1.1) (2025-06-04)

* Fix decision statutory content on export

## [v4.1.0](https://github.com/GEIWS/gewisdb/tree/v4.1.0) (2025-05-22)

* Added new public URL for graduate status renewal (and future features)
* Fix generating decisions for annulment
* Fix proxy IP ranges due to university WAF

## [v4.0.1](https://github.com/GEIWS/gewisdb/tree/v4.0.1) (2025-04-30)

* Fix legacy board installation function for Education position
* Fix showing created decision in application language

## [v4.0.0](https://github.com/GEIWS/gewisdb/tree/v4.0.0) (2025-04-23)

* Added migrations
* Removed (board) installation function configuration
* Fix house numer regex still being too restrictive

## [v3.2.0](https://github.com/GEWIS/gewisdb/tree/v3.2.0) (2024-10-18)

* Added validity checks for TU/e usernames
* Fix house number regex being too restrictive
* Updated dependencies

## [v3.1.0](https://github.com/GEWIS/gewisdb/tree/v3.1.0) (2024-09-01)

* Update Stripe API
* Fix organs not clickable due to incorrect templating
* Fix extra spaces being allowed in many data fields

## [v3.0.4](https://github.com/GEWIS/gewisdb/tree/v3.0.4) (2024-08-31)

* Fix keyholder decisions expiring one day too early

## [v3.0.3](https://github.com/GEWIS/gewisdb/tree/v3.0.3) (2024-08-24)

* Changed GEWISWEB account registration URL
* Fix inconsistency in API response

## [v3.0.2](https://github.com/GEWIS/gewisdb/tree/v3.0.2) (2024-08-19)

* Fix missing information in checkout status notice for deferred payments

## [v3.0.1](https://github.com/GEWIS/gewisdb/tree/v3.0.1) (2024-08-18)

* Improved API return states
* Fix hardcoding names in e-mails
* Fix URLs for GEWIS' regulations

## [v3.0.0](https://github.com/GEWIS/gewisdb/tree/v3.0.0) (2024-07-10)

* API changes to health endpoint
* Added "proper" alternative content for `Other` subdecision
* Added English translations
* Added `Reappointment` subdecision for installations in bodies
* Added `deleted` flag to members to ensure they are filtered from any checks
* Added `key_grant` and `key_withdraw` subdecisions
* Added birth date API functionality & email permissions
* Added button to allow decisions to be copied directly
* Added cronjobs to prepare for deployment
* Added functionality for searching for meetings.
* Added membership type to API and details for `/members` endpoint
* Added missing concurrency check to unit test action
* Added non-official translations for all decisions
* Added option for "Other" type of change introduced in the PR
* Added organ regulation decision
* Added reproducible builds by building through GitHub actions
* Added selectable postal regions to prevent people from using different formats for their address
* Added template for pull requests
* Added validation for birth date in enrolment form
* Allow KCC to have organ regulations
* Allow navigation back to meeting after deleting decision
* Also require sorting of .pot files
* Changed `AV` to `ALV`, including the taskforce and committee organs
* Changed `SubDecision` `number` property to `sequence`
* Changed audit committee abbreviation to KCC
* Changed how renewal works for existing external members
* Changed translation compilation to multi-stage building process
* Check against `expiration` instead of `membershipEndsOn`
* Clarification in payment email
* Clarify what will happen to existing organ membership during graduate renewal
* Count expired members/graduates separately
* Database query improvements
* Dependencies 2022-12
* Disable enrollment form
* Disable registration for GEWIS through join.gewis.nl in July
* Don't show historic organ installations for simple members
* Enforce version constraints for development environment
* Ensure correct timezone for all Docker containers
* Extra guarantees on member approval and database-constraint update
* Feature/minutes decision
* Filter member search in decisions
* Fix GH-291, member must be able to keep own email
* Fix GH-360, first week of new year
* Fix TU/e-username lookup route not working
* Fix XSS in decision content
* Fix `$member` being `null` or already a `Member`
* Fix `Checker` being unable to send e-mails
* Fix `REMOTE_ADDR` not being actual remote addr behind reverse proxy
* Fix `membershipEndsOn` not updating when making `Member` `external`
* Fix approving members with formerly failed checkout
* Fix being unable to delete `Organ` `SubDecision`s
* Fix bug that prevented approving memberships
* Fix closing h4 tag for prospective member
* Fix e-mails not being sent when headers contain unicode
* Fix handling of expired but recovered checkout sessions
* Fix member enrolment form and related problems
* Fix missing parentheses
* Fix not creating third or later `CheckoutSession` after recovery
* Fix query exports
* Fix redirect for Stripe
* Fix removal query to find fully expired Checkout Sessions
* Fix restarting Checkout Sessions and handling of events after paying
* Fix several UI issues
* Fix some issues with boards and their decisions
* Fix sorting of installations on install form
* Fix spelling of Romania
* Fix successful and failed payment ordering
* Fix typo PHP-FPM config
* Fix typos in board member release codes
* Generate a `Message-Id` for e-mails
* Grammar graduate renewal
* Handle manual approval of prospective members and potential refunds
* Hidden members
* Implement API for getting data from other systems
* Implement API principal management
* Implement LDAP authentication
* Implement active members & organ membership API endpoints
* Implement audit logs for renewal
* Implement dynamic configuration service
* Implement graduate member renewal
* Implement member notes
* Improve subscribe form and update translation file structure
* Improved e-mail address filtering
* Inactive member organ members and fixes to installation/discharge UX
* Integrate TU/e data into manual check & membership approval proccess
* Integrated Stripe payment flow for enrolment form
* Invalidate renewal link on membership update
* Language binary files no longer in repo
* Make `SubDecision` hash collision proof
* Make member name clickable in organ overview
* Make membership type changes retroactive again
* Mark API token as sensitive to prevent it from appearing in logs
* Member updates from external sources
* Members that became active today are also active
* More and better typing
* Move any `Member` association to `SubDecision` itself
* Move organ related forms to single tab and Added inverted subtabs
* Move to unified GEWISPHP Coding Standards
* PHP8 Constructor Promotion
* Reduce chance on invalid direct debits
* Registration confirmation GEWIS mailings template
* Remove audit entries when clearing member and improve flash messenger messages
* Remove deprecated properties for CLI commands
* Rename `Destroy` and `Reckoning` sub-decisions
* Send welcome email after membership approval
* Show commit in UI
* Signal through the API that modifications are being made
* Split membership checks
* Split unpaid and paid members on index page
* Switch to Alpine based images
* Switch to `actions/checkout@v3`
* Unable to delete `Foundation` installation
* Update Docker registry
* Update `doctrine/orm` to `v2.14.0` and remove patch for `Enum`s in association mapping
* Update dependencies
* Update dependencies and fix language switching enrolment form
* Update graduate renewal e-mail templates with new secretary
* Update member welcome email
* Update studies on registration page
* Update translations and fix `translate-helper`
* Updated dependencies
* Upgrade to PHP 8.2.0
* Upgrade to PHP 8.3.2
* Use meeting date for comparisons on key granting and withdrawal
* Various API fixes
* [API] Multiple extensions member typing fix GH-385
* [WIP] Upgrade to PHP 8.1 and Laminas Framework
* feat: do not show deleted members in API

## [v2.0.0](https://github.com/GEWIS/gewisdb/tree/v2.0.0) (2022-07-01)

* Dockerized gewisdb similar to the Dockerization approach of gewisweb
* Added prospective Github workflows for phpunit, phpcs, phpstan, psalm, and docs
