<?php

namespace Drupal\beedit\Breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Link;

/**
 * Class BeEditBreadcrumbBuilder.
 *
 * @package Drupal\beedit\Breadcrumb
 */
class BeEditBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  /**
   * Determine whether to override default breadcrumb builder.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $attributes
   *   Breadcrumb attributes.
   *
   * @return bool
   *   Return true to call build method.
   */
  public function applies(RouteMatchInterface $attributes) {
    // If route name is beedit.view, beedit.add, etc. then return TRUE to call
    // custom build method.
    $route_name = $attributes->getRouteName();
    $regex = "/^beedit.(view|add|edit|run|delete|settings)$/";
    return preg_match($regex, $route_name);
  }

  /**
   * Override default breadcrumb builder.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   Route_match.
   *
   * @return \Drupal\Core\Breadcrumb\Breadcrumb
   *   Customized breadcrumb links.
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();
    $breadcrumb->addCacheContexts(['url.path.parent']);
    $breadcrumb->setLinks($this->buildDefaultLinks());
    $breadcrumb->addLink(Link::createFromRoute('BeEdit', 'beedit.list'));
    return $breadcrumb;
  }

  /**
   * Helper function to build an array of default links for breadcrumb trail.
   *
   * @return array
   *   Default breadcrumb links
   */
  private function buildDefaultLinks() {
    $links = [
      Link::createFromRoute(t('Home'), '<front>'),
      Link::createFromRoute(t('Administration'), 'system.admin'),
      Link::createFromRoute(t('Configuration'), 'system.admin_config'),
      Link::createFromRoute(t('Development'), 'system.admin_config_development'),
    ];
    return $links;
  }

}
