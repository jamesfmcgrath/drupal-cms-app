<?php

namespace Drupal\project_browser\Element;

use Drupal\Component\Utility\DeprecationHelper;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Attribute\RenderElement;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\ElementInterface;
use Drupal\Core\Render\Element\RenderElementBase;
use Drupal\project_browser\Plugin\ProjectBrowserSourceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a render element for the Project Browser.
 *
 * @RenderElement("project_browser")
 */
#[RenderElement('project_browser')]
final class ProjectBrowser implements ElementInterface, ContainerFactoryPluginInterface {

  use DependencySerializationTrait;

  public function __construct(
    private readonly string $pluginId,
    private readonly mixed $pluginDefinition,
    private readonly ModuleHandlerInterface $moduleHandler,
    private readonly ConfigFactoryInterface $configFactory,
    private readonly UuidInterface $uuid,
    private readonly CurrentPathStack $currentPath,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getPluginId(): string {
    return $this->pluginId;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginDefinition(): mixed {
    return $this->pluginDefinition;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $plugin_id,
      $plugin_definition,
      $container->get(ModuleHandlerInterface::class),
      $container->get(ConfigFactoryInterface::class),
      $container->get(UuidInterface::class),
      $container->get(CurrentPathStack::class),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    return [
      '#theme' => 'project_browser_main_app',
      '#attached' => [
        'library' => [
          'project_browser/svelte',
        ],
        'drupalSettings' => [
          'project_browser' => [],
        ],
      ],
      '#pre_render' => [
        [$this, 'attachProjectBrowserSettings'],
      ],
    ];
  }

  /**
   * Prepares a render element for Project Browser.
   *
   * @param array $element
   *   A render element array.
   *
   * @return array
   *   The render element array.
   */
  public function attachProjectBrowserSettings(array $element): array {
    assert($element['#source'] instanceof ProjectBrowserSourceInterface);
    $element['#id'] ??= $this->uuid->generate();
    $element['#attached']['drupalSettings']['project_browser'] = $this->getDrupalSettings(
      $element['#source'],
      $element['#id'],
      $element['#max_selections'] ?? $this->configFactory->get('project_browser.admin_settings')->get('max_selections') ?? NULL,
    );
    return $element;
  }

  /**
   * Gets the Drupal settings for the Project Browser.
   *
   * @param \Drupal\project_browser\Plugin\ProjectBrowserSourceInterface $source
   *   The source plugin to query for projects.
   * @param string $instance_id
   *   An identifier for the project browser application instance.
   * @param int|null $max_selections
   *   (optional) The maximum number of project to install at once, or NULL for
   *   no limit. Defaults to NULL.
   *
   * @return array
   *   An array of Drupal settings.
   */
  private function getDrupalSettings(ProjectBrowserSourceInterface $source, string $instance_id, ?int $max_selections = NULL): array {
    if (is_int($max_selections) && $max_selections <= 0) {
      throw new \InvalidArgumentException('$max_selections must be a positive integer or NULL.');
    }
    $sort_options = $source->getSortOptions();
    return [
      'module_path' => $this->moduleHandler->getModule('project_browser')->getPath(),
      'default_plugin_id' => $source->getPluginId(),
      'package_manager' => $this->configFactory->get('project_browser.admin_settings')->get('allow_ui_install') && $this->moduleHandler->moduleExists('package_manager'),
      'max_selections' => $max_selections,
      'current_path' => '/' . $this->currentPath->getPath(),
      'instances' => [
        $instance_id => [
          'source' => $source->getPluginId(),
          'name' => $source->getPluginDefinition()['label'],
          'filters' => (object) $source->getFilterDefinitions(),
          'sorts' => (object) $sort_options,
          'sortBy' => key($sort_options),
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function setAttributes(&$element, $class = []): void {
    DeprecationHelper::backwardsCompatibleCall(
      \Drupal::VERSION,
      '10.3',
      static fn () => RenderElementBase::setAttributes($element, $class),
      static fn () => Element::setAttributes($element, $class)
    );
  }

}
