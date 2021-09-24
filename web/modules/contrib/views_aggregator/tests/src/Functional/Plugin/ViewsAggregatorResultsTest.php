<?php

namespace Drupal\Tests\views_aggregator\Functional\Plugin;

use Drupal\Tests\views\Functional\ViewTestBase;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Entity\View;

/**
 * Tests the views aggregator results.
 *
 * @group views_agregator
 */
class ViewsAggregatorResultsTest extends ViewTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['va_test_style_table'];

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'views',
    'views_aggregator',
    'views_aggregator_test_config',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Set to TRUE to strict check all configuration saved.
   *
   * @var bool
   *
   * @see \Drupal\Core\Config\Development\ConfigSchemaChecker
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);
    $this->enableViewsTestModule();
    ViewTestData::createTestViews(get_class($this), ['views_aggregator_test_config']);
  }

  /**
   * Test the pager and how it works with totals.
   */
  public function testPagerSettings() {
    $this->drupalGet('va-test-style-table');

    $view = View::load('va_test_style_table');
    $display = &$view->getDisplay('default');

    // Enable the pager on page_1.
    $display['display_options']['pager']['type'] = 'full';
    $display['display_options']['pager']['options']['items_per_page'] = 3;
    $display['display_options']['pager']['options']['offset'] = 0;
    $display['display_options']['pager']['options']['id'] = 0;
    $display['display_options']['pager']['options']['total_pages'] = NULL;
    $view->save();

    // Check the total sum of 'age' is for the page shown.
    $this->drupalGet('va-test-style-table');
    $this->assertFieldByXPath('//thead/tr/td', '84');

    // Enable totals to be calculated on the entire result set.
    $display = &$view->getDisplay('default');
    $display['display_options']['style']['options']['column_aggregation']['totals_per_page'] = '0';
    $view->save();

    // Check the total sum of 'age' is for the entire result set.
    $this->drupalGet('va-test-style-table');
    $this->assertFieldByXPath('//thead/tr/td', '136');
  }

  /**
   * Test the positioning of the column aggregation results (totals).
   */
  public function testTotalsSettings() {
    $this->drupalGet('va-test-style-table');

    // The results should be in both table header and footer.
    $this->assertFieldByXPath('//thead/tr/td', 'TOTAL');
    $this->assertFieldByXPath('//tfoot/tr/td', 'TOTAL');

    // Set the totals row in the table header only.
    $view = View::load('va_test_style_table');
    $display = &$view->getDisplay('default');
    $display['display_options']['style']['options']['column_aggregation']['totals_row_position']['1'] = '1';
    $display['display_options']['style']['options']['column_aggregation']['totals_row_position']['2'] = '0';
    $view->save();

    // Ensure the 'TOTAL' label appears only in the table header.
    $this->drupalGet('va-test-style-table');
    $this->assertFieldByXPath('//thead/tr/td', 'TOTAL');
    $this->assertNoFieldByXPath('//tfoot/tr/td', 'TOTAL');

    // Set the totals row in the table header only.
    $display = &$view->getDisplay('default');
    $display['display_options']['style']['options']['column_aggregation']['totals_row_position']['1'] = '0';
    $display['display_options']['style']['options']['column_aggregation']['totals_row_position']['2'] = '2';
    $view->save();

    // Ensure the 'TOTAL' label appears only in the table footer.
    $this->drupalGet('va-test-style-table');
    $this->assertNoFieldByXPath('//thead/tr/td', 'TOTAL');
    $this->assertFieldByXPath('//tfoot/tr/td', 'TOTAL');
  }

  /**
   * Test the group functions.
   */
  public function testGroupResultFunctions() {
    $this->drupalGet('va-test-style-table');

    $view = View::load('va_test_style_table');
    $display = &$view->getDisplay('default');

    // Make sure 'Singer' appears multiple times on the page.
    $this->assertNoUniqueText('Singer');

    // Make sure 'age' is not grouped either.
    $this->assertNoFieldByXPath('//tbody/tr/td[@headers="view-age-table-column" and @class="views-field views-field-age"]', '52');

    // Remove the 'job_1' column.
    unset($display['display_options']['fields']['job_1']);
    unset($display['display_options']['style']['options']['columns']['job_1']);
    unset($display['display_options']['style']['options']['info']['job_1']);
    $view->save();

    // Set the 'Group and compress' and 'Tally members' functions.
    $display = &$view->getDisplay('default');
    $display['display_options']['style']['options']['caption'] = 'Test group + compress and Tally';
    $display['display_options']['style']['options']['info']['job']['aggr'] = [];
    $display['display_options']['style']['options']['info']['job']['aggr']['views_aggregator_group_and_compress'] = 'views_aggregator_group_and_compress';
    $display['display_options']['style']['options']['info']['job']['has_aggr'] = 1;
    $display['display_options']['style']['options']['info']['name']['aggr'] = [];
    $display['display_options']['style']['options']['info']['name']['aggr']['views_aggregator_tally'] = 'views_aggregator_tally';
    $display['display_options']['style']['options']['info']['name']['has_aggr'] = 1;
    $view->save();

    $this->drupalGet('va-test-style-table');

    // Check if 'Tally members' function worked.
    $this->assertRaw('George (1)<br />John (1)');
    // Check if 'Singer' is found only once.
    $this->assertUniqueText('Singer');
    // Check that 'age' is grouped, compressed and sum is applied.
    $this->assertFieldByXPath('//tbody/tr/td[@headers="view-age-table-column" and @class="views-field views-field-age"]', '52');

    // Set the 'Enumerate (sort., no dupl.)' function.
    $display = &$view->getDisplay('default');
    $display['display_options']['style']['options']['caption'] = 'Test group & compress + Enumerate no dupl.';
    $display['display_options']['style']['options']['info']['name']['aggr'] = [];
    $display['display_options']['style']['options']['info']['name']['aggr']['views_aggregator_enumerate'] = 'views_aggregator_enumerate';
    $display['display_options']['style']['options']['info']['name']['has_aggr'] = 1;
    $view->save();

    $this->drupalGet('va-test-style-table');
    // Check if 'Enumerate (sort., no dupl.)' function worked.
    $this->assertRaw('George<br />John');

    // Set the 'Count unique' function.
    $display = &$view->getDisplay('default');
    $display['display_options']['style']['options']['caption'] = 'Test group & compress + Count unique';
    $display['display_options']['style']['options']['info']['name']['aggr'] = [];
    $display['display_options']['style']['options']['info']['name']['aggr']['views_aggregator_count_unique'] = 'views_aggregator_count_unique';
    $display['display_options']['style']['options']['info']['name']['has_aggr'] = 1;
    $view->save();

    $this->drupalGet('va-test-style-table');
    // Check if 'Count unique' function worked.
    $this->assertFieldByXpath('//tbody/tr/td[@headers="view-name-table-column" and @class="views-field views-field-name"]', '2');
  }

  /**
   * Test the column functions.
   */
  public function testColumnResultFunctions() {
    $this->drupalGet('va-test-style-table');

    // We have 'Sum' selected as column aggregation on column 'age'.
    $this->assertFieldByXPath('//thead/tr/td', '136');

    // And 'Label' as column aggregation on column 'ID'.
    $this->assertFieldByXPath('//thead/tr/td', 'TOTAL');

    $view = View::load('va_test_style_table');

    // Function 'Average'.
    $display = &$view->getDisplay('default');
    $display['display_options']['style']['options']['caption'] = 'Test column - Average';
    $display['display_options']['style']['options']['info']['age']['aggr_column'] = 'views_aggregator_average';
    $view->save();

    $this->drupalGet('va-test-style-table');
    $this->assertFieldByXpath('//thead/tr/td[@id="view-age-table-column" and @class="views-field views-field-age"]', '27.2');

    // Function 'Count'.
    $display = &$view->getDisplay('default');
    $display['display_options']['style']['options']['caption'] = 'Test column - Count';
    $display['display_options']['style']['options']['info']['age']['aggr_column'] = 'views_aggregator_count';
    $view->save();

    $this->drupalGet('va-test-style-table');
    $this->assertFieldByXPath('//thead/tr/td', '5');

    // Function 'Count unique'.
    $display = &$view->getDisplay('default');
    $display['display_options']['style']['options']['caption'] = 'Test column - Count unique';
    $display['display_options']['style']['options']['info']['job']['aggr_column'] = 'views_aggregator_count_unique';
    $display['display_options']['style']['options']['info']['job']['has_aggr_column'] = 1;
    $view->save();

    $this->drupalGet('va-test-style-table');
    $this->assertFieldByXpath('//thead/tr/td[@id="view-job-table-column" and @class="views-field views-field-job"]', '4');

    // Function 'Enumerate raw'.
    $display = &$view->getDisplay('default');
    $display['display_options']['style']['options']['caption'] = 'Test column - Enumerate';
    $display['display_options']['style']['options']['info']['job']['aggr_column'] = 'views_aggregator_enumerate_raw';
    $display['display_options']['style']['options']['info']['job']['has_aggr_column'] = 1;
    $view->save();

    $this->drupalGet('va-test-style-table');
    $this->assertRaw('Speaker<br />Songwriter<br />Drummer<br />Singer<br />Singer</td>');

    // Function 'Enumerate (sort, no dupl.)'.
    $display = &$view->getDisplay('default');
    $display['display_options']['style']['options']['caption'] = 'Test column - Enumerate (sort., no dupl.)';
    $display['display_options']['style']['options']['info']['job']['aggr_column'] = 'views_aggregator_enumerate';
    $display['display_options']['style']['options']['info']['job']['has_aggr_column'] = 1;
    $view->save();

    $this->drupalGet('va-test-style-table');
    $this->assertRaw('Drummer<br />Singer<br />Songwriter<br />Speaker');

    // Function 'Maximum'.
    $display = &$view->getDisplay('default');
    $display['display_options']['style']['options']['caption'] = 'Test column - Maximum';
    $display['display_options']['style']['options']['info']['age']['aggr_column'] = 'views_aggregator_maximum';
    $view->save();

    $this->drupalGet('va-test-style-table');
    $this->assertFieldByXpath('//thead/tr/td[@id="view-age-table-column" and @class="views-field views-field-age"]', '30');

    // Function 'Median'.
    $display = &$view->getDisplay('default');
    $display['display_options']['style']['options']['caption'] = 'Test column - Median';
    $display['display_options']['style']['options']['info']['age']['aggr_column'] = 'views_aggregator_median';
    $view->save();

    $this->drupalGet('va-test-style-table');
    $this->assertFieldByXpath('//thead/tr/td[@id="view-age-table-column" and @class="views-field views-field-age"]', '27');

    // Function 'Minimum'.
    $display = &$view->getDisplay('default');
    $display['display_options']['style']['options']['caption'] = 'Test column - Minimum';
    $display['display_options']['style']['options']['info']['age']['aggr_column'] = 'views_aggregator_minimum';
    $view->save();

    $this->drupalGet('va-test-style-table');
    $this->assertFieldByXpath('//thead/tr/td[@id="view-age-table-column" and @class="views-field views-field-age"]', '25');

    // Function 'Range'.
    $display = &$view->getDisplay('default');
    $display['display_options']['style']['options']['caption'] = 'Test column - Range';
    $display['display_options']['style']['options']['info']['age']['aggr_column'] = 'views_aggregator_range';
    $view->save();

    $this->drupalGet('va-test-style-table');
    $this->assertFieldByXpath('//thead/tr/td[@id="view-age-table-column" and @class="views-field views-field-age"]', '25 - 30');
  }

}
