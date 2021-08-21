<?php

namespace Drupal\commerce_add_to_cart_link\Controller;

use Drupal\commerce\Context;
use Drupal\commerce_add_to_cart_link\CartLinkTokenInterface;
use Drupal\commerce_cart\CartManagerInterface;
use Drupal\commerce_cart\CartProviderInterface;
use Drupal\commerce_order\Resolver\OrderTypeResolverInterface;
use Drupal\commerce_price\Resolver\ChainPriceResolverInterface;
use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\commerce_store\CurrentStoreInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines the add to cart controller.
 *
 * The controller enables product variations to be added via GET requests.
 */
class AddToCartController extends ControllerBase {

  /**
   * The cart link token service.
   *
   * @var \Drupal\commerce_add_to_cart_link\CartLinkTokenInterface
   */
  protected $cartLinkToken;

  /**
   * The cart manager.
   *
   * @var \Drupal\commerce_cart\CartManagerInterface
   */
  protected $cartManager;

  /**
   * The cart provider.
   *
   * @var \Drupal\commerce_cart\CartProviderInterface
   */
  protected $cartProvider;

  /**
   * The chain base price resolver.
   *
   * @var \Drupal\commerce_price\Resolver\ChainPriceResolverInterface
   */
  protected $chainPriceResolver;

  /**
   * The order type resolver.
   *
   * @var \Drupal\commerce_order\Resolver\OrderTypeResolverInterface
   */
  protected $orderTypeResolver;

  /**
   * The current store.
   *
   * @var \Drupal\commerce_store\CurrentStoreInterface
   */
  protected $currentStore;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The path validator.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * Constructs a new AddToCartController object.
   *
   * @param \Drupal\commerce_add_to_cart_link\CartLinkTokenInterface $cart_link_token
   *   The cart link token service.
   * @param \Drupal\commerce_cart\CartManagerInterface $cart_manager
   *   The cart manager.
   * @param \Drupal\commerce_cart\CartProviderInterface $cart_provider
   *   The cart provider.
   * @param \Drupal\commerce_order\Resolver\OrderTypeResolverInterface $order_type_resolver
   *   The order type resolver.
   * @param \Drupal\commerce_store\CurrentStoreInterface $current_store
   *   The current store.
   * @param \Drupal\commerce_price\Resolver\ChainPriceResolverInterface $chain_price_resolver
   *   The chain base price resolver.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The path validator.
   */
  public function __construct(CartLinkTokenInterface $cart_link_token, CartManagerInterface $cart_manager, CartProviderInterface $cart_provider, OrderTypeResolverInterface $order_type_resolver, CurrentStoreInterface $current_store, ChainPriceResolverInterface $chain_price_resolver, AccountInterface $current_user, PathValidatorInterface $path_validator) {
    $this->cartLinkToken = $cart_link_token;
    $this->cartManager = $cart_manager;
    $this->cartProvider = $cart_provider;
    $this->orderTypeResolver = $order_type_resolver;
    $this->currentStore = $current_store;
    $this->chainPriceResolver = $chain_price_resolver;
    $this->currentUser = $current_user;
    $this->pathValidator = $path_validator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('commerce_add_to_cart_link.token'),
      $container->get('commerce_cart.cart_manager'),
      $container->get('commerce_cart.cart_provider'),
      $container->get('commerce_order.chain_order_type_resolver'),
      $container->get('commerce_store.current_store'),
      $container->get('commerce_price.chain_price_resolver'),
      $container->get('current_user'),
      $container->get('path.validator')
    );
  }

  /**
   * Performs the add to cart action and redirects to cart.
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface $commerce_product
   *   The product entity.
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface $commerce_product_variation
   *   The product variation to add.
   * @param string $token
   *   The CSRF token.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect to the cart after adding the product.
   *
   * @throws \Exception
   *   When the call to self::selectStore() throws an exception because the
   *   entity can't be purchased from the current store.
   */
  public function action(ProductInterface $commerce_product, ProductVariationInterface $commerce_product_variation, $token, Request $request) {
    $quantity = $request->query->get('quantity', 1);
    $combine = (bool) $request->query->get('combine', 1);

    $order_item = $this->cartManager->createOrderItem($commerce_product_variation, $quantity);

    $store = $this->selectStore($commerce_product_variation);
    $context = new Context($this->currentUser, $store);
    // Explicitly resolve the product price. @todo check necessity after https://www.drupal.org/project/commerce/issues/3088582 has been fixed.
    $resolved_price = $this->chainPriceResolver->resolve($commerce_product_variation, $quantity, $context);
    $order_item->setUnitPrice($resolved_price);

    $order_type_id = $this->orderTypeResolver->resolve($order_item);
    $cart = $this->cartProvider->getCart($order_type_id, $store);
    if (!$cart) {
      $cart = $this->cartProvider->createCart($order_type_id, $store);
    }
    $this->cartManager->addOrderItem($cart, $order_item, $combine);

    if ($this->config('commerce_add_to_cart_link.settings')->get('redirect_back')) {
      $referer = $request->server->get('HTTP_REFERER');
      $fake_request = Request::create($referer);
      $referer_url = $this->pathValidator->getUrlIfValid($fake_request->getRequestUri());
      if ($referer_url && $referer_url->isRouted() && $referer_url->getRouteName() !== 'user.login') {
        $referer_url->setAbsolute();
        return new RedirectResponse($referer_url->toString());
      }
    }

    return $this->redirect('commerce_cart.page');
  }

  /**
   * Access callback for the action route.
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface $commerce_product
   *   The product entity.
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface $commerce_product_variation
   *   The product variation to add.
   * @param string $token
   *   The CSRF token.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(ProductInterface $commerce_product, ProductVariationInterface $commerce_product_variation, $token) {
    if (!$commerce_product->isPublished() || !$commerce_product->access('view')) {
      // If product is disabled or the user has no view access, deny.
      return AccessResult::forbidden();
    }
    if (!$commerce_product_variation->isPublished() || !$commerce_product_variation->access('view')) {
      // If the variation is inactive, deny.
      return AccessResult::forbidden();
    }
    if ((int) $commerce_product->id() !== (int) $commerce_product_variation->getProductId()) {
      // Deny, if the product ID and variation's parent product ID don't match.
      return AccessResult::forbidden();
    }

    return AccessResult::allowedIf($this->cartLinkToken->validate($commerce_product_variation, $token));
  }

  /**
   * Selects the store for the given variation.
   *
   * If the variation is sold from one store, then that store is selected.
   * If the variation is sold from multiple stores, and the current store is
   * one of them, then that store is selected.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface $variation
   *   The variation being added to cart.
   *
   * @throws \Exception
   *   When the variation can't be purchased from the current store.
   *
   * @return \Drupal\commerce_store\Entity\StoreInterface
   *   The selected store.
   */
  protected function selectStore(ProductVariationInterface $variation) {
    $stores = $variation->getStores();
    if (count($stores) === 1) {
      $store = reset($stores);
    }
    else {
      $store = $this->currentStore->getStore();
      if (!in_array($store, $stores)) {
        // Indicates that the site listings are not filtered properly.
        throw new \Exception("The given entity can't be purchased from the current store.");
      }
    }

    return $store;
  }

}
