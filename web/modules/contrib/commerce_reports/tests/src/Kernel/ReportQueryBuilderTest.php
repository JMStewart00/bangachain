<?php

namespace Drupal\Tests\commerce_reports\Kernel;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\Query\QueryAggregateInterface;
use Drupal\Core\Entity\Query\Sql\QueryAggregate;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests ReportQueryBuilder.
 *
 * @group commerce_reports
 */
class ReportQueryBuilderTest extends CommerceKernelTestBase {

  public static $modules = [
    'entity_reference_revisions',
    'profile',
    'state_machine',
    'commerce_order',
    'commerce_reports',
  ];

  /**
   * The report query builder.
   *
   * @var \Drupal\commerce_reports\ReportQueryBuilder
   */
  protected $reportQueryBuilder;

  /**
   * The report type manager.
   *
   * @var \Drupal\commerce_reports\ReportTypeManager
   */
  protected $reportTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->reportQueryBuilder = $this->container->get('commerce_reports.query_builder');
    $this->reportTypeManager = $this->container->get('plugin.manager.commerce_report_type');
  }

  /**
   * Tests ::getQuery.
   */
  public function testGetQuery() {
    /** @var \Drupal\commerce_reports\Plugin\Commerce\ReportType\ReportTypeInterface $report_type_plugin */
    $report_type_plugin = $this->reportTypeManager->createInstance('order_report');

    $query = $this->reportQueryBuilder->getQuery($report_type_plugin);
    $this->assertInstanceOf(QueryAggregateInterface::class, $query);
    $this->assertTrue($query->hasTag('commerce_reports'));
    $this->assertEquals('F Y', $query->getMetaData('report_date_format'));

    $query = $this->reportQueryBuilder->getQuery($report_type_plugin, 'j F Y');
    $this->assertEquals('j F Y', $query->getMetaData('report_date_format'));
  }

  /**
   * Tests ::alterQuery.
   */
  public function testAlterQuery() {
    /** @var \Drupal\commerce_reports\Plugin\Commerce\ReportType\ReportTypeInterface $report_type_plugin */
    $report_type_plugin = $this->reportTypeManager->createInstance('order_report');

    /** @var \Drupal\Core\Entity\Query\Sql\QueryAggregate $query */
    $query = $this->reportQueryBuilder->getQuery($report_type_plugin);
    /** @var \Drupal\Core\Database\Query\SelectInterface $sqlQuery */
    $sqlQuery = $this->getSqlQueryFromAggregateQuery($query);
    $this->reportQueryBuilder->alterQuery($sqlQuery);

    $expressions = $sqlQuery->getExpressions();
    $this->assertTrue(isset($expressions['formatted_date']));
    $formatted_date_expression = $expressions['formatted_date']['expression'];

    $db_type = $this->container->get('database')->databaseType();
    switch ($db_type) {
      case 'mysql':
        $this->assertEquals("DATE_FORMAT(FROM_UNIXTIME(base_table.created), '%M %Y')", $formatted_date_expression);
        break;

      case 'sqlite':
        $this->assertEquals("strftime('%m %Y', base_table.created, 'unixepoch')", $formatted_date_expression);
        break;

      case 'pgsql':
        break;
    }

    /** @var \Drupal\Core\Entity\Query\Sql\QueryAggregate $query */
    $query = $this->reportQueryBuilder->getQuery($report_type_plugin, 'j F Y');
    /** @var \Drupal\Core\Database\Query\SelectInterface $sqlQuery */
    $sqlQuery = $this->getSqlQueryFromAggregateQuery($query);
    $this->reportQueryBuilder->alterQuery($sqlQuery);

    $expressions = $sqlQuery->getExpressions();
    $this->assertTrue(isset($expressions['formatted_date']));
    $formatted_date_expression = $expressions['formatted_date']['expression'];

    $db_type = $this->container->get('database')->databaseType();
    switch ($db_type) {
      case 'mysql':
        $this->assertEquals("DATE_FORMAT(FROM_UNIXTIME(base_table.created), '%e %M %Y')", $formatted_date_expression);
        break;

      case 'sqlite':
        $this->assertEquals("strftime('%d %m %Y', base_table.created, 'unixepoch')", $formatted_date_expression);
        break;

      case 'pgsql':
        break;
    }
  }

  /**
   * Gets an sql query from an aggreggate query.
   *
   * @param \Drupal\Core\Entity\Query\Sql\QueryAggregate $query
   *   The aggregate query.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   The sql query.
   */
  protected function getSqlQueryFromAggregateQuery(QueryAggregate $query) {
    $query->prepare();
    $reflection = new \ReflectionObject($query);
    $property = $reflection->getProperty('sqlQuery');
    $property->setAccessible(TRUE);
    /** @var \Drupal\Core\Database\Query\SelectInterface $sqlQuery */
    $sqlQuery = $property->getValue($query);

    $this->assertInstanceOf(SelectInterface::class, $sqlQuery);
    return $sqlQuery;
  }

}
