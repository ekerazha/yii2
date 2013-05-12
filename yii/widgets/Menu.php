<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\widgets;

use Yii;
use yii\base\Widget;
use yii\helpers\Html;

/**
 * Menu displays a multi-level menu using nested HTML lists.
 *
 * The main property of Menu is [[items]], which specifies the possible items in the menu.
 * A menu item can contain sub-items which specify the sub-menu under that menu item.
 * 
 * Menu checks the current route and request parameters to toggle certain menu items
 * with active state.
 * 
 * Note that Menu only renders the HTML tags about the menu. It does do any styling.
 * You are responsible to provide CSS styles to make it look like a real menu.
 *
 * The following example shows how to use Menu:
 * 
 * ~~~
 * // $this is the view object currently being used
 * echo Menu::widget($this, array(
 *     'items' => array(
 *         // Important: you need to specify url as 'controller/action',
 *         // not just as 'controller' even if default action is used.
 *         array('label' => 'Home', 'url' => array('site/index')),
 *         // 'Products' menu item will be selected as long as the route is 'product/index'
 *         array('label' => 'Products', 'url' => array('product/index'), 'items' => array(
 *             array('label' => 'New Arrivals', 'url' => array('product/index', 'tag' => 'new')),
 *             array('label' => 'Most Popular', 'url' => array('product/index', 'tag' => 'popular')),
 *         )),
 *         array('label' => 'Login', 'url' => array('site/login'), 'visible' => Yii::app()->user->isGuest),
 *     ),
 * ));
 * ~~~
 * 
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Menu extends Widget
{
	/**
	 * @var array list of menu items. Each menu item should be an array of the following structure:
	 *
	 * - label: string, optional, specifies the menu item label. When [[encodeLabels]] is true, the label
	 *   will be HTML-encoded. If the label is not specified, an empty string will be used.
	 * - url: string or array, optional, specifies the URL of the menu item. It will be processed by [[Html::url]].
	 *   When this is set, the actual menu item content will be generated using [[linkTemplate]];
	 *   otherwise, [[labelTemplate]] will be used.
	 * - visible: boolean, optional, whether this menu item is visible. Defaults to true.
	 * - items: array, optional, specifies the sub-menu items. Its format is the same as the parent items.
	 * - active: boolean, optional, whether this menu item is in active state (currently selected).
	 *   If a menu item is active, its CSS class will be appended with [[activeCssClass]].
	 *   If this option is not set, the menu item will be set active automatically when the current request
	 *   is triggered by [[url]]. For more details, please refer to [[isItemActive()]].
	 * - template: string, optional, the template used to render the content of this menu item.
	 *   The token `{url}` will be replaced by the URL associated with this menu item,
	 *   and the token `{label}` will be replaced by the label of the menu item.
	 *   If this option is not set, [[linkTemplate]] or [[labelTemplate]] will be used instead. 
	 */
	public $items = array();
	/**
	 * @var string the template used to render the body of a menu which is a link.
	 * In this template, the token `{url}` will be replaced with the corresponding link URL;
	 * while `{label}` will be replaced with the link text.
	 * This property will be overridden by the `template` option set in individual menu items via [[items]].
	 */
	public $linkTemplate = '<a href="{url}">{label}</a>';
	/**
	 * @var string the template used to render the body of a menu which is NOT a link.
	 * In this template, the token `{label}` will be replaced with the label of the menu item.
	 * This property will be overridden by the `template` option set in individual menu items via [[items]].
	 */
	public $labelTemplate = '{label}';
	/**
	 * @var string the template used to render a list of sub-menus.
	 * In this template, the token `{items}` will be replaced with the renderer sub-menu items.
	 */
	public $submenuTemplate = "\n<ul>\n{items}\n</ul>\n";
	/**
	 * @var boolean whether the labels for menu items should be HTML-encoded.
	 */
	public $encodeLabels = true;
	/**
	 * @var string the CSS class to be appended to the active menu item.
	 */
	public $activeCssClass = 'active';
	/**
	 * @var boolean whether to automatically activate items according to whether their route setting
	 * matches the currently requested route.
	 * @see isItemActive
	 */
	public $activateItems = true;
	/**
	 * @var boolean whether to activate parent menu items when one of the corresponding child menu items is active.
	 * The activated parent menu items will also have its CSS classes appended with [[activeCssClass]].
	 */
	public $activateParents = false;
	/**
	 * @var boolean whether to hide empty menu items. An empty menu item is one whose `url` option is not
	 * set and which has no visible child menu items.
	 */
	public $hideEmptyItems = true;
	/**
	 * @var array the HTML attributes for the menu's container tag.
	 */
	public $options = array();
	/**
	 * @var string the CSS class that will be assigned to the first item in the main menu or each submenu.
	 * Defaults to null, meaning no such CSS class will be assigned.
	 */
	public $firstItemCssClass;
	/**
	 * @var string the CSS class that will be assigned to the last item in the main menu or each submenu.
	 * Defaults to null, meaning no such CSS class will be assigned.
	 */
	public $lastItemCssClass;
	/**
	 * @var string the route used to determine if a menu item is active or not.
	 * If not set, it will use the route of the current request. 
	 * @see params
	 * @see isItemActive
	 */
	public $route;
	/**
	 * @var array the parameters used to determine if a menu item is active or not.
	 * If not set, it will use `$_GET`.
	 * @see route
	 * @see isItemActive
	 */
	public $params;


	/**
	 * Renders the menu.
	 */
	public function run()
	{
		if ($this->route === null && Yii::$app->controller !== null) {
			$this->route = Yii::$app->controller->getRoute();
		}
		if ($this->params === null) {
			$this->params = $_GET;
		}
		$items = $this->normalizeItems($this->items, $hasActiveChild);
		echo Html::tag('ul', $this->renderItems($items), $this->options);
	}

	/**
	 * Recursively renders the menu items (without the container tag).
	 * @param array $items the menu items to be rendered recursively
	 * @return string the rendering result
	 */
	protected function renderItems($items)
	{
		$n = count($items);
		$lines = array();
		foreach ($items as $i => $item) {
			$options = isset($item['itemOptions']) ? $item['itemOptions'] : array();
			$class = array();
			if ($item['active']) {
				$class[] = $this->activeCssClass;
			}
			if ($i === 0 && $this->firstItemCssClass !== null) {
				$class[] = $this->firstItemCssClass;
			}
			if ($i === $n - 1 && $this->lastItemCssClass !== null) {
				$class[] = $this->lastItemCssClass;
			}
			if (!empty($class)) {
				if (empty($options['class'])) {
					$options['class'] = implode(' ', $class);
				} else {
					$options['class'] .= ' ' . implode(' ', $class);
				}
			}

			$menu = $this->renderItem($item);
			if (!empty($item['items'])) {
				$menu .= strtr($this->submenuTemplate, array(
					'{items}' => $this->renderItems($item['items']),
				));
			}
			$lines[] = Html::tag('li', $menu, $options);
		}
		return implode("\n", $lines);
	}

	/**
	 * Renders the content of a menu item.
	 * Note that the container and the sub-menus are not rendered here.
	 * @param array $item the menu item to be rendered. Please refer to [[items]] to see what data might be in the item.
	 * @return string the rendering result
	 */
	protected function renderItem($item)
	{
		if (isset($item['url'])) {
			$template = isset($item['template']) ? $item['template'] : $this->linkTemplate;
			return strtr($template, array(
				'{url}' => Html::url($item['url']),
				'{label}' => $item['label'],
			));
		} else {
			$template = isset($item['template']) ? $item['template'] : $this->labelTemplate;
			return strtr($template, array(
				'{label}' => $item['label'],
			));
		}
	}

	/**
	 * Normalizes the [[items]] property to remove invisible items and activate certain items.
	 * @param array $items the items to be normalized.
	 * @param boolean $active whether there is an active child menu item.
	 * @return array the normalized menu items
	 */
	protected function normalizeItems($items, &$active)
	{
		foreach ($items as $i => $item) {
			if (isset($item['visible']) && !$item['visible']) {
				unset($items[$i]);
				continue;
			}
			if (!isset($item['label'])) {
				$item['label'] = '';
			}
			if ($this->encodeLabels) {
				$items[$i]['label'] = Html::encode($item['label']);
			}
			$hasActiveChild = false;
			if (isset($item['items'])) {
				$items[$i]['items'] = $this->normalizeItems($item['items'], $route, $hasActiveChild);
				if (empty($items[$i]['items']) && $this->hideEmptyItems) {
					unset($items[$i]['items']);
					if (!isset($item['url'])) {
						unset($items[$i]);
						continue;
					}
				}
			}
			if (!isset($item['active'])) {
				if ($this->activateParents && $hasActiveChild || $this->activateItems && $this->isItemActive($item)) {
					$active = $items[$i]['active'] = true;
				} else {
					$items[$i]['active'] = false;
				}
			} elseif ($item['active']) {
				$active = true;
			}
		}
		return array_values($items);
	}

	/**
	 * Checks whether a menu item is active.
	 * This is done by checking if [[route]] and [[params]] match that specified in the `url` option of the menu item.
	 * When the `url` option of a menu item is specified in terms of an array, its first element is treated
	 * as the route for the item and the rest of the elements are the associated parameters.
	 * Only when its route and parameters match [[route]] and [[params]], respectively, will a menu item
	 * be considered active.
	 * @param array $item the menu item to be checked
	 * @return boolean whether the menu item is active
	 */
	protected function isItemActive($item)
	{
		if (isset($item['url']) && is_array($item['url']) && trim($item['url'][0], '/') === $this->route) {
			unset($item['url']['#']);
			if (count($item['url']) > 1) {
				foreach (array_splice($item['url'], 1) as $name => $value) {
					if (!isset($this->params[$name]) || $this->params[$name] != $value) {
						return false;
					}
				}
			}
			return true;
		}
		return false;
	}

}