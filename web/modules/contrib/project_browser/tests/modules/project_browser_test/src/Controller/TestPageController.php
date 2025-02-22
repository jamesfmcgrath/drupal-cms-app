<?php

declare(strict_types=1);

namespace Drupal\project_browser_test\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\project_browser\Plugin\ProjectBrowserSourceInterface;

/**
 * Returns a test page for Project Browser.
 */
class TestPageController extends ControllerBase {

  /**
   * Renders the Project Browser test page.
   *
   * @param \Drupal\project_browser\Plugin\ProjectBrowserSourceInterface $source
   *   The source plugin to query for projects.
   *
   * @return array
   *   A render array.
   */
  public function render(ProjectBrowserSourceInterface $source): array {
    return [
      '#type' => 'project_browser',
      '#max_selections' => 2,
      '#source' => $source,
    ];
  }

}
