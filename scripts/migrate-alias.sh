#/bin/sh

# This script allows selecting an alias
# If you put this directly in the makefile, replace $ with $$
set -e

read -rp "Enter EM_ALIAS (orm_default or orm_report): " alias
([ "$alias" == "orm_default" ] || [ "$alias" == "orm_report" ]) || (echo "Not a valid alias, expected orm_default or orm_report, exiting..."; exit 1)

export alias=$alias
