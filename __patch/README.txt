ALL Patches are provided to enhance the cohort selection when keeping millesimed cohort over several years.
It will reorder the cohorts using the millesim prefix length defined in ent_installer global settings, then
uses idnumber as second sorting subkey.

It redraws:

- the cohort_search web service for enrol_cohort
- the enrol_cohort internal sorting
- the general cohort lib used in cohort listing index.

these patchs are NOT mandatory for the ent_installer, but if you use them, use also the customscript
bundle to get the sorting locallib.php at the right place.