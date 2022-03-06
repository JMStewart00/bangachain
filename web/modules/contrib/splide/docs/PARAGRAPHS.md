
***
##  <a name="paragraphs"></a>SPLIDE PARAGRAPHS

Provides integration between Splide and Paragraphs.
Splide Paragraphs allows richer slideshow/ carousel contents with a mix of text,
image and video, and more complex slide components like nested sliders, or any
relevant field type as slide components and probably with a few extra fields
before and or after the slideshow with the goodness of Paragraphs.
It is also possible to make individual bundle as a slide.

This provides a Splide Paragraphs formatter for the Paragraphs type.


### REQUIREMENTS
1. [Paragraphs](https://drupal.org/project/paragraphs)


### USAGE / CONFIGURATION
There are two formatters:

1. **Splide Paragraphs Vanilla**, to render each slide (paragraph bundle) as is,
   to have different composition of fields per slide. Use Field Group, Display
   Suite, Bootstrap Layouts, etc. to get different layouts at ease.
   This formatter is available at both top-level and child paragraphs types.
   Requires Blazy.
2. **Splide Paragraphs Media**, to have customized slide with advanced features.
   This formatter is only available at second level paragraphs type.


#### USAGE INSTRUCTIONS
The following instruction applies to [2], while [1] acts like any regular
formatter.

The final sample structure will be like:

  **Node > Paragraphs > Slideshow > Slide**

  * **Node** can be any public facing entity like User, ECK, etc.
  * **Paragraphs** is a field type paragraphs inside Node.
  * **Slideshow**, along with other paragraphs, containing a field type
    paragraph
  * **Slides** is the host paragraph bundle for child paragraph bundle **Slide**
    which contains non-paragraph fields.

  Unless you need more themeing control, **Default** view mode suffices for all.
  All the steps here can be reversed when you get the big picture.

  This should help clarify any step below:
  Adding a paragraphs type/bundle is like adding a content type.
  Adding a field type paragraph is like adding any other field.

Visit any of the given URLs, and or adjust accordingly.

* **/admin/structure/paragraphs_type/add**
  + Add a new Paragraphs bundle to hold slide components, e.g.: **Slide**.
  + Alternatively skip and re-use existing paragraph bundles, and make note of
    the paragraph machine name to replace **slide** for the following steps.

* **/admin/structure/paragraphs_type/slide/fields**
  + Add or re-use fields for the **Slide** components, e.g.:
    Image/Video/Media, Title, Caption, Link, Color, Layout list, etc.

  + You are going to have a multi-value field **Slides**, so it is reasonable
    to have single-value fields for any of the non-paragraph fields here,
    except probably field links.

  + Alternatively, just render a multi-value text, image or media entity here
    as a Splide carousel to make them as nested or independent splides later.

  + Manage individual field display later when done:
    + **/admin/structure/paragraphs_type/slide/display**
    + Be sure to make expected fields visible here.

* **/admin/structure/paragraphs_type/add**
  + Add a new Paragraphs bundle to host the created **Slide**, e.g.: Slideshow

* **/admin/structure/paragraphs_type/slideshow/fields/add-field**
  + Add a new field Paragraph type named **Slides** (Entity reference
    revisions), and select the previously created **Slide**, excluding other
    paragraph bundles to avoid complication. Choose Unlimited so to have
    multiple slides.

* **/admin/structure/paragraphs_type/slideshow/display**
  + Select **Splide Paragraphs** for the **Slides** field under **Format**, and
    click the **Configure** icon.
  + Adjust Splide formatter options accordingly, including your optionset.

* **/admin/structure/types**, or
  **/admin/config/people/accounts/fields**, or
  any fieldable entity.
  + Select **Manage fields** for the target bundle.
  + If you already have Paragraphs, simply edit and select **Slideshow** to
    include it along with other Paragraphs bundles.
  + If none, add or re-use **Paragraph** field under **Reference revisions**.
  + Be sure to at least choose **Slideshow** under **Paragraph types**,
    excluding **Slide** bundle which is already embedded inside **Slideshow**
    bundle.

* Add a content with a Slideshow paragraph, and see Splide Carousel there.

The more complex is your slide, the more options are available.


### KNOWN ISSUES
* The Splide Paragraphs formatters only work from within Field UI Manage display
  under Formatter, not Views UI. The issue is Views UI doesn't seem to respect
  SplideParagraphsFormatter::isApplicable(), or there may need additional
  method. Till proper fix, please ignore **Splide Paragraphs** formatter within
  Views UI.
