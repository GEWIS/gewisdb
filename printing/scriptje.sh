#!/bin/bash

# todo wkhtmltopdf

while read url
do
    hash=$(echo "$url" | sha1sum | awk '{print $1}')

    curl "https://gewis.nl/~kokx/testtest.pdf" > $hash.pdf
    # wkhtmltopdf <options> "$url" "$hash.pdf"
    echo "$hash.pdf"
done
