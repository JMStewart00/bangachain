<?php

namespace Drupal\commerce_add_to_wishlist_link\Controller;

use Drupal\commerce_add_to_cart_link\CartLinkTokenInterface;
use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\commerce_wishlist\WishlistManagerInterface;
use Drupal\commerce_wishlist\WishlistProviderInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Path\PathValidatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines the add to wishlist controller.
 *
 * The controller enables product variations to be added via GET requests.
 */
class AddToWishlistController extends ControllerBase {

  /**
   * The cart link token service.
   *
   * @var \Drupal\commerce_add_to_cart_link\CartLinkTokenInterface
   */
  protected $cartLinkToken;

  /**
   * The wishlist manager.
   *
   * @var \Drupal\commerce_wishlist\WishlistManagerInterface
   */
  protected $wishlistManager;

  /**
   * The wishlist provider.
   *
   * @var \Drupal\commerce_wishlist\WishlistProviderInterface
   */
  protected $wishlistProvider;

  /**
   * The path validator.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * Constructs a new AddToWishlistController object.
   *
   * @param \Drupal\commerce_add_to_cart_link\CartLinkTokenInterface $cart_link_token
   *   The cart link token service.
   * @param \Drupal\commerce_wishlist\WishlistManagerInterface $wishlist_manager
   *   The wishlist manager.
   * @param \Drupal\commerce_wishlist\WishlistProviderInterface $wishlist_provider
   *   The wishlist provider.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The path validator.
   */
  public function __construct(CartLinkTokenInterface $cart_link_token, WishlistManagerInterface $wishlist_manager, WishlistProviderInterface $wishlist_provider, PathValidatorInterface $path_validator) {
    $this->cartLinkToken = $cart_link_token;
    $this->wishlistManager = $wishlist_manager;
    $this->wishlistProvider = $wishlist_provider;
    $this->pathValidator = $path_validator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('commerce_add_to_cart_link.token'),
      $container->get('commerce_wishlist.wishlist_manager'),
      $container->get('commerce_wishlist.wishlist_provider'),
      $container->get('path.validator')
    );
  }

  /**
   * Performs the add to wishlist action and redirects to wishlist.
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
   *   A redirect to the wishlist after adding the product.
   *
   * @throws \Exception
   *   When the call to self::selectStore() throws an exception because the
   *   entity can't be purchased from the current store.
   */
  public function action(ProductInterface $commerce_product, ProductVariationInterface $commerce_product_variation, $token, Request $request) {
    $quantity = $request->query->get('quantity', 1);

    // Determine the wishlist type to use.
    $wishlist_type = $this->config('commerce_wishlist.settings')->get('default_type') ?: 'default';

    $wishlist = $this->wishlistProvider->getWishlist($wishlist_type);
    if (!$wishlist) {
      $wishlist = $this->wishlistProvider->createWishlist($wishlist_type);
    }
    $this->wishlistManager->addEntity($wishlist, $commerce_product_variation, $quantity);

    if ($this->config('commerce_add_to_cart_link.settings')->get('redirect_back')) {
      $referer = $request->server->get('HTTP_REFERER');
      $fake_request = Request::create($referer);
      $referer_url = $this->pathValidator->getUrlIfValid($fake_request->getRequestUri());
      if ($referer_url && $referer_url->isRouted() && $referer_url->getRouteName() !== 'user.login') {
        $referer_url->setAbsolute();
        return new RedirectResponse($referer_url->toString());
      }
    }

    return $this->redirect('commerce_wishlist.page');
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

}
