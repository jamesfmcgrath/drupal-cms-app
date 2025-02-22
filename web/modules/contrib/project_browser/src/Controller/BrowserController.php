<?php

namespace Drupal\project_browser\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\project_browser\Plugin\ProjectBrowserSourceInterface;

/**
 * Defines a controller to provide the Project Browser UI.
 *
 * @internal
 *   Controller classes are internal.
 */
class BrowserController extends ControllerBase {

  /**
   * Builds the browse page for a particular source.
   *
   * @param \Drupal\project_browser\Plugin\ProjectBrowserSourceInterface $source
   *   The source plugin to query for projects.
   *
   * @return array
   *   A render array.
   */
  public function browse(ProjectBrowserSourceInterface $source): array {
    return [
      '#type' => 'project_browser',
      '#source' => $source,
    ];
  }

}
