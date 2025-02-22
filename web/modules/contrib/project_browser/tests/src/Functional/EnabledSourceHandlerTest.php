<?php

declare(strict_types=1);

namespace Drupal\Tests\project_browser\Functional;

use Drupal\project_browser\ActivationStatus;
use Drupal\project_browser\EnabledSourceHandler;
use Drupal\project_browser\ProjectBrowser\Project;
use Drupal\project_browser_test\Plugin\ProjectBrowserSource\ProjectBrowserTestMock;
use Drupal\Tests\BrowserTestBase;

/**
 * @covers \Drupal\project_browser\EnabledSourceHandler
 * @group project_browser
 */
class EnabledSourceHandlerTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['project_browser_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->config('project_browser.admin_settings')
      ->set('enabled_sources', ['project_browser_test_mock', 'drupal_core'])
      ->save(TRUE);
  }

  /**
   * Tests that trying to load a previously unseen project throws an exception.
   */
  public function testExceptionOnGetUnknownProject(): void {
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage("Project 'sight/unseen' was not found in non-volatile storage.");

    $this->container->get(EnabledSourceHandler::class)
      ->getStoredProject('sight/unseen');
  }

  /**
   * Tests loading a previously seen project.
   */
  public function testGetStoredProject(): void {
    $handler = $this->container->get(EnabledSourceHandler::class);

    $project = $handler->getProjects('project_browser_test_mock')->list[0];

    $project_again = $handler->getStoredProject('project_browser_test_mock/' . $project->id);
    $this->assertNotSame($project, $project_again);
    // Project::$status is a typed property and therefore must be initialized
    // before it is accessed by jsonSerialize().
    $project->status = ActivationStatus::Active;
    $project_again->status = ActivationStatus::Active;
    $this->assertSame($project->jsonSerialize(), $project_again->jsonSerialize());

    // The activation status and commands should be set.
    $this->assertTrue(self::hasActivationData($project_again));
  }

  /**
   * Tests that query results are not stored if there was an error.
   */
  public function testErrorsAreNotStored(): void {
    /** @var \Drupal\project_browser\EnabledSourceHandler $handler */
    $handler = $this->container->get(EnabledSourceHandler::class);

    $handler->getProjects('project_browser_test_mock');
    /** @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface $storage */
    $storage = $this->container->get('keyvalue')
      ->get('project_browser:project_browser_test_mock');
    // Query results should have been stored.
    $query_cache_key = 'query:' . md5('[]');
    $this->assertTrue($storage->has($query_cache_key));

    $handler->clearStorage();
    ProjectBrowserTestMock::$resultsError = 'Nope!';

    $handler->getProjects('project_browser_test_mock');
    // No query results should have been stored.
    $this->assertFalse($storage->has($query_cache_key));
  }

  /**
   * Checks if a project object is carrying activation data.
   *
   * @param \Drupal\project_browser\ProjectBrowser\Project $project
   *   The project object.
   *
   * @return bool
   *   TRUE if the project has its activation status and commands set, FALSE
   *   otherwise.
   */
  private static function hasActivationData(Project $project): bool {
    $status = new \ReflectionProperty(Project::class, 'status');
    $commands = new \ReflectionProperty(Project::class, 'commands');
    return $status->isInitialized($project) && $commands->isInitialized($project);
  }

  /**
   * Tests that the install profile is ignored by the drupal_core source.
   */
  public function testProfileNotListedByCoreSource(): void {
    $result = $this->container->get(EnabledSourceHandler::class)->getProjects('drupal_core');
    // Assert that the current install profile is not returned by the source.
    $this->assertNotContains($this->profile, array_column($result->list, 'machineName'));
  }

}
