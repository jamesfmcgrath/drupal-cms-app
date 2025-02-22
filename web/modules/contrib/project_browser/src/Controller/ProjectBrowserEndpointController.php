<?php

namespace Drupal\project_browser\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\project_browser\ActivatorInterface;
use Drupal\project_browser\EnabledSourceHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for the proxy layer.
 */
final class ProjectBrowserEndpointController extends ControllerBase {

  public function __construct(
    private readonly EnabledSourceHandler $enabledSource,
    private readonly ActivatorInterface $activator,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get(EnabledSourceHandler::class),
      $container->get(ActivatorInterface::class),
    );
  }

  /**
   * Returns a list of projects that match a query.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A list of projects.
   *
   * @see \Drupal\project_browser\ProjectBrowser\ProjectsResultsPage
   */
  public function getAllProjects(Request $request): JsonResponse {
    $current_sources = $this->enabledSource->getCurrentSources();
    $query = $this->buildQuery($request);
    if (!$current_sources || empty($query['source'])) {
      return new JsonResponse([], Response::HTTP_ACCEPTED);
    }

    // The activator is the source of truth about the status of the project with
    // respect to the current site, and it is responsible for generating
    // the activation instructions or commands.
    $result = $this->enabledSource->getProjects($query['source'], $query);
    foreach ($result->list as $project) {
      $project->status = $this->activator->getStatus($project);
      $project->commands = $this->activator->getInstructions($project);
    }
    return new JsonResponse($result);
  }

  /**
   * Builds the query based on the current request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return array
   *   See \Drupal\project_browser\EnabledSourceHandler::getProjects().
   */
  private function buildQuery(Request $request): array {
    // Validate and build query.
    $query = [
      'page' => (int) $request->query->get('page', 0),
      'limit' => (int) $request->query->get('limit', 12),
    ];

    $machine_name = $request->query->get('machine_name');
    if ($machine_name) {
      $query['machine_name'] = $machine_name;
    }

    $sort = $request->query->get('sort');
    if ($sort) {
      $query['sort'] = $sort;
    }

    $title = $request->query->get('search');
    if ($title) {
      $query['search'] = $title;
    }

    $categories = $request->query->get('categories');
    if ($categories) {
      $query['categories'] = $categories;
    }

    $maintenance_status = $request->query->get('maintenance_status');
    if ($maintenance_status) {
      $query['maintenance_status'] = $maintenance_status;
    }

    $development_status = $request->query->get('development_status');
    if ($development_status) {
      $query['development_status'] = $development_status;
    }

    $security_advisory_coverage = $request->query->get('security_advisory_coverage');
    if ($security_advisory_coverage) {
      $query['security_advisory_coverage'] = $security_advisory_coverage;
    }

    $displayed_source = $request->query->get('source', 0);
    if ($displayed_source) {
      $query['source'] = $displayed_source;
    }

    return $query;
  }

}
