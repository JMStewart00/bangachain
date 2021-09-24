VIEWS AGGREGATOR PLUS
=====================
Because the Views and ViewsCalc modules rely on the database to perform
aggregation, you only have limited options at your disposal. That is where this
module comes in. Unlike Views and ViewsCalc, this module:
o enumerates group members (see https://drupal.org/node/1300900)
o produces tallies (textual histograms, see http://drupal.org/node/1256716)
o can filter out rows on regular expressions (regexp)
o can aggregate across entire columns (e.g show column data range at the top)
o lets you add your own custom aggregation functions to the existing set
o aggregation functions can take parameters, as currently employed by "Filter
  rows", "Count" and "Label"
o on Views of type "Webform submissions" the module supports the field "Webform
  submission data: Value" (requires Webform 8.x-5.x)
o works well with Commerce 2.0 fields (requires Commerce 8.x-2.x)
o handles well Views fields of the type "Global: Custom text", where you can
  use Twig syntax to do math operations and format the results
o can use the available formulas without aggregation, meaning the results will
  be displayed as subtotal rows

Basics Recap: what is aggregation again?
----------------------------------------
In the context of Views and this module, aggregation is the process of grouping
and collapsing result rows on the identical values of ONE column, while at the
same time applying "summary" functions on other columns. For example you can
group the result set on a taxonomy term, so that all rows sharing the same
value of the taxonomy column are represented as single rows, with aggregation
functions, like TALLY, SUM, or ENUMERATE applied to the remaining columns.

Example
-------
Say the original View based on raw database results looks like below.

Industry|Company Name |     Turnover |
--------|-------------|--------------|
IT      |       AquiB |  $25,000,000 |
Clothing|    Cenneton |  $99,000,000 |
Food    |       Heiny |  $66,000,000 |
IT      |PreviousBest |  $ 5,000,000 |
Food    |   McRonalds | $500,000,000 |

Then with the grouping taking place on, say Industry, and aggregation functions
COUNT and SUM applied on Company Name and Turnover respectively, the final
result will display like below. A descending sort was applied to
Turnover and the display name of "Company Name" was changed to "Comp. Count".

Industry| Comp. Count |     Turnover |
--------|-------------|--------------|
Food    |           2 | $566,000,000 |
Clothing|           1 |  $99,000,000 |
IT      |           2 |  $30,000,000 |

That's the basics and you can do the above with Views. But with Views
Aggregator Plus (VAgg+) you can also aggregate like below, using its TALLY and
ENUMERATE group aggregation functions, as well as LABEL, COUNT and SUM for the
added bottom row.

Industry    |Companies           |     Turnover |
------------|--------------------|--------------|
Food (2)    |Heiny, McRonalds    | $566,000,000 |
Clothing (1)|Cenneton            |  $99,000,000 |
IT (2)      |AcquiB, PreviousBest|  $30,000,000 |
------------|--------------------|--------------|
Totals      |                  5 | $695,000,000 |
------------------------------------------------

You can also use all available formulas for group and comlumn aggregation
without aggregation! If in the setting "Aggregation results per group" you
will select the second option (no aggregation), then the results will be shown
after each group (like subtotals). You can choose the prefix and/or suffix (in
our case here " - Sum"), which will be used for the subtotals label and the 
classes for the grouped column and subtotals row.

Industry       |Company Name        |     Turnover |
---------------|--------------------|--------------|
IT             |              AquiB |  $25,000,000 |
IT             |       PreviousBest |  $ 5,000,000 |
---------------|--------------------|--------------|
IT - Sum       |                  2 |  $30,000,000 |
---------------|--------------------|--------------|
Clothing       |           Cenneton |  $99,000,000 |
---------------|--------------------|--------------|
Clothing - Sum |                  1 |  $99,000,000 |
---------------|--------------------|--------------|
Food           |              Heiny |  $66,000,000 |
Food           |          McRonalds | $500,000,000 |
---------------|--------------------|--------------|
Food - Sum     |                  2 | $566,000,000 |
---------------|--------------------|--------------|
Totals         |                  5 | $695,000,000 |
---------------|--------------------|--------------|

The possibility to use PHP code in connection with the ViewsPHP module has not
been ported to the D8 version (and will not be ported).

HOW TO USE
----------
On the main Views UI page, admin/structure/views/view/YOUR-VIEW/edit/page,
under Format, click and select "Table with aggregation options". Having arrived
at the Settings page, follow the hints under the header "Style Options".
All group aggregation functions, except "Filter rows" require exactly one field
to be assigned the "Group and compress" function.
Column aggregation functions may be used independently of group aggregation
functions. If a column aggregation function requires an argument, it may take
it from the corresponding group aggregation function, if also enabled.

There are no permissions or global module configurations.

Views Aggregator Plus does not combine well with Views' native aggregation.
So in the Advanced section (upper right) set "Use aggregation: No".

Keep in mind that the process of grouping and aggregation as performed by this
module is different from the Grouping option in Views. With Grouping in Views
the total number of rows remains the same, but the rows are grouped in separate
tables. With this module with the aggregation option selected, the number of 
rows is reduced as they are grouped and collapsed, but the end result is always
a single table. If you chose subtotals (the second option), then the number of
rows increases, but again the result is one table.

WORKING WITH TWIG SYNTAX
------------------------
You can now use the "Global: Custom text" Views field and use Twig syntax to do
data manipulations. In the Text area of a "Global: Custom text" you can write:
{{ ((field_1|striptags) * (field_2|striptags))|number_format(2) }}

You can use all fields, available under "Replacement patters". You can also use
all Twig filters, functions, operators, tags to create manipulate data to your
liking. If you use "number_format" as your last filter, then your aggregated
result will be formatted accordingly.

FUNCTIONS
-------------------
Math functions: COUNT, AVERAGE, MIN, MAX, SUM, MEDIAN
String functions:
o Enumerate, Enumerate (sort, no dupl.) - list of values
o Count unique - count the unique appearances of a value
o Tally members - list of values and their appearances in the format Value(x)
o Range - shows result in the form "first/min" [separator] "last/max" value
o Display first member - displays the first member of a group
o Label - the text to display under the column, which is grouped
o Filter - hides/omits the rows, specified by a regexp

You can write your own group and column functions. See the API documentation
of this module for further instructions.

FUNCTION PARAMETERS
-------------------
Functions marked with an asterisk take an optional parameter.

"Group and Compress" takes an optional keyword 'case-insensitive' (in English or
in the translated language on your site) to perform grouping in a
case-insensitive manner. The default is case-sensitive.

"Average" takes an optional precision: the number of decimals to round to after
calculating the average.

"Range", "Tally members" and the two "Enumerate" functions use their parameter
to specify the separator. The default is an HTML line-break, <br/>, for "Tally"
and "Enumerate" and ' - ' for "Range".

"Filter rows" and "Count" take a regular expression. This is explained below.

REGEXPS
-------
Some aggregation functions, like "Filter rows" and "Count" take a regular
expression as a parameter. In its simplest form a regular expression is a word
or part of a word you want to filter on. If you use regexps in this way, you may
omit the special delimiters around the parameter, most commonly a pair of
forward slashes. So "red" and "/red/" are equivalent.
Here are some more regexps:

/RED/i         targets rows that contain the word "red" in the field specified,
               case-insensitive
/red|blue/     rows with either the word "red" or "blue" in the field
/^(Red|Blue)/  rows where the specified field begins with "Red" or "Blue"
/Z[0-9]+/      the letter Z followed by one or more digits

Ref: http://work.lauralemay.com/samples/perl.html (for PERL, but quite good)

LIMITATIONS
-----------
o If an aggregation function result does not display correctly, try changing the
  field formatter. For example use "Plain text", rather than "Default".
o When you apply two aggregation functions on the same field, the 2nd function
  gets applied on the results of the first -- not always what you want.
o Aggregation works with rendered data, so any manipulations you are trying to
  do with the aggregated results (e.g. work with SUM values etc.) are not going
  to work

ACKNOWLEDGMENT
--------------
The UI of this module borrows heavily from Views Calc and the work by the
authors and contributors done on that module is gratefully acknowledged.

REFs
----
https://drupal.org/node/1219356#comment-4782582
https://drupal.org/node/1219356#comment-6909160
https://drupal.org/node/1300900
https://drupal.org/node/1791796
https://drupal.org/node/1140896#comment-7657061
