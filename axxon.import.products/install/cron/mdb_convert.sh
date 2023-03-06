#!/bin/bash

while getopts f:o: option
do
    case "${option}"
    in
        f) FILE=${OPTARG};;
        o) OUTPUT=${OPTARG};;
    esac
done

[ ! -d "$OUTPUT" ] && mkdir $OUTPUT;

mdb-export -R '\n' $FILE ARTIKEL > $OUTPUT/artikel.csv
mdb-export -R '\n' $FILE aa_techcat_d > $OUTPUT/aa_techcat_d.csv
mdb-export -R '\n' $FILE aa_icon_d > $OUTPUT/aa_icon_d.csv
mdb-export -R '\n' $FILE aa_headline0_d > $OUTPUT/aa_headline0_d.csv
mdb-export -R '\n' $FILE aa_headline1_d > $OUTPUT/aa_headline1_d.csv
mdb-export -R '\n' $FILE aa_headline2_d > $OUTPUT/aa_headline2_d.csv
mdb-export -R '\n' $FILE aa_features_d > $OUTPUT/aa_features_d.csv
mdb-export -R '\n' $FILE aa_techdata_d > $OUTPUT/aa_techdata_d.csv
mdb-export -R '\n' $FILE aa_p2ac_d > $OUTPUT/aa_p2ac_d.csv
mdb-export -R '\n' $FILE aa_p2icon_d > $OUTPUT/aa_p2icon_d.csv
mdb-export -R '\n' $FILE aa_p2fe_d > $OUTPUT/aa_p2fe_d.csv
mdb-export -R '\n' $FILE aa_assets > $OUTPUT/aa_assets.csv
mdb-export -R '\n' $FILE aa_description_sh_d > $OUTPUT/aa_description_sh_d.csv
mdb-export -R '\n' $FILE aa_p2tctd_d > $OUTPUT/aa_p2tctd_d.csv