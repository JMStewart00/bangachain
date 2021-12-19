<?php

namespace Drupal\splide_ui\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the Splide admin settings form.
 */
class SplideSettingsForm extends ConfigFormBase {

  /**
   * Drupal\Core\Asset\LibraryDiscoveryInterface definition.
   *
   * @var Drupal\Core\Asset\LibraryDiscoveryInterface
   */
  protected $libraryDiscovery;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /**
     * @var \Drupal\splide_ui\Form\SplideSettingsForm
     */
    $instance = parent::create($container);
    $instance->libraryDiscovery = $container->get('library.discovery');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'splide_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['splide.settings'];
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('splide.settings');

    $form['module_css'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Enable Splide module splide.theme.css'),
      '#description'   => $this->t('Uncheck to permanently disable the module splide.theme.css, normally included along with skins.'),
      '#default_value' => $config->get('module_css'),
      '#prefix'        => $this->t("Note! Splide doesn't need Splide UI to run. It is always safe to uninstall Splide UI once done with optionsets."),
    ];

    $form['splide_css'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Enable Splide library splide.min.css'),
      '#description'   => $this->t('Uncheck to permanently disable the optional splide.min.css, normally included along with skins.'),
      '#default_value' => $config->get('splide_css'),
    ];

    $form['sitewide'] = [
      '#type'         => 'select',
      '#title'        => $this->t('Load splide globally'),
      '#empty_option' => $this->t('- None -'),
      '#options'      => [
        1 => $this->t('With default initializer'),
        2 => $this->t('With vanilla initializer'),
        3 => $this->t('Without initializer'),
      ],
      '#description' => $this->t('<b>Warning!</b> <br>- Leave it empty if usages are for body texts only, please use shortcodes provided via Splide filter instead, see <a href=":url1">/filter/tips</a> page. <br>- This currently _might break Splide formatters and Splide Views till this line removed. <br>- Not compatible with BigPipe module due to assets re-ordering issue, <a href=":url2">read more</a>. Meaning may break any stylings provided by this module. <br>Leave it empty unless you really need to, and or are willing to help the developments, or know how to fix the side effects. <ol><li><b>With default initializer</b> will include the module splide.load.min.js as the initializer normally used by the module formatters or views identified by <code>.splide--default</code> selector. Only if you need consistent styling/ skins, classes, media player, lightboxes, and markups. Works immediately at body texts.</li><li><b>With vanilla initializer</b> will include the module splide.vanilla.min.js as the minimal initializer identified by <code>.splide-vanilla</code> selector. Default skins, media player, lightboxes are unusable. Be sure to add CSS class <code>.splide-vanilla</code> to your Splide. Recommended to not interfere or co-exist with module formatters/ views. Works immediately at body texts.</li><li><b>Without initializer</b> will load only the main libraries. No module skins, no module JS. It is all yours -- broken unless you initialize it.</li></ol> This will include Splide anywhere except admin pages. Only do this if you need Splide where PHP or Twig is not available such as at body texts. Otherwise use the provided API instead. Implements <code>hook_splide_attach_alter</code> to include additional libraries such as skins, colorbox, etc. At any rate, you can inject options via <code>data-splide</code> attribute, or custom JavaScript. You can also include them at your theme, it is just a convenient way to avoid hard-coding at every theme changes. Check out splide.html.twig for more markups.', [
        ':url1' => '/filter/tips#splide',
        ':url2' => 'https://drupal.org/node/3211873',
      ]),
      '#default_value' => $config->get('sitewide'),
    ];

    $default = $config->get('sitewide') == 0 || $config->get('sitewide') == 1;
    $form['preview'] = $default ? $this->withInitializer() : $this->withoutInitializer();

    return parent::buildForm($form, $form_state);
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->configFactory->getEditable('splide.settings')
      ->set('module_css', $form_state->getValue('module_css'))
      ->set('splide_css', $form_state->getValue('splide_css'))
      ->set('sitewide', (int) $form_state->getValue('sitewide'))
      ->save();

    // Invalidate the library discovery cache to update new assets.
    $this->libraryDiscovery->clearCachedDefinitions();
    $this->configFactory->clearStaticCache();

    parent::submitForm($form, $form_state);
  }

  /**
   * Provides sample with default splide.html.twig markups.
   */
  private function withInitializer() {
    $items = [];

    foreach (['One', 'Two', 'Three'] as $key) {
      $img = '<img src="https://drupal.org/files/' . $key . '.gif" />';
      $items[] = [
        'slide'   => ['#markup' => $img],
        'caption' => ['title' => $key],
      ];
    }

    $build = [
      'items' => $items,
      'settings' => ['skin' => 'classic', 'layout' => 'bottom'],
      'options' => ['type' => 'loop', 'arrows' => TRUE, 'pagination' => TRUE],
    ];

    $content = \splide()->build($build);
    return $this->preview($content, ' splide--default');
  }

  /**
   * Provides sample without default splide.html.twig markups.
   */
  private function withoutInitializer() {
    $config  = $this->config('splide.settings');
    $vanilla = $config->get('sitewide') == 2;
    $items   = [];

    foreach (['One', 'Two', 'Three'] as $key) {
      $items[] = [
        '#markup' => '<img src="https://drupal.org/files/' . $key . '.gif" />',
        '#wrapper_attributes' => ['class' => ['splide__slide']],
      ];
    }

    $class  = $vanilla ? ' splide--vanilla' : ' splide--whatever';
    $config = "{&quot;type&quot;: &quot;loop&quot;, &quot;arrows&quot;: true, &quot;pagination&quot;: true}";
    $prefix = 'class="splide' . $class . '" data-splide="' . $config . '"';

    $content = [
      '#theme' => 'item_list',
      '#items' => $items,
      '#attributes' => ['class' => ['splide__list']],
      '#wrapper_attributes' => ['class' => ['splide__track']],
      '#prefix' => '<div class="splide__slider">',
      '#suffix' => '</div>',
    ];

    return $this->preview($content, $class, $prefix);
  }

  /**
   * Provides sample w/o default Splide markups.
   */
  private function preview($content, $class = '', $prefix = '') {
    $config = $this->config('splide.settings');
    $unload = $config->get('sitewide') == 2 || $config->get('sitewide') == 3;
    $attach = \splide()->attach([
      '_unload'  => $unload,
      '_vanilla' => $config->get('sitewide') == 2,
    ]);

    $suffix = '<p>' . $this->t("The <code>splide__slider</code> DIV is optional for more reboust positioning with more complex slide layouts. The <code>splide, splide__track, splide__list</code> classes are required by all variants, including the item list (UL) markups. Thus you need to have your own unique class to not conflict with the module-reserved <code>splide--default</code>. Replace/ escape single quote (') with <b>& quot;</b> without spaces if any issues. Pay attention to the classes, single and double quotes. Quotes can be reversed as long as not uniformly single nor double quotes.") . '</p>';
    $suffix .= "<blockquote><pre>&lt;div class=&quot;splide" . $class . "&quot; data-splide=&quot;{'type': 'loop', 'arrows': true, 'pagination': true}&quot;&gt;
    &lt;div class=&quot;splide__slider&quot;&gt;
      &lt;div class=&quot;splide__track&quot;&gt;
        &lt;ul class=&quot;splide__list&quot;&gt;
          &lt;li class=&quot;splide__slide&quot;&gt;&lt;img src=&quot;https://drupal.org/files/One.gif&quot; /&gt;&lt;/li&gt;
          &lt;li class=&quot;splide__slide&quot;&gt;&lt;img src=&quot;https://drupal.org/files/Two.gif&quot; /&gt;&lt;/li&gt;
          &lt;li class=&quot;splide__slide&quot;&gt;&lt;img src=&quot;https://drupal.org/files/Three.gif&quot; /&gt;&lt;/li&gt;
        &lt;/ul&gt;
      &lt;/div&gt;
    &lt;/div&gt;
  &lt;/div&gt;</pre></blockquote>";

    return [
      '#type'     => 'inline_template',
      '#template' => '{{ prefix | raw }}{{ stage }}{{ suffix | raw }}',
      '#context'  => [
        'stage'  => $content,
        'prefix' => '<div style="background: rgb(52, 152, 219);"><div style="margin: 30px auto; max-width: 350px; min-height: 240px; text-align: center;" ' . $prefix . '>',
        'suffix' => '</div></div>' . $suffix,
      ],
      '#attached' => $attach,
    ];
  }

}
